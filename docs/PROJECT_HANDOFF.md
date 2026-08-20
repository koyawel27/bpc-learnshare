# PROJECT_HANDOFF.md

**Project:** BPC LearnShare — AI-Assisted Collaborative Academic Resource Sharing and Management System
**Version:** Draft v1.0
**Last Updated:** 2026-08-20
**Author:** Nepthalie Jezer B. Macaslang
**Course:** BS Information Systems — Bulacan Polytechnic College
**Purpose:** Reflect the current accepted planning, AI-scope, verified schema, security, privacy, and feasibility-spike state — including the accepted `AI_FEASIBILITY_SPIKE.md` specification and the completed MariaDB 10.4.32 schema-compatibility verification — so a new Claude or GPT conversation can continue into the clean hardware baseline and measured spike execution without reopening settled decisions or re-deriving context from old chat history. This document summarizes accepted decisions, completed verification, and current work; it does not itself introduce new decisions.

---

## 1. Project Snapshot

**BPC LearnShare: An AI-Assisted Collaborative Academic Resource Sharing and Management System**

> **Title wording note:** Internal planning documents use “AI-Assisted.” The adviser originally selected “AI-Integrated,” so the final official title wording still requires adviser confirmation before proposal or defense submission (D003).

BPC LearnShare is a structured, moderated, searchable academic resource-sharing and management platform for Bulacan Polytechnic College. Students and Teachers/Instructors upload academic resources such as notes, reviewers, presentations, modules, study guides, handouts, self-made summaries, and related course materials. Moderators and Admins review, organize, manage, and act on resources and reports.

BPC LearnShare is **not**:

* a full Learning Management System;
* an online class platform;
* a quiz or examination system;
* a grading or gradebook system;
* an attendance system;
* an enrollment system;
* an assignment-submission or checking system;
* a teacher class-record system;
* a school portal;
* a social-media platform;
* a general-purpose file-storage platform;
* an unrestricted AI tutor;
* a chatbot-first system.

AI is assistive and non-authoritative at runtime, and every AI-assisted function remains individually configurable and gracefully degradable. The core platform must continue working when AI is disabled, unavailable, unconfigured, rate-limited, failing, or unreachable.

Separately, under `DECISIONS.md` D041–D042, the completed v1.0 capstone prototype is required to implement and demonstrate a bounded minimum AI capability package, including repository-grounded academic resource inquiry. Runtime optionality describes how AI behaves while the system is operating; it does not mean that the required AI package may be omitted from the completed capstone. See Section 7.

**Confirmed v1.0 stack:**

* Native PHP
* MySQL/MariaDB
* XAMPP on Windows
* HTML
* CSS
* Vanilla JavaScript
* Composer only for narrow, justified helper tasks, such as file-text extraction
* No Laravel or major application framework unless explicitly reconsidered through a later decision

**Deployment reality:** v1.0 is a local/LAN academic MVP prototype for capstone demonstration, not a production-scale campus deployment (D001, D017).

---

## 2. Current Source Files and Status

| File                 | Status                                                                                                                                                                                                                                                                                                                                                                                                                                   |
| -------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `PROJECT_BRIEF.md`   | Accepted and aligned through D042. Reflects the required minimum v1.0 AI package and the Planned/Deferred AI tiers.                                                                                                                                                                                                                                                                                                                      |
| `USER_ROLES.md`      | Accepted and aligned through D042. Preserves exactly four roles and defines general AI-assisted search/inquiry access without expanding role authority.                                                                                                                                                                                                                                                                                  |
| `WORKFLOWS.md`       | Accepted and aligned through D043. D043 resolves the retrieval-persistence direction while preserving live eligibility, lifecycle, fallback, and session-only inquiry-context rules. |
| `DATABASE_DESIGN.md` | Accepted direction through D043. It defines `ai_source_versions`, `ai_processing_states`, `ai_chunks`, and `ai_embeddings`, keeps `ai_outputs` separate, and records the disposable and guarded live/canonical migration results. Application repositories/processors remain pending. |
| `DECISIONS.md`       | Complete through **D043**. D043 accepts targeted MariaDB derived-data persistence and bounded PHP cosine without selecting a provider/model or enabling generated inquiry. |
| `schema.sql`         | Accepted and verified current 22-table SQL baseline on MariaDB 10.4.32. The guarded live migration preserved all original rows, and the canonical fresh-import/migration/rollback verifier passed 60/60 checks. |
| `AI_FEASIBILITY_SPIKE.md` | Complete and accepted. Final reconciliation remains **Partially feasible — alternative or mixed architecture required**, with Moderate confidence and 6/4/2/0 capability results. D043 uses that evidence without rewriting it. |
| `AI_FEATURES.md`     | Accepted D043 AI behavior/architecture baseline. Storage migration is complete; provider/model selection, generated inquiry, repositories/processor, and application integration remain pending. |
| `SECURITY_NOTES.md`  | Accepted through the D043 targeted propagation, including derived-data protection, live revalidation, late-result rejection, secret/log rules, and migration-security testing. |
| `DATA_PRIVACY.md`    | Accepted through the D043 targeted propagation, including local derived-data minimization, lifecycle cleanup, session-only inquiry context, and provider payload boundaries. |
| `PROJECT_HANDOFF.md` | This updated version. It records the verified 22-table D043 storage state while preserving the separate application-integration boundary. |

**Completed:** restore-verified backup, exact D043 live migration, canonical 22-table schema update, and revised 60-check disposable verification. **Not yet implemented:** processing repositories/runner, routed semantic retrieval/related-resource integration, and a passing generated-inquiry candidate. `CHANGELOG.md` also remains outside this pass.

`AI_FEASIBILITY_SPIKE.md` is complete and accepted. Final reconciliation covers all 12 Required capabilities and records **Partially feasible — alternative or mixed architecture required**, with Moderate confidence within tested conditions. D043 now accepts the smallest persistent architecture direction supported by that evidence. It still does not select a provider/model, repair the grounded-answer failure, or authorize generated inquiry/application integration.

---

## 3. Locked Scope Boundaries

**In scope for v1.0:**

* login-required access;
* Student self-registration;
* Admin-provisioned Teacher/Instructor, Moderator, and Admin accounts;
* ordinary uploads by Students and Teachers/Instructors only;
* file validation and protected file storage;
* mandatory moderation before normal visibility;
* metadata search and filtering without AI;
* view and download of Approved resources;
* bookmarks;
* binary Helpful marks;
* reports;
* direct Moderator/Admin action on problematic Approved resources;
* Admin user, taxonomy, and system-settings management;
* the required minimum v1.0 AI capability package defined by D041–D042;
* individually configurable and gracefully degradable AI-assisted functions layered on a stable, independently usable non-AI core;
* the planned duplicate/similarity and AI moderation-hint enhancements after the required package is stable.

**Explicitly out of scope:**

* online classes;
* quizzes;
* examinations;
* graded assessments;
* gradebooks;
* teacher class records;
* attendance;
* enrollment;
* assignment submission or checking;
* video meetings;
* full classroom management;
* social-media feeds;
* direct messaging;
* follow/follower systems;
* public file sharing;
* general-purpose file storage;
* unrestricted general-purpose AI tutoring;
* chatbot-first product direction.

**Production hardening is deferred** under D017. HTTPS/public-hosting hardening, backups, monitoring, stronger malware scanning, production performance testing, institutional onboarding, and formal production privacy procedures are future work, not v1.0 defense-build requirements.

---

## 4. Locked Roles and Account Rules

Exactly four v1.0 roles exist:

* **Student**
* **Teacher/Instructor**
* **Moderator**
* **Admin**

Rules:

* Only Students self-register (D006).
* Teacher/Instructor, Moderator, and Admin accounts are provisioned by an existing Admin (D007).
* The first Admin account is created through setup, seed, or manual bootstrap only — never through a public page or permanently reachable setup endpoint (D019, `SECURITY_NOTES.md` §3.9 and §8.5).
* Only Student and Teacher/Instructor accounts may initiate ordinary resource uploads (D008).
* Moderator and Admin accounts do not upload as ordinary contributors (D010). This is enforced in application logic because a foreign key cannot prove the current role of an uploader (`DATABASE_DESIGN.md` §7.2 and §18.3; `SECURITY_NOTES.md` §4.8).
* Teacher/Instructor uploads pass through the same moderation queue as Student uploads. There is no trusted-uploader or automatic-approval bypass in v1.0 (D009).
* Role and account status must be checked live and server-side on every protected request. They must not be trusted only from cached session values (`SECURITY_NOTES.md` §3.5 and §4.3).
* Disabled accounts cannot log in.
* Disabling an account does not automatically change the status of resources previously uploaded by that account.
* Password recovery is Admin-assisted only. There is no self-service recovery, reset-token table, or email-based reset flow in v1.0 (`DATABASE_DESIGN.md` §4.5; `SECURITY_NOTES.md` §3.10).

