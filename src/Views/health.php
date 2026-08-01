<?php

declare(strict_types=1);

use function BpcLearnShare\Support\e;

/**
 * @var string $environment
 * @var array<string, bool> $checks
 */
?>
<main class="single-card-shell">
    <section class="status-card">
        <p class="eyebrow">Foundation checkpoint</p>
        <h1>Local readiness</h1>
        <p class="lead">
            This diagnostic confirms the local PHP, configuration, database,
            and authentication foundation without exposing credentials.
        </p>

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
