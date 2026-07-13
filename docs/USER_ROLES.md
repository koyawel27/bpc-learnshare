# USER_ROLES.md

**Project:** BPC LearnShare — AI-Assisted Collaborative Academic Resource Sharing and Management System
**Version:** Draft v1.0
**Last Updated:** 2026-07-12
**Author:** Nepthalie Jezer B. Macaslang
**Course:** BS Information Systems — Bulacan Polytechnic College
**Status:** Draft v1.0 — accepted role and permission baseline through D042

---

## 1. Purpose of the Document

This document defines the user roles, permissions, and access boundaries for BPC LearnShare v1.0. It exists to give a single, precise reference for what each role is allowed to do before any workflow, database, or AI-feature design decisions are made.

This document does not describe step-by-step processes (see WORKFLOWS.md), database schema (see DATABASE_DESIGN.md), or AI trigger logic in detail (see AI_FEATURES.md). It only defines **who is allowed to do what**.

Where a permission is not yet decided, it is explicitly marked **Future decision** rather than assumed, per PROJECT_BRIEF.md's planning rules.

---

## 2. Role Summary

| Role | One-line description |
|---|---|
| **Student** | Consumes and contributes academic resources; no moderation or admin authority. |
| **Teacher / Instructor** | Uploads official/recommended resources; same base access as Student, no bypass of moderation in v1.0. |
| **Moderator** | Reviews, approves, rejects, and manages resources and reports; no system/user administration authority. |
| **Admin** | Manages users, taxonomy (courses/programs, subjects, year levels, resource types, and controlled tags), system settings, and can perform moderation actions; the only role with destructive/system-level authority. |

No role beyond these four is defined for v1.0. See Section 14 for a flagged potential future role.

---

## 3. Permission Matrix

| Permission | Student | Teacher / Instructor | Moderator | Admin |
|---|---|---|---|---|
| Student self-registration | Allowed | Not allowed | Not allowed | Not allowed |
| Log in to active account | Allowed | Allowed | Allowed | Allowed |
| Create/manage Teacher accounts | Not allowed | Not allowed | Not allowed | Admin only |
| Create/manage Moderator accounts | Not allowed | Not allowed | Not allowed | Admin only |
| Create/manage Admin accounts | Not allowed | Not allowed | Not allowed | Admin only |
| Role assignment / change | Not allowed | Not allowed | Not allowed | Admin only |
| Browse & search approved resources | Allowed | Allowed | Allowed | Allowed |
| View / download approved resources | Allowed | Allowed | Allowed | Allowed |
| Bookmark a resource | Allowed | Allowed | Allowed | Allowed |
| Mark a resource as helpful | Allowed | Allowed | Allowed | Allowed |
| Use AI-assisted search, inquiry, and related-resource suggestions on eligible Approved resources | Allowed | Allowed | Allowed | Allowed |
| Upload a new resource | Allowed | Allowed | Not allowed | Not allowed |
| Edit own resource while Pending, Needs Correction, or Rejected | Own only | Own only | Allowed (any resource, as part of review) | Allowed (any resource) |
| Edit own resource after Approval | Not allowed | Not allowed | Allowed (as moderation action) | Allowed (as administrative/moderation action) |
| Withdraw own Pending, Needs Correction, or Rejected resource | Own only | Own only | Not applicable | Not applicable |
| Report an approved resource | Allowed | Allowed | N/A — acts via moderation workflow | N/A — acts via moderation workflow |
| Review pending uploads | Not allowed | Not allowed | Allowed | Allowed |
| Approve / reject / request correction | Not allowed | Not allowed | Allowed | Allowed |
| Review & act on reported resources | Not allowed | Not allowed | Allowed | Allowed |
| Hide / restrict a resource | Not allowed | Not allowed | Allowed | Allowed |
| Edit metadata of any user's resource | Not allowed | Not allowed | Allowed | Allowed |
| Remove resource from system or delete stored file when necessary | Not allowed | Not allowed | Not allowed | Admin only |
| Manage user accounts | Not allowed | Not allowed | Not allowed | Admin only |
| Manage courses/programs, subjects, year levels, resource types, and controlled tags | Not allowed | Not allowed | Not allowed | Admin only |
| Manage system settings (incl. enabling/disabling AI) | Not allowed | Not allowed | Not allowed | Admin only |
| View report queue | Not allowed | Not allowed | Allowed | Allowed |
| View full system activity logs | Not allowed | Not allowed | Moderator/Admin only (reports-related actions only) | Allowed (full) |
| Review / edit AI-generated summaries, tag suggestions, or metadata suggestions where current lifecycle and permissions allow | Own resource only (pre-approval) | Own resource only (pre-approval) | Allowed for authorized resources during review | Allowed for authorized resources |

