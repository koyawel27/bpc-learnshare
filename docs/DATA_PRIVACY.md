## 1. Purpose, Scope, and Relationship to Other Documents

### 1.1 What This Document Is

This document is the privacy reference for BPC LearnShare v1.0. It aligns the already-confirmed v1.0 design — roles, resource statuses, workflows, database design, accepted schema, and security controls — with general principles of the Philippine Data Privacy Act (Republic Act No. 10173), at a level appropriate for a local/LAN BS Information Systems capstone MVP, not a production campus deployment.

This document does not introduce new roles, resource statuses, report statuses, database tables, fields, modules, workflows, or AI features. The four confirmed v1.0 roles (Student, Teacher/Instructor, Moderator, Admin), the nine confirmed resource statuses, the four report statuses, and the 18-table schema baseline established by D033, updated by D039, and supplemented by D040's application-level Removed-resource lifecycle rule remain exactly as confirmed. D040 adds no table or column.

This document consolidates privacy-relevant rules already established in `PROJECT_BRIEF.md`, `DECISIONS.md`, `USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`, `schema.sql`, and `SECURITY_NOTES.md`. It explains their significance from a data-privacy standpoint rather than redefining them as new workflow, database, security, or AI requirements.

### 1.2 Why This Document Exists

`PROJECT_BRIEF.md` Section 14 already identifies RA 10173 as the relevant privacy framework and states that personal data and uploaded file content sent to an external AI API require appropriate privacy consideration, a documented basis, and user notice. That discussion is brief and was written before the workflows, database design, schema, and security baseline were completed.

Privacy-relevant behavior is now distributed across several accepted documents and expressed in different terms:

* `WORKFLOWS.md` defines resource visibility, uploader ownership, moderation behavior, the AI notice-and-acknowledgment gate, and status-based lifecycle rules.
* `DATABASE_DESIGN.md` defines the data areas the system requires, the intentionally limited account dataset, the relationships between stored records, and the expected handling of Removed and Withdrawn resources.
* `schema.sql` defines the accepted fields, relationships, closed-set values, AI-notice acknowledgment fields, AI-output lifecycle states, and accountability structures that implementation will use.
* `SECURITY_NOTES.md` defines the controls that enforce access restrictions, protected file serving, safe logging, AI eligibility, and derived-output visibility.

This document exists to gather those privacy-relevant rules into one reference, explain what they mean for account holders and for people whose information may appear in uploaded academic content, and identify what remains genuinely unresolved from a privacy standpoint.

It does not re-derive the security controls or workflow logic that already explain how the system behaves.

### 1.3 What This Document Restates, and What It Does Not Change

This document restates confirmed decisions. It does not reopen, silently reinterpret, or expand them.

Specifically, this document:

* **Does not introduce new roles, resource statuses, report statuses, tables, fields, modules, workflows, permissions, or AI features.** The four roles, nine resource statuses, four report statuses, and 18-table schema baseline remain exactly as confirmed.
* **Does not change any confirmed permission, ownership rule, workflow transition, or visibility rule.** Where this document restates a rule from `USER_ROLES.md` or `WORKFLOWS.md`, it must match the original rule.
* **Does not change the AI eligibility model.** Status-based AI eligibility (D014), the non-authoritative AI rule (D013 and D015), the optional Phase 5 boundary (D016), and the Pending-file validation, notice, and acknowledgment requirements remain unchanged.
* **Does not change the `file_availability` model.** The three values (`available`, `deleted`, `invalidated`) and the dual-gate file-serving rule from D034 remain as confirmed.
* **Does not change the AI-output lifecycle.** AI outputs remain tied to one resource, are not inherited by replacements, and follow the accepted draft, retained, and invalidated lifecycle.
* **Does not change the Removed-resource minimization rule.** D040 requires exact removal-time replacement of title, description, topic, and original filename, deletion of associated `resource_tags`, preservation of required accountability relationships, and continued Admin-only access. The retained record is minimized but not anonymized. This rule does not apply to Withdrawn resources.
* **Does not change the audit-log or resource-action-history content rules.** The two-ledger model and safe-summary-only content boundary remain defined by `SECURITY_NOTES.md`. This document explains their privacy significance without redefining their implementation mechanics.
* **Does not change the schema.** Any privacy requirement that would need a new table, column, field, status, or automated retention mechanism must be identified as a scope or source-alignment issue before being adopted.

If a privacy expectation conflicts with a confirmed decision, workflow, database rule, or accepted schema definition, the conflict must be flagged and resolved through the project’s established planning process. It must not be resolved by silently changing this document’s description or by quietly overriding an upstream source.

### 1.4 What This Document Is Not

This document is not `SECURITY_NOTES.md`. It does not redefine authentication mechanics, session handling, CSRF protection, SQL injection prevention, output escaping, file-upload validation, document-root protection, or controlled file-serving implementation. Those remain owned by `SECURITY_NOTES.md`.

This document references security controls only where they directly support a privacy principle, such as limiting access to non-public resources, preventing unauthorized file exposure, or keeping full uploaded content and full AI output out of accountability logs.

This document is not a legal opinion, privacy compliance certification, formal Privacy Impact Assessment, or Data Privacy Act audit. It is a student-project privacy planning reference, not a legal determination prepared by a lawyer, Data Protection Officer, or institutional compliance authority.

It does not certify that BPC LearnShare v1.0 complies fully with RA 10173, and it does not treat GDPR as the project’s primary privacy framework. Where this document discusses the purpose and practical basis for handling data, it explains why confirmed data supports confirmed workflows; it does not issue a formal legal determination of lawful basis.

This document does not introduce:

* a Data Protection Officer role or module;
* a privacy-notice management module;
* a consent-management system;
* a data-subject request portal;
* a breach-response module;
* a retention scheduler;
* a purge engine;
* an anonymization feature.

Where a real institutional deployment may require formal notices, provider review, retention approval, data-subject procedures, incident handling, or other organizational privacy work, those remain responsibilities for Bulacan Polytechnic College or the authorized deploying office to determine and establish outside the v1.0 application scope.

This document is not `BUILD_PLAN.md` or `TESTING_CHECKLIST.md`. It does not sequence implementation work and does not define complete test scripts. It identifies privacy requirements and carry-forward items that those later documents must preserve or verify.

This document is not `AI_FEATURES.md`. `DATA_PRIVACY.md` defines the privacy content, meaning, and required disclosures of the AI-processing notice and may establish or approve its privacy-facing wording. `AI_FEATURES.md` defines how that notice is presented, acknowledged, connected to AI triggers, and implemented with the selected AI provider.

### 1.5 Scope of This Document

This document covers BPC LearnShare v1.0 only, for the confirmed local/LAN XAMPP academic MVP deployment model.

It addresses:

* BPC LearnShare’s privacy role relative to Bulacan Polytechnic College and any authorized deploying office, without asserting a settled legal controller determination;
* the categories of personal, academic, operational, and derived data handled through the accepted v1.0 design;
* the practical purpose of each data category, tied to confirmed workflows;
* data minimization and personal/profile information intentionally not collected in v1.0;
* privacy risks in uploaded academic content, including information about classmates, teachers, or other people who may be named, described, or pictured in uploaded files;
* uploader responsibility for avoiding unnecessary personal information and sharing only material they are authorized to provide;
* role-based access, ownership rules, resource status, and current visibility as privacy controls;
* reports, report comments, moderation notes, resource action history, bookmarks, Helpful marks, activity counts, notifications, and administrative audit records;
* the AI-processing notice, acknowledgment, eligibility gate, and the limits of what acknowledgment represents;
* AI-generated output as derived data tied to one source resource;
* retention, withdrawal, removal, D040 descriptive-field sanitization, controlled-tag-association removal, file availability, AI-output invalidation, retained historical relationships, and the distinction that a minimized Removed-resource record is not anonymized;
* the local/LAN storage boundary and the possibility that eligible resource content may be transmitted to an external AI provider when optional API-based AI features are configured and used;
* known v1.0 privacy limitations; and
* privacy requirements to carry forward into `BUILD_PLAN.md`, `AI_FEATURES.md`, and `TESTING_CHECKLIST.md`.

This document does not define privacy requirements for unrelated future features such as email-based notifications, active-session management, public access, or production-scale campus deployment.

Phase 5 AI resource inquiry remains an optional stretch feature under D016. If it is implemented, it must follow the privacy, resource-eligibility, external-provider, source-citation, access-control, and lifecycle boundaries established by the accepted project documents and this privacy reference. Detailed retrieval, prompt, provider, and implementation behavior remains the responsibility of `AI_FEATURES.md`.

### 1.6 Relationship to Other Planning Documents

| Document               | Relationship to This Document                                                                                                                                                                                                                                                                                                                                                 |
| ---------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `PROJECT_BRIEF.md`     | Source of the project goal, scope, local/LAN deployment direction, AI boundaries, and original privacy discussion in Section 14. This document expands the privacy discussion without replacing or contradicting the brief.                                                                                                                                                   |
| `DECISIONS.md`         | Source of the accepted decisions treated as fixed privacy inputs, including account/access decisions D005–D007; AI decisions D013–D018 and D028; resource lifecycle decisions D020, D022, D027, and D040; image-upload implications in D025; report and notification decisions D029 and D031; file/AI/polymorphic validation decisions D034–D036; and the D039 audit-log extension. D040 defines exact Removed-resource minimization, controlled-tag-association deletion, retained accountability relationships, and the distinction from Withdrawn retention. |
| `USER_ROLES.md`        | Source of the four-role permission model, ownership boundaries, staff authority, status-based visibility expectations, and AI-related permissions. This document explains their privacy significance without redefining them.                                                                                                                                                 |
| `WORKFLOWS.md`         | Source of the confirmed operational behavior for registration, upload, moderation, resource visibility, reporting, withdrawal, replacement, AI notice and acknowledgment, AI output lifecycle, notifications, and historical handling.                                                                                                                                        |
| `DATABASE_DESIGN.md`   | Source of the conceptual data inventory and data-minimization direction. Section 3 and Appendix A identify the required data areas and table purposes; Sections 14 and 18.5 inform AI-output lifecycle and retained-accountability discussion.                                                                                                                                |
| `schema.sql`           | Source of the accepted 18-table implementation baseline, including actual account fields, resource metadata, file metadata, AI-notice acknowledgment fields, lifecycle enums, relationships, activity counters, notifications, the D039-patched audit structure, and comments documenting D040's application-level Removed-resource behavior. D040 changes no table or column. |
| `SECURITY_NOTES.md`    | Source of the implementation-facing controls that enforce privacy-relevant boundaries, especially object/view restrictions, controlled file serving, audit-log safety, polymorphic target validation, AI security boundaries, and known v1.0 limitations. This document explains why those controls matter for privacy without re-deriving them.                              |
| `PROJECT_HANDOFF.md`   | Identifies `DATA_PRIVACY.md` as the current documentation phase and defines the scope, AI privacy rules, institutional-responsibility boundaries, and conflict-handling requirements this document must follow.                                                                                                                                                               |
| `BUILD_PLAN.md`        | Written after this document. The privacy carry-forward section will identify requirements the implementation sequence must preserve.                                                                                                                                                                                                                                          |
| `AI_FEATURES.md`       | Written after `BUILD_PLAN.md`. It will implement the AI notice, provider handling, eligibility rules, cost controls, prompts, and output behavior within the privacy boundaries established here.                                                                                                                                                                             |
| `TESTING_CHECKLIST.md` | Written after `BUILD_PLAN.md`. The privacy carry-forward section will identify behaviors that later testing must verify.                                                                                                                                                                                                                                                      |

---

*End of Section 1.*

## 2. BPC LearnShare's Privacy Role and Institutional Responsibility

### 2.1 Purpose of This Section

This section distinguishes what BPC LearnShare v1.0 *does* as a piece of software from what Bulacan Polytechnic College, or any office that authorizes its deployment, would still need to *decide and formally establish* as an institution. The application enforces confirmed data-handling, access, moderation, logging, lifecycle, and AI-processing rules. It does not, by itself, constitute an institutional privacy-governance program.

### 2.2 Application-Level Behavior Versus Institutional Responsibility

BPC LearnShare v1.0 implements privacy-relevant behavior at the application level: login-required access, role-based visibility, status-gated resource exposure, the AI-processing notice-and-acknowledgment gate, minimal Student registration data, safe-summary audit logging, and the D040 Removed-resource minimization rule. These are confirmed, source-of-truth behaviors already defined in `WORKFLOWS.md`, `DATABASE_DESIGN.md`, `schema.sql`, and `SECURITY_NOTES.md`, and this document explains their privacy significance.

These behaviors support privacy-conscious application operation but do not, by themselves, establish institutional privacy compliance. For any real academic deployment, Bulacan Polytechnic College or the authorized deploying office would still need to determine and formally establish matters such as the appropriate basis for handling personal and other privacy-relevant data processed through the system, official privacy-notice content and delivery, arrangements with any external AI provider, retention and disposal policy beyond the technical lifecycle rules described in this document, incident and breach procedures, and institutional privacy accountability, as applicable.

BPC LearnShare v1.0 does not perform any of the institutional functions listed above. It is a capstone MVP that behaves consistently with general Data Privacy Act principles at the application layer. It is not, and does not claim to be, the institution's privacy-compliance program.

### 2.3 No Determination of Personal Information Controller Status

RA 10173 assigns specific obligations to a Personal Information Controller (PIC) and, where applicable, a Personal Information Processor. No source document for this project — `PROJECT_BRIEF.md`, `DECISIONS.md`, `USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`, or `SECURITY_NOTES.md` — makes a formal determination of which party (Bulacan Polytechnic College, an authorized deploying office, or another party) holds PIC status, PIP status, or any other formal role under RA 10173 for a real deployment of this system.

This document does not make that determination either. Where this document or others refer to BPC LearnShare's "privacy role," that phrase describes the application's *functional* behavior — what data it collects, how it restricts access, how it logs actions, and how it processes AI requests — not a legal classification. Any statement in project documentation describing data handling, purpose, or basis explains why the confirmed v1.0 design supports the confirmed workflows; it is not a legal opinion and does not resolve controller/processor status, lawful-basis classification, or any other formal RA 10173 designation. That determination, if needed, belongs to Bulacan Polytechnic College or the authorized deploying office, exercised outside the scope of this capstone project.

### 2.4 Responsibilities That Remain Outside v1.0 Application Scope

The following remain the responsibility of Bulacan Polytechnic College or the authorized deploying office, and are not resolved by this document or by the BPC LearnShare v1.0 application:

* determining the institution's formal legal basis and role under RA 10173 for handling personal and other privacy-relevant data processed through the system;
* issuing and maintaining official privacy notices beyond the in-application AI-processing notice described in Section 8;
* reviewing and authorizing any external AI provider from an institutional, data-protection, and contractual standpoint before eligible real user data or uploaded-resource content is transmitted to that provider;
* setting an institutional retention and disposal policy that governs data beyond what this document's technical lifecycle rules in Section 11 describe;
* establishing a breach-response and incident-notification procedure;
* designating institutional privacy accountability, such as a Data Protection Officer or equivalent function;
* handling formal data-subject requests and related procedures as institutional responsibilities rather than v1.0 application features;
* approving BPC LearnShare for any deployment beyond the local/LAN capstone demonstration scope defined in `PROJECT_BRIEF.md`.

None of the items above are implemented as application features in v1.0, and none should be inferred as implemented because this document discusses related application behavior. Where this document later describes retention, AI notice, or audit-log behavior, it is describing what the software does, not substituting for the institutional decisions listed here.

### 2.5 The AI-Processing Notice Is a Workflow Gate, Not a Legal Instrument

BPC LearnShare v1.0 shows uploaders a notice before Pending-file AI processing and requires acknowledgment before that processing occurs, per D014 and the eligibility rules in `WORKFLOWS.md` Section 10. This notice-and-acknowledgment mechanism is a UX and workflow gate confirmed at the application level. This document does not treat acknowledgment alone as legal consent under RA 10173 or as resolving every lawful-basis question. Declining or not providing acknowledgment never blocks ordinary upload; it only causes Pending-file AI processing to be skipped, consistent with D004 and D013. Full mechanics of this notice belong to Section 8 of this document; this section notes it here only to make clear that the gate operates at the application layer and does not substitute for the institutional AI-provider review described in Section 2.4.

### 2.6 RA 10173 as Guiding Framework, Not a Compliance Certification

This document continues to use general Philippine Data Privacy Act (RA 10173) principles as its primary reference framework, consistent with Section 1. It does not treat GDPR as the project's primary framework, and it does not state or imply that BPC LearnShare v1.0 has been certified, audited, or formally found compliant with RA 10173. Where this document describes data handling as aligning with Data Privacy Act principles, it means the confirmed v1.0 design was built with those principles in mind at a student-project planning level — not that a compliance determination has been made by BPC, the National Privacy Commission, or any other authority.

---

*End of Section 2.*

## 3. Data Categories Handled in v1.0

### 3.1 Purpose of This Inventory

This section identifies the categories of data that BPC LearnShare v1.0 actually stores and processes, based only on the accepted 18-table schema (`schema.sql`) and the confirmed workflows in `WORKFLOWS.md` and `DATABASE_DESIGN.md`. It does not introduce new fields, tables, or tracking mechanisms, and it does not reach a formal legal conclusion about which items constitute personal or sensitive personal information under RA 10173 in every circumstance.

It describes what the system handles and notes where privacy relevance is apparent so that later sections can build on an accurate inventory rather than an assumed one.

### 3.2 Account and Authentication Data for All Four Roles

All four v1.0 roles — Student, Teacher/Instructor, Moderator, and Admin — share a single `accounts` table and the same account-level data fields. No role has additional profile fields in the accepted schema.

The account data stored for every account, regardless of role, is:

* a **username**, used as the login identifier and provided during Student self-registration or Admin account provisioning;
* a **password hash** generated through PHP's `password_hash()` function — the plaintext password is not stored;
* a **display name**, provided during Student self-registration or Admin account provisioning;
* one **role** value: Student, Teacher/Instructor, Moderator, or Admin;
* one **account status** value: Active or Disabled;
* account creation and last-updated timestamps.

Two points are important for privacy accuracy:

* **The accepted design does not use account data as proof of official institutional identity.** Student usernames and display names are self-provided during public registration. Teacher/Instructor, Moderator, and Admin accounts are Admin-provisioned, but the accepted design still contains no roster integration, institutional-email verification, identity-verification field, or external institutional identity-validation mechanism. A username or display name must therefore not be treated by the application as independent proof of a person's official institutional identity.
* **No email address, student number, course/program, year level, or section is collected as account data.** These fields do not exist in the accepted `accounts` table and are not required by the Student self-registration workflow in `WORKFLOWS.md` Section 5 or the Admin account-provisioning workflow in `WORKFLOWS.md` Section 6. If such a field is ever added, it would require a new decision and a schema change; it is not part of v1.0.

Teacher/Instructor, Moderator, and Admin accounts use the same accepted account fields as Student accounts. Their difference is the Admin-provisioned account workflow and assigned role, not the collection of additional profile fields.

### 3.3 Academic Resource Metadata and Uploaded File Content

Each academic resource consists of a database record in the `resources` table and uploaded file content kept in protected file storage.

The resource record stores:

* uploader-entered descriptive metadata: **title**, **description**, and **topic**;
* controlled taxonomy references selected from Admin-managed lookups: **course/program**, **subject**, **year level**, and **resource type**;
* zero or more controlled **tags**, linked through the `resource_tags` junction table;
* the **current resource status** from the nine confirmed statuses;
* the file reference and technical metadata described in Section 3.8.

The uploaded file content is not stored as a database field or blob. It is stored separately under a randomized, non-guessable filename in protected file storage, while the resource record keeps the reference and metadata needed to identify, validate, and control access to that file.

This is the core content category of the platform. Title, description, topic, original filename, and uploaded file content originate from the uploader and may contain names, references to Students or Teachers/Instructors, or other identifying details about people who are not the uploader. This risk is addressed from an uploader-responsibility and content-review standpoint in Section 6; it is noted here only as a data-category characteristic.

Resource metadata is subject to the resource-status lifecycle. Most relevant to this inventory, when a resource becomes **Removed**, D040 requires:

* `title` to become `[Removed resource]`;
* `description` to become `[Removed resource]`;
* `topic` to become `[Removed]`;
* `original_filename` to become `[removed]`;
* all associated `resource_tags` rows to be deleted.

The retained Removed-resource record is minimized within the accepted schema but is **not anonymized**. It still carries the uploader reference and other retained account-linked, taxonomy, technical, activity, acknowledgment, timestamp, and historical data identified across this section.

This rule applies only to Removed resources and does not apply to Withdrawn resources. Withdrawn resources retain their original descriptive metadata and controlled-tag associations. Full retention mechanics belong to Section 11; this section notes the distinction only so the inventory above is not read as unconditionally permanent.

### 3.4 Ownership and Resource-Lifecycle Data

Each resource record also carries data that establishes ownership and lifecycle position rather than descriptive content:

* the **uploader reference** (`uploader_id`), linking the resource to the account that submitted it;
* the **replacement linkage** (`replaces_resource_id`), when the resource is a corrected replacement for a previously Approved resource;
* **creation and last-updated timestamps**;
* a temporary **open-replacement tracking record**, which exists only while a replacement is Pending or Needs Correction and is deleted once the replacement is resolved.

This data supports accountability, ownership-based actions, and the linked replacement workflow. It does not itself constitute descriptive resource content, but the uploader reference is what keeps a resource — including a minimized Removed resource — attributable to a specific account.

### 3.5 Moderation, Report, and Accountability Data

The following accepted tables store moderation, reporting, and accountability data:

* **`reports`** — the reported resource, the reporting account, a controlled reason category, an optional free-text comment, report status, and, where applicable, escalating and resolving actor references, a resolution note, and relevant timestamps.
* **`open_report_tracking`** — a temporary tracking record containing the reporter account, resource, and report references needed to enforce one unresolved report per user/resource. The record remains while the report is Open or Escalated and is deleted when the report becomes Dismissed or Actioned.
* **`resource_action_history`** — an append-only ledger of moderation and resource-status actions: the affected resource, acting account or `NULL` for an accepted system-triggered action, action type, status before and after, an optional note, an optional related-report reference, and a timestamp.
* **`audit_log`** — a separate append-only ledger for accepted administrative and system-level actions, including account creation, account disabling or re-enabling, role changes, Admin-assisted password resets, taxonomy management, and system-setting changes. It stores the acting account, action type, target type and identifier, an optional note, and a timestamp.

These are operational and accountability records generated through user, staff, or system activity rather than profile data supplied solely by the uploader or resource subject.

The free-text fields in this category — report comments, resolution notes, resource-action notes, and audit notes — may incidentally reference identifiable individuals. `SECURITY_NOTES.md` already limits what these ledgers may contain, including excluding passwords, API keys, full uploaded-file content, and full AI output. Section 10 addresses the privacy significance and safe-summary boundary of these records.

### 3.6 Bookmarks, Helpful Marks, Activity Counts, and Notifications

* **`bookmarks`** — a user-resource relationship containing the account reference, resource reference, and timestamp.
* **`helpful_marks`** — a binary user-resource relationship of the same general form. There is no numeric rating or written review in v1.0.
* **View and download counts** — aggregate counters stored directly on the `resources` row as `view_count` and `download_count`. These are totals only. The accepted schema contains no per-view or per-download event log, so the system cannot reconstruct who viewed or downloaded a specific resource or when an individual access occurred.
* **`notifications`** — a per-recipient record containing the recipient account, a target type and optional target identifier for an accepted resource, report, action-history, moderation-queue, or report-queue destination, a message string, read/unread state, an optional read timestamp, and a creation timestamp.

These are behavioral and interaction records created through ordinary platform use.

Per D027, bookmarks, Helpful marks, reports, views, and downloads do not transfer automatically to a replacement resource. Each replacement is a separate resource record and accumulates its own interaction and activity data.

### 3.7 AI-Processing Acknowledgment and AI-Generated Derived Output

Two distinct categories exist:

* **AI-notice acknowledgment**, stored directly on the resource record through `ai_notice_acknowledged` and `ai_notice_acknowledged_at`, recording whether and when the uploader acknowledged the AI-processing notice for that resource.
* **AI-generated derived output**, stored in `ai_outputs`: one current row per resource and output type, containing the generated content, output type, lifecycle state, source file reference, and relevant timestamps.

Accepted AI output types are:

