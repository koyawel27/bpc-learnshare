# WORKFLOWS.md

**Project:** BPC LearnShare — AI-Assisted Collaborative Academic Resource Sharing and Management System
**Version:** Draft v1.0
**Last Updated:** 2026-07-12
**Author:** Nepthalie Jezer B. Macaslang
**Course:** BS Information Systems — Bulacan Polytechnic College
**Status:** Draft v1.0 — accepted workflow baseline through D042

---

## 1. Purpose of the Document

This document defines the concrete workflows for BPC LearnShare v1.0. It explains how the system behaves when users register, log in, upload resources, moderate submissions, access approved resources, report resources, and handle status changes.

Where `USER_ROLES.md` defines **who is allowed to do what**, this document defines **how those actions happen step by step**. Each workflow identifies the actor, required preconditions, user actions, server-side validations, system responses, and possible outcomes.

This document does not:

* Define database tables, fields, indexes, or SQL statements. Those belong in `DATABASE_DESIGN.md`.
* Define AI prompts, model settings, API details, or detailed AI trigger logic. Those belong in `AI_FEATURES.md`.
* Define visual UI design, screen layout, or final wording of interface messages.
* Introduce any role, module, or major feature outside `PROJECT_BRIEF.md`, `USER_ROLES.md`, and `DECISIONS.md`.

The workflows in this document are written for the v1.0 capstone MVP only. Production deployment workflows, advanced institutional onboarding, and future expansion features are outside this document unless explicitly marked as deferred notes.

---

## 2. Workflow Notation and Rules

Each workflow follows this structure:

* **Actor** — the user or system entity that starts the workflow.
* **Preconditions** — what must already be true before the workflow can begin.
* **Steps** — numbered actions and validations.
* **Outcomes** — the result of the workflow, including success, failure, or handoff to another workflow.

Workflow steps use these labels:

* **User Action** — an action performed by a human user, such as submitting a form or selecting an action button.
* **System Validation** — a server-side check performed before the system allows a state change or data access.
* **System Response** — what the system does after validation, such as creating a record, rejecting the request, redirecting the user, or showing an error.

Additional notation rules:

1. **Server-side validation is always required.** UI hiding is never enough to enforce permissions.
2. **Resource status names are capitalized consistently** and must come from the status definitions in Section 7.
3. **Account status and resource status are separate.** An account may be Active or Disabled, while a resource may be Pending, Approved, Hidden, Removed, and so on.
4. **Database field names are not finalized here.** This document may describe required behavior, but exact fields and relationships belong in `DATABASE_DESIGN.md`.
5. **AI behavior is described only at workflow level.** Detailed AI prompts, model behavior, and storage mechanics belong in `AI_FEATURES.md`, `DATA_PRIVACY.md`, and `DATABASE_DESIGN.md`.

---

## 3. Global Workflow Rules

These rules apply to all workflows in BPC LearnShare v1.0.

### 3.1 Login-required access

BPC LearnShare requires users to log in before browsing, searching, viewing, downloading, bookmarking, reporting, or uploading resources.

Unauthenticated visitors may access only:

1. The login page.
2. The Student registration page.
3. Basic public information pages, if included.

There is no public logged-out resource catalog in v1.0.

### 3.2 Server-side permission checks

Every permission must be enforced on the server side. This includes:

* page access,
* form submission,
* file viewing,
* file downloading,
* upload actions,
* moderation actions,
* account management actions,
* AI-related actions,
* report handling actions.

The interface may hide buttons or menu items from unauthorized users, but that is only a usability improvement. A direct URL request or manipulated form submission must still be rejected server-side.

### 3.3 Role boundaries

The system uses four v1.0 roles:

* Student
* Teacher/Instructor
* Moderator
* Admin

No workflow may introduce a new role. Any future role, such as a Content Verifier or Subject-Matter Reviewer, must be treated as a future scope decision and not added quietly to v1.0 workflows.

### 3.4 Core workflows must work without AI

AI is optional, assistive, and configurable at runtime. No core workflow may fail only because AI is disabled, unavailable, unconfigured, rate-limited, failing, or unreachable.

If an AI-assisted step is unavailable, the system must continue through the non-AI path. The affected user must receive a clear, non-technical message that the AI-assisted feature is temporarily unavailable rather than a raw error. AI failure or unavailability must never change a resource's status, undo a successful non-AI action, or block login, upload, moderation, metadata search/filtering, resource access, bookmarks, Helpful marks, reports, notifications, or Admin/Moderator management.

This runtime optionality is separate from build scope. Under `DECISIONS.md` D041–D042, the completed v1.0 capstone prototype is required to implement and demonstrate the minimum AI capability package defined in this document. "Optional" in this section describes how AI behaves while running; it does not mean the required package may be omitted from the completed system.

### 3.5 Human decision authority

AI must never approve, reject, publish, validate, Hide, Restrict, Remove, delete, or otherwise change a resource's status automatically. AI must not automatically create or manage taxonomy values. Any moderation decision must be made by an authorized human Moderator or Admin, and any action remains limited to that role's existing permissions.

### 3.6 Audit logging

The system must log important moderation and administration actions, including:

* approval,
* rejection,
* request for correction,
* hide/restrict actions,
* removal actions,
* account creation or disabling,
* role assignment or role changes,
* taxonomy add/edit/deactivate/reactivate actions,
* important AI-related actions if AI is enabled.

The exact audit log table design belongs in `DATABASE_DESIGN.md`.

### 3.7 No LMS scope creep

No workflow in this document may add:

* online classes,
* quizzes or exams,
* grading,
* attendance,
* enrollment,
* assignment submission/checking,
* teacher class records,
* video meetings,
* social-media-style feeds or messaging.

BPC LearnShare remains a resource-sharing, organization, discovery, moderation, and management platform.

---

## 4. Authentication and Access Workflow

### 4.1 Account status definitions

User accounts have an account status separate from resource status.

| Account Status | Meaning                                                                                                       |
| -------------- | ------------------------------------------------------------------------------------------------------------- |
| **Active**     | The account can log in and use the system according to its role.                                              |
| **Disabled**   | The account exists but cannot log in. It remains in the database for accountability and historical reference. |

Disabling an account affects login access only. It does not automatically hide, restrict, remove, or replace resources previously approved from that account. Approved resources remain visible unless a Moderator or Admin separately changes the resource status through the resource workflow.

### 4.2 Unauthenticated access boundary

**Actor:** Unauthenticated Visitor
**Preconditions:** No active logged-in session.

Allowed unauthenticated access:

1. Login page.
2. Student registration page.
3. Basic public information pages, if included.

Disallowed unauthenticated access:

* resource browsing,
* resource search,
* resource viewing,
* resource download,
* bookmarks,
* reports,
* upload page,
* moderation dashboard,
* admin dashboard,
* direct file links,
* AI-assisted semantic content search, repository-grounded inquiry, related-resource suggestions, and other protected AI-assisted features.

**Workflow:**

1. **User Action** — Visitor attempts to access a protected page or resource.
2. **System Validation** — System checks whether a valid authenticated session exists.
3. **System Response** — If no valid session exists, the request is rejected and the visitor is redirected to the login page.
4. **System Response** — For protected API/AJAX requests, the system returns an access-denied response instead of processing the action.

### 4.3 Login workflow

**Actor:** Student, Teacher/Instructor, Moderator, or Admin
**Preconditions:** Actor has an existing account and is not currently logged in.

**Steps:**

1. **User Action** — User opens the login page.
2. **User Action** — User submits login credentials.
3. **System Validation** — System checks whether the submitted identifier exists.
4. **System Validation** — System verifies the submitted password against the stored password hash.
5. **System Validation** — System checks whether the account status is Active.
6. **System Response — Success** — System creates an authenticated session and redirects the user to the appropriate area based on role.
7. **System Response — Failure** — If the identifier is invalid, the password is incorrect, or the account is Disabled, the system shows a generic login failure message.

For v1.0, Disabled accounts receive the same generic failure message as invalid credentials. This reduces account-status disclosure and keeps the login behavior simple.

### 4.4 Role-based landing after login

After successful login:

| Role                   | Suggested Landing Area                           |
| ---------------------- | ------------------------------------------------ |
| **Student**            | Resource browse/search page or Student dashboard |
| **Teacher/Instructor** | Resource browse/search page or Teacher dashboard |
| **Moderator**          | Moderation queue or Moderator dashboard          |
| **Admin**              | Admin dashboard                                  |

Exact UI routing may be adjusted during implementation, but access must still follow the role permissions in `USER_ROLES.md`.

### 4.5 Logout workflow

**Actor:** Any authenticated user
**Preconditions:** User has an active session.

**Steps:**

1. **User Action** — User selects logout.
2. **System Response** — System destroys the session.
3. **System Response** — User is redirected to the login page.

### 4.6 Session expiration

Sessions should expire after inactivity to reduce risk from unattended logged-in devices.

When a session expires:

1. The user is treated as unauthenticated.
2. Protected requests are rejected.
3. The user is redirected to the login page or shown an access-denied response for API/AJAX requests.

The exact timeout value and session security details belong in `SECURITY_NOTES.md`.

---

## 5. Student Self-Registration Workflow

**Actor:** Unauthenticated Visitor
**Preconditions:** None. Student self-registration is enabled and guaranteed-on in v1.0.

### 5.1 Registration rules

Only Student accounts can be created through public self-registration.

Teacher/Instructor, Moderator, and Admin accounts cannot self-register. They must be created or assigned by an Admin through the Admin account provisioning workflow.

The Student registration workflow must never accept a role value from the submitted form. The system must assign the Student role server-side.

### 5.2 Student registration workflow

**Steps:**

1. **User Action** — Visitor opens the Student registration page.
2. **User Action** — Visitor submits required registration information.
3. **System Validation** — System checks that required fields are present.
4. **System Validation** — System checks that the chosen account identifier is not already in use.
5. **System Validation** — System checks that the password meets the minimum password requirement.
6. **System Validation** — System forces the role value to Student on the server side.
7. **System Response — Success** — System creates a new Active Student account.
8. **System Response — Success** — User is redirected to the login page and must log in manually.
9. **System Response — Failure** — If validation fails, the system shows a validation error and does not create the account.

### 5.3 Minimum registration data

Exact registration fields belong in `DATABASE_DESIGN.md`, but the workflow requires at least:

* an account identifier,
* password,
* basic student profile information required by the final database design.

The registration form may later include fields such as name, course/program, year level, section, or institutional email if these are required by the final data model.

### 5.4 Duplicate account handling

If the submitted identifier is already in use:

1. The system rejects the registration.
2. The system shows a generic duplicate-account validation message.
3. The system must not reveal whether the identifier belongs to a Student, Teacher/Instructor, Moderator, or Admin account.

### 5.5 Email verification

Email verification is not required for v1.0. It may be considered as future hardening if the project later moves toward a wider pilot or production deployment.

---

## 6. Admin Account Provisioning Workflow

**Actor:** Admin
**Preconditions:** Actor is logged in as an Active Admin account.

This workflow is used for creating and managing Teacher/Instructor, Moderator, and Admin accounts after the first Admin account already exists.

### 6.1 First Admin setup boundary

The first Admin account cannot be created through the normal in-app Admin provisioning workflow because that workflow requires an existing Admin.

For v1.0, the first Admin account is created during initial setup only, outside the normal application workflow. The recommended approach is a one-time database seed or manual setup step during local XAMPP setup.

Rules for first Admin creation:

1. There must be no public “create first Admin” registration page.
2. There must be no permanently reachable setup endpoint for creating Admin accounts.
3. Exact setup steps belong in `BUILD_PLAN.md`.
4. Security handling for setup credentials belongs in `SECURITY_NOTES.md`.
5. After the first Admin exists, all Teacher/Instructor, Moderator, and additional Admin accounts must be created or assigned by an existing Admin.

This keeps first Admin creation as a setup/bootstrap procedure, not an ordinary user-facing workflow.

### 6.2 Create Teacher/Instructor, Moderator, or Admin account

**Actor:** Admin
**Preconditions:** Admin is logged in and has access to account management.

**Steps:**

1. **User Action** — Admin opens the account management area.
2. **User Action** — Admin selects “Create account” or equivalent action.
3. **User Action** — Admin enters required account details and selects one of the allowed roles: Teacher/Instructor, Moderator, or Admin.
4. **System Validation** — System checks that the acting user is an Active Admin.
5. **System Validation** — System checks that required account fields are present.
6. **System Validation** — System checks that the account identifier is not already in use.
7. **System Validation** — System rejects any role value outside the allowed v1.0 role list.
8. **System Response — Success** — System creates the account with the selected role and Active status.
9. **System Response — Failure** — If validation fails, the system shows an error and does not create the account.

The exact onboarding method for the new account, such as temporary password or manual password setting, belongs in `SECURITY_NOTES.md` and `BUILD_PLAN.md`.

### 6.3 Change an account role

**Actor:** Admin
**Preconditions:** Admin is logged in and the target account exists.

**Steps:**

1. **User Action** — Admin selects an existing account.
2. **User Action** — Admin changes the account role.
3. **System Validation** — System checks that the acting user is an Active Admin.
4. **System Validation** — System checks that the new role is one of the allowed v1.0 roles.
5. **System Response — Success** — System updates the account role.
6. **System Response — Success** — System logs the role-change action.
7. **System Response — Failure** — If validation fails, the system rejects the change.

Role changes must be handled carefully because they affect permissions immediately.

### 6.4 Disable or re-enable an account

**Actor:** Admin
**Preconditions:** Admin is logged in and the target account exists.

**Steps:**

1. **User Action** — Admin selects an existing account.
2. **User Action** — Admin disables or re-enables the account.
3. **System Validation** — System checks that the acting user is an Active Admin.
4. **System Response — Success** — System updates the account status to Disabled or Active.
5. **System Response — Success** — System logs the account-status change.
6. **System Response — Failure** — If validation fails, the system rejects the change.

Disabling an account prevents that user from logging in. It does not automatically affect resources previously uploaded by that account. If a resource needs to be hidden, restricted, removed, or reviewed, that must be handled separately through the resource workflow.

---

## 6A. Admin Taxonomy Management Workflow

**Actor:** Admin  
**Preconditions:** Actor is logged in as an Active Admin account.

This workflow is used to manage the controlled academic metadata values used during upload, moderation, browsing, and filtering.

Admin may manage:

* courses/programs,
* subjects,
* year levels,
* resource types,
* controlled tags.

These values are controlled system options. They are not ordinary user-generated free-text tags.

### 6A.1 Add taxonomy value