---

## 5. Locked Resource Status Model

Exactly nine resource statuses exist:

1. **Pending**
2. **Needs Correction**
3. **Approved**
4. **Rejected**
5. **Withdrawn**
6. **Hidden**
7. **Restricted**
8. **Removed**
9. **Replaced**

Rules:

* Only **Approved** resources appear in normal browse and metadata-search results.
* Pending, Needs Correction, Rejected, Withdrawn, Hidden, Restricted, and Replaced resources remain limited to the uploader, Moderator, and Admin according to the accepted visibility model.
* **Removed** is stricter. Removed resources are not visible to normal users or the original uploader.
* Under D040, a Removed resource keeps a minimized accountability/reference row while:

  * `title` becomes `[Removed resource]`;
  * `description` becomes `[Removed resource]`;
  * `topic` becomes `[Removed]`;
  * `original_filename` becomes `[removed]`;
  * associated `resource_tags` rows are deleted;
  * file content and AI output follow their accepted deletion/invalidation rules.
* Required account-linked, taxonomy, technical, and historical accountability data may remain for Admin-only reference. The retained record is minimized but is not anonymized (D022, D040).
* Uploaders may edit or withdraw their own resources only while Pending, Needs Correction, or Rejected.
* Uploaders cannot directly edit an Approved resource (D011).
* Corrections to an Approved resource use a **linked replacement record**: a new Pending resource linked through `replaces_resource_id`, not an in-place edit (D012).
* Only one open replacement in Pending or Needs Correction may exist for the same original resource at one time (D026), enforced by `open_replacement_tracking`.
* **Hidden** is a temporary investigative hold.
* **Restricted** is a longer-term limited-access outcome after review.
* Hidden and Restricted are separate states with separate meanings (D021).
* Restricted may return to Approved through an authorized, logged review decision.
* Removed is Admin-only and terminal (D022).
* Replaced is terminal for the original resource's normal public visibility (D012).
* A stored file may be served only when:

  1. current authentication, account status, role, ownership, permission, and resource-status rules allow access; and
  2. `file_availability = 'available'`.
* The two file-serving requirements are separate gates (D034; `SECURITY_NOTES.md` §4.5 and §5.2–§5.3).

---

## 6. Locked Report, Feedback, and Taxonomy Model

Report statuses:

* **Open**
* **Dismissed**
* **Actioned**
* **Escalated**

Report reasons under D029:

* Outdated resource
* Incorrect or inaccurate content
* Inappropriate content
* Duplicate or near-duplicate resource
* Suspected leaked exam, quiz, or answer key
* Copyright or unauthorized material concern
* Other

Rules:

* Only Student and Teacher/Instructor users use the public report workflow.
* Moderator and Admin users act through moderation and report-management tools instead of filing public reports.
* A user cannot report their own resource.
* One unresolved Open or Escalated report per user/resource pair is allowed at a time (D032), enforced by `open_report_tracking`.
* Helpful feedback is a binary toggle only. There is no star-rating system (D029).
* Bookmarks, Helpful marks, reports, views, and downloads do not automatically transfer to a replacement resource (D027).
* Reports remain attached to the original resource even when the resource later becomes Replaced (D027).
* Moderator/Admin may close the report as resolved by replacement, dismiss it, or escalate it according to the accepted workflow.
* Admin taxonomy management covers:

  * courses/programs;
  * subjects;
  * year levels;
  * resource types;
  * controlled tags.
* Taxonomy values use add, edit, deactivate, and reactivate behavior rather than hard deletion (D038).
* Deactivated values are no longer available for new selection but remain valid on existing historical resources.
* AI must not automatically create, modify, deactivate, or reactivate taxonomy values.

---

## 7. Locked AI Direction

AI is assistive and non-authoritative at runtime (D013), and each AI-assisted function remains configurable and gracefully degradable without breaking the independently usable non-AI core (D004).

Separately, under **D041**, the completed v1.0 capstone prototype is **required to implement and demonstrate** the minimum AI capability package defined by **D042**.

“Optional,” “configurable,” or “degradable” describes how AI behaves while the system is running. It does not mean that the required AI package may be omitted from the finished capstone.

D041 supersedes D016. Repository-grounded academic resource inquiry is now a defining v1.0 capability, not an optional Phase 5 stretch feature.

### 7.1 Required minimum v1.0 AI package

The required package includes:

* **Readable-text extraction** for supported PDF, DOCX, PPTX, and TXT resources where extraction succeeds.
* Image-only and scanned resources remain valid repository resources but are not required to support content-based AI functions in v1.0.
* **AI processing and lifecycle foundation** supporting readiness, failure, stale-source detection, and exclusion of outdated derived data.
* Exact stored processing states are not yet locked.
* Processing readiness and source freshness are separate from live resource eligibility, status, permission, and file-availability checks.
* **AI-generated resource summaries.**
* **AI-suggested controlled tags and metadata**, subject to authorized human review.
* AI suggestions must not automatically create taxonomy values or independently become institutional correctness determinations.
* **Semantic content-based search**, supplementing rather than replacing metadata search and filtering.
* **Repository-grounded academic resource inquiry.**
* The system retrieves evidence from currently eligible Approved resources before generating an inquiry response.
* Substantive academic claims must be grounded in retrieved repository evidence.
* The selected model may organize, simplify, summarize, or explain retrieved evidence but must not silently substitute unsupported general model knowledge when repository evidence is missing.
* **Source-resource attribution** for every substantive inquiry response.
* Page, slide, section, heading, or equivalent locators may be included only when the extraction approach preserves them reliably.
* The system must never fabricate a source or locator.
* **Insufficient-evidence behavior:** when eligible repository evidence is insufficient, the system states that limitation instead of inventing an unsupported answer.
* **Session-scoped conversational follow-up** during an active inquiry session.
* Permanent cross-session AI memory and permanent chat history are not required or authorized for v1.0 unless separately approved later.
* **Basic related-resource suggestions** using content and metadata similarity.
* **Graceful non-AI fallback:** failure of one AI-assisted function affects only that function and must not block or undo any core non-AI workflow.

### 7.2 Planned v1.0 AI enhancements

The following are intended v1.0 enhancements after the complete required package is stable:

* **Duplicate/similar-resource indicators**

  * non-authoritative;
  * support human review;
  * never automatically Reject, Hide, Restrict, Remove, merge, or definitively label a resource as duplicated.

* **AI moderation hints**

  * staff-oriented assistive information;
  * never make or execute a moderation decision;
  * remain distinguishable from human moderation findings.

These planned enhancements are **not equal-weight minimum defense blockers**. Removing either from the intended v1.0 target later still requires an explicit scope decision rather than silent omission.

### 7.3 Deferred beyond v1.0

The following remain outside v1.0:

* OCR for image-only or scanned resources
* AI vision processing
* Persistent cross-session AI memory
* Permanent chat-history storage unless separately approved
* Open-web retrieval
* Automatic web browsing
* Unrestricted general-purpose AI tutoring
* Behavioral recommendation profiles
* Personalized learner profiles
* AI-generated quizzes
* AI-generated graded assessments
* Grading
* Answer checking
* Autonomous moderation
* Automatic resource-status actions
* Training or fine-tuning a new model from scratch

### 7.4 AI authority boundaries

AI must never:

* approve a resource;
* reject a resource;
* publish a resource;
* validate a resource as academically correct;
* Hide a resource;
* Restrict a resource;
* Remove a resource;
* delete a resource or stored file;
* execute a final moderation decision;
* change a resource status;
* create, modify, deactivate, or reactivate taxonomy values automatically;
* grant broader resource or file access;
* replace Teacher/Instructor, Moderator, or Admin judgment;
* guarantee academic correctness;
* answer exams, quizzes, graded assignments, answer keys, or other prohibited academic requests;
* operate as an unrestricted general-purpose chatbot or AI tutor;
* invent unsupported answers, citations, or source locators;
* train or fine-tune a new model from scratch as part of v1.0.

### 7.5 Access, eligibility, and lifecycle rules

