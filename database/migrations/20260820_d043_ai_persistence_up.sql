-- =====================================================================
-- BPC LearnShare D043 AI-derived-data persistence migration (UP)
-- Target: MariaDB 10.4.32 / InnoDB / utf8mb4
-- Baseline: verified 18-table database/schema.sql
-- Result: 22 tables plus source/configuration binding on ai_outputs
--
-- IMPORTANT:
--   * This is a reviewed one-time migration, not an idempotent script.
--   * MariaDB DDL auto-commits. A normal SQL transaction cannot make the
--     complete migration atomic.
--   * Existing draft/retained ai_outputs cannot be proven source-current.
--     They are deliberately invalidated before the stronger constraint is
--     installed. Their content is not silently promoted or regenerated.
--   * This migration selects no AI provider/model and enables no inquiry UI.
-- =====================================================================

CREATE TABLE ai_source_versions (
    id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    resource_id             INT UNSIGNED NOT NULL,
    source_version_number   INT UNSIGNED NOT NULL,
    source_sha256           CHAR(64) CHARACTER SET ascii
                                COLLATE ascii_bin NOT NULL,
    stored_filename         VARCHAR(255) NOT NULL,
    file_size               INT UNSIGNED NOT NULL,
    detected_mime_type      VARCHAR(100) NOT NULL,
    extracted_text          MEDIUMTEXT NULL,
    extracted_text_sha256   CHAR(64) CHARACTER SET ascii
                                COLLATE ascii_bin NULL,
    lifecycle_state         ENUM('current', 'stale', 'invalidated')
                                NOT NULL DEFAULT 'current',
    current_marker          TINYINT UNSIGNED NULL DEFAULT 1,
    became_noncurrent_at    TIMESTAMP NULL DEFAULT NULL,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_ai_source_versions_resource_version
        (resource_id, source_version_number),
    UNIQUE KEY uq_ai_source_versions_resource_sha
        (resource_id, source_sha256),
    UNIQUE KEY uq_ai_source_versions_current
        (resource_id, current_marker),
    UNIQUE KEY uq_ai_source_versions_id_resource
        (id, resource_id),
    KEY idx_ai_source_versions_state (lifecycle_state),

    CONSTRAINT fk_ai_source_versions_resource
        FOREIGN KEY (resource_id) REFERENCES resources (id)
        ON DELETE CASCADE,

    CONSTRAINT chk_ai_source_versions_number
        CHECK (source_version_number > 0),

    CONSTRAINT chk_ai_source_versions_source_hash
        CHECK (source_sha256 REGEXP '^[0-9a-f]{64}$'),

    CONSTRAINT chk_ai_source_versions_file
        CHECK (
            file_size > 0
            AND CHAR_LENGTH(TRIM(stored_filename)) > 0
            AND CHAR_LENGTH(TRIM(detected_mime_type)) > 0
        ),

    CONSTRAINT chk_ai_source_versions_extracted_text
        CHECK (
            (
                extracted_text IS NULL
                AND extracted_text_sha256 IS NULL
            )
            OR
            (
                extracted_text IS NOT NULL
                AND CHAR_LENGTH(extracted_text) > 0
                AND extracted_text_sha256 IS NOT NULL
                AND extracted_text_sha256 REGEXP '^[0-9a-f]{64}$'
            )
        ),

    CONSTRAINT chk_ai_source_versions_current_marker
        CHECK (
            (
                lifecycle_state = 'current'
                AND current_marker = 1
                AND became_noncurrent_at IS NULL
            )
            OR
            (
                lifecycle_state IN ('stale', 'invalidated')
                AND current_marker IS NULL
                AND became_noncurrent_at IS NOT NULL
            )
        )

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE ai_processing_states (
    id                              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_version_id               BIGINT UNSIGNED NOT NULL,
    capability                      ENUM(
                                        'extraction',
                                        'segmentation',
                                        'embedding',
                                        'semantic_retrieval',
                                        'related_resources',
                                        'summary',
                                        'suggested_tags',
                                        'suggested_metadata',
                                        'duplicate_flag',
                                        'moderation_hint'
                                     ) NOT NULL,
    processing_status               ENUM(
                                        'unprocessed',
                                        'queued',
                                        'processing',
                                        'ready',
                                        'failed',
                                        'stale',
                                        'disabled'
                                     ) NOT NULL DEFAULT 'unprocessed',
    candidate_configuration_id      VARCHAR(100) NULL,
    dependency_fingerprint          CHAR(64) CHARACTER SET ascii
                                        COLLATE ascii_bin NULL,
    run_token                       VARCHAR(100) NULL,
    attempt_count                   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    queued_at                       TIMESTAMP NULL DEFAULT NULL,
    started_at                      TIMESTAMP NULL DEFAULT NULL,
    completed_at                    TIMESTAMP NULL DEFAULT NULL,
    last_error_code                 VARCHAR(80) NULL,
    last_error_summary              VARCHAR(500) NULL,
    created_at                      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_ai_processing_source_capability
        (source_version_id, capability),
    KEY idx_ai_processing_status_capability
        (processing_status, capability),

    CONSTRAINT fk_ai_processing_source_version
        FOREIGN KEY (source_version_id) REFERENCES ai_source_versions (id)
        ON DELETE CASCADE,

    CONSTRAINT chk_ai_processing_dependency_hash
        CHECK (
            dependency_fingerprint IS NULL
            OR dependency_fingerprint REGEXP '^[0-9a-f]{64}$'
        ),

    CONSTRAINT chk_ai_processing_state_fields
        CHECK (
            (
                processing_status = 'queued'
                AND run_token IS NOT NULL
                AND CHAR_LENGTH(TRIM(run_token)) > 0
                AND queued_at IS NOT NULL
                AND completed_at IS NULL
            )
            OR
            (
                processing_status = 'processing'
                AND run_token IS NOT NULL
                AND CHAR_LENGTH(TRIM(run_token)) > 0
                AND started_at IS NOT NULL
                AND completed_at IS NULL
            )
            OR
            (
                processing_status = 'ready'
                AND candidate_configuration_id IS NOT NULL
                AND CHAR_LENGTH(TRIM(candidate_configuration_id)) > 0
                AND dependency_fingerprint IS NOT NULL
                AND run_token IS NOT NULL
                AND CHAR_LENGTH(TRIM(run_token)) > 0
                AND completed_at IS NOT NULL
                AND last_error_code IS NULL
                AND last_error_summary IS NULL
            )
            OR
            (
                processing_status = 'failed'
                AND run_token IS NOT NULL
                AND CHAR_LENGTH(TRIM(run_token)) > 0
                AND completed_at IS NOT NULL
                AND last_error_code IS NOT NULL
                AND CHAR_LENGTH(TRIM(last_error_code)) > 0
                AND last_error_summary IS NOT NULL
                AND CHAR_LENGTH(TRIM(last_error_summary)) > 0
            )
            OR processing_status IN ('unprocessed', 'stale', 'disabled')
        )

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE ai_chunks (
    id                                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_version_id                   BIGINT UNSIGNED NOT NULL,
    chunk_index                         INT UNSIGNED NOT NULL,
    chunk_text                          MEDIUMTEXT NOT NULL,
    text_sha256                         CHAR(64) CHARACTER SET ascii
                                            COLLATE ascii_bin NOT NULL,
    character_count                     INT UNSIGNED NOT NULL,
    segmentation_configuration_id       VARCHAR(100) NOT NULL,
    locator_kind                        ENUM(
                                            'page',
                                            'slide',
                                            'section',
                                            'heading',
                                            'paragraph',
                                            'mixed',
                                            'unavailable'
                                         ) NOT NULL,
    start_locator                       VARCHAR(255) NULL,
    end_locator                         VARCHAR(255) NULL,
    locator_label                       VARCHAR(255) NULL,
    created_at                          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_ai_chunks_source_index (source_version_id, chunk_index),
    KEY idx_ai_chunks_source (source_version_id),

    CONSTRAINT fk_ai_chunks_source_version
        FOREIGN KEY (source_version_id) REFERENCES ai_source_versions (id)
        ON DELETE CASCADE,

    CONSTRAINT chk_ai_chunks_content
        CHECK (
            chunk_index > 0
            AND CHAR_LENGTH(chunk_text) > 0
            AND character_count = CHAR_LENGTH(chunk_text)
            AND text_sha256 REGEXP '^[0-9a-f]{64}$'
            AND CHAR_LENGTH(TRIM(segmentation_configuration_id)) > 0
        ),

    CONSTRAINT chk_ai_chunks_locator
        CHECK (
            (
                locator_kind = 'unavailable'
                AND start_locator IS NULL
                AND end_locator IS NULL
                AND locator_label IS NULL
            )
            OR
            (
                locator_kind <> 'unavailable'
                AND start_locator IS NOT NULL
                AND CHAR_LENGTH(TRIM(start_locator)) > 0
                AND end_locator IS NOT NULL
                AND CHAR_LENGTH(TRIM(end_locator)) > 0
                AND locator_label IS NOT NULL
                AND CHAR_LENGTH(TRIM(locator_label)) > 0
            )
        )

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE ai_embeddings (
    id                              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    chunk_id                        BIGINT UNSIGNED NOT NULL,
    candidate_configuration_id      VARCHAR(100) NOT NULL,
    model_reference                 VARCHAR(255) NOT NULL,
    model_digest                    CHAR(64) CHARACTER SET ascii
                                        COLLATE ascii_bin NULL,
    dimension                       SMALLINT UNSIGNED NOT NULL,
    vector_json                     LONGTEXT NOT NULL,
    vector_norm                     DECIMAL(14, 12) NOT NULL,
    vector_sha256                   CHAR(64) CHARACTER SET ascii
                                        COLLATE ascii_bin NOT NULL,
    generated_at                    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at                      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_ai_embeddings_chunk_configuration
        (chunk_id, candidate_configuration_id),
    KEY idx_ai_embeddings_configuration
        (candidate_configuration_id),

    CONSTRAINT fk_ai_embeddings_chunk
        FOREIGN KEY (chunk_id) REFERENCES ai_chunks (id)
        ON DELETE CASCADE,

    CONSTRAINT chk_ai_embeddings_identity
        CHECK (
            CHAR_LENGTH(TRIM(candidate_configuration_id)) > 0
            AND CHAR_LENGTH(TRIM(model_reference)) > 0
            AND (
                model_digest IS NULL
                OR model_digest REGEXP '^[0-9a-f]{64}$'
            )
            AND vector_sha256 REGEXP '^[0-9a-f]{64}$'
        ),

    CONSTRAINT chk_ai_embeddings_vector
        CHECK (
            dimension > 0
            AND JSON_VALID(vector_json)
            AND JSON_TYPE(vector_json) = 'ARRAY'
            AND JSON_LENGTH(vector_json) = dimension
            AND vector_norm BETWEEN 0.990000000000 AND 1.010000000000
        )

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


ALTER TABLE ai_outputs
    ADD COLUMN source_version_id BIGINT UNSIGNED NULL AFTER resource_id,
    ADD COLUMN candidate_configuration_id VARCHAR(100) NULL
        AFTER source_file_reference,
    ADD COLUMN prompt_template_version VARCHAR(100) NULL
        AFTER candidate_configuration_id,
    ADD KEY idx_ai_outputs_source_version (source_version_id);

-- Fail closed for legacy active rows. SQL alone cannot calculate the exact
-- file SHA-256/extracted source version required by D043.
UPDATE ai_outputs
SET lifecycle_state = 'invalidated'
WHERE lifecycle_state IN ('draft', 'retained');

ALTER TABLE ai_outputs
    DROP CONSTRAINT chk_ai_outputs_content_state;

ALTER TABLE ai_outputs
    ADD CONSTRAINT fk_ai_outputs_source_resource
        FOREIGN KEY (source_version_id, resource_id)
        REFERENCES ai_source_versions (id, resource_id)
        ON DELETE RESTRICT,

    ADD CONSTRAINT chk_ai_outputs_content_state
        CHECK (
            (
                lifecycle_state IN ('draft', 'retained')
                AND content IS NOT NULL
                AND CHAR_LENGTH(TRIM(content)) > 0
                AND source_file_reference IS NOT NULL
                AND CHAR_LENGTH(TRIM(source_file_reference)) > 0
                AND source_version_id IS NOT NULL
                AND candidate_configuration_id IS NOT NULL
                AND CHAR_LENGTH(TRIM(candidate_configuration_id)) > 0
                AND prompt_template_version IS NOT NULL
                AND CHAR_LENGTH(TRIM(prompt_template_version)) > 0
            )
            OR
            (
                lifecycle_state = 'invalidated'
                AND (
                    content IS NULL
                    OR CHAR_LENGTH(TRIM(content)) > 0
                )
            )
        );
