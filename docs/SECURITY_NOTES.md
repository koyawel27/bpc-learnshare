## 1. Purpose, Scope, and Relationship to Other Documents

### 1.1 What This Document Is

This document is the security reference for BPC LearnShare v1.0. It states the security controls that must be enforced, where they must be enforced, and what practical risks they reduce for a local machine or small campus LAN deployment demonstrated on XAMPP.

This document does not introduce new roles, resource statuses, report statuses, database tables, or workflow behavior. The four confirmed v1.0 roles (Student, Teacher/Instructor, Moderator, Admin), the nine confirmed resource statuses, the four report statuses, and the 18-table schema baseline (D033) remain unchanged. This document consolidates security-relevant rules already established in `USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`, and `DECISIONS.md`, and restates them in security-specific language so they are easier to apply correctly during implementation.

This document also resolves a small number of implementation-level security mechanics that earlier documents intentionally left open, including:

* password hashing, password verification, and the minimum password rule for v1.0;
* session ID regeneration behavior after login;
* session timeout and logout behavior;
* CSRF protection for state-changing requests;
* controlled serving of uploaded resource files based on resource status and `file_availability` (D034); and
* the practical boundary between PHP-side validation and database CHECK constraints (D037).

These are implementation-level security decisions. They describe how already-confirmed requirements must be enforced; they do not change what the system is allowed to do and must not require new tables, roles, resource statuses, or workflows unless a separate decision explicitly approves that change.

### 1.2 Why This Document Exists

The earlier planning documents already contain most of BPC LearnShare's security-relevant rules, but spread them across several files and frame them as workflow, permission, or database requirements rather than security requirements.

`USER_ROLES.md` defines who may perform which action, and states explicitly that every permission must be enforced server-side, not only hidden in the UI.

`WORKFLOWS.md` requires server-side permission checks on every protected action, including direct URL requests and manipulated form submissions. It also defines how authentication, authorization, resource access, upload, moderation, report handling, replacement, and AI-related workflows must behave.

`DATABASE_DESIGN.md` defines the constraints, transaction boundaries, and closed-set values needed to support the confirmed workflows. It also separates database-supported enforcement from application-required enforcement.

`DECISIONS.md` D034, D036, and D037 already anticipate several mechanics this document must handle: separating file availability from resource status, acknowledging that polymorphic notification and audit targets cannot be foreign-key enforced, and treating CHECK constraints as defense-in-depth only. PHP-side validation remains required even if the local MariaDB/MySQL version supports CHECK constraints. If the local database version ignores or does not enforce CHECK constraints, the application must still enforce the same rules correctly.

This document exists to gather those rules into one place, answer implementation-facing questions before coding starts, and resolve the remaining security mechanics that no earlier document owns, such as exact password hashing behavior, session handling, and CSRF token scope.

### 1.3 What This Document Translates, and What It Does Not Change

This document translates confirmed rules into security-framed requirements. It does not reopen or alter them.

Specifically, this document:

* **Does not introduce new roles, resource statuses, report statuses, or tables.** The four roles, nine resource statuses, four report statuses, and 18-table schema baseline (D033) remain exactly as confirmed.
* **Does not change any confirmed permission, workflow transition, or access rule.** Where this document restates a rule from `USER_ROLES.md`, `WORKFLOWS.md`, or `DATABASE_DESIGN.md`, it must match the original rule exactly.
* **Does not change the AI eligibility model.** Status-based AI eligibility (D014), the non-authoritative AI rule (D013, D015), and the notice-acknowledgment gate remain unchanged.
* **Does not change the `file_availability` model.** The three states (`available`, `deleted`, `invalidated`) and the dual-gate serving rule from D034 remain as confirmed.
* **Does not change the schema.** Any security control that would require a new column or table, such as a login-lockout counter, must be flagged as a scope question before being written into this document, not added silently.

If a proposed security control conflicts with a confirmed decision, the conflict must be flagged and resolved through the project's established process, not resolved by quietly treating this document as more authoritative.

### 1.4 What This Document Is Not

This document is not `DATA_PRIVACY.md`. It mentions privacy only where privacy directly affects security, such as keeping uploaded file contents, AI-generated content, passwords, password hashes, and API keys out of audit logs. Full Philippine Data Privacy Act (RA 10173) discussion, uploader notice wording, lawful basis, and AI API data-exposure privacy analysis belong to `DATA_PRIVACY.md`.

This document is also not a legal opinion, privacy compliance certification, or production security certification. It is a student-project security planning document for a local/LAN academic MVP. Any future real campus deployment would require additional institutional review, production hardening, and formal privacy/security procedures beyond this v1.0 document.

This document is not `BUILD_PLAN.md`. It does not assign implementation order, file names, PHP functions, routes, or SQL syntax. It states requirements that `BUILD_PLAN.md` must later sequence.

This document is not `TESTING_CHECKLIST.md`. It may identify behaviors that must later be verified, but it does not define full test cases or pass/fail scripts.

This document is not a production hardening plan. It does not require public internet deployment, mandatory HTTPS for the localhost/LAN demo, firewall configuration, intrusion detection, centralized monitoring, encryption-at-rest infrastructure, external security tooling, or enterprise incident-response procedures. Those concerns are deferred per D017 and are recorded here only as limitations, not expanded into v1.0 scope.

### 1.5 Scope of This Document

This document covers v1.0 only, for the confirmed local/LAN XAMPP deployment model.

It covers:

* authentication and session handling;
* password storage and handling;
* Admin-assisted password reset;
* first Admin bootstrap security;
* server-side authorization on every protected action, including live role, account-status, resource-status, and file-availability checks;
* direct URL and direct POST rejection for unauthorized actions;
* object- and view-level restriction, including draft AI output visibility and Moderator/Admin log visibility;
* input validation, SQL injection prevention, XSS prevention, and CSRF protection;
* file upload validation and controlled file serving;
* document-root and deployment-path protection;
* audit log and resource action history integrity;
* polymorphic target validation for `notifications` and `audit_log` (D036);
* AI-related security boundaries, including API key handling;
* concurrency and transaction safeguards for replacement, report, and moderation actions;
* known v1.0 security limitations and behaviors to verify later.

The security assumptions here are intentionally narrow. BPC LearnShare v1.0 is expected to run on a local machine or small campus LAN for capstone demonstration, not on the public internet. The realistic risks are missing server-side checks, incorrect status or availability gating, unsafe file handling, improper exposure of non-public resources or draft AI output, weak session handling, and misuse of legitimate role access — not nation-state threat actors or production-scale abuse.

### 1.6 Relationship to Other Planning Documents

`SECURITY_NOTES.md` does not override or take precedence over `PROJECT_BRIEF.md`, `DECISIONS.md`, `USER_ROLES.md`, `WORKFLOWS.md`, or `DATABASE_DESIGN.md`.

Every requirement in this document is either a security-framed restatement of an already-confirmed rule, or a narrowly scoped implementation-security decision explicitly assigned to this document because no earlier document owns it.

If a future review finds an apparent conflict, it must be flagged and resolved through the project's planning process, not silently resolved in this document's favor.

## 2. Security Design Principles and Threat Model

### 2.1 Purpose of This Section

This section defines the threat model that the rest of this document is written against. It exists so that later sections are not read as an arbitrary checklist. Every control in Sections 3 onward exists because of a specific, named risk in this section, and every control this document deliberately omits is omitted because the corresponding risk is explicitly out of scope for v1.0, not because it was overlooked.

### 2.2 Security Design Principles for v1.0

BPC LearnShare's security posture for v1.0 rests on a small number of principles applied consistently rather than exhaustively.

1. **Fail closed.** When the system cannot confirm an action is allowed — expired session, mismatched role, stale resource status, malformed request — the action is denied, not permitted by default. This applies equally to every security control in this document.

2. **Server-side enforcement is the only enforcement.** The interface may hide a button, but a hidden button is a usability decision, not an access control. Every permission check must be repeated at the point where the server actually performs the action.

3. **Session data is a convenience, not a source of truth for authorization.** The session identifies who is asking. It must not be treated as an authoritative record of what the user is currently allowed to do. Role and account status can change between login and any later request.

4. **Application logic is the primary enforcement layer; database constraints are a backstop.** Per D037, CHECK constraint support cannot be assumed for the local MariaDB/MySQL version until it is verified. PHP-side validation must independently enforce every rule this document describes, regardless of what the database does or does not catch.

5. **Least privilege per role.** Student, Teacher/Instructor, Moderator, and Admin each get exactly the authority defined in `USER_ROLES.md`. No role is granted a capability "just in case" it becomes useful later.

6. **Audit, do not pretend to fully prevent, legitimate-access misuse.** A Moderator or Admin who abuses correctly granted authority cannot be fully stopped by access control alone because the access was correctly granted. The control here is a reliable audit trail, not a technical barrier pretending to eliminate all insider misuse.

7. **Non-public resources must stay non-public through every access path.** Normal browse/search, direct links, bookmarks, notifications, and AI-assisted recommendations must all independently respect current resource status and file availability.

### 2.3 Realistic Threat Model for Local/LAN XAMPP Deployment

BPC LearnShare v1.0 is deployed on a local machine or small campus LAN for capstone demonstration. The realistic actor population is:

* registered Students and Teachers/Instructors using the system as intended but occasionally making mistakes or testing boundaries out of curiosity;
* Moderator/Admin users who have legitimate elevated access;
* testers, classmates, panel members, or LAN users attempting to probe the system during development, testing, or defense.

This is not a public-internet-facing system defending against organized external attackers, botnets, or nation-state actors. The threat model is scoped accordingly.

### 2.4 Threats Considered In Scope for v1.0

The following risks are realistic for BPC LearnShare v1.0 and must be addressed by this document:

* **Missing or incorrect server-side permission checks** — an action that should be role-gated is only hidden in the UI, or a check exists on the main path but not on an alternate route to the same action.

* **Session fixation or hijacking**, particularly relevant on shared lab/LAN machines where a session ID could be reused or observed.

* **Direct URL, direct POST, or manipulated form/API submission** that bypasses the intended UI path, such as a Moderator account submitting a raw upload request or a Student requesting a moderation-decision endpoint directly.

* **IDOR-style access mistakes** — guessing or incrementing a resource, notification, bookmark, report, or audit identifier to reach data the user should not access.

* **Unsafe file upload handling** — disallowed file types, disguised extensions, oversized or corrupt files, or a file that reaches storage before validation completes.

* **Serving a file that resource status or `file_availability` should currently block** — the most direct route to exposing Pending, Needs Correction, Rejected, Withdrawn, Hidden, Restricted, Removed, or Replaced content.

* **Premature or unauthorized AI processing** — a file reaching the AI pipeline before basic upload validation, before notice acknowledgment, or while in an ineligible status.

* **Draft AI output leaking beyond its intended audience** — draft summaries, tag suggestions, metadata suggestions, duplicate flags, or moderation hints on a Pending resource becoming visible to general authenticated users instead of only the uploader and Moderator/Admin.

* **SQL injection** through user-supplied input reaching a query without prepared statements.

* **XSS** through unescaped output of user-supplied text, such as resource titles, descriptions, topics, report comments, moderation notes, or notification text.

* **CSRF** on any state-changing action, including upload, moderation decision, hide/restrict/remove, report submission, report action, account management, taxonomy management, and system-setting changes.

* **Exposed credentials or API keys** — AI API keys or database credentials committed to version control, hardcoded in client-side code, or reachable through a misconfigured document root.

