<?php

declare(strict_types=1);

const SUMSUG_V2_CANDIDATE = 'GEN-GROQ-GPT-OSS-120B-001';

/** @var array<string, string> */
const SUMSUG_V1_HASHES = [
    'artifact-manifest.csv' =>
        'A4D286AD0AA6951FED04B35C470624AC4F83FBBC57241E1BC551498A451F648C',
    'PAYLOAD_REVIEW.md' =>
        '01A2AF14F724FB561EC78A99173AE052B25E25F1CAD1794A7877EE7417ADAB6B',
    'payload-manifest-preview.csv' =>
        '63DFA94F6A8D0E34FDED44AFF913342F906E077704C409DEAF660DC3C594EB90',
    'preview-summary.json' =>
        'B8BA66F210B3001A5553F96BEB789219413E5199CCBEFEF2CC8D66258884A4A5',
    'provider-requests.jsonl' =>
        '1AB46109C874B2CEACD6FE7495EC56FB26C23114A6F7688D72953843680C173E',
    'READY_FOR_REVIEW.marker' =>
        '905793EA9604F802B6C03BE0AA67FC8459BA741760EA5189724631E0D9447CD8',
    'request-index.csv' =>
        '561D7802E841D8854F79D7934B28EB2A2E688940B060F4A1D27AB44641B7D8A8',
];

/** @var list<string> */
$sumsugV2Passed = [];

function sumsugV2Fail(string $message): never
{
    fwrite(STDERR, 'GATE 5E SCHEMA CORRECTION FAILED: ' . $message . PHP_EOL);
    exit(1);
}

function sumsugV2Assert(bool $condition, string $message): void
{
    global $sumsugV2Passed;
    if (!$condition) {
        sumsugV2Fail($message);
    }
    $sumsugV2Passed[] = $message;
    echo '[PASS] ' . $message . PHP_EOL;
}

function sumsugV2Hash(string $path): string
{
    $hash = hash_file('sha256', $path);
    if (!is_string($hash)) {
        sumsugV2Fail('Could not hash ' . basename($path));
    }
    return strtoupper($hash);
}

/** @return array<string, mixed> */
function sumsugV2Object(string $json, string $label): array
{
    try {
        $value = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        sumsugV2Fail($label . ' is not valid JSON.');
    }
    if (!is_array($value)) {
        sumsugV2Fail($label . ' is not a JSON object.');
    }
    return $value;
}

/** @return array{headers: list<string>, rows: list<array<string, string>>} */
function sumsugV2Csv(string $path): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        sumsugV2Fail('Could not open ' . basename($path));
    }
    try {
        $headers = fgetcsv($handle);
        if (!is_array($headers) || $headers === []) {
            sumsugV2Fail('CSV header is missing.');
        }
        $headers = array_map(static fn (mixed $v): string => (string) $v, $headers);
        $headers[0] = trim($headers[0], "\xEF\xBB\xBF\"");
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if ($values === [null] || $values === []) {
                continue;
            }
            if (count($values) !== count($headers)) {
                sumsugV2Fail('CSV row width mismatch.');
            }
            $row = array_combine($headers, array_map(
                static fn (mixed $v): string => (string) $v,
                $values
            ));
            if (!is_array($row)) {
                sumsugV2Fail('CSV row could not be mapped.');
            }
            $rows[] = $row;
        }
        return ['headers' => $headers, 'rows' => $rows];
    } finally {
        fclose($handle);
    }
}

/** @param list<string> $headers @param list<array<string, string>> $rows */
function sumsugV2CsvText(array $headers, array $rows): string
{
    $handle = fopen('php://temp', 'w+b');
    if ($handle === false) {
        sumsugV2Fail('Could not create CSV buffer.');
    }
    fputcsv($handle, $headers);
    foreach ($rows as $row) {
        fputcsv($handle, array_map(
            static fn (string $header): string => $row[$header] ?? '',
            $headers
        ));
    }
    rewind($handle);
    $text = stream_get_contents($handle);
    fclose($handle);
    if (!is_string($text)) {
        sumsugV2Fail('Could not read CSV buffer.');
    }
    return $text;
}

/** @param array<string, mixed> $value */
function sumsugV2Json(array $value): string
{
    return json_encode(
        $value,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ) . PHP_EOL;
}

/** @param mixed $value */
function sumsugV2KeywordCount(mixed $value, string $keyword): int
{
    if (!is_array($value)) {
        return 0;
    }
    $count = array_key_exists($keyword, $value) ? 1 : 0;
    foreach ($value as $child) {
        $count += sumsugV2KeywordCount($child, $keyword);
    }
    return $count;
}

