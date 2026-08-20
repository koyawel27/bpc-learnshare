<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

use JsonException;
use PDO;
use Throwable;

/**
 * Provider-neutral D043 persistence coordinator.
 *
 * This class performs no extraction, embedding, generation, routing, or UI
 * work. Expensive adapters run outside it and return bounded data that is
 * validated and committed only while source, feature, and run-token guards
 * still pass.
 */
final class GuardedAiPersistenceProcessor
{
    private const CAPABILITIES = [
        'extraction',
        'segmentation',
        'embedding',
        'semantic_retrieval',
        'related_resources',
        'summary',
        'suggested_tags',
        'suggested_metadata',
        'duplicate_flag',
        'moderation_hint',
    ];

    private const OUTPUT_CAPABILITIES = [
        'summary' => 'summary',
        'suggested_tags' => 'suggested_tags',
        'suggested_metadata' => 'suggested_metadata',
        'duplicate_flag' => 'duplicate_flag',
        'moderation_hint' => 'moderation_hint',
    ];

    private const LOCATOR_KINDS = [
        'page',
        'slide',
        'section',
        'heading',
        'paragraph',
        'mixed',
        'unavailable',
    ];

    private const CLEANUP_STATUSES = [
        'rejected',
        'withdrawn',
        'replaced',
        'removed',
    ];

    private const MAX_CHUNKS = 2000;
    private const MAX_CHUNK_CHARACTERS = 20000;
    private const MAX_VECTOR_DIMENSION = 4096;
    private const MAX_EXTRACTED_TEXT_BYTES = 16000000;
    private const MAX_OUTPUT_BYTES = 65535;

    public function __construct(
        private readonly PDO $database,
        private readonly AiPersistenceRepository $repository,
        private readonly AiFeatureAvailability $featureGate,
        private readonly string $resourceStorageDirectory
    ) {
    }

    public function synchronizeCurrentSource(
        int $resourceId,
        string $detectedMimeType
    ): int {
        $this->assertPositiveId($resourceId, 'resource');
        $detectedMimeType = $this->boundedText(
            $detectedMimeType,
            100,
            'detected MIME type'
        );
        $this->assertFeatureEnabled();

        $resource = $this->repository->findResource($resourceId);

        if (!is_array($resource)) {
            throw $this->failure('Resource not found.', 'resource_not_found');
        }

        $this->assertApprovedAvailableResource($resource);
        $file = $this->snapshotResourceFile($resource);

        return $this->transaction(function () use (
            $resourceId,
            $detectedMimeType,
            $file
        ): int {
            $this->assertFeatureEnabled();
            $resource = $this->repository->findResource($resourceId, true);

            if (!is_array($resource)) {
                throw $this->failure(
                    'Resource disappeared before source registration.',
                    'resource_not_found'
                );
            }

            $this->assertApprovedAvailableResource($resource);
            $this->assertFileSnapshotMatchesResource($file, $resource);

            $current = $this->repository->findCurrentSource(
                $resourceId,
                true
            );

            if (is_array($current)) {
                if (hash_equals((string) $current['source_sha256'], $file['sha256'])) {
                    if (
                        !hash_equals(
                            (string) $current['stored_filename'],
                            $file['stored_filename']
                        )
                        || (int) $current['file_size'] !== $file['file_size']
                        || !hash_equals(
                            (string) $current['detected_mime_type'],
                            $detectedMimeType
                        )
                    ) {
                        throw $this->failure(
                            'Current source identity conflicts with the protected file snapshot.',
                            'source_identity_conflict'
                        );
                    }

                    return (int) $current['id'];
                }

                $currentId = (int) $current['id'];
                $this->repository->markProcessingStatesStale($currentId);
                $this->repository->markSourceNoncurrent($currentId, 'stale');
                $this->repository->invalidateOutputs($resourceId);
            }

            $matching = $this->repository->findSourceByHash(
                $resourceId,
                $file['sha256'],
                true
            );

            if (is_array($matching)) {
                $nextVersion =
                    $this->repository->nextSourceVersionNumber($resourceId);
                $this->repository->invalidateOutputs($resourceId);
                $this->repository->deleteSourceForReprocessing(
                    (int) $matching['id']
                );

                return $this->repository->insertSource([
                    'resource_id' => $resourceId,
                    'source_version_number' => $nextVersion,
                    'source_sha256' => $file['sha256'],
                    'stored_filename' => $file['stored_filename'],
                    'file_size' => $file['file_size'],
                    'detected_mime_type' => $detectedMimeType,
                ]);
            }

            return $this->repository->insertSource([
                'resource_id' => $resourceId,
                'source_version_number' =>
                    $this->repository->nextSourceVersionNumber($resourceId),
                'source_sha256' => $file['sha256'],
                'stored_filename' => $file['stored_filename'],
                'file_size' => $file['file_size'],
                'detected_mime_type' => $detectedMimeType,
            ]);
        });
    }

