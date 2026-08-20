<?php

declare(strict_types=1);

use BpcLearnShare\Ai\AiFeatureGate;
use BpcLearnShare\Ai\AiPersistenceException;
use BpcLearnShare\Ai\AiPersistenceRepository;
use BpcLearnShare\Ai\GuardedAiPersistenceProcessor;
use BpcLearnShare\Core\Environment;

require dirname(__DIR__, 2) . '/src/bootstrap.php';

const AI_PERSISTENCE_DATABASE_PREFIX = 'bpc_learnshare_ai_persist_';

$checks = 0;

function aiPersistAssert(bool $condition, string $label): void
{
    global $checks;

    if (!$condition) {
        throw new RuntimeException($label . ' failed.');
    }

    $checks++;
    fwrite(STDOUT, '[PASS] ' . $label . PHP_EOL);
}

/** @param mixed $actual */
function aiPersistSame(mixed $expected, mixed $actual, string $label): void
{
    aiPersistAssert(
        $expected === $actual,
        sprintf(
            '%s (expected %s; received %s)',
            $label,
            var_export($expected, true),
            var_export($actual, true)
        )
    );
}

/** @param callable(): void $operation */
function aiPersistExpectFailure(
    callable $operation,
    string $reason,
    string $label
): void {
    try {
        $operation();
    } catch (AiPersistenceException $exception) {
        aiPersistSame($reason, $exception->reason, $label);

        return;
    }

    throw new RuntimeException($label . ' failed: operation was accepted.');
}

function aiPersistQuoteIdentifier(string $identifier): string
{
    if (preg_match('/\A[a-z0-9_]+\z/', $identifier) !== 1) {
        throw new RuntimeException('Unsafe disposable database name refused.');
    }

    return '`' . $identifier . '`';
}

/** @return list<string> */
function aiPersistSplitSql(string $sql): array
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

        if ($character === '-' && $next === '-') {
            $after = $index + 2 < $length ? $sql[$index + 2] : '';

            if ($after === '' || ctype_space($after)) {
                $lineComment = true;
                $index++;

                continue;
            }
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
            $statement = trim($buffer);

            if ($statement !== '') {
                $statements[] = $statement;
            }

            $buffer = '';

            continue;
        }

        $buffer .= $character;
    }

    $remainder = trim($buffer);

    if ($quote !== null || $blockComment || $remainder !== '') {
        if ($remainder !== '' && $quote === null && !$blockComment) {
            $statements[] = $remainder;
        } else {
            throw new RuntimeException('Canonical schema contains unterminated SQL.');
        }
    }

    return $statements;
}

function aiPersistImportSchema(PDO $database, string $path): int
{
    $sql = file_get_contents($path);

    if (!is_string($sql) || trim($sql) === '') {
        throw new RuntimeException('Canonical schema is missing.');
    }

    $statements = aiPersistSplitSql($sql);

    foreach ($statements as $statement) {
        $database->exec($statement);
    }

    return count($statements);
}

function aiPersistCount(PDO $database, string $table): int
{
    if (preg_match('/\A[a-z_]+\z/', $table) !== 1) {
        throw new RuntimeException('Unsafe table name refused.');
    }

    return (int) $database->query(
        'SELECT COUNT(*) FROM `' . $table . '`'
    )->fetchColumn();
}

function aiPersistState(
    PDO $database,
    int $sourceVersionId,
    string $capability
): array {
    $statement = $database->prepare(
        'SELECT *
         FROM ai_processing_states
         WHERE source_version_id = :source_version_id
           AND capability = :capability'
    );
    $statement->execute([
        'source_version_id' => $sourceVersionId,
        'capability' => $capability,
    ]);
    $row = $statement->fetch();

    if (!is_array($row)) {
        throw new RuntimeException('Expected processing state is missing.');
    }

    return $row;
}

