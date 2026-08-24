<?php

declare(strict_types=1);

use function BpcLearnShare\Support\e;

/**
 * @var array<string, mixed> $account
 * @var list<array<string, mixed>> $resources
 * @var array<string, int|string> $filters
 * @var array<string, string> $errors
 * @var array<string, list<array{id: int, name: string}>> $taxonomy
 * @var string $searchMode
 * @var string $resultMode
 * @var string|null $semanticStatusMessage
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
        <p class="eyebrow">Repository search</p>
        <h2 id="filter-heading">Choose how to search</h2>

        <?php if ($errors !== []): ?>
            <p class="alert alert-error">
                Check the highlighted search or filter value and try again.
            </p>
        <?php endif; ?>

        <form method="get" action="/resources">
            <fieldset class="search-mode-fieldset">
                <legend>Search method</legend>
                <div class="search-mode-options">
                    <label class="search-mode-option">
                        <input
                            type="radio"
                            name="search_mode"
                            value="metadata"
                            <?= $searchMode === 'metadata' ? 'checked' : '' ?>
                        >
                        <span>
                            <strong>Standard search</strong>
                            <small>Matches titles, topics, and descriptions.</small>
                        </span>
                    </label>
                    <label class="search-mode-option">
                        <input
                            type="radio"
                            name="search_mode"
                            value="semantic"
                            <?= $searchMode === 'semantic' ? 'checked' : '' ?>
                        >
                        <span>
                            <strong>AI-assisted meaning search</strong>
                            <small>
                                Experimental. Searches processed Approved
                                resources and falls back safely if unavailable.
                            </small>
                        </span>
                    </label>
                </div>
                <?php if (isset($errors['search_mode'])): ?>
                    <p class="field-error"><?= e($errors['search_mode']) ?></p>
                <?php endif; ?>
            </fieldset>

            <label for="q">Search words</label>
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

    <?php if ($semanticStatusMessage !== null): ?>
        <p class="alert alert-info semantic-search-status" role="status">
            <?= e($semanticStatusMessage) ?>
        </p>
    <?php endif; ?>

    <section aria-labelledby="results-heading">
        <div class="resource-results-heading">
            <div>
                <p class="eyebrow">Current repository</p>
                <h2 id="results-heading">
                    <?= count($resources) ?>
                    <?php if ($resultMode === 'semantic'): ?>
                        AI-assisted <?= count($resources) === 1 ? 'match' : 'matches' ?>
                    <?php else: ?>
                        approved <?= count($resources) === 1 ? 'resource' : 'resources' ?>
                    <?php endif; ?>
                </h2>
            </div>
            <p>
                <?php if ($resultMode === 'semantic'): ?>
                    Showing up to five meaning-based matches.
                <?php else: ?>
                    Results are limited to the newest 100 matching resources.
                <?php endif; ?>
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

                        <?php if ($resultMode === 'semantic'): ?>
                            <div class="semantic-match-context">
                                <p class="semantic-match-label">AI-assisted match</p>
                                <?php if ((string) ($resource['matched_locator'] ?? '') !== ''): ?>
                                    <p class="semantic-match-locator">
                                        Matched section:
                                        <strong><?= e((string) $resource['matched_locator']) ?></strong>
                                    </p>
                                <?php endif; ?>
                                <p class="semantic-match-excerpt">
                                    <?= e((string) ($resource['matched_excerpt'] ?? '')) ?>
                                </p>
                            </div>
                        <?php endif; ?>

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
