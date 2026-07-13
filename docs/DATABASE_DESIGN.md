# DATABASE_DESIGN.md

**Project:** BPC LearnShare — AI-Assisted Collaborative Academic Resource Sharing and Management System
**Version:** Draft v1.0 — Conceptual Database Design
**Last Updated:** 2026-07-10
**Author:** Nepthalie Jezer B. Macaslang
**Course:** BS Information Systems — Bulacan Polytechnic College
**Status:** Draft v1.0 — accepted conceptual database-design baseline through D040

---

## 1. Purpose and Scope

This document translates the confirmed decisions in `DECISIONS.md` through D040 and the confirmed workflows in `WORKFLOWS.md` — particularly Section 22, Workflow-to-Database Implications — into the conceptual database design for BPC LearnShare v1.0.

**This document defines:**

* What data areas the system must be able to store and query, based only on confirmed workflows.
* The relationships and constraints between those data areas.
* Where a business rule, such as “only one open replacement per resource,” must be enforced at the database level rather than left to application code alone.

**This document does not yet define, and will not until explicitly noted:**

* Exact SQL `CREATE TABLE` statements.
* Exact column names, data types, or lengths.
* Indexes, foreign key syntax, or engine-level tuning.

Those come in a later drafting pass, once the data areas and relationships in this document are reviewed and accepted. A high-level design note is included in a section only where the section cannot be meaningfully understood without one, such as stating that status must be a single field rather than a set of booleans. This is a design principle, not a full schema.

This document targets native PHP with MySQL/MariaDB via prepared statements, using either PDO or mysqli, with no ORM. The deployment target remains XAMPP on Windows for local/LAN capstone demonstration.

---

## 2. Database Design Principles and Assumptions

These principles shape the later schema. They are implementation conventions and database-design assumptions, not new business features.

1. **Status is a single authoritative field per resource, never a set of boolean flags.**
   A resource has exactly one current status at a time. The system must not model resource state using separate fields such as `is_approved`, `is_hidden`, or `is_removed`, because that pattern can create contradictory states. For example, a resource should not be both Approved and Hidden at the same time through two independently set boolean values. The resource record should store one current status value from the confirmed resource status set.

2. **Role is a single authoritative field per account, never a set of boolean flags.**
   Each account has one role value from the confirmed v1.0 role set: Student, Teacher/Instructor, Moderator, or Admin. The database must not collapse Moderator and Admin into a single staff flag, and it must not use multiple boolean role flags.

3. **Status-transition history belongs in action history or audit records, not scattered per-status timestamp columns.**
   The resource record may still use ordinary bookkeeping timestamps such as `created_at` and `updated_at`. However, the design should avoid separate status-specific columns such as `approved_at`, `rejected_at`, `hidden_at`, or `restricted_at` for every possible status. The authoritative timeline of who changed a resource status, when, and why belongs in moderation/action-history or audit-log records.

4. **Use surrogate integer primary keys for v1.0.**
   For a local/LAN academic MVP using native PHP and MySQL/MariaDB, auto-increment integer identifiers are simpler to inspect, debug, and reference during development and defense. UUIDs solve distributed-system problems that this prototype does not currently have.

5. **Use `utf8mb4` character support.**
   The database should support Filipino names, special characters, punctuation, and other text values without character-encoding problems.

6. **No database-backed session table is required for v1.0.**
   The confirmed workflows require server-side session checking and session expiration, but they do not require Admin-visible active-session management or forced logout from a dashboard. PHP’s native session handling is enough for v1.0. A future feature such as “Admin can view or terminate active sessions” would require a separate decision and should not be added quietly through the schema.

7. **Use consistent naming conventions.**
   Final table and column names will be defined in the schema pass, but this document assumes snake_case naming and plural table names for readability and consistency.

---

## 3. Required Data Areas Implied by v1.0 Workflows

This is a checklist-level inventory, not a table definition. Naming a data area here does not mean it must become exactly one table. Some areas may require more than one table once the actual schema is drafted.

| Data Area                                 | One-line Description                                                                           | Primary Source                                                 | Expanded In |
| ----------------------------------------- | ---------------------------------------------------------------------------------------------- | -------------------------------------------------------------- | ----------- |
| Accounts & Roles                          | User accounts, one role value, and Active/Disabled account status                              | `WORKFLOWS.md` Section 22.1, D006–D008, D019                   | Section 4   |
| Account Identifier & Registration Data    | Login identifier model and minimum account/profile data                                        | `WORKFLOWS.md` Section 5.3                                     | Section 5   |
| Academic Taxonomy and Resource Metadata   | Courses/programs, subjects, year levels, topic, resource types, and controlled tags            | `PROJECT_BRIEF.md` Section 5, `USER_ROLES.md` Sections 3 and 7 | Section 6   |
| Resources                                 | Central resource record and academic metadata                                                  | `WORKFLOWS.md` Sections 8 and 22.2                             | Section 7   |
| File Storage Metadata                     | Original filename, stored filename/reference, file type, and file size                         | `WORKFLOWS.md` Sections 9 and 22.2                             | Section 8   |
| Resource Status & Visibility              | Nine canonical resource statuses and visibility derived from status                            | `WORKFLOWS.md` Sections 7 and 22.2                             | Section 9   |
| Moderation Decisions & Action History     | Approve, reject, request correction, direct staff actions, and preserved notes                 | `WORKFLOWS.md` Sections 11 and 22.4, D023                      | Section 10  |
| Replacement Linkage                       | Original-to-replacement resource relationship and one-open-replacement enforcement             | `WORKFLOWS.md` Section 17, D012, D026                          | Section 11  |
| Reports                                   | Report records, controlled reason categories, and report status independent of resource status | `WORKFLOWS.md` Sections 15–16 and 22.5, D029                   | Section 12  |
| Bookmarks, Helpful Marks, Activity Counts | User-resource relationships, binary Helpful marks, view counts, and download counts            | `WORKFLOWS.md` Sections 14 and 22.6                            | Section 13  |
| AI Outputs                                | Draft/retained AI outputs tied to one resource, notice acknowledgment, and invalidation        | `WORKFLOWS.md` Sections 10, 19, and 22.7, D014/D018            | Section 14  |
| Audit Log                                 | Actor, action, target, timestamp, and reason/note for accountability                           | `WORKFLOWS.md` Sections 3.6 and 22.9                           | Section 16  |
| In-App Notifications and Queue Visibility | User-targeted in-app notifications, unread/read state, and live dashboard counts for moderation/report queues | D031, `WORKFLOWS.md` dashboard and moderation/report queue implications | Section 15  |
| System Settings                           | System-level configuration values such as AI enabled/disabled state                                           | `USER_ROLES.md` Admin system-setting authority, D004, D013–D016         | Section 17  |


**Explicitly not a separate data area for v1.0:** user sessions. PHP’s normal server-side session handling is enough unless a future decision adds active-session management.

---

## 4. Accounts, Roles, Account Status, and First Admin Setup Implications

### 4.1 Role

Each account has one authoritative `role` value. The allowed v1.0 roles are:

* Student
* Teacher/Instructor
* Moderator
* Admin

No other role exists in v1.0. Moderator and Admin must remain distinct because Admin has user-management, system-configuration, and destructive authority that Moderator does not have.

The database design must not use separate boolean fields such as `is_student`, `is_teacher`, `is_moderator`, or `is_admin`. That approach can create invalid combinations. A single role value is clearer and easier to enforce.

### 4.2 Account status

Account status is separate from role and separate from resource status.

The v1.0 account statuses are:

* Active
* Disabled

A Disabled account cannot log in, but disabling an account must not automatically hide, restrict, remove, or replace resources that the account previously uploaded. Account status controls login access. Resource status controls resource visibility and availability.

### 4.3 First Admin setup

The first Admin account is created during setup only, outside the normal in-app account provisioning workflow.

There is no need for an `is_first_admin` field or a special first-Admin account type. Once the first Admin account exists, it is simply an Admin account like any later Admin account. The setup procedure belongs in `BUILD_PLAN.md` and `SECURITY_NOTES.md`, not in the database schema.

### 4.4 Account provisioning accountability

The system must be able to preserve accountability for account-related administrative actions, such as:

* creating Teacher/Instructor, Moderator, or Admin accounts,
* disabling or re-enabling accounts,
* changing account roles,
* resetting account passwords through Admin assistance, if implemented.

This can be handled through the general audit log data area. The account record itself does not need to store every administrative event directly, as long as the audit log records actor, target, action, timestamp, and relevant note where needed.

### 4.5 Password recovery and password reset

BPC LearnShare v1.0 does not include self-service password recovery.

For ordinary accounts, password reset is Admin-assisted. An Admin may reset the password of a Student, Teacher/Instructor, Moderator, or another Admin through account management, subject to role and audit rules.