* **Misuse of legitimately granted authority** — an authorized Moderator or Admin acting outside expected use, whether by mistake or intent.

* **Race conditions on concurrent state-changing actions** — two staff members acting on the same Pending resource, two replacement submissions for the same original resource, or a report resolving while another action is still in progress.

### 2.5 Threats Explicitly Deferred for v1.0

The following are real security concerns in general, but they are not v1.0 requirements for a local/LAN academic MVP. They are recorded here as accepted deferrals, not oversights.

* Public internet-facing attacks, including DDoS, credential stuffing at scale, and botnet traffic.
* Mandatory HTTPS/TLS for the localhost/LAN-only demo.
* Firewall configuration or network segmentation.
* SIEM, centralized monitoring, or intrusion detection.
* Encryption-at-rest infrastructure.
* Enterprise incident-response processes.
* Nation-state or advanced-persistent-threat modeling.
* Malware/antivirus scanning of uploaded files beyond the type, extension, and content validation already required by the upload workflow.
* Login brute-force lockout, CAPTCHA, two-factor authentication, and persistent login-attempt tracking. These are reasonable future hardening items, but they are not part of the accepted v1.0 schema or workflow baseline.
* Load-based or performance-oriented denial-of-service resistance.

### 2.6 How This Threat Model Shapes the Rest of This Document

Because public-internet-scale threats are out of scope, most of this document is about correctness of server-side logic rather than network-perimeter hardening.

The priority is making sure every permission, status, and availability check already required by `USER_ROLES.md`, `WORKFLOWS.md`, `DATABASE_DESIGN.md`, and `DECISIONS.md` is actually enforced at every access path, not only the expected one.

Sections 3 and 4 establish the authentication and authorization baseline. Later sections apply the same fail-closed, server-side-only posture to file handling, AI processing, audit logging, and concurrency.

---

## 3. Authentication and Session Security

### 3.1 Login Workflow and Server-Side Checks

Login follows the confirmed workflow: the system checks that the submitted identifier matches an account, verifies the submitted password against the stored password hash, and confirms that the account status is Active before creating a session.

All login checks happen server-side. None may be skipped because a client-side form already looked valid.

### 3.2 Password Hashing and Storage

Passwords are stored only as hashes produced by PHP's native `password_hash()` function and verified using `password_verify()`.

No plaintext password, reversible-encrypted password, or password hint is ever stored. This applies uniformly to Student self-registration and Admin-provisioned Teacher/Instructor, Moderator, and Admin accounts.

### 3.3 Minimum Password Rule for v1.0

The upload and registration workflows require a minimum password requirement but do not define the exact value. This document resolves that implementation-level security detail.

For v1.0, the minimum password length is **8 characters**, with no forced composition rules such as mandatory uppercase letters, numbers, or symbols.

This is a practical baseline appropriate for a student-facing academic MVP. It avoids very weak passwords while keeping registration simple. Stronger password rules may be considered in a future pilot or production version.

### 3.4 Generic Login Failure Messages

An invalid identifier, an incorrect password, and a Disabled account must all produce the same generic login failure message.

The system must not reveal which condition caused the failure. This reduces account enumeration and prevents a visitor from learning whether a given username exists or whether an account has been disabled.

The same non-disclosure principle applies to Student registration. A duplicate-identifier rejection must not reveal whether the existing account is a Student, Teacher/Instructor, Moderator, or Admin account.

### 3.5 Session Creation After Successful Login

A session is created only after the login checks pass. The session must be tied to the authenticated account's identity, such as the account ID, not to a client-supplied value.

No database-backed session table is required for v1.0. PHP's native server-side session handling is sufficient for the confirmed local/LAN MVP scope.

Authorization-relevant fields must not be trusted from the session as static values. The session may cache the account ID and may use role information for UI routing convenience, but role and account status must be re-verified from the database on protected actions.

This is necessary because role changes and account disabling must affect permissions immediately at the application level. A stale role value cached at login time must not continue to authorize actions after the account's role or status changes.

### 3.6 Session ID Regeneration After Login

The session identifier must be regenerated immediately after successful authentication, before authenticated content is served.

This prevents session fixation, where an attacker who fixes or knows a pre-login session ID could hijack the session after the victim logs in.

### 3.7 Session Timeout and Inactivity Handling

Sessions expire after inactivity to reduce risk from unattended logged-in devices.

For v1.0, the idle timeout is **30 minutes of inactivity**. After 30 minutes without a request, the session is treated as expired:

1. the user is treated as unauthenticated;
2. protected requests are rejected;
3. the user is redirected to the login page, or receives an access-denied response for AJAX/API requests.

This value is a practical default for a shared-lab or small-LAN academic environment. It balances usability for moderators and admins with the risk of unattended sessions on shared devices.

### 3.8 Logout and Session Destruction

Logout destroys the session server-side and redirects the user to the login page.

A destroyed session ID must not remain valid for later protected requests. Clearing only client-side interface state is not enough.

### 3.9 First Admin Bootstrap Security

The first Admin account is created during initial setup only, outside the normal in-app account provisioning workflow. This may be done through a one-time database seed or manual setup step during local XAMPP setup.

The first Admin setup must follow these rules:

1. There must be no public "create first Admin" registration page.
2. There must be no permanently reachable setup endpoint for creating Admin accounts.
3. If a setup script or seed file is used, it must not remain deployed as a live request-accessible page after setup is complete.
4. If a placeholder, seed, or default password is used during setup, it must be changed before the system is used for demonstration or defense.
5. After the first Admin account exists, every subsequent Teacher/Instructor, Moderator, and Admin account must go through the normal Admin-provisioning workflow.

Exact seed mechanics belong in `BUILD_PLAN.md`. The security rule is that no default or public Admin-creation path survives into normal use.

### 3.10 Admin-Assisted Password Reset Only

BPC LearnShare v1.0 has no self-service password recovery.

Password reset for ordinary accounts is Admin-assisted. An Admin may reset the password of a Student, Teacher/Instructor, Moderator, or another Admin through account management, subject to role and audit rules.

The following are not part of v1.0:

* password-reset-token table;
* expiring reset-token mechanism;
* email-based reset flow;
* public "forgot password" workflow.

Admin-assisted password reset only requires updating the account's password hash and recording the administrative action in the audit log.

First-Admin recovery, if the credential is lost, is a local database/setup maintenance concern. It belongs in `BUILD_PLAN.md`, not in the runtime security model.

### 3.11 Known v1.0 Limitations — Authentication and Sessions

The following are accepted v1.0 limitations:

* **No login-attempt lockout, rate limiting, or CAPTCHA.** The accepted `accounts` table has no failed-attempt counter or lockout column. Adding persistent lockout tracking would be a schema/workflow change and must be raised as a scope question before implementation.
* **No two-factor authentication.**
* **No email verification** for Student self-registration.
* **No self-service password recovery.**
* **No admin-visible active-session list and no forced-session-termination feature.** If an account is Disabled or its role is changed while a session is already open, the next protected request must fail or be re-authorized according to the current account status and role. However, v1.0 does not include a separate tool for viewing or forcibly ending active sessions.
* **Session security relies on PHP's native session handling**, not a custom or database-backed session store. This is sufficient for the confirmed v1.0 scope, but lighter than a production deployment would typically use.

---

## 4. Authorization and Server-Side Enforcement

### 4.1 Server-Side Authorization as the Primary Enforcement Rule

Every permission defined in `USER_ROLES.md` must be enforced at the point the server actually performs the corresponding action.

This applies to:

* page rendering;
* form submission;
* AJAX/API endpoints;
* file serving;
* upload processing;
* moderation decisions;
* report handling;
* account management;
* taxonomy management;
* system-setting changes;
* AI-related operations.

If a permission check exists only in the interface, it is not a security control.

### 4.2 Why UI Hiding Is Not a Security Boundary

Hiding a button, menu item, or page link from a role that should not use it is a usability improvement, not access control.

A hidden "Approve" button does not stop a Student from submitting the underlying request directly. Every server-side handler must independently verify that the requesting account's current role and status permit the action, regardless of what the interface displayed.

### 4.3 Live Role and Account-Status Checks

For every protected action, the system must confirm from the database that:

1. the account still exists;
2. the account status is currently Active;
3. the account's current role permits the requested action.

This prevents stale session data from authorizing actions after an Admin disables an account or changes its role.

The session may identify the account, but the current account status and role must be checked again for authorization-sensitive actions.

### 4.4 Live Resource-Status Checks

Every action or access request involving a specific resource must re-check that resource's current status at the moment of the request.

This applies to:

* browse/search;
* view/download;
* bookmark and Helpful actions;
* report submission;
* moderation decisions;
* withdrawal;
* replacement submission;
* replacement approval;
* direct Moderator/Admin action.

Only Approved resources are broadly available to authenticated active users. Pending, Needs Correction, Rejected, Withdrawn, Hidden, Restricted, Removed, and Replaced resources must not be served to general authenticated users through normal browse/search, direct links, old bookmarks, or previously rendered pages.

If the resource is no longer in the state the request assumes, the action fails cleanly rather than proceeding on stale information.

### 4.5 Live File-Availability Checks for File Serving

A stored file may be served only when both of the following are true at request time:

1. the current resource status and permission rules allow access; and
2. `file_availability = 'available'`.

Neither check substitutes for the other.

A resource that is Approved but whose file is marked `deleted` or `invalidated` must not have its file served. A file marked `available` must still not be served if the resource status or user permission blocks access.

File-serving logic must check both gates on every request.

### 4.6 Direct URL, Direct POST, and Manipulated Form Rejection

Every role- or status-gated action must reject a disallowed request regardless of how it arrives.

This includes requests submitted through:

* the expected UI path;
* a manually typed URL;
* a modified form submission;
* a direct POST request;
* an AJAX/API call.

For example, a Moderator or Admin attempting to upload as an ordinary contributor must be rejected server-side even if they craft the request manually. A Student attempting to call a moderation endpoint directly must also be rejected server-side.

### 4.7 Fail-Closed Behavior

When the system cannot confirm that an action is currently allowed, the action must be denied.

Examples include:

* expired session;
* missing account;
* Disabled account;
* role mismatch;
* ownership mismatch;
* stale resource status;
* unavailable file;
* malformed request;
* missing CSRF token;
* concurrency conflict.

The system must never default to allowing an action because a check could not be completed.

### 4.8 Upload-Role Enforcement

Only Student and Teacher/Instructor accounts may initiate ordinary resource uploads.

Moderator and Admin accounts must not upload as contributors in v1.0. Their role is to review, manage, restrict, remove, configure, and administer resources submitted by Student and Teacher/Instructor accounts.

This rule cannot be fully enforced by a foreign key or ordinary database constraint. A foreign key can confirm that the uploader account exists, but it does not prove that the uploader has the correct role.

Therefore, upload-role enforcement must happen in PHP application logic before any resource record is created. This applies to:

* first-time upload;
* resubmission after Needs Correction;
* linked replacement submission for an Approved resource.

If the current account role is not Student or Teacher/Instructor, the upload request must fail before the file is accepted as a resource record.

### 4.9 Moderator/Admin Authority Separation

Moderator and Admin remain distinct roles with different authority.

A Moderator may:

* review Pending resources;
* Approve, Reject, or Request Correction;
* Hide or Restrict an Approved resource;
* review and act on reports;
* escalate reports to Admin;
* view moderation- and report-related log entries.

