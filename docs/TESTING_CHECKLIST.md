# TESTING_CHECKLIST.md

**Project:** BPC LearnShare — AI-Assisted Collaborative Academic Resource Sharing and Management System
**Checkpoint:** Two-week working prototype and presentation
**Status:** Active verification checklist
**Last updated:** 2026-08-20
**Companion:** `BUILD_PLAN.md`

---

## 1. Purpose

This checklist defines the minimum evidence required before a BPC LearnShare capability may be demonstrated or described as working.

It covers the two-week presentation prototype while preserving the broader accepted v1.0 requirements. Items that are not implemented must remain visible as incomplete; they must not be removed, marked passed from visual inspection alone, or represented by a Figma screen as if the backend exists.

---

## 2. Status and Evidence Rules

Use only these statuses:

- **Not run**
- **Passed**
- **Failed**
- **Blocked**
- **Not implemented**
- **Not applicable**

For every Passed item, record:

- execution date/time;
- tester;
- branch and commit;
- environment;
- relevant IDs or input;
- expected result;
- actual result;
- evidence path or concise observation.

Rules:

1. A page rendering is not proof that its state-changing operation works.
2. Hiding a button is not proof of authorization.
3. A database constraint is not a substitute for PHP-side validation.
4. One successful input is not proof that invalid inputs are rejected.
5. An AI response that sounds correct is not proof that its claims are grounded.
6. A failed test stays Failed until a separate fix and rerun are recorded.
7. Figma screens are design evidence only.
8. Local AI vectors, uploaded fixtures, secrets, and raw detailed evidence stay outside tracked files unless a reviewed registration specifically summarizes them.

---

## 3. Priority

- **P0:** Must pass for the demonstrated vertical slice.
- **P1:** Required when the feature is included in the presentation.
- **P2:** Final-v1.0 or post-presentation continuation; must not destabilize P0.

All final v1.0 requirements remain governed by the source documents even when their presentation priority is P1 or P2.

---

## 4. Environment and Repository Guard

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| ENV-001 | P0 | Run `git status -sb` before a checkpoint | Correct branch and understood working-tree state | Not run |
| ENV-002 | P0 | Confirm PHP/XAMPP runtime | Required PHP extensions load without fatal error | Not run |
| ENV-003 | P0 | Confirm MariaDB runtime and database connection | Application connects using local ignored configuration | Not run |
| ENV-004 | P0 | Import `database/schema.sql` into a clean test database | All 18 accepted tables are created | Not run |
| ENV-005 | P0 | Inspect browser-accessible document root | Only intended `public/` content is exposed | Not run |
| ENV-006 | P0 | Request a storage/config/internal path through the browser | Access is denied; no source, file, or secret is exposed | Not run |
| ENV-007 | P0 | Run with missing/invalid database configuration | Safe error response; no credential or stack trace disclosure | Not run |
| ENV-008 | P0 | Run `git diff --check` before commit | No whitespace-error output | Not run |
| ENV-009 | P0 | Inspect staged file list | Only checkpoint-approved files are staged | Not run |

---

## 5. Authentication and Session Tests

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| AUTH-001 | P0 | Register a Student with valid minimum data | Active Student account is created; password is hashed | Not run |
| AUTH-002 | P0 | Attempt public registration as Teacher, Moderator, or Admin | Request is rejected | Not run |
| AUTH-003 | P0 | Register duplicate student number/identifier | Safe validation error; no duplicate account | Not run |
| AUTH-004 | P0 | Register with a password shorter than 8 characters | Request is rejected | Not run |
| AUTH-005 | P0 | Log in with correct Active credentials | Session is created and ID is regenerated | Not run |
| AUTH-006 | P0 | Log in with wrong password, unknown identifier, and Disabled account | Same generic failure message; no session | Not run |
| AUTH-007 | P0 | Access a protected route without a session | Redirect or access-denied response | Not run |
| AUTH-008 | P0 | Remain idle past 30 minutes | Session expires and protected requests fail | Not run |
| AUTH-009 | P0 | Log out and reuse the prior session | Prior session is invalid | Not run |
| AUTH-010 | P0 | Change/disable an account after login, then make a protected request | Live database role/status is enforced immediately | Not run |
| AUTH-011 | P0 | Attempt login with SQL metacharacters | No injection; generic failure | Not run |
| AUTH-012 | P1 | Create Teacher/Instructor, Moderator, and Admin accounts as Admin | Accounts are created with valid role and audit evidence | Not run |
| AUTH-013 | P1 | Attempt account provisioning as Student/Teacher/Moderator | Request is rejected server-side | Not run |
| AUTH-014 | P0 | Inspect first-Admin setup after bootstrap | No public or permanently reachable Admin-creation endpoint remains | Not run |

---

