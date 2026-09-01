<?php

declare(strict_types=1);

use function BpcLearnShare\Support\e;

/**
 * @var array<string, mixed> $account
 * @var array<string, string> $errors
 * @var string $csrfToken
 */
?>
<main class="auth-shell">
    <section class="intro-panel">
        <p class="eyebrow">Required account protection</p>
        <h1>Set your private password.</h1>
        <p class="lead">
            Your temporary credential opened this account once. Replace it
            before using any other LearnShare feature.
        </p>
    </section>

    <section class="form-card" aria-labelledby="password-heading">
        <p class="eyebrow">Mandatory password change</p>
        <h2 id="password-heading">Choose a new password</h2>

        <p class="field-help">
            Account Identifier:
            <strong><?= e((string) $account['username']) ?></strong>
        </p>

        <?php if (isset($errors['password_change'])): ?>
            <p class="alert alert-error">
                <?= e($errors['password_change']) ?>
            </p>
        <?php endif; ?>

        <form method="post" action="/account/change-password" novalidate>
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">

            <label for="password">New private password</label>
            <input
                id="password"
                name="password"
                type="password"
                minlength="8"
                maxlength="255"
                autocomplete="new-password"
                required
                autofocus
            >
            <p class="field-help">
                Use 8–255 characters and do not reuse the temporary password.
            </p>
            <?php if (isset($errors['password'])): ?>
                <p class="field-error"><?= e($errors['password']) ?></p>
            <?php endif; ?>

            <label for="password_confirmation">Confirm new password</label>
            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                minlength="8"
                maxlength="255"
                autocomplete="new-password"
                required
            >
            <?php if (isset($errors['password_confirmation'])): ?>
                <p class="field-error">
                    <?= e($errors['password_confirmation']) ?>
                </p>
            <?php endif; ?>

            <button type="submit">Save private password</button>
        </form>

        <form method="post" action="/logout" class="form-link">
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
            <button class="button-secondary" type="submit">Sign out</button>
        </form>
    </section>
</main>
