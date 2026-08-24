<?php

declare(strict_types=1);

use function BpcLearnShare\Support\e;

/**
 * @var array<string, mixed> $resource
 * @var array<string, mixed> $relatedResources
 */
$relatedStatus = (string) ($relatedResources['status'] ?? 'unavailable');
$relatedSuggestions = is_array($relatedResources['suggestions'] ?? null)
    ? $relatedResources['suggestions']
    : [];
$relatedMessage = (string) (
    $relatedResources['message']
    ?? 'No useful related resource is currently available.'
);
$fileSize = (int) $resource['file_size'];
$fileSizeLabel = $fileSize >= 1048576
    ? number_format($fileSize / 1048576, 2) . ' MB'
    : number_format(max(1, $fileSize) / 1024, 2) . ' KB';
?>
<main class="repository-shell">
    <section class="repository-heading resource-detail-heading">
        <div>
            <p class="eyebrow">Approved academic resource</p>
            <h1><?= e((string) $resource['title']) ?></h1>
            <p class="lead"><?= e((string) $resource['topic']) ?></p>
        </div>
        <a class="button-secondary button-link" href="/resources">
            Back to repository
        </a>
    </section>

    <div class="resource-detail-grid">
        <article class="status-card resource-detail-card">
            <div class="resource-card-topline">
                <span class="status-pill status-approved">Approved</span>
                <span>Resource #<?= (int) $resource['id'] ?></span>
            </div>

            <h2>About this resource</h2>
            <p class="resource-description"><?= e((string) $resource['description']) ?></p>

            <dl class="resource-detail-meta">
                <div>
                    <dt>Course/program</dt>
                    <dd><?= e((string) $resource['course_name']) ?></dd>
                </div>
                <div>
                    <dt>Subject</dt>
                    <dd><?= e((string) $resource['subject_name']) ?></dd>
                </div>
                <div>
                    <dt>Year level</dt>
                    <dd><?= e((string) $resource['year_level_name']) ?></dd>
                </div>
                <div>
                    <dt>Resource type</dt>
                    <dd><?= e((string) $resource['resource_type_name']) ?></dd>
                </div>
                <div>
                    <dt>Uploader</dt>
                    <dd><?= e((string) $resource['uploader_name']) ?></dd>
                </div>
                <div>
                    <dt>File</dt>
                    <dd>
                        <?= e(strtoupper((string) $resource['file_type'])) ?>
                        · <?= e($fileSizeLabel) ?>
                    </dd>
                </div>
            </dl>

            <section aria-labelledby="tags-heading">
                <h3 id="tags-heading">Tags</h3>
                <?php if ($resource['tags'] === []): ?>
                    <p class="muted-copy">
                        No controlled tags were selected for this resource.
                    </p>
                <?php else: ?>
                    <ul class="review-tags">
                        <?php foreach ($resource['tags'] as $tag): ?>
                            <li><?= e((string) $tag) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </article>

        <aside class="status-card resource-download-card">
            <p class="eyebrow">Protected download</p>
            <h2><?= e((string) $resource['original_filename']) ?></h2>
            <p>
                The application rechecks your active account, the resource's
                current approval status, and its file availability before
                serving this file.
            </p>
            <dl class="resource-access-counts">
                <div>
                    <dt>Views</dt>
                    <dd><?= (int) $resource['view_count'] ?></dd>
                </div>
                <div>
                    <dt>Downloads</dt>
                    <dd><?= (int) $resource['download_count'] ?></dd>
                </div>
            </dl>
            <a
                class="button-link"
                href="/resources/<?= (int) $resource['id'] ?>/download"
            >
                Download protected file
            </a>
            <p class="field-help">
                If the resource becomes Hidden, Restricted, Removed, Replaced,
                or unavailable, this link will stop serving the file.
            </p>
        </aside>
    </div>

    <section
        class="status-card related-resources-panel"
        aria-labelledby="related-resources-heading"
    >
        <div class="related-resources-heading">
            <div>
                <p class="eyebrow">Explore the repository</p>
                <h2 id="related-resources-heading">Related resources</h2>
                <p>
                    Suggestions use shared resource tags and are checked again
                    before they are displayed.
                </p>
            </div>
            <a class="button-secondary button-link" href="/resources">
                Search all resources
            </a>
        </div>

        <?php if ($relatedStatus === 'available' && $relatedSuggestions !== []): ?>
            <ul class="related-resource-list">
                <?php foreach ($relatedSuggestions as $suggestion): ?>
                    <li>
                        <a
                            class="related-resource-link"
                            href="<?= e((string) ($suggestion['href'] ?? '/resources')) ?>"
                        >
                            <span class="related-resource-title">
                                <?= e((string) ($suggestion['title'] ?? 'Approved resource')) ?>
                            </span>
                            <span class="related-resource-meta">
                                <?= e((string) ($suggestion['file_type'] ?? 'FILE')) ?>
                                · Approved resource
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="related-resource-empty"><?= e($relatedMessage) ?></p>
        <?php endif; ?>
    </section>
</main>