$repositoryRoot = dirname(__DIR__, 2);
$schemaPath = $repositoryRoot . '/database/schema.sql';
$host = Environment::get(
    'D043_DB_HOST',
    Environment::get('DB_HOST', '127.0.0.1')
);
$port = Environment::getInt(
    'D043_DB_PORT',
    Environment::getInt('DB_PORT', 3306)
);
$adminUser = Environment::get('D043_DB_ADMIN_USER', 'root');
$adminPassword = Environment::get('D043_DB_ADMIN_PASS', '');
$liveDatabase = Environment::get('DB_NAME', '');
$databaseName = AI_PERSISTENCE_DATABASE_PREFIX . bin2hex(random_bytes(5));
$storageDirectory = sys_get_temp_dir()
    . DIRECTORY_SEPARATOR
    . 'bpc-learnshare-ai-persistence-'
    . bin2hex(random_bytes(5));
$server = null;
$database = null;
$createdDatabase = false;
$createdStorage = false;
$failed = false;

try {
    fwrite(STDOUT, "=== GUARDED AI PERSISTENCE CHECKPOINT ===\n");
    fwrite(STDOUT, "Live database: {$liveDatabase} (read-only boundary)\n");
    fwrite(STDOUT, "Disposable database: {$databaseName}\n");
    fwrite(STDOUT, "Provider/model requests: prohibited\n\n");

    aiPersistAssert(
        preg_match(
            '/\A' . AI_PERSISTENCE_DATABASE_PREFIX . '[0-9a-f]{10}\z/',
            $databaseName
        ) === 1,
        'Disposable database name guard'
    );
    aiPersistAssert(
        $liveDatabase === '' || $databaseName !== $liveDatabase,
        'Disposable database differs from live database'
    );

    $server = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $host, $port),
        $adminUser,
        $adminPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    aiPersistSame(
        '10.4.32-MariaDB',
        (string) $server->query('SELECT VERSION()')->fetchColumn(),
        'Exact MariaDB runtime'
    );

    $liveCountBefore = null;

    if ($liveDatabase !== '') {
        $liveStatement = $server->prepare(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = :schema_name
               AND table_type = \'BASE TABLE\''
        );
        $liveStatement->execute(['schema_name' => $liveDatabase]);
        $liveCountBefore = (int) $liveStatement->fetchColumn();
        aiPersistSame(22, $liveCountBefore, 'Live database starts at 22 tables');
    }

    $server->exec(
        'CREATE DATABASE ' . aiPersistQuoteIdentifier($databaseName)
        . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
    $createdDatabase = true;
    $database = new PDO(
        sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $databaseName
        ),
        $adminUser,
        $adminPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND =>
                "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,"
                . "ERROR_FOR_DIVISION_BY_ZERO,NO_ZERO_IN_DATE,"
                . "NO_ZERO_DATE,NO_ENGINE_SUBSTITUTION'",
        ]
    );
    aiPersistAssert(
        aiPersistImportSchema($database, $schemaPath) > 0,
        'Canonical 22-table schema imported'
    );

    foreach (
        [
            "INSERT INTO accounts
                (id, username, password_hash, display_name, role, account_status)
             VALUES
                (1, 'ai_persist_admin', 'not-a-login-hash', 'AI Test Admin', 'admin', 'active')",
            "INSERT INTO courses (id, name, is_active)
             VALUES (1, 'AI Persistence Test', 1)",
            "INSERT INTO subjects (id, name, is_active)
             VALUES (1, 'AI Persistence Test Subject', 1)",
            "INSERT INTO year_levels (id, name, is_active)
             VALUES (1, 'Test Level', 1)",
            "INSERT INTO resource_types (id, name, is_active)
             VALUES (1, 'Test Document', 1)",
            "INSERT INTO system_settings
                (setting_name, setting_value, updated_by_account_id)
             VALUES ('ai_enabled', 'disabled', 1)",
        ] as $seed
    ) {
        $database->exec($seed);
    }

    if (!mkdir($storageDirectory, 0700, true) && !is_dir($storageDirectory)) {
        throw new RuntimeException('Unable to create isolated test storage.');
    }

    $createdStorage = true;
    $contentA = 'Alpha source document.';
    $contentB = 'Bravo source document.';
    aiPersistSame(
        strlen($contentA),
        strlen($contentB),
        'Controlled source variants have equal byte size'
    );
    $storedFilename = hash('sha256', 'protected-test-filename') . '.txt';
    $filePath = $storageDirectory . DIRECTORY_SEPARATOR . $storedFilename;
    file_put_contents($filePath, $contentA, LOCK_EX);
    $fileSize = filesize($filePath);

    $resourceStatement = $database->prepare(
        "INSERT INTO resources (
            uploader_id,
            title,
            description,
            topic,
            course_id,
            subject_id,
            year_level_id,
            resource_type_id,
            status,
            original_filename,
            stored_filename,
            file_type,
            file_size,
            file_availability,
            ai_notice_acknowledged,
            ai_notice_acknowledged_at
         ) VALUES (
            1,
            'AI Persistence Test Resource',
            'Synthetic test-only resource.',
            'Provider-neutral persistence',
            1,
            1,
            1,
            1,
            'approved',
            'ai-persistence-test.txt',
            :stored_filename,
            'txt',
            :file_size,
            'available',
            1,
            CURRENT_TIMESTAMP
         )"
    );
    $resourceStatement->execute([
        'stored_filename' => $storedFilename,
        'file_size' => $fileSize,
    ]);
    $resourceId = (int) $database->lastInsertId();
    $persistenceRepository = new AiPersistenceRepository($database);
    $processor = new GuardedAiPersistenceProcessor(
        $database,
        $persistenceRepository,
        new AiFeatureGate($database),
        $storageDirectory
    );

    aiPersistExpectFailure(
        static fn () => $processor->synchronizeCurrentSource(
            $resourceId,
            'text/plain'
        ),
        'ai_disabled',
        'Disabled feature fails closed before source persistence'
    );
    aiPersistSame(
        0,
        aiPersistCount($database, 'ai_source_versions'),
        'Disabled feature writes no source version'
    );

    $database->exec(
        "UPDATE system_settings
         SET setting_value = 'enabled'
         WHERE setting_name = 'ai_enabled'"
    );
    $sourceVersion1 = $processor->synchronizeCurrentSource(
        $resourceId,
        'text/plain'
    );
    aiPersistAssert($sourceVersion1 > 0, 'Current source version persisted');
    aiPersistSame(
        $sourceVersion1,
        $processor->synchronizeCurrentSource($resourceId, 'text/plain'),
        'Identical protected file reuses current source version'
    );
    aiPersistSame(
        1,
        aiPersistCount($database, 'ai_source_versions'),
        'Idempotent source synchronization creates one row'
    );

    $extractToken = $processor->queueRun(
        $sourceVersion1,
        'extraction',
        'EXTRACT-TEST-001',
        hash('sha256', 'extraction-dependencies')
    );
    aiPersistAssert(
        preg_match('/\A[a-f0-9]{64}\z/', $extractToken) === 1,
        'Queue creates opaque run token'
    );
    aiPersistSame(
        'queued',
        (string) aiPersistState(
            $database,
            $sourceVersion1,
            'extraction'
        )['processing_status'],
        'Extraction state queued'
    );
    $processor->startRun($sourceVersion1, 'extraction', $extractToken);
    aiPersistSame(
        'processing',
        (string) aiPersistState(
            $database,
            $sourceVersion1,
            'extraction'
        )['processing_status'],
        'Extraction state processing'
    );
    $extractedText = "First verified passage.\nSecond verified passage.";
    $processor->completeExtraction(
        $sourceVersion1,
        $extractToken,
        $extractedText
    );
    $sourceRow = $persistenceRepository->findSource($sourceVersion1);
    aiPersistSame(
        hash('sha256', $extractedText),
        (string) $sourceRow['extracted_text_sha256'],
        'Extraction stores exact text hash'
    );
    aiPersistSame(
        'ready',
        (string) aiPersistState(
            $database,
            $sourceVersion1,
            'extraction'
        )['processing_status'],
        'Complete extraction becomes ready'
    );

    $staleSegmentationToken = $processor->queueRun(
        $sourceVersion1,
        'segmentation',
        'SEG-TEST-001',
        hash('sha256', 'segmentation-dependencies-v1')
    );
    $processor->startRun(
        $sourceVersion1,
        'segmentation',
        $staleSegmentationToken
    );
    $currentSegmentationToken = $processor->queueRun(
        $sourceVersion1,
        'segmentation',
        'SEG-TEST-002',
        hash('sha256', 'segmentation-dependencies-v2')
    );
    aiPersistExpectFailure(
        static fn () => $processor->completeSegmentation(
            $sourceVersion1,
            $staleSegmentationToken,
            [[
                'text' => 'Late chunk result.',
                'locator_kind' => 'unavailable',
            ]]
        ),
        'run_token_mismatch',
        'Superseded segmentation result rejected'
    );
    aiPersistSame(
        0,
        aiPersistCount($database, 'ai_chunks'),
        'Late result writes no chunks'
    );
    $processor->startRun(
        $sourceVersion1,
        'segmentation',
        $currentSegmentationToken
    );
    $chunks = [
        [
            'text' => 'First verified passage.',
            'locator_kind' => 'page',
            'start_locator' => 'Page 1',
            'end_locator' => 'Page 1',
            'locator_label' => 'Page 1',
        ],
        [
            'text' => 'Second passage without a reliable locator.',
            'locator_kind' => 'unavailable',
        ],
    ];
    $processor->completeSegmentation(
        $sourceVersion1,
        $currentSegmentationToken,
        $chunks
    );
    aiPersistSame(2, aiPersistCount($database, 'ai_chunks'), 'Two chunks stored');
    aiPersistSame(
        'ready',
        (string) aiPersistState(
            $database,
            $sourceVersion1,
            'segmentation'
        )['processing_status'],
        'Complete segmentation becomes ready'
    );

    $embeddingToken = $processor->queueRun(
        $sourceVersion1,
        'embedding',
        'EMB-TEST-001',
        hash('sha256', 'embedding-dependencies')
    );
    $processor->startRun($sourceVersion1, 'embedding', $embeddingToken);
    aiPersistExpectFailure(
        static fn () => $processor->completeEmbedding(
            $sourceVersion1,
            $embeddingToken,
            [[
                'chunk_index' => 1,
                'model_reference' => 'synthetic/no-model-call',
                'model_digest' => hash('sha256', 'synthetic-model'),
                'vector' => [1.0, 0.0, 0.0],
            ]]
        ),
        'partial_embedding_result',
        'Partial embedding result rejected'
    );
    aiPersistSame(
        0,
        aiPersistCount($database, 'ai_embeddings'),
        'Partial result writes no embedding'
    );
    aiPersistSame(
        'processing',
        (string) aiPersistState(
            $database,
            $sourceVersion1,
            'embedding'
        )['processing_status'],
        'Partial result never becomes ready'
    );
    $modelDigest = hash('sha256', 'synthetic-model');
    $processor->completeEmbedding(
        $sourceVersion1,
        $embeddingToken,
        [
            [
                'chunk_index' => 1,
                'model_reference' => 'synthetic/no-model-call',
                'model_digest' => $modelDigest,
                'vector' => [1.0, 0.0, 0.0],
            ],
            [
                'chunk_index' => 2,
                'model_reference' => 'synthetic/no-model-call',
                'model_digest' => $modelDigest,
                'vector' => [0.0, 1.0, 0.0],
            ],
        ]
    );
    aiPersistSame(
        2,
        aiPersistCount($database, 'ai_embeddings'),
        'Complete normalized embedding set stored'
    );
    aiPersistSame(
        'ready',
        (string) aiPersistState(
            $database,
            $sourceVersion1,
            'embedding'
        )['processing_status'],
        'Complete embedding set becomes ready'
    );

    $resegmentToken = $processor->queueRun(
        $sourceVersion1,
        'segmentation',
        'SEG-TEST-003',
        hash('sha256', 'segmentation-dependencies-v3')
    );
    $processor->startRun(
        $sourceVersion1,
        'segmentation',
        $resegmentToken
    );
    $processor->completeSegmentation(
        $sourceVersion1,
        $resegmentToken,
        $chunks
    );
    aiPersistSame(
        0,
        aiPersistCount($database, 'ai_embeddings'),
        'Re-segmentation removes embeddings through chunk replacement'
    );
    aiPersistSame(
        'stale',
        (string) aiPersistState(
            $database,
            $sourceVersion1,
            'embedding'
        )['processing_status'],
        'Re-segmentation explicitly stales embedding readiness'
    );

    $summaryToken = $processor->queueRun(
        $sourceVersion1,
        'summary',
        'SUMMARY-TEST-001',
        hash('sha256', 'summary-dependencies')
    );
    $processor->startRun($sourceVersion1, 'summary', $summaryToken);
    $processor->completeOutput(
        $sourceVersion1,
        'summary',
        $summaryToken,
        'Synthetic grounded summary; no model was called.',
        'draft',
        'PROMPT-SUMMARY-001'
    );
    $output = $database->query(
        "SELECT * FROM ai_outputs WHERE output_type = 'summary'"
    )->fetch();
    aiPersistSame(
        $sourceVersion1,
        (int) $output['source_version_id'],
        'Stored output bound to exact source version'
    );
    aiPersistSame(
        'SUMMARY-TEST-001',
        (string) $output['candidate_configuration_id'],
        'Stored output records configuration identity'
    );

    $failedToken = $processor->queueRun(
        $sourceVersion1,
        'suggested_tags',
        'TAGS-TEST-001',
        hash('sha256', 'tag-dependencies')
    );
    $processor->startRun($sourceVersion1, 'suggested_tags', $failedToken);
    $processor->failRun(
        $sourceVersion1,
        'suggested_tags',
        $failedToken,
        'adapter_unavailable',
        'Synthetic safe failure without prompt or response content.'
    );
    $failedState = aiPersistState(
        $database,
        $sourceVersion1,
        'suggested_tags'
    );
    aiPersistSame(
        'failed',
        (string) $failedState['processing_status'],
        'Capability failure stored without affecting other capabilities'
    );
    aiPersistSame(
        'adapter_unavailable',
        (string) $failedState['last_error_code'],
        'Safe error code stored'
    );

    file_put_contents($filePath, $contentB, LOCK_EX);
    clearstatcache(true, $filePath);
    $sourceVersion2 = $processor->synchronizeCurrentSource(
        $resourceId,
        'text/plain'
    );
    aiPersistAssert(
        $sourceVersion2 !== $sourceVersion1,
        'Changed protected file creates a new source version'
    );
    $source1After = $persistenceRepository->findSource($sourceVersion1);
    aiPersistSame(
        'stale',
        (string) $source1After['lifecycle_state'],
        'Old source version becomes stale'
    );
    aiPersistSame(
        'invalidated',
        (string) $database->query(
            "SELECT lifecycle_state FROM ai_outputs WHERE output_type = 'summary'"
        )->fetchColumn(),
        'Source change invalidates current output'
    );
    aiPersistSame(
        'stale',
        (string) aiPersistState(
            $database,
            $sourceVersion1,
            'embedding'
        )['processing_status'],
        'Source change stales dependent readiness'
    );
    aiPersistExpectFailure(
        static fn () => $processor->queueRun(
            $sourceVersion1,
            'embedding',
            'EMB-TEST-LATE',
            hash('sha256', 'late-dependencies')
        ),
        'source_file_changed',
        'Old source cannot start a new run'
    );

    $database->exec(
        "UPDATE system_settings
         SET setting_value = 'disabled'
         WHERE setting_name = 'ai_enabled'"
    );
    aiPersistExpectFailure(
        static fn () => $processor->queueRun(
            $sourceVersion2,
            'extraction',
            'EXTRACT-DISABLED',
            hash('sha256', 'disabled-dependencies')
        ),
        'ai_disabled',
        'Disabled feature blocks new processing after persistence exists'
    );

    $database->exec(
        "UPDATE resources SET status = 'hidden' WHERE id = {$resourceId}"
    );
    aiPersistExpectFailure(
        static fn () => $processor->cleanIneligibleResource($resourceId),
        'cleanup_not_authorized',
        'Hidden resource is excluded live without destructive cleanup'
    );
    $database->exec(
        "UPDATE resources SET status = 'rejected' WHERE id = {$resourceId}"
    );
    $processor->cleanIneligibleResource($resourceId);
    $source2After = $persistenceRepository->findSource($sourceVersion2);
    aiPersistSame(
        'invalidated',
        (string) $source2After['lifecycle_state'],
        'Rejected resource invalidates current source tree while AI disabled'
    );

    $database->exec(
        "UPDATE resources SET status = 'approved' WHERE id = {$resourceId}"
    );
    $database->exec(
        "UPDATE system_settings
         SET setting_value = 'enabled'
         WHERE setting_name = 'ai_enabled'"
    );
    $sourceVersion3 = $processor->synchronizeCurrentSource(
        $resourceId,
        'text/plain'
    );
    aiPersistAssert(
        $sourceVersion3 !== $sourceVersion2,
        'Byte-identical invalidated source receives a new monotonic version'
    );
    $reactivated = $persistenceRepository->findSource($sourceVersion3);
    aiPersistSame(
        'current',
        (string) $reactivated['lifecycle_state'],
        'Reprocessed source becomes current'
    );
    aiPersistSame(
        3,
        (int) $reactivated['source_version_number'],
        'Reprocessed source version number remains monotonic'
    );
    $stateCount = (int) $database->query(
        "SELECT COUNT(*)
         FROM ai_processing_states
         WHERE source_version_id = {$sourceVersion3}"
    )->fetchColumn();
    aiPersistSame(
        0,
        $stateCount,
        'New source retains no old readiness or run token'
    );

    $database->exec(
        "UPDATE resources
         SET status = 'removed', file_availability = 'deleted'
         WHERE id = {$resourceId}"
    );
    $processor->cleanIneligibleResource($resourceId);
    aiPersistSame(
        0,
        aiPersistCount($database, 'ai_source_versions'),
        'Removed cleanup deletes source versions'
    );
    aiPersistSame(
        0,
        aiPersistCount($database, 'ai_chunks'),
        'Removed cleanup deletes chunks through source cascade'
    );
    aiPersistSame(
        0,
        aiPersistCount($database, 'ai_embeddings'),
        'Removed cleanup deletes embeddings through source cascade'
    );
    aiPersistSame(
        0,
        aiPersistCount($database, 'ai_outputs'),
        'Removed cleanup deletes content-bearing AI output'
    );
    aiPersistSame(1, aiPersistCount($database, 'resources'), 'Core resource row preserved');

    if ($liveDatabase !== '') {
        $liveStatement->execute(['schema_name' => $liveDatabase]);
        aiPersistSame(
            $liveCountBefore,
            (int) $liveStatement->fetchColumn(),
            'Live configured database table count unchanged'
        );
    }
} catch (Throwable $exception) {
    $failed = true;
    fwrite(STDERR, "\nAI PERSISTENCE CHECKPOINT FAILED: ");
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
} finally {
    $database = null;

    if ($createdDatabase && $server instanceof PDO) {
        try {
            $server->exec(
                'DROP DATABASE ' . aiPersistQuoteIdentifier($databaseName)
            );
            fwrite(STDOUT, "Disposable database removed: {$databaseName}\n");
        } catch (Throwable $cleanupException) {
            $failed = true;
            fwrite(STDERR, 'Disposable database cleanup failed: ');
            fwrite(STDERR, $cleanupException->getMessage() . PHP_EOL);
        }
    }

    if ($createdStorage) {
        if (is_file($filePath ?? '')) {
            unlink($filePath);
        }

        if (is_dir($storageDirectory) && !rmdir($storageDirectory)) {
            $failed = true;
            fwrite(STDERR, "Isolated test storage cleanup failed.\n");
        }
    }
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "\nGUARDED AI PERSISTENCE CHECKPOINT PASSED.\n");
fwrite(STDOUT, "Checks passed: {$checks}\n");
fwrite(STDOUT, "Provider/model requests performed: 0\n");
fwrite(STDOUT, "Live database writes performed: 0\n");
fwrite(STDOUT, "AI route or UI added: No\n");
fwrite(STDOUT, "Next boundary: review before semantic integration.\n");
