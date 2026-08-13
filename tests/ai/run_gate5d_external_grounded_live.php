<?php

declare(strict_types=1);

const EXT_LIVE_PROVIDER = 'GroqCloud';
const EXT_LIVE_MODEL = 'openai/gpt-oss-120b';
const EXT_LIVE_CANDIDATE = 'GEN-GROQ-GPT-OSS-120B-001';
const EXT_LIVE_ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';
const EXT_LIVE_APPROVAL = 'TRANSMIT_6_REVIEWED_SYNTHETIC_CASES';
const EXT_LIVE_CASES = 6;
const EXT_LIVE_SPACING_SECONDS = 65;
const EXT_LIVE_MAX_COMPLETION_TOKENS = 400;

/** @var array<string, string> */
const EXT_LIVE_PACKET_HASHES = [
    'provider-requests.jsonl' =>
        'C7FB888A79D9D038FB8BD440CEA5D556C42039C38CBC0952603AB75A0FEE0CDB',
    'request-index.csv' =>
        '7AFC8DC86FED60E110680DBDAAF3E5715EECBE6DC5218E022763866A48211DB9',
    'payload-manifest-preview.csv' =>
        '10876A90F18246E5822842BCD59270A304C87303EC70A72022C4E4A21C91CEAA',
    'PAYLOAD_REVIEW.md' =>
        'EAA064D0791EEBDC63037891124EB8CBABB1C58F4358DDAE5B886BD0F9A18247',
    'preview-summary.json' =>
        'E2E750035B5B3884F97AC0B33AEA8C181357E99506F9F41B8133D6D81F9F2FB2',
];

/** @var array<string, string> */
const EXT_LIVE_SOURCE_HASHES = [
    '.local/ai-feasibility-spike/results/grounded-inquiry/'
        . 'GEN-GROUNDED-OLLAMA-QWEN35-4B-CLAIMS-004/'
        . 'run-20260802-053419Z/case-payloads.jsonl' =>
        '33B8499C3F0931B9A1B63ECED7CCCC5881D483EBD3B19E78ED50F7501FC404DF',
    '.local/ai-feasibility-spike/results/grounded-inquiry/'
        . 'GEN-GROUNDED-OLLAMA-QWEN35-4B-DIVERSE-005/'
        . 'run-20260802-053900Z/case-payloads.jsonl' =>
        '5BAB3BD26E1DF41240302F92CD597FCB2563DB99131842394469C1C20830D5C3',
    'docs/ai-feasibility-spike/registers/fixtures.csv' =>
        '648D47A2F882D2E419A6E46E1B99EC62BD373EEFFA0C44A0845D3985F4AC8080',
    'docs/ai-feasibility-spike/registers/queries.csv' =>
        '7D212A05932FEC0803F81754100A4523BC191FAF97BE19CA055504530D2CF5BE',
    'docs/ai-feasibility-spike/registers/expected_evidence.csv' =>
        'B57A07AED948F0DA7BBF59954AC4046EFFA4B8853EA16B67AC554C77D900DACA',
    'docs/ai-feasibility-spike/registers/payload_manifests.csv' =>
        'F90FB0AD7A467A1C8C4F4EEE135AC86534A99C5517A387A20BD795030FE18275',
];

/** @var list<string> */
const EXT_LIVE_QUERY_IDS = [
    'Q-INQ-001',
    'Q-SEM-004',
    'Q-NOEVID-001',
    'Q-PROHIB-001',
    'Q-MULTI-001',
    'Q-PART-005',
];

/** @var array<string, string> */
const EXT_LIVE_EXPECTED_OUTCOMES = [
    'Q-INQ-001' => 'answered',
    'Q-SEM-004' => 'answered',
    'Q-NOEVID-001' => 'insufficient_evidence',
    'Q-PROHIB-001' => 'refused',
    'Q-MULTI-001' => 'answered',
    'Q-PART-005' => 'partially_answered',
];

/** @var list<string> */
$extLivePassed = [];
$extLiveRunFolder = null;
$extLiveRequestsCompleted = 0;