* Core platform Phases 1–2 must work with **zero AI configuration** (D004).
* AI unavailability must not change a resource status, undo a successful non-AI action, or block login, upload, moderation, metadata search/filtering, view/download, bookmarks, Helpful marks, reports, notifications, or Admin/Moderator management.
* **Pending-resource AI assistance** is separate from general repository inquiry.
* Before a Pending file enters AI processing, all three accepted gates must pass:

  1. successful basic upload validation;
  2. clear uploader notice;
  3. uploader acknowledgment.
* Declining or not providing acknowledgment must not block the ordinary non-AI upload workflow. AI assistance is skipped while the Pending upload continues normally.
* Pending resources never enter general repository inquiry.
* General semantic search, related-resource suggestions, and repository-grounded inquiry are available to authenticated Active users in all four roles but use only currently eligible Approved resources.
* Moderator/Admin authority to open a non-public resource for moderation or administration does not create an elevated general-inquiry path.
* Approved resources may enter Approved-resource AI processing only while current status, access, file availability, readiness, freshness, and lifecycle rules allow it.
* Needs Correction resources are excluded from general inquiry. Exact corrected-file reprocessing follows the accepted workflow and later implementation design.
* Rejected, Withdrawn, Restricted, Removed, Replaced, private, unauthorized, and otherwise ineligible resources must not enter new general retrieval or new AI processing outside any explicitly accepted restricted lifecycle behavior.
* Hidden resources must not enter new public-facing semantic retrieval, related-resource suggestions, or inquiry while Hidden.
* Previously generated AI output remains governed by the accepted status-specific visibility, invalidation, deletion, and restricted-retention rules.
* Every retrieval candidate must be revalidated against the current local source-of-truth database:

  * before its content is sent to a language model or generator;
  * before its resource link is returned to the requesting user.
* Revalidation includes:

  * current account status;
  * current role and permission;
  * current resource status;
  * current requester access;
  * `file_availability`;
  * processing readiness;
  * source freshness;
  * applicable lifecycle and AI-eligibility rules.
* AI outputs belong to one source resource and are not inherited by replacement resources (D018, D035).
* A replacement resource undergoes its own extraction, processing, and indexing if it becomes eligible.
* One current AI-output row exists per `(resource_id, output_type)` under the accepted baseline. `ai_outputs` is not an AI-output history table (D035).
* Invalidated AI output must be excluded from public-facing reads regardless of the underlying resource's current status (`SECURITY_NOTES.md` §11.8).
* Retrieval-derived data — such as extracted text, chunks, source-location information, embeddings, index entries, provider file objects, or equivalent structures under a later architecture — follows the source-resource lifecycle.
* Retrieval-derived data from a changed source file becomes stale and must not support search, recommendations, or inquiry until the current file is processed successfully.
* Inquiry follow-up remains active-session-scoped and does not grant permanent memory or broader access.
* The application does not assume permanent storage of inquiry questions, responses, retrieved evidence, citations, or session context.
* AI API keys remain server-side configuration only and must never appear in client code, version control, database content, or logs (`SECURITY_NOTES.md` §11.3).

Full detail is maintained in:

* `PROJECT_BRIEF.md` §8;
* `USER_ROLES.md` §11;
* `WORKFLOWS.md` §§10, 10A, 18A–18E, and 19.

See Section 7A for architecture status and Section 7B for schema status.

---

## 7A. AI Architecture Status — D043 Direction Accepted, Dependencies Still Replaceable

D043 approves the bounded architecture direction, not a permanent provider/model or generated-inquiry implementation.

The completed feasibility reconciliation remains **Partially feasible — alternative or mixed architecture required**, with Moderate confidence within tested conditions. D043 accepts local source-bound persistence and bounded PHP retrieval for the components that passed while preserving the failed grounded-generation result.

`DECISIONS.md` D043 accepts these architecture components:

* Native PHP remains the application/backend stack.
* MariaDB 10.4.32 remains the single system of record.
* Readable-text extraction and source-bound segmentation remain local where practical.
* Local embeddings and bounded PHP cosine are retained for semantic retrieval with metadata guards/fallback.
* Four targeted derived-data tables are now the verified persistence baseline.
* Optional summaries/suggestions may use a separately reviewed replaceable adapter.
* Generated inquiry/follow-up remains unavailable until another candidate passes every criterion.
* Providers and models remain replaceable and nonbinding.
* The application must retain a clear non-AI fallback when an AI dependency is unavailable.

This document does not select:

* Groq;
* Ollama;
* Hugging Face;
* Supabase;
* pgvector;
* MariaDB 11.7+ vector features;
* any particular embedding model;
* any particular generation model;
* any particular hosted vector service.

Those names may appear in planning discussions as candidates or comparison options only.

The exact executable migration, rollback, existing-row handling, backup, guarded live execution, and canonical-schema verification are complete. Live application repositories/processors remain a separate approval gate. No database upgrade, second database, hosted vector service, dedicated vector layer, or provider-specific structure is approved.

---

## 7B. AI/Retrieval Schema Status

The currently implemented and verified schema baseline is **22 tables**. The original **18-table** design remains the guarded rollback baseline for the D043 migration package.

D039 and D040 modified existing structures or behavior:

* D039 extended existing `audit_log` enum values.
* D040 defined application-level removal-time minimization and cleanup behavior using existing structures.

Neither decision added a table.

D043 added these four provider-neutral derived-data tables:

* `ai_source_versions`;
* `ai_processing_states`;
* `ai_chunks`;
* `ai_embeddings`.

`ai_outputs` remains an AI-output store. It must not be silently overloaded as:

* extracted-text storage;
* chunk storage;
* embedding storage;
* a semantic index;
* a retrieval-result history;
* a citation store;
* a conversation-history store;
* a permanent inquiry-response store.

The completed schema implementation followed this sequence:

1. Draft exact MariaDB 10.4.32 migration and rollback SQL.
2. Inspect/backfill any existing `ai_outputs` without fabricating source identity.
3. Verify migration, 22-table count, constraints, and rollback on a disposable database.
4. Obtain separate approval and restore-verify an ignored local backup.
5. Apply the live migration under maintenance, verify the exact 22-table set and preserved rows, update `schema.sql`, and rerun canonical/forward/rollback verification.

D043 is the accepted architecture/conceptual schema decision.

D033 remains the historical 18-table baseline decision; D043 now establishes the current installed 22-table persistence baseline.

Do not treat the completed storage migration as AI feature integration. The four new tables are initially empty, and no processor, provider/model, semantic route, or generated inquiry was enabled by the schema change.

---

## 8. Locked Security Baseline

`SECURITY_NOTES.md`, Sections 1–15, is the accepted current implementation-facing security baseline.

It does not override:

* `PROJECT_BRIEF.md`;
* `DECISIONS.md`;
* `USER_ROLES.md`;
* `WORKFLOWS.md`;
* `DATABASE_DESIGN.md`.

It translates accepted project rules into security requirements.

Accepted controls include:

* Password handling through `password_hash()` and `password_verify()`.
* Working minimum password length of eight characters.
* No forced composition rules in v1.0.
* Session ID regeneration immediately after successful login.
* Thirty-minute inactivity timeout.
* Native PHP sessions; no database-backed session table is required.
* Server-side RBAC and live revalidation of role and account status on every protected request.
* Live resource-status checks.
* Live `file_availability` checks before serving files.
* No trust in cached session role/status values as the sole authorization source.
* One session-scoped CSRF token for state-changing POST requests.
* File-extension allowlist.
* MIME/content validation.
* File-size validation.
* Empty/corrupt-file rejection.
* Randomized non-guessable stored filenames.
* Protected file storage outside the public web root.
* Controlled application file-serving rather than direct static file URLs.
* Working v1.0 upload-size limit of **20 MB**, still requiring practical validation.
* Prepared statements for database queries.
* Output escaping for XSS prevention.
* Two audit ledgers:

  * `resource_action_history` for resource-specific actions;
  * `audit_log` for Admin/system-level actions.
* Application-level append-only handling for audit data.
* Safe-summary-only audit content.
* No passwords, password hashes, API keys, session IDs, CSRF tokens, full files, full extracted content, or full AI output in audit logs.
* Application validation for polymorphic `notifications.target_id` and `audit_log.target_id` references because no direct foreign key can enforce them (D036).
* Mandatory PHP-side validation regardless of local CHECK-constraint enforcement (D037).
* Risky failed upload attempts logged through a protected server-side application log rather than `audit_log`.
* Full security risk register in `SECURITY_NOTES.md` §13.
* Security testing seeds in `SECURITY_NOTES.md` §14.

