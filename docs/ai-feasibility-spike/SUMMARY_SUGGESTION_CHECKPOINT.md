# Summary and Controlled Suggestion Checkpoint

**Status:** Offline evaluation contract prepared and validated; no generation executed
**Prepared:** 2026-08-14
**Controlling specification:** `docs/AI_FEASIBILITY_SPIKE.md`, Section 9
**Accepted thresholds:** `docs/ai-feasibility-spike/ACCEPTED_CRITERIA.md`

## Purpose and boundary

This checkpoint prepares a fixed, human-reviewed test contract for the two required capabilities that the registered findings still list as not yet executed:

1. non-authoritative resource summaries; and
2. controlled tag and metadata suggestions.

The preparation is deliberately offline. It reuses accepted local extraction artifacts and does not call Ollama, GroqCloud, or another model/provider. It does not create generated output, register a test run, change a taxonomy row, change a resource, alter the database schema, choose a provider/model, or authorize application integration.

The eventual scored test, if separately approved, will evaluate one or more candidate configurations against this same frozen contract. A candidate name records what was tested; it does not select the final architecture.

## Fixed representative scope

The scope contains eight project-created synthetic primary-readable fixtures: two each for PDF, DOCX, PPTX, and TXT. Every input comes from the accepted `EX-LOCAL-PHP-001` full-corpus extraction run. Boundary-negative fixtures, real user uploads, account data, moderation data, and private institutional material are excluded.

| Reference note | Fixture | Source version | Format | Extracted-input identifier | Characters | Text SHA-256 |
|---|---|---|---|---|---:|---|
| REF-SUMSUG-001 | FX-PDF-001 | SV-FX-PDF-001-001 | PDF | TC-EXT-FULL-001-FX-PDF-001.json | 3,657 | 933de2087b731459e927777fcfbb87a3273504a5bf4c2bf16e0a3f4a580fac58 |
| REF-SUMSUG-002 | FX-PDF-005 | SV-FX-PDF-005-001 | PDF | TC-EXT-FULL-005-FX-PDF-005.json | 2,739 | 9b36353efd484c683f0a4a43e7b5aae97c41de03c70edc5f3c9ba27e732bcd71 |
| REF-SUMSUG-003 | FX-DOCX-002 | SV-FX-DOCX-002-001 | DOCX | TC-EXT-FULL-009-FX-DOCX-002.json | 2,843 | 080dc61bf18f6d560086706f9770a257531ba1876a0335e3145f6f01c964060b |
| REF-SUMSUG-004 | FX-DOCX-003 | SV-FX-DOCX-003-001 | DOCX | TC-EXT-FULL-010-FX-DOCX-003.json | 2,675 | 27f10410a67a3b9a52a7b8cb3358b7df4168ade534bb24a4abe36299d1db92d9 |
| REF-SUMSUG-005 | FX-PPTX-004 | SV-FX-PPTX-004-001 | PPTX | TC-EXT-FULL-017-FX-PPTX-004.json | 1,332 | 0767bf3f3d1191eb11945cb00f541c24d4b1ec6241a213cb83af92bab43efe0d |
| REF-SUMSUG-006 | FX-PPTX-006 | SV-FX-PPTX-006-001 | PPTX | TC-EXT-FULL-019-FX-PPTX-006.json | 2,026 | caac7a400c7b47bd60c22050e692c55913ee020032f67bc66db16a6f44cb1bb8 |
| REF-SUMSUG-007 | FX-TXT-001 | SV-FX-TXT-001-001 | TXT | TC-EXT-FULL-020-FX-TXT-001.json | 1,828 | dfc9097f83bef30ee5e1b736c052848199ca0de46ab6c026349e2baf88988feb |
| REF-SUMSUG-008 | FX-TXT-005 | SV-FX-TXT-005-001 | TXT | TC-EXT-FULL-024-FX-TXT-005.json | 2,109 | 9a8dfe4b30ec48ccf83ac616a34910933f07c15de069d1fdb510259920cdaeaa |

The extraction-candidate identifier for all eight items is `EX-LOCAL-PHP-001`.

## Controlled taxonomy fixture

The test fixture reuses the five active demonstration tags already defined by `database/seeds/seed_demo_taxonomy.php`:

| Test tag ID | Name | Fixture state | Intended representative use |
|---|---|---|---|
| TAG-DEMO-DATABASE | Database | Active | Databases, SQL, normalization, keys, and related concepts |
| TAG-DEMO-PROGRAMMING | Programming | Active | Programming and implementation concepts where directly supported |
| TAG-DEMO-RESEARCH | Research | Active | Research design, sampling, instruments, validity, and reliability |
| TAG-DEMO-SECURITY | Security | Active | Security, privacy controls, validation, escaping, authentication, and access control |
| TAG-DEMO-USABILITY | Usability | Active | Usability, accessibility, interface consistency, and user feedback |
| TAG-SPIKE-INACTIVE-REQUIREMENTS | Requirements | Inactive | Test-only historical value; unavailable for a new assignment |
| TAG-SPIKE-INACTIVE-DATA-PRIVACY | Data Privacy | Inactive | Test-only historical value; unavailable for a new assignment |

