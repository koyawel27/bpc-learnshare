<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

use PDO;

/** Read-only SQL boundary for current, ready semantic-retrieval candidates. */
final class SemanticRetrievalRepository
{
    /** One extra row lets the service reject overflow instead of omitting silently. */
    private const MAX_CANDIDATES = 2001;
    private const EXTRACTION_CONFIGURATION_ID = 'EX-LOCAL-PHP-001';
    private const SEGMENTATION_CONFIGURATION_ID =
        'SEG-BLOCK-AWARE-CONTEXT-FIT-002';

    public function __construct(private readonly PDO $database)
    {
    }

    /** @return array<string, mixed>|null */
    public function findActiveRequester(int $accountId): ?array
    {
        $statement = $this->database->prepare(
            "SELECT id, role, account_status
             FROM accounts
             WHERE id = :id
               AND role IN ('student', 'teacher_instructor', 'moderator', 'admin')
               AND account_status = 'active'
             LIMIT 1"
        );
        $statement->execute(['id' => $accountId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
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
    public function findReadyCandidates(
        array $filters,
        string $embeddingConfigurationId,
        string $modelReference,
        string $modelDigest,
        int $dimension,
        int $limit = self::MAX_CANDIDATES
    ): array {
        $limit = max(1, min($limit, self::MAX_CANDIDATES));
        $conditions = [
            "r.status = 'approved'",
            "r.file_availability = 'available'",
            "sv.lifecycle_state = 'current'",
            'sv.current_marker = 1',
            'sv.stored_filename = r.stored_filename',
            'sv.file_size = r.file_size',
            "extraction.processing_status = 'ready'",
            'extraction.candidate_configuration_id = :extraction_configuration_id',
            "segmentation.processing_status = 'ready'",
            'segmentation.candidate_configuration_id = c.segmentation_configuration_id',
            'c.segmentation_configuration_id = :segmentation_configuration_id',
            "embedding.processing_status = 'ready'",
            'embedding.candidate_configuration_id = e.candidate_configuration_id',
            'e.candidate_configuration_id = :embedding_configuration_id',
            'e.model_reference = :model_reference',
            'e.model_digest = :model_digest',
            'e.dimension = :dimension',
        ];
        $parameters = [
            'embedding_configuration_id' => $embeddingConfigurationId,
            'extraction_configuration_id' => self::EXTRACTION_CONFIGURATION_ID,
            'segmentation_configuration_id' => self::SEGMENTATION_CONFIGURATION_ID,
            'model_reference' => $modelReference,
            'model_digest' => $modelDigest,
            'dimension' => $dimension,
        ];

        foreach (
            ['course_id', 'subject_id', 'year_level_id', 'resource_type_id']
            as $field
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
                r.id AS resource_id,
                r.title,
                r.description,
                r.topic,
                r.file_type,
                r.file_size,
                r.stored_filename,
                r.view_count,
                r.download_count,
                r.created_at,
                a.display_name AS uploader_name,
                course.name AS course_name,
                subject.name AS subject_name,
                year_level.name AS year_level_name,
                resource_type.name AS resource_type_name,
                (
                    SELECT GROUP_CONCAT(
                        DISTINCT tag.name ORDER BY tag.name SEPARATOR "||"
                    )
                    FROM resource_tags resource_tag
                    INNER JOIN tags tag ON tag.id = resource_tag.tag_id
                    WHERE resource_tag.resource_id = r.id
                ) AS tag_names,
                sv.id AS source_version_id,
                sv.source_sha256,
                c.id AS chunk_id,
                c.chunk_index,
                c.chunk_text,
                c.text_sha256,
                c.character_count,
                c.locator_kind,
                c.start_locator,
                c.end_locator,
                c.locator_label,
                c.segmentation_configuration_id,
                e.candidate_configuration_id,
                e.model_reference,
                e.model_digest,
                e.dimension,
                e.vector_json,
                e.vector_norm,
                e.vector_sha256
             FROM resources r
             INNER JOIN accounts a ON a.id = r.uploader_id
             INNER JOIN courses course ON course.id = r.course_id
             INNER JOIN subjects subject ON subject.id = r.subject_id
             INNER JOIN year_levels year_level ON year_level.id = r.year_level_id
             INNER JOIN resource_types resource_type
                ON resource_type.id = r.resource_type_id
             INNER JOIN ai_source_versions sv ON sv.resource_id = r.id
             INNER JOIN ai_processing_states extraction
                ON extraction.source_version_id = sv.id
               AND extraction.capability = \'extraction\'
             INNER JOIN ai_processing_states segmentation
                ON segmentation.source_version_id = sv.id
               AND segmentation.capability = \'segmentation\'
             INNER JOIN ai_processing_states embedding
                ON embedding.source_version_id = sv.id
               AND embedding.capability = \'embedding\'
             INNER JOIN ai_chunks c ON c.source_version_id = sv.id
             INNER JOIN ai_embeddings e ON e.chunk_id = c.id
             WHERE ' . implode(' AND ', $conditions) . '
             ORDER BY r.id, c.chunk_index
             LIMIT ' . $limit
        );
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function candidateRemainsEligible(
        int $chunkId,
        int $resourceId,
        int $sourceVersionId,
        string $segmentationConfigurationId,
        string $embeddingConfigurationId,
        string $modelReference,
        string $modelDigest,
        int $dimension
    ): bool {
        $statement = $this->database->prepare(
            "SELECT 1
             FROM resources r
             INNER JOIN ai_source_versions sv
                ON sv.resource_id = r.id
             INNER JOIN ai_chunks c
                ON c.source_version_id = sv.id
             INNER JOIN ai_embeddings e
                ON e.chunk_id = c.id
             INNER JOIN ai_processing_states extraction
                ON extraction.source_version_id = sv.id
               AND extraction.capability = 'extraction'
             INNER JOIN ai_processing_states segmentation
                ON segmentation.source_version_id = sv.id
               AND segmentation.capability = 'segmentation'
             INNER JOIN ai_processing_states embedding
                ON embedding.source_version_id = sv.id
               AND embedding.capability = 'embedding'
             WHERE r.id = :resource_id
               AND r.status = 'approved'
               AND r.file_availability = 'available'
               AND sv.id = :source_version_id
               AND sv.lifecycle_state = 'current'
               AND sv.current_marker = 1
               AND sv.stored_filename = r.stored_filename
               AND sv.file_size = r.file_size
               AND c.id = :chunk_id
               AND c.segmentation_configuration_id = :segmentation_configuration_id
               AND extraction.processing_status = 'ready'
               AND extraction.candidate_configuration_id = :extraction_configuration_id
               AND segmentation.processing_status = 'ready'
               AND segmentation.candidate_configuration_id = c.segmentation_configuration_id
               AND c.segmentation_configuration_id = :accepted_segmentation_configuration_id
               AND embedding.processing_status = 'ready'
               AND embedding.candidate_configuration_id = e.candidate_configuration_id
               AND e.candidate_configuration_id = :embedding_configuration_id
               AND e.model_reference = :model_reference
               AND e.model_digest = :model_digest
               AND e.dimension = :dimension
             LIMIT 1"
        );
        $statement->execute([
            'resource_id' => $resourceId,
            'source_version_id' => $sourceVersionId,
            'chunk_id' => $chunkId,
            'segmentation_configuration_id' => $segmentationConfigurationId,
            'extraction_configuration_id' => self::EXTRACTION_CONFIGURATION_ID,
            'accepted_segmentation_configuration_id' => self::SEGMENTATION_CONFIGURATION_ID,
            'embedding_configuration_id' => $embeddingConfigurationId,
            'model_reference' => $modelReference,
            'model_digest' => $modelDigest,
            'dimension' => $dimension,
        ]);

        return $statement->fetchColumn() !== false;
    }
}
