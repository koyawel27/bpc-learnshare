<?php

declare(strict_types=1);

use BpcLearnShare\Core\Environment;

require dirname(__DIR__, 2) . '/src/bootstrap.php';

const D044_DATABASE_PREFIX = 'bpc_learnshare_d044_verify_';
const D044_APPLY_APPROVAL = 'D044-DISPOSABLE-MIGRATION-ACCEPTANCE';

/** @var int $d044Checks */
$d044Checks = 0;

function d044Assert(bool $condition, string $label): void
{
    global $d044Checks;

    if (!$condition) {
        throw new RuntimeException($label . ' failed.');
    }

    $d044Checks++;
    fwrite(STDOUT, "[PASS] {$label}\n");
}

/** @param mixed $actual */
function d044AssertSame(mixed $expected, mixed $actual, string $label): void
{
    d044Assert(
        $actual === $expected,
        sprintf(
            '%s (expected %s; received %s)',
            $label,
            var_export($expected, true),
            var_export($actual, true)
        )
    );
}

function d044QuoteIdentifier(string $identifier): string
{
    if (!preg_match('/^[a-z0-9_]+$/', $identifier)) {
        throw new RuntimeException('Unsafe database identifier refused.');
    }

    return '`' . $identifier . '`';
}

/** @return list<string> */
function d044SplitSql(string $sql): array
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

/** @return list<string> */
function d044SqlFileStatements(string $path): array
{
    $sql = file_get_contents($path);

    if (!is_string($sql) || trim($sql) === '') {
        throw new RuntimeException('SQL file is missing or empty: ' . $path);
    }

    return d044SplitSql($sql);
}

function d044ExecuteSqlFile(PDO $database, string $path): int
{
    $statements = d044SqlFileStatements($path);

    foreach ($statements as $position => $statement) {
        try {
            if (preg_match('/^SELECT\b/i', ltrim($statement)) === 1) {
                $result = $database->query($statement);
                $result->fetchAll();
                $result->closeCursor();
            } else {
                $database->exec($statement);
            }
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

/** @param callable(): void $operation */
function d044ExpectDatabaseFailure(callable $operation, string $label): void
{
    try {
        $operation();
    } catch (Throwable $exception) {
        $cause = $exception;

        while ($cause !== null && !$cause instanceof PDOException) {
            $cause = $cause->getPrevious();
        }

        d044Assert($cause instanceof PDOException, $label . ' returned a database error');

        return;
    }

    throw new RuntimeException($label . ' failed: database accepted prohibited rollback.');
}

/** @return list<string> */
function d044TableNames(PDO $database): array
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

/** @return list<string> */
function d044SchemaTableNames(PDO $server, string $schema): array
{
    $statement = $server->prepare(
        "SELECT table_name
         FROM information_schema.tables
         WHERE table_schema = :schema_name
           AND table_type = 'BASE TABLE'
         ORDER BY table_name"
    );
    $statement->execute(['schema_name' => $schema]);

    return array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN));
}

/** @param list<string> $expected */
function d044AssertTables(PDO $database, array $expected, string $label): void
{
    sort($expected);
    $actual = d044TableNames($database);
    sort($actual);

    d044AssertSame($expected, $actual, $label);
}

function d044ColumnExists(PDO $database, string $table, string $column): bool
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

function d044SchemaColumnExists(
    PDO $server,
    string $schema,
    string $table,
    string $column
): bool {
    $statement = $server->prepare(
        'SELECT COUNT(*)
         FROM information_schema.columns
         WHERE table_schema = :schema_name
           AND table_name = :table_name
           AND column_name = :column_name'
    );
    $statement->execute([
        'schema_name' => $schema,
        'table_name' => $table,
        'column_name' => $column,
    ]);

    return (int) $statement->fetchColumn() === 1;
}

/** @return array<string, string|null> */
function d044ColumnMetadata(PDO $database, string $table, string $column): array
{
    $statement = $database->prepare(
        'SELECT column_type, is_nullable, column_default, extra
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = :table_name
           AND column_name = :column_name'
    );
    $statement->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);
    $row = $statement->fetch();

    if (!is_array($row)) {
        throw new RuntimeException('Expected column metadata was not found.');
    }

    return [
        'column_type' => (string) $row['column_type'],
        'is_nullable' => (string) $row['is_nullable'],
        'column_default' => $row['column_default'] === null
            ? null
            : (string) $row['column_default'],
        'extra' => (string) $row['extra'],
    ];
}

