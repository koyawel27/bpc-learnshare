<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if (is_string($path)) {
    $candidate = __DIR__ . DIRECTORY_SEPARATOR . ltrim(
        str_replace('/', DIRECTORY_SEPARATOR, $path),
        DIRECTORY_SEPARATOR
    );

    if ($path !== '/' && is_file($candidate)) {
        return false;
    }
}

require __DIR__ . '/index.php';
