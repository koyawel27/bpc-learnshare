<?php

declare(strict_types=1);

use function BpcLearnShare\Support\e;

/**
 * @var string $heading
 * @var string $message
 */
?>
<main class="single-card-shell">
    <section class="form-card">
        <p class="eyebrow">BPC LearnShare</p>
        <h1><?= e($heading) ?></h1>
        <p class="lead"><?= e($message) ?></p>
        <a class="button-link" href="/">Return to the application</a>
    </section>
</main>
