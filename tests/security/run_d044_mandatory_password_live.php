<?php

declare(strict_types=1);

use BpcLearnShare\Core\Database;
use BpcLearnShare\Core\Environment;

/**
 * Guarded D044 live HTTP acceptance package.
 *
 * Validate mode is source/configuration inspection only. It makes no HTTP
 * request, database connection, session mutation, account change, or file
 * write. Apply mode requires the exact approval token below and creates only
 * one directly inserted, randomized, test-owned flagged Student fixture.
 *
 * This package does not test or claim Admin provisioning, Admin-assisted
 * reset, provisioning/reset audit atomicity, one-time Admin credential
 * display, CSV import, MIS integration, or sole-Admin recovery.
 */

const D044_LIVE_APPROVAL = 'D044-MANDATORY-PASSWORD-LIVE-ACCEPTANCE';
const D044_LOCAL_OWNER_CONFIRMATION = 'LOCAL-OWNER-CONFIRMED';

const D044_EXPECTED_TABLES = [
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

$root = dirname(__DIR__, 2);
require $root . '/src/bootstrap.php';

$mode = 'validate';
$approval = '';
$exclusiveLocalUseConfirmation = '';
$baseUrlInput = Environment::get('APP_URL', 'http://127.0.0.1:8081');

foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--mode=')) {
        $mode = substr($argument, 7);
    } elseif (str_starts_with($argument, '--approve=')) {
        $approval = substr($argument, 10);
    } elseif (str_starts_with($argument, '--base-url=')) {
        $baseUrlInput = substr($argument, 11);
    } elseif (str_starts_with($argument, '--confirm-exclusive-local-use=')) {
        $exclusiveLocalUseConfirmation = substr($argument, 30);
    } else {
        throw new RuntimeException('Unknown argument: ' . $argument);
    }
}

if (!in_array($mode, ['validate', 'apply'], true)) {
    throw new RuntimeException('Mode must be validate or apply.');
}

if ($mode === 'validate' && $approval !== '') {
    throw new RuntimeException(
        'The approval token is accepted only with --mode=apply.'
    );
}

if (
    $exclusiveLocalUseConfirmation !== ''
    && !hash_equals(
        D044_LOCAL_OWNER_CONFIRMATION,
        $exclusiveLocalUseConfirmation
    )
) {
    throw new RuntimeException(
        'The exclusive-local-use confirmation value is invalid.'
    );
}

if ($mode === 'validate' && $exclusiveLocalUseConfirmation !== '') {
    throw new RuntimeException(
        'Owner confirmation is accepted only with --mode=apply and does not authorize apply mode.'
    );
}

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
function d044Same(mixed $expected, mixed $actual, string $label): void
{
    d044Assert(
        $expected === $actual,
        sprintf(
            '%s (expected %s; received %s)',
            $label,
            var_export($expected, true),
            var_export($actual, true)
        )
    );
}

function d044Read(string $path): string
{
    $content = file_get_contents($path);

    if (!is_string($content)) {
        throw new RuntimeException('Required local file could not be read.');
    }

    return $content;
}

