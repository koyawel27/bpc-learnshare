<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

use PDO;

/**
 * SQL-only persistence operations for D043 derived data.
 *
 * Transaction ownership, validation, live eligibility checks, and run-token
 * decisions belong to GuardedAiPersistenceProcessor.
 */
final class AiPersistenceRepository
{
    public function __construct(private readonly PDO $database)
    {
    }

    /** @return array<string, mixed>|null */
    public function findAuthorizedProcessingActor(
        int $accountId,
        bool $lock = false
    ): ?array {
        $statement = $this->database->prepare(
            "SELECT id, role, account_status
             FROM accounts
             WHERE id = :id
               AND role IN ('moderator', 'admin')
               AND account_status = 'active'
             LIMIT 1" . ($lock ? ' FOR UPDATE' : '')
        );
        $statement->execute(['id' => $accountId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findResource(int $resourceId, bool $lock = false): ?array
    {
        $statement = $this->database->prepare(
            'SELECT
                id,
                status,
                stored_filename,
                file_type,
                file_size,
                file_availability
             FROM resources
             WHERE id = :id
             LIMIT 1' . ($lock ? ' FOR UPDATE' : '')
        );
        $statement->execute(['id' => $resourceId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findCurrentSource(
        int $resourceId,
        bool $lock = false
    ): ?array {
        $statement = $this->database->prepare(
            "SELECT *
             FROM ai_source_versions
             WHERE resource_id = :resource_id
               AND lifecycle_state = 'current'
               AND current_marker = 1
             LIMIT 1" . ($lock ? ' FOR UPDATE' : '')
        );
        $statement->execute(['resource_id' => $resourceId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findSourceByHash(
        int $resourceId,
        string $sourceSha256,
        bool $lock = false
    ): ?array {
        $statement = $this->database->prepare(
            'SELECT *
             FROM ai_source_versions
             WHERE resource_id = :resource_id
               AND source_sha256 = :source_sha256
             LIMIT 1' . ($lock ? ' FOR UPDATE' : '')
        );
        $statement->execute([
            'resource_id' => $resourceId,
            'source_sha256' => $sourceSha256,
        ]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    public function findSource(int $sourceVersionId, bool $lock = false): ?array
    {
        $statement = $this->database->prepare(
            'SELECT
                source.*,
                resource.status AS resource_status,
                resource.stored_filename AS resource_stored_filename,
                resource.file_type AS resource_file_type,
                resource.file_size AS resource_file_size,
                resource.file_availability AS resource_file_availability
             FROM ai_source_versions AS source
             INNER JOIN resources AS resource
                ON resource.id = source.resource_id
             WHERE source.id = :id
             LIMIT 1' . ($lock ? ' FOR UPDATE' : '')
        );
        $statement->execute(['id' => $sourceVersionId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function nextSourceVersionNumber(int $resourceId): int
    {
        $statement = $this->database->prepare(
            'SELECT COALESCE(MAX(source_version_number), 0) + 1
             FROM ai_source_versions
             WHERE resource_id = :resource_id'
        );
        $statement->execute(['resource_id' => $resourceId]);

        return (int) $statement->fetchColumn();
    }

    /** @param array<string, int|string> $source */
    public function insertSource(array $source): int
    {
        $statement = $this->database->prepare(
            "INSERT INTO ai_source_versions (
                resource_id,
                source_version_number,
                source_sha256,
                stored_filename,
                file_size,
                detected_mime_type,
                lifecycle_state,
                current_marker
             ) VALUES (
                :resource_id,
                :source_version_number,
                :source_sha256,
                :stored_filename,
                :file_size,
                :detected_mime_type,
                'current',
                1
             )"
        );
        $statement->execute($source);

        return (int) $this->database->lastInsertId();
    }

    public function markSourceNoncurrent(int $sourceVersionId, string $state): void
    {
        $statement = $this->database->prepare(
            'UPDATE ai_source_versions
             SET lifecycle_state = :lifecycle_state,
                 current_marker = NULL,
                 became_noncurrent_at = CURRENT_TIMESTAMP
             WHERE id = :id
               AND lifecycle_state = \'current\'
               AND current_marker = 1'
        );
        $statement->execute([
            'lifecycle_state' => $state,
            'id' => $sourceVersionId,
        ]);
    }

    public function markProcessingStatesStale(int $sourceVersionId): void
    {
        $statement = $this->database->prepare(
            "UPDATE ai_processing_states
             SET processing_status = 'stale',
                 run_token = NULL,
                 queued_at = NULL,
                 started_at = NULL,
                 completed_at = CURRENT_TIMESTAMP,
                 last_error_code = NULL,
                 last_error_summary = NULL
             WHERE source_version_id = :source_version_id"
        );
        $statement->execute(['source_version_id' => $sourceVersionId]);
    }

    /** @param list<string> $capabilities */
    public function markCapabilitiesStale(
        int $sourceVersionId,
        array $capabilities
    ): void {
        if ($capabilities === []) {
            return;
        }

        $parameters = ['source_version_id' => $sourceVersionId];
        $placeholders = [];

        foreach ($capabilities as $index => $capability) {
            $parameter = 'capability_' . $index;
            $parameters[$parameter] = $capability;
            $placeholders[] = ':' . $parameter;
        }

        $statement = $this->database->prepare(
            "UPDATE ai_processing_states
             SET processing_status = 'stale',
                 run_token = NULL,
                 queued_at = NULL,
                 started_at = NULL,
                 completed_at = CURRENT_TIMESTAMP,
                 last_error_code = NULL,
                 last_error_summary = NULL
             WHERE source_version_id = :source_version_id
               AND capability IN (" . implode(', ', $placeholders) . ')'
        );
        $statement->execute($parameters);
    }

    public function deleteSourceForReprocessing(int $sourceVersionId): void
    {
        $output = $this->database->prepare(
            "UPDATE ai_outputs
             SET lifecycle_state = 'invalidated',
                 source_version_id = NULL
             WHERE source_version_id = :source_version_id"
        );
        $output->execute(['source_version_id' => $sourceVersionId]);

        $source = $this->database->prepare(
            'DELETE FROM ai_source_versions WHERE id = :id'
        );
        $source->execute(['id' => $sourceVersionId]);
    }

    public function invalidateOutputs(int $resourceId): void
    {
        $statement = $this->database->prepare(
            "UPDATE ai_outputs
             SET lifecycle_state = 'invalidated'
             WHERE resource_id = :resource_id
               AND lifecycle_state IN ('draft', 'retained')"
        );
        $statement->execute(['resource_id' => $resourceId]);
    }

    /** @return array<string, mixed>|null */
    public function findProcessingState(
        int $sourceVersionId,
        string $capability,
        bool $lock = false
    ): ?array {
        $statement = $this->database->prepare(
            'SELECT *
             FROM ai_processing_states
             WHERE source_version_id = :source_version_id
               AND capability = :capability
             LIMIT 1' . ($lock ? ' FOR UPDATE' : '')
        );
        $statement->execute([
            'source_version_id' => $sourceVersionId,
            'capability' => $capability,
        ]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    public function queueRun(
        int $sourceVersionId,
        string $capability,
        string $configurationId,
        string $dependencyFingerprint,
        string $runToken
    ): void {
        $statement = $this->database->prepare(
            "INSERT INTO ai_processing_states (
                source_version_id,
                capability,
                processing_status,
                candidate_configuration_id,
                dependency_fingerprint,
                run_token,
                attempt_count,
                queued_at,
                started_at,
                completed_at,
                last_error_code,
                last_error_summary
             ) VALUES (
                :source_version_id,
                :capability,
                'queued',
                :candidate_configuration_id,
                :dependency_fingerprint,
                :run_token,
                1,
                CURRENT_TIMESTAMP,
                NULL,
                NULL,
                NULL,
                NULL
             )
             ON DUPLICATE KEY UPDATE
                processing_status = 'queued',
                candidate_configuration_id = VALUES(candidate_configuration_id),
                dependency_fingerprint = VALUES(dependency_fingerprint),
                run_token = VALUES(run_token),
                attempt_count = attempt_count + 1,
                queued_at = CURRENT_TIMESTAMP,
                started_at = NULL,
                completed_at = NULL,
                last_error_code = NULL,
                last_error_summary = NULL"
        );
        $statement->execute([
            'source_version_id' => $sourceVersionId,
            'capability' => $capability,
            'candidate_configuration_id' => $configurationId,
            'dependency_fingerprint' => $dependencyFingerprint,
            'run_token' => $runToken,
        ]);
    }

    public function markRunProcessing(
        int $sourceVersionId,
        string $capability,
        string $runToken
    ): int {
        $statement = $this->database->prepare(
            "UPDATE ai_processing_states
             SET processing_status = 'processing',
                 started_at = CURRENT_TIMESTAMP,
                 completed_at = NULL
             WHERE source_version_id = :source_version_id
               AND capability = :capability
               AND processing_status = 'queued'
               AND run_token = :run_token"
        );
        $statement->execute([
            'source_version_id' => $sourceVersionId,
            'capability' => $capability,
            'run_token' => $runToken,
        ]);

        return $statement->rowCount();
    }

    public function markRunReady(
        int $sourceVersionId,
        string $capability,
        string $runToken
    ): int {
        $statement = $this->database->prepare(
            "UPDATE ai_processing_states
             SET processing_status = 'ready',
                 completed_at = CURRENT_TIMESTAMP,
                 last_error_code = NULL,
                 last_error_summary = NULL
             WHERE source_version_id = :source_version_id
               AND capability = :capability
               AND processing_status = 'processing'
               AND run_token = :run_token"
        );
        $statement->execute([
            'source_version_id' => $sourceVersionId,
            'capability' => $capability,
            'run_token' => $runToken,
        ]);

        return $statement->rowCount();
    }

    public function markRunFailed(
        int $sourceVersionId,
        string $capability,
        string $runToken,
        string $errorCode,
        string $errorSummary
    ): int {
        $statement = $this->database->prepare(
            "UPDATE ai_processing_states
             SET processing_status = 'failed',
                 completed_at = CURRENT_TIMESTAMP,
                 last_error_code = :last_error_code,
                 last_error_summary = :last_error_summary
             WHERE source_version_id = :source_version_id
               AND capability = :capability
               AND processing_status IN ('queued', 'processing')
               AND run_token = :run_token"
        );
        $statement->execute([
            'last_error_code' => $errorCode,
            'last_error_summary' => $errorSummary,
            'source_version_id' => $sourceVersionId,
            'capability' => $capability,
            'run_token' => $runToken,
        ]);

        return $statement->rowCount();
    }

    public function saveExtractedText(
        int $sourceVersionId,
        string $text,
        string $textSha256
    ): void {
        $statement = $this->database->prepare(
            'UPDATE ai_source_versions
             SET extracted_text = :extracted_text,
                 extracted_text_sha256 = :extracted_text_sha256
             WHERE id = :id'
        );
        $statement->execute([
            'extracted_text' => $text,
            'extracted_text_sha256' => $textSha256,
            'id' => $sourceVersionId,
        ]);
    }

    /**
     * @param list<array<string, int|string|null>> $chunks
     * @return array<int, int> map of chunk index to database ID
     */
    public function replaceChunks(int $sourceVersionId, array $chunks): array
    {
        $delete = $this->database->prepare(
            'DELETE FROM ai_chunks WHERE source_version_id = :source_version_id'
        );
        $delete->execute(['source_version_id' => $sourceVersionId]);

        $insert = $this->database->prepare(
            'INSERT INTO ai_chunks (
                source_version_id,
                chunk_index,
                chunk_text,
                text_sha256,
                character_count,
                segmentation_configuration_id,
                locator_kind,
                start_locator,
                end_locator,
                locator_label
             ) VALUES (
                :source_version_id,
                :chunk_index,
                :chunk_text,
                :text_sha256,
                :character_count,
                :segmentation_configuration_id,
                :locator_kind,
                :start_locator,
                :end_locator,
                :locator_label
             )'
        );

        $ids = [];

        foreach ($chunks as $chunk) {
            $insert->execute(['source_version_id' => $sourceVersionId] + $chunk);
            $ids[(int) $chunk['chunk_index']] =
                (int) $this->database->lastInsertId();
        }

        return $ids;
    }

    /** @return list<array<string, mixed>> */
    public function findChunks(int $sourceVersionId, bool $lock = false): array
    {
        $statement = $this->database->prepare(
            'SELECT *
             FROM ai_chunks
             WHERE source_version_id = :source_version_id
             ORDER BY chunk_index' . ($lock ? ' FOR UPDATE' : '')
        );
        $statement->execute(['source_version_id' => $sourceVersionId]);

        return $statement->fetchAll();
    }

    /** @param list<array<string, int|string|null>> $embeddings */
    public function replaceEmbeddings(
        int $sourceVersionId,
        string $configurationId,
        array $embeddings
    ): void {
        $delete = $this->database->prepare(
            'DELETE embedding
             FROM ai_embeddings AS embedding
             INNER JOIN ai_chunks AS chunk ON chunk.id = embedding.chunk_id
             WHERE chunk.source_version_id = :source_version_id
               AND embedding.candidate_configuration_id = :configuration_id'
        );
        $delete->execute([
            'source_version_id' => $sourceVersionId,
            'configuration_id' => $configurationId,
        ]);

        $insert = $this->database->prepare(
            'INSERT INTO ai_embeddings (
                chunk_id,
                candidate_configuration_id,
                model_reference,
                model_digest,
                dimension,
                vector_json,
                vector_norm,
                vector_sha256
             ) VALUES (
                :chunk_id,
                :candidate_configuration_id,
                :model_reference,
                :model_digest,
                :dimension,
                :vector_json,
                :vector_norm,
                :vector_sha256
             )'
        );

        foreach ($embeddings as $embedding) {
            $insert->execute($embedding);
        }
    }

    /** @param array<string, int|string> $output */
    public function upsertOutput(array $output): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO ai_outputs (
                resource_id,
                source_version_id,
                output_type,
                content,
                lifecycle_state,
                source_file_reference,
                candidate_configuration_id,
                prompt_template_version
             ) VALUES (
                :resource_id,
                :source_version_id,
                :output_type,
                :content,
                :lifecycle_state,
                :source_file_reference,
                :candidate_configuration_id,
                :prompt_template_version
             )
             ON DUPLICATE KEY UPDATE
                source_version_id = VALUES(source_version_id),
                content = VALUES(content),
                lifecycle_state = VALUES(lifecycle_state),
                source_file_reference = VALUES(source_file_reference),
                candidate_configuration_id = VALUES(candidate_configuration_id),
                prompt_template_version = VALUES(prompt_template_version),
                generated_at = CURRENT_TIMESTAMP'
        );
        $statement->execute($output);
    }

    public function invalidateSourceTree(int $resourceId): void
    {
        $current = $this->findCurrentSource($resourceId, true);

        if (is_array($current)) {
            $sourceVersionId = (int) $current['id'];
            $this->markProcessingStatesStale($sourceVersionId);
            $this->markSourceNoncurrent($sourceVersionId, 'invalidated');
        }

        $this->invalidateOutputs($resourceId);
    }

    public function deleteResourceDerivedData(int $resourceId): void
    {
        $output = $this->database->prepare(
            'DELETE FROM ai_outputs WHERE resource_id = :resource_id'
        );
        $output->execute(['resource_id' => $resourceId]);

        $source = $this->database->prepare(
            'DELETE FROM ai_source_versions WHERE resource_id = :resource_id'
        );
        $source->execute(['resource_id' => $resourceId]);
    }
}