1. **User Action** — Admin opens the taxonomy management area.
2. **User Action** — Admin selects the taxonomy type to manage, such as course/program, subject, year level, resource type, or controlled tag.
3. **User Action** — Admin enters the new value name.
4. **System Validation** — System checks that the actor is logged in as an Active Admin.
5. **System Validation** — System checks that the value name is not empty.
6. **System Validation** — System checks that the same value does not already exist in the selected taxonomy type.
7. **System Response — Success** — System creates the taxonomy value as Active.
8. **System Response — Success** — System logs the taxonomy creation action.
9. **System Response — Failure** — If validation fails, the system rejects the request and shows a validation error.

### 6A.2 Edit taxonomy value

1. **User Action** — Admin selects an existing taxonomy value.
2. **User Action** — Admin edits the value name.
3. **System Validation** — System checks that the actor is logged in as an Active Admin.
4. **System Validation** — System checks that the new value name is not empty.
5. **System Validation** — System checks that the edited name does not duplicate another value in the same taxonomy type.
6. **System Response — Success** — System updates the taxonomy value.
7. **System Response — Success** — System logs the taxonomy edit action.
8. **System Response — Failure** — If validation fails, the system rejects the request and shows a validation error.

### 6A.3 Deactivate taxonomy value

Taxonomy values already used by resources should not be hard-deleted in v1.0.

Instead, Admin may deactivate a value.

1. **User Action** — Admin selects an existing taxonomy value.
2. **User Action** — Admin chooses Deactivate.
3. **System Validation** — System checks that the actor is logged in as an Active Admin.
4. **System Response — Success** — System marks the taxonomy value as Inactive.
5. **System Response — Success** — The inactive value no longer appears as a selectable option for new uploads.
6. **System Response — Success** — Existing resources that already reference the value remain valid and keep their historical metadata.
7. **System Response — Success** — System logs the taxonomy deactivation action.

### 6A.4 Reactivate taxonomy value

1. **User Action** — Admin selects an inactive taxonomy value.
2. **User Action** — Admin chooses Reactivate.
3. **System Validation** — System checks that the actor is logged in as an Active Admin.
4. **System Response — Success** — System marks the taxonomy value as Active again.
5. **System Response — Success** — The value becomes available again for new uploads.
6. **System Response — Success** — System logs the taxonomy reactivation action.

### 6A.5 Taxonomy management limits

Taxonomy management must not create new roles, resource statuses, report statuses, LMS features, or new workflow modules outside the accepted scope.

For v1.0:

* subjects remain a flat Admin-managed lookup;
* subjects are not scoped to specific courses/programs;
* topic remains uploader-entered resource metadata, not an Admin-managed taxonomy table;
* AI-suggested tags or metadata values do not create, modify, deactivate, or reactivate taxonomy values automatically; only Admin may manage taxonomy values, and any newly required controlled value must first be added through the accepted Admin taxonomy workflow before it can be used as a resource taxonomy assignment;
* taxonomy changes do not automatically modify existing resource status, moderation history, report history, bookmarks, Helpful marks, or AI outputs.


---

## 6B. Admin AI Configuration Workflow

**Actor:** Admin  
**Preconditions:** Actor is logged in as an Active Admin account.

This workflow governs Admin's system-level authority to enable, disable, or reconfigure AI-assisted capabilities. It does not define provider-specific configuration screens, exact settings fields, or final architecture. Those remain for `AI_FEATURES.md`, `BUILD_PLAN.md`, and the later architecture/schema decision.

1. **User Action** — Admin opens system settings.
2. **User Action** — Admin enables, disables, or adjusts an AI-assisted capability, such as Pending-file assistance, semantic search, repository-grounded inquiry, related-resource suggestions, or — once implemented — duplicate/similarity indicators or moderation hints.
3. **System Validation** — System checks that the actor is still an Active Admin.
4. **System Response — Success** — System updates the applicable setting and logs the change under Section 3.6.
5. **System Response — Scope** — Disabling one AI-assisted capability affects only that capability. It does not disable unrelated AI capabilities and does not disable any non-AI workflow.
6. **System Response — Re-enabling** — Re-enabling a previously disabled AI capability does not immediately treat all resources as eligible. Normal live eligibility, processing-readiness, and source-freshness checks in Sections 18A–18E and 19 still apply before the capability is used on a specific resource.
7. **System Response — No silent exposure** — A configuration change must not expose a resource, file, AI output, or retrieval-derived data that current resource-status, permission, file-availability, readiness, freshness, or lifecycle rules would otherwise block.
8. **System Response — Deliverable status unaffected** — Disabling an AI capability at runtime is a normal supported action. It does not remove the requirement that the completed v1.0 capstone prototype implement and demonstrate the required AI package under D041–D042; that requirement concerns what is built, not whether Admin may temporarily disable a capability during ordinary operation.

Exact per-capability settings, provider/model configuration, API-key handling, and reprocessing controls belong to `SECURITY_NOTES.md`, `AI_FEATURES.md`, and `BUILD_PLAN.md`, not this workflow.

---

## 7. Resource Status Definitions

Every resource has exactly one resource status at a time. Resource status controls visibility, searchability, moderation state, and the actions available to users.

### 7.1 Canonical resource statuses

| Status               | Meaning                                                                                        | Discoverable in Normal Browse/Search | Visible To                                                       | Who Can Set It                                 |
| -------------------- | ---------------------------------------------------------------------------------------------- | -----------------------------------: | ---------------------------------------------------------------- | ---------------------------------------------- |
| **Pending**          | A newly uploaded resource or corrected replacement awaiting moderation review.                 |                                   No | Uploader, Moderator, Admin                                       | System on upload or resubmission               |
| **Needs Correction** | A Moderator/Admin reviewed the resource and found issues that the uploader may correct.        |                                   No | Uploader, Moderator, Admin                                       | Moderator, Admin                               |
| **Approved**         | Resource passed moderation and is available to authenticated users.                            |                                  Yes | All authenticated active users                                   | Moderator, Admin                               |
| **Rejected**         | Resource failed moderation and will not be published as submitted.                             |                                   No | Uploader, Moderator, Admin                                       | Moderator, Admin                               |
| **Withdrawn**        | Uploader withdrew their own resource while it was Pending, Needs Correction, or Rejected.       |                                   No | Uploader, Moderator, Admin                                       | Uploader (self-service)                        |
| **Hidden**           | Temporary moderation hold while a report or concern is being investigated.                     |                                   No | Uploader, Moderator, Admin                                       | Moderator, Admin                               |
| **Restricted**       | Longer-term limited-access state after review. Resource is retained but not broadly available. |                                   No | Uploader, Moderator, Admin                                       | Moderator, Admin                               |
| **Removed**          | Resource is withdrawn from the system by Admin action.                                         |                                   No | No normal user access; D040-minimized Admin accountability/reference data remains | Admin only                                     |
| **Replaced**         | Original Approved resource whose corrected replacement has been Approved.                      |                                   No | Uploader, Moderator, Admin                                       | System, triggered when replacement is Approved |

### 7.2 Pending

A resource is **Pending** when it has been submitted but has not yet received a moderation decision.

Pending applies to:

* new Student uploads,
* new Teacher/Instructor uploads,
* corrected replacement uploads for previously Approved resources,
* resubmitted resources after a Needs Correction decision.

Pending resources are not discoverable in normal browse/search and are not visible to general authenticated users.

### 7.3 Needs Correction

A resource is **Needs Correction** when a Moderator or Admin determines that the submission may be acceptable after changes.

Examples:

* missing or unclear description,
* incorrect metadata,
* wrong subject or category,
* minor file issue that the uploader can fix,
* unclear title or tags.

Needs Correction is not the same as Rejected. It gives the uploader a chance to correct and resubmit the same pending resource for another moderation review.

### 7.4 Approved

A resource is **Approved** when it has passed moderation and is available to authenticated active users.

Only Approved resources appear in normal browse/search results.

Approved resources may be:

* viewed,
* downloaded,
* bookmarked,
* marked helpful,
* reported,
* processed by approved-resource AI features if AI is enabled.

Uploaders cannot directly edit an Approved resource. Corrections to Approved resources use the linked replacement-record workflow.

### 7.5 Rejected

A resource is **Rejected** when it fails moderation and will not be published as submitted.

Rejected resources are not visible to general authenticated users and do not appear in normal browse/search.

Rejected resources remain visible to the uploader, Moderator, and Admin for review/history purposes unless later withdrawn or removed through an allowed workflow.

AI must not process Rejected resources.

### 7.6 Withdrawn

A resource is **Withdrawn** when its uploader removes it from consideration before it becomes publicly Approved.

Withdrawn may apply only to resources with status Pending, Needs Correction, or Rejected. It must not be used for Approved, Hidden, Restricted, Removed, or Replaced resources.

Withdrawn resources are not visible in normal browse/search and are not available to general authenticated users. File content and draft AI outputs should be deleted or invalidated. A minimal history/audit record may remain so the system can show that the uploader withdrew the resource.

Withdrawn is different from Removed. Withdrawn is initiated by the uploader for their own non-public resource. Removed is an Admin-only action for withdrawing a resource from the system.

Unlike Removed resources, Withdrawn resources are not subject to D040 sanitization. Their original title, description, topic, original filename, and controlled-tag associations remain unchanged on the resource record. File content and draft AI outputs are still deleted or invalidated according to D020.

### 7.7 Hidden

A resource is **Hidden** when it is temporarily removed from normal browse/search while a concern is being reviewed.

Hidden is used as a temporary moderation hold, commonly after:

* a report is submitted,
* a possible policy issue is discovered,
* a possible duplicate or inappropriate resource needs investigation,
* a Moderator/Admin needs to prevent broad access while reviewing the concern.

Hidden is reversible. After review, the resource may return to Approved, move to Restricted, or be Removed by Admin if necessary.

### 7.8 Restricted

A resource is **Restricted** when it has been reviewed and should no longer be broadly available to all authenticated users, but should still be retained for uploader/staff visibility and audit/reference.

Restricted is different from Hidden:

* **Hidden** is temporary and investigative.
* **Restricted** is a longer-term limited-access outcome after review.

In v1.0, Restricted resources are not discoverable in normal browse/search and are visible only to the uploader, Moderator, and Admin unless a future decision defines a more detailed access model.

### 7.9 Removed

A resource is **Removed** when an Admin withdraws it from the system as a terminal administrative action.

Removed resources are not visible to normal users, including the original uploader. The resource row remains to preserve accountability, referential integrity, reports, action history, and other accepted historical relationships.

Under D040, the removal operation minimizes the retained resource data by:

* changing `title` to `[Removed resource]`;
* changing `description` to `[Removed resource]`;
* changing `topic` to `[Removed]`;
* changing `original_filename` to `[removed]`;
* deleting all `resource_tags` rows linked to the resource;
* deleting or invalidating file content and AI outputs according to their confirmed lifecycle rules.

The retained record may still contain an uploader reference, required course/subject/year-level/resource-type references, technical file metadata, activity counts, AI-notice acknowledgment fields, timestamps, and historical relationships. It is therefore minimized within the accepted v1.0 schema but is not anonymized.

Removed-resource access is Admin-only for accountability/reference purposes. Resource removal is Admin-only and has no reversal workflow in v1.0.

### 7.10 Replaced

A resource is **Replaced** when it was previously Approved, but a corrected replacement resource has also been Approved.

The Replaced resource:

* does not appear in normal browse/search,
* is not visible to general authenticated users,
* remains visible to the uploader, Moderator, and Admin for history, audit, and reference,
* must not share or reuse AI outputs with the replacement resource.

The replacement resource becomes the current visible Approved resource.

### 7.11 Correction mechanics clarification

There are two different correction paths:

1. **Correction before approval**
   If a resource is Pending or Needs Correction, the uploader may edit and resubmit the same resource. No new resource record is created.

2. **Correction after approval**
   If a resource is already Approved, the uploader cannot edit it directly. A corrected upload creates a new Pending replacement resource linked to the original Approved resource. The original remains Approved and visible while the replacement is Pending.

If the replacement is Approved, the original becomes Replaced. If the replacement is Rejected, the original remains Approved and unchanged.

### 7.12 Status rules summary

* Only Approved resources are broadly visible and searchable by authenticated users.
* Pending, Needs Correction, Rejected, Withdrawn, Hidden, Restricted, and Replaced resources are not visible in normal browse/search.
* Removed resources are not visible to normal users or the uploader.
* Moderator and Admin can view non-public resources as part of review and management.
* Admin-only removal is separate from Moderator/Admin hide or restrict actions.
* AI processing must follow the resource status and eligibility rules in `DECISIONS.md`.

---

<!-- Sections 8–17: Core Resource Lifecycle -->

## 8. Resource Upload Workflow

**Actor:** Student or Teacher/Instructor only
**Preconditions:** Actor is logged in with an Active Student or Teacher/Instructor account.

Moderator and Admin accounts must not initiate ordinary resource uploads in v1.0. Their role is to review, manage, restrict, remove, configure, or administer resources submitted by Student and Teacher/Instructor accounts.

### 8.1 Upload workflow steps

1. **User Action** — Student or Teacher/Instructor opens the upload form.
2. **User Action** — User selects a file and enters required metadata, such as title, description, course/program, subject, year level, topic, resource type, and tags.
3. **System Validation** — System checks that the actor is logged in.
4. **System Validation** — System checks that the actor’s role is Student or Teacher/Instructor.
5. **System Validation** — System rejects the request if the actor is Moderator or Admin, even if the request was submitted through a direct URL, manipulated form, or API/AJAX call.
6. **System Validation** — System checks that required metadata fields are present.
7. **System Validation** — System checks that a file is attached.
8. **Handoff** — The uploaded file is passed to Section 9 — Basic Upload Validation Workflow.
9. **System Response — Success** — If upload validation passes, the system creates a new resource record with status **Pending**, linked to the uploader’s account.
10. **System Response — Success** — The Pending resource enters the moderation queue.
11. **System Response — Optional** — If AI is enabled and the upload meets the AI notice and eligibility gate, the Pending resource may also pass through Section 10 — Optional Pending-File AI Assistance Workflow.
12. **System Response — Failure** — If required metadata is missing, the file is missing, or the actor is not allowed to upload, the system rejects the request and creates no resource record.

### 8.2 Upload status rule

Every normal upload creates a **Pending** resource. No role can upload directly into Approved status, including Teacher/Instructor.

Teacher/Instructor uploads must go through the same moderation queue as Student uploads.

---

## 9. Basic Upload Validation Workflow

This workflow is the file-safety gate for all uploaded resources. A file must pass this workflow before the resource can be created as Pending and before any Pending-file AI processing may occur.