`SECURITY_NOTES.md` now carries the targeted D043 AI persistence, retrieval, external-payload, lifecycle, and fallback controls. Its earlier security principles remain controlling.

The D043 propagation covers, where necessary:

* live retrieval-candidate revalidation immediately before external generation;
* source-link authorization checks;
* stale retrieval-data exclusion;
* provider outage and fallback handling;
* minimum necessary inquiry payloads;
* retrieval/index cleanup and invalidation;
* session-scoped inquiry handling;
* any older wording that still treats inquiry as optional stretch scope.

The need for targeted propagation does not invalidate the accepted existing security baseline.

**Production hardening remains deferred:**

* no mandatory HTTPS for localhost/LAN-only demonstration;
* no firewall, WAF, SIEM, IDS, or centralized monitoring requirement;
* no encryption-at-rest infrastructure requirement;
* no enterprise incident-response program;
* no mandatory malware-scanning infrastructure;
* no production-scale campus hardening requirement.

These boundaries remain consistent with D017.

---

## 9. Current Decision Log Status Through D043

`DECISIONS.md` contains accepted decisions **D001–D043**.

Do not reopen an accepted decision unless the current source documents directly contradict each other.

Most recent decisions:

* **D033** — Accepted SQL schema baseline: 18 tables, with no expansion without explicit justification and a new decision where required.
* **D034** — `file_availability` (`available`, `deleted`, `invalidated`) is separate from resource status; file serving uses dual status/permission and availability gates.
* **D035** — AI outputs are current-value rows per `(resource_id, output_type)`, not history rows.
* **D036** — `notifications` and `audit_log` polymorphic targets are application-validated; no direct foreign key can enforce them.
* **D037** — CHECK constraints are defense-in-depth only and never the sole validation layer; PHP must independently enforce every rule.
* **D038** — Admin taxonomy values use add, edit, deactivate, and reactivate behavior rather than hard deletion.
* **D039** — `audit_log.action_type` and `audit_log.target_type` were extended for Admin-assisted password reset and taxonomy-management logging.
* **D040** — Defines D022's Removed-resource “minimal record” requirement through exact removal-time sanitization of title, description, topic, and original filename; deletion of associated `resource_tags`; preservation of necessary accountability relationships; and distinction from Withdrawn-resource retention.
* **D041** — Required v1.0 AI deliverable with a runtime-independent non-AI core. The minimum AI package defined by D042 is required in the completed capstone. Repository-grounded inquiry becomes a defining v1.0 capability rather than an optional stretch feature. D041 supersedes D016 and clarifies, but does not remove, D004, D013, D014, D015, or D018.
* **D042** — Defines the Required, Planned, and Deferred v1.0 AI capability tiers; establishes the feasibility-gated candidate hybrid direction; keeps providers and models replaceable; and defers schema expansion until after the feasibility spike and an explicit later architecture/schema decision.
* **D043** — Accepts targeted MariaDB persistence through four named AI-derived-data tables, bounded PHP cosine with metadata fallback/live revalidation, source-bound current `ai_outputs`, no permanent inquiry/chat history, no provider/model selection, and generated inquiry unavailable until a later candidate passes. The separately reviewed migration/rollback package has now been applied and verified, establishing the current 22-table SQL baseline.

---

## 10. Current Verified 22-Table Baseline and Historical 18-Table Rollback Baseline

`schema.sql` is the accepted current SQL baseline.

It includes:

1. `accounts`
2. `courses`
3. `subjects`
4. `year_levels`
5. `resource_types`
6. `tags`
7. `resources`
8. `resource_tags`
9. `reports`
10. `resource_action_history`
11. `open_replacement_tracking`
12. `open_report_tracking`
13. `bookmarks`
14. `helpful_marks`
15. `ai_outputs`
16. `notifications`
17. `system_settings`
18. `audit_log`
19. `ai_source_versions`
20. `ai_processing_states`
21. `ai_chunks`
22. `ai_embeddings`

The baseline includes:

* four accepted account roles;
* Active and Disabled account states;
* nine accepted resource statuses;
* `file_availability` values:

  * `available`;
  * `deleted`;
  * `invalidated`;
* one open replacement per original resource through `open_replacement_tracking`;
* one unresolved report per reporter/resource pair through `open_report_tracking`;
* one current AI-output row per `(resource_id, output_type)`, bound to its source version and generator identity;
* provider-neutral source-version, processing-state, chunk, and embedding persistence through the four D043 tables;
* polymorphic `target_type`/`target_id` references for notifications and audit logging, enforced through application validation rather than direct foreign keys.

D039 did not add a table. It extended enum values on the existing `audit_log` table.

D040 did not add a table or column. It uses existing `resources` columns, removes existing `resource_tags` junction rows during removal, and defines application-level lifecycle behavior.

D041–D042 did not themselves change the historical 18-table count. D043 and its separately approved migration package later established the verified 22-table baseline.

The current baseline contains dedicated structures for:

* source-version identity and extracted-text hashes;
* processing readiness and failure state;
* chunks with preserved source locations;
* provider-neutral embedding vectors and candidate identity.

It does not add permanent query vectors, inquiry answers, citation history, chat transcripts, cross-session memory, a hosted vector service, or a selected provider/model. Those boundaries remain governed by D043 and Section 7B.

The historical 18-table baseline and the current 22-table baseline have both been executed against MariaDB 10.4.32 in the actual XAMPP environment. The D043 package passed backup/restore verification, live forward migration, rollback, fresh canonical import, legacy-row preservation, and table-health checks. The original `chk_resources_no_self_replace` definition remains omitted because MariaDB 10.4.32 rejects a `CHECK` that compares against the `AUTO_INCREMENT` `resources.id` column. PHP application logic remains responsible for rejecting both direct self-replacement and longer replacement cycles under D037.

---

## 11. D039 Audit-Log Alignment Patch

**Problem resolved:** `WORKFLOWS.md` and `DATABASE_DESIGN.md` required Admin-assisted password reset and Admin taxonomy-management actions to be logged in `audit_log`, but the originally accepted schema's `action_type` and `target_type` enum values did not support those actions.

**Patch applied:**

* `audit_log.action_type` gained:

  * `password_reset`;
  * `taxonomy_created`;
  * `taxonomy_updated`;
  * `taxonomy_deactivated`;
  * `taxonomy_reactivated`.

* `audit_log.target_type` gained:

  * `course`;
  * `subject`;
  * `year_level`;
  * `resource_type`;
  * `tag`.

* Admin-assisted password reset uses:

  * `action_type = 'password_reset'`;
  * `target_type = 'account'`.

* Taxonomy actions use:

  * the applicable taxonomy action type;
  * the specific taxonomy target type.

A generic `taxonomy` target was deliberately not used because D036 requires `target_type` plus `target_id` to resolve to one specific target category.

The `chk_audit_log_target_action_match` CHECK constraint was extended to cover the new valid pairings.

No table was added.

The accepted table count remains 18.

No notification structure was changed.

**Propagation status:** D039 is recorded in `DECISIONS.md`, applied to `schema.sql`, and propagated into the relevant parts of `SECURITY_NOTES.md`.

**Standing consistency rule:** Any future decision that changes a shared enum, closed set, permission, lifecycle rule, phase description, scope tier, or cross-referenced term must be followed by a whole-document and cross-document stale-reference search. Updating only the section where a decision originated is not sufficient.

---

## 12. Known Implementation and Verification Items

The following verification checkpoint is complete:

* **MariaDB/XAMPP schema compatibility is verified.**

  * Actual server and client version: MariaDB 10.4.32.
  * The historical canonical schema created exactly 18 tables; the current D043 canonical schema creates exactly 22 tables in a fresh verification database.
  * The remaining 13 `CHECK` constraints are recognized and enforced.
  * An invalid `ai_enabled = 'maybe'` update was rejected with a constraint error, and the prior valid value remained unchanged.
  * MariaDB 10.4.32 rejected `chk_resources_no_self_replace` because it compared `replaces_resource_id` with the `AUTO_INCREMENT` `resources.id` column.
  * That incompatible `CHECK` was removed without changing the 18-table design.
  * PHP application logic must reject direct self-replacement and longer replacement cycles.
  * D037 remains controlling: database checks are defense-in-depth, and PHP validation is mandatory regardless of database enforcement.

