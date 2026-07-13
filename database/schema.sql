-- =====================================================================
-- BPC LearnShare — Database Schema
-- Part 1 of 5: Identity & Taxonomy Foundations
-- Tables: accounts, courses, subjects, year_levels, resource_types, tags
-- Target: MySQL/MariaDB via XAMPP/phpMyAdmin
-- Engine: InnoDB (required for foreign keys)
-- Charset: utf8mb4 (per DATABASE_DESIGN.md Section 2, principle 5)
-- =====================================================================

-- ---------------------------------------------------------------------
-- Table: accounts
-- Purpose: Single table for all four v1.0 roles. One authoritative
-- `role` field, never boolean flags (DATABASE_DESIGN.md Section 2, principle 2).
-- ---------------------------------------------------------------------
CREATE TABLE accounts (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username        VARCHAR(50)  NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,   -- output of PHP password_hash()
    display_name    VARCHAR(100) NOT NULL,
    role            ENUM('student', 'teacher_instructor', 'moderator', 'admin')
                        NOT NULL,
    account_status  ENUM('active', 'disabled')
                        NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_accounts_username (username),
    KEY idx_accounts_role (role),
    KEY idx_accounts_status (account_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes explained:
--   idx_accounts_role   -> needed for "list all Teacher/Instructor accounts"
--                          type queries (e.g. Admin account management,
--                          Teacher-upload badge join in the moderation queue).
--   idx_accounts_status -> needed for login checks and account-management
--                          filtering (Active vs Disabled).


-- ---------------------------------------------------------------------
-- Table: courses
-- Purpose: Admin-managed controlled lookup for course/program metadata.
-- is_active supports deactivate-not-delete: a course already referenced
-- by a resource cannot be hard-deleted (see resources.course_id FK,
-- Part 2), but can be hidden from future upload forms via is_active = 0.
-- ---------------------------------------------------------------------
CREATE TABLE courses (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_courses_name (name),
    KEY idx_courses_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- idx_courses_active -> needed to quickly populate "active courses only"
-- dropdowns on the upload form without scanning the whole table.


-- ---------------------------------------------------------------------
-- Table: subjects
-- Purpose: Admin-managed controlled lookup. Flat/standalone for v1.0 —
-- deliberately NOT scoped to a course_id (per this session's confirmed
-- direction; do not add course_id without a new decision).
-- ---------------------------------------------------------------------
CREATE TABLE subjects (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_subjects_name (name),
    KEY idx_subjects_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Table: year_levels
-- Purpose: Admin-managed controlled lookup (e.g. "1st Year" .. "4th Year").
-- ---------------------------------------------------------------------
CREATE TABLE year_levels (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(50) NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_year_levels_name (name),
    KEY idx_year_levels_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Table: resource_types
-- Purpose: Admin-managed controlled lookup (e.g. "Reviewer", "Module",
-- "Presentation", "Notes", "Handout", "Study Guide"). Handles the broad
-- FORM of a resource — distinct from tags, which handle topical
-- descriptors (DATABASE_DESIGN.md Section 6.3, categories-vs-tags direction).
-- ---------------------------------------------------------------------
CREATE TABLE resource_types (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_resource_types_name (name),
    KEY idx_resource_types_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Table: tags
-- Purpose: Admin-managed controlled vocabulary. NOT free text typed by
-- uploaders (USER_ROLES.md — tag management is Admin system-config
-- authority, same as courses/subjects/resource_types).
-- ---------------------------------------------------------------------
CREATE TABLE tags (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                    ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_tags_name (name),
    KEY idx_tags_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- BPC LearnShare — Database Schema
-- Part 2 of 5: Core Resource Data
-- Tables: resources, resource_tags
-- Depends on: accounts, courses, subjects, year_levels, resource_types,
--             tags (Part 1)
-- Target: MySQL/MariaDB via XAMPP/phpMyAdmin
-- Engine: InnoDB (required for foreign keys)
-- Charset: utf8mb4
-- =====================================================================

-- ---------------------------------------------------------------------
-- Table: resources
-- Purpose: The central record for every upload, resubmission, and
-- replacement. One row per resource — no separate draft/version table
-- (DATABASE_DESIGN.md Section 7.1). replaces_resource_id is the
-- self-referencing link used by the D012 replacement workflow.
-- ---------------------------------------------------------------------
CREATE TABLE resources (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,

    uploader_id             INT UNSIGNED NOT NULL,

    title                   VARCHAR(200) NOT NULL,
    description             TEXT NOT NULL,
    topic                   VARCHAR(150) NOT NULL,

    course_id               INT UNSIGNED NOT NULL,
    subject_id              INT UNSIGNED NOT NULL,
    year_level_id           INT UNSIGNED NOT NULL,
    resource_type_id        INT UNSIGNED NOT NULL,

    status                  ENUM(
                                'pending',
                                'needs_correction',
                                'approved',
                                'rejected',
                                'withdrawn',
                                'hidden',
                                'restricted',
                                'removed',
                                'replaced'
                             ) NOT NULL DEFAULT 'pending',

    original_filename       VARCHAR(255) NOT NULL,
    stored_filename         VARCHAR(255) NOT NULL,
    file_type               ENUM('pdf', 'docx', 'pptx', 'txt', 'jpg', 'png')
                                 NOT NULL,
    file_size               INT UNSIGNED NOT NULL,
    file_availability        ENUM('available', 'deleted', 'invalidated')
                                 NOT NULL DEFAULT 'available',

    replaces_resource_id    INT UNSIGNED NULL,

    ai_notice_acknowledged      TINYINT(1) NOT NULL DEFAULT 0,
    ai_notice_acknowledged_at   TIMESTAMP NULL DEFAULT NULL,

    view_count              INT UNSIGNED NOT NULL DEFAULT 0,
    download_count          INT UNSIGNED NOT NULL DEFAULT 0,

    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_resources_stored_filename (stored_filename),

    KEY idx_resources_uploader (uploader_id),
    KEY idx_resources_course (course_id),
    KEY idx_resources_subject (subject_id),
    KEY idx_resources_year_level (year_level_id),
    KEY idx_resources_resource_type (resource_type_id),
    KEY idx_resources_replaces (replaces_resource_id),
    KEY idx_resources_status_created (status, created_at),

    CONSTRAINT fk_resources_uploader
        FOREIGN KEY (uploader_id) REFERENCES accounts (id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_resources_course
        FOREIGN KEY (course_id) REFERENCES courses (id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_resources_subject
        FOREIGN KEY (subject_id) REFERENCES subjects (id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_resources_year_level
        FOREIGN KEY (year_level_id) REFERENCES year_levels (id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_resources_resource_type
        FOREIGN KEY (resource_type_id) REFERENCES resource_types (id)
        ON DELETE RESTRICT,

    -- fk_resources_replaces is a nullable self-referencing foreign key.
    -- It supports the linked replacement-record workflow.
    -- Application logic must still ensure that replacements target an eligible
    -- current resource and do not create invalid replacement chains.
    CONSTRAINT fk_resources_replaces
        FOREIGN KEY (replaces_resource_id) REFERENCES resources (id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_resources_file_size
        CHECK (file_size > 0),

    CONSTRAINT chk_resources_ai_notice_pair
        CHECK (
            (ai_notice_acknowledged = 0 AND ai_notice_acknowledged_at IS NULL)
            OR
            (ai_notice_acknowledged = 1 AND ai_notice_acknowledged_at IS NOT NULL)
        )

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notes:
--   Every FK column now has an explicitly named single-column index
--   (idx_resources_uploader, idx_resources_course, etc.). Correction to
--   what I said in the previous draft: this is NOT redundant with
--   InnoDB's automatic FK indexing — declaring the index explicitly
--   just means MySQL uses your named index to satisfy the FK
--   requirement instead of silently generating one with an
--   auto-assigned name. No duplicate index is created either way.
--
--   idx_resources_status_created (status, created_at) replaces the
--   earlier single-column status index. This supports both "list all
--   Pending resources, oldest first" (moderation queue) and "list all
--   Approved resources, newest first" (browse/search) without a
--   separate index for each.
--
--   No further composite indexes (e.g. status + course_id together)
--   are added yet. No confirmed workflow currently filters on that
--   specific combination; adding it now would be speculative tuning.
--   Cheap to add later via ALTER TABLE if real testing shows a need.
--
--   MariaDB 10.4.32 rejects a CHECK on resources that compares
--   replaces_resource_id with the AUTO_INCREMENT id column. Therefore,
--   direct self-replacement is not enforced through a CHECK constraint.
--
--   PHP application logic must reject both:
--     1. direct self-replacement; and
--     2. longer replacement cycles such as A -> B -> C -> A.
--
--   This remains consistent with D037: database CHECK constraints are
--   defense-in-depth only, and PHP must independently enforce every
--   applicable business rule.
--
--   Live verification on MariaDB 10.4.32 confirmed that the remaining
--   CHECK constraints are recognized and enforced. The accepted schema
--   therefore contains 13 CHECK constraints after removal of the
--   incompatible direct self-replacement CHECK.

-- D040 — Removed-resource minimization is an application-level lifecycle
-- rule using the existing 18-table schema.
--
-- When a resource transitions to 'removed', PHP application logic must:
--   1. set status = 'removed';
--   2. set file_availability to 'deleted' or 'invalidated';
--   3. set title = '[Removed resource]';
--   4. set description = '[Removed resource]';
--   5. set topic = '[Removed]';
--   6. set original_filename = '[removed]';
--   7. delete all resource_tags rows for the resource;
--   8. apply the confirmed AI-output lifecycle update; and
--   9. write the required resource_action_history entry.
--
-- The related database writes should use one transaction.
-- Physical file deletion is not database-transactional and must be
-- coordinated separately. A cleanup failure must not make the file
-- servable again; status and file_availability remain the serving gates.
--
-- created_at remains unchanged. updated_at changes automatically because
-- this table uses ON UPDATE CURRENT_TIMESTAMP.
--
-- The retained row is minimized but not anonymized. Account references,
-- required taxonomy references, technical metadata, activity counts,
-- timestamps, and historical relationships may remain.
--
-- No new CHECK constraint is required for v1.0. PHP-side enforcement is
-- mandatory under D037. Any future database CHECK would be defense-in-depth
-- only after the actual MariaDB/MySQL version and support are confirmed.


-- ---------------------------------------------------------------------
-- Table: resource_tags
-- Purpose: Many-to-many junction between resources and tags. Tags are
-- optional per resource — a resource with zero rows here simply has
-- no tags. Exists specifically because a comma-separated tags column
-- was rejected in DATABASE_DESIGN.md Section 7.1 as incompatible with
-- enforcing a controlled tag vocabulary.
-- ---------------------------------------------------------------------
CREATE TABLE resource_tags (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    resource_id     INT UNSIGNED NOT NULL,
    tag_id          INT UNSIGNED NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_resource_tags_pair (resource_id, tag_id),
    KEY idx_resource_tags_tag_resource (tag_id, resource_id),

    CONSTRAINT fk_resource_tags_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON DELETE CASCADE,

    CONSTRAINT fk_resource_tags_tag
        FOREIGN KEY (tag_id) REFERENCES tags (id)
        ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: uq_resource_tags_pair (resource_id, tag_id) supports "given a
-- resource, list its tags" efficiently via leftmost-prefix matching.
-- idx_resource_tags_tag_resource (tag_id, resource_id) supports the
-- reverse direction — "given a tag, list resources that have it,"
-- needed for tag-based browse/search filtering per DATABASE_DESIGN.md
-- Section 20.2. This same index also satisfies fk_resource_tags_tag's
-- indexing requirement, since tag_id is its leftmost column — no
-- separate single-column index on tag_id is needed on top of it.
--
-- created_at only, no updated_at, unchanged from the prior draft —
-- this is a pure junction row, deleted and reinserted, never edited
-- in place.

-- =====================================================================
-- BPC LearnShare — Database Schema
-- Part 3 of 5: Reports, Moderation History & Tracking
-- Tables: reports, resource_action_history,
--         open_replacement_tracking, open_report_tracking
-- Depends on: accounts, resources (Parts 1–2)
-- Target: MySQL/MariaDB via XAMPP/phpMyAdmin
-- Engine: InnoDB (required for foreign keys)
-- Charset: utf8mb4
-- =====================================================================

-- ---------------------------------------------------------------------
-- Table: reports
-- Purpose: User-submitted concerns about Approved resources. Report
-- status is independent of resource status (WORKFLOWS.md Section 15.3).
-- resource_id is fixed at creation and never reassigned, even if the
-- resource later becomes Replaced (D027, WORKFLOWS.md Section 21.5).
-- This is an editable core record (status/escalation/resolution fields
-- change after creation), so it gets both created_at and updated_at.
-- ---------------------------------------------------------------------
CREATE TABLE reports (
    id                          INT UNSIGNED NOT NULL AUTO_INCREMENT,

    resource_id                 INT UNSIGNED NOT NULL,
    reporter_account_id         INT UNSIGNED NOT NULL,

    reason                      ENUM(
                                    'outdated',
                                    'incorrect_or_inaccurate',
                                    'inappropriate',
                                    'duplicate_or_near_duplicate',
                                    'suspected_leaked_exam',
                                    'copyright_or_unauthorized',
                                    'other'
                                 ) NOT NULL,
    comment                     TEXT NULL,

    status                      ENUM('open', 'dismissed', 'actioned', 'escalated')
                                    NOT NULL DEFAULT 'open',

    escalated_by_account_id     INT UNSIGNED NULL,
    escalated_at                TIMESTAMP NULL DEFAULT NULL,

    resolved_by_account_id      INT UNSIGNED NULL,
    resolution_note             TEXT NULL,
    resolved_at                 TIMESTAMP NULL DEFAULT NULL,

    created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_reports_resource (resource_id),
    KEY idx_reports_reporter (reporter_account_id),
    KEY idx_reports_escalated_by (escalated_by_account_id),
    KEY idx_reports_resolved_by (resolved_by_account_id),
    KEY idx_reports_status_created (status, created_at),

    CONSTRAINT fk_reports_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_reports_reporter
        FOREIGN KEY (reporter_account_id) REFERENCES accounts (id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_reports_escalated_by
        FOREIGN KEY (escalated_by_account_id) REFERENCES accounts (id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_reports_resolved_by
        FOREIGN KEY (resolved_by_account_id) REFERENCES accounts (id)
        ON DELETE RESTRICT,

    -- Escalation fields must be consistent with the report's actual
    -- status, not just paired with each other. Fixes a gap in the
    -- prior draft, which only checked pairing and never referenced
    -- status at all.
    CONSTRAINT chk_reports_escalation_consistency
        CHECK (
            (status = 'open'
                AND escalated_by_account_id IS NULL
                AND escalated_at IS NULL)
            OR
            (status = 'escalated'
                AND escalated_by_account_id IS NOT NULL
                AND escalated_at IS NOT NULL)
            OR
            (status IN ('dismissed', 'actioned')
                AND (
                    (escalated_by_account_id IS NULL AND escalated_at IS NULL)
                    OR
                    (escalated_by_account_id IS NOT NULL AND escalated_at IS NOT NULL)
                ))
        ),

    -- Resolution fields are now strictly required whenever status is
    -- dismissed/actioned — closes the escape hatch in the prior draft
    -- that allowed a resolved report with no recorded resolver, time,
    -- or note. CHAR_LENGTH(TRIM(...)) > 0 blocks whitespace-only notes.
    CONSTRAINT chk_reports_resolution_consistency
        CHECK (
            (status IN ('open', 'escalated')
                AND resolved_by_account_id IS NULL
                AND resolved_at IS NULL
                AND resolution_note IS NULL)
            OR
            (status IN ('dismissed', 'actioned')
                AND resolved_by_account_id IS NOT NULL
                AND resolved_at IS NOT NULL
                AND resolution_note IS NOT NULL
                AND CHAR_LENGTH(TRIM(resolution_note)) > 0)
        )

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Table: resource_action_history
-- Purpose: Append-only moderation ledger. Never updated in place —
-- created_at only, no updated_at (DATABASE_DESIGN.md Section 10.1,
-- WORKFLOWS.md Section 22.4's explicit rejection of a single
-- overwritten "latest note" field).
-- actor_account_id is nullable: NULL means the action was
-- system-triggered (e.g. original resource auto-transitioning to
-- 'replaced' when its replacement is approved), not performed by a
-- human — see DATABASE_DESIGN.md Section 10.3.
-- return_to_approved covers BOTH Hidden->Approved and
-- Restricted->Approved as one action type, distinguished by
-- status_before — confirmed, not split into two action types.
-- ---------------------------------------------------------------------
CREATE TABLE resource_action_history (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,

    resource_id         INT UNSIGNED NOT NULL,
    actor_account_id    INT UNSIGNED NULL,

    action_type         ENUM(
                            'approve',
                            'reject',
                            'request_correction',
                            'resubmit',
                            'hide',
                            'restrict',
                            'remove',
                            'withdraw',
                            'return_to_approved',
                            'replaced'
                         ) NOT NULL,

    status_before       ENUM(
                            'pending', 'needs_correction', 'approved',
                            'rejected', 'withdrawn', 'hidden',
                            'restricted', 'removed', 'replaced'
                         ) NOT NULL,

    status_after        ENUM(
                            'pending', 'needs_correction', 'approved',
                            'rejected', 'withdrawn', 'hidden',
                            'restricted', 'removed', 'replaced'
                         ) NOT NULL,

    note                TEXT NULL,
    related_report_id   INT UNSIGNED NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_rah_resource_created (resource_id, created_at),
    KEY idx_rah_actor (actor_account_id),
    KEY idx_rah_related_report (related_report_id),

    CONSTRAINT fk_rah_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_rah_actor
        FOREIGN KEY (actor_account_id) REFERENCES accounts (id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_rah_related_report
        FOREIGN KEY (related_report_id) REFERENCES reports (id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_rah_status_change_or_allowed_noop
    CHECK (
        status_before <> status_after
        OR (
            action_type = 'request_correction'
            AND status_before = 'approved'
            AND status_after = 'approved'
            AND related_report_id IS NOT NULL
        )
    ),

    -- Whitespace-only notes ("  ") satisfied IS NOT NULL in the prior
    -- draft but shouldn't count as an actual note. CHAR_LENGTH(TRIM())
    -- closes that.
    CONSTRAINT chk_rah_note_required
        CHECK (
            action_type NOT IN ('reject', 'request_correction', 'hide',
                                 'restrict', 'remove')
            OR
            (note IS NOT NULL AND CHAR_LENGTH(TRIM(note)) > 0)
        )

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Table: open_replacement_tracking
-- Purpose: Database-level enforcement of D026 — only one open
-- replacement (status Pending or Needs Correction) may exist per
-- original Approved resource at a time. Row is DELETED by application
-- logic (not status-flagged) when the replacement resolves to
-- Approved, Rejected, or Withdrawn — this is what frees the original
-- for a future replacement attempt (DATABASE_DESIGN.md Section 11.3).
-- Pure tracking table: created_at only, no updated_at, since rows are
-- inserted then deleted, never edited in place. Unchanged this part.
-- ---------------------------------------------------------------------
CREATE TABLE open_replacement_tracking (
    id                          INT UNSIGNED NOT NULL AUTO_INCREMENT,

    original_resource_id        INT UNSIGNED NOT NULL,
    replacement_resource_id     INT UNSIGNED NOT NULL,

    created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_repltrack_original (original_resource_id),
    UNIQUE KEY uq_repltrack_replacement (replacement_resource_id),

    CONSTRAINT fk_repltrack_original
        FOREIGN KEY (original_resource_id) REFERENCES resources (id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_repltrack_replacement
        FOREIGN KEY (replacement_resource_id) REFERENCES resources (id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_repltrack_not_self
        CHECK (original_resource_id <> replacement_resource_id)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Table: open_report_tracking
-- Purpose: Database-level enforcement of D032 — one unresolved
-- (Open or Escalated) report per user per resource. report_id links
-- the tracking row to the specific report it guards. Row is DELETED
-- by application logic when the report resolves to Dismissed or
-- Actioned; it persists through Escalated, since escalation is still
-- unresolved. Pure tracking table: created_at only. Unchanged this part.
-- ---------------------------------------------------------------------
CREATE TABLE open_report_tracking (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,

    reporter_account_id     INT UNSIGNED NOT NULL,
    resource_id             INT UNSIGNED NOT NULL,
    report_id               INT UNSIGNED NOT NULL,

    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_reporttrack_reporter_resource (reporter_account_id, resource_id),
    UNIQUE KEY uq_reporttrack_report (report_id),

    CONSTRAINT fk_reporttrack_reporter
        FOREIGN KEY (reporter_account_id) REFERENCES accounts (id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_reporttrack_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_reporttrack_report
        FOREIGN KEY (report_id) REFERENCES reports (id)
        ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- BPC LearnShare — Database Schema
-- Part 4 of 5: Engagement & AI
-- Tables: bookmarks, helpful_marks, ai_outputs
-- Depends on: accounts, resources (Parts 1–2)
-- Target: MySQL/MariaDB via XAMPP/phpMyAdmin
-- Engine: InnoDB (required for foreign keys)
-- Charset: utf8mb4
-- =====================================================================

-- ---------------------------------------------------------------------
-- Table: bookmarks
-- Unchanged from prior accepted draft.
-- ---------------------------------------------------------------------
CREATE TABLE bookmarks (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id      INT UNSIGNED NOT NULL,
    resource_id     INT UNSIGNED NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_bookmarks_account_resource (account_id, resource_id),
    KEY idx_bookmarks_resource (resource_id),

    CONSTRAINT fk_bookmarks_account
        FOREIGN KEY (account_id) REFERENCES accounts (id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_bookmarks_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Table: helpful_marks
-- Unchanged from prior accepted draft.
-- ---------------------------------------------------------------------
CREATE TABLE helpful_marks (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_id      INT UNSIGNED NOT NULL,
    resource_id     INT UNSIGNED NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_helpful_marks_account_resource (account_id, resource_id),
    KEY idx_helpful_marks_resource (resource_id),

    CONSTRAINT fk_helpful_marks_account
        FOREIGN KEY (account_id) REFERENCES accounts (id)
        ON DELETE RESTRICT,

    CONSTRAINT fk_helpful_marks_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON DELETE RESTRICT

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Table: ai_outputs
-- Purpose: AI-generated content tied to exactly one resource (D018).
-- One row per (resource_id, output_type) — regeneration UPDATES the
-- existing row rather than inserting a new one (this is a
-- current-value table, not an event ledger — see Part 4 reasoning).
--
-- Replacement resources cannot inherit this content: a replacement is
-- a new row in `resources` with its own id, and every ai_outputs row
-- is keyed to a specific resource_id — no schema path for inheritance
-- exists (DATABASE_DESIGN.md Section 11.4).
--
-- Visibility restriction (Hidden/Restricted resource) is NOT a stored
-- lifecycle_state value. It is derived at read time by joining to the
-- resource's current status. lifecycle_state only tracks whether the
-- CONTENT itself is still usable, not whether it's currently
-- public-facing.
-- ---------------------------------------------------------------------
CREATE TABLE ai_outputs (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,

    resource_id             INT UNSIGNED NOT NULL,

    output_type             ENUM(
                                'summary',
                                'suggested_tags',
                                'suggested_metadata',
                                'duplicate_flag',
                                'moderation_hint',
                                'related_recommendation'
                             ) NOT NULL,

    content                 TEXT NULL,

    lifecycle_state         ENUM('draft', 'retained', 'invalidated')
                                NOT NULL DEFAULT 'draft',

    -- Snapshot of resources.stored_filename at the time this content
    -- was generated. Used to detect staleness on resubmission per the
    -- cost-control rule (Section 14.4).
    source_file_reference   VARCHAR(255) NULL,

    generated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_ai_outputs_resource_type (resource_id, output_type),

    CONSTRAINT fk_ai_outputs_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON DELETE RESTRICT,

    -- Active output (draft/retained) must have real, non-blank content
    -- AND a recorded source file reference — a row cannot exist in
    -- these states as an empty placeholder. Invalidated output may
    -- either have its content cleared to NULL, or retain it (per
    -- Section 14.5's "delete OR keep minimal record" allowance) — but
    -- may not hold a blank/whitespace-only string in either state.
    -- NOTE: this constraint does not itself hide invalidated content
    -- from any view — that filtering happens at query time, always.
    CONSTRAINT chk_ai_outputs_content_state
        CHECK (
            (
                lifecycle_state IN ('draft', 'retained')
                AND content IS NOT NULL
                AND CHAR_LENGTH(TRIM(content)) > 0
                AND source_file_reference IS NOT NULL
                AND CHAR_LENGTH(TRIM(source_file_reference)) > 0
            )
            OR
            (
                lifecycle_state = 'invalidated'
                AND (
                    content IS NULL
                    OR CHAR_LENGTH(TRIM(content)) > 0
                )
            )
        )

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: no "is_accepted"/"accepted_by" column exists here, and none
-- should be added. Per D028, AI suggestions are implicitly accepted if
-- a Moderator/Admin approves the resource without discarding/editing
-- them — no separate acceptance mechanism is required for v1.0.

-- =====================================================================
-- BPC LearnShare — Database Schema
-- Part 5 of 5: Notifications, Settings & Audit (REVISED)
-- Tables: notifications, system_settings, audit_log
-- Depends on: accounts (Part 1), resources, reports, resource_action_history
--             (Parts 2–3) — referenced only via application-validated
--             target_id, not real foreign keys.
-- Target: MySQL/MariaDB via XAMPP/phpMyAdmin
-- Engine: InnoDB
-- Charset: utf8mb4
-- =====================================================================

-- ---------------------------------------------------------------------
-- Table: notifications
-- Purpose: D031 lightweight in-app notifications. target_type/target_id
-- is polymorphic by design decision: MySQL/MariaDB cannot enforce a
-- foreign key across more than one possible target table, so target_id
-- integrity is 100% application-validated. This mirrors audit_log below.
--
-- REVISED from the prior draft: 'report', 'moderation_queue', and
-- 'report_queue' are added because DATABASE_DESIGN.md Section 15.4-15.5
-- and D031 explicitly name report-linked and queue-linked notifications
-- (e.g. "notify Admin accounts when a report is escalated") — the
-- prior draft incorrectly excluded these. 'action_history' remains an
-- unsourced addition of convenience, not a confirmed requirement.
--
-- Adding 'report' as a target type does NOT add a reporter-facing
-- notification workflow. It only allows the already-confirmed
-- Admin/report-queue notification examples to be stored correctly.
-- ---------------------------------------------------------------------
CREATE TABLE notifications (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,

    recipient_account_id    INT UNSIGNED NOT NULL,

    target_type             ENUM(
                                'resource',
                                'report',
                                'action_history',
                                'moderation_queue',
                                'report_queue'
                             ) NOT NULL,
    target_id               INT UNSIGNED NULL,

    message                 VARCHAR(255) NOT NULL,

    is_read                 TINYINT(1) NOT NULL DEFAULT 0,
    read_at                 TIMESTAMP NULL DEFAULT NULL,

    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    -- Supports "give me this user's unread notifications, newest first"
    -- for the badge/list shown at login.
    KEY idx_notifications_recipient_unread_created
        (recipient_account_id, is_read, created_at),

    -- Supports reverse lookup ("all notifications about resource X" /
    -- "all notifications about report Y") for troubleshooting — not a
    -- confirmed workflow requirement, cheap to have.
    KEY idx_notifications_target (target_type, target_id),

    CONSTRAINT fk_notifications_recipient
        FOREIGN KEY (recipient_account_id) REFERENCES accounts (id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_notifications_read_state
        CHECK (
            (is_read = 0 AND read_at IS NULL)
            OR
            (is_read = 1 AND read_at IS NOT NULL)
        ),

    CONSTRAINT chk_notifications_message_not_blank
        CHECK (CHAR_LENGTH(TRIM(message)) > 0),

    -- Queue-type notifications (moderation_queue, report_queue) are not
    -- about one specific row, so target_id must be NULL for those.
    -- Row-specific types (resource, report, action_history) must have
    -- a target_id. NOTE: as of this draft, no confirmed D031 example
    -- actually produces a moderation_queue/report_queue notification —
    -- every named example targets a specific resource or report. This
    -- branch is included for schema flexibility, not because a
    -- concrete trigger for it currently exists.
    CONSTRAINT chk_notifications_target_consistency
        CHECK (
            (
                target_type IN ('resource', 'report', 'action_history')
                AND target_id IS NOT NULL
            )
            OR
            (
                target_type IN ('moderation_queue', 'report_queue')
                AND target_id IS NULL
            )
        )

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Table: system_settings
-- Unchanged from prior accepted draft.
-- ---------------------------------------------------------------------
CREATE TABLE system_settings (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,

    setting_name            VARCHAR(100) NOT NULL,
    setting_value           VARCHAR(255) NOT NULL,

    updated_by_account_id   INT UNSIGNED NULL,

    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    UNIQUE KEY uq_system_settings_name (setting_name),

    CONSTRAINT fk_system_settings_updated_by
        FOREIGN KEY (updated_by_account_id) REFERENCES accounts (id)
        ON DELETE RESTRICT,

    CONSTRAINT chk_system_settings_ai_enabled_value
        CHECK (
            setting_name <> 'ai_enabled'
            OR setting_value IN ('enabled', 'disabled')
        )

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
-- Table: audit_log
-- Purpose: Administrative/system-level accountability ledger.
-- Revised per D039 to cover Admin-assisted password reset and Admin
-- taxonomy management logging without adding new tables.
--
-- target_type uses one specific value per taxonomy table (course,
-- subject, year_level, resource_type, tag) rather than one generic
-- 'taxonomy' value. This keeps target_type + target_id resolvable to
-- exactly one table for application-layer polymorphic target validation.
-- ---------------------------------------------------------------------
CREATE TABLE audit_log (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,

    actor_account_id    INT UNSIGNED NOT NULL,

    action_type         ENUM(
                            'account_created',
                            'account_disabled',
                            'account_reenabled',
                            'role_changed',
                            'password_reset',
                            'setting_changed',
                            'taxonomy_created',
                            'taxonomy_updated',
                            'taxonomy_deactivated',
                            'taxonomy_reactivated'
                         ) NOT NULL,

    target_type         ENUM(
                            'account',
                            'system_setting',
                            'course',
                            'subject',
                            'year_level',
                            'resource_type',
                            'tag'
                         ) NOT NULL,

    target_id           INT UNSIGNED NOT NULL,

    note                TEXT NULL,

    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),

    KEY idx_audit_log_actor (actor_account_id),
    KEY idx_audit_log_target (target_type, target_id, created_at),

    CONSTRAINT fk_audit_log_actor
        FOREIGN KEY (actor_account_id) REFERENCES accounts (id)
        ON DELETE RESTRICT,

    -- target_id has no normal FK because it points to a different table
    -- depending on target_type. Row existence for account, system_setting,
    -- course, subject, year_level, resource_type, and tag targets must be
    -- validated by PHP application logic.
    CONSTRAINT chk_audit_log_target_action_match
        CHECK (
            (target_type = 'account'
                AND action_type IN (
                    'account_created',
                    'account_disabled',
                    'account_reenabled',
                    'role_changed',
                    'password_reset'
                ))
            OR
            (target_type = 'system_setting'
                AND action_type = 'setting_changed')
            OR
            (target_type IN (
                    'course',
                    'subject',
                    'year_level',
                    'resource_type',
                    'tag'
                )
                AND action_type IN (
                    'taxonomy_created',
                    'taxonomy_updated',
                    'taxonomy_deactivated',
                    'taxonomy_reactivated'
                ))
        )

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
