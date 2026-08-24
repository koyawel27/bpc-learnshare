<?php

declare(strict_types=1);

use BpcLearnShare\Core\Database;
use BpcLearnShare\Core\Environment;

require dirname(__DIR__, 2) . '/src/bootstrap.php';

const PJ_APPROVAL = 'CORE-JOURNEY-LIVE-ACCEPTANCE';
const PJ_FIXTURE_ID = 'FX-TXT-001';

$pjChecks = 0;
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

function pjAssert(bool $condition, string $label): void
{
    global $pjChecks;

    if (!$condition) {
        throw new RuntimeException($label . ' failed.');
    }

    $pjChecks++;
    fwrite(STDOUT, "[PASS] {$label}\n");
}

/** @param mixed $actual */
function pjSame(mixed $expected, mixed $actual, string $label): void
{
    pjAssert(
        $expected === $actual,
        sprintf('%s (expected %s; received %s)', $label, var_export($expected, true), var_export($actual, true))
    );
}

/** @return list<array<string, string>> */
function pjCsv(string $path): array
{
    $handle = fopen($path, 'rb');

    if ($handle === false) {
        throw new RuntimeException('Unable to read CSV evidence.');
    }

    try {
        $headers = fgetcsv($handle, 0, ',', '"', '');

        if (!is_array($headers)) {
            throw new RuntimeException('CSV evidence has no header.');
        }

        $headers = array_map(
            static fn (string $header): string => trim(
                preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header,
                " \t\n\r\0\x0B\""
            ),
            $headers
        );
        $rows = [];

        while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if (count($values) !== count($headers)) {
                throw new RuntimeException('CSV row width mismatch.');
            }

            $row = array_combine($headers, $values);

            if (!is_array($row)) {
                throw new RuntimeException('CSV row parse failed.');
            }

            $rows[] = $row;
        }

        return $rows;
    } finally {
        fclose($handle);
    }
}

/** @return array<string, string> */
function pjFixture(array $rows): array
{
    $matches = array_values(array_filter(
        $rows,
        static fn (array $row): bool => ($row['fixture_id'] ?? '') === PJ_FIXTURE_ID
    ));
    pjSame(1, count($matches), 'One accepted fixture row exists');

    return $matches[0];
}

/** @return array<string, int> */
function pjTableCounts(PDO $database): array
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

        $counts[$name] = (int) $database->query("SELECT COUNT(*) FROM `{$name}`")->fetchColumn();
    }

    return $counts;
}

/** @return array<string, array{bytes: int, sha256: string}> */
function pjStorageManifest(string $directory): array
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
function pjHttp(string $url, string $cookieFile, array|string|null $post = null, int $timeout = 30): array
{
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
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_USERAGENT => 'BPC-LearnShare-Core-Journey-Acceptance/1.0',
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

function pjCsrf(string $html): string
{
    if (!preg_match('/name="_token"\s+value="([a-f0-9]{64})"/', $html, $matches)) {
        throw new RuntimeException('CSRF token was not found in the local form.');
    }

    return html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/** @return array<string, mixed> */
function pjResource(PDO $database, string $title): array
{
    $statement = $database->prepare('SELECT * FROM resources WHERE title = :title ORDER BY id DESC');
    $statement->execute(['title' => $title]);
    $rows = $statement->fetchAll();
    pjSame(1, count($rows), 'Exactly one temporary resource row exists');

    return $rows[0];
}

function pjLogin(string $baseUrl, string $cookie, string $username, string $password, string $label): void
{
    $page = pjHttp($baseUrl . '/login', $cookie);
    pjSame(200, $page['status'], $label . ' login form opens');
    $response = pjHttp(
        $baseUrl . '/login',
        $cookie,
        http_build_query([
            '_token' => pjCsrf($page['body']),
            'username' => $username,
            'password' => $password,
        ])
    );
    pjSame(303, $response['status'], $label . ' signs in through HTTP');
}

/** @param list<string> $usernames */
function pjCleanup(PDO $database, array $usernames, string $title, string $storage): void
{
    $resourceStatement = $database->prepare(
        'SELECT id, stored_filename FROM resources WHERE title = :title'
    );
    $resourceStatement->execute(['title' => $title]);
    $resources = $resourceStatement->fetchAll();

    $database->beginTransaction();

    try {
        $deleteHistory = $database->prepare(
            'DELETE FROM resource_action_history WHERE resource_id = :resource_id'
        );
        $deleteTags = $database->prepare(
            'DELETE FROM resource_tags WHERE resource_id = :resource_id'
        );

        foreach ($resources as $resource) {
            $resourceId = (int) $resource['id'];
            $deleteHistory->execute(['resource_id' => $resourceId]);
            $deleteTags->execute(['resource_id' => $resourceId]);
        }

        $deleteResources = $database->prepare('DELETE FROM resources WHERE title = :title');
        $deleteResources->execute(['title' => $title]);
        $deleteAccount = $database->prepare('DELETE FROM accounts WHERE username = :username');

        foreach ($usernames as $username) {
            $deleteAccount->execute(['username' => $username]);
        }
        $database->commit();
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }

        throw $exception;
    }

    foreach ($resources as $resource) {
        $stored = (string) ($resource['stored_filename'] ?? '');

        if (preg_match('/\A[a-f0-9]{64}\.[a-z0-9]+\z/', $stored) !== 1) {
            throw new RuntimeException('Refusing unsafe cleanup filename.');
        }

        $path = $storage . DIRECTORY_SEPARATOR . $stored;

        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Unable to remove temporary protected file.');
        }
    }
}