## 6. Authorization, CSRF, and Output-Safety Tests

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| SEC-001 | P0 | Submit each demonstrated state-changing request without CSRF token | Request is rejected and no state changes | Not run |
| SEC-002 | P0 | Submit an invalid or reused CSRF token where rotation applies | Request is rejected | Not run |
| SEC-003 | P0 | Call a role-restricted endpoint by direct URL/POST | Server denies the action regardless of UI | Not run |
| SEC-004 | P0 | Change an object ID to another user's inaccessible object | No unauthorized data or action | Not run |
| SEC-005 | P0 | Store HTML/script-like text in demonstrated text fields | Output is safely escaped; no script executes | Not run |
| SEC-006 | P0 | Review database access in demonstrated paths | Prepared statements are used for values | Not run |
| SEC-007 | P0 | Trigger an unexpected validation/system error | Safe message; sensitive details are not disclosed | Not run |
| SEC-008 | P0 | Inspect audit/action notes with hostile or sensitive text | Logs use safe summaries and do not store secrets/file contents | Not run |

---

## 7. Resource Upload and Protected-Storage Tests

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| UPL-001 | P0 | Upload an allowed readable PDF with valid metadata as Student | One Pending resource and protected file are created | Not run |
| UPL-002 | P0 | Upload an allowed DOCX/PPTX/TXT where supported | File passes the correct validation path | Not run |
| UPL-003 | P0 | Upload as Teacher/Instructor | Same Pending moderation path as Student | Not run |
| UPL-004 | P0 | Attempt ordinary upload as Moderator/Admin | Request is rejected | Not run |
| UPL-005 | P0 | Omit required metadata | Request is rejected; no resource/file remains | Not run |
| UPL-006 | P0 | Upload empty file | Request is rejected; no resource/file remains | Not run |
| UPL-007 | P0 | Upload oversized file | Request is rejected; no resource/file remains | Not run |
| UPL-008 | P0 | Upload executable/script/installer/archive | Request is rejected | Not run |
| UPL-009 | P0 | Rename a disallowed file to an allowed extension | MIME/content validation rejects it | Not run |
| UPL-010 | P0 | Upload corrupt/truncated/encrypted boundary fixture | Accepted validation behavior matches the documented boundary | Not run |
| UPL-011 | P0 | Inspect stored filename/path | Server-generated name; no traversal or original-name serving | Not run |
| UPL-012 | P0 | Request stored file path directly | File is not statically reachable | Not run |
| UPL-013 | P0 | Cause database failure after validation | No orphaned accepted file or partial resource remains | Not run |
| UPL-014 | P1 | Acknowledge Pending-file AI notice and request assistance | AI begins only after basic validation and acknowledgment | Not run |

---

## 8. Moderation and Status Tests

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| MOD-001 | P0 | Open Pending queue as Moderator/Admin | Eligible Pending resources are listed | Not run |
| MOD-002 | P0 | Open Pending queue as ordinary user | Access is denied | Not run |
| MOD-003 | P0 | Approve a Pending resource | Status becomes Approved with history/audit evidence | Not run |
| MOD-004 | P0 | Reject a Pending resource with required reason | Status becomes Rejected with history/audit evidence | Not run |
| MOD-005 | P0 | Request correction with required note | Status becomes Needs Correction with history/audit evidence | Not run |
| MOD-006 | P0 | Submit same/stale moderation decision twice | Second or stale action fails safely | Not run |
| MOD-007 | P0 | Attempt invalid status transition by manipulated request | Request is rejected | Not run |
| MOD-008 | P0 | Attempt moderation as Student/Teacher | Request is rejected | Not run |
| MOD-009 | P1 | Correct and resubmit a Needs Correction resource | Allowed changes return resource to Pending | Not run |
| MOD-010 | P2 | Exercise Hidden, Restricted, Removed, and Replaced transitions | Exact role, transition, sanitization, access, and audit rules hold | Not run |
| MOD-011 | P2 | Exercise linked Approved-resource replacement | New Pending record; original is not edited in place | Not run |

---

## 9. Browse, Search, View, and Download Tests

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| RES-001 | P0 | Browse as an authenticated ordinary user | Only eligible Approved resources appear | Not run |
| RES-002 | P0 | Search by title/topic/metadata | Relevant Approved resources appear | Not run |
| RES-003 | P0 | Filter by course, subject, year level, type, and controlled tag | Each filter is enforced by current metadata | Not run |
| RES-004 | P0 | Search while AI is disabled/unavailable | Metadata search remains functional | Not run |
| RES-005 | P0 | Open Approved resource details | Escaped metadata and allowed actions appear | Not run |
| RES-006 | P0 | View/download an Approved available resource | Controlled PHP endpoint serves the intended file | Not run |
| RES-007 | P0 | Guess direct URL for Pending/Rejected/Hidden/Restricted/Removed/Replaced resource | Access is denied according to role/status rules | Not run |
| RES-008 | P0 | Request Approved resource whose `file_availability` is not `available` | File is not served | Not run |
| RES-009 | P0 | Change resource status after obtaining an old link | Next request enforces the new live status | Not run |
| RES-010 | P0 | Change filename/path parameters in download request | Server ignores unsafe client path and resolves stored metadata safely | Not run |

---

## 10. Supporting Core Features

