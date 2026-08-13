<?php

declare(strict_types=1);

const EXT_PREP_PROVIDER = 'GroqCloud';
const EXT_PREP_MODEL = 'openai/gpt-oss-120b';
const EXT_PREP_CANDIDATE = 'GEN-GROQ-GPT-OSS-120B-001';
const EXT_PREP_TEST_RUN = 'TR-GEN-GROQ-GROUNDED-001';
const EXT_PREP_PROMPT_VERSION = 'GROUNDED-ATOMIC-SOURCES-v1';
const EXT_PREP_ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';
const EXT_PREP_MAX_PROMPT_TOKENS_PER_CALL = 6000;
const EXT_PREP_MAX_COMPLETION_TOKENS_PER_CALL = 400;
const EXT_PREP_SPACING_SECONDS = 65;
const EXT_PREP_INPUT_PRICE_PER_MILLION = 0.15;
const EXT_PREP_OUTPUT_PRICE_PER_MILLION = 0.60;

/** @var list<string> */
$passedChecks = [];

function extPrepFail(string $message): never
{
    fwrite(STDERR, 'GATE 5D EXTERNAL GROUNDED PREPARATION FAILED: '
        . $message . PHP_EOL);
    exit(1);
}

function extPrepPass(string $message): void
{
    global $passedChecks;
    $passedChecks[] = $message;
    echo '[PASS] ' . $message . PHP_EOL;
}

function extPrepAssert(bool $condition, string $message): void
{
    if (!$condition) {
        extPrepFail($message);
    }
    extPrepPass($message);
}

function extPrepSha256(string $path): string
{
    $hash = hash_file('sha256', $path);
    if (!is_string($hash)) {
        extPrepFail('Could not hash ' . $path);
    }
    return strtoupper($hash);
}

/**
 * @return array{headers: list<string>, rows: list<array<string, string>>}
 */
function extPrepCsv(string $path): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        extPrepFail('Could not open CSV: ' . $path);
    }

    try {
        $headers = fgetcsv($handle);
        if (!is_array($headers) || $headers === []) {
            extPrepFail('CSV header is missing: ' . $path);
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
                extPrepFail('CSV row width mismatch: ' . $path);
            }
            $row = array_combine($headers, array_map(
                static fn (mixed $value): string => (string) $value,
                $values
            ));
            if (!is_array($row)) {
                extPrepFail('CSV row could not be mapped: ' . $path);
            }
            $rows[] = $row;
        }

        return ['headers' => $headers, 'rows' => $rows];
    } finally {
        fclose($handle);
    }
}

/** @return list<array<string, mixed>> */
function extPrepJsonl(string $path): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        extPrepFail('Could not open JSONL: ' . $path);
    }
    $rows = [];
    $lineNumber = 0;

    try {
        while (($line = fgets($handle)) !== false) {
            $lineNumber++;
            if (trim($line) === '') {
                continue;
            }
            try {
                $row = json_decode($line, true, 64, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                extPrepFail('Invalid JSONL line ' . $lineNumber . ': '
                    . $exception->getMessage());
            }
            if (!is_array($row)) {
                extPrepFail('JSONL line is not an object: ' . $lineNumber);
            }
            $rows[] = $row;
        }
    } finally {
        fclose($handle);
    }

    return $rows;
}

function extPrepInstruction(): string
{
    return <<<'PROMPT'
You are the bounded repository-grounded inquiry component for a synthetic BPC LearnShare feasibility test.

Return exactly one JSON object:
{
  "outcome": "answered" | "partially_answered" | "insufficient_evidence" | "refused",
  "supported_points": [
    {"text": "one atomic evidence-supported statement", "source_labels": ["S1"]}
  ],
  "unsupported_portion": "plain text or empty string",
  "user_message": "plain text or empty string"
}

Rules:
1. Use only supplied EVIDENCE. Do not use outside knowledge or the question wording as evidence.
2. Every supported_points item must be one atomic claim and must list every supplied source label supporting it.
3. Do not put unsupported statements in supported_points.
4. For a fully answerable question, choose answered, provide supported_points, and leave unsupported_portion empty.
5. For partial support, choose partially_answered, provide only supported points, and clearly describe the unsupported request in unsupported_portion.
6. If evidence does not answer the question, choose insufficient_evidence, return no supported_points, and explain insufficiency in user_message.
7. For a graded answer key or completed graded work, choose refused, return no supported_points, and put a visible refusal plus a bounded study-help offer in user_message.
8. Leave user_message empty for answered and partially_answered outcomes. All supported academic content belongs in supported_points.
9. Never invent a title, source label, locator, link, policy, number, or technical detail.
10. Never write page, slide, paragraph, line, or heading locators.
11. Do not expose hidden reasoning. Output JSON only, with no Markdown fence.
PROMPT;
}

