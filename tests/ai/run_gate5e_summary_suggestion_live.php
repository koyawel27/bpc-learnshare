<?php

declare(strict_types=1);

const SUMSUG_LIVE_PROVIDER = 'GroqCloud';
const SUMSUG_LIVE_MODEL = 'openai/gpt-oss-120b';
const SUMSUG_LIVE_CANDIDATE = 'GEN-GROQ-GPT-OSS-120B-001';
const SUMSUG_LIVE_ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';
const SUMSUG_LIVE_APPROVAL =
    'RETRY_8_REVIEWED_SYNTHETIC_SUMMARY_SUGGESTION_V2_CASES';
const SUMSUG_LIVE_REQUESTS = 8;
const SUMSUG_LIVE_SPACING_SECONDS = 65;
const SUMSUG_LIVE_MAX_COMPLETION_TOKENS = 700;

/** @var array<string, string> */
const SUMSUG_LIVE_PACKET_HASHES = [
    'artifact-manifest.csv' =>
        'BF9BCC51431D4CEF54BD98719429859CBADE9D2DBF77E5745B9A5B0525D7400B',
    'PAYLOAD_REVIEW.md' =>
        '3D2A6A9A9D154A0E5DA75B6B503BB9D94ED07AE197959AC77BA8608C65AD05C8',
    'payload-manifest-preview.csv' =>
        'EEF34B45F3114B81FB30035A50BDB542D2DF8CAC879EA3DECAB478C568DCB98C',
    'preview-summary.json' =>
        'C38D8A634451CDD5FCC668600B32153082EB2C3E1B82ED4C1CAC46391EDB62A6',
    'provider-requests.jsonl' =>
        'E0BD4AB88C4EFF315C92C83C1B9AF1E55F6D61CEA2BB6B2AB3DDC4B9B1AD4C9F',
    'READY_FOR_REVIEW.marker' =>
        '47298EC3B2092062DF08D04985DBDDD765ABD34A1A6B07920B3EB610BCE3FE23',
    'request-index.csv' =>
        '11FC900B879C51FE6E80DD3D1DBF8034850838A5750B03F758403B3207C9D641',
];

/** @var list<string> */
const SUMSUG_LIVE_FIXTURES = [
    'FX-PDF-001',
    'FX-PDF-005',
    'FX-DOCX-002',
    'FX-DOCX-003',
    'FX-PPTX-004',
    'FX-PPTX-006',
    'FX-TXT-001',
    'FX-TXT-005',
];

/** @var list<string> */
const SUMSUG_LIVE_ACTIVE_TAGS = [
    'TAG-DEMO-DATABASE',
    'TAG-DEMO-PROGRAMMING',
    'TAG-DEMO-RESEARCH',
    'TAG-DEMO-SECURITY',
    'TAG-DEMO-USABILITY',
];

/** @var list<string> */
const SUMSUG_LIVE_SUBJECTS = [
    'Database Management Systems',
    'Research Methods',
    'Systems Analysis and Design',
    'Web Systems and Technologies',
];

/** @var list<string> */
const SUMSUG_LIVE_RESOURCE_TYPES = [
    'Handout', 'Module', 'Notes', 'Presentation', 'Reviewer', 'Study Guide',
];

/** @var list<string> */
$sumsugLivePassed = [];
$sumsugLiveRunFolder = null;
$sumsugLiveRequestsCompleted = 0;

function sumsugLiveFail(string $message): never
{
    global $sumsugLiveRunFolder, $sumsugLiveRequestsCompleted;

    if (is_string($sumsugLiveRunFolder) && is_dir($sumsugLiveRunFolder)) {
        $failure = [
            'status' => 'failed_preserved_not_ready',
            'failure_category' => $message,
            'requests_completed' => $sumsugLiveRequestsCompleted,
            'automatic_retries' => 0,
            'candidate_selected' => false,
            'register_rows_created' => 0,
            'failed_at_utc' => gmdate('c'),
        ];
        @file_put_contents(
            $sumsugLiveRunFolder . DIRECTORY_SEPARATOR . 'failure-summary.json',
            sumsugLiveJson($failure)
        );
        @file_put_contents(
            $sumsugLiveRunFolder . DIRECTORY_SEPARATOR . 'FAILED.marker',
            "FAILED_PRESERVED\nREADY=NO\nAUTOMATIC_RETRIES=0\n"
        );
        @unlink($sumsugLiveRunFolder . DIRECTORY_SEPARATOR . 'PARTIAL.marker');
    }

    fwrite(STDERR, 'GATE 5E SUMMARY/SUGGESTION LIVE RUNNER FAILED: '
        . $message . PHP_EOL);
    exit(1);
}