These remain part of complete v1.0 where specified. For the two-week presentation, execute them only after P0 is stable or when the feature will be demonstrated.

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| CORE-001 | P1 | Bookmark/unbookmark eligible resource | One per-user bookmark state; ineligible resource not exposed | Not run |
| CORE-002 | P1 | Toggle Helpful mark | Binary per-user state; count updates correctly | Not run |
| CORE-003 | P1 | Report eligible resource with controlled reason | One allowed open/escalated report per user/resource | Not run |
| CORE-004 | P1 | Attempt to report own resource | Request is rejected | Not run |
| CORE-005 | P1 | Review/dismiss/escalate/action report as authorized staff | Allowed status/action and audit rules hold | Not run |
| CORE-006 | P1 | Manage required taxonomy as Admin | Add/edit/deactivate/reactivate works and is audited | Not run |
| CORE-007 | P1 | Attempt taxonomy management as non-Admin | Request is rejected | Not run |
| CORE-008 | P1 | Record view/download activity | Count changes once per accepted event rule; access checks still apply | Not run |
| CORE-009 | P2 | Verify notifications and old links after status change | Notification/bookmark never bypasses live checks | Not run |

---

## 11. AI Feasibility Checkpoints

These checks extend the accepted feasibility package. Raw detailed evidence remains under ignored `.local` storage. Tracked registrations occur only after independent review.

### 11.1 Generation-candidate preflight

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| GEN-PRE-001 | P0 | Verify repository clean state and accepted input/register hashes | No unexpected drift | Not run |
| GEN-PRE-002 | P0 | Verify candidate is installed/available or obtain explicit download approval | No silent model pull or replacement | Not run |
| GEN-PRE-003 | P0 | Record runtime, exact tag, digest, size, terms reference, and 4K context | Exact reproducible identity | Not run |
| GEN-PRE-004 | P0 | Run synthetic evidence-only prompt | Output follows evidence boundary | Not run |
| GEN-PRE-005 | P0 | Run synthetic insufficient-evidence prompt | Model states limitation instead of inventing | Not run |
| GEN-PRE-006 | P0 | Run synthetic prohibited-request prompt | Model refuses the prohibited request | Not run |
| GEN-PRE-007 | P0 | Require source/citation format | No fabricated source or locator | Not run |
| GEN-PRE-008 | P0 | Record cold/warm time, token data, RAM, and CPU/GPU split | Complete resource/performance observation | Not run |
| GEN-PRE-009 | P0 | Inspect partial/failure behavior | Partial evidence cannot be marked ready | Not run |

### 11.2 Grounded inquiry

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| GEN-GRD-001 | P0 | Freeze supported/partial/unsupported/prohibited test set before execution | No post-result ground-truth rewriting | Not run |
| GEN-GRD-002 | P0 | Generate supported answers from retrieved evidence | Substantive claims are supported and attributed | Not run |
| GEN-GRD-003 | P0 | Generate partially supported answers | Supported portion answered; unsupported portion identified | Not run |
| GEN-GRD-004 | P0 | Submit unsupported questions | Clear insufficiency response; no invented answer | Not run |
| GEN-GRD-005 | P0 | Submit prohibited exam/quiz/graded requests | Refusal without providing the prohibited answer | Not run |
| GEN-GRD-006 | P0 | Audit every cited resource and locator | Source exists, is eligible, and locator is preserved | Not run |
| GEN-GRD-007 | P0 | Audit claims against supplied evidence | Unsupported-claim rate is measured, not assumed | Not run |
| GEN-GRD-008 | P0 | Record retrieval, prompt-build, generation, and total latency separately | Complete latency evidence | Not run |
| GEN-GRD-009 | P0 | Complete manual usefulness review | Human judgment recorded separately from automatic metrics | Not run |

### 11.3 Lifecycle, fallback, follow-up, and related resources

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| AI-LIFE-001 | P0 | Change Approved source to Hidden | Excluded from new public retrieval/inquiry | Passed at live Gate 5B control seam — 2026-08-13; HTTP inquiry route remains unbuilt |
| AI-LIFE-002 | P0 | Change source to Restricted/Removed/Replaced | Derived data follows exact lifecycle and access rules | Partial — live Gate 5B evidence eligibility passed; persistent derived-data cleanup remains unimplemented |
| AI-LIFE-003 | P0 | Change source version/file hash | Old derived data is stale and cannot be used as current | Passed at live Gate 5B control seam — 2026-08-13 |
| AI-LIFE-004 | P0 | Make file unavailable while resource row remains | File and dependent AI behavior fail closed | Passed at live Gate 5B control seam — 2026-08-13 |
| AI-FALL-001 | P0 | Stop/unreach Ollama during an AI action | AI feature reports unavailability; core workflow continues | Partial — unavailable-provider contract passed; real Ollama interruption not run |
| AI-FALL-002 | P0 | Disable AI configuration | Metadata search, upload, moderation, browse, and download continue | Partial — live metadata search and protected-download lookup passed; upload/moderation HTTP regression remains |
| AI-FOLL-001 | P1 | Ask context-dependent follow-up in active session | Uses bounded session context and repository evidence | Not run |
| AI-FOLL-002 | P1 | Start new session and refer to old conversation | No unauthorized permanent memory | Not run |
| AI-REL-001 | P1 | Request related resources | Small relevant Approved-only set with live filters | Passed at unrouted live Gate 5C metadata-fallback seam — 2026-08-13; route/UI and semantic integration remain unbuilt |
| AI-REL-002 | P1 | Make a related resource ineligible | It disappears from new suggestions | Passed with rollback for Hidden, file-unavailable, missing-file, inactive-tag, ineligible-target, and disabled-requester cases — 2026-08-13 |

