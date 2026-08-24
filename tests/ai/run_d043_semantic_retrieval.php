<?php

declare(strict_types=1);

use BpcLearnShare\Ai\AiFeatureGate;
use BpcLearnShare\Ai\AiPersistenceRepository;
use BpcLearnShare\Ai\BlockAwareContextFitSegmenter;
use BpcLearnShare\Ai\GuardedAiPersistenceProcessor;
use BpcLearnShare\Ai\GuardedLocalResourceProcessor;
use BpcLearnShare\Ai\GuardedSemanticRetrieval;
use BpcLearnShare\Ai\LocalEmbeddingAdapter;
use BpcLearnShare\Ai\LocalProcessingException;
use BpcLearnShare\Ai\LocalReadableTextExtractor;
use BpcLearnShare\Ai\SemanticRetrievalRepository;
use BpcLearnShare\Core\Environment;
use BpcLearnShare\Resource\ResourceDiscoveryRepository;

require dirname(__DIR__, 2) . '/src/bootstrap.php';

const D043_RETRIEVAL_DB_PREFIX = 'bpc_learnshare_ai_retrieval_';
const D043_RETRIEVAL_MODEL_DIGEST =
    '1b226e2802dbb772b5fc32a58f103ca1804ef7501331012de126ab22f67475ef';

final class D043RetrievalFakeAdapter implements LocalEmbeddingAdapter
{
    public int $requests = 0;

    /** @param null|callable(): void $afterEmbed */
    public function __construct(
        private readonly bool $fail = false,
        private readonly mixed $afterEmbed = null
    )
    {
    }

    public function configurationId(): string
    {
        return 'EMB-OLLAMA-ALL-MINILM-001';
    }

    public function dependencyFingerprint(): string
    {
        return hash('sha256', 'd043-retrieval-fake-adapter');
    }

    public function preflight(): array
    {
        if ($this->fail) {
            throw new RuntimeException('Synthetic adapter outage.');
        }

        return [
            'runtime_version' => '0.32.1-test-double',
            'model_reference' => 'all-minilm:latest',
            'model_digest' => D043_RETRIEVAL_MODEL_DIGEST,
            'model_size_bytes' => 45960996,
            'expected_dimension' => 384,
        ];
    }

    public function embed(string $text): array
    {
        $this->preflight();
        $this->requests++;

        if (is_callable($this->afterEmbed)) {
            ($this->afterEmbed)();
        }

        $text = strtolower($text);
        $vector = array_fill(0, 384, 0.0);

        if (str_contains($text, 'normalization') || str_contains($text, 'anomal')) {
            $vector[0] = 1.0;
        } elseif (str_contains($text, 'usability') || str_contains($text, 'heuristic')) {
            $vector[1] = 1.0;
        } else {
            $vector[2] = 1.0;
        }

        return [
            'model_reference' => 'all-minilm:latest',
            'model_digest' => D043_RETRIEVAL_MODEL_DIGEST,
            'vector' => $vector,
        ];
    }
}

$checks = 0;

function retrievalAssert(bool $condition, string $label): void
{
    global $checks;

    if (!$condition) {
        throw new RuntimeException($label . ' failed.');
    }

    $checks++;
    fwrite(STDOUT, '[PASS] ' . $label . PHP_EOL);
}

function retrievalSame(mixed $expected, mixed $actual, string $label): void
{
    retrievalAssert(
        $expected === $actual,
        sprintf('%s (expected %s; received %s)', $label, var_export($expected, true), var_export($actual, true))
    );
}

/** @param callable(): void $operation */
function retrievalFailure(callable $operation, string $reason, string $label): void
{
    try {
        $operation();
    } catch (LocalProcessingException $exception) {
        retrievalSame($reason, $exception->reason, $label);

        return;
    }

    throw new RuntimeException($label . ' failed: operation was accepted.');
}

function retrievalIdentifier(string $value): string
{
    if (preg_match('/\A[a-z0-9_]+\z/', $value) !== 1) {
        throw new RuntimeException('Unsafe disposable database name refused.');
    }

    return '`' . $value . '`';
}