/** @param array<string, mixed> $request @return array<string, mixed> */
function sumsugV2CorrectRequest(array $request, string $fixtureId): array
{
    $schema = $request['response_format']['json_schema']['schema'] ?? null;
    sumsugV2Assert(is_array($schema),
        'Strict response schema exists for ' . $fixtureId);
    $paths = ['controlled_tag_ids', 'unmapped_tag_terms', 'caveats'];
    foreach ($paths as $field) {
        sumsugV2Assert(
            ($schema['properties'][$field]['uniqueItems'] ?? null) === true,
            'v1 contains the unsupported uniqueItems keyword for '
                . $fixtureId . ' ' . $field
        );
        unset($request['response_format']['json_schema']['schema']
            ['properties'][$field]['uniqueItems']);
    }
    sumsugV2Assert(
        sumsugV2KeywordCount(
            $request['response_format']['json_schema']['schema'],
            'uniqueItems'
        ) === 0,
        'v2 removes every uniqueItems keyword for ' . $fixtureId
    );
    sumsugV2Assert(
        ($request['response_format']['json_schema']['strict'] ?? null) === true
        && ($request['model'] ?? null) === 'openai/gpt-oss-120b'
        && ($request['stream'] ?? null) === false
        && !array_key_exists('tools', $request),
        'v2 preserves strict mode, model, no streaming, and no tools for '
            . $fixtureId
    );
    return $request;
}

/** @param array<string, string> $files */
function sumsugV2Manifest(array $files): string
{
    $rows = [];
    foreach ($files as $name => $contents) {
        $rows[] = [
            'artifact' => $name,
            'bytes' => (string) strlen($contents),
            'sha256' => strtoupper(hash('sha256', $contents)),
        ];
    }
    return sumsugV2CsvText(['artifact', 'bytes', 'sha256'], $rows);
}

$mode = null;
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--mode=')) {
        $mode = substr($argument, strlen('--mode='));
    } else {
        sumsugV2Fail('Unknown argument: ' . $argument);
    }
}
sumsugV2Assert(in_array($mode, ['validate', 'apply'], true),
    'Mode is validate or apply');

$root = dirname(__DIR__, 2);
$v1 = $root . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR
    . 'ai-feasibility-spike' . DIRECTORY_SEPARATOR . 'results'
    . DIRECTORY_SEPARATOR . 'summary-suggestion-preview'
    . DIRECTORY_SEPARATOR . SUMSUG_V2_CANDIDATE
    . DIRECTORY_SEPARATOR . 'payload-review-v1';
sumsugV2Assert(is_dir($v1), 'Accepted v1 packet exists');
foreach (SUMSUG_V1_HASHES as $name => $hash) {
    $path = $v1 . DIRECTORY_SEPARATOR . $name;
    sumsugV2Assert(is_file($path) && sumsugV2Hash($path) === $hash,
        'Accepted v1 artifact is unchanged: ' . $name);
}

$requestLines = file(
    $v1 . DIRECTORY_SEPARATOR . 'provider-requests.jsonl',
    FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
);
if (!is_array($requestLines)) {
    sumsugV2Fail('Could not read v1 requests.');
}
$index = sumsugV2Csv($v1 . DIRECTORY_SEPARATOR . 'request-index.csv');
$manifests = sumsugV2Csv(
    $v1 . DIRECTORY_SEPARATOR . 'payload-manifest-preview.csv'
);
sumsugV2Assert(count($requestLines) === 8
    && count($index['rows']) === 8
    && count($manifests['rows']) === 8,
    'v1 request, index, and manifest rows reconcile 8/8');

