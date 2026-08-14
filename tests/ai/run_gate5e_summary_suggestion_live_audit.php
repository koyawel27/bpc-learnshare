<?php

declare(strict_types=1);

const SUMSUG_AUDIT_RUN = 'run-20260814-114049Z';
const SUMSUG_AUDIT_CANDIDATE = 'GEN-GROQ-GPT-OSS-120B-001';

/** @var array<string, string> */
const SUMSUG_AUDIT_RUN_HASHES = [
    'artifact-manifest.csv' =>
        '190E15CCD9FF4B400D4C7ED28C0421442E9130391AC6F3CDC76AC52FC36CE6E2',
    'model-outputs.jsonl' =>
        'F48229EB628038D26D51E41BB79F6A02D56ABAECD0E2E005B4B07036F297C4C9',
    'per-case-results.csv' =>
        '10392DA513D9B8366F5D6642D41A23939486717811B20F0BEF228F46CF485036',
    'READY.marker' =>
        'E0425EE470C053CEC90DC62805FBDCF2B95024495EEC76B011F7FC4F087DC7A7',
    'requests.jsonl' =>
        '9817BD58652C906D839ACB65B84B724ED366D7B04E6F2FEDFF1E7D186B18AF28',
    'response-envelopes.jsonl' =>
        '471C2F61CA8F60EC7E7020414BCD309F1DCCB5FBA04813BEA13BBF685E8389A1',
    'RUN_INFO.txt' =>
        '960544D6C35A1876997030592DDF9102F14FF2B1D80E2E60617EF5AF79F89CE7',
    'run-summary.json' =>
        '90C1358E2944AFD0491BCE0C1BCAA7962F73B5A7626232F84629D1FC57EA6F56',
];

/** @var list<string> */
$sumsugAuditPassed = [];

function sumsugAuditFail(string $message): never
{
    fwrite(STDERR, 'GATE 5E SAVED EVIDENCE AUDIT FAILED: '
        . $message . PHP_EOL);
    exit(1);
}

function sumsugAuditAssert(bool $condition, string $message): void
{
    global $sumsugAuditPassed;
    if (!$condition) {
        sumsugAuditFail($message);
    }
    $sumsugAuditPassed[] = $message;
    echo '[PASS] ' . $message . PHP_EOL;
}

function sumsugAuditHash(string $path): string
{
    $hash = hash_file('sha256', $path);
    if (!is_string($hash)) {
        sumsugAuditFail('Could not hash ' . basename($path));
    }
    return strtoupper($hash);
}

/** @return array<string, mixed> */
function sumsugAuditObject(string $json, string $label): array
{
    try {
        $value = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        sumsugAuditFail($label . ' is not valid JSON.');
    }
    if (!is_array($value)) {
        sumsugAuditFail($label . ' is not a JSON object.');
    }
    return $value;
}

/** @return list<array<string, mixed>> */
function sumsugAuditJsonl(string $path): array
{
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        sumsugAuditFail('Could not read ' . basename($path));
    }
    $rows = [];
    foreach ($lines as $position => $line) {
        $rows[] = sumsugAuditObject($line,
            basename($path) . ' line ' . ($position + 1));
    }
    return $rows;
}

/** @return array{headers: list<string>, rows: list<array<string, string>>} */
function sumsugAuditCsv(string $path): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        sumsugAuditFail('Could not open ' . basename($path));
    }
    try {
        $headers = fgetcsv($handle);
        if (!is_array($headers) || $headers === []) {
            sumsugAuditFail('CSV header is missing.');
        }
        $headers = array_map(static fn (mixed $v): string => (string) $v, $headers);
        $headers[0] = trim($headers[0], "\xEF\xBB\xBF\"");
        $rows = [];
        while (($values = fgetcsv($handle)) !== false) {
            if ($values === [null] || $values === []) {
                continue;
            }
            if (count($values) !== count($headers)) {
                sumsugAuditFail('CSV row width mismatch.');
            }
            $row = array_combine($headers, array_map(
                static fn (mixed $v): string => (string) $v,
                $values
            ));
            if (!is_array($row)) {
                sumsugAuditFail('CSV row could not be mapped.');
            }
            $rows[] = $row;
        }
        return ['headers' => $headers, 'rows' => $rows];
    } finally {
        fclose($handle);
    }
}

