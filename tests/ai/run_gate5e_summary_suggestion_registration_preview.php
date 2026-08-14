<?php

declare(strict_types=1);

const G5E_CANDIDATE = 'GEN-GROQ-GPT-OSS-120B-001';
const G5E_FAILED_RUN = 'TR-GEN-GROQ-SUMSUG-SCHEMA-COMPAT-001';
const G5E_PASSED_RUN = 'TR-GEN-GROQ-SUMSUG-001';
const G5E_PROMPT = 'SUMMARY-SUGGESTION-v1';

/** @var array<string, array{count:int,hash:string}> */
const G5E_BASELINE = [
    'docs/ai-feasibility-spike/registers/candidates.csv' => [
        'count' => 17,
        'hash' => 'A987ADA70ED1119B7A2DD3C5CD98F11FE92406B817491C9ECD840B2D16752AE9',
    ],
    'docs/ai-feasibility-spike/registers/test_runs.csv' => [
        'count' => 62,
        'hash' => '8117F8200F44702965FB9290118799B895CB85B53F79992E7576908402313793',
    ],
    'docs/ai-feasibility-spike/registers/payload_manifests.csv' => [
        'count' => 6,
        'hash' => 'AD9414938F1148ABF40E7D614093D8394339DFB975F9A21553C4FF68DD731CA6',
    ],
    'docs/ai-feasibility-spike/results/measurements.csv' => [
        'count' => 711,
        'hash' => '01E1DBFC8596618726CF8B69C237B283A7D0F119FB78C4DD5ABDC926448D6C5C',
    ],
];

function g5eFail(string $message): never
{
    fwrite(STDERR, 'GATE 5E REGISTRATION PREVIEW FAILED: ' . $message . PHP_EOL);
    exit(1);
}

function g5eAssert(bool $condition, string $message): void
{
    if (!$condition) {
        g5eFail($message);
    }
}

function g5eRoot(): string
{
    $root = realpath(__DIR__ . '/../..');
    g5eAssert(is_string($root), 'Could not resolve repository root.');
    return $root;
}

