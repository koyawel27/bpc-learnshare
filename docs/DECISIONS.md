# DECISIONS.md

**Project:** BPC LearnShare — AI-Assisted Collaborative Academic Resource Sharing and Management System
**Version:** Draft v1.0 
**Last Updated:** 2026-07-11
**Author:** Nepthalie Jezer B. Macaslang
**Course:** BS Information Systems — Bulacan Polytechnic College
**Status:** Draft v1.0 — accepted planning decision baseline through D042

---

## Purpose

This document records confirmed architectural, workflow, scope, access-control, deployment, and AI-related decisions for BPC LearnShare. It exists so that future documents — `WORKFLOWS.md`, `DATABASE_DESIGN.md`, `AI_FEATURES.md`, `SECURITY_NOTES.md`, `DATA_PRIVACY.md`, `BUILD_PLAN.md`, and `TESTING_CHECKLIST.md` — do not reopen, silently reinterpret, or contradict decisions that have already been made.

This document is also a defense preparation resource. Each decision should be something that can be explained clearly to a panel: what was decided, what alternatives existed, and why the chosen direction is appropriate for a BS Information Systems capstone.

---

## Format

Each decision entry contains:

- **ID** — sequential identifier such as D001, D002, and so on
- **Decision** — the confirmed decision, stated precisely
- **Alternatives considered** — reasonable alternatives that were rejected or deferred
- **Reason** — why this decision was chosen
- **Affects** — future documents or system areas affected by the decision
- **Status** — decision status, such as Accepted or Accepted with deferred implementation details

---

## Decision Log

### D001 — MVP Prototype Scope, Not Production Deployment

**Decision:**  
BPC LearnShare v1.0 is a functional academic MVP prototype for local or LAN-based capstone demonstration. It is not a production-scale campus deployment.

**Alternatives considered:**  
- Build for full production deployment within the capstone timeline.
- Present the system as already deployed campus-wide.
- Limit the system to a narrow single-course demo with no future expansion direction.

**Reason:**  
The capstone timeline supports a working and testable MVP prototype, not production infrastructure. Claiming production readiness would require additional work such as server hardening, HTTPS deployment, backups, monitoring, storage policies, performance testing, and institutional approval. Campus-wide adoption remains a long-term design intent, not a current deployment claim.

**Affects:**  
`PROJECT_BRIEF.md`, `BUILD_PLAN.md`, `TESTING_CHECKLIST.md`, `PROJECT_HANDOFF.md`

**Status:** Accepted

---

### D002 — Resource-Sharing Platform, Not a Full LMS

**Decision:**  
BPC LearnShare is a resource-sharing, organization, discovery, moderation, and management platform. It is not a full Learning Management System.

The system explicitly excludes online classes, quizzes, exams, grading, attendance, enrollment, assignment submission, teacher class records, video meetings, full classroom management, and social-media-style features.

**Alternatives considered:**  
- Build a full LMS with class, grading, and attendance features.
- Build a hybrid resource-sharing and class-management system.
- Keep the system focused on academic resource sharing and moderation.

**Reason:**  
The problem being solved is fragmented and unmoderated academic resource sharing, not full class delivery. Adding LMS features would expand the project beyond a realistic capstone scope and weaken the system’s identity.

**Affects:**  
`PROJECT_BRIEF.md`, `USER_ROLES.md`, `WORKFLOWS.md`, all future feature requests

**Status:** Accepted

---

### D003 — “AI-Assisted” Wording in Documentation

**Decision:**  
Project documentation uses “AI-assisted” because AI features support users and moderators but do not control system decisions.

The adviser’s originally selected wording was “AI-Integrated.” The final official title wording still requires adviser confirmation before proposal or defense submission.

**Alternatives considered:**  
- Use “AI-Integrated” everywhere.
- Remove AI wording from internal documentation.
- Use “AI-assisted” in planning documents while confirming the final official title wording with the adviser.

**Reason:**  
“AI-assisted” accurately describes the planned AI behavior. AI may suggest summaries, tags, metadata values, related resources, duplicate flags, or moderation hints, but it must not approve, reject, publish, validate, or delete resources automatically.

**Affects:**  
`PROJECT_BRIEF.md`, document headers, `AI_FEATURES.md`, defense materials

**Status:** Accepted for internal documentation; pending adviser confirmation for official title wording

---

### D004 — Core Platform Must Work Without AI

**Decision:**  
The core platform must remain fully functional when AI features are disabled, unconfigured, unavailable, or failing.

**Alternatives considered:**  
- Make search, tagging, or resource discovery dependent on AI.
- Build AI-first and add non-AI fallback later.
- Build the core platform first, with AI as an optional assistive layer.

**Reason:**  
A defense demo should not depend on an external AI service, API key, internet access, rate limit, or paid subscription. The non-AI core must independently support upload, moderation, search, filtering, viewing, downloading, bookmarking, reporting, and management.

**Affects:**  
`PROJECT_BRIEF.md`, `BUILD_PLAN.md`, `AI_FEATURES.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D005 — Login-Required Resource Access

**Decision:**  
BPC LearnShare v1.0 requires users to log in before browsing, searching, viewing, downloading, bookmarking, reporting, or uploading resources. There is no public logged-out resource catalog.

Unauthenticated visitors may only access the login page, the Student registration page, and basic public information pages.

**Alternatives considered:**  
- Allow public browsing of approved resources.
- Allow public browsing but require login for downloads.
- Require login for all resource access.

**Reason:**  
Academic resources may contain student-created or teacher-provided materials. A login-required model is simpler to implement, easier to audit, and more appropriate for a campus-oriented academic repository than a public file-sharing catalog.

**Affects:**  
`USER_ROLES.md`, `WORKFLOWS.md`, `SECURITY_NOTES.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D006 — Student Self-Registration Is Guaranteed-On

**Decision:**  
Student self-registration is enabled and guaranteed-on in v1.0. It is not a configurable toggle.

Teacher/Instructor, Moderator, and Admin accounts cannot self-register. They must be created or assigned by an Admin.

**Alternatives considered:**  
- Make all accounts Admin-created.
- Make Student self-registration optional through a system setting.
- Allow all roles to self-register and choose their role.
- Enable Student self-registration only, while keeping elevated roles Admin-provisioned.

**Reason:**  
Students are the largest expected user group, so requiring Admins to manually create every Student account would create unnecessary administrative work. However, Teacher/Instructor, Moderator, and Admin roles carry higher trust or authority, so those roles must not be self-selected by ordinary users.

**Affects:**  
`PROJECT_BRIEF.md`, `USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`, `SECURITY_NOTES.md`

**Status:** Accepted

---

### D007 — Teacher/Instructor, Moderator, and Admin Accounts Are Admin-Provisioned

**Decision:**  
Teacher/Instructor, Moderator, and Admin accounts are created, assigned, or managed by Admin only. These roles do not have public self-registration paths.

**Alternatives considered:**  
- Teacher self-registration with later Admin approval.
- Institutional-email-domain auto-verification.
- Admin-provisioning for all elevated roles.

**Reason:**  
The moderation model depends on trusted role assignment. Allowing users to self-select Teacher, Moderator, or Admin roles would weaken the system’s access control and trust model.

**Affects:**  
`USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`, `SECURITY_NOTES.md`

**Status:** Accepted

---

### D008 — Only Student and Teacher/Instructor Roles Can Upload

**Decision:**  
Only Student and Teacher/Instructor accounts may initiate ordinary resource uploads in v1.0.

**Alternatives considered:**  
- Allow Moderator and Admin accounts to upload as contributors.
- Restrict uploads to Teacher/Instructor accounts only.
- Allow Student and Teacher/Instructor accounts to upload.

**Reason:**  
Students and teachers are the intended contributors of academic resources. Moderator and Admin accounts exist to review, manage, configure, restrict, or remove resources, not to contribute ordinary resource submissions.

**Affects:**  
`USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`

**Status:** Accepted

---

### D009 — Teacher Uploads Still Go Through Moderation