function extLiveFail(string $message): never
{
    global $extLiveRunFolder, $extLiveRequestsCompleted;

    if (is_string($extLiveRunFolder) && is_dir($extLiveRunFolder)) {
        $failure = [
            'status' => 'failed_preserved_not_ready',
            'failure_category' => $message,
            'requests_completed' => $extLiveRequestsCompleted,
            'automatic_retries' => 0,
            'candidate_selected' => false,
            'register_rows_created' => 0,
            'failed_at_utc' => gmdate('c'),
        ];
        @file_put_contents(
            $extLiveRunFolder . DIRECTORY_SEPARATOR . 'failure-summary.json',
            extLiveJson($failure)
        );
        @file_put_contents(
            $extLiveRunFolder . DIRECTORY_SEPARATOR . 'FAILED.marker',
            "FAILED_PRESERVED\nREADY=NO\nAUTOMATIC_RETRIES=0\n"
        );
        @unlink($extLiveRunFolder . DIRECTORY_SEPARATOR . 'PARTIAL.marker');
    }

    fwrite(STDERR, 'GATE 5D EXTERNAL GROUNDED RUNNER FAILED: '
        . $message . PHP_EOL);
    exit(1);
}

function extLivePass(string $message): void
{
    global $extLivePassed;
    $extLivePassed[] = $message;
    echo '[PASS] ' . $message . PHP_EOL;
}

function extLiveAssert(bool $condition, string $message): void
{
    if (!$condition) {
        extLiveFail($message);
    }
    extLivePass($message);
}

/** @param array<string, mixed> $value */
function extLiveJson(array $value): string
{
    return json_encode(
        $value,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ) . PHP_EOL;
}

function extLiveHash(string $path): string
{
    $hash = hash_file('sha256', $path);
    if (!is_string($hash)) {
        extLiveFail('Could not hash an accepted input artifact.');
    }
    return strtoupper($hash);
}

function extLiveWrite(string $path, string $contents): void
{
    $written = file_put_contents($path, $contents);
    if ($written !== strlen($contents)) {
        extLiveFail('Could not write complete ignored local evidence: '
            . basename($path));
    }
}

/** @return list<array<string, string>> */
function extLiveCsv(string $path): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        extLiveFail('Could not open a required CSV artifact.');
    }
    try {
        $headers = fgetcsv($handle);
        if (!is_array($headers) || $headers === []) {
            extLiveFail('A required CSV header is missing.');
        }
        $headers = array_map(static fn (mixed $v): string => (string) $v, $headers);
        $headers[0] = trim($headers[0], "\xEF\xBB\xBF\"");
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if ($values === [null] || $values === []) {
                continue;
            }
            if (count($values) !== count($headers)) {
                extLiveFail('A required CSV row has the wrong width.');
            }
            $row = array_combine($headers, array_map(
                static fn (mixed $v): string => (string) $v,
                $values
            ));
            if (!is_array($row)) {
                extLiveFail('A required CSV row could not be mapped.');
            }
            $rows[] = $row;
        }
        return $rows;
    } finally {
        fclose($handle);
    }
}

/** @return list<string> */
function extLiveLines(string $path): array
{
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        extLiveFail('Could not load reviewed request bodies.');
    }
    return array_values($lines);
}

/** @return array<string, mixed> */
function extLiveDecodeObject(string $json, string $label): array
{
    try {
        $decoded = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        extLiveFail($label . ' is not valid JSON.');
    }
    if (!is_array($decoded)) {
        extLiveFail($label . ' is not a JSON object.');
    }
    return $decoded;
}

function extLiveReadKey(string $root): string
{
    $process = getenv('GROQ_API_KEY');
    if (is_string($process) && trim($process) !== '') {
        $key = trim($process);
    } else {
        $path = $root . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($path) || !is_readable($path)) {
            extLiveFail('The ignored local .env file is unavailable.');
        }
        $key = '';
        $matches = 0;
        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (!preg_match('/^\s*GROQ_API_KEY\s*=\s*(.+)\s*$/', $line, $match)) {
                continue;
            }
            $matches++;
            $key = trim($match[1], " \t\n\r\0\x0B\"'");
        }
        if ($matches !== 1) {
            extLiveFail('Expected exactly one GROQ_API_KEY entry.');
        }
    }
    if (!preg_match('/^gsk_[A-Za-z0-9_-]{16,}$/', $key)) {
        extLiveFail('The configured Groq credential has an invalid shape.');
    }
    return $key;
}

/**
 * @return array{
 *   packet: string,
 *   requests: list<string>,
 *   request_objects: list<array<string, mixed>>,
 *   index: list<array<string, string>>,
 *   summary: array<string, mixed>
 * }
 */
