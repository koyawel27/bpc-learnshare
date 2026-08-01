<?php

declare(strict_types=1);

use function BpcLearnShare\Support\e;

/**
 * @var array<string, string> $errors
 * @var array<string, string> $old
 * @var string $csrfToken
 * @var string|null $notice
 * @var string|null $success
 */
?>
<main class="auth-shell">
    <section class="intro-panel">
        <p class="eyebrow">BPC academic resource repository</p>
        <h1>Learn together. Share responsibly.</h1>
        <p class="lead">
            Sign in to browse, share, and manage moderated academic resources.
            Core access remains available even when optional AI features are off.
        </p>
    </section>

    <section class="form-card" aria-labelledby="login-heading">
        <p class="eyebrow">Welcome back</p>
        <h2 id="login-heading">Sign in</h2>

        <?php if ($notice !== null): ?>
            <p class="alert alert-info"><?= e($notice) ?></p>
        <?php endif; ?>

        <?php if ($success !== null): ?>
            <p class="alert alert-success"><?= e($success) ?></p>
        <?php endif; ?>

        <?php if (isset($errors['login'])): ?>
            <p class="alert alert-error"><?= e($errors['login']) ?></p>
        <?php endif; ?>

        <form method="post" action="/login" novalidate>
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">

            <label for="username">Username</label>
            <input
                id="username"
                name="username"
                type="text"
                value="<?= e($old['username']) ?>"
                maxlength="50"
                autocomplete="username"
                required
                autofocus
            >

            <label for="password">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                maxlength="255"
                autocomplete="current-password"
                required
            >

            <button type="submit">Sign in</button>
        </form>

        <p class="form-link">
            New student? <a href="/register">Create a Student account</a>
        </p>
    </section>
</main>
