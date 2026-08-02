# BUILD_PLAN.md

**Project:** BPC LearnShare — AI-Assisted Collaborative Academic Resource Sharing and Management System
**Planning horizon:** Two-week prototype and presentation checkpoint
**Status:** Active implementation plan
**Last updated:** 2026-08-01
**Scope authority:** This plan sequences accepted requirements. It does not replace `PROJECT_BRIEF.md`, `DECISIONS.md`, `USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`, `SECURITY_NOTES.md`, `DATA_PRIVACY.md`, or `AI_FEASIBILITY_SPIKE.md`.

---

## 1. Purpose

This document turns the accepted BPC LearnShare scope into a small, testable implementation sequence for the next two weeks.

The immediate goal is a **real working prototype**, not a collection of disconnected mock screens. The prototype must demonstrate the central resource-sharing workflow and preserve a maintainable path toward the complete v1.0 capstone.

The two-week presentation checkpoint is not permission to silently remove confirmed v1.0 requirements. A feature that is not implemented by the checkpoint must be reported honestly as **Not implemented**, **Partially implemented**, or **Planned after the presentation checkpoint**.

Figma may supplement the presentation for screens that are not yet implemented, but it is the fallback—not the primary prototype.

---

## 2. Confirmed Baseline

### 2.1 Planning and evidence already available

- Accepted project scope, role model, workflows, database design, security rules, and privacy rules.
- Verified MariaDB 10.4.32-compatible 18-table schema baseline.
- Completed AI feasibility evidence for:
  - readable-text extraction;
  - corrected segmentation;
  - complete local embedding;
  - PHP cosine correctness and bounded performance;
  - standalone semantic retrieval;
  - manual relevance review;
  - versioned ground-truth correction and evaluation.
- Current retrieval evidence supports retaining the tested local embedding and PHP cosine approach as a candidate, but no final AI architecture or schema expansion has been selected.

### 2.2 Current implementation reality

- Gates 0 and 1 now have a working native-PHP foundation, database connectivity, Student registration, login/logout, session protection, CSRF handling, live account rechecks, and a non-public first-Admin bootstrap.
- Gate 2 now has a server-rendered Student/Teacher upload form, controlled prototype taxonomy, guarded file validation, protected randomized storage, and transactional `Pending` resource creation.
- Moderation, Approved-only discovery, controlled file serving, and the remaining core workflows are still pending.
- Two synthetic local-generation preflights are complete: Qwen3 4B failed the tested quality/latency criteria, while Llama 3.2 3B passed and may proceed to bounded grounded evaluation. Grounded inquiry, lifecycle, follow-up, related-resource, fallback, and the final AI recommendation remain pending.

---

## 3. Two Different Completion Targets

### 3.1 Two-week presentation prototype

The presentation prototype should prove one complete, secure vertical slice:

1. A Student can register and log in.
2. An authorized Student or Teacher/Instructor can upload an allowed resource with required metadata.
3. The upload is validated and stored outside the public web root.
4. The resource enters `Pending`.
5. A Moderator can review it and choose an allowed moderation outcome.
6. An `Approved` resource appears in browse and metadata search.
7. An authorized user can view its details and download it through a controlled PHP endpoint.
8. Non-Approved or unavailable resources remain inaccessible through normal listings and direct URLs.
9. The prototype can demonstrate the accepted semantic-retrieval evidence.
10. If the generation checkpoint passes, the prototype can demonstrate a bounded repository-grounded answer with source attribution and insufficient-evidence behavior.
11. The core resource workflow still works when AI is disabled or unavailable.

The prototype must not contain fake successful actions, hardcoded AI answers, public file links that bypass authorization, or buttons that imply an unavailable backend operation.

### 3.2 Complete v1.0 capstone

The completed v1.0 remains broader and still includes:

- all accepted Phase 1 and Phase 2 workflows;
- bookmarks, Helpful marks, reports, report handling, activity counts, taxonomy management, account management, replacements, and the complete status model;
- the required AI package across extraction, lifecycle, summaries, suggested metadata/tags, semantic search, related resources, grounded inquiry, citations, insufficiency handling, follow-up, and graceful fallback;
- the accepted security, privacy, audit, and test requirements.

The presentation checkpoint does not redefine this final scope.

---

## 4. Implementation Principles

1. **Build the core without an AI dependency.** Login, upload, moderation, metadata search, and controlled file access must remain usable when Ollama is stopped.
2. **Work in vertical slices.** Finish and test one complete user path before starting another.
3. **Enforce rules on the server.** Hidden buttons are not authorization.
4. **Fail closed.** If identity, role, status, ownership, file availability, CSRF, or input validity cannot be confirmed, deny the operation.
5. **Use the accepted schema as-is until an AI architecture decision is approved.**
6. **Keep uploaded files outside `public/`.** Serve them only through a checked PHP endpoint.
7. **Use prepared statements and output escaping everywhere.**
8. **Keep state-changing requests protected by CSRF tokens.**
9. **Avoid demo-only shortcuts.** Seed data is allowed; bypassing permissions or status rules is not.
10. **Record honest status.** A failed test remains evidence and does not become a pass through wording changes.
11. **Commit at verified checkpoints.** Do not combine unrelated unfinished work into one commit.
12. **Freeze new scope.** No new roles, tables, statuses, frameworks, AI providers, or major features during the two-week checkpoint without an explicit decision.

---

## 5. Proposed Native-PHP Structure

The exact filenames may be refined during implementation, but responsibilities should remain separated:

```text
app/
  Config/          environment and application configuration
  Core/            database, routing, sessions, responses, validation
  Security/        authentication, authorization, CSRF, escaping helpers
  Repositories/    prepared database queries grouped by data area
  Services/        resource, upload, moderation, file, search, and AI logic
  Controllers/     request coordination
  Views/           server-rendered PHP templates and reusable partials

public/
  index.php        browser entry point/front controller
  assets/          CSS, JavaScript, and safe static images

storage/
  uploads/         protected uploaded files; never statically served
  extracted_text/  protected derived text where the accepted design allows it

database/
  schema.sql       accepted schema baseline
  seeds/           local demonstration data and one-time bootstrap helpers

config/
  local.example.php

tests/
  smoke/
  integration/
```

Local secrets belong in an ignored local configuration file. They must not be committed or exposed through `public/`.

### 5.1 First Admin bootstrap

The first Admin is created once through the non-public command-line helper:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass `
    -File database\seeds\create_first_admin.ps1
```

The helper:

* runs only from the local command line and has no browser route;
* asks for the username, display name, password, and confirmation;
* keeps the password out of command-line arguments and application logs;
* uses the same account validation and `password_hash()` path as runtime account creation;
* refuses to run after any Admin account exists; and
* creates no default or placeholder credential.

The first setup action cannot write a normal `audit_log` row because that table requires an existing actor account. The setup command itself is the local bootstrap record. Every later elevated account must be created by an authenticated Admin and audited through the normal account-management workflow.

### 5.2 Current protected-upload implementation

The local prototype uses an idempotent command-line seed for demonstration taxonomy values. Those values support the presentation workflow and are not presented as the final official BPC course, subject, year-level, resource-type, or tag list.

The upload path enforces the accepted 20 MB limit, extension and detected-MIME agreement, non-empty input, and format-specific structure checks. PDF files require a PDF header and completion marker; DOCX/PPTX files require a consistent ZIP package and their expected internal document entry; TXT files require UTF-8 text without NUL bytes; JPG/JPEG and PNG files require matching image signatures.

Only an active Student or Teacher/Instructor may create an ordinary upload. Role, status, taxonomy availability, and selected controlled values are rechecked server-side, including a transaction-time uploader check. Accepted files receive a cryptographically random storage filename outside `public/`, and the database row is created as `Pending`. If the database write fails, the moved file is removed.