**Decision:**  
Teacher/Instructor uploads enter the same Pending moderation queue as Student uploads. There is no automatic approval or trusted-uploader bypass for Teacher/Instructor uploads in v1.0.

**Alternatives considered:**  
- Automatically approve Teacher/Instructor uploads.
- Create a lighter review path for Teacher/Instructor uploads.
- Use the same moderation queue for Student and Teacher/Instructor uploads.

**Reason:**  
A trusted-uploader bypass changes the moderation workflow and trust model. It may be considered in a future version, but v1.0 should first prove the basic moderation workflow consistently.

**Affects:**  
`USER_ROLES.md`, `WORKFLOWS.md`, `SECURITY_NOTES.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D010 — Moderator and Admin Accounts Do Not Upload as Contributors

**Decision:**  
Moderator and Admin accounts review, approve, reject, restrict, remove, configure, and manage resources, but they do not initiate ordinary resource uploads as contributors.

**Alternatives considered:**  
- Allow Moderator/Admin uploads directly to Approved status.
- Allow Moderator/Admin uploads into the Pending queue.
- Disallow ordinary contributor uploads for Moderator and Admin accounts.

**Reason:**  
This keeps contributor roles separate from moderation and administration roles. It also avoids the risk of a Moderator reviewing or approving their own submitted material.

**Affects:**  
`USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`, `SECURITY_NOTES.md`

**Status:** Accepted

---

### D011 — Uploaders Cannot Directly Edit Approved Resources

**Decision:**  
Once a resource reaches Approved status, the uploader can no longer edit it directly.

**Alternatives considered:**  
- Allow direct edits to Approved resources.
- Allow metadata-only edits without moderation.
- Block direct edits and require a moderated correction path.

**Reason:**  
Direct post-approval edits would allow a resource to change after it has already passed moderation. This weakens the moderation gate and creates uncertainty about what was actually reviewed.

**Affects:**  
`USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D012 — Approved Resource Corrections Use a Linked Replacement Record

**Decision:**  
Corrections to an Approved resource use a linked replacement-record approach.

When an uploader needs to correct an Approved resource, the corrected upload creates a new Pending resource record linked to the original resource. The original Approved resource is not edited in place.

The original resource remains active while the corrected version is pending review. If the corrected version is approved, the original resource is marked as Replaced and is hidden from normal search and browse results. The replacement resource becomes the current visible version. If the corrected version is rejected, the original Approved resource remains unchanged and visible.

The exact database field names will be finalized in `DATABASE_DESIGN.md`, but the design direction is confirmed: this is a new linked resource record, not an in-place edit and not a true versioned-row system.

**Alternatives considered:**  
- Allow in-place editing of the Approved resource.
- Create a full version-history system with multiple versions under one resource record.
- Treat the corrected upload as a completely unrelated new resource.
- Use a new linked replacement record.

**Reason:**  
A linked replacement record is simpler than a full versioning system but safer than an unrelated re-upload. It preserves the original approved resource, keeps moderation intact, and gives the database enough structure to identify that one resource replaces another.

**Affects:**  
`USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D013 — AI Is Optional, Assistive, and Non-Authoritative

**Decision:**  
All AI features are optional, assistive, and non-authoritative. AI supports human users but does not make final system decisions.

**Alternatives considered:**  
- Make AI required for the platform to work.
- Allow AI to automatically act on high-confidence results.
- Keep AI as an optional assistive layer only.

**Reason:**  
AI outputs can be useful but may be incomplete, inaccurate, or unsupported. Human users, moderators, and admins remain responsible for decisions and actions.

**Affects:**  
`PROJECT_BRIEF.md`, `USER_ROLES.md`, `AI_FEATURES.md`, `SECURITY_NOTES.md`, `DATA_PRIVACY.md`

**Status:** Accepted

**Clarification added by D041:** "Optional" in this decision describes AI's runtime dependency and authority: AI may be disabled, unavailable, unconfigured, or failing without preventing completion of the core non-AI workflows, and AI never makes final system decisions. It does not mean that the completed v1.0 capstone may omit the minimum AI capability package required by D041 and defined by D042. D013's assistive, non-authoritative, and capable-of-being-disabled-at-runtime behavior remains unchanged.
---

### D014 — Status-Based AI Eligibility Rule

**Decision:**  
AI processing eligibility is determined by resource status.

Approved resources may be processed for summaries, recommendations, and content-based search. Pending uploads may be processed only for uploader-visible suggestions and moderator/admin review assistance, after basic upload validation and with clear notice to the uploader.

Rejected, restricted, removed, private, or otherwise unauthorized files must never be processed by AI.

**Alternatives considered:**  
- Allow AI processing only after approval.
- Allow AI to process any uploaded file regardless of status.
- Use a status-based eligibility rule.

**Reason:**  
The status-based rule allows useful upload-time AI assistance while still limiting AI exposure. It also prevents AI from processing files that are rejected, restricted, removed, private, or unauthorized.

**Affects:**  
`PROJECT_BRIEF.md`, `USER_ROLES.md`, `AI_FEATURES.md`, `SECURITY_NOTES.md`, `DATA_PRIVACY.md`

**Status:** Accepted with deferred implementation details: `AI_FEATURES.md` must define basic upload validation and clear user notice before Pending-file AI processing is implemented.

---

### D015 — AI Cannot Take Unilateral Actions

**Decision:**  
AI must never approve, reject, publish, hide, restrict, validate, or delete a resource automatically.

**Alternatives considered:**  
- Allow AI to auto-approve low-risk uploads.
- Allow AI to auto-hide highly suspicious uploads.
- Prohibit all automatic AI actions.

**Reason:**  
A strict no-auto-action rule is easier to explain, implement, test, and defend. It keeps AI clearly assistive rather than controlling.

**Affects:**  
`AI_FEATURES.md`, `USER_ROLES.md`, `WORKFLOWS.md`, `SECURITY_NOTES.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D016 — Phase 5 AI Inquiry Is Optional and Approved-Resource-Only

**Decision:**  
The AI resource inquiry/chat feature is an optional stretch goal. If implemented, it must answer only from Approved resources, cite or reference the source resources used, and clearly state when no Approved resource supports an answer.

**Alternatives considered:**  
- Make AI inquiry/chat a required core feature.
- Remove AI inquiry/chat from the plan entirely.
- Keep it as an optional stretch feature after the core platform and earlier AI features are stable.

**Reason:**  
AI inquiry has higher implementation risk than summaries, tag suggestions, or recommendations. It introduces concerns around retrieval quality, hallucination, citations, and API cost. It should not determine whether the core capstone succeeds.

**Affects:**  
`PROJECT_BRIEF.md`, `AI_FEATURES.md`, `BUILD_PLAN.md`, `TESTING_CHECKLIST.md`

**Status:** Superseded by D041. Retained for historical record only. D016's framing of AI resource inquiry as an optional, cut-first Phase 5 stretch feature no longer reflects the required v1.0 AI direction. Its substantive safeguards — Approved-resource-only grounding, source-resource attribution, explicit insufficiency handling, and prohibition on unsupported answers — are preserved and carried forward under D041 and D042.
---

### D017 — Production Deployment and Hardening Are Deferred

**Decision:**  
Production-scale deployment and hardening are deferred to a future pilot or production version after the core MVP is stable.

This includes HTTPS deployment, server hardening, backup and restore procedures, monitoring, performance testing, storage limits, malware scanning or stronger file safety checks, formal privacy procedures, institutional onboarding, and real campus-wide deployment planning.

**Alternatives considered:**  
- Include production deployment in v1.0 scope.
- Ignore future production concerns entirely.
- Defer production concerns while keeping the system structured for future hardening.

**Reason:**  
Production hardening is a separate level of responsibility from a capstone MVP. Deferring it keeps v1.0 realistic while still acknowledging the long-term direction.

**Affects:**  
`PROJECT_BRIEF.md`, `BUILD_PLAN.md`, `SECURITY_NOTES.md`, `DATA_PRIVACY.md`, `PROJECT_HANDOFF.md`

**Status:** Accepted — deferred, not rejected