**Notes on permissions that may be misunderstood:**

- **Teacher, Moderator, and Admin accounts are never self-selected by ordinary users.** Only Student accounts can be created through self-registration. Teacher, Moderator, and Admin accounts must be created and assigned by an existing Admin. This exists because allowing self-selection of elevated roles would let anyone grant themselves moderation or administrative trust — which defeats the purpose of having a moderation model at all.
- **"Upload a new resource" is Not allowed for Moderator and Admin.** For v1.0, only Student and Teacher/Instructor accounts can initiate ordinary resource uploads. Moderator and Admin accounts manage, review, approve, restrict, remove, and configure resources, but they do not initiate normal resource submissions. This avoids self-review and keeps contributor roles separate from moderation and administration roles.
- **"Withdraw own Pending, Needs Correction, or Rejected resource" is Not applicable for Moderator and Admin**, not "Not allowed" — the distinction matters. Since Moderator and Admin accounts cannot upload resources at all (see above), there is no scenario where they would hold uploader-level withdrawal rights over their own submission; the concept doesn't apply to these roles the way it applies to Student/Teacher. Their equivalent authority over any resource is exercised through the separate Hide/Restrict and Remove/Delete rows, not through this row.
- **"Edit own resource after Approval" is Not allowed for Student and Teacher/Instructor.** Once a resource is Approved, the uploader cannot silently change it. Any correction must go through the linked replacement-record workflow, where the corrected upload creates a new Pending resource linked to the original Approved resource and re-enters moderation.
- **"Report an approved resource" is marked N/A for Moderator and Admin, not "Allowed."** Moderators and Admins don't use the public report button; they identify and act on problems directly through the moderation and report-management workflow (Sections 6, 7, and 10).
- **"View full system activity logs" splits Moderator and Admin.** Moderators only need visibility into report-handling and moderation-decision logs to do their job. Full system logs (login attempts, admin actions, account changes) are Admin-only.
- **"Remove resource from system or delete stored file" replaces "permanently delete"** because removal may mean different things depending on implementation (a soft-delete that retains a record, versus a hard file deletion). Regardless of implementation, this action is Admin-only and must be logged (see Section 7).
- **AI-output review/edit permission remains lifecycle- and ownership-limited.** Student and Teacher/Instructor users may review or edit supported AI-generated summaries and metadata/tag suggestions only for their own resource before Approval and only where the current resource lifecycle permits. Moderator and Admin permissions apply only to resources they are currently authorized to review or manage. AI output never expands access to the underlying resource or grants a new moderation or administrative permission.
- **"Use AI-assisted search, inquiry, and related-resource suggestions on eligible resources" is Allowed for all four roles** because these general user-facing capabilities operate only over currently Approved resources that the requesting authenticated Active user is already authorized to access. They do not grant broader access to a resource, file, or resource detail and do not use Pending, Needs Correction, Rejected, Withdrawn, Hidden, Restricted, Removed, or Replaced resources. Moderator/Admin review assistance for non-public resources is a separate staff workflow and must not be treated as general repository inquiry.

---

## 4. Student Role

A Student may:
- Self-register for a Student account, then log in
- Browse, search, and filter approved resources
- View and download approved resources
- Bookmark resources
- Mark resources as helpful
- Use AI-assisted semantic search, repository-grounded academic resource inquiry, session-scoped follow-up, and related-resource suggestions on eligible Approved resources that are currently accessible to them
- Upload academic resources for moderation review
- Edit or withdraw their own resource while it is Pending, Needs Correction, or Rejected
- Report an approved resource believed to violate guidelines
- Review and, if enabled, edit AI-suggested summary, tag suggestions, and metadata suggestions on their own pending upload before it is reviewed

A Student may **not**:
- Approve, reject, publish, hide, restrict, or remove/delete any resource
- Moderate or act on reports filed by others
- Edit metadata on a resource they do not own
- Edit their own resource once it has been Approved (see Section 8 for the revision-request path)
- Access another user's private, pending, or rejected resources
- Use AI-assisted search, inquiry, citations, or related-resource suggestions to access any resource or file beyond what their current account status, role, resource status, permissions, and file availability already allow
- Manage users, taxonomy, or system settings
- Browse, search, view, download, bookmark, report, or upload without being logged in (see Section 12)