An Admin may perform Moderator actions and also:

* Remove a resource or delete/invalidated its stored file when necessary;
* manage user accounts;
* change roles;
* disable or re-enable accounts;
* manage courses/programs, subjects, year levels, resource types, and controlled tags;
* manage system settings, including AI enable/disable;
* view the full audit log.

A request for an Admin-only action must be rejected server-side if the acting account is only a Moderator. This boundary is per action, not merely per page.

### 4.10 Application-Layer Validation Is Mandatory Regardless of Database Enforcement

The local MariaDB/MySQL version's CHECK-constraint support has not yet been confirmed. Even if CHECK constraints are supported, they are defense-in-depth only.

Every rule described in this section must work correctly in PHP even if database CHECK constraints are ignored.

This is especially important because several critical rules have no practical CHECK-constraint equivalent, including:

* only Student and Teacher/Instructor accounts may upload;
* only Moderator/Admin can approve, reject, request correction, hide, or restrict;
* only Admin can remove resources;
* resource status must be re-checked before a state change;
* file availability and resource status must both be checked before file serving;
* notifications and audit-log polymorphic targets must be validated by the application.

Database constraints help catch mistakes, but they do not replace server-side authorization and validation.

### 4.11 Known v1.0 Limitations — Authorization

The following are accepted v1.0 limitations:

* **No centralized authorization middleware or major framework.** Because v1.0 uses native PHP with no Laravel or major framework, role/status/availability checks must be applied consistently on every protected script or endpoint. This is a real implementation risk and should be included in `TESTING_CHECKLIST.md`.
* **No rate limiting on repeated unauthorized attempts.** Direct URL probing and repeated blocked actions should fail safely, but rate limiting is deferred.
* **No automated alerting for authorization-bypass attempts.** A blocked action fails for the requester, but does not automatically create a real-time alert.
* **Role and status re-verification adds database reads.** This is an accepted performance/security tradeoff for v1.0's local/LAN scale.

## 5. Object- and View-Level Restriction

### 5.1 Purpose of This Section

Section 4 establishes that role, account status, resource status, and file availability must be checked live, server-side, on every request. This section is narrower: it states which specific objects and content types must stay restricted to specific audiences even when the requester is otherwise authenticated and active, and it flags the access paths most likely to leak them by accident.

This section does not repeat the full resource-status lifecycle already defined in `WORKFLOWS.md`. It assumes that document as given and focuses only on the security-relevant visibility boundary each status implies.

### 5.2 Resource Visibility by Status — Security Summary

Only resources with status **Approved** are returned by normal browse/search and are servable to general authenticated active users.

Resources with status **Pending**, **Needs Correction**, **Rejected**, **Withdrawn**, **Hidden**, **Restricted**, or **Replaced** are not broadly visible. They are restricted to the uploader, Moderator, and Admin according to the confirmed workflow rules.

**Removed** is stricter and is handled separately in Section 5.3.

This document does not restate the meaning of every status. It states the enforcement requirement: any endpoint capable of returning resource data or serving a resource file must apply the current visibility rule independently. The system must not rely on the assumption that the resource "should not normally be reachable" through the main browse UI.

### 5.3 Removed Resources — No User Access, Including the Uploader

Removed resources are not visible to normal users, including the original uploader. Only Admin may access the remaining accountability/reference data.

Per D040, Removed means both **access restriction** and **content minimization**. At removal time:

* `title` becomes `[Removed resource]`;
* `description` becomes `[Removed resource]`;
* `topic` becomes `[Removed]`;
* `original_filename` becomes `[removed]`;
* associated `resource_tags` rows are deleted;
* file content becomes unavailable through the confirmed `file_availability` lifecycle;
* AI outputs are deleted or invalidated.

The resource row and accepted historical relationships remain so reports, resource action history, bookmarks, Helpful marks, replacement relationships, and other accountability records do not point to a missing resource.

The retained record is minimized within the accepted v1.0 schema but is not anonymized. It may still contain an uploader reference, required taxonomy references, technical file metadata, activity counts, AI-notice acknowledgment fields, timestamps, and historical relationships. The Admin-only access restriction therefore remains necessary after sanitization.

Any endpoint that shows a user their own upload history must explicitly exclude Removed resources. It must not rely only on the general non-Approved restriction.

### 5.4 Draft AI Output Visibility

AI output generated for a Pending resource is draft output.

Draft AI output is visible only to:

* the uploader, for their own Pending resource; and
* Moderator/Admin, for review assistance.

It must not be visible to general authenticated users, must not appear in any public listing, and must not be returned by a general-purpose resource-detail endpoint unless the requester is allowed to view that draft output.

This is a distinct check from the resource-status check in Section 5.2. A Pending resource being correctly hidden from browse/search does not automatically mean its attached draft AI output is hidden from a user who reaches an AI-suggestion endpoint, resource-detail endpoint, or related internal route through a direct request.

### 5.5 AI Output Must Not Leak When the Source Resource Is Ineligible

AI outputs must follow the eligibility and visibility of their source resource. Once a resource becomes ineligible for public AI use, its AI output must stop appearing in public-facing surfaces, including:

* summaries;
* related-resource recommendations;
* duplicate/similar-resource hints shown outside staff review;
* content-based search;
* optional AI inquiry/chat results, if that stretch feature is ever implemented.

The practical rules are:

* **Hidden/Restricted:** AI output is excluded from recommendations and search, and remains visible only to the uploader, Moderator, and Admin according to the resource visibility rules.
* **Removed:** AI output must be deleted or invalidated and must not remain searchable, visible, or usable anywhere. Only minimal audit/reference data may remain if required for accountability.
* **Replaced:** AI output from the original resource is excluded from public-facing views and recommendations. The replacement resource never inherits it and must generate or store its own AI output if eligible.

The practical risk is stale derived data. A recommendation list, search index, or AI-output query built while a resource was Approved must not continue exposing the old output after the source resource becomes Hidden, Restricted, Removed, Replaced, or otherwise ineligible.

### 5.6 Moderator vs. Admin Log Visibility

Moderator accounts may view moderation- and report-related history only.

Full system audit logs are Admin-only. This includes logs for account creation, account disabling, role changes, system-setting changes, and other administrative events.

Any log-viewing endpoint must enforce this split server-side. Omitting an "Admin log" menu item from the Moderator interface is not enough.

### 5.7 Notifications, Bookmarks, and Old Links Must Not Bypass Status or Availability Checks

Notifications, bookmarks, and old direct links are pointers only. They must never bypass current resource status or `file_availability` checks.

If a notification links to a resource, report, queue, or action-history page, the destination endpoint must still perform the normal permission and status checks before showing anything.

If a bookmarked resource later becomes Hidden, Restricted, Removed, or Replaced, the bookmark relationship may remain in the database for history or display purposes, but the file must not be served through it. The user should receive an unavailable, restricted, or replaced-resource response rather than the underlying file content.

### 5.8 Known v1.0 Limitations — Object- and View-Level Restriction

* There is no per-field redaction layer for BPC LearnShare v1.0 comparable to a role-tiered restricted-field model. The restriction boundary is mostly status-driven and audience-driven, not field-by-field within an otherwise visible record.
* AI-output visibility depends on every AI-facing endpoint applying the same status and lifecycle rules. There is no database-only rule that automatically guarantees correct public AI-output filtering. This must be verified later in `TESTING_CHECKLIST.md`.

---

## 6. Input Validation and Injection Prevention

### 6.1 Server-Side Validation Is Mandatory for All Input

Every value submitted to the system must be validated server-side before it is used in a query, written to the database, or reflected in output.

This includes:

* form fields;
* query parameters;
* uploaded file metadata;
* AJAX/API payloads;
* resource IDs;
* report IDs;
* notification IDs;
* account IDs;
* taxonomy IDs;
* system-setting values.

Client-side validation, HTML5 field constraints, and JavaScript checks are usability aids only. They do not satisfy any security requirement in this document and must never be the only checks performed.

### 6.2 Required-Field, Length, and Format Checks

Every required field defined by the confirmed workflows must be checked for presence before an action proceeds.

At minimum, this includes:

* title, description, topic, course, subject, year level, and resource type on upload;
* username, password, and display name on registration or account creation;
* reason category on report submission;
* note/reason on actions that require one, such as Reject, Request Correction, Hide, Restrict, Remove, and report resolution where required.

Length limits must be enforced in application code to match the column definitions in `schema.sql`. For example:

* `username` must not exceed the schema length;
* `title` must not exceed the schema length;
* `topic` must not exceed the schema length;
* display names, taxonomy names, and stored labels must respect their schema-defined lengths.

The goal is to reject invalid submissions with clear validation messages before they cause database errors, silent truncation, or inconsistent data.

### 6.3 Closed-Set Validation

Every field with a fixed set of allowed values in `schema.sql` must be validated against that exact list in PHP before the value is written. This is required regardless of whether the database's own ENUM or CHECK enforcement is active.

The closed sets requiring application-side validation include:

| Field                                                    | Allowed values                                                                                                                                       |
| -------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| `accounts.role`                                          | `student`, `teacher_instructor`, `moderator`, `admin`                                                                                                |
| `accounts.account_status`                                | `active`, `disabled`                                                                                                                                 |
| `resources.status`                                       | `pending`, `needs_correction`, `approved`, `rejected`, `withdrawn`, `hidden`, `restricted`, `removed`, `replaced`                                    |
| `resources.file_type`                                    | `pdf`, `docx`, `pptx`, `txt`, `jpg`, `png`                                                                                                           |
| `resources.file_availability`                            | `available`, `deleted`, `invalidated`                                                                                                                |
| `reports.reason`                                         | `outdated`, `incorrect_or_inaccurate`, `inappropriate`, `duplicate_or_near_duplicate`, `suspected_leaked_exam`, `copyright_or_unauthorized`, `other` |
| `reports.status`                                         | `open`, `dismissed`, `actioned`, `escalated`                                                                                                         |
| `resource_action_history.action_type`                    | `approve`, `reject`, `request_correction`, `resubmit`, `hide`, `restrict`, `remove`, `withdraw`, `return_to_approved`, `replaced`                    |
| `resource_action_history.status_before` / `status_after` | same allowed values as `resources.status`                                                                                                            |
| `ai_outputs.output_type`                                 | `summary`, `suggested_tags`, `suggested_metadata`, `duplicate_flag`, `moderation_hint`, `related_recommendation`                                     |
| `ai_outputs.lifecycle_state`                             | `draft`, `retained`, `invalidated`                                                                                                                   |
| `notifications.target_type`                              | `resource`, `report`, `action_history`, `moderation_queue`, `report_queue`                                                                           |
| `audit_log.action_type` | `account_created`, `account_disabled`, `account_reenabled`, `role_changed`, `password_reset`, `setting_changed`, `taxonomy_created`, `taxonomy_updated`, `taxonomy_deactivated`, `taxonomy_reactivated` |
| `audit_log.target_type` | `account`, `system_setting`, `course`, `subject`, `year_level`, `resource_type`, `tag` |

A value outside the allowed set must be rejected before it reaches any query. It must not be passed through and left for the database to reject or coerce.

### 6.4 SQL Injection Prevention Through Prepared Statements

Every SQL query that includes any user-supplied or request-derived value must use a parameterized prepared statement, using PDO or mysqli.

No user input may be concatenated directly into a SQL string. This includes values that appear to come from a controlled list, such as role, status, reason category, or file type. Closed-set values must still be validated and then bound as parameters.