The two Inactive rows exist only inside this spike fixture. They are not inserted into the application database. Any absent name is out of vocabulary. An Inactive or out-of-vocabulary suggestion may be recorded as a quality finding, but it must never create, activate, rename, or assign a taxonomy value.

## Metadata subset

The fixed scored subset is:

- `subject` — controlled by the existing four demonstration subjects;
- `resource_type` — controlled by the existing six demonstration resource types; and
- `topic` — descriptive text that must be concise and source-supported.

`course/program` and `year_level` are excluded because the selected file contents do not consistently establish them. This is intentional: a correct `not reliably inferable` result is better than an overconfident suggestion.

## Human-reviewed reference notes

These notes describe acceptable content and known boundaries. They are evaluation aids, not required wording and not an academic or institutional correctness certification.

### REF-SUMSUG-001 — Database Normalization Study Guide

- A useful summary should cover normalization's purpose, update/insertion/deletion anomalies, First through Third Normal Form, the BPC LearnShare database examples, and the caution that denormalization is an intentional tradeoff rather than a shortcut.
- Do not invent a required production schema, performance result, fourth or fifth normal form, or a claim that normalization guarantees a perfect design.
- Active tag expected: `Database`. Other active tags are not clearly required.
- Supportable metadata: subject `Database Management Systems`; resource type `Study Guide`; topic similar to `Database normalization and relational design`.
- Unsupported or overconfident metadata: a course/program, year level, official subject code, or a different controlled subject.

### REF-SUMSUG-002 — Philippine Data Privacy Principles for Student Systems

- A useful summary should cover purpose limitation, data minimization, clear uploader notice for Pending-file AI, external-provider review, session-scoped inquiry context, and the limitation that the fixture is not legal advice or institutional certification.
- Do not invent a legal-compliance guarantee, mandatory production architecture, named provider approval, permanent chat history, or permission to transmit unrelated account/moderation data.
- Active tag expected: `Security`. `Data Privacy` is a clearly related but Inactive test value and is unavailable for assignment.
- Supportable metadata: topic similar to `Data privacy principles for student systems`. Subject and resource type may correctly be reported as not reliably inferable.
- Unsupported or overconfident metadata: a law-course assignment, a year level, institutional approval, or any new custom controlled value.

### REF-SUMSUG-003 — Functional and Nonfunctional Requirements Reviewer

- A useful summary should distinguish functional behavior from nonfunctional qualities/constraints, emphasize testable requirements and acceptance criteria, give bounded BPC LearnShare examples, and preserve the listed out-of-scope LMS/tutoring functions.
- Do not invent additional v1.0 modules, enterprise guarantees, unlimited AI performance, or a claim that broad requirements are already testable.
- No Active demo tag is clearly required. `Requirements` is an Inactive test value and must not be assigned; forcing an unrelated Active tag is a quality failure.
- Supportable metadata: subject `Systems Analysis and Design`; resource type `Reviewer`; topic similar to `Functional, nonfunctional, and testable requirements`.
- Unsupported or overconfident metadata: course/program, year level, or an LMS/online-class classification.

### REF-SUMSUG-004 — Input Validation and Output Escaping Notes

- A useful summary should distinguish server-side validation from output escaping, explain allowlists and prepared statements, cover upload validation, state that validation does not replace authorization, and mention safe actionable error messages.
- Do not claim that escaping replaces validation, that validation makes a file AI-ready, or that a valid resource identifier proves authorization.
- Active tags expected: `Security`; `Programming` is also directly relevant and acceptable.
- Supportable metadata: subject `Web Systems and Technologies`; resource type `Notes`; topic similar to `Input validation, output escaping, and authorization boundaries`.
- Unsupported or overconfident metadata: a course/program, year level, automatic security certification, or a claim that client-side validation is sufficient.

### REF-SUMSUG-005 — UI Consistency and Accessibility

- A useful summary should cover consistent actions/labels/layouts, accessibility basics, useful feedback and error messages, and the distinction between accessibility and authorization.
- Do not claim complete standards compliance, accessibility certification, or that accessible controls grant permission.
- Active tag expected: `Usability`.
- Supportable metadata: subject `Web Systems and Technologies`; resource type `Presentation`; topic similar to `UI consistency, accessibility, and user feedback`.
- Unsupported or overconfident metadata: a course/program, year level, legal accessibility compliance, or a Security tag without direct justification.

