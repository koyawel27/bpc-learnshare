# TESTING_CHECKLIST.md

**Project:** BPC LearnShare — AI-Assisted Collaborative Academic Resource Sharing and Management System
**Checkpoint:** Two-week working prototype and presentation
**Status:** Active verification checklist
**Last updated:** 2026-08-01
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
| AI-REL-001 | P1 | Request related resources | Small relevant Approved-only set with live filters | Not run |
| AI-REL-002 | P1 | Make a related resource ineligible | It disappears from new suggestions | Not run |

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

### 11.4 Final AI recommendation

| ID | Priority | Test | Expected result | Status |
|---|---|---|---|---|
| AI-FIN-001 | P0 | Reconcile every accepted criterion with registered evidence | No invented or omitted measurements | Not run |
| AI-FIN-002 | P0 | Document passed, failed, and unresolved capabilities | Honest bounded conclusion | Not run |
| AI-FIN-003 | P0 | Review provider/model/runtime terms and hardware reality | Suitability limitations recorded | Not run |
| AI-FIN-004 | P0 | Prepare smallest justified architecture/schema impact | No premature vector database or schema expansion | Not run |
| AI-FIN-005 | P0 | Confirm core non-AI independence | Final direction preserves graceful fallback | Not run |

---

## 12. Integrated AI Prototype Tests

Run only after the feasibility recommendation approves an integration direction.

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