    public function queueRun(
        int $sourceVersionId,
        string $capability,
        string $configurationId,
        string $dependencyFingerprint
    ): string {
        $this->assertPositiveId($sourceVersionId, 'source version');
        $capability = $this->capability($capability);
        $configurationId = $this->configurationId($configurationId);
        $dependencyFingerprint = $this->sha256(
            $dependencyFingerprint,
            'dependency fingerprint'
        );
        $file = $this->snapshotSourceFile($sourceVersionId);
        $runToken = bin2hex(random_bytes(32));

        $this->transaction(function () use (
            $sourceVersionId,
            $capability,
            $configurationId,
            $dependencyFingerprint,
            $runToken,
            $file
        ): void {
            $source = $this->lockCurrentEligibleSource(
                $sourceVersionId,
                $file
            );
            $existing = $this->repository->findProcessingState(
                $sourceVersionId,
                $capability,
                true
            );

            if (
                is_array($existing)
                && (int) $existing['attempt_count'] >= 65535
            ) {
                throw $this->failure(
                    'Processing attempt limit reached.',
                    'attempt_limit_reached'
                );
            }

            $this->repository->queueRun(
                (int) $source['id'],
                $capability,
                $configurationId,
                $dependencyFingerprint,
                $runToken
            );
        });

        return $runToken;
    }

    public function startRun(
        int $sourceVersionId,
        string $capability,
        string $runToken
    ): void {
        $capability = $this->capability($capability);
        $runToken = $this->runToken($runToken);
        $file = $this->snapshotSourceFile($sourceVersionId);

        $this->transaction(function () use (
            $sourceVersionId,
            $capability,
            $runToken,
            $file
        ): void {
            $this->lockCurrentEligibleSource($sourceVersionId, $file);

            if (
                $this->repository->markRunProcessing(
                    $sourceVersionId,
                    $capability,
                    $runToken
                ) !== 1
            ) {
                throw $this->failure(
                    'Queued processing run is stale or no longer current.',
                    'run_token_mismatch'
                );
            }
        });
    }

    public function completeExtraction(
        int $sourceVersionId,
        string $runToken,
        string $extractedText
    ): void {
        $runToken = $this->runToken($runToken);

        if (
            $extractedText === ''
            || strlen($extractedText) > self::MAX_EXTRACTED_TEXT_BYTES
        ) {
            throw $this->failure(
                'Extracted text must not be empty.',
                'invalid_extracted_text'
            );
        }

        $textHash = hash('sha256', $extractedText);
        $file = $this->snapshotSourceFile($sourceVersionId);

        $this->transaction(function () use (
            $sourceVersionId,
            $runToken,
            $extractedText,
            $textHash,
            $file
        ): void {
            $source = $this->lockCurrentEligibleSource(
                $sourceVersionId,
                $file
            );
            $this->assertActiveRun(
                $sourceVersionId,
                'extraction',
                $runToken
            );
            $this->repository->markCapabilitiesStale(
                $sourceVersionId,
                [
                    'segmentation',
                    'embedding',
                    'semantic_retrieval',
                    'related_resources',
                    'summary',
                    'suggested_tags',
                    'suggested_metadata',
                    'duplicate_flag',
                    'moderation_hint',
                ]
            );
            $this->repository->invalidateOutputs((int) $source['resource_id']);
            $this->repository->saveExtractedText(
                $sourceVersionId,
                $extractedText,
                $textHash
            );
            $this->markReady($sourceVersionId, 'extraction', $runToken);
        });
    }

