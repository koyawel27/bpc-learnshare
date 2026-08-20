<?php

declare(strict_types=1);

use BpcLearnShare\Core\Environment;

require dirname(__DIR__, 2) . '/src/bootstrap.php';

const D043_DATABASE_PREFIX = 'bpc_learnshare_d043_verify_';

/** @var int $d043Checks */
$d043Checks = 0;

function d043Assert(bool $condition, string $label): void
{
    global $d043Checks;

    if (!$condition) {
        throw new RuntimeException($label . ' failed.');
    }

    $d043Checks++;
    fwrite(STDOUT, "[PASS] {$label}\n");
}

/** @param mixed $actual */
function d043AssertSame(mixed $expected, mixed $actual, string $label): void
{
    d043Assert(
        $actual === $expected,
        sprintf(
            '%s (expected %s; received %s)',
            $label,
            var_export($expected, true),
            var_export($actual, true)
        )
    );
}

function d043QuoteIdentifier(string $identifier): string
{
    if (!preg_match('/^[a-z0-9_]+$/', $identifier)) {
        throw new RuntimeException('Unsafe database identifier refused.');
    }

    return '`' . $identifier . '`';
}

/** @return list<string> */
function d043SplitSql(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $quote = null;
    $lineComment = false;
    $blockComment = false;

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

    if ($quote !== null || $blockComment) {
        throw new RuntimeException('Unterminated SQL quote or block comment.');
    }

    $remainder = trim($buffer);

    if ($remainder !== '') {
        $statements[] = $remainder;
    }

    return $statements;
}

function d043ExecuteSqlFile(PDO $database, string $path): int
{
    $sql = file_get_contents($path);

    if (!is_string($sql) || trim($sql) === '') {
        throw new RuntimeException('SQL file is missing or empty: ' . $path);
    }

    $statements = d043SplitSql($sql);

    foreach ($statements as $position => $statement) {
        try {
            $database->exec($statement);
        } catch (Throwable $exception) {
            throw new RuntimeException(sprintf(
                'SQL statement %d from %s failed: %s',
                $position + 1,
                basename($path),
                $exception->getMessage()
            ), 0, $exception);
        }
    }

    return count($statements);
}

/** @return list<string> */
function d043TableNames(PDO $database): array
{
    $names = $database->query(
        "SELECT table_name
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_type = 'BASE TABLE'
         ORDER BY table_name"
    )->fetchAll(PDO::FETCH_COLUMN);

    return array_map('strval', $names);
}

/** @param list<string> $expected */
function d043AssertTables(PDO $database, array $expected, string $label): void
{
    sort($expected);
    $actual = d043TableNames($database);
    sort($actual);

    d043AssertSame($expected, $actual, $label);
}

function d043ConstraintExists(
    PDO $database,
    string $table,
    string $constraint,
    string $type
): bool {
    $statement = $database->prepare(
        'SELECT COUNT(*)
         FROM information_schema.table_constraints
         WHERE constraint_schema = DATABASE()
           AND table_name = :table_name
           AND constraint_name = :constraint_name
           AND constraint_type = :constraint_type'
    );
    $statement->execute([
        'table_name' => $table,
        'constraint_name' => $constraint,
        'constraint_type' => $type,
    ]);

    return (int) $statement->fetchColumn() === 1;
}

function d043ColumnExists(PDO $database, string $table, string $column): bool
{
    $statement = $database->prepare(
        'SELECT COUNT(*)
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = :table_name
           AND column_name = :column_name'
    );
    $statement->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);

    return (int) $statement->fetchColumn() === 1;
}

/** @param callable(): void $operation */
function d043ExpectDatabaseFailure(callable $operation, string $label): void
{
    try {
        $operation();
    } catch (PDOException) {
        d043Assert(true, $label);

        return;
    }

    throw new RuntimeException($label . ' failed: database accepted invalid data.');
}

function d043InsertResource(PDO $database, string $suffix): int
{
    $statement = $database->prepare(
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
            :title,
            'Disposable D043 verification resource.',
            'D043 migration verification',
            1,
            1,
            1,
            1,
            'approved',
            :original_filename,
            :stored_filename,
            'txt',
            128,
            'available',
            1,
            CURRENT_TIMESTAMP
         )"
    );
    $statement->execute([
        'title' => 'D043 Test Resource ' . $suffix,
        'original_filename' => 'd043-' . $suffix . '.txt',
        'stored_filename' => 'd043-' . $suffix . '-stored.txt',
    ]);

    return (int) $database->lastInsertId();
}