Prepared statements are required for:

* login;
* registration;
* account management;
* resource upload and metadata editing;
* search/filtering;
* view/download lookup;
* bookmark and Helpful actions;
* report submission and review;
* moderation actions;
* taxonomy management;
* system settings;
* audit and action-history writes;
* AI-output reads/writes.

### 6.5 XSS Prevention Through Output Escaping

All text rendered into HTML must be escaped at output time.

This includes:

* resource titles;
* descriptions;
* topics;
* display names;
* report comments;
* moderation notes;
* notification text;
* taxonomy names;
* AI-generated summaries and suggestions.

Output escaping is required even if the value was already validated on input. Input validation reduces invalid data, but it is not the primary XSS defense.

AI-generated content must be escaped the same way as user-submitted content. It is not safe just because it came from an AI service rather than a human user.

### 6.6 CSRF Protection for State-Changing Requests

A CSRF token must be required and validated on every state-changing request.

This includes:

* Student registration;
* login and logout where implemented as POST actions;
* upload;
* resubmission;
* withdrawal;
* moderation decisions;
* direct Hide/Restrict/Remove actions;
* report submission;
* report actions;
* bookmark and Helpful toggles;
* account creation;
* account disabling/re-enabling;
* role changes;
* Admin-assisted password reset;
* taxonomy add/edit/deactivate/reactivate;
* system-setting changes;
* AI enable/disable changes.

Read-only requests such as browse, search, and viewing a resource detail page do not require a CSRF token, but they still require authentication and authorization where applicable.

This document resolves the token model as a **single session-scoped CSRF token**, generated for the session and required on every state-changing POST request. This is simpler than a per-form or per-request token model and is acceptable for the confirmed v1.0 local/LAN threat model.

### 6.7 Existence and Ownership Validation Before Acting on an ID

Any action that accepts an identifier must confirm that the referenced record exists and that the requester has the required relationship to it before performing the action.

Examples:

* **Withdraw** requires that the acting account is the resource's uploader of record.
* **Report submission** requires that the resource exists, is currently Approved, and the reporter is not the resource uploader.
* **Replacement submission** requires that the acting account is the original resource's uploader and that the original resource is currently Approved.
* **Bookmark and Helpful actions** require that the resource exists and is currently Approved.
* **Notification links** require that the target exists and the user is allowed to view the target.
* **Audit-log and notification target IDs** require confirming that the referenced object exists and matches the stated `target_type`.

An ID that resolves to no record, or to a record the requester has no valid relationship with, must fail safely with no partial action taken.

### 6.8 File Size and Metadata Values Must Come From the Server

File metadata used for security decisions must come from server-side inspection, not from client-submitted form values.

At minimum:

* actual file size must come from the uploaded file metadata received by PHP;
* MIME/content validation must come from server-side inspection;
* stored file type must come from the validated file, not from a hidden form field;
* stored filename must be generated by the server, not supplied by the user.

A client-provided filename, extension, MIME type, or size value may be displayed or used as a hint only after validation. It must not be trusted as the source of truth.

### 6.9 What Must Never Reach Logs or Storage Unfiltered

Validation and logging code must prevent the following from being written to logs or unrelated tables in readable form:

* plaintext passwords;
* temporary passwords after use;
* AI API keys;
* database credentials;
* full uploaded file contents;
* full AI-generated output content inside `audit_log` or `resource_action_history`;
* raw session IDs;
* CSRF tokens.

Action-history and audit entries record that an action happened and preserve a short reason or note where required. They are not a copy of the uploaded file, AI output, password, token, or API configuration involved.

### 6.10 Known v1.0 Limitations — Input Validation

The following are accepted v1.0 limitations:

* Validation logic is implemented in native PHP without a major framework or schema-validation library. Consistency across every form and endpoint is an implementation discipline requirement.
* CSRF protection uses one session-scoped token rather than rotating per-request tokens. This is a practical simplification for v1.0, not a production-grade CSRF design.
* Database ENUM and CHECK constraints are treated as defense-in-depth only. PHP validation remains mandatory.

---

## 7. File Upload Security

### 7.1 Allowed and Disallowed File Types

The v1.0 allowed file types are exactly:

* PDF;
* DOCX;
* PPTX;
* TXT;
* JPG/JPEG;
* PNG.

The schema stores JPG and JPEG under the single `jpg` file type value. Validation must accept both `.jpg` and `.jpeg` extensions and map them to the stored `jpg` value.

Executable, script, installer, archive, and web-executable formats are not allowed. Examples include:

* EXE;
* BAT;
* CMD;
* JS;
* PHP;
* HTML;
* ZIP;
* RAR;
* 7Z;
* APK.

This is an allowlist model, not a denylist model. Only the accepted academic file types pass validation. Everything else is rejected.

### 7.2 Required Validation Sequence

Every upload must pass all required validation before a resource record is created and before any AI processing may occur.

This applies to:

* first-time upload;
* resubmission after Needs Correction;
* linked replacement submission for an Approved resource.

Required validation steps:

1. **Extension check** — the extension must be in the allowed list.
2. **MIME/content validation** — the actual file content must match the claimed extension.
3. **File size check** — the actual uploaded file size must not exceed the configured maximum.
4. **Empty/corrupt file rejection** — zero-byte files and files that cannot be safely read must be rejected.

Any failure rejects the upload. No resource record is created, and no file is moved into permanent protected storage.

### 7.3 File Size Limit

For v1.0, the working default maximum is **20 MB per file**.

This is an implementation-level security default, not a schema change and not an Admin-managed setting. It should be implemented as a server-side configuration value or constant.

The limit is intended to balance realistic academic files, such as image-heavy presentations and scanned handouts, against local storage limits and abusive oversized uploads.

This value must be tested against realistic sample files before final defense. If testing shows that 20 MB is too low for common legitimate academic resources, `BUILD_PLAN.md` may adjust the implementation value, but the change should be recorded back into `SECURITY_NOTES.md` rather than changed silently during coding.

### 7.4 DOCX/PPTX Are ZIP-Based — Handling Without Allowing Archives

DOCX and PPTX files are internally ZIP-based Office Open XML formats.

Validation must recognize legitimate DOCX/PPTX structure while still rejecting general ZIP, RAR, and 7Z uploads. The system must not treat "some kind of ZIP file" as enough to accept a file as DOCX or PPTX.

A legitimate DOCX/PPTX should have expected Office Open XML structure, such as `[Content_Types].xml` and expected internal folders. The exact validation method belongs in `BUILD_PLAN.md`, but the security rule is clear: DOCX/PPTX may be accepted only as valid Office document formats, not as a loophole for arbitrary archive uploads.

### 7.5 Safe Storage: Filename, Path, and Web-Root Placement

The original filename is stored for display only. It must never be used as the physical storage path.

Every accepted file must be stored using a randomized, non-guessable generated filename.

Uploaded files must be stored either:

* outside the public web root; or
* in a location protected from direct unauthenticated web access.

The storage path must not be predictable from the original filename, resource title, uploader name, or resource ID.

### 7.6 No Direct Static Serving — Controlled File Access Only

Uploaded files must never be reachable through a static, directly browsable URL.

Every file access must go through an application-controlled file-serving endpoint that performs:

1. session/authentication check;
2. account status check;
3. role check where the resource's current status requires staff/uploader access;
4. live resource-status check;
5. live `file_availability` check;
6. final file existence check before streaming.

A correctly restricted database record does not protect the file if the physical file is reachable directly through the web server.

### 7.7 Failed Validation Does Not Create a Resource Record

If a file fails any required validation check, the system rejects the upload attempt before creating a `resources` row.

The `resources` table represents accepted, validated uploads only. A failed validation attempt must not create a Pending resource, a draft resource, or a partially stored resource record.

Temporary upload data created by PHP during request handling must not be treated as accepted storage.

### 7.8 Minimal Logging of Risky Validation Failures

Ordinary validation failures, such as a missing metadata field, do not need to be logged as security events.

Risky upload failures should be logged minimally. Examples include:

* attempted executable/script upload;
* MIME/content mismatch;
* repeated rejected uploads from the same account;
* unusually large file attempts;
* suspicious filenames or extensions.

The log entry should store only minimal accountability data, such as:

* actor account ID, if logged in;
* timestamp;
* failure category;
* attempted extension;
* approximate size category if useful.

The rejected file itself must never be stored.

### 7.9 File Replacement and Orphaned File Cleanup

When a Pending or Needs Correction resource has its file replaced during correction, the previous physical file should be deleted or invalidated once the new file is safely stored.

An old file left reachable after replacement is an orphaned object outside the resource-status and file-availability model. It must not remain servable.

If cleanup fails, the system must still prevent the old file from being served through the application and should record the cleanup issue for developer/admin review.

### 7.10 Malware Scanning Is Deferred

Malware or antivirus scanning of uploaded files is not a v1.0 requirement.

The accepted v1.0 controls are:

* extension allowlist;
* MIME/content validation;
* file size limit;
* empty/corrupt file rejection;
* protected storage;
* controlled file serving.

Deeper content inspection, malware scanning, or antivirus integration is deferred production hardening and must not be added as a mandatory v1.0 requirement.

### 7.11 Known v1.0 Limitations — File Upload

The following are accepted v1.0 limitations:

* No malware or antivirus scanning.
* No content-hash-based duplicate detection at the storage layer.
* MIME/content validation depends on the PHP mechanism or library chosen during implementation, such as `finfo` or a narrowly justified helper library. The exact mechanism belongs in `BUILD_PLAN.md`.
* The 20 MB size limit is a working default and still needs testing against realistic academic files.
* Image-only resources may not be searchable by extracted text unless OCR or AI vision support is added later.

## 8. Document Root and Deployment Path Protection

### 8.1 Local/LAN XAMPP Deployment Assumptions

BPC LearnShare v1.0 runs on XAMPP on Windows for local machine or LAN demonstration.

Apache serves whatever is placed under its configured document root. If the document root is too broad, files that were never meant to be requested directly may become reachable through a browser. The risk this section addresses is not sophisticated intrusion; it is a misconfigured or overly permissive local/LAN deployment exposing configuration files, internal PHP files, setup scripts, or uploaded resources.

### 8.2 Minimal, Intentional Browser-Accessible Surface

No source document has fixed the final BPC LearnShare folder layout yet. That belongs to `BUILD_PLAN.md`.

This document defines the security requirement the layout must satisfy: **the set of files reachable by direct browser request must be small and deliberate**.

Browser-accessible files should be limited to:

* application entry-point scripts;
* public CSS files;
* public client-side JavaScript files;
* public images or assets that are intentionally safe to expose.

Everything else must stay outside the browser-accessible surface, including:

* PHP include files;
* internal application logic;
* configuration files;
* database credentials;
* AI API keys;
* uploaded resource files;
* setup or seed scripts.

The recommended mechanism for a native PHP project is a `public/`-style document root, where Apache points only to the public folder while application code, configuration, and storage remain in sibling directories that Apache does not serve directly.

`BUILD_PLAN.md` should adopt this pattern, or an equivalent pattern, deliberately.

### 8.3 Uploaded Files Must Not Be Statically Reachable

Uploaded resource files must not sit inside the browser-accessible document root as static, directly linkable files.

Every file access must pass through the controlled file-serving endpoint described earlier in this document. That endpoint must perform:

* session/authentication check;
* account status check;
* role check where needed;
* live resource-status check;
* live `file_availability` check;
* final file existence check before streaming.

If uploaded files are placed under the public document root as a shortcut, every status and availability restriction becomes bypassable by requesting the stored filename directly.

### 8.4 Configuration, Credentials, and Internal Scripts Must Not Be Browser-Accessible

The following must never be reachable by direct browser request:

* database connection configuration;
* database credentials;
* AI API keys;
* AI-provider configuration;
* PHP include/class files that are not intended entry points;
* setup or seed scripts;
* first Admin bootstrap scripts.

Configuration values must be kept outside the browser-accessible surface. They may be stored in a configuration file outside the public document root, an environment file outside the public document root, or environment variables.

If a `.env` file or equivalent local config file is used, it must be excluded from version control from the start of the project. It must not be committed and removed later only after accidental exposure.

### 8.5 First Admin Setup Script Must Not Remain Reachable

If the first Admin bootstrap procedure is implemented as a script rather than a pure database seed or manual database step, that script must not remain reachable after setup.

A bootstrap script that creates an Admin account and remains accessible at a URL after setup is a direct privilege-escalation path. It would undermine the normal rule that later Admin accounts must be created only by an authenticated Admin.

Acceptable handling includes:

* running the first Admin setup as a manual database seed;
* running a local setup script outside the public document root;
* deleting or disabling the setup script immediately after successful setup;
* ensuring no permanent public "create Admin" endpoint exists.

### 8.6 Composer Dependencies

Composer may be used only for narrow, justified helper libraries, not as a major framework dependency.

If Composer is used, the `vendor/` directory must not be browser-accessible. Dependency folders may contain examples, tests, debug utilities, or scripts that were never intended to be requested directly. Keeping `vendor/` outside the public document root prevents accidental exposure without needing to review every dependency's internal files.

### 8.7 Practical XAMPP Misconfiguration Risks

For a local/LAN demonstration, realistic deployment risks include:

* leaving XAMPP's default landing page, phpMyAdmin, or other bundled tools reachable and unprotected on a LAN;
* leaving directory listing enabled on a folder that should not be browsable;
* placing the whole project directly under `htdocs`, making the entire codebase browser-reachable unless sensitive files are blocked individually;
* leaving setup scripts, test scripts, or seed scripts in a reachable public folder;
* storing uploaded files in a public folder for convenience.

The safer pattern is to restrict what Apache can serve by design, not to rely on a long denylist of files that should not be accessed.

### 8.8 Known v1.0 Limitations — Document Root and Deployment

The following are accepted v1.0 limitations:

* No production deployment hardening is required.
* Mandatory HTTPS, firewall rules, reverse proxy, WAF, and public-hosting hardening are deferred.
* Exact folder names and final project structure remain `BUILD_PLAN.md` decisions.
* A deny-all fallback rule such as `.htaccess` may be used as defense-in-depth, but it is not a substitute for correct document-root separation.
* XAMPP and phpMyAdmin hardening are treated as local setup concerns, not as full production server administration.

---

## 9. Audit Log and Resource Action History Integrity

### 9.1 Two-Ledger Model

BPC LearnShare uses two separate accountability ledgers:

* **`resource_action_history`** — the resource-specific ledger for moderation and resource-status actions.
* **`audit_log`** — the administrative/system-level ledger for account and system-setting actions.

`resource_action_history` records actions such as:

* approve;
* reject;
* request correction;
* resubmit;
* hide;
* restrict;
* remove;
* withdraw;
* return to Approved;
* replaced.

`audit_log` records administrative/system actions currently supported by the accepted schema, such as:

* account creation;
* account disabling;
* account re-enabling;
* role changes;
* Admin-assisted password resets (D039);
* system-setting changes;
* taxonomy add/edit/deactivate/reactivate actions (D039).

These two ledgers are not interchangeable. Resource-status changes belong in `resource_action_history`. Account and system-setting changes belong in `audit_log`.

### 9.2 Append-Only at the Application Level

Both ledgers are append-only at the application level.

Application code must not update or delete existing `resource_action_history` or `audit_log` rows during normal operation. Every action creates a new row. If a mistaken entry must be clarified, the correction should be recorded as a new clarifying entry rather than editing or deleting the original row.

This is not a production-grade tamper-proof audit system. It is an accountability record appropriate for a local/LAN capstone MVP.

### 9.3 Write the Log Entry With the Action It Describes

A log or history entry should be written as part of the same logical operation as the action it describes.

Examples:

* a moderation decision commits together with the resource status change and its `resource_action_history` row;
* a replacement approval commits the replacement status change, original resource status change, open-replacement tracking update, and related history entries together;
* an account-status change commits together with its `audit_log` entry;
* a role change commits together with its `audit_log` entry;
* a system-setting change commits together with its `audit_log` entry.

An action must not succeed while its accountability record silently fails to write. A log entry must also not be written for an action that did not actually complete.

### 9.4 Safe-Summary-Only Content

Audit and action-history rows must store enough information to explain:

* what happened;
* who performed the action;
* what target was affected;
* when it happened;
* the short reason or note where required.

They must not store full sensitive content.

The following must never be stored in `resource_action_history`, `audit_log`, or application logs:

* plaintext passwords;
* password hashes;
* temporary passwords after use;
* session IDs;
* CSRF tokens;
* AI API keys;
* database credentials;
* full uploaded file contents;
* full AI-generated output content.

A note may state that AI output existed, was accepted, discarded, invalidated, or unavailable. It must not copy the full AI output into the log.

### 9.5 Required Actions — What the Confirmed Schema Currently Supports

The accepted `resource_action_history` action types fully cover the core resource-specific moderation and status-change actions.

The accepted `audit_log` action types, as extended by D039 (see §9.6), cover account creation, account disabling, account re-enabling, role changes, Admin-assisted password resets, system-setting changes, and taxonomy add/edit/deactivate/reactivate actions.

AI enable/disable is covered as a system-setting change.

### 9.6 Admin-Assisted Password Reset and Taxonomy Logging

`WORKFLOWS.md` and `DATABASE_DESIGN.md` require Admin-assisted password resets and taxonomy add/edit/deactivate/reactivate actions to be recorded in `audit_log`.

The originally accepted `schema.sql` baseline did not yet include matching `action_type` and `target_type` values for all of these actions. This schema-alignment gap is resolved by D039.

`audit_log.action_type` now includes:

* `password_reset`
* `taxonomy_created`
* `taxonomy_updated`
* `taxonomy_deactivated`
* `taxonomy_reactivated`

`audit_log.target_type` now includes:

* `course`
* `subject`
* `year_level`
* `resource_type`
* `tag`

Admin-assisted password reset is logged with:

* `action_type = 'password_reset'`
* `target_type = 'account'`

Taxonomy actions are logged with the appropriate taxonomy action type and the specific taxonomy target type.

Specific taxonomy target types are used instead of one generic `taxonomy` value so that `target_type` + `target_id` continues to resolve unambiguously to exactly one target table.

Risky failed upload-validation attempts remain in a server-side application log file, not in `audit_log`. These attempts do not create resource records and are treated as security-event logging, not ordinary Admin audit-log events.

### 9.7 Audit Logs Are Accountability Records, Not Analytics

Audit logs and resource action history are for accountability, not analytics.

They should not become a general event tracker for:

* page views;
* searches;
* bookmark toggles;
* Helpful mark toggles;
* every failed form submission;
* every ordinary validation error.

Those behaviors belong to their own counters, relationships, or not at all.

Keeping the ledgers focused prevents them from becoming noisy and difficult to review during development and defense.

### 9.8 Known v1.0 Limitations — Audit and Action History

The following are accepted v1.0 limitations:

* Append-only behavior is enforced by application discipline, not by database triggers.
* There is no tamper-proof audit infrastructure.
* There is no audit-log export or advanced reporting feature.
* There is no automated alerting on logged actions.
* The D039 schema patch resolving the Section 9.6 alignment gap has been applied; `BUILD_PLAN.md` and `TESTING_CHECKLIST.md` still need to verify it end-to-end against running code (see Section 14.4–14.5).

---

## 10. Polymorphic Target Validation (`notifications`, `audit_log`)

### 10.1 The Problem D036 Already Named

`notifications.target_type` / `target_id` and `audit_log.target_type` / `target_id` are polymorphic references.

This means one `target_id` column can point to different possible tables depending on the value of `target_type`.

MySQL/MariaDB cannot enforce a normal foreign key from one `target_id` column to multiple possible target tables. Therefore, target correctness must be enforced by PHP application logic.

### 10.2 Accepted Target Types in the Current Schema

The accepted schema currently uses the following target types:

| Table | `target_type` values | `target_id` requirement |
| --- | --- | --- |
| `notifications` | `resource`, `report`, `action_history` | Required; must reference an existing row of the matching type |
| `notifications` | `moderation_queue`, `report_queue` | Must be `NULL`; these point to queue views, not one row |
| `audit_log` | `account`, `system_setting`, `course`, `subject`, `year_level`, `resource_type`, `tag` | Required; must reference an existing row of the matching type |

For `audit_log` entries with `target_type` values of `course`, `subject`, `year_level`, `resource_type`, or `tag`, the application must confirm that the referenced row exists in that specific lookup table before trusting `target_id`. This follows the same application-validation rule already required for `account` and `system_setting` audit targets.

### 10.3 Required Application-Layer Validation

Because no foreign key backs these polymorphic target columns, the application must perform validation for every read and write.

For every polymorphic target, the system must:

1. validate `target_type` against the accepted closed set;
2. validate whether `target_id` is required or must be `NULL`;
3. when `target_id` is required, confirm that the referenced row exists in the correct target table;
4. reject mismatched, missing, or impossible target combinations before writing or displaying the record.

For example:

* a notification with `target_type = 'resource'` must reference an existing resource;
* a notification with `target_type = 'report'` must reference an existing report;
* a notification with `target_type = 'moderation_queue'` must not require a row ID;
* an audit log with `target_type = 'account'` must reference an existing account;
* an audit log with `target_type = 'system_setting'` must reference an existing system setting.
* an audit log with `target_type = 'tag'` must reference an existing controlled tag.

### 10.4 Notification Links Must Still Pass Downstream Checks

A notification is only a pointer. It does not grant access.

When a user opens a notification, the system must still check:

* the notification belongs to the current account;
* the current account is Active;
* the user's current role is allowed to view the destination;
* the target object still exists;
* the target object's current status allows access;
* for resource targets, current resource status and `file_availability` both allow access.

A notification created while a resource was Approved must not continue granting access if the resource later becomes Hidden, Restricted, Removed, Replaced, or otherwise unavailable.

### 10.5 Audit Target Validation Must Not Cross the Moderator/Admin Boundary

`audit_log` entries are Admin-only.

Any endpoint that resolves an `audit_log` entry into a displayed target must first confirm that the viewer is an Admin.

The system must not allow a Moderator to use audit-log target IDs to view account-management or system-setting information indirectly.

### 10.6 Known v1.0 Limitations — Polymorphic Target Validation

The following are accepted v1.0 limitations:

* Polymorphic target validation is application-enforced only.
* There is no foreign-key guarantee for `notifications.target_id` or `audit_log.target_id`.
* There is no ORM or framework layer that automatically validates polymorphic targets.

---

## 11. AI-Related Security Boundaries

### 11.1 AI Is Optional, Assistive, and Non-Authoritative

