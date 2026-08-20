# D043 AI Persistence Migration and Rollback Plan

**Date:** 2026-08-20
**Target:** MariaDB 10.4.32
**Status:** Executable package, guarded live migration, canonical 22-table schema, and unrouted provider-neutral persistence foundation verified; adapter/route integration remains unauthorized

## 1. Purpose

This package converted D043's accepted conceptual direction into exact MariaDB SQL. It first passed disposable verification without changing the legacy baseline, then received separate approval for a restore-verified backup, guarded live execution, canonical schema update, and post-migration verification.

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

The approved live gate included:

1. confirmed backup and restore procedure;
2. maintenance window with application writes stopped;
3. exact MariaDB version and clean working-tree checks;
4. pre-migration table, row-count, and `ai_outputs` lifecycle snapshot;
5. forward execution and 22-table verification;
6. application repository/processor deployment only after database verification;
7. rollback decision point and post-operation audit.

The storage migration is complete. A later separately approved checkpoint added the unrouted provider-neutral repository and guarded persistence processor, verified by 49/49 disposable checks. This still does not authorize AI routes, provider/model calls, live processing, or generated inquiry.

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
* imports the current canonical 22-table schema;
* applies rollback to recreate the guarded 18-table legacy baseline;
* inserts controlled synthetic rows and applies the forward migration;
* verifies the exact 22-table set, columns, foreign keys, checks, invalidation behavior, source-version uniqueness, locator rules, vector dimension checks, and cross-resource binding rejection;
* applies rollback;
* verifies the exact 18-table set and preservation of unrelated rows;
* confirms the configured live database remains at 22 tables and `database/schema.sql` does not change during the disposable run;
* deletes only the guarded disposable database.

Administrative connection settings are read from:

* `D043_DB_HOST` and `D043_DB_PORT`, falling back to normal local DB host/port;
* `D043_DB_ADMIN_USER`, defaulting to the local XAMPP `root` account;
* `D043_DB_ADMIN_PASS`, defaulting to an empty local XAMPP password.

The password is never printed. These variables are local test settings and must not be committed with real credentials.

### 6.1 Verification result

On 2026-08-20, the original corrected pre-live verifier passed 51/51 checks. After the live/canonical update, the revised verifier passed 60/60 checks on the exact local `10.4.32-MariaDB` runtime.

Confirmed results:

* fresh canonical import: exact 22 tables;
* canonical rollback: exact 18-table legacy baseline;
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
* live configured database remained at 22 tables during disposable verification;
* Apache restarted successfully after the maintenance window;
* `/login` and `/health` both returned HTTP 200 after restart;
* live recovery retained 2 accounts, 5 resources, 5 resource-action rows, and 5 resource-tag rows;
* all four new D043 derived-data tables remained empty after recovery;
* canonical `database/schema.sql` SHA-256: `EF1673B9DF5B618C80025B608C02E5688C3406822FBC67862C1EFEA2A6DAD740`;
* guarded disposable database removed;
* provider/model requests: zero.

The first harness attempt failed after forward execution because it expected 10 forward statements while the file correctly contained 8. Cleanup removed that disposable database. Only the verifier's statement-count expectations were corrected to the actual 8 forward and 8 rollback statements; the SQL and quality gates were not weakened.

Accepted migration hashes and current canonical artifacts:

* forward SQL SHA-256: `0874AE6D7ACE35674D75AD3FE643B8E3EB55FB1C30138E70DF7BBD5AD117D4BE`;
* rollback SQL SHA-256: `8CFCE04828D887DF857F14BBAAA7A7B3B6CDCDFAB3C8FB96622565CB387A5B91`;
* disposable verifier SHA-256: `922120A32640407327D3C90CDC5F73BDED7BCE2840F9D06DA1251C80019E13B7`;
* pre-migration dump SHA-256: `1A81C661FB37B116C2A0249FD1EB79FD337F0462586C78B425417B779730B2C1`.

## 7. Still Unselected or Unauthorized

Completing this migration does not:

* select Groq, Ollama, a model, or an embedding provider;
* add a vector database, second database, or MariaDB upgrade;
* persist query vectors, retrieval histories, inquiry answers, citations, chat messages, or cross-session memory;
* authorize generated inquiry/follow-up;
* add an AI route, UI, scheduler, or autonomous moderation authority;
* authorize commit or push without a separate reviewed Git checkpoint.

## 8. Completed Persistence Foundation and Next Approval Boundary

The separately approved provider-neutral persistence foundation is implemented through `AiPersistenceRepository` and `GuardedAiPersistenceProcessor`. `tests/ai/run_d043_ai_persistence.php` passed 49/49 checks for source versioning, readiness, complete chunk/embedding persistence, freshness, run-token rejection, output identity, cleanup, and AI-disabled fallback. The random disposable database and temporary storage were removed; the configured live database remained read-only at 22 tables.

The next decision is whether to connect one already accepted local extraction/segmentation/embedding adapter to this foundation through a bounded CLI/admin-triggered path. That approval would still add no public AI route, select no generation provider/model, and enable no generated inquiry.
