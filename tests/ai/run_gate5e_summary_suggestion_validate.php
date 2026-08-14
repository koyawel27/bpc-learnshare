<?php

declare(strict_types=1);

/** @var list<string> */
$passedChecks = [];

function gate5eFail(string $message): never
{
    fwrite(
        STDERR,
        'GATE 5E SUMMARY/SUGGESTION VALIDATION FAILED: '
        . $message
        . PHP_EOL
    );
    exit(1);
}

function gate5ePass(string $message): void
{
    global $passedChecks;
    $passedChecks[] = $message;
    echo '[PASS] ' . $message . PHP_EOL;
}

function gate5eAssert(bool $condition, string $message): void
{
    if (!$condition) {
        gate5eFail($message);
    }
    gate5ePass($message);
}

function gate5eHash(string $path): string
{
    $hash = hash_file('sha256', $path);
    if (!is_string($hash)) {
        gate5eFail('Could not hash: ' . $path);
    }
    return strtoupper($hash);
}

/**
 * @return array{headers: list<string>, rows: list<array<string, string>>}
 */
function gate5eCsv(string $path): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        gate5eFail('Could not open CSV: ' . $path);
    }

    try {
        $headers = fgetcsv($handle);
        if (!is_array($headers) || $headers === []) {
            gate5eFail('CSV header is missing: ' . $path);
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
                gate5eFail('CSV row width mismatch: ' . $path);
            }
            $row = array_combine($headers, array_map(
                static fn (mixed $value): string => (string) $value,
                $values
            ));
            if (!is_array($row)) {
                gate5eFail('CSV row could not be mapped: ' . $path);
            }
            $rows[] = $row;
        }

        return ['headers' => $headers, 'rows' => $rows];
    } finally {
        fclose($handle);
    }
}