* summary;
* suggested tags;
* suggested metadata;
* duplicate flag;
* moderation hint;
* related recommendation.

The lifecycle states are:

* draft;
* retained;
* invalidated.

The source file reference associates the AI output with the file state from which it was generated and supports stale-output detection when the source file changes.

AI output is derived data. It exists only when AI features are enabled and only for resources that satisfy the accepted status-based eligibility rules. It belongs to exactly one resource and is not inherited by replacement resources.

The privacy handling of external AI-provider exposure, acknowledgment, visibility, invalidation, and lifecycle behavior is addressed in Sections 8 and 9.

### 3.8 Technical File Metadata, System Settings, and Other Operational Records

Technical file metadata is stored on the resource record and includes:

* the **original filename**, retained for display while permitted by the resource lifecycle;
* the randomized **stored filename** or protected storage reference;
* the validated **file type**;
* the **file size**;
* the **file-availability state**: `available`, `deleted`, or `invalidated`.

These values support validation, protected file storage, controlled file serving, and file-lifecycle handling.

The original filename may itself incidentally contain personal information if an uploader includes a person's name or another identifying detail in the filename. This concern is addressed under uploader responsibility in Section 6 rather than through a new schema behavior.

The **`system_settings`** table stores system-wide configuration values, including the AI enabled/disabled setting, together with the setting value, update timestamp, and the Admin account that last changed it.

These are operational and technical records rather than uploaded academic content or additional account-profile fields.

### 3.9 Distinguishing Data by Origin

The categories above can also be grouped according to how they come to exist in the system:

* **Account and authentication data provided during registration or account provisioning** — username, password hash, display name, role, account status, and account timestamps. Student username and display-name values are self-provided, while Teacher/Instructor, Moderator, and Admin accounts are Admin-provisioned. The accepted design does not use these fields as independent proof of official institutional identity.
* **Uploaded academic content that may incidentally contain information about other people** — resource title, description, topic, original filename, and uploaded file content. This content originates from the uploader and is not limited to information about the uploader alone.
* **Operational and accountability records created through user, staff, or system activity** — reports, open-report tracking, resource action history, audit logs, open-replacement tracking, bookmarks, Helpful marks, activity counters, and notifications. These exist because of platform use and workflow activity rather than as additional account-profile fields.
* **AI-generated derived output** — content produced through AI processing of an eligible resource, existing only when AI is enabled and the applicable eligibility conditions are satisfied.
* **Technical and configuration records** — file metadata, file-availability state, resource timestamps, and system-setting values used to support storage, access control, lifecycle behavior, and optional AI configuration.

This distinction matters for the sections that follow. Purpose and practical basis are addressed in Section 4, data minimization in Section 5, uploader responsibility for information about other people in Section 6, and access controls in Section 7.

---

*End of Section 3.

## 4. Purpose and Practical Basis for Data Handling

### 4.1 What This Section Does and Does Not Do

This section explains the **practical, functional purpose** behind each data category identified in Section 3 — why the accepted v1.0 design collects, stores, or generates it, tied to a specific workflow, permission rule, technical operation, or accountability requirement already confirmed in `WORKFLOWS.md`, `USER_ROLES.md`, `DATABASE_DESIGN.md`, and `schema.sql`.

This section does not make a formal determination of lawful basis under RA 10173. Explaining that a data category exists because a confirmed workflow needs it is a statement about system design, not a legal classification of consent, contract, legal obligation, legitimate interest, or any other formal lawful-basis category. As stated in Section 2.3, that determination — if needed for a real institutional deployment — belongs to Bulacan Polytechnic College or the authorized deploying office, not to this document.

A practical purpose also does not, by itself, justify collecting more data than that purpose requires. Where a category's purpose is narrow, the corresponding data handling in v1.0 is also intended to remain narrow. Section 5 addresses this minimization principle directly.

### 4.2 Account and Authentication Data

Account data identified in Section 3.2 supports four practical purposes:

* **authentication** — the username identifies the account during login, while the stored password hash supports secure password verification;
* **user-facing account identification** — the display name helps identify an account within appropriate interface and staff workflow contexts;
* **authorization** — role and account status support current server-side permission and access checks;
* **account administration and accountability** — role, account status, timestamps, and related audit records support accepted Admin account-management actions and accountability.

The username and display name are not treated as verified institutional identity for any of these purposes. They identify an account within BPC LearnShare but do not independently prove the account holder's official identity or institutional status. This limitation is a design characteristic carried forward from Section 3, not a new identity-verification requirement.

The stored password hash is used only for accepted authentication and password-management functions. It is not displayed as user-facing information, used as profile data, or treated as a recoverable version of the original password.

### 4.3 Academic Resource Metadata and Uploaded File Content

Resource metadata and uploaded file content identified in Section 3.3 support the core platform purpose: **upload, moderation, organization, metadata search and filtering, status-gated access, resource-detail display, controlled viewing, and downloading** of academic resources.

Title, description, and topic support resource identification, moderation review, and metadata-based discovery. Course/program, subject, year level, resource type, and controlled tags support structured organization and filtering. The protected file reference and uploaded file content support controlled viewing or downloading only when the resource's current status, the requester's permission, and file availability allow access.

The existence of an upload workflow supports collecting resource-related metadata required by the accepted design. It does not mean the system expects, requires, or encourages unrelated personal information inside resource metadata, original filenames, or uploaded files.

The system does not define a field whose purpose is to collect personal information about classmates, teachers, or other people. When an uploader independently includes such information in metadata or uploaded content, that becomes an uploader-responsibility and content-handling concern addressed in Section 6, not an additional purpose of the platform.

### 4.4 Ownership and Resource-Lifecycle Data

Ownership and lifecycle data identified in Sections 3.4 and 3.8 support:

* **uploader-of-record identification**, so the system can determine who owns a resource and who may perform confirmed ownership-based actions;
* **management of eligible non-public resources**, including permitted editing, resubmission, and withdrawal by the uploader;
* **moderation-state tracking**, so Moderator/Admin actions operate on the resource's current status;
* **replacement linkage**, so a corrected resource can remain connected to the Approved resource it is intended to replace;
* **one-open-replacement enforcement**, so the D026 rule can be enforced without allowing conflicting simultaneous replacement attempts;
* **file-availability handling**, so a stored file is not served when its current status, permission rules, or `file_availability` state prohibit access;
* **historical accountability**, through retained references, status history, and relevant timestamps.

This data is not collected to create a general behavioral profile or analytics history. Its practical purpose is to make the confirmed ownership, moderation, replacement, access, and lifecycle rules enforceable and understandable.

### 4.5 Moderation, Report, and Accountability Data

Reports, open-report tracking, resource action history, and audit-log records identified in Section 3.5 support distinct but related purposes:

* **reports** allow eligible users to raise concerns about Approved resources and allow Moderator/Admin users to review and resolve those concerns;
* **open-report tracking** enforces the rule that one user may have only one unresolved report for the same resource at a time;
* **resource action history** preserves moderation decisions, status changes, required reasons or notes, and related-report context without reducing the resource's history to one overwritten latest value;
* **audit-log records** preserve accepted administrative and system-level accountability for actions such as account management, role changes, password resets, taxonomy management, and system-setting changes.

These records exist because the confirmed moderation and administrative workflows require a durable and reviewable account of what action occurred, who performed it, when it occurred, and any short reason or context required by the workflow.

They are not collected for general surveillance, behavioral profiling, or unrestricted activity analytics. Detailed privacy-safe content boundaries for these accountability records belong to Section 10.

### 4.6 Bookmarks, Helpful Marks, Activity Counts, and Notifications

* **Bookmarks** primarily support **user convenience** by allowing a user to save an Approved resource for later access. The bookmark relationship does not grant access and is not collected to create a general behavioral profile or analytics history.
* **Helpful marks** provide a **binary resource-feedback signal**. There is no rating scale, written review, or sentiment-profile feature in v1.0.
* **View and download counts** exist only as **aggregate totals** supporting basic resource activity indicators and simple listings such as most-viewed or most-downloaded resources. The accepted design contains no per-user viewing history, per-user download history, behavioral profile, analytics trail, or recommendation profile.
* **Notifications** support **workflow awareness**, such as informing a user that an upload received a decision, that a correction note is available, or that a relevant workflow item requires attention. A notification is a pointer only. It does not grant access and does not replace current role, account-status, resource-status, ownership, or file-availability checks.

### 4.7 AI-Processing Acknowledgment

AI-notice acknowledgment identified in Section 3.7 exists as an **application-level workflow gate for optional Pending-file AI processing**.

Its practical function is to record whether the uploader acknowledged the applicable notice and to determine whether an otherwise eligible Pending resource may enter the configured AI-processing path after successful basic upload validation.

This document does not treat acknowledgment alone as legal consent under RA 10173 or as resolving every lawful-basis question that may apply to a real institutional deployment. The acknowledgment is an application workflow condition, not a substitute for institutional legal analysis, official privacy notice responsibilities, or AI-provider review.

Declining or not providing acknowledgment never blocks the ordinary non-AI upload and moderation workflow. It only causes Pending-file AI processing to be skipped for that resource.

Detailed notice content, external-provider exposure, acknowledgment behavior, and transmission boundaries belong to Section 8.

### 4.8 AI-Generated Derived Output

AI-generated output identified in Section 3.7 exists only when the applicable AI feature is enabled and the source resource satisfies the accepted status-based eligibility rules.

Its practical purposes are limited to the accepted assistive functions:

* draft resource summaries;
* suggested tags;
* suggested metadata;
* duplicate or similarity flags;
* moderation hints;
* related-resource recommendations.

These outputs may support human review, resource organization, and resource discovery. They do not approve, reject, publish, validate, hide, restrict, remove, or otherwise control a resource decision.

AI output remains optional, configurable, assistive, and non-authoritative. The core platform in Phases 1–2 remains fully usable with zero AI configuration and without any AI output being generated.

Detailed external processing, provider exposure, visibility, invalidation, and resource-lifecycle behavior belong to Sections 8 and 9.

### 4.9 Technical File Metadata and System Settings

Technical file metadata identified in Section 3.8 — original filename, protected stored-file reference, validated file type, file size, and `file_availability` — supports:

* server-side upload validation;
* protected file identification and storage reference;
* controlled file serving;
* file-lifecycle handling when a file becomes deleted, invalidated, unavailable, or otherwise ineligible for access.

These purposes do not make the technical metadata an independent permission grant. Current account, role, ownership, resource-status, and file-availability rules must still govern access.

The `system_settings` data area supports the accepted system-wide AI enabled/disabled configuration behavior. It does not create a broader user-preference profile, analytics configuration system, or additional privacy-management module.

### 4.10 A Valid Purpose Is Not a Blank Check

Each purpose described in this section is intentionally narrow and tied to a confirmed workflow, permission, technical operation, or accountability requirement.

None of these purposes should be interpreted as permission to collect additional information beyond what the accepted v1.0 design requires. A valid practical purpose does not justify unrelated, excessive, speculative, or future-oriented data collection.

For example, richer account profiles might support personalization, and per-user activity histories might support more detailed analytics. Neither purpose is part of the accepted v1.0 scope, and neither may be introduced by broadly reinterpreting the purposes described above.

Any future proposal to collect additional personal, behavioral, academic, or technical data would require explicit review for scope, schema, workflow, security, and privacy impact rather than being treated as automatically covered by this section.

Section 5 defines the corresponding data-minimization and avoided-collection boundaries in more detail.

---

*End of Section 4.*

## 5. Data Minimization and Avoided Collection

### 5.1 What Minimization Means for This Document

Data minimization, in this document, means limiting **collection, storage, duplication, exposure, and retention** to what the confirmed v1.0 workflows, technical operations, and accountability requirements actually need.

This section describes data the accepted design deliberately avoids collecting or tracking and explains the practical boundaries that keep existing data handling tied to confirmed system purposes.

It is a student-project privacy-planning reference, not a compliance certification. Describing the accepted design as minimized does not mean that it has been formally verified as compliant with RA 10173 by an institutional, legal, or regulatory authority.

### 5.2 Account Data Remains Minimal

Apart from its internal account record identifier, the accepted `accounts` table stores only:

* username;
* password hash;
* display name;
* role;
* account status;
* account creation and last-updated timestamps.

No additional account-profile field is included in v1.0.

The Student self-registration workflow requires only the accepted account information above. Teacher/Instructor, Moderator, and Admin accounts use the same account fields but are created or assigned through the Admin-provisioning workflow.

No email address, student number, course/program, year level, section, phone number, address, birth date, gender, profile photo, biography, or identity-verification status is stored as part of an account profile.

Course/program and year-level data do exist elsewhere in the accepted design, but only as controlled academic taxonomy and resource-organization metadata. They describe uploaded resources, not the personal academic profile of the account holder.

Avoiding additional profile fields reduces the amount of personal information held by the application, but it also creates an accepted tradeoff. The system has no roster integration, institutional-email verification, identity-verification field, or external institutional identity source through which it can independently verify a self-registered Student's official institutional identity.

Student username and display-name values are self-provided. Teacher/Instructor, Moderator, and Admin accounts are created or assigned through the controlled Admin-provisioning workflow, but the application still has no separate technical mechanism that independently verifies the account holder against an institutional identity source.

This is an accepted v1.0 tradeoff between minimal profile collection and identity assurance. It is not an open requirement for institutional-email collection, student-number validation, roster integration, or a new identity-verification feature.

Plaintext passwords are not retained. The application stores only the password hash required for accepted authentication and password-management behavior. The hash is not a recoverable copy of the original password and is not used as profile information. Detailed password hashing and verification mechanics remain defined in `SECURITY_NOTES.md`.

Two related storage and scope choices are also preserved:

* v1.0 has no self-service password-recovery token table, expiring reset-token mechanism, email-based reset workflow, or public “forgot password” process. Password reset is Admin-assisted through the existing account and audit structures.
* v1.0 uses PHP's native server-side session handling. It has no database-backed session table, Admin-visible active-session history, or separate session-management module.

These points are stated here only as avoided additional storage and scope. Authentication and session-security implementation remain owned by `SECURITY_NOTES.md`.

Email verification and email-based notifications are also not v1.0 requirements. The application does not collect contact information merely to support a possible future notification method that is not part of the accepted design.

### 5.3 Resource Metadata Remains Limited to Confirmed Fields

The accepted resource metadata set is limited to:

* title;
* description;
* course/program;
* subject;
* year level;
* topic;
* resource type;
* controlled tags.

Course/program, subject, year level, resource type, and tags are **resource-organization metadata**. They classify and organize an uploaded academic resource; they are not additional Student account-profile fields.

Tags are controlled, Admin-managed values. They are not unrestricted profile tags, social labels, or user-created identity descriptors.

This field-level minimization has an important limit: it constrains what the schema requests, but it cannot by itself control every detail an uploader includes in the accepted free-text fields, original filename, or uploaded file content.

Title, description, topic, original filename, and uploaded file content originate with the uploader. Moderator/Admin users may later review or edit resource metadata according to the accepted moderation permissions, but minimizing the schema does not automatically ensure that uploader-provided content is free of unnecessary information about the uploader, classmates, teachers, or other people.

For example, an uploader may include a classmate's name in a description, place a teacher's name in an original filename, or upload a scanned handout containing names, signatures, photographs, or other identifying details. The schema does not request those details merely because it permits academic resource uploads.

The privacy implications of information about other people and the uploader's responsibility for submitted content are addressed in Section 6.

BPC LearnShare v1.0 does not include:

* automated personal-information detection;
* automatic content redaction;
* an anonymization process;
* OCR-based privacy scanning;
* AI-based privacy scanning;
* automatic removal of personal information from uploaded content.

Uploaded resources continue through the accepted human moderation workflow. That workflow does not create a guarantee that every unnecessary or inappropriate personal detail will always be detected.

### 5.4 No Behavioral-Profiling or General Activity-Tracking Purpose

The accepted design deliberately does not collect:

* per-user viewing history;
* per-user download history;
* written resource reviews;
* numeric resource ratings;
* per-user engagement scores;
* user rankings;
* general analytics event trails;
* behavioral profiles;
* recommendation profiles based on user activity;
* advertising or promotional profiles.

View and download data remain aggregate counters stored on the resource record. The application does not record which individual account performed each view or download or preserve a per-user access history.

Helpful remains a binary account-resource relationship. It does not include a rating scale, written review, sentiment score, or user reputation value.

Bookmarks and Helpful marks are still account-resource relationships and therefore reflect specific user actions. However, the accepted v1.0 design uses them only for their confirmed purposes: bookmark convenience and binary resource feedback. It does not define a profiling, user-scoring, advertising, or general behavioral-analytics use for those relationships.

The existence of these accepted relationships must not be interpreted as authorization to combine them into a new user-interest profile, engagement score, personalized behavioral model, or recommendation profile without a separate scope, schema, workflow, security, and privacy review.

BPC LearnShare is also not a social-media platform. It does not collect data for:

* social feeds;
* following or follower relationships;
* direct messaging;
* public social profiles;
* social engagement scores.

These absences are accepted scope and minimization boundaries, not missing v1.0 features.

### 5.5 Accountability Records Should Contain Only What the Workflow Needs

Reports, resource action history, audit-log entries, and notifications exist for the accepted reporting, moderation, accountability, and workflow-awareness purposes described in Section 4.

Minimization for these records means that comments, reasons, notes, and messages should contain only the information reasonably needed to understand or complete the relevant workflow. They must not become unrestricted narratives, duplicate storage for uploaded content, or general activity-tracking records.

For notifications specifically, stored messages should use generic action or status wording, such as indicating that a decision was made or that an item requires attention.

Stored notification messages should not duplicate:

* resource titles;
* original filenames;
* full moderation notes;
* uploaded content;
* full report details;
* other unnecessary resource information.

Where a notification points to a resource, report, action-history entry, moderation queue, or report queue, the destination page — not the stored notification message — resolves the current permitted details.

Opening the destination must still pass the normal ownership, account-status, role, target-existence, resource-status, and file-availability checks that apply to that destination. A notification remains a pointer only and never becomes a permission grant.

Detailed privacy-safe content boundaries for `resource_action_history` and `audit_log` belong to Section 10. This section establishes only the broader minimization requirement that accountability records remain limited to their accepted purpose.

### 5.6 AI Processing Remains Limited to Eligible Content and Accepted Purposes

Optional AI processing must remain limited to:

* resources that satisfy the accepted status-based eligibility rules;
* content needed for the applicable accepted AI-assisted function;
* the confirmed assistive purposes defined by the project.

AI availability does not justify exploratory, unrestricted, or unrelated processing of resource content.

For Pending resources, successful basic upload validation, clear notice, and uploader acknowledgment remain required before Pending-file AI processing may occur.

If acknowledgment is missing or declined:

* ordinary upload continues;
* the resource may proceed through the normal non-AI moderation workflow;
* Pending-file AI processing is skipped.

This document does not treat acknowledgment alone as legal consent or as resolving every lawful-basis question that may apply to a real institutional deployment.

Detailed notice content, external-provider exposure, transmission boundaries, and provider handling belong to Section 8.

The system should also avoid unnecessary repeat processing of unchanged content. When a valid stored AI output remains current, eligible, and usable under the accepted lifecycle rules, the system should use that stored output rather than repeatedly regenerating it from an unchanged file.

This preserves the accepted cost-control direction and also reduces unnecessary repeated exposure of eligible resource content to the configured AI-processing path.

The accepted current-value AI-output storage model supports this behavior, but exact caching, change-detection, regeneration, and provider-call mechanics remain for `AI_FEATURES.md` and `BUILD_PLAN.md`.

### 5.7 Removed-Resource Minimization Under D040

D040 is the accepted application-level minimization rule for Removed resources specifically.

When a resource becomes Removed:

* `title` becomes `[Removed resource]`;
* `description` becomes `[Removed resource]`;
* `topic` becomes `[Removed]`;
* `original_filename` becomes `[removed]`;
* all associated `resource_tags` rows are deleted.

Required account-linked, taxonomy, technical, activity, AI-notice acknowledgment, timestamp, and historical relationship data may remain because reports, resource action history, bookmarks, Helpful marks, replacement relationships, and other accepted records may still reference the resource.

The retained Removed-resource record is therefore **minimized within the accepted schema but not anonymized**. It remains attributable to the uploader account and retains the context required for Admin-only accountability and reference.

D040 applies only to Removed resources.

It does not apply to Withdrawn resources. Withdrawn resources retain their original:

* title;
* description;
* topic;
* original filename;
* controlled-tag associations.

Their file content and draft AI outputs still follow the separate Withdrawn-resource lifecycle rules established by D020.

D040:

* adds no table;
* adds no column;
* creates no new resource status;
* creates no anonymization feature;
* creates no purge engine;
* creates no retention scheduler;
* creates no general data-erasure module.

It is a fixed, narrowly scoped removal-time lifecycle rule implemented through existing resource fields and relationships.

Full status-by-status retention, file handling, AI-output handling, and historical-record behavior belong to Section 11.

### 5.8 Minimization Is Not Expanded by Anticipated Future Usefulness

BPC LearnShare v1.0 does not collect data merely because it might become useful for an unconfirmed future feature.

Each accepted data category must remain connected to a confirmed v1.0 workflow, technical operation, access requirement, or accountability purpose.

The absence of a field or tracking mechanism — such as:

* email address;
* student number;
* expanded account profile;
* identity-verification status;
* written rating;
* per-user activity history;
* behavioral profile;
* recommendation profile;

must not be treated as permission to add that data quietly during implementation.

Any future proposal involving additional personal, academic, behavioral, operational, or technical data requires explicit review for:

* project scope;
* user need;
* workflow impact;
* permissions;
* database and schema impact;
* security risk;
* privacy impact;
* implementation feasibility.

Additional collection is not automatically authorized by the purposes stated in Section 4 or by the minimization discussion in this section.

---

*End of Section 5.*

## 6. Uploaded Academic Content, Information About Other People, and Uploader Responsibilities

### 6.1 Purpose of This Section

Sections 3–5 identified what data the accepted design handles, why it is needed, and how collection is minimized.

This section addresses a different and narrower risk: uploaded academic content — including resource metadata, original filenames, and file content — originates with the uploader and is not necessarily limited to information about the uploader. It may contain information about classmates, teachers, authors, presenters, document creators, or other people.

This section explains that risk, states the uploader's practical responsibility before submission, and clarifies what the application, moderation process, and existing report workflow do and do not address.

### 6.2 Who May Upload, and What That Does Not Guarantee

Only Student and Teacher/Instructor accounts may submit ordinary academic resources in v1.0.

Teacher/Instructor uploads pass through the same Pending moderation process as Student uploads. There is no automatic approval or trusted-uploader bypass for Teacher/Instructor submissions.

Moderator and Admin accounts do not submit ordinary resources as contributors.

These role restrictions determine **who may submit content**. They do not determine **what the submitted content contains** and do not independently verify:

* that the uploader is authorized or reasonably permitted to share a particular document;
* that information about other people is necessary or appropriate for the academic resource;
* that a photograph, screenshot, scanned page, or document contains no unnecessary identifying information;
* that the uploader owns every part of the submitted material;
* that every person named or depicted has received notice or provided any legally relevant permission.

Upload eligibility and content appropriateness are separate concerns.

### 6.3 Where Information About Other People May Appear

Information about people other than the uploader may appear in:

* the resource title;
* the resource description;
* the topic field;
* the original filename;
* document text;
* presentation slides;
* citations and source references;
* photographs;
* screenshots;
* scanned handwritten notes;
* images embedded in documents or presentations.

Examples may include:

* classmates' names;
* Teacher/Instructor names;
* author or document-creator names;
* presenter names;
* group-project member names;
* faces;
* signatures;
* student identifiers visible inside a document or image;
* handwriting;
* classroom or institutional details.

The presence of a name, photograph, attribution, citation, or reference to another person is not automatically inappropriate or unlawful.

Academic attribution, author names, teacher names, presenter information, citations, and source references may be relevant and reasonably necessary to understand the origin, authorship, or academic context of a resource.

The practical minimization principle is therefore not to remove all names or attribution automatically. Uploaders should avoid unnecessary identifying information and should include information about other people only when it is relevant, appropriate, and reasonably necessary for the academic resource being shared.

This document does not make a formal legal determination regarding consent, copyright ownership, fair use, authorization, or lawful basis for any particular resource.

### 6.4 Uploader Responsibility Before Submission

