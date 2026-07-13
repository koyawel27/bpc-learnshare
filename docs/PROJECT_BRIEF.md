# PROJECT_BRIEF.md

**Project:** BPC LearnShare — AI-Assisted Collaborative Academic Resource Sharing and Management System  
**Version:** Draft v1.0  
**Last Updated:** 2026-07-12
**Author:** Nepthalie Jezer B. Macaslang  
**Course:** BS Information Systems — Bulacan Polytechnic College  
**Status:** Draft — pending adviser review and official title wording confirmation

---

## 1. Project Title

**BPC LearnShare: An AI-Assisted Collaborative Academic Resource Sharing and Management System**

> Note: The adviser originally selected the wording "AI-Integrated." For planning and implementation documentation, this project uses "AI-assisted" because the AI features support users and moderators but do not control system decisions. The final official title wording should be confirmed with the adviser before proposal or defense submission.

---

## 2. Project Overview

BPC LearnShare is a web-based academic resource-sharing and management platform designed for use within Bulacan Polytechnic College (BPC). It allows students and teachers to upload, organize, search, view, download, bookmark, and report academic learning resources — such as notes, reviewers, presentations, modules, study guides, handouts, and self-made summaries — in a single, moderated, centrally searchable repository.

The system is built as a resource **management and discovery platform**, not a learning delivery platform. It does not conduct classes, administer assessments, or manage grades. Its purpose is to reduce the fragmentation of academic materials currently scattered across informal channels (chat groups, personal drives, social media pages) by giving the campus community a structured, moderated, and searchable alternative.

Artificial intelligence is a required part of the completed v1.0 capstone deliverable: the platform must implement and demonstrate the minimum AI capability package defined in `DECISIONS.md` D041–D042. AI nevertheless remains an **assistive and non-authoritative layer** that the platform does not depend on for ordinary core operation. Core non-AI workflows — including upload, moderation, metadata search/filtering, resource access, feedback, reporting, and management — remain usable when AI is disabled, unconfigured, unavailable, rate-limited, failing, or unreachable.

---

## 3. Problem Statement

Academic resources at BPC (and similarly, at most campuses) are currently shared through informal, fragmented, and unmoderated channels — messaging group chats, personal cloud storage links, social media groups, or word-of-mouth. This creates several recurring problems:

- **Fragmentation:** Resources for the same subject exist in multiple disconnected places, with no single point of discovery.
- **No structure or metadata:** Files shared informally are rarely tagged by subject, topic, year level, or resource type, making later retrieval difficult.
- **No quality control:** There is no moderation step, so outdated, incorrect, duplicate, or inappropriate materials circulate alongside legitimate ones with no way to distinguish them.
- **No authorship or source trust signal:** A student cannot easily tell whether a shared reviewer came from a teacher, a top-performing student, or an unverified source.
- **Repeated effort:** Students and teachers often recreate materials that already exist elsewhere on campus simply because they cannot find them.
- **No accountability for harmful uploads:** Informal channels have no reporting or takedown mechanism for outdated, misleading, or improperly shared content (e.g., leaked exams).

BPC LearnShare addresses this by providing one moderated, structured, and searchable platform for legitimate academic resource sharing, implementing the required v1.0 AI capability package — including repository-grounded resource inquiry — to reduce the manual effort of organizing, discovering, and querying resources, while remaining fully functional without AI at runtime. It differs from informal sharing tools such as chat groups, personal cloud drives, or social media pages by providing mandatory moderation before public visibility, structured academic metadata (course, subject, topic, resource type, tags) that enables real filtering rather than folder-guessing, and a reporting/takedown workflow owned by the institution rather than a third-party platform's moderation policy.

---

## 4. Project Goal

To design and develop a campus-oriented, moderated academic resource-sharing and management platform that allows students and teachers to upload, organize, discover, and manage academic learning resources efficiently, implementing the required v1.0 AI capability package — including repository-grounded resource inquiry — to support, but never replace, human judgment in organization, moderation, and discovery, while remaining fully operable when AI is disabled or unavailable.

---

## 5. Specific Objectives

