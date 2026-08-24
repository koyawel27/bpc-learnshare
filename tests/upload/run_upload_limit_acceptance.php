<?php

declare(strict_types=1);

use BpcLearnShare\Core\Database;
use BpcLearnShare\Resource\UploadValidator;

require dirname(__DIR__, 2) . '/src/bootstrap.php';

const UA_APPROVAL = 'UPLOAD-LIMIT-LIVE-ACCEPTANCE';
const UA_PDF_FIXTURE = 'FX-PDF-008';
const UA_PPTX_FIXTURE = 'FX-PPTX-001';

$uaChecks = 0;
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

function uaAssert(bool $condition, string $label): void
{
    global $uaChecks;

    if (!$condition) {
        throw new RuntimeException($label . ' failed.');
    }

    $uaChecks++;
    fwrite(STDOUT, "[PASS] {$label}\n");
}

/** @param mixed $actual */
function uaSame(mixed $expected, mixed $actual, string $label): void
{
    uaAssert(
        $expected === $actual,
        sprintf(
            '%s (expected %s; received %s)',
            $label,
            var_export($expected, true),
            var_export($actual, true)
        )
    );
}

function uaIniBytes(string $value): int
{
    $value = trim($value);
    $unit = strtolower(substr($value, -1));
    $number = (float) $value;

    return (int) floor($number * match ($unit) {
        'g' => 1024 ** 3,
        'm' => 1024 ** 2,
        'k' => 1024,
        default => 1,
    });
}

/** @return list<array<string, string>> */
function uaCsv(string $path): array
{
    $handle = fopen($path, 'rb');

    if ($handle === false) {
        throw new RuntimeException('Unable to read fixture register.');
    }

    try {
        $headers = fgetcsv($handle, 0, ',', '"', '');

        if (!is_array($headers)) {
            throw new RuntimeException('Fixture register has no header.');
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
                throw new RuntimeException('Fixture register row width mismatch.');
            }

            $row = array_combine($headers, $values);

            if (!is_array($row)) {
                throw new RuntimeException('Fixture register row parse failed.');
            }

            $rows[] = $row;
        }

        return $rows;
    } finally {
        fclose($handle);
    }
}

/** @return array<string, string> */
function uaFixture(array $rows, string $fixtureId): array
{
    $matches = array_values(array_filter(
        $rows,
        static fn (array $row): bool =>
            ($row['fixture_id'] ?? '') === $fixtureId
    ));
    uaSame(1, count($matches), 'One fixture row exists for ' . $fixtureId);

    return $matches[0];
}

/** @return array<string, int> */
function uaTableCounts(PDO $database): array
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
function uaStorageManifest(string $directory): array
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

/** @return array{status: int, body: string} */
function uaHttp(
    string $url,
    string $cookiePath,
    string|array|null $post = null,
    int $timeout = 90
): array {
    $handle = curl_init($url);

    if ($handle === false) {
        throw new RuntimeException('Unable to initialize local HTTP request.');
    }

    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_COOKIEJAR => $cookiePath,
        CURLOPT_COOKIEFILE => $cookiePath,
        CURLOPT_HTTPHEADER => ['Expect:'],
        CURLOPT_USERAGENT => 'BPC-LearnShare-Upload-Acceptance/1.0',
    ]);

    if ($post !== null) {
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $post);
    }

    $body = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $error = curl_error($handle);
    curl_close($handle);

    if (!is_string($body)) {
        throw new RuntimeException('Local HTTP request failed: ' . $error);
    }

    return ['status' => $status, 'body' => $body];
}

function uaCsrf(string $html): string
{
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);

    try {
        if (!$document->loadHTML($html)) {
            throw new RuntimeException('Local form HTML cannot be parsed.');
        }
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
    }

    $nodes = (new DOMXPath($document))
        ->query('//input[@name="_token"]/@value');

    if ($nodes === false || $nodes->length !== 1) {
        throw new RuntimeException('Expected one CSRF token in local form.');
    }

    return (string) $nodes->item(0)?->nodeValue;
}

