<?php

declare(strict_types=1);

namespace BpcLearnShare\Resource;

use PDO;
use RuntimeException;
use Throwable;

final class ResourceRepository
{
    private const TAXONOMY_TABLES = [
        'course_id' => 'courses',
        'subject_id' => 'subjects',
        'year_level_id' => 'year_levels',
        'resource_type_id' => 'resource_types',
    ];

    public function __construct(private readonly PDO $database)
    {
    }

    /**
     * @param array<string, mixed> $resource
     * @param array<string, mixed> $file
     */
    public function createPending(
        int $uploaderId,
        array $resource,
        array $file,
        string $storedFilename
    ): int {
        $this->database->beginTransaction();

        try {
            $this->lockEligibleUploader($uploaderId);

            foreach (self::TAXONOMY_TABLES as $field => $table) {
                $this->lockActiveTaxonomy($table, (int) $resource[$field]);
            }

            foreach ($resource['tag_ids'] as $tagId) {
                $this->lockActiveTaxonomy('tags', (int) $tagId);
            }

            $statement = $this->database->prepare(
                'INSERT INTO resources (
                    uploader_id,
                    title,
                    description,
                    topic,
                    course_id,
                    subject_id,
                    year_level_id,
                    resource_type_id,
                    status,
                    original_filename,
                    stored_filename,
                    file_type,
                    file_size,
                    file_availability,
                    replaces_resource_id,
                    ai_notice_acknowledged,
                    ai_notice_acknowledged_at
                ) VALUES (
                    :uploader_id,
                    :title,
                    :description,
                    :topic,
                    :course_id,
                    :subject_id,
                    :year_level_id,
                    :resource_type_id,
                    :status,
                    :original_filename,
                    :stored_filename,
                    :file_type,
                    :file_size,
                    :file_availability,
                    NULL,
                    0,
                    NULL
                )'
            );
            $statement->execute([
                'uploader_id' => $uploaderId,
                'title' => $resource['title'],
                'description' => $resource['description'],
                'topic' => $resource['topic'],
                'course_id' => $resource['course_id'],
                'subject_id' => $resource['subject_id'],
                'year_level_id' => $resource['year_level_id'],
                'resource_type_id' => $resource['resource_type_id'],
                'status' => 'pending',
                'original_filename' => $file['original_filename'],
                'stored_filename' => $storedFilename,
                'file_type' => $file['file_type'],
                'file_size' => $file['file_size'],
                'file_availability' => 'available',
            ]);

            $resourceId = (int) $this->database->lastInsertId();

            if ($resourceId < 1) {
                throw new RuntimeException('Resource creation returned no ID.');
            }

            if ($resource['tag_ids'] !== []) {
                $tagStatement = $this->database->prepare(
                    'INSERT INTO resource_tags (resource_id, tag_id)
                     VALUES (:resource_id, :tag_id)'
                );

                foreach ($resource['tag_ids'] as $tagId) {
                    $tagStatement->execute([
                        'resource_id' => $resourceId,
                        'tag_id' => $tagId,
                    ]);
                }
            }

            $this->database->commit();

            return $resourceId;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }
    }

    private function lockEligibleUploader(int $uploaderId): void
    {
        $statement = $this->database->prepare(
            'SELECT role, account_status
             FROM accounts
             WHERE id = :id
             FOR UPDATE'
        );
        $statement->execute(['id' => $uploaderId]);
        $account = $statement->fetch();

        if (!is_array($account)
            || $account['account_status'] !== 'active'
            || !in_array(
                (string) $account['role'],
                ['student', 'teacher_instructor'],
                true
            )
        ) {
            throw new RuntimeException(
                'The uploader account is no longer eligible to submit resources.'
            );
        }
    }

    private function lockActiveTaxonomy(string $table, int $id): void
    {
        $allowedTables = array_merge(
            array_values(self::TAXONOMY_TABLES),
            ['tags']
        );

        if (!in_array($table, $allowedTables, true)) {
            throw new RuntimeException('Unsupported taxonomy table.');
        }

        $statement = $this->database->prepare(
            "SELECT id
             FROM {$table}
             WHERE id = :id AND is_active = 1
             FOR UPDATE"
        );
        $statement->execute(['id' => $id]);

        if ($statement->fetchColumn() === false) {
            throw new RuntimeException(
                'Selected metadata became unavailable. Please review the form.'
            );
        }
    }
}