**Actor:** System
**Preconditions:** A file has been submitted through the Resource Upload Workflow.

### 9.1 Allowed file types

For v1.0, the allowed academic file types are:

* PDF
* DOCX
* PPTX
* TXT
* JPG/JPEG
* PNG

Image files are included because students may realistically upload photographed handwritten notes or visual learning materials. However, image-only resources may not be searchable by extracted text unless OCR or AI vision support is added later. This limitation should be reflected in `AI_FEATURES.md` and `TESTING_CHECKLIST.md`.

Executable, script, installer, and archive file types must not be accepted.

Examples of disallowed file types include:

* EXE
* BAT
* CMD
* JS
* PHP
* HTML
* ZIP
* RAR
* 7Z
* APK

### 9.2 Validation steps

1. **System Validation — Extension check** — System checks the file extension against the allowed list.
2. **System Validation — MIME/content check** — System checks that the file’s actual content type matches the claimed extension.
3. **System Validation — File size check** — System checks that the file does not exceed the configured maximum file size.
4. **System Validation — Empty/corrupt file check** — System rejects empty files and files that cannot be read safely.
5. **System Response — Storage** — If all checks pass, the system stores the file using a randomized, non-guessable filename.
6. **System Response — Storage** — Uploaded files should be stored outside the public web root or protected from direct unauthenticated access.
7. **System Response — Metadata** — The original filename may be stored for display, but it must not be used as the physical storage path.
8. **System Response — Success** — The file is allowed to proceed to Pending resource creation.
9. **System Response — Failure** — If any validation fails, the upload attempt is rejected and no resource record is created.

### 9.3 Failed validation logging

Missing metadata or ordinary form validation errors do not need to be logged as security events.

However, risky file validation failures should be logged at a minimal level, especially:

* disallowed executable/script file attempts,
* MIME/content mismatch,
* repeated rejected file uploads from the same account,
* unusually large file attempts,
* suspicious file names or extensions.

The log should store only what is needed for accountability and security review, such as actor ID, timestamp, validation failure category, and attempted file extension. The rejected file itself should not be stored.


---

## 10. Optional Pending-File AI Assistance Workflow

Pending-file AI assistance is optional, assistive, and non-authoritative at runtime. It is separate from the required Approved-resource semantic search and repository-grounded inquiry workflows in Sections 18A–18E. This workflow may run only after the file has passed basic upload validation and the uploader has received and acknowledged the required notice.

**Actor:** System  
**Preconditions:**

* Resource exists with status Pending.
* File passed Section 9 — Basic Upload Validation Workflow.
* The applicable Pending-file AI capability is enabled and configured.
* Uploader received a clear AI-processing notice for the upload and acknowledged it before AI processing began.

### 10.1 AI notice and acknowledgment gate

Pending-file AI processing may occur only after all three gates pass:

1. the file passed successful basic upload validation;
2. the uploader received a clear notice before AI processing began;
3. the uploader acknowledged that notice.

The notice must explain, in clear user-facing language, that eligible uploaded-file content may be processed locally and/or sent to an external AI provider, depending on the later selected architecture, for assistive features such as:

* draft summary;
* suggested controlled tags;
* suggested metadata values;
* authorized review assistance.

The notice must not describe acknowledgment as legal consent or imply that acknowledgment independently resolves every lawful-basis, institutional-authorization, provider-review, or privacy-governance requirement.

If the uploader declines or does not acknowledge the AI-processing notice:

* the ordinary non-AI upload still proceeds;
* the resource may still be created as Pending and enter moderation;
* Pending-file AI processing is skipped;
* the uploader is not treated as having failed or cancelled the upload.

The exact notice wording, acknowledgment control, and acknowledgment-scope/reset behavior belong in `AI_FEATURES.md`, subject to the accepted privacy requirements.

### 10.2 AI assistance steps

1. **System Validation** — System checks whether the applicable Pending-file AI capability is enabled and configured.
2. **System Validation** — System checks that the resource is still Pending.
3. **System Validation** — System checks that the file successfully passed Section 9.
4. **System Validation** — System checks that the uploader received and acknowledged the required AI-processing notice.
5. **System Response — AI skipped** — If AI is disabled, unavailable, unconfigured, rate-limited, failing, or the notice was not acknowledged, the resource proceeds to moderation without AI output. Declining or not acknowledging the notice is not treated as a failed or cancelled upload; the ordinary non-AI upload workflow completes normally.
6. **System Response — AI processing** — If all conditions pass, the system processes only the eligible content through the configured local component and/or external provider under the accepted data-minimization and security rules.
7. **System Response — Draft output** — The system may create authorized draft output, such as a summary or suggested tags/metadata, linked only to the Pending resource.
8. **System Response — Visibility** — Draft AI output is visible only to the uploader for their own resource and to Moderator/Admin where current lifecycle and permissions allow.
9. **System Response — Planned enhancements** — Duplicate/similarity indicators and AI moderation hints are planned v1.0 enhancements governed by Section 10A. They are not required outputs of this Pending-file workflow.
10. **System Response — Failure isolation** — If AI processing is delayed or fails, the resource still proceeds to moderation. AI failure must not block upload or review, automatically Reject the upload, or change its moderation status.

### 10.3 AI output rule

Pending-resource AI output is draft, assistive, and non-authoritative.

A human uploader, Moderator, or Admin may review, edit, or discard AI-generated suggestions only where their current role, ownership, resource status, and lifecycle permissions allow.

AI output must not:

* automatically change final resource metadata;
* create, modify, deactivate, or reactivate taxonomy values;
* approve, reject, publish, validate, Hide, Restrict, Remove, delete, or otherwise change resource status;
* replace the independent review of a Moderator/Admin.

The exact lifecycle point at which a suggested tag or metadata value may be written into stored resource metadata remains for `AI_FEATURES.md` and the later detailed workflow decision.

### 10.4 AI reuse, refresh, and cost-control rule

AI must not be called repeatedly for an unchanged file when a valid current reusable output already exists and reuse is allowed by the accepted lifecycle.

If a Pending or Needs Correction resource is resubmitted with the same file and only metadata changes, existing output may remain reusable only when the changed metadata does not affect that output. Where changed metadata affects the relevant summary, suggestion, or retrieval representation, the affected derived data must be refreshed or treated as stale.

If the file itself changes:

* previous draft AI output must be discarded or invalidated as required by the lifecycle;
* extracted text, embeddings, index entries, or equivalent retrieval-derived data tied to the previous file become stale and must not be used;
* AI may run again only after the changed file passes basic upload validation and the applicable notice, acknowledgment, eligibility, lifecycle, and configuration requirements are satisfied.

Exact caching, dependency, fingerprinting, and refresh mechanics belong in `AI_FEATURES.md` and the later architecture/schema decision.

### 10.5 Acknowledgment scope [NEEDS CONFIRMATION]

Whether AI-notice acknowledgment applies to one specific uploaded file, one specific file version, or a broader scope is not yet settled by an accepted decision.

`DATA_PRIVACY.md` identifies a privacy-preferred direction of resetting acknowledgment when the uploaded file of a Pending or Needs Correction resource is replaced, but carries the exact implementation forward to `AI_FEATURES.md`.

This document does not resolve the acknowledgment scope or reset mechanism. `AI_FEATURES.md` must define it before Pending-file AI processing is implemented.

---

## 10A. Planned AI Enhancements: Duplicate/Similar-Resource Indicators and AI Moderation Hints

Per `DECISIONS.md` D042 Part B, the following are planned v1.0 AI enhancements targeted only after the required minimum AI package in Sections 10 and 18A–18E is stable. They are not equal-weight minimum defense blockers. Removing either from the intended v1.0 target later requires an explicit scope decision rather than silent omission.

### 10A.1 Duplicate/similar-resource indicators

* Duplicate/similarity indicators are non-authoritative review aids only.
* They must never automatically Reject, Hide, Restrict, Remove, merge, or definitively label a resource as duplicated.
* Moderator/Admin must independently review the resource and decide whether any action is appropriate under the existing moderation workflow.
* Processing must follow the applicable accepted AI context:
  * if the indicator is generated for a Pending resource, the file must first pass basic upload validation and the uploader-notice-and-acknowledgment gate;
  * if the indicator is generated for an Approved resource, the resource must pass the current Approved-resource eligibility, access, file-availability, readiness, and freshness checks.
* General repository inquiry eligibility must not be silently reused as the only eligibility rule for Pending-resource moderation assistance.
* **[NEEDS CONFIRMATION]** — The exact trigger timing and whether an uploader may see a duplicate/similarity indicator before Moderator/Admin review remain unresolved and are deferred to `AI_FEATURES.md` and the later detailed workflow decision.

### 10A.2 AI moderation hints

* AI moderation hints are staff-oriented, non-authoritative assistive information for Moderator/Admin review.
* They may flag possible content, metadata, similarity, or policy concerns but must never make or execute a moderation decision.
* Moderator/Admin must independently inspect the resource and apply only actions already permitted by the accepted role and resource-status model.
* If moderation hints process a Pending file, the accepted basic-validation, uploader-notice, and acknowledgment gate still applies.
* The hints must remain clearly distinguishable from findings entered or confirmed by a human Moderator/Admin.
* **[NEEDS CONFIRMATION]** — Whether any uploader-facing explanation is shown remains unresolved. Exact queue placement, severity scoring, action-button behavior, or automatic escalation is not authorized by this document.

Both planned enhancements remain subject to live account-status, role, permission, resource-status, file-availability, lifecycle, processing-readiness, and source-freshness checks appropriate to the specific AI context in which they are used.

---

## 11. Moderation Review Workflow

**Actor:** Moderator or Admin
**Preconditions:** Resource exists with status Pending. Actor is logged in as Moderator or Admin.

This workflow applies to:

* new Student uploads,
* new Teacher/Instructor uploads,
* resubmitted resources after Needs Correction,
* corrected replacement resources for previously Approved resources.

### 11.1 Review workflow steps

1. **User Action** — Moderator/Admin opens the moderation queue.
2. **System Response** — System lists resources with status Pending.
3. **User Action** — Moderator/Admin opens a Pending resource.
4. **System Response** — System displays the uploaded file, submitted metadata, uploader identity, and any available authorized draft AI output, including AI moderation hints and duplicate/similarity indicators where the planned enhancements in Section 10A have been implemented, are enabled, and are permitted for the current resource context.
5. **System Response** — If the Pending resource is a replacement for an existing Approved resource, the system clearly shows the original resource being replaced.
6. **User Action** — Moderator/Admin reviews the resource.
7. **User Action** — Moderator/Admin chooses one moderation decision:

   * Approve,
   * Reject,
   * Request Correction.
8. **System Validation** — System checks that the actor is still Moderator or Admin.
9. **System Validation** — System checks that the resource is still Pending.
10. **System Response** — System applies the selected moderation decision.
11. **System Response** — System logs the decision.

### 11.2 Approve decision

If the Moderator/Admin selects **Approve**:

1. Resource status becomes **Approved**.
2. Resource becomes visible in normal browse/search to authenticated active users.
3. Resource becomes eligible for Approved-resource AI features if AI is enabled.
4. If the Approved resource is a replacement resource, Section 17 rules also apply:

   * the original resource becomes Replaced,
   * the replacement becomes the current Approved resource.

### 11.3 Reject decision

If the Moderator/Admin selects **Reject**:

1. Moderator/Admin must provide a rejection reason or note.
2. Resource status becomes **Rejected**.
3. Resource does not appear in normal browse/search.
4. Resource remains visible to uploader, Moderator, and Admin for history/review.
5. Draft AI outputs tied to the resource must be deleted or invalidated according to the AI output lifecycle rules.
6. Decision is logged.

### 11.4 Request Correction decision

If the Moderator/Admin selects **Request Correction**:

1. Moderator/Admin must provide correction notes.
2. Resource status becomes **Needs Correction**.
3. Resource remains non-public.
4. Correction notes are shown to the uploader in the uploader’s upload/status area.
5. The uploader may revise and resubmit through Section 12 — Needs Correction Workflow.
6. Decision is logged.

Request Correction applies only to Pending resources under moderation. It must not be used as a direct status transition for already Approved resources.

### 11.5 Notification rule

For v1.0, moderation results are communicated through in-app status updates and notes visible in the uploader’s “My Uploads” or equivalent upload-management area.

Email notifications are not required in v1.0 and may be considered future hardening.

---

## 12. Needs Correction Workflow

**Actor:** Original uploader
**Preconditions:** Resource status is Needs Correction. Actor is logged in and is the resource uploader.

Needs Correction is a pre-approval correction path. It allows the uploader to revise the same non-public resource before it is approved or rejected.

### 12.1 Correction steps

1. **User Action** — Uploader opens their upload-management area.
2. **System Response** — System shows the resource status as Needs Correction and displays the Moderator/Admin correction notes.
3. **User Action** — Uploader edits metadata and/or replaces the file.
4. **System Validation** — System checks that the actor is the resource uploader.
5. **System Validation** — System checks that the resource status is Needs Correction.
6. **System Validation** — If the file is replaced, the new file passes through Section 9 — Basic Upload Validation Workflow.
7. **System Validation** — If only metadata is changed, required metadata fields are still checked.
8. **System Response — Success** — Resource status returns to Pending.
9. **System Response — Success** — Resource re-enters the moderation queue.
10. **System Response — AI and retrieval handling** — If the file changed, old draft AI output is discarded or invalidated. Any extracted text, embeddings, index entries, or equivalent retrieval-derived data associated with the previous file becomes stale and must not be used until the changed file is successfully reprocessed. AI may run again only when the applicable eligibility, notice, acknowledgment, lifecycle, and configuration requirements are satisfied.

    If only metadata changed, existing AI output or retrieval-derived data may remain usable only when the changed metadata does not affect that output or the relevant search/recommendation representation. Where the changed metadata affects the capability, the affected derived data must be refreshed or treated as stale. Exact dependency and refresh rules remain for `AI_FEATURES.md` and the later architecture decision.
11. **System Response — Failure** — If validation fails, the resource remains Needs Correction and no resubmission occurs.

### 12.2 Withdrawal of non-approved resources

A Student or Teacher/Instructor may withdraw their own resource only while it is:

* Pending,
* Needs Correction,
* Rejected.

Withdrawal is not allowed for Approved, Hidden, Restricted, Removed, or Replaced resources.

### 12.3 Withdrawal steps

