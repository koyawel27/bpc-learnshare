<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

use PDO;

final class DatabaseRelatedResourceMetadata
{
    private const MAX_SUGGESTIONS = 5;
    private const CANDIDATE_SCAN_LIMIT = 25;

    public function __construct(
        private readonly PDO $database,
        private readonly AiSourceEligibility $eligibility,
        private readonly SourceAttributionPresenter $presenter
    ) {
    }

    /** @return array<string, mixed> */
    public function suggest(
        int $accountId,
        int $targetResourceId,
        int $limit = self::MAX_SUGGESTIONS
    ): array {
        if (
            $accountId <= 0
            || $targetResourceId <= 0
            || $limit < 1
            || $limit > self::MAX_SUGGESTIONS
        ) {
            return $this->unavailable('invalid_request');
        }

        $targetReference = $this->resourceReference($targetResourceId);

        if (
            $targetReference === null
            || $this->eligibility->revalidate(
                $accountId,
                [$targetReference]
            ) === null
        ) {
            return $this->unavailable('target_ineligible');
        }

        $references = $this->sharedActiveTagReferences($targetResourceId);
        $suggestions = [];

        foreach ($references as $reference) {
            $eligible = $this->eligibility->revalidate(
                $accountId,
                [$reference]
            );

            if (!is_array($eligible) || count($eligible) !== 1) {
                continue;
            }

            $presented = $this->presenter->present($eligible);

            if (count($presented) !== 1) {
                continue;
            }

            $suggestions[] = $presented[0];

            if (count($suggestions) >= $limit) {
                break;
            }
        }

        if ($suggestions === []) {
            return $this->unavailable('no_useful_related_resource');
        }

        return [
            'status' => 'available',
            'reason_code' => null,
            'message' => null,
            'matching_method' => 'metadata_shared_active_tag',
            'suggestions' => $suggestions,
        ];
    }

    /**
     * @return array{resource_id: int, source_file_reference: string}|null
     */
    private function resourceReference(int $resourceId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, stored_filename
             FROM resources
             WHERE id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $resourceId]);
        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return [
            'resource_id' => (int) $row['id'],
            'source_file_reference' => (string) $row['stored_filename'],
        ];
    }

    /**
     * @return list<array{resource_id: int, source_file_reference: string}>
     */
    private function sharedActiveTagReferences(int $targetResourceId): array
    {
        $statement = $this->database->prepare(
            "SELECT
                candidate.id AS resource_id,
                candidate.stored_filename AS source_file_reference,
                COUNT(DISTINCT target_tag.tag_id) AS shared_tag_count
             FROM resource_tags target_tag
             INNER JOIN tags active_tag
                ON active_tag.id = target_tag.tag_id
               AND active_tag.is_active = 1
             INNER JOIN resource_tags candidate_tag
                ON candidate_tag.tag_id = target_tag.tag_id
             INNER JOIN resources candidate
                ON candidate.id = candidate_tag.resource_id
             WHERE target_tag.resource_id = :target_resource_id
               AND candidate.id <> :excluded_resource_id
               AND candidate.status = 'approved'
               AND candidate.file_availability = 'available'
             GROUP BY candidate.id, candidate.stored_filename
             ORDER BY shared_tag_count DESC, candidate.id ASC
             LIMIT " . self::CANDIDATE_SCAN_LIMIT
        );
        $statement->execute([
            'target_resource_id' => $targetResourceId,
            'excluded_resource_id' => $targetResourceId,
        ]);

        return array_map(
            static fn (array $row): array => [
                'resource_id' => (int) $row['resource_id'],
                'source_file_reference' =>
                    (string) $row['source_file_reference'],
            ],
            $statement->fetchAll()
        );
    }

    /** @return array<string, mixed> */
    private function unavailable(string $reasonCode): array
    {
        return [
            'status' => 'unavailable',
            'reason_code' => $reasonCode,
            'message' => 'No useful related resource is currently available.',
            'matching_method' => 'metadata_shared_active_tag',
            'suggestions' => [],
        ];
    }
}