/** @return array<string, mixed> */
function gate5eJson(string $path): array
{
    $contents = file_get_contents($path);
    if (!is_string($contents)) {
        gate5eFail('Could not read JSON: ' . $path);
    }
    if (str_starts_with($contents, "\xEF\xBB\xBF")) {
        $contents = substr($contents, 3);
    }
    try {
        $decoded = json_decode($contents, true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        gate5eFail('Invalid JSON in ' . $path . ': ' . $exception->getMessage());
    }
    if (!is_array($decoded)) {
        gate5eFail('JSON root is not an object: ' . $path);
    }
    return $decoded;
}

/** @return array<string, array<string, mixed>> */
function gate5eCases(): array
{
    return [
        'FX-PDF-001' => [
            'reference' => 'REF-SUMSUG-001',
            'version' => 'SV-FX-PDF-001-001',
            'title' => 'Database Normalization Study Guide',
            'format' => 'PDF',
            'artifact' => 'TC-EXT-FULL-001-FX-PDF-001.json',
            'artifact_hash' => '17C8F8A02D3B95C3F72984E378F920A83BC28BFB9F40CDEF72E38E6CB9F41649',
            'text_chars' => 3657,
            'text_hash' => '933de2087b731459e927777fcfbb87a3273504a5bf4c2bf16e0a3f4a580fac58',
            'blocks' => 2,
            'keywords' => ['First Normal Form', 'Third Normal Form', 'Denormalization'],
            'active_tags' => ['Database'],
            'subject' => 'Database Management Systems',
            'resource_type' => 'Study Guide',
        ],
        'FX-PDF-005' => [
            'reference' => 'REF-SUMSUG-002',
            'version' => 'SV-FX-PDF-005-001',
            'title' => 'Philippine Data Privacy Principles for Student Systems',
            'format' => 'PDF',
            'artifact' => 'TC-EXT-FULL-005-FX-PDF-005.json',
            'artifact_hash' => 'B2620C6F0C877D663E3948C2A6D0B7F31EBE8D2B545B1536A9F4C6DC1FAD85B7',
            'text_chars' => 2739,
            'text_hash' => '9b36353efd484c683f0a4a43e7b5aae97c41de03c70edc5f3c9ba27e732bcd71',
            'blocks' => 1,
            'keywords' => ['Data Minimization', 'Uploader Notice', 'Session-Scoped'],
            'active_tags' => ['Security'],
            'subject' => null,
            'resource_type' => null,
        ],
        'FX-DOCX-002' => [
            'reference' => 'REF-SUMSUG-003',
            'version' => 'SV-FX-DOCX-002-001',
            'title' => 'Functional and Nonfunctional Requirements Reviewer',
            'format' => 'DOCX',
            'artifact' => 'TC-EXT-FULL-009-FX-DOCX-002.json',
            'artifact_hash' => 'A7DA00AD9359D482500BA785BDB3B6C97631000E854009FE77D74206B2D5F15C',
            'text_chars' => 2843,
            'text_hash' => '080dc61bf18f6d560086706f9770a257531ba1876a0335e3145f6f01c964060b',
            'blocks' => 19,
            'keywords' => ['Functional Requirements', 'Nonfunctional Requirements', 'Acceptance Criteria'],
            'active_tags' => [],
            'subject' => 'Systems Analysis and Design',
            'resource_type' => 'Reviewer',
        ],
        'FX-DOCX-003' => [
            'reference' => 'REF-SUMSUG-004',
            'version' => 'SV-FX-DOCX-003-001',
            'title' => 'Input Validation and Output Escaping Notes',
            'format' => 'DOCX',
            'artifact' => 'TC-EXT-FULL-010-FX-DOCX-003.json',
            'artifact_hash' => '0018E73451A46F5FDAFC4F9EA26FE54CC183C915E89182A517BE59BD53725673',
            'text_chars' => 2675,
            'text_hash' => '27f10410a67a3b9a52a7b8cb3358b7df4168ade534bb24a4abe36299d1db92d9',
            'blocks' => 21,
            'keywords' => ['Allowlist Validation', 'Output escaping', 'Validation Does Not Replace Authorization'],
            'active_tags' => ['Security', 'Programming'],
            'subject' => 'Web Systems and Technologies',
            'resource_type' => 'Notes',
        ],
        'FX-PPTX-004' => [
            'reference' => 'REF-SUMSUG-005',
            'version' => 'SV-FX-PPTX-004-001',
            'title' => 'UI Consistency and Accessibility',
            'format' => 'PPTX',
            'artifact' => 'TC-EXT-FULL-017-FX-PPTX-004.json',
            'artifact_hash' => '5D986772DBCB1905B94D2E0E1B73907167C7123FB4F2F6DC5390D8FD25C21560',
            'text_chars' => 1332,
            'text_hash' => '0767bf3f3d1191eb11945cb00f541c24d4b1ec6241a213cb83af92bab43efe0d',
            'blocks' => 6,
            'keywords' => ['Consistency', 'Accessibility Basics', 'Accessibility Is Not Authorization'],
            'active_tags' => ['Usability'],
            'subject' => 'Web Systems and Technologies',
            'resource_type' => 'Presentation',
        ],
        'FX-PPTX-006' => [
            'reference' => 'REF-SUMSUG-006',
            'version' => 'SV-FX-PPTX-006-001',
            'title' => 'SDLC and Capstone Planning',
            'format' => 'PPTX',
            'artifact' => 'TC-EXT-FULL-019-FX-PPTX-006.json',
            'artifact_hash' => '8CFFAD156F666B7FF238D94137179CEE9F80BE5E8808EA28586184B20D8827D4',
            'text_chars' => 2026,
            'text_hash' => 'caac7a400c7b47bd60c22050e692c55913ee020032f67bc66db16a6f44cb1bb8',
            'blocks' => 6,
            'keywords' => ['Planning and Requirements', 'Build in Small Phases', 'Maintenance and Handoff'],
            'active_tags' => [],
            'subject' => 'Systems Analysis and Design',
            'resource_type' => 'Presentation',
        ],
        'FX-TXT-001' => [
            'reference' => 'REF-SUMSUG-007',
            'version' => 'SV-FX-TXT-001-001',
            'title' => 'SQL Terminology Quick Reference',
            'format' => 'TXT',
            'artifact' => 'TC-EXT-FULL-020-FX-TXT-001.json',
            'artifact_hash' => '5A9A8ADF9F50FDC122FBAF30DCD247853948515DA63D50CD83AB20220F352200',
            'text_chars' => 1828,
            'text_hash' => 'dfc9097f83bef30ee5e1b736c052848199ca0de46ab6c026349e2baf88988feb',
            'blocks' => 14,
            'keywords' => ['Primary Key', 'LEFT JOIN', 'Transaction'],
            'active_tags' => ['Database'],
            'subject' => 'Database Management Systems',
            'resource_type' => null,
        ],
        'FX-TXT-005' => [
            'reference' => 'REF-SUMSUG-008',
            'version' => 'SV-FX-TXT-005-001',
            'title' => 'Research Methods Glossary',
            'format' => 'TXT',
            'artifact' => 'TC-EXT-FULL-024-FX-TXT-005.json',
            'artifact_hash' => 'A3345EB35718A900064246DF5AB2309845E2058F8DB75499E2A71AE8651ED829',
            'text_chars' => 2109,
            'text_hash' => '9a8dfe4b30ec48ccf83ac616a34910933f07c15de069d1fdb510259920cdaeaa',
            'blocks' => 13,
            'keywords' => ['Sampling Method', 'Validity', 'Scope of Findings'],
            'active_tags' => ['Research'],
            'subject' => 'Research Methods',
            'resource_type' => null,
        ],
    ];
}

$mode = null;
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--mode=')) {
        $mode = substr($argument, strlen('--mode='));
    } else {
        gate5eFail('Unknown argument: ' . $argument);
    }
}
gate5eAssert($mode === 'validate', 'Mode is exactly offline validate');