### REF-SUMSUG-006 — SDLC and Capstone Planning

- A useful summary should cover planning and requirements, design before coding, small phased implementation, testing/review, maintenance, and handoff alignment for a realistic capstone MVP.
- Do not invent a mandatory methodology, fixed schedule, completed implementation, LMS scope, or proof that the project is production-ready.
- No Active demo tag is clearly required; the test must allow an empty tag suggestion.
- Supportable metadata: subject `Systems Analysis and Design`; resource type `Presentation`; topic similar to `SDLC and phased capstone planning`.
- Unsupported or overconfident metadata: course/program, year level, a specific agile/waterfall mandate, or forced unrelated tags.

### REF-SUMSUG-007 — SQL Terminology Quick Reference

- A useful summary should describe the resource as a compact reference for tables/rows/columns, keys and constraints, joins, junction tables, indexes, and transactions, including its MariaDB/BPC LearnShare examples.
- Do not invent SQL syntax, benchmark results, database-engine guarantees, or claim that database constraints replace PHP business rules.
- Active tag expected: `Database`.
- Supportable metadata: subject `Database Management Systems`; topic similar to `SQL and relational database terminology`. Resource type may correctly be reported as not reliably inferable because `Quick Reference` is not an available controlled resource type.
- Unsupported or overconfident metadata: course/program, year level, or an out-of-vocabulary resource type silently treated as controlled.

### REF-SUMSUG-008 — Research Methods Glossary

- A useful summary should describe the glossary's coverage of research design, population/sample/sampling, quantitative and qualitative research, validity/reliability, instruments, pilot testing, scope of findings, and honest capstone limitations.
- Do not invent a required sample size, campus-wide generalizability, study results, or a claim that convenience sampling represents the whole campus.
- Active tag expected: `Research`.
- Supportable metadata: subject `Research Methods`; topic similar to `Research design, sampling, validity, and reliability`. Resource type may correctly be reported as not reliably inferable because `Glossary` is not an available controlled resource type.
- Unsupported or overconfident metadata: course/program, year level, exact participant count, or a campus-wide evidence claim.

## Required future output contract

Each separately approved generation attempt must preserve the fixture/source/extraction/reference identifiers and return reviewable fields for:

- one concise non-authoritative summary;
- zero or more tag suggestions using only fixture tag IDs;
- `subject`, `resource_type`, and `topic` suggestions, with an explicit `not reliably inferable` option;
- no taxonomy mutation or direct metadata write; and
- no moderation or resource-status action.

The prompt and structured response schema must be frozen and reviewed before a live request. A local run may be authorized separately from an external run. For an external run, exact payloads, current terms/retention, model allowlist, quotas, request ceiling, and estimated cost must be reviewed again. No retry is allowed unless a later procedure predeclares and justifies one.

## Scoring and decision rules

The accepted thresholds remain unchanged:

- summaries: at least 80% `Pass`;
- summaries: at least 95% `Pass` or `Needs light review/edit`;
- any material unsupported or contradicted claim fails the affected summary;
- directly usable tag suggestions: at least 80% relevant and Active;
- resources with clearly relevant Active tags: at least 75% receive at least one;
- metadata suggestions: at least 80% relevant and source-supported; and
- scored summary/suggestion outputs: at least 80% usable as-is or after light human editing.

Every generated item must also record the Section 9.6 measurement fields, including the candidate/runtime, prompt version, non-secret settings, sizes, timestamps, latency, result class, quality judgments, unsupported-content findings, failure behavior, retry occurrence, quota/cost effect, and reviewer notes. Secrets must never enter evidence.

## What this checkpoint does not decide

Passing the offline validator means only that the experiment is ready for payload preparation. It does not prove that summaries or suggestions are good, authorize a live request, accept a candidate, add an application route/UI, approve automatic metadata, change the taxonomy, select storage, or permit schema changes.

If the next preparation step is not approved, the project remains safe and the two capabilities remain unresolved. The working non-AI repository and all already measured AI evidence remain valid.

## Offline validation result

On 2026-08-14, `tests/ai/run_gate5e_summary_suggestion_validate.php --mode=validate` passed 234/234 checks. It verified the frozen schema/register/taxonomy hashes, the 30-row fixture register, all eight accepted extracted inputs, two-per-format coverage, all eight reference-note bindings, five seed-backed Active tags, two non-persistent test-only Inactive values, the three-field metadata subset, the unchanged accepted thresholds, and the non-authority boundary.

The validator made zero Ollama/provider/network requests, read zero credentials, generated zero summaries or suggestions, and changed zero taxonomy, database, schema, or register rows. Live generation remains unauthorized.