AI is optional, assistive, and non-authoritative.

AI must never:

* approve a resource;
* reject a resource;
* publish a resource;
* hide a resource;
* restrict a resource;
* remove a resource;
* validate academic correctness;
* decide access permissions;
* replace Moderator/Admin judgment.

AI output is only a suggestion. A human uploader, Moderator, or Admin may review, edit, accept, or discard AI output according to the confirmed workflow rules.

### 11.2 Core Platform Must Work With AI Disabled

The core platform must remain functional when AI is:

* disabled;
* unconfigured;
* unavailable;
* failing;
* rate-limited;
* unreachable.

AI failure must not block:

* login;
* upload;
* moderation;
* metadata search/filtering;
* view/download;
* bookmarks;
* Helpful marks;
* reports;
* account management;
* taxonomy management;
* system settings.

An AI outage is a degraded-feature condition, not a reason for the core platform to fail.

### 11.3 AI API Key Handling

AI API keys are server-side configuration values.

They must never be:

* embedded in client-side JavaScript;
* exposed in HTML;
* committed to version control;
* stored in the database;
* written to `resource_action_history`;
* written to `audit_log`;
* written to ordinary application logs.

If AI processing fails and the failure is logged for debugging, the log should record that a failure occurred. It must not expose API keys, full request payloads, full response payloads, or sensitive provider error details.

### 11.4 Status-Based AI Eligibility

AI eligibility must be checked server-side at the moment AI processing would occur.

The rules are:

* **Approved resources** may be processed for Approved-resource AI features, such as summaries, recommendations, and content-based search if implemented.
* **Pending resources** may be processed only after both conditions are true:

  * the file has passed basic upload validation; and
  * the uploader has acknowledged the AI-processing notice.
* **Rejected, Withdrawn, Restricted, Removed, Replaced, private, or otherwise unauthorized resources** must not be processed by AI while they are in those statuses.
* **Hidden resources** must not trigger new public-facing AI processing while Hidden, because they are no longer Approved and are not Pending.

If a reversible status later returns to Approved through an allowed workflow, future AI eligibility should be evaluated again based on the current Approved status and the AI-output lifecycle rules.

### 11.5 AI Failure Must Not Block Other Workflows

If an AI request fails, times out, or is unavailable, the affected AI feature should fail safely.

The resource should still proceed through the normal non-AI path. For example:

* upload still creates a Pending resource if upload validation passes;
* moderation still proceeds without AI assistance;
* metadata search still works;
* view/download still works for eligible resources;
* reports and admin workflows still work.

AI failure must not be treated as if AI succeeded. The system must not invent AI output, auto-fill AI results, or silently reuse stale output from another resource.

### 11.6 AI Output Must Follow the Source Resource's Lifecycle

AI output belongs to one source resource and must follow that source resource's lifecycle.

The practical rules are:

* **Pending:** AI output is draft. It is visible only to the uploader for their own resource and to Moderator/Admin for review assistance.
* **Approved:** AI output that was visible during moderation and not discarded may be retained and shown according to normal Approved-resource visibility rules.
* **Rejected / Withdrawn / Removed:** AI output must be deleted or invalidated and must not appear in search, recommendations, summaries, report views, or public-facing pages.
* **Hidden / Restricted:** AI output must be excluded from public-facing surfaces such as recommendations and search. It may remain visible to the uploader, Moderator, and Admin according to resource visibility rules. If the resource returns to Approved through an allowed workflow, retained output may become visible again if still valid.
* **Replaced:** AI output from the original resource must not remain publicly visible, searchable, or usable for recommendations. It may remain visible to the uploader, Moderator, and Admin for history/reference only if policy allows. The replacement resource never inherits AI output from the original resource and must generate or store its own output if eligible.

### 11.7 No Empty Placeholder AI Rows

An `ai_outputs` row must not be inserted before AI content actually exists.

The system must not create placeholder AI rows while waiting for an AI response. If AI processing fails, no successful AI-output row should be created.

If database CHECK constraints are not enforced by the local database version, PHP application logic must still enforce this rule.

### 11.8 Public Queries Must Exclude Invalidated Output

Every public-facing query that surfaces AI output must exclude invalidated AI output.

This applies to:

* summary display;
* recommendation lists;
* content-based search results;
* duplicate/similar-resource hints shown outside staff review;
* optional AI inquiry/chat if implemented later.

Public-facing AI queries must also check the current source resource status. A retained AI output must not be displayed publicly if the source resource is currently Hidden, Restricted, Removed, Replaced, or otherwise unavailable.

### 11.9 Phase 5 Optional Inquiry/Chat Boundary

Phase 5 AI inquiry/chat is optional stretch scope only.

If implemented later, it must:

* answer only from currently Approved resources;
* cite or reference the source resource or resources used;
* state clearly when no Approved resource supports an answer;
* avoid generating unsupported answers;
* avoid answering exams, quizzes, graded assignments, or answer keys;
* never access Pending, Rejected, Withdrawn, Hidden, Restricted, Removed, Replaced, private, or unauthorized resources.

This feature must not become the main product direction. BPC LearnShare remains a moderated academic resource-sharing and management system, not a chatbot-first system.

### 11.10 Known v1.0 Limitations — AI Security

The following are accepted v1.0 limitations:

* No AI-specific rate limiting beyond the rule that AI should not be repeatedly called for an unchanged file.
* No production AI governance, model auditing, output-quality monitoring, or drift detection.
* No guarantee that AI output is academically correct.
* No AI replacement for human moderation.
* No AI access to ineligible resource statuses.
* AI-output filtering depends on every AI-triggering and AI-displaying code path applying the same eligibility and lifecycle rules.

---

## 12. Concurrency and Transaction Safeguards

### 12.1 Why Concurrency Still Matters in a Local/LAN System

A small user base does not mean zero concurrency risk. Two Moderators reviewing the same Pending queue, an uploader withdrawing a resource at the moment a Moderator approves it, or two replacement submissions racing against the same original resource are all realistic even with a handful of concurrent LAN users.

This section states the enforcement requirement. It does not introduce new workflow behavior beyond what `WORKFLOWS.md`, `DATABASE_DESIGN.md`, and the accepted decision log already require.

### 12.2 General Re-Check-Before-Commit Rule

Before any state-changing action commits, the system must re-check the current database state, not the state the requesting user's page last displayed.

If the state has changed since the request was formed, the action must fail cleanly rather than overwrite a decision made by someone else.

This applies to every action named in this section and is the single mechanism the rest of Section 12 is built on.

A practical native PHP/MySQL pattern is:

1. start a database transaction;
2. perform the update using the expected prior state in the `WHERE` clause;
3. check the affected-row count;
4. commit only if the expected row was actually updated;
5. roll back and return a clean conflict message if no row was updated.

For example, a moderation approval should not simply update a resource by ID. It should update only if the resource is still in the expected status, such as `Pending`.

This is deliberately simple. It does not require row-level locking frameworks, stored procedures, trigger-heavy business rules, or a major PHP framework.

### 12.3 Moderation Decision Concurrency

Approve, Reject, and Request Correction must only apply if the resource is still `Pending` at commit time.

Resubmission after Needs Correction must only apply if the resource is still `Needs Correction`.

Withdrawal must only apply if the resource is still in a withdrawal-eligible status.

If two staff users attempt to decide the same Pending resource nearly simultaneously, the first valid committed decision wins. The second attempt fails cleanly with a message that the resource has already changed.

The same rule applies when an uploader attempts to withdraw a resource while a Moderator is approving or rejecting it. Whichever valid action commits first determines the outcome. The second action fails because its expected prior status no longer holds.

### 12.4 Replacement Approval Atomicity

Approving a replacement is one of the most important atomic operations in the system.

The following must commit together in one transaction, or not at all:

1. the replacement resource becomes `Approved`;
2. the original resource becomes `Replaced`;
3. the corresponding `open_replacement_tracking` row is cleared;
4. related `resource_action_history` rows are written.

No partial outcome is acceptable.

The system must never allow:

* the replacement to become Approved while the original remains Approved;
* the original to become Replaced while the replacement fails to become Approved;
* the open replacement tracking row to remain after the replacement has been resolved.

Before the transaction commits, the system must re-check the original resource's current status:

* if the original is still `Approved`, normal replacement approval applies;
* if the original is `Hidden` or `Restricted`, the Moderator/Admin must review the current original status before approving the replacement, and the action history must record the original resource's previous status;
* if the original is `Removed` or already `Replaced`, the pending replacement must not be approved as the replacement for that original.

### 12.5 Report Resolution and `open_report_tracking` Consistency

When a report becomes `Dismissed` or `Actioned`, the following must commit together:

1. the report status update;
2. the report resolution fields, such as resolving actor, resolution note, and resolved timestamp;
3. the deletion of the matching `open_report_tracking` row.

A report must not end up resolved while its tracking row still exists. That would incorrectly block the same reporter from filing a later report on the same resource after the earlier report was already resolved.

Escalation does not clear the tracking row, because an Escalated report is still unresolved.

### 12.6 Open Replacement Tracking Consistency

When a replacement is resolved as `Approved`, `Rejected`, or `Withdrawn`, the matching `open_replacement_tracking` row must be cleared as part of the same operation as the status change.

Leaving a stale open-replacement tracking row after resolution would incorrectly block the uploader from submitting a future replacement for the same original resource.

### 12.7 Withdrawn/Removed Lifecycle and Removed-Resource Sanitization

Withdrawn and Removed resources share some file/AI lifecycle requirements, but only Removed resources use D040 content sanitization.

For a Withdrawn resource, the following database changes must be handled as one logical operation:

1. resource status changes to `Withdrawn`;
2. `file_availability` changes to `deleted` or `invalidated`;
3. AI-output database lifecycle changes according to D018 and D035;
4. required history is written.

Withdrawn resources retain their original title, description, topic, original filename, and controlled-tag associations.

For a Removed resource, the following database changes must commit together in one transaction:

1. resource status changes to `Removed`;
2. `file_availability` changes to `deleted` or `invalidated`;
3. `title` becomes `[Removed resource]`;
4. `description` becomes `[Removed resource]`;
5. `topic` becomes `[Removed]`;
6. `original_filename` becomes `[removed]`;
7. all related `resource_tags` rows are deleted;
8. AI-output database lifecycle changes according to D018 and D035;
9. the required `resource_action_history` row is written.

`created_at` remains unchanged. `updated_at` changes automatically when the resource row is updated.

Physical file deletion is not part of the database transaction and cannot automatically roll back with it. The implementation must coordinate physical cleanup with the database operation. If physical cleanup fails, the system must fail closed: the resource remains non-servable because its status and `file_availability` do not allow file access, and the cleanup failure must be handled safely.

A resource that is `Removed` or `Withdrawn` while its file remains servable would violate the dual-gate file-serving rule.

### 12.8 Audit and Action-History Writes Commit With Their Action

Every `resource_action_history` write must commit with the resource action it describes.

Every `audit_log` write must commit with the administrative action it describes.

This includes the D039-covered audit actions:

* Admin-assisted password reset must commit the updated password hash together with an `audit_log` entry using `action_type = 'password_reset'` and `target_type = 'account'`;
* taxonomy add/edit/deactivate/reactivate actions must commit the lookup-table change together with the matching audit entry using the correct taxonomy action type and target type.

An action must never be allowed to succeed while its required audit or action-history entry silently fails to write.

A log entry must also not be written for an action that did not actually complete.