1. **User Action** — Uploader selects Withdraw on their own eligible resource.
2. **System Validation** — System checks that the actor is the uploader.
3. **System Validation** — System checks that the resource status is Pending, Needs Correction, or Rejected.
4. **System Response — Success** — Resource status becomes **Withdrawn**.
5. **System Response — Success** — File content and draft AI outputs should be deleted or invalidated.
6. **System Response — Success** — A minimal history/audit record is retained.
7. **System Response — Failure** — If the actor is not the uploader or the resource status is not eligible for withdrawal, the request is rejected.

Withdrawn resources are not visible in normal browse/search and are not eligible for AI processing.

---

## 13. Approved Resource Access Workflow

**Actor:** Any authenticated Active user
**Preconditions:** User is logged in. Target resource status is Approved.

Approved resources are the only resources broadly available to authenticated users.

### 13.1 Browse/search workflow

1. **User Action** — User opens the resource browse/search page.
2. **User Action** — User searches or filters by metadata such as course, subject, year level, topic, resource type, or tags. Where enabled, the user may also use semantic content search under Section 18B or repository-grounded inquiry under Section 18D. These AI-assisted paths supplement and never replace metadata search/filtering, which remains independently available without AI.
3. **System Validation** — System checks that the user is authenticated and Active.
4. **System Response** — System returns only resources with status Approved.
5. **System Response** — Resources with status Pending, Needs Correction, Rejected, Withdrawn, Hidden, Restricted, Removed, or Replaced do not appear in normal browse/search.

### 13.2 View/download workflow

1. **User Action** — User opens an Approved resource detail page or selects Download.
2. **System Validation** — System checks that the user is authenticated and Active.
3. **System Validation** — System checks the resource’s current status at request time.
4. **System Response — Success** — If the resource is still Approved, the system displays or serves the resource according to the allowed access path.
5. **System Response — Activity count** — The system records view/download counts where applicable.
6. **System Response — Failure** — If the resource is no longer Approved, the system does not serve the file and shows an appropriate “resource unavailable” response.

Status must be checked at the time of each view/download request. A previously bookmarked or directly linked resource must not bypass current status rules.

### 13.3 Replaced resource access by old link

If a general authenticated user follows an old link to a Replaced resource:

1. The system must not serve the old file.
2. The system may show a message that the resource has been replaced.
3. The system may link or redirect to the current Approved replacement if available.
4. The user may bookmark the replacement separately.

The old Replaced resource itself remains hidden from normal browse/search and is not served to general authenticated users.

---

## 14. Bookmarking and Resource Feedback Workflow

**Actor:** Any authenticated Active user
**Preconditions:** Target resource status is Approved.

Bookmarking and feedback are available only for Approved resources.

### 14.1 Bookmark workflow

1. **User Action** — User selects Bookmark on an Approved resource.
2. **System Validation** — System checks that the user is authenticated and Active.
3. **System Validation** — System checks that the resource is currently Approved.
4. **System Response — Add bookmark** — If no bookmark exists, the system creates a bookmark relationship between the user and the resource.
5. **System Response — Remove bookmark** — If a bookmark already exists, the system removes or toggles the bookmark.
6. **System Response — Failure** — If the resource is no longer Approved, the bookmark action is rejected.

### 14.2 Helpful feedback workflow

For v1.0, resource feedback uses a simple binary **Helpful** toggle, not a numeric star rating.

1. **User Action** — User marks an Approved resource as Helpful.
2. **System Validation** — System checks that the user is authenticated and Active.
3. **System Validation** — System checks that the resource is currently Approved.
4. **System Response — Add helpful mark** — If the user has not marked the resource Helpful, the system records one Helpful mark for that user and resource.
5. **System Response — Remove helpful mark** — If the user already marked it Helpful, the system may allow the mark to be removed or toggled.
6. **System Response — Failure** — If the resource is not Approved, the action is rejected.

Each user may have at most one Helpful mark per resource.

### 14.3 Bookmark and feedback behavior after status changes

Bookmarks and Helpful marks do not automatically transfer to replacement resources.

If a bookmarked resource later becomes Hidden, Restricted, Removed, or Replaced:

1. The bookmark relationship may remain in the database for history.
2. The system must not serve restricted or unavailable file content through the bookmark.
3. The user may see a “resource unavailable” or “resource replaced” message.
4. If the resource was Replaced, the user may be directed to the current Approved replacement.
5. The user may bookmark the replacement separately.

This keeps the v1.0 behavior simple and avoids automatically moving user actions from one resource record to another.

---

## 15. Resource Reporting Workflow

**Actor:** Student or Teacher/Instructor
**Preconditions:** User is logged in, target resource is Approved, and the user is not the resource uploader.

Moderator and Admin do not use the public reporting workflow. They act directly through moderation/report-management tools.

### 15.1 Report reason categories

For v1.0, report reasons are:

* Outdated resource,
* Incorrect or inaccurate content,
* Inappropriate content,
* Duplicate or near-duplicate resource,
* Suspected leaked exam, quiz, or answer key,
* Copyright or unauthorized material concern,
* Other.

The report form may allow an optional comment. For serious categories such as suspected leaked exam/answer key or copyright concern, the system should encourage the reporter to provide additional details.

### 15.2 Report submission steps

1. **User Action** — Student or Teacher/Instructor selects Report on an Approved resource.
2. **User Action** — User chooses a report reason and optionally enters a comment.
3. **System Validation** — System checks that the user is authenticated and Active.
4. **System Validation** — System checks that the user is Student or Teacher/Instructor.
5. **System Validation** — System checks that the resource is currently Approved.
6. **System Validation** — System checks that the reporter is not the resource uploader.
7. **System Validation** — System checks that the user does not already have an Open report for the same resource.
8. **System Response — Success** — System creates a report with status **Open**.
9. **System Response — Success** — Report appears in the Moderator/Admin report queue.
10. **System Response — Failure** — If validation fails, the report is not created.

A user may have only one Open report per resource. After the report is Dismissed or Actioned, a future report may be allowed if a new concern arises.

### 15.3 Report status definitions

Report status is separate from resource status.

| Report Status | Meaning                                                                                                  |
| ------------- | -------------------------------------------------------------------------------------------------------- |
| **Open**      | Report has been submitted and awaits Moderator/Admin review.                                             |
| **Dismissed** | Moderator/Admin reviewed the report and found no action needed.                                          |
| **Actioned**  | Moderator/Admin acted on the report through hide, restrict, request correction, or other allowed action. |
| **Escalated** | Moderator flagged the report for Admin-level action.                                                     |

A report status change does not automatically imply a specific resource status change unless the Moderator/Admin also takes a resource action.

---

## 16. Report Review and Action Workflow

**Actor:** Moderator or Admin
**Preconditions:** An Open or Escalated report exists. Actor is logged in as Moderator or Admin.

### 16.1 Report review steps

1. **User Action** — Moderator/Admin opens the report queue.
2. **User Action** — Moderator/Admin selects a report.
3. **System Response** — System displays the report reason, optional comment, reported resource, uploader, and reporter identity.
4. **System Validation** — System checks that the actor is Moderator or Admin.
5. **User Action** — Actor chooses an allowed report decision:

   * Dismiss,
   * Hide,
   * Restrict,
   * Request Correction,
   * Escalate,
   * Remove, Admin only.
6. **System Response** — System applies the selected action.
7. **System Response** — System logs the report decision.

### 16.2 Dismiss report

If the report is invalid, duplicate, unsupported, or does not require action:

1. Report status becomes Dismissed.
2. Resource status does not change.
3. Resource remains Approved.
4. Decision is logged.

### 16.3 Hide resource from report review

If the resource needs temporary investigation:

1. Resource status becomes Hidden.
2. Report status becomes Actioned.
3. Resource is removed from normal browse/search.
4. Resource remains visible to the uploader, Moderator, and Admin.
5. Decision is logged.

Hidden is a temporary moderation hold. After review, the resource may return to Approved, become Restricted, or be Removed by Admin if necessary.

### 16.4 Restrict resource from report review

If review concludes that the resource should not remain broadly available but should still be retained:

1. Resource status becomes Restricted.
2. Report status becomes Actioned.
3. Resource is removed from normal browse/search.
4. Resource remains visible to the uploader, Moderator, and Admin.
5. Decision is logged.

Restricted is a longer-term limited-access outcome, not just an investigation hold.

### 16.5 Request correction from uploader for an Approved resource

Request Correction in the report context is **not** the same as the `Needs Correction` status.

An Approved resource must not transition to Needs Correction because uploaders cannot directly edit Approved resources. Instead, a correction request on an Approved resource means:

1. Moderator/Admin records a correction request note.
2. The uploader is notified through the in-app upload/resource management area.
3. The resource may remain Approved, become Hidden, or become Restricted depending on severity.
4. The uploader’s remedy is to submit a corrected replacement through Section 17 — Resource Correction and Replacement Workflow.
5. The report status becomes Actioned once the correction request is sent.
6. Decision is logged.

This preserves the rule that Approved resources are not edited in place.

### 16.6 Escalate report

Escalation is used when a Moderator determines that the report may require Admin-level authority.

Examples:

* possible resource removal,
* possible account-level concern,
* repeated serious violations,
* unclear case requiring Admin decision.

If a Moderator escalates a report:

1. Report status becomes Escalated.
2. Resource status does not automatically change.
3. Moderator may also set the resource to Hidden if temporary broad access should be stopped during investigation.
4. Escalated report appears in the Admin’s report/action queue.
5. Decision is logged.

Admin may later dismiss, hide, restrict, request correction, or remove the resource according to the allowed workflows.

### 16.7 Remove resource from report review

Remove is Admin-only.

If the report requires permanent withdrawal from the system:

1. Admin selects Remove.
2. System checks that the actor is Admin.
3. Resource status becomes Removed.
4. Resource is no longer visible to normal users or the original uploader.
5. File content and AI outputs are deleted or invalidated according to policy.
6. Minimal audit record remains.
7. Report status becomes Actioned.
8. Decision is logged.

Moderator cannot remove resources. Moderator must escalate to Admin if removal may be needed.

---

## 17. Resource Correction and Replacement Workflow

**Actor:** Original uploader
**Preconditions:** Target resource status is Approved. Actor is logged in and is the original uploader of the Approved resource.

This workflow is used when an already Approved resource needs correction. It is different from Needs Correction before approval.

### 17.1 Replacement submission rules

1. The Approved resource is not edited in place.
2. The corrected upload creates a new Pending resource record linked to the original Approved resource.
3. The original Approved resource remains visible while the replacement is Pending.
4. Only one open replacement may exist for the same original Approved resource at a time.
5. The replacement resource goes through normal upload validation and moderation.
6. If the replacement is Approved, the original becomes Replaced.
7. If the replacement is Rejected, the original remains Approved and unchanged.
8. If the replacement is Withdrawn, the original remains Approved and unchanged.

### 17.2 Replacement submission steps

1. **User Action** — Uploader opens their own Approved resource.
2. **User Action** — Uploader selects Submit Correction or equivalent action.
3. **User Action** — Uploader uploads a corrected file and/or updated metadata.
4. **System Validation** — System checks that the actor is the original uploader.
5. **System Validation** — System checks that the original resource is currently Approved.
6. **System Validation** — System checks that no open replacement already exists for the original resource.
7. **System Validation** — The corrected file passes through Section 9 — Basic Upload Validation Workflow.
8. **System Response — Success** — System creates a new resource record with status Pending.
9. **System Response — Success** — System links the new Pending replacement to the original Approved resource.
10. **System Response — Success** — Original resource remains Approved and visible.
11. **System Response — Success** — Replacement enters Section 11 — Moderation Review Workflow.
12. **System Response — Failure** — If validation fails, no replacement resource is created.

### 17.3 Replacement approved

If the replacement resource is Approved:

1. Replacement resource becomes the current Approved resource.
2. Original resource status becomes Replaced.
3. Original resource is removed from normal browse/search.
4. Original resource is not served to general authenticated users.
5. Original resource remains visible to uploader, Moderator, and Admin for history/audit/reference.
6. Bookmarks, Helpful marks, views, downloads, and reports from the original do not automatically transfer to the replacement.
7. AI outputs from the original must not be reused as AI outputs for the replacement.

### 17.4 Replacement rejected

If the replacement resource is Rejected:

1. Replacement resource status becomes Rejected.
2. Original resource remains Approved.
3. Original resource remains visible in normal browse/search.
4. Uploader may submit another replacement later.
5. Draft AI outputs for the rejected replacement are deleted or invalidated.

### 17.5 Replacement needs correction

If the replacement resource receives Needs Correction:

1. Replacement resource status becomes Needs Correction.
2. Uploader may revise and resubmit the same replacement resource through Section 12.
3. No third resource is created.
4. Link to the original Approved resource remains.
5. Original Approved resource remains visible while the replacement is being corrected.

### 17.6 Replacement withdrawn

If the uploader withdraws a Pending or Needs Correction replacement:

1. Replacement status becomes Withdrawn.
2. Original Approved resource remains unchanged and visible.
3. File content and draft AI outputs for the withdrawn replacement are deleted or invalidated.
4. Minimal history/audit record remains.

### 17.7 Replacement AI and retrieval rule

A replacement resource may receive its own AI-assisted draft output only if the applicable capability is enabled and all Pending-resource validation, notice, acknowledgment, eligibility, lifecycle, and permission requirements are met.

The replacement must not inherit, reuse, or display AI outputs from the original resource. AI outputs are tied to the specific resource that generated them.

If the replacement later becomes Approved, it must undergo its own extraction, semantic processing, and indexing under Section 18A before it may support semantic search, related-resource suggestions, or repository-grounded inquiry. It must not inherit extracted text, chunks, embeddings, index entries, provider retrieval objects, citation identity, or any equivalent retrieval-derived data from the original resource.

No AI output, retrieval-derived data, bookmark, Helpful mark, report, view, or download history transfers automatically from the original resource to the replacement.

---

<!-- Sections 18–24: Consequences, Edge Cases, and Verification -->

---

## 18. Hidden, Restricted, Removed, and Replaced Resource Handling

This section defines how the system handles non-public post-approval statuses and related staff actions.

These statuses are used after a resource has already been Approved, except Replaced, which occurs automatically when a corrected replacement is Approved.

### 18.1 Status transition summary

