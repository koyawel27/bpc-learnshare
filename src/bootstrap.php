<?php

declare(strict_types=1);

use BpcLearnShare\Core\Environment;
use function BpcLearnShare\Support\base_path;

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';

if (is_file($composerAutoload)) {
    require_once $composerAutoload;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'BpcLearnShare\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $relativePath = str_replace('\\', DIRECTORY_SEPARATOR, $relative);
    $path = __DIR__ . DIRECTORY_SEPARATOR . $relativePath . '.php';

    if (is_file($path)) {
        require $path;

        return;
    }

    $segments = explode(DIRECTORY_SEPARATOR, $relativePath);
    $segments[0] = lcfirst($segments[0]);
    $fallbackPath = __DIR__
        . DIRECTORY_SEPARATOR
        . implode(DIRECTORY_SEPARATOR, $segments)
        . '.php';

    if (is_file($fallbackPath)) {
        require $fallbackPath;
    }
});

require __DIR__ . '/Support/helpers.php';

Environment::load(base_path('.env'));

date_default_timezone_set(
    Environment::get('APP_TIMEZONE', 'Asia/Manila')
);

$debug = Environment::getBool('APP_DEBUG', false);

ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set(
    'session.cookie_secure',
    (
        isset($_SERVER['HTTPS'])
        && strtolower((string) $_SERVER['HTTPS']) !== 'off'
    ) ? '1' : '0'
);

session_name('bpc_learnshare_session');

set_exception_handler(static function (Throwable $exception): void {
    error_log(sprintf(
        '[BPC LearnShare] Uncaught %s: %s in %s:%d',
        $exception::class,
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ));

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "BPC LearnShare could not start.\n");
        exit(1);
    }

    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Application unavailable</title></head><body>';
    echo '<main><h1>Application temporarily unavailable</h1>';
    echo '<p>The local application could not complete this request.</p>';
    echo '</main></body></html>';
});
