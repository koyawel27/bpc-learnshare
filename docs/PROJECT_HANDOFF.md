# PROJECT_HANDOFF.md

**Project:** BPC LearnShare — AI-Assisted Collaborative Academic Resource Sharing and Management System
**Version:** Draft v1.0
**Last Updated:** 2026-07-13
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
| `WORKFLOWS.md`       | Accepted and aligned through D042. Includes Admin AI configuration, Pending-resource AI assistance, Approved-resource extraction/processing, semantic search, related-resource suggestions, repository-grounded inquiry, session-scoped follow-up, planned AI enhancement boundaries, and AI/retrieval-derived-data lifecycle workflows. No provider, model, vector database, final retrieval architecture, table, or column was locked. |
| `DATABASE_DESIGN.md` | Accepted **pre-D041/D042** conceptual baseline. Retrieval architecture, extraction/chunk/embedding storage, and any resulting schema expansion are intentionally not yet propagated. Its current silence on retrieval structures must not be treated as a decision that no additional structure will be needed.                                                                                                                          |
| `DECISIONS.md`       | Complete through **D042**. D041–D042 establish the required completed-capstone AI package, the Required/Planned/Deferred AI tiers, and the feasibility-gated architecture direction.                                                                                                                                                                                                                                                     |
| `schema.sql`         | Accepted and live-verified 18-table SQL baseline corresponding to the current pre-D041/D042 database design. MariaDB 10.4.32 successfully creates all 18 tables and enforces the remaining 13 `CHECK` constraints. The incompatible direct self-replacement `CHECK` was removed because MariaDB rejects a comparison against the `AUTO_INCREMENT` `resources.id`; PHP must enforce direct self-replacement and longer-cycle prevention under D037. No retrieval-related schema expansion has been authorized. |
| `AI_FEASIBILITY_SPIKE.md` | Complete and accepted as the pre-run feasibility-spike specification, Sections 1–26. The specification defines the corpus, measurements, guardrails, pre-run criteria, evidence package, recommendation format, and architecture/schema handoff. The spike itself has not yet been executed, and no provider, model, retrieval layer, database upgrade, second database, or schema expansion has been selected. |
| `SECURITY_NOTES.md`  | Complete and accepted, Sections 1–15, through Draft 1.2 and aligned through D040. It predates D041–D042. Its existing security baseline remains accepted, but targeted AI/retrieval propagation is scheduled after architecture selection. Any older wording that treats the newly required inquiry capability as optional is superseded by D041. |
| `DATA_PRIVACY.md`    | Complete and accepted, Sections 1–15, through the pre-D041/D042 baseline. Its existing privacy principles remain accepted, but targeted AI/retrieval propagation is scheduled after architecture selection. Any older optional/stretch framing for inquiry is superseded by D041. |
| `PROJECT_HANDOFF.md` | This updated version. It records the accepted feasibility-spike specification, the verified MariaDB 10.4.32 schema baseline, the targeted compatibility patch, and the clean-hardware baseline as the next execution checkpoint. |

**Not yet substantively completed:**

* `AI_FEATURES.md`
* `BUILD_PLAN.md`
* `TESTING_CHECKLIST.md`
* `CHANGELOG.md`

`AI_FEASIBILITY_SPIKE.md` is now complete and accepted. The immediate next execution checkpoint is the clean hardware and runtime baseline defined in Sections 12A and 13, followed by measured spike execution.

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

## 7A. AI Architecture Status — Feasibility-Gated, Not Yet Locked

No final AI architecture has been approved.

`DECISIONS.md` D042 establishes a **candidate direction for feasibility testing only**, not a locked design:

* Native PHP remains the application/backend stack.
* MariaDB 10.4 remains the current primary database for the first feasibility spike.
* Text extraction should be performed locally where practical.
* Local embeddings should be tested where practical.
* Application-side similarity retrieval in PHP should be tested for a bounded, representative corpus.
* An external generation API may be used where necessary for acceptable answer quality and response time.
* Local generation may be tested as an optional experimental fallback.
* Local generation must not be assumed feasible before measurement.
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

The feasibility spike in Section 12A must determine whether the bounded MariaDB 10.4 plus application-side retrieval direction is adequate before any database upgrade, second database, hosted vector service, dedicated vector layer, or schema expansion is approved.

---

## 7B. AI/Retrieval Schema Status

The accepted schema baseline remains **18 tables** under D033.

D039 and D040 modified existing structures or behavior:

* D039 extended existing `audit_log` enum values.
* D040 defined application-level removal-time minimization and cleanup behavior using existing structures.

Neither decision added a table.

D041–D042 define required AI scope and a feasibility-gated architecture direction. They do **not** by themselves authorize a new database table or column.