/** @param list<string> $headers @param list<array<string, string>> $rows */
function sumsugAuditCsvText(array $headers, array $rows): string
{
    $handle = fopen('php://temp', 'w+b');
    if ($handle === false) {
        sumsugAuditFail('Could not create CSV buffer.');
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
        sumsugAuditFail('Could not read CSV buffer.');
    }
    return $text;
}

/** @param array<string, mixed> $value */
function sumsugAuditJson(array $value): string
{
    return json_encode(
        $value,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ) . PHP_EOL;
}

/** @param array<string, string> $files */
function sumsugAuditManifest(array $files): string
{
    $rows = [];
    foreach ($files as $name => $contents) {
        $rows[] = [
            'artifact' => $name,
            'bytes' => (string) strlen($contents),
            'sha256' => strtoupper(hash('sha256', $contents)),
        ];
    }
    return sumsugAuditCsvText(['artifact', 'bytes', 'sha256'], $rows);
}

/** @return list<array<string, string|int>> */
function sumsugAuditQualityRows(): array
{
    return [
        ['fixture_id' => 'FX-PDF-001', 'summary_supported' => 'Yes',
            'summary_usability' => 'Usable as-is', 'returned_tags' => 1,
            'directly_usable_tags' => 1, 'clearly_tag_eligible' => 'Yes',
            'eligible_tag_received' => 'Yes', 'metadata_suggestions' => 3,
            'supported_metadata_suggestions' => 3,
            'overall_usability' => 'Usable as-is',
            'review_note' => 'Normalization summary, Database tag, and three metadata values match the reviewed reference.'],
        ['fixture_id' => 'FX-PDF-005', 'summary_supported' => 'Yes',
            'summary_usability' => 'Usable as-is', 'returned_tags' => 1,
            'directly_usable_tags' => 1, 'clearly_tag_eligible' => 'Yes',
            'eligible_tag_received' => 'Yes', 'metadata_suggestions' => 2,
            'supported_metadata_suggestions' => 1,
            'overall_usability' => 'Usable after light editing',
            'review_note' => 'Summary and Security tag are strong; Handout is a plausible but insufficiently evidenced controlled type.'],
        ['fixture_id' => 'FX-DOCX-002', 'summary_supported' => 'Yes',
            'summary_usability' => 'Usable as-is', 'returned_tags' => 2,
            'directly_usable_tags' => 2, 'clearly_tag_eligible' => 'No',
            'eligible_tag_received' => 'Not applicable', 'metadata_suggestions' => 3,
            'supported_metadata_suggestions' => 3,
            'overall_usability' => 'Usable after light editing',
            'review_note' => 'Summary and metadata are strong; Security and Usability are source-related but broad secondary tags.'],
        ['fixture_id' => 'FX-DOCX-003', 'summary_supported' => 'Yes',
            'summary_usability' => 'Usable as-is', 'returned_tags' => 2,
            'directly_usable_tags' => 2, 'clearly_tag_eligible' => 'Yes',
            'eligible_tag_received' => 'Yes', 'metadata_suggestions' => 2,
            'supported_metadata_suggestions' => 2,
            'overall_usability' => 'Usable after light editing',
            'review_note' => 'Summary, tags, type, and topic are supported; subject was conservatively left uninferred.'],
        ['fixture_id' => 'FX-PPTX-004', 'summary_supported' => 'Yes',
            'summary_usability' => 'Usable as-is', 'returned_tags' => 1,
            'directly_usable_tags' => 1, 'clearly_tag_eligible' => 'Yes',
            'eligible_tag_received' => 'Yes', 'metadata_suggestions' => 2,
            'supported_metadata_suggestions' => 2,
            'overall_usability' => 'Usable after light editing',
            'review_note' => 'Summary, Usability tag, type, and topic are supported; subject was conservatively left uninferred.'],
        ['fixture_id' => 'FX-PPTX-006', 'summary_supported' => 'Yes',
            'summary_usability' => 'Usable as-is', 'returned_tags' => 0,
            'directly_usable_tags' => 0, 'clearly_tag_eligible' => 'No',
            'eligible_tag_received' => 'Not applicable', 'metadata_suggestions' => 3,
            'supported_metadata_suggestions' => 3,
            'overall_usability' => 'Usable as-is',
            'review_note' => 'Correctly avoided forcing a tag; summary and all metadata are supported.'],
        ['fixture_id' => 'FX-TXT-001', 'summary_supported' => 'Yes',
            'summary_usability' => 'Usable as-is', 'returned_tags' => 2,
            'directly_usable_tags' => 1, 'clearly_tag_eligible' => 'Yes',
            'eligible_tag_received' => 'Yes', 'metadata_suggestions' => 3,
            'supported_metadata_suggestions' => 2,
            'overall_usability' => 'Usable after light editing',
            'review_note' => 'Database tag, summary, subject, and topic are supported; Programming and Handout are over-broad.'],
        ['fixture_id' => 'FX-TXT-005', 'summary_supported' => 'Yes',
            'summary_usability' => 'Usable as-is', 'returned_tags' => 1,
            'directly_usable_tags' => 1, 'clearly_tag_eligible' => 'Yes',
            'eligible_tag_received' => 'Yes', 'metadata_suggestions' => 3,
            'supported_metadata_suggestions' => 2,
            'overall_usability' => 'Usable after light editing',
            'review_note' => 'Research tag, summary, subject, and topic are supported; Handout should remain not reliably inferable.'],
    ];
}