function extLiveValidateInputs(string $root): array
{
    foreach (EXT_LIVE_SOURCE_HASHES as $relativePath => $expectedHash) {
        $path = $root . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        extLiveAssert(is_file($path),
            'Accepted source exists: ' . basename($relativePath));
        extLiveAssert(extLiveHash($path) === $expectedHash,
            'Accepted source hash matches: ' . basename($relativePath));
    }

    $packet = $root . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR
        . 'ai-feasibility-spike' . DIRECTORY_SEPARATOR . 'results'
        . DIRECTORY_SEPARATOR . 'external-grounded-preview'
        . DIRECTORY_SEPARATOR . EXT_LIVE_CANDIDATE
        . DIRECTORY_SEPARATOR . 'payload-review-v1';
    extLiveAssert(is_dir($packet), 'Reviewed local payload packet exists');

    foreach (EXT_LIVE_PACKET_HASHES as $name => $expectedHash) {
        $path = $packet . DIRECTORY_SEPARATOR . $name;
        extLiveAssert(is_file($path), 'Reviewed artifact exists: ' . $name);
        extLiveAssert(extLiveHash($path) === $expectedHash,
            'Reviewed artifact hash matches: ' . $name);
    }

    $marker = file_get_contents($packet . DIRECTORY_SEPARATOR
        . 'READY_FOR_REVIEW.marker');
    extLiveAssert(
        $marker === "READY_FOR_HUMAN_REVIEW\nNETWORK_REQUESTS=0\nLIVE_RUN_AUTHORIZED=NO\n",
        'Review marker confirms zero prior requests and no authorization'
    );

    $summaryText = file_get_contents($packet . DIRECTORY_SEPARATOR
        . 'preview-summary.json');
    extLiveAssert(is_string($summaryText), 'Preview summary is readable');
    $summary = extLiveDecodeObject($summaryText, 'Preview summary');
    extLiveAssert(
        ($summary['status'] ?? null) === 'ready_for_human_review_not_authorized'
        && ($summary['model'] ?? null) === EXT_LIVE_MODEL
        && ($summary['case_count'] ?? null) === EXT_LIVE_CASES
        && ($summary['query_ids'] ?? null) === EXT_LIVE_QUERY_IDS
        && ($summary['maximum_requests'] ?? null) === EXT_LIVE_CASES
        && ($summary['automatic_retries'] ?? null) === 0
        && ($summary['minimum_spacing_seconds'] ?? null)
            === EXT_LIVE_SPACING_SECONDS
        && ($summary['network_requests_made'] ?? null) === 0
        && ($summary['payload_register_rows_created'] ?? null) === 0
        && ($summary['live_run_authorized'] ?? null) === false
        && ($summary['candidate_selected'] ?? null) === false,
        'Preview summary preserves all request and decision boundaries'
    );

    $requests = extLiveLines($packet . DIRECTORY_SEPARATOR
        . 'provider-requests.jsonl');
    $index = extLiveCsv($packet . DIRECTORY_SEPARATOR . 'request-index.csv');
    extLiveAssert(count($requests) === EXT_LIVE_CASES,
        'Exactly six reviewed request bodies are loaded');
    extLiveAssert(count($index) === EXT_LIVE_CASES,
        'Exactly six request-index rows are loaded');

    $objects = [];
    foreach ($requests as $position => $json) {
        $sequence = $position + 1;
        $object = extLiveDecodeObject($json, 'Reviewed request ' . $sequence);
        $row = $index[$position];
        $queryId = $row['query_id'] ?? '';
        extLiveAssert($queryId === EXT_LIVE_QUERY_IDS[$position],
            'Request order is fixed at sequence ' . $sequence);
        extLiveAssert(($row['expected_model_outcome'] ?? '')
            === EXT_LIVE_EXPECTED_OUTCOMES[$queryId],
            'Expected outcome is fixed for ' . $queryId);
        extLiveAssert((int) ($row['request_body_bytes'] ?? -1) === strlen($json),
            'Reviewed request byte count matches for ' . $queryId);
        extLiveAssert(strtoupper((string) ($row['request_body_sha256'] ?? ''))
            === strtoupper(hash('sha256', $json)),
            'Reviewed request hash matches for ' . $queryId);
        extLiveAssert(($object['model'] ?? null) === EXT_LIVE_MODEL,
            'Model remains restricted for ' . $queryId);
        extLiveAssert(($object['stream'] ?? null) === false,
            'Streaming remains disabled for ' . $queryId);
        extLiveAssert(!array_key_exists('tools', $object),
            'No tools are enabled for ' . $queryId);
        extLiveAssert(($object['response_format']['json_schema']['strict'] ?? null)
            === true, 'Strict JSON Schema remains required for ' . $queryId);
        extLiveAssert(($object['max_completion_tokens'] ?? null)
            === EXT_LIVE_MAX_COMPLETION_TOKENS,
            'Completion limit remains fixed for ' . $queryId);
        $objects[] = $object;
    }

    $acceptedRegister = $root . DIRECTORY_SEPARATOR . 'docs'
        . DIRECTORY_SEPARATOR . 'ai-feasibility-spike'
        . DIRECTORY_SEPARATOR . 'registers'
        . DIRECTORY_SEPARATOR . 'payload_manifests.csv';
    extLiveAssert(extLiveCsv($acceptedRegister) === [],
        'Accepted payload register remains empty');

    return [
        'packet' => $packet,
        'requests' => $requests,
        'request_objects' => $objects,
        'index' => $index,
        'summary' => $summary,
    ];
}

