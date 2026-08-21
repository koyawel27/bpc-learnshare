<?php

declare(strict_types=1);

use BpcLearnShare\Ai\AiFeatureGate;
use BpcLearnShare\Ai\AiPersistenceException;
use BpcLearnShare\Ai\AiPersistenceRepository;
use BpcLearnShare\Ai\BlockAwareContextFitSegmenter;
use BpcLearnShare\Ai\GuardedAiPersistenceProcessor;
use BpcLearnShare\Ai\GuardedLocalResourceProcessor;
use BpcLearnShare\Ai\LocalEmbeddingAdapter;
use BpcLearnShare\Ai\LocalProcessingException;
use BpcLearnShare\Ai\LocalReadableTextExtractor;
use BpcLearnShare\Core\Environment;

require dirname(__DIR__, 2) . '/src/bootstrap.php';

const D043_LOCAL_DB_PREFIX = 'bpc_learnshare_ai_local_';

final class D043FakeEmbeddingAdapter implements LocalEmbeddingAdapter
{
    public int $requests = 0;

    /** @param null|callable(int): void $afterEmbed */
    public function __construct(private readonly mixed $afterEmbed = null)
    {
    }

    public function configurationId(): string
    {
        return 'EMB-OLLAMA-ALL-MINILM-001';
    }

    public function dependencyFingerprint(): string
    {
        return hash('sha256', 'fake-reviewed-all-minilm-adapter');
    }

    public function preflight(): array
    {
        return [
            'runtime_version' => '0.32.1-test-double',
            'model_reference' => 'all-minilm:latest',
            'model_digest' => str_repeat('a', 64),
            'model_size_bytes' => 45960996,
            'expected_dimension' => 384,
        ];
    }

    public function embed(string $text): array
    {
        if (trim($text) === '') {
            throw new RuntimeException('Test adapter received empty text.');
        }

        $this->requests++;

        if (is_callable($this->afterEmbed)) {
            ($this->afterEmbed)($this->requests);
        }

        $vector = array_fill(0, 384, 0.0);
        $vector[($this->requests - 1) % 384] = 1.0;

        return [
            'model_reference' => 'all-minilm:latest',
            'model_digest' => str_repeat('a', 64),
            'vector' => $vector,
        ];
    }
}

$checks = 0;

function localAssert(bool $condition, string $label): void
{
    global $checks;

    if (!$condition) {
        throw new RuntimeException($label . ' failed.');
    }

    $checks++;
    fwrite(STDOUT, '[PASS] ' . $label . PHP_EOL);
}

function localSame(mixed $expected, mixed $actual, string $label): void
{
    localAssert(
        $expected === $actual,
        sprintf('%s (expected %s; received %s)', $label, var_export($expected, true), var_export($actual, true))
    );
}

/** @param callable(): void $operation */
function localFailure(callable $operation, string $reason, string $label): void
{
    try {
        $operation();
    } catch (LocalProcessingException|AiPersistenceException $exception) {
        localSame($reason, $exception->reason, $label);

        return;
    }

    throw new RuntimeException($label . ' failed: operation was accepted.');
}

function localIdentifier(string $value): string
{
    if (preg_match('/\A[a-z0-9_]+\z/', $value) !== 1) {
        throw new RuntimeException('Unsafe disposable database name refused.');
    }

    return '`' . $value . '`';
}

/** @return list<string> */
function localSplitSql(string $sql): array
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

    if ($quote !== null || $blockComment) {
        throw new RuntimeException('Canonical schema contains unterminated SQL.');
    }

    if (trim($buffer) !== '') {
        $statements[] = trim($buffer);
    }

    return $statements;
}

function localImportSchema(PDO $database, string $path): void
{
    $sql = file_get_contents($path);

    if (!is_string($sql) || trim($sql) === '') {
        throw new RuntimeException('Canonical schema is missing.');
    }

    foreach (localSplitSql($sql) as $statement) {
        $database->exec($statement);
    }
}

function localCount(PDO $database, string $table): int
{
    if (preg_match('/\A[a-z_]+\z/', $table) !== 1) {
        throw new RuntimeException('Unsafe table name refused.');
    }

    return (int) $database->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
}