| From       | Can transition to              | Trigger                                                  | Actor            |
| ---------- | ------------------------------ | -------------------------------------------------------- | ---------------- |
| Approved   | Hidden                         | Report action or direct moderation action                | Moderator, Admin |
| Approved   | Restricted                     | Report action or direct moderation action                | Moderator, Admin |
| Approved   | Removed                        | Report action or direct moderation action                | Admin only       |
| Approved   | Replaced                       | Linked replacement is Approved                           | System           |
| Hidden     | Approved                       | Investigation finds no lasting issue                     | Moderator, Admin |
| Hidden     | Restricted                     | Investigation finds longer-term limited access is needed | Moderator, Admin |
| Hidden     | Removed                        | Investigation finds removal is needed                    | Admin only       |
| Restricted | Approved                       | Later review determines broad access may be restored     | Moderator, Admin |
| Restricted | Removed                        | Later Admin decision                                     | Admin only       |
| Removed    | None                           | Terminal status                                          | None             |
| Replaced   | None for the original resource | Terminal public state for the original resource          | None             |

### 18.2 Hidden resource handling

Hidden is a temporary moderation hold.

A resource may be set to Hidden when a Moderator/Admin needs to temporarily stop broad access while a concern is being investigated.

Examples:

* report is under review,
* suspected inappropriate content,
* suspected leaked exam or answer key,
* suspected copyright issue,
* suspected duplicate or misleading material,
* uncertain issue requiring temporary review.

When a resource becomes Hidden:

1. It is removed from normal browse/search.
2. General authenticated users cannot view or download it.
3. The uploader, Moderator, and Admin may still view it.
4. Existing bookmarks must not serve the file.
5. AI outputs follow the same visibility restriction.
6. The action must be logged with a reason/note.

Hidden is reversible. After investigation, the resource may return to Approved, become Restricted, or be Removed by Admin.

A “Hidden for review” dashboard list is recommended for build planning so Hidden resources are not forgotten, but it is not a required separate v1.0 module.

### 18.3 Restricted resource handling

Restricted is a longer-term limited-access outcome after review.

A resource may be set to Restricted when it should not remain broadly available to all authenticated users, but should still be retained for uploader/staff visibility and audit/reference.

Restricted is different from Hidden:

* **Hidden** is temporary and investigative.
* **Restricted** is a reviewed limited-access outcome.

When a resource becomes Restricted:

1. It is removed from normal browse/search.
2. General authenticated users cannot view or download it.
3. The uploader, Moderator, and Admin may still view it.
4. Existing bookmarks must not serve the file.
5. AI outputs follow the same visibility restriction.
6. The action must be logged with a reason/note.

Restricted may be reversed back to Approved by Moderator/Admin if later review determines that broad access is acceptable again. The reversal must be logged with a reason/note.

### 18.4 Removed resource handling

Removed is an Admin-only terminal status.

For v1.0, Removed is implemented as a retained but minimized resource row with file and AI-output unavailability. The database row is not hard-deleted because reports, action history, bookmarks, Helpful marks, replacement relationships, and other accepted historical records may still reference the resource ID.

When a resource becomes Removed:

1. **System Validation** — The system confirms that the actor is still an Active Admin and that the current resource status allows the requested Remove action.

2. **System Response** — Resource status becomes `Removed`.

3. **System Response** — The resource is excluded from normal browse/search and from all normal-user and uploader views.

4. **System Response — D040 sanitization** — The system overwrites:

   * `title` with `[Removed resource]`;
   * `description` with `[Removed resource]`;
   * `topic` with `[Removed]`;
   * `original_filename` with `[removed]`.

5. **System Response — Tag cleanup** — The system deletes all `resource_tags` rows associated with the Removed resource.

6. **System Response — File lifecycle** — `file_availability` becomes `deleted` or `invalidated`, according to the actual file-handling result. The stored file must no longer be servable.

7. **System Response — AI and retrieval lifecycle** — AI outputs tied to the resource and any retrieval-derived data, such as extracted text, chunks, embeddings, local or hosted index entries, provider file objects, provider retrieval identifiers, or an equivalent representation where such data exists under the selected architecture, are deleted, invalidated, disabled, or otherwise made unusable under the accepted lifecycle. They must be excluded from public-facing or unauthorized reads, caches, search, recommendations, inquiry evidence, citations, and stale links.

8. **System Response — Accountability** — The system writes the required `resource_action_history` entry with the Admin actor, removal action, removal reason/note, previous status, new status, and timestamp.

9. **System Response — Retained relationships** — The resource ID, uploader reference, required taxonomy references, technical file metadata, activity counts, AI-notice acknowledgment fields, timestamps, reports, action history, bookmarks, Helpful marks, and other accepted historical relationships remain unless another confirmed lifecycle rule states otherwise.

The database updates for status, file availability, field sanitization, tag-association deletion, AI-output database lifecycle, and action history should commit in one transaction.

Physical file deletion is not part of the database transaction and cannot automatically roll back with it. File cleanup must be coordinated with the removal operation. If physical cleanup fails, the system must fail closed: the resource's status and `file_availability` must still prevent file serving, and the cleanup failure must be handled safely.

`created_at` remains unchanged. `updated_at` changes automatically when the resource row is updated.

The retained Removed-resource record is minimized within the accepted schema but is not anonymized. Removed has no reversal workflow in v1.0. If the same material needs to be shared again, it must be uploaded as a new resource and pass moderation again.

### 18.5 Replaced resource handling

A resource becomes Replaced when a linked corrected replacement is Approved.

When the replacement is Approved:

1. The replacement resource becomes the current Approved resource.
2. The original resource becomes Replaced.
3. The original resource is removed from normal browse/search.
4. General authenticated users cannot view or download the original resource.
5. The uploader, Moderator, and Admin may still view the original resource for history/audit/reference.
6. AI outputs from the original must not be reused for the replacement.
7. Bookmarks, Helpful marks, views, downloads, and reports from the original do not automatically transfer to the replacement.

Replaced is terminal for the original resource’s public visibility. The original does not return to Approved.

### 18.6 Direct Moderator/Admin action without a report

Moderator/Admin may act directly on a problematic Approved resource even when no Student/Teacher report exists.

This workflow exists because Moderators/Admins may discover issues themselves while browsing, reviewing, or managing resources. They should not need to create a public report first.

Allowed direct actions:

| Action   | Who Can Perform  | Result                      |
| -------- | ---------------- | --------------------------- |
| Hide     | Moderator, Admin | Resource becomes Hidden     |
| Restrict | Moderator, Admin | Resource becomes Restricted |
| Remove   | Admin only       | Resource becomes Removed    |

Direct action workflow:

1. **User Action** — Moderator/Admin opens an Approved resource.
2. **User Action** — Moderator/Admin selects Hide, Restrict, or Remove.
3. **System Validation** — System checks that the actor is Moderator/Admin.
4. **System Validation** — If action is Remove, system checks that the actor is Admin.
5. **User Action** — Actor provides a reason/note.
6. **System Response** — System applies the selected status change.
7. **System Response** — System logs the action with actor, timestamp, resource, action type, and reason/note.

No report record is created because no report exists. The audit log is the accountability record for direct moderation/admin action.


---

## 18A. Approved-Resource AI Processing Workflow

**Actor:** System  
**Preconditions:** Resource exists with status Approved and is being considered for an enabled Approved-resource AI capability.

This section defines a conceptual processing sequence for the required minimum AI foundation under `DECISIONS.md` D042. It does not define exact tables, columns, queues, cron jobs, services, provider APIs, storage formats, or status enums. Those remain for the feasibility spike, the later architecture/schema decision, `AI_FEATURES.md`, and `DATABASE_DESIGN.md`.

### 18A.1 Conceptual processing sequence

1. A resource becomes Approved and is considered for Approved-resource AI processing. If its file was changed earlier through an allowed Pending or Needs Correction correction/resubmission workflow, any derived data from the prior file version remains stale and must not be reused.
2. **System Validation — Live eligibility** — System confirms that the resource is currently Approved, is accessible under the applicable rules, has `file_availability = 'available'`, and remains otherwise eligible for the requested AI capability.
3. **System Response — Extraction** — For readable PDF, DOCX, PPTX, or TXT files where extraction succeeds, the system extracts readable text. Image-only files, scanned PDFs, and other files without extractable text remain valid repository resources but are not required to support content-based AI functions in v1.0. No OCR or AI-vision step is required.
4. **System Response — Source location** — Where the selected extraction approach preserves information reliably, the system may retain page, slide, section, heading, or another source-location reference for later attribution. A locator must never be invented when it was not preserved reliably.
5. **System Response — Retrieval representation** — The system prepares a reusable representation of eligible extracted content for semantic search, related-resource suggestions, and repository-grounded inquiry.
6. **System Response — Derived outputs** — Where the applicable capability is enabled, configured, and permitted for the current resource lifecycle, the system may generate or update authorized AI outputs such as the resource summary and suggested tags/metadata. These outputs remain non-authoritative and subject to Section 11 and D028. Generating a suggestion does not automatically create a taxonomy value or automatically write the suggestion into final resource metadata.
7. **System Response — Capability readiness** — The system records whether each applicable capability is unprocessed, being prepared, ready, failed, or stale using the later approved architecture. Exact stored state names are not defined here.
8. **System Response — Independent readiness** — Capabilities may become ready independently. A resource may have a ready summary while semantic retrieval is still being prepared, or semantic retrieval may be ready while another derived output failed. The system must not assume that all AI capabilities complete in one operation.
9. **System Response — Failure isolation** — Failure of one AI capability must not change the resource's Approved status, block ordinary browse/search, prevent view/download through the accepted non-AI path, or invalidate another independently ready AI capability.
10. **System Response — Metadata search unaffected** — Metadata search/filtering under Section 13 remains available regardless of extraction, embedding, indexing, generation, or semantic-processing success.
11. **System Response — User-facing availability** — Where appropriate, the interface may indicate that a specific AI capability is unavailable, failed, or still being prepared without exposing raw provider errors or blocking non-AI access.
12. **System Response — No automatic moderation action** — Extraction, processing readiness, processing failure, similarity, or any generated output must never trigger an automatic status change, approval, rejection, Hide, Restrict, Remove, or other moderation action.

### 18A.2 Source-file and dependency freshness

The system must be able to detect whether retrieval-derived data or AI output still corresponds to the current source file, using a source fingerprint or equivalent later-approved mechanism.

When the source file changes, extracted text, chunks, embeddings, index entries, source locators, or equivalent data generated from the prior file become stale and must be excluded from use until the changed file is successfully reprocessed.

Metadata changes may also require refresh when the affected metadata contributes to a summary, suggestion, semantic representation, ranking, recommendation, or retrieval filter. Unaffected current output may remain reusable only when the later architecture confirms that it does not depend on the changed metadata.

Exact fingerprinting, dependency tracking, caching, and refresh mechanics are intentionally deferred.

---

## 18B. Semantic Content-Search Workflow

**Actor:** Any authenticated Active user  
**Preconditions:** The semantic content-search capability is enabled and configured.

1. **User Action** — User submits a semantic or content-based query, optionally together with ordinary metadata filters.
2. **System Validation — Account** — System rechecks that the requesting account exists, is Active, and is currently authorized to use the feature.
3. **System Response — Eligible candidate set** — Search considers only resources that are currently:
   * Approved;
   * accessible to the requester;
   * `file_availability = 'available'`;
   * successfully processed and ready for semantic search;
   * current rather than stale;
   * otherwise eligible under the applicable lifecycle and AI rules.
4. **System Response — Supplement, not replacement** — Semantic retrieval supplements metadata search/filtering. It never replaces the independently functional metadata path required by D004.
5. **System Response — Candidate revalidation** — Every candidate returned from a live query, cache, local index, hosted index, or external retrieval service is revalidated against the current local source-of-truth database before any snippet or resource link is displayed.
6. **System Validation — Current access** — Revalidation confirms current account status, resource status, requester access, file availability, processing readiness, source freshness, and any other applicable lifecycle rule.
7. **System Response — Results** — Only candidates that still pass all checks are returned. Any snippet must be limited to currently eligible content and must not expose non-public or stale content.
8. **System Response — Fallback** — If semantic search is disabled, unavailable, unconfigured, rate-limited, failing, or not ready, the system shows a clear non-technical message and preserves ordinary metadata search/filtering.
9. **System Response — No leakage** — Pending, Needs Correction, Rejected, Withdrawn, Hidden, Restricted, Removed, Replaced, private, unauthorized, stale, file-unavailable, or otherwise ineligible content must not appear through snippets, cached results, indexes, or old links.

Exact embedding models, dimensions, chunk sizes, ranking formulas, similarity thresholds, vector infrastructure, and storage mechanisms are not defined by this document.

---

## 18C. Related-Resource Suggestion Workflow

**Actor:** Any authenticated Active user  
**Preconditions:** The related-resource capability is enabled and configured. The target resource is currently Approved and accessible to the requester.

1. **System Validation — Target resource** — System rechecks that the target resource remains Approved, accessible, file-available, current, and eligible.
2. **System Response — Suggestion set** — System prepares a small number of potentially relevant Approved resources using content and metadata similarity where available.
3. **System Validation — Candidate checks** — Every suggested resource must currently pass the same account, access, status, file-availability, processing-readiness, source-freshness, and lifecycle checks required by Section 18B.
4. **System Response — Revalidation** — Each suggested resource is revalidated against the current local source-of-truth database before its title, metadata, snippet, or link is returned.
5. **System Response — No profiling** — Suggestions must not rely on a persistent learner profile, behavioral-tracking profile, or cross-session personalization. No such profile is authorized in v1.0.
6. **System Response — No implied endorsement** — "Related" means potentially relevant based on the configured matching method. It does not mean academically correct, officially endorsed, approved by a Teacher/Instructor, or confirmed as a duplicate.
7. **System Response — No automatic data transfer** — Bookmarks, Helpful marks, reports, views, downloads, moderation history, and other engagement or lifecycle data do not transfer between related resources.
8. **System Response — Fallback** — If semantic/content similarity is unavailable, the feature may use a simpler metadata-based matching path where that fallback is implemented and enabled. The fallback remains subject to the same live eligibility and link-revalidation rules and must not create personalized profiling or functionality outside the accepted scope.
9. **System Response — Failure isolation** — If no eligible related resources can be produced, the system may show no suggestions or a clear unavailable message without affecting access to the current resource.

---

## 18D. Repository-Grounded Academic Resource Inquiry Workflow

**Actor:** Any authenticated Active user  
**Preconditions:** The repository-grounded inquiry capability is enabled and configured.

Repository-grounded inquiry is a required v1.0 capstone capability under D041–D042. It remains assistive, non-authoritative, repository-centered, and independent from the ordinary non-AI resource-sharing workflows.