---

## 5. Teacher / Instructor Role

A Teacher/Instructor account is created and assigned by an Admin — Teachers do not self-register (see Section 3). Once logged in, a Teacher/Instructor has the same base actions as a Student (browse, search, view, download, bookmark, mark resources as helpful, report, and use AI-assisted semantic search, repository-grounded inquiry, session-scoped follow-up, and related-resource suggestions on eligible Approved resources) and additionally:
- Uploads official or recommended academic resources
- Manages (edits/withdraws) their own uploaded resources while Pending, Needs Correction, or Rejected

**In v1.0, Teacher/Instructor uploads go through the same moderation queue as Student uploads.** There is no automatic-approval or trusted-uploader bypass for teachers in this version. Any future decision to grant teachers moderation-bypass privileges must be documented explicitly in DECISIONS.md before implementation, since it changes the moderation workflow and the trust model of the platform.

A Teacher/Instructor may **not**:
- Approve, reject, or moderate their own or others' uploads
- Act on reports
- Edit another user's resource metadata
- Edit their own resource once it has been Approved (see Section 8)
- Use AI-assisted search, inquiry, citations, or related-resource suggestions to access any resource or file beyond what their current account status, role, resource status, permissions, and file availability already allow
- Manage users, taxonomy, or system settings

---

## 6. Moderator Role

A Moderator account is created and assigned by an Admin (see Section 3). A Moderator may:
- Review all Pending uploads
- Approve, reject, or request correction on any resource
- Review and act on reported resources (hide, restrict, escalate, or dismiss a report)
- Edit metadata (title, description, subject, tags, resource type) on any resource when needed for accuracy or policy compliance
- Edit an Approved resource as a moderation action (e.g., correcting metadata after a report), separate from the uploader's own edit rights, which end at Approval
- View AI-generated summaries, suggested tags/metadata, and — once implemented — duplicate/similar-resource indicators and AI moderation hints as assistive information for resources they are currently authorized to review; these remain non-authoritative and never substitute for independent human judgment
- View the report queue and moderation-action logs

A Moderator may **not**:
- Upload a new resource as an ordinary contributor (see Section 3 and Section 9)
- Manage user accounts
- Manage courses/programs, subjects, year levels, resource types, or controlled tags at the system-configuration level
- Manage system-wide settings, including enabling/disabling AI features
- Remove a resource from the system or delete its stored file (uses hide/restrict instead — see Section 12; removal/deletion is Admin only, see Section 7)
- Treat an AI-generated summary, suggestion, duplicate/similarity indicator, or moderation hint as proof of correctness, a substitute for independent review, or authorization for an automatic moderation action
- View full system activity logs beyond moderation- and report-related entries

---

## 7. Admin Role

An Admin account is created and assigned by an existing Admin (see Section 3). An Admin may:
- Perform all Moderator actions
- Manage user accounts (create, disable, change role assignment)
- Manage courses/programs, subjects, year levels, resource types, and controlled tags
- Manage system-wide settings, including enabling or disabling AI features
- Remove a resource from the system or delete its stored file when necessary
- View full system activity logs and all reports

An Admin may **not**:
- Upload a new resource as an ordinary contributor (see Section 3 and Section 9)

Admin and Moderator are kept as **separate roles**, not a single "staff" role, because Admin carries destructive and system-configuration authority (user management, resource removal, system settings) that a Moderator should not need for day-to-day content review. This separation should be preserved in DATABASE_DESIGN.md as distinct role values, not collapsed into one "staff" flag.

Destructive actions (account removal, resource removal or file deletion, role changes) must be logged. For Removed resources, D040 preserves Admin-only accountability/reference data while sanitizing uploader-entered descriptive fields and removing controlled-tag associations.

---

## 8. Resource Ownership Rules

- A resource's **uploader** is its owner of record regardless of role. Since only Student and Teacher/Instructor accounts may upload (see Section 9), only these two roles ever hold uploader-level ownership over a resource.
- Ownership grants the right to edit or withdraw a resource **only while it is Pending, Needs Correction, or Rejected**. Once a resource is Approved, the uploader cannot edit it directly.
- To correct an Approved resource, the uploader must submit a corrected replacement resource, which re-enters the Pending queue and goes through moderation again. This prevents an already-reviewed resource from being changed without another moderation check.
- Ownership does **not** grant the right to bypass moderation or self-approve.
- Moderators and Admins may edit metadata on any resource, including Approved resources, regardless of ownership, as part of their review authority — this is a review power, not an ownership transfer.