/** @param array<int, int> $ids */
function localInsertResource(PDO $database, string $storage, int $number, string $text, array &$ids): int
{
    $stored = hash('sha256', 'd043-local-resource-' . $number) . '.txt';
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
            2, :title, 'Synthetic local-processing test resource.',
            'Guarded local processing', 1, 1, 1, 1, 'approved', :original,
            :stored, 'txt', :size, 'available', 1, CURRENT_TIMESTAMP
         )"
    );
    $statement->execute([
        'title' => 'Local Processing Test ' . $number,
        'original' => 'local-processing-' . $number . '.txt',
        'stored' => $stored,
        'size' => $size,
    ]);
    $ids[] = (int) $database->lastInsertId();

    return (int) $database->lastInsertId();
}

$root = dirname(__DIR__, 2);
$host = Environment::get('D043_DB_HOST', Environment::get('DB_HOST', '127.0.0.1'));
$port = Environment::getInt('D043_DB_PORT', Environment::getInt('DB_PORT', 3306));
$user = Environment::get('D043_DB_ADMIN_USER', 'root');
$password = Environment::get('D043_DB_ADMIN_PASS', '');
$liveDatabase = Environment::get('DB_NAME', '');
$databaseName = D043_LOCAL_DB_PREFIX . bin2hex(random_bytes(5));
$storage = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bpc-learnshare-ai-local-' . bin2hex(random_bytes(5));
$server = null;
$database = null;
$createdDatabase = false;
$createdStorage = false;
$resourceIds = [];
$failed = false;

