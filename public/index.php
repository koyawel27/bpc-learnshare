<?php

declare(strict_types=1);

use BpcLearnShare\Core\Database;
use BpcLearnShare\Core\Environment;

require dirname(__DIR__) . '/src/bootstrap.php';

header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header(
    "Content-Security-Policy: default-src 'self'; "
    . "style-src 'self'; img-src 'self'; "
    . "script-src 'self'; base-uri 'none'; frame-ancestors 'none'; "
    . "form-action 'self'"
);

$databaseReady = false;

try {
    $databaseReady = Database::ping();
} catch (Throwable $exception) {
    error_log(sprintf(
        '[BPC LearnShare] Database health check failed: %s',
        $exception->getMessage()
    ));
}

if (!$databaseReady) {
    http_response_code(503);
}

$appName = Environment::get('APP_NAME', 'BPC LearnShare');
$environment = Environment::get('APP_ENV', 'local');
$checks = [
    'PHP application' => true,
    'Environment configuration' => true,
    'MariaDB connection' => $databaseReady,
];

require dirname(__DIR__) . '/src/Views/health.php';