/** @return list<string> */
function retrievalSplitSql(string $sql): array
{
    $statements = [];
    $buffer = '';
    $quote = null;
    $lineComment = false;
    $blockComment = false;
    $length = strlen($sql);

    for ($index = 0; $index < $length; $index++) {
        $character = $sql[$index];
        $next = $index + 1 < $length ? $sql[$index + 1] : '';

        if ($lineComment) {
            if ($character === "\n") {
                $lineComment = false;
                $buffer .= "\n";
            }
            continue;
        }

        if ($blockComment) {
            if ($character === '*' && $next === '/') {
                $blockComment = false;
                $index++;
            }
            continue;
        }

        if ($quote !== null) {
            $buffer .= $character;
            if ($character === '\\' && $quote !== '`' && $next !== '') {
                $buffer .= $next;
                $index++;
                continue;
            }
            if ($character === $quote) {
                if ($next === $quote && $quote !== '`') {
                    $buffer .= $next;
                    $index++;
                } else {
                    $quote = null;
                }
            }
            continue;
        }

        if ($character === '-' && $next === '-' && ($index + 2 >= $length || ctype_space($sql[$index + 2]))) {
            $lineComment = true;
            $index++;
            continue;
        }
        if ($character === '/' && $next === '*') {
            $blockComment = true;
            $index++;
            continue;
        }
        if ($character === "'" || $character === '"' || $character === '`') {
            $quote = $character;
            $buffer .= $character;
            continue;
        }
        if ($character === ';') {
            if (trim($buffer) !== '') {
                $statements[] = trim($buffer);
            }
            $buffer = '';
            continue;
        }
        $buffer .= $character;
    }

    if (trim($buffer) !== '') {
        $statements[] = trim($buffer);
    }

    return $statements;
}

function retrievalImportSchema(PDO $database, string $path): void
{
    $sql = file_get_contents($path);

    if (!is_string($sql) || trim($sql) === '') {
        throw new RuntimeException('Canonical schema is missing.');
    }

    foreach (retrievalSplitSql($sql) as $statement) {
        $database->exec($statement);
    }
}

function retrievalCount(PDO $database, string $table): int
{
    if (preg_match('/\A[a-z_]+\z/', $table) !== 1) {
        throw new RuntimeException('Unsafe table name refused.');
    }

    return (int) $database->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
}

/** @param list<int> $resourceIds */
function retrievalInsertResource(
    PDO $database,
    string $storage,
    int $number,
    string $title,
    string $topic,
    string $text,
    int $courseId,
    int $tagId,
    array &$resourceIds
): int {
    $stored = hash('sha256', 'd043-retrieval-resource-' . $number) . '.txt';
    $path = $storage . DIRECTORY_SEPARATOR . $stored;
    file_put_contents($path, $text, LOCK_EX);
    $size = filesize($path);

    if (!is_int($size)) {
        throw new RuntimeException('Unable to size disposable resource.');
    }

    $statement = $database->prepare(
        "INSERT INTO resources (
            uploader_id, title, description, topic, course_id, subject_id,
            year_level_id, resource_type_id, status, original_filename,
            stored_filename, file_type, file_size, file_availability,
            ai_notice_acknowledged, ai_notice_acknowledged_at
         ) VALUES (
            2, :title, :description, :topic, :course_id, 1, 1, 1,
            'approved', :original, :stored, 'txt', :size, 'available',
            1, CURRENT_TIMESTAMP
         )"
    );
    $statement->execute([
        'title' => $title,
        'description' => $text,
        'topic' => $topic,
        'course_id' => $courseId,
        'original' => 'retrieval-' . $number . '.txt',
        'stored' => $stored,
        'size' => $size,
    ]);
    $resourceId = (int) $database->lastInsertId();
    $database->prepare(
        'INSERT INTO resource_tags (resource_id, tag_id) VALUES (:resource_id, :tag_id)'
    )->execute(['resource_id' => $resourceId, 'tag_id' => $tagId]);
    $resourceIds[] = $resourceId;

    return $resourceId;
}