$v2Lines = [];
$totalBytes = 0;
$estimatedInputTokens = 0;
foreach ($requestLines as $position => $json) {
    $row =& $index['rows'][$position];
    $fixtureId = $row['fixture_id'];
    $request = sumsugV2Object($json, 'v1 request ' . ($position + 1));
    $corrected = sumsugV2CorrectRequest($request, $fixtureId);
    $body = json_encode(
        $corrected,
        JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
    sumsugV2Assert($body !== $json,
        'v2 serialized request changes for ' . $fixtureId);
    $row['request_body_bytes'] = (string) strlen($body);
    $row['request_body_sha256'] = strtoupper(hash('sha256', $body));
    $row['estimated_input_tokens_ceiling'] =
        (string) ((int) ceil(strlen($body) / 4));
    $manifests['rows'][$position]['approximate_size'] = (string) strlen($body);
    $manifests['rows'][$position]['notes'] =
        'Versioned v2 compatibility preview; zero network requests; '
        . 'uniqueItems removed from provider schema only; uniqueness remains '
        . 'enforced by the live runner; not registered';
    $v2Lines[] = $body;
    $totalBytes += strlen($body);
    $estimatedInputTokens += (int) ceil(strlen($body) / 4);
}
unset($row);

$v1SummaryText = file_get_contents($v1 . DIRECTORY_SEPARATOR
    . 'preview-summary.json');
if (!is_string($v1SummaryText)) {
    sumsugV2Fail('Could not read v1 summary.');
}
$summary = sumsugV2Object($v1SummaryText, 'v1 preview summary');
$summary['schema_version'] = '1.1';
$summary['payload_revision'] = 2;
$summary['corrected_from'] = 'payload-review-v1';
$summary['correction_reason'] =
    'Groq HTTP 400 unsupported_uniqueItems before model generation';
$summary['correction_scope'] =
    'Removed only three unsupported uniqueItems schema keywords; '
    . 'runner-side uniqueness validation retained';
$summary['prepared_utc'] = gmdate('c');
$summary['total_request_body_bytes'] = $totalBytes;
$summary['estimated_input_tokens_ceiling'] = $estimatedInputTokens;
$summary['published_rate_worst_case_cost_usd'] = round(
    ($estimatedInputTokens / 1_000_000) * 0.15
    + (5600 / 1_000_000) * 0.60,
    8
);
$summary['network_requests_made'] = 0;
$summary['credential_reads'] = 0;
$summary['live_run_authorized'] = false;
$summary['candidate_selected'] = false;

$review = implode(PHP_EOL, [
    '# Gate 5E Exact Payload Review v2',
    '',
    'Candidate: `GEN-GROQ-GPT-OSS-120B-001`',
    'Model: `openai/gpt-oss-120b`',
    'Requests: 8; zero automatic retries',
    '',
    'Correction: removed only the unsupported JSON Schema `uniqueItems` '
        . 'keyword from three array fields.',
    'Uniqueness remains mandatory and is checked by the fail-closed live runner.',
    'Messages, synthetic document text, model, limits, strict mode, and all '
        . 'other response constraints remain unchanged.',
    'No request has been sent from this v2 packet. Separate renewed approval '
        . 'is required.',
    '',
]);
$files = [
    'provider-requests.jsonl' => implode(PHP_EOL, $v2Lines) . PHP_EOL,
    'request-index.csv' => sumsugV2CsvText($index['headers'], $index['rows']),
    'payload-manifest-preview.csv' => sumsugV2CsvText(
        $manifests['headers'],
        $manifests['rows']
    ),
    'PAYLOAD_REVIEW.md' => $review,
    'preview-summary.json' => sumsugV2Json($summary),
];

if ($mode === 'validate') {
    echo PHP_EOL;
    echo 'GATE 5E SCHEMA-COMPATIBILITY CORRECTION VALIDATION PASSED.' . PHP_EOL;
    echo 'Checks passed: ' . count($sumsugV2Passed) . PHP_EOL;
    echo 'Corrected requests computed: 8' . PHP_EOL;
    echo 'Provider requests: 0' . PHP_EOL;
    echo 'Credential reads: 0' . PHP_EOL;
    echo 'Live rerun authorized: No' . PHP_EOL;
    exit(0);
}

$v2 = dirname($v1) . DIRECTORY_SEPARATOR . 'payload-review-v2';
sumsugV2Assert(!file_exists($v2), 'Final v2 packet folder does not exist');
$partial = dirname($v1) . DIRECTORY_SEPARATOR . '.partial-v2-' . getmypid();
if (!mkdir($partial, 0775, false)) {
    sumsugV2Fail('Could not create partial v2 packet folder.');
}
foreach ($files as $name => $contents) {
    if (file_put_contents($partial . DIRECTORY_SEPARATOR . $name, $contents)
        !== strlen($contents)) {
        sumsugV2Fail('Could not write ' . $name);
    }
}
file_put_contents(
    $partial . DIRECTORY_SEPARATOR . 'artifact-manifest.csv',
    sumsugV2Manifest($files)
);
file_put_contents(
    $partial . DIRECTORY_SEPARATOR . 'READY_FOR_REVIEW.marker',
    "READY_FOR_HUMAN_PAYLOAD_REVIEW\nNETWORK_REQUESTS=0\n"
        . "LIVE_RUN_AUTHORIZED=NO\nRENEWED_APPROVAL_REQUIRED=YES\n"
);
if (!rename($partial, $v2)) {
    sumsugV2Fail('Could not finalize the v2 packet.');
}

echo PHP_EOL;
echo 'GATE 5E SCHEMA-COMPATIBILITY PAYLOAD v2 SAVED.' . PHP_EOL;
echo 'Folder: ' . $v2 . PHP_EOL;
echo 'Corrected requests: 8' . PHP_EOL;
echo 'Provider requests: 0' . PHP_EOL;
echo 'Credential reads: 0' . PHP_EOL;
echo 'Renewed live approval required: Yes' . PHP_EOL;