---

### D018 — AI Outputs Follow Resource Status and Must Not Become Orphaned

**Decision:**  
AI-generated outputs are treated as derived data tied to the resource that produced them. AI outputs must not remain publicly visible, searchable, or usable after the source resource becomes ineligible.

For v1.0 planning:

- AI outputs generated for Pending uploads are draft outputs only.
- If the Pending resource is Approved, accepted AI outputs may be retained and shown according to normal resource visibility rules.
- If the Pending resource is Rejected or withdrawn, its draft AI outputs must be deleted or invalidated and must not appear in search, recommendations, summaries, or reports.
- If an Approved resource is Hidden or Restricted, its AI outputs follow the same access restrictions as the resource.
- If a resource is Removed, its AI outputs must also be removed or invalidated. D040 defines the retained Admin-only accountability/reference data and removal-time resource-field sanitization.
- If a resource is Replaced by a corrected version, AI outputs for the old resource must not be used as outputs for the replacement. The replacement resource must generate, review, or store its own AI outputs.

**Alternatives considered:**  
- Keep all AI outputs permanently for audit or reuse.
- Delete all AI outputs immediately after moderation.
- Let AI outputs follow the status and visibility of their source resource.

**Reason:**  
AI summaries, suggested tags, recommendations, and moderation hints may contain derived information from uploaded files. If the source file is rejected, removed, restricted, or replaced, its AI outputs should not continue to circulate independently. This prevents orphaned AI-derived data from becoming a privacy or accuracy problem.

**Affects:**  
`AI_FEATURES.md`, `DATA_PRIVACY.md`, `DATABASE_DESIGN.md`, `SECURITY_NOTES.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted with deferred implementation details: exact storage, deletion, and invalidation mechanics belong in `AI_FEATURES.md`, `DATA_PRIVACY.md`, and `DATABASE_DESIGN.md`.

---

### D019 — First Admin Account Is Created During Setup Only

**Decision:**
The first Admin account is created during initial system setup only, outside the normal in-app account provisioning workflow.

For v1.0, the recommended approach is a one-time database seed or manual setup step in the local XAMPP setup process. There must be no public “create first Admin” registration page and no permanently reachable setup endpoint for creating Admin accounts.

After the first Admin account exists, all Teacher/Instructor, Moderator, and additional Admin accounts must be created or assigned by an existing Admin.

**Alternatives considered:**

* Allow the first Admin to self-register through a public setup page.
* Leave a permanent “create Admin” setup endpoint available.
* Create the first Admin through a one-time setup/bootstrap process only.

**Reason:**
The normal Admin provisioning workflow requires an existing Admin. A setup-only bootstrap process resolves this initial setup problem without creating a public privilege-escalation path.

**Affects:**
`WORKFLOWS.md`, `BUILD_PLAN.md`, `DATABASE_DESIGN.md`, `SECURITY_NOTES.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D020 — Withdrawn Is a Resource Status for Non-Public Uploader Withdrawal

**Decision:**
`Withdrawn` is a valid resource status in v1.0.

A resource may become Withdrawn only when the original uploader withdraws their own resource while it is still Pending, Needs Correction, or Rejected. Withdrawn must not be used for Approved, Hidden, Restricted, Removed, or Replaced resources.

Withdrawn resources are not visible in normal browse/search, are not available to general authenticated users, and are not eligible for AI processing. File content and draft AI outputs should be deleted or invalidated, while a minimal history/audit record may remain.

**Alternatives considered:**

* Hard-delete withdrawn resources with no history.
* Treat withdrawal as Removed.
* Add Withdrawn as a separate non-public uploader-initiated status.

**Reason:**
Withdrawal is different from Admin removal. It applies only to non-public uploader-owned resources and should not be confused with Removed, which is an Admin-only action.

**Affects:**
`USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`, `AI_FEATURES.md`, `DATA_PRIVACY.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D021 — Hidden and Restricted Have Distinct Meanings

**Decision:**
Hidden and Restricted are separate resource statuses with different workflow purposes.

* **Hidden** means a temporary moderation hold while a report or concern is being investigated.
* **Restricted** means a longer-term limited-access outcome after review.

Both statuses are excluded from normal browse/search in v1.0. Both are visible only to the uploader, Moderator, and Admin unless a future decision defines a more detailed access model.

Restricted may return to Approved if a later Moderator/Admin review determines that broad access is acceptable again. Any such reversal must be logged with a reason.

**Alternatives considered:**

* Treat Hidden and Restricted as identical statuses.
* Use only Hidden for all non-public post-approval cases.
* Define Hidden as temporary and Restricted as longer-term limited access.

**Reason:**
Using separate meanings prevents the statuses from becoming redundant. It also makes report handling and moderation outcomes easier to explain during defense.

**Affects:**
`WORKFLOWS.md`, `DATABASE_DESIGN.md`, `SECURITY_NOTES.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D022 — Removed Keeps a Minimal Resource Record While File and AI Outputs Are Invalidated

**Decision:**
For v1.0, Removed is an Admin-only terminal status that keeps a minimal resource database record for audit/reference, while file content and AI outputs are deleted or invalidated.

A Removed resource is not visible to normal users, including the original uploader. Only minimal audit/reference data should remain.

**Alternatives considered:**

* Hard-delete the entire resource row and all related records.
* Keep the full resource, file, and AI outputs but block access.
* Keep a minimized resource record while deleting or invalidating file content and AI outputs. D040 later defines the exact removal-time field sanitization and tag-association cleanup.

**Reason:**
Keeping a minimal database record avoids broken audit trails and makes moderation history easier to preserve. Deleting or invalidating the file and AI outputs prevents removed content from remaining accessible.

**Affects:**
`WORKFLOWS.md`, `DATABASE_DESIGN.md`, `AI_FEATURES.md`, `DATA_PRIVACY.md`, `SECURITY_NOTES.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D023 — Moderator and Admin May Act Directly on Approved Resources Without a Report

**Decision:**
Moderator and Admin users may act directly on a problematic Approved resource even if no Student or Teacher/Instructor report exists.

Allowed direct actions are:

* Moderator/Admin may Hide an Approved resource.
* Moderator/Admin may Restrict an Approved resource.
* Admin only may Remove an Approved resource.

A direct action must require a reason/note and must be audit-logged. No report record is created when no report exists.

**Alternatives considered:**

* Require a Student/Teacher report before any staff action.
* Require Moderator/Admin to file a public-style report against the resource first.
* Allow staff to act directly through moderation/resource-management tools.

**Reason:**
Moderators and Admins may discover issues themselves while browsing or managing resources. Requiring them to wait for a user report or file a public report against the resource adds unnecessary friction and contradicts their management responsibilities.

**Affects:**
`USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`, `SECURITY_NOTES.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D024 — Report-Based Correction Requests for Approved Resources Use the Replacement Workflow

**Decision:**
When a Moderator/Admin requests correction for a reported Approved resource, the Approved resource must not transition to Needs Correction.

Instead, “Request Correction” in the report context means:

* Moderator/Admin records a correction request note.
* The uploader is notified through the in-app upload/resource management area.
* The resource may remain Approved, become Hidden, or become Restricted depending on severity.
* The uploader’s remedy is to submit a corrected replacement through the linked replacement-record workflow defined in D012.

Needs Correction remains a pre-approval status for Pending resources only.

**Alternatives considered:**

* Move Approved resources to Needs Correction.
* Allow uploaders to directly edit Approved resources after a report.
* Treat report-based correction request as a notification that routes the uploader to the replacement workflow.

**Reason:**
Moving an Approved resource to Needs Correction would contradict D011 because Needs Correction assumes an in-place correction/resubmission path. Using the linked replacement workflow preserves moderation integrity and keeps D011 and D012 consistent.