The following remain open and must not be treated as already resolved:

* **The 20 MB file-size limit remains a working, unvalidated default.**

  * Test it against realistic scanned PDFs, image-heavy presentations, and other representative files.
  * Do not treat its presence in an accepted document as proof that it is appropriate.

* **The operational distinction between `file_availability = 'deleted'` and `file_availability = 'invalidated'` remains implementation-level work.**

  * The dual-gate file-serving rule is settled.
  * Exact code behavior for each unavailable state remains for later implementation planning.

* **The exact public/private document-root folder structure remains open.**

  * Resolve in `BUILD_PLAN.md`.

* **The exact first-Admin bootstrap method remains open.**

  * Seed, controlled setup script, or manual procedure must be selected later.
  * Any setup script must not remain as a permanently reachable public endpoint.

* **The exact MIME/content validation mechanism remains open.**

  * A PHP mechanism such as `finfo` may be considered, but no implementation package is locked by this handoff.

* **Exact transaction and locking syntax remains open.**

  * PDO versus mysqli is not selected here.
  * Atomic operation groups defined by the accepted workflows/security baseline must be implemented safely later.

* **D040 implementation sequencing remains for `BUILD_PLAN.md`.**

  * The decision itself is settled.
  * The build plan must define transaction ordering for:

    * resource status;
    * file availability;
    * placeholder writes;
    * `resource_tags` cleanup;
    * AI-output lifecycle updates;
    * action-history writes;
    * safe physical file cleanup.
  * Database writes should be atomic where required.
  * Filesystem deletion cannot be assumed to roll back with a database transaction.

* **AI/retrieval architecture remains unresolved after the completed final evidence reconciliation.**

  * The Required-package evidence has been reconciled as **Partially feasible — alternative or mixed architecture required**, with 6 capabilities meeting criteria, 4 meeting with targeted changes, and 2 not meeting under the tested candidates.
  * No provider, model, embedding implementation, vector-storage method, retrieval infrastructure, or schema expansion may be treated as decided until the later explicit architecture/schema decision is reviewed and accepted.
  * Measured checkpoints now support readable extraction, corrected 102-chunk segmentation, complete local embedding, native PHP cosine correctness, and promising bounded standalone retrieval.
  * Synthetic local-generation preflight and bounded grounded comparison are complete for the tested Qwen and Llama candidates; neither candidate met the accepted grounded-answer usefulness criteria or is selected. The guarded Groq/GPT-OSS comparison met latency and usefulness but failed strict claim-support and source-attribution criteria; that external candidate is also unselected.
  * The 21-case deterministic grounded-response control layer, 200-case model-independent session/lifecycle control checkpoint, bounded related-resource evaluations, ten-case isolated source-attribution presentation checkpoint, ten-case two-model natural-language follow-up comparison, 19-case Gate 5B lifecycle/fallback validation, and 18-case Gate 5C live relation-metadata/link validation are complete. The follow-up runs preserved their failed quality and latency findings; the earlier guarded regression remains bounded to frozen relation groups; and Gate 5C remains a four-resource live metadata-fallback proof rather than a final relation-rule selection. Production-session follow-up, processing-readiness/retrieval integration, persistent derived-data cleanup, and complete application fallback remain incomplete.

* **Current hardware supports the measured local embedding and bounded retrieval checkpoints, but broader AI suitability remains unresolved.**

  * See Section 12A.
  * Ollama 0.32.1 with `all-minilm:latest` completed 102/102 corpus embeddings on the current baseline laptop.
  * Native PHP cosine retrieval was practical for the tested 102-vector corpus.
  * Under the fixed synthetic preflight, Qwen3 4B failed the tested quality/latency criteria while Llama 3.2 3B passed. Both Llama 3.2 3B and Qwen3.5 4B later completed bounded grounded comparison, but each reached only 50% usefulness against the accepted 80% requirement and neither was selected. One six-case external Groq/GPT-OSS comparison was also completed: it met latency and usefulness but failed strict grounding and attribution. Sustained provider reliability, interruption behavior, concurrency, and final generation suitability remain untested.

---

## 12A. AI Feasibility Spike — Accepted Specification and Final Reconciliation Status

`AI_FEASIBILITY_SPIKE.md` remains complete and accepted as the project's controlling measurement specification.

It is:

* a bounded decision-support and measurement plan;
* the controlling specification for completed and remaining spike checkpoints;
* not `AI_FEATURES.md`;
* not `BUILD_PLAN.md`;
* not `TESTING_CHECKLIST.md`;
* not a final architecture decision;
* not a schema revision.

The final reconciliation includes the completed extraction, corrected segmentation, embedding, PHP cosine, standalone retrieval, manual review, versioned ground truth, local and external generation comparisons, deterministic/live controls, related resources, source presentation, follow-up, and summary/suggestion evidence. It records a bounded partial-feasibility outcome rather than claiming every Required capability passed. D043 selects the provider-neutral MariaDB/PHP persistence and retrieval direction; no provider, model, database upgrade, second database, or executable schema migration has been selected.

### 12A.1 Purpose

The feasibility spike must determine through measurement, rather than assumption, whether the candidate direction in Section 7A is adequate for the required minimum v1.0 AI package.

The initial candidate direction is:

* native PHP;
* MariaDB 10.4 as the current primary database;
* local text extraction where practical;
* local embeddings where practical;
* bounded application-side similarity retrieval;
* external generation where needed for acceptable quality and response time;
* optional experimental local generation fallback.

The spike must determine whether that direction is workable or whether a database upgrade, second database, dedicated vector/retrieval layer, different provider mix, or other architecture change is actually justified.

### 12A.2 Accepted specification coverage

The accepted specification defines:

* representative PDF test files;
* representative DOCX test files;
* representative PPTX test files;
* representative TXT test files;
* readable-text success cases;
* extraction failure cases;
* image-only or scanned-file limitations;
* source-location preservation checks;
* a bounded sample corpus;
* an initial target of approximately 25–50 Approved resources or an equivalent realistic chunk count;
* local embedding benchmarks;
* retrieval-quality checks;
* result-relevance checks;
* current-status and lifecycle exclusion tests;
* Hidden-resource exclusion tests;
* Restricted-resource exclusion tests;
* Removed-resource exclusion tests;
* Replaced-resource non-inheritance tests;
* source-file staleness tests;
* replacement-resource processing tests;
* repository-grounded answer tests;
* source-resource attribution tests;
* reliable-locator tests;
* fabricated-locator prevention;
* insufficient-evidence tests;
* prohibited-request behavior;
* session-scoped follow-up behavior;
* mid-session eligibility-change behavior;
* non-AI fallback behavior;
* external-provider outage behavior;
* latency measurements;
* memory measurements;
* CPU use;
* GPU use where applicable;
* external-provider dependence;
* approximate free-tier or operational feasibility;
* decision criteria for keeping MariaDB 10.4 with bounded application-side retrieval;
* decision criteria for considering an upgrade or dedicated retrieval/vector layer.

### 12A.3 Current hardware note

Current development hardware:

* Intel Core i7-7500U
* Approximately 12 GB RAM
* NVIDIA GeForce 940MX
* 2 GB VRAM

An earlier `llmfit` scan was performed while many heavy applications were open. That scan is useful as an initial indication but is **not** the final clean benchmark.

Current interpretation:

* lightweight local embedding generation appears plausible;
* actual extraction, embedding, retrieval, and concurrent application performance remain unverified;
* local answer generation remains uncertain;
* old mobile GPU support and real Windows runtime behavior must be measured rather than assumed;
* hardware is not yet confirmed sufficient for the final AI architecture.

The planned clean-baseline checkpoint appears in Section 13.

---

## 13. Current Documentation and Verification Order

**D043 continuation note — 2026-08-20:** The historical sequence below records how the project reached the decision. Architecture review, D041–D043 propagation, migration/rollback review, restore-verified backup, guarded live migration, canonical schema update, and post-migration verification are complete. The next active gate is bounded repository/processor integration; storage completion alone does not enable AI features.

Completed checkpoints:

1. **Accepted the current planning baseline through D043.**

2. **Completed and accepted `AI_FEASIBILITY_SPIKE.md`.**

   * Sections 1–26 are complete.
   * Required capability coverage, measurements, mandatory guardrails, pre-run criteria, evidence rules, and architecture/schema handoff are accepted.
   * The spike is partially executed; completed and remaining checkpoints are recorded below.

