<?php

declare(strict_types=1);

const SUMSUG_AUDIT_CANDIDATE = 'GEN-GROQ-GPT-OSS-120B-001';
const SUMSUG_AUDIT_MODEL = 'openai/gpt-oss-120b';

/** @var list<string> */
$passedChecks = [];

function sumsugAuditFail(string $message): never
{
    fwrite(STDERR, 'GATE 5E PAYLOAD AUDIT FAILED: ' . $message . PHP_EOL);
    exit(1);
}

function sumsugAuditPass(string $message): void
{
    global $passedChecks;
    $passedChecks[] = $message;
    echo '[PASS] ' . $message . PHP_EOL;
}

function sumsugAuditAssert(bool $condition, string $message): void
{
    if (!$condition) {
        sumsugAuditFail($message);
    }
    sumsugAuditPass($message);
}

function sumsugAuditHash(string $path): string
{
    $hash = hash_file('sha256', $path);
    if (!is_string($hash)) {
        sumsugAuditFail('Could not hash ' . $path);
    }
    return strtoupper($hash);
}

/** @return array{headers: list<string>, rows: list<array<string, string>>} */
function sumsugAuditCsv(string $path): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        sumsugAuditFail('Could not open CSV: ' . $path);
    }
    try {
        $headers = fgetcsv($handle);
        if (!is_array($headers) || $headers === []) {
            sumsugAuditFail('CSV header missing: ' . $path);
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
                sumsugAuditFail('CSV width mismatch: ' . $path);
            }
            $row = array_combine($headers, array_map(
                static fn (mixed $value): string => (string) $value,
                $values
            ));
            if (!is_array($row)) {
                sumsugAuditFail('Could not map CSV row: ' . $path);
            }
            $rows[] = $row;
        }
        return ['headers' => $headers, 'rows' => $rows];
    } finally {
        fclose($handle);
    }
}

