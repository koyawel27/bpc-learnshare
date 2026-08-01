<?php

declare(strict_types=1);

use function BpcLearnShare\Support\e;

/**
 * @var string $appName
 * @var string $title
 * @var string $content
 */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — <?= e($appName) ?></title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="/">
            <span class="brand-mark" aria-hidden="true">BL</span>
            <span><?= e($appName) ?></span>
        </a>
    </header>

    <?= $content ?>
</body>
</html>