$root = dirname(__DIR__, 2);
$relativeInputs = [
    'database/schema.sql' => '8C56089A01A1D6DED5C457AEBA26F695B372C4A95F536A77ECA507EA7F9BBEEE',
    'database/seeds/seed_demo_taxonomy.php' => '2DC86DD476977B5A155402B057147DE2ADB0609E4655BC89C2C57431F82556C1',
    'docs/ai-feasibility-spike/registers/fixtures.csv' => '648D47A2F882D2E419A6E46E1B99EC62BD373EEFFA0C44A0845D3985F4AC8080',
    'docs/ai-feasibility-spike/registers/candidates.csv' => 'A987ADA70ED1119B7A2DD3C5CD98F11FE92406B817491C9ECD840B2D16752AE9',
    'docs/ai-feasibility-spike/registers/test_runs.csv' => '8117F8200F44702965FB9290118799B895CB85B53F79992E7576908402313793',
    'docs/ai-feasibility-spike/registers/payload_manifests.csv' => 'AD9414938F1148ABF40E7D614093D8394339DFB975F9A21553C4FF68DD731CA6',
    'docs/ai-feasibility-spike/results/measurements.csv' => '01E1DBFC8596618726CF8B69C237B283A7D0F119FB78C4DD5ABDC926448D6C5C',
    'docs/AI_FEASIBILITY_SPIKE.md' => null,
    'docs/ai-feasibility-spike/ACCEPTED_CRITERIA.md' => null,
    'docs/ai-feasibility-spike/SUMMARY_SUGGESTION_CHECKPOINT.md' => null,
];

foreach ($relativeInputs as $relative => $expectedHash) {
    $path = $root . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    gate5eAssert(
        is_file($path) && is_readable($path),
        'Required input is readable: ' . $relative
    );
    if (is_string($expectedHash)) {
        gate5eAssert(
            gate5eHash($path) === $expectedHash,
            'Frozen input hash is unchanged: ' . $relative
        );
    }
}