**Affects:**
`USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D025 — Image Uploads Are Allowed in v1.0 With Search Limitations

**Decision:**
BPC LearnShare v1.0 allows image uploads for academic resources.

The v1.0 allowed file types are:

* PDF
* DOCX
* PPTX
* TXT
* JPG/JPEG
* PNG

Image-only resources may not be searchable by extracted text unless OCR, AI vision, or another text-extraction method is added later.

Executable, script, installer, and archive file types are not allowed.

**Alternatives considered:**

* Allow only document-based resources.
* Allow images without documenting search limitations.
* Allow common academic image formats while clearly documenting their limitations.

**Reason:**
Students may share photographed handwritten notes or visual learning materials. Allowing JPG/JPEG and PNG better reflects realistic academic resource-sharing behavior. However, image uploads require clear limitations because content-based search may not work on image-only files without OCR or AI vision.

**Affects:**
`WORKFLOWS.md`, `AI_FEATURES.md`, `SECURITY_NOTES.md`, `DATABASE_DESIGN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted with deferred implementation details: exact file-size limits, MIME validation details, and image text-extraction limitations belong in `SECURITY_NOTES.md`, `AI_FEATURES.md`, and `TESTING_CHECKLIST.md`.

---

### D026 — Only One Open Replacement May Exist Per Approved Resource

**Decision:**
Only one open replacement may exist for the same original Approved resource at a time.

An open replacement means a linked replacement resource with status Pending or Needs Correction. The uploader must wait for the current replacement to be Approved, Rejected, or Withdrawn before submitting another replacement for the same original Approved resource.

**Alternatives considered:**

* Allow multiple simultaneous replacement submissions for one original resource.
* Allow only the latest replacement to count.
* Limit each original Approved resource to one open replacement at a time.

**Reason:**
Multiple simultaneous replacements would create ambiguity about which version should become the current visible resource if more than one is approved. The one-open-replacement rule keeps the workflow simple and easier to enforce.

**Affects:**
`WORKFLOWS.md`, `DATABASE_DESIGN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted with deferred implementation details: `DATABASE_DESIGN.md` must define how this is enforced safely at the database level, not only through application logic.

---

### D027 — Bookmarks, Helpful Marks, Reports, and Activity Counts Do Not Automatically Transfer to Replacements

**Decision:**
When an Approved resource becomes Replaced, user interactions and activity data from the original resource do not automatically transfer to the replacement.

This includes:

* bookmarks,
* Helpful marks,
* views,
* downloads,
* reports.

Users may bookmark or mark the replacement as Helpful separately.

Reports against the original resource remain attached to the original resource. If the original becomes Replaced before an Open report is resolved, Moderator/Admin may close the report as resolved by replacement, dismiss it, or escalate it if further review is needed. If the replacement has the same issue, staff should act directly on the replacement resource.

**Alternatives considered:**

* Automatically transfer bookmarks and activity data to the replacement.
* Delete all interactions from the original once replaced.
* Keep interactions attached to the original and let users interact with the replacement separately.

**Reason:**
The replacement is a separate resource record. Automatically transferring user interactions could misrepresent what users actually viewed, downloaded, bookmarked, or marked Helpful. Keeping data attached to the original preserves history and avoids silent data movement.

**Affects:**
`WORKFLOWS.md`, `DATABASE_DESIGN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D028 — AI Suggestions May Be Treated as Accepted When Approved After Human Review

**Decision:**
For v1.0, AI suggestions shown during moderation may be treated as accepted if the Moderator/Admin approves the resource without discarding or changing those suggestions.

No separate “accept each AI field” checkbox is required in v1.0, as long as the Moderator/Admin had a visible opportunity to review, edit, or discard the AI output before approval.

**Alternatives considered:**

* Require a separate explicit acceptance checkbox for every AI-generated field.
* Discard all AI outputs after approval unless explicitly confirmed field by field.
* Treat visible, undiscarded AI suggestions as accepted during human approval.

**Reason:**
A separate explicit acceptance step for every AI field adds interface and workflow complexity. For a v1.0 capstone MVP, visible human review before approval is enough to keep AI assistive and non-authoritative while avoiding unnecessary moderation friction.

**Affects:**
`WORKFLOWS.md`, `AI_FEATURES.md`, `DATABASE_DESIGN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D029 — v1.0 Reports and Feedback Use Simple Controlled Rules

**Decision:**
Reports and feedback use simple controlled rules in v1.0.

For resource feedback, v1.0 uses a binary Helpful toggle, not a numeric rating scale.

For reporting, v1.0 uses the following report reason categories:

* Outdated resource
* Incorrect or inaccurate content
* Inappropriate content
* Duplicate or near-duplicate resource
* Suspected leaked exam, quiz, or answer key
* Copyright or unauthorized material concern
* Other

A user may have only one Open report for the same resource at a time. A later report may be allowed after the previous report is Dismissed or Actioned.

**Alternatives considered:**

* Use a numeric rating system.
* Allow free-text-only reports with no categories.
* Allow unlimited duplicate Open reports from the same user on the same resource.
* Use simple controlled feedback and report rules for v1.0.

**Reason:**
Simple feedback and report controls are easier to implement, test, moderate, and explain. They reduce duplicate spam and avoid overcomplicating the v1.0 user interaction model.

**Affects:**
`WORKFLOWS.md`, `DATABASE_DESIGN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D030 — Open Replacement Handling When Original Resource Status Changes

**Decision:**
If an Approved resource has an open replacement and the original resource later becomes Hidden, Restricted, Removed, or Replaced before the replacement is resolved, the system must not silently resolve, delete, or ignore the open replacement.

The current status of the original resource must be checked before approving the replacement.

For v1.0:

* If the original resource is still Approved, normal replacement approval applies.
* If the original resource is Hidden or Restricted, the Moderator/Admin must review the current original status before approving the replacement. If the replacement is approved, the replacement becomes Approved and the original becomes Replaced in one atomic action. The action history must record the original resource’s previous status.
* If the original resource is Removed or already Replaced, the pending replacement must not be approved as the replacement for that original. The Moderator/Admin must reject or otherwise resolve the open replacement according to allowed workflows.
* The one-open-replacement lock remains active until the replacement reaches a resolved status: Approved, Rejected, or Withdrawn.

**Alternatives considered:**

* Automatically reject or withdraw the open replacement when the original changes status.
* Automatically approve the replacement if it appears to fix the issue.
* Leave the open replacement unresolved without checking the original’s current status.
* Require live status checking and human Moderator/Admin review before final replacement approval.

**Reason:**
This avoids silent cascading behavior while preserving the linked replacement workflow. It also prevents a replacement from being approved against an original resource that is already Removed or already Replaced.

**Affects:**
`WORKFLOWS.md`, `DATABASE_DESIGN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D031 — Lightweight In-App Notifications and Queue Visibility

**Decision:**
BPC LearnShare v1.0 includes lightweight in-app notification and queue-visibility support to reduce the risk that uploads, moderation decisions, and reports go unnoticed.

This does not change the moderation workflow. Teacher/Instructor uploads still enter Pending status and are not automatically approved.

For v1.0, allowed notification and visibility features are limited to:

* in-app notifications shown after login or page load,
* unread notification count or badge,
* notification links to relevant pages such as My Uploads, moderation queue, report queue, or resource details,
* Moderator/Admin dashboard counts for Pending resources, Open reports, and Pending Teacher/Instructor uploads,
* visual Teacher Upload badge/filter/sort in the moderation queue,
* uploader-visible status updates and moderation notes.

The system does not include email notifications, SMS notifications, push notifications, WebSocket real-time updates, urgent-publication bypass, or automatic approval for Teacher/Instructor uploads in v1.0.

**Alternatives considered:**

* Keep only manual queue checking with no notification support.
* Add email or real-time notification infrastructure.
* Auto-approve Teacher/Instructor uploads to avoid queue delays.
* Add lightweight in-app notifications and dashboard visibility while keeping moderation intact.

**Reason:**
The main usability risk is that Pending uploads and reports may go unnoticed. Lightweight in-app notifications and dashboard counts improve user experience without changing the role model, moderation model, or v1.0 local/LAN deployment assumptions.

**Affects:**
`WORKFLOWS.md`, `DATABASE_DESIGN.md`, `BUILD_PLAN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D032 — Active Report Tracking for One Unresolved Report Rule

