<?php

declare(strict_types=1);

use function BpcLearnShare\Support\e;

/**
 * @var array<string, mixed> $account
 * @var string $roleLabel
 * @var string $csrfToken
 * @var bool $canUpload
 * @var bool $canModerate
 */
?>
<main class="dashboard-shell">
    <section class="dashboard-card">
        <div>
            <p class="eyebrow">Working prototype dashboard</p>
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
            Authentication is active. Resource submissions are stored securely
            and enter moderation before they can appear in the repository.
        </p>

        <div class="dashboard-actions">
            <?php if ($canUpload): ?>
                <a class="button-link" href="/resources/upload">
                    Upload a resource
                </a>
            <?php endif; ?>

            <?php if ($canModerate): ?>
                <a class="button-link" href="/moderation">
                    Open moderation queue
                </a>
            <?php endif; ?>

            <form method="post" action="/logout">
                <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
                <button class="button-secondary" type="submit">Sign out</button>
            </form>
        </div>
    </section>
</main>
