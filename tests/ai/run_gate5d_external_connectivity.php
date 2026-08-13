<?php

declare(strict_types=1);

const GATE5D_CONNECTIVITY_PROVIDER = 'GroqCloud';
const GATE5D_CONNECTIVITY_MODEL = 'openai/gpt-oss-120b';
const GATE5D_CONNECTIVITY_ENDPOINT =
    'https://api.groq.com/openai/v1/chat/completions';
const GATE5D_CONNECTIVITY_PROBE =
    'Return the status value runtime_ready. '
    . 'Do not include any repository or project content.';
const GATE5D_CONNECTIVITY_APPROVAL = 'EXTERNAL_RUNTIME_PROBE_ONLY';

function connectivityFail(string $message): never
{
    fwrite(STDERR, 'GATE 5D CONNECTIVITY CHECK FAILED: ' . $message . PHP_EOL);
    exit(1);
}

function connectivityPass(string $message): void
{
    echo '[PASS] ' . $message . PHP_EOL;
}

function connectivityAssert(bool $condition, string $message): void
{
    if (!$condition) {
        connectivityFail($message);
    }

    connectivityPass($message);
}

function readGroqKey(string $root): string
{
    $processValue = getenv('GROQ_API_KEY');

    if (is_string($processValue) && trim($processValue) !== '') {
        $key = trim($processValue);
    } else {
        $envPath = $root . DIRECTORY_SEPARATOR . '.env';

        if (!is_file($envPath) || !is_readable($envPath)) {
            connectivityFail('The ignored local .env file is unavailable.');
        }

        $key = '';
        $handle = fopen($envPath, 'rb');

        if ($handle === false) {
            connectivityFail('The ignored local .env file could not be opened.');
        }

        try {
            while (($line = fgets($handle)) !== false) {
                if (!preg_match('/^\s*GROQ_API_KEY\s*=\s*(.+)\s*$/', $line, $match)) {
                    continue;
                }

                if ($key !== '') {
                    connectivityFail('More than one GROQ_API_KEY entry was found.');
                }

                $key = trim($match[1], " \t\n\r\0\x0B\"'");
            }
        } finally {
            fclose($handle);
        }
    }

    if (!preg_match('/^gsk_[A-Za-z0-9_-]{16,}$/', $key)) {
        connectivityFail('The configured Groq credential has an invalid shape.');
    }

    return $key;
}

/**
 * @return array<string, mixed>
 */
function buildConnectivityRequest(): array
{
    $schema = [
        'type' => 'object',
        'properties' => [
            'status' => [
                'type' => 'string',
                'enum' => ['runtime_ready'],
            ],
            'message' => [
                'type' => 'string',
                'enum' => ['EXTERNAL_RUNTIME_READY'],
            ],
        ],
        'required' => ['status', 'message'],
        'additionalProperties' => false,
    ];

    return [
        'model' => GATE5D_CONNECTIVITY_MODEL,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Return only the required synthetic status.',
            ],
            [
                'role' => 'user',
                'content' => GATE5D_CONNECTIVITY_PROBE,
            ],
        ],
        'temperature' => 0,
        'reasoning_effort' => 'low',
        'max_completion_tokens' => 128,
        'stream' => false,
        'response_format' => [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'external_runtime_probe',
                'strict' => true,
                'schema' => $schema,
            ],
        ],
    ];
}

/**
 * @param array<string, mixed> $request
 */
function validateConnectivityRequest(array $request): string
{
    $encoded = json_encode(
        $request,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
    );

    connectivityAssert(
        $request['model'] === GATE5D_CONNECTIVITY_MODEL,
        'Model is restricted to ' . GATE5D_CONNECTIVITY_MODEL
    );
    connectivityAssert(
        count($request['messages']) === 2,
        'Request contains exactly one system and one user message'
    );
    connectivityAssert(
        $request['messages'][1]['content'] === GATE5D_CONNECTIVITY_PROBE,
        'User content is exactly the accepted synthetic probe'
    );
    connectivityAssert(
        strlen($encoded) < 4000,
        'Encoded request remains below 4,000 bytes'
    );
    connectivityAssert(
        !str_contains($encoded, 'fixture_id')
        && !str_contains($encoded, 'resource_id')
        && !str_contains($encoded, 'source_version_id'),
        'Request contains no repository identifiers'
    );
    connectivityAssert(
        !array_key_exists('tools', $request),
        'Request enables no tools'
    );
    connectivityAssert(
        $request['stream'] === false,
        'Streaming is disabled'
    );
    connectivityAssert(
        $request['max_completion_tokens'] === 128,
        'Maximum completion length is 128 tokens'
    );
    connectivityAssert(
        $request['response_format']['json_schema']['strict'] === true,
        'Strict JSON Schema response is required'
    );

    return $encoded;
}

/**
 * @return array<string, mixed>
 */