function uaWriteBoundaryPdf(
    string $source,
    string $destination,
    int $targetBytes
): void {
    $data = file_get_contents($source);

    if (!is_string($data) || !str_starts_with($data, '%PDF-')) {
        throw new RuntimeException('Accepted image-only PDF fixture is invalid.');
    }

    $eof = strrpos($data, '%%EOF');

    if ($eof === false) {
        throw new RuntimeException('Accepted PDF has no completion marker.');
    }

    $prefix = substr($data, 0, $eof);
    $suffix = "%%EOF\n";
    $remaining = $targetBytes - strlen($prefix) - strlen($suffix);

    if ($remaining < 3) {
        throw new RuntimeException('Requested PDF boundary is too small.');
    }

    $handle = fopen($destination, 'wb');

    if ($handle === false) {
        throw new RuntimeException('Boundary PDF cannot be created.');
    }

    try {
        fwrite($handle, $prefix);
        $line = '%' . str_repeat('A', 78) . "\n";

        while ($remaining >= strlen($line)) {
            fwrite($handle, $line);
            $remaining -= strlen($line);
        }

        fwrite($handle, match ($remaining) {
            0 => '',
            1 => "\n",
            2 => "%\n",
            default => '%' . str_repeat('A', $remaining - 2) . "\n",
        });
        fwrite($handle, $suffix);
    } finally {
        fclose($handle);
    }

    uaSame($targetBytes, filesize($destination), 'Generated PDF has exact target size');
}

function uaPng(int $width, int $height): string
{
    $raw = '';

    for ($row = 0; $row < $height; $row++) {
        $raw .= "\0" . random_bytes($width * 3);
    }

    $compressed = gzcompress($raw, 6);

    if (!is_string($compressed)) {
        throw new RuntimeException('Synthetic scan image compression failed.');
    }

    $chunk = static fn (string $type, string $bytes): string =>
        pack('N', strlen($bytes))
        . $type
        . $bytes
        . pack('N', crc32($type . $bytes));

    return "\x89PNG\r\n\x1a\n"
        . $chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
        . $chunk('IDAT', $compressed)
        . $chunk('IEND', '');
}

function uaWriteImageHeavyPptx(string $source, string $destination): void
{
    if (!copy($source, $destination)) {
        throw new RuntimeException('Accepted PPTX fixture cannot be copied.');
    }

    $archive = new ZipArchive();

    if ($archive->open($destination) !== true) {
        throw new RuntimeException('Representative PPTX cannot be opened.');
    }

    try {
        foreach ([
            'ppt/media/bpc-scan-1.png' => uaPng(2048, 2048),
            'ppt/media/bpc-scan-2.png' => uaPng(1024, 1024),
        ] as $name => $bytes) {
            if (!$archive->addFromString($name, $bytes)) {
                throw new RuntimeException('Synthetic scan cannot be added to PPTX.');
            }

            $archive->setCompressionName($name, ZipArchive::CM_STORE);
        }

        $types = $archive->getFromName('[Content_Types].xml');

        if (!is_string($types)) {
            throw new RuntimeException('PPTX content types are missing.');
        }

        if (!str_contains($types, 'Extension="png"')) {
            $types = str_replace(
                '</Types>',
                '<Default Extension="png" ContentType="image/png"/></Types>',
                $types
            );
            $archive->deleteName('[Content_Types].xml');
            $archive->addFromString('[Content_Types].xml', $types);
        }
    } finally {
        $archive->close();
    }

    $check = new ZipArchive();
    $opened = $check->open(
        $destination,
        ZipArchive::RDONLY | ZipArchive::CHECKCONS
    );
    uaSame(true, $opened, 'Image-heavy PPTX package is internally consistent');
    $check->close();
    $size = filesize($destination);
    uaAssert(
        is_int($size)
        && $size >= 15 * 1024 * 1024
        && $size <= UploadValidator::MAX_FILE_SIZE_BYTES,
        'Image-heavy PPTX is between 15 MiB and the 20 MiB limit'
    );
}

