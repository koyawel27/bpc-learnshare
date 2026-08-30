-- =====================================================================
-- BPC LearnShare D044 mandatory-password-change migration (DOWN)
-- Target: MariaDB 10.4.32
-- Result: preserve 22 tables and remove only the additive accounts flag
--
-- WARNING:
--   * MariaDB DDL auto-commits; this rollback is not transaction-atomic.
--   * The count below must be reviewed before the column is dropped.
--   * The temporary CHECK constraint is a database-enforced fail-closed guard.
--   * If any row has must_change_password = 1, the guard statement fails and
--     the column remains present. Do not continue after that failure.
--   * Never use a client option that continues after SQL errors.
-- =====================================================================

SET SESSION check_constraint_checks = 1;

SELECT COUNT(*) AS flagged_accounts_before_d044_rollback
FROM accounts
WHERE must_change_password = 1;

ALTER TABLE accounts
    ADD CONSTRAINT chk_d044_rollback_no_flagged
        CHECK (must_change_password = 0);

ALTER TABLE accounts
    DROP CONSTRAINT chk_d044_rollback_no_flagged,
    DROP COLUMN must_change_password;
