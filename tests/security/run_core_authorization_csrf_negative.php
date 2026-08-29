<?php

declare(strict_types=1);

/**
 * Evidence status:
 * - The accepted 77-check apply run was executed against the pre-D044 application.
 * - That accepted one-time apply run must not be executed again.
 * - The public-registration checks marked below are historical baseline evidence
 *   superseded by D044; they do not prove that D044 is implemented or tested.
 * - The remaining authorization and CSRF assertions are reusable only after a
 *   future harness revision provides D044-compatible account fixtures.
 */

use BpcLearnShare\Core\Database;

require dirname(__DIR__, 2) . '/src/bootstrap.php';

const NEG_APPROVAL = 'CORE-AUTHZ-CSRF-LIVE-ACCEPTANCE';

$negChecks = 0;
$mode = 'validate';
$approval = '';

foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--mode=')) {
        $mode = substr($argument, 7);
    } elseif (str_starts_with($argument, '--approve=')) {
        $approval = substr($argument, 10);
    } else {
        throw new RuntimeException('Unknown argument: ' . $argument);
    }
}

if (!in_array($mode, ['validate', 'apply'], true)) {
    throw new RuntimeException('Mode must be validate or apply.');
}

function negAssert(bool $condition, string $label): void
{
    global $negChecks;

    if (!$condition) {
        throw new RuntimeException($label . ' failed.');
    }

    $negChecks++;
    fwrite(STDOUT, "[PASS] {$label}\n");
}

/** @param mixed $actual */
function negSame(mixed $expected, mixed $actual, string $label): void
{
    negAssert(
        $expected === $actual,
        sprintf(
            '%s (expected %s; received %s)',
            $label,
            var_export($expected, true),
            var_export($actual, true)
        )
    );
}

/** @return array<string, int> */
function negTableCounts(PDO $database): array
{
    $tables = $database->query(
        "SELECT table_name FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
         ORDER BY table_name"
    )->fetchAll(PDO::FETCH_COLUMN);
    $counts = [];

    foreach ($tables as $table) {
        $name = (string) $table;

        if (!preg_match('/^[a-z0-9_]+$/', $name)) {
            throw new RuntimeException('Unsafe table name encountered.');
        }

        $counts[$name] = (int) $database
            ->query("SELECT COUNT(*) FROM `{$name}`")
            ->fetchColumn();
    }

    return $counts;
}

/** @return array<string, array{bytes: int, sha256: string}> */
function negStorageManifest(string $directory): array
{
    $items = scandir($directory);

    if ($items === false) {
        throw new RuntimeException('Protected storage cannot be inspected.');
    }

    $manifest = [];

    foreach ($items as $name) {
        $path = $directory . DIRECTORY_SEPARATOR . $name;

        if ($name === '.' || $name === '..' || !is_file($path)) {
            continue;
        }

        $size = filesize($path);
        $hash = hash_file('sha256', $path);

        if (!is_int($size) || !is_string($hash)) {
            throw new RuntimeException('Protected file cannot be inspected.');
        }

        $manifest[$name] = ['bytes' => $size, 'sha256' => $hash];
    }

    ksort($manifest);

    return $manifest;
}

/**
 * @param array<string, mixed>|string|null $post
 * @return array{status: int, headers: string, body: string}
 */
function negHttp(
    string $url,
    string $cookieFile,
    array|string|null $post = null
): array {
    $handle = curl_init($url);

    if ($handle === false) {
        throw new RuntimeException('Unable to initialize local HTTP request.');
    }

    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_USERAGENT => 'BPC-LearnShare-Authorization-CSRF-Negative/1.0',
    ]);

    if ($post !== null) {
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $post);
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

    return [
        'status' => $status,
        'headers' => substr($response, 0, $headerBytes),
        'body' => substr($response, $headerBytes),
    ];
}

function negCsrf(string $html): string
{
    if (!preg_match('/name="_token"\s+value="([a-f0-9]{64})"/', $html, $matches)) {
        throw new RuntimeException('CSRF token was not found in the local form.');
    }

    return $matches[1];
}

function negCookie(string $directory, string $name): string
{
    $path = $directory . DIRECTORY_SEPARATOR . $name . '.txt';

    if (!touch($path)) {
        throw new RuntimeException('Unable to create temporary cookie file.');
    }

    return $path;
}

function negAccountCount(PDO $database, string $username): int
{
    $statement = $database->prepare(
        'SELECT COUNT(*) FROM accounts WHERE username = :username'
    );
    $statement->execute(['username' => $username]);

    return (int) $statement->fetchColumn();
}

