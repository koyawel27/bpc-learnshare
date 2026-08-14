<?php

declare(strict_types=1);

const SUMSUG_PROVIDER = 'GroqCloud';
const SUMSUG_CANDIDATE = 'GEN-GROQ-GPT-OSS-120B-001';
const SUMSUG_MODEL = 'openai/gpt-oss-120b';
const SUMSUG_TEMPLATE = 'SUMMARY-SUGGESTION-v1';
const SUMSUG_ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';
const SUMSUG_MAX_COMPLETION_TOKENS = 700;
const SUMSUG_MAX_REQUESTS = 8;
const SUMSUG_RETRIES = 0;
const SUMSUG_SPACING_SECONDS = 65;
const SUMSUG_INPUT_PRICE_PER_MILLION = 0.15;
const SUMSUG_OUTPUT_PRICE_PER_MILLION = 0.60;

/** @var list<string> */
$passedChecks = [];

function sumsugFail(string $message): never
{
    fwrite(STDERR, 'GATE 5E PAYLOAD PREPARATION FAILED: ' . $message . PHP_EOL);
    exit(1);
}

function sumsugPass(string $message): void
{
    global $passedChecks;
    $passedChecks[] = $message;
    echo '[PASS] ' . $message . PHP_EOL;
}

function sumsugAssert(bool $condition, string $message): void
{
    if (!$condition) {
        sumsugFail($message);
    }
    sumsugPass($message);
}

function sumsugHash(string $path): string
{
    $hash = hash_file('sha256', $path);
    if (!is_string($hash)) {
        sumsugFail('Could not hash ' . $path);
    }
    return strtoupper($hash);
}

/**
 * @return array{headers: list<string>, rows: list<array<string, string>>}
 */
function sumsugCsv(string $path): array
{
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        sumsugFail('Could not open CSV: ' . $path);
    }
    try {
        $headers = fgetcsv($handle);
        if (!is_array($headers) || $headers === []) {
            sumsugFail('CSV header is missing: ' . $path);
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
                sumsugFail('CSV row width mismatch: ' . $path);
            }
            $row = array_combine($headers, array_map(
                static fn (mixed $value): string => (string) $value,
                $values
            ));
            if (!is_array($row)) {
                sumsugFail('CSV row could not be mapped: ' . $path);
            }
            $rows[] = $row;
        }
        return ['headers' => $headers, 'rows' => $rows];
    } finally {
        fclose($handle);
    }
}