**General Objective:**  
Develop BPC LearnShare, an AI-assisted collaborative academic resource-sharing and management system that provides a structured, moderated repository of academic materials for students and teachers.

**Specific Objectives:**

1. Develop a role-based platform supporting Student, Teacher/Instructor, Moderator, and Admin accounts with distinct permissions.
2. Implement a resource upload workflow with file-type validation, metadata tagging (course, subject, year level, topic, resource type, tags), and mandatory moderation before public visibility.
3. Implement search and filtering functionality allowing users to locate resources by metadata fields without relying on AI.
4. Implement bookmarking, reporting, and basic engagement tracking (views/downloads) for uploaded resources.
5. Implement a moderator workflow for reviewing, approving, rejecting, or requesting correction of pending and reported resources.
6. Implement an admin workflow for managing users, courses/programs, subjects, year levels, resource types, controlled tags, and system-level settings.
7. Design and implement the required v1.0 AI capability package — readable-text extraction, AI-generated summaries, suggested controlled tags and metadata, semantic content-based retrieval/search, repository-grounded academic resource inquiry with source-resource attribution, and basic related-resource suggestions — together with the planned v1.0 enhancements of duplicate/similar-resource flags and AI moderation hints once the required package is stable. All AI-assisted features must remain individually configurable and must degrade gracefully without breaking core platform functionality when disabled, unavailable, or failing.
8. Ensure the platform enforces file-upload safety, authentication, authorization, and basic data-privacy safeguards appropriate for handling student- and teacher-submitted academic content.
9. Evaluate the platform's usability, functional correctness, and security posture through structured testing prior to defense.

---

## 6. Target Users

| Role | Description |
|---|---|
| **Student** | Uploads self-made academic resources, searches and accesses approved resources, bookmarks resources, marks resources as helpful, reports inappropriate or outdated resources. |
| **Teacher / Instructor** | Uploads official or recommended learning resources and manages their own uploaded resources; uploads still pass through the same moderation queue in v1.0. |
| **Moderator** | Reviews pending uploads, approves/rejects/requests corrections, reviews reported resources, manages duplicate or inappropriate resources, may edit resource metadata. |
| **Admin** | Manages user accounts, courses/programs, subjects, year levels, resource types, controlled tags, system settings, and views reports and activity logs. |

No additional roles are defined at this stage. Any new role proposed later must be flagged for scope impact before being added, per project planning rules.

---

## 7. Core Features

**Phase 1 — Core platform (no AI dependency):**
- User authentication and role-based access control
- Resource upload with metadata (course, subject, year level, topic, type, tags)
- Resource approval/moderation workflow
- Search and filtering by metadata
- View/download resources
- Bookmark resources
- Report resources
- Basic admin/moderator dashboard

**Phase 2 — Organization and management:**
- Course/program, subject, and year-level management
- Resource-type and controlled-tag management
- Helpful feedback on resources
- Most-viewed/most-downloaded resource listings
- Resource report handling
- Basic resource activity counts, such as views, downloads, and bookmarks
    
---

## 8. AI-Assisted Features

AI features are **assistive and non-authoritative at runtime**. They support human decision-making but never make final decisions on their own, and each AI-assisted capability may be disabled, degraded, or unavailable without breaking the platform's core non-AI workflows.

Separately, the completed v1.0 capstone prototype is required to implement and demonstrate the minimum AI capability package defined below. AI being optional or independently configurable at runtime does not mean that the defining AI capability package may be omitted from the completed capstone. See `DECISIONS.md` D041–D042.

**Required Minimum v1.0 AI Package** (implemented across Phases 3–5; see Section 15):

- **Readable-text extraction** for supported PDF, DOCX, PPTX, and TXT resources where extraction succeeds. Image-only resources, scanned PDFs, and other files without extractable text remain valid repository resources but are not required to support content-based AI functions in v1.0.

- **AI processing and lifecycle foundation** that tracks processing readiness, failure, and staleness; detects when a source file has changed so stale derived data is not used; and keeps current resource eligibility as a separate live status, access, file-availability, and permission check. Exact stored processing states remain subject to the later architecture/schema decision.