The frontend should initially use server-rendered PHP, reusable HTML partials, CSS, and small vanilla-JavaScript enhancements. A single-page application or frontend framework is not required.

### 5.3 Upload-form usability review (proposed; not implemented)

**Status:** Recorded usability refinement. No metadata rule, schema, taxonomy authority, or application behavior is changed by this note. Changes that conflict with accepted source documents require an explicit decision before implementation.

**Reason for the proposal:** A walkthrough of the working upload form showed that Students and Teacher/Instructors may receive files with weak filenames such as `INFO-SHEET-1.pdf` and may not know enough about the contents to write a detailed title, description, or topic. Technical validation wording and incomplete subject/tag choices may add avoidable friction. The form should help honest uploaders provide useful metadata without implying that they must already be cataloging experts.

#### Recommended near-term refinements that preserve the accepted data model

| Area | Recommendation | Why this is better |
|---|---|---|
| Title | Relabel the field as “Resource title.” Keep it required and editable, but suggest a human-readable value from the selected filename, such as `INFO-SHEET-1.pdf` to `Info Sheet 1`. Clearly label it as a suggestion. | This distinguishes the human-readable title of the contents from the original filename, while still helping uploaders when the document has no clear internal title. |
| Description guidance | Replace database-oriented messages such as “65,535 bytes” with plain instructions. Explain that a short description helps moderation, ordinary search, and later AI-assisted discovery, but does not guarantee AI correctness. | Users receive an understandable reason for the field instead of an internal storage limit. |
| Topic guidance | Keep topic required for now, but relabel it as “Topic or lesson covered” and state that a short phrase is sufficient. | This preserves the confirmed metadata-search requirement while making the expected answer clearer. |
| Subject display | Use controlled subject labels that include the official code and full name where known, for example `ISP-323 — Information Systems Project`. The prototype may keep this combined value in `subjects.name`. | Students commonly recognize subject codes, while the full name avoids ambiguous initials. This needs no immediate schema change. |
| Tags | Keep tag selection optional and Admin-controlled. Tell uploaders to choose only clearly related tags and leave the field blank when none fit. Expand the prototype list only with a small reviewed set of general tags. | Relevant tags can improve browsing and later AI-assisted discovery, but an unrelated tag produces misleading search results and is worse than no tag. Title, topic, subject, and description remain the primary metadata when tags are blank. |

Suggested user-facing title helper text:

> Enter the title shown inside the document. If it has no clear title, use a readable version of the filename.

Suggested user-facing tag helper text:

> Optional. Choose only tags that clearly match this resource. Leave this blank if none apply.

Suggested user-facing description text **if optional Description is later approved**:

> Optional details can help moderators and other users understand and find this resource. They may also help future AI-assisted search use the file more effectively. A short sentence is enough.

Suggested validation wording when a description remains required:

> Please add a short description of what the file contains.

#### Changes that require an explicit decision

1. **Making Description optional at initial submission.** Current source documents treat Description as required and `resources.description` is `NOT NULL`. The recommended direction is to consider allowing an empty initial description while keeping it reviewable during moderation. Before implementation, the project must decide whether an Approved resource may remain without a description or whether a Moderator should request correction when the missing description materially harms discovery.
2. **Making Topic optional.** This is not recommended for the current prototype because Topic is explicitly required for non-AI metadata search. The lower-friction alternative is clearer wording and accepting a short phrase.
3. **Allowing uploaders to create custom tags.** This is not recommended as direct creation because the accepted design makes tags a controlled Admin-managed vocabulary. A later “suggest a tag” workflow could send a proposed value for Admin review without immediately creating it.
4. **Adding a separate subject-code column.** This is unnecessary for the current vertical slice. A combined controlled label is sufficient until an official institutional subject list or a confirmed code-specific search requirement justifies a schema decision.

#### Safety and scope boundaries