/** @return array<string, mixed> */
function sumsugJsonFile(string $path): array
{
    $contents = file_get_contents($path);
    if (!is_string($contents)) {
        sumsugFail('Could not read JSON: ' . $path);
    }
    if (str_starts_with($contents, "\xEF\xBB\xBF")) {
        $contents = substr($contents, 3);
    }
    try {
        $decoded = json_decode($contents, true, 64, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        sumsugFail('Invalid JSON in ' . $path . ': ' . $exception->getMessage());
    }
    if (!is_array($decoded)) {
        sumsugFail('JSON root is not an object: ' . $path);
    }
    return $decoded;
}

/** @param list<string> $headers @param list<array<string, string>> $rows */
function sumsugCsvText(array $headers, array $rows): string
{
    $stream = fopen('php://temp', 'w+b');
    if ($stream === false) {
        sumsugFail('Could not open temporary CSV stream.');
    }
    fputcsv($stream, $headers);
    foreach ($rows as $row) {
        fputcsv($stream, array_map(
            static fn (string $header): string => $row[$header] ?? '',
            $headers
        ));
    }
    rewind($stream);
    $contents = stream_get_contents($stream);
    fclose($stream);
    if (!is_string($contents)) {
        sumsugFail('Could not build CSV text.');
    }
    return $contents;
}

/** @param array<string, mixed> $value */
function sumsugJsonText(array $value): string
{
    return json_encode(
        $value,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    ) . PHP_EOL;
}

/** @return array<string, array<string, string|int>> */
function sumsugCases(): array
{
    return [
        'FX-PDF-001' => [
            'reference' => 'REF-SUMSUG-001',
            'version' => 'SV-FX-PDF-001-001',
            'title' => 'Database Normalization Study Guide',
            'format' => 'PDF',
            'artifact' => 'TC-EXT-FULL-001-FX-PDF-001.json',
            'artifact_hash' => '17C8F8A02D3B95C3F72984E378F920A83BC28BFB9F40CDEF72E38E6CB9F41649',
            'text_hash' => '933de2087b731459e927777fcfbb87a3273504a5bf4c2bf16e0a3f4a580fac58',
            'chars' => 3657,
        ],
        'FX-PDF-005' => [
            'reference' => 'REF-SUMSUG-002',
            'version' => 'SV-FX-PDF-005-001',
            'title' => 'Philippine Data Privacy Principles for Student Systems',
            'format' => 'PDF',
            'artifact' => 'TC-EXT-FULL-005-FX-PDF-005.json',
            'artifact_hash' => 'B2620C6F0C877D663E3948C2A6D0B7F31EBE8D2B545B1536A9F4C6DC1FAD85B7',
            'text_hash' => '9b36353efd484c683f0a4a43e7b5aae97c41de03c70edc5f3c9ba27e732bcd71',
            'chars' => 2739,
        ],
        'FX-DOCX-002' => [
            'reference' => 'REF-SUMSUG-003',
            'version' => 'SV-FX-DOCX-002-001',
            'title' => 'Functional and Nonfunctional Requirements Reviewer',
            'format' => 'DOCX',
            'artifact' => 'TC-EXT-FULL-009-FX-DOCX-002.json',
            'artifact_hash' => 'A7DA00AD9359D482500BA785BDB3B6C97631000E854009FE77D74206B2D5F15C',
            'text_hash' => '080dc61bf18f6d560086706f9770a257531ba1876a0335e3145f6f01c964060b',
            'chars' => 2843,
        ],
        'FX-DOCX-003' => [
            'reference' => 'REF-SUMSUG-004',
            'version' => 'SV-FX-DOCX-003-001',
            'title' => 'Input Validation and Output Escaping Notes',
            'format' => 'DOCX',
            'artifact' => 'TC-EXT-FULL-010-FX-DOCX-003.json',
            'artifact_hash' => '0018E73451A46F5FDAFC4F9EA26FE54CC183C915E89182A517BE59BD53725673',
            'text_hash' => '27f10410a67a3b9a52a7b8cb3358b7df4168ade534bb24a4abe36299d1db92d9',
            'chars' => 2675,
        ],
        'FX-PPTX-004' => [
            'reference' => 'REF-SUMSUG-005',
            'version' => 'SV-FX-PPTX-004-001',
            'title' => 'UI Consistency and Accessibility',
            'format' => 'PPTX',
            'artifact' => 'TC-EXT-FULL-017-FX-PPTX-004.json',
            'artifact_hash' => '5D986772DBCB1905B94D2E0E1B73907167C7123FB4F2F6DC5390D8FD25C21560',
            'text_hash' => '0767bf3f3d1191eb11945cb00f541c24d4b1ec6241a213cb83af92bab43efe0d',
            'chars' => 1332,
        ],
        'FX-PPTX-006' => [
            'reference' => 'REF-SUMSUG-006',
            'version' => 'SV-FX-PPTX-006-001',
            'title' => 'SDLC and Capstone Planning',
            'format' => 'PPTX',
            'artifact' => 'TC-EXT-FULL-019-FX-PPTX-006.json',
            'artifact_hash' => '8CFFAD156F666B7FF238D94137179CEE9F80BE5E8808EA28586184B20D8827D4',
            'text_hash' => 'caac7a400c7b47bd60c22050e692c55913ee020032f67bc66db16a6f44cb1bb8',
            'chars' => 2026,
        ],
        'FX-TXT-001' => [
            'reference' => 'REF-SUMSUG-007',
            'version' => 'SV-FX-TXT-001-001',
            'title' => 'SQL Terminology Quick Reference',
            'format' => 'TXT',
            'artifact' => 'TC-EXT-FULL-020-FX-TXT-001.json',
            'artifact_hash' => '5A9A8ADF9F50FDC122FBAF30DCD247853948515DA63D50CD83AB20220F352200',
            'text_hash' => 'dfc9097f83bef30ee5e1b736c052848199ca0de46ab6c026349e2baf88988feb',
            'chars' => 1828,
        ],
        'FX-TXT-005' => [
            'reference' => 'REF-SUMSUG-008',
            'version' => 'SV-FX-TXT-005-001',
            'title' => 'Research Methods Glossary',
            'format' => 'TXT',
            'artifact' => 'TC-EXT-FULL-024-FX-TXT-005.json',
            'artifact_hash' => 'A3345EB35718A900064246DF5AB2309845E2058F8DB75499E2A71AE8651ED829',
            'text_hash' => '9a8dfe4b30ec48ccf83ac616a34910933f07c15de069d1fdb510259920cdaeaa',
            'chars' => 2109,
        ],
    ];
}

function sumsugInstruction(): string
{
    return <<<'PROMPT'
You are evaluating one synthetic academic resource for the BPC LearnShare feasibility spike.

Use only DOCUMENT TEXT. Do not use outside knowledge. Return exactly one JSON object matching the supplied schema.

SUMMARY RULES
1. Write a concise, readable, non-authoritative summary of at most 120 words.
2. Cover the resource's important scope without inventing facts, requirements, policies, results, people, institutions, numbers, or conclusions.
3. Preserve meaningful uncertainty or limits in the document. Do not claim academic, legal, institutional, moderation, or production approval.

TAG RULES
4. controlled_tag_ids may contain only supplied Active tag IDs that are clearly relevant. Return an empty array when none is clearly relevant.
5. Never select an Inactive tag ID. Never create, rename, activate, or assign a taxonomy value.
6. If a useful descriptive term is absent from the controlled vocabulary, place it only in unmapped_tag_terms. It remains unassignable review information.

METADATA RULES
7. Evaluate only subject, resource_type, and topic. Never infer course/program or year level.
8. subject and resource_type must use supplied Active values or status not_reliably_inferable with an empty value.
9. topic may be a concise source-supported description. If the document does not support one, use not_reliably_inferable with an empty value.
10. Each basis must be brief and must refer only to document content. Do not expose hidden reasoning.

AUTHORITY RULES
11. Do not approve, publish, reject, validate, moderate, classify as institutionally correct, or change the status of the resource.
12. Do not include credentials, paths, filenames, links, locators, or unrelated content.
PROMPT;
}

/** @return array<string, mixed> */
function sumsugSchema(): array
{
    $tagIds = [
        'TAG-DEMO-DATABASE',
        'TAG-DEMO-PROGRAMMING',
        'TAG-DEMO-RESEARCH',
        'TAG-DEMO-SECURITY',
        'TAG-DEMO-USABILITY',
        'TAG-SPIKE-INACTIVE-REQUIREMENTS',
        'TAG-SPIKE-INACTIVE-DATA-PRIVACY',
    ];
    $subjects = [
        '',
        'Database Management Systems',
        'Research Methods',
        'Systems Analysis and Design',
        'Web Systems and Technologies',
    ];
    $types = [
        '', 'Handout', 'Module', 'Notes', 'Presentation', 'Reviewer', 'Study Guide',
    ];
    $controlledField = static fn (array $values): array => [
        'type' => 'object',
        'properties' => [
            'status' => [
                'type' => 'string',
                'enum' => ['suggested', 'not_reliably_inferable'],
            ],
            'value' => ['type' => 'string', 'enum' => $values],
            'basis' => ['type' => 'string', 'maxLength' => 240],
        ],
        'required' => ['status', 'value', 'basis'],
        'additionalProperties' => false,
    ];

    return [
        'type' => 'object',
        'properties' => [
            'summary' => [
                'type' => 'object',
                'properties' => [
                    'text' => ['type' => 'string', 'maxLength' => 1200],
                ],
                'required' => ['text'],
                'additionalProperties' => false,
            ],
            'controlled_tag_ids' => [
                'type' => 'array',
                'items' => ['type' => 'string', 'enum' => $tagIds],
                'maxItems' => 3,
                'uniqueItems' => true,
            ],
            'unmapped_tag_terms' => [
                'type' => 'array',
                'items' => ['type' => 'string', 'maxLength' => 80],
                'maxItems' => 3,
                'uniqueItems' => true,
            ],
            'metadata' => [
                'type' => 'object',
                'properties' => [
                    'subject' => $controlledField($subjects),
                    'resource_type' => $controlledField($types),
                    'topic' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => [
                                'type' => 'string',
                                'enum' => ['suggested', 'not_reliably_inferable'],
                            ],
                            'value' => ['type' => 'string', 'maxLength' => 160],
                            'basis' => ['type' => 'string', 'maxLength' => 240],
                        ],
                        'required' => ['status', 'value', 'basis'],
                        'additionalProperties' => false,
                    ],
                ],
                'required' => ['subject', 'resource_type', 'topic'],
                'additionalProperties' => false,
            ],
            'caveats' => [
                'type' => 'array',
                'items' => ['type' => 'string', 'maxLength' => 180],
                'maxItems' => 3,
                'uniqueItems' => true,
            ],
        ],
        'required' => [
            'summary',
            'controlled_tag_ids',
            'unmapped_tag_terms',
            'metadata',
            'caveats',
        ],
        'additionalProperties' => false,
    ];
}