function d044ConstraintExists(PDO $database, string $constraint): bool
{
    $statement = $database->prepare(
        'SELECT COUNT(*)
         FROM information_schema.table_constraints
         WHERE constraint_schema = DATABASE()
           AND table_name = :table_name
           AND constraint_name = :constraint_name
           AND constraint_type = :constraint_type'
    );
    $statement->execute([
        'table_name' => 'accounts',
        'constraint_name' => $constraint,
        'constraint_type' => 'CHECK',
    ]);

    return (int) $statement->fetchColumn() === 1;
}

/** @return array<string, int> */
function d044TableCounts(PDO $database): array
{
    $counts = [];

    foreach (d044TableNames($database) as $table) {
        $counts[$table] = (int) $database
            ->query('SELECT COUNT(*) FROM ' . d044QuoteIdentifier($table))
            ->fetchColumn();
    }

    return $counts;
}

/** @return list<array<string, string|int>> */
function d044AccountSnapshot(PDO $database): array
{
    $rows = $database->query(
        "SELECT id, username, password_hash, display_name, role,
                account_status,
                DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') AS created_at,
                DATE_FORMAT(updated_at, '%Y-%m-%d %H:%i:%s') AS updated_at
         FROM accounts
         ORDER BY id"
    )->fetchAll();

    if (!is_array($rows)) {
        throw new RuntimeException('Unable to capture account snapshot.');
    }

    return $rows;
}

function d044FlaggedAccountCount(PDO $database): int
{
    return (int) $database
        ->query('SELECT COUNT(*) FROM accounts WHERE must_change_password = 1')
        ->fetchColumn();
}

function d044Hash(string $path): string
{
    $hash = hash_file('sha256', $path);

    if (!is_string($hash)) {
        throw new RuntimeException('Unable to hash protected file: ' . $path);
    }

    return $hash;
}

/** @return array{mode: string, approval_token: string} */
function d044Options(): array
{
    $options = getopt('', ['mode:', 'approval-token::']);

    if (!is_array($options)) {
        throw new RuntimeException('Unable to parse command-line options.');
    }

    $mode = (string) ($options['mode'] ?? 'validate');
    $approvalToken = (string) ($options['approval-token'] ?? '');

    if (!in_array($mode, ['validate', 'apply'], true)) {
        throw new RuntimeException('Mode must be exactly validate or apply.');
    }

    if ($mode === 'apply' && !hash_equals(D044_APPLY_APPROVAL, $approvalToken)) {
        throw new RuntimeException(
            'Apply mode requires --approval-token=' . D044_APPLY_APPROVAL
        );
    }

    return [
        'mode' => $mode,
        'approval_token' => $approvalToken,
    ];
}

$repository = dirname(__DIR__, 2);
$schemaPath = $repository . '/database/schema.sql';
$upPath = $repository
    . '/database/migrations/20260830_d044_mandatory_password_change_up.sql';
$downPath = $repository
    . '/database/migrations/20260830_d044_mandatory_password_change_down.sql';
$server = null;
$database = null;
$created = false;
$failed = false;
$disposableDatabase = '';
$options = ['mode' => 'validate', 'approval_token' => ''];