---

## 9. Upload and Moderation Permissions

- **Only Student and Teacher/Instructor roles may initiate ordinary resource uploads in v1.0.** Moderator and Admin accounts do not upload as contributors; their authority is limited to reviewing, approving, rejecting, restricting, and removing resources submitted by others. This separation avoids a reviewer approving or moderating their own submission.
- Every new upload enters a **Pending** status by default. No role can force a resource directly to Approved on upload, including Teacher/Instructor.
- Only Moderator and Admin roles may change a resource's status among Pending, Approved, Rejected, or Needs Correction.
- Only Moderator and Admin roles may hide or restrict an already-Approved resource.
- Only Admin may remove a resource from the system or delete its stored file when necessary.
- An uploader cannot edit an Approved resource in place. To correct one, they must submit a revision request or re-upload a corrected version, which re-enters Pending and goes through moderation again (see Section 8).

This section defines *who* holds these permissions. The exact status flow and state transitions belong in WORKFLOWS.md, not here.

---

## 10. Report Handling Permissions

- Any authenticated user (Student or Teacher/Instructor) may report an Approved resource they believe is incorrect, outdated, inappropriate, duplicate, or otherwise in violation of guidelines.
- Only Moderator and Admin roles may view the report queue, investigate a report, and take action (hide, restrict, request correction from the uploader, or dismiss the report as invalid).
- Moderators and Admins do not use the public report mechanism. If a Moderator or Admin identifies a problem with an Approved resource, they act on it directly through the moderation/report-management workflow rather than filing a report against it.
- A user cannot report their own resource through this mechanism; withdrawing or correcting their own pending/rejected resource is handled through ownership rights (Section 8), not the report system.

---

## 11. AI-Related Permissions

- **AI remains assistive and non-authoritative at runtime.** Admin may enable or disable configured AI-assisted capabilities at the system level. Individual AI functions may also become unavailable or degraded because of missing configuration, local-runtime failure, provider failure, rate limits, internet unavailability, or other technical conditions. None of these conditions removes or blocks the accepted non-AI permissions of any role.

- Runtime optionality is separate from completed-capstone scope. Under `DECISIONS.md` D041–D042, the completed v1.0 capstone prototype must implement and demonstrate the required minimum AI capability package. "Optional" or "configurable" in this document describes how AI behaves during operation, not permission to omit the required package from the finished prototype.

- The required minimum v1.0 AI package includes readable-text extraction, AI-generated summaries, suggested controlled tags and metadata, semantic content-based search, repository-grounded academic resource inquiry with source-resource attribution, session-scoped follow-up, and basic related-resource suggestions. Duplicate/similar-resource indicators and AI moderation hints are planned v1.0 enhancements implemented after the required package is stable and are not equal-weight minimum defense blockers.

- **Two separate AI contexts exist and must not be conflated:**

  - **Pending-resource AI assistance** — draft summaries, suggested tags, suggested metadata, and other authorized review assistance associated with a specific Pending upload. This context requires successful basic upload validation, clear uploader notice, and uploader acknowledgment before the file may be processed.

  - **General repository-grounded semantic search, inquiry, and related-resource suggestions** — available to authenticated Active users in all four roles, but limited strictly to resources that are currently Approved, currently accessible to the requesting user, `file_availability = 'available'`, successfully processed and ready for the applicable AI function, current rather than stale, and otherwise eligible under all live lifecycle and permission rules.

- General repository inquiry never uses Pending, Needs Correction, Rejected, Withdrawn, Hidden, Restricted, Removed, or Replaced resources. Moderator/Admin authority to open a non-public resource for moderation or administration does not make that resource eligible for general repository inquiry.

- **AI processing follows current status and lifecycle eligibility.**

  - Approved resources may be processed for authorized Approved-resource AI functions such as summaries, semantic retrieval, repository-grounded inquiry, and related-resource suggestions.

  - Pending resources may be processed only for the accepted Pending-resource assistance context after successful basic upload validation, clear uploader notice, and uploader acknowledgment.

  - Needs Correction resources are not eligible for general repository inquiry. Exact reprocessing behavior after a corrected file is submitted remains for `WORKFLOWS.md`, `AI_FEATURES.md`, and the later implementation plan and must not be assumed here.

  - Rejected, Withdrawn, Restricted, Removed, Replaced, private, unauthorized, and otherwise currently ineligible resources must not enter new AI processing or general repository retrieval.

  - Hidden resources must not enter new public-facing AI processing, semantic retrieval, related-resource recommendations, or inquiry while Hidden.

  - Previously generated AI-derived data remains subject to the accepted lifecycle, invalidation, restricted-visibility, and cleanup rules. This section does not authorize public use of retained output from an ineligible resource.