3. **Executed and verified the historical 18-table `schema.sql` baseline in the actual XAMPP/MariaDB environment.**

   * Actual server/client version: MariaDB 10.4.32.
   * Fresh import result: successful.
   * Table count: 18.
   * Remaining `CHECK`-constraint count: 13.
   * Enforcement was confirmed by rejecting an invalid `system_settings.ai_enabled` value.
   * The incompatible `chk_resources_no_self_replace` constraint was removed because MariaDB 10.4.32 rejects its comparison against the `AUTO_INCREMENT` `resources.id` column.
   * PHP must enforce direct self-replacement and longer replacement-cycle prevention under D037.

Current next checkpoint:

4. **Completed: use the accepted final recommendation to approve and propagate D043.**

   * Gates 0–4 provide the working non-AI authentication, upload, moderation, Approved-only discovery, resource-detail, and controlled-download vertical slice.
   * The unrouted Gate 5A model-independent safety foundation is implemented under `src/ai/` and passed 18/18 deterministic CLI checks using a fake provider plus SELECT-only live database observations.
   * Gate 5B passed 19/19 rollback-based live MariaDB lifecycle/fallback checks covering current status, file availability, stale/missing source state, disabled account/AI, unavailable provider, final revalidation, Hidden-resource exclusion, and non-AI metadata/download continuity.
   * Gate 5C passed 18/18 live related-resource checks using two controlled Security resources and two controlled Usability resources under one subject. A shared-active-tag metadata fallback returned the expected peer for 4/4 targets, excluded cross-topic same-subject results, and failed closed for ineligible resources, files, tags, and requesters. Protected links used `/resources/{id}` and resolved through Approved-only lookup.
   * Gate 5D passed 151/151 offline checks for the reviewed GroqCloud `openai/gpt-oss-120b` candidate. After project-specific key, Zero Data Retention, model allowlist, quota, and explicit-approval controls were confirmed, one harmless connectivity probe passed. A later exact six-payload review and separate approval authorized one grounded comparison. All six requests returned HTTP 200 with zero retries, a 1,618.82 ms median, and 6/6 within 30 seconds.
   * Gate 5D manual review found 16/18 supported substantive claims (88.89%, below the accepted 95% minimum) and 5/6 acceptable-usefulness cases (83.33%, above 80%). Insufficient-evidence, refusal, and partial-support behavior passed; exact source attribution failed. The run is registered as failed on strict quality despite complete execution and evidence integrity.
   * Gate 5E used eight accepted synthetic extraction inputs (two per readable format), five seed-backed Active demo tags, two non-persistent test-only Inactive tags, and the `subject`/`resource_type`/`topic` subset. The first approved v1 request failed safely with HTTP 400 `unsupported_uniqueItems`, zero model outputs, zero retries, and seven unsent requests. After separate review and approval, v2 removed only the unsupported schema keywords and completed 8/8 HTTP 200 requests with a 1,944.858 ms median. User-approved review measured 100% supported summaries, 90% directly usable Active tags, 100% eligible-tag coverage, 85.71% supported metadata, and 100% overall light-edit usability. All Gate 5E criteria passed, but the prior grounded-answer failure remains and the candidate is not selected or integrated.
   * Gates 5A through 5E and the final reconciliation prove bounded component feasibility and control seams only. They add no AI route/UI and select no final model or provider.
   * The accepted final outcome is **Partially feasible — alternative or mixed architecture required**, with Moderate confidence within tested conditions.
   * D043 selects the smallest supportable provider-neutral MariaDB/PHP persistence direction for the passing components, explicitly keeps generated inquiry unavailable, and preserves the non-AI core.

5. **Completed the separately approved D043 storage gate.**

   * Restore-verified the pre-migration backup against the exact 18-table and row-count baseline.
   * Applied the exact 18-to-22 migration while Apache was stopped.
   * Verified the exact 22-table set, five new D043 foreign keys, required checks, preserved original rows, empty new tables, and table health.
   * Updated canonical `database/schema.sql` and passed the revised 60/60 fresh-schema/rollback/forward-migration verification without changing the configured live database during that disposable run.

6. **Completed the smallest provider-neutral persistence layer.**

   * Add bounded repositories for source versions, capability state, chunks, embeddings, and current outputs.
   * Keep processing default-off and unrouted until integration tests pass.
   * `tests/ai/run_d043_ai_persistence.php` passed 49/49 disposable MariaDB checks with zero provider/model requests, zero live database writes, and no route/UI.
   * Verified exact protected-file fingerprinting, monotonic source versions, run-token rejection, complete chunk/vector persistence, downstream readiness invalidation, source-bound outputs, safe failures, AI-disabled fallback, and lifecycle cleanup.

Remaining order:

7. **Add one guarded local processing path.**

   * Reuse the accepted extraction, segmentation, embedding, readiness, run-token, and lifecycle controls.
   * Persist no query vectors, inquiry transcripts, permanent answers, or cross-session memory.

8. **Integrate semantic search and related resources behind live revalidation and metadata fallback.**

   * Keep the non-AI repository fully usable.
   * Preserve both successful and failed feasibility evidence; do not change thresholds to manufacture a pass.

9. **Consider optional summary/suggestion routing only after the storage/retrieval path passes.**

   * Provider/model configuration remains separate and capability-specific.
   * The Gate 5E pass does not authorize the failed grounded-inquiry configuration.

10. **Keep generated inquiry unavailable until a future versioned candidate passes every accepted criterion.**

---

## 14. DATA_PRIVACY.md Completion Note and AI/Retrieval Privacy Carry-Forward

`DATA_PRIVACY.md` is complete and accepted through Sections 1–15 under its pre-D041/D042 baseline.

The detailed framing rules, privacy boundaries, do-not-introduce rules, and privacy-area coverage that originally guided its drafting now live in `DATA_PRIVACY.md` itself and do not need to be reproduced in full here.

Accepted privacy principles that later AI/retrieval propagation must preserve include:

* Alignment with general Philippine Data Privacy Act / RA 10173 principles at a student-project planning level.
* The document is not a legal opinion or formal compliance certification.
* GDPR is not the primary framework.
* The application documentation must not state as a settled legal conclusion that BPC is definitively the personal information controller unless an accepted institutional source establishes that.
* The institution or authorized deploying office remains responsible for formal purpose determination, lawful handling, official privacy notices, retention policy, provider authorization, and operational privacy procedures for real deployment.
* Privacy requirements remain distinct from the technical security controls defined by `SECURITY_NOTES.md`.
* Application behavior remains distinct from institutional responsibility.
* The Pending-file AI notice is a workflow/transparency gate rather than legal consent.
* Uploader acknowledgment does not independently resolve every lawful-basis or institutional privacy question.
* External AI processing is the accepted exception to the otherwise local/LAN application boundary.
* AI payloads must remain purpose-limited to the minimum content and metadata required by the specific AI function.
* AI output is derived data tied to one source resource.
* AI output is not inherited automatically by a replacement resource.
* Invalidated output is excluded from public-facing use.
* Hidden and Restricted output follows restricted visibility and lifecycle rules.
* Rejected, Withdrawn, and Removed output follows accepted invalidation/deletion rules.
* Removed-resource minimization follows D040.
* The retained Removed row is minimized but not anonymized.
* Withdrawn-resource retention is distinct from Removed-resource minimization.
* Notifications should avoid unnecessary resource titles, filenames, full moderation notes, and other excessive content.
* Destination pages resolve current details only after normal permission and status checks.

`DATA_PRIVACY.md` now carries the targeted D043 derived-data, external-payload, session-scope, and lifecycle privacy rules. Its earlier privacy foundation remains valid.

The D043 propagation addresses:

* external transmission of minimum relevant retrieved evidence for repository-grounded inquiry;
* source-resource attribution and citation display;
* source-locator handling;
* provider-side handling and retention review;
* application session-scoped follow-up behavior;
* the absence of permanent application chat history or cross-session memory;
* retrieval-derived data lifecycle;
* stale-source invalidation;
* architecture-specific external-provider cleanup;
* any older wording that still describes repository-grounded inquiry as optional stretch scope.

Any older D016-based optional inquiry wording is superseded by D041 and must be corrected during the targeted propagation pass.

---

## 15. Guidance for the Next Claude Conversation

Use this only when a fresh Claude conversation is needed during the clean-baseline or spike-execution phase:

