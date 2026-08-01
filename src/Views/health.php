<?php

declare(strict_types=1);

use function BpcLearnShare\Support\e;

/**
 * @var string $appName
 * @var string $environment
 * @var array<string, bool> $checks
 */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($appName) ?> — Foundation Check</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <main>
        <p class="eyebrow">Foundation checkpoint</p>
        <h1><?= e($appName) ?></h1>
        <p class="lead">
            The native-PHP application foundation is running. This page checks
            only the local bootstrap and database connection; user features
            have not been implemented yet.
        </p>

        <section class="status-card" aria-labelledby="status-heading">
            <h2 id="status-heading">Local readiness</h2>
            <dl class="checks">
                <?php foreach ($checks as $label => $ready): ?>
                    <div class="check">
                        <dt><?= e($label) ?></dt>
                        <dd class="<?= $ready ? 'ready' : 'unavailable' ?>">
                            <?= $ready ? 'Ready' : 'Unavailable' ?>
                        </dd>
                    </div>
                <?php endforeach; ?>
            </dl>

            <p class="note">
                Environment: <?= e($environment) ?>.
                Credentials and database details are intentionally not shown.
            </p>
        </section>
    </main>
</body>
</html>