1. **User Action** — User opens the inquiry feature and submits a question.
2. **System Validation — Account and feature** — System performs a live check that the account exists, is Active, is currently authorized, and that the inquiry capability is available.
3. **System Response — Retrieval request** — System creates a retrieval request using only resources that are currently Approved, accessible to the requester, `file_availability = 'available'`, processed and ready for inquiry retrieval, current rather than stale, and otherwise eligible.
4. **System Response — Status exclusions** — Pending, Needs Correction, Rejected, Withdrawn, Hidden, Restricted, Removed, Replaced, private, unauthorized, file-unavailable, stale, or otherwise ineligible resources must not be used as general inquiry evidence.
5. **System Response — Candidate revalidation before use** — Every retrieved candidate is revalidated against the current local source-of-truth database before its content is sent to a language model and before its resource link is returned.
6. **System Validation — Revalidation scope** — Revalidation includes current account status, current Approved status, current requester access, `file_availability = 'available'`, processing/index readiness, source freshness or fingerprint match, and all other applicable lifecycle and AI-eligibility rules.
7. **System Response — Minimum necessary payload** — The system sends only the minimum relevant eligible evidence and necessary metadata to the selected generator. It must not send unrelated full files, full extracted corpora, secrets, or unnecessary personal data merely because they are available locally.
8. **System Response — Grounded answer** — The generated response grounds substantive academic claims in the retrieved evidence. The model may organize, simplify, summarize, or explain that evidence but must not silently substitute unsupported general model knowledge when repository evidence is missing.
9. **System Response — Source attribution** — Every substantive response identifies or links its supporting resource or resources. Page, slide, section, heading, or another source locator is shown only when the selected extraction method preserved it reliably. The system must never fabricate a source, citation, or locator.
10. **System Response — Insufficiency handling** — If the available eligible evidence is insufficient, the system clearly states that the repository does not contain enough supporting evidence. It must not silently answer from unsupported general model knowledge.
11. **System Response — Prohibited requests** — Requests for exam answers, quiz answers, graded-assignment answers, answer keys, grading, or other prohibited academic tasks receive a bounded refusal or redirection consistent with the accepted project scope.
12. **System Response — Generation failure** — If generation is unavailable or fails, the system does not expose raw provider errors, partial unsafe output, secrets, or stale evidence. It shows a clear temporary-unavailability message and leaves the non-AI platform unaffected.
13. **System Response — Final source-link check** — Immediately before each source link is displayed, the system confirms that the requesting user remains authorized to access the resource and that the resource remains eligible.
14. **System Response — No permanent inquiry-history module** — The application does not create a permanent chat-history or cross-session memory module under this workflow. Request-scoped or active-session behavior is defined in Section 18E.

Grounding and attribution do not require copying long passages from source resources. Responses should summarize or explain evidence and provide the required resource identification and reliable locator where available.

---

## 18E. Session-Scoped Inquiry Follow-Up Workflow

**Actor:** Any authenticated Active user engaged in an active inquiry session  
**Preconditions:** An inquiry session under Section 18D is currently active.

1. **System Response — Session-scoped context** — The application may temporarily preserve the minimum conversation context needed for follow-up questions during the active inquiry session.
2. **System Validation — Recheck every follow-up** — Every follow-up independently reruns the live account, feature, resource-access, resource-status, file-availability, processing-readiness, source-freshness, and AI-eligibility checks required by Section 18D. Eligibility established in an earlier turn must not be assumed to remain valid.
3. **System Response — Mid-session ineligibility** — If a resource that supported an earlier response becomes Hidden, Restricted, Removed, Replaced, file-unavailable, stale, or otherwise ineligible, it must not support later follow-up responses unless and until it independently becomes eligible again under the accepted rules.
4. **System Response — No access expansion** — Prior questions, prior citations, or temporary session context must never grant access beyond the user's current permissions.
5. **System Response — Source revalidation** — Retrieved candidates and source links are revalidated again for every follow-up before evidence is sent for generation or a link is returned.
6. **System Response — Session end** — Ending, logging out, or expiration of the applicable active inquiry context makes that follow-up context unavailable for later use.
7. **System Response — No permanent memory assumption** — This workflow does not authorize permanent chat history, persistent cross-session memory, user profiling, or behavior-based personalization. Inquiry questions, responses, retrieved evidence, citations, and temporary follow-up context are not assumed to be permanent application records. Provider-side handling remains subject to the separately reviewed provider terms and cannot be guaranteed solely by application behavior.

**[NEEDS CONFIRMATION]** — The exact inquiry-session duration and temporary-memory implementation are not settled. `AI_FEATURES.md` and `BUILD_PLAN.md` must determine whether the inquiry context follows the general authenticated session, uses a shorter feature-specific lifetime, or uses another bounded temporary mechanism without creating persistent cross-session memory.

---

## 19. AI Output and Retrieval-Derived Data Lifecycle Workflow

This section defines what happens to stored AI outputs and retrieval-derived data as a resource moves through its lifecycle.

The accepted `ai_outputs` current-value design supports resource-linked AI output categories such as:

* summaries;
* suggested tags;
* suggested metadata;
* duplicate/similarity indicators;
* moderation hints;
* related-resource recommendation output.

The current accepted schema does not define semantic-search chunks, embeddings, retrieval indexes, retrieved evidence sets, inquiry answers, citations, or session conversation context as `ai_outputs`.

Retrieval-derived data may include, depending on the later architecture:

* extracted text;
* chunks;
* preserved source-location information;
* embeddings;
* local index entries;
* hosted vector entries;
* provider file objects;
* provider retrieval identifiers.

Retrieved candidate sets, inquiry responses, citations, and active-session follow-up context may remain request-scoped or session-scoped and are not assumed to be permanent stored AI outputs.

Exact storage, caching, deletion, invalidation, and synchronization mechanics are not defined by this document. Those remain for the feasibility spike, the later architecture/schema decision, `AI_FEATURES.md`, and `DATABASE_DESIGN.md`. The lifecycle rules below apply regardless of the storage method eventually selected.

### 19.1 Core principle

Every stored AI output is tied to exactly one resource record.

AI output must not be copied, inherited, or reused across resources, including from an original resource to its replacement.

A replacement resource may generate its own AI output only if it independently satisfies the applicable validation, notice, acknowledgment, eligibility, lifecycle, and configuration rules.

The same no-inheritance rule applies to retrieval-derived data. A replacement must undergo its own extraction, processing, and indexing and must not inherit the original resource's extracted content, chunks, embeddings, index entries, provider objects, or citation identity.

### 19.2 Pending

AI outputs generated for Pending uploads are draft outputs only.

While Pending:

1. AI output is visible only to the uploader for their own resource and to Moderator/Admin where current permissions allow.
2. AI output is not visible to general authenticated users.
3. AI output does not affect final metadata unless an authorized human reviews and uses it under the later defined acceptance workflow.
4. AI output must not approve, reject, publish, Hide, Restrict, Remove, validate, delete, or otherwise change the resource's status.
5. Pending-resource AI processing requires successful basic upload validation, clear uploader notice, and uploader acknowledgment.
6. General semantic search, related-resource suggestions, and repository-grounded inquiry never use Pending resources as general evidence or results.

### 19.3 Needs Correction

If a Pending resource receives Needs Correction:

1. Existing draft AI output may remain available only when it still corresponds to the current file and relevant metadata.
2. If the file is replaced during correction, old draft AI output must be discarded or invalidated.
3. If AI is enabled and all applicable validation, notice, acknowledgment, eligibility, and lifecycle requirements are satisfied, the corrected file may receive new AI output.
4. The resource remains non-public.
5. General semantic search, related-resource suggestions, and repository-grounded inquiry do not use the Needs Correction resource.
6. Retrieval-derived data tied to a replaced file becomes stale and must not be used until the changed file is successfully reprocessed.
7. If only metadata changed, affected AI output or retrieval-derived data must be refreshed or treated as stale where that capability depends on the changed fields.

### 19.4 Approved

If a resource is Approved:

1. AI output that was visible during moderation and not discarded may be treated as accepted for the limited v1.0 current-value workflow under D028.
2. Accepted summaries, tag suggestions, and metadata suggestions may be retained according to the accepted output lifecycle.
3. Retained AI output becomes visible according to Approved-resource visibility and permission rules.
4. Once the applicable feature is enabled and its processing is ready, an eligible Approved resource may support summaries, related-resource recommendations, semantic content search, and repository-grounded inquiry under Sections 18A–18D.
5. Approved status alone does not make every AI capability ready. File availability, current access, processing readiness, source freshness, configuration, and other live eligibility rules still apply.
6. Inquiry answers, retrieved evidence sets, citations, and active-session context are not automatically stored merely because the source resources are Approved.

No separate "accept each AI field" checkbox is required in v1.0, provided the authorized Moderator/Admin had a visible opportunity to review and edit or discard the relevant Pending-stage output before approval. The exact write/acceptance mechanics for individual suggested metadata values remain for `AI_FEATURES.md`.

### 19.5 Rejected

If a resource becomes Rejected:

1. Draft AI outputs tied to the resource must be deleted or invalidated according to the accepted lifecycle.
2. AI outputs must not appear in public-facing search, recommendations, summaries, inquiry, citations, reports, or other user-facing views.
3. The Rejected resource must not enter new AI processing or general repository retrieval.
4. Any retrieval-derived data associated with the Rejected resource must be deleted, invalidated, disabled, or otherwise made unusable according to the later approved architecture. It must not remain searchable, recommendable, retrievable, or usable as inquiry evidence.

### 19.6 Withdrawn

If a resource becomes Withdrawn:

1. Draft AI outputs tied to the resource must be deleted or invalidated according to the accepted lifecycle.
2. AI outputs must not appear in public-facing search, recommendations, summaries, inquiry, citations, reports, or other user-facing views.
3. The Withdrawn resource must not enter new AI processing or general repository retrieval.
4. Any retrieval-derived data associated with the Withdrawn resource must be deleted, invalidated, disabled, or otherwise made unusable according to the later approved architecture. It must not remain searchable, recommendable, retrievable, or usable as inquiry evidence.

### 19.7 Hidden

If an Approved resource becomes Hidden:

1. AI outputs are removed from public-facing views.
2. AI outputs are excluded from new public-facing recommendations, semantic search, and repository-grounded inquiry while Hidden.
3. Previously retained AI output remains visible only to the uploader, Moderator, and Admin where the accepted Hidden-resource visibility and lifecycle rules allow.
4. If the resource later returns to Approved, previously retained AI output may return to normal visibility only after its lifecycle state, current source-file association, resource eligibility, and applicable readiness/freshness checks pass. Restoration is not automatic merely because the resource status changed.
5. The resource is immediately excluded from new public-facing semantic retrieval, related-resource suggestions, and inquiry while Hidden.
6. Returning to Approved does not automatically prove that prior retrieval-derived data is current. Current eligibility, readiness, and freshness must be checked before public-facing AI use resumes.

### 19.8 Restricted

If an Approved resource becomes Restricted:

1. AI outputs are removed from public-facing views.
2. AI outputs are excluded from new general recommendations, semantic search, and repository-grounded inquiry while Restricted.
3. Previously retained AI output remains visible only to the uploader, Moderator, and Admin where the accepted Restricted-resource visibility and lifecycle rules allow.
4. If the resource later returns to Approved, previously retained AI output may return to normal visibility only after its lifecycle state, current source-file association, resource eligibility, and applicable readiness/freshness checks pass. Restoration is not automatic merely because the resource status changed.
5. The resource is excluded from new general repository retrieval, related-resource suggestions, and inquiry while Restricted.
6. Returning to Approved requires current eligibility, readiness, and freshness checks before public-facing AI use resumes.

### 19.9 Removed

If a resource becomes Removed:

1. AI outputs tied to the resource must be deleted or invalidated according to the accepted lifecycle.
2. AI outputs must not remain searchable, visible, recommendable, retrievable, citable, or otherwise usable.
3. Minimal audit data may record that AI processing occurred, but must not store full AI-generated content, full prompts, full extracted text, or secrets.
4. Retrieval-derived data associated with the Removed resource must be deleted, invalidated, disabled, or otherwise made unusable consistently with D040 and the accepted security/privacy baseline.
5. Removed descriptive data must not remain exposed through caches, local or hosted indexes, retrieved snippets, citations, provider retrieval objects, or stale links.
6. The later architecture must define how local and provider-side retrieval data is cleaned up or invalidated without changing D040's accepted retained-record model.

### 19.10 Replaced

If a resource becomes Replaced:

1. AI outputs from the original resource are removed from public-facing views.
2. AI outputs from the original are excluded from recommendations, semantic search, and repository-grounded inquiry.
3. AI output from the original remains visible only to uploader, Moderator, and Admin where the accepted Replaced-resource lifecycle permits, unless deleted or invalidated by policy.
4. The replacement resource must not inherit or reuse the original resource's AI output.
5. The replacement resource may generate its own AI output only if independently eligible.
6. The replacement resource must undergo its own extraction, processing, and indexing under Section 18A and must not inherit the original resource's retrieval-derived data.
7. The original resource's citation identity must not be silently reused as the replacement's identity.
8. Bookmarks, Helpful marks, reports, views, downloads, and other historical relationships do not transfer automatically.

### 19.11 Retrieval-Derived Data, Search Index, and Inquiry Invalidation

Any status change away from Approved must update, disable, invalidate, remove, or otherwise make unusable the related public-facing retrieval-derived data for that resource.

This applies when a resource becomes:

* Hidden;
* Restricted;
* Removed;
* Replaced;
* Withdrawn;
* Rejected.

A resource must not remain searchable, suggestible, retrievable, citable, or usable as inquiry evidence through an index, cache, provider object, or retrieval mechanism after its current status, access, file availability, processing readiness, source freshness, or lifecycle no longer permits that use.

This rule applies whether retrieval-derived data is stored locally, in MariaDB-compatible storage, in application-managed files, in a hosted retrieval service, or with an external provider.

**Source-file staleness**

When a resource's source file changes, retrieval-derived data generated from the prior file becomes stale and must be excluded from semantic search, related-resource suggestions, and inquiry until the changed file is successfully reprocessed.

A source fingerprint or equivalent later-approved mechanism must be checked before stale-sensitive retrieval-derived data is used.

**Metadata dependency**

When metadata changes, only output and retrieval representations that depend on the changed fields require refresh. Unaffected current output may remain reusable only when the later architecture confirms that it does not depend on those fields.

**Replacement non-inheritance**

A replacement resource must undergo its own eligibility check, extraction, processing, and indexing. It must not inherit the original resource's AI output, extracted content, chunks, embeddings, index entries, provider objects, or citation identity.

**Removed-resource alignment**

Retrieval-derived data for a Removed resource must be deleted, invalidated, disabled, or otherwise made unusable consistently with D040 and the accepted security/privacy baseline. It must never be exposed through a cache, local or hosted index, retrieval result, citation, provider object, or stale link.