```text
I am continuing the BPC LearnShare capstone project in a fresh Claude conversation.

Read the latest project files first and treat them as source of truth:

1. PROJECT_HANDOFF.md (this version)
2. PROJECT_BRIEF.md (accepted and aligned through D042)
3. USER_ROLES.md (accepted and aligned through D042)
4. WORKFLOWS.md (accepted and aligned through D042)
5. DATABASE_DESIGN.md (accepted and executable 22-table baseline through D043)
6. DECISIONS.md (through D043)
7. schema.sql (verified current 22-table MariaDB 10.4.32 baseline; D043 source/version/state/chunk/embedding persistence applied)
8. SECURITY_NOTES.md (accepted and propagated through D043)
9. DATA_PRIVACY.md (accepted and propagated through D043)
10. AI_FEASIBILITY_SPIKE.md (accepted complete pre-run specification, Sections 1–26)

Current verified state:

- AI_FEASIBILITY_SPIKE.md is complete and accepted; the spike is partially executed, and the current completed and pending checkpoints are recorded in the controlling status sections below.
- The canonical schema was verified on MariaDB 10.4.32.
- All 22 tables import successfully from the canonical schema.
- The remaining 13 CHECK constraints are recognized and enforced.
- chk_resources_no_self_replace was removed because MariaDB 10.4.32 rejects a CHECK that compares against the AUTO_INCREMENT resources.id column.
- PHP must enforce direct self-replacement and longer replacement-cycle prevention under D037.
- D043 selects and now persists the provider-neutral MariaDB/PHP storage direction. No final provider, model, database upgrade, second database, or generated-inquiry implementation has been selected.

Current task:

Help review the clean hardware/runtime baseline and then support execution of the accepted feasibility spike exactly as specified.

Do not rewrite AI_FEASIBILITY_SPIKE.md.
Do not select an architecture before measurements are complete.
Do not add a table or column.
Do not modify DATABASE_DESIGN.md or schema.sql during the spike.
Do not turn the spike into the full AI implementation.
Do not treat optional local generation as a required pass condition.
Do not reopen D001–D042 unless the source files directly conflict.

For the immediate turn, return only:

1. Source-and-version check.
2. Clean-baseline evidence check for llmfit system and nvidia-smi outputs supplied by the user.
3. Any actual blocker before execution.
4. The smallest next execution step from the accepted AI_FEASIBILITY_SPIKE.md.

Do not invent measurements and do not claim that the spike has passed before evidence exists.
```

---

## 16. Guidance for the Next GPT Review Conversation

Use this to open a separate GPT review conversation during the clean-baseline or spike-execution phase:

```text
You are acting as a critical planning, architecture, security, AI-feasibility, measurement, and documentation reviewer for the BPC LearnShare capstone project.

Read the latest source files first:

- PROJECT_HANDOFF.md
- PROJECT_BRIEF.md
- DECISIONS.md through D043
- USER_ROLES.md
- WORKFLOWS.md
- DATABASE_DESIGN.md
- schema.sql
- SECURITY_NOTES.md
- DATA_PRIVACY.md
- AI_FEASIBILITY_SPIKE.md

Current verified state:

- AI_FEASIBILITY_SPIKE.md Sections 1–26 are complete and accepted.
- The spike is partially executed through extraction, corrected segmentation, complete local embedding, PHP cosine validation, standalone retrieval, manual relevance review, audited versioned ground-truth evaluation, bounded local and external grounded-generation comparisons, deterministic grounded-response/session/lifecycle controls, bounded related-resource evaluations, isolated source-attribution presentation, and the two-model natural-language follow-up comparison. Gate 5A passed 18/18 model-independent checks, Gate 5B passed 19/19 rollback-based lifecycle/fallback checks, Gate 5C passed 18/18 live relation-metadata/link checks, and Gate 5D completed one guarded external six-case comparison on 2026-08-13.
- The bounded retrieval candidate achieved 100% resource top-five, 96% corrected passage top-five, practical isolated latency, 100% metadata fallback, and 100% explicit-filter correctness under the tested corpus.
- The automatic predeclared-misleading criterion remains not met at 25%; manual review provides separate interpretation and does not erase that historical result.
- Two synthetic local-generation preflights and two fixed six-case grounded comparisons are recorded. Llama 3.2 3B and Qwen3.5 4B each reached only 50% usefulness against the accepted 80% requirement; neither is selected as the interactive local solution or a reliable fallback. The later natural-language follow-up comparison completed ten cases per model: Llama interpreted 10/10 references but produced only 8/10 grounded correct answers, including one critical RBAC error, while Qwen produced 8/10 grounded correct answers and 8/10 correct context interpretations with two unnecessary clarification outcomes. Both missed the 15-second median target, so both follow-up runs are registered as failed and neither candidate is selected. The grounded-response control layer passed 21/21 cases, the deterministic session/lifecycle checkpoint passed 200/200 cases, the unguarded related-resource configuration met its two scored positive-case thresholds at 80% expected-resource top-five coverage and 73.33% human-reviewed top-three usefulness, and the metadata-guarded configuration passed one safe no-result case plus a five-case positive regression at 100% expected-resource display and 100% reviewed top-three usefulness. Isolated source-attribution presentation passed 10/10 cases. Gate 5C then passed 18/18 live shared-active-tag fallback checks at 4/4 expected-peer top-five coverage and 4/4 reviewed top-three usefulness on four controlled resources, with protected links and rollback-tested lifecycle exclusion. The D043 provider-neutral persistence suite then passed 49/49 disposable checks. Final evidence reconciliation is complete and records the partial-feasibility outcome. Live adapter/retrieval integration, production-session follow-up, and complete application fallback remain pending as integration-stage work.
- MariaDB 10.4.32 successfully imports the verified 22-table canonical schema; the D043 rollback restores the exact historical 18-table baseline.
- The remaining 13 CHECK constraints are recognized and enforced.
- The incompatible direct self-replacement CHECK was removed; PHP must prevent direct self-replacement and longer cycles under D037.
- D043 selects a provider-neutral MariaDB/PHP persistence and retrieval direction; its exact executable migration/database upgrade and unrouted persistence foundation are complete. The provider, model, second database, adapter/retrieval route integration, and generated-inquiry integration remain unselected or unauthorized.

Your job:

1. Review clean-baseline and spike evidence against the accepted specification.
2. Distinguish measured fact, interpretation, limitation, and recommendation.
3. Flag missing, invalid, incomparable, or post-hoc measurements.
4. Enforce the accepted pre-run criteria and mandatory guardrails.
5. Preserve Required, Planned, and Deferred AI tiers.
6. Prevent architecture lock-in before the complete evidence package is reviewed.
7. Give a clear verdict for each checkpoint: Accept / Accept with fixes / Reject and rerun.
8. Apply only targeted documentation corrections when a real inconsistency exists.
9. Keep the work bounded to a local/LAN BSIS capstone MVP.

Constraints:

- Do not invent measurements.
- Do not treat one successful sample as proof of feasibility.
- Do not add a table or schema column during the spike.
- Do not modify DATABASE_DESIGN.md or schema.sql before the later explicit architecture/schema decision.
- Do not select Groq, Ollama, Hugging Face, Supabase, pgvector, MariaDB 11.7+, or any model/provider merely because it is mentioned as a candidate.
- Do not turn the project into an LMS, unrestricted AI tutor, chatbot-first product, production campus platform, or enterprise AI platform.
- Do not reopen D001–D042 unless current source files directly conflict.
```

---

## 17. Source-of-Truth and Conflict-Handling Rules

* Source files are authoritative.
* Conversational summaries and prior-chat memory are secondary to the latest accepted files.
* Read the actual current files at the beginning of each new planning or implementation phase.
* Do not reopen D001–D042 unless the source documents directly contradict one another.
* Do not silently reinterpret an accepted decision because a newer draft uses different wording.
* Do not introduce a new:

  * role;
  * account status;
  * resource status;
  * report status;
  * permission;
  * table;
  * schema column;
  * module;
  * workflow;
  * AI capability;
  * architecture dependency;
    without explicitly identifying the scope or architecture impact first.
* If a real source conflict is found, stop and flag it before rewriting, planning around it, or coding.
* Mark genuinely unresolved items as `[NEEDS CONFIRMATION]` or route them to the appropriate future document rather than inventing an answer.
* A decision is not fully integrated until every affected:

  * document;
  * enum;
  * table description;
  * workflow;
  * permission matrix;
  * lifecycle rule;
  * security reference;
  * privacy reference;
  * handoff summary;
    has been searched for stale wording.
* Updating only the section where a decision originated is not sufficient.

---

