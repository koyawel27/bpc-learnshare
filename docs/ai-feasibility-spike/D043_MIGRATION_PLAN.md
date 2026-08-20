# D043 AI Persistence Migration and Rollback Plan

**Date:** 2026-08-20
**Target:** MariaDB 10.4.32
**Status:** Executable package passed disposable-database verification; live application remains unauthorized

## 1. Purpose

This package converts D043's accepted conceptual direction into exact MariaDB SQL without changing the current `database/schema.sql` baseline or the configured BPC LearnShare database.

The package contains:

* `database/migrations/20260820_d043_ai_persistence_up.sql`;
* `database/migrations/20260820_d043_ai_persistence_down.sql`;
* `tests/database/run_d043_migration_disposable.php`.

The forward migration adds four provider-neutral derived-data tables and three source/configuration identity fields to `ai_outputs`. The rollback removes those additions and restores the original 18-table structure.

## 2. Accepted Forward Structure

### `ai_source_versions`

Stores one exact resource-file version, source SHA-256, protected filename snapshot, file size, detected MIME type, optional extracted text and hash, and current/stale/invalidated state.

The nullable `current_marker` plus a unique `(resource_id, current_marker)` index permits many non-current rows but at most one current row per resource.

### `ai_processing_states`

Stores one status per source version and capability. It records configuration identity, dependency fingerprint, opaque run token, bounded attempt count, timestamps, and safe error summaries. Ready rows require a configuration identity and dependency fingerprint. Queued, processing, failed, and ready states require a run token where late-result control matters.

### `ai_chunks`

Stores ordered source-version-bound text, SHA-256, exact character count, segmentation configuration, and either verified locator fields or explicit locator unavailability. The database rejects a claimed verified locator when the required locator values are missing.

### `ai_embeddings`

Stores one vector per chunk/configuration, model reference/digest, dimension, JSON vector representation, norm, and vector hash. MariaDB validates JSON-array shape, dimension agreement, and the accepted normalization range. PHP must still reject non-numeric or non-finite elements and recompute hashes/norms before persistence.

### `ai_outputs`

The migration adds:

* `source_version_id`;
* `candidate_configuration_id`;
* `prompt_template_version`.

A composite foreign key prevents an output for one resource from using another resource's source version. Draft/retained output requires all three identity values plus nonblank content and the existing source-file reference.

## 3. Existing-Row Handling

SQL cannot calculate a trustworthy server-read file hash, extraction result, or source version for an old `ai_outputs` row.

Therefore, the forward migration marks every existing draft/retained `ai_outputs` row `invalidated` before the stronger source-binding constraint is installed. It does not delete the row, fabricate a source version, regenerate content, or silently claim that old output is current.

After later processor integration, an eligible resource may be reprocessed through the normal validated/versioned workflow.

## 4. Transaction and Downtime Boundary

MariaDB DDL auto-commits. The full migration and rollback cannot be made atomic with a normal transaction.

Any future live application requires a separate approval and must include:

1. confirmed backup and restore procedure;
2. maintenance window with application writes stopped;
3. exact MariaDB version and clean working-tree checks;
4. pre-migration table, row-count, and `ai_outputs` lifecycle snapshot;
5. forward execution and 22-table verification;
6. application repository/processor deployment only after database verification;
7. rollback decision point and post-operation audit.

No live migration is authorized by this plan.

## 5. Rollback Behavior

Rollback deliberately:

1. invalidates active AI output;
2. removes the stronger `ai_outputs` source/configuration binding;
3. restores the original `ai_outputs` content-state constraint;
4. drops embeddings, chunks, processing states, and source versions in dependency order.

Rollback destroys migrated derived data but preserves original tables and AI-output accountability rows. It never silently reactivates output after removing exact-source traceability.

## 6. Disposable Verification Boundary

The verifier:

* creates a randomly named database beginning with `bpc_learnshare_d043_verify_`;
* refuses to use the configured live database name;
* imports the unchanged 18-table baseline;
* inserts controlled synthetic rows;
* applies the forward migration;
* verifies the exact 22-table set, columns, foreign keys, checks, invalidation behavior, source-version uniqueness, locator rules, vector dimension checks, and cross-resource binding rejection;
* applies rollback;
* verifies the exact 18-table set and preservation of unrelated rows;
* confirms the configured live database table count and `database/schema.sql` hash did not change;
* deletes only the guarded disposable database.

Administrative connection settings are read from:

* `D043_DB_HOST` and `D043_DB_PORT`, falling back to normal local DB host/port;
* `D043_DB_ADMIN_USER`, defaulting to the local XAMPP `root` account;
* `D043_DB_ADMIN_PASS`, defaulting to an empty local XAMPP password.

The password is never printed. These variables are local test settings and must not be committed with real credentials.

### 6.1 Verification result

On 2026-08-20, the corrected verifier passed 51/51 checks on the exact local `10.4.32-MariaDB` runtime.

Confirmed results:

* fresh baseline import: exact 18 tables;
* forward migration: exact 22 tables;
* required columns, foreign keys, and CHECK constraints present;
* second current source version rejected;
* ready processing state without configuration identity rejected;
* missing claimed locator rejected;
* embedding dimension/JSON-length mismatch rejected;
* cross-resource source-version binding rejected;
* active output without prompt version rejected;
* controlled legacy active output preserved but invalidated and left unbound;
* rollback: exact 18 tables;
* controlled account, resource, and AI-output accountability rows preserved;
* live configured database remained at 18 tables;
* `database/schema.sql` retained SHA-256 `8C56089A01A1D6DED5C457AEBA26F695B372C4A95F536A77ECA507EA7F9BBEEE`;
* guarded disposable database removed;
* provider/model requests: zero.

The first harness attempt failed after forward execution because it expected 10 forward statements while the file correctly contained 8. Cleanup removed that disposable database. Only the verifier's statement-count expectations were corrected to the actual 8 forward and 8 rollback statements; the SQL and quality gates were not weakened.

Accepted package hashes after that correction:

* forward SQL SHA-256: `0874AE6D7ACE35674D75AD3FE643B8E3EB55FB1C30138E70DF7BBD5AD117D4BE`;
* rollback SQL SHA-256: `8CFCE04828D887DF857F14BBAAA7A7B3B6CDCDFAB3C8FB96622565CB387A5B91`;
* disposable verifier SHA-256: `67B84937DCC3B22B0EED58197978C5C881E0FEF6E03EE96BA79EA3B69EF166B9`.

## 7. Still Unselected or Unauthorized

This migration does not:

* modify `database/schema.sql`;
* change the configured live database;
* select Groq, Ollama, a model, or an embedding provider;
* add a vector database, second database, or MariaDB upgrade;
* persist query vectors, retrieval histories, inquiry answers, citations, chat messages, or cross-session memory;
* authorize generated inquiry/follow-up;
* add an AI route, UI, scheduler, or autonomous moderation authority;
* authorize commit or push.

## 8. Next Approval Boundary

After the disposable run passes and its evidence is reviewed, the next decision is whether to accept the migration package as implementation-ready.

That acceptance still does not automatically authorize applying it to `bpc_learnshare_dev`. Live application requires its own explained approval, backup confirmation, and coordinated application integration plan.
