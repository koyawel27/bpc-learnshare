<?php

declare(strict_types=1);

use function BpcLearnShare\Support\e;

/**
 * @var array<string, mixed> $resource
 * @var array<string, string> $errors
 * @var array{action: string, note: string} $old
 * @var string $csrfToken
 */
$actionLabels = [
    'approve' => 'Approve',
    'reject' => 'Reject',
    'request_correction' => 'Request Correction',
];
$uploaderRoleLabels = [
    'student' => 'Student',
    'teacher_instructor' => 'Teacher/Instructor',
];
$fileSize = (int) $resource['file_size'];
$fileSizeLabel = $fileSize < 1024
    ? number_format($fileSize) . ' bytes'
    : number_format($fileSize / 1024, 1) . ' KB';
?>
<main class="moderation-shell">
    <section class="moderation-heading">
        <div>
            <p class="eyebrow">Pending resource review</p>
            <h1><?= e((string) $resource['title']) ?></h1>
            <p class="lead">
                Review the file, metadata, and uploader details before making
                one decision.
            </p>
        </div>
        <a class="button-secondary button-link" href="/moderation">
            Back to queue
        </a>
    </section>

    <?php if (isset($errors['decision'])): ?>
        <p class="alert alert-error"><?= e($errors['decision']) ?></p>
    <?php endif; ?>

    <div class="moderation-review-grid">
        <section class="status-card moderation-details">
            <div class="moderation-item-topline">
                <span class="status-pill">Pending</span>
                <span>Resource #<?= (int) $resource['id'] ?></span>
            </div>

            <h2>Submission details</h2>
            <dl class="moderation-meta">
                <div>
                    <dt>Uploader</dt>
                    <dd>
                        <?= e((string) $resource['uploader_name']) ?>
                        (<?= e((string) $resource['uploader_username']) ?>)
                    </dd>
                </div>
                <div>
                    <dt>Uploader role</dt>
                    <dd>
                        <?= e($uploaderRoleLabels[
                            (string) $resource['uploader_role']
                        ] ?? 'Uploader') ?>
                    </dd>
                </div>
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
                    <dt>Topic</dt>
                    <dd><?= e((string) $resource['topic']) ?></dd>
                </div>
                <div>
                    <dt>Submitted</dt>
                    <dd><?= e((string) $resource['created_at']) ?></dd>
                </div>
            </dl>

            <div class="moderation-copy">
                <h3>Description</h3>
                <p><?= nl2br(e((string) $resource['description'])) ?></p>
            </div>

            <div class="moderation-copy">
                <h3>Tags</h3>
                <?php if ($resource['tags'] === []): ?>
                    <p class="note">No tags were selected.</p>
                <?php else: ?>
                    <ul class="review-tags">
                        <?php foreach ($resource['tags'] as $tag): ?>
                            <li><?= e((string) $tag) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="file-review-card">
                <div>
                    <h3>Protected file</h3>
                    <p>
                        <?= e((string) $resource['original_filename']) ?>
                        · <?= e(strtoupper((string) $resource['file_type'])) ?>
                        · <?= e($fileSizeLabel) ?>
                    </p>
                </div>
                <a
                    class="button-secondary button-link"
                    href="/moderation/resources/<?= (int) $resource['id'] ?>/file"
                >Download for review</a>
            </div>
        </section>

        <aside class="form-card moderation-decision-card">
            <p class="eyebrow">Moderator decision</p>
            <h2>Choose the next status</h2>
            <p class="note">
                Reject and Request Correction require a clear explanation.
                Approve may include an optional internal note.
            </p>

            <form
                method="post"
                action="/moderation/resources/<?= (int) $resource['id'] ?>/decision"
                novalidate
            >
                <input type="hidden" name="_token" value="<?= e($csrfToken) ?>">

                <label for="action">Decision</label>
                <select id="action" name="action" required>
                    <option value="">Choose a decision</option>
                    <?php foreach ($actionLabels as $value => $label): ?>
                        <option
                            value="<?= e($value) ?>"
                            <?= $old['action'] === $value ? 'selected' : '' ?>
                        ><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['action'])): ?>
                    <p class="field-error"><?= e($errors['action']) ?></p>
                <?php endif; ?>

                <label for="note">Decision note</label>
                <textarea
                    id="note"
                    name="note"
                    rows="7"
                ><?= e($old['note']) ?></textarea>
                <p class="field-help">
                    Explain concrete issues the uploader can act on. Do not
                    include passwords or private information.
                </p>
                <?php if (isset($errors['note'])): ?>
                    <p class="field-error"><?= e($errors['note']) ?></p>
                <?php endif; ?>

                <button type="submit">Record decision</button>
            </form>

            <p class="prototype-note">
                The resource status and your active staff role are checked
                again inside the database transaction.
            </p>
        </aside>
    </div>
</main>