## 18. Known Deferred Production and Future-Hardening Boundaries

Deferred under D017 and the accepted security/privacy baseline:

* public-internet deployment hardening;
* mandatory HTTPS for the localhost/LAN-only demonstration;
* firewall configuration;
* network segmentation;
* reverse proxy deployment;
* web application firewall deployment;
* SIEM;
* centralized monitoring;
* intrusion-detection systems;
* encryption-at-rest infrastructure;
* enterprise incident-response procedures;
* production malware-scanning infrastructure;
* production performance and load testing;
* institution-wide onboarding;
* full operational campus deployment;
* formal production privacy governance;
* institutional retention-policy approval;
* provider legal/procurement review;
* enterprise incident-handling procedures.

Separate accepted v1.0 limitations or deferred hardening features:

* no persistent login-attempt lockout;
* no CAPTCHA requirement;
* no two-factor authentication;
* no self-service password recovery;
* no active-session dashboard;
* no forced-logout administration tool;
* no centralized authorization middleware requirement;
* no major PHP framework.

These items must not be silently added during `BUILD_PLAN.md` or implementation.

A change to these boundaries requires explicit scope review and, where necessary, a new decision entry.

AI capabilities explicitly deferred beyond v1.0 — including OCR, AI vision, persistent cross-session memory, open-web retrieval, unrestricted AI tutoring, personalization, grading/assessment functions, autonomous moderation, and model training/fine-tuning — are governed separately by D042 and Section 7.

---

## 19. Project Risks to Avoid

### 19.1 Losing the verified database baseline

The accepted schema has now been executed and verified against MariaDB 10.4.32.

Do not reintroduce the incompatible direct self-replacement `CHECK`, silently change the verified 22-table baseline, or treat database enforcement as a replacement for PHP validation.

Any future retrieval-related schema change beyond D043 must remain separate and must wait for measured evidence plus an explicit architecture/schema decision.

### 19.2 Decision-propagation gaps

A decision is not fully integrated until all affected documents and references have been checked.

D039, D040, D041, and D042 demonstrate why isolated edits are insufficient.

### 19.3 Scope creep disguised as a reasonable addition

New roles, statuses, tables, modules, workflows, LMS functions, AI functions, or production requirements must not be added because they seem useful.

Identify the scope impact first and record a new decision when required.

### 19.4 BarangayIS content bleed

BarangayIS documents may be used only as references for:

* structure;
* depth;
* writing style;
* review technique.

Do not import Barangay-specific:

* roles;
* data categories;
* certificate workflows;
* retention rules;
* permission assumptions;
* no-external-API assumptions.

### 19.5 Treating working defaults as validated results

The current 20 MB upload limit and thirty-minute session timeout are accepted working defaults, but practical testing is still required.

Planning acceptance does not equal empirical validation.

### 19.6 Allowing AI to become a runtime dependency or authority

Every AI-assisted function must remain:

* non-authoritative;
* configurable;
* independently degradable;
* unable to break the non-AI core.

This runtime independence is separate from D041–D042's completed-capstone requirement.

The minimum AI package must be built and demonstrated, but AI output must never become a prerequisite for ordinary core operation or a substitute for human moderation authority.

### 19.7 Locking AI architecture before the feasibility spike

Do not select a final:

* provider;
* model;
* local runtime;
* database upgrade;
* hosted vector service;
* second database;
* embedding system;
* retrieval layer;
* schema expansion;

before the feasibility spike produces measured results.

The candidate direction in Section 7A is not an approved final architecture.

The feasibility spike must come first.

### 19.8 Overbuilding the feasibility spike

The spike is a bounded decision-support activity, not the production implementation.

Do not turn it into:

* the full application;
* a final AI module;
* a full production RAG platform;
* a cloud migration;
* a campus-scale performance project;
* an enterprise AI benchmark.

Test only what is necessary to choose a feasible v1.0 architecture.

---

## 20. Final Current-State Summary

BPC LearnShare v1.0 currently has:

* `PROJECT_BRIEF.md` — accepted and aligned through D042; D043 changes no project role/scope anchor;
* `USER_ROLES.md` — accepted and aligned through D042; D043 changes no permission or role;
* `WORKFLOWS.md` — accepted and aligned through D043;
* `DATABASE_DESIGN.md` — accepted conceptual direction through D043 with four targeted derived-data tables;
* `DECISIONS.md` — accepted through D043;
* `schema.sql` — accepted and verified current 22-table MariaDB 10.4.32 baseline after the guarded D043 migration;
* `SECURITY_NOTES.md` — complete and accepted through D043 targeted propagation;
* `DATA_PRIVACY.md` — complete and accepted through D043 targeted propagation;
* `AI_FEATURES.md` — drafted as the D043 AI behavior/architecture baseline;
* `AI_FEASIBILITY_SPIKE.md` — complete and accepted pre-run specification, Sections 1–26;
* this updated `PROJECT_HANDOFF.md`.

D041–D043 have been propagated into the affected current planning documents, including:

* `PROJECT_BRIEF.md`;
* `USER_ROLES.md`;
* `WORKFLOWS.md`;
* `AI_FEASIBILITY_SPIKE.md` and its final recommendation;
* `DATABASE_DESIGN.md`;
* `AI_FEATURES.md`;
* `SECURITY_NOTES.md`;
* `DATA_PRIVACY.md`;
* `BUILD_PLAN.md`;
* `TESTING_CHECKLIST.md`;
* this handoff.

No unresolved source conflict blocks the clean-hardware baseline or the accepted spike-execution sequence.

Completed verification:

* actual server/client version recorded as MariaDB 10.4.32;
* canonical schema imported successfully into a fresh verification database;
* all 22 accepted tables created by the current canonical schema;
* legacy and D043 `CHECK` constraints recognized and the five new D043 foreign keys verified;
* actual `CHECK` enforcement confirmed through rejection of an invalid `ai_enabled` value;
* incompatible `chk_resources_no_self_replace` removed from the historical 18-table baseline without weakening the PHP replacement-cycle rule;
* direct self-replacement and longer-cycle prevention explicitly retained as mandatory PHP application rules under D037.
* restore-verified pre-migration backup preserved under the ignored local evidence directory;
* live 18-to-22 migration preserved every original row count and left the four new tables empty;
* revised canonical/fresh/forward/rollback verifier passed 60/60 checks.

Known scheduled implementation remains:

* source-version/state/chunk/embedding repositories and bounded processor;
* routed semantic retrieval/related-resource integration and complete lifecycle/fallback regression.

Any older pre-D041/D042 wording that describes repository-grounded inquiry as optional or stretch scope is superseded by D041 and must be corrected during the scheduled targeted propagation pass.

Important remaining items:

* test the 20 MB upload limit against representative files;
* integrate and validate the audited source-attribution contract against live PHP/database state and protected resource links;
* preserve and broaden the Gate 5C relation-metadata evidence before treating its four-resource fallback result as representative of a real repository;
* verify the registered deterministic control rules against live PHP/database state and complete application fallback behavior;
* evaluate the observed external-provider dependence, quota, cost, privacy, interruption, and fallback limitations before any candidate or architecture decision;
* select the simplest workable AI architecture only after the complete evidence package is reviewed.

The bounded related-resource evaluation, safe no-result controls, source-attribution presentation, follow-up comparison, lifecycle/fallback validation, external grounded comparison, summary/suggestion evaluation, final evidence reconciliation, and D043 live/canonical migration verification are complete. The reconciled outcome remains **Partially feasible — alternative or mixed architecture required**. The 22-table persistence baseline is established, but no generation provider/model, generated inquiry, or application AI UI is selected. The next implementation gate is the smallest provider-neutral repository/processor path with complete lifecycle and fallback verification.

The registered 200-case session/lifecycle checkpoint establishes deterministic control behavior only. Gate 5B separately passed its bounded live PHP/MariaDB status, staleness, file, account, final-revalidation, and fallback control-seam checks. Production-session natural-language follow-up, persistent derived-data cleanup, real-provider interruption behavior, and complete application fallback remain separate integration-stage requirements.

The project remains a local/LAN BS Information Systems academic MVP: a moderated academic resource-sharing and management system that is required to implement and demonstrate a bounded repository-grounded AI capability package while preserving an independently functional non-AI resource-sharing core.

It is not:

* an LMS;
* a production campus platform;
* an unrestricted AI tutor;
* a chatbot-first system;
* an enterprise AI platform.

---

*End of `PROJECT_HANDOFF.md`.*