For the first Admin account, recovery is a local setup/database maintenance concern because no earlier Admin exists to assist. The exact procedure should be documented later in `BUILD_PLAN.md` and `SECURITY_NOTES.md`.

**Schema consequence:** v1.0 does not need a password-reset token table, email-reset token fields, or expiring reset-token mechanism. Admin-assisted reset only requires the account record to store an updated password hash and the audit log to record the administrative action.

---

## 5. Student Registration and Admin-Provisioned Account Data

### 5.1 Login identifier

For BPC LearnShare v1.0, the login identifier is a unique self-chosen username.

The username is provided during Student registration or Admin account provisioning and is validated for uniqueness. It is not treated as proof of official institutional identity.

Student number or institutional ID must not be used as the login identifier in v1.0 because the system has no confirmed roster or enrollment validation source. Without such validation, a self-registering Student could enter another person’s student number and the system would have no reliable way to detect it.

Institutional email is also not selected as the v1.0 login identifier because there is no confirmed source document stating that every BPC student and teacher has an institutional email address. Personal email is not required because v1.0 does not require email verification or email notifications.

A student number, institutional ID, or email address may be considered later as an optional profile field if a future workflow needs it. It must not be used as the authentication identifier unless a future decision defines reliable validation.

### 5.2 Minimum Student registration data

Student self-registration requires only:

* username,
* password,
* display name.

Course/program, year level, section, student number, institutional email, and personal email are not required for v1.0 registration unless a later confirmed workflow uses them.

This keeps registration simple and avoids collecting personal data that no confirmed workflow reads. In the confirmed v1.0 design, year level is used as resource metadata, not as a required attribute of the registering Student.

### 5.3 Admin-provisioned account data

Teacher/Instructor, Moderator, and Admin accounts are provisioned by an Admin. These accounts use the same username-based login identifier for consistency.

Admin-provisioned account creation requires:

* username,
* password or temporary credential,
* display name,
* role.

The selected role must be one of the four confirmed v1.0 role values. Teacher/Instructor, Moderator, and Admin users do not self-register and do not submit their own role value.

Temporary credential handling, first-login password change, and password-reset procedure belong in `SECURITY_NOTES.md` and `BUILD_PLAN.md`.

---

## 6. Academic Taxonomy and Resource Metadata

### 6.1 Confirmed resource metadata set

The confirmed resource metadata set for v1.0 is:

* course/program,
* subject,
* year level,
* topic,
* resource type,
* tags.

This set comes from the project objective requiring metadata tagging by course, subject, year level, topic, resource type, and tags.

For database design, these metadata items are separated into two groups:

1. **Admin-managed controlled lookups**

   * course/program,
   * subject,
   * year level,
   * resource type,
   * controlled tags.

2. **Uploader-entered descriptive metadata**

   * topic.

### 6.2 Topic handling

For v1.0, `topic` is resource metadata, not a separate Admin-managed taxonomy table.

The topic should be treated as a short descriptive field entered by the uploader and reviewable by Moderator/Admin during moderation. This keeps the design simple while still supporting topic-based search and filtering.

The topic should be required for ordinary resource uploads unless a later workflow defines a specific case where it is not applicable. If the topic is missing or unclear, Moderator/Admin may request correction during moderation.

### 6.3 Course/program, subject, year level, and resource type

Course/program, subject, year level, and resource type should be controlled values managed by Admin.

This supports consistent filtering and prevents duplicate or misspelled values such as:

* “BSIS,” “B.S.I.S.,” and “Information Systems” being treated as unrelated programs,
* “1st Year” and “First Year” being treated as different year levels,
* “Reviewer” and “Review Material” being treated as different resource types without intention.

For v1.0, year level may be modeled as a simple standalone lookup, such as 1st Year, 2nd Year, 3rd Year, and 4th Year, unless a later requirement needs course-specific year-level rules.

### 6.4 Tags

Tags are controlled, Admin-managed descriptors. They are not free-text values typed freely by uploaders.

Uploaders may select from available tags, and AI may suggest tags if the AI feature is enabled. However, tag creation and tag management remain under Admin authority.

This avoids uncontrolled tag sprawl and keeps search/filtering behavior predictable.

### 6.5 No separate categories table for v1.0

For v1.0, the database design does not include a separate `categories` table or category-management module.

The project currently has no confirmed definition explaining how “category” differs from resource type or tags. To avoid adding an undefined taxonomy layer:

* resource type handles the broad form of the material, such as reviewer, module, presentation, notes, handout, or study guide;
* tags handle controlled topical or descriptive labels;
* topic captures the uploader’s specific topic description.

Existing “category” wording in source documents should be cleaned up so the role/permission documents and database design do not describe different taxonomy models.

### 6.6 AI metadata suggestions and taxonomy

AI may suggest summaries, tags, resource type, or other metadata values if the relevant AI features are implemented. These suggestions are assistive only.

AI suggestions do not create new taxonomy values automatically. If AI suggests a tag or resource type that does not exist in the controlled lookup, a human Moderator/Admin must decide whether to ignore it, map it to an existing value, or later ask an Admin to create the controlled value.

AI does not approve, publish, validate, or modify taxonomy records automatically.

---

## 7. Resource Core Records and Metadata

### 7.1 The resource core record

Every uploaded academic resource is represented by one resource record.

This includes:

* a first-time upload,
* a resource being corrected before approval,
* a replacement resource submitted to correct an already Approved resource.

There is no separate draft table, version table, or LMS-style content table for v1.0. A Pending or Needs Correction resource is edited in place on its own resource record. A correction to an already Approved resource creates a new Pending resource record linked to the original resource.

Conceptually, each resource record must support:

* **Uploader reference** — the Student or Teacher/Instructor who submitted the resource.
* **Title** — uploader-entered text.
* **Description** — uploader-entered text.
* **Topic** — uploader-entered text describing the specific topic covered by the resource.
* **Course/program** — controlled metadata managed by Admin.
* **Subject** — controlled metadata managed by Admin.
* **Year level** — controlled metadata managed by Admin.
* **Resource type** — controlled metadata managed by Admin.
* **Tags** — controlled descriptors managed by Admin.
* **Current status** — one value from the nine confirmed resource statuses.

Tags must be modeled as a relationship, not as one comma-separated text field. A resource may have multiple tags, and the same tag may apply to many resources. A comma-separated tag field would make it difficult to enforce controlled tags and would weaken search/filtering consistency.

### 7.2 Uploader-role enforcement

The database can require that every resource references an existing account. However, a normal account reference alone does not prove that the account has the correct role.

The rule that only Student and Teacher/Instructor accounts may upload ordinary resources must be enforced in the application before resource creation. This matches the workflow requirement that upload permission is checked server-side before a resource record is created.

The database design should still reflect the ownership rule by storing the uploader reference clearly and by avoiding any design that implies Moderator or Admin accounts submit ordinary resources.

### 7.3 Topic requirement

For v1.0, topic is required resource metadata.

The topic is not a separate Admin-managed taxonomy table. It is a short uploader-entered description of the specific lesson, concept, chapter, or subject matter covered by the resource.

This supports basic metadata search without adding a new taxonomy-management burden. If the topic is missing, vague, or unclear, Moderator/Admin may request correction before approval.

Examples:

* “Database normalization”
* “Introduction to PHP sessions”
* “Photosynthesis”
* “Business process modeling”
* “Midterm reviewer for system analysis”

### 7.4 No cached visibility or AI-eligibility flags

The resource record must not store separate flags such as:

* `is_visible`,
* `is_searchable`,
* `is_ai_eligible`,
* `is_downloadable`.

Visibility, searchability, download permission, and AI eligibility are derived from the current resource status and checked at request time.

This prevents stale database values. For example, if a resource becomes Hidden or Removed, an old bookmark or direct file link must not remain usable because of an outdated visibility flag.

---

## 8. File Storage Metadata and Upload Validation Implications

### 8.1 File metadata belongs to the resource record

Each resource has one current file reference.

For v1.0, a separate file-version table is not required. The confirmed workflows do not require preserving older file versions inside the same Pending or Needs Correction resource.

Conceptually, the resource record must support:

* **Original filename** — stored for display only.
* **Stored filename or protected storage reference** — randomized and non-guessable.
* **Validated file type** — one of the allowed v1.0 file types.
* **File size** — used for display and size-limit checks.
* **File availability state** — enough information to prevent serving a file after it has been deleted or invalidated.

The original filename must never be used as the physical storage path.

### 8.2 Allowed file types

The database design must support the v1.0 allowed file types:

* PDF
* DOCX
* PPTX
* TXT
* JPG/JPEG
* PNG

Executable files, scripts, installers, and archive uploads are not part of the allowed file set.