function sumsugUserPrompt(
    string $fixtureId,
    array $case,
    string $documentText
): string {
    return implode("\n", [
        'FIXTURE ID: ' . $fixtureId,
        'SOURCE VERSION: ' . $case['version'],
        'RESOURCE TITLE: ' . $case['title'],
        'FILE TYPE: ' . $case['format'],
        '',
        'CONTROLLED TAG FIXTURE:',
        '- TAG-DEMO-DATABASE | Database | Active',
        '- TAG-DEMO-PROGRAMMING | Programming | Active',
        '- TAG-DEMO-RESEARCH | Research | Active',
        '- TAG-DEMO-SECURITY | Security | Active',
        '- TAG-DEMO-USABILITY | Usability | Active',
        '- TAG-SPIKE-INACTIVE-REQUIREMENTS | Requirements | Inactive',
        '- TAG-SPIKE-INACTIVE-DATA-PRIVACY | Data Privacy | Inactive',
        '',
        'ACTIVE SUBJECT VALUES:',
        '- Database Management Systems',
        '- Research Methods',
        '- Systems Analysis and Design',
        '- Web Systems and Technologies',
        '',
        'ACTIVE RESOURCE TYPE VALUES:',
        '- Handout',
        '- Module',
        '- Notes',
        '- Presentation',
        '- Reviewer',
        '- Study Guide',
        '',
        'DOCUMENT TEXT:',
        $documentText,
    ]);
}

