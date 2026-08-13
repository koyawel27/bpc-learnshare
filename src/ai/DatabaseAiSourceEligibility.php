<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

use PDO;

final class DatabaseAiSourceEligibility implements AiSourceEligibility
{
    private const MAX_SOURCE_COUNT = 10;

    public function __construct(
        private readonly PDO $database,
        private readonly string $resourceStorageDirectory
    ) {
    }

    /**
     * @param list<array{resource_id: int, source_file_reference: string}> $references
     * @return list<array<string, mixed>>|null
     */
    public function revalidate(int $accountId, array $references): ?array
    {
        $references = $this->normalizeReferences($references);

        if ($accountId <= 0 || $references === null) {
            return null;
        }

        $accountStatement = $this->database->prepare(
            "SELECT id
             FROM accounts
             WHERE id = :id AND account_status = 'active'
             LIMIT 1"
        );
        $accountStatement->execute(['id' => $accountId]);

        if ($accountStatement->fetchColumn() === false) {
            return null;
        }

        $parameters = [];
        $placeholders = [];

        foreach ($references as $index => $reference) {
            $parameter = 'resource_' . $index;
            $placeholders[] = ':' . $parameter;
            $parameters[$parameter] = $reference['resource_id'];
        }

        $statement = $this->database->prepare(
            "SELECT
                id,
                title,
                file_type,
                stored_filename,
                file_size
             FROM resources
             WHERE id IN (" . implode(', ', $placeholders) . ")
               AND status = 'approved'
               AND file_availability = 'available'"
        );
        $statement->execute($parameters);

        $rowsById = [];

        foreach ($statement->fetchAll() as $row) {
            $rowsById[(int) $row['id']] = $row;
        }

        if (count($rowsById) !== count($references)) {
            return null;
        }

        $storageRoot = realpath($this->resourceStorageDirectory);

        if ($storageRoot === false) {
            return null;
        }

        $eligibleSources = [];

        foreach ($references as $reference) {
            $resourceId = $reference['resource_id'];
            $row = $rowsById[$resourceId] ?? null;

            if (!is_array($row)) {
                return null;
            }

            $storedFilename = (string) $row['stored_filename'];
            $fileType = (string) $row['file_type'];

            if (
                !hash_equals(
                    $storedFilename,
                    $reference['source_file_reference']
                )
                || preg_match(
                    '/\A[a-f0-9]{64}\.(pdf|docx|pptx|txt|jpg|png)\z/',
                    $storedFilename
                ) !== 1
                || !str_ends_with($storedFilename, '.' . $fileType)
            ) {
                return null;
            }

            $filePath = realpath(
                $this->resourceStorageDirectory
                . DIRECTORY_SEPARATOR
                . $storedFilename
            );

            if (
                $filePath === false
                || dirname($filePath) !== $storageRoot
                || !is_file($filePath)
                || filesize($filePath) !== (int) $row['file_size']
            ) {
                return null;
            }

            $eligibleSources[] = [
                'resource_id' => $resourceId,
                'title' => (string) $row['title'],
                'file_type' => $fileType,
                'source_file_reference' => $storedFilename,
            ];
        }

        return $eligibleSources;
    }

    /**
     * @param list<array{resource_id: int, source_file_reference: string}> $references
     * @return list<array{resource_id: int, source_file_reference: string}>|null
     */
    private function normalizeReferences(array $references): ?array
    {
        if (
            $references === []
            || count($references) > self::MAX_SOURCE_COUNT
            || !array_is_list($references)
        ) {
            return null;
        }

        $normalized = [];
        $seenResourceIds = [];

        foreach ($references as $reference) {
            if (!is_array($reference)) {
                return null;
            }

            $resourceId = $reference['resource_id'] ?? null;
            $sourceFileReference =
                $reference['source_file_reference'] ?? null;

            if (
                !is_int($resourceId)
                || $resourceId <= 0
                || isset($seenResourceIds[$resourceId])
                || !is_string($sourceFileReference)
                || $sourceFileReference === ''
            ) {
                return null;
            }

            $seenResourceIds[$resourceId] = true;
            $normalized[] = [
                'resource_id' => $resourceId,
                'source_file_reference' => $sourceFileReference,
            ];
        }

        return $normalized;
    }
}