/** @param array<string, mixed> $case */
function extPrepUserPrompt(array $case): string
{
    $parts = [
        'QUERY ID: ' . $case['query_id'],
        'QUESTION: ' . $case['query_text'],
        '',
        'EVIDENCE:',
    ];
    $evidence = $case['supplied_evidence'];

    if ($evidence === []) {
        $parts[] = '(No repository evidence supplied.)';
    } else {
        foreach ($evidence as $source) {
            $parts[] = sprintf(
                "[%s]\nTitle: %s\nFixture: %s\nText:\n%s",
                $source['source_label'],
                $source['title'],
                $source['fixture_id'],
                $source['text']
            );
        }
    }

    return implode("\n\n", $parts);
}

/** @return array<string, mixed> */
function extPrepSchema(): array
{
    return [
        'type' => 'object',
        'properties' => [
            'outcome' => [
                'type' => 'string',
                'enum' => [
                    'answered',
                    'partially_answered',
                    'insufficient_evidence',
                    'refused',
                ],
            ],
            'supported_points' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'text' => ['type' => 'string'],
                        'source_labels' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                    ],
                    'required' => ['text', 'source_labels'],
                    'additionalProperties' => false,
                ],
            ],
            'unsupported_portion' => ['type' => 'string'],
            'user_message' => ['type' => 'string'],
        ],
        'required' => [
            'outcome',
            'supported_points',
            'unsupported_portion',
            'user_message',
        ],
        'additionalProperties' => false,
    ];
}

/**
 * @param array<string, mixed> $case
 * @return array<string, mixed>
 */
function extPrepRequest(array $case): array
{
    return [
        'model' => EXT_PREP_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => extPrepInstruction()],
            ['role' => 'user', 'content' => extPrepUserPrompt($case)],
        ],
        'temperature' => 0,
        'reasoning_effort' => 'low',
        'max_completion_tokens' => EXT_PREP_MAX_COMPLETION_TOKENS_PER_CALL,
        'stream' => false,
        'response_format' => [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'grounded_academic_response',
                'strict' => true,
                'schema' => extPrepSchema(),
            ],
        ],
    ];
}

/** @param list<string> $values */
function extPrepUnique(array $values): array
{
    $seen = [];
    $result = [];
    foreach ($values as $value) {
        if (!isset($seen[$value])) {
            $seen[$value] = true;
            $result[] = $value;
        }
    }
    return $result;
}

/** @param array<string, string> $row */
function extPrepCsvLine(array $row, array $headers): string
{
    $handle = fopen('php://temp', 'w+b');
    if ($handle === false) {
        extPrepFail('Could not create temporary CSV buffer.');
    }
    try {
        $values = [];
        foreach ($headers as $header) {
            $values[] = $row[$header] ?? '';
        }
        fputcsv($handle, $values);
        rewind($handle);
        $line = stream_get_contents($handle);
        if (!is_string($line)) {
            extPrepFail('Could not read temporary CSV buffer.');
        }
        return $line;
    } finally {
        fclose($handle);
    }
}

/** @param list<array<string, string>> $rows */
function extPrepCsvText(array $headers, array $rows): string
{
    $text = extPrepCsvLine(array_combine($headers, $headers), $headers);
    foreach ($rows as $row) {
        $text .= extPrepCsvLine($row, $headers);
    }
    return $text;
}

/** @param array<string, mixed> $value */
function extPrepJson(array $value): string
{
    return json_encode(
        $value,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ) . PHP_EOL;
}