/** @return array<string, mixed> */
function sumsugAuditJson(string $text, string $label): array
{
    if (str_starts_with($text, "\xEF\xBB\xBF")) {
        $text = substr($text, 3);
    }
    try {
        $decoded = json_decode($text, true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        sumsugAuditFail('Invalid JSON for ' . $label . ': '
            . $exception->getMessage());
    }
    if (!is_array($decoded)) {
        sumsugAuditFail('JSON root is not an object: ' . $label);
    }
    return $decoded;
}

$root = dirname(__DIR__, 2);
$packet = $root . DIRECTORY_SEPARATOR . '.local'
    . DIRECTORY_SEPARATOR . 'ai-feasibility-spike'
    . DIRECTORY_SEPARATOR . 'results'
    . DIRECTORY_SEPARATOR . 'summary-suggestion-preview'
    . DIRECTORY_SEPARATOR . SUMSUG_AUDIT_CANDIDATE
    . DIRECTORY_SEPARATOR . 'payload-review-v1';
sumsugAuditAssert(is_dir($packet), 'Expected payload-review folder exists');

$expectedFiles = [
    'PAYLOAD_REVIEW.md',
    'READY_FOR_REVIEW.marker',
    'artifact-manifest.csv',
    'payload-manifest-preview.csv',
    'preview-summary.json',
    'provider-requests.jsonl',
    'request-index.csv',
];
$actualFiles = [];
foreach (new DirectoryIterator($packet) as $entry) {
    if ($entry->isFile()) {
        $actualFiles[] = $entry->getFilename();
    }
}
sort($actualFiles);
sumsugAuditAssert($actualFiles === $expectedFiles,
    'Exact seven-file packet set is present');
sumsugAuditAssert(
    !is_file($packet . DIRECTORY_SEPARATOR . 'FAILED.marker')
    && !is_file($packet . DIRECTORY_SEPARATOR . 'PARTIAL.marker'),
    'Failed and partial markers are absent'
);

$manifestPath = $packet . DIRECTORY_SEPARATOR . 'artifact-manifest.csv';
$manifest = sumsugAuditCsv($manifestPath);
sumsugAuditAssert(count($manifest['rows']) === 5,
    'Artifact manifest has five content entries');
$manifestNames = [];
foreach ($manifest['rows'] as $row) {
    $name = $row['artifact'];
    $path = $packet . DIRECTORY_SEPARATOR . $name;
    sumsugAuditAssert(is_file($path), 'Manifest artifact exists: ' . $name);
    sumsugAuditAssert((string) filesize($path) === $row['bytes'],
        'Manifest size matches: ' . $name);
    sumsugAuditAssert(sumsugAuditHash($path) === $row['sha256'],
        'Manifest hash matches: ' . $name);
    $manifestNames[] = $name;
}
sort($manifestNames);
$expectedManifestNames = [
    'PAYLOAD_REVIEW.md',
    'payload-manifest-preview.csv',
    'preview-summary.json',
    'provider-requests.jsonl',
    'request-index.csv',
];
sumsugAuditAssert($manifestNames === $expectedManifestNames,
    'Manifest covers exactly the five content artifacts');

$marker = file_get_contents(
    $packet . DIRECTORY_SEPARATOR . 'READY_FOR_REVIEW.marker'
);
sumsugAuditAssert(
    $marker === "READY_FOR_HUMAN_PAYLOAD_REVIEW\n"
        . "NETWORK_REQUESTS=0\nLIVE_RUN_AUTHORIZED=NO\n",
    'Ready marker preserves zero-network and no-live-authorization boundary'
);

$summaryText = file_get_contents(
    $packet . DIRECTORY_SEPARATOR . 'preview-summary.json'
);
if (!is_string($summaryText)) {
    sumsugAuditFail('Could not read preview summary.');
}
$summary = sumsugAuditJson($summaryText, 'preview-summary.json');
$summaryExpected = [
    'provider' => 'GroqCloud',
    'candidate_configuration_id' => SUMSUG_AUDIT_CANDIDATE,
    'model' => SUMSUG_AUDIT_MODEL,
    'prompt_template_version' => 'SUMMARY-SUGGESTION-v1',
    'request_count' => 8,
    'automatic_retries' => 0,
    'minimum_spacing_seconds_for_future_run' => 65,
    'max_completion_tokens_per_request' => 700,
    'maximum_completion_tokens_total' => 5600,
    'network_requests_made' => 0,
    'credential_reads' => 0,
    'live_run_authorized' => false,
    'candidate_selected' => false,
];
foreach ($summaryExpected as $field => $value) {
    sumsugAuditAssert(($summary[$field] ?? null) === $value,
        'Preview summary field matches: ' . $field);
}
sumsugAuditAssert(
    is_int($summary['estimated_input_tokens_ceiling'] ?? null)
    && $summary['estimated_input_tokens_ceiling'] < 50000,
    'Input-token planning ceiling remains below 50,000'
);
sumsugAuditAssert(
    is_float($summary['published_rate_worst_case_cost_usd'] ?? null)
    && $summary['published_rate_worst_case_cost_usd'] < 0.02,
    'Published-rate worst-case cost remains below USD 0.02'
);

$index = sumsugAuditCsv(
    $packet . DIRECTORY_SEPARATOR . 'request-index.csv'
);
$payloadPreview = sumsugAuditCsv(
    $packet . DIRECTORY_SEPARATOR . 'payload-manifest-preview.csv'
);
sumsugAuditAssert(count($index['rows']) === 8,
    'Request index has eight rows');
sumsugAuditAssert(count($payloadPreview['rows']) === 8,
    'Payload-manifest preview has eight rows');

$requestPath = $packet . DIRECTORY_SEPARATOR . 'provider-requests.jsonl';
$requestLines = file($requestPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (!is_array($requestLines)) {
    sumsugAuditFail('Could not read provider request JSONL.');
}
sumsugAuditAssert(count($requestLines) === 8,
    'Provider request JSONL has eight exact request bodies');

$fixtureRegister = sumsugAuditCsv(
    $root . DIRECTORY_SEPARATOR . 'docs'
    . DIRECTORY_SEPARATOR . 'ai-feasibility-spike'
    . DIRECTORY_SEPARATOR . 'registers'
    . DIRECTORY_SEPARATOR . 'fixtures.csv'
);
$fixtureById = [];
foreach ($fixtureRegister['rows'] as $fixture) {
    $fixtureById[$fixture['fixture_id']] = $fixture;
}
$seenFixtureIds = [];
$totalBodyBytes = 0;
$totalTokenCeiling = 0;

foreach ($requestLines as $offset => $line) {
    $sequence = $offset + 1;
    $indexRow = $index['rows'][$offset];
    $previewRow = $payloadPreview['rows'][$offset];
    sumsugAuditAssert((int) $indexRow['request_sequence'] === $sequence,
        'Request sequence is contiguous: ' . $sequence);
    sumsugAuditAssert(strlen($line) === (int) $indexRow['request_body_bytes'],
        'Request body byte count matches: ' . $sequence);
    sumsugAuditAssert(
        strtoupper(hash('sha256', $line)) === $indexRow['request_body_sha256'],
        'Request body hash matches: ' . $sequence
    );
    $totalBodyBytes += strlen($line);
    $totalTokenCeiling += (int) $indexRow['estimated_input_tokens_ceiling'];
    $request = sumsugAuditJson($line, 'provider request ' . $sequence);
    sumsugAuditAssert(($request['model'] ?? null) === SUMSUG_AUDIT_MODEL,
        'Model is exact: ' . $sequence);
    sumsugAuditAssert(($request['temperature'] ?? null) === 0,
        'Temperature is zero: ' . $sequence);
    sumsugAuditAssert(($request['reasoning_effort'] ?? null) === 'low',
        'Reasoning effort is low: ' . $sequence);
    sumsugAuditAssert(
        ($request['max_completion_tokens'] ?? null) === 700
        && ($request['stream'] ?? null) === false,
        'Completion ceiling and non-streaming mode match: ' . $sequence
    );
    sumsugAuditAssert(!array_key_exists('tools', $request),
        'No tools are enabled: ' . $sequence);
    sumsugAuditAssert(
        ($request['response_format']['type'] ?? null) === 'json_schema'
        && ($request['response_format']['json_schema']['strict'] ?? null) === true
        && ($request['response_format']['json_schema']['schema']['additionalProperties'] ?? null) === false,
        'Strict top-level JSON Schema is enforced: ' . $sequence
    );
    $messages = $request['messages'] ?? null;
    sumsugAuditAssert(
        is_array($messages)
        && count($messages) === 2
        && ($messages[0]['role'] ?? null) === 'system'
        && ($messages[1]['role'] ?? null) === 'user',
        'Exactly one system and one user message are present: ' . $sequence
    );
    $bodyText = $line;
    sumsugAuditAssert(
        !str_contains($bodyText, 'REF-SUMSUG-')
        && !str_contains($bodyText, 'Expected tags')
        && !str_contains($bodyText, 'Unsupported or overconfident metadata'),
        'Human answer key is absent: ' . $sequence
    );
    sumsugAuditAssert(
        preg_match('/\bgsk_[A-Za-z0-9_-]{16,}\b/', $bodyText) !== 1,
        'No key-shaped secret is present: ' . $sequence
    );
    $userPrompt = $messages[1]['content'] ?? null;
    sumsugAuditAssert(is_string($userPrompt),
        'User prompt is text: ' . $sequence);
    $fixtureId = $indexRow['fixture_id'];
    sumsugAuditAssert(!isset($seenFixtureIds[$fixtureId]),
        'Fixture appears once: ' . $fixtureId);
    $seenFixtureIds[$fixtureId] = true;
    sumsugAuditAssert(isset($fixtureById[$fixtureId]),
        'Fixture is registered: ' . $fixtureId);
    $fixture = $fixtureById[$fixtureId];
    sumsugAuditAssert(
        $fixture['fixture_set'] === 'primary-readable'
        && $fixture['review_status'] === 'Accepted - manually reviewed'
        && $fixture['contains_personal_or_sensitive_information'] === 'No',
        'Fixture remains accepted synthetic primary-readable: ' . $fixtureId
    );
    sumsugAuditAssert(
        str_contains($userPrompt, 'FIXTURE ID: ' . $fixtureId)
        && str_contains(
            $userPrompt,
            'SOURCE VERSION: ' . $indexRow['source_version_id']
        )
        && str_contains($userPrompt, 'DOCUMENT TEXT:'),
        'Prompt identity and document boundary match: ' . $fixtureId
    );
    sumsugAuditAssert(
        $previewRow['payload_manifest_id'] === $indexRow['payload_manifest_id']
        && $previewRow['fixture_ids'] === $fixtureId
        && $previewRow['external_transmission_authorization_basis']
            === 'Not authorized - exact local payload preview and review only'
        && $previewRow['personal_or_account_linked_information_included'] === 'No',
        'Payload preview remains unauthorized and non-personal: ' . $fixtureId
    );
}

sumsugAuditAssert(count($seenFixtureIds) === 8,
    'Eight unique fixture requests are preserved');
sumsugAuditAssert(
    $totalBodyBytes === ($summary['total_request_body_bytes'] ?? null),
    'Total serialized request bytes reconcile'
);
sumsugAuditAssert(
    $totalTokenCeiling === ($summary['estimated_input_tokens_ceiling'] ?? null),
    'Total conservative input-token ceiling reconciles'
);

$reviewText = file_get_contents(
    $packet . DIRECTORY_SEPARATOR . 'PAYLOAD_REVIEW.md'
);
sumsugAuditAssert(
    is_string($reviewText)
    && str_contains($reviewText, 'No request has been sent.')
    && str_contains($reviewText, 'does not authorize external transmission'),
    'Human review notice preserves the no-send boundary'
);

echo PHP_EOL;
echo 'GATE 5E SAVED PAYLOAD REVIEW PACKET AUDIT PASSED.' . PHP_EOL;
echo 'Checks passed: ' . count($passedChecks) . PHP_EOL;
echo 'Packet files: 7/7' . PHP_EOL;
echo 'Manifest entries: 5/5' . PHP_EOL;
echo 'Exact request bodies: 8/8' . PHP_EOL;
echo 'Unique accepted synthetic fixtures: 8/8' . PHP_EOL;
echo 'Network/provider requests: 0' . PHP_EOL;
echo 'Credential reads: 0' . PHP_EOL;
echo 'Accepted payload-register rows created: 0' . PHP_EOL;
echo 'Live generation authorized: No' . PHP_EOL;
echo 'Candidate selected: No' . PHP_EOL;
echo 'Next action: human review of the exact packet, current provider controls, '
    . 'and one-run ceiling before any live-request approval.' . PHP_EOL;