- **AI-generated resource summaries** that remain non-authoritative, follow the source resource lifecycle, and may be reviewed, edited, or discarded by the Uploader, Moderator, or Admin within their accepted permissions.

- **AI-suggested controlled tags and metadata** that require human review before use. AI suggestions must not automatically create taxonomy values, modify taxonomy records, approve metadata, or make moderation decisions.

- **Semantic content-based retrieval and search** across eligible extracted resource content. Semantic retrieval supplements rather than replaces metadata search and filtering, which must remain fully available without AI.

- **Repository-grounded academic resource inquiry** through a conversational interface that retrieves evidence from currently eligible Approved resources inside BPC LearnShare before generating a response. Substantive academic claims must be grounded in that retrieved evidence. The selected model may use its language capability to organize, simplify, summarize, or explain the evidence, but it must not silently substitute unsupported general model knowledge when repository evidence is missing.

- **Source-resource attribution** for every substantive inquiry response. The system must identify or link the supporting resource or resources. Page, slide, section, heading, or another source locator should be shown only when the selected extraction approach preserves that information reliably. The system must never fabricate a locator.

- **Clear insufficiency behavior** when the available repository evidence does not support an answer. The system must state that the evidence is insufficient instead of inventing an unsupported response.

- **Session-scoped conversational follow-up** during an active inquiry session. Persistent cross-session AI memory and permanent chat-history storage are not required in v1.0.

- **Basic related-resource suggestions** that return a small number of relevant Approved resources using content and metadata similarity. Personalized behavioral or learner profiles are not required.

- **Graceful non-AI fallback** so that failure, unavailability, or disabling of an AI-assisted capability affects only that capability and does not block or prevent completion of any core non-AI workflow.

**Planned v1.0 AI Enhancements** (targeted only after the complete required minimum package is stable — not equal-weight minimum defense blockers):

- **AI-assisted duplicate or similar-resource detection** that provides non-authoritative similarity indicators for human review. It must never automatically Reject, Hide, Restrict, Remove, or definitively label a resource as duplicated.

- **AI-assisted moderation support** that may flag possible content, metadata, similarity, or policy concerns for Moderator/Admin review. AI moderation hints must never make or execute moderation decisions and must remain clearly distinguishable from human moderation findings.

These capabilities remain part of the intended v1.0 AI target but are implemented only after the required minimum package is stable. Removing either planned enhancement from v1.0 later requires an explicit scope decision rather than silent omission.

**Deferred beyond v1.0 scope:**

- OCR for image-only or scanned resources
- AI vision processing
- Persistent cross-session AI memory
- Permanent chat-history storage unless separately approved
- Open-web retrieval or automatic web browsing
- Unrestricted general-purpose AI tutoring
- Personalized learning profiles
- Behavioral recommendation profiles
- AI-generated quizzes, graded assessments, grading, or answer checking
- Autonomous moderation or automatic resource-status actions
- Training or fine-tuning a new model from scratch

**AI must never:**

- Replace Teacher/Instructor, Moderator, or Admin judgment or authority
- Guarantee the academic correctness of an uploaded resource or generated response
- Approve, reject, publish, validate, Hide, Restrict, Remove, or delete a resource
- Make or execute a final moderation decision
- Answer exams, quizzes, graded assignments, answer keys, or other prohibited academic requests
- Use Pending resources for general repository inquiry; Pending-resource AI assistance remains subject to successful basic upload validation, clear uploader notice, and uploader acknowledgment
- Use Rejected, Withdrawn, Restricted, Removed, Replaced, private, unauthorized, or otherwise currently ineligible resources for new AI processing or general repository inquiry
- Use Hidden resources for new public-facing retrieval or inquiry while they remain Hidden
- Invent an answer, citation, page number, slide number, heading, section, or other source locator
- Operate as an unrestricted general-purpose chatbot, open-web assistant, or AI tutor
- Train or fine-tune a new model from scratch as part of v1.0