`ai_outputs` remains an AI-output store. It must not be silently overloaded as:

* extracted-text storage;
* chunk storage;
* embedding storage;
* a semantic index;
* a retrieval-result history;
* a citation store;
* a conversation-history store;
* a permanent inquiry-response store.

Any schema expansion required for extraction, chunking, embeddings, retrieval indexes, provider references, or other retrieval support must follow this sequence:

1. Complete the focused feasibility spike.
2. Select the simplest workable architecture based on measured results.
3. Record an explicit architecture/schema decision in `DECISIONS.md`.
4. Apply targeted revisions to `DATABASE_DESIGN.md`.
5. Apply targeted revisions to `schema.sql`.

This handoff does not pre-assign a future decision number.

D033 remains the active accepted schema baseline until the sequence above is completed.

Do not interpret the current 18-table schema's silence on retrieval structures as proof that no additional structure is needed. Do not add a table or column before the feasibility and decision sequence is completed.

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

`SECURITY_NOTES.md` predates D041–D042. Its accepted security principles remain controlling, but targeted AI/retrieval propagation is required after architecture selection.

That later pass should cover, where necessary:

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

## 9. Current Decision Log Status Through D042

`DECISIONS.md` contains accepted decisions **D001–D042**.

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

---

## 10. Accepted and Verified 18-Table Schema Baseline

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
* one current AI-output row per `(resource_id, output_type)`;
* polymorphic `target_type`/`target_id` references for notifications and audit logging, enforced through application validation rather than direct foreign keys.

D039 did not add a table. It extended enum values on the existing `audit_log` table.

D040 did not add a table or column. It uses existing `resources` columns, removes existing `resource_tags` junction rows during removal, and defines application-level lifecycle behavior.

D041–D042 also do not change the accepted 18-table count. They establish required AI scope and a feasibility-gated architecture direction but intentionally defer any retrieval-schema consequence.

The current baseline does not yet contain dedicated structures for:

* extracted text;
* chunks;
* preserved source locations;
* embeddings;
* semantic indexes;
* hosted vector references;
* retrieval indexes;
* inquiry evidence.

Whether any such structure is required remains subject to the feasibility spike, architecture selection, and explicit later decision described in Section 7B.

The baseline has now been executed against a fresh MariaDB 10.4.32 database in the actual XAMPP environment. All 18 tables were created successfully. MariaDB recognized and enforced the remaining 13 `CHECK` constraints; an invalid `system_settings.ai_enabled = 'maybe'` update was rejected while the valid value remained unchanged. The original `chk_resources_no_self_replace` definition was removed because MariaDB 10.4.32 rejects a `CHECK` that compares against the `AUTO_INCREMENT` `resources.id` column. PHP application logic remains responsible for rejecting both direct self-replacement and longer replacement cycles under D037.

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
  * The canonical schema creates exactly 18 tables in a fresh verification database.
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

* **AI/retrieval architecture remains unresolved pending the feasibility spike.**

  * No provider, model, embedding approach, vector-storage method, retrieval infrastructure, or schema expansion may be treated as decided before measured results are reviewed.

* **Hardware suitability for local AI processing remains unconfirmed.**

  * See Section 12A.
  * Local embeddings appear plausible but remain unverified.
  * Local generation remains uncertain.

---

## 12A. AI Feasibility Spike — Accepted Pre-Run Specification

`AI_FEASIBILITY_SPIKE.md` is complete and accepted as the project's pre-run measurement specification.

It is:

* a bounded decision-support and measurement plan;
* the controlling specification for the next clean-baseline and spike-execution phase;
* not `AI_FEATURES.md`;
* not `BUILD_PLAN.md`;
* not `TESTING_CHECKLIST.md`;
* not a final architecture decision;
* not a schema revision.

Specification completion does not mean that the spike has been executed or that a provider, model, retrieval layer, database upgrade, second database, or schema expansion has been selected.

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

Completed checkpoints:

1. **Accepted the current planning baseline through D042.**

2. **Completed and accepted `AI_FEASIBILITY_SPIKE.md`.**

   * Sections 1–26 are complete.
   * Required capability coverage, measurements, mandatory guardrails, pre-run criteria, evidence rules, and architecture/schema handoff are accepted.
   * The spike itself has not yet been executed.

3. **Executed and verified the canonical 18-table `schema.sql` in the actual XAMPP/MariaDB environment.**

   * Actual server/client version: MariaDB 10.4.32.
   * Fresh import result: successful.
   * Table count: 18.
   * Remaining `CHECK`-constraint count: 13.
   * Enforcement was confirmed by rejecting an invalid `system_settings.ai_enabled` value.
   * The incompatible `chk_resources_no_self_replace` constraint was removed because MariaDB 10.4.32 rejects its comparison against the `AUTO_INCREMENT` `resources.id` column.
   * PHP must enforce direct self-replacement and longer replacement-cycle prevention under D037.