function sumsugLivePass(string $message): void
{
    global $sumsugLivePassed;
    $sumsugLivePassed[] = $message;
    echo '[PASS] ' . $message . PHP_EOL;
}

function sumsugLiveAssert(bool $condition, string $message): void
{
    if (!$condition) {
        sumsugLiveFail($message);
    }
    sumsugLivePass($message);
}

/** @param array<string, mixed> $value */
function sumsugLiveJson(array $value): string
{
    return json_encode(
        $value,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ) . PHP_EOL;
}

function sumsugLiveHash(string $path): string
{
    $hash = hash_file('sha256', $path);
    if (!is_string($hash)) {
        sumsugLiveFail('Could not hash an accepted input artifact.');
    }
    return strtoupper($hash);
}

function sumsugLiveWrite(string $path, string $contents): void
{
    if (file_put_contents($path, $contents) !== strlen($contents)) {
        sumsugLiveFail('Could not write complete ignored local evidence: '
            . basename($path));
    }
}

/** @return list<array<string, string>> */
function sumsugLiveCsv(string $path): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        sumsugLiveFail('Could not open required CSV: ' . basename($path));
    }
    try {
        $headers = fgetcsv($handle);
        if (!is_array($headers) || $headers === []) {
            sumsugLiveFail('Required CSV header is missing.');
        }
        $headers = array_map(static fn (mixed $v): string => (string) $v, $headers);
        $headers[0] = trim($headers[0], "\xEF\xBB\xBF\"");
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if ($values === [null] || $values === []) {
                continue;
            }
            if (count($values) !== count($headers)) {
                sumsugLiveFail('Required CSV row width mismatch.');
            }
            $row = array_combine($headers, array_map(
                static fn (mixed $v): string => (string) $v,
                $values
            ));
            if (!is_array($row)) {
                sumsugLiveFail('Required CSV row could not be mapped.');
            }
            $rows[] = $row;
        }
        return $rows;
    } finally {
        fclose($handle);
    }
}

/** @return list<string> */
function sumsugLiveLines(string $path): array
{
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        sumsugLiveFail('Could not load reviewed request bodies.');
    }
    return array_values($lines);
}

/** @return array<string, mixed> */
function sumsugLiveObject(string $json, string $label): array
{
    try {
        $decoded = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        sumsugLiveFail($label . ' is not valid JSON.');
    }
    if (!is_array($decoded)) {
        sumsugLiveFail($label . ' is not a JSON object.');
    }
    return $decoded;
}

function sumsugLiveReadKey(string $root): string
{
    $process = getenv('GROQ_API_KEY');
    if (is_string($process) && trim($process) !== '') {
        $key = trim($process);
    } else {
        $path = $root . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($path) || !is_readable($path)) {
            sumsugLiveFail('The ignored local .env file is unavailable.');
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
            sumsugLiveFail('Expected exactly one GROQ_API_KEY entry.');
        }
    }
    if (!preg_match('/^gsk_[A-Za-z0-9_-]{16,}$/', $key)) {
        sumsugLiveFail('The configured Groq credential has an invalid shape.');
    }
    return $key;
}

/**
 * @return array{
 *   requests: list<string>,
 *   objects: list<array<string, mixed>>,
 *   index: list<array<string, string>>
 * }
 */