Exact file-size limits, MIME/content validation details, and upload-security implementation belong in `SECURITY_NOTES.md` and the implementation plan, not in this database-design section.

### 8.3 Failed validation attempts

A failed upload validation attempt does not create a resource record.

For example, if the user uploads a disallowed executable file or a corrupt file, the system rejects the upload before resource creation. Any security-relevant failed upload attempt belongs in audit/security logging, not in the resource table.

The resource record should represent accepted uploaded resources only.

### 8.4 File replacement before approval

If a resource is still Pending or Needs Correction, the uploader may replace the file as part of correction or resubmission.

This updates the same resource record’s file reference. It does not create a new resource record and does not require a file-history table.

The previous physical file should be deleted or invalidated after the new file is safely stored. This is a file-handling implementation detail, not a separate schema feature.

### 8.5 File access must check live resource status

A stored file reference does not mean the file is currently allowed to be served.

Every view or download request must check the current resource status before serving the file. This applies even if the user has:

* an old direct link,
* a bookmark,
* a previously opened resource page,
* a copied download URL.

Only resources that are currently allowed by the status and permission rules may be served.

---

## 9. Resource Status Values and Visibility Rules

### 9.1 Canonical resource statuses

Every resource has exactly one current status.

The confirmed v1.0 resource statuses are:

* Pending
* Needs Correction
* Approved
* Rejected
* Withdrawn
* Hidden
* Restricted
* Removed
* Replaced

No other resource status exists in v1.0.

### 9.2 Status-derived visibility

Normal browse/search must return only Approved resources.

All other statuses are excluded from normal browse/search:

* Pending
* Needs Correction
* Rejected
* Withdrawn
* Hidden
* Restricted
* Removed
* Replaced

This rule must be applied when querying resources, not stored as a separate visibility flag.

### 9.3 Status-derived access

A user-resource relationship does not override resource status.

For example:

* a bookmark does not make a Hidden resource viewable,
* a Helpful mark does not make a Restricted resource downloadable,
* an old direct file link does not make a Removed resource accessible,
* an old link to a Replaced resource must not serve the old file.

The system must re-check the current resource status at the time of every access request.

### 9.4 Status-value storage

The final schema pass may decide whether resource status is stored using a database enum-like value, a constrained text value, or a small lookup table.

At this conceptual stage, the important requirement is not the exact storage mechanism. The important requirement is that the database allows only the nine confirmed resource status values and does not allow arbitrary or conflicting statuses.

### 9.5 Status-transition validity

The database should prevent invalid status values. The application should enforce valid status transitions.

For example, the application must prevent:

* a Student from approving a resource,
* an uploader from withdrawing an Approved resource,
* a Moderator from removing a resource when only Admin may remove,
* an Approved resource from moving to Needs Correction instead of using the replacement workflow.

Using database triggers to enforce every transition is possible but too heavy for the v1.0 native PHP/XAMPP scope. Server-side application checks, transactions, and audit/action history are the practical enforcement approach.

### 9.6 Concurrency-safe status changes

Before any status-changing action is saved, the system must re-check the current resource status.

For example, if two staff users try to act on the same Pending resource at nearly the same time, only the first valid committed decision should succeed. The second action should fail cleanly because the resource is no longer in the expected status.

The database design must support transaction-safe updates where the intended change depends on the current status. This avoids accidental overwriting of another user’s moderation decision.

---

## 10. Moderation Decisions, Notes, and Action History

### 10.1 Purpose of resource action history

Moderation notes and status decisions must be preserved as history.

The system must not store only one overwritten “latest note” on the resource record. That would lose important context after later actions.

Resource action history is needed for:

* approval,
* rejection,
* request correction,
* resubmission after Needs Correction,
* hide,
* restrict,
* return from Hidden or Restricted to Approved,
* Admin removal,
* replacement approval,
* replacement rejection,
* direct Moderator/Admin action without a report.

### 10.2 Conceptual data captured per action

Each resource action-history entry should capture:

* the affected resource,
* the actor who performed the action,
* whether the actor was a human user or the system,
* the action taken,
* the previous resource status,
* the new resource status,
* the reason or note, when required,
* the related report, if the action came from a report,
* the timestamp of the action.

This keeps the resource history understandable even after many status changes.

### 10.3 Human actions and system-triggered actions

The design must distinguish human decisions from automatic system consequences.

For example, when a replacement resource is Approved:

1. The Moderator/Admin approves the replacement resource.
2. The system changes the original resource to Replaced.

These should be recorded as related but separate action-history entries:

* one human action against the replacement resource,
* one system-triggered action against the original resource.

This prevents the original resource from appearing to change status without an understandable reason.

### 10.4 Notes and reasons

Some actions require a reason or note.

Examples:

* Reject,
* Request Correction,
* Hide,
* Restrict,
* Remove,
* direct Moderator/Admin action without a report.

The note should be preserved with the action-history entry, not overwritten on the resource record.

### 10.5 Relationship to the general audit log

Resource action history is the resource-specific history of moderation and status changes.

The general audit log, covered later in Section 16, is broader. It may include account creation, role changes, account disabling, system-setting changes, AI-related administrative actions, and other security-relevant events.

The final schema may implement these as separate tables or as one unified audit/action table with enough structure to distinguish target type and action type. The important design rule is that resource status history must remain queryable and understandable.

---

## 11. Linked Replacement-Record Design and One-Open-Replacement Enforcement

### 11.1 Replacement as a linked resource record

Corrections to an Approved resource do not edit the original resource in place.

Instead, the corrected upload creates a new Pending resource record linked to the original Approved resource.

The original resource remains Approved and visible while the replacement is Pending. If the replacement is Approved, the original becomes Replaced and the replacement becomes the current visible Approved resource. If the replacement is Rejected or Withdrawn, the original remains unchanged.

The database design must therefore support a self-referencing resource relationship:

* ordinary uploads have no replaced-resource reference,
* replacement uploads reference the original resource they intend to replace.

### 11.2 Replacement chains

A resource may be corrected more than once over time.

For example:

* Resource A is Approved.
* Resource B is submitted as a replacement for Resource A.
* Resource B is Approved, so Resource A becomes Replaced.
* Later, Resource C is submitted as a replacement for Resource B.

In this case:

* Resource B references Resource A as the resource it replaced.
* Resource C references Resource B as the resource it replaces.

This allows replacement history to be followed without building a complex full versioning system.

### 11.3 Why ordinary uniqueness on the replacement link is not enough

The system must allow only one open replacement per original Approved resource at a time.

However, a simple rule such as “only one resource may ever reference the same original” would be wrong. If a replacement is Rejected or Withdrawn, the uploader may later submit another replacement for the same original.

Therefore, the database must prevent multiple open replacements, not multiple historical replacement attempts.

### 11.4 Open replacement tracking

To enforce the one-open-replacement rule safely, the database design should include a small open-replacement tracking structure.

This structure tracks only currently open replacement attempts. An open replacement is a replacement resource with status Pending or Needs Correction.

Conceptually:

* when a replacement is submitted, the system creates an open-replacement tracking record for the original resource;
* if another open replacement already exists for the same original resource, the new replacement submission is rejected;
* when the replacement is Approved, Rejected, or Withdrawn, the open-replacement tracking record is cleared;
* if the replacement moves between Pending and Needs Correction, the tracking record remains because the replacement is still open.

This supports D026 at the database level and avoids relying only on a fragile application-side “check first, insert later” pattern.

### 11.5 Atomic replacement approval

Approving a replacement requires multiple related changes:

* the replacement resource becomes Approved,
* the original resource becomes Replaced,
* the open-replacement tracking record is cleared,
* resource action-history entries are created.

These changes must be treated as one atomic operation. The system must not allow a partial result where the replacement is Approved but the original remains Approved, or the original is Replaced but the replacement is not Approved.

### 11.6 Replacement rejection or withdrawal

If a replacement is Rejected or Withdrawn:

* the replacement resource keeps its own status,
* the original resource remains unchanged,
* the open-replacement tracking record is cleared,
* draft AI outputs tied to the rejected or withdrawn replacement are deleted or invalidated according to the AI lifecycle rules.

A rejected or withdrawn replacement does not block future replacement attempts for the same original resource.

### 11.7 AI outputs and replacements

AI outputs are tied to one specific resource record.

A replacement resource must not inherit, reuse, or display AI outputs from the original resource. If AI processing is enabled and the replacement is eligible, the replacement may generate its own AI outputs.

This follows naturally from the linked-record design because the replacement has its own resource identity.

### 11.8 Original resource status changes while a replacement is open

If the original resource changes status while a replacement is still Pending or Needs Correction, the system must not silently approve, reject, withdraw, or delete the open replacement.

Before a replacement is approved, the system must re-check the original resource’s current status.

For v1.0:

* If the original resource is still Approved, normal replacement approval applies.
* If the original resource is Hidden or Restricted, Moderator/Admin must review the original’s current status before approving the replacement. If the replacement is approved, the replacement becomes Approved and the original becomes Replaced in the same atomic action. The action history must record the original resource’s previous status.
* If the original resource is Removed or already Replaced, the pending replacement must not be approved as the replacement for that original. Moderator/Admin must reject or otherwise resolve the open replacement according to allowed workflows.
* The open-replacement tracking record remains until the replacement is resolved as Approved, Rejected, or Withdrawn.

This prevents an open replacement from becoming detached from the current state of the original resource.

---

## 12. Reports and Report Status Handling

### 12.1 Report record

A report is a user-submitted concern about an Approved resource.

Conceptually, each report record must support:

* the reported resource,
* the reporting user,
* the controlled report reason,
* an optional comment,
* the current report status,
* escalation information, if the report is escalated,
* final resolution information, if the report is dismissed or actioned.

Report status is separate from resource status. A report may be Open, Dismissed, Actioned, or Escalated. Changing a report status does not automatically mean the resource status changes unless Moderator/Admin also takes a resource action such as Hide, Restrict, or Remove.

### 12.2 Controlled report reasons

The v1.0 report reason categories are fixed controlled values:

* Outdated resource
* Incorrect or inaccurate content
* Inappropriate content
* Duplicate or near-duplicate resource
* Suspected leaked exam, quiz, or answer key
* Copyright or unauthorized material concern
* Other

The final schema pass may decide whether these reason values are stored as an enum-like value or through a small lookup table. At this conceptual stage, the important rule is that users select from the controlled list instead of submitting report reasons as unrestricted free text.

The optional comment field may provide additional detail, especially for serious concerns such as suspected leaked exams, answer keys, or copyright issues.

### 12.3 Report status and resolution data

A report’s lifecycle is simpler than a resource’s lifecycle.

Most reports follow one of these paths:

* Open → Dismissed
* Open → Actioned
* Open → Escalated → Actioned
* Open → Escalated → Dismissed

Because report history is shallow, v1.0 does not need a separate report-history table. The report record itself should preserve enough resolution data to remain understandable.

Conceptually, a report should support:

* current report status,
* escalation actor, if escalated,
* escalation timestamp, if escalated,
* resolving actor, if dismissed or actioned,
* resolution note,
* resolution timestamp.

If report review also changes the related resource status, the detailed status transition belongs in resource action history. The report record only needs to show how the report itself was handled.

### 12.4 One unresolved report per user per resource

A user may not create multiple unresolved reports for the same resource.

For database design, this should be enforced through an active-report tracking structure rather than a permanent uniqueness rule on all report history.

A plain unique rule on reporter and resource would be too strict because a user may be allowed to report the same resource again after the previous report is Dismissed or Actioned. The system must prevent duplicate unresolved reports, not prevent all future reports forever.

For v1.0, the active-report tracking structure should behave as follows:

* when a report is created with status Open, an active-report tracking record is created for that reporter-resource pair;
* if an active-report tracking record already exists for the same reporter-resource pair, another report is rejected;
* if a report becomes Escalated, the active-report tracking record remains because the report is still unresolved;
* when the report becomes Dismissed or Actioned, the active-report tracking record is cleared.

This gives the “one unresolved report per user per resource” rule database-level protection without deleting historical report records.

### 12.5 Reporting own resources

A user must not report their own resource through the public report workflow.

This does not require a separate database field. The system checks the report submitter against the resource uploader at submission time. If they are the same account, the report is rejected.

Uploader concerns about their own resources are handled through upload management, withdrawal before approval, or the linked replacement workflow after approval.

### 12.6 Reports stay attached to the original resource

If a reported resource later becomes Replaced, the report remains attached to the original resource.

The system must not silently move the report to the replacement resource. If the replacement has the same problem, staff may act directly on the replacement or a new report may be filed against the replacement if allowed.

If the replacement resolves the issue, Moderator/Admin may close the original report as Actioned with a resolution note such as “resolved by approved replacement.” This is a resolution note, not a new report status.

---

## 13. Bookmarks, Helpful Marks, Activity Counts, Views, and Downloads

### 13.1 Bookmarks

A bookmark is a user-resource relationship.

A user may bookmark an Approved resource and may later remove the bookmark. Each user-resource pair should have at most one bookmark relationship.

Unlike reports or replacements, bookmarks do not need an active-tracking helper table. If the user removes the bookmark, the relationship can be removed or marked inactive according to the final schema design. The key rule is that duplicate bookmark rows for the same user and resource must not exist.

A bookmark does not grant access to a resource. If a bookmarked resource later becomes Hidden, Restricted, Removed, or Replaced, the bookmark may remain for history or display purposes, but the resource file must not be served unless the current resource status allows it.

### 13.2 Helpful marks

Helpful feedback is a binary toggle only.

A user-resource pair may have at most one Helpful mark. There is no numeric rating, star rating, written review, or score in v1.0.

If the user toggles Helpful off, the system may remove the relationship or mark it inactive depending on the final schema design. The conceptual requirement is simple: each user can either have a Helpful mark for a resource or not have one.

### 13.3 View and download counts

View and download tracking supports basic engagement counts and listings such as most-viewed or most-downloaded resources.

For v1.0, aggregate counters are enough. The system does not need a full per-view or per-download event log unless a later version requires time-based analytics, per-user reading history, or advanced reporting.

Conceptually, the database must support:

* total view count per resource,
* total download count per resource.

These counts are used for sorting or display. They do not affect approval, visibility, moderation status, or resource ownership.

### 13.4 Non-transfer to replacements

Bookmarks, Helpful marks, reports, views, and downloads do not automatically transfer from an original resource to its replacement.

A replacement resource is a separate resource record with its own identity. Users may bookmark or mark the replacement as Helpful separately. The replacement starts with its own activity counts.

This preserves the meaning of user interactions. A user who bookmarked, viewed, downloaded, or marked the original resource Helpful did not necessarily interact with the replacement.

### 13.5 Status checks still control access

Bookmarks, Helpful marks, views, and downloads must not bypass resource status rules.

For example:

* a bookmark to a Hidden resource must not serve the file;
* a Helpful mark on a Restricted resource must not make it visible to general users;
* an old view/download link to a Removed resource must fail safely;
* a Replaced resource must not serve the old file to general users.

Resource access is always checked against the current resource status at request time.

---

## 14. AI Output Storage, Notice Acknowledgment, and Invalidation

### 14.1 AI output belongs to one resource

Every AI output must be tied to exactly one resource record.

AI output must not exist independently from its source resource, and it must not be copied or inherited by a replacement resource.

Conceptually, AI output storage must support:

* the related resource,
* the AI output type,
* the generated content or stored AI result,
* lifecycle state or invalidation status,
* when the output was generated,
* whether the output is draft, retained, or invalidated.

Possible AI output types include:

* draft summary,
* suggested tags,
* suggested metadata values,
* duplicate or similarity hints,
* moderation-support hints,
* content-search derived output, if Phase 4 is implemented.

AI output is assistive only. It must not approve, reject, publish, hide, restrict, validate, or delete a resource.

### 14.2 Draft output for Pending resources

AI output generated for a Pending resource is draft output.

Draft AI output may be visible to:

* the uploader for their own Pending resource,
* Moderator/Admin during review.

It is not visible to general authenticated users and does not make the resource public.

If the Moderator/Admin approves the resource and the AI output was visible and not discarded, accepted AI output may be retained according to the approved-resource visibility rules.

### 14.3 Notice acknowledgment

Pending-file AI processing may occur only if the uploader has received and acknowledged the AI-processing notice.

The database must support recording this acknowledgment without making AI required for upload.

Conceptually, the system must be able to know:

* whether the uploader acknowledged the AI-processing notice,
* when the acknowledgment occurred,
* which resource or upload/resubmission it applies to.

If the uploader does not acknowledge the notice, the upload may still proceed through the normal non-AI workflow. The only effect is that Pending-file AI processing is skipped.

This prevents AI notice acknowledgment from becoming a hidden upload requirement.

### 14.4 File changes and AI output freshness

AI must not be repeatedly called for an unchanged file.

If a resource is resubmitted with the same file and only metadata changes, existing draft AI output may remain available.

If the file changes, the old AI output must be discarded or invalidated because it may no longer describe the current file. If AI is enabled and notice requirements are satisfied, new AI output may be generated for the new file.

The schema should therefore allow AI output to be associated with the resource and the file reference or file state that produced it.

### 14.5 Visibility restriction versus invalidation

There are two different AI lifecycle behaviors.

**Visibility restriction** applies when an Approved resource becomes Hidden or Restricted. In this case:

* AI output is removed from public-facing views;
* AI output is excluded from recommendations and content-based search;
* AI output may remain visible to uploader, Moderator, and Admin according to resource visibility rules;
* if the resource returns to Approved, the AI output may become visible again.

**Invalidation or deletion** applies when a resource becomes Rejected, Withdrawn, or Removed. In this case:

* draft or retained AI output must no longer be visible or searchable;
* AI output must not appear in recommendations, summaries, reports, or content search;
* the system may delete the AI output content or keep only a minimal invalidated record if needed for accountability.

This distinction matters because Hidden and Restricted are not the same as Rejected, Withdrawn, or Removed.

### 14.6 Replaced resources and AI output

When a resource becomes Replaced, AI outputs from the original resource must not transfer to the replacement.

The replacement resource may generate its own AI output only if AI is enabled and the replacement independently satisfies AI eligibility rules.

AI output from the original resource must not be reused as the replacement’s summary, tags, recommendations, or search content.

---

## 15. In-App Notifications and Queue Visibility

### 15.1 Purpose

Lightweight in-app notifications and queue visibility help prevent uploads, moderation decisions, and reports from being unnoticed.

This does not change moderation rules. A notification does not approve a resource, publish a file, escalate a report automatically, or create an urgent bypass.

### 15.2 Dashboard counts are computed live

Moderator/Admin dashboard counts do not need stored counter fields.

The system can compute these values from existing data:

* Pending resources,
* Open reports,
* Escalated reports,
* Pending Teacher/Instructor uploads.

These counts should be queried at page load or dashboard refresh. Storing them separately would risk stale counts and unnecessary synchronization work.

### 15.3 Teacher upload badge, filter, and sort

Teacher/Instructor uploads may be visually prioritized in the moderation queue.

This can be supported using existing relationships:

* resource → uploader account,
* uploader account → role.

No new resource status is needed. A “Teacher Upload” badge, filter, or sort option is a queue display feature, not a workflow change.

Teacher/Instructor uploads still enter Pending status and still require Moderator/Admin approval before public visibility.

### 15.4 Stored in-app notifications

The database should support a lightweight notifications data area for unread/read in-app messages.

Conceptually, each notification should support:

* recipient account,
* notification message or message key,
* related target type, such as resource, report, or moderation queue,
* related target identifier, if applicable,
* read/unread state,
* creation timestamp,
* read timestamp, if read.

Notifications are pull-based. They are shown after login, page load, or dashboard refresh. The system does not use email, SMS, push notifications, WebSockets, or real-time delivery in v1.0.

### 15.5 Notification recipients

For v1.0, notifications may be created for specific user accounts.

Examples:

* notify an uploader when their resource is Approved, Rejected, or marked Needs Correction;
* notify an uploader when a correction note is available;
* notify active Moderator/Admin accounts when a Teacher/Instructor upload enters the Pending queue;
* notify Admin accounts when a report is escalated and requires Admin-level action.

The final build may rely more heavily on dashboard counts for staff queues, but the database design should not prevent user-targeted in-app notifications where D031 requires them.

### 15.6 Notification limits

In-app notifications are only visibility aids.

They must not:

* replace server-side permission checks,
* allow access to a resource that the user cannot normally view,
* expose file contents directly,
* send content outside the system,
* create urgent-publication behavior,
* auto-approve Teacher/Instructor uploads.

If a notification links to a resource, report, or queue page, that destination must still perform normal permission and status checks.

---

## 16. Audit Logs

### 16.1 Purpose of the general audit log

The general audit log preserves accountability for administrative and system-level actions.

Resource-specific moderation and status changes are handled by resource action history in Section 10. The general audit log covers broader actions that are not only resource-status events.

This keeps the design clear:

* resource action history records what happened to a resource;
* the general audit log records administrative and system-level actions.

### 16.2 Actions covered by the general audit log

The general audit log should capture important actions such as:

* account creation,
* account disabling,
* account re-enabling,
* role changes,
* password reset by Admin,
* system setting changes,
* AI feature enable/disable changes,
* other security-relevant administrative actions.

Resource removal may also be referenced in the general audit log if the implementation wants a system-wide view of destructive actions, but the detailed resource status transition must still be preserved in resource action history.

### 16.3 Conceptual data captured per audit entry

Each general audit entry should support:

* actor account,
* action type,
* target type,
* target identifier,
* timestamp,
* short note or reason when applicable,
* minimal context needed for accountability.

The audit log must not store full uploaded file contents, AI output content, passwords, API keys, or unnecessary personal data.

### 16.4 System settings storage is a separate data area

Admin has authority to manage system-wide settings, including enabling or disabling AI features. The general audit log records changes to those settings, but it does not store the current setting values themselves.

The database design therefore uses the separate system-settings data area defined in Section 17.

At minimum, that later section must support:

* AI enabled/disabled setting,
* setting value,
* last updated timestamp,
* actor who changed the setting or an audit reference.

This is not a new feature. It is the database support required for an already-confirmed Admin permission.

### 16.5 Audit usefulness after account or resource changes

Audit history must remain useful even after an account is disabled or a resource is removed.

This is supported by two existing design rules:

* accounts are disabled rather than physically deleted;
* Removed resources keep D040-minimized Admin accountability/reference data without retaining uploader-entered descriptive fields or controlled-tag associations.

Because the referenced account or resource record remains available, audit entries do not become meaningless broken references after later status changes.

### 16.6 Audit logs are not activity analytics

The audit log is for accountability, not user behavior analytics.

It should not become a general event tracker for every page view, download, search, bookmark toggle, or Helpful mark. Those belong to their own data areas or aggregate counters where needed.

Keeping audit logs focused prevents the database from becoming noisy and difficult to review during development and defense.

---

## 17. System Settings and Configuration

### 17.1 Scope

BPC LearnShare v1.0 needs database support for system-wide settings that Admin can manage through the application.

For v1.0, the confirmed system setting is the AI enabled/disabled state. This supports the rule that AI features are optional and that the core platform must continue working when AI is disabled, unavailable, or unconfigured.

This section does not create a full configuration-management module. It only defines the small database support needed for confirmed system settings.

### 17.2 Settings storage

System settings should be stored in a small database-backed settings area rather than only in a PHP config file.

A PHP config file is useful for developer-only environment values, but it is not enough for settings that Admin is expected to manage through the application. Since Admin has authority to enable or disable AI features, the current value must be stored somewhere the application can read and update through an Admin-only interface.

Conceptually, the settings data area must support:

* setting name,
* setting value,
* last updated timestamp,
* last updated actor or audit reference.

For v1.0, the only confirmed setting that must be supported is whether AI processing is enabled system-wide.

### 17.3 AI enabled/disabled behavior

The AI enabled/disabled setting is checked only when an AI-assisted operation would run.

If AI is disabled:

* upload still works,
* moderation still works,
* metadata search/filtering still works,
* view/download still works,
* bookmark and Helpful marks still work,
* reports still work,
* dashboard and queue visibility still work.

Disabling AI must not disable the core platform.

This keeps the database design aligned with the project rule that AI is optional, assistive, and not required for basic use.

### 17.4 Setting changes and audit logging

Changes to system settings should be audit-logged.

This includes enabling or disabling AI features. Even though system-setting changes are not resource moderation actions, they are still Admin-level actions that affect system behavior.

The general audit log should therefore support target references that are not limited to user accounts. At minimum, it should be able to record whether the target was an account or a system setting.

This does not create an open-ended audit design for every possible object. For v1.0, the general audit log only needs to support the target types required by confirmed workflows and settings.

---

## 18. Data Integrity Rules and Constraints

### 18.1 Closed-set values

The database design must protect fixed value sets from invalid entries.

The following values are closed sets in v1.0:

* role: Student, Teacher/Instructor, Moderator, Admin;
* account status: Active, Disabled;
* resource status: Pending, Needs Correction, Approved, Rejected, Withdrawn, Hidden, Restricted, Removed, Replaced;
* report status: Open, Dismissed, Actioned, Escalated;
* report reason categories from D029;
* allowed file types: PDF, DOCX, PPTX, TXT, JPG/JPEG, PNG.

The final schema pass may choose the exact mechanism for each closed set, such as enum-like values or lookup tables. The conceptual rule is that arbitrary values must not be accepted.

### 18.2 Referential integrity

The database must preserve relationships between records.

At a conceptual level, the design must ensure that references point to existing records for:

* resource uploader account,
* resource taxonomy values,
* resource tags,
* replacement resource linkage,
* report reporter and reported resource,
* bookmark user and resource,
* Helpful mark user and resource,
* AI output and source resource,
* notification recipient,
* audit/action target references.

Foreign-key-style references can confirm that a related record exists. They do not automatically enforce every business rule.

For example, a database reference can confirm that a resource uploader account exists, but it does not by itself prove that the account is a Student or Teacher/Instructor. Role-based business rules still require server-side application checks.