$root = dirname(__DIR__, 2);
$baseUrl = 'http://127.0.0.1:8081';
$storage = $root . '/storage/uploads/resources';
$register = $root . '/docs/ai-feasibility-spike/registers/fixtures.csv';
$database = Database::connection();
$accountPrefix = 'journey_accept_';
$titlePrefix = 'Journey Acceptance ';
$tempDirectory = '';
$cleanupNeeded = false;
$cleanupUsernames = [];
$cleanupTitle = '';
$baselineCounts = null;
$baselineStorage = null;
$failed = false;

fwrite(STDOUT, "=== CORE PRESENTATION JOURNEY ACCEPTANCE ===\n");
fwrite(STDOUT, "Mode: {$mode}\n\n");

try {
    pjAssert(str_starts_with($baseUrl, 'http://127.0.0.1:'), 'Target is the local application only');
    pjAssert(extension_loaded('curl'), 'PHP cURL extension is available');
    pjAssert(extension_loaded('fileinfo'), 'PHP Fileinfo extension is available');
    pjAssert(is_dir($storage), 'Protected resource storage exists');
    if ($mode === 'apply') {
        pjAssert(is_writable($storage), 'Protected resource storage is writable');
    }
    pjAssert(
        !str_starts_with(
            str_replace('\\', '/', (string) realpath($storage)),
            str_replace('\\', '/', (string) realpath($root . '/public'))
        ),
        'Protected resource storage is outside public'
    );
    pjSame(22, count(pjTableCounts($database)), 'Database has 22 accepted tables');

    foreach (['courses', 'subjects', 'year_levels', 'resource_types', 'tags'] as $table) {
        $count = (int) $database->query(
            "SELECT COUNT(*) FROM `{$table}` WHERE is_active = 1"
        )->fetchColumn();
        pjAssert($count > 0, 'Active ' . $table . ' option exists');
    }

    $fixture = pjFixture(pjCsv($register));
    pjSame('TXT', $fixture['file_type'], 'Fixture type is TXT');
    pjSame('Accepted - manually reviewed', $fixture['review_status'], 'Fixture review status is accepted');
    pjSame('Yes', $fixture['local_testing_allowed'], 'Fixture permits local testing');
    $fixturePath = $root . '/' . str_replace(
        ['/', '\\'],
        DIRECTORY_SEPARATOR,
        $fixture['baseline_copy_path_or_reference']
    );
    pjAssert(is_file($fixturePath), 'Accepted TXT fixture source exists');
    pjAssert(
        (new finfo(FILEINFO_MIME_TYPE))->file($fixturePath) === 'text/plain',
        'Accepted fixture has detected text/plain content'
    );
    pjAssert(!Environment::getBool('AI_LOCAL_PROCESSING_ENABLED', false), 'Local AI processing is disabled');
    pjAssert(!Environment::getBool('AI_SEMANTIC_RETRIEVAL_ENABLED', false), 'Semantic retrieval route is default-off');
    pjAssert(!Environment::getBool('AI_RELATED_RESOURCES_ENABLED', false), 'Related-resource route is default-off');
    $setting = $database->prepare(
        "SELECT setting_value FROM system_settings WHERE setting_name = 'ai_enabled'"
    );
    $setting->execute();
    $settingValue = $setting->fetchColumn();
    pjAssert(
        $settingValue === false || $settingValue === 'disabled',
        'Live database does not enable AI'
    );
    $healthCookie = tempnam(sys_get_temp_dir(), 'pj-cookie-');
    pjAssert(is_string($healthCookie), 'Temporary health cookie file is available');
    $health = pjHttp($baseUrl . '/health', $healthCookie);
    unlink($healthCookie);
    pjSame(200, $health['status'], 'Local health route responds');
    pjAssert(str_contains($health['body'], 'MariaDB connection'), 'Health route includes its database check');

    if ($mode === 'validate') {
        fwrite(STDOUT, "\nCORE JOURNEY VALIDATION PASSED.\n");
        fwrite(STDOUT, "Checks passed: {$pjChecks}\n");
        fwrite(STDOUT, "No account, resource, history row, or protected file was created.\n");
        fwrite(STDOUT, "No AI/model request, schema/register change, commit, or push occurred.\n");
        fwrite(STDOUT, "Next boundary: separately approve one apply run with token " . PJ_APPROVAL . ".\n");
        exit(0);
    }

    pjSame(PJ_APPROVAL, $approval, 'Exact one-time approval token supplied');
    $baselineCounts = pjTableCounts($database);
    $baselineStorage = pjStorageManifest($storage);
    $runId = bin2hex(random_bytes(6));
    $studentUsername = $accountPrefix . 'student_' . $runId;
    $staffUsername = $accountPrefix . 'moderator_' . $runId;
    $studentPassword = 'Journey-Student-' . bin2hex(random_bytes(10));
    $staffPassword = 'Journey-Staff-' . bin2hex(random_bytes(10));
    $title = $titlePrefix . $runId;
    $cleanupUsernames = [$studentUsername, $staffUsername];
    $cleanupTitle = $title;
    $tempDirectory = sys_get_temp_dir() . '/bpc-core-journey-' . $runId;

    if (!mkdir($tempDirectory, 0700, true) && !is_dir($tempDirectory)) {
        throw new RuntimeException('Temporary run directory cannot be created.');
    }

    $studentCookie = $tempDirectory . '/student-cookies.txt';
    $staffCookie = $tempDirectory . '/staff-cookies.txt';
    touch($studentCookie);
    touch($staffCookie);
    $cleanupNeeded = true;

    $page = pjHttp($baseUrl . '/register', $studentCookie);
    pjSame(200, $page['status'], 'Public Student registration form opens');
    $response = pjHttp(
        $baseUrl . '/register',
        $studentCookie,
        http_build_query([
            '_token' => pjCsrf($page['body']),
            'username' => $studentUsername,
            'display_name' => 'Journey Acceptance Student',
            'password' => $studentPassword,
            'password_confirmation' => $studentPassword,
        ])
    );
    pjSame(303, $response['status'], 'Temporary Student registers through HTTP');

    $staffHash = password_hash($staffPassword, PASSWORD_DEFAULT);
    pjAssert(is_string($staffHash), 'Temporary Moderator password is hashed');
    $insertStaff = $database->prepare(
        "INSERT INTO accounts (username, password_hash, display_name, role, account_status)
         VALUES (:username, :password_hash, :display_name, 'moderator', 'active')"
    );
    $insertStaff->execute([
        'username' => $staffUsername,
        'password_hash' => $staffHash,
        'display_name' => 'Journey Acceptance Moderator',
    ]);
    pjAssert((int) $database->lastInsertId() > 0, 'Temporary Moderator setup succeeds');

    pjLogin($baseUrl, $studentCookie, $studentUsername, $studentPassword, 'Temporary Student');
    $taxonomy = [];

    foreach ([
        'course_id' => 'courses',
        'subject_id' => 'subjects',
        'year_level_id' => 'year_levels',
        'resource_type_id' => 'resource_types',
        'tag_id' => 'tags',
    ] as $field => $table) {
        $taxonomy[$field] = (int) $database->query(
            "SELECT id FROM `{$table}` WHERE is_active = 1 ORDER BY id LIMIT 1"
        )->fetchColumn();
    }

    $page = pjHttp($baseUrl . '/resources/upload', $studentCookie);
    pjSame(200, $page['status'], 'Student upload form opens');
    $response = pjHttp(
        $baseUrl . '/resources/upload',
        $studentCookie,
        [
            '_token' => pjCsrf($page['body']),
            'title' => $title,
            'description' => 'Temporary accepted resource for the core presentation journey.',
            'topic' => 'SQL terminology acceptance journey',
            'course_id' => (string) $taxonomy['course_id'],
            'subject_id' => (string) $taxonomy['subject_id'],
            'year_level_id' => (string) $taxonomy['year_level_id'],
            'resource_type_id' => (string) $taxonomy['resource_type_id'],
            'tag_ids[0]' => (string) $taxonomy['tag_id'],
            'resource_file' => new CURLFile($fixturePath, 'text/plain', 'Journey-SQL-Terminology.txt'),
        ]
    );
    pjSame(303, $response['status'], 'Student submits one valid resource');
    $resource = pjResource($database, $title);
    $resourceId = (int) $resource['id'];
    pjAssert(
        $resource['status'] === 'pending'
        && $resource['file_availability'] === 'available'
        && $resource['file_type'] === 'txt',
        'Upload creates one Pending available TXT resource'
    );
    pjAssert(
        preg_match('/\A[a-f0-9]{64}\.txt\z/', (string) $resource['stored_filename']) === 1,
        'Uploaded resource receives a randomized protected filename'
    );

    $query = http_build_query(['search_mode' => 'metadata', 'q' => $title]);
    $pendingSearch = pjHttp($baseUrl . '/resources?' . $query, $studentCookie);
    pjSame(200, $pendingSearch['status'], 'Repository search works while resource is Pending');
    pjAssert(
        !str_contains(
            $pendingSearch['body'],
            'href="/resources/' . $resourceId . '"'
        ),
        'Pending resource is excluded from public discovery results'
    );
    pjSame(404, pjHttp($baseUrl . '/resources/' . $resourceId, $studentCookie)['status'], 'Pending resource detail is unavailable');
    pjSame(404, pjHttp($baseUrl . '/resources/' . $resourceId . '/download', $studentCookie)['status'], 'Pending resource download is unavailable');
    pjSame(
        404,
        pjHttp($baseUrl . '/storage/uploads/resources/' . $resource['stored_filename'], $studentCookie)['status'],
        'Protected file has no direct public URL'
    );

    pjLogin($baseUrl, $staffCookie, $staffUsername, $staffPassword, 'Temporary Moderator');
    $queue = pjHttp($baseUrl . '/moderation', $staffCookie);
    pjSame(200, $queue['status'], 'Moderator queue opens');
    pjAssert(str_contains($queue['body'], $title), 'Pending resource appears in queue');
    $review = pjHttp($baseUrl . '/moderation/resources/' . $resourceId, $staffCookie);
    pjSame(200, $review['status'], 'Moderator review page opens');
    pjAssert(str_contains($review['body'], $title), 'Review page shows the resource');
    $decision = pjHttp(
        $baseUrl . '/moderation/resources/' . $resourceId . '/decision',
        $staffCookie,
        http_build_query([
            '_token' => pjCsrf($review['body']),
            'action' => 'approve',
            'note' => 'Core presentation journey acceptance.',
        ])
    );
    pjSame(303, $decision['status'], 'Moderator approves through HTTP');
    pjSame(
        'approved',
        (string) $database->query('SELECT status FROM resources WHERE id = ' . $resourceId)->fetchColumn(),
        'Resource status becomes Approved'
    );
    $history = $database->prepare(
        "SELECT COUNT(*) FROM resource_action_history
         WHERE resource_id = :resource_id AND action_type = 'approve'
           AND status_before = 'pending' AND status_after = 'approved'"
    );
    $history->execute(['resource_id' => $resourceId]);
    pjSame(1, (int) $history->fetchColumn(), 'Approval history is recorded once');

    $approvedQuery = http_build_query([
        'search_mode' => 'metadata',
        'q' => $title,
        'course_id' => $taxonomy['course_id'],
        'subject_id' => $taxonomy['subject_id'],
        'year_level_id' => $taxonomy['year_level_id'],
        'resource_type_id' => $taxonomy['resource_type_id'],
        'tag_id' => $taxonomy['tag_id'],
    ]);
    $search = pjHttp($baseUrl . '/resources?' . $approvedQuery, $studentCookie);
    pjSame(200, $search['status'], 'Filtered metadata search opens');
    pjAssert(
        str_contains($search['body'], $title)
        && str_contains($search['body'], '/resources/' . $resourceId),
        'Approved resource appears through metadata search and filters'
    );

    $fallback = pjHttp(
        $baseUrl . '/resources?' . http_build_query(['search_mode' => 'semantic', 'q' => $title]),
        $studentCookie
    );
    pjSame(200, $fallback['status'], 'AI-assisted search request fails over safely');
    pjAssert(
        str_contains($fallback['body'], $title)
        && str_contains($fallback['body'], 'unavailable right now'),
        'AI-disabled request preserves standard metadata results'
    );

    $detail = pjHttp($baseUrl . '/resources/' . $resourceId, $studentCookie);
    pjSame(200, $detail['status'], 'Approved resource detail opens');
    pjAssert(
        str_contains($detail['body'], $title)
        && str_contains($detail['body'], '/resources/' . $resourceId . '/download'),
        'Detail page shows metadata and protected download link'
    );
    $download = pjHttp($baseUrl . '/resources/' . $resourceId . '/download', $studentCookie);
    pjSame(200, $download['status'], 'Protected Approved download succeeds');
    pjAssert(
        hash('sha256', $download['body']) === hash_file('sha256', $fixturePath),
        'Downloaded bytes match the accepted source fixture'
    );
    pjAssert(str_contains($download['headers'], 'Content-Disposition: attachment;'), 'Download uses an attachment response');
    pjSame(
        1,
        (int) $database->query('SELECT download_count FROM resources WHERE id = ' . $resourceId)->fetchColumn(),
        'Successful download is counted once'
    );

    $dashboard = pjHttp($baseUrl . '/dashboard', $studentCookie);
    pjSame(200, $dashboard['status'], 'Student dashboard remains available');
    $logout = pjHttp(
        $baseUrl . '/logout',
        $studentCookie,
        http_build_query(['_token' => pjCsrf($dashboard['body'])])
    );
    pjSame(303, $logout['status'], 'Student logs out through HTTP');
    pjSame(303, pjHttp($baseUrl . '/resources', $studentCookie)['status'], 'Protected repository requires sign-in after logout');

    pjCleanup($database, $cleanupUsernames, $cleanupTitle, $storage);
    $cleanupNeeded = false;
    pjAssert($baselineCounts === pjTableCounts($database), 'All 22 table row counts are restored');
    pjAssert($baselineStorage === pjStorageManifest($storage), 'Protected storage manifest is restored without disclosure');

    fwrite(STDOUT, "\nCORE PRESENTATION JOURNEY ACCEPTANCE PASSED.\n");
    fwrite(STDOUT, "Checks passed: {$pjChecks}\n");
    fwrite(STDOUT, "Student registration/login/upload: passed.\n");
    fwrite(STDOUT, "Pending exclusion and staff approval/history: passed.\n");
    fwrite(STDOUT, "Approved search/detail/download: passed.\n");
    fwrite(STDOUT, "AI-disabled metadata fallback and non-AI continuity: passed.\n");
    fwrite(STDOUT, "Temporary accounts/resource/history/file remaining: 0.\n");
    fwrite(STDOUT, "No provider request, schema/register change, commit, or push occurred.\n");
} catch (Throwable $exception) {
    $failed = true;
    fwrite(STDERR, "\nCORE JOURNEY ACCEPTANCE FAILED\n");
    fwrite(STDERR, $exception->getMessage() . "\n");
} finally {
    if ($cleanupNeeded) {
        try {
            pjCleanup($database, $cleanupUsernames, $cleanupTitle, $storage);
            fwrite(STDERR, "Emergency cleanup completed.\n");
        } catch (Throwable $cleanupException) {
            $failed = true;
            fwrite(STDERR, 'EMERGENCY CLEANUP FAILED: ' . $cleanupException->getMessage() . "\n");
        }
    }

    if ($failed && is_array($baselineCounts) && is_array($baselineStorage)) {
        try {
            if ($baselineCounts !== pjTableCounts($database)) {
                throw new RuntimeException('Table row counts did not return to baseline.');
            }

            if ($baselineStorage !== pjStorageManifest($storage)) {
                throw new RuntimeException('Protected storage did not return to baseline.');
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