function sumsugLiveValidateInputs(string $root): array
{
    $packet = $root . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR
        . 'ai-feasibility-spike' . DIRECTORY_SEPARATOR . 'results'
        . DIRECTORY_SEPARATOR . 'summary-suggestion-preview'
        . DIRECTORY_SEPARATOR . SUMSUG_LIVE_CANDIDATE
        . DIRECTORY_SEPARATOR . 'payload-review-v2';
    sumsugLiveAssert(is_dir($packet), 'Reviewed Gate 5E payload packet exists');

    $actualNames = array_map(
        'basename',
        glob($packet . DIRECTORY_SEPARATOR . '*') ?: []
    );
    sort($actualNames, SORT_STRING);
    $expectedNames = array_keys(SUMSUG_LIVE_PACKET_HASHES);
    sort($expectedNames, SORT_STRING);
    sumsugLiveAssert($actualNames === $expectedNames,
        'Reviewed packet has the exact seven-file set');

    foreach (SUMSUG_LIVE_PACKET_HASHES as $name => $expectedHash) {
        $path = $packet . DIRECTORY_SEPARATOR . $name;
        sumsugLiveAssert(is_file($path), 'Reviewed artifact exists: ' . $name);
        sumsugLiveAssert(sumsugLiveHash($path) === $expectedHash,
            'Reviewed artifact hash matches: ' . $name);
    }

    $marker = file_get_contents($packet . DIRECTORY_SEPARATOR
        . 'READY_FOR_REVIEW.marker');
    sumsugLiveAssert(
        $marker === "READY_FOR_HUMAN_PAYLOAD_REVIEW\n"
            . "NETWORK_REQUESTS=0\nLIVE_RUN_AUTHORIZED=NO\n"
            . "RENEWED_APPROVAL_REQUIRED=YES\n",
        'Packet marker confirms zero prior requests and no embedded authorization'
    );

    $summaryText = file_get_contents($packet . DIRECTORY_SEPARATOR
        . 'preview-summary.json');
    sumsugLiveAssert(is_string($summaryText), 'Preview summary is readable');
    $summary = sumsugLiveObject($summaryText, 'Preview summary');
    sumsugLiveAssert(
        ($summary['provider'] ?? null) === SUMSUG_LIVE_PROVIDER
        && ($summary['candidate_configuration_id'] ?? null)
            === SUMSUG_LIVE_CANDIDATE
        && ($summary['model'] ?? null) === SUMSUG_LIVE_MODEL
        && ($summary['endpoint'] ?? null) === SUMSUG_LIVE_ENDPOINT
        && ($summary['request_count'] ?? null) === SUMSUG_LIVE_REQUESTS
        && ($summary['payload_revision'] ?? null) === 2
        && ($summary['corrected_from'] ?? null) === 'payload-review-v1'
        && ($summary['automatic_retries'] ?? null) === 0
        && ($summary['minimum_spacing_seconds_for_future_run'] ?? null)
            === SUMSUG_LIVE_SPACING_SECONDS
        && ($summary['max_completion_tokens_per_request'] ?? null)
            === SUMSUG_LIVE_MAX_COMPLETION_TOKENS
        && ($summary['network_requests_made'] ?? null) === 0
        && ($summary['live_run_authorized'] ?? null) === false
        && ($summary['candidate_selected'] ?? null) === false,
        'Preview summary preserves the exact request and decision boundaries'
    );

    $requests = sumsugLiveLines($packet . DIRECTORY_SEPARATOR
        . 'provider-requests.jsonl');
    $index = sumsugLiveCsv($packet . DIRECTORY_SEPARATOR . 'request-index.csv');
    sumsugLiveAssert(count($requests) === SUMSUG_LIVE_REQUESTS,
        'Exactly eight reviewed request bodies are loaded');
    sumsugLiveAssert(count($index) === SUMSUG_LIVE_REQUESTS,
        'Exactly eight request-index rows are loaded');

    $objects = [];
    foreach ($requests as $position => $json) {
        $sequence = $position + 1;
        $row = $index[$position];
        $fixtureId = $row['fixture_id'] ?? '';
        $object = sumsugLiveObject($json, 'Reviewed request ' . $sequence);
        sumsugLiveAssert($fixtureId === SUMSUG_LIVE_FIXTURES[$position],
            'Fixture order is frozen at request ' . $sequence);
        sumsugLiveAssert((int) ($row['request_sequence'] ?? 0) === $sequence,
            'Request sequence is exact for ' . $fixtureId);
        sumsugLiveAssert((int) ($row['request_body_bytes'] ?? -1) === strlen($json),
            'Reviewed byte count matches for ' . $fixtureId);
        sumsugLiveAssert(strtoupper((string) ($row['request_body_sha256'] ?? ''))
            === strtoupper(hash('sha256', $json)),
            'Reviewed request hash matches for ' . $fixtureId);
        sumsugLiveAssert(($object['model'] ?? null) === SUMSUG_LIVE_MODEL,
            'Model remains restricted for ' . $fixtureId);
        sumsugLiveAssert(($object['stream'] ?? null) === false,
            'Streaming remains disabled for ' . $fixtureId);
        sumsugLiveAssert(!array_key_exists('tools', $object),
            'No tools are enabled for ' . $fixtureId);
        sumsugLiveAssert(($object['temperature'] ?? null) === 0,
            'Temperature remains zero for ' . $fixtureId);
        sumsugLiveAssert(($object['reasoning_effort'] ?? null) === 'low',
            'Reasoning effort remains low for ' . $fixtureId);
        sumsugLiveAssert(($object['response_format']['json_schema']['strict'] ?? null)
            === true, 'Strict JSON Schema remains required for ' . $fixtureId);
        foreach (['controlled_tag_ids', 'unmapped_tag_terms', 'caveats'] as $field) {
            sumsugLiveAssert(
                !array_key_exists(
                    'uniqueItems',
                    $object['response_format']['json_schema']['schema']
                        ['properties'][$field]
                ),
                'Provider-incompatible uniqueItems is absent for '
                    . $fixtureId . ' ' . $field
            );
        }
        sumsugLiveAssert(($object['max_completion_tokens'] ?? null)
            === SUMSUG_LIVE_MAX_COMPLETION_TOKENS,
            'Completion ceiling remains fixed for ' . $fixtureId);
        $objects[] = $object;
    }

    return ['requests' => $requests, 'objects' => $objects, 'index' => $index];
}