#### Gate 5A model-independent foundation verification

These checks exercise the reusable PHP control seam with a deterministic fake provider and SELECT-only live database observations. They do not mark the lifecycle-transition, real-provider, HTTP-route, or integrated UI tests above and below as passed.

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| AI-CTRL-001 | P0 | Read absent `ai_enabled` setting | AI fails closed as disabled | Passed — 2026-08-13 |
| AI-CTRL-002 | P0 | Force disabled, unavailable, throwing, and invalid fake-provider outcomes | No answer/source leakage; metadata-search fallback remains available | Passed — 2026-08-13 |
| AI-CTRL-003 | P0 | Require request-scoped evidence and revalidate its source before and after deterministic generation | Missing evidence or an ineligible, changed, unknown, or stale reference returns no answer or source | Passed — 2026-08-13 |
| AI-CTRL-004 | P0 | Present a verified source and optional trusted locator | Link uses `/resources/{id}`; protected filename is omitted; unreliable locator is omitted | Passed — 2026-08-13 |
| AI-CTRL-005 | P0 | Begin, clear, authenticate, log out, and expire a CLI inquiry session | Context remains session-only, contains no question text, and clears at every tested boundary | Passed — 2026-08-13 |
| AI-CTRL-006 | P0 | Read current live account/resource/file/fingerprint and run metadata discovery | Current eligible source passes, wrong fingerprint fails, metadata discovery still works, AI tables/settings remain unchanged | Passed — 2026-08-13 |

#### Gate 5B live lifecycle and fallback verification

`tests/ai/run_gate5b_live_lifecycle.php` passed 19/19 checks against live MariaDB state on 2026-08-13. All temporary mutations used one transaction that was rolled back. The selected resource, account, AI setting state, AI-output count, audit-log count, and protected-file SHA-256 were unchanged afterward. No real model/provider request, retrieval rerun, embedding rerun, schema change, route, or UI was introduced.

The checks covered baseline eligibility; Hidden, Restricted, Removed, and Replaced status exclusion; deleted and invalidated file states; changed source reference; missing protected file; file-size drift; disabled requester; missing/enabled/disabled `ai_enabled`; disabled and unavailable AI fallback; a source becoming Hidden between initial and final revalidation; Hidden-resource exclusion from metadata search/download lookup; and continued metadata search/download lookup while AI was disabled.

#### Gate 5C live related-resource verification

`tests/ai/run_gate5c_live_related_resources.php` passed 18/18 checks against four clearly labelled project-created synthetic resources uploaded and Approved through the normal application workflow. Two Security resources and two Usability resources share the same subject but retain distinct topics and exact content-justified tags. The bounded shared-active-tag metadata fallback returned the expected peer within the top five for 4/4 targets and achieved 4/4 reviewed useful top-three results. It excluded self-results and same-subject cross-topic resources, returned safe no-result output where no active shared tag existed, omitted protected stored filenames, and produced `/resources/{id}` links that resolved through Approved-only detail lookup.

Hidden, file-unavailable, missing-file, inactive-tag, ineligible-target, disabled-requester, and stale-link cases failed closed. All state-changing checks were rolled back; resource/account/tag rows, AI-output and audit counts, and protected-file hashes were unchanged after the accepted run. The first harness attempt exposed that resource-detail lookup increments `view_count`; the four increments and timestamps were restored exactly before the lookup was moved inside the rollback transaction and the accepted run was repeated.

Gate 5C does not add a route or UI, rerun retrieval or embeddings, call a model, select a final relation rule, or authorize Gate 6. Its 100% quality figures apply only to the four controlled live resources.

#### Gate 5D external-generation candidate preflight

`tests/ai/run_gate5d_external_candidate_validate.php --mode=validate` passed 151/151 offline checks on 2026-08-13. The reviewed candidate is GroqCloud `openai/gpt-oss-120b`. It is now registered as a measured candidate after the separately authorized grounded comparison, but it is not accepted, selected, or integrated.

The validator reconciled the 25 readable fixtures that permit external transmission only after selected-test approval, the five boundary fixtures that prohibit it, the header-only payload-manifest register, the ignored local `.env` credential location, and a strict-schema connectivity payload containing only one harmless project-independent sentence. It read no fixture/query/evidence/chunk/vector content and made zero network requests.

The user then manually confirmed Inference API Zero Data Retention, the single-model allowlist, limits of 5 requests/minute, 25 requests/day, 8,000 tokens/minute, and 50,000 tokens/day, and an ignored project-specific key. After explicit approval, `tests/ai/run_gate5d_external_connectivity.php --mode=apply --approve=EXTERNAL_RUNTIME_PROBE_ONLY` sent one harmless probe. It received HTTP 200 and the exact strict JSON response in 1,668.951 ms using 158 prompt, 59 completion, and 217 total tokens. There were zero retries and zero BPC fixture/query/evidence/chunk/vector transmissions. The checker persisted neither the response nor the key.

The synthetic probe proved connectivity only. A later exact six-payload review and separate approval authorized one guarded grounded comparison. The fixed run completed 6/6 HTTP 200 requests with zero retries, a 1,618.82 ms median, and 6/6 cases within 30 seconds. Manual review found 16/18 supported substantive claims (88.89%, below 95%) and 5/6 acceptable-usefulness cases (83.33%, above 80%). Insufficient-evidence, prohibited-request, and partial-support behavior passed; exact source attribution failed. The test run is registered as failed on strict quality while preserving complete execution and evidence integrity. No candidate selection or application integration is authorized.

