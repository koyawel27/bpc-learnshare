<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

use BpcLearnShare\Resource\ResourceDiscoveryRepository;
use JsonException;
use Throwable;

/**
 * Backend-only semantic ranking with live eligibility and metadata fallback.
 *
 * This class deliberately exposes no route and persists no query vector.
 */
final class GuardedSemanticRetrieval
{
    private const CONFIGURATION_ID = 'EMB-OLLAMA-ALL-MINILM-001';
    private const MODEL_REFERENCE = 'all-minilm:latest';
    private const MODEL_DIGEST =
        '1b226e2802dbb772b5fc32a58f103ca1804ef7501331012de126ab22f67475ef';
    private const DIMENSION = 384;
    private const NORM_MIN = 0.99;
    private const NORM_MAX = 1.01;
    private const MAX_RESULTS = 10;
    private const MAX_CANDIDATE_CHUNKS = 2000;

    private readonly string $storageDirectory;

    public function __construct(
        private readonly SemanticRetrievalRepository $repository,
        private readonly ResourceDiscoveryRepository $metadataDiscovery,
        private readonly AiFeatureAvailability $featureGate,
        private readonly LocalEmbeddingAdapter $embeddingAdapter,
        string $protectedStorageDirectory,
        private readonly bool $operatorEnabled = false
    ) {
        $protectedStorageDirectory = trim($protectedStorageDirectory);

        if ($protectedStorageDirectory === '') {
            throw $this->failure('Protected resource storage is invalid.', 'invalid_storage_root');
        }

        $this->storageDirectory = rtrim(
            $protectedStorageDirectory,
            DIRECTORY_SEPARATOR
        );
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
     * @return array{
     *     mode: string,
     *     fallback_reason: string|null,
     *     query_vector_persisted: false,
     *     similarity_score_is_evidence_threshold: false,
     *     results: list<array<string, mixed>>
     * }
     */
    public function search(array $filters, int $requesterId, int $limit = 5): array
    {
        $this->assertRequest($filters, $requesterId, $limit);
        $query = trim($filters['q']);

        if ($query === '') {
            return $this->fallback($filters, 'empty_query');
        }

        if (!$this->operatorEnabled) {
            return $this->fallback($filters, 'semantic_retrieval_disabled');
        }

        if (!$this->featureGate->isEnabled()) {
            return $this->fallback($filters, 'ai_disabled');
        }

        try {
            $this->assertAdapterIdentity();
            $queryEmbedding = $this->embeddingAdapter->embed($query);
            $queryVector = $this->validatedQueryVector($queryEmbedding);
            $candidates = $this->repository->findReadyCandidates(
                $filters,
                self::CONFIGURATION_ID,
                self::MODEL_REFERENCE,
                self::MODEL_DIGEST,
                self::DIMENSION
            );

            if ($candidates === []) {
                return $this->fallback($filters, 'semantic_index_unavailable');
            }

            if (count($candidates) > self::MAX_CANDIDATE_CHUNKS) {
                throw $this->failure(
                    'Semantic candidate corpus exceeds the bounded PHP checkpoint.',
                    'semantic_candidate_limit_exceeded'
                );
            }

            $ranked = [];
            $verifiedFiles = [];

            foreach ($candidates as $candidate) {
                $sourceId = (int) $candidate['source_version_id'];

                if (!isset($verifiedFiles[$sourceId])) {
                    $this->assertProtectedSource($candidate);
                    $verifiedFiles[$sourceId] = true;
                }

                $vector = $this->validatedCandidateVector($candidate);
                $ranked[] = $candidate + [
                    'internal_similarity_score' => $this->cosine(
                        $queryVector,
                        $vector
                    ),
                ];
            }

            usort($ranked, static function (array $left, array $right): int {
                $score = $right['internal_similarity_score']
                    <=> $left['internal_similarity_score'];

                if ($score !== 0) {
                    return $score;
                }

                $resource = (int) $left['resource_id']
                    <=> (int) $right['resource_id'];

                return $resource !== 0
                    ? $resource
                    : (int) $left['chunk_index'] <=> (int) $right['chunk_index'];
            });

            $results = [];
            $seenResources = [];

            foreach ($ranked as $candidate) {
                $resourceId = (int) $candidate['resource_id'];

                if (isset($seenResources[$resourceId])) {
                    continue;
                }

                if ($this->repository->findActiveRequester($requesterId) === null) {
                    throw $this->failure(
                        'The requesting account is no longer active.',
                        'semantic_requester_not_authorized'
                    );
                }

                if (!$this->repository->candidateRemainsEligible(
                    (int) $candidate['chunk_id'],
                    $resourceId,
                    (int) $candidate['source_version_id'],
                    (string) $candidate['segmentation_configuration_id'],
                    self::CONFIGURATION_ID,
                    self::MODEL_REFERENCE,
                    self::MODEL_DIGEST,
                    self::DIMENSION
                )) {
                    continue;
                }

                $this->assertProtectedSource($candidate);
                $results[] = $this->present($candidate);
                $seenResources[$resourceId] = true;

                if (count($results) >= $limit) {
                    break;
                }
            }

            if ($results === []) {
                return $this->fallback($filters, 'semantic_index_unavailable');
            }

            return [
                'mode' => 'semantic',
                'fallback_reason' => null,
                'query_vector_persisted' => false,
                'similarity_score_is_evidence_threshold' => false,
                'results' => $results,
            ];
        } catch (LocalProcessingException $exception) {
            if ($exception->reason === 'semantic_requester_not_authorized') {
                throw $exception;
            }

            return $this->fallback($filters, 'semantic_dependency_unavailable');
        } catch (Throwable) {
            return $this->fallback($filters, 'semantic_dependency_unavailable');
        }
    }

    /** @param array<string, mixed> $filters */
    private function assertRequest(array $filters, int $requesterId, int $limit): void
    {
        $required = [
            'q', 'course_id', 'subject_id', 'year_level_id',
            'resource_type_id', 'tag_id',
        ];

        foreach ($required as $field) {
            if (!array_key_exists($field, $filters)) {
                throw $this->failure('Search filters are malformed.', 'invalid_search_request');
            }
        }

        if (
            $requesterId <= 0
            || $this->repository->findActiveRequester($requesterId) === null
        ) {
            throw $this->failure(
                'An active signed-in account is required.',
                'semantic_requester_not_authorized'
            );
        }

        if (
            mb_strlen(trim((string) $filters['q'])) > 100
            || $limit < 1
            || $limit > self::MAX_RESULTS
        ) {
            throw $this->failure('Search request is outside its bounds.', 'invalid_search_request');
        }

        foreach (array_slice($required, 1) as $field) {
            if (!is_int($filters[$field]) || $filters[$field] < 0) {
                throw $this->failure('Search filters are malformed.', 'invalid_search_request');
            }
        }
    }

    private function assertAdapterIdentity(): void
    {
        if ($this->embeddingAdapter->configurationId() !== self::CONFIGURATION_ID) {
            throw $this->failure('Embedding configuration changed.', 'embedding_identity_mismatch');
        }

        $runtime = $this->embeddingAdapter->preflight();

        if (
            ($runtime['model_reference'] ?? null) !== self::MODEL_REFERENCE
            || ($runtime['model_digest'] ?? null) !== self::MODEL_DIGEST
            || ($runtime['expected_dimension'] ?? null) !== self::DIMENSION
        ) {
            throw $this->failure('Embedding identity changed.', 'embedding_identity_mismatch');
        }
    }

    /** @param array<string, mixed> $embedding @return list<float> */
    private function validatedQueryVector(array $embedding): array
    {
        if (
            ($embedding['model_reference'] ?? null) !== self::MODEL_REFERENCE
            || ($embedding['model_digest'] ?? null) !== self::MODEL_DIGEST
            || !isset($embedding['vector'])
            || !is_array($embedding['vector'])
        ) {
            throw $this->failure('Query embedding is malformed.', 'invalid_query_embedding');
        }

        return $this->validatedNumericVector(
            $embedding['vector'],
            self::DIMENSION,
            null
        );
    }

    /** @param array<string, mixed> $candidate @return list<float> */
    private function validatedCandidateVector(array $candidate): array
    {
        $json = (string) ($candidate['vector_json'] ?? '');

        if (
            !hash_equals(
                strtolower((string) ($candidate['vector_sha256'] ?? '')),
                hash('sha256', $json)
            )
        ) {
            throw $this->failure('Stored vector hash changed.', 'invalid_candidate_vector');
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw $this->failure('Stored vector is malformed.', 'invalid_candidate_vector');
        }

        if (!is_array($decoded)) {
            throw $this->failure('Stored vector is malformed.', 'invalid_candidate_vector');
        }

        return $this->validatedNumericVector(
            $decoded,
            (int) ($candidate['dimension'] ?? 0),
            (float) ($candidate['vector_norm'] ?? 0.0)
        );
    }

    /** @param array<mixed> $values @return list<float> */
    private function validatedNumericVector(
        array $values,
        int $dimension,
        ?float $storedNorm
    ): array {
        if (!array_is_list($values) || $dimension !== self::DIMENSION || count($values) !== $dimension) {
            throw $this->failure('Embedding vector shape is invalid.', 'invalid_candidate_vector');
        }

        $vector = [];
        $sumSquares = 0.0;

        foreach ($values as $value) {
            if (!is_int($value) && !is_float($value)) {
                throw $this->failure('Embedding vector contains non-numeric data.', 'invalid_candidate_vector');
            }

            $number = (float) $value;

            if (!is_finite($number)) {
                throw $this->failure('Embedding vector contains non-finite data.', 'invalid_candidate_vector');
            }

            $vector[] = $number;
            $sumSquares += $number * $number;
        }

        $norm = sqrt($sumSquares);

        if (
            $norm < self::NORM_MIN
            || $norm > self::NORM_MAX
            || ($storedNorm !== null && abs($storedNorm - $norm) > 0.000001)
        ) {
            throw $this->failure('Embedding vector norm is invalid.', 'invalid_candidate_vector');
        }

        return $vector;
    }

    /** @param array<string, mixed> $candidate */
    private function assertProtectedSource(array $candidate): void
    {
        $storedFilename = basename((string) ($candidate['stored_filename'] ?? ''));

        if ($storedFilename === '' || $storedFilename !== (string) $candidate['stored_filename']) {
            throw $this->failure('Protected source reference is invalid.', 'source_file_changed');
        }

        $root = realpath($this->storageDirectory);
        $path = $root === false
            ? false
            : realpath($root . DIRECTORY_SEPARATOR . $storedFilename);

        if ($root === false || $path === false || dirname($path) !== $root || !is_file($path)) {
            throw $this->failure('Protected source is unavailable.', 'source_file_changed');
        }

        $size = filesize($path);
        $hash = hash_file('sha256', $path);

        if (
            !is_int($size)
            || $size !== (int) ($candidate['file_size'] ?? 0)
            || !is_string($hash)
            || !hash_equals(strtolower((string) $candidate['source_sha256']), strtolower($hash))
        ) {
            throw $this->failure('Protected source changed.', 'source_file_changed');
        }

        $chunkText = (string) ($candidate['chunk_text'] ?? '');

        if (
            $chunkText === ''
            || mb_strlen($chunkText) !== (int) ($candidate['character_count'] ?? 0)
            || !hash_equals(
                strtolower((string) ($candidate['text_sha256'] ?? '')),
                hash('sha256', $chunkText)
            )
        ) {
            throw $this->failure('Stored chunk changed.', 'invalid_candidate_chunk');
        }
    }

    /** @param list<float> $left @param list<float> $right */
    private function cosine(array $left, array $right): float
    {
        $dot = 0.0;
        $leftSquares = 0.0;
        $rightSquares = 0.0;

        foreach ($left as $index => $value) {
            $other = $right[$index];
            $dot += $value * $other;
            $leftSquares += $value * $value;
            $rightSquares += $other * $other;
        }

        $denominator = sqrt($leftSquares) * sqrt($rightSquares);

        if (!is_finite($denominator) || $denominator <= 0.0) {
            throw $this->failure('Cosine calculation is invalid.', 'invalid_candidate_vector');
        }

        $score = $dot / $denominator;

        if (!is_finite($score)) {
            throw $this->failure('Cosine result is invalid.', 'invalid_candidate_vector');
        }

        return $score;
    }

    /** @param array<string, mixed> $candidate @return array<string, mixed> */
    private function present(array $candidate): array
    {
        $tagNames = (string) ($candidate['tag_names'] ?? '');
        $text = preg_replace('/\s+/u', ' ', trim((string) $candidate['chunk_text']));
        $excerpt = is_string($text) ? $text : '';

        if (mb_strlen($excerpt) > 320) {
            $excerpt = mb_substr($excerpt, 0, 320);
            $wordBoundary = mb_strrpos($excerpt, ' ');

            if ($wordBoundary !== false && $wordBoundary >= 240) {
                $excerpt = mb_substr($excerpt, 0, $wordBoundary);
            }

            $excerpt = rtrim($excerpt, " \t\n\r\0\x0B,;:-") . '…';
        }

        return [
            'id' => (int) $candidate['resource_id'],
            'title' => (string) $candidate['title'],
            'description' => (string) $candidate['description'],
            'topic' => (string) $candidate['topic'],
            'file_type' => (string) $candidate['file_type'],
            'file_size' => (int) $candidate['file_size'],
            'view_count' => (int) $candidate['view_count'],
            'download_count' => (int) $candidate['download_count'],
            'created_at' => (string) $candidate['created_at'],
            'uploader_name' => (string) $candidate['uploader_name'],
            'course_name' => (string) $candidate['course_name'],
            'subject_name' => (string) $candidate['subject_name'],
            'year_level_name' => (string) $candidate['year_level_name'],
            'resource_type_name' => (string) $candidate['resource_type_name'],
            'tags' => $tagNames === '' ? [] : explode('||', $tagNames),
            'matched_locator' => $candidate['locator_label'] !== null
                ? (string) $candidate['locator_label']
                : null,
            'matched_excerpt' => $excerpt,
            'internal_similarity_score' => (float) $candidate['internal_similarity_score'],
        ];
    }

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    private function fallback(array $filters, string $reason): array
    {
        return [
            'mode' => 'metadata_fallback',
            'fallback_reason' => $reason,
            'query_vector_persisted' => false,
            'similarity_score_is_evidence_threshold' => false,
            'results' => $this->metadataDiscovery->search($filters),
        ];
    }

    private function failure(
        string $message,
        string $reason
    ): LocalProcessingException {
        return new LocalProcessingException($message, $reason);
    }
}