$expectedTables = [
    'accounts',
    'ai_chunks',
    'ai_embeddings',
    'ai_outputs',
    'ai_processing_states',
    'ai_source_versions',
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

try {
    $options = d044Options();
    $schemaHashBefore = d044Hash($schemaPath);
    $upHashBefore = d044Hash($upPath);
    $downHashBefore = d044Hash($downPath);
    $upSql = file_get_contents($upPath);
    $downSql = file_get_contents($downPath);

    fwrite(STDOUT, "=== D044 MIGRATION DISPOSABLE VERIFIER ===\n");
    fwrite(STDOUT, 'Mode: ' . $options['mode'] . "\n");
    fwrite(STDOUT, "Credential values: not displayed\n\n");

    d044Assert(is_file($schemaPath), 'Canonical schema file present');
    d044Assert(is_file($upPath), 'D044 forward migration file present');
    d044Assert(is_file($downPath), 'D044 rollback migration file present');
    d044Assert(is_string($upSql) && trim($upSql) !== '', 'Forward migration is nonempty');
    d044Assert(is_string($downSql) && trim($downSql) !== '', 'Rollback migration is nonempty');
    d044AssertSame(1, count(d044SqlFileStatements($upPath)), 'Forward migration statement count');
    d044AssertSame(4, count(d044SqlFileStatements($downPath)), 'Rollback migration statement count');
    d044Assert(
        preg_match(
            '/ADD\s+COLUMN\s+must_change_password\s+TINYINT\(1\)\s+NOT\s+NULL\s+DEFAULT\s+0/i',
            $upSql
        ) === 1,
        'Forward migration has the accepted additive column definition'
    );
    d044Assert(
        str_contains($downSql, 'WHERE must_change_password = 1'),
        'Rollback includes the required flagged-account count'
    );
    d044Assert(
        preg_match(
            '/SET\s+SESSION\s+check_constraint_checks\s*=\s*1/i',
            $downSql
        ) === 1,
        'Rollback explicitly enables CHECK-constraint enforcement'
    );
    d044Assert(
        preg_match(
            '/ADD\s+CONSTRAINT\s+chk_d044_rollback_no_flagged\s+CHECK\s*\(must_change_password\s*=\s*0\)/i',
            $downSql
        ) === 1,
        'Rollback has a database-enforced fail-closed guard'
    );
    d044Assert(
        preg_match('/DROP\s+COLUMN\s+must_change_password/i', $downSql) === 1,
        'Rollback removes only the additive account flag'
    );

    $host = Environment::get('D044_DB_HOST', Environment::get('DB_HOST', '127.0.0.1'));
    $port = Environment::getInt('D044_DB_PORT', Environment::getInt('DB_PORT', 3306));
    $adminUser = Environment::get(
        'D044_DB_ADMIN_USER',
        Environment::get('D043_DB_ADMIN_USER', 'root')
    );
    $adminPassword = Environment::get(
        'D044_DB_ADMIN_PASS',
        Environment::get('D043_DB_ADMIN_PASS', '')
    );
    $liveDatabase = Environment::get('DB_NAME', '');

    d044Assert($liveDatabase !== '', 'Configured live database name is present');

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

    d044AssertSame(
        '10.4.32-MariaDB',
        (string) $server->query('SELECT VERSION()')->fetchColumn(),
        'Exact MariaDB verification runtime'
    );

    $liveTablesBefore = d044SchemaTableNames($server, $liveDatabase);
    $expectedLiveTables = $expectedTables;
    sort($expectedLiveTables);
    sort($liveTablesBefore);
    d044AssertSame(
        $expectedLiveTables,
        $liveTablesBefore,
        'Configured live database has the exact protected 22-table baseline'
    );
    $liveFlagBefore = d044SchemaColumnExists(
        $server,
        $liveDatabase,
        'accounts',
        'must_change_password'
    );
    d044Assert(!$liveFlagBefore, 'Configured live database remains pre-D044');

    if ($options['mode'] === 'validate') {
        d044AssertSame($schemaHashBefore, d044Hash($schemaPath), 'schema.sql hash unchanged');
        d044AssertSame($upHashBefore, d044Hash($upPath), 'Forward migration hash unchanged');
        d044AssertSame($downHashBefore, d044Hash($downPath), 'Rollback migration hash unchanged');

        fwrite(STDOUT, "\nD044 READ-ONLY VALIDATION PASSED.\n");
        fwrite(STDOUT, "No disposable database was created.\n");
        fwrite(STDOUT, "No migration was applied.\n");
    } else {
        $disposableDatabase = D044_DATABASE_PREFIX . bin2hex(random_bytes(5));
        d044Assert(
            preg_match(
                '/^' . D044_DATABASE_PREFIX . '[0-9a-f]{10}$/',
                $disposableDatabase
            ) === 1,
            'Disposable database name guard'
        );
        d044Assert(
            $disposableDatabase !== $liveDatabase,
            'Disposable database differs from configured live database'
        );

        fwrite(STDOUT, 'Disposable database: ' . $disposableDatabase . "\n");

        $server->exec(
            'CREATE DATABASE ' . d044QuoteIdentifier($disposableDatabase)
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
        d044AssertSame(
            $disposableDatabase,
            (string) $database->query('SELECT DATABASE()')->fetchColumn(),
            'All test writes target the disposable database'
        );

        $schemaStatements = d044ExecuteSqlFile($database, $schemaPath);
        d044Assert($schemaStatements >= 22, 'Canonical schema statements executed');
        d044AssertTables($database, $expectedTables, 'Fresh schema has exact 22-table set');
        d044Assert(
            !d044ColumnExists($database, 'accounts', 'must_change_password'),
            'Fresh current schema remains pre-D044'
        );

        $bootstrapHash = password_hash(bin2hex(random_bytes(18)), PASSWORD_DEFAULT);
        $studentHash = password_hash(bin2hex(random_bytes(18)), PASSWORD_DEFAULT);
        d044Assert(
            is_string($bootstrapHash) && is_string($studentHash),
            'Controlled fixture password hashes created without output'
        );

        $accountInsert = $database->prepare(
            "INSERT INTO accounts (
                id,
                username,
                password_hash,
                display_name,
                role,
                account_status,
                created_at,
                updated_at
             ) VALUES (
                :id,
                :username,
                :password_hash,
                :display_name,
                :role,
                'active',
                '2026-08-30 00:00:00',
                '2026-08-30 00:00:00'
             )"
        );
        $accountInsert->execute([
            'id' => 1,
            'username' => 'd044_bootstrap_admin',
            'password_hash' => $bootstrapHash,
            'display_name' => 'D044 Bootstrap Admin',
            'role' => 'admin',
        ]);
        $accountInsert->execute([
            'id' => 2,
            'username' => 'd044_existing_student',
            'password_hash' => $studentHash,
            'display_name' => 'D044 Existing Student',
            'role' => 'student',
        ]);

        $accountSnapshotBefore = d044AccountSnapshot($database);
        $tableCountsBefore = d044TableCounts($database);
        d044AssertSame(2, count($accountSnapshotBefore), 'Two controlled existing accounts inserted');

        $upStatements = d044ExecuteSqlFile($database, $upPath);
        d044AssertSame(1, $upStatements, 'Forward migration executed once');
        d044AssertTables($database, $expectedTables, 'Forward migration preserves exact 22-table set');
        d044Assert(
            d044ColumnExists($database, 'accounts', 'must_change_password'),
            'Forward migration added account flag'
        );
        d044AssertSame(
            [
                'column_type' => 'tinyint(1)',
                'is_nullable' => 'NO',
                'column_default' => '0',
                'extra' => '',
            ],
            d044ColumnMetadata($database, 'accounts', 'must_change_password'),
            'Account flag has the exact accepted metadata'
        );
        d044AssertSame(0, d044FlaggedAccountCount($database), 'Existing accounts initialize to zero');
        d044Assert(
            $accountSnapshotBefore === d044AccountSnapshot($database),
            'Forward migration preserves every pre-existing account field'
        );
        d044AssertSame(
            $tableCountsBefore,
            d044TableCounts($database),
            'Forward migration preserves all 22 table row counts'
        );

        $database->exec(
            'UPDATE accounts
             SET must_change_password = 1,
                 updated_at = updated_at
             WHERE id = 2'
        );
        d044AssertSame(1, d044FlaggedAccountCount($database), 'Controlled temporary-password flag set');

        d044ExpectDatabaseFailure(
            static function () use ($database, $downPath): void {
                d044ExecuteSqlFile($database, $downPath);
            },
            'Rollback is refused while a flagged account exists'
        );
        d044Assert(
            d044ColumnExists($database, 'accounts', 'must_change_password'),
            'Failed rollback preserves the account flag column'
        );
        d044AssertSame(1, d044FlaggedAccountCount($database), 'Failed rollback preserves flagged account');
        d044Assert(
            !d044ConstraintExists($database, 'chk_d044_rollback_no_flagged'),
            'Failed guard statement leaves no temporary constraint'
        );
        d044AssertTables($database, $expectedTables, 'Failed rollback preserves exact 22-table set');

        $database->exec(
            'UPDATE accounts
             SET must_change_password = 0,
                 updated_at = updated_at
             WHERE id = 2'
        );
        d044AssertSame(0, d044FlaggedAccountCount($database), 'Rollback precondition resolved explicitly');

        $downStatements = d044ExecuteSqlFile($database, $downPath);
        d044AssertSame(4, $downStatements, 'Rollback migration executed after precondition passed');
        d044Assert(
            !d044ColumnExists($database, 'accounts', 'must_change_password'),
            'Rollback removed only the additive account flag'
        );
        d044AssertTables($database, $expectedTables, 'Rollback preserves exact 22-table set');
        d044Assert(
            $accountSnapshotBefore === d044AccountSnapshot($database),
            'Rollback preserves all original account fields and password hashes'
        );
        d044AssertSame(
            $tableCountsBefore,
            d044TableCounts($database),
            'Rollback preserves all 22 table row counts'
        );

        d044AssertSame(1, d044ExecuteSqlFile($database, $upPath), 'Forward migration reapplies cleanly');
        d044AssertSame(0, d044FlaggedAccountCount($database), 'Reapplied migration initializes rows to zero');
        d044AssertTables($database, $expectedTables, 'Reapplied migration preserves exact 22-table set');

        $database = null;

        $liveTablesAfter = d044SchemaTableNames($server, $liveDatabase);
        sort($liveTablesAfter);
        d044AssertSame($liveTablesBefore, $liveTablesAfter, 'Configured live table set remains unchanged');
        d044AssertSame(
            $liveFlagBefore,
            d044SchemaColumnExists(
                $server,
                $liveDatabase,
                'accounts',
                'must_change_password'
            ),
            'Configured live account-column state remains unchanged'
        );
        d044AssertSame($schemaHashBefore, d044Hash($schemaPath), 'schema.sql hash unchanged');
        d044AssertSame($upHashBefore, d044Hash($upPath), 'Forward migration hash unchanged');
        d044AssertSame($downHashBefore, d044Hash($downPath), 'Rollback migration hash unchanged');

        fwrite(STDOUT, "\nD044 DISPOSABLE MIGRATION ACCEPTANCE PASSED.\n");
        fwrite(STDOUT, "The configured live database was not migrated.\n");
        fwrite(STDOUT, "D044 application enforcement was not claimed or tested.\n");
    }
} catch (Throwable $exception) {
    $failed = true;
    fwrite(STDERR, "\nD044 MIGRATION DISPOSABLE VERIFIER FAILED\n");
    fwrite(STDERR, $exception->getMessage() . "\n");
} finally {
    $database = null;

    if ($created && $server instanceof PDO) {
        try {
            if (
                !str_starts_with($disposableDatabase, D044_DATABASE_PREFIX)
                || preg_match(
                    '/^' . D044_DATABASE_PREFIX . '[0-9a-f]{10}$/',
                    $disposableDatabase
                ) !== 1
            ) {
                throw new RuntimeException('Cleanup database name guard failed.');
            }

            $server->exec(
                'DROP DATABASE ' . d044QuoteIdentifier($disposableDatabase)
            );
            $cleanupCheck = $server->prepare(
                'SELECT COUNT(*)
                 FROM information_schema.schemata
                 WHERE schema_name = :schema_name'
            );
            $cleanupCheck->execute(['schema_name' => $disposableDatabase]);
            d044AssertSame(
                0,
                (int) $cleanupCheck->fetchColumn(),
                'Disposable database cleanup confirmed'
            );
            fwrite(STDOUT, 'Disposable database removed: ' . $disposableDatabase . "\n");
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

fwrite(STDOUT, "Checks passed: {$d044Checks}\n");
fwrite(STDOUT, 'Mode completed: ' . $options['mode'] . "\n");
fwrite(STDOUT, "Configured live database changed: No.\n");
fwrite(STDOUT, "database/schema.sql changed: No.\n");
fwrite(STDOUT, "Application or D044 workflow tested: No.\n");