- Access to an AI-assisted result — including a summary, suggestion, semantic-search result, inquiry answer, citation, or related-resource suggestion — never bypasses the user's current account status, role, ownership rules, resource status, permissions, file availability, processing readiness, or lifecycle restrictions.

- Repository-grounded inquiry responses must ground substantive academic claims in retrieved eligible repository evidence, identify or link the supporting resource or resources, and state clearly when available repository evidence is insufficient. Page, slide, section, heading, or other locators may be shown only when preserved reliably and must never be fabricated.

- Follow-up context may exist only during the active inquiry session. No role receives permanent cross-session AI memory, a permanent chat-history permission, or broader access because of prior inquiry context. This document does not authorize permanent storage of inquiry answers, retrieved evidence, citations, or session conversation history.

- AI-generated summaries, suggestions, inquiry responses, duplicate/similarity indicators, and moderation hints remain assistive and non-authoritative.

- AI cannot:

  - approve, reject, publish, validate, Hide, Restrict, Remove, delete, or otherwise change a resource's status;
  - make or execute a final moderation decision;
  - automatically create, modify, deactivate, or reactivate a taxonomy value;
  - grant access to a resource or file;
  - replace Teacher/Instructor, Moderator, or Admin judgment.

- Human actions remain limited to the existing role model:

  - Moderator and Admin may perform only the moderation and lifecycle actions already granted to them in Section 3.
  - Removal and stored-file deletion remain Admin-only.
  - Taxonomy creation and management remain Admin-only.
  - AI output never expands a role's authority.

- AI-generated Pending-resource summaries and metadata/tag suggestions are reviewable and editable by:

  - the uploader (Student or Teacher/Instructor) for their own Pending resource;
  - Moderator or Admin for resources they are currently authorized to review or manage, subject to the accepted resource lifecycle and visibility rules.

- Duplicate/similarity indicators and AI moderation hints, once implemented, remain non-authoritative review aids. Moderator/Admin may use them only as supporting information and must still perform an independent human review.

- **Intentionally unresolved for `AI_FEATURES.md` and `WORKFLOWS.md`:**

  - whether an uploader may see a duplicate/similarity indicator before moderation;
  - whether any uploader-facing explanation of a moderation hint is shown, while preserving that the actual moderation-assistance surface is staff-oriented;
  - the exact point at which a suggested tag or metadata value is written into stored resource metadata at each permitted lifecycle stage;
  - the exact UI placement, trigger timing, and request sequencing for semantic search, inquiry, and related-resource suggestions.

> Note: `AI_FEATURES.md` must define the exact Pending-file notice, acknowledgment handling, processing trigger, AI-output visibility, live-eligibility checks, processing-readiness checks, source-attribution behavior, session behavior, and failure/fallback rules before implementation. `WORKFLOWS.md` must define the user-visible and server-side sequence without inventing schema structures.

---

## 12. Access Restrictions

**BPC LearnShare v1.0 requires users to log in before browsing, searching, viewing, downloading, bookmarking, reporting, uploading, or using AI-assisted search, inquiry, or related-resource suggestions.** Unauthenticated visitors may only access the login page, the Student registration page, and basic public information pages. Approved resources are visible only to authenticated active users — there is no public, logged-out catalog.