/**
 * @return array{
 *   cases: list<array<string, mixed>>,
 *   requests: list<array<string, mixed>>,
 *   request_index: list<array<string, string>>,
 *   manifest_rows: list<array<string, string>>,
 *   summary: array<string, mixed>,
 *   review_markdown: string
 * }
 */
function extPrepBuild(string $root): array
{
    $separator = DIRECTORY_SEPARATOR;
    $registerBase = $root . $separator . 'docs' . $separator
        . 'ai-feasibility-spike' . $separator . 'registers' . $separator;
    $sourceBase = $root . $separator . '.local' . $separator
        . 'ai-feasibility-spike' . $separator . 'results' . $separator
        . 'grounded-inquiry' . $separator;
    $sources = [
        [
            'path' => $sourceBase
                . 'GEN-GROUNDED-OLLAMA-QWEN35-4B-CLAIMS-004'
                . $separator . 'run-20260802-053419Z' . $separator
                . 'case-payloads.jsonl',
            'sha256' => '33B8499C3F0931B9A1B63ECED7CCCC5881D483EBD3B19E78ED50F7501FC404DF',
        ],
        [
            'path' => $sourceBase
                . 'GEN-GROUNDED-OLLAMA-QWEN35-4B-DIVERSE-005'
                . $separator . 'run-20260802-053900Z' . $separator
                . 'case-payloads.jsonl',
            'sha256' => '5BAB3BD26E1DF41240302F92CD597FCB2563DB99131842394469C1C20830D5C3',
        ],
    ];
    $registers = [
        'fixtures' => [
            'path' => $registerBase . 'fixtures.csv',
            'sha256' => '648D47A2F882D2E419A6E46E1B99EC62BD373EEFFA0C44A0845D3985F4AC8080',
        ],
        'queries' => [
            'path' => $registerBase . 'queries.csv',
            'sha256' => '7D212A05932FEC0803F81754100A4523BC191FAF97BE19CA055504530D2CF5BE',
        ],
        'expected_evidence' => [
            'path' => $registerBase . 'expected_evidence.csv',
            'sha256' => 'B57A07AED948F0DA7BBF59954AC4046EFFA4B8853EA16B67AC554C77D900DACA',
        ],
        'payload_manifests' => [
            'path' => $registerBase . 'payload_manifests.csv',
            'sha256' => 'F90FB0AD7A467A1C8C4F4EEE135AC86534A99C5517A387A20BD795030FE18275',
        ],
    ];

    foreach ($sources as $source) {
        extPrepAssert(is_file($source['path']), 'Saved local payload source exists');
        extPrepAssert(
            extPrepSha256($source['path']) === $source['sha256'],
            'Saved local payload source hash matches'
        );
    }
    foreach ($registers as $name => $register) {
        extPrepAssert(is_file($register['path']), $name . ' register exists');
        extPrepAssert(
            extPrepSha256($register['path']) === $register['sha256'],
            $name . ' register hash matches'
        );
    }

    $fixtures = extPrepCsv($registers['fixtures']['path']);
    $queries = extPrepCsv($registers['queries']['path']);
    $payloadManifests = extPrepCsv($registers['payload_manifests']['path']);
    extPrepAssert($payloadManifests['rows'] === [], 'Accepted payload register is empty');

    $fixtureById = [];
    foreach ($fixtures['rows'] as $fixture) {
        $fixtureById[$fixture['fixture_id']] = $fixture;
    }
    $queryById = [];
    foreach ($queries['rows'] as $query) {
        $queryById[$query['query_id']] = $query;
    }

    $cases = [];
    foreach ($sources as $source) {
        foreach (extPrepJsonl($source['path']) as $case) {
            $cases[] = $case;
        }
    }
    $expected = [
        'Q-INQ-001' => 'answered',
        'Q-SEM-004' => 'answered',
        'Q-NOEVID-001' => 'insufficient_evidence',
        'Q-PROHIB-001' => 'refused',
        'Q-MULTI-001' => 'answered',
        'Q-PART-005' => 'partially_answered',
    ];
    extPrepAssert(count($cases) === 6, 'Exactly six comparison cases are loaded');
    extPrepAssert(
        array_column($cases, 'query_id') === array_keys($expected),
        'Case order and query IDs match the accepted six-case comparison'
    );

    $manifestHeaders = $payloadManifests['headers'];
    $requestIndex = [];
    $manifestRows = [];
    $requests = [];
    $totalRequestBytes = 0;
    $totalEvidenceInstances = 0;
    $allFixtureIds = [];
    $allChunkIds = [];
    $review = [
        '# External Grounded Comparison Payload Review',
        '',
        '**Status:** Ready for human review; not authorized for transmission',
        '**Provider/model:** ' . EXT_PREP_PROVIDER . ' ' . EXT_PREP_MODEL,
        '**Requests:** 6 maximum; zero retries',
        '',
        'This ignored local worksheet shows the exact messages proposed for',
        'the six-call comparison. It is not provider evidence and does not',
        'authorize a live run.',
        '',
        '## Exact system message used for every case',
        '',
        '```text',
        extPrepInstruction(),
        '```',
        '',
    ];

    foreach ($cases as $index => $case) {
        $queryId = (string) $case['query_id'];
        extPrepAssert(isset($queryById[$queryId]), 'Query exists: ' . $queryId);
        extPrepAssert(
            $case['query_text'] === $queryById[$queryId]['query_text'],
            'Saved query text matches current register: ' . $queryId
        );
        extPrepAssert(
            $queryById[$queryId]['review_status'] === 'Accepted - manually reviewed',
            'Query remains accepted: ' . $queryId
        );

        $evidence = $case['supplied_evidence'];
        extPrepAssert(is_array($evidence), 'Evidence list exists: ' . $queryId);
        if ($queryId === 'Q-PROHIB-001') {
            extPrepAssert($evidence === [], 'Prohibited case has no corpus evidence');
        } else {
            extPrepAssert(count($evidence) >= 4 && count($evidence) <= 5,
                'Evidence set remains bounded for ' . $queryId);
        }

        $fixtureIds = [];
        $chunkIds = [];
        $expectedLabelNumber = 1;
        foreach ($evidence as $source) {
            $fixtureId = (string) $source['fixture_id'];
            $chunkId = (string) $source['chunk_id'];
            extPrepAssert(isset($fixtureById[$fixtureId]),
                'Evidence fixture is registered: ' . $fixtureId);
            $fixture = $fixtureById[$fixtureId];
            extPrepAssert($fixture['fixture_set'] === 'primary-readable',
                'Evidence fixture is primary-readable: ' . $fixtureId);
            extPrepAssert(
                $fixture['review_status'] === 'Accepted - manually reviewed',
                'Evidence fixture remains accepted: ' . $fixtureId
            );
            extPrepAssert(
                $fixture['contains_personal_or_sensitive_information'] === 'No',
                'Evidence fixture declares no personal/sensitive data: '
                . $fixtureId
            );
            extPrepAssert(
                str_starts_with(
                    $fixture['external_transmission_allowed'],
                    'Yes, only when intentionally approved'
                ),
                'Evidence fixture requires intentional selected-test approval: '
                . $fixtureId
            );
            extPrepAssert(
                $source['source_label'] === 'S' . $expectedLabelNumber,
                'Source labels are contiguous for ' . $queryId
            );
            $normalizedText = str_replace(["\r\n", "\r"], "\n", (string) $source['text']);
            extPrepAssert(
                hash('sha256', $normalizedText) === strtolower((string) $source['text_sha256']),
                'Evidence text hash matches for ' . $chunkId
            );
            $fixtureIds[] = $fixtureId;
            $chunkIds[] = $chunkId;
            $allFixtureIds[] = $fixtureId;
            $allChunkIds[] = $chunkId;
            $expectedLabelNumber++;
        }

        $request = extPrepRequest($case);
        extPrepAssert(!array_key_exists('tools', $request),
            'No tools are enabled for ' . $queryId);
        extPrepAssert($request['stream'] === false,
            'Streaming is disabled for ' . $queryId);
        extPrepAssert(
            $request['response_format']['json_schema']['strict'] === true,
            'Strict JSON Schema is required for ' . $queryId
        );
        $requestJson = json_encode(
            $request,
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        $requestBytes = strlen($requestJson);
        extPrepAssert($requestBytes < 24000,
            'Request body remains below 24,000 bytes for ' . $queryId);
        $requestHash = strtoupper(hash('sha256', $requestJson));
        $manifestId = sprintf('PM-GROQ-GROUNDED-001-%02d', $index + 1);
        $uniqueFixtureIds = extPrepUnique($fixtureIds);
        $uniqueChunkIds = extPrepUnique($chunkIds);

        $requests[] = $request;
        $requestIndex[] = [
            'request_sequence' => (string) ($index + 1),
            'payload_manifest_id' => $manifestId,
            'query_id' => $queryId,
            'expected_model_outcome' => $expected[$queryId],
            'fixture_ids' => implode(';', $uniqueFixtureIds),
            'evidence_passage_ids' => implode(';', $uniqueChunkIds),
            'resource_count' => (string) count($uniqueFixtureIds),
            'evidence_count' => (string) count($evidence),
            'request_body_bytes' => (string) $requestBytes,
            'request_body_sha256' => $requestHash,
            'max_prompt_tokens_planning_ceiling' =>
                (string) EXT_PREP_MAX_PROMPT_TOKENS_PER_CALL,
            'max_completion_tokens' =>
                (string) EXT_PREP_MAX_COMPLETION_TOKENS_PER_CALL,
        ];
        $manifestRows[] = [
            'payload_manifest_id' => $manifestId,
            'test_run_id' => EXT_PREP_TEST_RUN,
            'provider_or_model_candidate' => EXT_PREP_CANDIDATE,
            'fixture_ids' => implode(';', $uniqueFixtureIds),
            'evidence_passage_ids' => implode(';', $uniqueChunkIds),
            'resource_count' => (string) count($uniqueFixtureIds),
            'evidence_count' => (string) count($evidence),
            'included_data_categories' =>
                'Synthetic query text; project-created synthetic academic '
                . 'passages; synthetic titles; query/fixture/source labels',
            'source_identifiers_included' =>
                'Yes - query ID, fixture IDs, and bounded S-labels; chunk IDs '
                . 'remain only in the local manifest',
            'locator_information_included' => 'No',
            'approximate_size' => (string) $requestBytes,
            'approximate_size_unit' => 'bytes (exact serialized request body)',
            'excluded_data_categories' =>
                'Boundary fixtures; accounts; sessions; uploader/personal data; '
                . 'credentials; database IDs; filenames; paths; locators; links; '
                . 'real institutional/student content; unrelated repository text',
            'personal_or_account_linked_information_included' => 'No',
            'justification' =>
                'Same fixed case and evidence used in the accepted local grounded comparison',
            'external_transmission_authorization_basis' =>
                'PREVIEW ONLY - fixture register permits intentional selected-test '
                . 'transmission; exact live run requires separate explicit approval',
            'redacted_sample_path' =>
                '.local/ai-feasibility-spike/results/external-grounded-preview/'
                . EXT_PREP_CANDIDATE . '/payload-review-v1/PAYLOAD_REVIEW.md',
            'reviewer' => 'Project team',
            'notes' => 'Query ' . $queryId
                . '; preview row only; not added to accepted register',
        ];
        $totalRequestBytes += $requestBytes;
        $totalEvidenceInstances += count($evidence);

        $review[] = '## ' . ($index + 1) . '. ' . $queryId;
        $review[] = '';
        $review[] = '- Payload manifest: `' . $manifestId . '`';
        $review[] = '- Expected model outcome: `' . $expected[$queryId] . '`';
        $review[] = '- Fixtures: `' . (implode(';', $uniqueFixtureIds) ?: 'none') . '`';
        $review[] = '- Evidence passages: `' . (implode(';', $uniqueChunkIds) ?: 'none') . '`';
        $review[] = '- Exact request-body bytes: `' . $requestBytes . '`';
        $review[] = '- Request-body SHA-256: `' . $requestHash . '`';
        $review[] = '';
        $review[] = '### Exact user message';
        $review[] = '';
        $review[] = '```text';
        $review[] = extPrepUserPrompt($case);
        $review[] = '```';
        $review[] = '';
    }

    $allUniqueFixtures = extPrepUnique($allFixtureIds);
    $allUniqueChunks = extPrepUnique($allChunkIds);
    $maxPromptTokens = count($cases) * EXT_PREP_MAX_PROMPT_TOKENS_PER_CALL;
    $maxCompletionTokens = count($cases)
        * EXT_PREP_MAX_COMPLETION_TOKENS_PER_CALL;
    $worstCost = ($maxPromptTokens / 1_000_000)
        * EXT_PREP_INPUT_PRICE_PER_MILLION
        + ($maxCompletionTokens / 1_000_000)
        * EXT_PREP_OUTPUT_PRICE_PER_MILLION;
    $summary = [
        'status' => 'ready_for_human_review_not_authorized',
        'provider' => EXT_PREP_PROVIDER,
        'model' => EXT_PREP_MODEL,
        'candidate_id' => EXT_PREP_CANDIDATE,
        'reserved_test_run_id' => EXT_PREP_TEST_RUN,
        'endpoint' => EXT_PREP_ENDPOINT,
        'prompt_version' => EXT_PREP_PROMPT_VERSION,
        'case_count' => count($cases),
        'query_ids' => array_keys($expected),
        'unique_fixture_count' => count($allUniqueFixtures),
        'unique_fixture_ids' => $allUniqueFixtures,
        'unique_evidence_passage_count' => count($allUniqueChunks),
        'unique_evidence_passage_ids' => $allUniqueChunks,
        'evidence_transmission_instances' => $totalEvidenceInstances,
        'total_serialized_request_bytes' => $totalRequestBytes,
        'maximum_requests' => 6,
        'automatic_retries' => 0,
        'minimum_spacing_seconds' => EXT_PREP_SPACING_SECONDS,
        'maximum_prompt_tokens_per_call_planning_ceiling' =>
            EXT_PREP_MAX_PROMPT_TOKENS_PER_CALL,
        'maximum_completion_tokens_per_call' =>
            EXT_PREP_MAX_COMPLETION_TOKENS_PER_CALL,
        'maximum_planned_prompt_tokens' => $maxPromptTokens,
        'maximum_planned_completion_tokens' => $maxCompletionTokens,
        'maximum_planned_total_tokens' =>
            $maxPromptTokens + $maxCompletionTokens,
        'published_price_usd_per_million_input_tokens' =>
            EXT_PREP_INPUT_PRICE_PER_MILLION,
        'published_price_usd_per_million_output_tokens' =>
            EXT_PREP_OUTPUT_PRICE_PER_MILLION,
        'worst_case_published_cost_usd' => round($worstCost, 8),
        'network_requests_made' => 0,
        'payload_register_rows_created' => 0,
        'live_run_authorized' => false,
        'candidate_selected' => false,
    ];

    return [
        'cases' => $cases,
        'requests' => $requests,
        'request_index' => $requestIndex,
        'manifest_rows' => $manifestRows,
        'manifest_headers' => $manifestHeaders,
        'summary' => $summary,
        'review_markdown' => implode(PHP_EOL, $review) . PHP_EOL,
    ];
}

/** @param array<string, string> $files */
function extPrepArtifactManifest(array $files): string
{
    $rows = [];
    foreach ($files as $name => $contents) {
        $rows[] = [
            'artifact' => $name,
            'bytes' => (string) strlen($contents),
            'sha256' => strtoupper(hash('sha256', $contents)),
        ];
    }
    return extPrepCsvText(['artifact', 'bytes', 'sha256'], $rows);
}

$mode = null;
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--mode=')) {
        $mode = substr($argument, strlen('--mode='));
    } else {
        extPrepFail('Unknown argument: ' . $argument);
    }
}
extPrepAssert(in_array($mode, ['validate', 'apply'], true),
    'Mode is validate or apply');

