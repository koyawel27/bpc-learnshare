<?php

declare(strict_types=1);

namespace BpcLearnShare\Ai;

use JsonException;

/** Local-only adapter for the measured Ollama all-minilm candidate. */
final class OllamaAllMiniLmEmbeddingAdapter implements LocalEmbeddingAdapter
{
    private const CONFIGURATION_ID = 'EMB-OLLAMA-ALL-MINILM-001';
    private const NORM_MIN = 0.99;
    private const NORM_MAX = 1.01;

    /** @var array<string, int|string|null>|null */
    private ?array $runtime = null;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $expectedRuntimeVersion,
        private readonly string $modelName,
        private readonly string $modelDigest,
        private readonly int $expectedDimension = 384,
        private readonly int $timeoutSeconds = 60
    ) {
        $normalizedBase = rtrim(strtolower(trim($baseUrl)), '/');

        if (!in_array(
            $normalizedBase,
            ['http://127.0.0.1:11434', 'http://localhost:11434'],
            true
        )) {
            throw $this->failure(
                'Embedding endpoint must be the local Ollama loopback API.',
                'nonlocal_embedding_endpoint'
            );
        }

        if (
            preg_match('/\A[0-9]+\.[0-9]+\.[0-9]+\z/', $expectedRuntimeVersion) !== 1
            || $modelName !== 'all-minilm:latest'
            || preg_match('/\A[a-f0-9]{64}\z/', $modelDigest) !== 1
            || $expectedDimension !== 384
            || $timeoutSeconds < 1
            || $timeoutSeconds > 300
        ) {
            throw $this->failure(
                'Embedding adapter identity or bounds are invalid.',
                'invalid_embedding_configuration'
            );
        }
    }

    public function configurationId(): string
    {
        return self::CONFIGURATION_ID;
    }

    public function dependencyFingerprint(): string
    {
        return hash('sha256', implode('|', [
            self::CONFIGURATION_ID,
            strtolower(rtrim($this->baseUrl, '/')),
            $this->expectedRuntimeVersion,
            $this->modelName,
            $this->modelDigest,
            (string) $this->expectedDimension,
            'truncate=false',
            'keep_alive=5m',
            'retries=0',
        ]));
    }

    /** @return array<string, int|string|null> */
    public function preflight(): array
    {
        if (is_array($this->runtime)) {
            return $this->runtime;
        }

        $versionResponse = $this->httpJson(
            'GET',
            rtrim($this->baseUrl, '/') . '/api/version'
        );
        $version = (string) ($versionResponse['json']['version'] ?? '');

        if (
            $versionResponse['status'] !== 200
            || $version !== $this->expectedRuntimeVersion
        ) {
            throw $this->failure(
                'Installed Ollama runtime identity does not match configuration.',
                'ollama_runtime_mismatch'
            );
        }

        $tagsResponse = $this->httpJson(
            'GET',
            rtrim($this->baseUrl, '/') . '/api/tags'
        );
        $models = $tagsResponse['json']['models'] ?? null;

        if ($tagsResponse['status'] !== 200 || !is_array($models)) {
            throw $this->failure(
                'Installed Ollama model list is unavailable.',
                'ollama_model_unavailable'
            );
        }

        $matches = array_values(array_filter(
            $models,
            fn (mixed $model): bool => is_array($model)
                && (
                    ($model['name'] ?? '') === $this->modelName
                    || ($model['model'] ?? '') === $this->modelName
                )
        ));

        if (count($matches) !== 1) {
            throw $this->failure(
                'Expected local embedding model is unavailable or ambiguous.',
                'ollama_model_unavailable'
            );
        }

        $digest = strtolower((string) ($matches[0]['digest'] ?? ''));

        if (!hash_equals($this->modelDigest, $digest)) {
            throw $this->failure(
                'Installed embedding model digest does not match configuration.',
                'ollama_model_digest_mismatch'
            );
        }

        $this->runtime = [
            'runtime_version' => $version,
            'model_reference' => $this->modelName,
            'model_digest' => $digest,
            'model_size_bytes' => isset($matches[0]['size'])
                ? (int) $matches[0]['size']
                : null,
            'expected_dimension' => $this->expectedDimension,
        ];

        return $this->runtime;
    }

    /**
     * @return array{
     *     model_reference: string,
     *     model_digest: string,
     *     vector: list<float>
     * }
     */
    public function embed(string $text): array
    {
        $this->preflight();
        $text = trim($text);

        if ($text === '' || mb_strlen($text) > 20000) {
            throw $this->failure(
                'Embedding input is empty or outside the bounded chunk limit.',
                'invalid_embedding_input'
            );
        }

        $response = $this->httpJson(
            'POST',
            rtrim($this->baseUrl, '/') . '/api/embed',
            [
                'model' => $this->modelName,
                'input' => $text,
                'truncate' => false,
                'keep_alive' => '5m',
            ]
        );

        if ($response['status'] !== 200) {
            throw $this->failure(
                'Local embedding request was rejected safely.',
                $response['status'] === 400
                    ? 'embedding_context_rejected'
                    : 'embedding_request_failed'
            );
        }

        $json = $response['json'];
        $vectors = $json['embeddings'] ?? null;

        if (
            !is_array($vectors)
            || count($vectors) !== 1
            || !is_array($vectors[0])
            || !array_is_list($vectors[0])
            || count($vectors[0]) !== $this->expectedDimension
        ) {
            throw $this->failure(
                'Local embedding response shape is invalid.',
                'malformed_embedding_response'
            );
        }

        if (isset($json['model']) && $json['model'] !== $this->modelName) {
            throw $this->failure(
                'Local embedding response model identity changed.',
                'embedding_model_response_mismatch'
            );
        }

        $vector = [];
        $sumSquares = 0.0;

        foreach ($vectors[0] as $value) {
            if (!is_int($value) && !is_float($value)) {
                throw $this->failure(
                    'Local embedding contains a non-numeric value.',
                    'malformed_embedding_response'
                );
            }

            $number = (float) $value;

            if (!is_finite($number)) {
                throw $this->failure(
                    'Local embedding contains a non-finite value.',
                    'malformed_embedding_response'
                );
            }

            $vector[] = $number;
            $sumSquares += $number * $number;
        }

        $norm = sqrt($sumSquares);

        if ($norm < self::NORM_MIN || $norm > self::NORM_MAX) {
            throw $this->failure(
                'Local embedding is outside the accepted normalization guard.',
                'invalid_embedding_norm'
            );
        }

        return [
            'model_reference' => $this->modelName,
            'model_digest' => $this->modelDigest,
            'vector' => $vector,
        ];
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{status: int, json: array<string, mixed>}
     */
    private function httpJson(
        string $method,
        string $url,
        ?array $payload = null
    ): array {
        if (!extension_loaded('curl')) {
            throw $this->failure(
                'PHP cURL support is unavailable.',
                'curl_dependency_unavailable'
            );
        }

        $handle = curl_init();

        if ($handle === false) {
            throw $this->failure(
                'Local HTTP adapter could not initialize.',
                'embedding_transport_unavailable'
            );
        }

        $headers = ['Accept: application/json'];
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($payload !== null) {
            try {
                $body = json_encode(
                    $payload,
                    JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_THROW_ON_ERROR
                );
            } catch (JsonException) {
                curl_close($handle);
                throw $this->failure(
                    'Local embedding request could not be encoded.',
                    'invalid_embedding_request'
                );
            }

            $options[CURLOPT_POSTFIELDS] = $body;
            $options[CURLOPT_HTTPHEADER] = [
                'Accept: application/json',
                'Content-Type: application/json',
            ];
        }

        curl_setopt_array($handle, $options);
        $responseBody = curl_exec($handle);
        $curlNumber = curl_errno($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        if ($responseBody === false || $curlNumber !== 0) {
            throw $this->failure(
                'Local Ollama service is unavailable.',
                'embedding_transport_unavailable'
            );
        }

        try {
            $json = json_decode(
                (string) $responseBody,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            throw $this->failure(
                'Local embedding service returned invalid JSON.',
                'malformed_embedding_response'
            );
        }

        if (!is_array($json)) {
            throw $this->failure(
                'Local embedding service returned an invalid response.',
                'malformed_embedding_response'
            );
        }

        return ['status' => $status, 'json' => $json];
    }

    private function failure(string $message, string $reason): LocalProcessingException
    {
        return new LocalProcessingException($message, $reason);
    }
}