function d044LoopbackBaseUrl(string $input): string
{
    $parts = parse_url($input);

    if (!is_array($parts)) {
        throw new RuntimeException('Application URL is invalid.');
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = strtolower((string) ($parts['host'] ?? ''));
    $path = (string) ($parts['path'] ?? '');

    if (!in_array($scheme, ['http', 'https'], true)) {
        throw new RuntimeException('Application URL must use HTTP or HTTPS.');
    }

    if (!in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
        throw new RuntimeException('Application URL must use a loopback host.');
    }

    if (
        isset($parts['user'])
        || isset($parts['pass'])
        || isset($parts['query'])
        || isset($parts['fragment'])
        || !in_array($path, ['', '/'], true)
    ) {
        throw new RuntimeException(
            'Application URL must contain only loopback origin information.'
        );
    }

    $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
    $displayHost = $host === '::1' ? '[::1]' : $host;

    return $scheme . '://' . $displayHost . $port;
}

function d044AssertLocalDatabaseHost(string $host): void
{
    d044Assert(
        d044IsLocalDatabaseHost($host),
        'Database host is local only'
    );
}

function d044IsLocalDatabaseHost(string $host): bool
{
    $normalized = strtolower(trim($host, " \t\n\r\0\x0B[]"));

    return in_array($normalized, ['127.0.0.1', 'localhost', '::1'], true);
}

function d044IsProcessPrivilegeLimitation(Throwable $exception): bool
{
    if (!$exception instanceof PDOException) {
        return false;
    }

    $message = strtolower($exception->getMessage());
    $errorInfo = $exception->errorInfo ?? [];
    $hasCompleteErrorInfo = isset($errorInfo[0], $errorInfo[1]);
    $sqlStateMatches = $hasCompleteErrorInfo
        ? strtolower((string) $errorInfo[0]) === '42000'
        : str_contains($message, 'sqlstate[42000]');
    $driverCodeMatches = $hasCompleteErrorInfo
        ? (int) $errorInfo[1] === 1227
        : (int) $exception->getCode() === 1227
            && str_contains($message, '1227');

    return $sqlStateMatches
        && $driverCodeMatches
        && str_contains($message, 'access denied')
        && str_contains($message, 'process')
        && str_contains($message, 'privilege');
}

function d044ResolveConcurrencyEvidence(
    ?Throwable $inspectionFailure,
    string $ownerConfirmation,
    bool $applicationIsLoopback,
    bool $databaseIsLocal
): string {
    if ($inspectionFailure === null) {
        return 'automatically-proven';
    }

    if (!d044IsProcessPrivilegeLimitation($inspectionFailure)) {
        throw new RuntimeException(
            'Database-session inspection failed for a reason other than the accepted PROCESS-privilege limitation.',
            0,
            $inspectionFailure
        );
    }

    if (!hash_equals(D044_LOCAL_OWNER_CONFIRMATION, $ownerConfirmation)) {
        throw new RuntimeException(
            'Automated database-session inspection is unavailable because PROCESS privilege is not granted; exact local-owner confirmation is required.'
        );
    }

    if (!$applicationIsLoopback || !$databaseIsLocal) {
        throw new RuntimeException(
            'Owner-attested concurrency exclusion is allowed only for loopback application and local database hosts.'
        );
    }

    return 'owner-attested-process-limitation';
}

function d044RequireApplyApproval(string $approval): void
{
    if (!hash_equals(D044_LIVE_APPROVAL, $approval)) {
        throw new RuntimeException(
            'The exact one-time live-acceptance approval token is required.'
        );
    }
}

function d044ExpectRuntimeFailure(
    callable $operation,
    string $messageFragment,
    string $label
): void {
    $failure = null;

    try {
        $operation();
    } catch (RuntimeException $exception) {
        $failure = $exception;
    }

    d044Assert(
        $failure instanceof RuntimeException
        && str_contains($failure->getMessage(), $messageFragment),
        $label
    );
}

function d044InspectDatabaseConcurrency(
    PDO $database,
    string $ownerConfirmation,
    bool $applicationIsLoopback,
    bool $databaseIsLocal
): string {
    try {
        $connectionId = (int) $database
            ->query('SELECT CONNECTION_ID()')
            ->fetchColumn();
        $processes = $database
            ->query('SHOW FULL PROCESSLIST')
            ->fetchAll(PDO::FETCH_ASSOC);
        $otherClientConnections = 0;

        foreach ($processes as $process) {
            if ((int) ($process['Id'] ?? 0) === $connectionId) {
                continue;
            }

            if (
                strtolower((string) ($process['Command'] ?? '')) === 'daemon'
                || strtolower((string) ($process['User'] ?? '')) === 'system user'
            ) {
                continue;
            }

            $otherClientConnections++;
        }

        $transactionStatement = $database->prepare(
            'SELECT COUNT(*)
             FROM information_schema.innodb_trx
             WHERE trx_mysql_thread_id <> :connection_id'
        );
        $transactionStatement->execute(['connection_id' => $connectionId]);
        $otherOpenTransactions = (int) $transactionStatement->fetchColumn();

        d044Same(
            0,
            $otherClientConnections,
            'No other client connection is using the local database server'
        );
        d044Same(
            0,
            $otherOpenTransactions,
            'No other InnoDB transaction is open'
        );

        return d044ResolveConcurrencyEvidence(
            null,
            $ownerConfirmation,
            $applicationIsLoopback,
            $databaseIsLocal
        );
    } catch (PDOException $exception) {
        return d044ResolveConcurrencyEvidence(
            $exception,
            $ownerConfirmation,
            $applicationIsLoopback,
            $databaseIsLocal
        );
    }
}

/**
 * @param list<string> $relativePaths
 * @return array<string, string>
 */
function d044SourceHashes(string $root, array $relativePaths): array
{
    $hashes = [];

    foreach ($relativePaths as $relativePath) {
        $path = $root . '/' . $relativePath;
        $hash = hash_file('sha256', $path);

        if (!is_string($hash)) {
            throw new RuntimeException('A protected source hash could not be read.');
        }

        $hashes[$relativePath] = $hash;
    }

    ksort($hashes);

    return $hashes;
}

function d044SafeIdentifier(string $identifier): string
{
    if (!preg_match('/\A[a-z0-9_]+\z/', $identifier)) {
        throw new RuntimeException('Unsafe database identifier encountered.');
    }

    return '`' . $identifier . '`';
}

/** @return list<string> */
function d044TableNames(PDO $database): array
{
    $tables = $database->query(
        "SELECT table_name
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_type = 'BASE TABLE'
         ORDER BY table_name"
    )->fetchAll(PDO::FETCH_COLUMN);

    return array_map(
        static fn (mixed $table): string => (string) $table,
        $tables
    );
}

/**
 * @param list<string> $expected
 * @param list<string> $returned
 */
function d044AssertExactTableSet(
    array $expected,
    array $returned,
    string $label
): void {
    $normalizedExpected = array_values($expected);
    $normalizedReturned = array_values($returned);
    sort($normalizedExpected, SORT_STRING);
    sort($normalizedReturned, SORT_STRING);

    $expectedCounts = array_count_values($expected);
    $returnedCounts = array_count_values($returned);
    $expectedDuplicates = [];
    $returnedDuplicates = [];

    foreach ($expectedCounts as $table => $count) {
        if ($count > 1) {
            $expectedDuplicates[] = (string) $table;
        }
    }

    foreach ($returnedCounts as $table => $count) {
        if ($count > 1) {
            $returnedDuplicates[] = (string) $table;
        }
    }

    sort($expectedDuplicates, SORT_STRING);
    sort($returnedDuplicates, SORT_STRING);
    $missing = array_values(array_unique(
        array_diff($normalizedExpected, $normalizedReturned),
        SORT_STRING
    ));
    $unexpected = array_values(array_unique(
        array_diff($normalizedReturned, $normalizedExpected),
        SORT_STRING
    ));
    sort($missing, SORT_STRING);
    sort($unexpected, SORT_STRING);

    if (
        count($expected) !== 22
        || count($returned) !== 22
        || $expectedDuplicates !== []
        || $returnedDuplicates !== []
        || $normalizedExpected !== $normalizedReturned
    ) {
        $format = static function (array $tables): string {
            $encoded = json_encode(
                array_values($tables),
                JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
            );

            return is_string($encoded) ? $encoded : '[]';
        };

        throw new RuntimeException(sprintf(
            '%s failed. Expected count: 22; received count: %d. '
            . 'Missing tables: %s. Unexpected tables: %s. '
            . 'Duplicate expected tables: %s. Duplicate returned tables: %s.',
            $label,
            count($returned),
            $format($missing),
            $format($unexpected),
            $format($expectedDuplicates),
            $format($returnedDuplicates)
        ));
    }

    d044Assert(true, $label);
}

/**
 * @return array{
 *   counts: array<string, int>,
 *   fingerprints: array<string, string>
 * }
 */
function d044TableState(PDO $database): array
{
    $counts = [];
    $fingerprints = [];

    foreach (d044TableNames($database) as $table) {
        $quotedTable = d044SafeIdentifier($table);
        $counts[$table] = (int) $database
            ->query("SELECT COUNT(*) FROM {$quotedTable}")
            ->fetchColumn();

        $keyStatement = $database->prepare(
            "SELECT column_name
             FROM information_schema.key_column_usage
             WHERE table_schema = DATABASE()
               AND table_name = :table_name
               AND constraint_name = 'PRIMARY'
             ORDER BY ordinal_position"
        );
        $keyStatement->execute(['table_name' => $table]);
        $orderColumns = array_map(
            static fn (mixed $column): string => (string) $column,
            $keyStatement->fetchAll(PDO::FETCH_COLUMN)
        );

        if ($orderColumns === []) {
            $columnStatement = $database->prepare(
                "SELECT column_name
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE()
                   AND table_name = :table_name
                 ORDER BY ordinal_position"
            );
            $columnStatement->execute(['table_name' => $table]);
            $orderColumns = array_map(
                static fn (mixed $column): string => (string) $column,
                $columnStatement->fetchAll(PDO::FETCH_COLUMN)
            );
        }

        if ($orderColumns === []) {
            throw new RuntimeException('Database table has no readable columns.');
        }

        $order = implode(', ', array_map(
            static fn (string $column): string => d044SafeIdentifier($column),
            $orderColumns
        ));
        $rows = $database->query(
            "SELECT * FROM {$quotedTable} ORDER BY {$order}"
        );
        $context = hash_init('sha256');

        while (($row = $rows->fetch(PDO::FETCH_ASSOC)) !== false) {
            hash_update($context, serialize($row) . "\n");
        }

        $fingerprints[$table] = hash_final($context);
    }

    ksort($counts);
    ksort($fingerprints);

    return ['counts' => $counts, 'fingerprints' => $fingerprints];
}

/** @return array<string, array{bytes: int, sha256: string}> */
function d044StorageManifest(string $directory): array
{
    $root = realpath($directory);

    if (!is_string($root) || !is_dir($root)) {
        throw new RuntimeException('Protected storage cannot be inspected.');
    }

    $manifest = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $root,
            FilesystemIterator::SKIP_DOTS
        )
    );

    foreach ($iterator as $item) {
        if (!$item->isFile()) {
            continue;
        }

        $path = $item->getPathname();
        $relative = str_replace(
            '\\',
            '/',
            ltrim(substr($path, strlen($root)), '\\/')
        );
        $size = $item->getSize();
        $hash = hash_file('sha256', $path);

        if (!is_string($hash)) {
            throw new RuntimeException('Protected file could not be hashed.');
        }

        $manifest[$relative] = ['bytes' => $size, 'sha256' => $hash];
    }

    ksort($manifest);

    return $manifest;
}

function d044ArrayDigest(array $value): string
{
    return hash('sha256', serialize($value));
}

function d044AccountsAutoIncrement(PDO $database): int
{
    $statement = $database->query(
        "SELECT auto_increment
         FROM information_schema.tables
         WHERE table_schema = DATABASE()
           AND table_name = 'accounts'"
    );
    $value = $statement->fetchColumn();

    if ($value === false || (int) $value < 1) {
        throw new RuntimeException('Accounts AUTO_INCREMENT could not be read.');
    }

    return (int) $value;
}

function d044AutoIncrementIsAcceptable(
    int $baselineAutoIncrement,
    int $currentAutoIncrement
): bool {
    return $currentAutoIncrement >= $baselineAutoIncrement;
}

/**
 * @return array{status: int, headers: string, body: string}
 */
function d044Http(
    string $baseUrl,
    string $path,
    string $cookieFile,
    ?string $postBody = null,
    array $secrets = []
): array {
    if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
        throw new RuntimeException('Unsafe local request path.');
    }

    $url = $baseUrl . $path;

    foreach ($secrets as $secret) {
        if (is_string($secret) && $secret !== '' && str_contains($url, $secret)) {
            throw new RuntimeException('A credential was placed in a request URL.');
        }
    }

    $handle = curl_init($url);

    if ($handle === false) {
        throw new RuntimeException('Unable to initialize local HTTP request.');
    }

    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS => 0,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        CURLOPT_USERAGENT => 'BPC-LearnShare-D044-Mandatory-Password-Live/1.0',
    ]);

    if ($postBody !== null) {
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $postBody);
        curl_setopt(
            $handle,
            CURLOPT_HTTPHEADER,
            ['Content-Type: application/x-www-form-urlencoded']
        );
    }

    $response = curl_exec($handle);

    if (!is_string($response)) {
        $message = curl_error($handle);
        curl_close($handle);
        throw new RuntimeException('Local HTTP request failed: ' . $message);
    }

    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $headerBytes = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
    curl_close($handle);

    $headers = substr($response, 0, $headerBytes);
    $body = substr($response, $headerBytes);

    foreach ($secrets as $secret) {
        if (
            is_string($secret)
            && $secret !== ''
            && (str_contains($headers, $secret) || str_contains($body, $secret))
        ) {
            throw new RuntimeException(
                'A credential or password hash appeared in an HTTP response.'
            );
        }
    }

    return ['status' => $status, 'headers' => $headers, 'body' => $body];
}