The uploader is responsible for reviewing the material before submission and for avoiding content that they are not authorized or reasonably permitted to share.

Before uploading, the uploader should review:

* the title;
* description;
* topic;
* selected metadata;
* original filename;
* visible document or presentation content;
* photographs and screenshots;
* scanned pages;
* embedded images;
* names, signatures, identifiers, and other information about people appearing in the resource.

This responsibility exists because BPC LearnShare v1.0 has no:

* automated personal-information detector;
* automatic privacy-redaction system;
* OCR-based privacy scanner;
* AI-based privacy scanner;
* automatic ownership verifier;
* automatic copyright-verification system;
* automated authorization-to-share checker.

None of these mechanisms is introduced by this document as a v1.0 requirement.

This limitation does not mean that all privacy, security, or governance responsibility transfers to the uploader.

The responsibility boundary remains:

* **the uploader** is responsible for reviewing and making appropriate submission choices;
* **BPC LearnShare** must enforce its accepted application controls, including technical upload validation, mandatory moderation, status-based visibility, protected file access, and reporting;
* **Bulacan Polytechnic College or the authorized deploying office** remains responsible for formal institutional privacy governance and real-deployment procedures outside the v1.0 application scope.

Uploader responsibility does not replace the application's required controls or the institution's responsibilities.

### 6.5 What Basic Upload Validation Does and Does Not Check

Basic upload validation checks the accepted technical file requirements before a resource record is created.

The required checks are:

1. allowed file extension;
2. MIME/content consistency with the claimed file type;
3. file size against the configured maximum;
4. rejection of empty or unreadable/corrupt files.

Passing these checks means only that the submitted file satisfied the accepted technical upload requirements.

Successful validation does not determine or guarantee:

* privacy compliance;
* academic correctness;
* factual accuracy;
* copyright ownership;
* authorization to share;
* consent or permission involving people named or depicted;
* absence of unnecessary personal information;
* absence of leaked, inappropriate, or otherwise unauthorized academic content.

A file may pass every technical validation check and still contain information that should not have been included or material the uploader may not be reasonably permitted to share.

Basic upload validation is a technical safety gate, not a privacy, ownership, academic-quality, or legal review.

### 6.6 What Human Moderation Does and Does Not Guarantee

Every ordinary Student or Teacher/Instructor upload enters the Pending moderation workflow before it can become broadly visible.

For a Pending resource, Moderator/Admin review may result in:

* Approve;
* Reject;
* Request Correction.

During review, Moderator/Admin users may identify content that appears:

* inappropriate;
* inaccurate or misleading;
* unauthorized;
* duplicated;
* related to a suspected leaked exam, quiz, or answer key;
* privacy-concerning;
* otherwise unsuitable for approval.

If a concern is identified after a resource is already Approved, the existing post-approval workflows apply instead.

Moderator/Admin may:

* Hide the resource for temporary investigation;
* Restrict the resource after review;
* request correction through the accepted linked replacement workflow;
* return an eligible Hidden or Restricted resource to Approved after review;
* Remove the resource through Admin-only authority where appropriate.

An Approved resource must not be moved directly to Needs Correction. A correction to an Approved resource follows the accepted linked replacement-record workflow.

Human moderation is an important control, but it is not a guarantee.

Approval confirms only that the resource passed the accepted moderation process. It does not guarantee:

* factual accuracy;
* academic correctness;
* legal authorization to share;
* copyright compliance;
* privacy compliance;
* detection of every unnecessary personal detail;
* detection of every name, signature, identifier, or image-related concern;
* detection of every inappropriate or unauthorized element.

Human review may miss information that is buried in a long document, embedded in an image, contained in a scanned page, or otherwise difficult to notice.

The system does not certify uploaded resources as legally authorized, privacy-compliant, or academically correct merely because they reached Approved status.

### 6.7 Ongoing Oversight Through the Existing Report Workflow

After a resource becomes Approved, eligible Student and Teacher/Instructor users may report it through the existing report workflow.

The accepted report reasons remain exactly:

* Outdated resource;
* Incorrect or inaccurate content;
* Inappropriate content;
* Duplicate or near-duplicate resource;
* Suspected leaked exam, quiz, or answer key;
* Copyright or unauthorized material concern;
* Other.

A user raising a concern involving unnecessary information about another person must use the closest applicable accepted report reason and may provide relevant context through the existing optional report-comment field.

This document does not introduce:

* a privacy-specific report reason;
* a new complaint type;
* a copyright portal;
* a consent-verification workflow;
* a privacy complaint portal;
* a data-subject request portal;
* a separate takedown module;
* a new content-review status.

Moderator and Admin users do not submit public-style reports.

If Moderator/Admin users identify a concern directly, they act through the existing moderation or resource-management workflow without first creating a public report.

Moderator/Admin may directly Hide or Restrict an Approved resource. Admin may directly Remove an Approved resource. These actions remain subject to the accepted reason, logging, role, status, and accountability requirements.

### 6.8 Image Uploads and Their Particular Privacy Risk

Under D025, JPG/JPEG and PNG remain accepted academic resource file types together with PDF, DOCX, PPTX, and TXT.

Images and scanned materials may contain information that is not visible in the resource metadata, including:

* faces;
* names;
* signatures;
* student identifiers;
* handwriting;
* classroom details;
* document headers;
* photographs of people;
* information appearing in the background of an image.

Image-only resources may not be searchable through extracted text unless OCR, AI vision, or another text-extraction method is added in a future version.

BPC LearnShare v1.0 has no:

* OCR-based privacy scan;
* AI-vision privacy screening;
* facial-recognition feature;
* automatic face blurring;
* automatic identifier detection;
* image-redaction feature.

None of these is introduced as a v1.0 requirement.

For privacy-relevant information inside images, the applicable **content-level safeguards** are:

* uploader review before submission;
* human moderation before normal public visibility;
* the existing report and post-approval moderation workflows.

Technical upload validation, protected storage, status-based access control, and controlled file serving still apply to image resources, but those controls do not inspect an image for unnecessary personal information.

Optional AI moderation assistance, where implemented and eligible, remains assistive and non-authoritative. It is not an automated privacy-screening system and does not guarantee detection of privacy-relevant image content.

### 6.9 Original Filenames May Reveal Information

The original filename is retained for display while permitted by the applicable resource lifecycle. It is not used as the physical storage path.

The stored physical filename or protected storage reference remains randomized and non-guessable according to the accepted file-handling rules.

However, an original filename may itself contain identifying information, such as:

* a classmate's name;
* a Teacher/Instructor name;
* a student identifier;
* a group name;
* another descriptive detail about a person.

Uploaders should review the original filename before submission and avoid unnecessary identifying information where it is not needed for the resource's academic context.

The accepted lifecycle rules still apply. For a Removed resource, D040 replaces `original_filename` with `[removed]`.

### 6.10 Information About Other People and Optional AI Processing

When optional Pending-file AI processing is enabled and the resource satisfies the accepted eligibility conditions, information about other people contained in the eligible resource may also be included in the content processed through the configured AI path.

Because of this possibility, uploaders should review the complete submission — including metadata, filename, document content, images, and information about other people — before deciding whether to acknowledge Pending-file AI processing.

The application-level acknowledgment records whether the uploader acknowledged the applicable AI-processing notice for that resource.

This document does not treat acknowledgment alone as:

* establishing legal consent on behalf of another person named or depicted in the content;
* proving that the uploader owns or is authorized to share every part of the resource;
* resolving third-party rights;
* resolving every lawful-basis question that may apply to the processing.

Detailed notice content, external AI-provider exposure, transmission boundaries, acknowledgment mechanics, and institutional provider responsibilities belong to Section 8.

Detailed AI-output privacy and resource-lifecycle behavior belong to Section 9.

---

*End of Section 6.*

## 7. Role-, Ownership-, and Status-Based Access as Privacy Controls

### 7.1 Access Control as a Privacy-Supporting Behavior, Not a Compliance Guarantee

BPC LearnShare v1.0 restricts who may see and act on information through login-required access, role-based permissions, ownership rules, resource-status visibility, object-level checks, and file-availability rules.

These are application-level controls that reduce unnecessary exposure of account data, resource content, AI-derived output, reports, and accountability records.

This section explains the privacy significance of those already-confirmed controls. It does not re-derive their full security implementation, which remains defined in `SECURITY_NOTES.md`, and it does not claim that role-based access or restricted visibility alone constitutes or guarantees legal compliance with RA 10173 or another framework.

### 7.2 Login-Required Access Is the Baseline Control

Under D005, BPC LearnShare v1.0 has no public, logged-out resource catalog.

Unauthenticated visitors may access only:

* the login page;
* the Student registration page;
* basic public information pages, if included.

Unauthenticated visitors may not:

* browse or search academic resources;
* open resource-detail pages;
* view or download uploaded files;
* create or use bookmarks;
* mark resources as Helpful;
* submit reports;
* upload resources;
* access moderation or administrative areas;
* access protected file-serving routes;
* use AI-assisted resource features.

Protected access requires a currently authenticated and Active account.

This login boundary reduces broad public exposure of academic content and account-linked system activity, but authentication alone does not grant access to every resource, file, report, AI output, or administrative record. The system must still apply the role-, ownership-, status-, object-, and availability-based checks relevant to the requested action.

### 7.3 Role-Based Access

The four confirmed v1.0 roles are:

* Student;
* Teacher/Instructor;
* Moderator;
* Admin.

No additional privacy officer, Data Protection Officer application role, reviewer role, content-owner role, or separate privacy-access tier exists in v1.0.

Role determines the general category of actions an account may perform.

For protected requests, the application must verify from current database data that:

* the account still exists;
* the account status is Active;
* the current role permits the requested action.

Role and account status must not be trusted only from values cached when the session was created.

If an account is Disabled or its role changes after login, an old session must not preserve authority that the account no longer has. The next protected request must be evaluated against the account's current database state.

This section does not re-derive password handling, session creation, session timeout, logout, or CSRF mechanics. Those implementation-security requirements remain defined in `SECURITY_NOTES.md`.

### 7.4 Ownership-Based Visibility and Management

Ownership is distinct from role.

Role determines the general category of action an account may perform. Ownership identifies the Student or Teacher/Instructor account recorded as the uploader of a specific resource and supports the status-dependent visibility and management rules for that resource.

Because only Student and Teacher/Instructor accounts may initiate ordinary resource uploads, only these roles hold uploader-level ownership over ordinary resources.

Ownership supports visibility of the uploader's own non-public resource only where the current resource-status rules permit uploader access.

Under the accepted status model, an uploader may view their own resource record while it is:

* Pending;
* Needs Correction;
* Rejected;
* Withdrawn;
* Hidden;
* Restricted;
* Replaced.

Approved resources are available to all authenticated Active users under the normal Approved-resource access rules.

Removed resources are not visible to the original uploader. Only Admin may access the remaining minimized accountability/reference record.

Visibility does not automatically grant management authority.

The uploader may edit or withdraw their own resource only while it is:

* Pending;
* Needs Correction;
* Rejected.

The exact allowed action still depends on the current workflow and status.

Ownership does not:

* bypass moderation;
* permit self-approval;
* make a non-public resource broadly visible;
* allow the uploader to change a resource's status directly;
* permit direct editing of an Approved resource;
* permit withdrawal of an Approved, Hidden, Restricted, Removed, or Replaced resource;
* override current resource status;
* override current file availability;
* override a current server-side permission check.

Once a resource becomes Approved, the uploader may not edit it directly.

A correction to an Approved resource requires a new linked Pending replacement resource. Only one open replacement may exist for the same original resource at a time.

The replacement is a separate resource record with its own uploader reference, status, file, lifecycle, AI output, and activity relationships. The replacement workflow does not create additional access rights beyond the accepted role, ownership, status, and file-availability rules.

### 7.5 Resource Status Determines the Current Visibility Boundary

Every resource has exactly one current status.

The accepted visibility model is:

| Resource Status      | Current Visibility                                                                                     |
| -------------------- | ------------------------------------------------------------------------------------------------------ |
| **Approved**         | Available through normal browse/search to authenticated Active users                                   |
| **Pending**          | Uploader, Moderator, and Admin only                                                                    |
| **Needs Correction** | Uploader, Moderator, and Admin only                                                                    |
| **Rejected**         | Uploader, Moderator, and Admin only                                                                    |
| **Withdrawn**        | Uploader, Moderator, and Admin only                                                                    |
| **Hidden**           | Uploader, Moderator, and Admin only                                                                    |
| **Restricted**       | Uploader, Moderator, and Admin only                                                                    |
| **Replaced**         | Uploader, Moderator, and Admin only                                                                    |
| **Removed**          | Admin-only access to the minimized accountability/reference record; no uploader or general-user access |

Only Approved resources appear in normal browse and search.

Hidden and Restricted retain their distinct accepted meanings:

* **Hidden** is a temporary investigative hold while a report or concern is being reviewed.
* **Restricted** is a longer-term limited-access outcome after review.

This visibility model does not introduce:

* course-specific access;
* subject-specific access;
* classroom-specific access;
* group-specific sharing;
* public sharing;
* guest access;
* anonymous links;
* per-resource access lists;
* custom uploader-selected visibility levels.

Resource status must be checked at the time of the request. A previous Approved state does not continue granting broad access after the resource becomes Hidden, Restricted, Removed, Replaced, or otherwise non-public.

### 7.6 Pointers and Previously Rendered Content Do Not Grant Continuing Access

Bookmarks, notifications, target references, old direct links, and previously rendered interface state are pointers or prior representations only.

They do not independently grant permission.

If a referenced resource later changes status, the destination request must use the resource's current database state rather than the state that existed when the bookmark, notification, link, or page was created.

For example:

* a bookmark to a resource that later becomes Hidden does not make the Hidden resource broadly accessible;
* a notification pointing to a Restricted resource does not override Restricted-resource visibility;
* an old link to a Removed resource does not restore uploader or general-user access;
* an old link to a Replaced resource must not serve the replaced file to general authenticated users;
* a page that was rendered while a resource was Approved does not authorize a later request after the resource status changes.

Each destination endpoint must perform the current authentication, account-status, role, ownership, object-existence, resource-status, and file-availability checks applicable to the requested action.

### 7.7 Resource Visibility and File Availability Are Separate Gates

Permission to view a resource record does not automatically mean that its uploaded file may be served.

Under D034, a file may be served only when both of the following are true at request time:

1. the applicable current role, ownership, permission, and resource-status rules allow access; and
2. `file_availability = 'available'`.

Both gates are required.

Neither gate substitutes for the other.

Therefore:

* a file marked `available` must not be served when the resource's current status or the requester's permission blocks access;
* an Approved resource file must not be served when `file_availability` is `deleted` or `invalidated`;
* uploader visibility of a Withdrawn, Hidden, Restricted, or Replaced resource record does not automatically mean that its physical file remains available;
* a stored file reference does not itself grant access.

Uploaded files must be served only through the controlled application file-serving path.

They must not be exposed through direct static URLs that bypass current application checks.

Detailed storage paths, file-streaming mechanics, document-root structure, and implementation routing remain assigned to `SECURITY_NOTES.md` and `BUILD_PLAN.md`.

### 7.8 Moderator and Admin Access Remain Distinct

Moderator and Admin remain separate roles.

A Moderator may perform the accepted moderation and report-management actions, including:

* reviewing eligible Pending resources;
* Approving;
* Rejecting;
* Requesting Correction;
* reviewing reports;
* Hiding an eligible Approved resource;
* Restricting an eligible Approved resource;
* returning an eligible Hidden or Restricted resource to Approved according to the accepted workflow;
* viewing moderation- and report-related history required for the role.

A Moderator may not perform Admin-only resource removal.

An Admin may perform accepted Moderator actions and also holds the accepted administrative authority to:

* manage user accounts;
* assign or change roles;
* disable or re-enable accounts;
* perform Admin-assisted password resets;
* manage courses/programs, subjects, year levels, resource types, and controlled tags;
* manage system settings;
* perform Admin-only resource removal;
* view the full system audit log.

Moderator access to moderation- and report-related history must not be expanded into full system audit-log access.

Detailed audit-log content, safe-summary rules, and ledger integrity remain assigned to Section 10.

### 7.9 Report Access Remains Within the Accepted Workflow

Student and Teacher/Instructor users may use the public report workflow only for an eligible Approved resource that they do not own.

Moderator and Admin users do not file public-style reports. They access the report queue and use the accepted moderation or report-management actions according to their role.

Access to a report does not automatically grant access to:

* unrelated reports;
* unrelated resources;
* unrelated uploader records;
* full system audit information;
* administrative records outside the reviewer's role.

Any report, resource, action-history, or queue target must still pass the current target-existence, role, status, and visibility checks before information is shown.

This section does not introduce:

* reporter-facing report-history permissions;
* a report appeal workflow;
* a private complaint portal;
* a privacy-specific report system;
* an additional report access tier.

### 7.10 AI-Output Access Requires Both Source-Resource Eligibility and Output-Lifecycle Eligibility

AI output is tied to one source resource and must not become an independent route around that resource's access rules.

Draft AI output generated for a Pending resource is visible only to:

* the uploader of that Pending resource;
* Moderator;
* Admin.

Draft output is not available to general authenticated users and must not appear in normal public-facing resource reads, browse/search results, or recommendation surfaces.

Source-resource access is necessary but is not the only AI-output check.

The application must also respect the AI output's current lifecycle state.

In particular:

* invalidated AI output is excluded from every public-facing read regardless of the source resource's current status;
* AI output from a Hidden or Restricted resource is excluded from public-facing search and recommendation use and remains subject to the source resource's restricted audience;
* AI output tied to a Replaced resource must not remain publicly visible or be inherited by the replacement;
* AI output tied to a Removed resource must be deleted or invalidated according to the accepted lifecycle rules;
* a replacement resource must generate, review, or store its own output if it becomes eligible.

A permitted source-resource status does not make invalidated output visible.

Likewise, a retained AI-output row does not independently make an ineligible resource public.

Detailed output transitions, invalidation behavior, replacement handling, and source-file-change behavior belong to Section 9.

### 7.11 Removed-Resource Access Remains the Strictest Resource Boundary

A Removed resource is not visible to:

* general authenticated users;
* the original uploader.

Only Admin may access the retained accountability/reference record.

Under D040, that retained record is minimized within the accepted schema but is not anonymized. It may still retain account-linked, taxonomy, technical, activity, acknowledgment, timestamp, and historical information needed for referential integrity and accountability.

Admin-only access therefore remains necessary after minimization.

Minimization does not make the retained record public, broadly visible, or safe to expose without current authorization.

This section does not repeat the full D040 placeholder and removal-operation rules because those are already documented in Sections 3 and 5. Section 7 addresses only the resulting access boundary.

### 7.12 Access Control Reduces Exposure but Does Not Eliminate Authorized-Access Misuse

Role-, ownership-, status-, object-, and file-availability checks reduce unnecessary access by limiting data to the users and staff roles permitted by the accepted design.

However, access controls cannot eliminate every misuse by a Moderator or Admin who already holds legitimate authority to view a resource or perform a permitted action.

For consequential state-changing activity, the accepted accountability structures preserve records of moderation, resource-status, administrative, taxonomy, account, and system-setting actions.

`resource_action_history` records accepted resource actions.

`audit_log` records accepted administrative and system-level actions.

These ledgers support later review of the actions they are designed to record.

They do **not** record every page view, resource-detail inspection, file viewing event, search, or other read-only access.

BPC LearnShare v1.0 does not include a general staff-access log, per-resource read audit, or complete activity-monitoring system.

The application must therefore not claim that it can reconstruct every instance in which an authorized Moderator or Admin viewed permitted information.

This is an accepted limitation of the current v1.0 accountability model, not a requirement introduced here for a new table, access-tracking event, monitoring module, or expanded audit system.

Detailed privacy-safe accountability content remains assigned to Section 10.

The limitation concerning read-only authorized-access visibility should be preserved in Section 13.

Access control and accountability support privacy, but they do not guarantee privacy compliance or prevent every form of insider misuse.

---

*End of Section 7.*

## 8. AI Processing, External API Exposure, Notice, and Acknowledgment

### 8.1 AI Remains Optional, Assistive, and Non-Authoritative

Every AI feature in BPC LearnShare v1.0 is optional, assistive, and non-authoritative.

Core Phases 1–2 remain fully usable when AI is:

* disabled;
* unconfigured;
* unavailable;
* failing;
* rate-limited;
* missing an API key;
* entirely absent.

Authentication, upload, moderation, metadata search and filtering, viewing, downloading, bookmarking, Helpful marks, reporting, notifications, and core Moderator/Admin workflows must continue through their accepted non-AI paths.

Zero AI output is a supported operating condition.

BPC LearnShare is not:

* AI-first;
* chatbot-first;
* an AI tutor;
* dependent on AI for ordinary academic resource use.

Admin controls the accepted system-wide AI enabled/disabled setting.

Enabling that setting permits configured AI-assisted functions to operate, but does not by itself trigger processing or make every resource eligible.

Actual AI processing still depends on:

* provider or local-AI configuration;
* applicable feature availability;
* current resource status;
* current status-based eligibility;
* successful basic upload validation where required;
* uploader notice acknowledgment for Pending-file processing;
* current lifecycle and access rules.

Admin enabling AI does not constitute uploader acknowledgment for a Pending resource and does not resolve institutional privacy, provider-review, legal, or governance responsibilities.

### 8.2 Local/LAN Operation and Optional External AI Transmission

BPC LearnShare v1.0 is designed as a local/LAN academic MVP.

Core application data, workflows, resource access, moderation, and non-AI functions remain within that local/LAN deployment environment.

Optional external AI processing creates an important exception to that boundary.

When an external API-based AI provider is configured and used, eligible resource content may leave the local/LAN environment and be transmitted to the external provider for the applicable accepted AI-assisted function.

Depending on the implemented phase and feature, accepted AI-assisted purposes may include:

* draft summaries;
* suggested tags;
* suggested metadata;
* duplicate or similarity flags;
* moderation hints;
* related-resource recommendations;
* Phase 4 content-based search;
* optional Phase 5 Approved-resource-only inquiry/chat.

Not every possible AI implementation necessarily transmits content externally.

API-based AI remains the preferred prototyping direction because it is more practical for the v1.0 capstone environment, while local AI through tools such as Ollama may be explored experimentally if available hardware supports it.

External API processing is therefore the primary privacy boundary that this document must address, but it is not described as the only possible implementation.

If an AI component is configured to run entirely within the local environment, the specific processing call may avoid transmitting eligible content to an external provider.

However, local execution does not automatically guarantee:

* correct access control;
* appropriate content eligibility;
* secure storage;
* accurate output;
* safe lifecycle handling;
* complete privacy protection;
* institutional governance compliance.

The same role, status, eligibility, lifecycle, human-review, and non-authoritative AI rules still apply regardless of where processing occurs.

### 8.3 Provider-Specific Practices Must Not Be Assumed

This document does not make generic claims regarding any AI provider's:

* data-retention period;
* model-training use;
* deletion practices;
* geographic processing or storage location;
* confidentiality;
* ownership terms;
* security guarantees;
* regulatory compliance.

Those matters depend on the provider actually selected, the provider's current terms and privacy documentation, available account or API configuration, and the institutional acceptability of those conditions at the time of real deployment.

Bulacan Polytechnic College or the authorized deploying office remains responsible for any formal provider review, contractual arrangement, data-sharing determination, institutional authorization, and related governance required for real deployment.

BPC LearnShare v1.0 does not introduce:

* a provider registry;
* a provider-review module;
* contract-management functionality;
* an institutional approval workflow;
* a data-sharing management module.

### 8.4 AI Payloads Must Remain Purpose-Limited

AI processing must remain limited to:

* content from a currently eligible resource;
* extracted text, file content, or metadata reasonably needed for the specific accepted AI function;
* the minimum practical payload required to perform that function.

The AI-processing path must not receive the entire database or unrelated system data merely because those records exist.

The following must not be included in an AI payload merely because they are available elsewhere in the application:

* plaintext passwords;
* password hashes;
* session identifiers;
* CSRF tokens;
* database credentials;
* AI API keys;
* full audit logs;
* full resource-action histories;
* unrelated report comments;
* unrelated moderation history;
* unrelated notification history;
* unrelated account information;
* unrelated resource content.

Uploader account identifiers, display names, original filenames, or other account-linked information should not be added to the payload unless they are reasonably necessary for the specific accepted AI function.