$fixturePath = $root . DIRECTORY_SEPARATOR
    . 'docs' . DIRECTORY_SEPARATOR . 'ai-feasibility-spike'
    . DIRECTORY_SEPARATOR . 'registers' . DIRECTORY_SEPARATOR
    . 'fixtures.csv';
$fixtures = gate5eCsv($fixturePath);
gate5eAssert(count($fixtures['rows']) === 30, 'Fixture register has 30 rows');
$fixtureById = [];
foreach ($fixtures['rows'] as $fixture) {
    $id = $fixture['fixture_id'];
    gate5eAssert($id !== '' && !isset($fixtureById[$id]),
        'Fixture ID is unique: ' . $id);
    $fixtureById[$id] = $fixture;
}

$registerCounts = [
    'candidates.csv' => 17,
    'test_runs.csv' => 62,
    'payload_manifests.csv' => 6,
];
foreach ($registerCounts as $file => $expectedCount) {
    $path = dirname($fixturePath) . DIRECTORY_SEPARATOR . $file;
    gate5eAssert(
        count(gate5eCsv($path)['rows']) === $expectedCount,
        $file . ' row count remains ' . $expectedCount
    );
}
$measurementPath = $root . DIRECTORY_SEPARATOR . 'docs'
    . DIRECTORY_SEPARATOR . 'ai-feasibility-spike'
    . DIRECTORY_SEPARATOR . 'results'
    . DIRECTORY_SEPARATOR . 'measurements.csv';
gate5eAssert(
    count(gate5eCsv($measurementPath)['rows']) === 711,
    'measurements.csv row count remains 711'
);

$cases = gate5eCases();
gate5eAssert(count($cases) === 8, 'Exactly eight generation items are frozen');
$formatCounts = ['PDF' => 0, 'DOCX' => 0, 'PPTX' => 0, 'TXT' => 0];
$referenceIds = [];
$activeTagCoverage = 0;
$rawRoot = $root . DIRECTORY_SEPARATOR . '.local'
    . DIRECTORY_SEPARATOR . 'ai-feasibility-spike'
    . DIRECTORY_SEPARATOR . 'results'
    . DIRECTORY_SEPARATOR . 'extraction'
    . DIRECTORY_SEPARATOR . 'EX-LOCAL-PHP-001'
    . DIRECTORY_SEPARATOR . 'full-20260715-215350'
    . DIRECTORY_SEPARATOR . 'raw';
$planPath = $root . DIRECTORY_SEPARATOR . 'docs'
    . DIRECTORY_SEPARATOR . 'ai-feasibility-spike'
    . DIRECTORY_SEPARATOR . 'SUMMARY_SUGGESTION_CHECKPOINT.md';
$planText = file_get_contents($planPath);
if (!is_string($planText)) {
    gate5eFail('Could not read the checkpoint contract.');
}