function g5ePath(string $root, string $relative): string
{
    return $root . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function g5eHash(string $path): string
{
    $hash = hash_file('sha256', $path);
    g5eAssert(is_string($hash), 'Could not hash ' . $path);
    return strtoupper($hash);
}

/** @return list<array<string,string>> */
function g5eCsv(string $path): array
{
    $handle = fopen($path, 'rb');
    g5eAssert($handle !== false, 'Could not open ' . $path);
    $headers = fgetcsv($handle, 0, ',', '"', '\\');
    g5eAssert(is_array($headers), 'Missing CSV header in ' . $path);
    $headers[0] = (string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
    $rows = [];
    while (($values = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
        if ($values === [] || $values === [null]) {
            continue;
        }
        g5eAssert(count($values) === count($headers), 'CSV width mismatch in ' . $path);
        /** @var array<string,string> $row */
        $row = array_combine($headers, $values);
        $rows[] = $row;
    }
    fclose($handle);
    return $rows;
}

/** @return array<string,mixed> */
function g5eJson(string $path): array
{
    $raw = file_get_contents($path);
    g5eAssert(is_string($raw), 'Could not read ' . $path);
    try {
        $decoded = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        g5eFail('Invalid JSON in ' . $path);
    }
    g5eAssert(is_array($decoded), 'Expected JSON object in ' . $path);
    return $decoded;
}

/** @param list<string> $headers @param list<array<string,string>> $rows */
function g5eWriteCsv(string $path, array $headers, array $rows): void
{
    $handle = fopen($path, 'wb');
    g5eAssert($handle !== false, 'Could not create ' . $path);
    fputcsv($handle, $headers, ',', '"', '\\', "\n");
    foreach ($rows as $row) {
        g5eAssert(array_keys($row) === $headers, 'CSV schema mismatch for ' . basename($path));
        fputcsv($handle, array_values($row), ',', '"', '\\', "\n");
    }
    fclose($handle);
}

/** @param array<string,mixed> $value */
function g5eWriteJson(string $path, array $value): void
{
    $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    file_put_contents($path, $json . PHP_EOL);
}

/** @return array<string,string> */
function g5eCandidate(array $current): array
{
    $current['capability'] = 'optional external grounded generation, summaries, and controlled suggestions';
    $current['version'] = 'Provider/model state reviewed and tested 2026-08-14';
    $current['configuration_summary'] = 'Measured in one six-case grounded comparison and one eight-resource summary/suggestion checkpoint. Sequential strict-JSON requests used temperature=0, reasoning_effort=low, max_completion_tokens=400 for grounded answers or 700 for Gate 5E, no tools/web/streaming, zero automatic retries, and project ZDR/model allowlist/limits confirmed before execution.';
    $current['reason_for_inclusion'] = 'Bounded external comparison for interactive grounded answers and non-authoritative upload-time summaries, controlled tags, and limited metadata suggestions without selecting a final provider or architecture.';
    $current['non_secret_settings'] = 'provider=GroqCloud; model=openai/gpt-oss-120b; temperature=0; reasoning_effort=low; max_completion_tokens=400 grounded or 700 Gate5E; strict_json_schema=true; retries=0; minimum_spacing_seconds=65';
    $current['decision_status'] = 'Mixed capability evidence: grounded inquiry failed strict claim-support and source-attribution criteria; Gate 5E summary and controlled-suggestion quality met every accepted threshold; candidate remains not accepted, selected, or integrated';
    $current['notes'] = 'Grounded result remains failed at 16/18 supported claims (88.89%) despite 5/6 usefulness. Gate 5E v1 stopped safely after one HTTP 400 unsupported_uniqueItems schema response, zero model outputs, and zero retries. The versioned v2 run completed 8/8 requests with a 1,944.858 ms median, 15,778 total tokens, estimated published cost USD 0.0038472, 100% supported summaries, 90% directly usable Active tags, 100% eligible-tag coverage, 85.71% supported metadata suggestions, and 100% outputs usable as-is or after light editing. The user accepted the quality review on 2026-08-14. This does not override the grounded failure or select the candidate.';
    return $current;
}

/** @return array<string,string> */
function g5eTestRun(array $headers, bool $failed, array $measurementIds, array $payloadIds): array
{
    $values = $failed ? [
        'test_run_id' => G5E_FAILED_RUN,
        'execution_timestamp' => '2026-08-14T11:29:01+00:00',
        'test_case_id' => 'TC-GEN-GROQ-SUMSUG-SCHEMA-COMPAT-001',
        'capability' => 'external summary and controlled-suggestion schema compatibility',
        'fixture_id' => 'FX-PDF-001',
        'source_version_id' => 'SV-FX-PDF-001-001',
        'query_id' => '',
        'expected_evidence_id' => '',
        'candidate_configuration_id' => G5E_CANDIDATE,
        'segmentation_configuration_id' => '',
        'prompt_template_version' => G5E_PROMPT,
        'payload_manifest_id' => implode(';', $payloadIds),
        'environment_baseline_id' => '',
        'result_status' => 'failed',
        'measurement_record_ids' => implode(';', $measurementIds),
        'deviation_or_rerun_reference' => G5E_PASSED_RUN . ' used a versioned provider-schema compatibility correction after separate review and approval.',
        'reviewer' => 'Project team',
        'notes' => 'The first of eight approved synthetic requests returned HTTP 400 because the provider strict-schema subset rejected uniqueItems. No model output was created, zero automatic retries occurred, the remaining seven requests were not sent, and the failed folder was preserved. No quality conclusion was drawn from this failed compatibility attempt.',
    ] : [
        'test_run_id' => G5E_PASSED_RUN,
        'execution_timestamp' => '2026-08-14T11:40:49+00:00',
        'test_case_id' => 'TC-GEN-GROQ-SUMSUG-001',
        'capability' => 'external non-authoritative summary and controlled metadata suggestion',
        'fixture_id' => 'FX-PDF-001;FX-PDF-005;FX-DOCX-002;FX-DOCX-003;FX-PPTX-004;FX-PPTX-006;FX-TXT-001;FX-TXT-005',
        'source_version_id' => 'SV-FX-PDF-001-001;SV-FX-PDF-005-001;SV-FX-DOCX-002-001;SV-FX-DOCX-003-001;SV-FX-PPTX-004-001;SV-FX-PPTX-006-001;SV-FX-TXT-001-001;SV-FX-TXT-005-001',
        'query_id' => '',
        'expected_evidence_id' => '',
        'candidate_configuration_id' => G5E_CANDIDATE,
        'segmentation_configuration_id' => '',
        'prompt_template_version' => G5E_PROMPT,
        'payload_manifest_id' => implode(';', $payloadIds),
        'environment_baseline_id' => '',
        'result_status' => 'passed',
        'measurement_record_ids' => implode(';', $measurementIds),
        'deviation_or_rerun_reference' => G5E_FAILED_RUN . '; v2 removed only unsupported provider-schema uniqueItems keywords while retaining runner-side uniqueness validation.',
        'reviewer' => 'Project team',
        'notes' => 'The separately reviewed v2 run completed 8/8 HTTP 200 requests with zero retries and preserved evidence. Median latency was 1,944.858 ms; 8/8 were within 15 seconds; total usage was 15,778 tokens; estimated published-rate cost was USD 0.0038472. User-approved review found 100% source-supported summaries, 90% directly usable Active tags, 100% clearly eligible tag coverage, 85.71% supported metadata suggestions, and 100% outputs usable as-is or after light editing. All accepted Gate 5E criteria passed. This result neither overrides the prior grounded-generation failure nor selects or integrates the candidate.',
    ];
    g5eAssert(array_keys($values) === $headers, 'Test-run schema mismatch.');
    return $values;
}

/** @return array<string,string> */
function g5eMeasurementRow(array $headers, string $id, string $run, array $spec): array
{
    [$area, $metric, $value, $unit, $classification, $mandatory, $pass, $judgment, $path, $notes] = $spec;
    $row = [
        'measurement_record_id' => $id,
        'test_run_id' => $run,
        'measurement_area' => $area,
        'metric_name' => $metric,
        'metric_value' => $value,
        'unit' => $unit,
        'result_classification' => $classification,
        'mandatory_guardrail' => $mandatory,
        'guardrail_pass' => $pass,
        'reviewer_judgment' => $judgment,
        'evidence_path' => $path,
        'reviewer' => 'Project team',
        'notes' => $notes,
    ];
    g5eAssert(array_keys($row) === $headers, 'Measurement schema mismatch.');
    return $row;
}

/** @return list<array<string,string>> */
function g5eMeasurements(array $headers): array
{
    $failedEvidence = '.local/ai-feasibility-spike/results/summary-suggestion/' . G5E_CANDIDATE . '/run-20260814-112901Z/failure-summary.json';
    $failedResponse = '.local/ai-feasibility-spike/results/summary-suggestion/' . G5E_CANDIDATE . '/run-20260814-112901Z/response-envelopes.jsonl';
    $runEvidence = '.local/ai-feasibility-spike/results/summary-suggestion/' . G5E_CANDIDATE . '/run-20260814-114049Z/run-summary.json';
    $reviewEvidence = '.local/ai-feasibility-spike/results/summary-suggestion/' . G5E_CANDIDATE . '/run-20260814-114049Z-quality-review-preview-v1/quality-review-summary.json';
    $reviewRows = '.local/ai-feasibility-spike/results/summary-suggestion/' . G5E_CANDIDATE . '/run-20260814-114049Z-quality-review-preview-v1/quality-review-preview.csv';
    $failed = [
        ['execution','requests_planned','8','requests','observed','No','Yes','Recorded approved run ceiling',$failedEvidence,'Eight requests were approved before execution.'],
        ['execution','requests_attempted','1','request','observed','No','Yes','Recorded fail-closed attempt',$failedResponse,'Only request 1 was transmitted.'],
        ['execution','http_400_responses','1','response','failed','Yes','No','Provider schema compatibility failed',$failedResponse,'The provider rejected unsupported uniqueItems before model generation.'],
        ['execution','requests_completed','0','requests','failed','Yes','No','No model output completed',$failedEvidence,'The failed attempt produced zero completed model outputs.'],
        ['execution','automatic_retries','0','retries','passed','Yes','Yes','Fail-closed retry boundary preserved',$failedEvidence,'No automatic retry or silent schema mutation occurred.'],
        ['compatibility','provider_schema_error','unsupported_uniqueItems','category','failed','Yes','No','Provider strict-schema subset mismatch preserved',$failedResponse,'The exact error and schema path were saved locally.'],
        ['boundary','remaining_requests_not_sent','7','requests','passed','Yes','Yes','Transmission stopped after first failure',$failedEvidence,'Requests 2 through 8 were not transmitted.'],
        ['evidence','failed_run_preserved','Yes','boolean','passed','Yes','Yes','Failure evidence retained',$failedEvidence,'FAILED marker, request, response envelope, and failure summary remain local.'],
        ['boundary','candidate_selected','No','boolean','passed','Yes','Yes','Decision boundary preserved',$failedEvidence,'The compatibility failure selected no candidate.'],
    ];
    $passed = [
        ['execution','requests_completed','8','requests','passed','Yes','Yes','Accepted complete execution',$runEvidence,'Eight of eight planned requests completed.'],
        ['execution','http_200_responses','8','responses','passed','Yes','Yes','Accepted complete execution',$runEvidence,'Every corrected request returned HTTP 200.'],
        ['execution','automatic_retries','0','retries','passed','Yes','Yes','Accepted fail-closed execution',$runEvidence,'No request was retried.'],
        ['execution','schema_and_boundary_contracts_passed','8','cases','passed','Yes','Yes','Accepted structural execution',$runEvidence,'All outputs passed strict structure plus runner-side uniqueness and authority guards.'],
        ['performance','median_latency','1944.858','milliseconds','passed','Yes','Yes','Met accepted median latency criterion',$runEvidence,'Accepted maximum was 15,000 ms.'],
        ['performance','requests_within_15_seconds','8','of 8 cases','passed','Yes','Yes','Met accepted responsiveness criterion',$runEvidence,'Every request completed within 15 seconds.'],
        ['usage','prompt_tokens','12488','tokens','observed','No','Yes','Recorded provider usage',$runEvidence,'Provider-reported prompt-token total.'],
        ['usage','completion_tokens','3290','tokens','observed','No','Yes','Recorded provider usage',$runEvidence,'Provider-reported completion-token total.'],
        ['usage','total_tokens','15778','tokens','observed','No','Yes','Recorded provider usage',$runEvidence,'Provider-reported combined token total.'],
        ['cost','estimated_published_cost','0.0038472','USD','observed','No','Yes','Accepted bounded estimate',$runEvidence,'Calculated from published rates reviewed for the checkpoint; not a permanent price guarantee.'],
        ['quality','source_supported_summaries','8','of 8 cases','passed','Yes','Yes','All summaries source-supported',$reviewRows,'No material unsupported summary statement was accepted.'],
        ['quality','summary_support_rate','100','percent','passed','Yes','Yes','Met accepted summary criterion',$reviewEvidence,'Accepted requirement was complete material support.'],
        ['quality','directly_usable_active_tags','9','of 10 returned tags','passed','Yes','Yes','Met accepted tag relevance criterion',$reviewRows,'One Programming tag was broad; the remaining nine were directly usable.'],
        ['quality','directly_usable_active_tag_rate','90','percent','passed','Yes','Yes','Above accepted threshold',$reviewEvidence,'Accepted minimum was 80%.'],
        ['quality','clearly_tag_eligible_cases_covered','6','of 6 cases','passed','Yes','Yes','Met accepted tag coverage criterion',$reviewRows,'Every clearly tag-eligible fixture received at least one suitable Active tag.'],
        ['quality','clearly_tag_eligible_coverage','100','percent','passed','Yes','Yes','Above accepted threshold',$reviewEvidence,'Accepted minimum was 75%.'],
        ['quality','supported_metadata_suggestions','18','of 21 suggestions','passed','Yes','Yes','Met accepted metadata criterion',$reviewRows,'Three broad or unsupported values require omission or light correction.'],
        ['quality','supported_metadata_suggestion_rate','85.71','percent','passed','Yes','Yes','Above accepted threshold',$reviewEvidence,'Accepted minimum was 80%.'],
        ['quality','outputs_usable_as_is_or_light_edit','8','of 8 cases','passed','Yes','Yes','Met accepted overall usability criterion',$reviewRows,'All cases were usable without major rewriting.'],
        ['quality','usable_as_is_or_light_edit_rate','100','percent','passed','Yes','Yes','Above accepted threshold',$reviewEvidence,'Accepted minimum was 80%.'],
        ['quality','unsupported_handout_suggestions','2','suggestions','observed','No','Yes','Preserved targeted weakness',$reviewRows,'FX-TXT-001 and FX-TXT-005 should not automatically receive Handout.'],
        ['quality','insufficiently_evidenced_handout_suggestion','1','suggestion','observed','No','Yes','Preserved targeted weakness',$reviewRows,'FX-PDF-005 Handout was plausible but not sufficiently evidenced.'],
        ['quality','broad_programming_tag_suggestion','1','suggestion','observed','No','Yes','Preserved targeted weakness',$reviewRows,'FX-TXT-001 Programming was source-related but too broad for direct use.'],
        ['quality','conservative_non_inference_cases','2','fixtures','passed','No','Yes','Accepted conservative behavior',$reviewRows,'DOCX-003 and PPTX-004 left subject not reliably inferable.'],
        ['safety','inactive_tags_returned','0','tags','passed','Yes','Yes','Controlled taxonomy boundary preserved',$runEvidence,'No Inactive tag was returned.'],
        ['safety','unauthorized_authority_actions','0','actions','passed','Yes','Yes','Non-authority boundary preserved',$runEvidence,'No approval, publication, rejection, validation, or taxonomy mutation was performed.'],
        ['evidence','saved_evidence_audit','passed','status','passed','Yes','Yes','Independent read-only audit passed',$reviewEvidence,'All eight requests, responses, hashes, timings, and boundaries reconciled.'],
        ['review','quality_review_approved_by_user','Yes','boolean','passed','Yes','Yes','User accepted the preserved review',$reviewEvidence,'Approval was given on 2026-08-14 without changing the local pre-approval artifact.'],
        ['boundary','prior_grounded_failure_overridden','No','boolean','passed','Yes','Yes','Capability-specific verdict preserved',$reviewEvidence,'Gate 5E success does not repair the earlier grounded claim-support and attribution failure.'],
        ['boundary','candidate_selected','No','boolean','passed','Yes','Yes','Decision boundary preserved',$runEvidence,'Evidence registration would not select the provider/model.'],
        ['boundary','application_integration_authorized','No','boolean','passed','Yes','Yes','Integration boundary preserved',$runEvidence,'No application route, UI, storage, schema, or provider coupling is authorized.'],
    ];
    $rows = [];
    foreach ($failed as $i => $spec) {
        $rows[] = g5eMeasurementRow($headers, sprintf('MR-GEN-GROQ-SUMSUG-SCHEMA-COMPAT-001-%02d', $i + 1), G5E_FAILED_RUN, $spec);
    }
    foreach ($passed as $i => $spec) {
        $rows[] = g5eMeasurementRow($headers, sprintf('MR-GEN-GROQ-SUMSUG-001-%02d', $i + 1), G5E_PASSED_RUN, $spec);
    }
    return $rows;
}

$mode = 'validate';
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--mode=validate') {
        $mode = 'validate';
    } elseif ($arg === '--mode=apply') {
        $mode = 'apply';
    } elseif ($arg === '--mode=audit-preview') {
        $mode = 'audit-preview';
    } else {
        g5eFail('Unknown argument: ' . $arg);
    }
}

$root = g5eRoot();
chdir($root);
$outputRelative = '.local/ai-feasibility-spike/results/registration-preview/'
    . G5E_CANDIDATE . '/summary-suggestion-registration-v1';
$output = g5ePath($root, $outputRelative);

if ($mode === 'audit-preview') {
    echo '=== GATE 5E SAVED REGISTRATION PREVIEW AUDIT v1 ===' . PHP_EOL;
    g5eAssert(is_dir($output), 'Preview folder is missing.');
    $expected = [
        'candidate-updated-row.csv' => 1,
        'test-run-rows.csv' => 2,
        'payload-manifest-rows.csv' => 9,
        'measurement-rows.csv' => 40,
    ];
    foreach ($expected as $file => $count) {
        $rows = g5eCsv($output . '/' . $file);
        g5eAssert(count($rows) === $count, $file . ' row count changed.');
    }
    $manifest = g5eCsv($output . '/artifact-manifest.csv');
    g5eAssert(count($manifest) === 6, 'Expected six manifested artifacts.');
    foreach ($manifest as $row) {
        $path = $output . '/' . $row['artifact_name'];
        g5eAssert(is_file($path), 'Missing manifested artifact ' . $row['artifact_name']);
        g5eAssert((string) filesize($path) === $row['size_bytes'], 'Size mismatch for ' . $row['artifact_name']);
        g5eAssert(g5eHash($path) === $row['sha256'], 'Hash mismatch for ' . $row['artifact_name']);
    }
    $summary = g5eJson($output . '/registration-summary.json');
    g5eAssert($summary['failed_schema_run_status'] === 'failed', 'Failed run verdict changed.');
    g5eAssert($summary['successful_quality_run_status'] === 'passed', 'Successful run verdict changed.');
    g5eAssert($summary['candidate_selected'] === false, 'Candidate unexpectedly selected.');
    g5eAssert($summary['prior_grounded_failure_overridden'] === false, 'Grounded failure unexpectedly overridden.');
    g5eAssert($summary['registers_or_docs_modified'] === false, 'Preview claims tracked changes.');
    g5eAssert(trim((string) file_get_contents($output . '/READY.marker')) === 'REGISTRATION_PREVIEW_READY_NOT_APPLIED', 'READY marker changed.');
    echo 'Candidate update rows: 1' . PHP_EOL;
    echo 'Test runs: 2 (one failed schema compatibility; one passed Gate 5E quality)' . PHP_EOL;
    echo 'Payload manifests: 9 (one failed-attempt payload; eight successful v2 payloads)' . PHP_EOL;
    echo 'Measurements: 40' . PHP_EOL;
    echo 'Candidate selected: No' . PHP_EOL;
    echo 'Prior grounded failure overridden: No' . PHP_EOL;
    echo 'Accepted registers or docs changed: No' . PHP_EOL . PHP_EOL;
    echo 'GATE 5E SAVED REGISTRATION PREVIEW AUDIT PASSED.' . PHP_EOL;
    exit(0);
}

echo '=== GATE 5E REGISTRATION PREVIEW v1 ===' . PHP_EOL;
echo 'Mode: ' . $mode . PHP_EOL . PHP_EOL;
$registers = [];
foreach (G5E_BASELINE as $relative => $expected) {
    $path = g5ePath($root, $relative);
    g5eAssert(is_file($path), 'Missing register ' . $relative);
    g5eAssert(g5eHash($path) === $expected['hash'], 'Register hash changed: ' . $relative);
    $rows = g5eCsv($path);
    g5eAssert(count($rows) === $expected['count'], 'Register count changed: ' . $relative);
    $registers[$relative] = $rows;
    echo $relative . ': ' . count($rows) . ' rows; hash passed' . PHP_EOL;
}

$candidateMatches = array_values(array_filter(
    $registers['docs/ai-feasibility-spike/registers/candidates.csv'],
    static fn(array $row): bool => $row['candidate_configuration_id'] === G5E_CANDIDATE
));
g5eAssert(count($candidateMatches) === 1, 'Expected one existing Groq candidate row.');
foreach ($registers['docs/ai-feasibility-spike/registers/test_runs.csv'] as $row) {
    g5eAssert(!in_array($row['test_run_id'], [G5E_FAILED_RUN, G5E_PASSED_RUN], true), 'Proposed test-run ID already exists.');
}

$failedRelative = '.local/ai-feasibility-spike/results/summary-suggestion/' . G5E_CANDIDATE . '/run-20260814-112901Z';
$runRelative = '.local/ai-feasibility-spike/results/summary-suggestion/' . G5E_CANDIDATE . '/run-20260814-114049Z';
$reviewRelative = $runRelative . '-quality-review-preview-v1';
$v1Relative = '.local/ai-feasibility-spike/results/summary-suggestion-preview/' . G5E_CANDIDATE . '/payload-review-v1';
$v2Relative = '.local/ai-feasibility-spike/results/summary-suggestion-preview/' . G5E_CANDIDATE . '/payload-review-v2';

$failed = g5eJson(g5ePath($root, $failedRelative . '/failure-summary.json'));
$run = g5eJson(g5ePath($root, $runRelative . '/run-summary.json'));
$quality = g5eJson(g5ePath($root, $reviewRelative . '/quality-review-summary.json'));
g5eAssert($failed['requests_completed'] === 0 && $failed['automatic_retries'] === 0, 'Failed run boundary changed.');
g5eAssert($run['requests_completed'] === 8 && $run['automatic_retries'] === 0, 'Successful run boundary changed.');
g5eAssert($run['median_latency_ms'] === 1944.858 && $run['total_tokens'] === 15778, 'Successful run measurements changed.');
g5eAssert($quality['summary_support_rate_percent'] === 100, 'Summary quality changed.');
g5eAssert($quality['directly_usable_active_tag_rate_percent'] === 90, 'Tag quality changed.');
g5eAssert($quality['clearly_tag_eligible_coverage_percent'] === 100, 'Tag coverage changed.');
g5eAssert($quality['supported_metadata_suggestion_rate_percent'] === 85.71, 'Metadata quality changed.');
g5eAssert($quality['usable_as_is_or_light_edit_rate_percent'] === 100, 'Overall usability changed.');
g5eAssert($quality['candidate_selected'] === false && $quality['prior_grounded_generation_failure_overridden'] === false, 'Quality boundary changed.');
g5eAssert(g5eHash(g5ePath($root, $reviewRelative . '/quality-review-preview.csv')) === 'B541A6CBDB4B30DABFA0046CD865ED00217ADF5202188A0D2E1C6749B02DA7F1', 'Quality review rows changed.');

$candidateHeaders = array_keys($candidateMatches[0]);
$testHeaders = array_keys($registers['docs/ai-feasibility-spike/registers/test_runs.csv'][0]);
$measurementHeaders = array_keys($registers['docs/ai-feasibility-spike/results/measurements.csv'][0]);
$payloadHeaderLine = (string) file(g5ePath($root, 'docs/ai-feasibility-spike/registers/payload_manifests.csv'))[0];
$payloadHeaders = array_map(static fn(string $v): string => trim((string) preg_replace('/^\xEF\xBB\xBF/', '', $v)), str_getcsv($payloadHeaderLine));

$candidate = g5eCandidate($candidateMatches[0]);
g5eAssert(array_keys($candidate) === $candidateHeaders, 'Candidate schema mismatch.');
$measurements = g5eMeasurements($measurementHeaders);
$failedMeasurementIds = array_column(array_values(array_filter($measurements, static fn(array $row): bool => $row['test_run_id'] === G5E_FAILED_RUN)), 'measurement_record_id');
$passedMeasurementIds = array_column(array_values(array_filter($measurements, static fn(array $row): bool => $row['test_run_id'] === G5E_PASSED_RUN)), 'measurement_record_id');

$v1Payloads = g5eCsv(g5ePath($root, $v1Relative . '/payload-manifest-preview.csv'));
$v2Payloads = g5eCsv(g5ePath($root, $v2Relative . '/payload-manifest-preview.csv'));
g5eAssert(count($v1Payloads) === 8 && count($v2Payloads) === 8, 'Payload preview counts changed.');
$payloadRows = [];
$makePayload = static function(array $source, string $id, string $runId, string $authorization, string $path, string $notes) use ($payloadHeaders): array {
    $row = [
        'payload_manifest_id' => $id,
        'test_run_id' => $runId,
        'provider_or_model_candidate' => $source['provider_or_model_candidate'],
        'fixture_ids' => $source['fixture_ids'],
        'evidence_passage_ids' => '',
        'resource_count' => '1',
        'evidence_count' => '1',
        'included_data_categories' => $source['included_data_categories'],
        'source_identifiers_included' => $source['source_identifiers_included'],
        'locator_information_included' => $source['locator_information_included'],
        'approximate_size' => $source['approximate_size'],
        'approximate_size_unit' => $source['approximate_size_unit'],
        'excluded_data_categories' => $source['excluded_data_categories'],
        'personal_or_account_linked_information_included' => $source['personal_or_account_linked_information_included'],
        'justification' => 'Required bounded feasibility measurement of non-authoritative summaries and controlled tag/metadata suggestions using one accepted project-created synthetic resource.',
        'external_transmission_authorization_basis' => $authorization,
        'redacted_sample_path' => $path,
        'reviewer' => 'Project team',
        'notes' => $notes,
    ];
    g5eAssert(array_keys($row) === $payloadHeaders, 'Payload schema mismatch.');
    return $row;
};
$payloadRows[] = $makePayload(
    $v1Payloads[0],
    'PM-GROQ-SUMSUG-SCHEMA-001-01',
    G5E_FAILED_RUN,
    'Exact v1 eight-request preview reviewed; user approved one live run on 2026-08-14 after ZDR, model allowlist, project limits, key, payload, and cost guards were confirmed.',
    $v1Relative . '/PAYLOAD_REVIEW.md',
    'Only this first approved v1 payload was transmitted. The provider rejected unsupported uniqueItems before model generation; seven remaining v1 payloads were not sent.'
);
$passedPayloadIds = [];
foreach ($v2Payloads as $i => $source) {
    $id = sprintf('PM-GROQ-SUMSUG-001-%02d', $i + 1);
    $passedPayloadIds[] = $id;
    $payloadRows[] = $makePayload(
        $source,
        $id,
        G5E_PASSED_RUN,
        'Versioned v2 compatibility payloads reviewed; user approved one corrected live run on 2026-08-14 after the exact schema-only delta and retained runner-side uniqueness guards were audited.',
        $v2Relative . '/PAYLOAD_REVIEW.md',
        'Exact transmitted v2 payload. The only request-body change from v1 was removal of unsupported uniqueItems schema keywords; content, settings, boundaries, and runner-side uniqueness validation were retained.'
    );
}
$testRuns = [
    g5eTestRun($testHeaders, true, $failedMeasurementIds, ['PM-GROQ-SUMSUG-SCHEMA-001-01']),
    g5eTestRun($testHeaders, false, $passedMeasurementIds, $passedPayloadIds),
];

echo PHP_EOL . '=== PROPOSED REGISTRATION ===' . PHP_EOL;
echo 'Candidate rows updated: 1 (same candidate ID; mixed capability history)' . PHP_EOL;
echo 'Test-run rows added: 2' . PHP_EOL;
echo 'Payload-manifest rows added: 9' . PHP_EOL;
echo 'Measurement rows added: ' . count($measurements) . PHP_EOL;
echo 'Failed v1 schema attempt preserved: Yes' . PHP_EOL;
echo 'Successful v2 Gate 5E quality verdict: Passed' . PHP_EOL;
echo 'Prior grounded-generation failure overridden: No' . PHP_EOL;
echo 'Candidate selected: No' . PHP_EOL;
echo 'Application integration authorized: No' . PHP_EOL;
echo 'Additional provider requests: 0' . PHP_EOL;

if ($mode === 'validate') {
    echo PHP_EOL . 'GATE 5E REGISTRATION PREVIEW VALIDATION PASSED.' . PHP_EOL;
    echo 'No preview folder, provider request, register/documentation change, candidate selection, integration, commit, or push was performed.' . PHP_EOL;
    echo 'Next permitted action: rerun with --mode=apply to create the ignored local registration preview.' . PHP_EOL;
    exit(0);
}

g5eAssert(!file_exists($output), 'Preview folder already exists; refusing overwrite.');
g5eAssert(mkdir($output, 0777, true), 'Could not create preview folder.');
g5eWriteCsv($output . '/candidate-updated-row.csv', $candidateHeaders, [$candidate]);
g5eWriteCsv($output . '/test-run-rows.csv', $testHeaders, $testRuns);
g5eWriteCsv($output . '/payload-manifest-rows.csv', $payloadHeaders, $payloadRows);
g5eWriteCsv($output . '/measurement-rows.csv', $measurementHeaders, $measurements);

$documentation = <<<'MARKDOWN'
# Gate 5E Registration and Documentation Preview

## Proposed factual status

The first approved Gate 5E attempt stopped safely after the first request returned
HTTP 400 because Groq's strict-schema subset did not support `uniqueItems`. It
created no model output, made zero automatic retries, did not send the remaining
seven requests, and preserved the failed evidence. A separately reviewed v2
removed only the unsupported schema keywords while retaining runner-side
uniqueness validation, all content and settings, and every safety boundary.

The corrected v2 run completed 8/8 HTTP 200 requests with zero retries. Median
latency was 1,944.858 ms; all eight completed within 15 seconds; provider usage
was 12,488 prompt tokens and 3,290 completion tokens; estimated published-rate
cost was USD 0.0038472.

The user-approved quality review measured 8/8 source-supported summaries (100%),
9/10 directly usable Active tag suggestions (90%), 6/6 clearly tag-eligible
cases covered (100%), 18/21 supported metadata suggestions (85.71%), and 8/8
outputs usable as-is or after light editing (100%). Every accepted Gate 5E
threshold passed. Preserved weaknesses include three Handout type suggestions
that should be omitted or reviewed, one broad Programming tag, and some broad
but source-related secondary tags.

This is a capability-specific pass for non-authoritative summaries and controlled
suggestions. It does not repair the candidate's earlier failed grounded-answer
claim-support and source-attribution result. The correct candidate status is
mixed and still unselected. No AI route, UI, permanent output storage, taxonomy
change, schema change, or provider integration is authorized.

## Proposed tracked update if separately approved

- Update the existing candidate row without adding a second candidate.
- Add one failed schema-compatibility test run and one passed Gate 5E test run.
- Add one actually transmitted v1 payload record and eight actually transmitted
  v2 payload records; do not register the seven unsent v1 payloads.
- Add the execution, cost, quality, weakness, safety, and decision-boundary
  measurements in this preview.
- Update `SUMMARY_SUGGESTION_PAYLOAD_PLAN.md`,
  `EXTERNAL_GENERATION_PREFLIGHT.md`, `results/findings.md`, `BUILD_PLAN.md`,
  `TESTING_CHECKLIST.md`, and `PROJECT_HANDOFF.md` to replace stale pre-run text
  with the measured mixed result.

Approving tracked implementation would make those narrow register and status-doc
changes only. It would not call Groq again, change thresholds or ground truth,
choose a final model/provider, integrate AI, change taxonomy/resources/schema,
commit, or push. Not approving leaves the accepted registers and status documents
unchanged; this ignored preview remains local evidence only.
MARKDOWN;
file_put_contents($output . '/DOCUMENTATION_PREVIEW.md', $documentation . PHP_EOL);

$summary = [
    'status' => 'registration_preview_ready_not_applied',
    'candidate_id' => G5E_CANDIDATE,
    'proposed_candidate_rows_updated' => 1,
    'proposed_test_run_rows_added' => 2,
    'proposed_payload_manifest_rows_added' => 9,
    'proposed_measurement_rows_added' => count($measurements),
    'failed_schema_run_status' => 'failed',
    'successful_quality_run_status' => 'passed',
    'quality_review_approved_by_user' => true,
    'all_gate5e_thresholds_met' => true,
    'prior_grounded_failure_overridden' => false,
    'candidate_selected' => false,
    'application_integration_authorized' => false,
    'additional_provider_requests' => 0,
    'registers_or_docs_modified' => false,
];
g5eWriteJson($output . '/registration-summary.json', $summary);
$manifestFiles = [
    'candidate-updated-row.csv',
    'test-run-rows.csv',
    'payload-manifest-rows.csv',
    'measurement-rows.csv',
    'DOCUMENTATION_PREVIEW.md',
    'registration-summary.json',
];
$manifestRows = [];
foreach ($manifestFiles as $name) {
    $path = $output . '/' . $name;
    $manifestRows[] = [
        'artifact_name' => $name,
        'size_bytes' => (string) filesize($path),
        'sha256' => g5eHash($path),
    ];
}
g5eWriteCsv($output . '/artifact-manifest.csv', ['artifact_name','size_bytes','sha256'], $manifestRows);
file_put_contents($output . '/READY.marker', 'REGISTRATION_PREVIEW_READY_NOT_APPLIED' . PHP_EOL);

echo PHP_EOL . 'GATE 5E REGISTRATION PREVIEW SAVED.' . PHP_EOL;
echo 'Preview folder: ' . $output . PHP_EOL;
echo 'Manifest entries: 6/6' . PHP_EOL;
echo 'No provider request, register/documentation change, candidate selection, integration, commit, or push was performed.' . PHP_EOL;
echo 'Next permitted action: run --mode=audit-preview, then request explicit approval before tracked implementation.' . PHP_EOL;