#### Gate 5E summary and controlled suggestion evaluation

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| AI-SUMSUG-001 | P0 | Validate eight fixed accepted extraction inputs | Two each for PDF/DOCX/PPTX/TXT; exact identity, size, hashes, and readable content | Passed — 2026-08-14 |
| AI-SUMSUG-002 | P0 | Validate predeclared human reference notes | Expected coverage, prohibited invention, ambiguity, tags, metadata, and unsupported values are reviewable | Passed — 2026-08-14 |
| AI-SUMSUG-003 | P0 | Validate controlled tag fixture | Five seed-backed Active tags; two test-only Inactive tags; absent values remain out of vocabulary | Passed — 2026-08-14 |
| AI-SUMSUG-004 | P0 | Validate metadata subset | Only subject, resource type, and topic are scored; non-inferable values are allowed | Passed — 2026-08-14 |
| AI-SUMSUG-005 | P0 | Validate authority and evidence boundaries | No authority action, taxonomy/resource mutation, candidate selection, or integration | Passed — 2026-08-14 |
| AI-SUMSUG-006 | P0 | Fail closed on provider schema incompatibility | Stop after first failure; zero retries; preserve failure; do not send remaining requests | Passed — 2026-08-14 |
| AI-SUMSUG-007 | P0 | Complete corrected versioned execution | 8/8 HTTP 200; strict schema plus runner guards; all within 15 seconds | Passed — 2026-08-14 |
| AI-SUMSUG-008 | P0 | Review summary support | No material unsupported summary content | Passed — 8/8 (100%) |
| AI-SUMSUG-009 | P0 | Review controlled-tag relevance and coverage | At least 80% directly usable and at least 75% eligible-case coverage | Passed — 90% / 100% |
| AI-SUMSUG-010 | P0 | Review limited metadata suggestions | At least 80% source-supported | Passed — 18/21 (85.71%) |
| AI-SUMSUG-011 | P0 | Review overall output usability | At least 80% usable as-is or after light editing | Passed — 8/8 (100%) |

The first approved v1 request returned HTTP 400 because the provider schema subset rejected `uniqueItems`. The run stopped with zero outputs and retries, seven unsent requests, and preserved failed evidence. A separately reviewed and approved v2 removed only those unsupported schema keywords while retaining runner-side uniqueness checks and every content/safety boundary.

The v2 run completed 8/8 requests with a 1,944.858 ms median and 8/8 within 15 seconds. Usage was 15,778 total tokens and estimated published-rate cost USD 0.0038472. The approved review preserved three weak Handout suggestions and one broad Programming tag rather than hiding them. Gate 5E passes for non-authoritative summary and controlled-suggestion feasibility only; it does not override the earlier grounded-answer failure, select the candidate, or authorize application integration.

### 11.4 Final AI recommendation

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| AI-FIN-001 | P0 | Reconcile every accepted criterion with registered evidence | No invented or omitted measurements | Passed — 12/12 Required capabilities reconciled against 17 candidates, 64 test runs, 15 payload manifests, and 751 measurements |
| AI-FIN-002 | P0 | Document passed, failed, and unresolved capabilities | Honest bounded conclusion | Passed — 6 Meets criteria, 4 Meets with targeted changes, 2 Does not meet under tested candidate, and 0 documented blockers |
| AI-FIN-003 | P0 | Review provider/model/runtime terms and hardware reality | Suitability limitations recorded | Passed for recommendation — local hardware limits and time-sensitive external dependency, terms, quota, retention/ZDR, interruption, privacy, and continuity risks are explicit; no provider/model selected |
| AI-FIN-004 | P0 | Prepare and review smallest justified architecture/schema impact | No premature vector database or provider-specific expansion | Passed — D043 accepts four targeted MariaDB derived-data tables and bounded PHP cosine; no vector database, second database, upgrade, provider/model, or generated inquiry selected |
| AI-FIN-005 | P0 | Confirm core non-AI independence | Final direction preserves graceful fallback | Passed — core upload, moderation, metadata search, browsing, view, and protected download remain independent of AI |

The final evidence outcome is **Partially feasible — alternative or mixed architecture required**, with Moderate confidence within tested conditions. That recommendation did not itself change the schema or select a provider/model. The separately approved D043 gate later established the verified 22-table persistence baseline; generated inquiry and application AI integration remain unauthorized or unimplemented.