foreach ($cases as $fixtureId => $case) {
    gate5eAssert(isset($fixtureById[$fixtureId]),
        'Selected fixture exists: ' . $fixtureId);
    $fixture = $fixtureById[$fixtureId];
    gate5eAssert(
        $fixture['source_version_id'] === $case['version'],
        'Source version matches: ' . $fixtureId
    );
    gate5eAssert(
        $fixture['title_or_test_identifier'] === $case['title'],
        'Fixture title matches: ' . $fixtureId
    );
    gate5eAssert(
        $fixture['file_type'] === $case['format'],
        'Fixture format matches: ' . $fixtureId
    );
    gate5eAssert(
        $fixture['fixture_set'] === 'primary-readable'
        && $fixture['review_status'] === 'Accepted - manually reviewed',
        'Fixture is accepted primary-readable: ' . $fixtureId
    );
    gate5eAssert(
        $fixture['contains_personal_or_sensitive_information'] === 'No'
        && $fixture['local_testing_allowed'] === 'Yes',
        'Fixture is synthetic-safe for local testing: ' . $fixtureId
    );
    $formatCounts[$case['format']]++;
    gate5eAssert(!isset($referenceIds[$case['reference']]),
        'Reference-note ID is unique: ' . $case['reference']);
    $referenceIds[$case['reference']] = true;

    $artifactPath = $rawRoot . DIRECTORY_SEPARATOR . $case['artifact'];
    gate5eAssert(is_file($artifactPath) && is_readable($artifactPath),
        'Accepted extracted input exists: ' . $case['artifact']);
    gate5eAssert(gate5eHash($artifactPath) === $case['artifact_hash'],
        'Extracted artifact hash matches: ' . $fixtureId);
    $artifact = gate5eJson($artifactPath);
    gate5eAssert(
        ($artifact['result_status'] ?? null) === 'success'
        && ($artifact['fixture_id'] ?? null) === $fixtureId
        && ($artifact['source_version_id'] ?? null) === $case['version']
        && ($artifact['file_type'] ?? null) === $case['format'],
        'Extracted identity and success state match: ' . $fixtureId
    );
    gate5eAssert(
        ($artifact['text_char_count'] ?? null) === $case['text_chars']
        && ($artifact['text_sha256'] ?? null) === $case['text_hash']
        && ($artifact['block_count'] ?? null) === $case['blocks'],
        'Extracted size, text hash, and block count match: ' . $fixtureId
    );
    $text = $artifact['full_text'] ?? null;
    gate5eAssert(is_string($text) && strlen(trim($text)) > 0,
        'Extracted text is nonblank: ' . $fixtureId);
    foreach ($case['keywords'] as $keyword) {
        gate5eAssert(str_contains($text, $keyword),
            'Reference anchor is present for ' . $fixtureId . ': ' . $keyword);
    }
    gate5eAssert(
        str_contains($planText, $case['reference'])
        && str_contains($planText, $fixtureId)
        && str_contains($planText, $case['text_hash']),
        'Contract binds reference, fixture, and text hash: ' . $fixtureId
    );
    gate5eAssert(
        str_contains($planText, '### ' . $case['reference'])
        && str_contains($planText, 'Do not invent')
        && str_contains($planText, 'Unsupported or overconfident metadata'),
        'Human-review note structure is present: ' . $case['reference']
    );
    if ($case['active_tags'] !== []) {
        $activeTagCoverage++;
    }
}

foreach ($formatCounts as $format => $count) {
    gate5eAssert($count === 2, 'Representative scope has two ' . $format . ' items');
}
gate5eAssert($activeTagCoverage === 6,
    'Six of eight resources have at least one clearly relevant Active tag');

$taxonomyPath = $root . DIRECTORY_SEPARATOR . 'database'
    . DIRECTORY_SEPARATOR . 'seeds'
    . DIRECTORY_SEPARATOR . 'seed_demo_taxonomy.php';
$taxonomyText = file_get_contents($taxonomyPath);
if (!is_string($taxonomyText)) {
    gate5eFail('Could not read the demonstration taxonomy seed.');
}
$activeTags = ['Database', 'Programming', 'Research', 'Security', 'Usability'];
foreach ($activeTags as $tag) {
    gate5eAssert(
        substr_count($taxonomyText, "'" . $tag . "'") === 1
        && str_contains($planText, '| ' . $tag . ' | Active |'),
        'Active controlled tag is seed-backed and recorded: ' . $tag
    );
}
gate5eAssert(
    str_contains($planText, 'TAG-SPIKE-INACTIVE-REQUIREMENTS')
    && str_contains($planText, 'TAG-SPIKE-INACTIVE-DATA-PRIVACY')
    && str_contains($planText, 'They are not inserted into the application database.'),
    'Inactive values are explicitly test-only and non-persistent'
);

