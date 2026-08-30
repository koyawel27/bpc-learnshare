-- =====================================================================
-- BPC LearnShare D044 mandatory-password-change migration (UP)
-- Target: MariaDB 10.4.32 / InnoDB / utf8mb4
-- Baseline: verified 22-table D043 schema
-- Result: preserve 22 tables and add one bounded accounts flag
--
-- IMPORTANT:
--   * This is a reviewed one-time migration, not an idempotent script.
--   * MariaDB DDL auto-commits. Use only after backup and review.
--   * Existing accounts, including the controlled bootstrap Admin, receive 0.
--   * Application code must explicitly write 1 for every newly provisioned
--     or Admin-reset account after D044 is activated.
--   * Adding the column alone does not enforce the mandatory-change workflow.
--   * A later separately approved schema.sql update will give fresh
--     installations the same column without replaying this migration.
-- =====================================================================

ALTER TABLE accounts
    ADD COLUMN must_change_password
        TINYINT(1) NOT NULL DEFAULT 0
        AFTER account_status;