Current next checkpoint:

4. **Perform the clean-hardware and runtime baseline.**

   * Restart Windows.
   * Close unnecessary heavy applications.
   * Run:

```text
llmfit system
```

* Run:

```text
nvidia-smi
```

* Record the clean baseline exactly.
* Do not treat the previous high-load scan as the final benchmark.
* Do not begin model/provider selection during this checkpoint.

Remaining order:

5. **Execute the accepted feasibility spike.**

   * Use the accepted representative corpus and evaluation set.
   * Follow the pre-run criteria and evidence-recording rules in `AI_FEASIBILITY_SPIKE.md`.
   * Record actual results rather than relying on model/tool marketing claims.

6. **Evaluate and accept the measured findings.**

   * extraction success and failure;
   * source-location preservation;
   * embedding performance;
   * retrieval relevance and latency;
   * grounded inquiry and attribution;
   * insufficient-evidence behavior;
   * prohibited-request behavior;
   * session-scoped follow-up;
   * stale-source and lifecycle exclusion;
   * CPU/RAM/GPU/VRAM use;
   * external-provider dependence, quota, cost, and fallback;
   * maintainability.

7. **Select the simplest workable architecture based on the measured results.**

   * Do not prefer a more complex architecture merely because it is newer or more scalable.
   * Preserve beginner maintainability and capstone feasibility.

8. **Record the architecture/schema decision explicitly in `DECISIONS.md`.**

   * Use the next available decision number at that time.
   * Do not pre-assign the number in advance.

9. **Apply targeted changes to `DATABASE_DESIGN.md` and `schema.sql`.**

   * Only after the architecture/schema decision is accepted.
   * Do not redesign unrelated tables.

10. **Perform targeted D041–D042 AI/retrieval propagation into `SECURITY_NOTES.md` and `DATA_PRIVACY.md`.**

    * Preserve accepted security/privacy principles.
    * Correct superseded optional/stretch wording.
    * Add only architecture-relevant retrieval controls and privacy handling.

11. **Draft `AI_FEATURES.md`.**

    * Use the accepted architecture, lifecycle rules, role rules, workflow rules, security rules, and privacy rules.

12. **Draft `BUILD_PLAN.md`.**

    * Use the accepted AI feature specification and updated database design.

13. **Draft `TESTING_CHECKLIST.md`.**

    * Include core workflows, AI behaviors, security controls, privacy-sensitive behavior, fallback behavior, and lifecycle exclusions.

14. **Perform a final cross-document consistency review before implementation.**

`BUILD_PLAN.md` must not be drafted before:

* the feasibility spike;
* the resulting architecture decision;
* the targeted database-design/schema update;
* the targeted security/privacy propagation;
* `AI_FEATURES.md`.

This ordering replaces the pre-D041/D042 documentation sequence.

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

`DATA_PRIVACY.md` predates D041–D042. Its accepted privacy foundation remains valid, but targeted propagation is required after architecture selection.

That pass should address:

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
5. DATABASE_DESIGN.md (accepted pre-D041/D042 conceptual baseline; retrieval architecture not yet propagated)
6. DECISIONS.md (through D042)
7. schema.sql (verified 18-table MariaDB 10.4.32 baseline; 13 enforced CHECK constraints; no retrieval/schema expansion)
8. SECURITY_NOTES.md (accepted Sections 1–15 pre-D041/D042 baseline)
9. DATA_PRIVACY.md (accepted Sections 1–15 pre-D041/D042 baseline)
10. AI_FEASIBILITY_SPIKE.md (accepted complete pre-run specification, Sections 1–26)

Current verified state:

- AI_FEASIBILITY_SPIKE.md is complete and accepted, but the spike has not yet been executed.
- The canonical schema was verified on MariaDB 10.4.32.
- All 18 tables import successfully.
- The remaining 13 CHECK constraints are recognized and enforced.
- chk_resources_no_self_replace was removed because MariaDB 10.4.32 rejects a CHECK that compares against the AUTO_INCREMENT resources.id column.
- PHP must enforce direct self-replacement and longer replacement-cycle prevention under D037.
- No final provider, model, retrieval layer, database upgrade, second database, or retrieval-related schema expansion has been selected.

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
- DECISIONS.md through D042
- USER_ROLES.md
- WORKFLOWS.md
- DATABASE_DESIGN.md
- schema.sql
- SECURITY_NOTES.md
- DATA_PRIVACY.md
- AI_FEASIBILITY_SPIKE.md