$repository = dirname(__DIR__, 2);
$schemaPath = $repository . '/database/schema.sql';
$upPath = $repository
    . '/database/migrations/20260820_d043_ai_persistence_up.sql';
$downPath = $repository
    . '/database/migrations/20260820_d043_ai_persistence_down.sql';
$schemaHashBefore = hash_file('sha256', $schemaPath);

if (!is_string($schemaHashBefore)) {
    fwrite(STDERR, "Unable to hash database/schema.sql.\n");
    exit(1);
}

$host = Environment::get('D043_DB_HOST', Environment::get('DB_HOST', '127.0.0.1'));
$port = Environment::getInt('D043_DB_PORT', Environment::getInt('DB_PORT', 3306));
$adminUser = Environment::get('D043_DB_ADMIN_USER', 'root');
$adminPassword = Environment::get('D043_DB_ADMIN_PASS', '');
$liveDatabase = Environment::get('DB_NAME', '');
$disposableDatabase = D043_DATABASE_PREFIX . bin2hex(random_bytes(5));
$server = null;
$database = null;
$created = false;
$failed = false;

$baselineTables = [
    'accounts',
    'ai_outputs',
    'audit_log',
    'bookmarks',
    'courses',
    'helpful_marks',
    'notifications',
    'open_replacement_tracking',
    'open_report_tracking',
    'reports',
    'resource_action_history',
    'resource_tags',
    'resource_types',
    'resources',
    'subjects',
    'system_settings',
    'tags',
    'year_levels',
];
$migratedTables = array_merge($baselineTables, [
    'ai_chunks',
    'ai_embeddings',
    'ai_processing_states',
    'ai_source_versions',
]);