/** @return array<string, mixed> */
function sumsugBuild(string $root): array
{
    $planPath = $root . DIRECTORY_SEPARATOR . 'docs'
        . DIRECTORY_SEPARATOR . 'ai-feasibility-spike'
        . DIRECTORY_SEPARATOR . 'SUMMARY_SUGGESTION_PAYLOAD_PLAN.md';
    $checkpointPath = dirname($planPath)
        . DIRECTORY_SEPARATOR . 'SUMMARY_SUGGESTION_CHECKPOINT.md';
    $candidatePath = dirname($planPath) . DIRECTORY_SEPARATOR
        . 'registers' . DIRECTORY_SEPARATOR . 'candidates.csv';
    $fixturePath = dirname($candidatePath) . DIRECTORY_SEPARATOR . 'fixtures.csv';

    foreach ([$planPath, $checkpointPath, $candidatePath, $fixturePath] as $path) {
        sumsugAssert(is_file($path) && is_readable($path),
            'Required source is readable: ' . substr($path, strlen($root) + 1));
    }
    $planText = file_get_contents($planPath);
    $checkpointText = file_get_contents($checkpointPath);
    if (!is_string($planText) || !is_string($checkpointText)) {
        sumsugFail('Could not read tracked Gate 5E plans.');
    }
    foreach ([
        'Eight requests',
        'Zero automatic retries',
        'Strict JSON Schema',
        'human reference note, expected tags, expected metadata',
        'does not authorize the live run',
    ] as $requiredText) {
        sumsugAssert(str_contains($planText, $requiredText),
            'Payload plan records: ' . $requiredText);
    }

    $candidates = sumsugCsv($candidatePath)['rows'];
    $candidateRows = array_values(array_filter(
        $candidates,
        static fn (array $row): bool =>
            $row['candidate_configuration_id'] === SUMSUG_CANDIDATE
    ));
    sumsugAssert(count($candidateRows) === 1,
        'Exactly one registered comparison candidate is found');
    sumsugAssert(
        $candidateRows[0]['model_or_library'] === SUMSUG_MODEL,
        'Registered model identity matches the payload candidate'
    );
    sumsugAssert(
        str_contains($candidateRows[0]['decision_status'], 'not accepted')
        && str_contains(
            $candidateRows[0]['decision_status'],
            'not accepted, selected, or integrated'
        )
        && str_contains(
            $candidateRows[0]['decision_status'],
            'strict claim-support and source-attribution criteria failed'
        ),
        'Prior candidate failure and non-selection remain explicit'
    );

    $fixtureRows = sumsugCsv($fixturePath)['rows'];
    $fixtureById = [];
    foreach ($fixtureRows as $row) {
        $fixtureById[$row['fixture_id']] = $row;
    }
    $cases = sumsugCases();
    sumsugAssert(count($cases) === SUMSUG_MAX_REQUESTS,
        'Exactly eight fixed cases are prepared');

    $rawRoot = $root . DIRECTORY_SEPARATOR . '.local'
        . DIRECTORY_SEPARATOR . 'ai-feasibility-spike'
        . DIRECTORY_SEPARATOR . 'results' . DIRECTORY_SEPARATOR . 'extraction'
        . DIRECTORY_SEPARATOR . 'EX-LOCAL-PHP-001'
        . DIRECTORY_SEPARATOR . 'full-20260715-215350'
        . DIRECTORY_SEPARATOR . 'raw';
    $requests = [];
    $indexRows = [];
    $manifestRows = [];
    $totalRequestBytes = 0;
    $estimatedInputTokens = 0;
    $sequence = 0;

    foreach ($cases as $fixtureId => $case) {
        $sequence++;
        sumsugAssert(isset($fixtureById[$fixtureId]),
            'Fixture exists: ' . $fixtureId);
        $fixture = $fixtureById[$fixtureId];
        sumsugAssert(
            $fixture['source_version_id'] === $case['version']
            && $fixture['file_type'] === $case['format']
            && $fixture['fixture_set'] === 'primary-readable'
            && $fixture['review_status'] === 'Accepted - manually reviewed',
            'Fixture identity and accepted state match: ' . $fixtureId
        );
        sumsugAssert(
            $fixture['contains_personal_or_sensitive_information'] === 'No'
            && $fixture['external_transmission_allowed'] !== 'No',
            'Fixture contains no personal data and is selected-test eligible: '
            . $fixtureId
        );
        sumsugAssert(
            str_contains($checkpointText, $case['reference'])
            && str_contains($checkpointText, $case['text_hash']),
            'Frozen human reference remains external to payload: ' . $fixtureId
        );

        $artifactPath = $rawRoot . DIRECTORY_SEPARATOR . $case['artifact'];
        sumsugAssert(is_file($artifactPath),
            'Accepted extraction artifact exists: ' . $fixtureId);
        sumsugAssert(sumsugHash($artifactPath) === $case['artifact_hash'],
            'Accepted extraction artifact hash matches: ' . $fixtureId);
        $artifact = sumsugJsonFile($artifactPath);
        $text = $artifact['full_text'] ?? null;
        sumsugAssert(
            ($artifact['result_status'] ?? null) === 'success'
            && ($artifact['fixture_id'] ?? null) === $fixtureId
            && ($artifact['source_version_id'] ?? null) === $case['version']
            && ($artifact['text_sha256'] ?? null) === $case['text_hash']
            && ($artifact['text_char_count'] ?? null) === $case['chars']
            && is_string($text)
            && trim($text) !== '',
            'Accepted extracted text identity is exact: ' . $fixtureId
        );

        $request = [
            'model' => SUMSUG_MODEL,
            'messages' => [
                ['role' => 'system', 'content' => sumsugInstruction()],
                [
                    'role' => 'user',
                    'content' => sumsugUserPrompt($fixtureId, $case, $text),
                ],
            ],
            'temperature' => 0,
            'reasoning_effort' => 'low',
            'max_completion_tokens' => SUMSUG_MAX_COMPLETION_TOKENS,
            'stream' => false,
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'learnshare_summary_suggestion',
                    'strict' => true,
                    'schema' => sumsugSchema(),
                ],
            ],
        ];
        sumsugAssert(!array_key_exists('tools', $request),
            'No tools are enabled: ' . $fixtureId);
        $body = json_encode(
            $request,
            JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );
        sumsugAssert(strlen($body) < 24000,
            'Serialized request stays below 24,000 bytes: ' . $fixtureId);
        sumsugAssert(
            !str_contains($body, 'REF-SUMSUG-')
            && !str_contains($body, 'expected tags')
            && !str_contains($body, 'Unsupported or overconfident metadata'),
            'Human answer key is absent from provider payload: ' . $fixtureId
        );
        sumsugAssert(
            !preg_match('/\bgsk_[A-Za-z0-9_-]{16,}\b/', $body),
            'Payload contains no Groq key-shaped value: ' . $fixtureId
        );

        $requestBytes = strlen($body);
        $bodyHash = strtoupper(hash('sha256', $body));
        $inputTokens = (int) ceil($requestBytes / 4);
        $totalRequestBytes += $requestBytes;
        $estimatedInputTokens += $inputTokens;
        $manifestId = sprintf('PM-GROQ-SUMSUG-PREVIEW-%03d', $sequence);
        $requests[] = $request;
        $indexRows[] = [
            'request_sequence' => (string) $sequence,
            'payload_manifest_id' => $manifestId,
            'fixture_id' => $fixtureId,
            'source_version_id' => (string) $case['version'],
            'reference_note_id_local_only' => (string) $case['reference'],
            'file_type' => (string) $case['format'],
            'text_char_count' => (string) $case['chars'],
            'text_sha256' => (string) $case['text_hash'],
            'request_body_bytes' => (string) $requestBytes,
            'request_body_sha256' => $bodyHash,
            'estimated_input_tokens_ceiling' => (string) $inputTokens,
            'max_completion_tokens' => (string) SUMSUG_MAX_COMPLETION_TOKENS,
        ];
        $manifestRows[] = [
            'payload_manifest_id' => $manifestId,
            'provider_or_model_candidate' => SUMSUG_CANDIDATE,
            'fixture_ids' => $fixtureId,
            'included_data_categories' => 'Synthetic fixture identity; synthetic title; file type; complete accepted extracted synthetic text; controlled test taxonomy',
            'source_identifiers_included' => 'Yes - synthetic fixture ID and source-version ID',
            'locator_information_included' => 'Only locator-like text already present inside the synthetic document; no separate locator field',
            'approximate_size' => (string) $requestBytes,
            'approximate_size_unit' => 'bytes (exact serialized request body)',
            'excluded_data_categories' => 'Accounts; sessions; roles; uploader/moderator data; database IDs; protected filenames; server/local paths; real institutional/student content; boundary fixtures; unrelated resources; prior outputs; human answer key; credentials; course/program; year level',
            'personal_or_account_linked_information_included' => 'No',
            'justification' => 'Offline preview for required non-authoritative summary and controlled-suggestion feasibility measurement',
            'external_transmission_authorization_basis' => 'Not authorized - exact local payload preview and review only',
            'reviewer' => 'Pending human payload review',
            'notes' => 'Zero network requests; preview row only; not added to accepted payload manifest register',
        ];
    }

    $maxOutputTokens = SUMSUG_MAX_REQUESTS * SUMSUG_MAX_COMPLETION_TOKENS;
    $worstCost = ($estimatedInputTokens / 1_000_000)
        * SUMSUG_INPUT_PRICE_PER_MILLION
        + ($maxOutputTokens / 1_000_000)
        * SUMSUG_OUTPUT_PRICE_PER_MILLION;
    sumsugAssert($estimatedInputTokens < 50000,
        'Conservative total input-token estimate stays below 50,000');
    sumsugAssert($worstCost < 0.02,
        'Published-rate worst-case cost stays below USD 0.02');

    return [
        'requests' => $requests,
        'index_rows' => $indexRows,
        'manifest_rows' => $manifestRows,
        'summary' => [
            'schema_version' => '1.0',
            'prepared_utc' => gmdate('c'),
            'provider' => SUMSUG_PROVIDER,
            'candidate_configuration_id' => SUMSUG_CANDIDATE,
            'model' => SUMSUG_MODEL,
            'endpoint' => SUMSUG_ENDPOINT,
            'prompt_template_version' => SUMSUG_TEMPLATE,
            'request_count' => SUMSUG_MAX_REQUESTS,
            'automatic_retries' => SUMSUG_RETRIES,
            'minimum_spacing_seconds_for_future_run' => SUMSUG_SPACING_SECONDS,
            'max_completion_tokens_per_request' => SUMSUG_MAX_COMPLETION_TOKENS,
            'total_request_body_bytes' => $totalRequestBytes,
            'estimated_input_tokens_ceiling' => $estimatedInputTokens,
            'maximum_completion_tokens_total' => $maxOutputTokens,
            'published_rate_worst_case_cost_usd' => round($worstCost, 8),
            'network_requests_made' => 0,
            'credential_reads' => 0,
            'live_run_authorized' => false,
            'candidate_selected' => false,
        ],
    ];
}

