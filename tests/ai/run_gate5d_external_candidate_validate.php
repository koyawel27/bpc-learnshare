<?php

declare(strict_types=1);

const GATE5D_PROVIDER = 'GroqCloud';
const GATE5D_MODEL = 'openai/gpt-oss-120b';
const GATE5D_ENDPOINT =
    'https://api.groq.com/openai/v1/chat/completions';
const GATE5D_PROBE =
    'Return the status value runtime_ready. '
    . 'Do not include any repository or project content.';

/** @var list<string> */
$passedChecks = [];

function gate5dFail(string $message): never
{
    fwrite(STDERR, 'GATE 5D VALIDATION FAILED: ' . $message . PHP_EOL);
    exit(1);
}

function gate5dPass(string $message): void
{
    global $passedChecks;

    $passedChecks[] = $message;
    echo '[PASS] ' . $message . PHP_EOL;
}

function gate5dAssert(bool $condition, string $message): void
{
    if (!$condition) {
        gate5dFail($message);
    }

    gate5dPass($message);
}

/**
 * @return array{headers: list<string>, rows: list<array<string, string>>}
 */
function gate5dReadCsv(string $path): array
{
    $handle = fopen($path, 'rb');

    if ($handle === false) {
        gate5dFail('Could not open CSV: ' . $path);
    }

    try {
        $headers = fgetcsv($handle);

        if (!is_array($headers) || $headers === []) {
            gate5dFail('CSV header is missing: ' . $path);
        }

        $headers = array_map(
            static fn (mixed $value): string => (string) $value,
            $headers
        );
        $headers[0] = trim($headers[0], "\xEF\xBB\xBF\"");
        $rows = [];

        while (($values = fgetcsv($handle)) !== false) {
            if ($values === [null] || $values === []) {
                continue;
            }

            if (count($values) !== count($headers)) {
                gate5dFail('CSV row width mismatch: ' . $path);
            }

            $row = array_combine($headers, array_map(
                static fn (mixed $value): string => (string) $value,
                $values
            ));

            if (!is_array($row)) {
                gate5dFail('CSV row could not be mapped: ' . $path);
            }

            $rows[] = $row;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    } finally {
        fclose($handle);
    }
}

function gate5dHasLocalKey(string $root): bool
{
    $environmentKey = getenv('GROQ_API_KEY');

    if (is_string($environmentKey) && trim($environmentKey) !== '') {
        return true;
    }

    $envPath = $root . DIRECTORY_SEPARATOR . '.env';

    if (!is_file($envPath) || !is_readable($envPath)) {
        return false;
    }

    $handle = fopen($envPath, 'rb');

    if ($handle === false) {
        return false;
    }

    try {
        while (($line = fgets($handle)) !== false) {
            if (preg_match('/^\s*GROQ_API_KEY\s*=\s*(.+)\s*$/', $line, $match)) {
                $value = trim($match[1], " \t\n\r\0\x0B\"'");

                return $value !== '';
            }
        }
    } finally {
        fclose($handle);
    }

    return false;
}

$mode = null;

foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--mode=')) {
        $mode = substr($argument, strlen('--mode='));
    } else {
        gate5dFail('Unknown argument: ' . $argument);
    }
}

gate5dAssert($mode === 'validate', 'Mode is exactly offline validate');

$root = dirname(__DIR__, 2);
$fixturePath = $root
    . DIRECTORY_SEPARATOR
    . 'docs'
    . DIRECTORY_SEPARATOR
    . 'ai-feasibility-spike'
    . DIRECTORY_SEPARATOR
    . 'registers'
    . DIRECTORY_SEPARATOR
    . 'fixtures.csv';
$payloadPath = dirname($fixturePath)
    . DIRECTORY_SEPARATOR
    . 'payload_manifests.csv';
$reviewPath = $root
    . DIRECTORY_SEPARATOR
    . 'docs'
    . DIRECTORY_SEPARATOR
    . 'ai-feasibility-spike'
    . DIRECTORY_SEPARATOR
    . 'EXTERNAL_GENERATION_PREFLIGHT.md';
$gitignorePath = $root . DIRECTORY_SEPARATOR . '.gitignore';
$envExamplePath = $root . DIRECTORY_SEPARATOR . '.env.example';

foreach ([$fixturePath, $payloadPath, $reviewPath, $gitignorePath,
    $envExamplePath] as $requiredPath) {
    gate5dAssert(
        is_file($requiredPath) && is_readable($requiredPath),
        'Required tracked input is readable: '
        . substr($requiredPath, strlen($root) + 1)
    );
}

$fixtures = gate5dReadCsv($fixturePath);
$requiredFixtureHeaders = [
    'fixture_id',
    'fixture_set',
    'authorization_basis',
    'contains_personal_or_sensitive_information',
    'local_testing_allowed',
    'external_transmission_allowed',
    'review_status',
];

foreach ($requiredFixtureHeaders as $requiredHeader) {
    gate5dAssert(
        in_array($requiredHeader, $fixtures['headers'], true),
        'Fixture register includes ' . $requiredHeader
    );
}

gate5dAssert(
    count($fixtures['rows']) === 30,
    'Fixture register contains exactly 30 rows'
);

$fixtureIds = [];
$primaryCount = 0;
$boundaryCount = 0;
$externallyEligibleCount = 0;