### 12.9 What Is Not Required

BPC LearnShare v1.0 does not require:

* distributed transactions;
* message queues;
* advanced row-locking frameworks;
* database triggers enforcing business rules;
* production-scale concurrency engineering;
* automatic retry queues.

Standard database transactions, current-state re-checks, affected-row-count checks, and clean conflict messages are sufficient for the local/LAN academic MVP scope.

### 12.10 Known v1.0 Limitations — Concurrency

The following are accepted v1.0 limitations:

* **No optimistic-locking version column.** Conflict detection relies on current-state checks and affected-row-count checks, not a dedicated `version` or `row_version` field.
* **No automatic retry mechanism.** A failed or conflicting action returns a clean failure message. The user must reload or resubmit manually if appropriate.
* **Default transaction isolation is accepted for v1.0.** No custom isolation-level tuning is required unless actual testing reveals a problem.
* **Transaction correctness depends on implementation discipline.** The schema identifies the required transaction boundaries, but PHP code must still implement them correctly.

---

## 13. Known v1.0 Security Limitations and Risk Register

### 13.1 Purpose of This Register

This section consolidates the known security limitations already stated throughout Sections 3–12 and identifies items that must be carried into `BUILD_PLAN.md` and `TESTING_CHECKLIST.md`.

Each item is labeled as one of the following:

* **Accepted** — a deliberate v1.0 boundary, not expected to change before defense;
* **Deferred** — a real future-hardening item, explicitly outside v1.0 scope;
* **Verify in Testing** — a correctness requirement that must be confirmed against running code;
* **Open** — an implementation detail that must be resolved during build planning.

### 13.2 Local/LAN Deployment Limitations

| Item                                                                                                                         | Status            |
| ---------------------------------------------------------------------------------------------------------------------------- | ----------------- |
| No mandatory HTTPS for the localhost/LAN-only demo                                                                           | Accepted          |
| No firewall configuration, network segmentation, reverse proxy, or WAF                                                       | Deferred          |
| No production deployment hardening                                                                                           | Deferred          |
| Document-root structure correctly isolates config, credentials, internal code, uploads, and setup scripts from public access | Verify in Testing |

### 13.3 Authentication and Session Limitations

| Item                                                                                                                                                | Status            |
| --------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------- |
| No persistent login-attempt lockout or failed-attempt tracking                                                                                      | Accepted          |
| No CAPTCHA                                                                                                                                          | Accepted          |
| No general rate-limiting requirement for v1.0                                                                                                       | Deferred          |
| No two-factor authentication                                                                                                                        | Accepted          |
| No email verification for Student self-registration                                                                                                 | Accepted          |
| No self-service password recovery, reset-token table, or email-based reset flow                                                                     | Accepted          |
| No forced session termination on account disable or role change; an already-open session fails on its next protected request through live re-checks | Accepted          |
| No Admin-visible active-session list or forced-logout tool                                                                                          | Accepted          |
| Session ID regeneration occurs after every successful login                                                                                         | Verify in Testing |
| 30-minute idle timeout is practical for actual moderator/admin use                                                                                  | Verify in Testing |

### 13.4 Authorization and RBAC Limitations

| Item                                                                                                                           | Status            |
| ------------------------------------------------------------------------------------------------------------------------------ | ----------------- |
| No centralized authorization middleware or major framework; checks are applied by hand per endpoint                            | Accepted          |
| No automated detection or alerting for repeated authorization-bypass attempts                                                  | Deferred          |
| Every protected endpoint independently re-verifies role, account status, resource status, and file availability where relevant | Verify in Testing |
| Upload-role restriction is enforced on first-time upload, resubmission, and replacement submission                             | Verify in Testing |

### 13.5 Validation and Database-Constraint Reliability

| Item                                                                                                                                | Status                                                                  |
| ----------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------- |
| Local MariaDB/MySQL CHECK-constraint support is still unconfirmed                                                                   | Open — must be resolved before `BUILD_PLAN.md` relies on CHECK behavior |
| PHP validation independently enforces every closed-set, ownership, status, and permission rule regardless of database CHECK support | Accepted requirement                                                    |
| CSRF protection uses one session-scoped token, not rotating per-request tokens                                                      | Accepted                                                                |
| Closed-set validation matches `schema.sql` after the D039 audit-log patch                                                           | Verify in Testing                                                       |

### 13.6 Polymorphic Target Validation Limitations

| Item                                                                                                                           | Status             |
| ------------------------------------------------------------------------------------------------------------------------------ | ------------------ |
| `notifications.target_id` and `audit_log.target_id` have no normal FK enforcement because they are polymorphic references      | Accepted by design |
| Every target read/write path performs type validation, existence validation, and visibility checks before trusting `target_id` | Verify in Testing  |
| Admin-only `audit_log` target resolution does not leak account, system-setting, or taxonomy details to Moderator sessions      | Verify in Testing  |

### 13.7 Audit and Action-History Limitations

| Item                                                                                                       | Status            |
| ---------------------------------------------------------------------------------------------------------- | ----------------- |
| `resource_action_history` and `audit_log` are append-only by application discipline, not database triggers | Accepted          |
| No cryptographic tamper-evidence for log rows                                                              | Deferred          |
| No audit-log export or advanced reporting feature                                                          | Accepted          |
| Password-reset and taxonomy audit actions from D039 are correctly categorized and paired                   | Verify in Testing |
| Removed-resource D040 sanitization uses the exact placeholder values, deletes `resource_tags`, preserves required relationships, updates `updated_at`, and remains distinct from Withdrawn handling | Verify in Testing |
| Risky failed upload attempts are logged through a server-side application log, not `audit_log`             | Accepted          |

### 13.8 File Upload and Storage Limitations

| Item                                                                                                                     | Status                            |
| ------------------------------------------------------------------------------------------------------------------------ | --------------------------------- |
| No malware or antivirus scanning of uploaded files                                                                       | Deferred                          |
| No content-hash-based duplicate detection at the storage layer                                                           | Deferred                          |
| 20 MB working file-size limit is not yet tested against realistic scanned-PDF or image-heavy-PPTX samples                | Verify in Testing                 |
| Exact MIME/content validation mechanism, such as PHP `finfo` or a narrowly justified helper library, is not yet selected | Open — resolve in `BUILD_PLAN.md` |
| Uploaded files are never statically reachable through Apache/public URLs                                                 | Verify in Testing                 |

### 13.9 AI-Related Limitations

| Item                                                                                                                         | Status                                                                |
| ---------------------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------- |
| No AI-specific rate limiting beyond not regenerating for unchanged files                                                     | Accepted                                                              |
| No production AI governance, model auditing, output-quality monitoring, or drift detection                                   | Deferred                                                              |
| No guarantee that AI output is academically correct                                                                          | Accepted                                                              |
| AI API keys never appear in client code, version control, database rows, audit logs, or application logs                     | Verify in Testing                                                     |
| Status-based AI eligibility and invalidated-output exclusion are enforced on every AI-triggering and AI-displaying code path | Verify in Testing                                                     |
| AI API cost exposure from many distinct file submissions remains possible                                                    | Accepted v1.0 risk; consider cost-control planning in `BUILD_PLAN.md` |

### 13.10 Concurrency Limitations

| Item                                                                                                                                                      | Status                            |
| --------------------------------------------------------------------------------------------------------------------------------------------------------- | --------------------------------- |
| No optimistic-locking version column                                                                                                                      | Accepted                          |
| No automatic retry on conflicting actions                                                                                                                 | Accepted                          |
| Replacement approval, report resolution, Withdrawn handling, Removed handling, password reset, and taxonomy actions commit as complete logical operations | Verify in Testing                 |
| Transaction syntax and database-access pattern are not yet chosen                                                                                         | Open — resolve in `BUILD_PLAN.md` |

### 13.11 How `BUILD_PLAN.md` and `TESTING_CHECKLIST.md` Should Use This Register

Items marked **Accepted** should not be silently "fixed" during implementation without a scope discussion. They are deliberate v1.0 boundaries, not oversights.

Items marked **Deferred** belong in future-hardening notes, not v1.0 effort estimates.

Items marked **Verify in Testing** are correctness claims this document makes but has not yet proven against running code. `TESTING_CHECKLIST.md` should treat them as required verification items.

Items marked **Open** should be resolved in `BUILD_PLAN.md` before implementation begins for the affected feature.

---

## 14. Security Behaviors to Verify Later

This is a seed list for `TESTING_CHECKLIST.md`. It names categories and representative checks. It is not the full test plan, test data, or pass/fail script set.

### 14.1 Authentication and Session

* Valid login succeeds.
* Wrong password, unknown identifier, and Disabled-account login all produce the same generic failure message.
* Session ID changes immediately after successful login.
* A session idle past 30 minutes is rejected on the next protected request.
* Logout destroys the session server-side.
* A destroyed session ID is rejected on later protected requests.

### 14.2 RBAC, Direct URL, and Direct POST

* Each role can reach only the actions granted to it.
* Restricted actions are tested through the normal UI path.
* The same restricted actions are retested through direct URL requests and hand-crafted POST requests.
* A Moderator/Admin attempting ordinary upload is rejected.
* A Student or Teacher/Instructor attempting a moderation endpoint directly is rejected.
* A Moderator attempting an Admin-only Remove, account-management, taxonomy-management, or system-setting action is rejected.

### 14.3 Account Provisioning

* Non-Admin users cannot access account creation, role-change, disable/re-enable, or Admin-assisted password reset actions.
* Role values outside the four allowed roles are rejected server-side.
* Account status values outside Active/Disabled are rejected server-side.
* Disabling an account blocks later login.
* A Disabled account with an already-open session fails on its next protected action.

### 14.4 Admin-Assisted Password Reset

* Admin-assisted password reset updates the target account's password hash.
* Admin-assisted password reset writes an `audit_log` entry using `action_type = 'password_reset'` and `target_type = 'account'`.
* Password reset and audit entry are committed together.
* A password reset attempted by a non-Admin account is rejected before any write occurs.
* Plaintext or temporary passwords are not stored in logs.

### 14.5 Taxonomy Audit Logging

* Add, edit, deactivate, and reactivate actions for courses are logged with the correct D039 audit action and target type.
* Add, edit, deactivate, and reactivate actions for subjects are logged with the correct D039 audit action and target type.
* Add, edit, deactivate, and reactivate actions for year levels are logged with the correct D039 audit action and target type.
* Add, edit, deactivate, and reactivate actions for resource types are logged with the correct D039 audit action and target type.
* Add, edit, deactivate, and reactivate actions for controlled tags are logged with the correct D039 audit action and target type.
* Mismatched taxonomy action/target-type pairings are rejected by PHP validation.
* Deactivated taxonomy values stop appearing as selectable options for new uploads.
* Existing resources keep historical references to deactivated taxonomy values.

### 14.6 Upload Validation

* Each allowed file type is accepted when valid: PDF, DOCX, PPTX, TXT, JPG/JPEG, PNG.
* Disallowed file types are rejected, including EXE, BAT, CMD, JS, PHP, HTML, ZIP, RAR, 7Z, and APK.
* A disguised executable renamed with an allowed extension is rejected by content validation.
* Legitimate DOCX/PPTX files are accepted without being treated as arbitrary archive uploads.
* Oversized files are rejected.
* Empty or corrupt files are rejected.
* Rejected uploads do not create resource records.
* Risky failed upload attempts are logged minimally without storing the rejected file.