/**
 * @return array{body: string, http_status: int, elapsed_ms: float}
 */
function extLiveSend(string $key, string $payload): array
{
    $curl = curl_init(EXT_LIVE_ENDPOINT);
    if ($curl === false) {
        extLiveFail('cURL initialization failed.');
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
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS => 0,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
    ]);
    $started = hrtime(true);
    $body = curl_exec($curl);
    $elapsed = (hrtime(true) - $started) / 1_000_000;
    $error = curl_error($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if (!is_string($body)) {
        extLiveFail('Provider transport failure: '
            . ($error !== '' ? 'cURL transport error' : 'unknown transport error'));
    }
    return ['body' => $body, 'http_status' => $status, 'elapsed_ms' => $elapsed];
}

/**
 * @param array<string, mixed> $envelope
 * @param list<string> $allowedLabels
 * @return array<string, mixed>
 */
function extLiveValidateResponse(
    array $envelope,
    string $queryId,
    string $expectedOutcome,
    array $allowedLabels
): array {
    $returnedModel = $envelope['model'] ?? null;
    extLiveAssert(is_string($returnedModel)
        && str_contains($returnedModel, 'gpt-oss-120b'),
        'Provider identifies the requested model for ' . $queryId);
    $content = $envelope['choices'][0]['message']['content'] ?? null;
    extLiveAssert(is_string($content),
        'Structured message content exists for ' . $queryId);
    $result = extLiveDecodeObject($content, 'Model content for ' . $queryId);
    $resultKeys = array_keys($result);
    sort($resultKeys, SORT_STRING);
    extLiveAssert($resultKeys === [
        'outcome', 'supported_points', 'unsupported_portion', 'user_message',
    ], 'Structured response has the exact four fields for ' . $queryId);
    extLiveAssert(($result['outcome'] ?? null) === $expectedOutcome,
        'Expected bounded outcome is returned for ' . $queryId);
    extLiveAssert(is_array($result['supported_points'] ?? null),
        'Supported points are an array for ' . $queryId);
    extLiveAssert(is_string($result['unsupported_portion'] ?? null)
        && is_string($result['user_message'] ?? null),
        'Bounded text fields are strings for ' . $queryId);

    foreach ($result['supported_points'] as $point) {
        $pointKeys = is_array($point) ? array_keys($point) : [];
        sort($pointKeys, SORT_STRING);
        extLiveAssert(is_array($point)
            && $pointKeys === ['source_labels', 'text']
            && is_string($point['text'] ?? null)
            && trim($point['text']) !== ''
            && is_array($point['source_labels'] ?? null)
            && $point['source_labels'] !== [],
            'Each supported point is nonempty and source-labeled for ' . $queryId);
        foreach ($point['source_labels'] as $label) {
            extLiveAssert(is_string($label) && in_array($label, $allowedLabels, true),
                'Every returned source label was supplied for ' . $queryId);
        }
    }

    $points = count($result['supported_points']);
    if ($expectedOutcome === 'answered') {
        extLiveAssert($points > 0
            && $result['unsupported_portion'] === ''
            && $result['user_message'] === '',
            'Answered case obeys the bounded field contract for ' . $queryId);
    } elseif ($expectedOutcome === 'partially_answered') {
        extLiveAssert($points > 0
            && trim($result['unsupported_portion']) !== ''
            && $result['user_message'] === '',
            'Partial case preserves its unsupported portion for ' . $queryId);
    } else {
        extLiveAssert($points === 0 && trim($result['user_message']) !== '',
            'No-answer/refusal case returns no supported claims for ' . $queryId);
    }

    $joinedText = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    extLiveAssert(!preg_match(
        '/\b(page|slide|paragraph|line|heading)\s*\d+\b/i',
        $joinedText
    ), 'Response invents no numeric locator for ' . $queryId);

    $usage = $envelope['usage'] ?? null;
    extLiveAssert(is_array($usage), 'Usage metadata exists for ' . $queryId);
    foreach (['prompt_tokens', 'completion_tokens', 'total_tokens'] as $field) {
        extLiveAssert(isset($usage[$field]) && is_int($usage[$field])
            && $usage[$field] >= 0,
            'Usage includes ' . $field . ' for ' . $queryId);
    }
    extLiveAssert($usage['prompt_tokens'] <= 6000,
        'Prompt usage stays within the planning ceiling for ' . $queryId);
    extLiveAssert($usage['completion_tokens'] <= EXT_LIVE_MAX_COMPLETION_TOKENS,
        'Completion usage stays within the fixed ceiling for ' . $queryId);
    return ['model_output' => $result, 'usage' => $usage];
}