### 18.3 Business-rule enforcement boundaries

Some rules are best enforced directly by database constraints or tracking structures, while others are enforced by application logic.

Database-supported enforcement is required or recommended for:

* valid closed-set values,
* unique username,
* one open replacement per original resource,
* one unresolved report per user per resource,
* unique bookmark per user-resource pair,
* unique Helpful mark per user-resource pair.

Application-level enforcement is required for:

* checking that only Student and Teacher/Instructor users can upload,
* checking that Teacher/Instructor uploads still enter Pending,
* checking that only Moderator/Admin can approve, reject, request correction, hide, or restrict,
* checking that only Admin can remove resources,
* checking status transitions before state changes,
* checking live resource status before view/download access.

This balance keeps the design practical for native PHP and MySQL/MariaDB without requiring heavy trigger-based business logic.

### 18.4 Non-transfer and non-inheritance

Bookmarks, Helpful marks, reports, activity counts, and AI outputs do not transfer automatically to replacement resources.

A replacement is a separate resource record with its own identity. Any interaction with the replacement must be recorded against the replacement itself.

This prevents the system from incorrectly implying that a user viewed, downloaded, bookmarked, marked Helpful, or reported a replacement resource when they only interacted with the original.

### 18.5 Minimal records for accountability

Removed and Withdrawn resources preserve different levels of database history. They must not be treated as identical lifecycle outcomes.

For Removed resources:

* the resource row remains because reports, action history, bookmarks, Helpful marks, replacement relationships, and other accepted records may still reference the resource ID;
* `title` is overwritten with `[Removed resource]`;
* `description` is overwritten with `[Removed resource]`;
* `topic` is overwritten with `[Removed]`;
* `original_filename` is overwritten with `[removed]`;
* all `resource_tags` rows linked to the resource are deleted;
* file content is deleted or invalidated and `file_availability` no longer permits serving;
* AI outputs are deleted or invalidated;
* resource ID, uploader reference, required course/subject/year-level/resource-type references, technical file metadata, activity counts, AI-notice acknowledgment fields, timestamps, and accepted historical relationships remain;
* normal users and the uploader cannot access the resource;
* only Admin may access the remaining accountability/reference data.

This produces a record that is minimized within the accepted v1.0 schema but is not anonymized. Retained uploader and taxonomy references may still identify the account and preserve broad academic context.

For Withdrawn resources:

* the resource row remains with its original title, description, topic, original filename, and controlled-tag associations;
* file content and draft AI outputs are deleted or invalidated;
* the uploader, Moderator, and Admin may retain enough history to understand that the resource was withdrawn;
* D040 sanitization does not apply.

This distinction preserves accountability without incorrectly applying the stronger Admin-removal minimization rule to uploader-initiated withdrawal.

---

## 19. Transaction and Atomic Write Boundaries

### 19.1 General rule

State-changing actions must re-check the current database state before committing.

The system must not rely only on what the user saw when the page first loaded. Resource status, report status, account status, or replacement state may have changed before the user submits an action.

For important state changes, the system should use transaction-safe operations so that related updates succeed together or fail together.

### 19.2 Resource moderation decisions

Moderation decisions must check that the resource is still in the expected status before applying the decision.

For example:

* Approve should only succeed if the resource is still Pending.
* Reject should only succeed if the resource is still Pending.
* Request Correction should only succeed if the resource is still Pending.
* Resubmission should only succeed if the resource is still Needs Correction.
* Withdrawal should only succeed if the resource is Pending, Needs Correction, or Rejected.

If another staff user or the uploader already changed the resource state, the later action should fail cleanly.

### 19.3 Replacement approval

Approving a replacement is one of the most important atomic operations in the system.

When a replacement is approved, the following must happen together:

* the replacement resource becomes Approved;
* the original resource becomes Replaced;
* the open-replacement tracking record is cleared;
* action-history entries are created for both the replacement approval and the original resource becoming Replaced;
* AI/search visibility updates are triggered or prepared according to lifecycle rules.

The system must not allow a partial result where the replacement becomes Approved but the original remains Approved, or the original becomes Replaced while the replacement does not become Approved.

Before approving the replacement, the system must re-check the original resource’s current status.

For v1.0:

* if the original is still Approved, normal replacement approval applies;
* if the original is Hidden or Restricted, Moderator/Admin must review the current original status before approving the replacement;
* if the original is Removed or already Replaced, the pending replacement must not be approved as the replacement for that original.

This implements the D030 rule.

### 19.4 Replacement rejection or withdrawal

If a replacement is Rejected or Withdrawn:

* the replacement resource keeps its own final status;
* the original resource remains unchanged;
* the open-replacement tracking record is cleared;
* draft AI outputs tied to the replacement are deleted or invalidated according to AI lifecycle rules;
* the action is recorded where applicable.

These updates should be handled as one transaction-safe operation so that the open-replacement tracking record does not remain locked after the replacement is resolved.

### 19.5 Report resolution

The active-report tracking record must remain while a report is unresolved.

For v1.0:

* Open reports are unresolved;
* Escalated reports are still unresolved;
* Dismissed and Actioned reports are resolved.

When a report becomes Dismissed or Actioned:

* the report status is updated;
* resolution data is recorded;
* the active-report tracking record is cleared.

These changes should happen together. The system must not leave a resolved report with an uncleared active-report lock.

### 19.6 Direct moderation/admin actions

Direct Hide, Restrict, and Remove actions must also be transaction-safe.

For example:

* Moderator/Admin hides an Approved resource;
* Moderator/Admin restricts an Approved or Hidden resource;
* Admin removes a resource.

The system must re-check the actor’s role, account status, and the current resource status before committing the action. The resource status change and action-history record should be saved together.

### 19.7 Setting changes

System setting changes, such as enabling or disabling AI, should be saved together with an audit entry.

If the setting value changes but the audit entry is not created, accountability is weakened. If the audit entry is created but the setting does not actually change, the audit log becomes misleading.

The setting update and audit entry should therefore be treated as one related write operation.

### 19.8 What is not required

v1.0 does not require distributed transactions, message queues, advanced locking frameworks, or production-scale concurrency engineering.

Standard database transactions, live status checks, and simple tracking structures are enough for a local/LAN academic MVP.

---

## 20. Indexing and Search Support

### 20.1 Metadata search

The core search and filtering feature depends on resource metadata.

The database design should support efficient filtering by:

* resource status,
* course/program,
* subject,
* year level,
* topic,
* resource type,
* tags.

Normal browse/search must return only Approved resources for general authenticated users.

The final schema pass will decide exact index definitions. This section only identifies the fields that matter for search.

### 20.2 Tag filtering

Tags are controlled values connected to resources through a relationship.

Filtering by tag should use that relationship, not a comma-separated text field. This supports cleaner filtering and avoids unreliable string matching.

### 20.3 Sorting and activity-based listings

The database design should support simple sorting and listing options such as:

* newest resources,
* most viewed,
* most downloaded,
* most Helpful.

These use existing resource timestamps, view/download counters, and Helpful mark counts. They do not require a separate analytics system.

### 20.4 Content-based and AI-assisted search

If Phase 4 content-based search is implemented, its index must follow resource eligibility and status rules.

When a resource leaves Approved status, any content-based or AI search entry tied to it must be removed, disabled, or invalidated.

This applies when a resource becomes:

* Hidden,
* Restricted,
* Removed,
* Replaced,
* Withdrawn,
* Rejected.

When a replacement becomes Approved, the replacement must be indexed based on its own content. It must not reuse the original resource’s AI or content-search output.

The exact indexing technology belongs in `AI_FEATURES.md` and the build plan, not in this conceptual database section.

### 20.5 Dashboard counts

Moderator/Admin dashboard counts should be computed from live data rather than stored as separate counters.

Examples:

* Pending resources,
* Pending Teacher/Instructor uploads,
* Open reports,
* Escalated reports.

Live counts reduce the risk of stale dashboard values.

---

## 21. Security and Privacy Implications Affecting Database Design

This section stays narrow. It covers database design implications only. Full security and privacy rules belong in `SECURITY_NOTES.md` and `DATA_PRIVACY.md`.

### 21.1 Password storage

The account data area must store only password hashes.

Plaintext passwords, reversible encrypted passwords, and password hints must not be stored.

Password hashing behavior belongs in implementation and security documentation, but the database design must assume that only a password hash is stored.

### 21.2 File path privacy

The database must separate:

* original filename for display,
* randomized stored filename or protected storage reference.

The original filename must not be used as the physical storage path. This helps prevent predictable file access and protects uploaded files from direct guessing.

### 21.3 Minimal account data collection

Student registration requires only:

* username,
* password,
* display name.

Course/program, year level, section, student number, and email are not required unless a future confirmed workflow needs them.

