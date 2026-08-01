<?php

declare(strict_types=1);

namespace BpcLearnShare\Support;

use RuntimeException;

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

/**
 * @param array<string, mixed> $data
 */
function render(string $view, array $data = []): void
{
    if (!preg_match('/\A[a-zA-Z0-9_\/-]+\z/', $view)) {
        throw new RuntimeException('Invalid view name.');
    }

    $viewFile = base_path('src/Views/' . $view . '.php');

    if (!is_file($viewFile)) {
        throw new RuntimeException('Requested view is unavailable.');
    }

    extract($data, EXTR_SKIP);

    ob_start();
    require $viewFile;
    $content = ob_get_clean();

    if ($content === false) {
        throw new RuntimeException('View output could not be prepared.');
    }

    require base_path('src/Views/layout.php');
}

function redirect(string $path): never
{
    if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
        throw new RuntimeException('Unsafe redirect path.');
    }

    header('Location: ' . $path, true, 303);
    exit;
}