**Decision:**
BPC LearnShare v1.0 enforces the “one unresolved report per user per resource” rule using active report tracking at the database-design level.

A user may have only one unresolved report for the same resource at a time. For this rule, unresolved means the report status is Open or Escalated.

For v1.0:

* When a user creates an Open report, the system creates an active report tracking record for that reporter-resource pair.
* If an active report tracking record already exists for the same reporter-resource pair, the system rejects another report from that user for that resource.
* If the report becomes Escalated, the active report tracking record remains because the report is still unresolved.
* When the report becomes Dismissed or Actioned, the active report tracking record is cleared.
* Historical report records remain stored and are not deleted simply because the active tracking record is cleared.

**Alternatives considered:**

* Enforce the rule only through application logic.
* Use a permanent uniqueness rule on reporter and resource.
* Use active report tracking only while the report is unresolved.

**Reason:**
A permanent uniqueness rule would incorrectly prevent a user from filing a later report after a previous report was resolved. Application-only checking may allow race conditions if duplicate reports are submitted nearly at the same time. Active report tracking gives a simple database-supported way to prevent duplicate unresolved reports while preserving report history.

**Affects:**
`DATABASE_DESIGN.md`, `WORKFLOWS.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---



### D033 — Accepted SQL Schema Baseline Uses an 18-Table Structure

**Decision:**
BPC LearnShare v1.0 uses an accepted SQL schema baseline with 18 tables:

* `accounts`,
* `courses`,
* `subjects`,
* `year_levels`,
* `resource_types`,
* `tags`,
* `resources`,
* `resource_tags`,
* `reports`,
* `resource_action_history`,
* `open_replacement_tracking`,
* `open_report_tracking`,
* `bookmarks`,
* `helpful_marks`,
* `ai_outputs`,
* `notifications`,
* `system_settings`,
* `audit_log`.

The schema is documented separately in `schema.sql`. It must not be expanded with new tables unless a later decision explicitly justifies the scope impact.

**Alternatives considered:**

* Continue only with conceptual database design and defer SQL structure.
* Add extra tables for full analytics, file versioning, report history, session management, or AI-output history.
* Accept a practical 18-table schema baseline aligned with the confirmed v1.0 workflows.

**Reason:**
The 18-table baseline is enough to support the confirmed v1.0 workflows without turning the database into a production analytics system, LMS schema, or enterprise audit platform. It also gives future coding work a concrete starting point while preserving the rule that new tables require explicit scope review.

**Affects:**
`DATABASE_DESIGN.md`, `schema.sql`, `BUILD_PLAN.md`, `TESTING_CHECKLIST.md`, `PROJECT_HANDOFF.md`

**Status:** Accepted with deferred implementation details: `schema.sql` must still be executed and tested against the actual local XAMPP MySQL/MariaDB environment before PHP implementation depends on it.

---

### D034 — File Availability Is Separate From Resource Status

**Decision:**
The system tracks file availability separately from resource status.

For v1.0, file availability uses three states:

* `available` — the stored file exists and may be served only if resource status and permission checks also allow access;
* `deleted` — the physical file has been removed from storage and must not be served;
* `invalidated` — the stored file reference or content is no longer usable even if a record remains.

A file may be served only when both conditions are true:

1. the resource status and permission rules allow access; and
2. file availability is `available`.

When a workflow deletes or invalidates a file, such as Withdrawn or Removed handling, the application must update resource status and file availability consistently, preferably in the same transaction where practical.

**Alternatives considered:**

* Derive physical file availability only from resource status.
* Keep only a stored filename and rely on file existence checks.
* Track file availability separately from resource visibility.

**Reason:**
Resource status describes workflow and visibility. File availability describes whether the stored file can safely be served. These are related but not identical. For example, a Hidden resource may keep an available file for staff-only review, while a Removed or Withdrawn resource may retain a database record but have its file deleted or invalidated.

**Affects:**
`DATABASE_DESIGN.md`, `schema.sql`, `WORKFLOWS.md`, `SECURITY_NOTES.md`, `BUILD_PLAN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D035 — AI Outputs Are Current-Value Rows, Not History Rows

**Decision:**
AI output storage uses one current row per resource and AI output type. Regenerating an AI output updates the existing row instead of inserting a new history row.

For v1.0:

* each AI output belongs to exactly one resource;
* AI output must not be inherited by replacement resources;
* a replacement resource must generate or store its own AI output if eligible;
* empty placeholder `ai_outputs` rows must not be inserted before AI content is actually generated;
* public-facing queries must exclude invalidated AI output;
* AI-output history is not stored as a separate event log in v1.0.

**Alternatives considered:**

* Store every AI generation attempt as history.
* Insert placeholder rows before AI processing completes.
* Store only one current AI output row per resource/output type.

**Reason:**
The confirmed v1.0 workflows need current summaries, suggestions, duplicate flags, moderation hints, and related-resource recommendations. They do not require full AI-output audit history. A current-value design is simpler to implement and easier to maintain for a local/LAN academic MVP.

**Affects:**
`AI_FEATURES.md`, `DATABASE_DESIGN.md`, `schema.sql`, `DATA_PRIVACY.md`, `BUILD_PLAN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D036 — Polymorphic Notification and Audit Targets Are Application-Validated

**Decision:**
The `notifications` and `audit_log` designs may use `target_type` and `target_id` fields for flexible target references.

For notifications, accepted target types include:

* `resource`,
* `report`,
* `action_history`,
* `moderation_queue`,
* `report_queue`.

For general audit logs, accepted target types include:

* `account`,
* `system_setting`.

Because MySQL/MariaDB cannot enforce a normal foreign key from one `target_id` column to multiple possible target tables, correctness of polymorphic target references must be enforced by application logic.

**Alternatives considered:**

* Use separate nullable foreign-key columns for every possible notification or audit target.
* Create separate notification/audit tables per target type.
* Use `target_type` and `target_id` while documenting that application validation is required.

**Reason:**
The polymorphic target approach keeps the schema simpler and supports the lightweight in-app notification and audit-log requirements without creating many narrowly scoped tables. The tradeoff is clear: database-level foreign-key enforcement is not possible for these target IDs, so PHP validation and permission checks must be reliable.

**Affects:**
`DATABASE_DESIGN.md`, `schema.sql`, `WORKFLOWS.md`, `BUILD_PLAN.md`, `SECURITY_NOTES.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted with explicit application-validation requirement

---

### D037 — CHECK Constraints Are Defense-in-Depth, Not the Only Validation Layer

**Decision:**
The SQL schema may use simple `CHECK` constraints for database-level defense-in-depth, but PHP validation must still enforce the same rules before database writes.

The project must not rely on database `CHECK` constraints as the only enforcement layer because local XAMPP environments may use MySQL/MariaDB versions with different `CHECK` support.

Before implementation depends on these constraints, the actual local database version must be checked and `schema.sql` must be executed against a fresh database.

**Alternatives considered:**

* Rely only on PHP validation.
* Rely only on database constraints.
* Use both PHP validation and database constraints, treating database constraints as a backstop.

**Reason:**
Application validation provides clear user-facing errors and predictable workflow handling. Database constraints help catch developer mistakes and inconsistent writes during testing. Using both is practical, but the system must remain correct even if a local database version does not enforce `CHECK` constraints.

**Affects:**
`schema.sql`, `SECURITY_NOTES.md`, `BUILD_PLAN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D038 — Admin Taxonomy Values Use Add/Edit/Deactivate/Reactivate, Not Hard Delete

**Decision:**
Admin-managed taxonomy values are managed through add, edit, deactivate, and reactivate actions.

This applies to:

* courses/programs,
* subjects,
* year levels,
* resource types,
* controlled tags.

Taxonomy values already referenced by resources must not be hard-deleted in v1.0. If a value should no longer be available for new uploads, Admin deactivates it. Inactive values stop appearing as selectable options for new uploads but remain valid for existing resources that already reference them.

**Alternatives considered:**

* Allow hard deletion of taxonomy values.
* Allow free-text taxonomy values created by uploaders.
* Use deactivate/reactivate behavior for Admin-managed controlled values.

**Reason:**
Hard-deleting referenced taxonomy values could break existing resource metadata and weaken historical accuracy. Free-text values would create inconsistent filtering. Add/edit/deactivate/reactivate keeps taxonomy management simple while preserving existing records.

**Affects:**
`USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`, `schema.sql`, `BUILD_PLAN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D039 — Audit Log Extended for Password Reset and Taxonomy Management Actions