> **Note:** The required minimum AI package, including repository-grounded inquiry, is a defining v1.0 capstone deliverable under `DECISIONS.md` D041–D042. It is not an optional stretch feature and must not be silently removed under schedule pressure. The planned duplicate/similarity and moderation-hint enhancements are implemented only after the required package is stable and are not equal-weight minimum defense blockers; removing either from the intended v1.0 target still requires an explicit scope decision.

---

## 9. Scope

**In scope:**
- Academic resource upload, organization, moderation, search, discovery, bookmarking, reporting, and management
- Role-based access for Student, Teacher/Instructor, Moderator, Admin
- Login-required access to approved resources for authenticated active users
- The required v1.0 AI capability package (per DECISIONS.md D041–D042), layered on top of a fully functional non-AI core; AI remains individually toggleable and gracefully degradable at runtime and is never required for ordinary core platform operation
- A prototype demonstrated using sample course/subject data representative of campus use, built with campus-wide adoption as the long-term design intent

**Out of scope (explicitly excluded to prevent LMS scope creep):**
- Online classes or live sessions
- Quizzes, exams, or graded assessments
- Grading or gradebooks
- Attendance tracking
- Enrollment management
- Assignment submission and checking
- Teacher class records
- Video meetings/conferencing
- Full classroom management
- General social-media features (feeds, direct messaging, following/followers)

---

## 10. Limitations

- The system is not a Learning Management System and does not replicate LMS functionality such as grading, enrollment, or assessment.
- Some AI-assisted capabilities may depend on local AI runtime availability and/or an external AI service, including internet connectivity, provider availability, configuration, rate limits, free-tier limits, or continued funding. When an AI dependency is unavailable, only the affected AI-assisted capability should degrade; core non-AI workflows remain usable.
- AI-generated summaries, tag suggestions, metadata suggestions, similarity flags, and moderation hints may be incomplete or inaccurate and remain subject to authorized human review before use. Repository-grounded inquiry responses are also non-authoritative: substantive academic claims must be supported by cited eligible repository resources, and the system must state when available repository evidence is insufficient.
- Semantic content search, content-based related-resource suggestions, and repository-grounded inquiry (Phases 4–5) operate only on readable text that can be successfully extracted from eligible Approved resources. Scanned or image-only resources without OCR support may remain valid repository resources but may not be searchable or usable as inquiry evidence by content.
- The platform is developed and demonstrated on a local XAMPP environment; production-grade deployment (load handling, uptime, backups) is outside the scope of the capstone defense build.
- Full "campus-wide" deployment is a design goal, not a claim of actual current-scale usage; the defense build is a functional prototype validated on representative sample data.
- The system does not verify the academic correctness of uploaded content; it verifies that content passed moderation, not that it is factually correct.

---

## 11. Technology Stack

- **Backend:** Native PHP
- **Database:** MySQL/MariaDB
- **Local development environment:** XAMPP on Windows
- **Frontend:** HTML, CSS, vanilla JavaScript
- **Dependency management:** Composer, used only for specific, justified helper libraries (e.g., file-text extraction), not for a framework
- **Frameworks:** No Laravel or major application framework unless explicitly reconsidered through a later scope and architecture decision
- **AI integration:** Native PHP application logic combined with local processing where practical (e.g., text extraction and embeddings) and an external AI API where necessary for acceptable answer quality and response time, with cost controls and fallback handling; the exact providers, models, local-AI configuration, retrieval approach, and supporting architecture remain unresolved pending the feasibility spike and later architecture/schema decision required by `DECISIONS.md` D042

---

## 12. Deployment Context

The initial deployment target is a local development and demonstration environment using XAMPP on Windows. The system may be tested on a local machine or LAN setup for capstone demonstration purposes.

The project is designed as a functional academic prototype, not as a production-scale campus deployment. Campus-wide use is treated as the long-term design intent, while the defense build will be validated using representative sample users, subjects, courses, and resources.

---

## 13. File and Resource Handling

Uploaded resources must include required metadata such as title, description, course/program, subject, year level, topic, resource type, and tags when applicable. Files must pass server-side validation for allowed file types, file size limits, and basic safety checks.

Uploaded resources are not visible through normal browse or search immediately. They remain Pending until reviewed and approved by a Moderator or Admin.

