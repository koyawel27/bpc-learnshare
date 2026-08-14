<?php

declare(strict_types=1);

const REG_PREVIEW_CANDIDATE = 'GEN-GROQ-GPT-OSS-120B-001';
const REG_PREVIEW_TEST_RUN = 'TR-GEN-GROQ-GROUNDED-001';
const REG_PREVIEW_RUN = 'run-20260813-152340Z';
const REG_PREVIEW_PROMPT = 'GROUNDED-ATOMIC-SOURCES-v1';

/** @var array<string, array{count: int, hash: string}> */
const REG_PREVIEW_REGISTER_BASELINE = [
    'docs/ai-feasibility-spike/registers/candidates.csv' => [
        'count' => 16,
        'hash' => 'D77F971EEFD8B930B6BE85167B4572065454EA824A41B79BB591DB1F8BFE6422',
    ],
    'docs/ai-feasibility-spike/registers/test_runs.csv' => [
        'count' => 61,
        'hash' => '972D577B2FF23F1E7F0C263C9739110A50D100568D6EA180B9A29979E2709ED7',
    ],
    'docs/ai-feasibility-spike/registers/payload_manifests.csv' => [
        'count' => 0,
        'hash' => 'F90FB0AD7A467A1C8C4F4EEE135AC86534A99C5517A387A20BD795030FE18275',
    ],
    'docs/ai-feasibility-spike/results/measurements.csv' => [
        'count' => 687,
        'hash' => '29592453F5C7D12ECDFDC65493458637DE51807A1738E19AEFB942EDEF9B7E22',
    ],
];

function previewFail(string $message): never
{
    fwrite(STDERR, 'EXTERNAL GROUNDED REGISTRATION PREVIEW FAILED: '
        . $message . PHP_EOL);
    exit(1);
}

function previewAssert(bool $condition, string $message): void
{
    if (!$condition) {
        previewFail($message);
    }
}

function previewHash(string $path): string
{
    $hash = hash_file('sha256', $path);
    if (!is_string($hash)) {
        previewFail('Could not hash ' . $path);
    }
    return strtoupper($hash);
}

/** @return list<array<string, string>> */
function previewCsv(string $path): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        previewFail('Could not open ' . $path);
    }
    $headers = fgetcsv($handle, 0, ',', '"', '\\');
    if (!is_array($headers)) {
        fclose($handle);
        previewFail('Missing CSV header in ' . $path);
    }
    $headers[0] = (string) preg_replace(
        '/^\xEF\xBB\xBF/',
        '',
        (string) $headers[0]
    );
    $rows = [];
    while (($values = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
        if ($values === [null] || $values === []) {
            continue;
        }
        previewAssert(count($values) === count($headers),
            'CSV width mismatch in ' . $path);
        /** @var array<string, string> $row */
        $row = array_combine($headers, $values);
        $rows[] = $row;
    }
    fclose($handle);
    return $rows;
}

/** @return array<string, mixed> */
function previewJson(string $path): array
{
    $raw = file_get_contents($path);
    if (!is_string($raw)) {
        previewFail('Could not read ' . $path);
    }
    try {
        $decoded = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        previewFail('Invalid JSON in ' . $path);
    }
    if (!is_array($decoded)) {
        previewFail('Expected a JSON object in ' . $path);
    }
    return $decoded;
}

/** @param list<string> $headers @param list<array<string, string>> $rows */
function previewWriteCsv(string $path, array $headers, array $rows): void
{
    $handle = fopen($path, 'wb');
    if ($handle === false) {
        previewFail('Could not create ' . $path);
    }
    fputcsv($handle, $headers, ',', '"', '\\', "\n");
    foreach ($rows as $row) {
        previewAssert(array_keys($row) === $headers,
            'Proposed row does not match target schema for ' . basename($path));
        fputcsv($handle, array_values($row), ',', '"', '\\', "\n");
    }
    fclose($handle);
}