**Decision:**
`audit_log.action_type` is extended with five values:

* `password_reset`
* `taxonomy_created`
* `taxonomy_updated`
* `taxonomy_deactivated`
* `taxonomy_reactivated`

`audit_log.target_type` is extended with five values:

* `course`
* `subject`
* `year_level`
* `resource_type`
* `tag`

This closes the gap between confirmed logging requirements and the accepted `schema.sql` baseline.

Admin-assisted password reset is logged as `action_type = 'password_reset'` with `target_type = 'account'`.

Taxonomy-management actions are logged using the appropriate taxonomy action type and the specific taxonomy target type.

**Alternatives considered:**

* Reuse `role_changed` for password-reset events. Rejected because it would misrepresent the actual action.
* Use one generic `taxonomy` target type for all taxonomy tables. Rejected because `target_type` + `target_id` must resolve to one clear target. A generic `taxonomy` value would be ambiguous across `courses`, `subjects`, `year_levels`, `resource_types`, and `tags`.
* Log Admin-assisted password resets and taxonomy actions only in a flat server-side application log file. Rejected for these actions because they are ordinary Admin accountability events and should remain queryable through the structured audit log.
* Add specific taxonomy target types and specific audit action types to the existing `audit_log` table. Accepted.

**Reason:**
The workflows require taxonomy add, edit, deactivate, and reactivate actions to be logged. The database design requires Admin-assisted password reset to be auditable. The previous `audit_log` enum values did not fully support those confirmed requirements.

This is a small additive patch to one existing table. It does not change the 18-table structure confirmed in D033. It does not add a role, resource status, report status, module, workflow, or AI feature.