function negLogin(
    string $baseUrl,
    string $cookie,
    string $username,
    string $password,
    string $label
): void {
    $page = negHttp($baseUrl . '/login', $cookie);
    negSame(200, $page['status'], $label . ' login form opens');
    $response = negHttp(
        $baseUrl . '/login',
        $cookie,
        http_build_query([
            '_token' => negCsrf($page['body']),
            'username' => $username,
            'password' => $password,
        ])
    );
    negSame(303, $response['status'], $label . ' signs in');
}

/** @param list<string> $usernames */
function negCleanup(PDO $database, array $usernames): void
{
    $delete = $database->prepare(
        'DELETE FROM accounts WHERE username = :username'
    );

    $database->beginTransaction();

    try {
        foreach ($usernames as $username) {
            $delete->execute(['username' => $username]);
        }

        $database->commit();
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }

        throw $exception;
    }
}

$root = dirname(__DIR__, 2);
$baseUrl = 'http://127.0.0.1:8081';
$storage = $root . '/storage/uploads/resources';
$database = Database::connection();
$tempDirectory = '';
$cleanupUsernames = [];
$cleanupNeeded = false;
$baselineCounts = null;
$baselineStorage = null;
$failed = false;

fwrite(STDOUT, "=== CORE AUTHORIZATION AND CSRF NEGATIVE ACCEPTANCE ===\n");
fwrite(STDOUT, "Mode: {$mode}\n\n");