Exact indexing, caching, provider synchronization, deletion, and invalidation mechanics belong in `AI_FEATURES.md` and the later architecture/schema decision under D042, not this document.

---

## 20. Error Handling and Invalid Action Behavior

### 20.1 Fail-closed principle

When the system cannot confirm that an action is allowed, it must deny the action.

The system must not allow an action by default when:

* role is unclear,
* session is expired,
* resource status changed,
* account status changed,
* ownership cannot be confirmed,
* request is malformed,
* database validation fails,
* workflow preconditions are not met.

### 20.2 Invalid action categories

| Invalid Action Type   | Example                                                   | Required Behavior                                             |
| --------------------- | --------------------------------------------------------- | ------------------------------------------------------------- |
| Role mismatch         | Moderator attempts ordinary upload                        | Reject server-side                                            |
| Status mismatch       | User tries to download a Hidden resource through old link | Reject server-side                                            |
| Ownership mismatch    | User tries to withdraw another user’s Pending resource    | Reject server-side                                            |
| Direct URL/API bypass | User accesses protected action without UI path            | Reject server-side                                            |
| Malformed request     | Missing required metadata or invalid file                 | Reject with validation error                                  |
| Concurrency conflict  | Two staff users act on same Pending resource              | First valid committed action wins; later action fails cleanly |

### 20.3 Concurrency handling

Before committing any state-changing action, the system must re-check the current database state.

This applies to:

* moderation decisions,
* withdrawals,
* replacement submissions,
* report actions,
* direct hide/restrict/remove actions,
* bookmark/helpful actions,
* account disable/enable actions.

If two users attempt conflicting actions at the same time, the first valid committed action wins. The second action must fail cleanly with a message that the target has already changed.

### 20.4 Message specificity

Security-sensitive failures should use generic messages.

Examples:

* invalid login,
* disabled account login attempt,
* duplicate registration identifier.

Ordinary validation failures may use specific messages.

Examples:

* unsupported file type,
* missing required metadata,
* file too large,
* invalid report reason.

### 20.5 Unexpected system errors

Unexpected system errors must not expose internal details to the user.

The system must not show:

* raw SQL errors,
* stack traces,
* server file paths,
* API keys,
* AI provider error payloads,
* PHP warnings/notices in production-like demo mode.

The user should see a safe generic message, while the system records enough detail for developer/admin troubleshooting.

---

## 21. Edge Cases

### 21.1 Uploader account disabled while resource is Pending

If an uploader account is Disabled while their resource is Pending, moderation may still proceed.

Account status and resource status are separate. A disabled account cannot log in or act, but the resource may still be reviewed by Moderator/Admin.

If the resource is valid and appropriate, Moderator/Admin may still Approve it. If the uploader’s disabled status is relevant to the review, Moderator/Admin may consider that context manually.

### 21.2 No report assignment model

v1.0 does not include report claiming or assignment.

Any Moderator/Admin may act on any Open or Escalated report. Coordination between staff is handled by current report status and audit logs, not by a report-assignment module.

### 21.3 Duplicate or near-duplicate uploads

Two unrelated users may upload similar or duplicate resources.

AI duplicate/similarity detection is a planned v1.0 enhancement under D042 and Section 10A, targeted only after the required minimum AI package is stable. If implemented and enabled, it provides non-authoritative hints only. It does not automatically Reject, merge, Hide, Restrict, Remove, or definitively label a resource as duplicated.

Moderator/Admin independently reviews the resource and decides whether to approve, reject, request correction, Hide, Restrict, or Remove under the existing role and lifecycle rules, with or without an AI hint available.

### 21.4 Withdrawal versus moderation race

If an uploader attempts to withdraw a Pending resource at the same time a Moderator/Admin attempts to approve, reject, or request correction:

1. System re-checks current status before committing either action.
2. First valid committed action wins.
3. Second action fails cleanly if the status is no longer eligible.

Example: if the Moderator approves first, the uploader can no longer withdraw because Approved resources cannot be withdrawn.

### 21.5 Open report against original resource that becomes Replaced

If an Approved resource has an Open report and a corrected replacement is Approved before the report is resolved:

1. Original resource becomes Replaced.
2. Report remains attached to the original resource.
3. Moderator/Admin sees that the reported resource has already been Replaced.
4. Moderator/Admin may mark the report Actioned as “resolved by replacement” if the replacement addresses the concern.
5. Moderator/Admin may Dismiss the report if no issue remains.
6. Moderator may Escalate if Admin review is still needed.
7. Hide/Restrict actions against the original are usually unnecessary because Replaced resources are already non-public.
8. If the replacement resource has the same issue, Moderator/Admin should act directly on the replacement resource through the direct moderation/admin action workflow.

This avoids moving a report silently to a different resource record while still preserving the report history.

### 21.6 Bookmark or Helpful mark on unavailable resource

If a resource with bookmarks or Helpful marks becomes Hidden, Restricted, Removed, or Replaced:

1. Bookmark/Helpful relationships may remain in the database for history.
2. The unavailable file must not be served.
3. User may see a “resource unavailable” or “resource replaced” message.
4. If a replacement exists, the user may navigate to the replacement and bookmark it separately.

Withdrawn resources normally should not have public bookmarks or Helpful marks because they were never Approved. If inconsistent data exists, the same unavailable-resource rule applies.

### 21.7 Low-quality AI output

If AI returns low-quality, incomplete, or inaccurate output, the system does not automatically detect or correct it.

Human review is the safeguard:

* uploader may edit/discard AI suggestions on their own Pending upload,
* Moderator/Admin may edit/discard AI suggestions during review,
* AI output is non-authoritative.

### 21.8 Late AI or retrieval-processing response after status or source change

If an AI-generation, extraction, embedding, indexing, recommendation, or retrieval-preparation request is still processing when the resource status or source file changes:

1. The system rechecks the resource's current status, file availability, AI eligibility, source-file association or fingerprint, and applicable processing context before saving or activating any result.

2. If the result no longer corresponds to the current eligible source state, it must not become valid current output or active retrieval data.

3. For ordinary AI output, a result may be discarded, invalidated, or retained only where the accepted status-specific lifecycle explicitly permits restricted staff-only retention. It must never become public or current merely because the request started while the resource was eligible.

4. Retrieval-derived data produced by an outdated or now-ineligible request must not be marked ready, searched, recommended, retrieved, or used as inquiry evidence. Exact cleanup or invalidation mechanics remain for the later architecture/schema decision.

5. This rule applies to Pending-resource assistance, Approved-resource processing, semantic indexing, related-resource preparation, and inquiry-evidence preparation.

### 21.9 DOCX and PPTX file validation

DOCX and PPTX files are internally ZIP-based formats.

The upload validation process must recognize legitimate Office Open XML formats and must not reject every DOCX/PPTX merely because they have ZIP-like internal structure.

At the same time, ordinary ZIP/RAR/7Z archive uploads remain disallowed in v1.0.

### 21.10 Concurrent replacement submissions

Only one open replacement may exist for one original Approved resource at a time.

The system must enforce this not only through application logic but also through database-level protection such as a uniqueness rule or transaction-safe check. Exact implementation belongs in `DATABASE_DESIGN.md`.

---

## 22. Workflow-to-Database Implications

This section lists database design implications from the workflows without defining the actual schema.

### 22.1 Accounts

The database must support:

* one authoritative role value per account,
* account status separate from role,
* Active and Disabled account states,
* Admin-created Teacher/Instructor, Moderator, and Admin accounts,
* Student self-registration,
* audit trail for account creation, disabling, re-enabling, and role changes.

### 22.2 Resources

The database must support the following resource statuses:

* Pending,
* Needs Correction,
* Approved,
* Rejected,
* Withdrawn,
* Hidden,
* Restricted,
* Removed,
* Replaced.

The database must also support:

* uploader reference,
* resource metadata,
* stored file reference,
* original filename for display,
* randomized storage filename or protected storage reference,
* replacement linkage between original and replacement resources,
* one open replacement per original Approved resource,
* status-change history or audit trail.

### 22.3 Removed resources

For v1.0, Removed resources keep the resource database row but minimize retained uploader-entered content and prevent normal file/content access.

The database and application design must support:

* `status = Removed`;

* exact D040 replacement values:

  * `title = '[Removed resource]'`;
  * `description = '[Removed resource]'`;
  * `topic = '[Removed]'`;
  * `original_filename = '[removed]'`;

* deletion of all `resource_tags` rows for the Removed resource;

* removal reason/note in `resource_action_history`;

* Admin actor and removal timestamp through action history;

* `file_availability = deleted` or `invalidated`;

* deleted or invalidated AI outputs;

* preservation of the resource ID and accepted historical relationships;

* retention of required uploader, course, subject, year-level, and resource-type references;

* retention of technical file metadata, activity counts, AI-notice acknowledgment fields, and timestamps;

* Admin-only accountability/reference access.

The retained record is minimized but not anonymized. Account-linked and broad academic-context data may remain because the accepted schema and historical relationships still require them.

This avoids broken references while removing unnecessary uploader-authored descriptive content and preventing access to removed files and AI-derived content.

### 22.4 Moderation notes and action history

Moderation notes should be stored as action history, not only as one overwritten latest note.

The system should preserve notes for:

* rejection,
* request correction,
* report action,
* hide/restrict/remove,
* direct moderation/admin action,
* replacement approval/rejection.

Exact table design belongs in `DATABASE_DESIGN.md`.

### 22.5 Reports

The database must support report records with:

* independent report status,
* linked resource,
* reporter,
* reason category,
* optional comment,
* status values: Open, Dismissed, Actioned, Escalated,
* resolving actor,
* resolution note,
* resolution timestamp.

Report status must not be confused with resource status.

### 22.6 Bookmarks and Helpful marks

The database must support:

* user-resource bookmark relationships,
* user-resource Helpful relationships,
* uniqueness per user-resource pair,
* persistence even if the resource later becomes unavailable,
* current resource status checks at read time.

Bookmarks and Helpful marks do not automatically transfer to replacement resources.

### 22.7 AI outputs and retrieval-derived data

The accepted database design supports current AI outputs tied to one resource record only.

AI output storage must support:

* draft output for eligible Pending resources;
* retained current output for Approved resources;
* invalidation or deletion when the source resource becomes ineligible;
* separate AI outputs for replacement resources;
* no inheritance or reuse of AI output across replacement resources;
* one current output per accepted resource/output-type combination under the existing design;
* visibility and usability derived from current resource status, output lifecycle state, permissions, and other live checks rather than a conflicting independent public-visibility flag.

The existing Pending-file AI notice acknowledgment is stored separately on the resource through the accepted `ai_notice_acknowledged` and `ai_notice_acknowledged_at` fields. It is not an `ai_outputs` record and must not be moved into or reimplemented through the AI-output store.

**Open architecture/schema item**

Sections 18A–18E and 19 require retrieval-derived behavior for extracted text, chunks, source-location information, embeddings, indexes, provider objects, or equivalent representations supporting semantic search, related-resource suggestions, and repository-grounded inquiry.

No exact table, column, external store, file format, or synchronization mechanism for this retrieval-derived data is authorized by this document.

Per D042 Part F:

* `ai_outputs` remains an AI-output store;
* it must not be overloaded as an extraction, chunk, embedding, retrieval-result, inquiry-history, citation-history, or conversation-history index;
* exact schema additions, if needed, require the feasibility spike and a later explicit architecture/schema decision;
* D033's accepted 18-table baseline remains unchanged until that later decision is accepted.

Retrieved candidate sets, generated inquiry answers, grounded citations, and active-session follow-up context may remain request-scoped or session-scoped and must not be assumed to require permanent database storage.

### 22.8 Search index, inquiry retrieval, and recommendations

To support the required semantic content search, repository-grounded inquiry retrieval, and related-resource suggestion capabilities defined by D041–D042, the selected database and/or retrieval layer must support:

* including only currently eligible Approved resources in general semantic retrieval;
* checking current account status, requester access, resource status, `file_availability`, processing readiness, source freshness, and lifecycle eligibility;
* excluding or disabling retrieval entries when resources become Hidden, Restricted, Removed, Replaced, Withdrawn, Rejected, stale, file-unavailable, unauthorized, or otherwise ineligible;
* independently processing a replacement resource after it becomes eligible rather than inheriting the original resource's retrieval-derived data;
* detecting and excluding stale entries when a source file changes;
* refreshing only the output or retrieval representations affected by relevant metadata changes;
* revalidating every retrieved candidate against the current local source-of-truth database before its content is sent to a language model and before its resource link is returned;
* preventing stale search, recommendation, inquiry, snippet, cache, citation, or provider-side retrieval data from exposing unavailable resources;
* invalidating, disabling, deleting, or otherwise making retrieval-derived data unusable when required by the source resource lifecycle.

This document does not authorize the exact storage mechanism, vector approach, provider integration, table design, column design, index format, or synchronization strategy. Those remain for the feasibility spike and a later explicit architecture/schema decision under D042.

### 22.9 Audit log

The database must support audit logging for important actions.

Audit records should capture:

* actor,
* action type,
* target type,
* target identifier,
* timestamp,
* short reason/note where needed,
* minimal context needed for accountability.

Audit logs should not store full sensitive file contents or unnecessary personal data.

---

## 23. Workflow-to-Test-Case Checklist

This section is a seed checklist for `TESTING_CHECKLIST.md`. It is not the full testing document.

### 23.1 Authentication and registration

* Valid login succeeds.
* Wrong password fails with a generic message.
* Nonexistent identifier fails with the same generic message.
* Disabled account fails with the same generic message.
* Student registration succeeds.
* Duplicate registration identifier fails safely.
* Injected role value during registration does not create elevated role.
* Session expiration blocks protected access.

### 23.2 Account provisioning

* Non-Admin cannot create Teacher/Moderator/Admin accounts.
* Admin can create Teacher/Instructor account.
* Admin can create Moderator account.
* Admin can create Admin account.
* Invalid role value is rejected.
* Disabled account cannot log in.
* Disabling an account does not automatically hide Approved resources.

### 23.2A Admin taxonomy management

* Non-Admin cannot open taxonomy management.
* Admin can add a course/program value.
* Admin can add a subject value.
* Admin can add a year-level value.
* Admin can add a resource-type value.
* Admin can add a controlled tag value.
* Duplicate taxonomy value in the same taxonomy type is rejected.
* Empty taxonomy value is rejected.
* Admin can edit an unused taxonomy value.
* Admin can deactivate a taxonomy value.
* Deactivated taxonomy value does not appear for new uploads.
* Existing resources that reference a deactivated taxonomy value remain valid.
* Admin can reactivate an inactive taxonomy value.
* Taxonomy management actions are logged.