**Affects:**
`schema.sql`, `DATABASE_DESIGN.md`, `SECURITY_NOTES.md`, `BUILD_PLAN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

### D040 — Removed-Resource Minimal Record Uses Removal-Time Content Sanitization

**Decision:**

D022's requirement that a Removed resource keep only a minimal resource database record is implemented through removal-time content sanitization.

When an Admin changes a resource to `Removed`, the application must perform the following as one coordinated removal operation:

1. change the resource status to `Removed`;

2. change `file_availability` to `deleted` or `invalidated`, according to the actual file-handling result;

3. overwrite the following existing `resources` fields with these exact non-null placeholder values:

   * `title` -> `[Removed resource]`
   * `description` -> `[Removed resource]`
   * `topic` -> `[Removed]`
   * `original_filename` -> `[removed]`

4. delete all `resource_tags` rows associated with the Removed resource;

5. delete or invalidate AI outputs according to D018 and D035; and

6. write the required `resource_action_history` entry, including the Admin actor, removal action, timestamp, and removal reason/note.

The related database writes must be handled in one transaction where supported by the accepted design. Physical file deletion is not itself database-transactional. The implementation must coordinate physical cleanup with the database operation and must fail closed: a file whose cleanup fails must still remain non-servable because its resource status and `file_availability` no longer permit access.

The following resource data remains for accountability, referential integrity, historical relationships, and technical reference:

* resource ID;
* uploader account reference;
* course/program, subject, year-level, and resource-type references;
* randomized stored filename/reference;
* file type and file size;
* replacement linkage, when present;
* AI-notice acknowledgment fields;
* view and download counts;
* creation timestamp; and
* update timestamp.

`created_at` remains unchanged. Under the accepted schema, `updated_at` changes automatically when the resource row is updated during removal.

Existing reports, resource action history, bookmarks, Helpful marks, and other accepted historical relationships remain attached to the same resource ID unless another confirmed lifecycle rule states otherwise.

The retained record is minimized within the accepted v1.0 schema, but it is not anonymized. It may still contain account-linked and broad academic-context data. Removed-resource access therefore remains restricted according to the confirmed Admin-only accountability/reference rules.

This rule applies only to `Removed` resources. It does not apply to `Withdrawn` resources. Withdrawn resources retain their original title, description, topic, original filename, and tag associations; their file content and draft AI outputs are deleted or invalidated according to D020.

No new table, column, role, status, module, workflow, or AI feature is introduced. The accepted 18-table schema remains unchanged.

**Alternatives considered:**

* Keep the full resource metadata and redefine "minimal" to mean only "inaccessible." Rejected because this would weaken D022 and `DATABASE_DESIGN.md` Section 18.5 instead of implementing their existing minimization intent.
* Apply the same content sanitization to Withdrawn resources. Rejected because D020 does not impose the same minimal-resource-record requirement and Withdrawn is a distinct uploader-initiated lifecycle outcome for a non-public resource.
* Hard-delete the complete resource row. Rejected because it would break or remove accountability relationships and conflict with D022's requirement to preserve a minimal record.
* Add a new archive, anonymization, or removed-resource table. Rejected as unnecessary for v1.0 because the required minimization can be implemented through existing fields and relationships.

**Reason:**

D022 and `DATABASE_DESIGN.md` Section 18.5 require a Removed resource record to remain minimal, but the accepted schema otherwise retains uploader-entered descriptive content and tag relationships indefinitely. This decision defines a practical, privacy-responsible implementation using the existing 18-table schema while preserving the resource ID and relationships needed for accountability.

**Affects:**

`USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`, `schema.sql` (documentation comments only), `SECURITY_NOTES.md`, `PROJECT_HANDOFF.md`, `DATA_PRIVACY.md`, `BUILD_PLAN.md`, `TESTING_CHECKLIST.md`

**Status:** Accepted

---

D041 — Required v1.0 AI Deliverable With a Runtime-Independent Non-AI Core

Decision:

The completed BPC LearnShare v1.0 capstone prototype must implement and demonstrate the minimum AI capability package defined by D042. Repository-grounded academic resource inquiry, in which substantive academic claims are grounded in evidence retrieved from eligible Approved resources inside BPC LearnShare, is a defining v1.0 AI capability rather than an optional Phase 5 stretch feature.

This requirement applies to the completed capstone implementation and defense demonstration. It does not change D004. The non-AI core — authentication, upload, moderation, metadata search/filtering, resource view/download, bookmarks, Helpful marks, reports, and Admin/Moderator management — must remain fully and independently functional whenever AI is disabled, unconfigured, unavailable, rate-limited, failing, or unreachable because internet access is unavailable.

"Required for the completed capstone deliverable" and "required for ordinary system operation" are distinct statements. The AI capability package defined in D042 must be built and demonstrated as part of the finished prototype. Temporary AI unavailability must not block or prevent completion of a core non-AI workflow.

AI capability required by this decision remains fully subject to D013 and D015. AI remains assistive and non-authoritative, and nothing in this decision authorizes AI to approve, reject, publish, validate, Hide, Restrict, Remove, or delete a resource, or to replace Teacher/Instructor, Moderator, or Admin judgment under any circumstance.

Repository-grounded inquiry must remain repository-centered. It may use the selected model's language capability to summarize, organize, simplify, or explain retrieved evidence, but it must not silently substitute unsupported general model knowledge when repository evidence is missing. It must not turn the platform into an unrestricted general-purpose chatbot, open-web assistant, or AI tutor, and it must not replace the repository, moderation workflow, metadata search, or any other core resource-management function.

This decision supersedes D016. It clarifies, but does not contradict, D013: D013's "optional" language describes AI's runtime dependency and non-authoritative character, not permission to omit all defining AI implementation from the completed capstone. This decision does not supersede D004, D014, D015, or D018, all of which remain in force.

Alternatives considered:

Retain D016 and keep repository-grounded inquiry an optional Phase 5 stretch feature that may be cut first under time pressure.
Make ordinary core platform operation — login, upload, moderation, search, view/download, bookmarks, Helpful marks, reports, and Admin/Moderator management — dependent on AI availability.
Require a bounded, clearly scoped AI capability package as part of the completed capstone deliverable while keeping the non-AI core fully independent of AI at runtime.

Reason:

During adviser consultation, the group presented repository-grounded AI inquiry as a defining capability of BPC LearnShare. The adviser emphasized the use of current technologies such as AI, IoT, or hardware, and the group selected AI as the project's technology direction. Continuing to treat repository-grounded inquiry as an optional, cut-first stretch goal under D016 no longer reflects the capability the group intends to demonstrate.

This decision does not claim that the adviser formally approved technical implementation details that were not discussed. It records only that repository-grounded inquiry is now an expected defining capability of the completed prototype.

D004's requirement that the platform remain a fully functional resource-sharing and management system without an operational AI dependency is a separate and still-valid engineering safeguard against provider outages, configuration failure, cost limits, rate limits, and demo-day connectivity problems. D004 remains unchanged.

Affects:

PROJECT_BRIEF.md, DECISIONS.md, USER_ROLES.md, WORKFLOWS.md, DATABASE_DESIGN.md, schema.sql (only if later justified by the selected architecture under a future decision), SECURITY_NOTES.md, DATA_PRIVACY.md, PROJECT_HANDOFF.md, AI_FEATURES.md, BUILD_PLAN.md, TESTING_CHECKLIST.md

Status: Accepted. Supersedes D016. Clarifies D013 without changing D013's assistive, non-authoritative, and runtime-independent rule. Does not supersede D004, D014, D015, or D018. Downstream document propagation is required but not yet completed.

---

D042 — v1.0 AI Capability Package, Implementation Tiers, and Feasibility-Gated Hybrid Direction

Decision:

Under D041, the completed v1.0 capstone prototype must implement the AI capability package defined below. The package is organized into a required minimum tier, a planned v1.0 enhancement tier, and a deferred tier, together with shared lifecycle requirements and a feasibility-gated implementation direction.

This decision does not select or authorize any specific new database table, column, vector-store structure, model, API provider, package, or external service as the final architecture. Exact architecture and schema changes remain subject to the feasibility spike and a later explicit architecture/schema decision.

A. Required v1.0 AI foundation and minimum defense package

Readable-text extraction

Support readable PDF, DOCX, PPTX, and TXT resources where text extraction succeeds.

JPG, JPEG, PNG, scanned PDFs, and other image-only resources remain valid repository resources but are not required to support content-based AI functions in v1.0.

OCR and AI vision are deferred under Part C.

AI processing and lifecycle foundation

The implementation must track enough processing state to distinguish content that is unprocessed, processing, ready, failed, or stale.

Current resource eligibility remains a separate live condition and must not be replaced by a stored processing-state value.

The implementation must be able to detect when the source file has changed so that stale extracted content, embeddings, indexes, or AI-derived data are not used.

Exact fields, tables, storage structures, and status names are not defined by this decision.

AI-generated summaries

AI-generated summaries remain derived, reviewable, non-authoritative, and tied to the source resource's lifecycle.

AI-suggested controlled tags and metadata

AI-generated tag and metadata suggestions require human review before use.

AI must not automatically create taxonomy values, modify taxonomy records, approve metadata, or make moderation decisions.

Semantic content-based retrieval/search

Semantic retrieval searches meaning and extracted content rather than relying only on exact metadata keywords.

Metadata search and filtering under D004 must continue to work independently without AI.

Repository-grounded academic resource inquiry

Repository-grounded inquiry is available only to authenticated Active users.

It may use only resources that are currently:

Approved;
accessible to the requester;
file_availability = 'available';
successfully processed;
current and non-stale;
eligible under all applicable AI rules.

The system must retrieve a small relevant evidence set from eligible resources before generating an answer.

Substantive academic claims in the response must be grounded in the retrieved repository evidence. The selected model may use its language capability to simplify, organize, summarize, or explain that evidence, but it must not silently substitute unsupported general model knowledge when repository evidence is missing.

The system must clearly state when available repository evidence is insufficient to answer the question.

It must not answer exams, quizzes, graded assignments, answer keys, or other prohibited academic requests, consistent with the existing project-wide AI boundaries.

Source attribution

Every substantive inquiry answer must identify or link the supporting resource or resources.

Page, slide, section, heading, or another source locator should be shown when the selected extraction approach preserves that information reliably.

The system must never fabricate a page, slide, section, heading, or source locator.

Session-scoped conversational follow-up

Follow-up context may be preserved during the active inquiry session.

Persistent cross-session memory and permanent chat-history storage are not required in v1.0.

This decision does not authorize an inquiry-session table, chat-message table, or permanent conversation-history module.

Basic related-resource suggestions

The system may return a small number of relevant Approved resources using content and metadata similarity.

No personalized behavioral profile, learner profile, or activity-based recommendation profile is required or authorized.

Graceful non-AI fallback

AI failure affects only the AI-assisted feature being used.

Core workflows continue through their accepted non-AI paths under D004.

B. Planned v1.0 AI enhancements after the minimum package is stable

Duplicate or similar-resource flags

Provide non-authoritative similarity indicators for human review.

The feature must not automatically Reject, Hide, Restrict, Remove, or definitively label a resource as duplicated.

AI moderation hints

May flag possible content, metadata, similarity, or policy concerns for Moderator/Admin review.

AI moderation hints must not make or execute moderation decisions and must remain clearly distinguishable from human moderation findings.

These capabilities remain part of the intended first-version AI target but are implemented only after Part A is stable. They are not equal-weight minimum defense blockers.

Removing either planned capability from v1.0 later requires an explicit scope decision rather than silent omission.

C. Deferred beyond the required v1.0 scope

The following are deferred:

OCR for image-only resources;
AI vision processing;
persistent cross-session AI memory;
permanent chat-history storage unless separately approved;
unrestricted general-purpose AI tutoring;
open-web retrieval or automatic web browsing;
personalized learning profiles;
behavioral recommendation profiles;
AI-generated quizzes, graded assessments, grading, or answer checking;
autonomous moderation or automatic resource-status actions;
training or fine-tuning a new model from scratch.
D. Shared pipeline, lifecycle, and live-eligibility requirements

The capabilities in Parts A and B should reuse a shared processing and retrieval foundation where technically appropriate.

Conceptually:

eligible source resource
→ readable-text extraction
→ chunking and source-location preservation
→ semantic representation and retrieval
→ summaries, suggestions, semantic search, related-resource recommendations, inquiry, similarity flags, and moderation hints

This conceptual flow does not authorize exact tables, columns, indexes, providers, or storage structures.

General repository inquiry and retrieval must use only resources that pass all applicable live gates at the moment of use:

the requesting account exists;
the requesting account is Active;
the resource is currently Approved;
the requester is currently authorized to access it;
file_availability = 'available';
AI is enabled and configured for the feature;
processing or index state is ready;
the stored source fingerprint or equivalent still matches the current file;
the resource remains eligible immediately before evidence is sent for answer generation.

Any candidate returned from a cache, local index, hosted index, or external retrieval service must be revalidated against the current local source-of-truth database before its content is sent to the language model or its resource link is returned to the user.

D014 remains in force:

Pending-resource AI assistance is separate from general repository inquiry and requires successful basic upload validation plus the accepted notice-and-acknowledgment gate.
General repository inquiry must not use Pending resources.
Rejected, Withdrawn, Restricted, Removed, Replaced, private, unauthorized, and otherwise currently ineligible resources must not enter general retrieval.
Hidden resources must not be used for new public-facing retrieval or inquiry while Hidden.

Retrieval-derived data — including, depending on the later architecture, extracted text, chunks, embeddings, local index entries, hosted vector entries, provider file objects, and provider retrieval identifiers — must follow the source resource lifecycle under the requirements established by this decision, consistent with D018's existing principle that AI-derived data must not remain usable after its source becomes ineligible.

A replacement resource must undergo its own extraction and indexing. It must not inherit the original resource's retrieval-derived data.

When a file changes, prior retrieval-derived data must be treated as stale and excluded from use until regenerated.

Exact deletion, invalidation, synchronization, and storage mechanics are not defined by this decision. They belong to a later architecture/schema decision and to AI_FEATURES.md, DATABASE_DESIGN.md, SECURITY_NOTES.md, DATA_PRIVACY.md, BUILD_PLAN.md, and TESTING_CHECKLIST.md.

E. Feasibility-gated hybrid implementation direction

The preferred current prototype direction, subject to a feasibility spike before final architecture and schema lock, is:

native PHP remains the application and backend stack;
the current MariaDB 10.4 environment remains the system-of-record database during the first feasibility spike;
text extraction is performed locally where practical;
lightweight local embeddings are preferred for the first spike;
a bounded prototype may store required retrieval data locally and calculate similarity in PHP;
an external language-model API may be used where necessary for acceptable answer quality and response time;
local language-model generation may be tested as an experimental fallback but is not yet a required dependency;
AI providers and models should remain replaceable where practical;
the project should prefer free, free-tier, or near-zero-cost prototype options without assuming that any third-party free tier will remain permanently available.

The current working candidate for the feasibility spike — illustrative only and not a final dependency choice — may combine:

local text extraction;
local embeddings;
MariaDB-based retrieval data;
PHP-side cosine similarity for a bounded corpus;
an external generation API such as Groq where necessary.

This decision does not lock Groq, Ollama, Hugging Face, Supabase, or any specific model as a final dependency.

It does not treat current free-tier availability as a permanent system assumption.

A MariaDB upgrade, Supabase pgvector, or another vector-storage or retrieval service must not be introduced before the feasibility spike demonstrates a practical need.

MariaDB 11.7+ native vector support, Supabase pgvector, or another vector service may be considered only if measured testing shows that the initial bounded approach is too slow, inaccurate, difficult to maintain, or otherwise unsuitable.

The feasibility spike must occur before final architecture and schema lock.

The spike should later verify, at minimum:

extraction from representative readable PDF, DOCX, PPTX, and TXT files;
a representative corpus of approximately 25–50 Approved resources;
chunking and source-location preservation;
local embedding generation;
retrieval relevance;
PHP-side similarity performance;
grounded inquiry;
resource attribution and citations;
insufficiency behavior;
live status and permission exclusion;
stale-source handling;
provider-outage behavior;
latency;
memory use;
actual cost or free-tier usage.

The detailed feasibility-spike plan is not created by this decision.

F. Relationship to D033 and schema scope

The required retrieval foundation provides a legitimate reason to reconsider the accepted 18-table baseline through a future explicit decision.

This decision does not itself authorize an unnamed or speculative table or column.

ai_outputs remains an AI-output store and must not be overloaded as an extraction, chunk, embedding, retrieval-result, or conversation-history index.

Any exact schema addition must be justified by the feasibility spike and approved through a later explicit architecture/schema decision before DATABASE_DESIGN.md or schema.sql is changed.

D033 remains the active accepted schema baseline until that later decision is accepted.

Persistent storage of retrieval results, generated inquiry answers, grounded citations, or chat history must not be assumed. These may remain request-scoped or session-scoped unless a later decision establishes a valid and justified need.

Alternatives considered:

Treat every AI capability as an equal-priority requirement with no tiering, increasing the risk of an unrealistic implementation and defense timeline.
Lock a specific vector database, hosted provider, MariaDB upgrade, or technical architecture immediately without a feasibility spike.
Leave AI scope undefined in DECISIONS.md and resolve it only inside AI_FEATURES.md.
Define a tiered required/planned/deferred package with a feasibility-gated hybrid direction and explicit schema deferral.

Reason:

A tiered package separates the capabilities that must exist for a defensible completed prototype from enhancements that strengthen the first version but are not minimum defense blockers, and from capabilities that are explicitly deferred.

This keeps the required deliverable bounded and realistic for a beginner-maintainable BSIS capstone developed by a small student team using native PHP without a major framework.

Gating the final architecture on a feasibility spike avoids committing to untested tools, pricing, hardware behavior, retrieval quality, or provider availability before they are measured. It also prevents the project from silently adopting additional infrastructure that has not been shown to be necessary.

Explicitly deferring schema changes preserves D033's rule that the accepted 18-table baseline is not expanded without justification and prevents this decision from quietly authorizing new tables through the AI capability requirement.

Affects:

PROJECT_BRIEF.md, DECISIONS.md, USER_ROLES.md, WORKFLOWS.md, DATABASE_DESIGN.md, schema.sql (only upon a future separate architecture/schema decision), SECURITY_NOTES.md, DATA_PRIVACY.md, PROJECT_HANDOFF.md, AI_FEATURES.md, BUILD_PLAN.md, TESTING_CHECKLIST.md

Status: Accepted. Establishes the required minimum AI package under D041. Does not modify D014, D015, D018, D025, D033, or D035 and does not modify the accepted 18-table schema baseline. The feasibility spike, architecture/schema decision, and downstream document propagation remain outstanding follow-on work.

---

## Notes on Using This Document

1. Future documents must check this decision log before redefining scope, roles, workflows, AI behavior, deployment assumptions, access rules, resource statuses, moderation behavior, or report behavior.

2. If a later decision changes something recorded here, add a new decision entry referencing the one it supersedes. Do not silently delete or rewrite past decisions without documenting the change.

3. Decisions marked as deferred are not forgotten. They are intentionally postponed to the document where the implementation detail belongs.

4. `WORKFLOWS.md` must follow D005, D006, D008, D009, D010, D011, D012, D019, D020, D021, D022, D023, D024, D026, D027, D029, D030, D031, D032, D034, D036, D038, D040, D041, and D042 when defining registration, upload, moderation, correction, replacement, withdrawal, reporting, notification, taxonomy-management, resource-status, Removed-resource sanitization, AI processing and retrieval, repository-grounded inquiry, graceful fallback, and AI-related lifecycle workflows.

5. `AI_FEATURES.md`, `DATA_PRIVACY.md`, and `SECURITY_NOTES.md` must follow D014, D015, D018, D025, D028, D035, D037, D040, D041, and D042 when defining AI eligibility, required and planned AI capabilities, AI-output and retrieval-derived-data lifecycle, AI user notice, AI-assisted moderation, repository-grounded inquiry, external-provider handling, AI-related privacy safeguards, image-resource limitations, validation boundaries, and Removed-resource minimization. D016 is retained only as a superseded historical decision and must not be used as the active v1.0 inquiry-scope rule.

6. `DATABASE_DESIGN.md` and `schema.sql` must follow D006, D007, D008, D010, D012, D014, D018, D019, D020, D021, D022, D023, D024, D026, D027, D028, D029, D030, D031, D032, D033, D034, D035, D036, D037, D038, D039, D040, D041, and D042 when defining user roles, account provisioning, resource ownership, resource statuses, replacement, withdrawal, reports, bookmarks, Helpful marks, AI-output storage, notifications, audit logs, taxonomy lookups, Removed-resource minimization, retrieval-data requirements, schema-scope boundaries, and database-level constraints. D042 does not itself authorize a schema expansion; any exact retrieval-related addition requires the later architecture/schema decision it specifies.

7. `BUILD_PLAN.md` must preserve the existing implementation requirements from D019 and D033–D040 and must also reflect D041–D042. It must sequence the AI feasibility spike before final retrieval architecture and schema lock, preserve the independently functional non-AI core, avoid treating the current hybrid working candidate as an already-proven final architecture, and carry forward the required minimum AI package, planned v1.0 enhancements, graceful fallback behavior, lifecycle controls, and later testing obligations. Before detailed implementation planning relies on database or AI assumptions, the project must also confirm that the basic local development environment, database connection, login flow, and file-upload path can run correctly in XAMPP.

8. Production deployment concerns listed in D017 are deferred hardening work. They should not be treated as v1.0 requirements unless the project scope is formally changed.

---

*End of DECISIONS.md*