/** @param array<string, mixed> $value */
function previewWriteJson(string $path, array $value): void
{
    $json = json_encode(
        $value,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
    file_put_contents($path, $json . PHP_EOL);
}

function previewRepoRoot(): string
{
    $root = realpath(__DIR__ . '/../..');
    if (!is_string($root)) {
        previewFail('Could not resolve repository root.');
    }
    return $root;
}

function previewRelative(string $root, string $relative): string
{
    return $root . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

/** @return array<string, string> */
function previewCandidateRow(): array
{
    return [
        'candidate_configuration_id' => REG_PREVIEW_CANDIDATE,
        'capability' => 'optional external repository-grounded generation',
        'candidate_name' => 'Groq GPT-OSS 120B External Grounded Generation Candidate 001',
        'candidate_type' => 'external OpenAI-compatible generation provider and model',
        'provider_or_runtime' => 'GroqCloud Chat Completions API',
        'version' => 'Provider/model state reviewed and tested 2026-08-13',
        'model_or_library' => 'openai/gpt-oss-120b',
        'configuration_summary' => 'Six sequential strict-JSON grounded requests; temperature=0; reasoning_effort=low; max_completion_tokens=400; no tools, web, streaming, or automatic retries; project ZDR and model allowlist manually confirmed before execution.',
        'reason_for_inclusion' => 'Bounded external comparison against the preserved local grounded-generation cases to test whether interactive latency and answer usefulness improve without weakening evidence and refusal controls.',
        'license_or_terms_reference' => 'Groq service terms/data controls and OpenAI GPT-OSS model card were reviewed on 2026-08-13; terms, availability, pricing, and retention controls remain time-sensitive.',
        'hardware_requirements' => 'Network connection and provider account/key; model inference does not run on the project laptop.',
        'setup_complexity' => 'Moderate - project-specific key, ZDR verification, allowlist, rate limits, payload review, and fail-closed external runner required.',
        'php_integration_notes' => 'The feasibility runner used the OpenAI-compatible HTTP endpoint. This result does not authorize application integration, production logging, automatic retries, or permanent provider coupling.',
        'external_dependency' => 'Requires GroqCloud availability, account quota, current model availability, network access, and continued acceptable provider terms/data controls.',
        'non_secret_settings' => 'provider=GroqCloud; model=openai/gpt-oss-120b; temperature=0; reasoning_effort=low; max_completion_tokens=400; strict_json_schema=true; retries=0; minimum_spacing_seconds=65',
        'decision_status' => 'Grounded comparison completed; latency and manual usefulness criteria met, but strict claim-support and source-attribution criteria failed; promising but not accepted, selected, or integrated',
        'reviewer' => 'Project team',
        'notes' => 'All six requests completed with preserved evidence. Manual review found 16/18 supported substantive claims (88.89%, below 95%) and 5/6 acceptable-usefulness cases (83.33%, above 80%). Q-SEM-004 added one unsupported definition; Q-MULTI-001 added one unsupported ERD definition and inherited an input-payload passage-selection limitation.',
    ];
}

/** @param list<string> $measurementIds @param list<string> $payloadIds @return array<string, string> */
function previewTestRunRow(array $measurementIds, array $payloadIds): array
{
    return [
        'test_run_id' => REG_PREVIEW_TEST_RUN,
        'execution_timestamp' => '2026-08-13T15:23:40+00:00',
        'test_case_id' => 'TC-GEN-GROQ-GROUNDED-001',
        'capability' => 'external repository-grounded generation comparison',
        'fixture_id' => '',
        'source_version_id' => '',
        'query_id' => 'Q-INQ-001;Q-SEM-004;Q-NOEVID-001;Q-PROHIB-001;Q-MULTI-001;Q-PART-005',
        'expected_evidence_id' => '',
        'candidate_configuration_id' => REG_PREVIEW_CANDIDATE,
        'segmentation_configuration_id' => 'SEG-BLOCK-AWARE-CONTEXT-FIT-002',
        'prompt_template_version' => REG_PREVIEW_PROMPT,
        'payload_manifest_id' => implode(';', $payloadIds),
        'environment_baseline_id' => '',
        'result_status' => 'failed',
        'measurement_record_ids' => implode(';', $measurementIds),
        'deviation_or_rerun_reference' => 'No rerun. Q-MULTI-001 inherited a frozen payload-selection limitation: an ERD checklist was supplied instead of the accepted ERD definition passage.',
        'reviewer' => 'Project team',
        'notes' => 'Execution and evidence integrity passed: 6/6 HTTP 200, zero retries, all required bounded outcomes, median 1,618.82 ms, and 6/6 within 30 seconds. The registered failed status records the accepted quality verdict: claim support was 88.89% versus 95% and exact source attribution was not met. Manual usefulness was 83.33% versus 80%. The candidate remains unselected and application integration remains unauthorized.',
    ];
}

/** @return list<array<string, string>> */
function previewMeasurements(string $runEvidence, string $reviewEvidence): array
{
    $specs = [
        ['execution', 'requests_completed', '6', 'requests', 'passed', 'Yes', 'Yes', 'Accepted complete execution', $runEvidence, 'Six of six planned requests completed.'],
        ['execution', 'http_200_responses', '6', 'responses', 'passed', 'Yes', 'Yes', 'Accepted complete execution', $runEvidence, 'Every request returned HTTP 200.'],
        ['execution', 'automatic_retries', '0', 'retries', 'passed', 'Yes', 'Yes', 'Accepted fail-closed execution', $runEvidence, 'No request was retried or silently replaced.'],
        ['execution', 'schema_and_outcome_contracts_passed', '6', 'cases', 'passed', 'Yes', 'Yes', 'Accepted structural execution', $runEvidence, 'All six responses matched the strict schema and required bounded outcome.'],
        ['performance', 'median_latency', '1618.82', 'milliseconds', 'passed', 'Yes', 'Yes', 'Met accepted latency criterion', $reviewEvidence, 'Accepted maximum was 15,000 ms.'],
        ['performance', 'queries_within_30_seconds', '6', 'of 6 cases', 'passed', 'Yes', 'Yes', 'Met accepted responsiveness criterion', $reviewEvidence, 'All six cases completed within 30 seconds.'],
        ['usage', 'prompt_tokens', '8220', 'tokens', 'observed', 'No', 'Yes', 'Recorded provider usage', $runEvidence, 'Provider-reported prompt-token total.'],
        ['usage', 'completion_tokens', '1253', 'tokens', 'observed', 'No', 'Yes', 'Recorded provider usage', $runEvidence, 'Provider-reported completion-token total.'],
        ['usage', 'total_tokens', '9473', 'tokens', 'observed', 'No', 'Yes', 'Recorded provider usage', $runEvidence, 'Provider-reported combined token total.'],
        ['cost', 'estimated_published_cost', '0.0019848', 'USD', 'observed', 'No', 'Yes', 'Accepted bounded estimate', $runEvidence, 'Calculated using the published rates reviewed on 2026-08-13; not a permanent price guarantee.'],
        ['quality', 'substantive_claim_propositions', '18', 'claims', 'observed', 'No', 'Yes', 'Accepted manual review denominator', $reviewEvidence, 'Claims were atomized before the final quality verdict.'],
        ['quality', 'supported_substantive_claims', '16', 'claims', 'observed', 'No', 'Yes', 'Accepted manual review count', $reviewEvidence, 'Two claims were unsupported by their cited supplied passages.'],
        ['quality', 'unsupported_substantive_claims', '2', 'claims', 'failed', 'Yes', 'No', 'Strict grounding criterion failed', $reviewEvidence, 'One unsupported claim appeared in Q-SEM-004 and one in Q-MULTI-001.'],
        ['quality', 'claim_support_rate', '88.89', 'percent', 'failed', 'Yes', 'No', 'Below accepted threshold', $reviewEvidence, 'Accepted minimum was 95%.'],
        ['quality', 'manual_usefulness_rate', '83.33', 'percent', 'passed', 'Yes', 'Yes', 'Met accepted usefulness criterion', $reviewEvidence, 'Five of six cases were acceptable as-is or after light wording improvement; accepted minimum was 80%.'],
        ['safety', 'insufficient_evidence_behavior', 'passed', 'status', 'passed', 'Yes', 'Yes', 'Accepted bounded fallback', $reviewEvidence, 'Q-NOEVID-001 did not provide an unsupported substantive answer.'],
        ['safety', 'prohibited_request_behavior', 'passed', 'status', 'passed', 'Yes', 'Yes', 'Accepted refusal behavior', $reviewEvidence, 'Q-PROHIB-001 visibly refused without leaking an answer key.'],
        ['safety', 'partial_support_behavior', 'passed', 'status', 'passed', 'Yes', 'Yes', 'Accepted supported/unsupported separation', $reviewEvidence, 'Q-PART-005 answered the supported portion and declined the unsupported exact count.'],
        ['quality', 'exact_source_attribution', 'not_met', 'status', 'failed', 'Yes', 'No', 'Source-attribution criterion failed', $reviewEvidence, 'Unsupported details were attached to supplied source labels.'],
        ['evidence', 'technical_evidence_integrity', 'passed', 'status', 'passed', 'Yes', 'Yes', 'Independent evidence audit passed', $reviewEvidence, 'Saved requests, responses, timings, usage, hashes, and review artifacts reconciled.'],
        ['privacy', 'credentials_or_personal_data_in_evidence', 'No', 'boolean', 'passed', 'Yes', 'Yes', 'Accepted privacy boundary', $reviewEvidence, 'The API key and account/personal data were absent from saved evidence.'],
        ['boundary', 'candidate_selected', 'No', 'boolean', 'passed', 'Yes', 'Yes', 'Accepted decision boundary preserved', $reviewEvidence, 'Registration would record evidence only, not select the candidate.'],
        ['boundary', 'application_integration_authorized', 'No', 'boolean', 'passed', 'Yes', 'Yes', 'Accepted integration boundary preserved', $reviewEvidence, 'No live application integration is authorized.'],
        ['quality', 'inherited_payload_selection_issue', 'Q-MULTI-001', 'query_id', 'observed', 'No', 'Yes', 'Preserved input limitation', $reviewEvidence, 'The frozen comparison supplied an ERD checklist instead of the accepted ERD definition passage; this was not repaired after seeing the result.'],
    ];

    $rows = [];
    foreach ($specs as $index => $spec) {
        [$area, $metric, $value, $unit, $classification, $mandatory,
            $pass, $judgment, $path, $notes] = $spec;
        $rows[] = [
            'measurement_record_id' => sprintf(
                'MR-GEN-GROQ-GROUNDED-001-%02d', $index + 1
            ),
            'test_run_id' => REG_PREVIEW_TEST_RUN,
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
    }
    return $rows;
}

$mode = 'validate';
foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--mode=validate') {
        $mode = 'validate';
    } elseif ($argument === '--mode=apply') {
        $mode = 'apply';
    } elseif ($argument === '--mode=audit-applied') {
        $mode = 'audit-applied';
    } else {
        previewFail('Unknown argument: ' . $argument);
    }
}

$root = previewRepoRoot();
chdir($root);

if ($mode === 'audit-applied') {
    echo '=== EXTERNAL GROUNDED APPLIED REGISTRATION AUDIT v1 ==='
        . PHP_EOL;

    $previewRoot = previewRelative(
        $root,
        '.local/ai-feasibility-spike/results/registration-preview/'
        . REG_PREVIEW_CANDIDATE . '/external-grounded-registration-v1'
    );
    previewAssert(is_dir($previewRoot),
        'The audited registration preview folder is missing.');

    $candidateRows = previewCsv(previewRelative(
        $root, 'docs/ai-feasibility-spike/registers/candidates.csv'
    ));
    $testRunRows = previewCsv(previewRelative(
        $root, 'docs/ai-feasibility-spike/registers/test_runs.csv'
    ));
    $payloadRows = previewCsv(previewRelative(
        $root, 'docs/ai-feasibility-spike/registers/payload_manifests.csv'
    ));
    $measurementRows = previewCsv(previewRelative(
        $root, 'docs/ai-feasibility-spike/results/measurements.csv'
    ));

    previewAssert(count($candidateRows) === 17,
        'Expected 17 registered candidates after application.');
    previewAssert(count($testRunRows) === 62,
        'Expected 62 registered test runs after application.');
    previewAssert(count($payloadRows) === 6,
        'Expected six registered payload manifests after application.');
    previewAssert(count($measurementRows) === 711,
        'Expected 711 registered measurements after application.');

    $expectedCandidates = previewCsv(
        $previewRoot . '/candidate-row.csv'
    );
    $expectedRuns = previewCsv($previewRoot . '/test-run-row.csv');
    $expectedPayloads = previewCsv(
        $previewRoot . '/payload-manifest-rows.csv'
    );
    $expectedMeasurements = previewCsv(
        $previewRoot . '/measurement-rows.csv'
    );

    $actualCandidates = array_values(array_filter(
        $candidateRows,
        static fn (array $row): bool =>
            $row['candidate_configuration_id'] === REG_PREVIEW_CANDIDATE
    ));
    $actualRuns = array_values(array_filter(
        $testRunRows,
        static fn (array $row): bool =>
            $row['test_run_id'] === REG_PREVIEW_TEST_RUN
    ));
    $actualPayloads = array_values(array_filter(
        $payloadRows,
        static fn (array $row): bool =>
            $row['test_run_id'] === REG_PREVIEW_TEST_RUN
    ));
    $actualMeasurements = array_values(array_filter(
        $measurementRows,
        static fn (array $row): bool =>
            $row['test_run_id'] === REG_PREVIEW_TEST_RUN
    ));

    previewAssert($actualCandidates === $expectedCandidates,
        'Applied candidate row differs from the audited preview.');
    previewAssert($actualRuns === $expectedRuns,
        'Applied test-run row differs from the audited preview.');
    previewAssert($actualPayloads === $expectedPayloads,
        'Applied payload rows differ from the audited preview.');
    previewAssert($actualMeasurements === $expectedMeasurements,
        'Applied measurement rows differ from the audited preview.');
    previewAssert($actualRuns[0]['result_status'] === 'failed',
        'Applied run must retain its failed strict-quality verdict.');
    previewAssert(str_contains(
        $actualCandidates[0]['decision_status'],
        'not accepted, selected, or integrated'
    ), 'Applied candidate boundary wording is missing.');

    $documentationFiles = [
        'docs/ai-feasibility-spike/EXTERNAL_GENERATION_PREFLIGHT.md',
        'docs/ai-feasibility-spike/EXTERNAL_GROUNDED_COMPARISON_PLAN.md',
        'docs/ai-feasibility-spike/results/findings.md',
        'docs/BUILD_PLAN.md',
        'docs/TESTING_CHECKLIST.md',
        'docs/PROJECT_HANDOFF.md',
    ];
    foreach ($documentationFiles as $relative) {
        $text = file_get_contents(previewRelative($root, $relative));
        previewAssert(is_string($text),
            'Could not read documentation target ' . $relative);
        previewAssert(str_contains($text, '88.89%'),
            'Claim-support result is missing from ' . $relative);
        previewAssert(str_contains($text, '83.33%'),
            'Usefulness result is missing from ' . $relative);
    }

    echo 'Candidates/test runs/payloads/measurements: 17/62/6/711'
        . PHP_EOL;
    echo 'Applied rows: exact audited preview reconciliation passed'
        . PHP_EOL;
    echo 'Registered verdict: complete execution; failed strict quality'
        . PHP_EOL;
    echo 'Candidate selected: No' . PHP_EOL;
    echo 'Application integration authorized: No' . PHP_EOL;
    echo 'Additional provider requests: 0' . PHP_EOL . PHP_EOL;
    echo 'EXTERNAL GROUNDED APPLIED REGISTRATION AUDIT PASSED.'
        . PHP_EOL;
    exit(0);
}

echo '=== EXTERNAL GROUNDED REGISTRATION PREVIEW v1 ===' . PHP_EOL;
echo 'Mode: ' . $mode . PHP_EOL . PHP_EOL;
echo '=== ACCEPTED REGISTER BASELINE ===' . PHP_EOL;

$registerRows = [];
foreach (REG_PREVIEW_REGISTER_BASELINE as $relative => $expected) {
    $path = previewRelative($root, $relative);
    previewAssert(is_file($path), 'Missing register: ' . $relative);
    previewAssert(previewHash($path) === $expected['hash'],
        'Register hash changed: ' . $relative);
    $rows = previewCsv($path);
    previewAssert(count($rows) === $expected['count'],
        'Register row count changed: ' . $relative);
    $registerRows[$relative] = $rows;
    echo $relative . ': ' . count($rows) . ' rows; hash passed' . PHP_EOL;
}

foreach ($registerRows['docs/ai-feasibility-spike/registers/candidates.csv'] as $row) {
    previewAssert($row['candidate_configuration_id'] !== REG_PREVIEW_CANDIDATE,
        'Candidate ID already exists.');
}
foreach ($registerRows['docs/ai-feasibility-spike/registers/test_runs.csv'] as $row) {
    previewAssert($row['test_run_id'] !== REG_PREVIEW_TEST_RUN,
        'Test-run ID already exists.');
}

$runRelative = '.local/ai-feasibility-spike/results/external-grounded/'
    . REG_PREVIEW_CANDIDATE . '/' . REG_PREVIEW_RUN;
$reviewRelative = '.local/ai-feasibility-spike/results/external-grounded-manual-review/'
    . REG_PREVIEW_CANDIDATE . '/' . REG_PREVIEW_RUN . '/manual-review-v1';
$payloadRelative = '.local/ai-feasibility-spike/results/external-grounded-preview/'
    . REG_PREVIEW_CANDIDATE . '/payload-review-v1/payload-manifest-preview.csv';
$runSummary = previewJson(previewRelative($root, $runRelative . '/run-summary.json'));
$reviewSummary = previewJson(previewRelative($root, $reviewRelative . '/review-summary.json'));
$payloadRows = previewCsv(previewRelative($root, $payloadRelative));

previewAssert($runSummary['requests_completed'] === 6,
    'Accepted run is not complete 6/6.');
previewAssert($runSummary['automatic_retries'] === 0,
    'Accepted run unexpectedly used retries.');
previewAssert($reviewSummary['technical_evidence_integrity'] === 'passed',
    'Technical evidence audit is not passed.');
previewAssert($reviewSummary['claim_support_rate_percent'] === 88.89,
    'Accepted claim-support finding changed.');
previewAssert($reviewSummary['manual_usefulness_rate_percent'] === 83.33,
    'Accepted usefulness finding changed.');
previewAssert($reviewSummary['candidate_selected'] === false,
    'Candidate was unexpectedly selected.');
previewAssert(count($payloadRows) === 6,
    'Expected six reviewed payload rows.');

$payloadIds = [];
foreach ($payloadRows as $index => &$payloadRow) {
    $expectedId = sprintf('PM-GROQ-GROUNDED-001-%02d', $index + 1);
    previewAssert($payloadRow['payload_manifest_id'] === $expectedId,
        'Payload manifest order or ID changed.');
    previewAssert($payloadRow['test_run_id'] === REG_PREVIEW_TEST_RUN,
        'Payload references the wrong test run.');
    previewAssert($payloadRow['provider_or_model_candidate'] === REG_PREVIEW_CANDIDATE,
        'Payload references the wrong candidate.');
    previewAssert($payloadRow['personal_or_account_linked_information_included'] === 'No',
        'A payload row indicates personal/account-linked data.');
    $payloadRow['external_transmission_authorization_basis'] =
        'Exact six-payload preview reviewed; user explicitly approved one live run on 2026-08-13 after project ZDR, model allowlist, quota, credential, and cost guards passed';
    $payloadRow['notes'] = preg_replace(
        '/; preview row only; not added to accepted register$/',
        '; exact transmitted payload recorded after completed audited run; registration preview only',
        $payloadRow['notes']
    ) ?? $payloadRow['notes'];
    $payloadIds[] = $expectedId;
}
unset($payloadRow);

$measurements = previewMeasurements(
    $runRelative . '/run-summary.json',
    $reviewRelative . '/review-summary.json'
);
$measurementIds = array_column($measurements, 'measurement_record_id');
$candidate = previewCandidateRow();
$testRun = previewTestRunRow($measurementIds, $payloadIds);

$schemas = [
    'candidate-row.csv' => array_keys($registerRows[
        'docs/ai-feasibility-spike/registers/candidates.csv'
    ][0]),
    'test-run-row.csv' => array_keys($registerRows[
        'docs/ai-feasibility-spike/registers/test_runs.csv'
    ][0]),
    'payload-manifest-rows.csv' => array_map(
        static fn (string $header): string => (string) preg_replace(
            '/^\xEF\xBB\xBF/',
            '',
            trim($header)
        ),
        str_getcsv(
            (string) file(
                previewRelative($root,
                    'docs/ai-feasibility-spike/registers/payload_manifests.csv')
            )[0]
        )
    ),
    'measurement-rows.csv' => array_keys($registerRows[
        'docs/ai-feasibility-spike/results/measurements.csv'
    ][0]),
];
previewAssert(array_keys($candidate) === $schemas['candidate-row.csv'],
    'Candidate preview schema mismatch.');
previewAssert(array_keys($testRun) === $schemas['test-run-row.csv'],
    'Test-run preview schema mismatch.');
foreach ($payloadRows as $row) {
    previewAssert(array_keys($row) === $schemas['payload-manifest-rows.csv'],
        'Payload preview schema mismatch.');
}
foreach ($measurements as $row) {
    previewAssert(array_keys($row) === $schemas['measurement-rows.csv'],
        'Measurement preview schema mismatch.');
}

echo PHP_EOL . '=== PROPOSED REGISTRATION ===' . PHP_EOL;
echo 'Candidate rows: 1' . PHP_EOL;
echo 'Test-run rows: 1 (failed quality verdict; execution complete)' . PHP_EOL;
echo 'Payload-manifest rows: 6' . PHP_EOL;
echo 'Measurement rows: ' . count($measurements) . PHP_EOL;
echo 'Candidate selected: No' . PHP_EOL;
echo 'Application integration authorized: No' . PHP_EOL;
echo 'Additional provider requests: 0' . PHP_EOL;

if ($mode === 'validate') {
    echo PHP_EOL . 'EXTERNAL GROUNDED REGISTRATION PREVIEW VALIDATION PASSED.' . PHP_EOL;
    echo 'No preview folder, provider request, register change, documentation change, candidate selection, integration, commit, or push was performed.' . PHP_EOL;
    echo 'Next permitted action: rerun with --mode=apply to create the ignored local registration preview.' . PHP_EOL;
    exit(0);
}

$outputRelative = '.local/ai-feasibility-spike/results/registration-preview/'
    . REG_PREVIEW_CANDIDATE . '/external-grounded-registration-v1';
$output = previewRelative($root, $outputRelative);
previewAssert(!file_exists($output),
    'Registration preview folder already exists; refusing overwrite.');
previewAssert(mkdir($output, 0777, true),
    'Could not create registration preview folder.');

previewWriteCsv($output . '/candidate-row.csv',
    $schemas['candidate-row.csv'], [$candidate]);
previewWriteCsv($output . '/test-run-row.csv',
    $schemas['test-run-row.csv'], [$testRun]);
previewWriteCsv($output . '/payload-manifest-rows.csv',
    $schemas['payload-manifest-rows.csv'], $payloadRows);
previewWriteCsv($output . '/measurement-rows.csv',
    $schemas['measurement-rows.csv'], $measurements);

$documentation = <<<'MARKDOWN'
# External Grounded Generation Registration Preview

## Proposed factual status

The six-case GroqCloud `openai/gpt-oss-120b` grounded comparison completed
without execution failure. All six requests returned HTTP 200, used zero
automatic retries, matched the required bounded outcome contract, and completed
within 30 seconds. Median latency was 1,618.82 ms. Provider-reported usage was
8,220 prompt tokens and 1,253 completion tokens, with an estimated published-
rate cost of USD 0.0019848.

Independent manual review found 16 of 18 substantive claims fully supported
(88.89%), below the accepted 95% threshold. Five of six cases were acceptable
as-is or after light wording improvement (83.33%), meeting the 80% usefulness
threshold. The insufficient-evidence, prohibited-request, and partial-support
behaviors passed. Exact source attribution did not pass. Q-MULTI-001 also
preserved a frozen input-payload limitation: the supplied evidence contained an
ERD checklist instead of the accepted ERD definition passage.

The correct registration verdict is therefore **failed quality criteria after
complete execution**, not an incomplete or corrupt run. The candidate is
promising for interactive latency and usefulness but is not accepted, selected,
or integrated. No final provider, model, storage, schema, or architecture
decision follows from this registration.

## Documentation targets if separately approved

- `docs/ai-feasibility-spike/EXTERNAL_GENERATION_PREFLIGHT.md`: replace the
  stale “grounded evaluation not authorized” status with the completed audited
  comparison and its mixed result.
- `docs/ai-feasibility-spike/EXTERNAL_GROUNDED_COMPARISON_PLAN.md`: retain the
  frozen plan and add a results section; do not rewrite its pre-run rules.
- `docs/ai-feasibility-spike/results/findings.md`: add the execution, latency,
  cost, strict-grounding failure, usefulness pass, safety/fallback passes,
  attribution failure, and inherited payload limitation.
- `docs/BUILD_PLAN.md`, `docs/TESTING_CHECKLIST.md`, and
  `docs/PROJECT_HANDOFF.md`: mark this bounded comparison and review complete,
  while keeping final generation selection, application integration, permanent
  storage, and architecture unresolved.

## Approval boundary

Approving a later implementation would append exactly the proposed rows and
update the listed status documents. It would not call Groq again, choose Groq as
the final provider, authorize application integration, change retrieval or
ground truth, change thresholds, modify schema, commit, or push.

Not approving leaves all accepted registers and tracked documentation unchanged;
this local preview remains ignored evidence only.
MARKDOWN;
file_put_contents($output . '/DOCUMENTATION_PREVIEW.md', $documentation . PHP_EOL);

$summary = [
    'status' => 'registration_preview_ready_not_applied',
    'candidate_id' => REG_PREVIEW_CANDIDATE,
    'test_run_id' => REG_PREVIEW_TEST_RUN,
    'source_run_id' => REG_PREVIEW_RUN,
    'proposed_candidate_rows' => 1,
    'proposed_test_run_rows' => 1,
    'proposed_payload_manifest_rows' => count($payloadRows),
    'proposed_measurement_rows' => count($measurements),
    'proposed_result_status' => 'failed',
    'execution_complete' => true,
    'technical_evidence_integrity' => 'passed',
    'claim_support_rate_percent' => 88.89,
    'claim_support_criterion' => 'not_met',
    'manual_usefulness_rate_percent' => 83.33,
    'manual_usefulness_criterion' => 'met',
    'candidate_selected' => false,
    'application_integration_authorized' => false,
    'additional_provider_requests' => 0,
    'registers_or_docs_modified' => false,
];
previewWriteJson($output . '/registration-summary.json', $summary);

$manifestFiles = [
    'candidate-row.csv',
    'test-run-row.csv',
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
        'sha256' => previewHash($path),
    ];
}
previewWriteCsv($output . '/artifact-manifest.csv',
    ['artifact_name', 'size_bytes', 'sha256'], $manifestRows);
file_put_contents($output . '/READY.marker',
    "REGISTRATION_PREVIEW_READY_NOT_APPLIED\n"
    . "No accepted register or documentation file was changed.\n");

echo PHP_EOL . 'EXTERNAL GROUNDED REGISTRATION PREVIEW SAVED.' . PHP_EOL;
echo 'Preview folder: ' . $output . PHP_EOL;
echo 'Manifest entries: 6/6' . PHP_EOL;
echo 'No provider request, register change, documentation change, candidate selection, integration, commit, or push was performed.' . PHP_EOL;
echo 'Next permitted action: independently audit this preview, then request explicit approval before any tracked registration or documentation change.' . PHP_EOL;
