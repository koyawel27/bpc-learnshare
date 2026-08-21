<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

use Throwable;

/**
 * One-resource CLI/admin-triggered local extraction, segmentation, and
 * embedding path. It exposes no route and performs no retrieval/generation.
 */
final class GuardedLocalResourceProcessor
{
    public function __construct(
        private readonly AiPersistenceRepository $repository,
        private readonly GuardedAiPersistenceProcessor $persistence,
        private readonly AiFeatureAvailability $featureGate,
        private readonly LocalReadableTextExtractor $extractor,
        private readonly BlockAwareContextFitSegmenter $segmenter,
        private readonly LocalEmbeddingAdapter $embedding,
        private readonly string $resourceStorageDirectory,
        private readonly bool $operatorEnabled
    ) {
    }

    /** @return array<string, int|string|null> */
    public function validate(int $resourceId, int $actorId): array
    {
        $this->assertPositiveId($resourceId, 'resource');
        $this->assertPositiveId($actorId, 'actor');
        $this->assertOperatorEnabled();
        $this->assertFeatureEnabled();
        $actor = $this->repository->findAuthorizedProcessingActor($actorId);

        if (!is_array($actor)) {
            throw $this->failure(
                'An active Moderator or Admin account is required.',
                'processing_not_authorized'
            );
        }

        $resource = $this->repository->findResource($resourceId);

        if (!is_array($resource)) {
            throw $this->failure('Resource not found.', 'resource_not_found');
        }

        if (
            (string) $resource['status'] !== 'approved'
            || (string) $resource['file_availability'] !== 'available'
        ) {
            throw $this->failure(
                'Only an Approved resource with an available file may be processed.',
                'resource_not_eligible'
            );
        }

        $fileType = strtolower((string) $resource['file_type']);

        if (!$this->extractor->supports($fileType)) {
            throw $this->failure(
                'This file type is outside the readable local AI path.',
                'unsupported_extraction_type'
            );
        }

        $path = $this->protectedFilePath($resource);
        $mime = $this->extractor->detectMimeType($path, $fileType);
        $runtime = $this->embedding->preflight();

        return [
            'resource_id' => $resourceId,
            'actor_id' => $actorId,
            'actor_role' => (string) $actor['role'],
            'file_type' => $fileType,
            'file_size' => (int) $resource['file_size'],
            'detected_mime_type' => $mime,
            'extraction_configuration_id' => $this->extractor->configurationId(),
            'segmentation_configuration_id' => $this->segmenter->configurationId(),
            'embedding_configuration_id' => $this->embedding->configurationId(),
        ] + $runtime;
    }