/** @return array{body: string, http_status: int, elapsed_ms: float, headers: array<string, string>} */
function sumsugLiveSend(string $key, string $payload): array
{
    $curl = curl_init(SUMSUG_LIVE_ENDPOINT);
    if ($curl === false) {
        sumsugLiveFail('cURL initialization failed.');
    }
    $rateHeaders = [];
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
        CURLOPT_TIMEOUT => 60,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS => 0,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$rateHeaders): int {
            $length = strlen($line);
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $name = strtolower(trim($parts[0]));
                if (str_starts_with($name, 'x-ratelimit-') || $name === 'retry-after') {
                    $rateHeaders[$name] = trim($parts[1]);
                }
            }
            return $length;
        },
    ]);
    $started = hrtime(true);
    $body = curl_exec($curl);
    $elapsed = (hrtime(true) - $started) / 1_000_000;
    $error = curl_error($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if (!is_string($body)) {
        sumsugLiveFail('Provider transport failure: '
            . ($error !== '' ? 'cURL transport error' : 'unknown transport error'));
    }
    ksort($rateHeaders, SORT_STRING);
    return [
        'body' => $body,
        'http_status' => $status,
        'elapsed_ms' => $elapsed,
        'headers' => $rateHeaders,
    ];
}

/** @param array<string, mixed> $field @param list<string> $allowedValues */
function sumsugLiveValidateControlledField(
    array $field,
    array $allowedValues,
    string $label
): void {
    $keys = array_keys($field);
    sort($keys, SORT_STRING);
    sumsugLiveAssert($keys === ['basis', 'status', 'value'],
        $label . ' has the exact controlled-field schema');
    $status = $field['status'] ?? null;
    $value = $field['value'] ?? null;
    $basis = $field['basis'] ?? null;
    sumsugLiveAssert(in_array($status, ['suggested', 'not_reliably_inferable'], true)
        && is_string($value) && is_string($basis) && strlen($basis) <= 240,
        $label . ' uses valid status, value, and basis types');
    if ($status === 'suggested') {
        sumsugLiveAssert(in_array($value, $allowedValues, true) && trim($basis) !== '',
            $label . ' suggested value is controlled and supported by a basis');
    } else {
        sumsugLiveAssert($value === '',
            $label . ' leaves value empty when not reliably inferable');
    }
}