- Filename-based title suggestions remain editable and must not silently overwrite uploader text.
- AI may later suggest metadata only when the accepted eligibility, notice, acknowledgment, lifecycle, and review rules allow it. AI must not make these fields authoritative automatically.
- General tags added for the prototype remain demonstration values, not an official BPC taxonomy.
- This note does not authorize schema changes, free-text tags, or optional metadata by itself.

---

## 6. Build Gates

Work does not move forward merely because a page looks complete.

### Gate 0 — Plan and environment

Pass when:

- this build plan and `TESTING_CHECKLIST.md` are reviewed;
- PHP, MariaDB, and the local document root are confirmed;
- the accepted schema imports cleanly;
- local configuration and secrets are ignored;
- the application can connect to the database and render a safe error page.

### Gate 1 — Authentication and authorization foundation

Pass when:

- the first Admin is created by a non-public bootstrap or seed;
- Student self-registration works;
- login uses `password_hash()`/`password_verify()`;
- session ID regenerates after login;
- logout invalidates the session;
- the 30-minute idle timeout works;
- every protected request reloads live account status and role;
- direct unauthorized requests are rejected.

### Gate 2 — Upload and protected storage

Pass when:

- only Student and Teacher/Instructor users can initiate ordinary uploads;
- required metadata is validated server-side;
- allowed extensions, MIME/content, size, empty-file, corrupt-file, and archive rules are enforced;
- validation finishes before a resource row or stored file is accepted;
- stored filenames are generated by the server;
- files are not directly reachable from the browser;
- a successful upload creates a `Pending` resource.

### Gate 3 — Moderation

Pass when:

- Moderator/Admin users can inspect eligible Pending resources;
- approve, reject, and request-correction actions enforce allowed transitions;
- the current status is rechecked inside the write transaction;
- action history/audit evidence is written with the decision;
- an ordinary user cannot call moderation endpoints directly.

#### Gate 3 implementation checkpoint

**Status:** Implemented and locally verified in the current working tree; awaiting user review and commit.

Implemented behavior:

- active Moderator and Admin accounts can open a Pending-resource queue and inspect the submitted metadata, uploader, and protected file;
- the file is streamed through a staff-only endpoint and the randomized storage filename is not exposed;
- Approve changes `Pending` to `Approved`;
- Reject changes `Pending` to `Rejected` and requires an explanatory note;
- Request Correction changes `Pending` to `Needs Correction` and requires an explanatory note;
- the actor's current role/status and the resource's current `Pending` status are locked and rechecked inside the same database transaction;
- each successful decision writes one append-only `resource_action_history` row in the same transaction;
- anonymous, Student, and Teacher/Instructor users cannot use the moderation queue, file, or decision endpoints directly;
- invalid CSRF, repeated/stale decisions, unavailable files, and disabled staff sessions fail without changing the resource.

**Why this design is safer:** The decision and its history record either succeed together or are both rolled back. This prevents a resource status from changing without evidence of who changed it and why. Moderation history is stored in the purpose-built resource-action table rather than forcing moderation values into the separate general audit-log action list.

Verified locally:

- all three Pending transitions and their history rows;
- required-note behavior for Reject and Request Correction;
- direct ordinary-user denial and protected file access;
- stale-decision, CSRF, stored-file, and live staff-status guards;
- responsive queue and review-page rendering;
- temporary test accounts, resources, history, files, and sessions were removed after testing.

Deferred boundaries:

- uploader correction and resubmission remain a later workflow;
- linked replacement approval remains a later P2 workflow and currently fails closed instead of partially updating the original resource;
- this checkpoint does not publish repository browse/search or ordinary-user download behavior, which belongs to Gate 4.

### Gate 4 — Approved-resource discovery and access

Pass when:

- ordinary browse and metadata search return only currently eligible Approved resources;
- filters use controlled values;
- resource details escape user-controlled output;
- view/download requests recheck authentication, role/permission, resource status, and `file_availability`;
- direct URLs to ineligible resources fail closed;
- the complete core path still works while AI is disabled.