try {
    negAssert(
        str_starts_with($baseUrl, 'http://127.0.0.1:'),
        'Target is the local application only'
    );
    negAssert(extension_loaded('curl'), 'PHP cURL extension is available');
    negAssert(is_dir($storage), 'Protected resource storage exists');
    negAssert(
        is_file($root . '/public/index.php'),
        'Application front controller exists'
    );
    negAssert(
        is_file($root . '/src/Security/Csrf.php'),
        'CSRF implementation exists'
    );
    negAssert(
        is_file($root . '/src/auth/AuthService.php'),
        'Authentication service exists'
    );
    negSame(22, count(negTableCounts($database)), 'Database has 22 accepted tables');
    negSame(
        0,
        (int) $database->query(
            "SELECT COUNT(*) FROM accounts
             WHERE username LIKE 'negative_accept_%'"
        )->fetchColumn(),
        'No prior negative-checkpoint account remains'
    );
    $healthCookie = tempnam(sys_get_temp_dir(), 'neg-cookie-');
    negAssert(is_string($healthCookie), 'Temporary health cookie is available');
    $health = negHttp($baseUrl . '/health', $healthCookie);
    unlink($healthCookie);
    negSame(200, $health['status'], 'Local health route responds');
    negAssert(
        str_contains($health['body'], 'MariaDB connection'),
        'Health route includes its database check'
    );

    if ($mode === 'validate') {
        fwrite(STDOUT, "\nAUTHORIZATION AND CSRF VALIDATION PASSED.\n");
        fwrite(STDOUT, "Checks passed: {$negChecks}\n");
        fwrite(STDOUT, "No account, resource, history row, or file was created.\n");
        fwrite(STDOUT, "No AI/provider request, schema/register change, commit, or push occurred.\n");
        fwrite(STDOUT, "Next boundary: separately approve one apply run with token " . NEG_APPROVAL . ".\n");
        exit(0);
    }

    negSame(NEG_APPROVAL, $approval, 'Exact one-time approval token supplied');
    $baselineCounts = negTableCounts($database);
    $baselineStorage = negStorageManifest($storage);
    $runId = bin2hex(random_bytes(6));
    $prefix = 'negative_accept_' . $runId . '_';
    $studentUsername = $prefix . 'student';
    $moderatorUsername = $prefix . 'moderator';
    $adminUsername = $prefix . 'admin';
    $disabledUsername = $prefix . 'disabled';
    $cleanupUsernames = [
        $studentUsername,
        $moderatorUsername,
        $adminUsername,
        $disabledUsername,
    ];
    $studentPassword = 'Negative-Student-' . bin2hex(random_bytes(10));
    $staffPassword = 'Negative-Staff-' . bin2hex(random_bytes(10));
    $disabledPassword = 'Negative-Disabled-' . bin2hex(random_bytes(10));
    $tempDirectory = sys_get_temp_dir() . '/bpc-authz-csrf-' . $runId;

    if (!mkdir($tempDirectory, 0700, true) && !is_dir($tempDirectory)) {
        throw new RuntimeException('Temporary run directory cannot be created.');
    }

    $cleanupNeeded = true;
    $publicCookie = negCookie($tempDirectory, 'public');
    $studentCookie = negCookie($tempDirectory, 'student');
    $moderatorCookie = negCookie($tempDirectory, 'moderator');
    $adminCookie = negCookie($tempDirectory, 'admin');

    // Reusable unauthenticated protected-route authorization checks.
    negSame(
        303,
        negHttp($baseUrl . '/dashboard', $publicCookie)['status'],
        'Unauthenticated dashboard request redirects'
    );
    negSame(
        303,
        negHttp($baseUrl . '/resources', $publicCookie)['status'],
        'Unauthenticated repository request redirects'
    );
    negSame(
        303,
        negHttp($baseUrl . '/moderation', $publicCookie)['status'],
        'Unauthenticated moderation request redirects'
    );

    // Historical pre-D044 public-registration checks.
    // Retained as accepted baseline evidence; superseded by D044 and not proof
    // that D044 account provisioning or mandatory password change is implemented.
    foreach (['teacher_instructor', 'moderator', 'admin'] as $role) {
        $username = $prefix . 'inject_' . $role;
        $page = negHttp($baseUrl . '/register', $publicCookie);
        $response = negHttp(
            $baseUrl . '/register',
            $publicCookie,
            http_build_query([
                '_token' => negCsrf($page['body']),
                'username' => $username,
                'display_name' => 'Injected Role Attempt',
                'password' => $studentPassword,
                'password_confirmation' => $studentPassword,
                'role' => $role,
            ])
        );
        negSame(422, $response['status'], 'Public ' . $role . ' role injection is rejected');
        negAssert(
            str_contains($response['body'], 'cannot select an account role'),
            'Role-injection rejection is explained safely'
        );
        negSame(0, negAccountCount($database, $username), 'Injected role creates no account');
    }

    $missingCsrfUsername = $prefix . 'missing_csrf';
    $response = negHttp(
        $baseUrl . '/register',
        $publicCookie,
        http_build_query([
            'username' => $missingCsrfUsername,
            'display_name' => 'Missing CSRF Attempt',
            'password' => $studentPassword,
            'password_confirmation' => $studentPassword,
        ])
    );
    negSame(403, $response['status'], 'Registration without CSRF is rejected');
    negAssert(str_contains($response['body'], 'security check failed'), 'Missing-CSRF response is safe');
    negSame(0, negAccountCount($database, $missingCsrfUsername), 'Missing-CSRF registration creates no account');

    $invalidCsrfUsername = $prefix . 'invalid_csrf';
    $response = negHttp(
        $baseUrl . '/register',
        $publicCookie,
        http_build_query([
            '_token' => str_repeat('0', 64),
            'username' => $invalidCsrfUsername,
            'display_name' => 'Invalid CSRF Attempt',
            'password' => $studentPassword,
            'password_confirmation' => $studentPassword,
        ])
    );
    negSame(403, $response['status'], 'Registration with invalid CSRF is rejected');
    negSame(0, negAccountCount($database, $invalidCsrfUsername), 'Invalid-CSRF registration creates no account');

    $page = negHttp($baseUrl . '/register', $studentCookie);
    $response = negHttp(
        $baseUrl . '/register',
        $studentCookie,
        http_build_query([
            '_token' => negCsrf($page['body']),
            'username' => $studentUsername,
            'display_name' => 'Negative Acceptance Student',
            'password' => $studentPassword,
            'password_confirmation' => $studentPassword,
        ])
    );
    negSame(303, $response['status'], 'Control Student registration succeeds');
    negSame(1, negAccountCount($database, $studentUsername), 'Control Student exists once');

    // Pre-D044 controlled fixture setup.
    // This does not implement or validate D044 institution provisioning or
    // must_change_password behavior.
    $hash = password_hash($staffPassword, PASSWORD_DEFAULT);
    $disabledHash = password_hash($disabledPassword, PASSWORD_DEFAULT);
    negAssert(is_string($hash) && is_string($disabledHash), 'Temporary account passwords are hashed');
    $insert = $database->prepare(
        'INSERT INTO accounts (
            username, password_hash, display_name, role, account_status
         ) VALUES (
            :username, :password_hash, :display_name, :role, :account_status
         )'
    );

    foreach ([
        [$moderatorUsername, $hash, 'Negative Acceptance Moderator', 'moderator', 'active'],
        [$adminUsername, $hash, 'Negative Acceptance Admin', 'admin', 'active'],
        [$disabledUsername, $disabledHash, 'Negative Disabled Student', 'student', 'disabled'],
    ] as [$username, $passwordHash, $displayName, $role, $status]) {
        $insert->execute([
            'username' => $username,
            'password_hash' => $passwordHash,
            'display_name' => $displayName,
            'role' => $role,
            'account_status' => $status,
        ]);
    }
    negSame(4, array_sum(array_map(
        static fn (string $username): int => negAccountCount($database, $username),
        $cleanupUsernames
    )), 'Exactly four temporary control accounts exist');

    // Reusable login, session, role-authorization, and CSRF checks.
    $missingLoginCookie = negCookie($tempDirectory, 'login-missing');
    $response = negHttp(
        $baseUrl . '/login',
        $missingLoginCookie,
        http_build_query(['username' => $studentUsername, 'password' => $studentPassword])
    );
    negSame(403, $response['status'], 'Login without CSRF is rejected');
    negSame(303, negHttp($baseUrl . '/dashboard', $missingLoginCookie)['status'], 'Missing-CSRF login creates no session');

    $invalidLoginCookie = negCookie($tempDirectory, 'login-invalid');
    $response = negHttp(
        $baseUrl . '/login',
        $invalidLoginCookie,
        http_build_query([
            '_token' => str_repeat('0', 64),
            'username' => $studentUsername,
            'password' => $studentPassword,
        ])
    );
    negSame(403, $response['status'], 'Login with invalid CSRF is rejected');
    negSame(303, negHttp($baseUrl . '/dashboard', $invalidLoginCookie)['status'], 'Invalid-CSRF login creates no session');

    foreach ([
        ['unknown', $prefix . 'unknown', $studentPassword],
        ['wrong-password', $studentUsername, $studentPassword . '-wrong'],
        ['disabled', $disabledUsername, $disabledPassword],
    ] as [$label, $username, $password]) {
        $cookie = negCookie($tempDirectory, 'failure-' . $label);
        $page = negHttp($baseUrl . '/login', $cookie);
        $response = negHttp(
            $baseUrl . '/login',
            $cookie,
            http_build_query([
                '_token' => negCsrf($page['body']),
                'username' => $username,
                'password' => $password,
            ])
        );
        negSame(422, $response['status'], $label . ' login fails generically');
        negAssert(
            str_contains($response['body'], 'Unable to sign in with those credentials.'),
            $label . ' login uses the generic message'
        );
        negSame(303, negHttp($baseUrl . '/dashboard', $cookie)['status'], $label . ' login creates no session');
    }

    negLogin($baseUrl, $studentCookie, $studentUsername, $studentPassword, 'Control Student');
    $studentDashboard = negHttp($baseUrl . '/dashboard', $studentCookie);
    negSame(200, $studentDashboard['status'], 'Student dashboard opens');
    $studentToken = negCsrf($studentDashboard['body']);
    $response = negHttp($baseUrl . '/moderation', $studentCookie);
    negSame(403, $response['status'], 'Student moderation queue access is denied');
    negAssert(str_contains($response['body'], 'moderation staff'), 'Student receives bounded moderation denial');
    negSame(
        403,
        negHttp($baseUrl . '/moderation/resources/999999', $studentCookie)['status'],
        'Student direct review access is denied'
    );
    negSame(
        403,
        negHttp(
            $baseUrl . '/moderation/resources/999999/decision',
            $studentCookie,
            http_build_query(['_token' => $studentToken, 'action' => 'approve'])
        )['status'],
        'Student direct moderation POST is denied'
    );

    $resourceCount = (int) $database->query('SELECT COUNT(*) FROM resources')->fetchColumn();
    negSame(
        403,
        negHttp($baseUrl . '/resources/upload', $studentCookie, http_build_query([]))['status'],
        'Student upload POST without CSRF is rejected'
    );
    negSame(
        403,
        negHttp(
            $baseUrl . '/resources/upload',
            $studentCookie,
            http_build_query(['_token' => str_repeat('0', 64)])
        )['status'],
        'Student upload POST with invalid CSRF is rejected'
    );
    negSame(
        $resourceCount,
        (int) $database->query('SELECT COUNT(*) FROM resources')->fetchColumn(),
        'Rejected upload requests create no resource'
    );

    negSame(
        403,
        negHttp($baseUrl . '/logout', $studentCookie, http_build_query([]))['status'],
        'Logout without CSRF is rejected'
    );
    negSame(200, negHttp($baseUrl . '/dashboard', $studentCookie)['status'], 'Missing-CSRF logout keeps the valid session');
    negSame(
        403,
        negHttp(
            $baseUrl . '/logout',
            $studentCookie,
            http_build_query(['_token' => str_repeat('0', 64)])
        )['status'],
        'Logout with invalid CSRF is rejected'
    );
    negSame(200, negHttp($baseUrl . '/dashboard', $studentCookie)['status'], 'Invalid-CSRF logout keeps the valid session');
    negSame(
        303,
        negHttp(
            $baseUrl . '/logout',
            $studentCookie,
            http_build_query(['_token' => $studentToken])
        )['status'],
        'Logout with valid CSRF succeeds'
    );
    negSame(303, negHttp($baseUrl . '/dashboard', $studentCookie)['status'], 'Logged-out Student loses protected access');

    foreach ([
        ['Moderator', $moderatorCookie, $moderatorUsername],
        ['Admin', $adminCookie, $adminUsername],
    ] as [$label, $cookie, $username]) {
        negLogin($baseUrl, $cookie, $username, $staffPassword, $label);
        negSame(200, negHttp($baseUrl . '/moderation', $cookie)['status'], $label . ' may open moderation');
        $response = negHttp($baseUrl . '/resources/upload', $cookie);
        negSame(403, $response['status'], $label . ' ordinary upload access is denied');
        negAssert(
            str_contains($response['body'], 'cannot submit ordinary uploads'),
            $label . ' upload denial is explained safely'
        );
    }

    negSame(
        403,
        negHttp(
            $baseUrl . '/moderation/resources/999999/decision',
            $moderatorCookie,
            http_build_query(['action' => 'approve'])
        )['status'],
        'Moderator decision without CSRF is rejected'
    );
    negSame(
        403,
        negHttp(
            $baseUrl . '/moderation/resources/999999/decision',
            $moderatorCookie,
            http_build_query(['_token' => str_repeat('0', 64), 'action' => 'approve'])
        )['status'],
        'Moderator decision with invalid CSRF is rejected'
    );
    negSame(200, negHttp($baseUrl . '/dashboard', $moderatorCookie)['status'], 'Rejected moderation requests keep the valid staff session');

    // Reusable cleanup and database/protected-storage restoration checks.
    negCleanup($database, $cleanupUsernames);
    $cleanupNeeded = false;
    negAssert($baselineCounts === negTableCounts($database), 'All 22 table row counts are restored');
    negAssert($baselineStorage === negStorageManifest($storage), 'Protected storage manifest is unchanged');

    fwrite(STDOUT, "\nCORE AUTHORIZATION AND CSRF NEGATIVE ACCEPTANCE PASSED.\n");
    fwrite(STDOUT, "Checks passed: {$negChecks}\n");
    fwrite(STDOUT, "Privilege injection and generic login failures: passed.\n");
    fwrite(STDOUT, "Protected-route and cross-role denials: passed.\n");
    fwrite(STDOUT, "Missing/invalid CSRF rejection with zero state change: passed.\n");
    fwrite(STDOUT, "Temporary accounts remaining: 0.\n");
    fwrite(STDOUT, "No resource/file, provider request, schema/register change, commit, or push occurred.\n");
} catch (Throwable $exception) {
    $failed = true;
    fwrite(STDERR, "\nAUTHORIZATION AND CSRF NEGATIVE ACCEPTANCE FAILED\n");
    fwrite(STDERR, $exception->getMessage() . "\n");
} finally {
    if ($cleanupNeeded) {
        try {
            negCleanup($database, $cleanupUsernames);
            fwrite(STDERR, "Emergency account cleanup completed.\n");
        } catch (Throwable $cleanupException) {
            $failed = true;
            fwrite(STDERR, 'EMERGENCY CLEANUP FAILED: ' . $cleanupException->getMessage() . "\n");
        }
    }

    if ($failed && is_array($baselineCounts) && is_array($baselineStorage)) {
        try {
            if ($baselineCounts !== negTableCounts($database)) {
                throw new RuntimeException('Table row counts did not return to baseline.');
            }

            if ($baselineStorage !== negStorageManifest($storage)) {
                throw new RuntimeException('Protected storage changed.');
            }

            fwrite(STDERR, "Emergency baseline restoration verified.\n");
        } catch (Throwable $restorationException) {
            $failed = true;
            fwrite(
                STDERR,
                'EMERGENCY RESTORATION CHECK FAILED: '
                . $restorationException->getMessage()
                . "\n"
            );
        }
    }

    if ($tempDirectory !== '' && is_dir($tempDirectory)) {
        $items = scandir($tempDirectory);

        if (is_array($items)) {
            foreach ($items as $item) {
                $path = $tempDirectory . DIRECTORY_SEPARATOR . $item;

                if ($item !== '.' && $item !== '..' && is_file($path)) {
                    unlink($path);
                }
            }
        }

        rmdir($tempDirectory);
    }
}

exit($failed ? 1 : 0);