$mode = null;
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--mode=')) {
        $mode = substr($argument, strlen('--mode='));
    } else {
        sumsugAuditFail('Unknown argument: ' . $argument);
    }
}
sumsugAuditAssert(in_array($mode, ['validate', 'apply'], true),
    'Mode is validate or apply');

$root = dirname(__DIR__, 2);
$run = $root . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR
    . 'ai-feasibility-spike' . DIRECTORY_SEPARATOR . 'results'
    . DIRECTORY_SEPARATOR . 'summary-suggestion'
    . DIRECTORY_SEPARATOR . SUMSUG_AUDIT_CANDIDATE
    . DIRECTORY_SEPARATOR . SUMSUG_AUDIT_RUN;
sumsugAuditAssert(is_dir($run), 'Saved successful run folder exists');
$actualFiles = array_map('basename', glob($run . DIRECTORY_SEPARATOR . '*') ?: []);
sort($actualFiles, SORT_STRING);
$expectedFiles = array_keys(SUMSUG_AUDIT_RUN_HASHES);
sort($expectedFiles, SORT_STRING);
sumsugAuditAssert($actualFiles === $expectedFiles,
    'Saved run has the exact eight-file successful set');
foreach (SUMSUG_AUDIT_RUN_HASHES as $name => $hash) {
    sumsugAuditAssert(sumsugAuditHash($run . DIRECTORY_SEPARATOR . $name) === $hash,
        'Saved run artifact is unchanged: ' . $name);
}
sumsugAuditAssert(
    !is_file($run . DIRECTORY_SEPARATOR . 'FAILED.marker')
    && !is_file($run . DIRECTORY_SEPARATOR . 'PARTIAL.marker'),
    'Failed and partial markers are absent'
);
sumsugAuditAssert(
    file_get_contents($run . DIRECTORY_SEPARATOR . 'READY.marker')
        === "READY_FOR_INDEPENDENT_AUDIT\nREGISTERED=NO\nCANDIDATE_SELECTED=NO\n",
    'Ready marker preserves non-registration and non-selection'
);

$manifest = sumsugAuditCsv($run . DIRECTORY_SEPARATOR . 'artifact-manifest.csv');
sumsugAuditAssert(count($manifest['rows']) === 6,
    'Run manifest contains six entries');
foreach ($manifest['rows'] as $entry) {
    $path = $run . DIRECTORY_SEPARATOR . $entry['artifact'];
    sumsugAuditAssert(is_file($path)
        && (string) filesize($path) === $entry['bytes']
        && sumsugAuditHash($path) === $entry['sha256'],
        'Manifest size and hash reconcile: ' . $entry['artifact']);
}

$requests = sumsugAuditJsonl($run . DIRECTORY_SEPARATOR . 'requests.jsonl');
$responses = sumsugAuditJsonl($run . DIRECTORY_SEPARATOR
    . 'response-envelopes.jsonl');