#### Gate 4 implementation checkpoint

**Status:** Implemented and locally verified in the current working tree;
awaiting user review and commit.

Implemented behavior:

- every active authenticated role can open the Approved-resource repository;
- ordinary browse results are hard-limited to resources whose current status
  is `Approved` and whose `file_availability` is `available`;
- metadata search checks title, topic, and description without depending on
  AI;
- course/program, subject, year level, resource type, and tag filters accept
  only active controlled taxonomy values;
- resource cards and details escape uploader-controlled text;
- opening a detail page rechecks live eligibility and records a view only when
  the resource remains eligible;
- downloads are served only through PHP after rechecking the active account,
  current approval status, file availability, randomized stored filename,
  expected extension, protected storage path, and exact file size;
- the client cannot choose a storage path or override the served filename with
  a query parameter;
- Pending, Needs Correction, Rejected, Withdrawn, Hidden, Restricted, Removed,
  Replaced, and Approved-but-unavailable resources fail closed through direct
  detail and download URLs;
- an old link stops working immediately after the resource becomes ineligible;
- the upload form now distinguishes the resource's human-readable title from
  its original filename and gives plain guidance for topic, description, and
  optional controlled tags.

Verified locally:

- anonymous repository access redirects to sign-in;
- Approved-and-available inclusion and every ineligible-status exclusion;
- metadata search and all five controlled filters;
- invalid filter rejection;
- output escaping and protected stored-filename non-disclosure;
- view/download activity counts;
- exact protected-file serving and attachment headers;
- unsafe filename/path query isolation;
- live status and disabled-account rechecks;
- PHP syntax, Git whitespace checks, and browser rendering of the repository
  and detail pages;
- temporary test accounts, resources, tag links, files, and cookies were
  removed after verification.

**AI boundary:** This checkpoint does not run or change Ollama, embeddings,
query vectors, retrieval rankings, thresholds, AI candidates, test runs,
measurements, accepted local AI evidence, or AI feasibility registers. It
provides the non-AI eligibility and access boundary that any later AI feature
must obey.

Deferred boundaries:

- bookmarks, Helpful marks, reports, recommendation UI, semantic search, and
  grounded inquiry remain later checkpoints;
- no schema, free-text tag, permanent AI-storage, or final AI-candidate
  decision is introduced here.

### Gate 5 — AI feasibility decision

Current evidence: the bounded synthetic Qwen3 4B preflight failed, while the comparable Llama 3.2 3B preflight passed. This permits grounded evaluation only and does not select a final candidate.

Pass when:

- a bounded generation candidate preflight is reviewed;
- grounded supported, partial, unsupported, and prohibited queries are evaluated;
- claims and source attribution are manually checked;
- lifecycle, stale-source, disabled/unavailable-AI, follow-up, and related-resource behavior are evaluated;
- a final spike recommendation records measured strengths, failures, constraints, and unresolved risks;
- any proposed schema or architecture change is reviewed separately before implementation.

### Gate 6 — Bounded AI prototype integration

Pass only if Gate 5 approves a candidate direction.

The integrated prototype must:

- use currently eligible Approved resources only;
- keep metadata search available without AI;
- preserve source-resource attribution;
- show reliable locators only when present in extraction evidence;
- state when evidence is insufficient;
- reject prohibited academic requests;
- expose no stale or ineligible derived data;
- fail without blocking upload, moderation, browse, or download.

### Gate 7 — Presentation freeze

Pass when:

- all P0 checklist items pass;
- every demonstrated control performs a real operation;
- seeded demonstration accounts and data contain no private credentials or personal institutional files;
- the demo works after a clean restart;
- the paper and presentation describe incomplete work honestly;
- screenshots or Figma frames are labelled as design previews when they do not represent implemented behavior;
- no new feature is added after the freeze unless it fixes a demonstrated blocker.

