<?php

declare(strict_types=1);

use function BpcLearnShare\Support\e;

/**
 * @var array<string, string> $errors
 * @var array<string, mixed> $old
 * @var array<string, list<array{id: int, name: string}>> $taxonomy
 * @var bool $taxonomyReady
 * @var string $csrfToken
 * @var string|null $success
 */
$selectedTags = array_map('intval', $old['tag_ids'] ?? []);
?>
<main class="upload-shell">
    <section class="intro-panel upload-intro">
        <p class="eyebrow">Protected resource submission</p>
        <h1>Share a resource for review.</h1>
        <p class="lead">
            Every accepted upload enters Pending and must be reviewed before it
            appears in the repository. AI is not required for this upload.
        </p>
        <ul class="upload-rules">
            <li>Allowed: PDF, DOCX, PPTX, TXT, JPG/JPEG, and PNG.</li>
            <li>Maximum file size: 20 MB.</li>
            <li>Files are stored with a protected, randomized name.</li>
        </ul>
    </section>

    <section class="form-card upload-card" aria-labelledby="upload-heading">
        <p class="eyebrow">Required metadata</p>
        <h2 id="upload-heading">Upload academic resource</h2>

        <?php if ($success !== null): ?>
            <p class="alert alert-success"><?= e($success) ?></p>
        <?php endif; ?>

        <?php if (!$taxonomyReady): ?>
            <p class="alert alert-info">
                Upload choices are not configured yet. Run the local
                demonstration-taxonomy seed or ask an Admin to configure them.
            </p>
        <?php endif; ?>

        <?php if (isset($errors['upload'])): ?>
            <p class="alert alert-error"><?= e($errors['upload']) ?></p>
        <?php endif; ?>

        <form
            method="post"
            action="/resources/upload"
            enctype="multipart/form-data"
            novalidate
        >
            <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="MAX_FILE_SIZE" value="20971520">

            <label for="title">Resource title</label>
            <input
                id="title"
                name="title"
                type="text"
                value="<?= e((string) $old['title']) ?>"
                maxlength="200"
                required
                autofocus
            >
            <p class="field-help">
                Enter the title shown inside the document. If it has no clear
                title, use a readable version of the filename.
            </p>
            <?php if (isset($errors['title'])): ?>
                <p class="field-error"><?= e($errors['title']) ?></p>
            <?php endif; ?>

            <label for="description">Description</label>
            <textarea
                id="description"
                name="description"
                rows="5"
                required
            ><?= e((string) $old['description']) ?></textarea>
            <?php if (isset($errors['description'])): ?>
                <p class="field-error"><?= e($errors['description']) ?></p>
            <?php endif; ?>

            <label for="topic">Topic or lesson covered</label>
            <input
                id="topic"
                name="topic"
                type="text"
                value="<?= e((string) $old['topic']) ?>"
                maxlength="150"
                required
            >
            <p class="field-help">
                Use a short phrase, such as Database normalization or PHP sessions.
            </p>
            <?php if (isset($errors['topic'])): ?>
                <p class="field-error"><?= e($errors['topic']) ?></p>
            <?php endif; ?>

            <div class="form-grid">
                <div>
                    <label for="course_id">Course/program</label>
                    <select id="course_id" name="course_id" required>
                        <option value="">Select course/program</option>
                        <?php foreach ($taxonomy['courses'] as $option): ?>
                            <option
                                value="<?= $option['id'] ?>"
                                <?= (int) $old['course_id'] === $option['id']
                                    ? 'selected'
                                    : '' ?>
                            ><?= e($option['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['course_id'])): ?>
                        <p class="field-error"><?= e($errors['course_id']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="subject_id">Subject</label>
                    <select id="subject_id" name="subject_id" required>
                        <option value="">Select subject</option>
                        <?php foreach ($taxonomy['subjects'] as $option): ?>
                            <option
                                value="<?= $option['id'] ?>"
                                <?= (int) $old['subject_id'] === $option['id']
                                    ? 'selected'
                                    : '' ?>
                            ><?= e($option['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['subject_id'])): ?>
                        <p class="field-error"><?= e($errors['subject_id']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="year_level_id">Year level</label>
                    <select id="year_level_id" name="year_level_id" required>
                        <option value="">Select year level</option>
                        <?php foreach ($taxonomy['year_levels'] as $option): ?>
                            <option
                                value="<?= $option['id'] ?>"
                                <?= (int) $old['year_level_id'] === $option['id']
                                    ? 'selected'
                                    : '' ?>
                            ><?= e($option['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['year_level_id'])): ?>
                        <p class="field-error"><?= e($errors['year_level_id']) ?></p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="resource_type_id">Resource type</label>
                    <select id="resource_type_id" name="resource_type_id" required>
                        <option value="">Select resource type</option>
                        <?php foreach ($taxonomy['resource_types'] as $option): ?>
                            <option
                                value="<?= $option['id'] ?>"
                                <?= (int) $old['resource_type_id'] === $option['id']
                                    ? 'selected'
                                    : '' ?>
                            ><?= e($option['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['resource_type_id'])): ?>
                        <p class="field-error"><?= e($errors['resource_type_id']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <fieldset>
                <legend>Tags <span>(optional)</span></legend>
                <div class="tag-options">
                    <?php foreach ($taxonomy['tags'] as $option): ?>
                        <label class="tag-option">
                            <input
                                type="checkbox"
                                name="tag_ids[]"
                                value="<?= $option['id'] ?>"
                                <?= in_array($option['id'], $selectedTags, true)
                                    ? 'checked'
                                    : '' ?>
                            >
                            <span><?= e($option['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="field-help">
                    Optional. Choose only tags that clearly match this
                    resource. Leave this blank if none apply.
                </p>
                <?php if (isset($errors['tag_ids'])): ?>
                    <p class="field-error"><?= e($errors['tag_ids']) ?></p>
                <?php endif; ?>
            </fieldset>

            <label for="resource_file">Resource file</label>
            <input
                id="resource_file"
                name="resource_file"
                type="file"
                accept=".pdf,.docx,.pptx,.txt,.jpg,.jpeg,.png"
                required
            >
            <p class="field-help">
                The original filename is stored only for display. It is never
                used as the protected storage filename.
            </p>
            <?php if (isset($errors['resource_file'])): ?>
                <p class="field-error"><?= e($errors['resource_file']) ?></p>
            <?php endif; ?>

            <button type="submit" <?= $taxonomyReady ? '' : 'disabled' ?>>
                Submit for moderation
            </button>
        </form>

        <p class="form-link">
            <a href="/dashboard">Return to dashboard</a>
        </p>
    </section>
</main>