The accepted schema contains no provider-payload table, transmission-history table, prompt-history table, provider registry, or data-sharing table.

BPC LearnShare v1.0 does not create a separate local queryable history of complete provider requests and responses.

Application logs must not store full AI request payloads, full response payloads, API keys, or unnecessary provider error details.

The absence of a local transmission-history table does not establish what an external provider may retain. Provider-side handling remains subject to the provider-specific review described in Section 8.3.

Detailed API-key storage and application-log security remain defined in `SECURITY_NOTES.md`.

### 8.5 Current Resource Eligibility Must Be Checked Before AI Processing

AI eligibility follows the accepted status-based rule.

#### Approved

A currently Approved resource may be processed for accepted Approved-resource AI functions when:

* AI is enabled;
* the applicable AI implementation is configured and available;
* the resource remains currently eligible;
* the applicable access and lifecycle rules are satisfied.

Approved-resource functions may include summaries, related-resource recommendations, Phase 4 content-based search, and other accepted Approved-tier functions.

#### Pending

A Pending resource may be processed only when all applicable conditions are satisfied:

1. the file passed basic upload validation;
2. the uploader was shown the clear Pending-file AI notice;
3. the uploader acknowledged that notice;
4. AI is enabled and configured;
5. the resource is still currently Pending and otherwise eligible at processing time.

Validation without acknowledgment is insufficient.

Acknowledgment without successful validation is insufficient.

A stored acknowledgment must not override current status or current eligibility.

#### Ineligible statuses and content

Resources currently in the following statuses must not be newly processed through the AI path:

* Rejected;
* Withdrawn;
* Restricted;
* Removed;
* Replaced.

Private, unauthorized, or otherwise ineligible content must also not be processed.

A Hidden resource must not trigger new public-facing AI processing while Hidden and must remain excluded from public-facing AI use according to the accepted lifecycle rules.

If a reversible resource status later returns to Approved through an allowed workflow, future AI eligibility must be evaluated again from the resource's current status and current AI-output lifecycle state.

### 8.6 Eligibility Must Be Rechecked Before Sending and Before Saving

Resource eligibility may change while an AI request is waiting, processing, or returning.

The application must therefore recheck current eligibility:

1. immediately before eligible content is sent into the configured AI-processing path; and
2. before a late AI response is accepted or stored.

The recheck must use current server-side data rather than relying only on the resource state that existed when the request was first prepared.

If the resource is no longer eligible before transmission:

* the content must not be sent;
* the AI operation must be skipped or cancelled where possible.

If the resource becomes ineligible before a response is stored:

* the late response must not be treated as valid current output;
* it must be discarded or handled according to the accepted lifecycle rules.

Detailed lifecycle transitions, invalidation behavior, and late-response handling belong to Section 9.

### 8.7 The Pending-File AI Notice

Before a Pending resource may enter the AI-processing path, the uploader must be shown a clear notice.

The notice must be presented before acknowledgment can satisfy the Pending-file acknowledgment condition.

The notice should explain in understandable language:

* that AI processing is optional;
* that ordinary upload and human moderation continue without AI;
* that eligible resource content may be processed for specific assistive purposes;
* the applicable Pending-stage purposes, such as:

  * draft summary;
  * suggested tags;
  * suggested metadata;
  * duplicate or similarity assistance;
  * moderation assistance;
* that eligible file content, extracted content, or necessary metadata may be processed;
* that information about classmates, teachers, authors, presenters, or other people included by the uploader may also be part of the processed content;
* that, when external API processing is configured, eligible content may leave the local/LAN environment and be received by an external provider;
* that AI output may be incomplete, inaccurate, unsupported, or require human review;
* that AI does not approve, reject, publish, validate, Hide, Restrict, Remove, or delete a resource;
* that declining or not providing acknowledgment skips Pending-file AI processing only;
* that declining or not providing acknowledgment does not block upload or normal non-AI moderation.

The notice must not be presented as:

* a broad waiver;
* a release of liability;
* a copyright declaration;
* an ownership certification;
* a third-party consent declaration;
* proof of authority to share;
* a legal contract.

The application-level notice is not, by itself, the institution's complete official privacy notice.

It does not replace:

* formal institutional privacy notices;
* provider review;
* legal analysis;
* institutional authorization;
* contractual review;
* real-deployment governance.

### 8.8 Meaning and Storage of Acknowledgment

Pending-file AI acknowledgment is an application-level workflow condition.

The accepted resource-level fields are:

* `ai_notice_acknowledged`;
* `ai_notice_acknowledged_at`.

These fields record whether and when the uploader acknowledged the applicable Pending-file AI notice for that resource.

No additional acknowledgment or consent structure exists in the accepted schema.

BPC LearnShare v1.0 does not add:

* a notice-version column;
* a consent-history table;
* a per-feature consent table;
* a provider-acknowledgment table;
* an account-wide AI consent profile;
* a withdrawal-of-consent workflow;
* a consent-management module.

Acknowledgment satisfies only the acknowledgment condition for the Pending-file AI workflow.

It does not override:

* failed upload validation;
* current resource status;
* current AI eligibility;
* AI disabled state;
* missing AI configuration;
* current access rules;
* current lifecycle rules.

This document does not treat acknowledgment alone as:

* legal consent under RA 10173;
* proof that the uploader has authority to share the resource;
* consent given on behalf of another person named or depicted;
* proof of copyright ownership;
* resolution of third-party rights;
* resolution of every applicable lawful-basis question;
* institutional approval of the configured provider.

### 8.9 Acknowledgment After File or Notice Changes Remains a Carry-Forward Implementation Detail

The accepted source documents do not currently define whether the existing acknowledgment fields must be reset when:

* a Pending or Needs Correction resource receives a replacement file during resubmission;
* the privacy-facing notice wording is materially changed after acknowledgment was recorded.

This document therefore does not claim that an earlier acknowledgment automatically carries forward to changed file content or revised notice wording.

The exact reset and re-acknowledgment behavior must be defined before implementation in `AI_FEATURES.md` and `BUILD_PLAN.md`, using the existing accepted fields unless a later decision explicitly changes the schema.

The accepted v1.0 schema does not support historical notice-version tracking.

This section does not introduce:

* a notice-version field;
* acknowledgment-history storage;
* a new consent record;
* a new acknowledgment status.

### 8.10 Approved-Resource AI Does Not Introduce a New Acknowledgment Workflow

The Pending-file notice-and-acknowledgment requirement applies specifically to Pending-resource processing.

No accepted source creates:

* a separate Approved-resource acknowledgment step;
* a per-call acknowledgment prompt;
* an account-wide AI consent profile;
* a permanent resource-level AI opt-out.

Approved-resource AI processing remains subject to:

* current Approved status;
* AI enabled/configured state;
* current eligibility;
* current access rules;
* current AI-output lifecycle rules;
* institutional notice and provider responsibilities for real deployment.

Under the current accepted design, declining or not providing Pending-file acknowledgment skips only Pending-file AI processing.

It does not create a permanent resource-level opt-out from all later Approved-resource AI processing if the resource is subsequently Approved.

The Pending-file notice must not imply otherwise.

Changing this behavior would require an explicit future decision rather than a silent reinterpretation of acknowledgment.

### 8.11 AI Output Remains Subject to Human Review

AI output may be:

* incomplete;
* inaccurate;
* misleading;
* unsupported by the source;
* inappropriate for direct use without review.

Human users remain responsible for reviewing AI suggestions within their accepted permissions.

For a Pending resource:

* the uploader may review, edit, or discard AI suggestions for their own resource;
* Moderator/Admin may review, edit, or discard AI suggestions during moderation.

Under D028, a separate field-by-field acceptance checkbox is not required in v1.0.

When Moderator/Admin had a visible opportunity to review, edit, or discard AI suggestions, approving the resource without discarding or changing those suggestions may treat them as accepted for the accepted workflow.

This does not make the AI authoritative.

AI must never:

* approve a resource;
* reject a resource;
* publish a resource;
* validate a resource;
* Hide a resource;
* Restrict a resource;
* Remove a resource;
* delete a resource;
* make a final moderation decision.

AI does not guarantee academic correctness and must not replace Teacher/Instructor, Moderator, or Admin judgment.

AI must not be used to answer exams, quizzes, graded assignments, or answer keys.

Detailed AI-output state, acceptance, retention, invalidation, replacement, and source-file-change behavior belongs to Section 9.

### 8.12 Phase 5 Remains Optional Approved-Resource-Only Stretch Scope

Phase 5 AI inquiry/chat remains optional stretch scope only.

It must not become a required core module or the primary system direction.

If implemented, it must:

* use only currently Approved and eligible resources;
* cite or reference the source resource or resources used;
* state clearly when no Approved resource supports an answer;
* avoid inventing unsupported answers;
* avoid answering exams, quizzes, graded assignments, or answer keys;
* remain subject to current access, resource-status, AI-output, and lifecycle rules.

BPC LearnShare remains a moderated academic resource-sharing and management system, not a chatbot-first platform.

### 8.13 AI Failure and Missing Acknowledgment Do Not Block Core Workflows

AI failure, provider unavailability, missing configuration, rate limits, timeouts, or missing/declined Pending-file acknowledgment must not block:

* upload;
* moderation;
* metadata search and filtering;
* resource viewing;
* eligible file downloading;
* bookmarking;
* Helpful marks;
* reporting;
* notifications;
* core Moderator workflows;
* core Admin workflows.

When AI fails:

* the affected AI-assisted function may remain unavailable;
* the ordinary non-AI workflow continues;
* the system must not invent AI output;
* the system must not treat the AI operation as successful;
* stale output from another resource must not be substituted.

Missing or declined acknowledgment is not treated as an error.

It simply skips Pending-file AI processing for that resource.

### 8.14 Avoid Unnecessary Repeat Processing

An unchanged eligible file should not be repeatedly transmitted or regenerated when a valid current stored output remains usable under the accepted lifecycle rules.

Where current stored output remains:

* tied to the same resource;
* tied to the current source file;
* not invalidated;
* currently eligible;
* usable for the applicable feature;

the system should use that current output rather than making an unnecessary new AI request.

This supports:

* cost control;
* reduced provider calls;
* reduced repeated exposure of eligible content;
* simpler v1.0 operation.

Exact implementation details remain assigned to `AI_FEATURES.md` and `BUILD_PLAN.md`, including:

* payload construction;
* prompt design;
* provider selection;
* file-change detection;
* source-file-reference comparison;
* caching;
* retries;
* timeouts;
* regeneration triggers;
* provider-call handling.

---

*End of Section 8.*

## 9. AI Output Privacy and Lifecycle

### 9.1 AI Output Is Derived Data

AI output is derived from eligible resource content.

Depending on the accepted AI-assisted function, output may:

* summarize source material;
* reorganize information;
* infer relationships or similarities;
* suggest metadata;
* identify possible duplication;
* provide moderation hints;
* recommend related resources;
* restate or reflect information contained in the source.

AI output may therefore carry privacy relevance even when it does not reproduce the source word-for-word.

For example, a generated summary may repeat a classmate's name, teacher attribution, author name, or other identifying detail that appeared in the uploaded resource.

This document does not classify every AI output as personal information in every circumstance. Privacy relevance depends on the actual source content and generated output.

AI output may also be:

* incomplete;
* inaccurate;
* unsupported;
* misleading;
* overly broad;
* inappropriate for direct reliance.

AI remains assistive and non-authoritative.

Human users remain responsible for evaluating AI output before relying on it within the accepted workflow. For AI suggestions shown during Pending-resource moderation, D028's visible human-review rule applies.

AI output must not be treated as automatically correct merely because it was generated, stored, retained, or displayed.

### 9.2 Accepted AI-Output Types Only

The accepted v1.0 AI-output types are exactly:

* `summary`;
* `suggested_tags`;
* `suggested_metadata`;
* `duplicate_flag`;
* `moderation_hint`;
* `related_recommendation`.

No additional output type is introduced by this document.

BPC LearnShare v1.0 does not add AI output for:

* sentiment analysis;
* Student scoring;
* Teacher/Instructor scoring;
* engagement scoring;
* academic grading;
* plagiarism scoring;
* risk scoring;
* automatic privacy detection;
* automatic copyright determination;
* automatic ownership verification;
* exam answering;
* quiz answering;
* graded-assignment answering;
* answer-key generation.

AI must not answer exams, quizzes, graded assignments, or answer keys.

### 9.3 Every AI Output Belongs to One Resource

Every AI output belongs to exactly one resource and remains linked to that resource's `resource_id`.

AI output must not be:

* copied from one resource to another;
* reassigned to another resource;
* shared as one output record across multiple resources;
* inherited by a replacement resource.

A replacement resource never inherits the original resource's:

* summary;
* suggested tags;
* suggested metadata;
* duplicate flag;
* moderation hint;
* related recommendation;
* AI-output lifecycle state.

If a replacement resource independently satisfies the applicable AI eligibility rules, it may generate and store its own output.

That output remains separate from the output associated with the original resource.

### 9.4 Current-Value Storage, Not AI Event History

The accepted `ai_outputs` design stores at most one current row for each:

```text
(resource_id, output_type)
```

The first successful generation for a resource/output-type pair may create the current row.

A later successful regeneration updates that current row rather than creating an AI-output history record.

`ai_outputs` is therefore a **current-value table**, not an event ledger.

BPC LearnShare v1.0 does not introduce:

* `ai_output_history`;
* prompt history;
* response history;
* conversation history;
* model-run history;
* provider-call history;
* regeneration history;
* AI acceptance history.

An `ai_outputs` row must not be inserted before real generated content exists.

The system must not create:

* an empty placeholder row while waiting for a provider response;
* a successful-looking row for a failed request;
* blank active output;
* active output without a source-file reference.

If generation fails before any output row exists, no successful AI-output row is created.

If regeneration fails while a valid current row already exists, the failed attempt must not overwrite that valid current output or make the failed attempt appear successful.

Exact retry, timeout, provider-call, and update mechanics remain assigned to `AI_FEATURES.md` and `BUILD_PLAN.md`.

### 9.5 AI-Output Lifecycle State Is Separate From Resource Status

AI output has exactly three lifecycle states:

* `draft`;
* `retained`;
* `invalidated`.

No additional AI-output lifecycle state exists.

This document does not introduce:

* accepted;
* rejected;
* published;
* hidden;
* restricted;
* removed;
* deleted;
* expired;
* archived;
* pending review.

Resource status and AI-output lifecycle state are separate.

The source resource's status determines whether the resource and its derived output are currently eligible for a particular audience or public-facing surface.

The AI-output lifecycle state indicates whether the current output content is:

* still under review as `draft`;
* retained as currently usable output;
* no longer eligible for active or public use as `invalidated`.

Hidden and Restricted visibility are not stored as AI-output lifecycle states.

Their effect is derived from the current source-resource status at read time.

Lifecycle state alone never grants visibility.

Every AI-output read must also evaluate:

* current source-resource status;
* current access rules;
* current output lifecycle state;
* current source-file association;
* whether the requested surface is appropriate for the output type.

### 9.6 Pending Resources Use Draft Output

AI output generated for a Pending resource has lifecycle state:

```text
draft
```

Draft output is visible only to:

* the uploader of that specific resource;
* Moderator;
* Admin.

General authenticated users must not access draft AI output.

Draft output must not appear in:

* normal browse/search;
* public-facing summaries;
* public recommendation surfaces;
* content-based search;
* optional Phase 5 retrieval;
* other general authenticated-user AI reads.

For their own Pending resource, the uploader may:

* review;
* edit;
* use;
* discard

the applicable AI suggestions.

Moderator/Admin may also review, edit, use, or discard applicable AI suggestions during moderation.

Draft AI output does not independently:

* change final metadata;
* change resource status;
* approve the resource;
* reject the resource;
* publish the resource;
* validate the resource;
* Hide the resource;
* Restrict the resource;
* Remove the resource;
* delete the resource.

Any accepted use of draft output remains subject to human action and the D028 review rule.

### 9.7 Needs Correction and Source-File Changes

If a Pending resource becomes Needs Correction, existing draft AI output may remain available to the uploader, Moderator, and Admin when the underlying file has not changed and the output remains current.

If the uploaded file is replaced during correction:

* output tied to the previous file must not be treated as current;
* stale draft or retained output must be discarded or invalidated according to the accepted lifecycle rules;
* the changed file may receive new AI output only if it independently satisfies the current AI eligibility requirements.

`source_file_reference` is the accepted source-file association.

It stores a snapshot of the resource's `stored_filename` at the time the AI output was generated.

The application may compare that stored reference with the resource's current `stored_filename` to determine whether the output still corresponds to the current uploaded file.

No accepted source defines:

* a file-version table;
* an AI-output version table;
* a content hash;
* a checksum field.

This document does not invent any of those mechanisms.

Exact file-change detection, regeneration, and update mechanics remain assigned to `AI_FEATURES.md` and `BUILD_PLAN.md`.

The acknowledgment-reset implementation direction already carried forward from Section 8 remains assigned to those later documents and is not reopened here.

### 9.8 Approval and D028 Human Review

When a resource becomes Approved after Moderator/Admin had a visible opportunity to review, edit, or discard the displayed AI suggestions, undiscarded output may be treated as accepted under D028.

No separate field-by-field acceptance checkbox is required.

The accepted schema does not add:

* `is_accepted`;
* `accepted_by`;
* `accepted_at`;
* an AI-acceptance table;
* an AI-acceptance history.

Acceptance is not stored as a separate AI-output status.

Approval after visible human review may allow eligible, still-valid output to transition from:

```text
draft
```

to:

```text
retained
```

This does not mean:

* AI made the approval decision;
* AI output is guaranteed correct;
* every output type becomes public;
* every output must be displayed;
* the output becomes independent of its source resource;
* the output may ignore current source-file validity;
* the output may ignore current access rules.

Detailed review-interface and presentation mechanics remain assigned to `AI_FEATURES.md`.

### 9.9 Approved Resources May Use Valid Retained Output

AI output may appear on an Approved-resource surface only when all applicable conditions are satisfied.

A public-facing or general authenticated-user AI read must verify that:

1. the source resource is currently Approved;
2. the output lifecycle state is currently `retained`;
3. the output is not stale;
4. `source_file_reference` still corresponds to the resource's current stored file;
5. the requested surface is appropriate for that output type;
6. current access rules permit the requester to use that surface.

Checking only that the output is “not invalidated” is insufficient because `draft` output must also remain non-public.

Not every retained output type is automatically public-facing.

For example:

* retained summaries may be eligible for an Approved-resource display surface;
* retained related recommendations may be eligible for recommendation surfaces;
* retained metadata-related output may support accepted resource organization;
* moderation hints and duplicate flags may remain staff-oriented where appropriate.

Exact surface-by-output-type behavior belongs to `AI_FEATURES.md`.

A retained row does not independently make its source resource or output public.

### 9.10 Rejected, Withdrawn, and Removed Resources

When a resource becomes Rejected, Withdrawn, or Removed, the accepted AI-output lifecycle action must be applied.

Output tied to those resources must be:

* physically deleted; or
* retained only as `invalidated`, according to the accepted lifecycle handling.

It must not remain available in:

* normal browse/search;
* resource summaries;
* recommendations;
* content-based search;
* public report views;
* public duplicate/similarity surfaces;
* optional Phase 5 retrieval;
* other public-facing or ordinary user-facing AI reads.

This document does not introduce a separate AI-output `deleted` lifecycle state.

If an AI-output row is physically deleted, no output row remains.

If a row remains as `invalidated`, all public-facing and ordinary user-facing AI queries must exclude it.

For a Removed resource:

* AI output must not remain publicly visible;
* AI output must not remain uploader-visible;
* AI output must not remain searchable or usable;
* only minimal accountability information may remain where required.

D040 does not add an AI-output table or column.

D040 uses the accepted existing AI-output lifecycle and deletion options together with the other confirmed Removed-resource operations.

Detailed resource-row, file, relationship, and historical retention behavior remains assigned to Section 11.

### 9.11 Hidden and Restricted Resources

When an Approved resource becomes Hidden or Restricted, existing AI output may remain stored.

However, it must be excluded from public-facing and general authenticated-user surfaces, including:

* public summaries;
* recommendations;
* content-based search;
* normal browse/search AI displays;
* public duplicate/similarity surfaces;
* optional Phase 5 retrieval.

AI output may remain visible only to:

* the uploader;
* Moderator;
* Admin;

according to the current source-resource visibility rules.

Hidden and Restricted do not create new AI-output lifecycle states.

Visibility remains derived from the source resource's current status.

If an allowed workflow later returns the source resource to Approved, previously retained output may become eligible for its accepted surface again only when:

* the output is still `retained`;
* the output has not been invalidated;
* `source_file_reference` still corresponds to the current stored file;
* the requested surface is appropriate for the output type;
* current access rules permit the read.

Returning the source resource to Approved does not bypass these checks.

A valid retained output does not require regeneration merely because the source resource temporarily became Hidden or Restricted.

Stale or invalidated output must not be exposed simply because the resource returned to Approved.

### 9.12 Replaced Resources

When an original resource becomes Replaced, AI output tied to the original resource must not remain:

* publicly visible;
* searchable;
* recommendable;
* usable in content-based search;
* available to optional Phase 5 retrieval;
* available through other public-facing AI reads.

Where the accepted lifecycle permits the original output row to remain, it may remain available only to:

* the original uploader;
* Moderator;
* Admin;

for permitted history or reference purposes.

The original AI output remains tied to the original resource's `resource_id`.

It must never be:

* reassigned to the replacement resource;
* copied into the replacement resource;
* treated as output generated from the replacement file;
* inherited by the replacement.

The replacement resource may generate or store its own output only if it independently becomes eligible.

AI-output noninheritance follows the accepted one-resource rule under D018 and the current-value storage model under D035.

### 9.13 Late Responses and Concurrent Changes

An AI request may still be processing when:

* the resource status changes;
* the uploaded file changes;
* the resource becomes ineligible;
* another valid output for the same resource/output type is stored.

Before sending content, the application must capture the source-resource and source-file information required to associate the request with the correct current file.

Before storing a returned response, the application must recheck:

* current resource status;
* current AI eligibility;
* current `stored_filename`;
* the request's captured source-file reference;
* whether the response still belongs to the current file state.

If the resource is no longer eligible or the request no longer corresponds to the current file:

* the response must not be inserted as valid active output;
* the response must not update the current row as `draft` or `retained`;
* the response must not overwrite a valid newer current output;
* the stale response must be discarded.

If an existing current output separately became stale because the source file changed, that existing output must follow the accepted invalidation or replacement handling.

A stale late response must not be preserved merely to create a processing history.

BPC LearnShare v1.0 does not introduce:

* queue history;
* response history;
* provider-call history;
* model-run history;
* discarded-response storage.

Exact concurrency, request-ordering, retry, and provider-response mechanics remain assigned to `AI_FEATURES.md` and `BUILD_PLAN.md`.

### 9.14 Invalidated Content Must Remain Excluded

When an output is `invalidated`, its current stored content is no longer eligible for active or public use.

Invalidated output must be excluded from every public-facing AI read regardless of the source resource's current status.

This includes:

* summary display;
* recommendation surfaces;
* content-based search;
* duplicate/similarity surfaces outside permitted staff review;
* optional Phase 5 retrieval;
* any other public-facing AI use.

A later return of the source resource to Approved does not automatically make invalidated content usable again.

The accepted current-value row is not a permanent history record.

If the resource later becomes eligible and new valid output is successfully generated for the same output type:

* the current row may be updated under D035;
* the old invalidated content is replaced rather than revived;
* the new content must use the current source-file reference;
* the lifecycle state must be set appropriately for the new output;
* no history row is created.

The previous invalidated content does not become visible again merely because the current row later stores new valid output.

### 9.15 Privacy Minimization for AI Output and Accountability Records

Full AI output must not be duplicated into:

* `resource_action_history`;
* `audit_log`;
* notifications;
* ordinary application logs.

Where an already-required moderation, resource-action, or administrative record needs limited context related to AI-assisted behavior, it may use only the minimum short, safe summary needed for the accepted accountability purpose.

This does not create:

* a new AI audit action;
* a new AI-discard action type;
* an AI processing-history ledger;
* an AI output-copying requirement.

Accountability records must not reproduce:

* full AI summaries;
* full suggested metadata;
* full moderation hints;
* full duplicate-analysis content;
* full recommendation content;
* full AI request payloads;
* full AI response payloads.

Detailed ledger content and safe-summary boundaries belong to Section 10.