Current verified state:

- AI_FEASIBILITY_SPIKE.md Sections 1–26 are complete and accepted.
- The spike has not yet been executed.
- MariaDB 10.4.32 successfully imports the verified 18-table schema.
- The remaining 13 CHECK constraints are recognized and enforced.
- The incompatible direct self-replacement CHECK was removed; PHP must prevent direct self-replacement and longer cycles under D037.
- No AI architecture, provider, model, retrieval layer, database upgrade, second database, or retrieval-related schema expansion is selected.

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

Do not reintroduce the incompatible direct self-replacement `CHECK`, silently change the verified 18-table baseline, or treat database enforcement as a replacement for PHP validation.

Future retrieval-related schema work must remain separate and must wait for measured spike findings plus an explicit architecture/schema decision.

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

* `PROJECT_BRIEF.md` — accepted and aligned through D042;
* `USER_ROLES.md` — accepted and aligned through D042;
* `WORKFLOWS.md` — accepted and aligned through D042;
* `DATABASE_DESIGN.md` — accepted pre-D041/D042 conceptual baseline, with retrieval architecture and any resulting schema expansion intentionally not yet propagated;
* `DECISIONS.md` — accepted through D042;
* `schema.sql` — accepted and verified 18-table MariaDB 10.4.32 baseline, including the D039 audit-log patch, D040 removal-lifecycle documentation, and the targeted removal of the incompatible direct self-replacement `CHECK`; the remaining 13 `CHECK` constraints are recognized and enforced;
* `SECURITY_NOTES.md` — complete and accepted through its pre-D041/D042 baseline;
* `DATA_PRIVACY.md` — complete and accepted through its pre-D041/D042 baseline;
* `AI_FEASIBILITY_SPIKE.md` — complete and accepted pre-run specification, Sections 1–26;
* this updated `PROJECT_HANDOFF.md`.

D041–D042 have been propagated into:

* `PROJECT_BRIEF.md`;
* `USER_ROLES.md`;
* `WORKFLOWS.md`;
* `AI_FEASIBILITY_SPIKE.md`;
* this handoff.

No unresolved source conflict blocks the clean-hardware baseline or the accepted spike-execution sequence.

Completed verification:

* actual server/client version recorded as MariaDB 10.4.32;
* canonical schema imported successfully into a fresh verification database;
* all 18 accepted tables created;
* 13 remaining `CHECK` constraints recognized;
* actual `CHECK` enforcement confirmed through rejection of an invalid `ai_enabled` value;
* incompatible `chk_resources_no_self_replace` removed without changing the 18-table design;
* direct self-replacement and longer-cycle prevention explicitly retained as mandatory PHP application rules under D037.

Known scheduled propagation remains:

* `DATABASE_DESIGN.md` and `schema.sql` after feasibility testing and an explicit architecture/schema decision, only where measured results justify changes;
* `SECURITY_NOTES.md` after architecture selection;
* `DATA_PRIVACY.md` after architecture selection.

Any older pre-D041/D042 wording that describes repository-grounded inquiry as optional or stretch scope is superseded by D041 and must be corrected during the scheduled targeted propagation pass.

Important remaining items:

* test the 20 MB upload limit against representative files;
* restart Windows and record the clean `llmfit system` and `nvidia-smi` baseline;
* execute the accepted AI feasibility spike;
* measure extraction quality and failure behavior;
* measure source-location preservation;
* measure local embedding feasibility;
* measure bounded retrieval quality and latency;
* evaluate grounded-answer behavior and source attribution;
* evaluate insufficient-evidence and prohibited-request handling;
* evaluate session-scoped follow-up and lifecycle revalidation;
* evaluate CPU, RAM, GPU, and VRAM use;
* evaluate external-provider dependence, quota, cost, privacy limitations, and fallback behavior;
* select the simplest workable AI architecture only after the complete evidence package is reviewed.

The immediate next checkpoint is the **clean hardware and runtime baseline**:

1. Restart Windows.
2. Close unnecessary heavy applications.
3. Run `llmfit system`.
4. Run `nvidia-smi`.
5. Record the clean baseline.
6. Begin the accepted feasibility spike only after the baseline is captured.

The project remains a local/LAN BS Information Systems academic MVP: a moderated academic resource-sharing and management system that is required to implement and demonstrate a bounded repository-grounded AI capability package while preserving an independently functional non-AI resource-sharing core.

It is not:

* an LMS;
* a production campus platform;
* an unrestricted AI tutor;
* a chatbot-first system;
* an enterprise AI platform.

---

*End of `PROJECT_HANDOFF.md`.*