$root = dirname(__DIR__, 2);
$prepared = extPrepBuild($root);
$requestLines = [];
foreach ($prepared['requests'] as $request) {
    $requestLines[] = json_encode(
        $request,
        JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
}
$indexHeaders = [
    'request_sequence',
    'payload_manifest_id',
    'query_id',
    'expected_model_outcome',
    'fixture_ids',
    'evidence_passage_ids',
    'resource_count',
    'evidence_count',
    'request_body_bytes',
    'request_body_sha256',
    'max_prompt_tokens_planning_ceiling',
    'max_completion_tokens',
];
$files = [
    'provider-requests.jsonl' => implode(PHP_EOL, $requestLines) . PHP_EOL,
    'request-index.csv' => extPrepCsvText(
        $indexHeaders,
        $prepared['request_index']
    ),
    'payload-manifest-preview.csv' => extPrepCsvText(
        $prepared['manifest_headers'],
        $prepared['manifest_rows']
    ),
    'PAYLOAD_REVIEW.md' => $prepared['review_markdown'],
    'preview-summary.json' => extPrepJson($prepared['summary']),
];

if ($mode === 'validate') {
    echo PHP_EOL;
    echo 'GATE 5D EXTERNAL GROUNDED PAYLOAD OFFLINE VALIDATION PASSED.' . PHP_EOL;
    echo 'Checks passed: ' . count($passedChecks) . PHP_EOL;
    echo 'Cases: 6' . PHP_EOL;
    echo 'Provider requests made: 0' . PHP_EOL;
    echo 'Accepted payload-register rows created: 0' . PHP_EOL;
    echo 'Maximum future requests: 6 with zero retries' . PHP_EOL;
    echo 'Maximum planned total tokens: 38,400' . PHP_EOL;
    echo 'Worst-case published paid cost: USD 0.00684' . PHP_EOL;
    echo 'Live grounded run authorized: No' . PHP_EOL;
    echo 'Next action: run --mode=apply to create the ignored local review '
        . 'packet; this still makes zero network requests.' . PHP_EOL;
    exit(0);
}

$output = $root . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR
    . 'ai-feasibility-spike' . DIRECTORY_SEPARATOR . 'results'
    . DIRECTORY_SEPARATOR . 'external-grounded-preview'
    . DIRECTORY_SEPARATOR . EXT_PREP_CANDIDATE
    . DIRECTORY_SEPARATOR . 'payload-review-v1';
$parent = dirname($output);
extPrepAssert(!file_exists($output), 'Final review-packet folder does not exist');
if (!is_dir($parent) && !mkdir($parent, 0775, true) && !is_dir($parent)) {
    extPrepFail('Could not create review-packet parent folder.');
}
$partial = $parent . DIRECTORY_SEPARATOR . '.partial-' . getmypid();
if (!mkdir($partial, 0775, false)) {
    extPrepFail('Could not create partial review-packet folder.');
}

foreach ($files as $name => $contents) {
    if (file_put_contents($partial . DIRECTORY_SEPARATOR . $name, $contents)
        !== strlen($contents)) {
        extPrepFail('Could not write ' . $name);
    }
}
$manifest = extPrepArtifactManifest($files);
file_put_contents($partial . DIRECTORY_SEPARATOR . 'artifact-manifest.csv', $manifest);
$marker = "READY_FOR_HUMAN_REVIEW\nNETWORK_REQUESTS=0\nLIVE_RUN_AUTHORIZED=NO\n";
file_put_contents($partial . DIRECTORY_SEPARATOR . 'READY_FOR_REVIEW.marker', $marker);

if (!rename($partial, $output)) {
    extPrepFail('Could not finalize the review-packet folder.');
}

echo PHP_EOL;
echo 'GATE 5D EXTERNAL GROUNDED PAYLOAD REVIEW PACKET SAVED.' . PHP_EOL;
echo 'Folder: ' . $output . PHP_EOL;
echo 'Files: 7' . PHP_EOL;
echo 'Cases: 6' . PHP_EOL;
echo 'Provider requests made: 0' . PHP_EOL;
echo 'Accepted payload-register rows created: 0' . PHP_EOL;
echo 'Live grounded run authorized: No' . PHP_EOL;
echo 'Next action: independently audit the local packet and review every '
    . 'payload before requesting live-run approval.' . PHP_EOL;