/** @param array<string, string> $files */
function extLiveManifest(array $files): string
{
    $text = "artifact,bytes,sha256\n";
    foreach ($files as $name => $contents) {
        $row = [$name, (string) strlen($contents),
            strtoupper(hash('sha256', $contents))];
        $handle = fopen('php://temp', 'w+b');
        if ($handle === false) {
            extLiveFail('Could not create manifest buffer.');
        }
        fputcsv($handle, $row);
        rewind($handle);
        $line = stream_get_contents($handle);
        fclose($handle);
        if (!is_string($line)) {
            extLiveFail('Could not read manifest buffer.');
        }
        $text .= $line;
    }
    return $text;
}

$mode = null;
$approval = null;
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--mode=')) {
        $mode = substr($argument, strlen('--mode='));
    } elseif (str_starts_with($argument, '--approve=')) {
        $approval = substr($argument, strlen('--approve='));
    } else {
        extLiveFail('Unknown argument: ' . $argument);
    }
}
extLiveAssert(in_array($mode, ['validate', 'apply'], true),
    'Mode is validate or apply');
$root = dirname(__DIR__, 2);
$inputs = extLiveValidateInputs($root);

if ($mode === 'validate') {
    extLiveAssert($approval === null,
        'Offline validation includes no transmission approval token');
    extLiveAssert(function_exists('curl_init'), 'PHP cURL support is available');
    echo PHP_EOL;
    echo 'GATE 5D EXTERNAL GROUNDED LIVE RUNNER OFFLINE VALIDATION PASSED.' . PHP_EOL;
    echo 'Checks passed: ' . count($extLivePassed) . PHP_EOL;
    echo 'Reviewed cases: 6' . PHP_EOL;
    echo 'Provider requests made: 0' . PHP_EOL;
    echo 'Automatic retries implemented: 0' . PHP_EOL;
    echo 'API key read during validation: No' . PHP_EOL;
    echo 'Live transmission authorized: No' . PHP_EOL;
    echo 'Candidate selected or registered: No' . PHP_EOL;
    echo 'Next action: review this runner, then obtain separate explicit '
        . 'approval before apply mode.' . PHP_EOL;
    exit(0);
}

extLiveAssert($approval === EXT_LIVE_APPROVAL,
    'Apply mode includes the exact six-case transmission token');
$key = extLiveReadKey($root);
extLivePass('Project-specific Groq key is available (value not displayed)');

$runParent = $root . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR
    . 'ai-feasibility-spike' . DIRECTORY_SEPARATOR . 'results'
    . DIRECTORY_SEPARATOR . 'external-grounded'
    . DIRECTORY_SEPARATOR . EXT_LIVE_CANDIDATE;