AI output must not be retained merely because it could become useful for an unconfirmed future feature.

Its continued storage and use must remain tied to:

* the accepted AI-output lifecycle;
* the source resource's current status;
* current source-file association;
* current eligibility;
* the accepted purpose of the output type.

The system should continue using valid current output rather than generating unnecessary duplicates for an unchanged eligible file.

AI-output privacy depends on every AI-reading and AI-displaying code path applying the current resource-status, lifecycle-state, source-file, and surface-eligibility checks consistently.

This application-level filtering dependency is a known v1.0 limitation and must be carried to Section 13 and later verification planning.

---

*End of Section 9.*

## 10. Audit Log and Resource Action History Privacy

### 10.1 The Two-Ledger Model as an Accountability Control

BPC LearnShare v1.0 uses two separate, purpose-specific accountability ledgers:

* **`resource_action_history`** records accepted resource-specific moderation, ownership, and status actions;
* **`audit_log`** records accepted administrative and system-configuration actions.

The two ledgers are not interchangeable and must not be merged into one general activity structure.

Their privacy-supporting purpose is to preserve enough information to understand:

* what accepted action occurred;
* which human actor performed it, or whether an accepted resource action was system-triggered;
* when it occurred;
* the relevant status or target context;
* the short reason or explanation required by the accepted workflow.

The ledgers are accountability records, not general surveillance, user profiling, or activity analytics.

They do not record every:

* resource view;
* page access;
* search;
* file inspection;
* file download;
* dashboard visit;
* read-only Moderator/Admin action.

This preserves the limitation already identified in Section 7.12: read-only access by an authorized Moderator or Admin is not comprehensively logged, so passive misuse of legitimately granted access may not be fully detectable or reconstructable from these ledgers.

This document does not introduce a read-access log, staff-view history, general activity-events table, or expanded monitoring requirement to close that limitation.

### 10.2 `resource_action_history`

`resource_action_history` is the append-only resource-specific ledger.

Its accepted action types are exactly:

* `approve`;
* `reject`;
* `request_correction`;
* `resubmit`;
* `hide`;
* `restrict`;
* `remove`;
* `withdraw`;
* `return_to_approved`;
* `replaced`.

No additional resource action type is introduced.

In particular, this document does not add:

* `view`;
* `download`;
* `open`;
* `inspect`;
* `ai_generated`;
* `ai_discarded`;
* `privacy_reviewed`;
* `consent_checked`;
* a generic correction action.

Each row stores:

* the affected resource through `resource_id`;
* the acting account through `actor_account_id`, or `NULL` only for an accepted system-triggered action;
* the accepted `action_type`;
* `status_before`;
* `status_after`;
* a short `note`;
* an optional `related_report_id`, where the action is connected to an existing report;
* `created_at`.

`actor_account_id` may be `NULL` only where the accepted workflow treats the action as system-triggered, such as the original resource automatically becoming Replaced when its linked replacement is Approved.

The note requirement depends on the action:

* a nonblank note is required for:

  * `reject`;
  * `request_correction`;
  * `hide`;
  * `restrict`;
  * `remove`;
* the note may remain optional for other accepted action types unless the applicable workflow requires additional context.

The note must remain a short explanation of the accepted action, not a full record of the review process or a duplicate copy of resource, report, file, or AI content.

`related_report_id` should be used where an accepted report-handling action needs to preserve the connection to the existing report rather than copying the report into the ledger.

This table remains a focused resource-action history, not a complete event stream of everything a user or staff member does.

### 10.3 `audit_log`

`audit_log` is the separate append-only ledger for accepted administrative and system-configuration actions.

The accepted action types are exactly:

* `account_created`;
* `account_disabled`;
* `account_reenabled`;
* `role_changed`;
* `password_reset`;
* `setting_changed`;
* `taxonomy_created`;
* `taxonomy_updated`;
* `taxonomy_deactivated`;
* `taxonomy_reactivated`.

The accepted target types are exactly:

* `account`;
* `system_setting`;
* `course`;
* `subject`;
* `year_level`;
* `resource_type`;
* `tag`.

No other action type or target type is introduced.

In particular, this document does not add:

* `resource`;
* `report`;
* `ai_output`;
* `provider`;
* `consent`;
* `privacy_notice`;
* `access_event`;
* `login_event`;
* `file_view_event`;
* a generic `taxonomy` target.

Each row stores:

* `actor_account_id`;
* the accepted `action_type`;
* the accepted `target_type`;
* `target_id`;
* an optional short `note`;
* `created_at`.

For normal in-application audit actions, `actor_account_id` is required and identifies the authenticated Admin account that performed the action.

The setup-only creation of the first Admin remains outside the normal in-application account-provisioning workflow. Its exact bootstrap procedure remains assigned to `BUILD_PLAN.md` and is not redefined by this section.

Because `target_id` may refer to different tables depending on `target_type`, it has no normal foreign key to one target table.

The application must therefore validate every audit target before writing or displaying it.

It must verify that:

1. `action_type` belongs to the accepted closed set;
2. `target_type` belongs to the accepted closed set;
3. the action and target type form an accepted pairing;
4. `target_id` identifies an existing row in the specific table named by `target_type`;
5. the current viewer is authorized to access the audit entry and any resolved target details.

The application must not resolve a target identifier without also using its target type.

For example, an identifier value of `5` may refer to different rows in `courses`, `subjects`, `year_levels`, `resource_types`, or `tags`. The target type is required to identify the correct table.

Application validation remains authoritative even where the database CHECK constraint also validates accepted action/target combinations.

### 10.4 Report Information Remains in the `reports` Structure

Report investigation and resolution information remains in the accepted `reports` structure.

That structure stores the applicable:

* reported resource;
* reporter;
* reason;
* optional comment;
* report status;
* escalation information;
* resolver;
* resolution note;
* timestamps.

Neither `resource_action_history` nor `audit_log` should duplicate an entire report record.

Where a resource action results from report handling, the existing `related_report_id` may preserve the connection between the action-history row and the report.

The ledger note should still contain only the short action context required by the resource workflow.

The system must not copy full report comments, escalation history, or resolution content into a ledger merely for convenience.

This document does not introduce:

* a report-history table;
* a privacy-case ledger;
* a separate complaint ledger;
* a report-content archive.

### 10.5 Ledger Notes Must Use Safe, Necessary Summaries

Notes and short summaries in both ledgers must contain only the minimum context needed to explain the accepted action.

Neither ledger may store:

* plaintext passwords;
* password hashes;
* temporary passwords;
* session identifiers;
* CSRF tokens;
* database credentials;
* AI API keys;
* full uploaded-file content;
* full AI-generated output;
* full AI request payloads;
* full AI response payloads;
* unnecessary provider error details;
* copied resource descriptions;
* copied original filenames merely for convenience;
* full report comments where a short reason or report reference is sufficient;
* unrelated personal information.

A safe-summary rule does not make free-text notes automatically safe.

A Moderator or Admin may still include a person's name or another identifying detail unnecessarily when writing a note.

Staff-facing forms, guidance, and implementation documentation should therefore encourage notes that are:

* short;
* factual;
* relevant to the action;
* limited to necessary context.

Examples of appropriate context include a concise correction reason, rejection reason, Hide/Restrict justification, removal reason, or brief administrative explanation.

Ledger notes should not become unrestricted narratives or duplicate storage for content already held elsewhere.

BPC LearnShare v1.0 does not introduce:

* automated personal-information detection for notes;
* automatic note redaction;
* AI-based note review;
* an AI privacy checker for ledger content.

### 10.6 Accountability Content Must Not Be Duplicated Into Notifications

Full ledger notes must not be copied into notification messages.

Notifications should continue using generic action or status wording.

A notification may indicate, for example, that:

* a resource received a moderation decision;
* correction information is available;
* an item requires staff attention.

The notification should not reproduce:

* a full resource-action note;
* a full audit note;
* a full report comment;
* a full resolution note;
* unnecessary resource or file information.

A notification may point to an accepted resource, report, action-history, or queue destination.

The destination page — not the notification message — is responsible for resolving current permitted information.

Opening the destination must still require:

* current authentication;
* current Active account status;
* current role authorization;
* current target existence;
* current object-level access;
* current resource status and file availability where applicable.

A notification remains a pointer only and never becomes a permission grant.

### 10.7 Append-Only at the Application Level

Both ledgers are append-only at the normal application level.

Normal application code must not update or delete an existing row in:

* `resource_action_history`;
* `audit_log`.

The absence of an `updated_at` field in these ledger structures is consistent with preserving entries as historical records rather than maintaining one editable latest note.

If an earlier entry is incomplete or mistaken, the application must not silently edit or remove it.

Where a later accepted workflow action legitimately clarifies or supersedes earlier context, that later action receives its own new ledger row using an already-accepted action type.

The system must not invent a generic correction, amendment, or annotation action merely to change ledger history.

Where no accepted later action exists, the limitation should be handled through administrative procedure rather than by fabricating a new action type or rewriting the original record.

Append-only behavior is an application rule.

It does not mean that the tables are:

* cryptographically immutable;
* tamper-proof;
* blockchain-backed;
* write-once infrastructure;
* independently certified.

A person with authorized direct database access outside normal application controls could still alter records.

That limitation is accepted for the local/LAN academic MVP and is carried to Section 13.

### 10.8 Required Ledger Writes Must Commit With the Actions They Describe

A required accountability entry must be part of the same logical database transaction as the action it records.

The system must not allow:

* an accepted action to commit while its required ledger row silently fails;
* a ledger row to commit for an action that did not complete;
* a partial multi-record workflow to leave misleading accountability history.

The accepted atomic database groups include:

* a moderation decision together with:

  * the resource-status change;
  * the required `resource_action_history` row;

* replacement approval together with:

  * the replacement becoming Approved;
  * the original becoming Replaced;
  * deletion of the related open-replacement tracking row;
  * the required action-history rows for the affected resources;

* an accepted account-management action together with:

  * the account creation, status change, role change, or password-hash update;
  * the required `audit_log` row;

* an accepted taxonomy action together with:

  * the taxonomy create, update, deactivate, or reactivate operation;
  * the required `audit_log` row;

* a system-setting change together with:

  * the setting update;
  * the required `audit_log` row;

* the D040 removal database operation together with:

  * the resource becoming Removed;
  * the required descriptive-field placeholders;
  * deletion of associated `resource_tags`;
  * the required `file_availability` change;
  * the accepted AI-output lifecycle update;
  * the required removal action-history row;
  * the other required database-side lifecycle changes.

Physical file deletion or invalidation occurs outside the database transaction and must be coordinated carefully with the committed database state.

A physical cleanup failure must not leave the file servable.

The database-side lifecycle must remain fail-closed so current `file_availability`, status, and permission checks continue blocking access even when physical cleanup requires later retry or maintenance.

Exact transaction syntax, locking, ordering, rollback behavior, and database/filesystem coordination remain assigned to `BUILD_PLAN.md`.

### 10.9 Access to Accountability Records Remains Role-Limited

A Moderator may access only the moderation- and report-related history needed for the Moderator role.

Moderator access must not expand into full administrative audit visibility.

A Moderator must not gain access to Admin-only audit information concerning:

* account creation;
* account disabling or re-enabling;
* role changes;
* password resets;
* taxonomy management;
* system-setting changes.

Full `audit_log` access remains Admin-only.

Before showing a ledger entry, the application must verify:

* that the current account exists;
* that the account is Active;
* that the current role permits access;
* that the requested ledger row exists;
* that any related resource, report, or target information may be shown to the current role.

For `audit_log`, the application must also verify the accepted polymorphic target rules before resolving target details.

An Admin-only audit target must not be exposed indirectly through:

* a manipulated direct request;
* a notification target;
* a guessed target identifier;
* a Moderator-facing resource or report page.

This document does not introduce:

* public ledger access;
* uploader-facing full action history;
* reporter-facing ledger access;
* a new audit viewer role;
* a privacy officer access tier.

Detailed endpoint authorization remains defined in `SECURITY_NOTES.md`.

### 10.10 Historical Records Remain Through Resource-Status Changes

Resource action history remains associated with the resource after later status changes, including when a resource becomes:

* Hidden;
* Restricted;
* Removed;
* Replaced.

D040 minimization does not delete required historical relationships.

For a Removed resource, accepted retained relationships may include:

* reports;
* `resource_action_history`;
* bookmarks;
* Helpful marks;
* replacement relationships;
* other required historical references.

These relationships remain attached to the same resource identifier.

They do not transfer to a replacement resource.

Bookmarks and Helpful marks are retained historical interaction relationships, not moderation ledgers, but they may still reference the Removed resource row and are therefore part of the referential-integrity reason that the row remains.

D040 still requires:

* deletion of associated `resource_tags`;
* file content to become deleted or invalidated and non-servable;
* AI output to become deleted or invalidated and unusable;
* the resource record to be minimized but not anonymized.

Historical retention does not make the Removed resource visible to the uploader or general authenticated users.

Current access remains:

* no normal user access;
* no uploader access;
* Admin-only access to the minimized accountability/reference record.

This section does not define a general retention duration or repeat the full status-by-status retention model.

Those matters belong to Section 11.

### 10.11 Known v1.0 Accountability Limitations

The following remain accepted v1.0 limitations:

* no comprehensive read-access log;
* no record of every page view or file inspection;
* no general activity-analytics system;
* no cryptographic tamper evidence for ledger rows;
* no cryptographic audit chaining;
* no blockchain-backed audit structure;
* no write-once audit infrastructure;
* no external log server;
* no SIEM;
* no centralized monitoring;
* no enterprise audit certification;
* no technical guarantee that a person with direct database access cannot alter ledger records outside normal application controls.

These limitations do not remove the value of the accepted two-ledger model.

The ledgers still provide practical application-level accountability for the specific state-changing resource and administrative actions they are designed to record.

The limitations must not be converted into new v1.0 requirements for enterprise audit infrastructure.

They should be consolidated in Section 13 and carried into later testing where applicable.

---

*End of Section 10.*

## 11. Retention, Removal, Withdrawal, and Historical Records

### 11.1 Scope of This Section

This section consolidates the accepted application-level lifecycle and retention behavior established across Sections 3–10.

It explains what happens to:

* account-linked records;
* taxonomy references;
* resource metadata;
* uploaded files;
* AI output;
* reports;
* bookmarks;
* Helpful marks;
* notifications;
* temporary tracking records;
* accountability history

as resources move through the nine accepted statuses:

* Pending;
* Needs Correction;
* Approved;
* Rejected;
* Withdrawn;
* Hidden;
* Restricted;
* Removed;
* Replaced.

This section documents accepted **application-level technical behavior**.

It is not Bulacan Polytechnic College's complete institutional retention policy and does not present itself as one.

No accepted source defines a fixed retention period in:

* days;
* months;
* years;
* semesters;
* academic terms.

This section does not invent one.

The absence of a fixed period also does not establish indefinite retention as institutional policy.

Formal retention periods, disposal schedules, applicable institutional or legal requirements, and related data-subject procedures remain responsibilities of Bulacan Polytechnic College or the authorized deploying office for any real deployment.

This section does not introduce:

* a retention scheduler;
* automatic expiry;
* a purge engine;
* a records-management module;
* a data-subject request portal;
* automatic anonymization;
* an archive table;
* an archival system.

### 11.2 Accounts and Account-Linked Records

The accepted v1.0 account lifecycle supports:

* account creation;
* account disabling;
* account re-enabling;
* role changes;
* Admin-assisted password reset.

There is no accepted self-service account-deletion workflow.

Disabling an account:

* blocks future login;
* causes an already-open session to fail on its next protected request through current account-status checks;
* does not delete the account row;
* does not automatically change the status of resources previously uploaded by that account.

Account references may remain because accepted records may identify an account as:

* resource uploader;
* report reporter;
* resource-action actor;
* audit actor;
* report escalator;
* report resolver;
* notification recipient;
* bookmark owner;
* Helpful-mark owner;
* Admin account that last updated a system setting.

A resource may also store AI-notice acknowledgment state while remaining linked to its uploader through `uploader_id`.

The acknowledgment fields do not create a separate account relationship; their attribution follows the resource's existing uploader relationship.

This document does not claim that account records must be retained indefinitely.

It states only that the accepted v1.0 schema and workflows currently define:

* no account-deletion lifecycle;
* no fixed account-retention period;
* no account-anonymization process;
* no automatic account expiry;
* no account purge.

This document does not introduce:

* self-service account deletion;
* automatic account deletion;
* account anonymization;
* an email-based deletion-request workflow.

### 11.3 Taxonomy Records

Courses/programs, subjects, year levels, resource types, and controlled tags follow the accepted v1.0 lifecycle:

* add;
* edit;
* deactivate;
* reactivate.

The accepted application workflow does not hard-delete taxonomy values.

Deactivating a taxonomy value:

* removes it from the active selectable options for new uploads;
* does not erase the taxonomy row;
* does not break existing resources that already reference it.

Existing resources retain valid historical references to deactivated values.

This document does not introduce:

* taxonomy hard deletion;
* a taxonomy archive table;
* automatic taxonomy expiry;
* an automatic taxonomy-purge process.

### 11.4 Pending

While a resource is Pending:

* the resource row remains;
* its descriptive and taxonomy metadata remain;
* its protected file reference remains;
* its uploaded file content remains subject to the accepted protected-storage and access rules;
* visibility remains limited to the uploader, Moderator, and Admin.

If AI is enabled and the resource satisfies the accepted eligibility requirements, the Pending resource may have `draft` AI output under Sections 8–9.

No accepted source defines:

* automatic Pending expiry;
* automatic Pending cleanup;
* a Pending retention duration.

A Pending resource remains in that status until an accepted workflow action changes it, such as:

* Approve;
* Reject;
* Request Correction;
* uploader withdrawal.

Uploader editing while the resource remains Pending does not create a separate resource version or retention record.

### 11.5 Needs Correction

Needs Correction continues to use the same resource row.

The uploader may:

* revise metadata;
* replace the uploaded file;
* resubmit the same resource

according to the accepted correction workflow.

No separate resource version or correction-history table is created.

Existing draft AI output may remain available only when:

* the source file has not changed;
* the output remains current;
* the applicable access and lifecycle rules still permit it.

If the file changes:

* output tied to the previous file must not be treated as current;
* stale output must be discarded or invalidated according to Section 9;
* the changed file follows current AI eligibility rules.

The privacy-preferred acknowledgment reset and re-acknowledgment direction identified in Section 8 remains a carry-forward implementation item for `AI_FEATURES.md` and `BUILD_PLAN.md`.

It is not converted here into:

* a new schema field;
* a notice-version structure;
* an acknowledgment-history table;
* a new consent mechanism.

This document does not introduce:

* file-version history;
* AI-output version history;
* a separate correction-resource table.

### 11.6 Approved

Approved status governs whether a resource is eligible for normal authenticated browse/search and ordinary Approved-resource access.

An Approved resource record and its permitted metadata may remain available to authenticated Active users while:

* the current resource status is Approved;
* the requester satisfies the current access rules.

Physical file serving is a separate decision.

The uploaded file may be served only when:

1. current access and Approved-resource status rules permit the request; and
2. `file_availability = 'available'`.

An Approved resource whose `file_availability` is `deleted` or `invalidated` must not have its file served, even though the resource row itself remains Approved unless another accepted workflow changes its status.

Approval is:

* a current status;
* not permanent publication;
* not a guarantee of permanent retention;
* not a guarantee that the physical file will always remain available.

An Approved resource may later become:

* Hidden;
* Restricted;
* Removed;
* Replaced.

While Approved, accepted related data may continue according to current workflow and lifecycle rules, including:

* metadata;
* taxonomy relationships;
* file references;
* bookmarks;
* Helpful marks;
* reports;
* aggregate view/download counts;
* notifications;
* valid eligible AI output.

This document does not introduce:

* automatic Approved-resource archival;
* semester-based expiry;
* term-based expiry;
* automatic deletion after approval.

### 11.7 Rejected

A Rejected resource remains:

* outside normal browse/search;
* non-public;
* visible only to the uploader, Moderator, and Admin.

The uploader may:

* edit;
* resubmit;
* withdraw

the Rejected resource according to the accepted workflow.

The same resource row remains.

Rejected status does not automatically hard-delete:

* the resource row;
* resource metadata;
* the uploaded file.

Current resource status and file-serving rules continue to govern access.

Draft AI output tied to a Rejected resource must be:

* deleted; or
* invalidated

according to the accepted lifecycle.

Rejected-resource AI output must not remain available in user-facing AI reads.

This document does not introduce:

* automatic Rejected-resource cleanup;
* a Rejected archive;
* a Rejected retention period.

### 11.8 Withdrawn

Withdrawn may occur only when the original uploader withdraws their own resource while it is:

* Pending;
* Needs Correction;
* Rejected.

Withdrawn does not apply to:

* Approved;
* Hidden;
* Restricted;
* Removed;
* Replaced.

The Withdrawn resource row remains.

Unlike Removed, Withdrawn retains its original:

* title;
* description;
* topic;
* original filename;
* controlled-tag relationships.

The file content must become deleted or invalidated according to the accepted lifecycle and must not remain servable.

Draft AI output must also become:

* deleted; or
* invalidated.

Withdrawn-resource AI output must not remain in user-facing AI reads.

The uploader, Moderator, and Admin retain the accepted history/reference visibility for the Withdrawn resource record.

D040 does not apply to Withdrawn resources.

Therefore, Withdrawn does **not** receive:

* `[Removed resource]` title replacement;
* `[Removed resource]` description replacement;
* `[Removed]` topic replacement;
* `[removed]` original-filename replacement;
* automatic deletion of associated `resource_tags`.

A Withdrawn resource remains distinct from Removed.

It is an uploader-initiated non-public lifecycle outcome, not the Admin-only Removed lifecycle.

The Withdrawn record is not anonymized and is not D040-minimized.

This document does not introduce:

* a Withdrawn archive;
* automatic Withdrawn expiry;
* a Withdrawn purge process.

### 11.9 Hidden

Hidden remains a temporary investigative hold.

When a resource becomes Hidden:

* the resource status changes to Hidden;
* the resource leaves normal browse/search;
* general authenticated users lose normal access;
* the uploader, Moderator, and Admin retain the accepted restricted visibility;
* the existing descriptive metadata and controlled-tag relationships are not D040-sanitized merely because the resource became Hidden.

The status change and related accountability action may update timestamps and history as required by the accepted workflow.

Existing AI output may remain stored, but it must be excluded from:

* public-facing summaries;
* recommendations;
* content-based search;
* ordinary public-facing AI reads;
* optional Phase 5 retrieval.

A Hidden resource may later:

* return to Approved;
* become Restricted;
* become Removed through Admin authority.

This document does not introduce:

* automatic Hidden-resource deletion;
* Hidden-resource anonymization;
* a fixed Hidden expiry period.

### 11.10 Restricted

Restricted remains a longer-term limited-access outcome after review and remains distinct from Hidden's temporary investigative purpose.

When a resource becomes Restricted:

* the resource status changes to Restricted;
* the resource remains outside normal browse/search;
* general authenticated users do not receive ordinary access;
* the uploader, Moderator, and Admin retain the accepted restricted visibility;
* descriptive metadata and controlled-tag relationships are not D040-sanitized merely because the resource became Restricted.

The status change and related accountability action may update timestamps and history as required by the accepted workflow.

Existing AI output may remain stored but must remain excluded from public-facing use.

Restricted may later return to Approved through the accepted logged workflow.

This document does not introduce:

* a fixed Restricted retention period;
* automatic Restricted expiry;
* automatic deletion;
* automatic anonymization.

### 11.11 Removed and D040

Remove is an Admin-only action.

Removed is a terminal resource status with the strictest resource-access boundary.

When a resource becomes Removed, D040 applies the following accepted removal-time lifecycle behavior.

The system overwrites:

* `title` with `[Removed resource]`;
* `description` with `[Removed resource]`;
* `topic` with `[Removed]`;
* `original_filename` with `[removed]`.

The system deletes:

* all associated `resource_tags` rows.

The resource row itself remains.

For timestamps:

* `created_at` remains unchanged;
* `updated_at` changes when the resource row is updated.

For the uploaded file:

* file content is deleted or invalidated according to the accepted lifecycle;
* `file_availability` becomes `deleted` or `invalidated` according to the actual file-handling result;
* the file must not remain servable.

For AI output:

* related output is deleted or invalidated;
* it must not remain searchable;
* it must not remain visible;
* it must not remain usable.

For accountability:

* the Admin actor remains recorded in `resource_action_history`;
* the required removal reason remains recorded;
* previous and new resource status remain recorded;
* the removal timestamp remains represented by the history-row timestamp.

Required retained data may include:

* resource ID;
* uploader account reference;
* required course/program reference;
* required subject reference;
* required year-level reference;
* required resource-type reference;
* randomized stored filename/reference;
* file type;
* file size;
* file-availability state;
* replacement linkage where present;
* AI-notice acknowledgment fields;
* aggregate view count;
* aggregate download count;
* creation timestamp;
* update timestamp;
* reports;
* resource action history;
* bookmarks;
* Helpful marks;
* replacement relationships;
* other accepted historical references.

The retained Removed-resource record is:

* minimized within the accepted schema;
* not anonymized.

Retained account and taxonomy references may still preserve attribution and broad academic context.

Access therefore remains:

* unavailable to general authenticated users;
* unavailable to the original uploader;
* limited to Admin for accountability/reference purposes.

D040:

* adds no table;
* adds no column;
* adds no role;
* adds no status;
* creates no anonymization feature;
* creates no complete erasure mechanism;
* creates no purge engine;
* creates no retention scheduler;
* creates no archival module.

D040 is a fixed removal-time lifecycle rule implemented through the accepted resource row, tag relationship, file-availability, AI-output, and action-history structures.

Physical file cleanup cannot be rolled back automatically by the database transaction.

The implementation must coordinate database state and filesystem cleanup so that:

* database-side removal changes remain internally consistent;
* a cleanup failure never restores file access;
* current Removed status and non-available `file_availability` continue blocking file serving;
* failed cleanup can be handled safely later without exposing the file.

Exact transaction ordering, filesystem ordering, retry handling, and cleanup mechanics remain assigned to `BUILD_PLAN.md`.

### 11.12 Replaced

An original Approved resource becomes Replaced when its linked replacement resource becomes Approved.

The original resource row remains.

The approved replacement row retains:

```text
replaces_resource_id = <original resource ID>
```

This preserves the replacement lineage from the replacement back to the original.

The original Replaced resource:

* does not appear in normal browse/search;
* remains visible to the original uploader, Moderator, and Admin for accepted history/reference purposes;
* must not have its file served to general authenticated users;
* remains subject to current resource-status and `file_availability` checks.

Replaced is distinct from Removed.

No accepted source requires the original physical file to be hard-deleted merely because the original resource became Replaced.

This document therefore does not invent automatic physical deletion for Replaced resources.

Original-resource AI output must not remain:

* publicly visible;
* searchable;
* recommendable;
* usable in content-based search;
* available to optional Phase 5 retrieval.

Where the accepted lifecycle permits original output to remain for restricted history/reference use, it remains tied to the original resource only.

The replacement does not inherit the original resource's:

* bookmarks;
* Helpful marks;
* reports;
* view count;
* download count;
* AI output.

A report filed against the original resource remains tied to that original resource.

It is not reassigned to the replacement.

The replacement remains a separate resource with its own:

* resource ID;
* uploader reference;
* status;
* metadata;
* uploaded file;
* file lifecycle;
* interactions;
* reports;
* AI output.

This document does not introduce automatic data transfer between original and replacement records beyond the accepted `replaces_resource_id` linkage.

### 11.13 Open Tracking Records

`open_replacement_tracking` and `open_report_tracking` are temporary helper/tracking tables.

They are not permanent event histories.

`open_replacement_tracking` exists only while the linked replacement is:

* Pending;
* Needs Correction.

Its row is deleted when that replacement becomes:

* Approved;
* Rejected;
* Withdrawn.

Deleting the helper row:

* does not delete the original resource;
* does not delete the replacement resource;
* does not delete replacement lineage;
* only clears the temporary one-open-replacement enforcement record.

`open_report_tracking` remains only while the report is:

* Open;
* Escalated.

Its row is deleted when the report becomes:

* Dismissed;
* Actioned.

Deleting the helper row:

* does not delete the report;
* does not delete the reported resource;
* does not erase report history;
* only clears the temporary one-unresolved-report enforcement record.

This document does not convert either helper table into:

* a replacement-history table;
* a report-history table;
* an event ledger.

### 11.14 Reports and Report History

Reports remain tied to the resource originally reported.

A report does not transfer to a replacement resource.

If the original resource later becomes Replaced:

* the existing report remains attached to the original resource;
* the report may continue through the accepted report workflow;
* Moderator/Admin may resolve it using the accepted status and resolution fields.

Dismissed and Actioned reports retain the accepted:

* resolving account reference;
* resolution note;
* resolution timestamp.

Escalated reports remain unresolved until an accepted later Admin action resolves them.

This document does not introduce:

* a report-history table;
* a privacy-case archive;
* automatic report expiry;
* automatic report purge;
* report transfer to replacement resources.

### 11.15 Bookmarks, Helpful Marks, and Aggregate Activity

Bookmarks and Helpful marks remain separate account-resource relationships.

View and download counts remain aggregate resource-level counters.

The following do not transfer automatically from an original resource to its replacement:

* bookmarks;
* Helpful marks;
* reports;
* view counts;
* download counts.

Existing bookmarks remain pointers only.

A bookmark does not preserve access after the resource becomes:

* Hidden;
* Restricted;
* Removed;
* Replaced;
* otherwise unavailable.

Current status, permission, and file-availability checks remain authoritative when the bookmarked destination is opened.

This document does not introduce automatic cleanup of:

* bookmarks;
* Helpful marks;
* view counts;
* download counts

unless another accepted lifecycle rule requires it.

For Removed resources, D040 permits required historical relationships and aggregate activity counts to remain as retained historical/reference data.

Their retention does not make the Removed resource visible or accessible to the account that created the relationship.

### 11.16 Notifications

Notifications retain the accepted:

* recipient account reference;
* target type;
* target identifier where applicable;
* generic message;
* read/unread state;
* optional read timestamp;
* creation timestamp.

No accepted source defines:

* a notification-retention duration;
* automatic notification expiry;
* automatic notification cleanup.

This section does not invent one.

An old notification remains a pointer only.

It does not preserve access to a resource that has become:

* Hidden;
* Restricted;
* Removed;
* Replaced;
* otherwise unavailable.

When the notification destination is opened, the application must recheck:

* current authentication;
* current account status;
* current role;
* current target existence;
* current object-level access;
* current resource status;
* current file availability where applicable.

This document does not introduce:

* email-notification history;
* push-notification history;
* notification archival;
* automatic notification purge.

### 11.17 Files and `file_availability`

Resource status and `file_availability` remain separate controls.

The accepted `file_availability` values are exactly:

* `available`;
* `deleted`;
* `invalidated`.

No new file state is introduced.

A file may be served only when:

1. current access and resource-status rules permit the request; and
2. `file_availability = 'available'`.

Both conditions are required.

`file_availability = 'deleted'` must prevent serving.

`file_availability = 'invalidated'` must also prevent serving.

The accepted current direction establishes the minimum meaning that:

* `available` means the stored file reference may be served only when status and permission also allow access;
* `deleted` represents a file that has been physically removed and must not be served;
* `invalidated` represents a stored file reference or file state that is no longer usable and must not be served.

The exact application behavior difference between `deleted` and `invalidated`, including any separate cleanup or maintenance handling, remains an implementation item for `BUILD_PLAN.md`.

This section does not invent additional semantics or a new file state to resolve that implementation detail.

A database rollback cannot automatically restore or undo a physical filesystem action that already occurred.

Database state and physical cleanup must therefore be coordinated carefully.

If physical cleanup fails, the system must fail closed:

* the file must remain non-servable;
* current resource status must continue to apply;
* current `file_availability` must continue to block serving;
* a cleanup failure must not restore access.

### 11.18 AI Output

Full AI-output privacy and lifecycle behavior remains defined in Section 9.

For retention purposes, this section confirms:

* `ai_outputs` stores one current row per resource and output type;
* lifecycle states remain exactly:

  * `draft`;
  * `retained`;
  * `invalidated`;
* there is no AI-output history table;
* a replacement resource does not inherit the original resource's AI output;
* output is not retained merely for speculative future use;
* invalidated content does not become public again merely because the source resource later becomes Approved.

Status-specific handling remains:

* **Rejected** — draft output is deleted or invalidated and excluded from user-facing AI reads;
* **Withdrawn** — draft output is deleted or invalidated and excluded from user-facing AI reads;
* **Removed** — output is deleted or invalidated and must not remain searchable, visible, or usable;
* **Hidden** — output may remain stored but is excluded from public-facing use;
* **Restricted** — output may remain stored but is excluded from public-facing use;
* **Replaced** — original-resource output remains tied to the original, is excluded from public use, and is not inherited by the replacement.

This document does not apply delete-or-invalidate behavior automatically to every status that is merely non-public.

Detailed:

* lifecycle transitions;
* source-file staleness;
* current-row regeneration;
* late-response handling;
* invalidated-output filtering

remain defined in Section 9.

### 11.19 Audit and Historical Accountability

Required `resource_action_history` and `audit_log` rows remain subject to Section 10.

Existing ledger rows remain append-only at the application level.

D040 does not erase:

* the Admin removal actor;
* the removal reason;
* the removal status transition;
* required historical resource references.

This document does not define:

* a fixed ledger-retention period;
* an audit archive;
* automatic ledger expiry;
* automatic ledger purge.

The absence of those features does not establish indefinite retention as institutional policy.

### 11.20 Formal Retention Remains an Institutional Determination

The accepted v1.0 documents do not define formal retention periods for:

* accounts;
* resources;
* reports;
* notifications;
* bookmarks;
* Helpful marks;
* audit records;
* resource-action history;
* AI output;
* other retained operational data.

This absence does not establish indefinite retention as Bulacan Polytechnic College policy.

The application-level rules in this section define what the current technical design preserves, minimizes, invalidates, or restricts during accepted workflows.

They do not determine:

* how many years data should remain;
* whether institutional archival is required;
* when final disposal should occur;
* what formal institutional authority applies to disposal;
* what future data-subject procedures may require.

Bulacan Polytechnic College or the authorized deploying office remains responsible for establishing formal retention and disposal schedules and related institutional procedures before real deployment, as applicable.

This document does not make that institutional determination.

---

*End of Section 11.*

## 12. Local/LAN Storage and External Transmission Boundary

### 12.1 Deployment Scope

BPC LearnShare v1.0 is a local/LAN academic MVP prototype built with:

* native PHP;
* MySQL/MariaDB;
* XAMPP on Windows;
* HTML;
* CSS;
* vanilla JavaScript.

Composer libraries may be used only for justified helper tasks within the accepted project direction.

The defense build is intended for demonstration on a local machine or LAN.

It is not:

* a production-scale campus deployment;
* a publicly internet-accessible service;
* a cloud-hosted platform;
* a currently institution-wide operating system.

Campus-wide adoption remains a future design direction rather than a claim about the current implementation or deployment scale.

This section does not introduce:

* cloud hosting;
* cloud file storage;
* CDN storage;
* public internet deployment;
* school-portal integration;
* external identity-provider integration;
* remote public file sharing;
* a new deployment target.

### 12.2 Primary Application-Managed Data Remains in the Local Server Environment

The accepted application's primary server-side records remain in the local XAMPP deployment environment.

MySQL/MariaDB stores the accepted application records, including:

* accounts;
* taxonomy records;
* resource metadata;
* ownership and lifecycle data;
* reports;
* temporary open-report and open-replacement tracking;
* resource action history;
* audit records;
* bookmarks;
* Helpful marks;
* notifications;
* system settings;
* AI-notice acknowledgment fields;
* AI output where generated and retained.

Uploaded file content is stored separately in protected local server storage.

The database stores the accepted file reference and technical metadata rather than embedding the uploaded file content directly in a database field.

This local-server design does not mean that every representation or copy of information always remains on one physical machine.

When BPC LearnShare is accessed through a LAN:

* authenticated client devices receive permitted web pages from the server;
* permitted resource information is transmitted across the local network;
* permitted uploaded-file content may be streamed or downloaded to an authenticated client;
* the client device does not become another database server merely because it receives a permitted browser response.

Once a user downloads a permitted resource, the resulting copy exists on a user-controlled device outside the application's direct storage and lifecycle control.

The application does not introduce:

* download-device tracking;
* remote deletion;
* digital-rights management;
* watermark enforcement;
* copy prevention;
* screenshot blocking;
* print blocking.

### 12.3 Local/LAN Deployment Reduces Exposure but Does Not Guarantee Privacy or Security

Local/LAN deployment reduces exposure to public-internet-scale access, but it does not automatically make the system private or secure.

Realistic local/LAN risks include:

* unauthorized physical access to the host computer;
* direct database access outside the application;
* direct access to uploaded files through the host filesystem;
* weak or shared Windows accounts;
* an unlocked or unattended host computer;
* shared or unattended authenticated client devices;
* careless copying of upload folders;
* careless handling of database exports;
* downloaded files remaining on user-controlled devices;
* screenshots;
* photographed screens;
* printed copies;
* unauthorized users connected to the same LAN;
* insecure or incorrect LAN configuration;
* incorrect Apache, document-root, alias, or storage-path configuration;
* server folders being shared through operating-system or network tools outside BPC LearnShare.

The fact that a device is connected to the same LAN does not make its user authorized to access BPC LearnShare data.

Application authentication, current account status, role permissions, ownership rules, resource status, and file availability remain necessary.

This section does not turn the local/LAN MVP into an enterprise threat model and does not introduce:

* endpoint-management software;
* device monitoring;
* remote wipe;
* data-loss-prevention infrastructure;
* mandatory firewall administration;
* network segmentation;
* a web application firewall;
* SIEM;
* intrusion-detection infrastructure;
* enterprise monitoring.

These remain limitations or future production-hardening concerns rather than new v1.0 requirements.

### 12.4 Protected Uploaded-File Storage

Uploaded files must use:

* randomized, non-guessable stored filenames;
* protected local server storage outside the public web root or otherwise protected from direct web access;
* controlled application-level file serving;
* no direct static file URL that bypasses current application checks.

The original filename is retained only for permitted display and lifecycle purposes.

It must never be used as:

* the physical storage path;
* the public file path;
* the authorization basis for serving the file.

Where D040 applies, the Removed-resource lifecycle later replaces the stored original-filename value with the accepted `[removed]` placeholder.

Before serving a file, the controlled endpoint must verify the applicable current conditions, including:

* authentication;
* current account existence;
* Active account status;
* current role;
* ownership where relevant;
* current resource status;
* current permission;
* `file_availability`;
* file existence.

Protected storage and controlled serving reduce accidental exposure and direct-URL bypass.

They do not:

* encrypt the uploaded file at rest;
* prevent a person with direct authorized host/filesystem access from reading stored content outside the application;
* compensate for an incorrectly configured document root or server path;
* eliminate the need for current server-side authorization.

Mandatory encryption-at-rest infrastructure is not introduced as a v1.0 requirement.

It remains a deferred production-hardening consideration.

### 12.5 LAN Transport and the HTTPS Boundary

Mandatory HTTPS is not a v1.0 requirement for the localhost/LAN capstone demonstration.

This does not mean local or LAN traffic is automatically encrypted.

When a client device accesses the BPC LearnShare server across the LAN using ordinary HTTP, information transmitted between the browser and server does not receive TLS/HTTPS transport protection.

Depending on the page or request, this may include:

* login credentials;
* session-related browser traffic;
* resource metadata;
* page content;
* uploaded content;
* downloaded file content.

Localhost-only traffic remains on the same machine, but ordinary HTTP still does not provide HTTPS/TLS protection.

For LAN access, the lack of HTTPS means transmitted data could be exposed if the network, host, or connected devices are misconfigured or compromised.

This is an accepted v1.0 limitation for the local/LAN academic demonstration and a deferred production-hardening concern.

This section does not introduce:

* public TLS deployment;
* reverse-proxy infrastructure;
* certificate automation;
* mandatory production HTTPS configuration;
* broader production network hardening.

### 12.6 Optional External AI Is the Confirmed External-Service Transmission Path

Within the accepted application's designed integrations, optional external API-based AI is the only confirmed path that intentionally transmits eligible resource content to an external third-party service.

This is the principal application-integrated exception to the local/LAN server boundary.

This statement does not mean that no copy of information can ever leave the server environment.

Separate situations also exist:

* permitted browser responses travel to authenticated LAN clients;
* permitted downloads create copies on user-controlled devices;
* users may print, photograph, or screenshot information they are allowed to view;
* administrators may manually create exports, copied folders, archives, or removable-media copies outside normal application workflows.

Those situations are not hidden external integrations and are addressed separately in Sections 12.9 and 12.10.

When an external AI provider is configured and used:

* only currently eligible resource content may enter the AI-processing path;
* the payload must remain limited to what the accepted AI-assisted purpose reasonably requires;
* Pending-file processing requires:

  * successful basic upload validation;
  * clear uploader notice;
  * uploader acknowledgment;
* missing or declined acknowledgment skips Pending-file AI processing;
* ordinary upload and non-AI moderation continue;
* external transmission occurs only when the external provider is actually configured and used.

This document does not assume a provider's:

* retention period;
* model-training use;
* deletion behavior;
* processing or storage location;
* confidentiality terms;
* ownership terms;
* security guarantees;
* regulatory compliance.

Those matters depend on the provider actually selected, its current terms and configuration, and institutional review.

Formal provider authorization remains a responsibility of Bulacan Polytechnic College or the authorized deploying office for real deployment.

This section does not repeat the complete notice and acknowledgment requirements already defined in Section 8.

### 12.7 Local AI Does Not Automatically Resolve Every Privacy Concern

Not every possible AI implementation sends content to an external provider.

A fully local AI component, such as an experimental Ollama-based configuration, may avoid external transmission for the specific processing call performed locally.

However, local AI remains experimental rather than the default v1.0 direction.

Local execution does not automatically guarantee:

* correct role and status checks;
* appropriate AI eligibility;
* secure local storage;
* correct lifecycle handling;
* accurate output;
* privacy-safe output;
* appropriate human review;
* complete institutional governance.

The accepted AI rules continue to apply regardless of whether processing occurs through:

* an external API;
* an experimental local AI component.

Local processing removes the specific external-provider transmission for that call; it does not remove every privacy, security, accuracy, lifecycle, or governance risk.

### 12.8 Unrelated Local Data Must Not Be Sent to an External AI Provider

The presence of information in the local database does not make that information part of an AI payload.

An external AI request must remain limited to:

* eligible resource content;
* necessary extracted content;
* necessary resource metadata;
* the minimum practical data required for the specific accepted AI-assisted function.

The following must not be included merely because they are locally available:

* plaintext passwords;
* password hashes;
* session identifiers;
* CSRF tokens;
* database credentials;
* AI API keys;
* full audit logs;
* full resource-action histories;
* unrelated reports;
* unrelated report comments;
* unrelated moderation history;
* unrelated notifications;
* unrelated account information;
* unrelated resources;
* unrelated uploaded-file content.

Exact payload construction remains assigned to `AI_FEATURES.md`.

That later document must define what information is sent for each selected AI function without expanding the accepted data scope.

### 12.9 No Other Confirmed Application Integration Exists

Other than optional external AI processing, the accepted v1.0 application has no confirmed workflow for:

* cloud synchronization;
* cloud file storage;
* external analytics;
* advertising;
* telemetry;
* school-portal integration;
* external account synchronization;
* external identity-provider synchronization;
* email-based notifications;
* push notifications;
* public file-sharing links;
* automatic remote backup;
* external document-export services.

This document does not introduce any of those integrations.

The absence of an application integration does not make outside operator actions technically impossible.

An administrator with direct access to the server environment may still use external tools to:

* export database content;
* copy upload folders;
* create archives;
* move files to removable media;
* share files outside the application.

Those actions occur outside the accepted BPC LearnShare workflow and must not be mistaken for application-managed integrations.

### 12.10 Data After a Permitted Download

When an authenticated user legitimately downloads an Approved resource, the resulting copy is stored on a user-controlled device outside BPC LearnShare's direct lifecycle enforcement.

A later resource-status change may remove or narrow future access through the application according to the current status, role, permission, and file-availability rules.

For example:

* Hidden removes the resource from normal browse/search and general authenticated-user access while preserving accepted uploader/Moderator/Admin visibility;
* Restricted keeps the resource outside normal browse/search while preserving accepted restricted visibility;
* Replaced removes the original from normal browse/search while preserving accepted history/reference visibility;
* Removed eliminates uploader and general-user access and leaves only the minimized Admin-only accountability/reference record.

Those later actions control future application access.

They cannot automatically erase a copy that was previously:

* downloaded;
* printed;
* photographed;
* screenshotted;
* copied to another folder or device.

This is an accepted limitation of providing legitimate download access without remote device control.

BPC LearnShare v1.0 does not introduce:

* remote deletion;
* digital-rights management;
* device tracking;
* mandatory watermarking;
* screenshot blocking;
* print blocking.

This limitation must be consolidated in Section 13.

### 12.11 Backups, Exports, and Manual Copies Remain Outside Normal Application Lifecycle Control

Production-grade backup infrastructure and formal backup/storage procedures are deferred production concerns.

BPC LearnShare v1.0 does not include:

* an application backup module;
* an automatic backup service;
* a remote backup service;
* a backup-retention scheduler;
* an application-managed restore archive.

An administrator may manually create:

* a MySQL/MariaDB export;
* a copied upload folder;
* a ZIP archive;
* a removable-media copy;
* another operating-system-level copy.

Such copies are not automatically governed by later application status changes.

For example, a copied upload folder or older database export may still contain information that the live application later:

* hides;
* restricts;
* minimizes;
* invalidates;
* removes from current use.

The application cannot automatically update, revoke, minimize, or delete every manual copy created outside its workflows.

Safe storage, access, transfer, retention, and disposal of manual copies remain operational and institutional responsibilities.

This section does not make manual backups or exports a confirmed v1.0 application feature and does not introduce a new backup module.

### 12.12 Institutional Responsibility for Real Deployment

For a real institutional deployment, Bulacan Polytechnic College or the authorized deploying office remains responsible for determining and establishing appropriate operational rules concerning:

* the approved host machine;
* the approved network environment;
* authorized system operators;
* physical access to the server;
* Windows account and device practices;
* use of shared devices;
* LAN access and configuration;
* production transport-security requirements;
* backup and manual-copy handling;
* downloaded and printed resource handling;
* AI-provider authorization;
* external-provider review;
* formal storage procedures;
* formal retention and disposal procedures.

These responsibilities remain outside the v1.0 application.

This document does not introduce an in-application:

* governance module;
* network-management module;
* backup-management module;
* device-management system;
* provider-approval workflow.

Exact directory paths, Apache configuration, protected storage folders, filesystem permissions, deployment steps, backup procedures, and any future production transport-security approach remain assigned to `BUILD_PLAN.md` or later deployment planning.

---

*End of Section 12.*

## 13. Known Privacy and v1.0 Limitations

### 13.1 Purpose of This Limitation Register

This section consolidates the privacy-relevant limitations, boundaries, implementation dependencies, and deferred concerns already identified throughout Sections 1–12.

Its purpose is to prevent BPC LearnShare from overstating:

* privacy protection;
* security maturity;
* legal compliance;
* production readiness;
* institutional deployment readiness.

The limitations are grouped into four broad categories:

1. **accepted v1.0 scope boundaries** — deliberate limits of the academic MVP rather than automatically missing features;
2. **implementation-dependent privacy risks** — areas where the accepted design protects information only when application code and configuration enforce it correctly;
3. **institutional or operational responsibilities** — matters that the application itself does not determine or manage;
4. **deferred production hardening** — controls that may be appropriate for a future pilot or production deployment but are not current v1.0 requirements.

Not every limitation requires a new feature.

Some require correct implementation of an already-accepted control. Others require testing, institutional procedure, or future production hardening.

This document does not claim that BPC LearnShare fully complies with RA 10173.

This section is not:

* a legal opinion;
* a compliance certification;
* a formal Privacy Impact Assessment;
* a production privacy audit;
* an enterprise threat model.

### 13.2 Legal, Institutional, and Governance Limitations

This document applies general RA 10173 principles only at a student-project planning level.

It does not make a formal determination regarding:

* the applicable lawful basis for every processing activity;
* the complete legal obligations of a real deployment;
* the final institutional privacy-governance structure.