foreach ($fixtures['rows'] as $fixture) {
    $fixtureId = trim($fixture['fixture_id']);

    gate5dAssert(
        $fixtureId !== '' && !isset($fixtureIds[$fixtureId]),
        'Fixture ID is unique and nonblank: ' . $fixtureId
    );

    $fixtureIds[$fixtureId] = true;
    gate5dAssert(
        $fixture['contains_personal_or_sensitive_information'] === 'No',
        'Fixture declares no personal or sensitive information: '
        . $fixtureId
    );
    gate5dAssert(
        $fixture['local_testing_allowed'] === 'Yes',
        'Fixture permits local testing: ' . $fixtureId
    );

    if ($fixture['fixture_set'] === 'primary-readable') {
        $primaryCount++;
        gate5dAssert(
            str_starts_with(
                $fixture['external_transmission_allowed'],
                'Yes, only when intentionally approved'
            ),
            'Readable fixture requires selected-test approval: '
            . $fixtureId
        );
        $externallyEligibleCount++;
    } elseif ($fixture['fixture_set'] === 'boundary-negative') {
        $boundaryCount++;
        gate5dAssert(
            str_starts_with(
                $fixture['external_transmission_allowed'],
                'No - '
            ),
            'Boundary fixture prohibits external transmission: '
            . $fixtureId
        );
    } else {
        gate5dFail('Unexpected fixture set: ' . $fixture['fixture_set']);
    }
}

gate5dAssert($primaryCount === 25, 'Primary-readable fixture count is 25');
gate5dAssert($boundaryCount === 5, 'Boundary-negative fixture count is 5');
gate5dAssert(
    $externallyEligibleCount === 25,
    'Selected-test external-approval pool contains 25 fixtures'
);

$payloads = gate5dReadCsv($payloadPath);
$expectedPayloadHeaders = [
    'payload_manifest_id',
    'test_run_id',
    'provider_or_model_candidate',
    'fixture_ids',
    'evidence_passage_ids',
    'resource_count',
    'evidence_count',
    'included_data_categories',
    'source_identifiers_included',
    'locator_information_included',
    'approximate_size',
    'approximate_size_unit',
    'excluded_data_categories',
    'personal_or_account_linked_information_included',
    'justification',
    'external_transmission_authorization_basis',
    'redacted_sample_path',
    'reviewer',
    'notes',
];

gate5dAssert(
    $payloads['headers'] === $expectedPayloadHeaders,
    'Payload-manifest register retains the accepted schema'
);
gate5dAssert(
    $payloads['rows'] === [],
    'No external payload has been registered or authorized'
);

$reviewText = file_get_contents($reviewPath);
$gitignoreText = file_get_contents($gitignorePath);
$envExampleText = file_get_contents($envExamplePath);

if (!is_string($reviewText)
    || !is_string($gitignoreText)
    || !is_string($envExampleText)) {
    gate5dFail('A tracked preflight input could not be read.');
}

foreach ([
    GATE5D_PROVIDER,
    GATE5D_MODEL,
    GATE5D_ENDPOINT,
    'https://console.groq.com/docs/your-data',
    'https://console.groq.com/docs/rate-limits',
] as $requiredReviewText) {
    gate5dAssert(
        str_contains($reviewText, $requiredReviewText),
        'Provider review records ' . $requiredReviewText
    );
}

gate5dAssert(
    preg_match('/^\.env\s*$/m', $gitignoreText) === 1,
    'Local .env credential file is ignored by Git'
);

foreach ([$reviewText, $envExampleText] as $trackedText) {
    gate5dAssert(
        preg_match('/\bgsk_[A-Za-z0-9_-]{16,}\b/', $trackedText) !== 1,
        'Tracked preflight text contains no Groq key-shaped value'
    );
}

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
$request = [
    'model' => GATE5D_MODEL,
    'messages' => [
        [
            'role' => 'system',
            'content' => 'Return only the required synthetic status.',
        ],
        [
            'role' => 'user',
            'content' => GATE5D_PROBE,
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

$encodedRequest = json_encode($request, JSON_THROW_ON_ERROR);

gate5dAssert(
    strlen($encodedRequest) < 4000,
    'Synthetic connectivity request remains below 4,000 bytes'
);
gate5dAssert(
    !str_contains($encodedRequest, 'fixture_id')
    && !str_contains($encodedRequest, 'resource_id')
    && !str_contains($encodedRequest, 'source_version_id'),
    'Synthetic request contains no repository identifiers'
);
gate5dAssert(
    !array_key_exists('tools', $request),
    'Synthetic request enables no external tools'
);
gate5dAssert(
    $request['response_format']['json_schema']['strict'] === true,
    'Synthetic response contract requires strict JSON Schema'
);

$keyAvailable = gate5dHasLocalKey($root);

echo PHP_EOL;
echo 'GATE 5D EXTERNAL CANDIDATE OFFLINE VALIDATION PASSED.' . PHP_EOL;
echo 'Checks passed: ' . count($passedChecks) . PHP_EOL;
echo 'Provider: ' . GATE5D_PROVIDER . PHP_EOL;
echo 'Model: ' . GATE5D_MODEL . PHP_EOL;
echo 'Credential currently available: '
    . ($keyAvailable ? 'Yes (value not displayed)' : 'No (not required)')
    . PHP_EOL;
echo 'Fixture content read: 0' . PHP_EOL;
echo 'Query/evidence/chunk/vector content read: 0' . PHP_EOL;
echo 'Network or provider requests: 0' . PHP_EOL;
echo 'External evidence transmission authorized: No' . PHP_EOL;
echo 'Payload-manifest rows created: 0' . PHP_EOL;
echo 'Register/schema/database changes: 0' . PHP_EOL;
echo 'Model/provider selected for final architecture: No' . PHP_EOL;
echo 'This offline result never authorizes a provider request or rerun. '
    . 'Every live payload requires separate review and explicit approval.'
    . PHP_EOL;