if (!is_dir($runParent) && !mkdir($runParent, 0775, true) && !is_dir($runParent)) {
    extLiveFail('Could not create the ignored local run parent.');
}
$runId = 'run-' . gmdate('Ymd-His') . 'Z';
$extLiveRunFolder = $runParent . DIRECTORY_SEPARATOR . $runId;
extLiveAssert(!file_exists($extLiveRunFolder), 'New run folder does not exist');
if (!mkdir($extLiveRunFolder, 0775, false)) {
    extLiveFail('Could not create the new ignored local run folder.');
}
extLiveWrite($extLiveRunFolder . DIRECTORY_SEPARATOR . 'PARTIAL.marker',
    "PARTIAL_NOT_READY\nAUTOMATIC_RETRIES=0\n");

$requestEvidence = '';
$responseEvidence = '';
$outputEvidence = '';
$caseRows = [];
$totalPrompt = 0;
$totalCompletion = 0;
$totalTokens = 0;
$latencies = [];
$startedUtc = gmdate('c');

foreach ($inputs['requests'] as $position => $payload) {
    $sequence = $position + 1;
    $row = $inputs['index'][$position];
    $queryId = $row['query_id'];
    if ($position > 0) {
        sleep(EXT_LIVE_SPACING_SECONDS);
    }
    $requestEvidence .= json_encode([
        'request_sequence' => $sequence,
        'query_id' => $queryId,
        'payload_manifest_id' => $row['payload_manifest_id'],
        'request_body_sha256' => strtoupper(hash('sha256', $payload)),
        'request' => $inputs['request_objects'][$position],
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    extLiveWrite($extLiveRunFolder . DIRECTORY_SEPARATOR
        . 'requests.jsonl', $requestEvidence);

    $sentAt = gmdate('c');
    $transport = extLiveSend($key, $payload);
    $receivedAt = gmdate('c');
    $responseEvidence .= json_encode([
        'request_sequence' => $sequence,
        'query_id' => $queryId,
        'http_status' => $transport['http_status'],
        'elapsed_ms' => round($transport['elapsed_ms'], 3),
        'sent_at_utc' => $sentAt,
        'received_at_utc' => $receivedAt,
        'response_body' => $transport['body'],
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    extLiveWrite($extLiveRunFolder . DIRECTORY_SEPARATOR
        . 'response-envelopes.jsonl', $responseEvidence);
    if ($transport['http_status'] !== 200) {
        extLiveFail('Provider returned a non-200 response at request '
            . $sequence . '; body preserved locally but not printed.');
    }
    $envelope = extLiveDecodeObject($transport['body'],
        'Provider response envelope for ' . $queryId);
    $evidenceCount = (int) ($row['evidence_count'] ?? 0);
    $labels = [];
    for ($i = 1; $i <= $evidenceCount; $i++) {
        $labels[] = 'S' . $i;
    }
    $validated = extLiveValidateResponse(
        $envelope,
        $queryId,
        EXT_LIVE_EXPECTED_OUTCOMES[$queryId],
        $labels
    );
    $extLiveRequestsCompleted++;
    $usage = $validated['usage'];
    $totalPrompt += $usage['prompt_tokens'];
    $totalCompletion += $usage['completion_tokens'];
    $totalTokens += $usage['total_tokens'];
    $latencies[] = $transport['elapsed_ms'];
    $outputEvidence .= json_encode([
        'request_sequence' => $sequence,
        'query_id' => $queryId,
        'expected_outcome' => EXT_LIVE_EXPECTED_OUTCOMES[$queryId],
        'model_output' => $validated['model_output'],
        'usage' => $usage,
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    extLiveWrite($extLiveRunFolder . DIRECTORY_SEPARATOR
        . 'model-outputs.jsonl', $outputEvidence);
    $caseRows[] = [
        'request_sequence' => $sequence,
        'query_id' => $queryId,
        'expected_outcome' => EXT_LIVE_EXPECTED_OUTCOMES[$queryId],
        'returned_outcome' => $validated['model_output']['outcome'],
        'http_status' => 200,
        'elapsed_ms' => round($transport['elapsed_ms'], 3),
        'prompt_tokens' => $usage['prompt_tokens'],
        'completion_tokens' => $usage['completion_tokens'],
        'total_tokens' => $usage['total_tokens'],
        'schema_and_boundary_status' => 'passed_pending_manual_quality_review',
    ];
    echo sprintf('[%d/6] %s accepted; %.3f ms%s', $sequence, $queryId,
        $transport['elapsed_ms'], PHP_EOL);
}

sort($latencies, SORT_NUMERIC);
$median = ($latencies[2] + $latencies[3]) / 2;
$withinThirty = count(array_filter($latencies,
    static fn (float $v): bool => $v <= 30000));
$summary = [
    'status' => 'ready_for_independent_audit_not_registered',
    'run_id' => $runId,
    'provider' => EXT_LIVE_PROVIDER,
    'model' => EXT_LIVE_MODEL,
    'candidate_id' => EXT_LIVE_CANDIDATE,
    'started_at_utc' => $startedUtc,
    'completed_at_utc' => gmdate('c'),
    'requests_planned' => 6,
    'requests_completed' => $extLiveRequestsCompleted,
    'automatic_retries' => 0,
    'minimum_spacing_seconds' => EXT_LIVE_SPACING_SECONDS,
    'prompt_tokens' => $totalPrompt,
    'completion_tokens' => $totalCompletion,
    'total_tokens' => $totalTokens,
    'estimated_published_cost_usd' => round(
        ($totalPrompt / 1_000_000) * 0.15
        + ($totalCompletion / 1_000_000) * 0.60,
        8
    ),
    'median_latency_ms' => round($median, 3),
    'queries_within_30_seconds' => $withinThirty,
    'schema_and_outcome_contracts_passed' => 6,
    'manual_claim_support_review_completed' => false,
    'manual_usefulness_review_completed' => false,
    'candidate_selected' => false,
    'register_rows_created' => 0,
    'application_integration_authorized' => false,
];

$csv = "request_sequence,query_id,expected_outcome,returned_outcome,http_status,elapsed_ms,prompt_tokens,completion_tokens,total_tokens,schema_and_boundary_status\n";
foreach ($caseRows as $caseRow) {
    $handle = fopen('php://temp', 'w+b');
    if ($handle === false) {
        extLiveFail('Could not create results CSV buffer.');
    }
    fputcsv($handle, array_values($caseRow));
    rewind($handle);
    $line = stream_get_contents($handle);
    fclose($handle);
    if (!is_string($line)) {
        extLiveFail('Could not read results CSV buffer.');
    }
    $csv .= $line;
}
$files = [
    'requests.jsonl' => $requestEvidence,
    'response-envelopes.jsonl' => $responseEvidence,
    'model-outputs.jsonl' => $outputEvidence,
    'per-case-results.csv' => $csv,
    'run-summary.json' => extLiveJson($summary),
    'RUN_INFO.txt' => "STATUS=READY_FOR_INDEPENDENT_AUDIT_NOT_REGISTERED\n"
        . "REQUESTS=6\nAUTOMATIC_RETRIES=0\nCANDIDATE_SELECTED=NO\n",
];
foreach ($files as $name => $contents) {
    extLiveWrite($extLiveRunFolder . DIRECTORY_SEPARATOR . $name, $contents);
}
extLiveWrite($extLiveRunFolder . DIRECTORY_SEPARATOR
    . 'artifact-manifest.csv', extLiveManifest($files));
@unlink($extLiveRunFolder . DIRECTORY_SEPARATOR . 'PARTIAL.marker');
extLiveWrite($extLiveRunFolder . DIRECTORY_SEPARATOR . 'READY.marker',
    "READY_FOR_INDEPENDENT_AUDIT\nREGISTERED=NO\nCANDIDATE_SELECTED=NO\n");

echo PHP_EOL;
echo 'GATE 5D EXTERNAL GROUNDED EVIDENCE SAVED.' . PHP_EOL;
echo 'Run folder: ' . $extLiveRunFolder . PHP_EOL;
echo 'Requests completed: 6/6' . PHP_EOL;
echo 'Automatic retries: 0' . PHP_EOL;
echo 'Median latency ms: ' . number_format($median, 3, '.', '') . PHP_EOL;
echo 'Within 30 seconds: ' . $withinThirty . '/6' . PHP_EOL;
echo 'Manual quality review completed: No' . PHP_EOL;
echo 'Candidate selected or registered: No' . PHP_EOL;
echo 'Next action: independently audit the saved evidence before any decision.'
    . PHP_EOL;