function sendConnectivityRequest(string $key, string $payload): array
{
    $curl = curl_init(GATE5D_CONNECTIVITY_ENDPOINT);

    if ($curl === false) {
        connectivityFail('The cURL request could not be initialized.');
    }

    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS => 0,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    ]);

    $startedAt = hrtime(true);
    $body = curl_exec($curl);
    $elapsedMilliseconds = (hrtime(true) - $startedAt) / 1_000_000;
    $curlError = curl_error($curl);
    $httpStatus = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    if (!is_string($body)) {
        connectivityFail(
            'Provider request failed before an HTTP response: '
            . ($curlError !== '' ? $curlError : 'unknown transport error')
        );
    }

    if ($httpStatus !== 200) {
        connectivityFail(
            'Provider returned HTTP ' . $httpStatus
            . '. The response body was not printed or persisted.'
        );
    }

    try {
        $decoded = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        connectivityFail('Provider returned invalid JSON.');
    }

    if (!is_array($decoded)) {
        connectivityFail('Provider response was not a JSON object.');
    }

    $decoded['_audit_http_status'] = $httpStatus;
    $decoded['_audit_elapsed_ms'] = $elapsedMilliseconds;

    return $decoded;
}

/**
 * @param array<string, mixed> $response
 */
function validateConnectivityResponse(array $response): void
{
    $content = $response['choices'][0]['message']['content'] ?? null;

    connectivityAssert(is_string($content), 'Response contains message content');

    try {
        $status = json_decode($content, true, 8, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        connectivityFail('Structured message content was not valid JSON.');
    }

    connectivityAssert(
        $status === [
            'status' => 'runtime_ready',
            'message' => 'EXTERNAL_RUNTIME_READY',
        ],
        'Structured response exactly matches the synthetic contract'
    );

    $returnedModel = $response['model'] ?? null;
    connectivityAssert(
        is_string($returnedModel)
        && str_contains($returnedModel, 'gpt-oss-120b'),
        'Provider response identifies the requested model family'
    );

    $usage = $response['usage'] ?? null;
    connectivityAssert(is_array($usage), 'Provider returned token-usage metadata');

    foreach (['prompt_tokens', 'completion_tokens', 'total_tokens'] as $field) {
        connectivityAssert(
            isset($usage[$field]) && is_int($usage[$field]) && $usage[$field] >= 0,
            'Usage metadata includes ' . $field
        );
    }

    echo PHP_EOL;
    echo 'GATE 5D SYNTHETIC CONNECTIVITY PROBE PASSED.' . PHP_EOL;
    echo 'Provider: ' . GATE5D_CONNECTIVITY_PROVIDER . PHP_EOL;
    echo 'Model: ' . GATE5D_CONNECTIVITY_MODEL . PHP_EOL;
    echo 'HTTP status: ' . $response['_audit_http_status'] . PHP_EOL;
    echo 'Elapsed ms: ' . number_format(
        (float) $response['_audit_elapsed_ms'],
        3,
        '.',
        ''
    ) . PHP_EOL;
    echo 'Prompt tokens: ' . $usage['prompt_tokens'] . PHP_EOL;
    echo 'Completion tokens: ' . $usage['completion_tokens'] . PHP_EOL;
    echo 'Total tokens: ' . $usage['total_tokens'] . PHP_EOL;
    echo 'Provider requests: 1' . PHP_EOL;
    echo 'Automatic retries: 0' . PHP_EOL;
    echo 'BPC fixture/query/evidence content transmitted: 0' . PHP_EOL;
    echo 'Response or key persisted by this checker: No' . PHP_EOL;
    echo 'Candidate selected or integrated: No' . PHP_EOL;
}

$mode = null;
$approval = null;

foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--mode=')) {
        $mode = substr($argument, strlen('--mode='));
    } elseif (str_starts_with($argument, '--approve=')) {
        $approval = substr($argument, strlen('--approve='));
    } else {
        connectivityFail('Unknown argument: ' . $argument);
    }
}

if (!in_array($mode, ['validate', 'apply'], true)) {
    connectivityFail('Use --mode=validate or --mode=apply.');
}

$root = dirname(__DIR__, 2);
$request = buildConnectivityRequest();
$payload = validateConnectivityRequest($request);
$key = readGroqKey($root);
connectivityPass('A project-specific Groq key is available (value not displayed)');

if ($mode === 'validate') {
    connectivityAssert(
        $approval === null,
        'Offline validation includes no live-approval argument'
    );

    echo PHP_EOL;
    echo 'GATE 5D CONNECTIVITY CHECKER OFFLINE VALIDATION PASSED.' . PHP_EOL;
    echo 'Network requests: 0' . PHP_EOL;
    echo 'Exact external user message:' . PHP_EOL;
    echo GATE5D_CONNECTIVITY_PROBE . PHP_EOL;
    echo 'Repository content read by checker: 0' . PHP_EOL;
    echo 'Provider response persisted: No' . PHP_EOL;
    echo 'A passing offline validation never authorizes a live request or '
        . 'rerun. Every live probe requires separate explicit approval.'
        . PHP_EOL;
    exit(0);
}

connectivityAssert(
    $approval === GATE5D_CONNECTIVITY_APPROVAL,
    'Live mode includes the exact synthetic-probe approval token'
);

$response = sendConnectivityRequest($key, $payload);
validateConnectivityResponse($response);