### 14.7 File Serving

* File access checks session, account status, role where needed, live resource status, and live `file_availability`.
* Approved resource with `file_availability = 'available'` can be served to eligible authenticated users.
* Approved resource with `file_availability = 'deleted'` or `invalidated` is not served.
* Pending, Needs Correction, Rejected, Withdrawn, Hidden, Restricted, Removed, and Replaced resources are not served to general authenticated users.
* Hidden/Restricted resources are not served through old bookmarks or notifications.
* Removed resources are not visible or servable to the uploader.
* Uploaded files are not reachable through guessed static storage filenames.

### 14.8 CSRF

* Every state-changing POST is rejected when the CSRF token is missing.
* Every state-changing POST is rejected when the CSRF token is incorrect.
* Read-only requests function without a CSRF token.
* CSRF checks apply to upload, withdrawal, moderation, reports, bookmarks, Helpful marks, account management, taxonomy management, system settings, and Admin-assisted password reset.

### 14.9 SQL Injection and Prepared Statements

* Injection-style input in title, description, topic, report comment, moderation note, and search/filter fields is safely handled.
* Request-derived values are not concatenated directly into SQL.
* Closed-set values are still bound as parameters after validation.
* Account, resource, report, notification, taxonomy, and audit identifiers are validated and bound safely.

### 14.10 XSS and Output Escaping

* Script-bearing input in user-editable text fields is escaped on output and does not execute.
* Report comments and moderation notes are escaped on output.
* Taxonomy names are escaped on output.
* AI-generated summaries, suggestions, and tags are escaped the same way as user-submitted content.
* Notification text does not execute script content.

### 14.11 Audit and Action History

* Every required resource action writes a `resource_action_history` row.
* Every required Admin/system action writes an `audit_log` row.
* `resource_action_history` and `audit_log` are not updated or deleted by normal application code.
* Ledger entries contain safe summaries only.
* Ledgers do not contain plaintext passwords, password hashes, API keys, session IDs, CSRF tokens, full file contents, or full AI outputs.

### 14.12 Polymorphic Target Validation

* Notification targets with `target_type = 'resource'`, `report`, or `action_history` reference existing rows of the matching type.
* Notification queue targets use the correct null-target handling.
* Audit targets with `target_type = 'account'`, `system_setting`, `course`, `subject`, `year_level`, `resource_type`, or `tag` reference existing rows of the matching type.
* Invalid `target_type` values are rejected.
* Invalid or mismatched `target_id` values are rejected.
* A Moderator cannot access Admin-only audit target details through direct request or indirect target resolution.

### 14.13 AI Eligibility and Lifecycle

* AI processing does not trigger for Rejected, Withdrawn, Restricted, Removed, Replaced, private, or unauthorized resources.
* Hidden resources do not trigger new public-facing AI processing while Hidden.
* Pending-resource AI processing requires both passed upload validation and uploader notice acknowledgment.
* AI failure does not block upload, moderation, search, view/download, bookmark, Helpful mark, report, or Admin workflows.
* Public-facing AI queries never return invalidated AI output.
* AI output from Replaced resources is not inherited by the replacement.
* Phase 5 optional inquiry/chat, if ever implemented, uses Approved resources only.

### 14.14 Concurrency

* Two near-simultaneous moderation decisions on the same Pending resource result in exactly one success and one clean failure.
* Withdrawal attempted after a moderation decision has already committed fails cleanly.
* Replacement approval fully commits all required updates or fully rolls back.
* Report resolution updates report status, resolution fields, and open-report tracking consistently.
* Replacement rejection or withdrawal clears open-replacement tracking consistently.
* Removed/Withdrawn handling updates resource status, file availability, AI lifecycle, and history/audit data consistently.

### 14.15 Removed-Resource Sanitization

* Only Admin can perform Remove.
* Removing a resource sets `title` to `[Removed resource]`.
* Removing a resource sets `description` to `[Removed resource]`.
* Removing a resource sets `topic` to `[Removed]`.
* Removing a resource sets `original_filename` to `[removed]`.
* Removing a resource deletes all associated `resource_tags` rows.
* Removing a resource does not delete the resource row.
* Removing a resource does not orphan reports, action history, bookmarks, Helpful marks, replacement relationships, or other accepted historical references.
* `created_at` remains unchanged.
* `updated_at` changes when the resource row is updated.
* The Admin removal reason remains in `resource_action_history`.
* Removed resource remains inaccessible to normal users and the uploader.
* Removed file remains non-servable even if physical cleanup encounters an error.
* Removed AI output is deleted or invalidated and excluded from public-facing reads.
* Withdrawn resources retain their original descriptive fields and tag associations.

### 14.16 Document Root and Protected Storage

* Application code outside the intended public surface is not reachable by direct browser request.
* Configuration files are not reachable by direct browser request.
* Uploaded files are not reachable by direct browser request.
* Composer `vendor/` files are not browser-accessible.
* First Admin setup script, if implemented as a script, is removed, disabled, or kept outside the public document root after setup.
* `.env` or local configuration files are not committed to version control.

---

## 15. Document Status and Revision History

### 15.1 Document Status

`SECURITY_NOTES.md` is complete through Sections 1–15 for BPC LearnShare v1.0.

It defines the security requirements, security boundaries, implementation-security decisions, known limitations, and testing carry-forward items that `BUILD_PLAN.md` and `TESTING_CHECKLIST.md` must account for.

It contains no PHP implementation code and no production deployment instructions. D039 and D040 are formally recorded in `DECISIONS.md` and reflected in `schema.sql` as appropriate, not treated as standalone `SECURITY_NOTES.md` overrides.

### 15.2 Source Documents Used

This document was written against, and remains consistent with:

* `PROJECT_BRIEF.md`
* `USER_ROLES.md`
* `WORKFLOWS.md`
* `DATABASE_DESIGN.md`
* `DECISIONS.md` through D040
* `schema.sql` with the D039-patched `audit_log` table and D040 application-level removal-behavior documentation
* `PROJECT_HANDOFF.md`

No unresolved conflict with these source documents remains as of this section. The still-open MariaDB/MySQL CHECK-constraint confirmation is a verification task, not a document conflict.

### 15.3 Security-Planning Decisions Resolved Inside This Document

The following implementation-security decisions were resolved within `SECURITY_NOTES.md` because earlier documents either assigned them here or left them for security planning.

These decisions do not change v1.0 scope, roles, resource statuses, report statuses, or the 18-table schema count:

* passwords are hashed and verified using `password_hash()` and `password_verify()`;
* minimum password length is 8 characters, with no forced composition rules;
* session ID is regenerated immediately after successful login;
* session idle timeout is 30 minutes;
* CSRF protection uses a single session-scoped token required on state-changing POST requests;
* v1.0 working file-size limit is 20 MB per upload, pending realistic testing;
* risky failed upload-validation attempts are logged through a server-side application log, not the `audit_log` table.

### 15.4 Cross-Document and Schema Alignment Decisions

Two formal alignment decisions affect this security baseline.

#### D039 — Audit Log Extended for Password Reset and Taxonomy Management Actions

D039 extends the existing `audit_log` action and target types so Admin-assisted password resets and taxonomy-management actions can be recorded consistently.

D039 changes the accepted `audit_log` enum values but does not add a table, role, resource status, report status, module, workflow, or AI feature.

#### D040 — Removed-Resource Minimal Record Uses Removal-Time Content Sanitization

D040 defines how D022's retained-resource minimization requirement is implemented without changing the 18-table schema.

For Removed resources only:

* `title` becomes `[Removed resource]`;
* `description` becomes `[Removed resource]`;
* `topic` becomes `[Removed]`;
* `original_filename` becomes `[removed]`;
* all associated `resource_tags` rows are deleted;
* file and AI-output lifecycle rules remain enforced;
* required accountability and historical relationships remain.

The retained record is minimized but not anonymized. D040 does not apply to Withdrawn resources.

D040 requires PHP-side lifecycle enforcement and coordinated database/file handling. It adds no table or column and does not replace D037's requirement that application validation remain authoritative.

### 15.5 Open Items Deferred to `BUILD_PLAN.md`

The following remain genuinely unresolved and must be settled during implementation planning:

* exact folder structure implementing the public/private document-root separation;
* exact first Admin bootstrap method;
* exact first Admin setup-script removal or disabling procedure, if a setup script is used;
* exact MIME/content validation mechanism, such as PHP `finfo` or a narrowly justified helper library;
* confirmation of the local MariaDB/MySQL version's CHECK-constraint support;
* realistic file-size testing against actual scanned-PDF and image-heavy-PPTX samples to validate or revise the 20 MB default;
* exact database access style, such as PDO or mysqli;
* exact transaction syntax implementing the atomic groups required in Section 12;
* exact implementation ordering and failure handling for D040 database updates, `resource_tags` cleanup, AI-output lifecycle updates, and physical file deletion/invalidation;
* exact location and retention handling for server-side logs used for risky failed upload attempts.

The CHECK-constraint version check must be completed before `BUILD_PLAN.md` treats any CHECK constraint in `schema.sql` as reliable enforcement. Regardless of the result, PHP validation remains mandatory.

### 15.6 Revision History

| Version   | Date       | Summary of Changes                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
| --------- | ---------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Draft 1.0 | 2026-07-09 | Initial complete draft of `SECURITY_NOTES.md`, Sections 1–15. Translates confirmed authentication, authorization, resource/file-status, audit, document-root, file-upload, validation, AI, and concurrency rules into security-specific requirements. Resolves the implementation-security decisions listed in Section 15.3. Identifies and resolves the D039 audit-log alignment gap. Consolidates known v1.0 security limitations into the risk register in Section 13 and identifies security behaviors to verify later in Section 14. |
| Draft 1.1 | 2026-07-09 | Consistency pass: closed the D039 propagation gap in Section 6.3, Section 9.1, Section 9.5, Section 9.8, Section 10.2, and Section 10.6, where the audit-log `action_type` / `target_type` extension was correctly resolved in Section 9.6 but not fully propagated into surrounding references. No new scope, roles, statuses, workflows, modules, or schema tables introduced. |
| Draft 1.2 | 2026-07-10 | Integrated D040 Removed-resource minimization: exact descriptive-field placeholders, `resource_tags` deletion, retained-but-not-anonymized accountability data, distinction from Withdrawn handling, coordinated database/filesystem lifecycle guidance, risk-register coverage, and dedicated security-testing seeds. No new table, column, role, status, module, workflow, or AI feature introduced. |

### 15.7 Relationship to Other Source-of-Truth Documents

`SECURITY_NOTES.md` does not override, reinterpret, or take precedence over `PROJECT_BRIEF.md`, `DECISIONS.md`, `USER_ROLES.md`, `WORKFLOWS.md`, or `DATABASE_DESIGN.md`.

Every requirement in this document is either a security-framed restatement of an already-confirmed rule, or a narrowly scoped implementation-security decision assigned to this document by the current planning phase.

D039 is the formal schema-enum alignment patch recorded in `DECISIONS.md` and applied to `schema.sql`. D040 is a formal application-level resource-lifecycle decision reflected across the source documents and documented in `schema.sql` comments without changing the accepted table or column structure. Neither decision is treated as a `SECURITY_NOTES.md`-only override.

If a future review finds an apparent conflict, it must be flagged and resolved through the project's established planning process, not resolved by treating this document as more authoritative by default.

---

*End of `SECURITY_NOTES.md`.*