- Resources with status Pending, Needs Correction, Rejected, Withdrawn, Hidden, Restricted, or Replaced are not discoverable through normal search or browse. They are visible only according to the rules defined in WORKFLOWS.md.
- Removed resources are not visible to normal users, including the original uploader. Under D040, removal overwrites the resource's `title`, `description`, `topic`, and `original_filename` with the confirmed placeholder values and removes its controlled-tag associations. The resource row and accepted historical relationships remain for Admin-only accountability and reference. The retained data is minimized within the accepted schema but is not anonymized because account references, required taxonomy references, technical file metadata, activity counts, timestamps, and historical relationships may remain.
- AI processing and AI-assisted access must follow Section 11. Approved resources may support summaries, semantic search, repository-grounded inquiry, and related-resource suggestions only while they remain currently Approved, accessible, file-available, AI-ready, current, and otherwise eligible. Pending resources may enter only the separate Pending-resource assistance path after successful basic upload validation, clear uploader notice, and uploader acknowledgment. General repository inquiry never uses Pending resources. Needs Correction, Rejected, Withdrawn, Hidden, Restricted, Removed, and Replaced resources must not enter general repository inquiry; Rejected, Withdrawn, Restricted, Removed, Replaced, private, unauthorized, and otherwise ineligible resources must not enter new AI processing, while Hidden resources are excluded from new public-facing AI processing while Hidden. Previously generated output remains governed by the accepted lifecycle and restricted-visibility rules rather than being treated as automatically public or permanently usable.
- Activity logs are restricted per Section 3 and Section 6/7: Moderators see moderation/report-related entries only; Admins see the full log.

---

## 13. Scope Boundaries

Per PROJECT_BRIEF.md, no role in this document may be extended to perform:
- Conducting or managing online classes
- Creating, taking, or grading quizzes or exams
- Recording grades or maintaining gradebooks
- Tracking attendance
- Managing enrollment
- Submitting or checking assignments
- Maintaining teacher class records
- Hosting or joining video meetings
- General classroom management
- Social-media-style features (feeds, direct messaging, follow/follower systems)

If any future feature request would require a role to perform one of the above, it must be flagged as an LMS-scope-creep risk and rejected or escalated for explicit re-scoping — not quietly added because a role "could" support it.

---

## 14. Notes for Future Documents

- **WORKFLOWS.md must treat the entire system as login-required.** Every resource-access workflow (browse, search, view, download, bookmark, report, upload, AI-assisted semantic search, repository-grounded inquiry, and related-resource suggestions) begins from an authenticated session; there is no anonymous/guest path to design for. The only pre-login screens are the login page, the Student registration page, and basic public information pages.
- **WORKFLOWS.md** must define the exact state machine for resource status, including Pending, Needs Correction, Approved, Rejected, Withdrawn, Hidden, Restricted, Removed, and Replaced. It must also define the linked replacement-record path for correcting an Approved resource and where each level of AI processing from Section 11 is permitted.
- **DATABASE_DESIGN.md** must encode role as a single authoritative field (not a set of boolean flags), and must enforce that Admin and Moderator remain distinct role values, not a shared "staff" role. It must also reflect that only Student/Teacher roles can be an uploader of record for a resource.
- **AI_FEATURES.md must define the Pending-resource AI-assistance gate** before implementation, including successful basic upload validation, the exact clear uploader notice, acknowledgment handling, the processing trigger, and the behavior when acknowledgment is not provided. Declining or withholding acknowledgment must not block the ordinary non-AI upload workflow.
- **WORKFLOWS.md and AI_FEATURES.md must define the exact workflow, live-eligibility checks, and processing-readiness gating** for semantic content search, repository-grounded inquiry, source-resource attribution, session-scoped follow-up, and related-resource suggestions, consistent with `DECISIONS.md` D041–D042. They must preserve Approved-resource-only general inquiry and must not grant Moderator/Admin an elevated general-inquiry path over non-public resources.
- **AI_FEATURES.md must resolve the AI-output visibility questions this document leaves open**, including whether duplicate/similarity indicators are visible to an uploader before moderation, whether any uploader-facing explanation related to an AI moderation hint is shown while the moderation-assistance surface remains staff-oriented, and the exact point at which a suggested tag or metadata value may be written into stored resource metadata at each permitted lifecycle stage. This document intentionally does not settle those implementation details.
- **SECURITY_NOTES.md** must confirm that every permission in Section 3 is enforced server-side, not only hidden in the UI. It must also preserve D040's Removed-resource rules: Admin-only accountability/reference access, exact descriptive-field sanitization, controlled-tag-association removal, file and AI-output unavailability, retained historical relationships, and the distinction that the minimized record is not anonymized.

**Potential Future Role (not part of v1.0 — flagged only):**
- **Content Verifier / Subject-Matter Reviewer** — a role distinct from Moderator, responsible for verifying academic *accuracy* rather than policy/safety compliance. PROJECT_BRIEF.md's Limitations section already states the system does not verify correctness of uploaded content; if the institution later wants subject-expert sign-off (e.g., a department head confirming a reviewer is academically sound), that would require a new role with narrower, subject-scoped authority. This is not needed for v1.0 and should not be added without a separate scope discussion.