$outputs = sumsugAuditJsonl($run . DIRECTORY_SEPARATOR . 'model-outputs.jsonl');
$cases = sumsugAuditCsv($run . DIRECTORY_SEPARATOR . 'per-case-results.csv');
sumsugAuditAssert(count($requests) === 8 && count($responses) === 8
    && count($outputs) === 8 && count($cases['rows']) === 8,
    'Requests, responses, outputs, and result rows reconcile 8/8');

$packet = dirname(dirname($run)) . DIRECTORY_SEPARATOR
    . '..' . DIRECTORY_SEPARATOR . 'summary-suggestion-preview'
    . DIRECTORY_SEPARATOR . SUMSUG_AUDIT_CANDIDATE
    . DIRECTORY_SEPARATOR . 'payload-review-v2';
$packet = realpath($packet);
sumsugAuditAssert(is_string($packet) && is_dir($packet),
    'Reviewed v2 packet is available');
$packetLines = file(
    $packet . DIRECTORY_SEPARATOR . 'provider-requests.jsonl',
    FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
);
sumsugAuditAssert(is_array($packetLines) && count($packetLines) === 8,
    'Reviewed v2 packet contains eight exact requests');

$promptTokens = 0;
$completionTokens = 0;
$totalTokens = 0;
$latencies = [];
foreach ($requests as $position => $requestRow) {
    $sequence = $position + 1;
    $fixtureId = $requestRow['fixture_id'] ?? '';
    sumsugAuditAssert(($requestRow['request_sequence'] ?? null) === $sequence,
        'Request sequence reconciles for ' . $fixtureId);
    $packetObject = sumsugAuditObject($packetLines[$position],
        'v2 packet request ' . $sequence);
    sumsugAuditAssert(($requestRow['request'] ?? null) == $packetObject,
        'Saved request exactly matches reviewed v2 packet for ' . $fixtureId);

    $responseRow = $responses[$position];
    $outputRow = $outputs[$position];
    $caseRow = $cases['rows'][$position];
    sumsugAuditAssert(($responseRow['fixture_id'] ?? null) === $fixtureId
        && ($outputRow['fixture_id'] ?? null) === $fixtureId
        && ($caseRow['fixture_id'] ?? null) === $fixtureId,
        'Response, output, and CSV fixture identity reconcile for ' . $fixtureId);
    sumsugAuditAssert(($responseRow['http_status'] ?? null) === 200
        && ($caseRow['http_status'] ?? null) === '200',
        'HTTP status is 200 for ' . $fixtureId);
    $envelope = sumsugAuditObject(
        (string) $responseRow['response_body'],
        'provider envelope for ' . $fixtureId
    );
    $content = $envelope['choices'][0]['message']['content'] ?? null;
    sumsugAuditAssert(is_string($content),
        'Provider content exists for ' . $fixtureId);
    $providerOutput = sumsugAuditObject($content,
        'provider model output for ' . $fixtureId);
    sumsugAuditAssert(($outputRow['model_output'] ?? null) == $providerOutput,
        'Saved model output matches provider envelope for ' . $fixtureId);
    sumsugAuditAssert(($outputRow['usage'] ?? null) == ($envelope['usage'] ?? null),
        'Saved usage matches provider envelope for ' . $fixtureId);
    $usage = $outputRow['usage'];
    sumsugAuditAssert((string) $usage['prompt_tokens']
        === $caseRow['prompt_tokens']
        && (string) $usage['completion_tokens']
            === $caseRow['completion_tokens']
        && (string) $usage['total_tokens'] === $caseRow['total_tokens'],
        'Per-case token measurements reconcile for ' . $fixtureId);
    sumsugAuditAssert(abs((float) $responseRow['elapsed_ms']
        - (float) $caseRow['elapsed_ms']) < 0.001,
        'Per-case latency reconciles for ' . $fixtureId);
    sumsugAuditAssert(
        $caseRow['schema_and_boundary_status']
            === 'passed_pending_manual_quality_review',
        'Case remains pending quality review for ' . $fixtureId
    );
    $promptTokens += $usage['prompt_tokens'];
    $completionTokens += $usage['completion_tokens'];
    $totalTokens += $usage['total_tokens'];
    $latencies[] = (float) $caseRow['elapsed_ms'];
}