function d044Header(string $headers, string $name): ?string
{
    $pattern = '/^' . preg_quote($name, '/') . ':\s*(.+)$/mi';

    if (!preg_match_all($pattern, $headers, $matches)) {
        return null;
    }

    $values = $matches[1];
    $value = end($values);

    return is_string($value) ? trim($value) : null;
}

function d044Csrf(string $html): string
{
    if (!preg_match('/name="_token"\s+value="([a-f0-9]{64})"/', $html, $matches)) {
        throw new RuntimeException('CSRF token was not found in the local form.');
    }

    return $matches[1];
}

function d044CookieFile(string $directory, string $name): string
{
    $path = $directory . DIRECTORY_SEPARATOR . $name . '.txt';

    if (!touch($path)) {
        throw new RuntimeException('Temporary cookie file could not be created.');
    }

    return $path;
}

function d044CookieValue(string $cookieFile, string $cookieName): ?string
{
    if (!is_file($cookieFile)) {
        return null;
    }

    $lines = file($cookieFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (!is_array($lines)) {
        throw new RuntimeException('Temporary cookie file could not be read.');
    }

    foreach ($lines as $line) {
        if (str_starts_with($line, '#') && !str_starts_with($line, '#HttpOnly_')) {
            continue;
        }

        $fields = explode("\t", $line);

        if (count($fields) >= 7 && $fields[5] === $cookieName) {
            return $fields[6] !== '' ? $fields[6] : null;
        }
    }

    return null;
}

/** @return array<string, mixed>|null */
function d044FixtureState(PDO $database, string $username): ?array
{
    $statement = $database->prepare(
        'SELECT id, username, password_hash, display_name, role,
                account_status, must_change_password
         FROM accounts
         WHERE username = :username
         LIMIT 1'
    );
    $statement->execute(['username' => $username]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);

    return is_array($row) ? $row : null;
}

function d044AssertCredentialStateUnchanged(
    array $expected,
    ?array $actual,
    string $label
): void {
    d044Assert(
        is_array($actual)
        && hash_equals(
            (string) $expected['password_hash'],
            (string) $actual['password_hash']
        )
        && (int) $expected['must_change_password']
            === (int) $actual['must_change_password'],
        $label
    );
}

/**
 * @param list<string> $secrets
 * @return array{status: int, headers: string, body: string}
 */
function d044GenericLoginFailure(
    string $baseUrl,
    string $cookieFile,
    string $username,
    string $password,
    string $label,
    array $secrets
): array {
    $page = d044Http($baseUrl, '/login', $cookieFile, null, $secrets);
    d044Same(200, $page['status'], $label . ' login form opens');
    $response = d044Http(
        $baseUrl,
        '/login',
        $cookieFile,
        http_build_query([
            '_token' => d044Csrf($page['body']),
            'username' => $username,
            'password' => $password,
        ]),
        $secrets
    );
    d044Same(422, $response['status'], $label . ' login is rejected');
    d044Assert(
        str_contains(
            $response['body'],
            'Unable to sign in with those credentials.'
        ),
        $label . ' uses the generic authentication message'
    );
    d044Assert(
        !str_contains(strtolower($response['body']), 'account is disabled')
        && !str_contains(strtolower($response['body']), 'identifier does not exist')
        && !str_contains(strtolower($response['body']), 'role is'),
        $label . ' does not disclose account, status, or role state'
    );
    $protected = d044Http(
        $baseUrl,
        '/dashboard',
        $cookieFile,
        null,
        $secrets
    );
    d044Same(303, $protected['status'], $label . ' creates no authenticated session');
    d044Same('/login', d044Header($protected['headers'], 'Location'), $label . ' redirects to login');

    return $response;
}

/**
 * @param list<string> $secrets
 * @return array{before_id: string, after_id: string, response: array}
 */
function d044SuccessfulLogin(
    string $baseUrl,
    string $cookieFile,
    string $username,
    string $password,
    string $expectedLocation,
    string $label,
    array $secrets
): array {
    $page = d044Http($baseUrl, '/login', $cookieFile, null, $secrets);
    d044Same(200, $page['status'], $label . ' login form opens');
    $beforeId = d044CookieValue($cookieFile, 'bpc_learnshare_session');
    d044Assert(is_string($beforeId) && $beforeId !== '', $label . ' has a pre-authentication session');
    $response = d044Http(
        $baseUrl,
        '/login',
        $cookieFile,
        http_build_query([
            '_token' => d044Csrf($page['body']),
            'username' => $username,
            'password' => $password,
        ]),
        $secrets
    );
    d044Same(303, $response['status'], $label . ' authentication succeeds');
    d044Same(
        $expectedLocation,
        d044Header($response['headers'], 'Location'),
        $label . ' receives the expected redirect'
    );
    $afterId = d044CookieValue($cookieFile, 'bpc_learnshare_session');
    d044Assert(is_string($afterId) && $afterId !== '', $label . ' has an authenticated session');
    d044Assert(!hash_equals($beforeId, $afterId), $label . ' session identifier regenerates');

    return [
        'before_id' => $beforeId,
        'after_id' => $afterId,
        'response' => $response,
    ];
}

/** @param array<string, mixed> $data */
function d044WriteRecoveryMarker(string $path, array $data): void
{
    $directory = dirname($path);

    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Recovery-marker directory could not be created.');
    }

    $json = json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );

    if (file_put_contents($path, $json . "\n", LOCK_EX) === false) {
        throw new RuntimeException('Recovery marker could not be written.');
    }
}

function d044ReportsReferenceQuery(): string
{
    return 'SELECT COUNT(*) FROM reports WHERE :id IN '
        . '(reporter_account_id, escalated_by_account_id, resolved_by_account_id)';
}

/** @return array<string, int> */
function d044FixtureReferences(PDO $database, int $accountId): array
{
    $queries = [
        'resources' => 'SELECT COUNT(*) FROM resources WHERE uploader_id = :id',
        'reports' => d044ReportsReferenceQuery(),
        'resource_action_history' => 'SELECT COUNT(*) FROM resource_action_history WHERE actor_account_id = :id',
        'open_report_tracking' => 'SELECT COUNT(*) FROM open_report_tracking WHERE reporter_account_id = :id',
        'bookmarks' => 'SELECT COUNT(*) FROM bookmarks WHERE account_id = :id',
        'helpful_marks' => 'SELECT COUNT(*) FROM helpful_marks WHERE account_id = :id',
        'notifications' => 'SELECT COUNT(*) FROM notifications WHERE recipient_account_id = :id',
        'system_settings' => 'SELECT COUNT(*) FROM system_settings WHERE updated_by_account_id = :id',
        'audit_log' => 'SELECT COUNT(*) FROM audit_log WHERE actor_account_id = :id',
    ];
    $references = [];

    foreach ($queries as $table => $sql) {
        $statement = $database->prepare($sql);
        $statement->execute(['id' => $accountId]);
        $count = (int) $statement->fetchColumn();

        if ($count > 0) {
            $references[$table] = $count;
        }
    }

    return $references;
}

function d044DeleteFixture(
    PDO $database,
    int $accountId,
    string $username,
    int $baselineAccountCount
): void {
    $state = d044FixtureState($database, $username);

    if ($state !== null) {
        if ($accountId <= 0) {
            $accountId = (int) $state['id'];
        } elseif ((int) $state['id'] !== $accountId) {
            throw new RuntimeException('Fixture identity no longer matches the recovery marker.');
        }

        $references = d044FixtureReferences($database, $accountId);

        if ($references !== []) {
            throw new RuntimeException(
                'Unexpected fixture references prevent targeted cleanup: '
                . implode(', ', array_keys($references))
            );
        }

        $delete = $database->prepare(
            'DELETE FROM accounts WHERE id = :id AND username = :username'
        );
        $delete->execute(['id' => $accountId, 'username' => $username]);

        if ($delete->rowCount() !== 1) {
            throw new RuntimeException('Targeted fixture deletion did not remove exactly one account.');
        }
    }

    $currentAccountCount = (int) $database
        ->query('SELECT COUNT(*) FROM accounts')
        ->fetchColumn();

    if ($currentAccountCount !== $baselineAccountCount) {
        throw new RuntimeException(
            'Account count did not return to its logical baseline.'
        );
    }
}