    /** @param list<array<string, mixed>> $chunks */
    public function completeSegmentation(
        int $sourceVersionId,
        string $runToken,
        array $chunks
    ): void {
        $runToken = $this->runToken($runToken);
        $chunks = $this->normalizeChunks($chunks);
        $file = $this->snapshotSourceFile($sourceVersionId);

        $this->transaction(function () use (
            $sourceVersionId,
            $runToken,
            $chunks,
            $file
        ): void {
            $this->lockCurrentEligibleSource($sourceVersionId, $file);
            $state = $this->assertActiveRun(
                $sourceVersionId,
                'segmentation',
                $runToken
            );
            $configurationId = (string) $state['candidate_configuration_id'];
            $stored = [];

            foreach ($chunks as $chunk) {
                $stored[] = $chunk + [
                    'segmentation_configuration_id' => $configurationId,
                ];
            }

            $this->repository->markCapabilitiesStale(
                $sourceVersionId,
                ['embedding', 'semantic_retrieval', 'related_resources']
            );
            $this->repository->replaceChunks($sourceVersionId, $stored);
            $this->markReady($sourceVersionId, 'segmentation', $runToken);
        });
    }

    /** @param list<array<string, mixed>> $embeddings */
    public function completeEmbedding(
        int $sourceVersionId,
        string $runToken,
        array $embeddings
    ): void {
        $runToken = $this->runToken($runToken);
        $normalized = $this->normalizeEmbeddings($embeddings);
        $file = $this->snapshotSourceFile($sourceVersionId);

        $this->transaction(function () use (
            $sourceVersionId,
            $runToken,
            $normalized,
            $file
        ): void {
            $this->lockCurrentEligibleSource($sourceVersionId, $file);
            $state = $this->assertActiveRun(
                $sourceVersionId,
                'embedding',
                $runToken
            );
            $chunks = $this->repository->findChunks($sourceVersionId, true);

            if (count($chunks) !== count($normalized)) {
                throw $this->failure(
                    'Embedding results do not cover every stored chunk.',
                    'partial_embedding_result'
                );
            }

            $chunkIds = [];

            foreach ($chunks as $chunk) {
                $chunkIds[(int) $chunk['chunk_index']] = (int) $chunk['id'];
            }

            $rows = [];

            foreach ($normalized as $embedding) {
                $chunkIndex = (int) $embedding['chunk_index'];

                if (!isset($chunkIds[$chunkIndex])) {
                    throw $this->failure(
                        'Embedding references an unknown chunk index.',
                        'embedding_chunk_mismatch'
                    );
                }

                unset($embedding['chunk_index']);
                $rows[] = [
                    'chunk_id' => $chunkIds[$chunkIndex],
                    'candidate_configuration_id' =>
                        (string) $state['candidate_configuration_id'],
                ] + $embedding;
            }

            $this->repository->markCapabilitiesStale(
                $sourceVersionId,
                ['semantic_retrieval', 'related_resources']
            );
            $this->repository->replaceEmbeddings(
                $sourceVersionId,
                (string) $state['candidate_configuration_id'],
                $rows
            );
            $this->markReady($sourceVersionId, 'embedding', $runToken);
        });
    }