---

## 7. Next AI Checkpoints

The immediate next AI checkpoint is a **generation-candidate preflight**, not application integration.

### 7.1 Generation preflight

- Select one small local candidate through an explicit test-run registration.
- Verify exact runtime, model tag, digest, size, license/terms reference, and local-only API route.
- Begin with a 4,096-token context.
- Record model load time, first-response time, warm-response time, generated token count/rate where available, CPU/GPU split, RAM observations, failures, and output size.
- Use synthetic non-corpus inputs first.
- Check strict instruction following, evidence-only behavior, citation-format compliance, insufficiency behavior, and prohibited-request refusal.
- Do not silently download a second model or promote a smoke pass to final suitability.

### 7.2 Grounded inquiry evaluation

- Use a predeclared subset of accepted supported, partially supported, unsupported, and prohibited questions.
- Retrieve evidence using the preserved retrieval candidate.
- Send only the bounded evidence package and necessary instruction prompt.
- Record every generated claim, supporting source, citation, locator, unsupported claim, omission, refusal, latency component, and failure.
- Require manual usefulness and grounding review.

### 7.3 Lifecycle and fallback evaluation

- Confirm that Hidden, Restricted, Removed, Replaced, stale-source, missing-file, and unavailable-AI states cannot leak or block core workflows.
- Confirm that metadata search and controlled resource access still work with AI disabled.

### 7.4 Follow-up and related-resource evaluation

- Test session-scoped follow-up without permanent cross-session memory.
- Test a small related-resource result set using current eligibility checks.

### 7.5 Final recommendation

- Compare all accepted measurements with `ACCEPTED_CRITERIA.md`.
- State which candidate/configuration is accepted, rejected, or requires further evidence.
- Identify the smallest justified application and schema impact.
- Do not select a vector database, second database, provider, model, or permanent storage merely because it is popular.

---

## 8. Two-Week Working Schedule

This is a priority order, not permission to mark incomplete work as complete. A failed gate consumes the next available buffer before a new feature begins.

| Day | Core implementation track | AI/evidence track | Required checkpoint |
|---|---|---|---|
| 1 | Confirm structure, local configuration, database import, and safe error handling | Finalize generation test design | Gate 0 |
| 2 | Build database, session, CSRF, validation, response, and escaping foundations | Verify candidate availability without downloading automatically | Foundation review |
| 3 | Implement Student registration, login, logout, timeout, and first-Admin seed | Run guarded generation preflight if approved | Gate 1 partial |
| 4 | Implement live RBAC/account checks and Admin account provisioning baseline | Review preflight; prepare grounded run only if passed | Gate 1 |
| 5 | Implement metadata form, upload validation, and protected storage | Run bounded grounded inquiry evaluation | Gate 2 partial |
| 6 | Finish Pending-resource creation and upload negative tests | Audit grounded evidence; preserve failures | Gate 2 |
| 7 | Implement moderation queue and allowed decisions | Run lifecycle and fallback evaluation | Gate 3 |
| 8 | Implement Approved-only browse, metadata search, and filters | Run follow-up and related-resource evaluation | Gate 4 partial |
| 9 | Implement details and controlled file serving | Produce final feasibility recommendation and architecture preview | Gate 4 and Gate 5 |
| 10 | Fix core security/access defects; add only small supporting core operations that pass their own tests | Review any proposed AI/schema impact | Core stabilization |
| 11 | Integrate the smallest approved AI path, or preserve the AI demo as an isolated measured prototype if integration is not yet justified | Integration evidence | Gate 6 partial |
| 12 | End-to-end, negative, lifecycle, and AI-disabled regression | Audit integrated or isolated AI demonstration | Gate 6 |
| 13 | UI clarity, accessibility basics, seed data, demo script, paper evidence, screenshots | Reconcile claims with recorded evidence | Presentation candidate |
| 14 | Buffer for confirmed blockers only; freeze after the final regression | Final evidence check | Gate 7 |