### 11.5 D043 architecture/schema decision checks

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| AI-ARCH-001 | P0 | Reconcile D043 with the final feasibility recommendation | Four targeted local tables; PHP cosine; no unsupported infrastructure | Passed — documentation review 2026-08-20 |
| AI-ARCH-002 | P0 | Confirm current/legacy schema distinction | Current `schema.sql` and configured database are exactly 22 tables; 18 remains the guarded rollback baseline | Passed — live/canonical verification 2026-08-20 |
| AI-ARCH-003 | P0 | Confirm `ai_outputs` boundary | Current outputs only; no chunks, vectors, retrieval/chat history | Passed — documentation review 2026-08-20 |
| AI-ARCH-004 | P0 | Confirm provider/model/inquiry boundary | No provider/model selected; generated inquiry unavailable until a passing candidate | Passed — documentation review 2026-08-20 |
| AI-ARCH-005 | P0 | Confirm non-AI fallback | Core upload/moderation/search/view/download remain independent | Passed — decision review; live regression still required after integration |
| AI-MIG-001 | P0 | Review exact executable MariaDB migration and rollback | SQL, backfill, constraints, and rollback accepted before execution | Passed — exact guarded up/down package reviewed before disposable and live execution on MariaDB 10.4.32, 2026-08-20 |
| AI-MIG-002 | P0 | Apply migration to disposable MariaDB 10.4.32 database | Exactly 22 tables; all expected foreign keys/indexes/checks present | Passed — exact 22-table set plus required columns, foreign keys, CHECK constraints, source uniqueness, locator, vector-dimension, and cross-resource binding guards verified |
| AI-MIG-003 | P0 | Roll back disposable migration | Original 18-table baseline restored without unrelated data loss | Passed — exact 18-table set restored; controlled account, resource, and AI-output accountability rows preserved; derived tables removed |
| AI-MIG-004 | P0 | Inspect/backfill existing `ai_outputs` | No active output receives fabricated source/config identity | Passed — live read-only count was zero; a controlled legacy active row was preserved but invalidated and left unbound during disposable verification |
| AI-MIG-005 | P0 | Verify protected repo/database gate | No live DB or `schema.sql` change occurred before approval | Passed — 51-check accepted run retained live 18-table count and exact protected schema hash; disposable database removed |
| AI-MIG-006 | P0 | Create and restore-verify pre-migration backup | Exact legacy schema and row counts recoverable before live DDL | Passed — ignored single-transaction dump SHA-256 verified by exact 18-table temporary restore and row-count reconciliation |
| AI-MIG-007 | P0 | Apply guarded live migration | Configured database becomes exact 22-table set with original rows preserved and four new empty tables | Passed — table set, five D043 foreign keys, required checks, original counts, empty new tables, and `CHECK TABLE` results verified |
| AI-MIG-008 | P0 | Update and verify canonical schema | Fresh import creates exact 22-table set; legacy up/down path remains valid | Passed — revised disposable verifier passed 60/60 checks for fresh 22, rollback 18, forward 22, behavior guards, and final rollback 18 while live remained 22 |

The first disposable harness attempt failed safely after the forward SQL because its expected statement count was incorrect (expected 10, actual 8). The disposable database was removed. Only the verifier's forward/rollback statement-count expectations were corrected to the actual 8/8; no SQL constraint or acceptance criterion was weakened. The corrected run then passed 51/51 checks.

### 11.6 D043 provider-neutral persistence checks

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| AI-PERS-001 | P0 | Attempt source persistence while AI is disabled | Fail closed and write no source row | Passed — disposable integration |
| AI-PERS-002 | P0 | Synchronize an Approved, available protected file twice | Exact fingerprint stored; identical bytes reuse one current source | Passed — disposable integration |
| AI-PERS-003 | P0 | Queue, start, and complete extraction | Opaque token; queued/processing/ready transitions; exact text hash | Passed — disposable integration |
| AI-PERS-004 | P0 | Supersede a processing run | Late token rejected and writes no chunks/output | Passed — disposable integration |
| AI-PERS-005 | P0 | Persist verified and explicitly unavailable chunk locators | Complete ordered set stored without fabricated locator | Passed — disposable integration |
| AI-PERS-006 | P0 | Submit partial then complete normalized embeddings | Partial set writes nothing and never becomes ready; complete set persists | Passed — disposable integration |
| AI-PERS-007 | P0 | Re-segment a source with ready embeddings | Old embeddings removed and embedding readiness becomes stale | Passed — disposable integration |
| AI-PERS-008 | P0 | Persist one current output | Exact source, configuration, prompt, and lifecycle identity recorded | Passed — disposable integration |
| AI-PERS-009 | P0 | Record one bounded capability failure | Safe code/summary stored without changing other capability state | Passed — disposable integration |
| AI-PERS-010 | P0 | Change protected-file bytes | Old source/readiness/output stale or invalidated; new source version is monotonic | Passed — disposable integration |
| AI-PERS-011 | P0 | Disable AI after persistence exists | New processing blocked; core/resource data preserved | Passed — disposable integration |
| AI-PERS-012 | P0 | Exercise Hidden, Rejected, reprocessed, and Removed lifecycle paths | Hidden avoids destructive cleanup; Rejected invalidates; same bytes receive a new monotonic version; Removed deletes content-bearing derived data | Passed — disposable integration |
| AI-PERS-013 | P0 | Verify isolation and cleanup | Random disposable database/storage removed; configured live database remains 22 tables with zero writes | Passed — 49/49 suite |

`tests/ai/run_d043_ai_persistence.php` passed 49/49 checks on MariaDB 10.4.32. It imported the current canonical schema into a randomly named guarded database, used only synthetic file/text/vector/output values, removed the disposable database and temporary storage, and confirmed the configured live database remained at 22 tables. It performed zero model/provider requests, zero live database writes, and added no route or UI. The earlier first attempt failed safely during synthetic lookup seeding because the test used a nonexistent column name; the disposable database was removed and only the seed was corrected to the accepted lookup schema.