/** @return array{q:string,course_id:int,subject_id:int,year_level_id:int,resource_type_id:int,tag_id:int} */
function retrievalFilters(string $query, int $courseId = 0, int $tagId = 0): array
{
    return [
        'q' => $query,
        'course_id' => $courseId,
        'subject_id' => 0,
        'year_level_id' => 0,
        'resource_type_id' => 0,
        'tag_id' => $tagId,
    ];
}

$root = dirname(__DIR__, 2);
$host = Environment::get('D043_DB_HOST', Environment::get('DB_HOST', '127.0.0.1'));
$port = Environment::getInt('D043_DB_PORT', Environment::getInt('DB_PORT', 3306));
$user = Environment::get('D043_DB_ADMIN_USER', 'root');
$password = Environment::get('D043_DB_ADMIN_PASS', '');
$liveDatabase = Environment::get('DB_NAME', '');
$databaseName = D043_RETRIEVAL_DB_PREFIX . bin2hex(random_bytes(5));
$storage = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bpc-learnshare-ai-retrieval-' . bin2hex(random_bytes(5));
$server = null;
$database = null;
$createdDatabase = false;
$createdStorage = false;
$resourceIds = [];
$failed = false;

try {
    fwrite(STDOUT, "=== D043 GUARDED SEMANTIC RETRIEVAL CHECKPOINT ===\n");
    fwrite(STDOUT, "Disposable database: {$databaseName}\n");
    fwrite(STDOUT, "Live database: {$liveDatabase} (read-only boundary)\n");
    fwrite(STDOUT, "Real provider/model requests: 0\n\n");

    retrievalAssert(preg_match('/\A' . D043_RETRIEVAL_DB_PREFIX . '[0-9a-f]{10}\z/', $databaseName) === 1, 'Disposable database name guard');
    retrievalAssert($liveDatabase === '' || $databaseName !== $liveDatabase, 'Disposable database differs from live database');

    $server = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );

    $liveTableCount = null;
    $liveCounts = [];
    if ($liveDatabase !== '') {
        $tableStatement = $server->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :schema AND table_type = 'BASE TABLE'");
        $tableStatement->execute(['schema' => $liveDatabase]);
        $liveTableCount = (int) $tableStatement->fetchColumn();
        retrievalSame(22, $liveTableCount, 'Live database starts at 22 tables');
        foreach (['ai_source_versions', 'ai_processing_states', 'ai_chunks', 'ai_embeddings', 'ai_outputs'] as $table) {
            $liveCounts[$table] = (int) $server->query('SELECT COUNT(*) FROM ' . retrievalIdentifier($liveDatabase) . '.' . retrievalIdentifier($table))->fetchColumn();
        }
    }

    $server->exec('CREATE DATABASE ' . retrievalIdentifier($databaseName) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $createdDatabase = true;
    $database = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $databaseName),
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
    retrievalImportSchema($database, $root . '/database/schema.sql');
    retrievalSame(22, (int) $database->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'")->fetchColumn(), 'Canonical 22-table schema imported');

    foreach ([
        "INSERT INTO accounts (id, username, password_hash, display_name, role, account_status) VALUES (1, 'retrieval_admin', 'not-login-hash', 'Retrieval Admin', 'admin', 'active')",
        "INSERT INTO accounts (id, username, password_hash, display_name, role, account_status) VALUES (2, 'retrieval_student', 'not-login-hash', 'Retrieval Student', 'student', 'active')",
        "INSERT INTO accounts (id, username, password_hash, display_name, role, account_status) VALUES (3, 'retrieval_teacher', 'not-login-hash', 'Retrieval Teacher', 'teacher_instructor', 'active')",
        "INSERT INTO accounts (id, username, password_hash, display_name, role, account_status) VALUES (4, 'retrieval_disabled', 'not-login-hash', 'Disabled Student', 'student', 'disabled')",
        "INSERT INTO courses (id, name, is_active) VALUES (1, 'Information Systems', 1), (2, 'User Experience', 1)",
        "INSERT INTO subjects (id, name, is_active) VALUES (1, 'Academic Resource Test', 1)",
        "INSERT INTO year_levels (id, name, is_active) VALUES (1, '3rd Year', 1)",
        "INSERT INTO resource_types (id, name, is_active) VALUES (1, 'Study Guide', 1)",
        "INSERT INTO tags (id, name, is_active) VALUES (1, 'Database', 1), (2, 'Usability', 1)",
        "INSERT INTO system_settings (setting_name, setting_value, updated_by_account_id) VALUES ('ai_enabled', 'enabled', 1)",
    ] as $seed) {
        $database->exec($seed);
    }

    if (!mkdir($storage, 0700, true) && !is_dir($storage)) {
        throw new RuntimeException('Unable to create disposable protected storage.');
    }
    $createdStorage = true;

    $normalization = retrievalInsertResource(
        $database,
        $storage,
        1,
        'Database Normalization Guide',
        'Normalization anomalies',
        "Database normalization reduces update, insertion, and deletion anomalies.\nThird normal form separates dependencies.",
        1,
        1,
        $resourceIds
    );
    $usability = retrievalInsertResource(
        $database,
        $storage,
        2,
        'Usability Evaluation Guide',
        'Heuristic evaluation',
        "Usability testing and heuristic evaluation reveal interface problems.\nThink-aloud sessions expose user confusion.",
        2,
        2,
        $resourceIds
    );
    $hidden = retrievalInsertResource(
        $database,
        $storage,
        3,
        'Hidden Normalization Draft',
        'Normalization draft',
        'Normalization draft content must not remain retrievable after a live status change.',
        1,
        1,
        $resourceIds
    );

    $gate = new AiFeatureGate($database);
    $persistenceRepository = new AiPersistenceRepository($database);
    $persistence = new GuardedAiPersistenceProcessor($database, $persistenceRepository, $gate, $storage);
    $processingAdapter = new D043RetrievalFakeAdapter();
    $processor = new GuardedLocalResourceProcessor(
        $persistenceRepository,
        $persistence,
        $gate,
        new LocalReadableTextExtractor(),
        new BlockAwareContextFitSegmenter(),
        $processingAdapter,
        $storage,
        true
    );
    foreach ([$normalization, $usability, $hidden] as $resourceId) {
        $processor->process($resourceId, 1);
    }
    $database->exec("UPDATE resources SET status = 'hidden' WHERE id = {$hidden}");

    $semanticRepository = new SemanticRetrievalRepository($database);
    $metadataRepository = new ResourceDiscoveryRepository($database);
    $queryAdapter = new D043RetrievalFakeAdapter();
    $disabledService = new GuardedSemanticRetrieval(
        $semanticRepository, $metadataRepository, $gate, $queryAdapter, $storage, false
    );
    $aiCountsBefore = [];
    foreach (['ai_source_versions', 'ai_processing_states', 'ai_chunks', 'ai_embeddings', 'ai_outputs'] as $table) {
        $aiCountsBefore[$table] = retrievalCount($database, $table);
    }

    $fallback = $disabledService->search(retrievalFilters('Normalization'), 2);
    retrievalSame('metadata_fallback', $fallback['mode'], 'Operator-disabled semantic path uses metadata fallback');
    retrievalSame('semantic_retrieval_disabled', $fallback['fallback_reason'], 'Operator-disabled fallback reason is explicit');
    retrievalSame(0, $queryAdapter->requests, 'Operator-disabled path sends no embedding request');
    retrievalSame($normalization, (int) $fallback['results'][0]['id'], 'Existing metadata search remains functional');

    $service = new GuardedSemanticRetrieval(
        $semanticRepository, $metadataRepository, $gate, $queryAdapter, $storage, true
    );
    $database->exec("UPDATE system_settings SET setting_value = 'disabled' WHERE setting_name = 'ai_enabled'");
    $liveDisabled = $service->search(retrievalFilters('Normalization'), 2);
    retrievalSame('metadata_fallback', $liveDisabled['mode'], 'Live AI disable uses metadata fallback');
    retrievalSame(0, $queryAdapter->requests, 'Live-disabled path sends no embedding request');
    $database->exec("UPDATE system_settings SET setting_value = 'enabled' WHERE setting_name = 'ai_enabled'");

    retrievalFailure(
        fn () => $service->search(retrievalFilters('Normalization'), 4),
        'semantic_requester_not_authorized',
        'Disabled requester is rejected before fallback or embedding'
    );
    retrievalSame(0, $queryAdapter->requests, 'Unauthorized requester sends no embedding request');

    $lateDisableAdapter = new D043RetrievalFakeAdapter(
        false,
        static function () use ($database): void {
            $database->exec("UPDATE accounts SET account_status = 'disabled' WHERE id = 3");
        }
    );
    $lateDisableService = new GuardedSemanticRetrieval(
        $semanticRepository,
        $metadataRepository,
        $gate,
        $lateDisableAdapter,
        $storage,
        true
    );
    retrievalFailure(
        fn () => $lateDisableService->search(retrievalFilters('normalization anomalies'), 3),
        'semantic_requester_not_authorized',
        'Late requester disable rejects result return instead of falling back'
    );
    $database->exec("UPDATE accounts SET account_status = 'active' WHERE id = 3");

    $empty = $service->search(retrievalFilters(''), 3);
    retrievalSame('metadata_fallback', $empty['mode'], 'Empty query safely uses metadata discovery');
    retrievalSame(0, $queryAdapter->requests, 'Empty query sends no embedding request');

    $semantic = $service->search(retrievalFilters('How does normalization prevent anomalies?'), 2, 5);
    retrievalSame('semantic', $semantic['mode'], 'Active student receives semantic ranking');
    retrievalSame(1, $queryAdapter->requests, 'Exactly one query embedding request is used');
    retrievalSame($normalization, (int) $semantic['results'][0]['id'], 'Meaning-based query ranks expected resource first');
    retrievalSame(false, $semantic['query_vector_persisted'], 'Query vector is explicitly non-persistent');
    retrievalSame(false, $semantic['similarity_score_is_evidence_threshold'], 'Similarity score is not treated as evidence sufficiency');
    retrievalSame(2, count($semantic['results']), 'One best passage per eligible resource is returned');
    retrievalAssert(!in_array($hidden, array_column($semantic['results'], 'id'), true), 'Hidden resource is excluded by live status');

    $filtered = $service->search(retrievalFilters('normalization anomalies', 2, 2), 3);
    retrievalSame('semantic', $filtered['mode'], 'Active teacher may use guarded semantic ranking');
    retrievalSame(1, count($filtered['results']), 'Course and tag filters constrain semantic candidates');
    retrievalSame($usability, (int) $filtered['results'][0]['id'], 'Filtered semantic result stays inside selected metadata scope');

    $normalSource = (int) $database->query("SELECT id FROM ai_source_versions WHERE resource_id = {$normalization} AND current_marker = 1")->fetchColumn();
    $database->exec("UPDATE ai_processing_states SET processing_status = 'stale' WHERE source_version_id = {$normalSource} AND capability = 'embedding'");
    $staleExcluded = $service->search(retrievalFilters('normalization anomalies'), 2);
    retrievalSame('semantic', $staleExcluded['mode'], 'Remaining ready corpus can still rank when one source becomes stale');
    retrievalAssert(!in_array($normalization, array_column($staleExcluded['results'], 'id'), true), 'Stale embedding source is excluded');
    $database->exec("UPDATE ai_processing_states SET processing_status = 'ready' WHERE source_version_id = {$normalSource} AND capability = 'embedding'");

    $embeddingId = (int) $database->query("SELECT e.id FROM ai_embeddings e INNER JOIN ai_chunks c ON c.id = e.chunk_id WHERE c.source_version_id = {$normalSource} ORDER BY e.id LIMIT 1")->fetchColumn();
    $validVectorJson = (string) $database->query("SELECT vector_json FROM ai_embeddings WHERE id = {$embeddingId}")->fetchColumn();
    $validVectorHash = hash('sha256', $validVectorJson);
    $corrupt = json_decode($validVectorJson, true, 512, JSON_THROW_ON_ERROR);
    $corrupt[0] = 'not-a-number';
    $corruptJson = json_encode($corrupt, JSON_THROW_ON_ERROR);
    $corruptUpdate = $database->prepare('UPDATE ai_embeddings SET vector_json = :vector_json, vector_sha256 = :vector_sha256 WHERE id = :id');
    $corruptUpdate->execute(['vector_json' => $corruptJson, 'vector_sha256' => hash('sha256', $corruptJson), 'id' => $embeddingId]);
    $corruptFallback = $service->search(retrievalFilters('normalization anomalies'), 2);
    retrievalSame('metadata_fallback', $corruptFallback['mode'], 'Malformed stored vector fails safely to metadata');
    retrievalSame('semantic_dependency_unavailable', $corruptFallback['fallback_reason'], 'Malformed-index fallback exposes only a safe reason');
    $corruptUpdate->execute(['vector_json' => $validVectorJson, 'vector_sha256' => $validVectorHash, 'id' => $embeddingId]);

    $normalFile = (string) $database->query("SELECT stored_filename FROM resources WHERE id = {$normalization}")->fetchColumn();
    $normalPath = $storage . DIRECTORY_SEPARATOR . $normalFile;
    $normalBytes = file_get_contents($normalPath);
    unlink($normalPath);
    $missingFallback = $service->search(retrievalFilters('normalization anomalies'), 2);
    retrievalSame('metadata_fallback', $missingFallback['mode'], 'Missing protected source fails safely to metadata');
    file_put_contents($normalPath, $normalBytes, LOCK_EX);

    $failedAdapter = new D043RetrievalFakeAdapter(true);
    $failedService = new GuardedSemanticRetrieval(
        $semanticRepository, $metadataRepository, $gate, $failedAdapter, $storage, true
    );
    $adapterFallback = $failedService->search(retrievalFilters('Normalization'), 2);
    retrievalSame('metadata_fallback', $adapterFallback['mode'], 'Embedding dependency outage preserves metadata search');
    retrievalSame(0, $failedAdapter->requests, 'Failed preflight transmits no query to model');

    foreach ($aiCountsBefore as $table => $before) {
        retrievalSame($before, retrievalCount($database, $table), 'Semantic searches do not write ' . $table);
    }

    if ($liveDatabase !== '') {
        $tableStatement->execute(['schema' => $liveDatabase]);
        retrievalSame($liveTableCount, (int) $tableStatement->fetchColumn(), 'Live database remains at 22 tables');
        foreach ($liveCounts as $table => $before) {
            $after = (int) $server->query('SELECT COUNT(*) FROM ' . retrievalIdentifier($liveDatabase) . '.' . retrievalIdentifier($table))->fetchColumn();
            retrievalSame($before, $after, 'Live ' . $table . ' remains unchanged');
        }
    }
} catch (Throwable $exception) {
    $failed = true;
    fwrite(STDERR, "\nD043 SEMANTIC RETRIEVAL CHECKPOINT FAILED: " . $exception->getMessage() . PHP_EOL);
} finally {
    $database = null;
    if ($createdDatabase && $server instanceof PDO) {
        try {
            $server->exec('DROP DATABASE ' . retrievalIdentifier($databaseName));
            fwrite(STDOUT, "Disposable database removed: {$databaseName}\n");
        } catch (Throwable $cleanupException) {
            $failed = true;
            fwrite(STDERR, 'Disposable database cleanup failed: ' . $cleanupException->getMessage() . PHP_EOL);
        }
    }
    if ($createdStorage && is_dir($storage)) {
        foreach (glob($storage . DIRECTORY_SEPARATOR . '*') ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        if (!rmdir($storage)) {
            $failed = true;
            fwrite(STDERR, "Disposable storage cleanup failed.\n");
        }
    }
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "\nD043 GUARDED SEMANTIC RETRIEVAL CHECKPOINT PASSED.\n");
fwrite(STDOUT, "Checks passed: {$checks}\n");
fwrite(STDOUT, "Real provider/model requests performed: 0\n");
fwrite(STDOUT, "Live database writes performed: 0\n");
fwrite(STDOUT, "Query vectors persisted: 0\n");
fwrite(STDOUT, "Public AI route or UI added: No\n");
fwrite(STDOUT, "Generation or inquiry added: No\n");
fwrite(STDOUT, "Next boundary: review before any public integration.\n");