/** @param list<string> $cookieFiles */
function d044DestroyCookieSessions(array $cookieFiles): void
{
    $sessionIds = [];

    foreach ($cookieFiles as $cookieFile) {
        $sessionId = d044CookieValue($cookieFile, 'bpc_learnshare_session');

        if (is_string($sessionId) && $sessionId !== '') {
            $sessionIds[$sessionId] = true;
        }
    }

    if ($sessionIds === []) {
        return;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    $oldCookies = (string) ini_get('session.use_cookies');
    $oldStrict = (string) ini_get('session.use_strict_mode');
    $oldLimiter = (string) ini_get('session.cache_limiter');
    $saveHandler = (string) ini_get('session.save_handler');
    $savePath = session_save_path();

    if (str_contains($savePath, ';')) {
        $parts = explode(';', $savePath);
        $savePath = (string) end($parts);
    }

    if ($saveHandler === 'files' && $savePath === '') {
        $savePath = sys_get_temp_dir();
    }

    ini_set('session.use_cookies', '0');
    ini_set('session.use_strict_mode', '0');
    ini_set('session.cache_limiter', '');

    try {
        foreach (array_keys($sessionIds) as $sessionId) {
            if (!preg_match('/\A[a-zA-Z0-9,-]+\z/', $sessionId)) {
                throw new RuntimeException('Unsafe test session identifier encountered.');
            }

            session_id($sessionId);

            if (!session_start()) {
                throw new RuntimeException('Test session could not be opened for cleanup.');
            }

            $_SESSION = [];

            if (!session_destroy()) {
                throw new RuntimeException('Test session could not be destroyed.');
            }

            session_id('');

            if (
                $saveHandler === 'files'
                && $savePath !== ''
                && is_file(rtrim($savePath, '\\/') . DIRECTORY_SEPARATOR . 'sess_' . $sessionId)
            ) {
                throw new RuntimeException('Test session file remains after cleanup.');
            }
        }
    } finally {
        ini_set('session.use_cookies', $oldCookies);
        ini_set('session.use_strict_mode', $oldStrict);
        ini_set('session.cache_limiter', $oldLimiter);
    }
}

function d044RemoveTempDirectory(string $directory): void
{
    if ($directory === '' || !is_dir($directory)) {
        return;
    }

    if (!str_contains(str_replace('\\', '/', $directory), '/bpc-d044-auth-')) {
        throw new RuntimeException('Refusing to remove an unexpected directory.');
    }

    $items = scandir($directory);

    if (!is_array($items)) {
        throw new RuntimeException('Temporary directory could not be inspected.');
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $path = $directory . DIRECTORY_SEPARATOR . $item;

        if (!is_file($path) || !unlink($path)) {
            throw new RuntimeException('Temporary test file could not be removed.');
        }
    }

    if (!rmdir($directory)) {
        throw new RuntimeException('Temporary test directory could not be removed.');
    }
}

function d044RemoveRecoveryMarker(string $markerPath): void
{
    if (is_file($markerPath) && !unlink($markerPath)) {
        throw new RuntimeException('Recovery marker could not be removed.');
    }

    $directory = dirname($markerPath);

    if (is_dir($directory)) {
        $items = scandir($directory);

        if (is_array($items) && count($items) === 2) {
            @rmdir($directory);
        }
    }
}

$baseUrl = d044LoopbackBaseUrl($baseUrlInput);
$databaseHost = Environment::get('DB_HOST', '127.0.0.1');
$applicationIsLoopback = preg_match(
    '#\Ahttps?://(?:127\.0\.0\.1|localhost|\[::1\])(?::[0-9]+)?\z#',
    $baseUrl
) === 1;
$databaseIsLocal = d044IsLocalDatabaseHost($databaseHost);
$storage = $root . '/storage/uploads/resources';
$recoveryDirectory = $root . '/.local/database-backups/d044-auth-live';
$recoveryMarker = $recoveryDirectory . '/RECOVERY.json';
$sourcePaths = [
    '.env',
    'database/schema.sql',
    'database/migrations/20260830_d044_mandatory_password_change_up.sql',
    'database/migrations/20260830_d044_mandatory_password_change_down.sql',
    'public/index.php',
    'src/Core/Session.php',
    'src/Security/Csrf.php',
    'src/Views/auth/change_password.php',
    'src/Views/auth/login.php',
    'src/auth/AccountInput.php',
    'src/auth/AccountRepository.php',
    'src/auth/AuthService.php',
    'tests/security/run_d044_auth_foundation.php',
    'tests/security/run_d044_mandatory_password_live.php',
];

$database = null;
$baselineState = null;
$baselineStorage = null;
$baselineSources = null;
$baselineAutoIncrement = null;
$baselineAccountCount = null;
$fixtureId = 0;
$fixtureUsername = '';
$temporaryDirectory = '';
$cookieFiles = [];
$markerData = [];
$cleanupNeeded = false;
$failed = false;
$cleanupVerified = false;

fwrite(STDOUT, "=== D044 MANDATORY PASSWORD LIVE ACCEPTANCE PACKAGE ===\n");
fwrite(STDOUT, "Mode: {$mode}\n\n");

try {
    d044AssertLocalDatabaseHost($databaseHost);
    d044Assert(
        $applicationIsLoopback,
        'Application URL is loopback only'
    );
    d044Assert(extension_loaded('curl'), 'PHP cURL extension is available');
    d044Assert(extension_loaded('pdo_mysql'), 'PDO MySQL extension is available');
    d044Assert(is_file($root . '/.env'), 'Ignored local environment file exists');
    d044Assert(is_readable($root . '/.env'), 'Local environment file is readable');
    d044Assert(is_dir($storage), 'Protected resource storage exists');
    d044Assert(
        !str_starts_with(
            str_replace('\\', '/', (string) realpath($storage)),
            str_replace('\\', '/', (string) realpath($root . '/public')) . '/'
        ),
        'Protected resource storage is outside public'
    );
    $gitignore = d044Read($root . '/.gitignore');
    d044Assert(
        str_contains($gitignore, '.local/database-backups/'),
        'Recovery-marker parent is ignored by Git'
    );
    d044Assert(!is_file($recoveryMarker), 'No interrupted D044 live-run marker exists');

    foreach ($sourcePaths as $relativePath) {
        d044Assert(
            is_file($root . '/' . $relativePath),
            'Required source exists: ' . $relativePath
        );
    }

    $indexSource = d044Read($root . '/public/index.php');
    $repositorySource = d044Read($root . '/src/auth/AccountRepository.php');
    $sessionSource = d044Read($root . '/src/Core/Session.php');
    $passwordViewSource = d044Read($root . '/src/Views/auth/change_password.php');
    $schemaSource = d044Read($root . '/database/schema.sql');
    $upMigration = d044Read(
        $root . '/database/migrations/20260830_d044_mandatory_password_change_up.sql'
    );
    $downMigration = d044Read(
        $root . '/database/migrations/20260830_d044_mandatory_password_change_down.sql'
    );

    $registerPost = strpos(
        $indexSource,
        "if (\$path === '/register' && \$requestMethod === 'POST')"
    );
    $sessionStart = strpos($indexSource, 'Session::start();');
    $databaseStart = strpos($indexSource, 'Database::connection();');
    d044Assert(
        is_int($registerPost)
        && is_int($sessionStart)
        && is_int($databaseStart)
        && $registerPost < $sessionStart
        && $registerPost < $databaseStart,
        'POST /register rejection precedes session and database startup'
    );
    d044Assert(
        str_contains($indexSource, "'/account/change-password',")
        && str_contains($indexSource, "'/logout',")
        && str_contains($indexSource, '!in_array($path, $mandatoryPasswordPaths, true)'),
        'Global mandatory-change route guard is present'
    );
    d044Assert(
        !str_contains($repositorySource, 'createStudent')
        && !is_file($root . '/src/Views/auth/register.php'),
        'Public Student registration implementation is absent'
    );
    d044Assert(
        str_contains($repositorySource, 'AND must_change_password = 1')
        && str_contains(
            $repositorySource,
            'AND BINARY password_hash = BINARY :expected_password_hash'
        ),
        'Password replacement uses live flag and optimistic hash guards'
    );
    d044Assert(
        str_contains($sessionSource, "\$_SESSION['csrf_token']")
        && str_contains($sessionSource, 'session_regenerate_id(true);'),
        'Session regeneration and CSRF rotation source is present'
    );
    d044Assert(
        substr_count($passwordViewSource, 'autocomplete="new-password"') === 2
        && str_contains($passwordViewSource, 'action="/logout"'),
        'Mandatory-password view exposes only new password and logout actions'
    );
    d044Assert(
        preg_match(
            '/must_change_password\s+TINYINT\(1\)\s+NOT\s+NULL\s+DEFAULT\s+0/i',
            $schemaSource
        ) === 1,
        'Canonical schema contains the D044 flag'
    );
    d044Same(
        22,
        preg_match_all('/^CREATE TABLE\s+/mi', $schemaSource),
        'Canonical schema declares exactly 22 tables'
    );
    d044Assert(
        str_contains($upMigration, 'ADD COLUMN must_change_password')
        && str_contains($upMigration, 'TINYINT(1) NOT NULL DEFAULT 0'),
        'D044 up migration is the bounded additive column change'
    );
    d044Assert(
        str_contains($downMigration, 'WHERE must_change_password = 1')
        && str_contains($downMigration, 'CHECK (must_change_password = 0)')
        && str_contains($downMigration, 'DROP COLUMN must_change_password'),
        'D044 rollback retains the fail-closed flagged-account guard'
    );
    $initialSourceHashes = d044SourceHashes($root, $sourcePaths);
    d044Same(
        count($sourcePaths),
        count($initialSourceHashes),
        'All protected source/configuration hashes are captured'
    );
    $reportsReferenceQuery = d044ReportsReferenceQuery();
    d044Assert(
        substr_count($reportsReferenceQuery, ':id') === 1
        && str_contains($reportsReferenceQuery, 'reporter_account_id')
        && str_contains($reportsReferenceQuery, 'escalated_by_account_id')
        && str_contains($reportsReferenceQuery, 'resolved_by_account_id')
        && d044AutoIncrementIsAcceptable(100, 101)
        && d044AutoIncrementIsAcceptable(100, 100)
        && !d044AutoIncrementIsAcceptable(100, 99),
        'Cleanup uses one reports placeholder and accepts AUTO_INCREMENT advancement without rewind'
    );

    d044AssertExactTableSet(
        D044_EXPECTED_TABLES,
        D044_EXPECTED_TABLES,
        'Exact expected table order is accepted'
    );
    $reorderedTables = array_reverse(D044_EXPECTED_TABLES);
    d044AssertExactTableSet(
        D044_EXPECTED_TABLES,
        $reorderedTables,
        'Identical table set in a different order is accepted'
    );
    $missingTable = D044_EXPECTED_TABLES;
    array_pop($missingTable);
    d044ExpectRuntimeFailure(
        static function () use ($missingTable): void {
            d044AssertExactTableSet(
                D044_EXPECTED_TABLES,
                $missingTable,
                'Synthetic missing-table set'
            );
        },
        'Missing tables: ["year_levels"]',
        'One missing table is rejected'
    );
    $unexpectedTable = D044_EXPECTED_TABLES;
    $unexpectedTable[array_key_last($unexpectedTable)] = 'year_level';
    d044ExpectRuntimeFailure(
        static function () use ($unexpectedTable): void {
            d044AssertExactTableSet(
                D044_EXPECTED_TABLES,
                $unexpectedTable,
                'Synthetic unexpected-table set'
            );
        },
        'Missing tables: ["year_levels"]. Unexpected tables: ["year_level"]',
        'One unexpected or misspelled table is rejected'
    );
    $duplicateTable = D044_EXPECTED_TABLES;
    $duplicateTable[array_key_last($duplicateTable)] = 'accounts';
    d044ExpectRuntimeFailure(
        static function () use ($duplicateTable): void {
            d044AssertExactTableSet(
                D044_EXPECTED_TABLES,
                $duplicateTable,
                'Synthetic duplicate-table set'
            );
        },
        'Duplicate returned tables: ["accounts"]',
        'A duplicate table value is rejected'
    );
    $caseChangedTable = D044_EXPECTED_TABLES;
    $caseChangedTable[0] = 'Accounts';
    d044ExpectRuntimeFailure(
        static function () use ($caseChangedTable): void {
            d044AssertExactTableSet(
                D044_EXPECTED_TABLES,
                $caseChangedTable,
                'Synthetic case-changed table set'
            );
        },
        'Missing tables: ["accounts"]. Unexpected tables: ["Accounts"]',
        'A case-changed table name is rejected'
    );
    $incorrectCountTables = D044_EXPECTED_TABLES;
    $incorrectCountTables[] = 'unexpected_extra_table';
    d044ExpectRuntimeFailure(
        static function () use ($incorrectCountTables): void {
            d044AssertExactTableSet(
                D044_EXPECTED_TABLES,
                $incorrectCountTables,
                'Synthetic incorrect-count table set'
            );
        },
        'received count: 23',
        'An incorrect table count is rejected'
    );

    $processPrivilegeFailure = new PDOException(
        'SQLSTATE[42000]: Syntax error or access violation: 1227 Access denied; '
        . 'you need (at least one of) the PROCESS privilege(s) for this operation',
        1227
    );
    $unrelatedDatabaseFailure = new PDOException(
        'SQLSTATE[HY000] [2006] MySQL server has gone away',
        2006
    );
    $wrongSqlStateFailure = new PDOException(
        'SQLSTATE[HY000]: 1227 Access denied; PROCESS privilege required',
        1227
    );
    $wrongDriverCodeFailure = new PDOException(
        'SQLSTATE[42000]: 1045 Access denied; PROCESS privilege required',
        1045
    );
    $missingProcessWordingFailure = new PDOException(
        'SQLSTATE[42000]: 1227 Access denied; additional privilege required',
        1227
    );
    d044Assert(
        d044IsProcessPrivilegeLimitation($processPrivilegeFailure),
        'Specific missing-PROCESS limitation is recognized narrowly'
    );
    d044Assert(
        !d044IsProcessPrivilegeLimitation($unrelatedDatabaseFailure),
        'Unrelated database errors are not treated as PROCESS limitations'
    );
    d044Assert(
        !d044IsProcessPrivilegeLimitation($wrongSqlStateFailure),
        'Wrong SQLSTATE cannot use the owner-attestation fallback'
    );
    d044Assert(
        !d044IsProcessPrivilegeLimitation($wrongDriverCodeFailure),
        'Wrong database driver code cannot use the owner-attestation fallback'
    );
    d044Assert(
        !d044IsProcessPrivilegeLimitation($missingProcessWordingFailure),
        'Generic privilege errors cannot use the owner-attestation fallback'
    );
    d044ExpectRuntimeFailure(
        static fn (): string => d044ResolveConcurrencyEvidence(
            $processPrivilegeFailure,
            '',
            true,
            true
        ),
        'exact local-owner confirmation is required',
        'Absent owner confirmation fails closed for missing PROCESS privilege'
    );
    d044ExpectRuntimeFailure(
        static fn (): string => d044ResolveConcurrencyEvidence(
            $processPrivilegeFailure,
            'INCORRECT-CONFIRMATION',
            true,
            true
        ),
        'exact local-owner confirmation is required',
        'Incorrect owner confirmation fails closed for missing PROCESS privilege'
    );
    d044Same(
        'owner-attested-process-limitation',
        d044ResolveConcurrencyEvidence(
            $processPrivilegeFailure,
            D044_LOCAL_OWNER_CONFIRMATION,
            true,
            true
        ),
        'Exact owner confirmation is accepted for the local PROCESS limitation'
    );
    d044ExpectRuntimeFailure(
        static fn (): string => d044ResolveConcurrencyEvidence(
            $processPrivilegeFailure,
            D044_LOCAL_OWNER_CONFIRMATION,
            false,
            true
        ),
        'allowed only for loopback application and local database hosts',
        'Owner confirmation cannot bypass a non-loopback application URL'
    );
    d044ExpectRuntimeFailure(
        static fn (): string => d044ResolveConcurrencyEvidence(
            $processPrivilegeFailure,
            D044_LOCAL_OWNER_CONFIRMATION,
            true,
            false
        ),
        'allowed only for loopback application and local database hosts',
        'Owner confirmation cannot bypass a non-local database host'
    );
    d044ExpectRuntimeFailure(
        static fn (): string => d044ResolveConcurrencyEvidence(
            $unrelatedDatabaseFailure,
            D044_LOCAL_OWNER_CONFIRMATION,
            true,
            true
        ),
        'reason other than the accepted PROCESS-privilege limitation',
        'Owner confirmation cannot bypass an unrelated database error'
    );
    d044Same(
        'automatically-proven',
        d044ResolveConcurrencyEvidence(null, '', true, true),
        'Automated database-session inspection remains preferred'
    );
    d044ExpectRuntimeFailure(
        static function (): void {
            d044RequireApplyApproval(D044_LOCAL_OWNER_CONFIRMATION);
        },
        'exact one-time live-acceptance approval token is required',
        'Owner confirmation does not independently authorize apply mode'
    );
    d044RequireApplyApproval(D044_LIVE_APPROVAL);
    d044Assert(
        true,
        'Exact apply token remains independently enforceable'
    );

    if ($mode === 'validate') {
        fwrite(STDOUT, "\nD044 LIVE PACKAGE READ-ONLY VALIDATION PASSED.\n");
        fwrite(STDOUT, "Checks passed: {$d044Checks}\n");
        fwrite(STDOUT, "No HTTP request, database connection, session mutation, account change, or file write occurred.\n");
        fwrite(STDOUT, "No documentation, schema, migration, configuration, commit, or push was changed.\n");
        fwrite(STDOUT, "Next boundary: separately approve one apply run with token " . D044_LIVE_APPROVAL . ".\n");
    } else {
        d044RequireApplyApproval($approval);
        d044Assert(
            true,
            'Exact one-time live-acceptance approval token supplied'
        );

        $database = Database::connection();
        $concurrencyEvidence = d044InspectDatabaseConcurrency(
            $database,
            $exclusiveLocalUseConfirmation,
            $applicationIsLoopback,
            $databaseIsLocal
        );

        if ($concurrencyEvidence === 'automatically-proven') {
            d044Assert(
                true,
                'Exclusive database use is automatically proven'
            );
        } else {
            d044Assert(
                $concurrencyEvidence === 'owner-attested-process-limitation',
                'Missing-PROCESS concurrency exclusion is owner-attested locally'
            );
            fwrite(
                STDOUT,
                "Concurrency exclusion: owner-attested for this loopback-only local run; not automatically proven and not a production-safe substitute.\n"
            );
        }

        d044AssertExactTableSet(
            D044_EXPECTED_TABLES,
            d044TableNames($database),
            'Live database has the exact accepted 22-table set'
        );
        $columnStatement = $database->query(
            "SELECT column_type, is_nullable, column_default
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = 'accounts'
               AND column_name = 'must_change_password'"
        );
        $column = $columnStatement->fetch(PDO::FETCH_ASSOC);
        d044Assert(
            is_array($column)
            && strtolower((string) $column['column_type']) === 'tinyint(1)'
            && (string) $column['is_nullable'] === 'NO'
            && (string) $column['column_default'] === '0',
            'Live accounts flag has exact D044 metadata'
        );
        d044Same(
            0,
            (int) $database->query(
                "SELECT COUNT(*) FROM accounts WHERE username LIKE 'd044_live_%'"
            )->fetchColumn(),
            'No prior D044 live fixture remains'
        );

        $baselineState = d044TableState($database);
        $baselineStorage = d044StorageManifest($storage);
        $baselineSources = d044SourceHashes($root, $sourcePaths);
        $baselineAutoIncrement = d044AccountsAutoIncrement($database);
        $baselineAccountCount = $baselineState['counts']['accounts'];
        d044Same(
            $baselineState,
            d044TableState($database),
            'Database baseline is stable before mutation'
        );
        d044Assert(
            $baselineStorage === d044StorageManifest($storage),
            'Protected-storage baseline is stable before mutation'
        );
        d044Same(
            $baselineSources,
            d044SourceHashes($root, $sourcePaths),
            'Source/configuration baseline is stable before mutation'
        );

        $runId = bin2hex(random_bytes(6));
        $fixtureUsername = 'd044_live_' . $runId;
        $temporaryPassword = 'Temp-' . rtrim(strtr(
            base64_encode(random_bytes(18)),
            '+/',
            '-_'
        ), '=');
        $replacementPassword = 'Private-' . rtrim(strtr(
            base64_encode(random_bytes(18)),
            '+/',
            '-_'
        ), '=');
        $wrongPassword = 'Wrong-' . rtrim(strtr(
            base64_encode(random_bytes(18)),
            '+/',
            '-_'
        ), '=');
        $temporaryHash = password_hash($temporaryPassword, PASSWORD_DEFAULT);
        d044Assert(is_string($temporaryHash), 'Controlled temporary credential is hashed in memory');
        $secrets = [
            $temporaryPassword,
            $replacementPassword,
            $wrongPassword,
            $temporaryHash,
        ];
        $temporaryDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'bpc-d044-auth-'
            . $runId;

        if (!mkdir($temporaryDirectory, 0700, true) && !is_dir($temporaryDirectory)) {
            throw new RuntimeException('Temporary live-run directory could not be created.');
        }
        $cleanupNeeded = true;

        foreach ([
            'public',
            'post-register',
            'wrong',
            'unknown',
            'disabled',
            'primary',
            'logout-control',
            'old-password',
            'new-password',
        ] as $cookieName) {
            $cookieFiles[$cookieName] = d044CookieFile(
                $temporaryDirectory,
                $cookieName
            );
        }

        $markerData = [
            'version' => 1,
            'status' => 'prepared',
            'run_id' => $runId,
            'fixture_username' => $fixtureUsername,
            'fixture_account_id' => null,
            'created_at_utc' => gmdate('c'),
            'temporary_directory' => $temporaryDirectory,
            'cookie_files' => array_values($cookieFiles),
            'baseline' => [
                'table_state_sha256' => d044ArrayDigest($baselineState),
                'storage_manifest_sha256' => d044ArrayDigest($baselineStorage),
                'source_manifest_sha256' => d044ArrayDigest($baselineSources),
                'accounts_auto_increment' => $baselineAutoIncrement,
            ],
            'contains_credentials' => false,
        ];
        d044WriteRecoveryMarker($recoveryMarker, $markerData);

        $register = d044Http(
            $baseUrl,
            '/register',
            $cookieFiles['public'],
            null,
            $secrets
        );
        d044Same(303, $register['status'], 'Logged-out GET /register redirects neutrally');
        d044Same('/login', d044Header($register['headers'], 'Location'), 'GET /register targets login');
        $registerLogin = d044Http(
            $baseUrl,
            '/login',
            $cookieFiles['public'],
            null,
            $secrets
        );
        d044Same(200, $registerLogin['status'], 'Neutral registration redirect reaches login');
        d044Assert(
            str_contains($registerLogin['body'], 'accounts are issued by the institution'),
            'GET /register displays the neutral institution-issued-account notice'
        );

        $postRegister = d044Http(
            $baseUrl,
            '/register',
            $cookieFiles['post-register'],
            http_build_query([
                'username' => $fixtureUsername,
                'display_name' => 'D044 Manipulated Registration',
                'password' => $temporaryPassword,
                'password_confirmation' => $temporaryPassword,
                'role' => 'admin',
            ]),
            $secrets
        );
        d044Same(405, $postRegister['status'], 'POST /register fails closed');
        d044Same('GET', d044Header($postRegister['headers'], 'Allow'), 'POST /register advertises GET only');
        d044Assert(
            d044Header($postRegister['headers'], 'Set-Cookie') === null
            && d044CookieValue(
                $cookieFiles['post-register'],
                'bpc_learnshare_session'
            ) === null,
            'POST /register creates no application session'
        );
        d044Same(
            $baselineState,
            d044TableState($database),
            'Public registration requests create no database state'
        );
        d044Assert(
            $baselineStorage === d044StorageManifest($storage),
            'Public registration requests create no protected file'
        );
        d044Same(
            $baselineSources,
            d044SourceHashes($root, $sourcePaths),
            'Protected source/configuration hashes remain unchanged before fixture creation'
        );

        $insert = $database->prepare(
            "INSERT INTO accounts (
                username,
                password_hash,
                display_name,
                role,
                account_status,
                must_change_password
             ) VALUES (
                :username,
                :password_hash,
                :display_name,
                'student',
                'active',
                1
             )"
        );
        $insert->execute([
            'username' => $fixtureUsername,
            'password_hash' => $temporaryHash,
            'display_name' => 'D044 Live Acceptance Fixture',
        ]);
        $fixtureId = (int) $database->lastInsertId();
        d044Assert($fixtureId > 0, 'One controlled flagged Student fixture is inserted directly');
        $fixtureState = d044FixtureState($database, $fixtureUsername);
        d044Assert(
            is_array($fixtureState)
            && (string) $fixtureState['role'] === 'student'
            && (string) $fixtureState['account_status'] === 'active'
            && (int) $fixtureState['must_change_password'] === 1
            && password_verify(
                $temporaryPassword,
                (string) $fixtureState['password_hash']
            ),
            'Fixture has exact Active Student and mandatory-change state'
        );
        d044Same(
            $baselineState['counts']['audit_log'],
            (int) $database->query('SELECT COUNT(*) FROM audit_log')->fetchColumn(),
            'Direct fixture setup creates no provisioning audit evidence'
        );

        d044GenericLoginFailure(
            $baseUrl,
            $cookieFiles['wrong'],
            $fixtureUsername,
            $wrongPassword,
            'Wrong temporary credential',
            $secrets
        );
        d044GenericLoginFailure(
            $baseUrl,
            $cookieFiles['unknown'],
            'd044_unknown_' . $runId,
            $wrongPassword,
            'Unknown Account Identifier',
            $secrets
        );

        $statusUpdate = $database->prepare(
            'UPDATE accounts SET account_status = :status WHERE id = :id AND username = :username'
        );
        $statusUpdate->execute([
            'status' => 'disabled',
            'id' => $fixtureId,
            'username' => $fixtureUsername,
        ]);
        d044Same(1, $statusUpdate->rowCount(), 'Owned fixture is temporarily Disabled for generic-login validation');
        d044GenericLoginFailure(
            $baseUrl,
            $cookieFiles['disabled'],
            $fixtureUsername,
            $temporaryPassword,
            'Disabled fixture',
            $secrets
        );
        $statusUpdate->execute([
            'status' => 'active',
            'id' => $fixtureId,
            'username' => $fixtureUsername,
        ]);
        d044Same(1, $statusUpdate->rowCount(), 'Owned fixture returns to Active for the D044 journey');

        $primaryLogin = d044SuccessfulLogin(
            $baseUrl,
            $cookieFiles['primary'],
            $fixtureUsername,
            $temporaryPassword,
            '/account/change-password',
            'Temporary-credential login',
            $secrets
        );

        foreach ([
            '/dashboard' => 'dashboard',
            '/resources' => 'repository',
            '/resources/upload' => 'upload',
            '/moderation' => 'moderation',
            '/admin/accounts' => 'representative administration path',
            '/resources?search_mode=semantic&q=d044-guard-check' => 'AI-assisted search path',
        ] as $path => $label) {
            $response = d044Http(
                $baseUrl,
                $path,
                $cookieFiles['primary'],
                null,
                $secrets
            );
            d044Same(303, $response['status'], 'Flagged account cannot access ' . $label);
            d044Same(
                '/account/change-password',
                d044Header($response['headers'], 'Location'),
                'Flagged ' . $label . ' request reaches mandatory password change'
            );
        }

        $passwordPage = d044Http(
            $baseUrl,
            '/account/change-password',
            $cookieFiles['primary'],
            null,
            $secrets
        );
        d044Same(200, $passwordPage['status'], 'Flagged account may open password change');
        d044Assert(
            str_contains($passwordPage['body'], 'action="/logout"'),
            'Flagged account has a logout action'
        );
        $oldCsrf = d044Csrf($passwordPage['body']);

        d044SuccessfulLogin(
            $baseUrl,
            $cookieFiles['logout-control'],
            $fixtureUsername,
            $temporaryPassword,
            '/account/change-password',
            'Logout-control temporary login',
            $secrets
        );
        $logoutPage = d044Http(
            $baseUrl,
            '/account/change-password',
            $cookieFiles['logout-control'],
            null,
            $secrets
        );
        $logout = d044Http(
            $baseUrl,
            '/logout',
            $cookieFiles['logout-control'],
            http_build_query(['_token' => d044Csrf($logoutPage['body'])]),
            $secrets
        );
        d044Same(303, $logout['status'], 'Flagged account may log out');
        d044Same('/login', d044Header($logout['headers'], 'Location'), 'Flagged logout returns to login');
        $loggedOut = d044Http(
            $baseUrl,
            '/dashboard',
            $cookieFiles['logout-control'],
            null,
            $secrets
        );
        d044Same(303, $loggedOut['status'], 'Logged-out flagged session loses protected access');
        d044Same('/login', d044Header($loggedOut['headers'], 'Location'), 'Logged-out flagged session reaches login');

        $beforeInvalidState = d044FixtureState($database, $fixtureUsername);
        d044Assert(is_array($beforeInvalidState), 'Fixture credential state is readable before invalid changes');
        $missingCsrf = d044Http(
            $baseUrl,
            '/account/change-password',
            $cookieFiles['primary'],
            http_build_query([
                'password' => $replacementPassword,
                'password_confirmation' => $replacementPassword,
            ]),
            $secrets
        );
        d044Same(403, $missingCsrf['status'], 'Password change without CSRF is rejected');
        d044AssertCredentialStateUnchanged(
            $beforeInvalidState,
            d044FixtureState($database, $fixtureUsername),
            'Missing-CSRF rejection preserves hash and flag'
        );
        $invalidCsrf = d044Http(
            $baseUrl,
            '/account/change-password',
            $cookieFiles['primary'],
            http_build_query([
                '_token' => str_repeat('0', 64),
                'password' => $replacementPassword,
                'password_confirmation' => $replacementPassword,
            ]),
            $secrets
        );
        d044Same(403, $invalidCsrf['status'], 'Password change with invalid CSRF is rejected');
        d044AssertCredentialStateUnchanged(
            $beforeInvalidState,
            d044FixtureState($database, $fixtureUsername),
            'Invalid-CSRF rejection preserves hash and flag'
        );
        $mismatch = d044Http(
            $baseUrl,
            '/account/change-password',
            $cookieFiles['primary'],
            http_build_query([
                '_token' => $oldCsrf,
                'password' => $replacementPassword,
                'password_confirmation' => $wrongPassword,
            ]),
            $secrets
        );
        d044Same(422, $mismatch['status'], 'Mismatched password confirmation is rejected');
        d044Assert(
            str_contains($mismatch['body'], 'Password confirmation does not match.'),
            'Mismatch receives the bounded validation message'
        );
        d044AssertCredentialStateUnchanged(
            $beforeInvalidState,
            d044FixtureState($database, $fixtureUsername),
            'Confirmation mismatch preserves hash and flag'
        );
        $reuse = d044Http(
            $baseUrl,
            '/account/change-password',
            $cookieFiles['primary'],
            http_build_query([
                '_token' => $oldCsrf,
                'password' => $temporaryPassword,
                'password_confirmation' => $temporaryPassword,
            ]),
            $secrets
        );
        d044Same(422, $reuse['status'], 'Temporary-password reuse is rejected');
        d044Assert(
            str_contains($reuse['body'], 'different from the temporary password'),
            'Temporary-password reuse receives the bounded validation message'
        );
        d044AssertCredentialStateUnchanged(
            $beforeInvalidState,
            d044FixtureState($database, $fixtureUsername),
            'Temporary-password reuse preserves hash and flag'
        );

        $sessionBeforeChange = d044CookieValue(
            $cookieFiles['primary'],
            'bpc_learnshare_session'
        );
        d044Assert(
            is_string($sessionBeforeChange) && $sessionBeforeChange !== '',
            'Pre-change authenticated session is present'
        );
        $validChange = d044Http(
            $baseUrl,
            '/account/change-password',
            $cookieFiles['primary'],
            http_build_query([
                '_token' => $oldCsrf,
                'password' => $replacementPassword,
                'password_confirmation' => $replacementPassword,
            ]),
            $secrets
        );
        d044Same(303, $validChange['status'], 'Valid mandatory password change succeeds');
        d044Same('/dashboard', d044Header($validChange['headers'], 'Location'), 'Valid change reaches dashboard');
        $sessionAfterChange = d044CookieValue(
            $cookieFiles['primary'],
            'bpc_learnshare_session'
        );
        d044Assert(
            is_string($sessionAfterChange)
            && $sessionAfterChange !== ''
            && !hash_equals($sessionBeforeChange, $sessionAfterChange),
            'Mandatory password change regenerates the session identifier'
        );
        $changedState = d044FixtureState($database, $fixtureUsername);
        d044Assert(
            is_array($changedState)
            && (int) $changedState['must_change_password'] === 0
            && password_verify(
                $replacementPassword,
                (string) $changedState['password_hash']
            )
            && !password_verify(
                $temporaryPassword,
                (string) $changedState['password_hash']
            ),
            'Valid change stores only the new hash and clears the flag'
        );
        $secrets[] = (string) $changedState['password_hash'];
        $dashboard = d044Http(
            $baseUrl,
            '/dashboard',
            $cookieFiles['primary'],
            null,
            $secrets
        );
        d044Same(200, $dashboard['status'], 'Changed account reaches dashboard');
        $newCsrf = d044Csrf($dashboard['body']);
        d044Assert(!hash_equals($oldCsrf, $newCsrf), 'Mandatory change rotates the CSRF token');
        $oldTokenResponse = d044Http(
            $baseUrl,
            '/logout',
            $cookieFiles['primary'],
            http_build_query(['_token' => $oldCsrf]),
            $secrets
        );
        d044Same(403, $oldTokenResponse['status'], 'Old CSRF token is rejected after rotation');
        d044Same(
            200,
            d044Http(
                $baseUrl,
                '/dashboard',
                $cookieFiles['primary'],
                null,
                $secrets
            )['status'],
            'Rejected old CSRF token does not end the valid session'
        );
        $primaryLogout = d044Http(
            $baseUrl,
            '/logout',
            $cookieFiles['primary'],
            http_build_query(['_token' => $newCsrf]),
            $secrets
        );
        d044Same(303, $primaryLogout['status'], 'Changed account logs out with the rotated CSRF token');

        d044GenericLoginFailure(
            $baseUrl,
            $cookieFiles['old-password'],
            $fixtureUsername,
            $temporaryPassword,
            'Old temporary credential',
            $secrets
        );
        d044SuccessfulLogin(
            $baseUrl,
            $cookieFiles['new-password'],
            $fixtureUsername,
            $replacementPassword,
            '/dashboard',
            'New private credential login',
            $secrets
        );
        $newDashboard = d044Http(
            $baseUrl,
            '/dashboard',
            $cookieFiles['new-password'],
            null,
            $secrets
        );
        d044Same(200, $newDashboard['status'], 'Student dashboard is authorized after password change');
        d044Assert(str_contains($newDashboard['body'], 'Student'), 'Live role reload identifies the fixture as Student');
        d044Same(
            200,
            d044Http(
                $baseUrl,
                '/resources',
                $cookieFiles['new-password'],
                null,
                $secrets
            )['status'],
            'Student repository access is authorized after password change'
        );
        d044Same(
            200,
            d044Http(
                $baseUrl,
                '/resources/upload',
                $cookieFiles['new-password'],
                null,
                $secrets
            )['status'],
            'Student upload form is authorized after password change'
        );

        $statusUpdate->execute([
            'status' => 'disabled',
            'id' => $fixtureId,
            'username' => $fixtureUsername,
        ]);
        d044Same(1, $statusUpdate->rowCount(), 'Authenticated owned fixture is Disabled directly for live revalidation');
        $disabledProtected = d044Http(
            $baseUrl,
            '/dashboard',
            $cookieFiles['new-password'],
            null,
            $secrets
        );
        d044Same(303, $disabledProtected['status'], 'Disabled authenticated fixture loses protected access immediately');
        d044Same('/login', d044Header($disabledProtected['headers'], 'Location'), 'Disabled live session returns to login');
        $statusUpdate->execute([
            'status' => 'active',
            'id' => $fixtureId,
            'username' => $fixtureUsername,
        ]);
        d044Same(1, $statusUpdate->rowCount(), 'Owned fixture returns to Active only for exact cleanup');

        d044DestroyCookieSessions(array_values($cookieFiles));
        d044DeleteFixture(
            $database,
            $fixtureId,
            $fixtureUsername,
            $baselineAccountCount
        );
        $fixtureId = 0;
        d044Same(
            $baselineState,
            d044TableState($database),
            'All 22 table counts and existing-row fingerprints are restored'
        );
        d044Assert(
            $baselineStorage === d044StorageManifest($storage),
            'Protected-storage manifest is restored exactly'
        );
        d044Same(
            $baselineSources,
            d044SourceHashes($root, $sourcePaths),
            'Source and ignored configuration hashes are restored exactly'
        );
        d044Assert(
            d044AutoIncrementIsAcceptable(
                $baselineAutoIncrement,
                d044AccountsAutoIncrement($database)
            ),
            'Temporary fixture AUTO_INCREMENT advancement is accepted as harmless'
        );
        d044RemoveTempDirectory($temporaryDirectory);
        $temporaryDirectory = '';
        d044RemoveRecoveryMarker($recoveryMarker);
        $cleanupNeeded = false;
        $cleanupVerified = true;

        fwrite(STDOUT, "\nD044 MANDATORY PASSWORD LIVE ACCEPTANCE PASSED.\n");
        fwrite(STDOUT, "Checks passed: {$d044Checks}\n");
        fwrite(STDOUT, "Temporary accounts remaining: 0.\n");
        fwrite(STDOUT, "Test-owned sessions remaining: 0.\n");
        fwrite(STDOUT, "Logical database rows, protected storage, source, and configuration restored; AUTO_INCREMENT advancement is accepted as harmless: Yes.\n");
        fwrite(STDOUT, "No Admin provisioning/reset, audit-atomicity, CSV, MIS, sole-Admin, schema, migration, documentation, commit, or push claim was made.\n");
    }
} catch (Throwable $exception) {
    $failed = true;
    fwrite(STDERR, "\nD044 MANDATORY PASSWORD LIVE PACKAGE FAILED\n");
    fwrite(STDERR, $exception->getMessage() . "\n");
} finally {
    if (
        $mode === 'apply'
        && $cleanupNeeded
        && $database instanceof PDO
        && is_array($baselineState)
        && is_array($baselineStorage)
        && is_array($baselineSources)
        && is_int($baselineAutoIncrement)
        && is_int($baselineAccountCount)
    ) {
        try {
            d044DestroyCookieSessions(array_values($cookieFiles));

            if (
                $fixtureUsername !== ''
                && d044FixtureState($database, $fixtureUsername) !== null
            ) {
                d044DeleteFixture(
                    $database,
                    $fixtureId,
                    $fixtureUsername,
                    $baselineAccountCount
                );
                $fixtureId = 0;
            }

            if (!d044AutoIncrementIsAcceptable(
                $baselineAutoIncrement,
                d044AccountsAutoIncrement($database)
            )) {
                throw new RuntimeException(
                    'Accounts AUTO_INCREMENT moved below its recorded baseline.'
                );
            }

            if ($baselineState !== d044TableState($database)) {
                throw new RuntimeException('Database did not return to its exact logical baseline.');
            }

            if ($baselineStorage !== d044StorageManifest($storage)) {
                throw new RuntimeException('Protected storage did not return to baseline.');
            }

            if ($baselineSources !== d044SourceHashes($root, $sourcePaths)) {
                throw new RuntimeException('Source or configuration changed during the failed run.');
            }

            d044RemoveTempDirectory($temporaryDirectory);
            $temporaryDirectory = '';
            d044RemoveRecoveryMarker($recoveryMarker);
            $cleanupNeeded = false;
            $cleanupVerified = true;
            fwrite(STDERR, "Emergency targeted cleanup and exact logical baseline restoration verified.\n");
        } catch (Throwable $cleanupException) {
            $failed = true;
            fwrite(
                STDERR,
                'EMERGENCY CLEANUP INCOMPLETE: '
                . $cleanupException->getMessage()
                . "\n"
            );
            fwrite(
                STDERR,
                'The credential-free ignored recovery marker remains for controlled review.\n'
            );
        }
    }

    if (
        $mode === 'apply'
        && $failed
        && !$cleanupVerified
        && is_file($recoveryMarker)
    ) {
        fwrite(
            STDERR,
            "Do not rerun apply mode until the recorded test-owned fixture and sessions are reviewed.\n"
        );
    }
}

exit($failed ? 1 : 0);