This reduces unnecessary personal data collection and supports basic data-privacy principles for a student-facing academic system.

### 21.4 Removed and Withdrawn resource data

Removed and Withdrawn resources have different retention behavior.

For Removed resources, D040 minimizes uploader-entered descriptive content by replacing title, description, topic, and original filename with the confirmed placeholders and deleting associated `resource_tags` rows. The resource ID, required references, technical metadata, timestamps, activity counts, and historical relationships remain where needed for accountability and referential integrity.

The resulting Removed-resource record is minimized but not anonymized. It remains inaccessible to normal users and the uploader.

For Withdrawn resources, the original descriptive metadata and tag associations remain. File content and draft AI outputs are deleted or invalidated according to D020.

In both cases, resource status and `file_availability` must prevent unauthorized file access.

### 21.5 Audit log limits

Audit logs should store the minimum information needed for accountability.

They should not store:

* full uploaded file contents,
* AI-generated content unless specifically required elsewhere,
* plaintext passwords,
* API keys,
* unnecessary personal data,
* raw provider error payloads that may contain sensitive data.

### 21.6 Deferred production hardening

The database design does not require production-grade hardening features for v1.0, such as encryption-at-rest infrastructure, centralized monitoring, SIEM integration, or enterprise incident-response tracking.

Those are deferred production concerns, not local/LAN capstone MVP requirements.

---

## 22. Deferred Database Items and Future-Version Boundaries

This section lists items intentionally deferred from the conceptual database design.

### 22.1 Deferred to the schema pass

The following are deferred to the later schema-drafting pass:

* exact table names,
* exact column names,
* exact column types and lengths,
* exact index definitions,
* exact foreign-key syntax,
* enum-like values versus lookup-table implementation for closed sets,
* exact settings table shape,
* exact timestamp column names.

These are not missing requirements. They are intentionally deferred because this document is still conceptual and database-focused, not a SQL schema.

### 22.2 Deferred to security and build documents

The following belong in `SECURITY_NOTES.md` or `BUILD_PLAN.md`:

* first Admin bootstrap procedure,
* first Admin recovery procedure,
* Admin-assisted password reset procedure,
* session timeout value,
* session cookie/security settings,
* exact file-size limits,
* exact MIME/content validation implementation,
* upload storage path configuration,
* AI API key storage and environment configuration.

### 22.3 Deferred to AI documentation

The following belong in `AI_FEATURES.md`:

* exact AI prompt design,
* AI model/provider choice,
* AI output review UI,
* content extraction approach,
* content-based search implementation,
* duplicate/similarity detection method,
* AI cost-control details,
* AI failure fallback behavior.

### 22.4 Future-version boundaries

The following are not part of v1.0 database design:

* teacher auto-approval or trusted-uploader bypass,
* urgent publication workflow,
* email/SMS/push notifications,
* WebSocket or real-time notification infrastructure,
* full analytics event tracking,
* per-user reading history,
* LMS features such as classes, quizzes, assignments, grades, attendance, or enrollment,
* production-scale security monitoring,
* encryption-at-rest infrastructure,
* advanced institutional identity verification or roster integration.

These may be considered only through later formal scope decisions.

### 22.5 Resolved items

The following items were resolved during database-design drafting and should not remain open:

* login identifier is a unique self-chosen username;
* topic is required uploader-entered resource metadata;
* no separate categories table/module exists in v1.0;
* resource corrections after approval use linked replacement records;
* one open replacement is enforced through active replacement tracking;
* one unresolved report per user per resource is enforced through active report tracking;
* open replacement behavior when the original resource changes status is governed by D030;
* lightweight in-app notifications and queue visibility are governed by D031;
* system settings are stored in a small database-backed settings area;
* resource action history and general audit logs are separate but related accountability structures.

---

## 23. Revision History

### 23.1 Document parts

* **Part 1 — Sections 1–6:** Defined purpose, design principles, required data areas, accounts/roles/account status, first Admin setup implications, Student registration data, username login identifier, and academic taxonomy direction.
* **Part 2 — Sections 7–11:** Defined resource core records, file storage metadata, resource statuses and visibility, resource action history, linked replacement-record design, and one-open-replacement enforcement.
* **Part 3 — Sections 12–16:** Defined reports, active report tracking, bookmarks, Helpful marks, activity counts, AI output storage and lifecycle, in-app notifications, queue visibility, and general audit logs.
* **Part 4 — Sections 17–23:** Defined system settings, data integrity rules, transaction and atomic write boundaries, indexing/search support, database-specific security/privacy implications, deferred database items, and revision history.

### 23.2 Decisions incorporated

This document incorporates the database-relevant decisions from `DECISIONS.md`, including:

* Student self-registration and Admin-provisioned elevated roles,
* role and account-status separation,
* Student/Teacher-only ordinary uploads,
* Teacher uploads still requiring moderation,
* linked replacement records for Approved-resource corrections,
* one open replacement per original resource,
* report statuses and controlled report reasons,
* binary Helpful marks,
* no transfer of interactions to replacements,
* AI output lifecycle and no inheritance across replacements,
* first Admin setup boundary,
* Removed and Withdrawn resource handling,
* Hidden and Restricted distinction,
* direct Moderator/Admin action,
* D030 open replacement handling when original status changes,
* D031 lightweight in-app notifications and queue visibility,
* D032 active report tracking for one unresolved report rule,
* D040 removal-time content sanitization for Removed resources, including exact placeholder values, removal of `resource_tags`, preservation of required accountability relationships, and the explicit distinction from Withdrawn-resource retention.

### 23.3 Documentation Alignment Status

The earlier cleanup items concerning duplicate D031 entries, taxonomy terminology, and future handoff section references have been checked against the current source set.

* `DECISIONS.md` contains one D031 entry.
* The accepted v1.0 taxonomy model remains courses/programs, subjects, year levels, resource types, controlled tags, and uploader-entered topic. No separate categories table or module exists.
* Current handoff prompts and source references use the completed database-design section structure.

These items are no longer open cleanup tasks. Future documentation changes must preserve the accepted taxonomy model and current cross-document references.

### 23.4 Status

`DATABASE_DESIGN.md` Sections 1–23 and Appendix A are complete at the conceptual database-design level and include the D040 Removed-resource minimization rule without changing the accepted 18-table schema.

This document intentionally does not include SQL, exact column types, exact index definitions, foreign-key syntax, or full table definitions. Those belong in the later schema-drafting pass after the conceptual design is accepted.

## Appendix A — Schema Drafting Plan

### A.1 Purpose

This appendix bridges the accepted conceptual database design and the later SQL schema drafting pass.

It lists the proposed database tables and data structures, their purposes, main conceptual fields, important relationships, and enforcement responsibilities. It does not define exact SQL `CREATE TABLE` statements, final column types, full indexes, seed data, or PHP implementation code.

---

### A.2 Accepted Table Catalog

#### 1. `accounts`

* **Purpose:** Single table for all four roles.
* **Fields:** username, password_hash, display_name, role, account_status, created_at, updated_at.
* **Relationships:** Referenced by resources as uploader, resource action history and audit logs as actor, reports as reporter, and notifications as recipient.
* **Constraints:** Username must be unique. Role is restricted to Student, Teacher/Instructor, Moderator, and Admin. Account status is restricted to Active and Disabled.
* **Type:** Core table.

#### 2–6. Academic Taxonomy Lookups: `courses`, `subjects`, `year_levels`, `resource_types`, `tags`

* **Purpose:** Admin-managed controlled vocabularies used for resource metadata.
* **Fields:** name, is_active, created_at, updated_at.
* **Relationships:** Referenced by `resources` through course_id, subject_id, year_level_id, and resource_type_id. Referenced by `resource_tags` through tag_id.
* **Constraints:** Name should be unique within each lookup table. `subjects` remains a flat standalone lookup for v1.0 and does not include `course_id` unless a future decision introduces course-specific subject rules.
* **Active/inactive handling:** Admin may add or edit lookup values. A value already referenced by at least one resource must not be hard-deleted. It may be deactivated instead. Deactivated values stop appearing as selectable options for new uploads but remain valid for resources that already reference them.
* **Type:** Lookup tables.

#### 7. `resources`