    /** @return array<string, int|string|null> */
    public function process(int $resourceId, int $actorId): array
    {
        $validated = $this->validate($resourceId, $actorId);
        $resource = $this->repository->findResource($resourceId);

        if (!is_array($resource)) {
            throw $this->failure('Resource not found.', 'resource_not_found');
        }

        $fileType = (string) $validated['file_type'];
        $path = $this->protectedFilePath($resource);
        $authorizationGuard = fn (): bool => is_array(
            $this->repository->findAuthorizedProcessingActor($actorId, true)
        );
        $sourceVersionId = $this->persistence->synchronizeCurrentSource(
            $resourceId,
            (string) $validated['detected_mime_type'],
            $authorizationGuard
        );
        $source = $this->repository->findSource($sourceVersionId);

        if (!is_array($source)) {
            throw $this->failure(
                'Source version disappeared before local processing.',
                'source_not_found'
            );
        }

        $sourceHash = (string) $source['source_sha256'];
        $extractionToken = $this->persistence->queueRun(
            $sourceVersionId,
            'extraction',
            $this->extractor->configurationId(),
            hash('sha256', implode('|', [
                'extraction',
                $this->extractor->configurationId(),
                $sourceHash,
            ])),
            $authorizationGuard
        );
        $this->persistence->startRun(
            $sourceVersionId,
            'extraction',
            $extractionToken,
            $authorizationGuard
        );

        try {
            $extraction = $this->extractor->extract($path, $fileType);

            if (!hash_equals(
                (string) $validated['detected_mime_type'],
                $extraction['detected_mime_type']
            )) {
                throw $this->failure(
                    'Detected MIME type changed during extraction.',
                    'extraction_mime_changed'
                );
            }

            $this->persistence->completeExtraction(
                $sourceVersionId,
                $extractionToken,
                $extraction['full_text'],
                $authorizationGuard
            );
        } catch (Throwable $exception) {
            $this->recordSafeFailure(
                $sourceVersionId,
                'extraction',
                $extractionToken,
                $exception
            );
            throw $exception;
        }

        $segmentationToken = $this->persistence->queueRun(
            $sourceVersionId,
            'segmentation',
            $this->segmenter->configurationId(),
            hash('sha256', implode('|', [
                'segmentation',
                $this->segmenter->configurationId(),
                hash('sha256', $extraction['full_text']),
            ])),
            $authorizationGuard
        );
        $this->persistence->startRun(
            $sourceVersionId,
            'segmentation',
            $segmentationToken,
            $authorizationGuard
        );

        try {
            $chunks = $this->segmenter->segment(
                $extraction['blocks'],
                $fileType
            );
            $this->persistence->completeSegmentation(
                $sourceVersionId,
                $segmentationToken,
                $chunks,
                $authorizationGuard
            );
        } catch (Throwable $exception) {
            $this->recordSafeFailure(
                $sourceVersionId,
                'segmentation',
                $segmentationToken,
                $exception
            );
            throw $exception;
        }

        $chunkFingerprints = array_map(
            static fn (array $chunk): string => hash('sha256', $chunk['text']),
            $chunks
        );
        $embeddingToken = $this->persistence->queueRun(
            $sourceVersionId,
            'embedding',
            $this->embedding->configurationId(),
            hash('sha256', implode('|', [
                'embedding',
                $this->segmenter->configurationId(),
                $this->embedding->dependencyFingerprint(),
                ...$chunkFingerprints,
            ])),
            $authorizationGuard
        );
        $this->persistence->startRun(
            $sourceVersionId,
            'embedding',
            $embeddingToken,
            $authorizationGuard
        );

        try {
            $embeddings = [];

            foreach ($chunks as $offset => $chunk) {
                $embeddings[] = [
                    'chunk_index' => $offset + 1,
                ] + $this->embedding->embed($chunk['text']);
            }

            $this->persistence->completeEmbedding(
                $sourceVersionId,
                $embeddingToken,
                $embeddings,
                $authorizationGuard
            );
        } catch (Throwable $exception) {
            $this->recordSafeFailure(
                $sourceVersionId,
                'embedding',
                $embeddingToken,
                $exception
            );
            throw $exception;
        }

        return $validated + [
            'source_version_id' => $sourceVersionId,
            'source_sha256' => $sourceHash,
            'extracted_text_sha256' => hash(
                'sha256',
                $extraction['full_text']
            ),
            'chunk_count' => count($chunks),
            'embedding_count' => count($embeddings),
        ];
    }

    /** @param array<string, mixed> $resource */
    private function protectedFilePath(array $resource): string
    {
        $root = realpath($this->resourceStorageDirectory);
        $storedFilename = (string) ($resource['stored_filename'] ?? '');
        $fileType = (string) ($resource['file_type'] ?? '');

        if (
            $root === false
            || !is_dir($root)
            || preg_match(
                '/\A[a-f0-9]{64}\.(pdf|docx|pptx|txt|jpg|png)\z/',
                $storedFilename
            ) !== 1
            || !str_ends_with($storedFilename, '.' . $fileType)
        ) {
            throw $this->failure(
                'Protected resource storage identity is invalid.',
                'storage_unavailable'
            );
        }

        $path = realpath($root . DIRECTORY_SEPARATOR . $storedFilename);
        $size = is_string($path) && is_file($path) ? filesize($path) : false;

        if (
            !is_string($path)
            || dirname($path) !== $root
            || !is_int($size)
            || $size !== (int) ($resource['file_size'] ?? 0)
        ) {
            throw $this->failure(
                'Protected resource file is missing or changed.',
                'source_file_changed'
            );
        }

        return $path;
    }

    private function recordSafeFailure(
        int $sourceVersionId,
        string $capability,
        string $runToken,
        Throwable $exception
    ): void {
        $reason = match (true) {
            $exception instanceof LocalProcessingException => $exception->reason,
            $exception instanceof AiPersistenceException => $exception->reason,
            default => 'local_processing_failed',
        };

        if (preg_match('/\A[a-z0-9][a-z0-9_:-]{0,79}\z/', $reason) !== 1) {
            $reason = 'local_processing_failed';
        }

        try {
            $this->persistence->failRun(
                $sourceVersionId,
                $capability,
                $runToken,
                $reason,
                'The local processing step failed safely.'
            );
        } catch (Throwable) {
            // A stale/disabled source may correctly refuse even the status write.
        }
    }

    private function assertOperatorEnabled(): void
    {
        if (!$this->operatorEnabled) {
            throw $this->failure(
                'Local AI processing is disabled by environment configuration.',
                'local_processing_disabled'
            );
        }
    }

    private function assertFeatureEnabled(): void
    {
        if (!$this->featureGate->isEnabled()) {
            throw $this->failure(
                'AI processing is disabled by the live application setting.',
                'ai_disabled'
            );
        }
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

    private function failure(string $message, string $reason): LocalProcessingException
    {
        return new LocalProcessingException($message, $reason);
    }
}
