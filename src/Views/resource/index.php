<?php

declare(strict_types=1);

use function BpcLearnShare\Support\e;

/**
 * @var array<string, mixed> $account
 * @var list<array<string, mixed>> $resources
 * @var array<string, int|string> $filters
 * @var array<string, string> $errors
 * @var array<string, list<array{id: int, name: string}>> $taxonomy
 */
?>
<main class="repository-shell">
    <section class="repository-heading">
        <div>
            <p class="eyebrow">Approved resource repository</p>
            <h1>Find academic resources.</h1>
            <p class="lead">
                Search the metadata or narrow the list with the controlled
                academic filters. Only currently Approved resources with an
                available protected file appear here.
            </p>
        </div>
        <a class="button-secondary button-link" href="/dashboard">
            Return to dashboard
        </a>
    </section>

    <section class="form-card resource-filter-card" aria-labelledby="filter-heading">
        <p class="eyebrow">Metadata search</p>
        <h2 id="filter-heading">Search and filter</h2>

        <?php if ($errors !== []): ?>
            <p class="alert alert-error">
                Check the highlighted search or filter value and try again.
            </p>
        <?php endif; ?>

        <form method="get" action="/resources">
            <label for="q">Title, topic, or description</label>
            <input
                id="q"
                name="q"
                type="search"
                value="<?= e((string) $filters['q']) ?>"
                maxlength="100"
                placeholder="Example: database normalization"
            >
            <?php if (isset($errors['q'])): ?>
                <p class="field-error"><?= e($errors['q']) ?></p>
            <?php endif; ?>

            <div class="form-grid resource-filter-grid">
                <?php
                $filterDefinitions = [
                    'course_id' => ['Course/program', 'courses'],
                    'subject_id' => ['Subject', 'subjects'],
                    'year_level_id' => ['Year level', 'year_levels'],
                    'resource_type_id' => ['Resource type', 'resource_types'],
                    'tag_id' => ['Tag', 'tags'],
                ];
                ?>
                <?php foreach ($filterDefinitions as $field => [$label, $optionKey]): ?>
                    <div>
                        <label for="<?= e($field) ?>"><?= e($label) ?></label>
                        <select id="<?= e($field) ?>" name="<?= e($field) ?>">
                            <option value="">All <?= e(strtolower($label)) ?></option>
                            <?php foreach ($taxonomy[$optionKey] as $option): ?>
                                <option
                                    value="<?= $option['id'] ?>"
                                    <?= (int) $filters[$field] === $option['id']
                                        ? 'selected'
                                        : '' ?>
                                ><?= e($option['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors[$field])): ?>
                            <p class="field-error"><?= e($errors[$field]) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="filter-actions">
                <button type="submit">Search resources</button>
                <a class="button-secondary button-link" href="/resources">
                    Clear filters
                </a>
            </div>
        </form>
    </section>

    <section aria-labelledby="results-heading">
        <div class="resource-results-heading">
            <div>
                <p class="eyebrow">Current repository</p>
                <h2 id="results-heading">
                    <?= count($resources) ?> approved
                    <?= count($resources) === 1 ? 'resource' : 'resources' ?>
                </h2>
            </div>
            <p>
                Results are limited to the newest 100 matching resources.
            </p>
        </div>

        <?php if ($resources === []): ?>
            <div class="status-card resource-empty-state">
                <h3>No matching Approved resources</h3>
                <p>
                    Try a shorter search, remove one filter, or return later
                    after more resources have completed moderation.
                </p>
            </div>
        <?php else: ?>
            <div class="resource-grid">
                <?php foreach ($resources as $resource): ?>
                    <article class="resource-card">
                        <div class="resource-card-topline">
                            <span class="status-pill status-approved">Approved</span>
                            <span><?= e(strtoupper((string) $resource['file_type'])) ?></span>
                        </div>
                        <h3><?= e((string) $resource['title']) ?></h3>
                        <p class="resource-topic">
                            <?= e((string) $resource['topic']) ?>
                        </p>
                        <p class="resource-card-description">
                            <?= e((string) $resource['description']) ?>
                        </p>

                        <dl class="resource-card-meta">
                            <div>
                                <dt>Subject</dt>
                                <dd><?= e((string) $resource['subject_name']) ?></dd>
                            </div>
                            <div>
                                <dt>Course/program</dt>
                                <dd><?= e((string) $resource['course_name']) ?></dd>
                            </div>
                            <div>
                                <dt>Year level</dt>
                                <dd><?= e((string) $resource['year_level_name']) ?></dd>
                            </div>
                            <div>
                                <dt>Resource type</dt>
                                <dd><?= e((string) $resource['resource_type_name']) ?></dd>
                            </div>
                        </dl>

                        <?php if ($resource['tags'] !== []): ?>
                            <ul class="review-tags" aria-label="Resource tags">
                                <?php foreach ($resource['tags'] as $tag): ?>
                                    <li><?= e((string) $tag) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <div class="resource-card-footer">
                            <span>
                                Shared by <?= e((string) $resource['uploader_name']) ?>
                            </span>
                            <a
                                class="button-link"
                                href="/resources/<?= (int) $resource['id'] ?>"
                            >
                                View resource
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