$subjects = [
    'Database Management Systems',
    'Research Methods',
    'Systems Analysis and Design',
    'Web Systems and Technologies',
];
$resourceTypes = [
    'Handout', 'Module', 'Notes', 'Presentation', 'Reviewer', 'Study Guide',
];
foreach ($cases as $fixtureId => $case) {
    if (is_string($case['subject'])) {
        gate5eAssert(in_array($case['subject'], $subjects, true)
            && str_contains($taxonomyText, "'" . $case['subject'] . "'"),
            'Expected subject is controlled: ' . $fixtureId);
    }
    if (is_string($case['resource_type'])) {
        gate5eAssert(in_array($case['resource_type'], $resourceTypes, true)
            && str_contains($taxonomyText, "'" . $case['resource_type'] . "'"),
            'Expected resource type is controlled: ' . $fixtureId);
    }
}
foreach (['`subject`', '`resource_type`', '`topic`'] as $field) {
    gate5eAssert(str_contains($planText, $field),
        'Selected metadata field is recorded: ' . $field);
}
gate5eAssert(
    str_contains(
        $planText,
        '`course/program` and `year_level` are excluded'
    ),
    'Course/program and year level are explicitly excluded'
);

$criteriaPath = $root . DIRECTORY_SEPARATOR . 'docs'
    . DIRECTORY_SEPARATOR . 'ai-feasibility-spike'
    . DIRECTORY_SEPARATOR . 'ACCEPTED_CRITERIA.md';
$criteriaText = file_get_contents($criteriaPath);
if (!is_string($criteriaText)) {
    gate5eFail('Could not read accepted criteria.');
}
$thresholds = [
    'Summaries: at least 80% Pass.',
    'Summaries: at least 95% Pass or Needs light review/edit.',
    'Material unsupported or contradicted claims fail the affected summary.',
    'Suggested directly usable tags: at least 80% relevant and Active.',
    'Resources with clearly relevant Active tags: at least 75% receive one.',
    'Metadata suggestions: at least 80% relevant and source-supported.',
    'Summary/suggestion outputs: at least 80% usable as-is or after light editing.',
];
foreach ($thresholds as $threshold) {
    gate5eAssert(str_contains($criteriaText, $threshold),
        'Accepted threshold remains present: ' . $threshold);
}

$requiredBoundaries = [
    'does not call Ollama, GroqCloud, or another model/provider',
    'does not create generated output',
    'does not create generated output, register a test run',
    'does not create generated output, register a test run, change a taxonomy row',
    'no taxonomy mutation or direct metadata write',
    'no moderation or resource-status action',
    'does not prove that summaries or suggestions are good',
    'authorize a live request',
    'accept a candidate',
    'permit schema changes',
];
foreach ($requiredBoundaries as $boundary) {
    gate5eAssert(str_contains($planText, $boundary),
        'Non-authority boundary is recorded: ' . $boundary);
}

echo PHP_EOL;
echo 'GATE 5E SUMMARY/SUGGESTION OFFLINE VALIDATION PASSED.' . PHP_EOL;
echo 'Checks passed: ' . count($passedChecks) . PHP_EOL;
echo 'Representative items: 8 (2 PDF, 2 DOCX, 2 PPTX, 2 TXT)' . PHP_EOL;
echo 'Accepted extraction artifacts verified: 8/8' . PHP_EOL;
echo 'Human-reviewed reference notes verified: 8/8' . PHP_EOL;
echo 'Active demo tags: 5; test-only Inactive tags: 2' . PHP_EOL;
echo 'Scored metadata fields: subject, resource_type, topic' . PHP_EOL;
echo 'Generated summaries or suggestions: 0' . PHP_EOL;
echo 'Ollama/provider/network requests: 0' . PHP_EOL;
echo 'Credential reads: 0' . PHP_EOL;
echo 'Taxonomy/database/schema/register changes: 0' . PHP_EOL;
echo 'Candidate/model/provider selected: No' . PHP_EOL;
echo 'Live generation authorized: No' . PHP_EOL;
echo 'Next action: prepare a strict structured payload preview for a separately '
    . 'chosen candidate; review it before any generation request.' . PHP_EOL;