try {
    fwrite(STDOUT, "=== D043 DISPOSABLE MIGRATION VERIFICATION ===\n");
    fwrite(STDOUT, "Live configured database: {$liveDatabase} (read-only boundary)\n");
    fwrite(STDOUT, "Disposable database: {$disposableDatabase}\n");
    fwrite(STDOUT, "Administrative password: not displayed\n\n");

    d043Assert(
        preg_match('/^' . D043_DATABASE_PREFIX . '[0-9a-f]{10}$/', $disposableDatabase) === 1,
        'Disposable database name guard'
    );
    d043Assert(
        $liveDatabase === '' || $disposableDatabase !== $liveDatabase,
        'Disposable database differs from configured live database'
    );
    d043Assert(is_file($schemaPath), 'Canonical schema file present');
    d043Assert(is_file($upPath), 'Forward migration file present');
    d043Assert(is_file($downPath), 'Rollback migration file present');

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

    $serverVersion = (string) $server->query('SELECT VERSION()')->fetchColumn();
    d043AssertSame(
        '10.4.32-MariaDB',
        $serverVersion,
        'Exact MariaDB verification runtime'
    );

    $liveTableCountBefore = null;

    if ($liveDatabase !== '') {
        $liveCountStatement = $server->prepare(
            "SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = :schema_name
               AND table_type = 'BASE TABLE'"
        );
        $liveCountStatement->execute(['schema_name' => $liveDatabase]);
        $liveTableCountBefore = (int) $liveCountStatement->fetchColumn();
        d043AssertSame(22, $liveTableCountBefore, 'Configured live database remains at D043 target before test');
    }

    $server->exec(
        'CREATE DATABASE ' . d043QuoteIdentifier($disposableDatabase)
        . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );
    $created = true;

    $database = new PDO(
        sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $disposableDatabase
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
    d043AssertSame(
        $disposableDatabase,
        (string) $database->query('SELECT DATABASE()')->fetchColumn(),
        'All test writes target the disposable database'
    );

    $schemaStatements = d043ExecuteSqlFile($database, $schemaPath);
    d043Assert($schemaStatements >= 22, 'Canonical schema statements executed');
    d043AssertTables($database, $migratedTables, 'Fresh canonical schema has exact 22-table set');

    foreach ([
        'source_version_id',
        'candidate_configuration_id',
        'prompt_template_version',
    ] as $column) {
        d043Assert(
            d043ColumnExists($database, 'ai_outputs', $column),
            'Fresh canonical ai_outputs column present: ' . $column
        );
    }

    $baselineDownStatements = d043ExecuteSqlFile($database, $downPath);
    d043AssertSame(8, $baselineDownStatements, 'Canonical-to-baseline rollback statement count');
    d043AssertTables($database, $baselineTables, 'Canonical rollback creates exact 18-table legacy baseline');

    foreach ([
        'source_version_id',
        'candidate_configuration_id',
        'prompt_template_version',
    ] as $column) {
        d043Assert(
            !d043ColumnExists($database, 'ai_outputs', $column),
            'Canonical rollback removed ai_outputs column: ' . $column
        );
    }

    d043Assert(
        d043ConstraintExists(
            $database,
            'ai_outputs',
            'chk_ai_outputs_content_state',
            'CHECK'
        ),
        'Canonical rollback restored legacy ai_outputs content-state constraint'
    );

    $database->exec(
        "INSERT INTO accounts
            (id, username, password_hash, display_name, role)
         VALUES
            (1, 'd043_verifier', 'not-a-real-login-hash', 'D043 Verifier', 'admin')"
    );
    $database->exec("INSERT INTO courses (id, name) VALUES (1, 'D043 Course')");
    $database->exec("INSERT INTO subjects (id, name) VALUES (1, 'D043 Subject')");
    $database->exec("INSERT INTO year_levels (id, name) VALUES (1, 'D043 Year')");
    $database->exec("INSERT INTO resource_types (id, name) VALUES (1, 'D043 Type')");

    $resourceOne = d043InsertResource($database, 'one');
    $resourceTwo = d043InsertResource($database, 'two');
    d043Assert($resourceOne > 0 && $resourceTwo > $resourceOne, 'Controlled baseline resources inserted');

    $database->exec(
        "INSERT INTO ai_outputs (
            resource_id,
            output_type,
            content,
            lifecycle_state,
            source_file_reference
         ) VALUES (
            {$resourceOne},
            'summary',
            'Legacy output that cannot prove a source version.',
            'draft',
            'd043-one-stored.txt'
         )"
    );
    $legacyOutputId = (int) $database->lastInsertId();
    d043Assert($legacyOutputId > 0, 'Legacy active AI output inserted before migration');

    $upStatements = d043ExecuteSqlFile($database, $upPath);
    d043AssertSame(8, $upStatements, 'Forward migration statement count');
    d043AssertTables($database, $migratedTables, 'Forward migration has exact 22-table set');

    foreach ([
        'source_version_id',
        'candidate_configuration_id',
        'prompt_template_version',
    ] as $column) {
        d043Assert(
            d043ColumnExists($database, 'ai_outputs', $column),
            'ai_outputs column present: ' . $column
        );
    }

    foreach ([
        ['ai_source_versions', 'fk_ai_source_versions_resource', 'FOREIGN KEY'],
        ['ai_source_versions', 'chk_ai_source_versions_current_marker', 'CHECK'],
        ['ai_processing_states', 'fk_ai_processing_source_version', 'FOREIGN KEY'],
        ['ai_chunks', 'fk_ai_chunks_source_version', 'FOREIGN KEY'],
        ['ai_embeddings', 'fk_ai_embeddings_chunk', 'FOREIGN KEY'],
        ['ai_outputs', 'fk_ai_outputs_source_resource', 'FOREIGN KEY'],
        ['ai_outputs', 'chk_ai_outputs_content_state', 'CHECK'],
    ] as [$table, $constraint, $type]) {
        d043Assert(
            d043ConstraintExists($database, $table, $constraint, $type),
            'Constraint present: ' . $constraint
        );
    }

    $legacyStatement = $database->prepare(
        'SELECT lifecycle_state, source_version_id,
                candidate_configuration_id, prompt_template_version
         FROM ai_outputs
         WHERE id = :id'
    );
    $legacyStatement->execute(['id' => $legacyOutputId]);
    $legacyRow = $legacyStatement->fetch();
    d043Assert(is_array($legacyRow), 'Legacy AI output preserved');
    d043AssertSame('invalidated', $legacyRow['lifecycle_state'], 'Legacy active AI output fails closed');
    d043AssertSame(null, $legacyRow['source_version_id'], 'Legacy output is not silently source-bound');

    $sourceText = 'Verified extracted text for D043.';
    $sourceInsert = $database->prepare(
        "INSERT INTO ai_source_versions (
            resource_id,
            source_version_number,
            source_sha256,
            stored_filename,
            file_size,
            detected_mime_type,
            extracted_text,
            extracted_text_sha256,
            lifecycle_state,
            current_marker
         ) VALUES (
            :resource_id,
            1,
            :source_hash,
            :stored_filename,
            128,
            'text/plain',
            :extracted_text,
            :text_hash,
            'current',
            1
         )"
    );
    $sourceInsert->execute([
        'resource_id' => $resourceOne,
        'source_hash' => hash('sha256', 'resource-one-file-bytes'),
        'stored_filename' => 'd043-one-stored.txt',
        'extracted_text' => $sourceText,
        'text_hash' => hash('sha256', $sourceText),
    ]);
    $sourceVersionOne = (int) $database->lastInsertId();
    d043Assert($sourceVersionOne > 0, 'Current source version inserted');

    d043ExpectDatabaseFailure(
        static function () use ($database, $resourceOne): void {
            $statement = $database->prepare(
                "INSERT INTO ai_source_versions (
                    resource_id,
                    source_version_number,
                    source_sha256,
                    stored_filename,
                    file_size,
                    detected_mime_type,
                    lifecycle_state,
                    current_marker
                 ) VALUES (
                    :resource_id,
                    2,
                    :source_hash,
                    'second-current.txt',
                    128,
                    'text/plain',
                    'current',
                    1
                 )"
            );
            $statement->execute([
                'resource_id' => $resourceOne,
                'source_hash' => hash('sha256', 'second-current-file'),
            ]);
        },
        'Second current source version rejected'
    );

    $sourceInsert->execute([
        'resource_id' => $resourceTwo,
        'source_hash' => hash('sha256', 'resource-two-file-bytes'),
        'stored_filename' => 'd043-two-stored.txt',
        'extracted_text' => $sourceText,
        'text_hash' => hash('sha256', $sourceText),
    ]);
    $sourceVersionTwo = (int) $database->lastInsertId();
    d043Assert($sourceVersionTwo > $sourceVersionOne, 'Independent resource source version inserted');

    $database->prepare(
        "INSERT INTO ai_processing_states (
            source_version_id,
            capability,
            processing_status,
            candidate_configuration_id,
            dependency_fingerprint,
            run_token,
            attempt_count,
            completed_at
         ) VALUES (
            :source_version_id,
            'embedding',
            'ready',
            'EMB-D043-VERIFY-001',
            :dependency_fingerprint,
            'd043-run-token-001',
            1,
            CURRENT_TIMESTAMP
         )"
    )->execute([
        'source_version_id' => $sourceVersionOne,
        'dependency_fingerprint' => hash('sha256', 'embedding-dependencies'),
    ]);
    d043Assert(true, 'Ready processing state inserted with configuration identity');

    d043ExpectDatabaseFailure(
        static function () use ($database, $sourceVersionOne): void {
            $database->exec(
                "INSERT INTO ai_processing_states (
                    source_version_id,
                    capability,
                    processing_status,
                    dependency_fingerprint,
                    run_token,
                    completed_at
                 ) VALUES (
                    {$sourceVersionOne},
                    'summary',
                    'ready',
                    '" . hash('sha256', 'summary-dependencies') . "',
                    'd043-run-token-002',
                    CURRENT_TIMESTAMP
                 )"
            );
        },
        'Ready state without candidate configuration rejected'
    );

    $chunkText = 'A verified D043 chunk.';
    $chunkStatement = $database->prepare(
        "INSERT INTO ai_chunks (
            source_version_id,
            chunk_index,
            chunk_text,
            text_sha256,
            character_count,
            segmentation_configuration_id,
            locator_kind,
            start_locator,
            end_locator,
            locator_label
         ) VALUES (
            :source_version_id,
            1,
            :chunk_text,
            :text_hash,
            :character_count,
            'SEG-D043-VERIFY-001',
            'page',
            'Page 1',
            'Page 1',
            'Page 1'
         )"
    );
    $chunkStatement->execute([
        'source_version_id' => $sourceVersionOne,
        'chunk_text' => $chunkText,
        'text_hash' => hash('sha256', $chunkText),
        'character_count' => strlen($chunkText),
    ]);
    $chunkId = (int) $database->lastInsertId();
    d043Assert($chunkId > 0, 'Verified-locator chunk inserted');

    d043ExpectDatabaseFailure(
        static function () use ($database, $sourceVersionOne): void {
            $text = 'Missing locator chunk.';
            $statement = $database->prepare(
                "INSERT INTO ai_chunks (
                    source_version_id,
                    chunk_index,
                    chunk_text,
                    text_sha256,
                    character_count,
                    segmentation_configuration_id,
                    locator_kind
                 ) VALUES (
                    :source_version_id,
                    2,
                    :chunk_text,
                    :text_hash,
                    :character_count,
                    'SEG-D043-VERIFY-001',
                    'page'
                 )"
            );
            $statement->execute([
                'source_version_id' => $sourceVersionOne,
                'chunk_text' => $text,
                'text_hash' => hash('sha256', $text),
                'character_count' => strlen($text),
            ]);
        },
        'Fabricated or missing verified locator rejected'
    );

    $vectorJson = '[1,0,0]';
    $embeddingStatement = $database->prepare(
        "INSERT INTO ai_embeddings (
            chunk_id,
            candidate_configuration_id,
            model_reference,
            model_digest,
            dimension,
            vector_json,
            vector_norm,
            vector_sha256
         ) VALUES (
            :chunk_id,
            'EMB-D043-VERIFY-001',
            'synthetic-verification-model',
            :model_digest,
            3,
            :vector_json,
            1.000000000000,
            :vector_hash
         )"
    );
    $embeddingStatement->execute([
        'chunk_id' => $chunkId,
        'model_digest' => hash('sha256', 'synthetic-model'),
        'vector_json' => $vectorJson,
        'vector_hash' => hash('sha256', $vectorJson),
    ]);
    d043Assert((int) $database->lastInsertId() > 0, 'Dimension-matched normalized embedding inserted');

    d043ExpectDatabaseFailure(
        static function () use ($database, $chunkId): void {
            $vector = '[1,0,0]';
            $statement = $database->prepare(
                "INSERT INTO ai_embeddings (
                    chunk_id,
                    candidate_configuration_id,
                    model_reference,
                    dimension,
                    vector_json,
                    vector_norm,
                    vector_sha256
                 ) VALUES (
                    :chunk_id,
                    'EMB-D043-INVALID-DIMENSION',
                    'synthetic-verification-model',
                    2,
                    :vector_json,
                    1.000000000000,
                    :vector_hash
                 )"
            );
            $statement->execute([
                'chunk_id' => $chunkId,
                'vector_json' => $vector,
                'vector_hash' => hash('sha256', $vector),
            ]);
        },
        'Embedding dimension mismatch rejected'
    );

    $database->prepare(
        "UPDATE ai_outputs
         SET lifecycle_state = 'retained',
             source_version_id = :source_version_id,
             candidate_configuration_id = 'GEN-D043-VERIFY-001',
             prompt_template_version = 'summary-v1'
         WHERE id = :id"
    )->execute([
        'source_version_id' => $sourceVersionOne,
        'id' => $legacyOutputId,
    ]);
    d043Assert(true, 'Source-bound current AI output accepted');

    d043ExpectDatabaseFailure(
        static function () use ($database, $resourceOne, $sourceVersionTwo): void {
            $database->exec(
                "INSERT INTO ai_outputs (
                    resource_id,
                    source_version_id,
                    output_type,
                    content,
                    lifecycle_state,
                    source_file_reference,
                    candidate_configuration_id,
                    prompt_template_version
                 ) VALUES (
                    {$resourceOne},
                    {$sourceVersionTwo},
                    'suggested_tags',
                    '[]',
                    'draft',
                    'd043-one-stored.txt',
                    'GEN-D043-VERIFY-001',
                    'tags-v1'
                 )"
            );
        },
        'Cross-resource source-version binding rejected'
    );

    d043ExpectDatabaseFailure(
        static function () use ($database, $resourceOne, $sourceVersionOne): void {
            $database->exec(
                "INSERT INTO ai_outputs (
                    resource_id,
                    source_version_id,
                    output_type,
                    content,
                    lifecycle_state,
                    source_file_reference,
                    candidate_configuration_id
                 ) VALUES (
                    {$resourceOne},
                    {$sourceVersionOne},
                    'suggested_metadata',
                    '{}',
                    'draft',
                    'd043-one-stored.txt',
                    'GEN-D043-VERIFY-001'
                 )"
            );
        },
        'Active AI output without prompt version rejected'
    );

    $accountCountBeforeRollback = (int) $database
        ->query('SELECT COUNT(*) FROM accounts')->fetchColumn();
    $resourceCountBeforeRollback = (int) $database
        ->query('SELECT COUNT(*) FROM resources')->fetchColumn();
    $outputCountBeforeRollback = (int) $database
        ->query('SELECT COUNT(*) FROM ai_outputs')->fetchColumn();

    $downStatements = d043ExecuteSqlFile($database, $downPath);
    d043AssertSame(8, $downStatements, 'Rollback migration statement count');
    d043AssertTables($database, $baselineTables, 'Rollback restores exact 18-table set');

    foreach ([
        'source_version_id',
        'candidate_configuration_id',
        'prompt_template_version',
    ] as $column) {
        d043Assert(
            !d043ColumnExists($database, 'ai_outputs', $column),
            'Rollback removed ai_outputs column: ' . $column
        );
    }

    d043Assert(
        d043ConstraintExists(
            $database,
            'ai_outputs',
            'chk_ai_outputs_content_state',
            'CHECK'
        ),
        'Original ai_outputs content-state constraint restored'
    );
    d043AssertSame(
        $accountCountBeforeRollback,
        (int) $database->query('SELECT COUNT(*) FROM accounts')->fetchColumn(),
        'Rollback preserves unrelated account rows'
    );
    d043AssertSame(
        $resourceCountBeforeRollback,
        (int) $database->query('SELECT COUNT(*) FROM resources')->fetchColumn(),
        'Rollback preserves unrelated resource rows'
    );
    d043AssertSame(
        $outputCountBeforeRollback,
        (int) $database->query('SELECT COUNT(*) FROM ai_outputs')->fetchColumn(),
        'Rollback preserves AI-output accountability rows'
    );
    d043AssertSame(
        'invalidated',
        (string) $database->query(
            'SELECT lifecycle_state FROM ai_outputs WHERE id = '
            . $legacyOutputId
        )->fetchColumn(),
        'Rollback does not silently reactivate AI output'
    );

    $database = null;

    if ($liveDatabase !== '' && $liveTableCountBefore !== null) {
        $liveCountStatement->execute(['schema_name' => $liveDatabase]);
        d043AssertSame(
            $liveTableCountBefore,
            (int) $liveCountStatement->fetchColumn(),
            'Configured live database table count unchanged after test'
        );
    }

    d043AssertSame(
        $schemaHashBefore,
        hash_file('sha256', $schemaPath),
        'Protected database/schema.sql hash unchanged'
    );
} catch (Throwable $exception) {
    $failed = true;
    fwrite(STDERR, "\nD043 DISPOSABLE MIGRATION VERIFICATION FAILED\n");
    fwrite(STDERR, $exception->getMessage() . "\n");
} finally {
    $database = null;

    if ($created && $server instanceof PDO) {
        try {
            if (!str_starts_with($disposableDatabase, D043_DATABASE_PREFIX)) {
                throw new RuntimeException('Cleanup database prefix guard failed.');
            }

            $server->exec(
                'DROP DATABASE ' . d043QuoteIdentifier($disposableDatabase)
            );
            fwrite(STDOUT, "Disposable database removed: {$disposableDatabase}\n");
        } catch (Throwable $cleanupException) {
            $failed = true;
            fwrite(STDERR, 'Disposable cleanup failed: ');
            fwrite(STDERR, $cleanupException->getMessage() . "\n");
        }
    }

    $server = null;
}

if ($failed) {
    exit(1);
}

fwrite(STDOUT, "\nD043 DISPOSABLE MIGRATION VERIFICATION PASSED.\n");
fwrite(STDOUT, "Checks passed: {$d043Checks}\n");
fwrite(STDOUT, "Fresh canonical result: exact 22-table target.\n");
fwrite(STDOUT, "Forward result: exact 22-table target.\n");
fwrite(STDOUT, "Rollback result: exact 18-table baseline.\n");
fwrite(STDOUT, "Live configured database changed: No.\n");
fwrite(STDOUT, "database/schema.sql changed during verification: No.\n");
fwrite(STDOUT, "Provider/model request performed: No.\n");
fwrite(STDOUT, "Next boundary: review canonical/live migration evidence before AI persistence integration.\n");