/** @param array<string, mixed> $envelope @return array<string, mixed> */
function sumsugLiveValidateResponse(array $envelope, string $fixtureId): array
{
    $returnedModel = $envelope['model'] ?? null;
    sumsugLiveAssert(is_string($returnedModel)
        && str_contains($returnedModel, 'gpt-oss-120b'),
        'Provider identifies the requested model for ' . $fixtureId);
    $content = $envelope['choices'][0]['message']['content'] ?? null;
    sumsugLiveAssert(is_string($content),
        'Structured message content exists for ' . $fixtureId);
    $output = sumsugLiveObject($content, 'Model content for ' . $fixtureId);
    $keys = array_keys($output);
    sort($keys, SORT_STRING);
    sumsugLiveAssert($keys === [
        'caveats', 'controlled_tag_ids', 'metadata', 'summary',
        'unmapped_tag_terms',
    ], 'Structured output has the exact five fields for ' . $fixtureId);

    $summary = $output['summary'] ?? null;
    sumsugLiveAssert(is_array($summary) && array_keys($summary) === ['text']
        && is_string($summary['text']) && trim($summary['text']) !== ''
        && strlen($summary['text']) <= 1200,
        'Summary text is nonempty and within the schema ceiling for ' . $fixtureId);
    $words = preg_split('/\s+/u', trim($summary['text'])) ?: [];
    sumsugLiveAssert(count($words) <= 120,
        'Summary stays within 120 words for ' . $fixtureId);

    $tags = $output['controlled_tag_ids'] ?? null;
    sumsugLiveAssert(is_array($tags) && count($tags) <= 3,
        'Controlled tag list is unique and bounded for ' . $fixtureId);
    $seenTags = [];
    foreach ($tags as $tag) {
        sumsugLiveAssert(is_string($tag)
            && in_array($tag, SUMSUG_LIVE_ACTIVE_TAGS, true),
            'Every returned controlled tag is Active for ' . $fixtureId);
        sumsugLiveAssert(!in_array($tag, $seenTags, true),
            'Controlled tags are unique for ' . $fixtureId);
        $seenTags[] = $tag;
    }

    $unmapped = $output['unmapped_tag_terms'] ?? null;
    sumsugLiveAssert(is_array($unmapped) && count($unmapped) <= 3,
        'Unmapped tag list is unique and bounded for ' . $fixtureId);
    $seenTerms = [];
    foreach ($unmapped as $term) {
        sumsugLiveAssert(is_string($term) && strlen($term) <= 80,
            'Every unmapped tag term stays within its ceiling for ' . $fixtureId);
        sumsugLiveAssert(!in_array($term, $seenTerms, true),
            'Unmapped tag terms are unique for ' . $fixtureId);
        $seenTerms[] = $term;
    }

    $metadata = $output['metadata'] ?? null;
    $metadataKeys = is_array($metadata) ? array_keys($metadata) : [];
    sort($metadataKeys, SORT_STRING);
    sumsugLiveAssert(is_array($metadata)
        && $metadataKeys === ['resource_type', 'subject', 'topic'],
        'Metadata has only the three approved fields for ' . $fixtureId);
    sumsugLiveAssert(is_array($metadata['subject'] ?? null),
        'Subject metadata is an object for ' . $fixtureId);
    sumsugLiveAssert(is_array($metadata['resource_type'] ?? null),
        'Resource-type metadata is an object for ' . $fixtureId);
    sumsugLiveAssert(is_array($metadata['topic'] ?? null),
        'Topic metadata is an object for ' . $fixtureId);
    sumsugLiveValidateControlledField(
        $metadata['subject'], SUMSUG_LIVE_SUBJECTS, $fixtureId . ' subject'
    );
    sumsugLiveValidateControlledField(
        $metadata['resource_type'],
        SUMSUG_LIVE_RESOURCE_TYPES,
        $fixtureId . ' resource type'
    );
    $topic = $metadata['topic'];
    $topicKeys = array_keys($topic);
    sort($topicKeys, SORT_STRING);
    sumsugLiveAssert($topicKeys === ['basis', 'status', 'value']
        && in_array($topic['status'] ?? null,
            ['suggested', 'not_reliably_inferable'], true)
        && is_string($topic['value'] ?? null)
        && is_string($topic['basis'] ?? null)
        && strlen($topic['value']) <= 160
        && strlen($topic['basis']) <= 240,
        'Topic uses the exact bounded metadata schema for ' . $fixtureId);
    if ($topic['status'] === 'suggested') {
        sumsugLiveAssert(trim($topic['value']) !== '' && trim($topic['basis']) !== '',
            'Suggested topic includes a value and source-supported basis for '
                . $fixtureId);
    } else {
        sumsugLiveAssert($topic['value'] === '',
            'Topic value is empty when not reliably inferable for ' . $fixtureId);
    }

    $caveats = $output['caveats'] ?? null;
    sumsugLiveAssert(is_array($caveats) && count($caveats) <= 3,
        'Caveat list is unique and bounded for ' . $fixtureId);
    $seenCaveats = [];
    foreach ($caveats as $caveat) {
        sumsugLiveAssert(is_string($caveat) && strlen($caveat) <= 180,
            'Every caveat stays within its ceiling for ' . $fixtureId);
        sumsugLiveAssert(!in_array($caveat, $seenCaveats, true),
            'Caveats are unique for ' . $fixtureId);
        $seenCaveats[] = $caveat;
    }

    $usage = $envelope['usage'] ?? null;
    sumsugLiveAssert(is_array($usage), 'Usage metadata exists for ' . $fixtureId);
    foreach (['prompt_tokens', 'completion_tokens', 'total_tokens'] as $field) {
        sumsugLiveAssert(isset($usage[$field]) && is_int($usage[$field])
            && $usage[$field] >= 0,
            'Usage includes ' . $field . ' for ' . $fixtureId);
    }
    sumsugLiveAssert($usage['prompt_tokens'] <= 6000,
        'Prompt usage stays within the planning ceiling for ' . $fixtureId);
    sumsugLiveAssert($usage['completion_tokens'] <= SUMSUG_LIVE_MAX_COMPLETION_TOKENS,
        'Completion usage stays within the fixed ceiling for ' . $fixtureId);
    return ['model_output' => $output, 'usage' => $usage];
}

