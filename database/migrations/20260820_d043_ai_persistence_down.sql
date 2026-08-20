-- =====================================================================
-- BPC LearnShare D043 AI-derived-data persistence migration (DOWN)
-- Target: MariaDB 10.4.32
-- Result: restore the original 18-table structural baseline
--
-- WARNING:
--   * MariaDB DDL auto-commits; this rollback is not transaction-atomic.
--   * All chunk, embedding, processing-state, and source-version rows are
--     deleted when their four tables are dropped.
--   * Active ai_outputs are invalidated before source/configuration binding
--     columns are removed. They are never silently restored as trustworthy.
--   * Use only after backup/review. Live execution requires separate approval.
-- =====================================================================

UPDATE ai_outputs
SET lifecycle_state = 'invalidated'
WHERE lifecycle_state IN ('draft', 'retained');

ALTER TABLE ai_outputs
    DROP CONSTRAINT chk_ai_outputs_content_state;

ALTER TABLE ai_outputs
    DROP FOREIGN KEY fk_ai_outputs_source_resource,
    DROP INDEX idx_ai_outputs_source_version,
    DROP COLUMN prompt_template_version,
    DROP COLUMN candidate_configuration_id,
    DROP COLUMN source_version_id;

ALTER TABLE ai_outputs
    ADD CONSTRAINT chk_ai_outputs_content_state
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
        );

DROP TABLE ai_embeddings;
DROP TABLE ai_chunks;
DROP TABLE ai_processing_states;
DROP TABLE ai_source_versions;