/** @param array<string, string> $files */
function sumsugManifest(array $files): string
{
    $rows = [];
    foreach ($files as $name => $contents) {
        $rows[] = [
            'artifact' => $name,
            'bytes' => (string) strlen($contents),
            'sha256' => strtoupper(hash('sha256', $contents)),
        ];
    }
    return sumsugCsvText(['artifact', 'bytes', 'sha256'], $rows);
}

$mode = null;
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--mode=')) {
        $mode = substr($argument, strlen('--mode='));
    } else {
        sumsugFail('Unknown argument: ' . $argument);
    }
}
sumsugAssert(in_array($mode, ['validate', 'apply'], true),
    'Mode is validate or apply');

$root = dirname(__DIR__, 2);
$prepared = sumsugBuild($root);
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
    'fixture_id',
    'source_version_id',
    'reference_note_id_local_only',
    'file_type',
    'text_char_count',
    'text_sha256',
    'request_body_bytes',
    'request_body_sha256',
    'estimated_input_tokens_ceiling',
    'max_completion_tokens',
];
$manifestHeaders = [
    'payload_manifest_id',
    'provider_or_model_candidate',
    'fixture_ids',
    'included_data_categories',
    'source_identifiers_included',
    'locator_information_included',
    'approximate_size',
    'approximate_size_unit',
    'excluded_data_categories',
    'personal_or_account_linked_information_included',
    'justification',
    'external_transmission_authorization_basis',
    'reviewer',
    'notes',
];
$review = implode(PHP_EOL, [
    '# Gate 5E Exact Payload Review',
    '',
    'Candidate: `' . SUMSUG_CANDIDATE . '`',
    'Model: `' . SUMSUG_MODEL . '`',
    'Requests: 8; zero automatic retries',
    '',
    'Review `provider-requests.jsonl` and `request-index.csv` before any live approval.',
    'The human reference notes and expected judgments are intentionally absent from provider requests.',
    'No request has been sent. This packet does not authorize external transmission.',
    '',
]);
$files = [
    'provider-requests.jsonl' => implode(PHP_EOL, $requestLines) . PHP_EOL,
    'request-index.csv' => sumsugCsvText($indexHeaders, $prepared['index_rows']),
    'payload-manifest-preview.csv' => sumsugCsvText(
        $manifestHeaders,
        $prepared['manifest_rows']
    ),
    'PAYLOAD_REVIEW.md' => $review,
    'preview-summary.json' => sumsugJsonText($prepared['summary']),
];

