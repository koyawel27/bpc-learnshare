<?php

declare(strict_types=1);

namespace BpcLearnShare\Resource;

use PDO;

final class TaxonomyRepository
{
    private const TABLES = [
        'courses' => 'courses',
        'subjects' => 'subjects',
        'year_levels' => 'year_levels',
        'resource_types' => 'resource_types',
        'tags' => 'tags',
    ];

    public function __construct(private readonly PDO $database)
    {
    }

    /**
     * @return array<string, list<array{id: int, name: string}>>
     */
    public function activeOptions(): array
    {
        $options = [];

        foreach (self::TABLES as $key => $table) {
            $statement = $this->database->query(
                "SELECT id, name
                 FROM {$table}
                 WHERE is_active = 1
                 ORDER BY name"
            );
            $rows = $statement->fetchAll();
            $options[$key] = array_map(
                static fn (array $row): array => [
                    'id' => (int) $row['id'],
                    'name' => (string) $row['name'],
                ],
                $rows
            );
        }

        return $options;
    }

    /**
     * @param array<string, mixed> $resource
     * @return array<string, string>
     */
    public function selectionErrors(array $resource): array
    {
        $errors = [];
        $singleSelections = [
            'course_id' => ['courses', 'course/program'],
            'subject_id' => ['subjects', 'subject'],
            'year_level_id' => ['year_levels', 'year level'],
            'resource_type_id' => ['resource_types', 'resource type'],
        ];

        foreach ($singleSelections as $field => [$tableKey, $label]) {
            if (!$this->activeIdExists($tableKey, (int) $resource[$field])) {
                $errors[$field] = 'The selected ' . $label . ' is unavailable.';
            }
        }

        $tagIds = $resource['tag_ids'];

        if ($tagIds !== [] && !$this->allActiveIdsExist('tags', $tagIds)) {
            $errors['tag_ids'] = 'One or more selected tags are unavailable.';
        }

        return $errors;
    }

    private function activeIdExists(string $tableKey, int $id): bool
    {
        $table = self::TABLES[$tableKey];
        $statement = $this->database->prepare(
            "SELECT 1 FROM {$table} WHERE id = :id AND is_active = 1"
        );
        $statement->execute(['id' => $id]);

        return $statement->fetchColumn() !== false;
    }

    /**
     * @param list<int> $ids
     */
    private function allActiveIdsExist(string $tableKey, array $ids): bool
    {
        $table = self::TABLES[$tableKey];
        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $statement = $this->database->prepare(
            "SELECT COUNT(*)
             FROM {$table}
             WHERE is_active = 1 AND id IN ({$placeholders})"
        );
        $statement->execute($ids);

        return (int) $statement->fetchColumn() === count($ids);
    }
}