    public function completeOutput(
        int $sourceVersionId,
        string $capability,
        string $runToken,
        string $content,
        string $lifecycleState,
        string $promptTemplateVersion
    ): void {
        $capability = $this->capability($capability);
        $outputType = self::OUTPUT_CAPABILITIES[$capability] ?? null;

        if (!is_string($outputType)) {
            throw $this->failure(
                'Capability does not produce a stored current output.',
                'unsupported_output_capability'
            );
        }

        $runToken = $this->runToken($runToken);
        $content = trim($content);

        if ($content === '' || strlen($content) > self::MAX_OUTPUT_BYTES) {
            throw $this->failure('Output content is empty.', 'invalid_output');
        }

        if (!in_array($lifecycleState, ['draft', 'retained'], true)) {
            throw $this->failure(
                'Output lifecycle state is invalid.',
                'invalid_output_state'
            );
        }

        $promptTemplateVersion = $this->configurationId(
            $promptTemplateVersion
        );
        $file = $this->snapshotSourceFile($sourceVersionId);

        $this->transaction(function () use (
            $sourceVersionId,
            $capability,
            $outputType,
            $runToken,
            $content,
            $lifecycleState,
            $promptTemplateVersion,
            $file
        ): void {
            $source = $this->lockCurrentEligibleSource(
                $sourceVersionId,
                $file
            );
            $state = $this->assertActiveRun(
                $sourceVersionId,
                $capability,
                $runToken
            );
            $this->repository->upsertOutput([
                'resource_id' => (int) $source['resource_id'],
                'source_version_id' => $sourceVersionId,
                'output_type' => $outputType,
                'content' => $content,
                'lifecycle_state' => $lifecycleState,
                'source_file_reference' => (string) $source['stored_filename'],
                'candidate_configuration_id' =>
                    (string) $state['candidate_configuration_id'],
                'prompt_template_version' => $promptTemplateVersion,
            ]);
            $this->markReady($sourceVersionId, $capability, $runToken);
        });
    }

    public function failRun(
        int $sourceVersionId,
        string $capability,
        string $runToken,
        string $errorCode,
        string $errorSummary
    ): void {
        $capability = $this->capability($capability);
        $runToken = $this->runToken($runToken);
        $errorCode = $this->safeErrorCode($errorCode);
        $errorSummary = $this->safeErrorSummary($errorSummary);
        $file = $this->snapshotSourceFile($sourceVersionId);

        $this->transaction(function () use (
            $sourceVersionId,
            $capability,
            $runToken,
            $errorCode,
            $errorSummary,
            $file
        ): void {
            $this->lockCurrentEligibleSource($sourceVersionId, $file);

            if (
                $this->repository->markRunFailed(
                    $sourceVersionId,
                    $capability,
                    $runToken,
                    $errorCode,
                    $errorSummary
                ) !== 1
            ) {
                throw $this->failure(
                    'Failed result belongs to a stale or unknown run.',
                    'run_token_mismatch'
                );
            }
        });
    }

    public function cleanIneligibleResource(int $resourceId): void
    {
        $this->assertPositiveId($resourceId, 'resource');

        $this->transaction(function () use ($resourceId): void {
            $resource = $this->repository->findResource($resourceId, true);

            if (!is_array($resource)) {
                throw $this->failure('Resource not found.', 'resource_not_found');
            }

            $status = (string) $resource['status'];
            $unavailable =
                (string) $resource['file_availability'] !== 'available';

            if (!in_array($status, self::CLEANUP_STATUSES, true) && !$unavailable) {
                throw $this->failure(
                    'Resource lifecycle does not authorize derived-data cleanup.',
                    'cleanup_not_authorized'
                );
            }

            if ($status === 'removed') {
                $this->repository->deleteResourceDerivedData($resourceId);

                return;
            }

            $this->repository->invalidateSourceTree($resourceId);
        }, false);
    }