/** @return array<string, mixed> */
function uaFindResource(PDO $database, int $accountId, string $title): array
{
    $statement = $database->prepare(
        'SELECT id, status, original_filename, stored_filename,
                file_type, file_size, file_availability
         FROM resources
         WHERE uploader_id = :uploader_id AND title = :title
         ORDER BY id DESC LIMIT 1'
    );
    $statement->execute(['uploader_id' => $accountId, 'title' => $title]);
    $row = $statement->fetch();

    return is_array($row) ? $row : [];
}

function uaCleanup(PDO $database, string $username, string $storage): void
{
    $statement = $database->prepare(
        'SELECT id FROM accounts WHERE username = :username LIMIT 1'
    );
    $statement->execute(['username' => $username]);
    $accountId = (int) ($statement->fetchColumn() ?: 0);

    if ($accountId < 1) {
        return;
    }

    $statement = $database->prepare(
        'SELECT stored_filename FROM resources WHERE uploader_id = :uploader_id'
    );
    $statement->execute(['uploader_id' => $accountId]);
    $storedNames = array_map('strval', $statement->fetchAll(PDO::FETCH_COLUMN));

    $database->beginTransaction();

    try {
        $statement = $database->prepare(
            'DELETE FROM resources WHERE uploader_id = :uploader_id'
        );
        $statement->execute(['uploader_id' => $accountId]);
        $statement = $database->prepare('DELETE FROM accounts WHERE id = :id');
        $statement->execute(['id' => $accountId]);
        $database->commit();
    } catch (Throwable $exception) {
        if ($database->inTransaction()) {
            $database->rollBack();
        }

        throw $exception;
    }

    foreach ($storedNames as $name) {
        if (!preg_match('/^[a-f0-9]{64}\.(pdf|pptx)$/', $name)) {
            throw new RuntimeException('Cleanup refused unexpected filename.');
        }

        $path = $storage . DIRECTORY_SEPARATOR . $name;

        if (is_file($path) && !unlink($path)) {
            throw new RuntimeException('Test protected file cleanup failed.');
        }
    }
}

function uaRemoveDirectory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $directory,
            FilesystemIterator::SKIP_DOTS
        ),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir()
            ? @rmdir($item->getPathname())
            : @unlink($item->getPathname());
    }

    @rmdir($directory);
}

$root = dirname(__DIR__, 2);
$storage = $root . '/storage/uploads/resources';
$register = $root . '/docs/ai-feasibility-spike/registers/fixtures.csv';
$database = null;
$username = '';
$runDirectory = '';
$baselineCounts = [];
$baselineStorage = [];
$failed = false;