### 23.3 Upload validation

* Student can upload allowed file type.
* Teacher/Instructor can upload allowed file type.
* Moderator/Admin upload attempt is rejected server-side.
* PDF upload accepted.
* DOCX upload accepted.
* PPTX upload accepted.
* TXT upload accepted.
* JPG/JPEG upload accepted.
* PNG upload accepted.
* EXE/BAT/CMD/JS/PHP upload rejected.
* ZIP/RAR/7Z upload rejected.
* Disguised executable with fake extension is rejected.
* Oversized file is rejected.
* Empty/corrupt file is rejected.
* Successful upload always creates Pending resource.

### 23.4 AI assistance — required minimum package

* AI disabled, unavailable, unconfigured, rate-limited, failing, or unreachable: upload, moderation, metadata search/filtering, resource access, bookmarks, Helpful marks, reports, notifications, and Admin/Moderator management still work through their accepted non-AI paths.
* Pending-file AI runs only after all three gates pass: successful basic upload validation, clear uploader notice, and uploader acknowledgment.
* Declining or not acknowledging the Pending-file AI notice skips AI processing but does not fail or cancel the ordinary upload.
* AI failure does not block upload or moderation, undo a successful non-AI action, or change a resource's status.
* Draft Pending-resource AI output is visible only to the uploader for their own resource and to Moderator/Admin where current lifecycle and permissions allow.
* AI cannot approve, reject, publish, validate, Hide, Restrict, Remove, delete, or otherwise change a resource's status.
* AI cannot automatically create, modify, deactivate, or reactivate taxonomy values.
* A late AI response is revalidated before saving or activation and is discarded, invalidated, or retained only according to the current source state and accepted lifecycle.
* Readable-text extraction succeeds for representative readable PDF, DOCX, PPTX, and TXT resources where supported.
* Image-only files and scanned PDFs remain valid repository resources without requiring OCR or AI vision and are clearly unavailable for content-based AI when no readable text can be extracted.
* Processing failure for one AI capability does not remove ordinary Approved-resource visibility or disable another independently ready capability.
* Semantic search returns only currently Approved, accessible, file-available, processed, ready, current, and otherwise eligible resources.
* Metadata search/filtering remains available when semantic search is disabled, unavailable, failing, or not ready.
* Every semantic-search candidate is revalidated against the current local source-of-truth database before any snippet or link is displayed.
* Related-resource suggestions return only currently eligible Approved resources and are revalidated before display.
* Related-resource suggestions do not use persistent learner profiles or behavioral-tracking profiles.
* Related-resource suggestions do not transfer bookmarks, Helpful marks, reports, views, downloads, or other engagement data.
* Repository-grounded inquiry uses only currently eligible Approved resources.
* Every retrieved inquiry candidate is revalidated against the current local source-of-truth database before its content is sent for generation and before its resource link is returned.
* Inquiry sends only the minimum relevant evidence and necessary metadata to the selected generator.
* Substantive academic claims in inquiry responses are grounded in retrieved repository evidence.
* Every substantive inquiry response identifies or links its supporting resource or resources.
* Page, slide, section, heading, or other source locators appear only when preserved reliably and are never fabricated.
* Inquiry clearly states when the repository lacks sufficient eligible evidence instead of silently answering from unsupported general model knowledge.
* Inquiry refuses or redirects prohibited requests involving exam answers, quiz answers, graded-assignment answers, answer keys, grading, or other accepted academic-integrity restrictions.
* Generation failure shows a safe non-technical message, exposes no raw provider error or partial unsafe output, and leaves the non-AI platform usable.
* Every follow-up inquiry turn reruns live account, access, status, file-availability, readiness, freshness, and source-link checks.
* A resource that becomes Hidden, Restricted, Removed, Replaced, file-unavailable, stale, or otherwise ineligible during an inquiry session no longer supports later follow-up responses.
* The application does not create permanent chat history or persistent cross-session memory from inquiry questions, answers, retrieved evidence, citations, or follow-up context. Active-session context is cleared or made unavailable when the inquiry session ends or expires. Provider-side handling remains subject to separately reviewed provider terms and must not be represented as guaranteed by this application behavior.
* Retrieval-derived data becomes stale and is excluded from semantic search, recommendations, and inquiry when the source file changes, until the current file is successfully reprocessed.
* A metadata change refreshes only the AI output or retrieval representation affected by the changed fields. Unaffected current output may remain reusable only when the later architecture confirms that it does not depend on those fields.
* Retrieval-derived data for Rejected, Withdrawn, Hidden, Restricted, Removed, Replaced, file-unavailable, stale, unauthorized, or otherwise ineligible resources is not exposed through public-facing semantic search, related-resource suggestions, inquiry evidence, snippets, citations, caches, or stale links.
* A replacement resource does not inherit the original resource's AI output, extracted content, chunks, embeddings, indexes, provider objects, citation identity, or engagement history.

### 23.4A AI assistance — planned enhancements

These are planned v1.0 enhancements after the required minimum package is stable and are not equal-weight minimum defense blockers.

* Duplicate/similarity indicators, where implemented, are non-authoritative hints only and never automatically Reject, merge, Hide, Restrict, Remove, or definitively label a resource as duplicated.
* Pending-resource duplicate/similarity processing follows the basic-validation, notice, and acknowledgment gate; Approved-resource processing follows the current Approved-resource eligibility, file-availability, readiness, and freshness checks.
* AI moderation hints, where implemented, are staff-oriented assistive information, never take a moderation action, and remain distinguishable from human findings.
* Moderator/Admin independently reviews the resource and applies only actions already permitted by the accepted role and status model.
* Uploader visibility of duplicate/similarity indicators, any uploader-facing explanation related to moderation hints, exact queue placement, severity scoring, action buttons, and automatic escalation remain unresolved unless later explicitly decided.

### 23.5 Moderation

* Moderator can approve Pending resource.
* Moderator can reject Pending resource with reason.
* Moderator can request correction with notes.
* Admin can perform same moderation actions.
* Student/Teacher cannot approve/reject/request correction.
* Second moderation decision on already-decided resource fails cleanly.
* Needs Correction resubmission returns resource to Pending.
* Withdrawal works only for Pending, Needs Correction, or Rejected resources.

### 23.6 Approved access

* Approved resources appear in browse/search.
* Non-Approved resources do not appear in browse/search.
* Direct link to Pending resource is denied.
* Direct link to Hidden resource is denied for general users.
* Direct link to Restricted resource is denied for general users.
* Direct link to Removed resource is denied.
* Direct link to Replaced resource does not serve old file.
* Status is checked at request time.

### 23.7 Bookmarking and Helpful marks

* User can bookmark Approved resource.
* User can remove bookmark.
* User can mark Approved resource as Helpful.
* User can remove Helpful mark if toggle is supported.
* Non-Approved resource cannot be bookmarked.
* Non-Approved resource cannot be marked Helpful.
* Bookmark to unavailable resource does not serve the file.
* Bookmark does not automatically transfer to replacement.

### 23.8 Reporting

* Student can report Approved resource.
* Teacher/Instructor can report Approved resource.
* Moderator/Admin do not use public report workflow.
* User cannot report own resource.
* Duplicate Open report by same user on same resource is blocked.
* Report can be Dismissed.
* Report can lead to Hidden.
* Report can lead to Restricted.
* Report can be Escalated.
* Admin can Remove from report review.
* Moderator cannot Remove even through direct request.
* Request Correction on Approved resource routes to replacement workflow, not Needs Correction status.

### 23.9 Replacement

* Uploader can submit replacement for own Approved resource.
* Non-uploader cannot submit replacement.
* Replacement creates new Pending resource linked to original.
* Original remains Approved while replacement is Pending.
* Only one open replacement allowed per original.
* Approved replacement marks original Replaced.
* Rejected replacement leaves original Approved.
* Withdrawn replacement leaves original Approved.
* Replacement does not inherit original AI output.
* Bookmarks/Helpful marks do not auto-transfer.

### 23.10 Direct moderation/admin action

* Moderator can directly Hide Approved resource without report.
* Moderator can directly Restrict Approved resource without report.
* Moderator cannot Remove directly.
* Admin can directly Hide, Restrict, or Remove Approved resource.
* Direct action requires reason/note.
* Direct action is logged.

### 23.11 Removed-resource sanitization and accountability

* Only Admin can Remove a resource.
* Removed resource is not visible to uploader.
* Removed resource is not visible to general authenticated users.
* `title` becomes `[Removed resource]`.
* `description` becomes `[Removed resource]`.
* `topic` becomes `[Removed]`.
* `original_filename` becomes `[removed]`.
* All `resource_tags` rows for the Removed resource are deleted.
* Removed file content is not served.
* Removed AI outputs are deleted or invalidated and excluded from public-facing reads.
* The resource ID and required accountability/historical relationships remain valid.
* `created_at` remains unchanged.
* `updated_at` changes when the resource row is updated.
* The Admin removal reason remains stored in `resource_action_history`.
* Withdrawn resources are not sanitized and retain their original descriptive fields and tag associations.
* Database writes for removal are transaction-safe.
* Physical file cleanup failure does not restore file access or bypass the status/availability gates.

---

## 24. Document Status and Revision History

**Current status:** Draft v1.0 workflow baseline. Parts 1–3 are complete at workflow level after resolving the major schema-shaping issues surfaced during drafting.

### 24.1 Resolved during workflow drafting

The following decisions were resolved while drafting `WORKFLOWS.md` and were later recorded as D019–D029 in `DECISIONS.md`. They are now reflected in the current `DATABASE_DESIGN.md` and `SECURITY_NOTES.md` baselines where applicable. Their remaining AI-, implementation-, and testing-specific consequences must be carried into `AI_FEATURES.md`, `BUILD_PLAN.md`, and `TESTING_CHECKLIST.md`:

* First Admin account is created during setup only, not through public registration.
* Admin taxonomy management covers add, edit, deactivate, and reactivate behavior for courses/programs, subjects, year levels, resource types, and controlled tags.
* Hidden and Restricted have distinct meanings.
* Withdrawn is a resource status for uploader-withdrawn non-public resources.
* Image uploads are allowed in v1.0 with content-search limitations.
* Request Correction on an Approved resource routes to the linked replacement workflow.
* Only one open replacement may exist for the same original Approved resource.
* Bookmarks and Helpful marks do not transfer automatically to replacements.
* Direct Moderator/Admin action exists even without a filed report.
* Removed keeps a D040-minimized resource row while file content and AI outputs are deleted or invalidated.
* Restricted may return to Approved after review.
* Open reports against a resource that becomes Replaced remain attached to the original and may be closed as resolved by replacement.

### 24.2 Revision history

* **Part 1 — Sections 1–7:** Defined workflow purpose, notation, global rules, authentication, registration, Admin provisioning, Admin taxonomy management, and resource status definitions.
* **Part 2 — Sections 8–17:** Defined upload, validation, Pending-file AI assistance, moderation, Needs Correction, Approved access, bookmarking/helpful feedback, reporting, report review, and replacement workflow.
* **Part 3 — Sections 18–24:** Defined post-approval status handling, AI output lifecycle, invalid action behavior, edge cases, database implications, testing checklist seed, and document status.

* **D040 consistency patch — 2026-07-10:** Defined Removed-resource minimization as exact removal-time sanitization of title, description, topic, and original filename; deletion of associated `resource_tags`; preservation of required accountability relationships; distinction from Withdrawn-resource retention; and coordinated database/file lifecycle handling without changing the confirmed role, status, workflow-module, or table count.

### 24.3 Carry-Forward Dependencies

The current `DATABASE_DESIGN.md` and `SECURITY_NOTES.md` baselines reflect the pre-D041/D042 workflow requirements below. Their AI-related sections require targeted propagation after this workflow update. Future `AI_FEATURES.md`, `BUILD_PLAN.md`, and `TESTING_CHECKLIST.md` must preserve all applicable requirements.

Existing carry-forward requirements:

* the full resource status set;
* linked replacement records;
* one open replacement rule;
* Withdrawn status;
* Removed D040-minimized retained-record approach;
* report statuses;
* binary Helpful marks;
* bookmark persistence;
* AI output lifecycle;
* audit logging requirements.

D041–D042 carry-forward requirements:

* the required minimum v1.0 AI package and the distinction between completed-capstone requirement and runtime-independent non-AI operation;
* the separate Pending-resource AI assistance and Approved-resource general retrieval/inquiry contexts;
* successful basic upload validation plus clear uploader notice plus uploader acknowledgment before Pending-file AI processing;
* readable-text extraction for supported PDF, DOCX, PPTX, and TXT files where extraction succeeds;
* semantic content search, basic related-resource suggestions, repository-grounded inquiry, source attribution, insufficiency behavior, and session-scoped follow-up;
* live account, permission, resource-status, `file_availability`, readiness, and source-freshness checks;
* candidate revalidation before evidence is sent for generation and before links or snippets are returned;
* retrieval-derived-data lifecycle, staleness, invalidation, and replacement non-inheritance;
* no permanent chat-history or cross-session-memory assumption;
* planned duplicate/similarity indicators and AI moderation hints after the required package is stable;
* the feasibility spike and later architecture/schema decision before exact retrieval storage or schema changes are locked.

### 24.4 D041–D042 Propagation

* **D041–D042 propagation — 2026-07-12:** Updated the workflow baseline to distinguish runtime-optional AI behavior from the required completed-capstone AI deliverable. Added Admin AI configuration, restored and expanded the Pending-file AI assistance workflow, separated the planned duplicate/similarity and moderation-hint enhancements, and added provider-neutral workflows for Approved-resource extraction/processing, semantic content search, related-resource suggestions, repository-grounded inquiry, and session-scoped follow-up.
* Extended AI-output lifecycle coverage to retrieval-derived data, source-file and metadata staleness, live candidate revalidation, Hidden/Restricted exclusion, Rejected/Withdrawn cleanup, Removed-resource protection, and replacement non-inheritance.
* Expanded workflow-to-database implications and test-case seeds without authorizing a new table, column, provider, model, package, vector database, or permanent chat-history structure.
* `DATABASE_DESIGN.md` and `schema.sql` remain unchanged in this pass. D033's accepted 18-table baseline remains active until the feasibility spike and a later explicit architecture/schema decision justify and approve any exact change.
* No new role, account status, resource status, report status, LMS feature, autonomous moderation authority, OCR requirement, AI-vision requirement, open-web retrieval capability, persistent cross-session AI memory, or production-scale deployment requirement was introduced.

---