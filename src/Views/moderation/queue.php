<?php

declare(strict_types=1);

use function BpcLearnShare\Support\e;

/**
 * @var array<string, mixed> $account
 * @var list<array<string, mixed>> $pendingResources
 * @var string|null $success
 */
?>
<main class="moderation-shell">
    <section class="moderation-heading">
        <div>
            <p class="eyebrow">Staff moderation</p>
            <h1>Pending resource queue</h1>
            <p class="lead">
                Review submitted academic resources before they can appear in
                the repository.
            </p>
        </div>
        <a class="button-secondary button-link" href="/dashboard">
            Return to dashboard
        </a>
    </section>

    <?php if ($success !== null): ?>
        <p class="alert alert-success"><?= e($success) ?></p>
    <?php endif; ?>

    <?php if ($pendingResources === []): ?>
        <section class="status-card moderation-empty">
            <p class="eyebrow">Queue clear</p>
            <h2>No Pending resources</h2>
            <p class="note">
                New Student and Teacher/Instructor submissions will appear
                here after they are stored securely.
            </p>
        </section>
    <?php else: ?>
        <section class="moderation-list" aria-label="Pending resources">
            <?php foreach ($pendingResources as $resource): ?>
                <article class="moderation-item">
                    <div class="moderation-item-main">
                        <div class="moderation-item-topline">
                            <span class="status-pill">Pending</span>
                            <span>
                                Resource #<?= (int) $resource['id'] ?>
                            </span>
                        </div>
                        <h2><?= e((string) $resource['title']) ?></h2>
                        <p class="moderation-topic">
                            <?= e((string) $resource['topic']) ?>
                        </p>
                        <dl class="moderation-meta compact-meta">
                            <div>
                                <dt>Uploader</dt>
                                <dd><?= e((string) $resource['uploader_name']) ?></dd>
                            </div>
                            <div>
                                <dt>Subject</dt>
                                <dd><?= e((string) $resource['subject_name']) ?></dd>
                            </div>
                            <div>
                                <dt>Type</dt>
                                <dd>
                                    <?= e((string) $resource['resource_type_name']) ?>
                                    · <?= e(strtoupper((string) $resource['file_type'])) ?>
                                </dd>
                            </div>
                            <div>
                                <dt>Submitted</dt>
                                <dd><?= e((string) $resource['created_at']) ?></dd>
                            </div>
                        </dl>
                    </div>
                    <a
                        class="button-link"
                        href="/moderation/resources/<?= (int) $resource['id'] ?>"
                    >Review resource</a>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