    /** @param array<string, mixed> $expectedFile */
    private function lockCurrentEligibleSource(
        int $sourceVersionId,
        array $expectedFile
    ): array {
        $this->assertFeatureEnabled();
        $source = $this->repository->findSource($sourceVersionId, true);

        if (!is_array($source)) {
            throw $this->failure('Source version not found.', 'source_not_found');
        }

        if (
            (string) $source['lifecycle_state'] !== 'current'
            || (int) $source['current_marker'] !== 1
            || (string) $source['resource_status'] !== 'approved'
            || (string) $source['resource_file_availability'] !== 'available'
            || !hash_equals(
                (string) $source['stored_filename'],
                (string) $source['resource_stored_filename']
            )
            || (int) $source['file_size'] !==
                (int) $source['resource_file_size']
        ) {
            throw $this->failure(
                'Source version is no longer current and eligible.',
                'source_not_eligible'
            );
        }

        if (
            !hash_equals(
                (string) $source['source_sha256'],
                (string) $expectedFile['sha256']
            )
            || !hash_equals(
                (string) $source['stored_filename'],
                (string) $expectedFile['stored_filename']
            )
            || (int) $source['file_size'] !== (int) $expectedFile['file_size']
        ) {
            throw $this->failure(
                'Protected source file changed before the guarded write.',
                'source_file_changed'
            );
        }

        return $source;
    }

    /** @return array<string, mixed> */
    private function assertActiveRun(
        int $sourceVersionId,
        string $capability,
        string $runToken
    ): array {
        $state = $this->repository->findProcessingState(
            $sourceVersionId,
            $capability,
            true
        );

        if (
            !is_array($state)
            || (string) $state['processing_status'] !== 'processing'
            || !is_string($state['run_token'])
            || !hash_equals((string) $state['run_token'], $runToken)
            || !is_string($state['candidate_configuration_id'])
            || trim((string) $state['candidate_configuration_id']) === ''
            || !is_string($state['dependency_fingerprint'])
        ) {
            throw $this->failure(
                'Processing result belongs to a stale or incomplete run.',
                'run_token_mismatch'
            );
        }

        return $state;
    }

    private function markReady(
        int $sourceVersionId,
        string $capability,
        string $runToken
    ): void {
        if (
            $this->repository->markRunReady(
                $sourceVersionId,
                $capability,
                $runToken
            ) !== 1
        ) {
            throw $this->failure(
                'Processing run changed before it could become ready.',
                'run_token_mismatch'
            );
        }
    }

    /** @return array{stored_filename: string, file_size: int, sha256: string} */
    private function snapshotSourceFile(int $sourceVersionId): array
    {
        $this->assertPositiveId($sourceVersionId, 'source version');
        $this->assertFeatureEnabled();
        $source = $this->repository->findSource($sourceVersionId);

        if (!is_array($source)) {
            throw $this->failure('Source version not found.', 'source_not_found');
        }

        $file = $this->snapshotResourceFile([
            'stored_filename' => $source['resource_stored_filename'],
            'file_type' => $source['resource_file_type'],
            'file_size' => $source['resource_file_size'],
        ]);

        if (!hash_equals((string) $source['source_sha256'], $file['sha256'])) {
            throw $this->failure(
                'Protected file fingerprint no longer matches its source version.',
                'source_file_changed'
            );
        }

        return $file;
    }