Only Approved resources appear through normal browse and search for authenticated Active users. Pending, Needs Correction, Rejected, Withdrawn, Hidden, Restricted, and Replaced resources remain limited to the uploader, Moderator, and Admin under the accepted visibility rules. Removed resources are stricter: ordinary users and the original uploader cannot access them, while Admin may access only the retained minimized accountability/reference record.

Every resource-detail and file-serving request must apply the current authentication, account-status, role, ownership, resource-status, permission, and `file_availability` checks. Stored file references or old links must never provide direct access outside the controlled application path.

---

## 14. Security and Privacy Considerations

- **Authentication:** Passwords hashed (e.g., via PHP's `password_hash`), session management secured against fixation/hijacking.
- **Authenticated resource access:** BPC LearnShare v1.0 requires users to log in before browsing, searching, viewing, downloading, bookmarking, reporting, or uploading resources. Unauthenticated visitors may only access the login page, the Student registration page, and basic public information pages.
- **Authorization:** Role-based access control enforced server-side on every action, not only hidden in the UI.
- **File upload safety:** Strict allow-list of academic file types (PDF, DOCX, PPTX, TXT, JPG/JPEG, PNG); no executable or script file types accepted; server-side MIME/content validation in addition to extension checks; uploaded files stored outside the public web root or with randomized non-guessable filenames; file size limits enforced.
- **Moderation gate:** No uploaded resource is visible through normal browse or search until approved by a Moderator or Admin.
- **Input validation:** All user input server-side validated; prepared statements used for all database queries to prevent SQL injection; output escaped to prevent XSS.
- **CSRF protection:** State-changing requests (upload, approve, delete, report) protected against cross-site request forgery.
- **Data privacy:** Student and Teacher/Instructor personal data, uploaded academic content, and AI-related processing are handled according to the general principles of the Philippine Data Privacy Act (RA 10173) at a student-project planning level. Clear in-application notice is required before eligible Pending-file content is sent to an external AI provider. Formal lawful-basis determinations, official institutional privacy notices, provider authorization, and other governance requirements remain responsibilities of Bulacan Polytechnic College or the authorized deploying office for any real deployment.
- **AI data exposure risk:** When an external AI provider is used, the minimum necessary eligible extracted content, retrieved excerpts, and supporting metadata may leave the local/LAN application boundary for processing. Approved-resource content may support summaries, suggestions, semantic retrieval, related-resource recommendations, and repository-grounded inquiry only under current status, access, file-availability, processing-readiness, and lifecycle rules. Pending-resource AI assistance remains separate and requires successful basic upload validation, clear uploader notice, and uploader acknowledgment; Pending resources are never used for general repository inquiry. Hidden resources are excluded from new public-facing retrieval and inquiry while Hidden. Rejected, Withdrawn, Restricted, Removed, Replaced, private, unauthorized, and otherwise ineligible resources must not be used for new AI processing or general retrieval.
- **API key handling:** AI API keys stored server-side only (e.g., environment variables/config outside web root), never exposed to client-side code or committed to version control.
- **AI cost and storage control:** An unchanged eligible file should not be repeatedly processed or regenerated when valid current reusable AI output or retrieval-derived data already exists. Stored or cached AI-derived data must follow the source resource lifecycle and stale-source rules. Repository-grounded inquiry answers, retrieved evidence results, citations, and session-scoped follow-up context are not assumed to be permanently stored; exact caching, retention, and provider-cleanup behavior belongs to `AI_FEATURES.md`, `DATA_PRIVACY.md`, and the later architecture/schema decision.
- **Audit trail:** Moderator and admin actions (approvals, rejections, edits, user management) are logged for accountability.

---

## 15. Development Phases

| Phase | Focus | AI Dependency | 
|---|---|---| 
| 1 | Core resource-sharing platform: authentication, roles, upload, moderation, metadata search/filtering, view/download, bookmarks, reports, and dashboard | None | 
| 2 | Organization and management: courses/programs, subjects, year levels, resource types, controlled tags, Helpful marks, reports, and basic resource activity counts | None | 
| 3 | Required AI foundation: readable-text extraction, processing/lifecycle foundation, AI-generated summaries, and suggested controlled tags and metadata | Required v1.0 deliverable; individually configurable and degradable at runtime | 
| 4 | Required semantic retrieval: content-based search and basic related-resource suggestions using eligible extracted content and metadata | Required v1.0 deliverable; individually configurable and degradable at runtime | 
| 5 | Required repository-grounded inquiry: evidence retrieval, grounded responses, source-resource attribution, reliable source locators where available, insufficiency handling, and session-scoped follow-up | Required v1.0 deliverable; individually configurable and degradable at runtime |

Each phase builds on a stable prior phase. Phases 3–5 together implement the required minimum v1.0 AI package defined by `DECISIONS.md` D041–D042. They are required for the completed capstone prototype and defense demonstration but remain layered on top of, and are never prerequisites for, the independently functional non-AI core in Phases 1–2.

After the complete required minimum AI package is stable, duplicate/similar-resource flags and AI moderation hints are targeted as planned v1.0 enhancements. They are not equal-weight minimum defense blockers and are not assigned a separate locked phase by this brief.

---

## 16. Expected Output

A functional web-based prototype of BPC LearnShare, demonstrable on a local XAMPP environment, consisting of:
- A working resource-sharing core (Phases 1–2) usable with zero AI configuration
- The required v1.0 AI capability package across Phases 3–5 and, after that package is stable, the planned duplicate/similarity and moderation-hint enhancements targeted for v1.0; all AI-assisted capabilities remain configurable, non-authoritative, and independently degradable without breaking the non-AI core
- Accompanying documentation: project brief, decisions log, user roles, workflows, database design, AI feature documentation, security notes, data privacy notes, build plan, testing checklist, and changelog
- Source code organized for beginner maintainability, without a major framework dependency

---

## 17. Success Criteria

The project will be considered successful if:

1. Students can self-register and log in, while Teacher/Instructor accounts can be created by an Admin. Active Student and Teacher/Instructor users can upload resources with correct metadata, and uploaded resources go through moderation before becoming publicly visible.
2. Users can search and filter resources by course, subject, year level, topic, resource type, and tags without needing any AI feature enabled.
3. Moderators can approve, reject, or request correction on pending resources, and manage reported resources, through a working dashboard.
4. Admins can manage users, courses/programs, subjects, year levels, resource types, and controlled tags.
5. The core platform (Phases 1–2) remains fully functional when all AI features are disabled or an AI API is unreachable.
6. The required v1.0 AI capability package is implemented and demonstrable at defense. AI-generated summaries and metadata/tag suggestions remain non-authoritative and may be reviewed, edited, or discarded by authorized human users where applicable. The planned duplicate/similarity flags and moderation hints, when implemented, must follow the same non-authoritative rule. AI never takes a final system or moderation action unilaterally.
7. Semantic retrieval and repository-grounded academic resource inquiry operate only on currently eligible Approved resources. Substantive academic claims are grounded in retrieved repository evidence, every substantive response identifies or links its supporting resource or resources, reliable source locators are shown only when available, and the system clearly states when repository evidence is insufficient. 
8. The system passes a documented security and testing checklist covering authentication, authorization, file-upload safety, AI eligibility and lifecycle behavior, source-attribution behavior, and basic SQL-injection/XSS protections. 
9. The system does not exhibit LMS-like, unrestricted-chatbot, general-AI-tutor, or production-campus-deployment scope creep beyond the boundaries defined in this brief.

---

*This document is the source-of-truth project definition for BPC LearnShare. Any decision in DECISIONS.md, USER_ROLES.md, WORKFLOWS.md, DATABASE_DESIGN.md, or AI_FEATURES.md that conflicts with this brief should be flagged and reconciled before implementation proceeds.*

---

## 18. Related Documents

The following documents will support and expand this project brief:
- DECISIONS.md
- USER_ROLES.md
- WORKFLOWS.md
- DATABASE_DESIGN.md
- AI_FEATURES.md
- SECURITY_NOTES.md
- DATA_PRIVACY.md
- BUILD_PLAN.md
- TESTING_CHECKLIST.md
- PROJECT_HANDOFF.md