* **Purpose:** Central record for every upload, resubmission, and replacement.
* **Fields:** uploader_id, title, description, topic, course_id, subject_id, year_level_id, resource_type_id, status, original_filename, stored_filename, file_type, file_size, file_availability, replaces_resource_id, ai_notice_acknowledged, ai_notice_acknowledged_at, view_count, download_count, created_at, updated_at.
* **Relationships:** References `accounts` through uploader_id. References each taxonomy lookup table. Self-references another resource through replaces_resource_id for replacement lineage. Connects to tags through `resource_tags`.
* **Constraints:** Status is restricted to the nine canonical resource statuses. course_id, subject_id, year_level_id, resource_type_id, and topic are required. Tags remain optional. Foreign keys from resources to taxonomy tables should use `ON DELETE RESTRICT` to prevent hard deletion of referenced lookup values.
* **Removal-time behavior (D040):** When status changes to `Removed`, application logic overwrites `title`, `description`, `topic`, and `original_filename` with the exact D040 placeholder values in the same database transaction as the status and `file_availability` updates. `created_at` remains unchanged and `updated_at` changes automatically. This is application-layer lifecycle behavior using existing columns, not a new schema field.
* **Type:** Core table.

#### 8. `resource_tags`

* **Purpose:** Many-to-many junction table between resources and tags.
* **Fields:** resource_id, tag_id.
* **Relationships:** References `resources` and `tags`.
* **Constraints:** Unique pair on resource_id and tag_id to prevent duplicate tag assignment.
* **Removed-resource lifecycle (D040):** All `resource_tags` rows for a resource are deleted when that resource becomes Removed. This does not delete the controlled tag records themselves and does not apply to Withdrawn resources.
* **Type:** Junction table.

#### 9. `resource_action_history`

* **Purpose:** Append-only moderation and resource-action history for approvals, rejections, correction requests, hides, restrictions, removals, replacement approvals, and system-triggered status changes.
* **Fields:** resource_id, actor_account_id, action_type, status_before, status_after, note, related_report_id, created_at.
* **Relationships:** References `resources`, optionally references `accounts` as actor, and optionally references `reports` when the action came from report handling. actor_account_id may be nullable when the actor is the system.
* **Constraints:** status_before and status_after are restricted to the nine canonical resource statuses.
* **Type:** Core table.

#### 10. `open_replacement_tracking`

* **Purpose:** Enforces D026: only one open replacement may exist per original resource.
* **Fields:** original_resource_id, replacement_resource_id, created_at.
* **Relationships:** References `resources` for both the original resource and the open replacement resource.
* **Constraints:** Unique rule on original_resource_id. The row is deleted when the replacement resolves to Approved, Rejected, or Withdrawn.
* **Type:** Helper/tracking table.

#### 11. `reports`

* **Purpose:** Stores user-submitted concerns about Approved resources.
* **Fields:** resource_id, reporter_account_id, reason, comment, status, escalated_by_account_id, escalated_at, resolved_by_account_id, resolution_note, resolved_at, created_at, updated_at.
* **Relationships:** References `resources` and `accounts`. The resource_id stays attached to the originally reported resource and is not reassigned if the resource later becomes Replaced.
* **Constraints:** Status is restricted to Open, Dismissed, Actioned, and Escalated. Reason is restricted to the seven D029 report reason categories.
* **Type:** Core table.

#### 12. `open_report_tracking`

* **Purpose:** Enforces D032: one unresolved report per user per resource.
* **Fields:** report_id, reporter_account_id, resource_id, created_at.
* **Relationships:** References `reports`, `accounts`, and `resources`.
* **Constraints:** Unique rule on reporter_account_id and resource_id. The row remains while the report is Open or Escalated. The row is deleted when the report becomes Dismissed or Actioned.
* **Type:** Helper/tracking table.

#### 13. `bookmarks`

* **Purpose:** Stores user-resource bookmark relationships.
* **Fields:** account_id, resource_id, created_at.
* **Relationships:** References `accounts` and `resources`.
* **Constraints:** Unique rule on account_id and resource_id.
* **Type:** Core table.

#### 14. `helpful_marks`

* **Purpose:** Stores binary Helpful marks for resources.
* **Fields:** account_id, resource_id, created_at.
* **Relationships:** References `accounts` and `resources`.
* **Constraints:** Unique rule on account_id and resource_id.
* **Type:** Core table.

#### 15. `ai_outputs`

* **Purpose:** Stores AI-generated or AI-assisted outputs tied to exactly one resource.
* **Fields:** resource_id, output_type, content, lifecycle_state, source_file_reference, generated_at.
* **Relationships:** References `resources`.
* **Constraints:** AI output belongs to one resource only and must not be inherited by replacement resources.
* **Type:** Core table.

#### 16. `notifications`

* **Purpose:** Stores D031 lightweight in-app notifications.
* **Fields:** recipient_account_id, target_type, target_id, message, is_read, created_at, read_at.
* **Relationships:** References `accounts` as notification recipient. target_type and target_id identify the in-app destination.
* **Target handling:** target_type may refer to a resource, report, moderation queue, report queue, or other accepted in-app destination. target_id may be nullable when the notification points to a queue rather than a specific record.
* **Type:** Core table.

#### 17. `system_settings`

* **Purpose:** Stores confirmed Admin-manageable system settings, especially the AI enabled/disabled setting.
* **Fields:** setting_name, setting_value, updated_at, updated_by_account_id.
* **Relationships:** updated_by_account_id references `accounts`.
* **Constraints:** setting_name must be unique.
* **Type:** Core table.

#### 18. `audit_log`

* **Purpose:** Stores administrative and system-level accountability events separate from resource-specific action history.
* **Fields:** actor_account_id, action_type, target_type, target_id, note, created_at.
* **Relationships:** actor_account_id references `accounts`. target_type and target_id identify the affected object or setting.
* **Target handling:** For v1.0, target_type should support at least account and system_setting. It may also support security_event or upload_attempt if `SECURITY_NOTES.md` decides to log risky failed upload attempts through the general audit log.
* **Type:** Core table.

---

### A.3 Lookup Tables

The lookup tables are:

* `courses`
* `subjects`
* `year_levels`
* `resource_types`
* `tags`

All five support `is_active` so values can be deactivated without breaking existing resource references.

For v1.0:

* subjects are flat and not course-scoped;
* topic is not a lookup table;
* tags are controlled Admin-managed values, not free-text user-created tags.

---

### A.4 Helper and Tracking Tables

The helper/tracking tables are:

* `open_replacement_tracking`
* `open_report_tracking`

Both tables track only currently open states. Their rows are deleted when the related item is resolved.

This pattern is used because the system needs uniqueness only while something is open, not permanently across all history.

---

### A.5 Enforcement Breakdown

#### Database constraints

Database-level enforcement should cover:

* closed-set values for role, account_status, resource status, report status, report reason, and file type;
* unique username;
* unique account_id/resource_id pair on `bookmarks`;
* unique account_id/resource_id pair on `helpful_marks`;
* unique original_resource_id on `open_replacement_tracking`;
* unique reporter_account_id/resource_id pair on `open_report_tracking`;
* unique resource_id/tag_id pair on `resource_tags`;
* unique setting_name on `system_settings`;
* `ON DELETE RESTRICT` from resources to referenced taxonomy lookup values;
* foreign-key existence checks where practical.

#### Application logic

Application-level enforcement remains required for:

* allowing only Student and Teacher/Instructor accounts to upload ordinary resources;
* valid resource status transitions;
* D030 live status re-check before approving a replacement;
* preventing a user from reporting their own resource;
* AI status-based eligibility;
* AI notice acknowledgment behavior;
* ensuring AI notice refusal does not block the normal upload workflow;
* role-gated actions such as Admin-only Remove and Moderator/Admin-only Hide, Restrict, Approve, or Reject;
* server-side role assignment during registration and account provisioning;
* taxonomy deactivation behavior in the Admin interface.

#### Transactions

The following operations should be transaction-safe:

* replacement approval: replacement becomes Approved, original becomes Replaced, open replacement tracking row is cleared, and action-history entries are created;
* replacement rejection or withdrawal: replacement status changes and open replacement tracking row is cleared;
* report resolution: report status changes and open report tracking row is cleared;
* moderation decision: conditional status update and action-history write occur together;
* direct Moderator/Admin action without a report: resource status update and action-history write occur together;
* system setting change: setting update and audit-log entry occur together.

---

### A.6 Deferred Items

The following items are intentionally deferred outside this appendix:

* Admin taxonomy-management workflow behavior is already reflected in `WORKFLOWS.md`. Later implementation documents still need to preserve add/edit/deactivate/reactivate behavior through lookup tables with `is_active` and `ON DELETE RESTRICT` for referenced values.
* Admin-assisted password reset details are deferred to `SECURITY_NOTES.md` and `BUILD_PLAN.md`.
* Exact session timeout value is deferred to `SECURITY_NOTES.md`.
* Exact MIME/content validation implementation is deferred to `SECURITY_NOTES.md`.
* First-Admin bootstrap procedure is deferred to `BUILD_PLAN.md`.
* Content-hash duplicate detection is not required for v1.0 and may be considered later only if testing shows a real need.
* Exact column types, indexes, foreign-key syntax, and full `CREATE TABLE` statements belong to the next schema-drafting pass.