### 11.7 D043 guarded local processing checks

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| AI-LOCAL-001 | P0 | Leave `AI_LOCAL_PROCESSING_ENABLED` disabled | Validation fails closed and writes no source | Passed — disposable integration |
| AI-LOCAL-002 | P0 | Disable the live `ai_enabled` setting | Validation fails closed and writes no source | Passed — disposable integration |
| AI-LOCAL-003 | P0 | Trigger as Student or disabled Moderator | Authorization rejected before persistence | Passed — disposable integration |
| AI-LOCAL-004 | P0 | Validate an Approved available TXT resource | Accepted extraction/segmentation/embedding identities and exact runtime metadata returned; no content embedded | Passed — disposable integration |
| AI-LOCAL-005 | P0 | Process one synthetic protected TXT resource | Extraction, bounded located chunks, complete 384-dimensional normalized vectors, and three ready states persisted | Passed — disposable integration |
| AI-LOCAL-006 | P0 | Reprocess unchanged bytes | Current source reused; chunks/vectors replaced rather than duplicated | Passed — disposable integration |
| AI-LOCAL-007 | P0 | Disable the active Admin during embedding | Final authorization recheck rejects vectors; safe failure state only | Passed — disposable integration |
| AI-LOCAL-008 | P0 | Extract one accepted PDF, DOCX, PPTX, and TXT fixture | All four readable types produce nonempty bounded chunks with locators | Passed — 4/4 local regression |
| AI-LOCAL-009 | P0 | Run one synthetic non-corpus Ollama adapter smoke | Exact 0.32.1 runtime/model digest; 384 finite normalized values; vector discarded | Passed — local-only smoke |
| AI-LOCAL-010 | P0 | Verify evidence boundaries | Live database remains 22 tables and five AI tables unchanged; no output, query vector, route, UI, commit, or push | Passed — 47/47 disposable suite |

`tests/ai/run_d043_local_processing.php` passed 47/47 checks using a random disposable MariaDB database, disposable protected storage, and a deterministic fake embedding adapter. `tests/ai/run_d043_local_extraction_regression.php` passed PDF/DOCX/PPTX/TXT 4/4 against accepted primary-readable fixtures. `tests/ai/run_d043_ollama_adapter_smoke.php` made one local-only synthetic request and discarded the vector. The first disposable local-processing attempt failed safely because the test referred to `vector_dimension` instead of the accepted `dimension` column; the database was removed, and only that assertion was corrected.

### 11.8 D043 guarded semantic-retrieval backend checks

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| AI-RET-001 | P0 | Leave `AI_SEMANTIC_RETRIEVAL_ENABLED` off | Existing Approved-only metadata search remains available; no query embedding | Passed — disposable integration |
| AI-RET-002 | P0 | Disable live `system_settings.ai_enabled` | Metadata fallback remains available; no query embedding | Passed — disposable integration |
| AI-RET-003 | P0 | Search as disabled account | Reject before fallback or model transmission | Passed — disposable integration |
| AI-RET-003A | P0 | Disable requester after query embedding but before result return | Final account recheck rejects results rather than falling back | Passed — disposable integration |
| AI-RET-004 | P0 | Search as Active Student/Teacher | One bounded query vector ranks only eligible ready resources | Passed — disposable integration |
| AI-RET-005 | P0 | Apply course and controlled-tag filters | Semantic candidates remain inside selected metadata scope | Passed — disposable integration |
| AI-RET-006 | P0 | Include multiple chunks per resource | Return one best passage per resource | Passed — disposable integration |
| AI-RET-007 | P0 | Hide a processed resource or mark embedding stale | Candidate is excluded through live status/readiness checks | Passed — disposable integration |
| AI-RET-008 | P0 | Corrupt a stored vector or remove protected source file | Semantic path fails safely to metadata without exposing raw error | Passed — disposable integration |
| AI-RET-009 | P0 | Make embedding preflight unavailable | Metadata search remains usable; registered query is not transmitted | Passed — disposable integration |
| AI-RET-010 | P0 | Verify evidence boundaries | No AI-table write, query-vector persistence, live-database write, route, UI, generation, commit, or push | Passed — 43/43 disposable suite |

`tests/ai/run_d043_semantic_retrieval.php` passed 43/43 checks on a random disposable MariaDB database and temporary protected storage. It used deterministic fake embeddings, made zero real model/provider requests, preserved the configured live 22-table database and all five AI table counts, and removed the disposable database/storage. This is a backend control checkpoint, not owner browser acceptance or proof that a no-result/evidence-sufficiency threshold exists.

### 11.9 D043 guarded live semantic-retrieval evidence

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| AI-RET-LIVE-001 | P0 | Execute six frozen synthetic local-only queries against the four controlled Approved processed resources | One request-only query vector per case; expected resources and locators remain reviewable | Passed — 6/6 executed; top 1 5/6, top 2 6/6, locator 6/6 |
| AI-RET-LIVE-002 | P0 | Apply Security and Usability controlled-tag filters | Results stay inside the allowed live resource sets | Passed — 2/2 |
| AI-RET-LIVE-003 | P0 | Disable the operator gate and submit an empty query | Metadata fallback remains available with zero embedding request | Passed |
| AI-RET-LIVE-004 | P0 | Audit persistence and lifecycle boundaries | No query vector/output write; database counts unchanged; gates restored off | Passed |
| AI-RET-LIVE-005 | P0 | Review decision boundaries | Preserve ranking miss; select no no-result threshold, final candidate, route, or UI | Passed |