    /**
     * @param array<string, mixed> $resource
     * @return array{stored_filename: string, file_size: int, sha256: string}
     */
    private function snapshotResourceFile(array $resource): array
    {
        $root = realpath($this->resourceStorageDirectory);

        if ($root === false || !is_dir($root)) {
            throw $this->failure(
                'Protected resource storage is unavailable.',
                'storage_unavailable'
            );
        }

        $storedFilename = (string) ($resource['stored_filename'] ?? '');
        $fileType = (string) ($resource['file_type'] ?? '');

        if (
            preg_match(
                '/\A[a-f0-9]{64}\.(pdf|docx|pptx|txt|jpg|png)\z/',
                $storedFilename
            ) !== 1
            || !str_ends_with($storedFilename, '.' . $fileType)
        ) {
            throw $this->failure(
                'Stored filename is not a protected resource filename.',
                'invalid_stored_filename'
            );
        }

        $path = realpath($root . DIRECTORY_SEPARATOR . $storedFilename);

        if (
            $path === false
            || dirname($path) !== $root
            || !is_file($path)
        ) {
            throw $this->failure(
                'Protected resource file is missing.',
                'source_file_missing'
            );
        }

        $size = filesize($path);
        $hash = hash_file('sha256', $path);

        if (
            !is_int($size)
            || $size <= 0
            || $size !== (int) ($resource['file_size'] ?? 0)
            || !is_string($hash)
        ) {
            throw $this->failure(
                'Protected resource file metadata changed.',
                'source_file_changed'
            );
        }

        return [
            'stored_filename' => $storedFilename,
            'file_size' => $size,
            'sha256' => strtolower($hash),
        ];
    }

    /** @param array<string, mixed> $file @param array<string, mixed> $resource */
    private function assertFileSnapshotMatchesResource(
        array $file,
        array $resource
    ): void {
        if (
            !hash_equals(
                (string) $file['stored_filename'],
                (string) $resource['stored_filename']
            )
            || (int) $file['file_size'] !== (int) $resource['file_size']
        ) {
            throw $this->failure(
                'Resource changed while its protected file was being fingerprinted.',
                'source_file_changed'
            );
        }
    }

    /** @param array<string, mixed> $resource */
    private function assertApprovedAvailableResource(array $resource): void
    {
        if (
            (string) ($resource['status'] ?? '') !== 'approved'
            || (string) ($resource['file_availability'] ?? '') !== 'available'
        ) {
            throw $this->failure(
                'Only an Approved, available resource may enter this processor.',
                'resource_not_eligible'
            );
        }
    }

    /** @param list<array<string, mixed>> $chunks @return list<array<string, int|string|null>> */
    private function normalizeChunks(array $chunks): array
    {
        if (
            $chunks === []
            || !array_is_list($chunks)
            || count($chunks) > self::MAX_CHUNKS
        ) {
            throw $this->failure(
                'Chunk result is empty, unbounded, or malformed.',
                'invalid_chunk_result'
            );
        }

        $normalized = [];

        foreach ($chunks as $offset => $chunk) {
            if (!is_array($chunk)) {
                throw $this->failure(
                    'Chunk result contains a malformed row.',
                    'invalid_chunk_result'
                );
            }

            $chunkIndex = $offset + 1;
            $text = (string) ($chunk['text'] ?? '');
            $locatorKind = (string) ($chunk['locator_kind'] ?? '');

            if (
                $text === ''
                || mb_strlen($text) > self::MAX_CHUNK_CHARACTERS
                || !in_array($locatorKind, self::LOCATOR_KINDS, true)
            ) {
                throw $this->failure(
                    'Chunk text or locator type is invalid.',
                    'invalid_chunk_result'
                );
            }

            $locators = [
                'start_locator' => $this->optionalLocator(
                    $chunk['start_locator'] ?? null
                ),
                'end_locator' => $this->optionalLocator(
                    $chunk['end_locator'] ?? null
                ),
                'locator_label' => $this->optionalLocator(
                    $chunk['locator_label'] ?? null
                ),
            ];

            if ($locatorKind === 'unavailable') {
                if (array_filter($locators, static fn ($value): bool => $value !== null)) {
                    throw $this->failure(
                        'Unavailable locator must not contain fabricated values.',
                        'invalid_chunk_locator'
                    );
                }
            } elseif (in_array(null, $locators, true)) {
                throw $this->failure(
                    'Verified locator is incomplete.',
                    'invalid_chunk_locator'
                );
            }

            $normalized[] = [
                'chunk_index' => $chunkIndex,
                'chunk_text' => $text,
                'text_sha256' => hash('sha256', $text),
                'character_count' => mb_strlen($text),
                'locator_kind' => $locatorKind,
            ] + $locators;
        }

        return $normalized;
    }