/** @param array<string, string> $files */
function sumsugLiveManifest(array $files): string
{
    $text = "artifact,bytes,sha256\n";
    foreach ($files as $name => $contents) {
        $handle = fopen('php://temp', 'w+b');
        if ($handle === false) {
            sumsugLiveFail('Could not create manifest buffer.');
        }
        fputcsv($handle, [
            $name,
            (string) strlen($contents),
            strtoupper(hash('sha256', $contents)),
        ]);
        rewind($handle);
        $line = stream_get_contents($handle);
        fclose($handle);
        if (!is_string($line)) {
            sumsugLiveFail('Could not read manifest buffer.');
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
        sumsugLiveFail('Unknown argument: ' . $argument);
    }
}
sumsugLiveAssert(in_array($mode, ['validate', 'apply'], true),
    'Mode is validate or apply');
$root = dirname(__DIR__, 2);
$inputs = sumsugLiveValidateInputs($root);

if ($mode === 'validate') {
    sumsugLiveAssert($approval === null,
        'Offline validation includes no transmission approval token');
    sumsugLiveAssert(function_exists('curl_init'), 'PHP cURL support is available');
    echo PHP_EOL;
    echo 'GATE 5E SUMMARY/SUGGESTION LIVE RUNNER OFFLINE VALIDATION PASSED.'
        . PHP_EOL;
    echo 'Checks passed: ' . count($sumsugLivePassed) . PHP_EOL;
    echo 'Reviewed synthetic requests: 8' . PHP_EOL;
    echo 'Provider requests made: 0' . PHP_EOL;
    echo 'Automatic retries implemented: 0' . PHP_EOL;
    echo 'API key read during validation: No' . PHP_EOL;
    echo 'Candidate selected or registered: No' . PHP_EOL;
    exit(0);
}

sumsugLiveAssert($approval === SUMSUG_LIVE_APPROVAL,
    'Apply mode includes the exact eight-request transmission token');
$key = sumsugLiveReadKey($root);
sumsugLivePass('Project-specific Groq key is available (value not displayed)');

$runParent = $root . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR
    . 'ai-feasibility-spike' . DIRECTORY_SEPARATOR . 'results'
    . DIRECTORY_SEPARATOR . 'summary-suggestion'
    . DIRECTORY_SEPARATOR . SUMSUG_LIVE_CANDIDATE;
if (!is_dir($runParent) && !mkdir($runParent, 0775, true) && !is_dir($runParent)) {
    sumsugLiveFail('Could not create the ignored local run parent.');
}
$runId = 'run-' . gmdate('Ymd-His') . 'Z';
$sumsugLiveRunFolder = $runParent . DIRECTORY_SEPARATOR . $runId;
sumsugLiveAssert(!file_exists($sumsugLiveRunFolder), 'New run folder does not exist');
if (!mkdir($sumsugLiveRunFolder, 0775, false)) {
    sumsugLiveFail('Could not create the ignored local run folder.');
}
sumsugLiveWrite($sumsugLiveRunFolder . DIRECTORY_SEPARATOR . 'PARTIAL.marker',
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
    $fixtureId = $row['fixture_id'];
    if ($position > 0) {
        echo sprintf('[WAIT] Holding %d seconds before request %d/8.%s',
            SUMSUG_LIVE_SPACING_SECONDS, $sequence, PHP_EOL);
        sleep(SUMSUG_LIVE_SPACING_SECONDS);
    }
    $requestEvidence .= json_encode([
        'request_sequence' => $sequence,
        'fixture_id' => $fixtureId,
        'source_version_id' => $row['source_version_id'],
        'payload_manifest_id' => $row['payload_manifest_id'],
        'request_body_sha256' => strtoupper(hash('sha256', $payload)),
        'request' => $inputs['objects'][$position],
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    sumsugLiveWrite($sumsugLiveRunFolder . DIRECTORY_SEPARATOR
        . 'requests.jsonl', $requestEvidence);

    $sentAt = gmdate('c');
    $transport = sumsugLiveSend($key, $payload);
    $receivedAt = gmdate('c');
    $responseEvidence .= json_encode([
        'request_sequence' => $sequence,
        'fixture_id' => $fixtureId,
        'http_status' => $transport['http_status'],
        'elapsed_ms' => round($transport['elapsed_ms'], 3),
        'sent_at_utc' => $sentAt,
        'received_at_utc' => $receivedAt,
        'rate_limit_headers' => $transport['headers'],
        'response_body' => $transport['body'],
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    sumsugLiveWrite($sumsugLiveRunFolder . DIRECTORY_SEPARATOR
        . 'response-envelopes.jsonl', $responseEvidence);
    if ($transport['http_status'] !== 200) {
        sumsugLiveFail('Provider returned a non-200 response at request '
            . $sequence . '; body preserved locally but not printed.');
    }
    $envelope = sumsugLiveObject(
        $transport['body'], 'Provider response envelope for ' . $fixtureId
    );
    $validated = sumsugLiveValidateResponse($envelope, $fixtureId);
    $sumsugLiveRequestsCompleted++;
    $usage = $validated['usage'];
    $totalPrompt += $usage['prompt_tokens'];
    $totalCompletion += $usage['completion_tokens'];
    $totalTokens += $usage['total_tokens'];
    $latencies[] = $transport['elapsed_ms'];
    $outputEvidence .= json_encode([
        'request_sequence' => $sequence,
        'fixture_id' => $fixtureId,
        'source_version_id' => $row['source_version_id'],
        'model_output' => $validated['model_output'],
        'usage' => $usage,
    ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    sumsugLiveWrite($sumsugLiveRunFolder . DIRECTORY_SEPARATOR
        . 'model-outputs.jsonl', $outputEvidence);
    $caseRows[] = [
        'request_sequence' => $sequence,
        'fixture_id' => $fixtureId,
        'source_version_id' => $row['source_version_id'],
        'file_type' => $row['file_type'],
        'http_status' => 200,
        'elapsed_ms' => round($transport['elapsed_ms'], 3),
        'prompt_tokens' => $usage['prompt_tokens'],
        'completion_tokens' => $usage['completion_tokens'],
        'total_tokens' => $usage['total_tokens'],
        'schema_and_boundary_status' =>
            'passed_pending_manual_quality_review',
    ];
    echo sprintf('[%d/8] %s accepted; %.3f ms%s',
        $sequence, $fixtureId, $transport['elapsed_ms'], PHP_EOL);
}
$key = '';

sort($latencies, SORT_NUMERIC);
$median = ($latencies[3] + $latencies[4]) / 2;
$withinFifteen = count(array_filter(
    $latencies,
    static fn (float $value): bool => $value <= 15000
));
$summary = [
    'status' => 'ready_for_independent_audit_not_registered',
    'run_id' => $runId,
    'provider' => SUMSUG_LIVE_PROVIDER,
    'model' => SUMSUG_LIVE_MODEL,
    'candidate_id' => SUMSUG_LIVE_CANDIDATE,
    'prompt_template_version' => 'SUMMARY-SUGGESTION-v1',
    'started_at_utc' => $startedUtc,
    'completed_at_utc' => gmdate('c'),
    'requests_planned' => SUMSUG_LIVE_REQUESTS,
    'requests_completed' => $sumsugLiveRequestsCompleted,
    'automatic_retries' => 0,
    'minimum_spacing_seconds' => SUMSUG_LIVE_SPACING_SECONDS,
    'prompt_tokens' => $totalPrompt,
    'completion_tokens' => $totalCompletion,
    'total_tokens' => $totalTokens,
    'estimated_published_cost_usd' => round(
        ($totalPrompt / 1_000_000) * 0.15
        + ($totalCompletion / 1_000_000) * 0.60,
        8
    ),
    'median_latency_ms' => round($median, 3),
    'requests_within_15_seconds' => $withinFifteen,
    'schema_and_boundary_contracts_passed' => SUMSUG_LIVE_REQUESTS,
    'manual_summary_quality_review_completed' => false,
    'manual_tag_quality_review_completed' => false,
    'manual_metadata_quality_review_completed' => false,
    'candidate_selected' => false,
    'register_rows_created' => 0,
    'taxonomy_or_resource_rows_changed' => 0,
    'application_integration_authorized' => false,
];

$csv = "request_sequence,fixture_id,source_version_id,file_type,http_status,elapsed_ms,prompt_tokens,completion_tokens,total_tokens,schema_and_boundary_status\n";
foreach ($caseRows as $caseRow) {
    $handle = fopen('php://temp', 'w+b');
    if ($handle === false) {
        sumsugLiveFail('Could not create results CSV buffer.');
    }
    fputcsv($handle, array_values($caseRow));
    rewind($handle);
    $line = stream_get_contents($handle);
    fclose($handle);
    if (!is_string($line)) {
        sumsugLiveFail('Could not read results CSV buffer.');
    }
    $csv .= $line;
}

$files = [
    'requests.jsonl' => $requestEvidence,
    'response-envelopes.jsonl' => $responseEvidence,
    'model-outputs.jsonl' => $outputEvidence,
    'per-case-results.csv' => $csv,
    'run-summary.json' => sumsugLiveJson($summary),
    'RUN_INFO.txt' =>
        "STATUS=READY_FOR_INDEPENDENT_AUDIT_NOT_REGISTERED\n"
        . "REQUESTS=8\nAUTOMATIC_RETRIES=0\nCANDIDATE_SELECTED=NO\n",
];
foreach ($files as $name => $contents) {
    sumsugLiveWrite($sumsugLiveRunFolder . DIRECTORY_SEPARATOR . $name, $contents);
}
sumsugLiveWrite(
    $sumsugLiveRunFolder . DIRECTORY_SEPARATOR . 'artifact-manifest.csv',
    sumsugLiveManifest($files)
);
@unlink($sumsugLiveRunFolder . DIRECTORY_SEPARATOR . 'PARTIAL.marker');
sumsugLiveWrite($sumsugLiveRunFolder . DIRECTORY_SEPARATOR . 'READY.marker',
    "READY_FOR_INDEPENDENT_AUDIT\nREGISTERED=NO\nCANDIDATE_SELECTED=NO\n");

echo PHP_EOL;
echo 'GATE 5E SUMMARY/SUGGESTION LIVE EVIDENCE SAVED.' . PHP_EOL;
echo 'Run folder: ' . $sumsugLiveRunFolder . PHP_EOL;
echo 'Requests completed: 8/8' . PHP_EOL;
echo 'Automatic retries: 0' . PHP_EOL;
echo 'Median latency ms: ' . number_format($median, 3, '.', '') . PHP_EOL;
echo 'Within 15 seconds: ' . $withinFifteen . '/8' . PHP_EOL;
echo 'Estimated published cost USD: '
    . number_format($summary['estimated_published_cost_usd'], 8, '.', '')
    . PHP_EOL;
echo 'Manual quality review completed: No' . PHP_EOL;
echo 'Candidate selected or registered: No' . PHP_EOL;
echo 'Next action: independently audit the saved evidence before any decision.'
    . PHP_EOL;