This document does not definitively declare Bulacan Polytechnic College to be the personal information controller unless an accepted institutional or legal source separately establishes that conclusion.

Pending-file AI acknowledgment remains an application-level workflow condition.

Acknowledgment alone is not treated as:

* legal consent under RA 10173;
* proof that the uploader is authorized to share the resource;
* consent given on behalf of another person named or depicted;
* proof of copyright ownership;
* resolution of third-party rights;
* resolution of every applicable lawful-basis question;
* institutional approval of the configured AI provider.

The in-application AI notice is not the institution's complete official privacy notice.

BPC LearnShare v1.0 does not include:

* a Data Protection Officer application role;
* a DPO module;
* a privacy-notice management module;
* a consent-management system;
* a data-subject request portal;
* a breach-response module;
* an institutional provider-approval workflow;
* an institutional retention-policy module.

Formal responsibilities remain with Bulacan Polytechnic College or the authorized deploying office for any real deployment, including:

* official privacy notices;
* provider review and authorization;
* lawful-basis determinations;
* contracts or formal data-sharing arrangements where applicable;
* institutional retention and disposal rules;
* data-subject procedures;
* incident handling;
* operator authorization;
* deployment governance.

These institutional responsibilities are not converted into new v1.0 application modules.

### 13.3 Identity and Account Limitations

Student self-registration uses the accepted minimal account dataset.

Username and display name identify an account inside BPC LearnShare but are not independent proof of official institutional identity.

Teacher/Instructor, Moderator, and Admin accounts are Admin-provisioned.

However, Admin provisioning is not the same as independent institutional identity verification.

BPC LearnShare v1.0 has no:

* institutional-email verification;
* student-number validation;
* roster integration;
* roster synchronization;
* external institutional identity source;
* identity-verification status;
* expanded personal academic profile.

Minimal account collection reduces the amount of personal information stored by the application.

The tradeoff is lower identity assurance.

The application cannot independently prove that a self-registered Student is the official institutional person suggested by the account's username or display name.

The application also has no separate technical identity-verification mechanism for Admin-provisioned roles.

There is no self-service account-deletion lifecycle.

Disabling an account:

* blocks future protected access;
* does not delete the account row;
* does not anonymize the account;
* does not remove accepted historical references linked to the account.

No fixed account-retention period is defined.

These limitations do not create a new v1.0 requirement for:

* institutional email;
* student-number collection;
* roster integration;
* identity verification;
* self-service account deletion;
* automatic account anonymization.

### 13.4 Uploaded Content and Information About Other People

Free-text metadata, filenames, documents, presentations, screenshots, photographs, scanned pages, handwriting, and embedded images may contain information about:

* classmates;
* teachers;
* authors;
* presenters;
* group members;
* document creators;
* other people.

Schema minimization limits the fields the application requests.

It cannot guarantee that uploader-provided content contains no unnecessary personal or identifying information.

Privacy-relevant information may still appear in:

* resource title;
* description;
* topic;
* original filename;
* document text;
* presentation content;
* photographs;
* screenshots;
* handwritten notes;
* scanned documents;
* embedded images.

Basic upload validation performs the accepted **technical upload checks**:

* allowed extension;
* MIME/content consistency;
* configured file-size limit;
* empty or unreadable/corrupt-file rejection.

Passing those checks does not establish:

* broad file safety;
* privacy compliance;
* copyright ownership;
* authority to share;
* consent;
* legal authorization;
* academic correctness;
* factual accuracy.

Human moderation also does not guarantee:

* privacy compliance;
* copyright compliance;
* legal authorization;
* factual correctness;
* academic correctness;
* detection of every unnecessary personal detail;
* detection of every concern embedded in an image or scanned document.

BPC LearnShare v1.0 has no:

* automated personal-information detector;
* automatic privacy redaction;
* OCR-based privacy scanning;
* AI-based privacy scanning;
* facial recognition;
* automatic face blurring;
* automatic identifier detection;
* automated ownership verification;
* automated copyright verification;
* malware-scanning service.

Image-only resources may contain privacy-relevant information that is not visible in metadata and is not searchable through extracted text without a future OCR or vision capability.

Uploader review before submission reduces risk but does not transfer all responsibility to the uploader.

The application must still enforce its accepted controls, and institutional privacy responsibilities remain outside the uploader's role.

### 13.5 Access-Control and Authorized-User Limitations

Role-, ownership-, status-, object-, and file-availability checks reduce unnecessary exposure.

They do not eliminate every possible misuse by a user who already has legitimate access.

Privacy depends on correct server-side enforcement.

The following are not permission controls by themselves:

* hidden interface buttons;
* disabled client-side controls;
* cached interface state;
* bookmarks;
* notifications;
* old direct links;
* previously rendered pages.

Every protected request must still apply current server-side checks.

The accepted application does not comprehensively log:

* resource-detail views;
* ordinary page access;
* file inspections;
* searches;
* dashboard visits;
* other read-only Moderator/Admin activity.

As a result, passive misuse of legitimately granted access may not be fully detectable or reconstructable.

For example, the application may not be able to distinguish after the fact between:

* a Moderator viewing a permitted resource for legitimate review;
* the same Moderator viewing that permitted resource out of personal curiosity.

The existing accountability ledgers record accepted state-changing resource and administrative actions.

They are not comprehensive read-access logs.

BPC LearnShare v1.0 does not introduce:

* a staff-view history;
* a read-access log;
* a general activity-events table;
* comprehensive user-behavior tracking.

### 13.6 Audit and Accountability Limitations

`resource_action_history` and `audit_log` are append-only at the normal application level.

Normal application code must not update or delete existing ledger rows.

However, application-level append-only behavior does not make the ledgers:

* cryptographically immutable;
* tamper-proof;
* blockchain-backed;
* write-once infrastructure;
* independently certified.

A person with direct database access outside normal application controls could alter ledger records.

BPC LearnShare v1.0 has no:

* cryptographic audit chaining;
* external log server;
* SIEM;
* centralized monitoring;
* enterprise audit certification.

The two ledgers also do not provide full activity analytics.

They preserve the accepted state-changing actions they were designed to record, not every system interaction.

Free-text ledger notes remain an additional limitation.

Even with safe-summary guidance, a Moderator or Admin may unnecessarily include:

* a person's name;
* identifying information;
* excessive narrative context;
* information copied from another record.

BPC LearnShare v1.0 has no:

* automatic personal-information detection for ledger notes;
* automatic note redaction;
* AI note review.

Privacy therefore depends partly on staff following the accepted short, factual, necessary-note guidance.

### 13.7 AI Processing and Provider Limitations

AI remains:

* optional;
* assistive;
* configurable;
* non-authoritative.

Core Phases 1–2 remain usable when AI is:

* disabled;
* unconfigured;
* unavailable;
* failing;
* rate-limited;
* missing an API key;
* entirely absent.

AI failure should affect only the applicable AI-assisted function.

It must not block ordinary:

* upload;
* moderation;
* metadata search and filtering;
* viewing;
* downloading;
* bookmarking;
* Helpful marks;
* reporting;
* core Moderator/Admin workflows.

AI output may be:

* incomplete;
* inaccurate;
* unsupported;
* misleading;
* inappropriate for direct reliance.

AI does not guarantee academic correctness.

AI must not:

* make final moderation decisions;
* approve;
* reject;
* publish;
* validate;
* Hide;
* Restrict;
* Remove;
* delete;
* answer exams;
* answer quizzes;
* answer graded assignments;
* provide answer keys.

When an external API-based AI provider is configured and used, eligible resource content may leave the local/LAN environment.

No provider-specific assumption is made regarding:

* retention;
* model-training use;
* deletion;
* geographic processing;
* geographic storage;
* confidentiality;
* ownership;
* security guarantees;
* regulatory compliance.

Those matters depend on:

* the provider actually selected;
* the provider's current terms;
* available account/API configuration;
* current privacy documentation;
* institutional review.

A local AI component may avoid external-provider transmission for a specific local call.

Local processing does not automatically resolve:

* privacy concerns;
* security concerns;
* access-control errors;
* lifecycle errors;
* inaccurate output;
* governance responsibilities.

BPC LearnShare v1.0 has no:

* provider registry;
* provider-approval module;
* transmission-history table;
* prompt-history table;
* provider-call-history table;
* consent-history system.

The application also does not maintain a full local history of complete AI request and response payloads.

This absence does not establish what an external provider may retain.

Provider-side handling remains subject to provider-specific review.

The accepted schema does not preserve historical notice versions.

The exact acknowledgment-reset behavior after:

* replacement of a Pending/Needs Correction file;
* material revision of the privacy-facing notice

remains a carry-forward implementation matter for `AI_FEATURES.md` and `BUILD_PLAN.md`.

The accepted privacy-preferred direction uses the existing fields:

* `ai_notice_acknowledged`;
* `ai_notice_acknowledged_at`.

No notice-version column, acknowledgment-history table, or new consent structure is introduced.

### 13.8 AI-Output Filtering and Lifecycle Limitations

AI-output privacy depends on every AI-reading and AI-displaying endpoint consistently applying:

* current source-resource status;
* current role and access rules;
* current AI-output lifecycle state;
* current source-file association;
* output-type eligibility;
* requested-surface eligibility.

The accepted schema provides the fields and constraints needed to support these checks.

The schema cannot automatically guarantee that every PHP query and endpoint applies every required condition correctly.

An incorrectly implemented query could expose:

* `draft` output;
* `invalidated` output;
* stale output;
* output tied to an outdated file;
* output from a Hidden resource;
* output from a Restricted resource;
* output from a Replaced resource;
* output from another ineligible resource;
* staff-oriented output on an inappropriate user-facing surface.

This remains a required implementation-review and testing area.

It is not a reason to add:

* an AI-visibility table;
* an AI-permissions table;
* a database-trigger-based visibility system;
* a new lifecycle state.

AI output remains current-value only.

BPC LearnShare v1.0 has no:

* AI-output history table;
* prompt-history table;
* response-history table;
* provider-call-history table;
* full locally retained provider request/response history.

The current-value model also means that previous generated content is not preserved as a version history when a current row is updated.

### 13.9 Retention, Removal, and Lifecycle Limitations

No formal retention period is defined for:

* accounts;
* resources;
* reports;
* notifications;
* bookmarks;
* Helpful marks;
* AI output;
* resource action history;
* audit records;
* other retained operational data.

This absence is not institutional approval for indefinite retention.

BPC LearnShare v1.0 has no:

* retention scheduler;
* automatic expiry;
* purge engine;
* archival module;
* automated anonymization;
* general erasure workflow.

D040 minimizes Removed resources but does not anonymize the retained record.

Required retained information may still include:

* account references;
* taxonomy references;
* technical metadata;
* aggregate activity counts;
* AI-notice acknowledgment state;
* timestamps;
* reports;
* action history;
* bookmarks;
* Helpful marks;
* replacement relationships;
* other required historical references.

Those retained relationships may preserve attribution and context.

Removed-resource access therefore remains Admin-only.

Withdrawn is different from Removed.

A Withdrawn resource:

* is not D040-minimized;
* retains its original title;
* retains its original description;
* retains its original topic;
* retains its original filename;
* retains its controlled-tag relationships.

Account disabling also does not delete account-linked historical records.

Exact institutional retention and disposal schedules remain unresolved outside the application.

### 13.10 File and Filesystem Limitations

Resource status and `file_availability` must both be enforced before a file is served.

Neither condition is sufficient by itself.

The accepted `file_availability` values remain:

* `available`;
* `deleted`;
* `invalidated`.

The minimum accepted behavior is:

* `available` permits serving only when current status and permission also allow access;
* `deleted` prevents serving;
* `invalidated` prevents serving.

The exact operational distinction between `deleted` and `invalidated` remains an implementation detail for `BUILD_PLAN.md` within the accepted three-state model.

Filesystem operations are not automatically rolled back with database transactions.

A failed physical cleanup may leave residual file content on disk even when:

* the resource status blocks application access;
* `file_availability` blocks serving;
* the application correctly fails closed.

Protected storage and randomized filenames reduce accidental exposure.

They do not provide encryption-at-rest.

A person with direct authorized host or filesystem access may bypass normal application file-serving controls.

BPC LearnShare v1.0 does not include:

* encryption-at-rest infrastructure;
* malware scanning;
* enterprise file-security tooling;
* automated content disarm or reconstruction.

Correct file privacy depends partly on correct implementation of:

* Apache configuration;
* document-root separation;
* protected storage paths;
* filesystem permissions;
* randomized stored filenames;
* controlled file serving;
* current object-level authorization;
* D034 dual-gate enforcement.

The exact protected public/private folder structure remains an implementation item for `BUILD_PLAN.md`.

This section does not introduce:

* a deletion queue;
* a cleanup-history table;
* a new file state.

### 13.11 Local/LAN and Transport Limitations

Local/LAN deployment reduces public-internet exposure.

It does not guarantee privacy or security.

Risks remain from:

* unauthorized physical host access;
* direct database access;
* direct filesystem access;
* weak or shared Windows account practices;
* unattended authenticated devices;
* unauthorized LAN users;
* LAN misconfiguration;
* incorrect Apache configuration;
* incorrect document-root configuration;
* incorrect storage-path configuration;
* operating-system or network sharing outside the application.

Mandatory HTTPS is not a v1.0 requirement for the localhost/LAN academic demonstration.

This does not mean ordinary HTTP traffic is protected.

When a client accesses the server over ordinary HTTP on the LAN, the connection does not receive TLS/HTTPS transport protection.

Depending on the request, transmitted information may include:

* login credentials;
* session-related browser traffic;
* pages;
* resource metadata;
* uploaded content;
* downloaded file content.

Encryption-at-rest infrastructure is also not included as a mandatory v1.0 requirement.

BPC LearnShare v1.0 does not require:

* firewall-management functionality;
* network-segmentation infrastructure;
* a web application firewall;
* intrusion-detection infrastructure;
* SIEM;
* centralized monitoring;
* enterprise incident-response infrastructure.

These remain accepted or deferred limitations of the academic MVP.

They must not be interpreted as evidence that production deployments would not need stronger controls.

### 13.12 Downloaded, Printed, and Manually Copied Data Limitations

A legitimate download creates a copy on a user-controlled device outside BPC LearnShare's direct lifecycle enforcement.

A later status change may remove or narrow future application access according to the current:

* resource status;
* role;
* permission;
* file availability.

However, a later Hide, Restrict, Remove, or Replace action cannot automatically erase a copy that was previously:

* downloaded;
* printed;
* photographed;
* screenshotted;
* copied to another folder;
* copied to another device.

Manual administrator-created copies may also exist outside normal application controls, including:

* database exports;
* copied upload folders;
* ZIP archives;
* removable-media copies;
* operating-system-level backups.

An older manual copy may still contain information that the live application later:

* Hides;
* Restricts;
* minimizes;
* invalidates;
* Removes from current use.

BPC LearnShare cannot automatically apply later status, access, minimization, retention, or disposal changes to copies created outside its normal workflows.

BPC LearnShare v1.0 has no:

* remote wipe;
* digital-rights management;
* device tracking;
* screenshot blocking;
* print blocking;
* mandatory watermarking;
* application backup module;
* automatic remote backup;
* backup-retention scheduler.

Handling downloaded, printed, exported, archived, or manually copied information remains partly a responsibility of:

* the receiving user;
* the system operator;
* Bulacan Polytechnic College or the authorized deploying office.

### 13.13 Implementation and Testing Dependence

Privacy behavior depends on correct implementation of the accepted design.

Important application-dependent areas include:

* live account-existence checks;
* live account-status checks;
* live role checks;
* object-level authorization;
* ownership checks;
* current resource-status checks;
* current notification-target validation;
* current audit-target validation;
* D034 dual-gate file serving;
* protected file storage;
* controlled file serving;
* D040 removal sequencing;
* safe-summary logging;
* append-only ledger behavior;
* required accountability writes;
* transaction atomicity;
* database/filesystem coordination;
* AI eligibility rechecks before transmission;
* AI eligibility rechecks before response storage;
* stale AI-output detection;
* invalidated-output filtering;
* output-type/surface filtering.

The accepted `schema.sql` baseline has not yet been executed and verified against the actual local XAMPP database environment.

The actual local database engine and version must be confirmed before implementation relies on CHECK constraints for secondary enforcement.

The accepted schema notes identify version-sensitive CHECK support.

Regardless of the local result:

* PHP-side validation remains mandatory;
* database CHECK constraints must not become the only validation layer;
* unsupported or unenforced CHECK behavior must not silently weaken application rules.

Additional accepted implementation and verification items remain:

* the exact MIME/content validation mechanism is not yet finalized;
* the current 20 MB maximum upload size is an untested working default and must be tested against realistic scanned PDFs and image-heavy presentations;
* the exact protected public/private folder structure remains for `BUILD_PLAN.md`;
* exact D040 database/filesystem sequencing remains for `BUILD_PLAN.md`;
* exact cleanup-failure and retry behavior remains for `BUILD_PLAN.md`;
* exact location and retention handling for server-side records of risky failed-upload attempts remains unresolved.

Server-side security or upload-failure logs must not become a place to store:

* full uploaded content;
* passwords;
* password hashes;
* session identifiers;
* CSRF tokens;
* API keys;
* unnecessary personal information.

These are implementation and testing dependencies.

They are not reasons to redesign the accepted schema in Section 13.

### 13.14 Boundary of This Register

These limitations are documented honestly so BPC LearnShare does not overclaim:

* privacy;
* security;
* legal compliance;
* production readiness;
* institutional deployment maturity.

Recording a limitation does not automatically justify adding a new v1.0:

* role;
* resource status;
* report status;
* permission;
* table;
* column;
* module;
* integration;
* AI feature;
* monitoring system;
* enterprise infrastructure component.

Appropriate responses fall into four categories:

1. correct implementation of an already-accepted control;
2. verification through implementation review and testing;
3. institutional or operational procedure outside the application's scope;
4. deferred production hardening or formally approved future scope.

Examples include:

* server-side authorization implemented correctly rather than adding a new role;
* lifecycle and AI filtering verified through testing rather than adding a new permissions table;
* formal institutional retention procedures rather than inventing an application retention scheduler;
* future production HTTPS, backup, monitoring, or storage hardening rather than expanding the local/LAN defense build.

The limitations in this register are not open invitations to expand v1.0.

Any future scope change still requires explicit review of:

* user need;
* permissions;
* workflow impact;
* database impact;
* security impact;
* privacy impact;
* implementation feasibility.

---

*End of Section 13.*

## 14. Carry-Forward Requirements

### 14.1 Purpose of This Handoff

This section is a concise routing reference.

It does not repeat the full limitation register in Section 13.

Its purpose is to identify where accepted privacy requirements, implementation dependencies, verification needs, institutional responsibilities, and deferred production concerns must be carried next.

The destinations are:

* `AI_FEATURES.md`;
* the immediate schema-execution and environment-verification step;
* `BUILD_PLAN.md`;
* `TESTING_CHECKLIST.md`;
* future institutional and operational preparation;
* deferred production hardening.

This section does not:

* create another planning document;
* reopen an accepted decision;
* redesign the accepted schema;
* introduce a new role;
* introduce a new resource or report status;
* introduce a new permission;
* introduce a new table or column;
* introduce a new module;
* introduce a new integration;
* introduce a new AI feature;
* introduce a new privacy feature.

### 14.2 Carry Forward to `AI_FEATURES.md`

`AI_FEATURES.md` must translate the accepted AI and privacy boundaries into exact AI-specific implementation behavior without making AI required for core system use.

It must preserve the following requirements.

#### AI implementation direction and provider evaluation

* External API-based AI remains the preferred prototyping direction.
* Local AI, including possible Ollama use, remains experimental and is not the default implementation.
* A selected external provider must be evaluated using its current:

  * terms;
  * privacy documentation;
  * configuration options;
  * retention practices;
  * model-training or data-use practices;
  * deletion practices;
  * geographic processing/storage information;
  * confidentiality terms;
  * ownership terms;
  * security information;
  * suitability for the academic prototype.

The technical/privacy evaluation for the selected provider should occur before actual project content is sent through that provider in the prototype.

Formal institutional provider approval, contractual review, authorization, and real-deployment acceptance remain responsibilities of Bulacan Polytechnic College or the authorized deploying office.

They are not replaced by `AI_FEATURES.md` and are not implemented as an in-application provider-approval workflow.

#### Purpose-limited AI payloads

Define the exact payload required for each accepted AI-assisted function.

Payloads must remain limited to:

* currently eligible resource content;
* necessary extracted content;
* necessary resource metadata;
* the minimum practical information required for the specific function.

Do not include merely because it exists:

* unrelated account information;
* plaintext passwords;
* password hashes;
* session identifiers;
* CSRF values;
* database credentials;
* AI API keys;
* full audit history;
* full resource-action history;
* unrelated report information;
* unrelated moderation information;
* unrelated notifications;
* unrelated resources;
* unrelated uploaded content.

Do not introduce:

* a provider-payload table;
* a transmission-history table;
* a prompt-history table;
* a provider-call-history table.

#### Pending-file AI notice

Define the exact presentation of the Pending-file AI notice.

The notice must remain understandable and explain the accepted points, including:

* AI processing is optional;
* ordinary upload and moderation continue without AI;
* the applicable AI-assisted purposes;
* eligible resource content may be processed;
* information about other people included in the resource may also be processed;
* eligible content may leave the local/LAN environment when an external API provider is used;
* AI output may be incomplete, inaccurate, or require human review;
* AI cannot take final moderation or resource-lifecycle actions;
* missing or declined acknowledgment skips Pending-file AI only and does not block ordinary upload.

The notice must not become:

* a waiver;
* a release of liability;
* a legal contract;
* an ownership declaration;
* a copyright declaration;
* proof of authority to share;
* a third-party consent declaration;
* the institution's complete official privacy notice.

#### Acknowledgment implementation

Use only the accepted fields:

* `ai_notice_acknowledged`;
* `ai_notice_acknowledged_at`.

Do not introduce:

* a notice-version column;
* acknowledgment-history storage;
* consent-history storage;
* account-wide AI consent;
* per-call consent;
* a provider-acknowledgment table;
* a new acknowledgment status;
* a new table;
* a new column.

`AI_FEATURES.md` must define and document the privacy-preferred carry-forward behavior for changed Pending content:

* when the uploaded file of a Pending or Needs Correction resource is replaced:

  * set `ai_notice_acknowledged = 0`;
  * set `ai_notice_acknowledged_at = NULL`;
  * show the notice again;
  * require acknowledgment again before the changed file may enter Pending-file AI processing.

For a material change to the notice wording while Pending resources are awaiting AI processing:

* use the existing acknowledgment fields;
* reset affected Pending-resource acknowledgment before further Pending-file AI processing;
* require the revised notice to be shown and acknowledged again.

This behavior must be implemented without adding notice-version history or a consent-management system.

The document must also preserve that:

* Pending-file acknowledgment is only one AI-eligibility condition;
* acknowledgment does not override current status, validation, AI configuration, access, or lifecycle rules;
* declining Pending-file AI does not create a permanent opt-out from later Approved-resource AI processing under the current accepted model;
* no separate Approved-resource or per-call acknowledgment workflow is introduced.

#### AI-output behavior

Define exact output-type-by-surface behavior for:

* `summary`;
* `suggested_tags`;
* `suggested_metadata`;
* `duplicate_flag`;
* `moderation_hint`;
* `related_recommendation`.

Do not assume every retained output type is public.

Preserve staff-oriented treatment where appropriate for:

* duplicate flags;
* moderation hints.

Define exact D028 behavior for:

* uploader review;
* uploader editing;
* uploader discard;
* Moderator/Admin review;
* Moderator/Admin editing;
* Moderator/Admin discard;
* approval after visible review without a separate field-by-field acceptance checkbox.

Do not add:

* `is_accepted`;
* `accepted_by`;
* an AI-acceptance table;
* an AI-acceptance history.

#### Source-file association and current-value storage

Use `source_file_reference` as the accepted association with the file that produced the output.

Define:

* how the request captures the applicable source-file reference;
* how the current file is compared before response storage;
* how stale output is detected;
* how stale output is discarded or invalidated;
* how a later valid generation updates the current row.

Do not introduce:

* a content-hash field;
* a file-version table;
* an AI-output version table;
* an AI-output history table

unless a future accepted decision formally changes the schema.

#### Provider calls, failure, and regeneration

Define exact:

* prompt construction;
* provider-call behavior;
* timeout behavior;
* retry behavior;
* rate-limit behavior;
* late-response handling;
* concurrency handling;
* caching;
* invalidation;
* regeneration;
* failure fallback.

Preserve:

* no empty AI-output placeholder rows;
* no successful-looking output on failure;
* failed regeneration must not overwrite valid current output;
* stale or late responses must not overwrite newer valid output;
* current eligibility must be checked before transmission and before response storage;
* unchanged eligible content should not be regenerated or retransmitted unnecessarily while valid current output remains usable.

#### Phase 5 boundary

Phase 5 remains optional stretch scope only.

If implemented, it must:

* use only currently Approved and eligible resources;
* cite or reference the source resources used;
* state clearly when no Approved resource supports an answer;
* avoid unsupported answer invention;
* remain prohibited from answering:

  * exams;
  * quizzes;
  * graded assignments;
  * answer keys.

Phase 5 must not become a required chatbot-first direction.

### 14.3 Immediate Schema and Environment Verification Before Detailed `BUILD_PLAN.md`

After `DATA_PRIVACY.md` is completed and before detailed `BUILD_PLAN.md` drafting relies on database behavior:

1. run the accepted `schema.sql` against a fresh local XAMPP database;
2. confirm the actual database engine and version;
3. verify that the accepted 18-table schema executes successfully;
4. check actual CHECK-constraint support and enforcement;
5. verify foreign keys, unique constraints, CHECK constraints, and critical indexes;
6. fix only actual SQL execution or compatibility errors without redesigning the accepted schema by preference;
7. preserve PHP-side validation regardless of database CHECK support.

The result of this verification must become an input to `BUILD_PLAN.md`.

The detailed build plan must not assume that CHECK constraints are active before the actual local environment has been tested.

### 14.4 Carry Forward to `BUILD_PLAN.md`

`BUILD_PLAN.md` must convert the accepted architecture, security, privacy, workflow, and verified database baseline into a small, ordered, beginner-maintainable implementation plan.

It must preserve the following requirements.

#### Database access and validation

* Select one accepted database-access approach:

  * PDO; or
  * mysqli.
* Use prepared statements for all database queries regardless of the selected approach.
* Define consistent:

  * connection handling;
  * transaction handling;
  * exception/error handling;
  * rollback handling.
* PHP-side validation remains mandatory regardless of database CHECK enforcement.

#### First Admin bootstrap

Define the exact setup-only first-Admin method:

* database seed; or
* one-time setup script.

If a one-time script is used, define:

* setup authorization;
* one-use behavior;
* required removal or disabling after successful bootstrap;
* verification that the setup path is no longer accessible.

Do not introduce public Admin registration.

#### Protected file storage

Define the exact:

* public document-root structure;
* private/protected upload directory;
* filesystem path configuration;
* directory permissions;
* upload-write path;
* controlled file-serving path.

Preserve:

* randomized, non-guessable stored filenames;
* original filename for permitted display only;
* no direct static file URL;
* no use of the original filename as the physical path.

The file-serving endpoint must verify the applicable current:

* authentication;
* account existence;
* Active account status;
* role;
* ownership where relevant;
* object existence;
* resource status;
* permission;
* `file_availability`;
* physical file existence.

#### Upload validation

Select and implement the exact MIME/content validation mechanism, such as PHP `finfo` or another narrowly justified helper.

Implement and document:

* extension allowlist;
* MIME/content consistency;
* maximum file size;
* empty-file rejection;
* unreadable/corrupt-file rejection;
* executable rejection;
* script rejection;
* installer rejection;
* archive rejection.

Test the current 20 MB working maximum against realistic:

* scanned PDF files;
* image-heavy PPTX files;
* other representative accepted academic resources.

Do not treat 20 MB as final until that testing is complete.

#### Polymorphic target validation

Implement D036 separately for each accepted polymorphic structure.

For notifications:

* validate `target_type` against the notification target closed set;
* validate the required/allowed `target_id` behavior;
* confirm the target exists;
* confirm the current viewer remains authorized before resolving the destination.

For `audit_log`:

* validate `action_type`;
* validate `target_type` against the audit target closed set;
* validate the D039 action/target pairing;
* confirm `target_id` exists in the target table identified by `target_type`;
* confirm the current viewer may access the audit information.

Do not treat notifications and audit entries as though they share one unrestricted generic target set.

#### Transactions and concurrency

Define exact transaction, locking, status-recheck, rollback, and ordering behavior for:

* moderation decisions;
* report transitions where atomic paired fields are required;
* replacement approval;
* account creation/status/role/password-reset actions;
* taxonomy actions;
* system-setting changes;
* Withdrawn handling;
* D040 removal.

Concurrency-sensitive actions must recheck current state before commit.

#### D040 implementation

Implement D040 exactly through the accepted structures.

The coordinated database-side operation must include:

* status change to Removed;
* `title = '[Removed resource]'`;
* `description = '[Removed resource]'`;
* `topic = '[Removed]'`;
* `original_filename = '[removed]'`;
* deletion of associated `resource_tags`;
* appropriate `file_availability` update;
* accepted AI-output deletion or invalidation;
* required `resource_action_history` entry;
* Admin removal actor and reason;
* correct timestamp behavior;
* preservation of required historical relationships.

Define exact:

* transaction ordering;
* rollback behavior;
* database/filesystem coordination;
* physical cleanup behavior;
* cleanup-failure handling;
* retry or maintenance handling.

Physical cleanup failure must remain fail-closed.

A cleanup failure must never make the file servable.

Do not add a cleanup table, deletion queue, or new file state unless a later formal decision changes scope.

#### File-availability behavior

Preserve exactly:

* `available`;
* `deleted`;
* `invalidated`.

Define the operational assignment and handling of each state without adding another state.

At minimum:

* `available` may be served only when current status and permission also allow;
* `deleted` must not be served;
* `invalidated` must not be served.

Document any implementation difference between `deleted` and `invalidated`.

#### AI configuration and secrets

Define:

* server-side AI configuration;
* environment/configuration-file handling;
* API-key loading;
* failure when configuration is absent.

AI API keys must never appear in:

* client-side code;
* version control;
* the database;
* page output;
* ordinary logs;
* AI payload logs.

#### Application and server logging

Define:

* which risky failed upload-validation attempts are logged;
* the server-side application-log location;
* access restrictions for the log;
* rotation or maintenance approach appropriate to the local MVP;
* retention handling for those records.

Logs must not store:

* passwords;
* password hashes;
* temporary passwords;
* session identifiers;
* CSRF tokens;
* database credentials;
* AI API keys;
* full uploaded files;
* full AI request payloads;
* full AI response payloads;
* unnecessary personal information.

Do not silently add public-internet production hardening to the v1.0 build plan.

### 14.5 Carry Forward to `TESTING_CHECKLIST.md`

`TESTING_CHECKLIST.md` must verify the accepted behavior rather than merely confirm that pages render.

#### Authentication and authorization

Verify:

* unauthenticated visitors can reach only the accepted public surfaces;
* Student registration remains available according to D006;
* unauthenticated users cannot access protected resource or AI functions;
* current account existence is checked;
* current account status is checked;
* current role is checked;
* a Disabled account with an already-open session fails on its next protected request;
* ownership checks apply where required;
* Student/Teacher, Moderator, and Admin permissions remain separated;
* direct URLs do not bypass authorization;
* manipulated identifiers do not bypass authorization;
* hidden buttons do not substitute for server-side checks;
* bookmarks, notifications, old links, cached state, and previously rendered pages never preserve outdated access.

#### Resource visibility and file serving

Verify every accepted resource-status visibility rule:

* Approved;
* Pending;
* Needs Correction;
* Rejected;
* Withdrawn;
* Hidden;
* Restricted;
* Removed;
* Replaced.

Verify:

* only Approved resources appear in normal browse/search;
* Removed remains Admin-only;
* resource-record visibility remains separate from file availability;
* D034's two file-serving gates are both required;
* direct static file access is blocked;
* protected storage is outside the public path or otherwise effectively protected;
* randomized stored filenames are used;
* the original filename is display-only;
* the original filename is never used as a physical path;
* missing physical files fail safely;
* `deleted` files are not served;
* `invalidated` files are not served;
* cleanup failure remains fail-closed.

#### Upload validation and content boundaries

Verify:

* allowed extensions are accepted;
* disallowed extensions are rejected;
* MIME/content mismatch is rejected;
* the configured maximum size is enforced;
* empty files are rejected;
* unreadable/corrupt files are rejected;
* executable files are rejected;
* script files are rejected;
* installer files are rejected;
* archive files are rejected;
* realistic scanned PDFs are tested;
* realistic image-heavy presentations are tested;
* the 20 MB working limit is reviewed using actual samples;
* technical upload validation is never presented as:

  * privacy verification;
  * copyright verification;
  * ownership verification;
  * authority-to-share verification;
  * academic correctness verification;
  * factual accuracy verification.

#### Resource lifecycle and replacement

Verify:

* Pending behavior;
* Needs Correction behavior;
* Approved behavior;
* Rejected behavior;
* Withdrawn behavior;
* Hidden behavior;
* Restricted behavior;
* Removed behavior;
* Replaced behavior;
* Approved does not transition directly to Needs Correction;
* Approved correction uses a linked Pending replacement;
* the replacement row points to the original through `replaces_resource_id`;
* only one open replacement may exist per original;
* open replacement tracking clears for Approved, Rejected, and Withdrawn resolution;
* bookmarks do not transfer;
* Helpful marks do not transfer;
* reports do not transfer;
* view counts do not transfer;
* download counts do not transfer;
* AI output does not transfer;
* reports remain tied to the originally reported resource;
* Withdrawn retains original descriptive metadata and controlled-tag relationships;
* Withdrawn does not receive D040 placeholders;
* D040 uses the exact placeholders;
* D040 deletes associated `resource_tags`;
* D040 preserves required historical relationships;
* Removed remains minimized but not anonymized.

#### Reports and notifications

Verify:

* one unresolved report per user/resource;
* report statuses remain exactly:

  * Open;
  * Escalated;
  * Dismissed;
  * Actioned;
* required escalation fields are enforced;
* required resolution fields are enforced;
* helper tracking remains for Open/Escalated reports;
* helper tracking clears for Dismissed/Actioned reports;
* current notification target type is valid;
* current notification target exists;
* the current viewer is authorized before destination details are shown;
* notification wording remains generic;
* notifications do not duplicate:

  * full resource titles where prohibited by the accepted minimization rule;
  * original filenames;
  * full moderation notes;
  * full report comments;
  * full ledger notes;
  * unnecessary resource content;
* notifications remain pointers only and never permission grants.

#### AI notice and eligibility

Verify:

* AI-disabled behavior;
* AI-unconfigured behavior;
* provider-unavailable behavior;
* timeout and failure fallback;
* core workflows continue with zero AI;
* Pending AI requires:

  * successful basic validation;
  * clear notice;
  * acknowledgment;
* notice presentation occurs before acknowledgment is used;
* notice explains optionality and nonblocking behavior;
* notice explains external-provider transmission where applicable;
* notice explains AI uncertainty and non-authoritative behavior;
* notice does not become a waiver, ownership declaration, third-party consent declaration, or complete institutional privacy notice;
* missing acknowledgment skips Pending AI but continues upload;
* declined acknowledgment skips Pending AI but continues upload;
* acknowledgment alone does not override current status or eligibility;
* changed-file acknowledgment reset and re-acknowledgment work as defined in `AI_FEATURES.md`;
* material notice-change reset and re-acknowledgment work as defined in `AI_FEATURES.md`;
* no separate Approved-resource acknowledgment workflow is introduced;
* no permanent resource-level opt-out is inferred from declining Pending-file AI;
* ineligible statuses are excluded;
* current eligibility is rechecked before transmission;
* current eligibility and source-file association are rechecked before response storage;
* stale and late responses are rejected;
* failed requests create no placeholder output;
* failed regeneration does not overwrite valid current output;
* stale responses do not overwrite newer valid output;
* unchanged valid output is not regenerated unnecessarily.

#### AI-output lifecycle

Verify:

* the six accepted output types;
* the three accepted lifecycle states;
* one current row per `(resource_id, output_type)`;
* no empty placeholder rows;
* draft-output visibility;
* general users cannot access draft output;
* D028 visible review/edit/discard behavior;
* eligible Approved-resource output becomes `retained` where appropriate;
* each output type appears only on an accepted surface;
* staff-oriented output remains restricted where applicable;
* Hidden output is excluded from public use;
* Restricted output is excluded from public use;
* Rejected output is deleted or invalidated;
* Withdrawn output is deleted or invalidated;
* Removed output is deleted or invalidated and unusable;
* Replaced output remains tied to the original and is not inherited;
* stale source-file output is excluded;
* old invalidated content remains excluded even if the source resource later becomes Approved;
* a later successful valid generation may update the same current-value row without reviving old invalidated content;
* every AI-reading and AI-displaying endpoint applies:

  * current source-resource status;
  * current access rules;
  * current lifecycle state;
  * current source-file association;
  * output-type eligibility;
  * surface eligibility.

#### Accountability and privacy-safe records

Verify:

* exact `resource_action_history.action_type` values;
* required nonblank notes for:

  * reject;
  * request correction;
  * hide;
  * restrict;
  * remove;
* exact `audit_log.action_type` values;
* exact `audit_log.target_type` values;
* valid D039 action/target pairing;
* target existence validation;
* append-only application behavior;
* Moderator access remains limited to moderation/report-related history;
* full `audit_log` remains Admin-only;
* notes and logs do not contain:

  * passwords;
  * password hashes;
  * temporary passwords;
  * session identifiers;
  * CSRF tokens;
  * database credentials;
  * API keys;
  * full file content;
  * full AI output;
  * full AI request/response payloads;
  * unnecessary full report content;
  * unnecessary personal information;
* full ledger notes are not copied into notifications;
* required state-changing actions and required ledger writes commit atomically;
* application behavior does not claim protection against direct database alteration outside normal application controls.

#### First Admin bootstrap

After the `BUILD_PLAN.md` method is selected, verify:

* the first Admin can be created only through the accepted setup-only method;
* public Admin registration does not exist;
* any one-time setup script cannot be reused after successful setup;
* the setup script is removed or disabled as required by the build plan.

#### Schema and compatibility

Verify:

* the actual local XAMPP database engine;
* the actual database version;
* actual CHECK-constraint support and enforcement;
* PHP validation remains effective regardless of CHECK behavior;
* the accepted schema creates exactly 18 tables;
* D039 action and target enums are present;
* D039 action/target CHECK behavior is present where supported;
* D040 schema comments and application behavior remain aligned;
* all foreign keys execute successfully;
* all unique constraints execute successfully;
* all CHECK constraints execute or are handled according to the verified local engine;
* all critical indexes are created successfully.

### 14.6 Carry Forward for Institutional and Operational Preparation

The following remain responsibilities for Bulacan Polytechnic College or the authorized deploying office before any real deployment.

They are not v1.0 application modules:

* selection and approval of the host machine;
* selection and approval of the network environment;
* identification of authorized operators;
* physical host-access rules;
* Windows account and device practices;
* shared-device procedures;
* formal institutional privacy notice;
* formal AI-provider review and authorization;
* formal lawful-basis analysis;
* contracts or data-sharing arrangements where applicable;
* formal retention and disposal schedules;
* data-subject request procedures;
* incident-handling procedures;
* backup and manual-copy procedures;
* downloaded and printed information procedures;
* production transport-security requirements;
* production storage requirements;
* institutional operating procedures.

This section does not introduce an in-application:

* governance module;
* DPO module;
* provider-approval workflow;
* retention-policy module;
* backup-management module;
* device-management module.

### 14.7 Deferred Production Hardening

The following remain deferred unless project scope is formally changed:

* HTTPS/TLS for a production or pilot deployment;
* broader server hardening;
* production-grade backup procedures;
* tested restore procedures;
* stronger monitoring;
* stronger malware and file scanning;
* encryption-at-rest infrastructure;
* performance testing;
* load testing;
* storage limits;
* storage-capacity planning;
* institutional onboarding;
* formal operating procedures;
* centralized logging where appropriate;
* SIEM where appropriate;
* mature incident-response procedures;
* public-internet deployment hardening.

These items are not defense-build blockers.

Their deferral does not mean that they would be unnecessary for a future production or campus-scale deployment.

---

*End of Section 14.*

## 15. Document Status and Revision History

### 15.1 Document Status

`DATA_PRIVACY.md` is complete for BPC LearnShare v1.0 privacy planning once all accepted Sections 1–15 and their applied corrections are merged into the repository copy.

The document is aligned with the accepted source-of-truth documents through D040.

It is a student-project privacy-planning reference for a local/LAN academic MVP prepared for a BS Information Systems capstone.

It is not:

* a legal opinion;
* legal advice;
* a compliance certification;
* a formal Privacy Impact Assessment;
* a production privacy audit;
* a complete institutional privacy policy.

Completion of this document does not mean that every implementation, testing, institutional, or future-production matter identified in it has already been resolved.

Section 14 routes those remaining items to their appropriate implementation, AI-planning, testing, institutional, or deferred-hardening destinations.

Those carry-forward items do not make `DATA_PRIVACY.md` incomplete. They are intentionally assigned to the later stage or responsible party where they belong.

### 15.2 Source Documents Used

This document was written against and remains aligned with:

* `PROJECT_BRIEF.md`;
* `DECISIONS.md`, through D040;
* `USER_ROLES.md`;
* `WORKFLOWS.md`;
* `DATABASE_DESIGN.md`;
* `schema.sql` — the accepted 18-table baseline with the D039 `audit_log` patch and D040 removal-lifecycle documentation;
* `SECURITY_NOTES.md`;
* `PROJECT_HANDOFF.md`.

These source documents remain stronger than summary or explanatory wording in `DATA_PRIVACY.md`.

This document must not silently reinterpret, replace, or override an accepted source decision through privacy-specific wording.

If a later accepted decision changes a shared:

* enum;
* closed set;
* role;
* permission;
* lifecycle rule;
* workflow;
* schema rule;
* cross-referenced term;

the affected source and downstream documents must receive a targeted whole-document and cross-document consistency review.

Updating only the document or section where the new decision originated is not sufficient when the changed rule is shared elsewhere.

### 15.3 Scope and Schema Impact

`DATA_PRIVACY.md` introduces:

* no new role;
* no new resource status;
* no new report status;
* no new permission;
* no new table;
* no new column;
* no new module;
* no new workflow;
* no new AI-output type;
* no new AI-output lifecycle state;
* no mandatory AI dependency.

D039 was already an accepted extension of the existing `audit_log` action and target enums.

It did not originate as a new change in this closing section.

D040 was also already accepted.

It uses existing project structures and lifecycle controls, including:

* `resources`;
* `resource_tags`;
* `ai_outputs`;
* `resource_action_history`;
* the existing `file_availability` field;
* coordinated physical-file handling.

D040 introduces:

* no new table;
* no new column;
* no additional resource status.

The accepted schema remains exactly 18 tables.

No D041 is required solely because of this document.

The implementation directions and requirements carried forward by this document — including:

* AI acknowledgment reset after applicable file or notice changes;
* AI notice presentation;
* AI output filtering;
* protected file serving;
* database/filesystem coordination;
* cleanup-failure handling

do not automatically become new schema decisions.

They must be implemented through the existing accepted structures unless a later explicit decision formally changes the design.

### 15.4 Confirmed Privacy-Planning Outcomes

At a high level, `DATA_PRIVACY.md` confirms the following v1.0 privacy-planning outcomes:

* The account dataset remains intentionally minimal, with a known identity-assurance tradeoff.

* Uploaded academic content may contain information about classmates, teachers, authors, presenters, or other people.

* Uploader responsibility for reviewing submitted content does not replace the application's required controls or the institution's governance responsibilities.

* Role, ownership, current resource status, object-level permission, and `file_availability` operate as key application-level privacy and access controls.

* Notifications and accountability records must use minimized, purpose-limited content rather than duplicating full resource, report, file, or AI information.

* Optional external API-based AI creates the accepted external-service transmission boundary through which eligible resource content may leave the local/LAN environment.

* Pending-file AI processing requires:

  * successful basic upload validation;
  * clear uploader notice;
  * uploader acknowledgment.

* Missing or declined acknowledgment skips Pending-file AI processing without blocking ordinary upload or non-AI moderation.

* Acknowledgment alone is not treated by this document as establishing legal consent, proving authority to share, resolving third-party rights, or resolving every applicable lawful-basis question.

* AI output remains tied to one source resource and subject to current resource status, access rules, lifecycle state, source-file association, and output-surface eligibility.

* D040 minimizes Removed-resource information through the accepted lifecycle but does not anonymize the retained record.

* No formal institutional retention period is defined for the accepted data categories.

* Local/LAN deployment, legitimate downloads, printed or captured information, and manual operator-created copies retain known privacy and lifecycle limitations.

* Correct privacy behavior depends on implementation and testing of the accepted controls.

* Formal institutional governance and stronger production hardening remain outside the v1.0 application scope unless project scope is later changed.

### 15.5 Remaining Carry-Forward Work

The following items are routed next work.

They are not unresolved source conflicts in `DATA_PRIVACY.md`.

1. Complete a final whole-document consistency validation across `DATA_PRIVACY.md` Sections 1–15 and verify alignment with the accepted source documents.

2. Run the accepted `schema.sql` against a fresh database in the actual local XAMPP environment.

3. Confirm:

   * the actual database engine;
   * the actual database version;
   * CHECK-constraint support;
   * actual CHECK-constraint enforcement.

4. Fix only genuine SQL execution or compatibility problems found during verification.

   Do not redesign accepted decisions merely because another design is preferred.

5. Create `BUILD_PLAN.md` using:

   * the verified schema result;
   * accepted workflows;
   * accepted security requirements;
   * accepted privacy requirements;
   * Section 14 carry-forward requirements.

6. Create `AI_FEATURES.md` after the core build direction and privacy constraints are clear.

7. Create `TESTING_CHECKLIST.md` using:

   * accepted workflows;
   * `SECURITY_NOTES.md`;
   * `DATA_PRIVACY.md`;
   * database and schema constraints;
   * Section 14 verification requirements.

8. Begin implementation only after the accepted planning and environment-verification sequence is complete.

The routed implementation items include:

* exact first-Admin bootstrap;
* PDO versus mysqli selection;
* prepared-statement and database-access conventions;
* exact MIME/content validation;
* realistic testing of the 20 MB working upload limit;
* exact protected public/private folder structure;
* exact operational handling of `deleted` and `invalidated`;
* D040 database/filesystem ordering;
* cleanup-failure and retry handling;
* failed-upload log location and retention handling;
* selected AI provider and purpose-limited payload design;
* exact AI notice presentation;
* acknowledgment-reset behavior;
* AI output surface mapping;
* AI caching, retry, timeout, late-response, and concurrency behavior;
* complete lifecycle, access, privacy, schema, and compatibility testing.

These items are not failures of `DATA_PRIVACY.md`.

They are intentionally assigned to the later documents and verification stages that own their implementation details.

### 15.6 Revision History

| Version         | Date         | Change                                                                                                                                                                                                                                                                                                         | Status                                                                           |
| --------------- | ------------ | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------- |
| `v1.0-planning` | `2026-07-11` | Created and aligned `DATA_PRIVACY.md` Sections 1–15 with accepted sources through D040, including two-ledger privacy rules, AI notice and external-transmission boundaries, AI-output lifecycle privacy, D040 Removed-resource minimization, retention limitations, and downstream carry-forward requirements. | Complete for v1.0 planning — pending final whole-document consistency validation |

### 15.7 Final Boundary

`DATA_PRIVACY.md` is complete as a planning guardrail for BPC LearnShare v1.0 once the accepted sections and corrections are merged and the final consistency validation is completed.

The document does not replace the source documents from which it was derived.

Implementation must continue to follow:

* the accepted source documents;
* the verified schema baseline;
* later accepted implementation plans;
* later accepted AI planning;
* later accepted testing requirements.

Any future scope expansion or move toward a pilot, production, campus-scale, or public deployment requires explicit review of:

* privacy impact;
* security impact;
* user and permission impact;
* workflow impact;
* database and schema impact;
* AI feasibility and provider impact;
* implementation feasibility.

No limitation, tradeoff, recommendation, or carry-forward item in this document silently authorizes expansion of v1.0 scope.

---

*End of `DATA_PRIVACY.md`.*