sort($latencies, SORT_NUMERIC);
$median = ($latencies[3] + $latencies[4]) / 2;
$withinFifteen = count(array_filter(
    $latencies,
    static fn (float $value): bool => $value <= 15000
));
$summaryText = file_get_contents($run . DIRECTORY_SEPARATOR . 'run-summary.json');
if (!is_string($summaryText)) {
    sumsugAuditFail('Could not read run summary.');
}
$summary = sumsugAuditObject($summaryText, 'run summary');
sumsugAuditAssert(
    ($summary['requests_completed'] ?? null) === 8
    && ($summary['automatic_retries'] ?? null) === 0
    && ($summary['prompt_tokens'] ?? null) === $promptTokens
    && ($summary['completion_tokens'] ?? null) === $completionTokens
    && ($summary['total_tokens'] ?? null) === $totalTokens
    && abs((float) $summary['median_latency_ms'] - $median) < 0.001
    && ($summary['requests_within_15_seconds'] ?? null) === $withinFifteen
    && ($summary['candidate_selected'] ?? null) === false
    && ($summary['register_rows_created'] ?? null) === 0
    && ($summary['application_integration_authorized'] ?? null) === false,
    'Run summary independently reconciles all execution aggregates'
);

$allEvidence = '';
foreach (array_keys(SUMSUG_AUDIT_RUN_HASHES) as $name) {
    $contents = file_get_contents($run . DIRECTORY_SEPARATOR . $name);
    if (is_string($contents)) {
        $allEvidence .= $contents;
    }
}
sumsugAuditAssert(
    preg_match('/\bgsk_[A-Za-z0-9_-]{16,}\b/', $allEvidence) !== 1,
    'Saved evidence contains no Groq key-shaped value'
);

$qualityRows = sumsugAuditQualityRows();
$supportedSummaries = count(array_filter(
    $qualityRows,
    static fn (array $row): bool => $row['summary_supported'] === 'Yes'
));
$returnedTags = array_sum(array_column($qualityRows, 'returned_tags'));
$usableTags = array_sum(array_column($qualityRows, 'directly_usable_tags'));
$tagEligible = count(array_filter(
    $qualityRows,
    static fn (array $row): bool => $row['clearly_tag_eligible'] === 'Yes'
));
$tagReceived = count(array_filter(
    $qualityRows,
    static fn (array $row): bool => $row['eligible_tag_received'] === 'Yes'
));
$metadataSuggestions = array_sum(array_column($qualityRows, 'metadata_suggestions'));
$supportedMetadata = array_sum(array_column(
    $qualityRows,
    'supported_metadata_suggestions'
));
$usableOutputs = count(array_filter(
    $qualityRows,
    static fn (array $row): bool => in_array(
        $row['overall_usability'],
        ['Usable as-is', 'Usable after light editing'],
        true
    )
));
$qualitySummary = [
    'status' => 'review_preview_pending_user_acceptance',
    'run_id' => SUMSUG_AUDIT_RUN,
    'summary_support_rate_percent' => round($supportedSummaries / 8 * 100, 2),
    'directly_usable_active_tag_rate_percent' =>
        round($usableTags / $returnedTags * 100, 2),
    'clearly_tag_eligible_coverage_percent' =>
        round($tagReceived / $tagEligible * 100, 2),
    'supported_metadata_suggestion_rate_percent' =>
        round($supportedMetadata / $metadataSuggestions * 100, 2),
    'usable_as_is_or_light_edit_rate_percent' => round($usableOutputs / 8 * 100, 2),
    'summary_threshold_met' => $supportedSummaries === 8,
    'tag_relevance_threshold_met' => $usableTags / $returnedTags >= 0.80,
    'tag_coverage_threshold_met' => $tagReceived / $tagEligible >= 0.75,
    'metadata_threshold_met' => $supportedMetadata / $metadataSuggestions >= 0.80,
    'overall_usability_threshold_met' => $usableOutputs / 8 >= 0.80,
    'review_approved_by_user' => false,
    'candidate_selected' => false,
    'prior_grounded_generation_failure_overridden' => false,
];