    /** @param list<array<string, mixed>> $embeddings @return list<array<string, int|string|null>> */
    private function normalizeEmbeddings(array $embeddings): array
    {
        if ($embeddings === [] || !array_is_list($embeddings)) {
            throw $this->failure(
                'Embedding result is empty or malformed.',
                'invalid_embedding_result'
            );
        }

        $normalized = [];
        $expectedDimension = null;
        $expectedModel = null;
        $expectedDigest = null;
        $identityInitialized = false;
        $seenIndexes = [];

        foreach ($embeddings as $embedding) {
            if (!is_array($embedding)) {
                throw $this->failure(
                    'Embedding result contains a malformed row.',
                    'invalid_embedding_result'
                );
            }

            $chunkIndex = $embedding['chunk_index'] ?? null;
            $modelReference = $this->boundedText(
                (string) ($embedding['model_reference'] ?? ''),
                255,
                'model reference'
            );
            $modelDigest = $embedding['model_digest'] ?? null;
            $vector = $embedding['vector'] ?? null;

            if (
                !is_int($chunkIndex)
                || $chunkIndex <= 0
                || isset($seenIndexes[$chunkIndex])
                || !is_array($vector)
                || !array_is_list($vector)
                || $vector === []
                || count($vector) > self::MAX_VECTOR_DIMENSION
            ) {
                throw $this->failure(
                    'Embedding chunk index or vector shape is invalid.',
                    'invalid_embedding_result'
                );
            }

            if ($modelDigest !== null) {
                if (!is_string($modelDigest)) {
                    throw $this->failure(
                        'Model digest is malformed.',
                        'invalid_embedding_identity'
                    );
                }

                $modelDigest = $this->sha256($modelDigest, 'model digest');
            }

            $numericVector = [];
            $sumSquares = 0.0;

            foreach ($vector as $value) {
                if (!is_int($value) && !is_float($value)) {
                    throw $this->failure(
                        'Embedding contains a non-numeric value.',
                        'invalid_embedding_value'
                    );
                }

                $number = (float) $value;

                if (!is_finite($number)) {
                    throw $this->failure(
                        'Embedding contains a non-finite value.',
                        'invalid_embedding_value'
                    );
                }

                $numericVector[] = $number;
                $sumSquares += $number * $number;
            }

            $dimension = count($numericVector);
            $norm = sqrt($sumSquares);

            if ($norm < 0.99 || $norm > 1.01) {
                throw $this->failure(
                    'Embedding vector is not normalized.',
                    'invalid_embedding_norm'
                );
            }

            if (
                $identityInitialized
                && (
                    $dimension !== $expectedDimension
                    || $modelReference !== $expectedModel
                    || $modelDigest !== $expectedDigest
                )
            ) {
                throw $this->failure(
                    'Embedding results contain mixed dimensions or model identity.',
                    'mixed_embedding_identity'
                );
            }

            try {
                $vectorJson = json_encode(
                    $numericVector,
                    JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION
                );
            } catch (JsonException $exception) {
                throw $this->failure(
                    'Embedding vector could not be encoded.',
                    'invalid_embedding_value',
                    $exception
                );
            }

            if (!$identityInitialized) {
                $expectedDimension = $dimension;
                $expectedModel = $modelReference;
                $expectedDigest = $modelDigest;
                $identityInitialized = true;
            }
            $seenIndexes[$chunkIndex] = true;
            $normalized[] = [
                'chunk_index' => $chunkIndex,
                'model_reference' => $modelReference,
                'model_digest' => $modelDigest,
                'dimension' => $dimension,
                'vector_json' => $vectorJson,
                'vector_norm' => number_format($norm, 12, '.', ''),
                'vector_sha256' => hash('sha256', $vectorJson),
            ];
        }

        return $normalized;
    }

