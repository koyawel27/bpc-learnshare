<?php

declare(strict_types=1);

use function BpcLearnShare\Support\e;

/**
 * @var array<string, string> $errors
 * @var array<string, string> $old
 * @var string $csrfToken
 */
?>
<main class="auth-shell">
    <section class="intro-panel">
        <p class="eyebrow">Student registration</p>
        <h1>Create your learning account.</h1>
        <p class="lead">
            Public registration creates Student accounts only. Teacher,
            Moderator, and Admin accounts are provided by an Admin.
        </p>
    </section>

    <section class="form-card" aria-labelledby="register-heading">
        <p class="eyebrow">Minimum information only</p>
        <h2 id="register-heading">Create Student account</h2>

        <?php if (isset($errors['role'])): ?>
            <p class="alert alert-error"><?= e($errors['role']) ?></p>
        <?php endif; ?>

        <form method="post" action="/register" novalidate>
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">

            <label for="display_name">Display name</label>
            <input
                id="display_name"
                name="display_name"
                type="text"
                value="<?= e($old['display_name']) ?>"
                minlength="2"
                maxlength="100"
                autocomplete="name"
                required
                autofocus
            >
            <?php if (isset($errors['display_name'])): ?>
                <p class="field-error"><?= e($errors['display_name']) ?></p>
            <?php endif; ?>

            <label for="username">Username</label>
            <input
                id="username"
                name="username"
                type="text"
                value="<?= e($old['username']) ?>"
                minlength="3"
                maxlength="50"
                autocomplete="username"
                aria-describedby="username-help"
                required
            >
            <p class="field-help" id="username-help">
                Use letters, numbers, dots, underscores, or hyphens.
            </p>
            <?php if (isset($errors['username'])): ?>
                <p class="field-error"><?= e($errors['username']) ?></p>
            <?php endif; ?>

            <label for="password">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                minlength="8"
                maxlength="255"
                autocomplete="new-password"
                required
            >
            <p class="field-help">Use at least 8 characters.</p>
            <?php if (isset($errors['password'])): ?>
                <p class="field-error"><?= e($errors['password']) ?></p>
            <?php endif; ?>

            <label for="password_confirmation">Confirm password</label>
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

            <button type="submit">Create Student account</button>
        </form>

        <p class="form-link">
            Already registered? <a href="/login">Return to sign in</a>
        </p>
    </section>
</main>