$qualityHeaders = [
    'fixture_id', 'summary_supported', 'summary_usability', 'returned_tags',
    'directly_usable_tags', 'clearly_tag_eligible', 'eligible_tag_received',
    'metadata_suggestions', 'supported_metadata_suggestions',
    'overall_usability', 'review_note',
];
$markdown = <<<MD
# Gate 5E Summary and Suggestion Quality Review Preview

Run: `run-20260814-114049Z`

## Provisional measured result

- Source-supported summaries: **{$supportedSummaries}/8 (100%)**
- Directly usable Active tag suggestions: **{$usableTags}/{$returnedTags} (90%)**
- Clearly tag-eligible resources receiving a tag: **{$tagReceived}/{$tagEligible} (100%)**
- Source-supported metadata suggestions: **{$supportedMetadata}/{$metadataSuggestions} (85.71%)**
- Outputs usable as-is or after light editing: **{$usableOutputs}/8 (100%)**

All accepted Gate 5E thresholds are provisionally met. The main weaknesses are
over-broad secondary tags and forced `Handout` values for source formats whose
controlled resource type is not reliably inferable.

This is a review preview, not an accepted registration or model decision. It
does not erase the candidate's earlier grounded-generation claim-support and
attribution failure.
MD;

if ($mode === 'validate') {
    echo PHP_EOL;
    echo 'GATE 5E SAVED EVIDENCE AUDIT PASSED.' . PHP_EOL;
    echo 'Checks passed: ' . count($sumsugAuditPassed) . PHP_EOL;
    echo 'Provider requests made by audit: 0' . PHP_EOL;
    echo 'Quality review preview written: No' . PHP_EOL;
    echo 'Candidate selected: No' . PHP_EOL;
    exit(0);
}

$review = dirname($run) . DIRECTORY_SEPARATOR
    . SUMSUG_AUDIT_RUN . '-quality-review-preview-v1';
sumsugAuditAssert(!file_exists($review),
    'Quality-review preview folder does not exist');
$partial = dirname($review) . DIRECTORY_SEPARATOR
    . '.partial-quality-review-' . getmypid();
if (!mkdir($partial, 0775, false)) {
    sumsugAuditFail('Could not create partial quality-review folder.');
}
$files = [
    'mechanical-audit-summary.json' => sumsugAuditJson([
        'status' => 'saved_evidence_audit_passed',
        'run_id' => SUMSUG_AUDIT_RUN,
        'requests_reconciled' => 8,
        'additional_provider_requests' => 0,
        'automatic_retries' => 0,
        'candidate_selected' => false,
    ]),
    'quality-review-preview.csv' => sumsugAuditCsvText(
        $qualityHeaders,
        array_map(
            static fn (array $row): array => array_map('strval', $row),
            $qualityRows
        )
    ),
    'quality-review-summary.json' => sumsugAuditJson($qualitySummary),
    'QUALITY_REVIEW_PREVIEW.md' => $markdown . PHP_EOL,
];
foreach ($files as $name => $contents) {
    file_put_contents($partial . DIRECTORY_SEPARATOR . $name, $contents);
}
file_put_contents(
    $partial . DIRECTORY_SEPARATOR . 'artifact-manifest.csv',
    sumsugAuditManifest($files)
);
file_put_contents(
    $partial . DIRECTORY_SEPARATOR . 'READY_FOR_REVIEW.marker',
    "READY_FOR_USER_QUALITY_REVIEW\nREGISTERED=NO\n"
        . "CANDIDATE_SELECTED=NO\n"
);
if (!rename($partial, $review)) {
    sumsugAuditFail('Could not finalize quality-review preview folder.');
}

echo PHP_EOL;
echo 'GATE 5E SAVED EVIDENCE AUDIT AND REVIEW PREVIEW SAVED.' . PHP_EOL;
echo 'Review folder: ' . $review . PHP_EOL;
echo 'Provider requests made by audit: 0' . PHP_EOL;
echo 'Summary support: 8/8' . PHP_EOL;
echo 'Directly usable Active tags: 9/10' . PHP_EOL;
echo 'Tag-eligible coverage: 6/6' . PHP_EOL;
echo 'Supported metadata suggestions: 18/21' . PHP_EOL;
echo 'Usable as-is or light edit: 8/8' . PHP_EOL;
echo 'User quality-review acceptance recorded: No' . PHP_EOL;
echo 'Candidate selected: No' . PHP_EOL;