    private function assertFeatureEnabled(): void
    {
        if (!$this->featureGate->isEnabled()) {
            throw $this->failure(
                'AI persistence is disabled or not configured.',
                'ai_disabled'
            );
        }
    }

    private function capability(string $capability): string
    {
        if (!in_array($capability, self::CAPABILITIES, true)) {
            throw $this->failure(
                'Processing capability is invalid.',
                'invalid_capability'
            );
        }

        return $capability;
    }

    private function configurationId(string $value): string
    {
        $value = trim($value);

        if (
            preg_match('/\A[A-Za-z0-9][A-Za-z0-9._:-]{0,99}\z/', $value) !== 1
        ) {
            throw $this->failure(
                'Configuration identity is invalid.',
                'invalid_configuration_id'
            );
        }

        return $value;
    }

    private function runToken(string $runToken): string
    {
        $runToken = strtolower(trim($runToken));

        if (preg_match('/\A[a-f0-9]{64}\z/', $runToken) !== 1) {
            throw $this->failure('Run token is invalid.', 'invalid_run_token');
        }

        return $runToken;
    }

    private function sha256(string $value, string $label): string
    {
        $value = strtolower(trim($value));

        if (preg_match('/\A[a-f0-9]{64}\z/', $value) !== 1) {
            throw $this->failure($label . ' is invalid.', 'invalid_hash');
        }

        return $value;
    }

    private function safeErrorCode(string $value): string
    {
        $value = trim($value);

        if (preg_match('/\A[a-z0-9][a-z0-9_:-]{0,79}\z/', $value) !== 1) {
            throw $this->failure(
                'Safe error code is invalid.',
                'invalid_error_record'
            );
        }

        return $value;
    }

    private function safeErrorSummary(string $value): string
    {
        $value = trim(str_replace(["\r", "\n"], ' ', $value));

        if ($value === '' || mb_strlen($value) > 500) {
            throw $this->failure(
                'Safe error summary is invalid.',
                'invalid_error_record'
            );
        }

        return $value;
    }

    private function boundedText(string $value, int $max, string $label): string
    {
        $value = trim($value);

        if ($value === '' || mb_strlen($value) > $max) {
            throw $this->failure($label . ' is invalid.', 'invalid_text');
        }

        return $value;
    }

    private function optionalLocator(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            throw $this->failure(
                'Chunk locator is malformed.',
                'invalid_chunk_locator'
            );
        }

        $value = trim($value);

        if ($value === '' || mb_strlen($value) > 255) {
            throw $this->failure(
                'Chunk locator is invalid.',
                'invalid_chunk_locator'
            );
        }

        return $value;
    }

    private function assertPositiveId(int $id, string $label): void
    {
        if ($id <= 0) {
            throw $this->failure(
                ucfirst($label) . ' ID is invalid.',
                'invalid_identifier'
            );
        }
    }

    /** @template T @param callable(): T $operation @return T */
    private function transaction(callable $operation, bool $requireAi = true): mixed
    {
        if ($this->database->inTransaction()) {
            throw $this->failure(
                'Nested AI persistence transaction refused.',
                'nested_transaction'
            );
        }

        $this->database->beginTransaction();

        try {
            if ($requireAi) {
                $this->assertFeatureEnabled();
            }

            $result = $operation();
            $this->database->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }

            throw $exception;
        }
    }

    private function failure(
        string $message,
        string $reason,
        ?Throwable $previous = null
    ): AiPersistenceException {
        $exception = new AiPersistenceException($message, $reason);

        if ($previous === null) {
            return $exception;
        }

        return new AiPersistenceException(
            $message . ' ' . $previous->getMessage(),
            $reason
        );
    }
}