try {
    fwrite(STDOUT, "=== REPRESENTATIVE 20 MB UPLOAD ACCEPTANCE ===\n");
    fwrite(STDOUT, "Mode: {$mode}\n\n");

    $baseUrl = rtrim(BpcLearnShare\Core\Environment::get('APP_URL'), '/');
    $url = parse_url($baseUrl);
    uaAssert(
        is_array($url)
        && ($url['scheme'] ?? '') === 'http'
        && in_array(($url['host'] ?? ''), ['localhost', '127.0.0.1'], true),
        'Target is the local HTTP application only'
    );
    uaSame(
        20 * 1024 * 1024,
        UploadValidator::MAX_FILE_SIZE_BYTES,
        'Application limit is exactly 20 MiB'
    );
    uaAssert(
        uaIniBytes((string) ini_get('upload_max_filesize'))
            > UploadValidator::MAX_FILE_SIZE_BYTES,
        'PHP upload transport limit is above 20 MiB'
    );
    uaAssert(
        uaIniBytes((string) ini_get('post_max_size'))
            > UploadValidator::MAX_FILE_SIZE_BYTES,
        'PHP POST limit is above 20 MiB'
    );
    uaAssert(
        extension_loaded('curl')
        && extension_loaded('dom')
        && extension_loaded('fileinfo')
        && extension_loaded('zip')
        && extension_loaded('pdo_mysql'),
        'Required local PHP extensions are available'
    );
    uaAssert(
        is_dir($storage)
        && !str_starts_with(
            realpath($storage) ?: '',
            (realpath($root . '/public') ?: '') . DIRECTORY_SEPARATOR
        ),
        'Protected storage exists outside public'
    );

    if ($mode === 'apply') {
        uaAssert(
            is_writable($storage),
            'Protected storage is writable for the approved live run'
        );
    }

    $fixtures = uaCsv($register);
    $pdfFixture = uaFixture($fixtures, UA_PDF_FIXTURE);
    $pptxFixture = uaFixture($fixtures, UA_PPTX_FIXTURE);
    uaSame(
        'Accepted - validation behavior confirmed',
        $pdfFixture['review_status'],
        'Image-only PDF validation behavior is accepted'
    );
    uaSame(
        'Accepted - manually reviewed',
        $pptxFixture['review_status'],
        'PPTX base fixture is manually accepted'
    );
    uaSame('Yes', $pdfFixture['local_testing_allowed'], 'PDF permits local testing');
    uaSame('Yes', $pptxFixture['local_testing_allowed'], 'PPTX permits local testing');

    $pdfSource = $root . '/' . $pdfFixture['baseline_copy_path_or_reference'];
    $pptxSource = $root . '/' . $pptxFixture['baseline_copy_path_or_reference'];
    uaAssert(is_file($pdfSource), 'Accepted image-only PDF source exists');
    uaAssert(is_file($pptxSource), 'Accepted PPTX source exists');

    $database = Database::connection();
    uaSame(22, count(uaTableCounts($database)), 'Database has the accepted 22 tables');
    $healthCookie = tempnam(sys_get_temp_dir(), 'bpc-upload-health-');

    if (!is_string($healthCookie)) {
        throw new RuntimeException('Health cookie file cannot be created.');
    }

    try {
        $health = uaHttp($baseUrl . '/health', $healthCookie);
        uaSame(200, $health['status'], 'Local health route responds');
        uaAssert(
            str_contains($health['body'], 'MariaDB connection'),
            'Local health route includes its database check'
        );
    } finally {
        @unlink($healthCookie);
    }

    if ($mode === 'validate') {
        fwrite(STDOUT, "\nUPLOAD ACCEPTANCE VALIDATION PASSED.\n");
        fwrite(STDOUT, "Checks passed: {$uaChecks}\n");
        fwrite(STDOUT, "No account, resource, database row, or protected file was created.\n");
        fwrite(STDOUT, "No AI/model request, schema/register change, commit, or push occurred.\n");
        fwrite(STDOUT, 'Next boundary: approve one local apply run with token ');
        fwrite(STDOUT, UA_APPROVAL . ".\n");
        exit(0);
    }

    uaSame(UA_APPROVAL, $approval, 'Exact one-time approval token supplied');
    $baselineCounts = uaTableCounts($database);
    $baselineStorage = uaStorageManifest($storage);
    $runId = bin2hex(random_bytes(6));
    $username = 'upload_accept_' . $runId;
    $password = 'Local-Upload-' . bin2hex(random_bytes(12));
    $runDirectory = sys_get_temp_dir() . '/bpc-upload-acceptance-' . $runId;

    if (!mkdir($runDirectory, 0700, true) && !is_dir($runDirectory)) {
        throw new RuntimeException('Temporary run directory cannot be created.');
    }

    $cookie = $runDirectory . '/cookies.txt';
    touch($cookie);
    $exactPdf = $runDirectory . '/scanned-notes-20mb.pdf';
    $oversizedPdf = $runDirectory . '/scanned-notes-over-limit.pdf';
    $heavyPptx = $runDirectory . '/image-heavy.pptx';
    uaWriteBoundaryPdf(
        $pdfSource,
        $exactPdf,
        UploadValidator::MAX_FILE_SIZE_BYTES
    );
    uaWriteBoundaryPdf(
        $pdfSource,
        $oversizedPdf,
        UploadValidator::MAX_FILE_SIZE_BYTES + 1
    );
    uaWriteImageHeavyPptx($pptxSource, $heavyPptx);
    uaAssert(
        (new finfo(FILEINFO_MIME_TYPE))->file($exactPdf) === 'application/pdf',
        'Boundary PDF retains detected PDF type'
    );

    $page = uaHttp($baseUrl . '/register', $cookie);
    uaSame(200, $page['status'], 'Student registration form opens');
    $response = uaHttp(
        $baseUrl . '/register',
        $cookie,
        http_build_query([
            '_token' => uaCsrf($page['body']),
            'username' => $username,
            'display_name' => 'Upload Acceptance Student',
            'password' => $password,
            'password_confirmation' => $password,
        ])
    );
    uaSame(303, $response['status'], 'Temporary Student registers');

    $page = uaHttp($baseUrl . '/login', $cookie);
    uaSame(200, $page['status'], 'Login form opens');
    $response = uaHttp(
        $baseUrl . '/login',
        $cookie,
        http_build_query([
            '_token' => uaCsrf($page['body']),
            'username' => $username,
            'password' => $password,
        ])
    );
    uaSame(303, $response['status'], 'Temporary Student signs in');

    $statement = $database->prepare(
        'SELECT id, role, account_status FROM accounts WHERE username = :username'
    );
    $statement->execute(['username' => $username]);
    $account = $statement->fetch();
    uaAssert(
        is_array($account)
        && $account['role'] === 'student'
        && $account['account_status'] === 'active',
        'Temporary account is an Active Student'
    );
    $accountId = (int) $account['id'];
    $taxonomy = [];

    foreach ([
        'course_id' => 'courses',
        'subject_id' => 'subjects',
        'year_level_id' => 'year_levels',
        'resource_type_id' => 'resource_types',
    ] as $field => $table) {
        $taxonomy[$field] = (int) $database->query(
            "SELECT id FROM `{$table}` WHERE is_active = 1 ORDER BY id LIMIT 1"
        )->fetchColumn();
        uaAssert($taxonomy[$field] > 0, 'Active ' . $table . ' option exists');
    }

    $upload = static function (
        string $title,
        string $path,
        string $mime,
        string $filename
    ) use ($baseUrl, $cookie, $taxonomy): array {
        $page = uaHttp($baseUrl . '/resources/upload', $cookie);

        if ($page['status'] !== 200) {
            throw new RuntimeException('Upload form did not open.');
        }

        return uaHttp(
            $baseUrl . '/resources/upload',
            $cookie,
            [
                '_token' => uaCsrf($page['body']),
                'MAX_FILE_SIZE' => (string) UploadValidator::MAX_FILE_SIZE_BYTES,
                'title' => $title,
                'description' => 'Synthetic local artifact for upload-limit acceptance.',
                'topic' => 'Upload size and protected storage validation',
                'course_id' => (string) $taxonomy['course_id'],
                'subject_id' => (string) $taxonomy['subject_id'],
                'year_level_id' => (string) $taxonomy['year_level_id'],
                'resource_type_id' => (string) $taxonomy['resource_type_id'],
                'resource_file' => new CURLFile($path, $mime, $filename),
            ],
            180
        );
    };

    $exactTitle = 'Acceptance Exact 20 MB PDF ' . $runId;
    $response = $upload(
        $exactTitle,
        $exactPdf,
        'application/pdf',
        'Representative-Scanned-Study-Notes-20MB.pdf'
    );
    uaSame(303, $response['status'], 'Exact-limit PDF upload is accepted');
    $exactRow = uaFindResource($database, $accountId, $exactTitle);
    uaAssert(
        $exactRow !== []
        && $exactRow['status'] === 'pending'
        && $exactRow['file_availability'] === 'available'
        && (int) $exactRow['file_size'] === UploadValidator::MAX_FILE_SIZE_BYTES,
        'Exact-limit PDF creates one Pending resource with exact size'
    );
    uaAssert(
        preg_match('/^[a-f0-9]{64}\.pdf$/', (string) $exactRow['stored_filename']) === 1
        && $exactRow['stored_filename'] !== $exactRow['original_filename'],
        'Exact-limit PDF receives a randomized protected filename'
    );
    uaAssert(
        is_file($storage . '/' . $exactRow['stored_filename'])
        && filesize($storage . '/' . $exactRow['stored_filename'])
            === UploadValidator::MAX_FILE_SIZE_BYTES,
        'Exact-limit PDF reaches protected storage without size drift'
    );

    $pptxTitle = 'Acceptance Image Heavy PPTX ' . $runId;
    $response = $upload(
        $pptxTitle,
        $heavyPptx,
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'Representative-Image-Heavy-Presentation.pptx'
    );
    uaSame(303, $response['status'], 'Image-heavy PPTX upload is accepted');
    $pptxRow = uaFindResource($database, $accountId, $pptxTitle);
    uaAssert(
        $pptxRow !== []
        && $pptxRow['status'] === 'pending'
        && $pptxRow['file_type'] === 'pptx'
        && (int) $pptxRow['file_size'] === filesize($heavyPptx),
        'Image-heavy PPTX creates one Pending resource with exact size'
    );

    $before = (int) $database->query(
        'SELECT COUNT(*) FROM resources WHERE uploader_id = ' . $accountId
    )->fetchColumn();
    $response = $upload(
        'Acceptance Over Limit PDF ' . $runId,
        $oversizedPdf,
        'application/pdf',
        'Representative-Scanned-Study-Notes-Over-Limit.pdf'
    );
    uaSame(200, $response['status'], 'One-byte-oversized PDF returns the form');
    uaAssert(
        str_contains($response['body'], 'exceeds the configured upload limit')
        || str_contains($response['body'], 'exceeds the 20 MB upload limit'),
        'One-byte-oversized PDF shows a clear rejection'
    );
    uaSame(
        $before,
        (int) $database->query(
            'SELECT COUNT(*) FROM resources WHERE uploader_id = ' . $accountId
        )->fetchColumn(),
        'One-byte-oversized PDF creates no resource row'
    );

    $direct = uaHttp(
        $baseUrl . '/storage/uploads/resources/' . $exactRow['stored_filename'],
        $cookie
    );
    uaSame(404, $direct['status'], 'Protected file has no direct public URL');

    uaCleanup($database, $username, $storage);
    $username = '';
    uaAssert(
        $baselineCounts === uaTableCounts($database),
        'All 22 table counts are restored exactly'
    );
    uaAssert(
        $baselineStorage === uaStorageManifest($storage),
        'Protected storage manifest is restored exactly without disclosure'
    );

    fwrite(STDOUT, "\nREPRESENTATIVE 20 MB UPLOAD ACCEPTANCE PASSED.\n");
    fwrite(STDOUT, "Checks passed: {$uaChecks}\n");
    fwrite(STDOUT, "Exact-limit scanned-PDF derivative: accepted at 20 MiB.\n");
    fwrite(STDOUT, "Image-heavy PPTX derivative: accepted below 20 MiB.\n");
    fwrite(STDOUT, "One-byte-oversized PDF: rejected with no resource row.\n");
    fwrite(STDOUT, "Temporary account/resources/files remaining: 0.\n");
    fwrite(STDOUT, "Live database and protected storage restored: Yes.\n");
    fwrite(STDOUT, "No AI/model request, schema/register change, commit, or push occurred.\n");
} catch (Throwable $exception) {
    $failed = true;
    fwrite(STDERR, "\nUPLOAD ACCEPTANCE FAILED\n");
    fwrite(STDERR, $exception->getMessage() . "\n");
} finally {
    if ($mode === 'apply' && $database instanceof PDO && $username !== '') {
        try {
            uaCleanup($database, $username, $storage);
            fwrite(STDOUT, "Emergency test-data cleanup completed.\n");
        } catch (Throwable $cleanupException) {
            $failed = true;
            fwrite(STDERR, 'Emergency cleanup failed: ');
            fwrite(STDERR, $cleanupException->getMessage() . "\n");
        }
    }

    if ($runDirectory !== '') {
        uaRemoveDirectory($runDirectory);
    }
}

if ($failed) {
    exit(1);
}