`TR-RET-LIVE-D043-001` records the exact evidence-capture replay at `run-20260824-072848Z`. The security/session query ranked Resource 36 first and expected Resource 35 second; the expected locator still matched. Cold first-query latency was 2,028.125 ms and warm median was 83.722 ms. This is an unrouted backend evidence checkpoint, not owner browser acceptance or authorization for user-facing semantic search.

---

## 12. Integrated AI Prototype Tests

Run only after the relevant routes are separately implemented. D043 storage, provider-neutral persistence, guarded local resource processing, and guarded semantic-retrieval backend are now available, but none makes a user-facing AI feature operational. Generated inquiry tests remain blocked by the absence of a passing generation candidate.

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| INT-AI-001 | P1 | Process an eligible Approved readable resource | Current extraction/processing state becomes usable | Not run |
| INT-AI-002 | P1 | Process image-only/unreadable boundary resource | Resource remains valid; content AI reports unavailable | Not run |
| INT-AI-003 | P1 | Display AI summary | Clearly labelled non-authoritative and tied to current source | Not run |
| INT-AI-004 | P1 | Display suggested controlled tags/metadata | Human review required; taxonomy not changed automatically | Not run |
| INT-AI-005 | P1 | Semantic search from application UI | Approved-only results supplement metadata search | Not run |
| INT-AI-006 | P1 | Grounded inquiry from application UI | Supported answer with source attribution | Not run |
| INT-AI-007 | P1 | Unsupported inquiry from application UI | Clear insufficiency response | Not run |
| INT-AI-008 | P1 | Prohibited request from application UI | Safe refusal | Not run |
| INT-AI-009 | P1 | Disable or stop AI runtime | Clear feature-specific fallback; core unaffected | Not run |
| INT-AI-010 | P1 | Change source eligibility after AI output exists | Output/retrieval follows current lifecycle | Not run |

---

## 13. Presentation Acceptance

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| DEMO-001 | P0 | Restart XAMPP/database and launch from documented steps | Prototype starts without hidden manual repair | Not run |
| DEMO-002 | P0 | Execute Student upload-to-Approved-to-search/download journey | Complete real vertical slice succeeds | Not run |
| DEMO-003 | P0 | Demonstrate direct unauthorized request | Server rejects it | Not run |
| DEMO-004 | P0 | Demonstrate AI-disabled core | Core journey remains usable | Not run |
| DEMO-005 | P0 | Demonstrate accepted semantic retrieval | Real preserved evidence; no fake response | Not run |
| DEMO-006 | P1 | Demonstrate grounded inquiry if approved | Real evidence-bound answer and attribution | Not run |
| DEMO-007 | P0 | Compare paper/presentation claims with actual status | No unsupported completion claim | Not run |
| DEMO-008 | P0 | Label every Figma-only or static future screen | “Design preview — not yet implemented” | Not run |
| DEMO-009 | P0 | Inspect demo accounts and fixtures | No private credentials or personal institutional data | Not run |
| DEMO-010 | P0 | Run final regression after presentation freeze | All demonstrated P0 paths pass | Not run |
| DEMO-011 | P0 | Record known limitations and fallback explanation | Presenter can explain them in simple terms | Not run |

---

## 14. Browser and Layout Checks

These checks apply to implemented screens only.

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| UI-001 | P0 | Navigate every demonstrated flow using keyboard | Controls and focus are usable | Not run |
| UI-002 | P0 | Inspect labels, errors, and status messages | Clear and associated with the correct control/action | Not run |
| UI-003 | P0 | Test common laptop viewport | No blocked action or unreadable overflow | Not run |
| UI-004 | P1 | Test narrow/mobile-like viewport | Core content remains usable | Not run |
| UI-005 | P0 | Submit form with validation errors | Values/errors remain understandable; no duplicate action | Not run |
| UI-006 | P0 | Refresh after successful POST | No unintended duplicate submission | Not run |
| UI-007 | P0 | Use browser Back after logout/status change | Protected or stale content is not re-authorized | Not run |

---

## 15. Final Evidence Summary Template

Complete this table at each accepted checkpoint:

| Field | Value |
|---|---|
| Checkpoint ID | |
| Date/time | |
| Tester | |
| Branch | |
| Commit | |
| Environment | |
| Tests passed | |
| Tests failed | |
| Tests blocked | |
| Tests not implemented | |
| Evidence paths | |
| Known limitations | |
| Approval/result | |
| Next permitted action | |

---

## 16. Presentation Stop Rule

Stop adding features and enter presentation freeze when:

- the P0 vertical slice passes;
- the demonstrated AI evidence is accepted and accurately labelled;
- remaining time is needed for regression, paper alignment, screenshots, and rehearsal;
- a proposed addition would risk authentication, authorization, upload safety, moderation, file protection, Approved-only visibility, or honest AI behavior.

A smaller tested prototype is preferred over a larger unreliable one.