if ($mode === 'validate') {
    echo PHP_EOL;
    echo 'GATE 5E SUMMARY/SUGGESTION PAYLOAD VALIDATION PASSED.' . PHP_EOL;
    echo 'Checks passed: ' . count($passedChecks) . PHP_EOL;
    echo 'Exact synthetic requests computed: 8' . PHP_EOL;
    echo 'Network/provider requests: 0' . PHP_EOL;
    echo 'Credential reads: 0' . PHP_EOL;
    echo 'Payload/register/schema/database changes: 0' . PHP_EOL;
    echo 'Live generation authorized: No' . PHP_EOL;
    echo 'Next action: run --mode=apply to create the ignored local review packet; '
        . 'this still makes zero network requests.' . PHP_EOL;
    exit(0);
}

$output = $root . DIRECTORY_SEPARATOR . '.local'
    . DIRECTORY_SEPARATOR . 'ai-feasibility-spike'
    . DIRECTORY_SEPARATOR . 'results'
    . DIRECTORY_SEPARATOR . 'summary-suggestion-preview'
    . DIRECTORY_SEPARATOR . SUMSUG_CANDIDATE
    . DIRECTORY_SEPARATOR . 'payload-review-v1';
sumsugAssert(!file_exists($output), 'Final payload-review folder does not exist');
$parent = dirname($output);
if (!is_dir($parent) && !mkdir($parent, 0775, true) && !is_dir($parent)) {
    sumsugFail('Could not create payload-review parent folder.');
}
$partial = $parent . DIRECTORY_SEPARATOR . '.partial-' . getmypid();
if (!mkdir($partial, 0775, false)) {
    sumsugFail('Could not create partial payload-review folder.');
}
foreach ($files as $name => $contents) {
    if (file_put_contents($partial . DIRECTORY_SEPARATOR . $name, $contents)
        !== strlen($contents)) {
        sumsugFail('Could not write ' . $name);
    }
}
$manifest = sumsugManifest($files);
file_put_contents($partial . DIRECTORY_SEPARATOR . 'artifact-manifest.csv', $manifest);
$marker = "READY_FOR_HUMAN_PAYLOAD_REVIEW\n"
    . "NETWORK_REQUESTS=0\nLIVE_RUN_AUTHORIZED=NO\n";
file_put_contents($partial . DIRECTORY_SEPARATOR . 'READY_FOR_REVIEW.marker', $marker);
if (!rename($partial, $output)) {
    sumsugFail('Could not finalize the payload-review folder.');
}

echo PHP_EOL;
echo 'GATE 5E SUMMARY/SUGGESTION PAYLOAD REVIEW PACKET SAVED.' . PHP_EOL;
echo 'Folder: ' . $output . PHP_EOL;
echo 'Files: 7' . PHP_EOL;
echo 'Exact synthetic requests: 8' . PHP_EOL;
echo 'Network/provider requests: 0' . PHP_EOL;
echo 'Credential reads: 0' . PHP_EOL;
echo 'Accepted payload-register rows created: 0' . PHP_EOL;
echo 'Live generation authorized: No' . PHP_EOL;
echo 'Next action: independently audit all packet hashes, request bodies, '
    . 'answer-key exclusion, and safety boundaries before live approval.' . PHP_EOL;