If the schedule slips, preserve Gates 0–4 and the measured AI evidence before adding secondary presentation features.

---

## 9. Presentation Priority

### P0 — Must work before presentation

- database setup and clean local start;
- Student registration and login;
- role/account authorization;
- one authorized upload path;
- file validation and protected storage;
- Pending moderation path;
- Approved-only browse/search;
- controlled details/download;
- AI-disabled core behavior;
- real semantic-retrieval demonstration from preserved evidence;
- clear disclosure of implemented, experimental, and unimplemented behavior;
- documented tests for the demonstrated path.

### P1 — Include when the P0 path is stable

- basic Admin account provisioning;
- taxonomy selection and minimal management needed by the upload form;
- one real grounded-inquiry demonstration if Gate 5 passes;
- summary/tag suggestion demonstration if the generation evidence and design support it;
- polished dashboards for the roles used in the demo;
- one report or bookmark/Helpful path if it can be completed and tested without destabilizing P0.

### P2 — Do not destabilize the presentation prototype to add

- complete visual coverage of every final v1.0 screen;
- advanced dashboard analytics;
- duplicate/similar-resource flags;
- AI moderation hints;
- extensive animation or decorative UI;
- public-internet deployment features;
- a new framework, vector database, or alternate embedding model without evidence.

P1 and P2 priority does not remove these items from the accepted final v1.0 scope where they are required or planned. It only controls the two-week presentation sequence.

---

## 10. Figma Boundary

Figma is allowed for:

- illustrating a final-state screen not implemented by the presentation checkpoint;
- explaining navigation between implemented and future screens;
- documenting a design before later coding.

Figma must not:

- be presented as proof that a backend action works;
- replace the real P0 vertical slice;
- conceal an unimplemented security, moderation, storage, or AI behavior;
- use screenshots or labels that imply a completed feature when it is only a design preview.

Every Figma-only screen shown in the presentation must be labelled **Design preview — not yet implemented**.

---

## 11. Change and Commit Discipline

1. Begin every checkpoint with `git status -sb`.
2. Inspect existing files before editing.
3. Change only the files required by the current gate.
4. Run focused tests plus `git diff --check`.
5. Review `git diff` before staging.
6. Commit only after the gate evidence is accepted.
7. Use narrow commit messages such as:
   - `feat(auth): add student registration and session login`
   - `feat(resources): add guarded upload and pending workflow`
   - `feat(moderation): add pending review decisions`
   - `feat(search): add approved metadata discovery`
   - `test(ai-spike): record grounded generation checkpoint`
8. Do not push failed secrets, uploaded fixtures, local AI vectors, local configuration, or private institutional files.

---

## 12. Stop and Escalate Conditions

Stop the current checkpoint and review before continuing when:

- a requirement conflicts with a source-of-truth document;
- a new role, status, permission, table, or AI authority would be needed;
- the accepted schema cannot support the proposed behavior;
- a file can be reached without the required PHP checks;
- an unauthorized direct request succeeds;
- upload validation can be bypassed;
- an AI answer contains unsupported claims or fabricated attribution;
- AI failure blocks a core workflow;
- a test requires changing accepted ground truth merely to make a candidate pass;
- working-tree changes extend beyond the current checkpoint;
- private credentials, personal files, or local AI artifacts appear in Git.

---

## 13. Definition of a Successful Two-Week Checkpoint

The checkpoint is successful when:

- the P0 vertical slice works from a clean local start;
- the demonstrated operations are backed by real PHP and database behavior;
- the P0 security and negative tests pass;
- the AI demonstration uses accepted measured evidence and is clearly labelled as integrated or isolated;
- the system remains useful without AI;
- known gaps are recorded rather than hidden;
- the paper and presentation claims match the implementation and evidence;
- the repository remains maintainable for continued work after the presentation.

The checkpoint does not require pretending that the complete v1.0 capstone is finished.

