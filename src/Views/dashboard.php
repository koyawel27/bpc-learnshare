<?php

declare(strict_types=1);

use function BpcLearnShare\Support\e;

/**
 * @var array<string, mixed> $account
 * @var string $roleLabel
 * @var string $csrfToken
 */
?>
<main class="dashboard-shell">
    <section class="dashboard-card">
        <div>
            <p class="eyebrow">Authentication checkpoint</p>
            <h1>Welcome, <?= e((string) $account['display_name']) ?>.</h1>
            <p class="lead">
                Your active <?= e($roleLabel) ?> account was rechecked from
                the database for this protected request.
            </p>
        </div>

        <dl class="account-summary">
            <div>
                <dt>Username</dt>
                <dd><?= e((string) $account['username']) ?></dd>
            </div>
            <div>
                <dt>Role</dt>
                <dd><?= e($roleLabel) ?></dd>
            </div>
            <div>
                <dt>Status</dt>
                <dd>Active</dd>
            </div>
        </dl>

        <p class="prototype-note">
            Resource upload, moderation, and search are the next vertical
            slices. This page confirms real authentication—not a visual mockup.
        </p>

        <form method="post" action="/logout">
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
            <button class="button-secondary" type="submit">Sign out</button>
        </form>
    </section>
</main>
