<?php

declare(strict_types=1);

namespace BpcLearnShare\Resource;

use PDO;

final class ResourceDiscoveryRepository
{
    public function __construct(private readonly PDO $database)
    {
    }

    /**
     * @param array{
     *     q: string,
     *     course_id: int,
     *     subject_id: int,
     *     year_level_id: int,
     *     resource_type_id: int,
     *     tag_id: int
     * } $filters
     * @return list<array<string, mixed>>
     */
    public function search(array $filters): array
    {
        $conditions = [
            "r.status = 'approved'",
            "r.file_availability = 'available'",
        ];
        $parameters = [];

        if ($filters['q'] !== '') {
            $conditions[] = '(
                LOCATE(:query_title, r.title) > 0
                OR LOCATE(:query_topic, r.topic) > 0
                OR LOCATE(:query_description, r.description) > 0
            )';
            $parameters['query_title'] = $filters['q'];
            $parameters['query_topic'] = $filters['q'];
            $parameters['query_description'] = $filters['q'];
        }

        foreach (
            [
                'course_id',
                'subject_id',
                'year_level_id',
                'resource_type_id',
            ] as $field
        ) {
            if ($filters[$field] > 0) {
                $conditions[] = "r.{$field} = :{$field}";
                $parameters[$field] = $filters[$field];
            }
        }

        if ($filters['tag_id'] > 0) {
            $conditions[] = 'EXISTS (
                SELECT 1
                FROM resource_tags selected_tag
                WHERE selected_tag.resource_id = r.id
                  AND selected_tag.tag_id = :tag_id
            )';
            $parameters['tag_id'] = $filters['tag_id'];
        }

        $statement = $this->database->prepare(
            'SELECT
                r.id,
                r.title,
                r.description,
                r.topic,
                r.file_type,
                r.file_size,
                r.view_count,
                r.download_count,
                r.created_at,
                a.display_name AS uploader_name,
                c.name AS course_name,
                s.name AS subject_name,
                y.name AS year_level_name,
                rt.name AS resource_type_name,
                GROUP_CONCAT(
                    DISTINCT t.name
                    ORDER BY t.name
                    SEPARATOR "||"
                ) AS tag_names
             FROM resources r
             INNER JOIN accounts a ON a.id = r.uploader_id
             INNER JOIN courses c ON c.id = r.course_id
             INNER JOIN subjects s ON s.id = r.subject_id
             INNER JOIN year_levels y ON y.id = r.year_level_id
             INNER JOIN resource_types rt ON rt.id = r.resource_type_id
             LEFT JOIN resource_tags resource_tag
                ON resource_tag.resource_id = r.id
             LEFT JOIN tags t ON t.id = resource_tag.tag_id
             WHERE ' . implode(' AND ', $conditions) . '
             GROUP BY
                r.id,
                r.title,
                r.description,
                r.topic,
                r.file_type,
                r.file_size,
                r.view_count,
                r.download_count,
                r.created_at,
                a.display_name,
                c.name,
                s.name,
                y.name,
                rt.name
             ORDER BY r.created_at DESC, r.id DESC
             LIMIT 100'
        );
        $statement->execute($parameters);

        return array_map(
            [$this, 'normalizeTags'],
            $statement->fetchAll()
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    public function openAvailableApproved(int $resourceId): ?array
    {
        $update = $this->database->prepare(
            "UPDATE resources
             SET view_count = view_count + 1
             WHERE id = :id
               AND status = 'approved'
               AND file_availability = 'available'"
        );
        $update->execute(['id' => $resourceId]);

        if ($update->rowCount() !== 1) {
            return null;
        }

        $statement = $this->database->prepare(
            "SELECT
                r.id,
                r.title,
                r.description,
                r.topic,
                r.original_filename,
                r.file_type,
                r.file_size,
                r.view_count,
                r.download_count,
                r.created_at,
                a.display_name AS uploader_name,
                c.name AS course_name,
                s.name AS subject_name,
                y.name AS year_level_name,
                rt.name AS resource_type_name,
                GROUP_CONCAT(
                    DISTINCT t.name
                    ORDER BY t.name
                    SEPARATOR '||'
                ) AS tag_names
             FROM resources r
             INNER JOIN accounts a ON a.id = r.uploader_id
             INNER JOIN courses c ON c.id = r.course_id
             INNER JOIN subjects s ON s.id = r.subject_id
             INNER JOIN year_levels y ON y.id = r.year_level_id
             INNER JOIN resource_types rt ON rt.id = r.resource_type_id
             LEFT JOIN resource_tags resource_tag
                ON resource_tag.resource_id = r.id
             LEFT JOIN tags t ON t.id = resource_tag.tag_id
             WHERE r.id = :id
               AND r.status = 'approved'
               AND r.file_availability = 'available'
             GROUP BY
                r.id,
                r.title,
                r.description,
                r.topic,
                r.original_filename,
                r.file_type,
                r.file_size,
                r.view_count,
                r.download_count,
                r.created_at,
                a.display_name,
                c.name,
                s.name,
                y.name,
                rt.name
             LIMIT 1"
        );
        $statement->execute(['id' => $resourceId]);
        $resource = $statement->fetch();

        return is_array($resource)
            ? $this->normalizeTags($resource)
            : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function availableDownload(int $resourceId): ?array
    {
        $statement = $this->database->prepare(
            "SELECT
                id,
                original_filename,
                stored_filename,
                file_type,
                file_size
             FROM resources
             WHERE id = :id
               AND status = 'approved'
               AND file_availability = 'available'
             LIMIT 1"
        );
        $statement->execute(['id' => $resourceId]);
        $file = $statement->fetch();

        return is_array($file) ? $file : null;
    }

    public function recordDownload(int $resourceId): bool
    {
        $statement = $this->database->prepare(
            "UPDATE resources
             SET download_count = download_count + 1
             WHERE id = :id
               AND status = 'approved'
               AND file_availability = 'available'"
        );
        $statement->execute(['id' => $resourceId]);

        return $statement->rowCount() === 1;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeTags(array $row): array
    {
        $tagNames = (string) ($row['tag_names'] ?? '');
        $row['tags'] = $tagNames === ''
            ? []
            : explode('||', $tagNames);
        unset($row['tag_names']);

        return $row;
    }
}
