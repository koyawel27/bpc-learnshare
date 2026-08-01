<?php

declare(strict_types=1);

namespace BpcLearnShare\Support;

function base_path(string $path = ''): string
{
    $base = dirname(__DIR__, 2);

    if ($path === '') {
        return $base;
    }

    return $base . DIRECTORY_SEPARATOR . ltrim(
        str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path),
        DIRECTORY_SEPARATOR
    );
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
