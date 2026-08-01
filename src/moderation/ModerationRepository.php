<?php

declare(strict_types=1);

namespace BpcLearnShare\Moderation;

use PDO;
use Throwable;

final class ModerationRepository
{
    public function __construct(private readonly PDO $database)
    {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function pendingQueue(): array
    {
        $statement = $this->database->query(
            "SELECT
                r.id,
                r.title,
                r.topic,
                r.file_type,
                r.file_size,
                r.created_at,
                a.display_name AS uploader_name,
                a.role AS uploader_role,
                c.name AS course_name,
                s.name AS subject_name,
                y.name AS year_level_name,
                rt.name AS resource_type_name
             FROM resources r
             INNER JOIN accounts a ON a.id = r.uploader_id
             INNER JOIN courses c ON c.id = r.course_id
             INNER JOIN subjects s ON s.id = r.subject_id
             INNER JOIN year_levels y ON y.id = r.year_level_id
             INNER JOIN resource_types rt ON rt.id = r.resource_type_id
             WHERE r.status = 'pending'
             ORDER BY r.created_at ASC, r.id ASC"
        );

        return $statement->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function pendingResource(int $resourceId): ?array
    {
        $statement = $this->database->prepare(
            "SELECT
                r.*,
                a.username AS uploader_username,
                a.display_name AS uploader_name,
                a.role AS uploader_role,
                c.name AS course_name,
                s.name AS subject_name,
                y.name AS year_level_name,
                rt.name AS resource_type_name
             FROM resources r
             INNER JOIN accounts a ON a.id = r.uploader_id
             INNER JOIN courses c ON c.id = r.course_id
             INNER JOIN subjects s ON s.id = r.subject_id
             INNER JOIN year_levels y ON y.id = r.year_level_id
             INNER JOIN resource_types rt ON rt.id = r.resource_type_id
             WHERE r.id = :id AND r.status = 'pending'
             LIMIT 1"
        );
        $statement->execute(['id' => $resourceId]);
        $resource = $statement->fetch();

        if (!is_array($resource)) {
            return null;
        }

        $resource['tags'] = $this->tagsForResource($resourceId);
        $resource['history'] = $this->historyForResource($resourceId);

        return $resource;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function pendingFile(int $resourceId): ?array
    {
        $statement = $this->database->prepare(
            "SELECT
                id,
                original_filename,
                stored_filename,
                file_type,
                file_size,
                file_availability
             FROM resources
             WHERE id = :id AND status = 'pending'
             LIMIT 1"
        );
        $statement->execute(['id' => $resourceId]);
        $file = $statement->fetch();

        return is_array($file) ? $file : null;
    }

    public function applyDecision(
        int $actorId,
        int $resourceId,
        string $action,
        string $note
    ): string {
        $inputErrors = ModerationInput::validate([
            'action' => $action,
            'note' => $note,
        ]);

        if ($inputErrors !== []) {
            throw new ModerationDecisionException(
                (string) reset($inputErrors),
                'invalid_decision_input'
            );
        }

        $statusByAction = [
            'approve' => 'approved',
            'reject' => 'rejected',
            'request_correction' => 'needs_correction',
        ];

        if (!array_key_exists($action, $statusByAction)) {
            throw new ModerationDecisionException(
                'That moderation decision is not supported.',
                'unsupported_action'
            );
        }

        $this->database->beginTransaction();

        try {
            $this->lockEligibleActor($actorId);

            $resourceStatement = $this->database->prepare(
                'SELECT id, status, replaces_resource_id
                 FROM resources
                 WHERE id = :id
                 FOR UPDATE'
            );
            $resourceStatement->execute(['id' => $resourceId]);
            $resource = $resourceStatement->fetch();

            if (!is_array($resource)) {
                throw new ModerationDecisionException(
                    'The requested resource no longer exists.',
                    'missing_resource',
                    404
                );
            }

            if ((string) $resource['status'] !== 'pending') {
                throw new ModerationDecisionException(
                    'This resource is no longer Pending. No decision was applied.',
                    'stale_status',
                    409
                );
            }

            if ($resource['replaces_resource_id'] !== null) {
                throw new ModerationDecisionException(
                    'Linked replacement moderation is not available in this prototype yet.',
                    'replacement_not_implemented',
                    409
                );
            }

            $statusAfter = $statusByAction[$action];
            $updateStatement = $this->database->prepare(
                "UPDATE resources
                 SET status = :status_after
                 WHERE id = :id AND status = 'pending'"
            );
            $updateStatement->execute([
                'status_after' => $statusAfter,
                'id' => $resourceId,
            ]);

            if ($updateStatement->rowCount() !== 1) {
                throw new ModerationDecisionException(
                    'The resource changed while it was being reviewed. No decision was applied.',
                    'concurrent_status_change',
                    409
                );
            }

            $historyStatement = $this->database->prepare(
                'INSERT INTO resource_action_history (
                    resource_id,
                    actor_account_id,
                    action_type,
                    status_before,
                    status_after,
                    note,
                    related_report_id
                 ) VALUES (
                    :resource_id,
                    :actor_account_id,
                    :action_type,
                    :status_before,
                    :status_after,
                    :note,
                    NULL
                 )'
            );
            $historyStatement->execute([
                'resource_id' => $resourceId,
                'actor_account_id' => $actorId,
                'action_type' => $action,
                'status_before' => 'pending',
                'status_after' => $statusAfter,
                'note' => $note === '' ? null : $note,
            ]);

            $this->database->commit();

            return $statusAfter;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }
    }

    private function lockEligibleActor(int $actorId): void
    {
        $statement = $this->database->prepare(
            'SELECT role, account_status
             FROM accounts
             WHERE id = :id
             FOR UPDATE'
        );
        $statement->execute(['id' => $actorId]);
        $actor = $statement->fetch();

        if (
            !is_array($actor)
            || (string) $actor['account_status'] !== 'active'
            || !in_array(
                (string) $actor['role'],
                ['moderator', 'admin'],
                true
            )
        ) {
            throw new ModerationDecisionException(
                'This account is no longer allowed to moderate resources.',
                'actor_not_eligible',
                403
            );
        }
    }

    /**
     * @return list<string>
     */
    private function tagsForResource(int $resourceId): array
    {
        $statement = $this->database->prepare(
            'SELECT t.name
             FROM resource_tags rt
             INNER JOIN tags t ON t.id = rt.tag_id
             WHERE rt.resource_id = :resource_id
             ORDER BY t.name ASC'
        );
        $statement->execute(['resource_id' => $resourceId]);

        return array_map(
            static fn (array $row): string => (string) $row['name'],
            $statement->fetchAll()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function historyForResource(int $resourceId): array
    {
        $statement = $this->database->prepare(
            'SELECT
                h.action_type,
                h.status_before,
                h.status_after,
                h.note,
                h.created_at,
                a.display_name AS actor_name
             FROM resource_action_history h
             LEFT JOIN accounts a ON a.id = h.actor_account_id
             WHERE h.resource_id = :resource_id
             ORDER BY h.created_at DESC, h.id DESC'
        );
        $statement->execute(['resource_id' => $resourceId]);

        return $statement->fetchAll();
    }
}