try {
    fwrite(STDOUT, "=== D043 GUARDED LOCAL PROCESSING CHECKPOINT ===\n");
    fwrite(STDOUT, "Disposable database: {$databaseName}\n");
    fwrite(STDOUT, "Live database: {$liveDatabase} (read-only boundary)\n");
    fwrite(STDOUT, "Real provider/model requests: 0\n\n");

    localAssert(preg_match('/\A' . D043_LOCAL_DB_PREFIX . '[0-9a-f]{10}\z/', $databaseName) === 1, 'Disposable database name guard');
    localAssert($liveDatabase === '' || $databaseName !== $liveDatabase, 'Disposable database differs from live database');

    $server = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
    localSame('10.4.32-MariaDB', (string) $server->query('SELECT VERSION()')->fetchColumn(), 'Exact MariaDB runtime');

    $liveCounts = [];
    $liveTableCount = null;
    if ($liveDatabase !== '') {
        $tableStatement = $server->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = :schema AND table_type = 'BASE TABLE'");
        $tableStatement->execute(['schema' => $liveDatabase]);
        $liveTableCount = (int) $tableStatement->fetchColumn();
        localSame(22, $liveTableCount, 'Live database starts at 22 tables');

        foreach (['ai_source_versions', 'ai_processing_states', 'ai_chunks', 'ai_embeddings', 'ai_outputs'] as $table) {
            $liveCountStatement = $server->query('SELECT COUNT(*) FROM ' . localIdentifier($liveDatabase) . '.' . localIdentifier($table));
            $liveCounts[$table] = (int) $liveCountStatement->fetchColumn();
            localSame(0, $liveCounts[$table], 'Live ' . $table . ' starts empty');
        }
    }

    $server->exec('CREATE DATABASE ' . localIdentifier($databaseName) . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $createdDatabase = true;
    $database = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $databaseName),
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
    localImportSchema($database, $root . '/database/schema.sql');
    localSame(22, (int) $database->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'")->fetchColumn(), 'Canonical 22-table schema imported');

    foreach ([
        "INSERT INTO accounts (id, username, password_hash, display_name, role, account_status) VALUES (1, 'local_admin', 'not-login-hash', 'Local Admin', 'admin', 'active')",
        "INSERT INTO accounts (id, username, password_hash, display_name, role, account_status) VALUES (2, 'local_student', 'not-login-hash', 'Local Student', 'student', 'active')",
        "INSERT INTO accounts (id, username, password_hash, display_name, role, account_status) VALUES (3, 'local_moderator', 'not-login-hash', 'Local Moderator', 'moderator', 'disabled')",
        "INSERT INTO courses (id, name, is_active) VALUES (1, 'Local AI Test', 1)",
        "INSERT INTO subjects (id, name, is_active) VALUES (1, 'Local AI Test Subject', 1)",
        "INSERT INTO year_levels (id, name, is_active) VALUES (1, 'Test Level', 1)",
        "INSERT INTO resource_types (id, name, is_active) VALUES (1, 'Test Document', 1)",
        "INSERT INTO system_settings (setting_name, setting_value, updated_by_account_id) VALUES ('ai_enabled', 'disabled', 1)",
    ] as $seed) {
        $database->exec($seed);
    }

    if (!mkdir($storage, 0700, true) && !is_dir($storage)) {
        throw new RuntimeException('Unable to create disposable protected storage.');
    }
    $createdStorage = true;

    $text = "Local processing checkpoint\n\n" . str_repeat('This verified academic paragraph preserves a deterministic locator and remains inside the accepted context-fit chunk size. ', 20);
    $resource1 = localInsertResource($database, $storage, 1, $text, $resourceIds);
    $repository = new AiPersistenceRepository($database);
    $gate = new AiFeatureGate($database);
    $persistence = new GuardedAiPersistenceProcessor($database, $repository, $gate, $storage);
    $extractor = new LocalReadableTextExtractor();
    $segmenter = new BlockAwareContextFitSegmenter();
    $disabledAdapter = new D043FakeEmbeddingAdapter();
    $disabledProcessor = new GuardedLocalResourceProcessor($repository, $persistence, $gate, $extractor, $segmenter, $disabledAdapter, $storage, false);

    localFailure(fn () => $disabledProcessor->validate($resource1, 1), 'local_processing_disabled', 'Environment switch defaults local processing off');
    localSame(0, localCount($database, 'ai_source_versions'), 'Disabled environment writes no source');

    $enabledAdapter = new D043FakeEmbeddingAdapter();
    $processor = new GuardedLocalResourceProcessor($repository, $persistence, $gate, $extractor, $segmenter, $enabledAdapter, $storage, true);
    localFailure(fn () => $processor->validate($resource1, 1), 'ai_disabled', 'Live database AI switch also fails closed');
    localSame(0, localCount($database, 'ai_source_versions'), 'Disabled live AI setting writes no source');

    $database->exec("UPDATE system_settings SET setting_value = 'enabled' WHERE setting_name = 'ai_enabled'");
    localFailure(fn () => $processor->validate($resource1, 2), 'processing_not_authorized', 'Student cannot trigger local processing');
    localFailure(fn () => $processor->validate($resource1, 3), 'processing_not_authorized', 'Disabled moderator cannot trigger local processing');
    localSame(0, localCount($database, 'ai_source_versions'), 'Unauthorized validation writes no source');

    $validation = $processor->validate($resource1, 1);
    localSame('txt', $validation['file_type'], 'Approved TXT resource validates');
    localSame('EX-LOCAL-PHP-001', $validation['extraction_configuration_id'], 'Accepted extraction identity retained');
    localSame('SEG-BLOCK-AWARE-CONTEXT-FIT-002', $validation['segmentation_configuration_id'], 'Accepted segmentation identity retained');
    localSame('EMB-OLLAMA-ALL-MINILM-001', $validation['embedding_configuration_id'], 'Accepted embedding identity retained');
    localSame(0, $enabledAdapter->requests, 'Validation performs no content embedding');

    $result = $processor->process($resource1, 1);
    localAssert((int) $result['source_version_id'] > 0, 'Guarded source version persisted');
    localAssert((int) $result['chunk_count'] >= 2, 'Deterministic segmentation stored multiple bounded chunks');
    localSame((int) $result['chunk_count'], (int) $result['embedding_count'], 'Every chunk has one embedding result');
    localSame((int) $result['chunk_count'], localCount($database, 'ai_chunks'), 'Exact chunk count stored');
    localSame((int) $result['embedding_count'], localCount($database, 'ai_embeddings'), 'Exact embedding count stored');
    localSame(0, localCount($database, 'ai_outputs'), 'Local path creates no generated output');
    localSame((int) $result['embedding_count'], $enabledAdapter->requests, 'One fake embedding request per chunk');

    $ready = $database->query("SELECT capability, processing_status FROM ai_processing_states ORDER BY capability")->fetchAll();
    localSame(3, count($ready), 'Only extraction segmentation and embedding states exist');
    localAssert(array_reduce($ready, static fn (bool $carry, array $row): bool => $carry && $row['processing_status'] === 'ready', true), 'All three local processing states are ready');
    localSame(0, (int) $database->query("SELECT COUNT(*) FROM ai_chunks WHERE CHAR_LENGTH(chunk_text) > 1200 OR locator_label IS NULL")->fetchColumn(), 'All stored chunks are bounded and located');
    localSame(0, (int) $database->query("SELECT COUNT(*) FROM ai_embeddings WHERE dimension <> 384 OR vector_norm < 0.99 OR vector_norm > 1.01")->fetchColumn(), 'All stored vectors pass dimension and norm guards');

    $sourceVersion = (int) $result['source_version_id'];
    $resultAgain = $processor->process($resource1, 1);
    localSame($sourceVersion, (int) $resultAgain['source_version_id'], 'Unchanged file reuses current source version');
    localSame(1, localCount($database, 'ai_source_versions'), 'Idempotent rerun creates no duplicate source');
    localSame((int) $resultAgain['chunk_count'], localCount($database, 'ai_chunks'), 'Idempotent rerun replaces rather than duplicates chunks');
    localSame((int) $resultAgain['embedding_count'], localCount($database, 'ai_embeddings'), 'Idempotent rerun replaces rather than duplicates vectors');

    $resource2 = localInsertResource($database, $storage, 2, $text . ' Second resource.', $resourceIds);
    $lateAdapter = new D043FakeEmbeddingAdapter(function (int $request) use ($database): void {
        if ($request === 1) {
            $database->exec("UPDATE accounts SET account_status = 'disabled' WHERE id = 1");
        }
    });
    $lateProcessor = new GuardedLocalResourceProcessor($repository, $persistence, $gate, $extractor, $segmenter, $lateAdapter, $storage, true);
    localFailure(fn () => $lateProcessor->process($resource2, 1), 'processing_not_authorized', 'Late actor disable rejects embedding completion');
    $resource2Source = (int) $database->query('SELECT id FROM ai_source_versions WHERE resource_id = ' . $resource2)->fetchColumn();
    localSame(0, (int) $database->query('SELECT COUNT(*) FROM ai_embeddings AS e INNER JOIN ai_chunks AS c ON c.id = e.chunk_id WHERE c.source_version_id = ' . $resource2Source)->fetchColumn(), 'Late authorization loss persists no vectors');
    localSame('failed', (string) $database->query("SELECT processing_status FROM ai_processing_states WHERE source_version_id = {$resource2Source} AND capability = 'embedding'")->fetchColumn(), 'Late authorization loss records safe failed state only');
    localSame(0, localCount($database, 'ai_outputs'), 'Late failure creates no output or answer');

    if ($liveDatabase !== '') {
        $tableStatement->execute(['schema' => $liveDatabase]);
        localSame($liveTableCount, (int) $tableStatement->fetchColumn(), 'Live database remains at 22 tables');
        foreach ($liveCounts as $table => $before) {
            $after = (int) $server->query('SELECT COUNT(*) FROM ' . localIdentifier($liveDatabase) . '.' . localIdentifier($table))->fetchColumn();
            localSame($before, $after, 'Live ' . $table . ' remains unchanged');
        }
    }
} catch (Throwable $exception) {
    $failed = true;
    fwrite(STDERR, "\nD043 LOCAL PROCESSING CHECKPOINT FAILED: " . $exception->getMessage() . PHP_EOL);
} finally {
    $database = null;
    if ($createdDatabase && $server instanceof PDO) {
        try {
            $server->exec('DROP DATABASE ' . localIdentifier($databaseName));
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

fwrite(STDOUT, "\nD043 GUARDED LOCAL PROCESSING CHECKPOINT PASSED.\n");
fwrite(STDOUT, "Checks passed: {$checks}\n");
fwrite(STDOUT, "Real provider/model requests performed: 0\n");
fwrite(STDOUT, "Live database writes performed: 0\n");
fwrite(STDOUT, "Public AI route or UI added: No\n");
fwrite(STDOUT, "Next boundary: review before retrieval integration.\n");
