# AI_FEASIBILITY_SPIKE.md

**Project:** BPC LearnShare — AI-Assisted Collaborative Academic Resource Sharing and Management System
**Version:** Draft v1.0 — Feasibility-Spike Specification (Sections 1–26)
**Last Updated:** 2026-07-13
**Author:** Nepthalie Jezer B. Macaslang
**Course:** BS Information Systems — Bulacan Polytechnic College
**Status:** Specification complete and accepted for pre-run use. The feasibility spike has not yet been executed. This document is not an architecture or schema decision.

---

## 1. Purpose and Decision Questions

This document defines a bounded, measurement-focused feasibility spike for the required minimum v1.0 AI capability package defined in `DECISIONS.md` D042 Part A.

Its purpose is to produce measured evidence — not assumptions — about whether the candidate architecture direction described in Section 4 is adequate for the representative v1.0 MVP corpus and expected capstone-defense use before any final architecture or schema decision is recorded.

The spike must help answer, through measurement rather than assumption:

* **Architecture questions** — Is bounded application-side similarity retrieval in native PHP practical for the representative v1.0 MVP corpus, or does measured evidence justify considering a different retrieval layer? Is keeping MariaDB 10.4 as the current primary application database compatible with the simplest workable AI direction, or does measured evidence justify reconsidering part of the architecture?

* **Quality questions** — Does extraction preserve enough readable text and source-location information to support faithful summaries, relevant controlled tag and metadata suggestions, useful semantic retrieval, grounded inquiry responses, reliable source-resource attribution, and accurate locators where available?

* **Performance questions** — What extraction, embedding, retrieval, and generation times are observed on the current development hardware, and are the results practical for the bounded local/LAN academic MVP rather than production-scale use?

* **Compatibility questions** — Does the candidate pipeline behave consistently across representative readable PDF, DOCX, PPTX, and TXT resources and handle extraction, processing, retrieval, and generation failures without creating unsupported results or breaking another independently usable capability?

* **Maintainability questions** — Is the candidate direction understandable, testable, debuggable, and maintainable by a small BS Information Systems student team using native PHP and narrowly justified helper libraries without a major application framework or unnecessary infrastructure?

* **Cost and dependency questions** — What latency, quota, cost, availability, configuration, and provider-dependence implications are observed for any external service tested? These are point-in-time observations and must not be treated as permanent guarantees.

* **Security and privacy questions** — Does the tested pipeline minimize external payloads, avoid unsafe logging, respect current eligibility and lifecycle conditions inside the spike harness, and avoid exposing unrelated account, resource, moderation, or system data?

* **Fallback questions** — When an AI-assisted operation is disabled, unavailable, stale, unready, or forced to fail inside the bounded spike environment, does only the affected capability fail while valid non-AI or independently ready behavior remains available?

**The spike is a bounded decision-support activity.** It is not:

* the full AI implementation;
* a final architecture decision;
* a schema revision;
* `AI_FEATURES.md`;
* `BUILD_PLAN.md`;
* `TESTING_CHECKLIST.md`;
* a production-scale retrieval-augmented generation platform;
* a cloud migration;
* a campus-scale performance project;
* an enterprise AI benchmark.

Its results become evidence for the later final recommendation in Section 25 and the separate architecture/schema decision process described in Section 26.

---

## 2. Source Constraints and Current Baseline

### 2.1 Governing Decisions and Source Authority

This spike is bound by the accepted project decisions and must not be designed, measured, or interpreted in a way that contradicts them.

* **D004 — Core platform independence from AI**

  The core non-AI platform must remain usable when AI is disabled, unavailable, unconfigured, rate-limited, failing, or unreachable.

  During this spike, bounded harness tests must verify failure isolation within the tested AI path and must not imply that the unbuilt full application has already passed complete fallback or regression testing.

  Full application-level confirmation that authentication, upload, moderation, metadata search/filtering, resource access, bookmarks, Helpful marks, reports, notifications, and Admin/Moderator management remain functional belongs later in implementation and `TESTING_CHECKLIST.md`.

* **D013 — Assistive and non-authoritative AI**

  AI remains assistive and non-authoritative at runtime.

  No result produced during this spike may be interpreted as a final academic determination, moderation decision, authorization decision, or automatic system action.

* **D014 — Status-based AI eligibility**

  General semantic search, related-resource evaluation, and repository-grounded inquiry tests use only resources represented in the spike harness as currently Approved, accessible, file-available, successfully processed, current, non-stale, and otherwise eligible.

  Pending-resource AI assistance remains a separate context and is not the general repository-inquiry path being measured.

* **D015 — No unilateral AI action**

  AI must not approve, reject, publish, validate, Hide, Restrict, Remove, delete, change a resource status, create or modify taxonomy values, or execute another final human decision.

  No spike scenario may require, imply, or simulate AI authority to perform those actions.

* **D018 — Source-bound AI-output lifecycle**

  AI output remains derived data tied to one source resource.

  Output and retrieval-derived data from an original resource must not be inherited by its replacement. Later lifecycle and replacement tests must preserve independent processing and source association.

* **D041 — Required completed-capstone AI capability**

  The required minimum AI package, including repository-grounded academic resource inquiry, is a required completed-capstone deliverable rather than an optional Phase 5 stretch feature.

  The spike exists because measured feasibility evidence is required before the package is implemented and demonstrated.

* **D042 — Required, Planned, and Deferred capability tiers and feasibility-gated direction**

  D042 defines:

  * the Required minimum v1.0 capability package;
  * the Planned v1.0 enhancement tier;
  * the Deferred tier;
  * the non-binding candidate architecture direction;
  * the rule that no exact retrieval-related table, column, index, store, provider, model, or architecture is approved before feasibility testing and a later explicit architecture/schema decision.

**D016 is superseded.**

Any wording elsewhere in the source documents that still frames repository-grounded inquiry as an optional Phase 5 stretch capability reflects the pre-D041 position and is not controlling.

`SECURITY_NOTES.md` and `DATA_PRIVACY.md` were completed before the D041–D042 revision and retain some older optional-inquiry wording. This is a known, non-blocking textual conflict. D041 expressly supersedes that wording, and targeted AI/retrieval propagation into those documents remains scheduled after architecture selection.

This spike:

* does not wait for that later propagation before proceeding;
* does not reopen D001–D042;
* does not edit `SECURITY_NOTES.md` or `DATA_PRIVACY.md`;
* must continue to preserve their accepted security and privacy principles where those principles do not conflict with D041–D042.

### 2.2 Known Hardware Context and Pending Clean Baseline

The current development machine is recorded as known hardware context:

* Intel Core i7-7500U;
* approximately 12 GB RAM;
* NVIDIA GeForce 940MX;
* 2 GB VRAM.

These specifications are planning inputs, not completed feasibility results.

Current status:

* An earlier `llmfit` scan was collected while several heavy applications were open.
* The earlier result is useful only as an initial observation and is **not** the accepted clean benchmark.
* Lightweight local embedding generation appears plausible but remains unverified.
* Actual extraction, embedding, retrieval, and concurrent-runtime behavior remain unverified.
* Local answer-generation feasibility remains uncertain.
* No hardware feasibility conclusion has been accepted.

The clean-baseline checkpoint does not occur during this drafting step.

The accepted order remains:

1. complete and accept the four-part feasibility-spike specification;
2. separately verify the accepted current `schema.sql` baseline in the target XAMPP/MariaDB environment without redesigning it;
3. restart Windows;
4. close unnecessary heavy applications;
5. run `llmfit system`;
6. run `nvidia-smi`;
7. record the clean hardware and runtime baseline;
8. execute the accepted feasibility spike.

### 2.3 Document and Decision Boundaries

This specification defines:

* what the feasibility spike will test;
* what measurements will be collected;
* how evidence will be recorded;
* how results will later be interpreted.

It is a planning and measurement-design document.

This document must not:

* select a final provider;
* select a final model;
* select a final embedding approach;
* select a final local model runtime;
* approve a MariaDB upgrade;
* approve a vector database;
* approve a second database;
* approve a hosted retrieval service;
* approve a retrieval-storage format;
* approve a new table or column;
* modify `DATABASE_DESIGN.md`;
* modify `schema.sql`;
* redefine a role;
* redefine an account status;
* redefine a resource status;
* redefine a report status;
* redefine a permission;
* introduce a new workflow or module;
* become `AI_FEATURES.md`;
* become `BUILD_PLAN.md`;
* become `TESTING_CHECKLIST.md`.

The spike may produce evidence suggesting that a later architecture or schema change should be considered. That evidence is not approval by itself.

The final recommendation defined in Section 25 becomes an input to a future explicit architecture/schema decision.

Only after that later decision may:

* `DATABASE_DESIGN.md` and `schema.sql` receive justified architecture-driven changes;
* `SECURITY_NOTES.md` receive targeted retrieval-specific security propagation;
* `DATA_PRIVACY.md` receive targeted retrieval-specific privacy propagation;
* `AI_FEATURES.md` define the selected feature behavior and provider/model integration;
* `BUILD_PLAN.md` sequence implementation;
* `TESTING_CHECKLIST.md` define complete implementation-level verification.

---

## 3. Scope and Non-Goals

### 3.1 In-Scope Required Capabilities

This spike covers the D042 Part A required minimum v1.0 AI capability package:

* readable-text extraction for supported PDF, DOCX, PPTX, and TXT resources where extraction succeeds;
* processing readiness;
* processing failure;
* independent capability readiness and failure behavior;
* stale-source detection and exclusion;
* AI-generated resource summaries;
* AI-suggested controlled tags and metadata;
* semantic content-based search;
* repository-grounded academic resource inquiry;
* source-resource attribution;
* accurate page, slide, section, heading, or equivalent locators where reliably preserved;
* omission rather than fabrication when a reliable locator is unavailable;
* clear insufficient-evidence behavior;
* session-scoped conversational follow-up;
* basic related-resource suggestions;
* graceful non-AI fallback and independent failure isolation.

### 3.2 Out-of-Scope Planned and Deferred Capabilities

#### Planned v1.0 enhancements

The following D042 Part B capabilities remain outside this required-package spike:

* duplicate/similar-resource indicators;
* AI moderation hints.

The extraction, embedding, retrieval, quality, latency, and lifecycle evidence produced for the Required package may later help evaluate the feasibility of those Planned enhancements.

However, this spike does not:

* execute a duplicate-detection evaluation track;
* score duplicate or similarity indicators;
* generate or score moderation hints;
* expand the Required-package evaluation set to implement either Planned enhancement.

Both remain later work after the complete Required package is stable.

#### Deferred beyond v1.0

The following remain outside this spike:

* OCR;
* AI vision;
* persistent cross-session AI memory;
* permanent application chat history unless separately approved;
* open-web retrieval;
* automatic web browsing;
* unrestricted general-purpose AI tutoring;
* personalized learner profiles;
* behavioral recommendation profiles;
* AI-generated quizzes;
* AI-generated graded assessments;
* grading;
* answer checking;
* autonomous moderation;
* automatic resource-status actions;
* training or fine-tuning a new model from scratch.

### 3.3 Non-Goals

The spike does not:

* select or lock a commercial provider;
* select or lock a hosted AI API;
* select or lock a generation model;
* select or lock an embedding model;
* select or lock a local runtime;
* design or approve final application architecture;
* design or approve final retrieval architecture;
* design a database schema;
* add or modify a database table;
* add or modify a database column;
* assume a database-engine upgrade;
* assume a vector database;
* assume a second database;
* assume cloud migration;
* assume hosted retrieval infrastructure;
* introduce enterprise orchestration, distributed queues, managed vector infrastructure, or production monitoring;
* produce production-ready implementation code;
* replace complete security testing;
* replace user-acceptance testing;
* perform campus-scale load testing;
* claim production readiness;
* add LMS functionality;
* add grading, assessment, enrollment, attendance, class-record, assignment-submission, or classroom-management behavior.

### 3.4 Spike-Harness and Final-Implementation Boundary

The feasibility spike may use:

* bounded experimental scripts;
* controlled fixtures;
* controlled sample records;
* temporary measurement artifacts;
* a small purpose-built measurement harness.

These may be used only to test the candidate pipeline and collect evidence against the representative corpus and evaluation set.

The spike harness is:

* a bounded measurement artifact;
* not the final AI module;
* not approved production code;
* not the final application architecture;
* not a schema revision;
* not proof that the complete application has passed integration or regression testing.

The following remain later work:

* complete application-level implementation of each AI-assisted capability;
* final integration with live account, resource, file, permission, moderation, lifecycle, and system-setting behavior;
* complete application fallback verification;
* end-to-end regression testing;
* complete authorization and access-control testing;
* complete security testing;
* user-acceptance testing with representative users;
* final deployment configuration;
* production or institutional deployment review.

Where the spike must approximate accepted workflow conditions, such as an eligible Approved resource or a resource becoming stale, it must use controlled fixture state or isolated test records.

The spike must not:

* require a change to `schema.sql`;
* imply that a fixture is a new production table or column;
* treat simulated eligibility as a replacement for later live application revalidation;
* use real production accounts or production data.

---

## 4. Candidate Architecture Direction Under Test

The following is the non-binding candidate direction being measured, based on D042 Part E and the current `PROJECT_HANDOFF.md`:

* Native PHP remains the application/backend stack.
* MariaDB 10.4 remains the current primary application database and current local environment.
* The spike does **not** presume that extracted text, chunks, embeddings, retrieval indexes, or other retrieval-derived data will be stored in MariaDB.
* Local text extraction is tested where practical.
* Lightweight local embedding generation is tested where practical.
* Similarity retrieval is tested as a bounded application-side computation in PHP over the representative corpus.
* External generation is tested where needed to evaluate acceptable grounded-answer quality and response time.
* Local answer generation may be tested only as an optional experimental fallback.

This is a candidate direction being measured, not accepted architecture.

No part of this section selects or approves:

* Groq;
* Ollama;
* Hugging Face;
* Supabase;
* pgvector;
* MariaDB 11.7 or newer vector features;
* a specific embedding model;
* a specific generation model;
* a hosted vector database;
* a local model runtime;
* a MariaDB upgrade;
* a second database;
* a retrieval-storage format;
* a final retrieval algorithm;
* a final persistence approach.

Temporary spike artifacts may use the simplest isolated mechanism needed to collect measurements.

That temporary mechanism:

* must not modify the accepted 18-table schema;
* must not overload `ai_outputs`;
* must not be treated as the final retrieval-storage architecture;
* must not create an assumption that retrieval-derived data requires permanent database storage.

Any named technology appearing during later spike execution remains a test candidate unless a later explicit architecture decision selects it.

---

## 5. Representative Test Corpus and Evaluation Set

### 5.1 Primary Readable Corpus Composition and Size

The spike uses an initial target of approximately **25–50 representative resources treated as currently eligible Approved-resource fixtures**, or an equivalent realistic bounded chunk count.

The primary readable corpus must include representative:

* PDF resources;
* DOCX resources;
* PPTX resources;
* TXT resources.

The corpus should contain:

* short handouts;
* medium-length reviewers or study guides;
* longer modules or reference materials;
* plain text;
* structured headings;
* tables where relevant;
* multi-column or visually complex formatting where practical;
* overlapping topics across multiple resources;
* clearly related resources;
* clearly unrelated resources;
* resources containing similar terminology but different meanings;
* enough topical overlap to test retrieval ambiguity rather than produce only trivial one-document matches.

The primary corpus is the set used to evaluate:

* readable extraction;
* extraction correctness;
* locator preservation;
* embedding feasibility;
* retrieval relevance;
* semantic-search usefulness;
* grounded inquiry;
* source attribution;
* session follow-up;
* related-resource relevance.

Known invalid files and deliberately non-extractable boundary samples are maintained separately under Section 5.3 and must not be counted as ordinary eligible readable resources unless a later measurement explicitly defines a separate boundary-case denominator.

This corpus is not intended to represent:

* campus-wide scale;
* production load;
* production storage requirements;
* full BPC repository volume.

The spike must not expand toward those goals.

### 5.2 Corpus Sourcing, Authorization, and Privacy

Every test resource must have a recorded and defensible sourcing basis.

Preferred content includes:

* project-created test materials;
* public-domain materials;
* openly licensed materials whose license permits the intended use;
* materials with explicit authorization for the intended testing;
* deliberately created synthetic academic fixtures.

For each corpus item, the spike evidence register should record:

* source or creator;
* title or test identifier;
* file type;
* license, public-domain status, project-created status, or authorization basis;
* whether the content contains personal or sensitive information;
* whether local-only testing is allowed;
* whether transmission to an external AI provider is allowed;
* any restrictions on reuse or redistribution.

“Available online” or “already present in a repository” is not sufficient by itself to establish authorization for external AI transmission.

Real Student- or Teacher/Instructor-authored content must not be sent to an external provider merely because it exists in the repository or because an account uploaded it.

Anything transmitted externally during the spike must be deliberately selected as safe and authorized for that test.

The corpus and external payloads must preserve:

* purpose limitation;
* data minimization;
* minimum practical payloads;
* exclusion of unrelated account information;
* exclusion of unrelated resource content;
* exclusion of unrelated reports, moderation history, notifications, audit data, and system information;
* provider-specific review before relying on provider behavior or terms.

### 5.3 Separate Boundary, Limitation, and Negative-Input Set

Boundary and negative-input samples must be maintained separately from the primary readable Approved-resource corpus.

This separate set may include:

* scanned or image-only content with no readable embedded text;
* a scanned PDF used to confirm the accepted no-OCR limitation;
* a structurally valid but non-extractable file where such a case can be prepared safely;
* an empty file;
* a corrupt or unreadable test file;
* another justified extraction or preprocessing failure sample.

Different cases serve different purposes:

* **Image-only or scanned resources** may remain valid repository resources but are expected to be unavailable for content-based AI when no readable text can be extracted. They are limitation cases, not OCR or AI-vision requirements.

* **Empty, corrupt, unreadable, or otherwise invalid upload samples** are negative upload-validation fixtures. They must not be represented as Approved resources. Under the accepted upload workflow, these files should be rejected before resource creation and before AI processing.

* **Extractor robustness tests**, where justified, may invoke an extraction component directly against an isolated invalid-file fixture to observe safe failure behavior. Such a direct component test does not mean that the final application may allow the invalid file to bypass upload validation.

The specification must keep separate measurements for:

* readable-corpus extraction success;
* valid but non-extractable limitation cases;
* invalid-file rejection or safe component failure.

The spike does not add:

* OCR;
* AI vision;
* automated document repair;
* production malware scanning.

### 5.4 Evaluation Query and Expected-Evidence Set

The spike requires a small, reviewable, deliberately constructed evaluation set.

It must include:

* **metadata-friendly queries** — questions or searches likely to work through exact title, subject, topic, resource type, controlled tag, or keyword matching and used as a comparison baseline;

* **semantic or paraphrased queries** — requests that express a concept using wording different from the relevant source text;

* **legitimate study and explanation questions** — requests for explanation, comparison, clarification, or summarization that may be answered from eligible repository evidence without completing prohibited academic work;

* **single-resource questions** — questions clearly supported by one expected resource;

* **multi-resource questions** — questions requiring evidence from more than one expected resource;

* **ambiguous questions** — questions plausibly matching multiple resources and requiring relevant evidence selection;

* **partially supported questions** — questions where only part of the requested information is supported, testing whether the response limits itself to available evidence;

* **unsupported questions** — questions deliberately unsupported by the eligible corpus and expected to trigger insufficient-evidence behavior;

* **clearly prohibited academic requests** — direct requests to answer or complete an examination, quiz, graded assignment, answer key, grading task, or equivalent prohibited academic work;

* **session-scoped follow-up questions** — later turns that depend on active-session context without creating permanent cross-session memory;

* **mid-session eligibility-change cases** — follow-up scenarios where previously retrieved evidence becomes ineligible and must no longer be used;

* **related-resource examples** — a selected starting resource with at least one genuinely related and one genuinely unrelated comparison resource;

* **reliable-locator cases** — questions supported by files where page, slide, heading, section, or equivalent location is expected to be preserved;

* **locator-unavailable cases** — questions supported by files where a reliable locator is not expected to be available, testing correct omission rather than fabrication.

Before spike execution, every evaluation item should have a recorded expected-evidence assessment where practical.

The assessment should identify:

* evaluation-item ID;

* query or request;

* evaluation category;

* expected outcome:

  * answerable;
  * partially answerable;
  * insufficient evidence;
  * prohibited request;

* expected supporting resource or resources;

* manually verified supporting passage or evidence;

* manually verified source location where available;

* whether the tested extraction path is expected to preserve that locator;

* expected unrelated or misleading resources that should not dominate retrieval;

* notes needed for later human evaluation.

The manually verified source location is evaluator ground truth.

Its existence does not mean the system is allowed to display that locator unless the selected extraction path preserves it reliably.

The expected-evidence set must be prepared before executing the scored evaluation so retrieval, grounding, attribution, insufficiency, refusal, and locator behavior are not judged only through post-hoc impression.

---

# 6. Readable-Text Extraction Tests

## 6.1 Extraction Objectives and Test Inputs

Extraction feasibility must establish whether readable text can be obtained from each required file type with sufficient correctness, ordering, coverage, and source association to support downstream summaries, controlled tag and metadata suggestions, semantic retrieval, related-resource suggestions, and repository-grounded inquiry.

Test inputs are drawn from two clearly separated fixture sets:

* **Primary readable corpus** — the representative PDF, DOCX, PPTX, and TXT resources accepted in Section 5.1 and treated as currently eligible Approved-resource fixtures for purposes of the spike.

* **Boundary, limitation, and negative-input fixtures** — the image-only, non-extractable, empty, corrupt, and other justified failure examples accepted in Section 5.3. These remain isolated extraction-limit or validation-robustness cases and are never treated as ordinary Approved-resource fixtures.

Every fixture receives:

* one stable fixture ID;
* one recorded baseline source-file identifier;
* one immutable baseline copy used for repeatable comparison;
* a source-version identifier where a controlled modified version is created for freshness testing.

Repeated tests must use the same recorded fixture version unless the test explicitly evaluates source change or staleness.

A controlled modified source used in Section 8 must remain linked to the same fixture ID but use a distinct source-version identifier. It must not silently overwrite the recorded baseline evidence.

No final extraction library is selected in this section.

A narrow, justified helper library or extraction method may later be tested, but any named package, command, runtime, or extraction component remains a **spike candidate only**, not an approved dependency.

## 6.2 Per-File-Type Extraction Procedure

A repeatable procedure is defined for every tested extraction candidate and every required readable format.

The procedure evaluates extraction of:

* plain text;
* headings;
* paragraphs;
* ordered and unordered lists;
* tables where present;
* multi-column or otherwise visually complex layouts where present;
* page boundaries where available and reliable;
* slide boundaries where available and reliable;
* character encoding;
* punctuation and special characters;
* ordering of extracted content relative to the source document’s intended reading order.

The following format-specific considerations are included:

* **PDF** — evaluate whether text order remains usable across ordinary, multi-column, table-heavy, or otherwise complex readable PDFs and whether page association is preserved.

* **DOCX** — evaluate paragraphs, headings, lists, tables, and document order without assuming fixed page numbers.

* **PPTX** — evaluate slide order, text-box ordering, titles, bullet content, tables where present, and reliable slide association.

* **TXT** — evaluate complete readable text, encoding, line ordering, and heading or block structure only where the source itself contains a reliable structure.

Perfect visual-layout preservation is not a goal or pass condition.

The purpose is to produce:

* usable academic text;
* sufficiently correct reading order;
* meaningful structural separation where feasible;
* reliable source-location information where the extraction method actually preserves it.

The spike must not reject an extraction result solely because fonts, colors, exact spacing, visual positioning, or decorative layout are not reproduced.

## 6.3 Extraction Correctness and Coverage Review

A human reviewer compares every tested primary-corpus extraction result against the original source file.

The reviewer records:

* extraction outcome:

  * successful;
  * partially successful;
  * unsuccessful;

* missing text;

* duplicated text;

* incorrect reading order;

* merged or fragmented passages;

* corrupted or substituted characters;

* lost headings;

* lost list structure;

* table degradation;

* missing page or slide separation where the tested method was expected to preserve it;

* qualitative extraction completeness;

* whether the output remains usable for:

  * summary generation;
  * tag suggestions;
  * metadata suggestions;
  * embedding generation;
  * semantic retrieval;
  * related-resource evaluation;
  * repository-grounded inquiry.

The review must distinguish:

* a minor formatting loss that does not materially affect meaning;
* a structural loss that weakens downstream use;
* a content loss or ordering error that changes or obscures meaning.

No final numeric pass/fail threshold is established in this section.

Any quantitative threshold, qualitative minimum, mandatory failure condition, or interpretation rule used for the final feasibility decision must appear as a proposed pre-run criterion in Section 23 and must be accepted before scored spike execution.

## 6.4 Extraction Performance and Resource Use

For every tested primary-corpus item and extraction candidate, record:

* fixture ID;
* source-version ID;
* file type;
* file size;
* page count where applicable;
* slide count where applicable;
* paragraph count or approximate content length where useful;
* extraction start time;
* extraction completion time;
* wall-clock processing time;
* extracted-text size;
* CPU utilization or representative CPU observation;
* peak or representative RAM use;
* GPU use only when the tested extraction method actually uses the GPU;
* VRAM use only when applicable;
* warnings;
* errors;
* whether the extraction completed normally, partially, or unsuccessfully.

The same measurement method should be used across comparable candidate runs where practical.

These measurements are limited to evaluating a bounded local/LAN academic MVP.

This section does not define:

* campus-scale processing throughput;
* concurrent production-worker targets;
* service-level objectives;
* institutional-scale ingestion capacity;
* production availability targets.

## 6.5 Boundary and Failure Behavior

Separate, clearly labeled cases are maintained for:

* image-only content without OCR;
* scanned PDF content without OCR;
* valid but non-extractable content;
* empty files;
* corrupt files;
* unreadable files;
* unsupported or malformed internal document structures;
* other justified extraction failures relevant to the allowed resource types.

The following distinctions remain mandatory.

### Valid but unavailable for content-based AI

Image-only resources, scanned PDFs, and other valid resources without usable extractable text may remain valid repository resources.

They may continue through applicable non-AI repository workflows but are unavailable for content-based AI capabilities when readable extraction does not succeed.

This is an accepted v1.0 limitation, not an OCR or AI-vision requirement.

### Invalid upload fixtures

Empty, corrupt, unreadable, or otherwise invalid upload files are expected to fail basic upload validation in the completed application.

They must not:

* create an ordinary resource record;
* become Pending;
* become Approved;
* enter AI processing.

Where an invalid fixture is passed directly to an extraction component during isolated spike testing, that direct component test exists only to observe safe failure behavior.

It does not authorize or imply an application-level bypass of upload validation.

### Safe failure result

For each failure case, record:

* the fixture category;
* whether failure occurred safely;
* whether partial or unusable text was incorrectly presented as successful;
* whether the tested method exposed a raw stack trace, secret, sensitive path, or unnecessary low-level provider/runtime detail;
* the short non-technical user-facing outcome that a later implementation should be capable of presenting.

Examples include:

* `Readable text could not be extracted from this resource.`
* `Content-based AI is unavailable for this file.`
* `The file could not be processed.`

Exact final interface wording remains for later implementation documents.

This section does not add:

* OCR;
* AI vision;
* automated document repair;
* automatic content recovery;
* production malware scanning.

## 6.6 Extraction Evidence to Record

Extraction evidence is organized into separate categories.

### Dedicated spike evidence files

The spike working area may retain, where authorized:

* extracted-text output;
* structural extraction output;
* captured locator metadata;
* extraction-candidate identifier;
* fixture and source-version identifiers.

These are dedicated feasibility artifacts.

They are not:

* application audit records;
* resource-action history;
* production application logs;
* new database records.

### Aggregate measurement records

The measurement register records:

* timings;
* source and output sizes;
* resource-use observations;
* success/partial/failure classifications;
* extraction-quality findings;
* locator-availability findings;
* downstream-usability assessments.

### Short error summaries

Failures should use a short, reviewable description such as:

* `PPTX-F012: no readable text returned`;
* `PDF-F021: reading order materially incorrect`;
* `DOCX-F008: extraction failed safely on corrupt fixture`.

Error summaries must avoid unnecessary duplication of source content.

### Content that must not enter application accountability logs

Full extracted document text, full uploaded files, full generated summaries, full provider payloads, or full model responses must not be copied into:

* `audit_log`;
* `resource_action_history`;
* notifications;
* ordinary application logs.

The spike does not create a database table, processing-history structure, extraction-history structure, or application logging requirement.

Evidence remains inside the controlled spike evidence set and follows the corpus authorization and privacy rules in Section 5.2.

---

# 7. Source-Location Preservation Tests

## 7.1 Locator Ground Truth

Locator evaluation uses the manually verified evaluator ground truth prepared under Section 5.4.

Three separate concepts must remain distinguishable:

1. **Evaluator ground-truth location**

   The manually verified location of the relevant passage in the original source document.

2. **Locator preserved by extraction**

   The page, slide, heading, section, block, or other source-location information actually retained by the tested extraction method.

3. **Locator permitted for display**

   A locator that the later system may show only because the extraction and source-association method preserved it reliably.

The evaluator knowing a location does not authorize the application to display it.

A manually observed page, slide, heading, or section must not be inserted into a generated answer when the tested extraction path did not reliably preserve and associate that locator with the supporting evidence.

## 7.2 Per-Format Locator Evaluation

Locator testing is format-specific.

### PDF

Test:

* page number;
* page range only where evidence genuinely spans multiple pages;
* association between an extracted passage and the source page from which it came.

Known risks include:

* incorrect page association after text reordering;
* merged content from adjacent pages;
* page labels differing from physical PDF page indexes.

The spike must record which convention the extraction candidate exposes.

It must not silently reinterpret one numbering convention as another.

### PPTX

Test:

* slide number;
* association between extracted content and the correct slide;
* multi-slide evidence only where genuinely required.

The spike must not invent a slide number when extracted text loses slide association.

### DOCX

Test, where reliably supported:

* heading;
* heading path;
* section;
* subsection.

DOCX page numbers are not assumed because page placement depends on rendering environment, fonts, layout, margins, and other presentation conditions.

The spike must not force fixed page numbers onto DOCX content unless a tested approach independently demonstrates reliable preservation under the chosen method.

### TXT

Test only where the source structure supports a reliable locator, such as:

* explicit headings;
* stable line ranges;
* stable block identifiers.

A line or block locator may be used only when:

* the source version is fixed;
* the extraction path preserves ordering;
* the locator can be reproduced reliably.

Otherwise TXT is treated as having no reliable fine-grained locator.

That is an accepted omission, not an extraction failure.

## 7.3 Locator Correctness and Omission

For each locator evaluation case, record one of the following outcomes:

* **Correct locator** — the preserved locator matches evaluator ground truth.

* **Correct locator range** — the evidence genuinely spans a verified range and the preserved range matches the source.

* **Incomplete locator** — some reliable location information is preserved but it is less specific than evaluator ground truth.

* **Incorrect locator** — a displayed or retained locator conflicts with evaluator ground truth.

* **Unavailable locator** — extraction did not preserve a reliable locator.

* **Correct omission** — no locator is displayed because reliable location information was unavailable.

* **Fabricated locator** — a page, slide, heading, section, line, block, or other locator is displayed without reliable preserved source-location support.

A fabricated locator is a mandatory failure condition because the accepted project rules prohibit invented source locations.

The spike must distinguish:

* omission caused by a genuine format or extraction limitation;
* incorrect failure to show an available reliable locator;
* fabricated display of an unavailable locator.

## 7.4 Locator Evidence

For every evaluated evidence passage, record:

* evaluation-item ID;
* fixture ID;
* source-version ID;
* evaluator ground-truth location;
* extraction-candidate identifier;
* locator actually preserved;
* locator type;
* outcome classification from Section 7.3;
* short reviewer note where clarification is necessary.

The locator evidence set supports later evaluation of:

* semantic search;
* repository-grounded inquiry;
* source attribution;
* locator correctness;
* locator omission.

This evidence exists only for spike review.

It is not:

* application citation history;
* inquiry history;
* retrieval history;
* conversation history;
* a new database table;
* a new database column.

---

# 8. Processing Readiness, Failure, Independent Capability Behavior, and Staleness

Exact stored state names, enum values, tables, columns, persistence structures, and synchronization mechanisms remain unresolved.

This section evaluates conceptual conditions and required behavior only.

## 8.1 Conceptual Readiness and Failure Conditions

The spike tests behavior associated with conceptual conditions such as:

* not yet processed;
* processing or in progress;
* ready;
* failed;
* stale.

These terms are descriptive test labels only.

They are not:

* selected database enum values;
* approved stored-state names;
* approved table fields;
* approved application-state constants.

Current live resource eligibility remains separate from capability readiness.

A resource fixture may be represented as:

* currently Approved and otherwise eligible, while one capability is not ready;
* currently Approved and eligible, while one capability is ready and another has failed;
* associated with previously prepared output that has become stale;
* associated with prepared output while the resource has since become ineligible.

A processing-ready condition alone must never override:

* current account status;
* role or permission;
* requester access;
* current resource status;
* `file_availability`;
* source freshness;
* lifecycle eligibility;
* AI configuration;
* other applicable AI rules.

## 8.2 Independent Capability Readiness and Failure Isolation

The spike defines controlled cases demonstrating that:

* extraction may succeed while summary generation fails;

* extraction may succeed while tag or metadata suggestion generation fails;

* extraction may succeed while embedding generation fails;

* a summary may be available while semantic retrieval is not ready;

* semantic retrieval may be ready while summary generation has failed;

* one resource fixture may complete successfully while another resource fixture fails;

* failure of one AI capability does not remove ordinary non-AI Approved-resource visibility inside the bounded harness;

* failure of one capability does not invalidate another independently current and valid output without a source, metadata-dependency, eligibility, or lifecycle reason;

* failure does not produce fabricated successful output;

* failure does not produce a false ready condition;

* failure never changes a fixture resource’s status.

The test must record whether another valid capability remained usable after the simulated failure.

This testing occurs only in the spike harness.

It does not prove that the final application has passed:

* full feature integration;
* complete fallback testing;
* full regression testing;
* complete access-control testing.

Those remain later implementation and `TESTING_CHECKLIST.md` responsibilities.

## 8.3 Source-Change, Metadata-Change, and Stale-Source Tests

The spike uses controlled fixture versions to test freshness behavior.

### Source-file change

A baseline fixture is processed.

A distinct modified source version is then created under:

* the same fixture identity;
* a new source-version identifier.

The spike evaluates whether prior source-dependent artifacts are excluded from current use after the file changes.

Potential affected artifacts include:

* extracted text;
* page, slide, heading, section, or block locators;
* embeddings;
* retrieval representations;
* summaries;
* tag suggestions;
* metadata suggestions.

The old artifacts must not be treated as current where the applicable lifecycle requires reprocessing, refresh, invalidation, or exclusion.

### Relevant metadata-only change

The spike changes metadata that a tested capability actually uses.

Examples may include:

* a controlled metadata filter used in retrieval;
* a resource type used in ranking or suggestion context;
* a topic value included in a tested representation.

The spike records whether the dependent output or retrieval representation is correctly treated as requiring refresh or reevaluation.

### Irrelevant metadata-only change

The spike changes metadata that the tested capability does not use.

The spike records whether unaffected current output remains usable rather than being invalidated automatically without a dependency reason.

The test must not assume:

* every metadata change invalidates every output;
* no metadata change affects any output.

Dependency behavior remains architecture-specific and must be measured or documented per tested candidate.

No final freshness mechanism is selected.

Candidate test methods may include a simple source fingerprint, source-version marker, or equivalent isolated comparison method.

Any such mechanism remains a spike method only and does not authorize:

* a new hash column;
* a file-version table;
* a processing-history table;
* a final dependency-tracking architecture.

## 8.4 Late-Result and Race-Like Test Cases

The spike defines bounded cases where extraction, embedding, summary generation, suggestion generation, or retrieval preparation completes after the relevant fixture state has changed.

Test cases include a result completing after:

* the source file changes;
* relevant metadata changes;
* the resource status changes;
* `file_availability` changes;
* requester access or applicable permission changes where relevant to the tested operation;
* AI eligibility changes;
* the tested capability is disabled;
* a newer valid result becomes current;
* the processing context no longer matches the request that produced the late result.

Before the result is treated as usable, the harness rechecks the applicable current fixture state, including:

* resource status;
* file availability;
* requester/access condition where applicable;
* AI eligibility;
* source-version association;
* relevant metadata dependency;
* processing context;
* whether a newer valid result already exists.

The spike records whether the late result is:

* accepted as still current;
* rejected as stale;
* discarded;
* excluded from active retrieval;
* excluded from current generated output.

A result must not become valid merely because processing began while the fixture was eligible.

An outdated result must not:

* overwrite a newer valid output;
* become the current summary;
* become a current suggestion;
* become a ready embedding representation;
* become active retrieval evidence;
* be returned as a current source locator.

This section does not design:

* production queues;
* worker infrastructure;
* distributed job control;
* retry orchestration;
* processing-history storage.

It tests only the minimum state-recheck behavior needed to evaluate feasibility safely.

## 8.5 Processing Evidence

For every processing, freshness, independent-failure, and late-result case, record:

* test-case ID;

* fixture ID;

* source-version ID;

* relevant metadata version or test condition;

* affected capability;

* conceptual starting condition;

* conceptual ending condition;

* start timestamp;

* completion timestamp;

* state change introduced during processing;

* current state at result completion;

* observed result:

  * successful and current;
  * failed;
  * partial;
  * correctly excluded as stale;
  * incorrectly accepted despite staleness;
  * correctly isolated from another capability;

* whether another independent capability remained usable;

* whether an outdated result was prevented from replacing a newer valid result;

* reviewer note where needed.

These are spike evidence records only.

This section does not introduce:

* a processing-history table;
* a provider-call-history table;
* a model-run-history table;
* a discarded-response table;
* a new application audit event.

---

# 9. Summary and Controlled Tag/Metadata Suggestion Feasibility

This section evaluates AI-generated summaries and controlled tag/metadata suggestions as independent, non-authoritative required capabilities.

The spike evaluates:

* output quality;
* source faithfulness;
* reviewability;
* latency;
* failure behavior;
* cost or quota implications where applicable.

It does not define the final user interface, save behavior, final supported metadata-field set, provider, model, or storage mechanism.

## 9.1 Test Inputs and Expected Reference

Use representative successfully extracted content from the accepted primary readable corpus.

Every tested generation item must record:

* fixture ID;
* source-version ID;
* extraction-candidate identifier;
* extracted-input identifier;
* input size;
* expected-reference-note identifier.

Before generation, prepare a human-reviewed reference note describing:

* major content that a useful summary should normally include;
* content that should not be invented;
* source ambiguity or uncertainty that should not be hidden;
* clearly relevant controlled tags available in the test fixture;
* metadata suggestions that appear supportable;
* metadata values that would be unsupported or overconfident.

The reference note is an evaluation aid.

It is not:

* the only acceptable summary wording;
* an institutional correctness certification;
* proof that the source resource itself is academically correct.

Multiple generated summaries may be acceptable when they remain faithful, useful, and appropriately concise.

## 9.2 Summary Evaluation

Evaluate each generated summary for:

* source faithfulness;
* important-content coverage;
* unsupported claims;
* unsupported interpretation;
* material omissions;
* contradiction of the source;
* readability;
* conciseness;
* organization;
* usefulness for understanding the resource’s scope and content;
* appropriate uncertainty where the source is incomplete, ambiguous, or unclear;
* absence of false confidence;
* absence of unrelated general-model content.

The evaluation must distinguish:

* a harmless wording difference;
* a minor omission;
* a material omission;
* a misleading simplification;
* an unsupported addition;
* a contradiction.

An AI-generated summary remains:

* derived;
* reviewable;
* editable where later permissions allow;
* discardable;
* non-authoritative;
* tied to its source resource and source state.

It is not proof of:

* academic correctness;
* institutional approval;
* successful moderation;
* factual validation by BPC.

## 9.3 Controlled Tag Suggestion Evaluation

Tag tests use a bounded controlled-taxonomy fixture.

The fixture records:

* tag identifier;
* tag name;
* Active or Inactive fixture state;
* intended representative use.

For new suggestion evaluation:

* Active fixture tags are available candidates;
* Inactive fixture tags are treated as unavailable for new assignment;
* absent values are out of vocabulary.

Evaluate:

* relevance to source content;
* whether each suggestion exists in the fixture;
* whether each suggested controlled value is Active;
* unsupported suggestions;
* duplicate suggestions;
* redundant suggestions;
* overly broad suggestions;
* overly narrow suggestions;
* omitted clearly relevant Active tags;
* inappropriate selection of an Inactive value;
* out-of-vocabulary output.

An out-of-vocabulary suggestion may be recorded as a quality finding.

It must not:

* create a new taxonomy value;
* become a controlled tag automatically;
* activate an Inactive value;
* modify the taxonomy fixture.

AI must not:

* create;
* rename;
* deactivate;
* reactivate;

a taxonomy value.

## 9.4 Metadata Suggestion Evaluation

Metadata suggestion testing is limited to fields already present in the accepted resource metadata model.

Before execution, select a small representative subset from existing fields for feasibility testing.

The subset may include examples from:

* course/program;
* subject;
* year level;
* resource type;
* topic.

The selected subset must be recorded before scored evaluation.

The subset is a spike fixture only.

It does not define:

* the final `suggested_metadata` field mapping;
* every metadata type the completed system must suggest;
* a new metadata field;
* the final implementation behavior in `AI_FEATURES.md`.

For controlled metadata values, the fixture records:

* available Active values;
* Inactive historical values;
* absent or out-of-vocabulary values.

Evaluate:

* relevance;
* consistency with source content;
* compatibility with the controlled fixture;
* unsupported suggestions;
* overly confident suggestions;
* contradictory suggestions;
* duplicate or conflicting suggestions;
* inappropriate use of an Inactive value for a new assignment;
* omission of an obvious available value where the source provides enough evidence.

For descriptive metadata such as topic, evaluate:

* source support;
* specificity;
* usefulness;
* unsupported detail;
* unnecessary invention.

The spike must not assume that every metadata field can be inferred reliably from file content alone.

A finding that a field is not reliably inferable is a valid feasibility result.

## 9.5 Human Review and Non-Authoritative Behavior

The spike evaluates whether generated output is practical for later authorized human review.

The review exercise may assess whether a human can:

* inspect the source and generated output;
* identify unsupported content;
* edit the output;
* discard the output;
* retain an output judged usable after review.

The spike does not implement:

* the final review interface;
* the final edit form;
* the final save workflow;
* a separate acceptance checkbox;
* a field-by-field AI-acceptance system;
* AI-acceptance history.

The spike must preserve the accepted D028 direction:

* visible authorized human review may later support retention of undiscarded output during approval;
* no separate acceptance field is required solely because the output came from AI;
* exact write and acceptance mechanics remain for `AI_FEATURES.md`.

AI must not:

* approve metadata;
* write unreviewed output as authoritative final metadata;
* publish a resource;
* approve a resource;
* reject a resource;
* validate a resource;
* create or modify taxonomy values;
* make or execute a moderation decision;
* change resource status.

## 9.6 Summary and Suggestion Measurements

For every generation run, record:

* test-run ID;
* fixture ID;
* source-version ID;
* extracted-input identifier;
* candidate provider, model, or local runtime used;
* prompt or instruction-template version;
* relevant non-secret generation settings;
* input size;
* output size;
* request start time;
* response completion time;
* wall-clock generation time;
* success, partial, failure, or timeout outcome;
* summary-quality rubric result;
* tag-suggestion-quality result;
* metadata-suggestion-quality result;
* unsupported-content findings;
* failure behavior;
* retry occurrence where the test procedure explicitly allows one;
* estimated quota effect;
* estimated or observed cost where applicable;
* reviewer notes.

Named providers, models, runtimes, prompts, and settings are recorded only to make the experiment reproducible.

They remain:

* test candidates;
* non-binding;
* subject to later architecture review.

API keys, credentials, secrets, and sensitive provider configuration must not be recorded in the evidence register.

The test may retain an authorized prompt template and generated output inside dedicated spike evidence.

Those artifacts must not be copied into ordinary application audit logs or accountability ledgers.

---

# 10. Local Embedding Feasibility

Local embedding feasibility must be measured rather than assumed.

No final:

* embedding model;
* local runtime;
* model format;
* acceleration method;
* segmentation strategy;
* persistence method;

is selected in this section.

## 10.1 Candidate-Selection Rules

Select only a very small number of lightweight candidates.

The comparison must remain bounded and must not become a broad model survey.

Candidate-screening criteria include:

* compatibility with Windows development;

* compatibility with the known hardware context:

  * Intel Core i7-7500U;
  * approximately 12 GB RAM;
  * NVIDIA GeForce 940MX;
  * 2 GB VRAM;

* ability to operate without requiring enterprise infrastructure;

* model/runtime download size;

* expected RAM requirement;

* expected VRAM requirement where applicable;

* CPU usability;

* ability to fall back to CPU where relevant;

* supported input length;

* vector dimension;

* setup complexity;

* native-PHP integration implications;

* need for a local helper process or runtime;

* licensing or usage constraints known at test time;

* maintenance burden for a small student team.

A candidate should not be tested merely because it is popular.

The reason for including each candidate must be recorded.

Every named model or runtime remains an experimental spike candidate only.

## 10.2 Embedding Input Units

Test only a small number of input-unit strategies needed to evaluate feasibility.

Candidate units may include:

* one full short resource;
* one section;
* one heading-based segment;
* one bounded fixed-size text segment.

Where relevant, a bounded overlap setting may be tested as an experimental parameter.

No final chunking or segmentation architecture is selected.

For every tested strategy, record:

* input-unit identifier;
* segmentation rule;
* approximate size limit;
* overlap setting where used;
* number of segments produced;
* source fixture association;
* source-version association;
* locator association;
* reason for inclusion.

A segment must remain traceable to:

* its source fixture;
* its source version;
* its available reliable locator information.

Testing a segmentation rule does not authorize:

* a chunk table;
* a chunk column;
* permanent chunk storage;
* a final indexing design.

## 10.3 Repeatable Benchmark Method

Embedding benchmarks begin only after:

1. the full feasibility-spike specification is accepted;
2. the accepted current schema is separately verified;
3. Windows is restarted;
4. unnecessary heavy applications are closed;
5. `llmfit system` is run;
6. `nvidia-smi` is run;
7. the clean hardware and runtime baseline is recorded.

Embedding measurements should then be executed under conditions consistent with that recorded baseline as far as practical.

For every candidate:

* record the environment state;
* record whether material background conditions changed;
* record model/runtime loading time;
* record first-run or cold-run behavior;
* record warm-up behavior where relevant;
* record warm-run behavior;
* repeat comparable runs;
* use the same accepted corpus version;
* use the same evaluation-query set where retrieval comparison is involved;
* use recorded segmentation parameters;
* avoid changing multiple variables without recording the change.

Record:

* candidate identifier;
* runtime identifier;
* candidate version where available;
* fixture and source-version IDs;
* input-unit strategy;
* model/runtime load time;
* first-run processing time;
* warm-run processing time;
* per-item time;
* per-segment time where relevant;
* full-corpus time;
* CPU use;
* RAM use;
* GPU use where applicable;
* VRAM use where applicable;
* failures;
* warnings;
* vector dimension;
* number of vectors generated;
* temporary artifact size;
* approximate bounded-corpus footprint.

The bounded-corpus footprint is only an estimate.

It does not select:

* MariaDB storage;
* file storage;
* a vector database;
* a second database;
* a hosted retrieval service.

The benchmark does not attempt:

* enterprise throughput testing;
* campus-scale ingestion modeling;
* production concurrent-load testing.

## 10.4 Embedding Quality Contribution

Execution speed alone is insufficient.

A candidate that generates vectors quickly but performs poorly in retrieval is not a successful feasibility result.

The same expected-evidence set from Section 5.4 must be used to evaluate retrieval contribution where comparable.

For each candidate and input-unit strategy, later retrieval evaluation records:

* whether expected resources are retrieved;
* whether expected supporting passages are retrieved;
* expected evidence rank;
* irrelevant high-ranked evidence;
* missed evidence;
* paraphrase handling;
* ambiguity handling.

Candidate comparison must distinguish the effects of:

* embedding candidate;
* segmentation strategy;
* overlap parameter;
* retrieval computation.

The spike should avoid claiming that one candidate is better when multiple uncontrolled inputs changed simultaneously.

## 10.5 Failure and Fallback Behavior

Test and record, where safely reproducible:

* runtime unavailable;
* model unavailable;
* incomplete model/runtime setup;
* unsupported hardware behavior;
* GPU path unavailable;
* CPU fallback behavior where supported;
* memory failure;
* excessive processing time relative to the proposed criteria in Section 23;
* timeout;
* malformed vector output;
* incorrect vector dimension;
* non-finite values;
* partial corpus completion;
* interruption during processing.

A failed or partial embedding run must not be recorded as fully ready.

The spike must not:

* invent missing vectors;
* substitute vectors from another fixture;
* silently reuse stale vectors;
* treat malformed output as valid.

Failure evidence should identify:

* affected candidate;
* affected fixture or segment;
* failure category;
* safe short explanation;
* whether previously valid unrelated capability output remained usable.

---

# 11. Bounded Application-Side Similarity Retrieval

This section tests the D042 candidate direction for bounded application-side retrieval.

It does not approve final retrieval architecture.

## 11.1 Retrieval Objective and Boundaries

The retrieval test determines:

* whether application-side similarity computation is responsive enough for representative interactive use;
* whether it retrieves the correct resources;
* whether it retrieves the correct supporting evidence passages;
* whether it handles paraphrased and ambiguous requests usefully;
* whether it is understandable and maintainable for a small student team;
* whether obvious degradation appears within the bounded corpus;
* whether measured evidence justifies retaining or reconsidering the candidate direction.

Testing is limited to:

* the accepted representative corpus;
* realistic bounded segment counts;
* a small controlled scaling observation.

This section does not:

* perform campus-scale testing;
* claim production capacity;
* estimate institutional maximum load;
* establish production service levels.

## 11.2 Candidate Retrieval Computation and Numerical Sanity Check

The spike implements only the minimum similarity computation necessary to test the candidate direction.

A test method may:

1. prepare the query representation;
2. compare it against candidate representations;
3. calculate a similarity score;
4. rank candidates;
5. return a bounded top-result set.

Any specific metric or computation used remains a spike method only.

Before scored retrieval evaluation, validate the candidate similarity implementation using a tiny controlled numerical sanity set.

The sanity set should include known cases such as:

* identical vectors;
* clearly similar vectors;
* clearly dissimilar vectors;
* a zero or invalid vector case where applicable.

Expected behavior should be independently checked through:

* manual calculation;
* a trusted mathematical reference calculation;
* another simple independent verification method.

This step exists to reduce the risk that a retrieval-quality result is actually caused by an implementation error.

It does not select:

* final retrieval code;
* a vector database;
* a database-native vector feature;
* a hosted retrieval service;
* a permanent retrieval index.

## 11.3 Corpus, Candidate Comparison, and Scaling Observations

Comparable retrieval runs should use:

* the same primary-corpus version;
* the same fixture IDs;
* the same source versions;
* the same expected-evidence set;
* the same evaluation queries;
* the same top-result count;
* the same relevant filtering assumptions;
* recorded segmentation settings;
* recorded embedding candidate;
* recorded similarity method.

Where one variable changes, record the change explicitly.

Examples include comparing:

* two embedding candidates with the same segmentation;
* two segmentation strategies using the same embedding candidate;
* two bounded corpus sizes using the same candidate configuration.

The scaling observation may compare:

* a smaller controlled subset;
* the complete bounded corpus;
* one modest repeated or expanded segment set where justified.

The purpose is only to identify obvious degradation patterns.

The spike must not extrapolate unsupported conclusions such as:

* campus-wide capacity;
* thousands-of-resource performance;
* production concurrent-user capacity.

## 11.4 Retrieval Performance Measurements

Record separate timing components where the tested method includes them:

* query preprocessing;
* query embedding;
* temporary representation loading or preparation;
* candidate eligibility/filter preparation inside the harness;
* similarity scoring;
* ranking;
* top-result selection;
* result formatting needed for evaluation;
* total isolated retrieval time.

Also record:

* test-run ID;
* query ID;
* candidate configuration ID;
* corpus resource count;
* segment count;
* top-result count;
* CPU use;
* RAM use;
* GPU use only when genuinely involved;
* VRAM use only when genuinely involved;
* errors;
* warnings;
* maintainability observations.

Maintainability observations may include:

* implementation complexity;
* debugging clarity;
* dependency burden;
* number of moving parts;
* difficulty of reproducing the result.

The total isolated retrieval time must be distinguishable from later full inquiry time.

This section does not include language-model answer generation latency.

## 11.5 Retrieval Relevance and Evidence-Passage Evaluation

Use the expected-evidence set accepted in Section 5.4.

For every evaluation query, record:

* query ID;
* expected supporting resource or resources;
* expected supporting passage or passages;
* expected locator where available;
* returned resource IDs;
* returned segment or evidence IDs;
* rank of each expected resource;
* rank of each expected supporting passage;
* whether the expected resource appeared;
* whether the expected evidence passage appeared;
* whether the returned passage actually supports the expected claim;
* irrelevant high-ranked resources;
* irrelevant high-ranked passages from otherwise relevant resources;
* missed expected resources;
* missed expected passages;
* duplicate or near-redundant returned passages;
* ambiguous-query behavior;
* semantic/paraphrased-query behavior;
* partially supported-query behavior where applicable.

A result is not considered fully useful merely because it returns the correct resource.

The retrieved evidence must also contain the relevant supporting content needed by downstream inquiry.

The evaluation should distinguish:

* right resource and right passage;
* right resource but wrong passage;
* relevant passage ranked too low;
* unrelated resource ranked highly;
* no useful evidence returned.

No final numeric retrieval threshold is set here.

Proposed requirements such as:

* expected evidence in the top K;
* maximum acceptable irrelevant-result rate;
* minimum relevance rubric;

belong in Section 23 and must be accepted before scored execution.

## 11.6 Architecture-Neutral Storage Boundary

The spike does not assume that embeddings or retrieval representations are stored permanently in:

* MariaDB;
* a new table;
* an existing table;
* `ai_outputs`;
* flat files;
* another local file format;
* a second database;
* a vector database;
* a hosted retrieval service.

Temporary measurement artifacts may use the simplest isolated storage needed to execute the experiment.

Temporary spike storage:

* must remain outside the accepted application schema;
* must not modify the 18-table baseline;
* must not overload `ai_outputs`;
* must be documented;
* must not be interpreted as the final architecture;
* must not imply that permanent persistence is required.

The later architecture decision may conclude that retrieval-derived data should use:

* some persistent structure;
* request-scoped preparation;
* session-scoped preparation;
* a local isolated store;
* another justified approach.

No choice is made here.

## 11.7 Evidence and Handoff

Part 2 evidence includes:

* extraction correctness;
* extraction coverage;
* extraction performance;
* locator preservation;
* locator omission behavior;
* processing readiness behavior;
* independent capability behavior;
* failure isolation;
* source-file freshness;
* metadata dependency observations;
* late-result handling;
* summary quality;
* controlled tag-suggestion quality;
* metadata-suggestion quality;
* generation latency and dependency observations;
* embedding feasibility;
* embedding resource use;
* embedding candidate comparison;
* retrieval numerical sanity;
* retrieval performance;
* resource-level relevance;
* evidence-passage relevance;
* maintainability observations.

This evidence feeds:

* Section 12 — Semantic-Search Evaluation;
* Section 13 — Repository-Grounded Inquiry Evaluation;
* Section 14 — Source Attribution and Locator Reliability;
* Section 15 — Insufficient-Evidence Behavior;
* Section 16 — Session-Scoped Follow-Up Evaluation;
* Section 17 — Related-Resource Suggestion Evaluation;
* Section 18 — External-Generation Feasibility;
* Section 19 — Optional Experimental Local Generation;
* Section 20 — Non-AI Fallback Behavior;
* Section 21 — Security, Privacy, Eligibility, and Lifecycle Checks;
* Section 22 — Measurements Summary;
* Section 23 — Decision Criteria;
* Section 24 — Evidence Recording;
* Section 25 — Final Recommendation Format;
* Section 26 — Handoff to Later Architecture and Schema Decisions.

Part 2 does not:

* select the final architecture;
* approve a provider;
* approve a model;
* approve an extraction library;
* approve an embedding model;
* approve a retrieval method;
* approve storage;
* authorize a schema change.

# 12. Semantic-Search Evaluation

## 12.1 Purpose and Evaluation Boundary

This section evaluates semantic search as a required capability under D042 that **supplements rather than replaces** metadata search and filtering.

The spike evaluates:

* retrieval usefulness;
* resource-level relevance;
* supporting-passage relevance;
* latency;
* added value beyond ordinary metadata and keyword search;
* failure behavior;
* feasibility at the bounded representative MVP corpus scale.

Semantic search is not:

* the only search path;
* a replacement for metadata search;
* permission to weaken or remove ordinary filtering;
* a campus-scale retrieval claim;
* a production-capacity benchmark.

Metadata search and filtering must remain independently usable when semantic search is:

* disabled;
* unavailable;
* unconfigured;
* failing;
* not ready;
* stale.

## 12.2 Metadata-Search Comparison Baseline

Use the same evaluation-query set accepted in Section 5.4 for metadata/keyword and semantic comparison where the query category supports a meaningful comparison.

The comparison uses a minimal harness-level representation of the accepted non-AI metadata and keyword behavior.

The spike does not implement:

* the final application search interface;
* final search-result layout;
* final ranking architecture;
* final metadata weighting.

Compare:

* metadata-friendly queries;
* semantic or paraphrased queries;
* ambiguous queries;
* partially supported queries.

For each query, record whether:

* metadata search performs adequately;
* semantic search adds useful evidence;
* semantic search adds little or no meaningful value;
* semantic search returns misleading evidence;
* the two approaches return complementary useful results.

Semantic-search performance must not be used as justification to remove the independent non-AI metadata-search path.

## 12.3 Semantic-Search Candidate Evaluation

Reuse the accepted Part 2 evidence while evaluating semantic search as a user-facing discovery capability.

For every query and candidate configuration, record:

* expected resource retrieval;
* expected evidence-passage retrieval;
* expected resource rank;
* expected passage rank;
* irrelevant high-ranked resources;
* irrelevant high-ranked passages;
* missed expected resources;
* missed expected evidence;
* ambiguous-query behavior;
* paraphrase behavior;
* latency;
* failure behavior;
* reviewer usefulness assessment.

Any embedding model, runtime, segmentation method, similarity method, provider, or other candidate configuration remains:

* experimental;
* test-specific;
* non-binding.

## 12.4 Combined Metadata and Semantic Conditions

Where justified by the evaluation set, test semantic retrieval together with explicit ordinary metadata filters.

Examples may include:

* a concept query limited to one Subject;
* a concept query limited to one course/program;
* a concept query limited to one year level;
* a concept query limited to one resource type.

No final:

* weighting;
* fusion;
* reranking;
* filter-order;
* scoring architecture;

is selected by this test.

Measure whether the explicit filter:

* correctly enforces the requested controlled value;
* improves relevance;
* reduces ambiguity;
* preserves expected evidence whose metadata matches the selected filter;
* incorrectly excludes evidence that should satisfy the selected filter;
* exposes fixture metadata-quality problems;
* produces a clear no-result condition where no eligible matching evidence exists.

Semantic ranking must not silently bypass an explicit filter merely because evidence outside the selected metadata value appears semantically similar.

If expected evidence is stored under a different metadata value from the user-selected filter, distinguish:

* correct exclusion caused by the explicit filter;
* incorrect fixture metadata;
* incorrect filter implementation.

Do not automatically treat every zero-result filtered query as a semantic-search failure.

## 12.5 Search Result Safety, Eligibility, and Revalidation

Within the bounded harness, every semantic-search candidate must be revalidated against current fixture state before any result is displayed.

The check includes:

* account still exists;
* account status is Active;
* current role/permission;
* current requester access;
* current resource status is Approved;
* `file_availability` is available;
* applicable extraction/retrieval processing is ready;
* source data is current rather than stale;
* lifecycle eligibility;
* AI eligibility;
* current feature availability.

Exclude fixtures represented as:

* Pending;
* Needs Correction;
* Rejected;
* Withdrawn;
* Hidden;
* Restricted;
* Removed;
* Replaced;
* file unavailable;
* stale;
* unprocessed;
* not ready;
* inaccessible to the requester;
* unauthorized;
* otherwise ineligible.

Every displayed:

* resource identity;
* title;
* snippet;
* locator;
* result summary;
* link;

must be based only on the current eligible fixture state.

A previously prepared candidate, cached result, or stale retrieval representation must not bypass current revalidation.

Full live-application access-control testing remains later work for implementation and `TESTING_CHECKLIST.md`.

## 12.6 Semantic-Search Measurements

For each query, record:

* test-run ID;
* query ID;
* query category;
* requester fixture;
* metadata-baseline result;
* semantic result;
* combined-filter result where tested;
* expected resource;
* expected passage;
* expected-resource rank;
* expected-passage rank;
* relevance assessment;
* misleading or irrelevant result;
* incorrect inclusion;
* incorrect exclusion;
* eligibility/revalidation outcome;
* retrieval latency;
* total result-preparation latency;
* failure behavior;
* fallback behavior;
* reviewer usefulness assessment.

No final acceptance threshold is defined in this section.

Proposed:

* relevance minimums;
* expected-evidence rank requirements;
* latency limits;
* mandatory failure conditions;

belong in Section 23 and must be accepted before scored execution.

---

# 13. Repository-Grounded Inquiry Evaluation

## 13.1 Inquiry Objective and Boundaries

This section evaluates the required repository-grounded academic resource inquiry capability without turning BPC LearnShare into:

* an unrestricted AI tutor;
* an open-web assistant;
* a chatbot-first platform;
* an exam-answering system;
* a replacement for Teacher/Instructor judgment.

Within the bounded harness, the inquiry path must:

1. receive a request from a simulated authenticated Active user;
2. validate current account and capability availability;
3. retrieve a small relevant evidence set from currently eligible Approved-resource fixtures;
4. revalidate every candidate before its content is sent for generation;
5. send only the minimum relevant eligible evidence and necessary metadata;
6. generate a response grounded in that evidence;
7. identify or link supporting resource(s);
8. display locators only where reliably preserved;
9. state insufficiency when eligible evidence is inadequate;
10. revalidate supporting candidates again before returning resource identity or links.

The generator may:

* organize;
* simplify;
* summarize;
* compare;
* explain;

retrieved evidence.

It must not silently substitute unsupported general model knowledge when repository evidence is missing.

## 13.2 Inquiry Evaluation Set

Use the accepted Section 5.4 evaluation set, including:

* legitimate study and explanation questions;
* single-resource questions;
* multi-resource questions;
* ambiguous questions;
* partially supported questions;
* unsupported questions;
* prohibited academic requests;
* locator-available cases;
* locator-unavailable cases.

Use the manually verified:

* expected evidence;
* expected supporting resource;
* expected passage;
* expected locator where applicable;
* expected outcome.

The evaluation set must remain fixed during scored comparison unless a correction is documented before rerunning affected tests.

## 13.3 Evidence Selection Before Generation

For every test question, record:

* test-run ID;
* query ID;
* requester fixture;
* retrieved resource candidates;
* retrieved passage candidates;
* passage ranks;
* available locator information;
* preliminary relevance;
* first-point eligibility/revalidation result;
* exclusion reason for rejected candidates;
* final filtered evidence selected for generation;
* evidence count;
* evidence size;
* source identifiers included;
* locator information included;
* payload-manifest identifier.

Use a small bounded evidence set.

Do not send:

* the whole repository;
* unrelated complete documents;
* unrelated extracted content;
* unrelated metadata;
* unrelated account data.

Where a smaller supporting passage is sufficient, do not send an unrelated full file merely because it is available locally.

## 13.4 Grounded-Answer Evaluation

For every generated answer, evaluate:

* source faithfulness;
* substantive claim support;
* unsupported additions;
* unsupported interpretation;
* contradiction of evidence;
* omission of necessary qualifications;
* useful explanation;
* appropriate uncertainty;
* answer scope;
* whether every substantive academic claim is traceable to eligible retrieved evidence.

Classify answer content as:

### Evidence-supported substantive claim

An academic statement directly supported by one or more retrieved eligible evidence passages.

### Harmless connective or organizational wording

Language used to:

* transition;
* introduce;
* organize;
* summarize structure;

without adding a new unsupported academic proposition.

### Partially supported claim

A statement whose evidence supports only part of what is asserted.

### Unsupported substantive claim

An academic statement that cannot be traced to retrieved eligible evidence.

### Contradicted claim

A statement that conflicts with the retrieved evidence.

Unsupported or contradicted substantive claims are grounding failures.

The evaluator must not excuse unsupported content merely because the model’s general knowledge appears plausible.

## 13.5 Partial Support

When only part of the request is supported:

* answer only the supported portion;
* state what part is unsupported;
* avoid filling the gap from silent general model knowledge;
* avoid implying that incomplete evidence is complete;
* preserve uncertainty where appropriate.

If the supported portion becomes too weak to answer usefully after eligibility filtering, return the accepted insufficient-evidence behavior.

## 13.6 Prohibited Academic Requests

Test direct requests to answer or complete:

* examinations;
* quizzes;
* graded assignments;
* answer keys;
* grading tasks;
* equivalent prohibited academic work.

Also test legitimate requests concerning the same topic, such as:

* explain the concept;
* summarize the relevant section;
* compare two theories;
* clarify a worked example already contained in eligible repository evidence;
* suggest what topic to review.

Expected prohibited-request behavior:

* refuse or redirect without providing the prohibited answer;
* avoid revealing an answer key;
* avoid completing the graded item;
* offer a bounded study-oriented alternative where appropriate;
* remain repository-grounded when providing an allowed explanation.

Refusal must not become overbroad.

A legitimate study question must not be rejected merely because the topic could also appear in an examination.

## 13.7 Inquiry Measurements

For every inquiry test, record:

* test-run ID;
* query ID;
* requester fixture;
* retrieval time;
* evidence-filter/revalidation time;
* generation time;
* total response time;
* evidence count;
* input size;
* output size;
* substantive-claim count where reviewed;
* supported-claim result;
* partially supported claim;
* unsupported-claim finding;
* contradiction finding;
* attribution result;
* locator result;
* insufficiency result;
* prohibited-request result;
* provider/model/runtime candidate;
* prompt/instruction-template version;
* relevant non-secret settings;
* quota observation;
* cost observation;
* failure behavior;
* reviewer note.

---

# 14. Source Attribution and Locator Reliability

## 14.1 Attribution Requirements

Every substantive inquiry response must identify or link its supporting resource or resources.

The spike must never fabricate:

* resource identity;
* resource title;
* resource link;
* page;
* page range;
* slide;
* slide range;
* heading;
* section;
* subsection;
* line;
* block;
* paragraph;
* another source locator.

Attribution must refer only to:

* the actual eligible resource;
* the actual supporting evidence;
* the current source identity.

A replacement resource must not inherit the original resource’s citation identity.

## 14.2 Claim-to-Source Review

For every substantive claim, classify whether it is:

* supported by the attributed source;
* supported but attributed to the wrong source;
* supported by more than one attributed source;
* partially supported;
* unsupported;
* contradicted;
* harmless connective wording that does not require separate academic evidence.

Keep the review bounded and practical.

The spike does not create:

* a formal academic citation engine;
* a citation-style formatter;
* a permanent claim graph;
* a citation-history database.

## 14.3 Multi-Source Attribution

For multi-resource answers, evaluate whether the response:

* preserves each source’s identity;
* associates claims with the correct supporting source;
* avoids implying that every listed resource supports every statement;
* avoids blending conflicting sources into false agreement;
* identifies disagreement or uncertainty where the evidence itself conflicts.

A compact source list is insufficient when it makes claim support ambiguous.

The response format may use:

* inline source labels;
* grouped evidence statements;
* another bounded test format.

No final citation UI is selected by this spike.

## 14.4 Locator Reliability

Reuse the accepted Section 7 locator evidence.

Classify every displayed locator as:

* correct;
* correct range;
* incomplete but not false;
* incorrect;
* unavailable and correctly omitted;
* fabricated.

A fabricated locator is a mandatory failure regardless of overall answer quality.

The spike must distinguish:

* a correct omission caused by unavailable reliable locator data;
* failure to display a reliable preserved locator;
* display of an incorrect or invented locator.

## 14.5 Second-Point Revalidation and Response Handling

Immediately before returning supporting resource identity, source attribution, or a resource link, revalidate the supporting fixture’s current:

* account status;
* role/permission;
* requester access;
* Approved status;
* `file_availability`;
* readiness;
* freshness;
* lifecycle eligibility;
* AI eligibility;
* source identity;
* source-version association.

This is the second minimum mandatory inquiry-candidate revalidation point.

If a supporting candidate fails this revalidation:

* do not return its resource identity as an active supporting source;
* do not return its link;
* do not return its snippet or locator;
* do not present the generated answer as grounded by that now-ineligible source.

Reassess the remaining eligible evidence.

If remaining evidence is sufficient:

* regenerate or rebuild the answer using only the remaining eligible evidence.

If remaining evidence is insufficient:

* return a safe insufficiency or temporarily unavailable result.

The spike must record whether an already generated answer was incorrectly returned after its supporting source failed second-point revalidation.

This section does not select the final regeneration, retry, or response-reconstruction implementation.

---

# 15. Insufficient-Evidence Behavior

## 15.1 No-Evidence Cases

Test questions with no eligible supporting evidence.

Expected behavior:

* state clearly that eligible repository evidence is insufficient;
* do not answer silently from unsupported general knowledge;
* do not fabricate a source;
* do not fabricate a locator;
* do not imply that the answer was verified by BPC LearnShare.

The exact final interface wording remains for later implementation documents.

## 15.2 Weak, Ambiguous, or Conflicting Evidence

Test:

* low-relevance evidence;
* incomplete evidence;
* ambiguous evidence;
* conflicting evidence across multiple resources;
* evidence that supports more than one reasonable interpretation.

Evaluate whether the response:

* limits claims to what the evidence supports;
* identifies uncertainty;
* identifies source disagreement where relevant;
* requests a narrower question where useful;
* avoids inventing a resolution;
* avoids presenting one source as authoritative merely because it ranked first;
* avoids unsupported certainty.

BPC LearnShare moderation does not guarantee academic correctness.

The inquiry response must not imply that Approved status proves that one conflicting resource is factually correct.

## 15.3 Partial-Evidence Cases

When a question is partly supported:

* answer the supported portion;
* identify the unsupported portion;
* state relevant limitations;
* avoid silently completing the missing portion;
* avoid citing a resource for content it does not support.

If the supported portion is not meaningful enough after current eligibility checks, use insufficiency behavior instead.

## 15.4 Insufficiency Measurements

Record:

* test-run ID;

* query ID;

* evidence condition:

  * none;
  * weak;
  * ambiguous;
  * conflicting;
  * partial;

* expected outcome;

* actual outcome;

* unsupported-answer occurrence;

* fabricated-source occurrence;

* fabricated-locator occurrence;

* inappropriate-certainty occurrence;

* correct limitation behavior;

* useful clarification request where applicable;

* reviewer assessment.

---

# 16. Session-Scoped Follow-Up Evaluation

## 16.1 Active-Session Context Only

Follow-up context may exist only during the active inquiry session.

The spike does not introduce:

* permanent application chat history;
* cross-session AI memory;
* inquiry-history table;
* message table;
* evidence-history table;
* citation-history table;
* learner profile;
* behavioral profile.

Active-session context may contain only what is reasonably needed for the current inquiry flow.

The absence of permanent application chat history does **not** establish that an external provider retains nothing.

Provider-side handling may depend on:

* current provider terms;
* current privacy documentation;
* API/account configuration;
* provider retention practices;
* provider model-training practices;
* provider deletion practices.

These matters must not be represented as guaranteed by application session behavior.

## 16.2 Follow-Up Grounding

Test:

* clarification;
* pronoun/reference follow-up;
* comparison follow-up;
* narrowing follow-up;
* expansion of a previously supported point;
* explanation of previously cited evidence;
* request to compare previously cited resources.

Every turn must remain grounded in currently eligible evidence.

Conversation context may help interpret the user’s follow-up, but it must not become an unchecked evidence source.

The system must not treat a previous answer as proof independent of the repository evidence that supported it.

## 16.3 Per-Turn Eligibility Revalidation

At every turn, recheck:

* account exists;
* account status is Active;
* current role/permission;
* requester access;
* current resource status;
* `file_availability`;
* processing readiness;
* source freshness;
* lifecycle eligibility;
* AI eligibility;
* capability availability;
* supporting source-link validity.

Evidence used in an earlier turn must be dropped if it becomes ineligible.

Earlier eligibility never guarantees later eligibility in the same session.

## 16.4 Mid-Session Change Cases

Test a supporting fixture changing during an active inquiry session:

* Approved to Hidden;
* Approved to Restricted;
* Approved to Removed;
* Approved to Replaced;
* file availability changes away from available;
* source becomes stale;
* relevant metadata change makes an existing representation stale;
* account becomes Disabled;
* role/permission changes;
* requester access changes;
* inquiry capability becomes disabled or unavailable.

The next turn must not reuse ineligible evidence.

Where remaining eligible evidence is sufficient, the response may continue using only that evidence.

Where it is insufficient, use insufficiency or unavailable behavior.

## 16.5 Session End, Expiration, Logout, and Reset

Test that context becomes unavailable after:

* explicit inquiry-session reset;
* application logout;
* authenticated session expiration;
* closing/ending the simulated inquiry session;
* starting a new application inquiry session.

A new session must not inherit:

* prior questions;
* prior answers;
* prior evidence;
* prior citations;
* prior follow-up context.

No permanent storage is required to support this behavior.

## 16.6 Follow-Up Measurements

Record:

* session/test ID;
* turn ID;
* context interpretation;
* context continuity;
* grounding result;
* evidence reused while still eligible;
* evidence removed after ineligibility;
* unsupported carryover;
* ineligible evidence carryover;
* session-end clearing;
* new-session isolation;
* per-turn retrieval latency;
* per-turn generation latency;
* total per-turn response time;
* reviewer assessment.

---

# 17. Related-Resource Suggestion Evaluation

## 17.1 Purpose and Boundaries

This section evaluates the required basic related-resource capability.

Use only:

* eligible extracted content;
* accepted resource metadata;
* bounded content similarity;
* bounded metadata similarity.

Do not use:

* learner profiles;
* behavioral profiles;
* per-user view history;
* per-user download history;
* bookmark behavior;
* Helpful-mark behavior;
* reports;
* user ranking;
* social relationships;
* engagement transfer between resources.

Related-resource suggestions must not become:

* personalized learner profiling;
* duplicate adjudication;
* automatic resource merging;
* a moderation decision.

## 17.2 Evaluation Fixtures

Use selected starting-resource fixtures with:

* clearly related resources;
* partially related resources;
* unrelated resources;
* overlapping terminology but different meaning;
* same subject but different topic;
* same topic but different resource type where useful;
* metadata-related but weakly content-related cases;
* content-related but differently worded cases.

Prepare expected related-resource assessments before scored evaluation.

## 17.3 Relevance Evaluation

For every starting resource, record:

* starting fixture;
* suggested fixture;
* rank;
* evaluator-recorded expected relationship basis;
* observed content-similarity basis;
* observed metadata-similarity basis;
* human relevance judgment;
* redundant suggestion;
* irrelevant suggestion;
* missed expected relation.

The recorded relationship basis is evaluation evidence.

It is not a requirement for the final application to generate a user-facing explanation of why two resources are related.

The spike must not:

* definitively label a resource as duplicate;
* score duplicate probability as a Planned-tier feature;
* execute a duplicate-moderation workflow.

## 17.4 Eligibility and Revalidation

Before generating or displaying suggestions, revalidate the starting resource and every suggested resource.

Check:

* account exists;
* account status is Active;
* current role/permission;
* requester access;
* resource status is Approved;
* `file_availability` is available;
* related-resource processing/readiness;
* source freshness;
* lifecycle eligibility;
* AI eligibility;
* current source identity.

Before display, revalidate every:

* suggested resource identity;
* title;
* snippet where used;
* locator where used;
* link.

Do not show a suggestion when:

* the starting resource is no longer eligible for the capability;
* the candidate resource is no longer eligible;
* the candidate became stale;
* the candidate file is unavailable;
* access changed.

## 17.5 Related-Resource Fallback Behavior

If content-based similarity is unavailable:

* use a simpler metadata-based relationship only where that fallback is implemented and enabled;
* apply the same live eligibility and link checks;
* do not introduce behavioral profiling;
* do not claim semantic relatedness when only metadata matching was used.

If no eligible useful suggestion can be produced:

* show no suggestions; or
* show a safe unavailable/no-related-resource result.

The failure must not affect access to the current resource.

## 17.6 Related-Resource Measurements

Record:

* test-run ID;

* starting fixture;

* candidate configuration;

* suggestion latency;

* result count;

* relevance;

* rank;

* diversity where useful;

* redundant result;

* incorrect inclusion;

* incorrect exclusion;

* missed expected relation;

* eligibility/revalidation result;

* fallback type:

  * content-based;
  * metadata-based;
  * unavailable/no result;

* reviewer assessment.

---

# 18. External-Generation Feasibility

External generation is tested only where needed to evaluate acceptable grounded-answer or summary quality and response time.

This section does not select or approve a final provider.

## 18.1 Candidate-Selection and Provider-Review Rules

Select only a very small number of external-generation candidates.

Candidate-screening factors include:

* accessible test availability;
* native-PHP integration practicality;
* documented API availability;
* observed latency;
* answer quality;
* quota;
* observed or estimated cost;
* configuration burden;
* maintainability;
* current provider terms and privacy documentation;
* available API/account data controls;
* provider-practice uncertainty.

Before transmitting test content, record a point-in-time review of available information concerning:

* data retention;
* model-training use;
* deletion practices;
* processing location where disclosed;
* storage location where disclosed;
* confidentiality claims;
* ownership terms;
* security claims;
* available account/API configuration;
* unavailable or unclear information.

This review:

* does not certify the provider;
* does not establish institutional approval;
* does not make a legal compliance determination;
* does not create a provider registry or approval module.

Only content authorized under Section 5.2 may be used.

Every provider and model remains:

* test-only;
* non-binding;
* subject to later architecture review.

## 18.2 Payload Boundary

For every external-generation call, send only:

* minimum relevant evidence;
* necessary source identifier;
* necessary locator information;
* necessary content/context instructions;
* necessary output-format instructions.

Do not send merely because the information is available:

* username;
* display name;
* uploader identity;
* original filename;
* unrelated account information;
* password or password hash;
* session identifier;
* CSRF token;
* database credential;
* API key;
* unrelated resource;
* unrelated full document;
* report;
* report comment;
* moderation history;
* resource-action history;
* audit log;
* notification;
* full repository content.

A source identifier used for attribution should be the minimum practical identifier needed for the test.

Do not include personal or account-linked information unless it is genuinely necessary for the accepted function and authorized for the test.

## 18.3 Latency, Availability, Quota, Cost, and Dependency

For every candidate and test run, record:

* request start time;
* time to first usable output where available;
* response completion time;
* total round-trip time;
* timeout;
* provider-unavailable result;
* rate-limit response;
* quota consumption;
* observed cost;
* estimated cost where necessary and clearly labeled;
* setup/configuration burden;
* internet dependency;
* retry occurrence where the accepted test procedure allows one.

Provider limits, prices, free tiers, quotas, and terms are point-in-time observations.

They must not be presented as permanent.

## 18.4 Quality Contribution and Comparable Evaluation

Where candidates are compared, use the same where applicable:

* evaluation item;
* source version;
* evidence set;
* prompt/instruction-template version;
* relevant non-secret generation settings;
* quality rubric.

Evaluate:

* grounding;
* claim support;
* explanation quality;
* source faithfulness;
* insufficiency behavior;
* prohibited-request behavior;
* response time;
* output consistency;
* failure behavior.

A provider is not successful merely because it is fast or free.

## 18.5 External Failure Behavior

Test:

* missing configuration;
* missing API key;
* no internet;
* provider unavailable;
* timeout;
* rate limit;
* malformed response;
* incomplete response;
* unsafe or unusable partial response.

In every failure case:

* do not expose API keys;
* do not expose raw provider errors;
* do not display unsafe partial output;
* do not create fake success;
* do not mark output ready incorrectly;
* do not change resource status;
* preserve the non-AI path;
* return a safe non-technical outcome.

---

# 19. Optional Experimental Local Generation

Local generation remains optional, experimental, and unverified.

The spike does not require a positive local-generation result.

## 19.1 Entry Conditions

Test local generation only after:

* the clean hardware baseline is recorded;
* readable extraction produces usable content for the selected test;
* applicable evidence-selection foundations are available.

For summary testing:

* usable extracted content is sufficient as the content foundation.

For inquiry testing:

* a valid currently eligible retrieved evidence set is required;
* embeddings are required only where the tested retrieval path depends on them.

Local generation is not mandatory for overall spike success.

A finding that local generation is impractical is valid.

## 19.2 Candidate Rules

Test at most a very small number of candidates appropriate to measured hardware.

Candidate factors include:

* model/runtime size;
* RAM requirement;
* VRAM requirement;
* CPU support;
* GPU compatibility;
* setup complexity;
* Windows practicality;
* local maintenance burden;
* response quality;
* licensing or usage constraints known at test time.

Any model or runtime remains:

* experimental;
* test-specific;
* non-binding.

## 19.3 Comparable Measurements

Where local and external generation are compared, use the same where applicable:

* evaluation item;
* source version;
* eligible evidence set;
* prompt/instruction-template version;
* relevant non-secret settings;
* output-quality rubric.

Record:

* test-run ID;
* candidate/runtime version;
* model/runtime load time;
* cold time to first usable output;
* warm time to first usable output;
* total generation time;
* output size;
* output throughput where meaningful;
* CPU use;
* RAM use;
* GPU use;
* VRAM use;
* grounding;
* claim support;
* unsupported claims;
* insufficiency behavior;
* prohibited-request behavior where evaluated;
* failure;
* setup burden;
* reviewer assessment.

## 19.4 Feasibility Interpretation

Valid findings include:

* practical for required inquiry and summary use;
* practical for one limited capability only;
* practical only as an experimental fallback;
* too slow for interactive use;
* acceptable only for offline/preprocessing use;
* insufficient answer quality;
* excessive hardware use;
* excessive setup or maintenance burden;
* incompatible with current hardware;
* not feasible.

Do not force a positive result.

Do not lower grounding or safety expectations solely to make local generation appear feasible.

---

# 20. Non-AI Fallback Behavior

This section evaluates fallback only within the bounded spike-harness boundary.

It is not complete application fallback, integration, or regression testing.

## 20.1 Failure Isolation

Force controlled failures in:

* extraction;
* summary generation;
* tag suggestion;
* metadata suggestion;
* embedding generation;
* semantic retrieval;
* related-resource generation;
* external generation;
* local generation where tested.

Initially test one failure at a time so the affected dependency is identifiable.

Where justified, add a small combined-dependency case only after single-failure behavior is understood.

Record whether:

* only the expected capability fails;
* an unrelated capability fails incorrectly;
* stale output is reused;
* a false ready condition appears.

## 20.2 Non-AI Path Preservation

Within the harness, represent continued availability of:

* metadata search;
* metadata filtering;
* ordinary Approved-resource identity;
* ordinary resource-detail availability state;
* ordinary file-access eligibility state;
* non-AI workflow independence.

The harness must show that an AI failure does not itself:

* change resource status;
* remove ordinary Approved-resource visibility;
* alter accepted metadata;
* undo a valid non-AI result.

This does not prove that the final application has passed:

* login fallback;
* upload fallback;
* moderation fallback;
* complete access fallback;
* complete regression testing.

Those remain implementation and `TESTING_CHECKLIST.md` responsibilities.

## 20.3 Safe User-Facing Outcome

Evaluate whether each failure can produce a short non-technical result such as:

* `Semantic search is temporarily unavailable. You can still use metadata search.`
* `AI inquiry is temporarily unavailable.`
* `A summary could not be generated for this resource.`
* `Related-resource suggestions are unavailable.`

These are test examples, not final approved interface copy.

Do not expose:

* raw stack traces;
* raw provider errors;
* local runtime internals;
* secrets;
* unsafe partial output.

## 20.4 No False Success or Invalid Side Effect

Failure must not:

* create fake AI output;
* create false evidence;
* create a fake ready condition;
* use stale output as current;
* return an ineligible link;
* change resource status;
* modify controlled taxonomy;
* overwrite valid metadata;
* undo a valid non-AI result;
* invalidate an unrelated current capability without a real dependency reason.

## 20.5 Fallback Measurements

Record:

* test-case ID;
* failed capability;
* failure type;
* expected affected behavior;
* actual affected behavior;
* expected unaffected behavior;
* actual unaffected behavior;
* incorrect cascade;
* stale reuse;
* false success;
* invalid state effect;
* safe user-facing outcome;
* recovery behavior where tested;
* reviewer assessment.

---

# 21. Security, Privacy, Eligibility, and Lifecycle Checks

This section is a bounded spike-harness evaluation.

It is not complete:

* security testing;
* penetration testing;
* authorization testing;
* privacy compliance assessment;
* institutional provider approval;
* production hardening.

## 21.1 Required Revalidation Points by Capability

### Inquiry

Every inquiry candidate must be revalidated **at minimum**:

1. before candidate evidence content is sent for generation;
2. before supporting resource identity, attribution, snippet, locator, or link is returned.

Additional revalidation may occur where useful.

### Semantic search

Every semantic-search candidate must be revalidated before displaying:

* resource identity;
* title;
* snippet;
* locator;
* result;
* link.

### Related-resource suggestions

The starting resource and every suggested resource must be revalidated before generating or displaying the suggestion.

At each applicable revalidation point, check:

* account exists;
* account status is Active;
* current role/permission;
* requester access;
* current resource status;
* `file_availability`;
* processing readiness;
* source freshness;
* lifecycle eligibility;
* AI eligibility;
* current source identity;
* relevant capability availability.

## 21.2 Status, Account, Permission, and Access Fixtures

Test exclusion or denial for:

* Disabled account;
* role/permission mismatch;
* requester-access mismatch;
* Pending;
* Needs Correction;
* Rejected;
* Withdrawn;
* Hidden;
* Restricted;
* Removed;
* Replaced;
* file unavailable;
* stale;
* unprocessed;
* not ready;
* inaccessible;
* unauthorized;
* capability disabled;
* otherwise ineligible.

Moderator/Admin ability to access non-public content for moderation or administration must not create an elevated general semantic-search, inquiry, or related-resource path.

## 21.3 Source Change, Lifecycle Invalidation, and Public-Facing Exclusion

Test that stale, invalidated, unavailable, or otherwise ineligible derived material is not used in:

* semantic results;
* related-resource suggestions;
* inquiry evidence;
* snippets;
* source attribution;
* citations;
* locators;
* links;
* generated answers;
* current summaries;
* current suggestions;
* caches or temporary active result sets.

Affected derived material may include:

* extracted text;
* locator data;
* embeddings;
* retrieval representations;
* prepared candidates;
* summaries;
* tag suggestions;
* metadata suggestions.

Returning to Approved does not automatically make prior derived data current.

Current:

* source association;
* readiness;
* freshness;
* lifecycle;
* access;
* eligibility;

must pass again.

## 21.4 Replacement Non-Inheritance

Test that a replacement fixture:

* does not inherit original extracted text;
* does not inherit original locators;
* does not inherit original embeddings;
* does not inherit original retrieval representations;
* does not inherit original provider objects;
* does not inherit original AI output;
* does not inherit original citation identity;
* does not silently reuse the original source link as its own;
* undergoes independent extraction;
* undergoes independent processing;
* undergoes independent retrieval preparation when eligible.

The replacement may become usable only after it independently satisfies applicable:

* status;
* access;
* file availability;
* readiness;
* freshness;
* lifecycle;
* AI eligibility.

## 21.5 External Payload Minimization Evidence

For every external test call, create a controlled payload manifest recording:

* test-run ID;
* provider/model candidate;
* evaluation item;
* fixture IDs included;
* evidence passage IDs included;
* resource count;
* evidence count;
* included data categories;
* source identifiers included;
* locator information included;
* approximate character/token or byte size;
* excluded data categories;
* whether personal/account-linked information was included;
* justification where any such information was necessary;
* authorization basis for external transmission.

The payload manifest should not unnecessarily duplicate the complete payload.

A redacted sample may be retained where useful.

A full prompt/payload may be retained only inside authorized controlled spike evidence when genuinely necessary for reproducibility and permitted by the corpus authorization rules.

Do not create:

* application transmission-history table;
* provider-call-history table;
* prompt-history table;
* full request/response application log.

## 21.6 Secret, Prompt, Content, and Log Safety

The following must not appear in:

* `audit_log`;
* `resource_action_history`;
* notifications;
* ordinary application logs;
* client-visible error output;

unless an accepted future design explicitly permits a safe limited field:

* API keys;
* database credentials;
* passwords;
* password hashes;
* session identifiers;
* CSRF tokens;
* full prompts;
* full payloads;
* full responses;
* full extracted documents;
* uploaded file content;
* unnecessary provider error details.

Dedicated authorized spike evidence remains separate from application-style accountability logging.

Use short safe outcome summaries.

## 21.7 No Permanent Application Inquiry History Assumption

Do not assume permanent application storage of:

* questions;
* answers;
* evidence sets;
* retrieved candidates;
* source attribution;
* citations;
* conversation context;
* follow-up context.

Request-scoped or active-session-scoped handling is sufficient for the spike.

The absence of local permanent history does not establish external-provider deletion or non-retention.

Provider-side behavior remains subject to current provider-specific review.

## 21.8 Prohibited Action, Taxonomy, and Authority Boundary

AI must not:

* approve;
* reject;
* publish;
* validate;
* Hide;
* Restrict;
* Remove;
* delete;
* change resource status;
* create taxonomy values;
* modify taxonomy values;
* deactivate taxonomy values;
* reactivate taxonomy values;
* write unreviewed output as authoritative final metadata;
* make or execute a moderation decision;
* replace human judgment;
* grant broader access;
* override current role or permission;
* guarantee academic correctness.

## 21.9 Lifecycle, Security, and Privacy Measurements

Record:

* test-case ID;
* capability;
* revalidation point;
* incorrect inclusion;
* incorrect exclusion;
* stale-data use;
* invalidated-output use;
* unavailable-file use;
* failed account-status check;
* failed role/permission check;
* failed requester-access check;
* failed status check;
* failed readiness check;
* failed freshness check;
* failed lifecycle check;
* failed AI-eligibility check;
* unauthorized evidence transmission;
* excessive payload;
* invalid source return;
* invalid link return;
* incorrect snippet return;
* incorrect locator return;
* answer returned after supporting evidence failed second-point revalidation;
* replacement inheritance;
* payload overexposure;
* secret exposure;
* unsafe log content;
* permanent-history assumption;
* incorrect taxonomy effect;
* incorrect status effect;
* safe failure;
* reviewer assessment.


# 22. Measurements Summary

## 22.1 Measurement Principles

All measurements recorded across the feasibility spike must be:

* **reproducible** — another reviewer should be able to repeat the test using the recorded fixtures, candidate configuration, procedure, and environment;

* **traceable** — every measurement must link to a test-run ID, fixture ID, source version, query or evaluation-item ID where applicable, and candidate-configuration ID;

* **recorded before interpretation** — raw observations must be preserved before conclusions or recommendations are written;

* **separated by evidence type** — quantitative measurements, qualitative reviewer judgments, mandatory-guardrail results, and implementation/maintainability observations must remain distinguishable;

* **comparable where comparison is intended** — candidate comparisons should use the same corpus version, evaluation items, evidence set, prompt/instruction version, relevant non-secret settings, and rubric unless a changed variable is explicitly part of the test;

* **honest about uncertainty** — failed tests, incomplete runs, unavailable provider information, measurement limitations, and reviewer disagreement must not be hidden;

* **bounded to tested conditions** — results apply only to the recorded corpus, fixtures, hardware, software versions, candidates, configuration, network conditions, and test period.

Point-in-time observations concerning:

* provider latency;
* free-tier availability;
* quota;
* pricing;
* provider terms;
* retention;
* model-training use;
* deletion;
* processing or storage location;
* confidentiality;
* security claims;

must not be presented as permanent guarantees.

Percentages must always be reported together with their underlying counts.

Where a scored category contains fewer than five applicable cases, the report should emphasize exact counts and case-level findings rather than presenting a percentage as if it were statistically strong.

## 22.2 Extraction and Locator Measurements

Consolidate the following from Sections 6–7:

### Extraction outcome

* successful extraction;
* partial extraction;
* unsuccessful extraction;
* outcome by file type;
* outcome by extraction candidate;
* valid-readable corpus outcome;
* boundary/limitation outcome;
* invalid-fixture safe-failure outcome.

### Extraction quality

* missing text;
* duplicated text;
* incorrect ordering;
* merged or fragmented passages;
* corrupted characters;
* heading preservation;
* list preservation;
* table degradation;
* structural preservation;
* overall completeness;
* downstream usability.

### Locator behavior

* locator available;
* correct locator;
* correct locator range;
* incomplete locator;
* incorrect locator;
* unavailable locator;
* correct omission;
* fabricated locator.

### Performance and resource use

* extraction time;
* source-file size;
* extracted-output size;
* CPU use;
* RAM use;
* GPU use where genuinely applicable;
* VRAM use where genuinely applicable;
* warning and error behavior.

## 22.3 Summary and Suggestion Measurements

Consolidate the following from Section 9:

### Summary quality

* source faithfulness;
* important-content coverage;
* unsupported additions;
* unsupported interpretation;
* material omissions;
* contradictions;
* readability;
* conciseness;
* usefulness;
* appropriate uncertainty;
* human reviewability.

### Controlled-tag behavior

* relevant Active suggestion;
* irrelevant suggestion;
* duplicate or redundant suggestion;
* overly broad suggestion;
* overly narrow suggestion;
* omitted relevant Active value;
* Inactive-value suggestion;
* out-of-vocabulary suggestion;
* attempted automatic taxonomy effect.

### Metadata-suggestion behavior

* relevance;
* source consistency;
* controlled-value compatibility;
* unsupported confidence;
* contradiction;
* omitted obvious value;
* inappropriate Inactive value;
* out-of-scope field suggestion;
* human reviewability.

### Generation measurements

* candidate provider/model/runtime;
* prompt/instruction-template version;
* relevant non-secret settings;
* input size;
* output size;
* generation time;
* quota observation;
* cost observation;
* failure behavior.

## 22.4 Embedding and Retrieval Measurements

Consolidate the following from Sections 10–11:

### Embedding preparation

* model/runtime load time;
* cold-run time;
* warm-up behavior;
* warm-run time;
* per-item time;
* per-segment time;
* full-corpus time;
* successful completion;
* partial completion;
* malformed output;
* vector dimension;
* non-finite or invalid values.

### Hardware and temporary footprint

* CPU use;
* RAM use;
* GPU use where applicable;
* VRAM use where applicable;
* system instability;
* out-of-memory behavior;
* number of vectors;
* temporary artifact size;
* bounded-corpus footprint estimate.

### Retrieval performance

* query preprocessing;
* query embedding;
* temporary representation preparation/loading;
* candidate-filter preparation;
* similarity scoring;
* ranking;
* top-result selection;
* result preparation;
* total isolated retrieval time.

### Retrieval quality

* expected-resource retrieval;
* expected-resource rank;
* expected-passage retrieval;
* expected-passage rank;
* irrelevant high-ranked resource;
* irrelevant high-ranked passage;
* missed expected resource;
* missed expected passage;
* ambiguous-query behavior;
* paraphrased-query behavior;
* partially supported-query behavior;
* duplicate or redundant evidence.

### Maintainability

* setup complexity;
* dependency burden;
* number of moving parts;
* debugging clarity;
* reproducibility;
* native-PHP integration implications;
* maintenance burden for the student team.

## 22.5 Semantic Search and Related-Resource Measurements

Consolidate the following from Sections 12 and 17:

### Metadata and semantic comparison

* metadata-search baseline;
* semantic-search result;
* combined-filter result where tested;
* useful semantic value added;
* little or no added value;
* misleading semantic result;
* correct explicit-filter enforcement;
* correct filter exclusion;
* incorrect metadata classification;
* incorrect filter implementation;
* relevance;
* latency.

### Eligibility and fallback

* incorrect inclusion;
* incorrect exclusion;
* stale result;
* inaccessible result;
* fallback to metadata search;
* safe semantic-unavailable behavior.

### Related-resource evaluation

* expected related resource;
* suggested resource;
* rank;
* evaluator-recorded relationship basis;
* relevance;
* redundancy;
* diversity where useful;
* missed expected relationship;
* unrelated suggestion;
* metadata-based fallback;
* safe no-result behavior.

## 22.6 Inquiry, Attribution, Insufficiency, and Follow-Up Measurements

Consolidate the following from Sections 13–16:

### Grounded-answer behavior

* substantive-claim count;
* supported substantive claim;
* partially supported claim;
* unsupported substantive claim;
* contradicted claim;
* harmless connective wording;
* source faithfulness;
* appropriate uncertainty;
* usefulness.

### Attribution and locator behavior

* correct source attribution;
* wrong-source attribution;
* missing source attribution;
* multi-source attribution clarity;
* correct locator;
* correct locator omission;
* incomplete locator;
* incorrect locator;
* fabricated locator;
* second-point revalidation result.

### Evidence sufficiency

* no-evidence behavior;
* weak-evidence behavior;
* ambiguous-evidence behavior;
* conflicting-evidence behavior;
* partial-evidence behavior;
* unsupported answer;
* inappropriate certainty;
* useful clarification request.

### Prohibited and legitimate academic requests

* correct refusal;
* prohibited-answer leakage;
* useful study-oriented redirection;
* false refusal of a legitimate study/explanation request.

### Session-scoped follow-up

* context continuity;
* per-turn grounding;
* per-turn revalidation;
* eligible evidence reuse;
* ineligible evidence removal;
* unsupported carryover;
* logout clearing;
* session-expiration clearing;
* explicit reset;
* new-session isolation;
* per-turn latency.

## 22.7 External and Local Generation Measurements

Consolidate the following from Sections 18–19:

### Candidate identification and reproducibility

* provider/model/runtime;
* candidate version;
* prompt/instruction-template version;
* relevant non-secret settings;
* evaluation item;
* source version;
* evidence-set version.

### Performance

* model/runtime load time;
* time to first usable output;
* total generation time;
* output size;
* output throughput where useful;
* CPU use;
* RAM use;
* GPU use;
* VRAM use.

### Quality

* grounding;
* claim support;
* source faithfulness;
* insufficiency behavior;
* prohibited-request behavior;
* response usefulness;
* output consistency.

### Dependency and operational feasibility

* internet dependency;
* setup burden;
* configuration burden;
* maintenance burden;
* quota use;
* observed cost;
* estimated cost where clearly labeled;
* rate-limit behavior;
* timeout behavior;
* provider/runtime outage behavior;
* safe failure.

## 22.8 Security, Privacy, Eligibility, Lifecycle, and Fallback Measurements

Consolidate the following from Sections 20–21:

### Live eligibility and revalidation

* account-status result;
* role/permission result;
* requester-access result;
* resource-status result;
* `file_availability` result;
* readiness result;
* freshness result;
* lifecycle result;
* AI-eligibility result;
* capability-availability result;
* first required inquiry revalidation;
* second required inquiry revalidation;
* semantic-result display revalidation;
* related-resource revalidation;
* additional revalidation where used.

### Incorrect exposure or lifecycle behavior

* incorrect inclusion;
* incorrect exclusion;
* stale-data use;
* invalidated-output use;
* unavailable-file use;
* invalid link;
* invalid snippet;
* invalid locator;
* answer returned after supporting evidence became ineligible;
* replacement inheritance;
* incorrect source identity;
* false ready state;
* false success state.

### Payload and provider review

* payload-manifest result;
* included evidence IDs;
* included data categories;
* excluded data categories;
* approximate payload size;
* external-transmission authorization;
* unnecessary data inclusion;
* provider-practice review completion;
* unclear provider practice;
* provider-review limitation.

### Secret, log, and history safety

* secret exposure;
* credential exposure;
* unsafe error exposure;
* unnecessary full-content logging;
* inappropriate full-payload logging;
* permanent application inquiry-history assumption;
* provider-side retention incorrectly inferred from application design.

### Authority and fallback

* incorrect resource-status effect;
* incorrect taxonomy effect;
* unreviewed authoritative metadata write;
* broader-access grant;
* unrelated capability failure;
* invalid failure cascade;
* stale fallback;
* non-AI path preservation;
* safe non-technical failure outcome.

These measurements provide spike-level evidence supporting the accepted D004 fallback and independence requirement.

They do not prove that the complete application has passed final implementation-level fallback, authorization, security, or regression testing.

## 22.9 Measurement Matrix

| Measurement Area           | Key Measures                                               | Primary Evidence Source | Decision Supported                                                          |
| -------------------------- | ---------------------------------------------------------- | ----------------------- | --------------------------------------------------------------------------- |
| Extraction                 | Success, correctness, completeness, structure, performance | Section 6 evidence      | Whether local readable-text extraction is viable per required file type     |
| Locators                   | Preservation, correctness, omission, fabrication           | Section 7 evidence      | Which locator types may be displayed reliably                               |
| Processing lifecycle       | Readiness, independent failure, freshness, late results    | Section 8 evidence      | Required lifecycle and invalidation behavior                                |
| Summaries                  | Faithfulness, coverage, unsupported content, usefulness    | Section 9 evidence      | Whether summary generation is usable and reviewable                         |
| Tags and metadata          | Relevance, controlled-value behavior, reviewability        | Section 9 evidence      | Whether suggestions are feasible without automatic authority                |
| Embeddings                 | Completion, latency, hardware use, footprint               | Section 10 evidence     | Local embedding feasibility on current hardware                             |
| Bounded retrieval          | Resource/passage relevance, timing, maintainability        | Section 11 evidence     | Whether bounded PHP-side retrieval remains practical                        |
| Semantic search            | Added value over metadata search and filter behavior       | Section 12 evidence     | Whether semantic search justifies its complexity                            |
| Inquiry                    | Grounding, claim support, usefulness                       | Section 13 evidence     | Required repository-grounded inquiry viability                              |
| Attribution and locators   | Source correctness, multi-source clarity, locator behavior | Section 14 evidence     | Trustworthy source presentation                                             |
| Insufficiency              | No/weak/conflicting/partial evidence behavior              | Section 15 evidence     | Whether unsupported answers are avoided                                     |
| Follow-up                  | Context continuity, revalidation, reset                    | Section 16 evidence     | Session-scoped follow-up viability                                          |
| Related resources          | Relevance, eligibility, redundancy, fallback               | Section 17 evidence     | Basic related-resource capability viability                                 |
| External generation        | Quality, latency, quota, cost, provider review             | Section 18 evidence     | Whether an external dependency is feasible and defensible                   |
| Local generation           | Quality, latency, hardware use, maintenance                | Section 19 evidence     | Whether local generation is viable, limited, or unsuitable                  |
| Fallback                   | Failure isolation and non-AI path preservation             | Section 20 evidence     | Spike-level support for D004 failure-isolation design                       |
| Security/privacy/lifecycle | Revalidation, payload minimization, authority boundaries   | Section 21 evidence     | Whether the tested pipeline is safe enough to inform architecture selection |

No measured results appear in this section.

The matrix defines what evidence must exist before the final recommendation is written.

---

# 23. Pre-Run Decision Criteria

## 23.1 Criteria Principles

The criteria in this section are proposed by the feasibility-spike specification.

They become the accepted interpretation rules only after review and acceptance **before spike execution**.

The criteria are designed for:

* approximately 25–50 representative resources or an equivalent realistic bounded segment count;
* a local/LAN academic MVP;
* capstone-development and defense use;
* the recorded current hardware;
* native PHP;
* beginner-maintainable implementation.

They are not:

* production service-level agreements;
* campus-wide capacity guarantees;
* institutional deployment commitments;
* guarantees for every future file, question, provider, or model.

The following evidence classes must remain distinct:

1. **Mandatory guardrails**

   Safety, eligibility, authority, source-grounding, and lifecycle behavior that must not be violated in the scored test set.

2. **Required-capability quality criteria**

   Minimum quality needed for the capability to be considered usable.

3. **Performance and maintainability criteria**

   Evidence used to compare feasible directions and identify targeted changes.

4. **Optional local-generation interpretation**

   An experimental result that does not determine whether the required package may proceed using another viable generation path.

Every percentage must be reported with exact counts.

Where fewer than five cases exist in a category, use exact case results and reviewer judgment rather than relying on percentage language alone.

## 23.2 Mandatory Guardrail Criteria

The following require **zero confirmed occurrences in the final accepted scored run**:

* fabricated resource identity;
* fabricated source attribution;
* fabricated resource link;
* fabricated locator;
* use of evidence that is currently ineligible;
* use of evidence known to be stale;
* return of a source identity, snippet, locator, answer, or link after the supporting evidence fails required revalidation;
* unauthorized external transmission;
* transmission of data not authorized for the test;
* unnecessary sensitive or account-linked data included without justification;
* API key exposure;
* credential exposure;
* password or password-hash exposure;
* session-identifier exposure;
* AI changing a resource status;
* AI approving, rejecting, publishing, validating, Hiding, Restricting, Removing, or deleting a resource;
* AI creating, modifying, deactivating, or reactivating taxonomy values;
* AI writing unreviewed output as authoritative final metadata;
* AI granting broader access;
* replacement-resource inheritance of original AI output or retrieval-derived data;
* prohibited academic-answer leakage;
* a substantive answer presented as supported when no eligible evidence exists;
* false ready state after processing failure;
* false success after processing failure;
* fabricated output used to conceal a failed capability.

If one of these occurs:

1. mark the affected case as failed;
2. investigate the cause;
3. correct the candidate/configuration or reject that candidate;
4. rerun the affected mandatory-guardrail set;
5. require zero recurrence before recommending the affected configuration.

A passed bounded guardrail set does not prove universal production safety.

It demonstrates only that the tested candidate satisfied the accepted cases under the recorded conditions.

## 23.3 Extraction and Locator Criteria

### Readable extraction coverage

For the primary readable corpus:

* at least **80% overall** should produce usable extraction output;

* each required file type should achieve at least **70% usable extraction** among its readable representative fixtures;

* each required file type should include at least **three usable successful examples** before that format is considered demonstrated.

If the evaluation set contains only five fixtures for a format, at least four should yield usable output for a strong result.

A lower result does not automatically cancel the required capability.

It indicates:

* targeted extraction-method changes;
* narrower documented support limitations;
* another candidate;
* or an architecture/implementation risk requiring explicit treatment.

### Extraction usability

An extraction is usable only when:

* the main academic content is present;
* reading order does not materially misrepresent meaning;
* character corruption does not materially change meaning;
* output can reasonably support at least one downstream required capability.

Minor visual-formatting loss is acceptable.

Perfect reproduction of:

* fonts;
* colors;
* margins;
* page layout;
* decorative positioning;

is not required.

### Major corruption

A fixture fails extraction usability when:

* major content is missing;
* ordering materially changes meaning;
* unrelated sections are merged into misleading content;
* character corruption materially changes meaning;
* the extracted result cannot safely support downstream use.

### Boundary and failure behavior

Every scored:

* scanned/image-only limitation case;
* valid non-extractable case;
* invalid-file direct component test;

must produce the expected safe limitation or failure classification without:

* raw stack-trace exposure;
* false successful extraction;
* fake readable content;
* application-level bypass of upload validation.

### Locator behavior

For every locator displayed in the scored set:

* **100% must match the reliably preserved source-location evidence**.

When a reliable locator is unavailable:

* correct omission is a pass;
* omission must not be replaced by an invented locator.

Locator availability is reported per format.

No minimum locator-availability percentage is imposed across all formats because valid locator support differs by format and extraction method.

## 23.4 Summary and Suggestion Criteria

Use the following three-level quality classification where applicable:

* **Pass** — faithful, useful, reviewable, and requires no material correction;

* **Needs light review/edit** — generally useful but contains a minor omission, wording issue, redundancy, or limited correction that does not change the central meaning;

* **Fail** — materially unsupported, contradictory, misleading, unusable, or requires substantial rewriting.

### Summary criteria

Across scored summaries:

* at least **80% should receive Pass**;

* at least **95% should receive Pass or Needs light review/edit**;

* no summary classified as Pass may contain a material unsupported or contradicted academic claim;

* a materially unsupported or contradicted claim makes that summary a Fail;

* repeated failure on the same pattern indicates a candidate/prompt/configuration problem rather than isolated reviewer preference.

A candidate may still be classified as feasible with targeted changes when limited Fail cases are corrected through a bounded, documented change and rerun.

### Controlled-tag criteria

For scored suggested tags:

* at least **80% of suggested directly usable tags** should be both:

  * relevant;
  * Active in the controlled-taxonomy fixture;

* at least **75% of resources with one or more clearly relevant Active fixture tags** should receive at least one such relevant suggestion;

* no Inactive value may be treated as available for new assignment;

* no out-of-vocabulary value may become a controlled tag automatically;

* no suggestion may create or modify taxonomy automatically.

### Metadata-suggestion criteria

For scored metadata suggestions:

* at least **80% of proposed values** should be judged relevant and source-supported;

* unsupported confidence, contradiction, or inappropriate Inactive values must be recorded as failures for the affected suggestion;

* no new metadata field may be invented;

* no output may be written as authoritative final metadata without authorized human review.

### Human reviewability

At least **80% of scored summary/suggestion outputs** should be judged usable as-is or after light human editing.

An output requiring substantial reconstruction does not satisfy the reviewability criterion.

## 23.5 Embedding and Retrieval Criteria

### Embedding completion

For a candidate to be recommended **as tested**:

* it should complete the full intended primary-corpus embedding run;

* it must not produce malformed, non-finite, mismatched, or silently substituted vectors;

* it must not silently omit failed fixtures or segments;

* any partial completion must be reported and cannot be classified as full success.

A candidate that completes at least **95%** but not all intended eligible inputs may remain a targeted-fix candidate only when:

* every failure is identified;
* no false ready condition occurs;
* a bounded correction path is demonstrated.

### Hardware behavior

The candidate must:

* avoid out-of-memory failure;
* avoid operating-system instability;
* avoid sustained system unresponsiveness that makes ordinary development impractical;
* complete under the recorded clean-baseline conditions.

High CPU or memory use is not automatically a failure.

It becomes an architecture concern when it causes:

* repeated failure;
* severe system instability;
* impractical processing time;
* inability to operate alongside the expected local development environment.

### Isolated retrieval latency

At the full bounded spike corpus:

* median total isolated retrieval time should be **2 seconds or less**;

* at least **90% of scored retrieval runs should complete within 5 seconds**.

This includes the recorded query-preparation and retrieval components defined in Section 11 but excludes language-model answer generation.

Results above these values are not production failures.

They indicate:

* targeted optimization;
* candidate/configuration comparison;
* or possible retrieval-architecture reconsideration.

### Expected-resource retrieval

Across answerable single-resource and multi-resource retrieval cases:

* the expected supporting resource should appear within the top five results in at least **85%** of applicable cases.

### Expected-passage retrieval

Across cases with manually verified supporting passages:

* an expected supporting passage should appear within the top five evidence passages in at least **75%** of applicable cases.

Correct-resource retrieval with the wrong passage remains a partial result rather than a full retrieval success.

### Irrelevant high-ranked evidence

An irrelevant passage should not occupy the top three results in more than **20%** of applicable scored queries.

Repeated irrelevant top-ranked evidence indicates:

* embedding weakness;
* segmentation weakness;
* ranking weakness;
* filtering weakness;
* or an unsuitable candidate configuration.

### Maintainability

A candidate receives a maintainability Pass only when:

* setup can be documented clearly;
* dependencies are limited and justified;
* another student developer can reproduce the test from the written instructions;
* debugging does not require enterprise-only infrastructure knowledge;
* operational steps remain realistic for the project team.

## 23.6 Semantic Search and Related-Resource Criteria

### Semantic value beyond metadata search

Semantic search should add useful expected evidence in at least **60%** of the designated semantic/paraphrased cases where the metadata baseline:

* misses the expected evidence;
* ranks it poorly;
* or cannot express the conceptual match adequately.

Semantic search does not need to outperform metadata search on exact title, tag, or controlled-filter queries.

### Metadata fallback

Metadata search and filtering must remain independently usable in **100% of scored semantic-unavailable fallback cases**.

Semantic-search success must not remove or weaken that path.

### Combined-filter correctness

For explicit metadata-filter cases:

* the filter must be enforced correctly;

* semantic similarity must not bypass the selected filter;

* expected evidence with matching metadata must not be incorrectly excluded.

### Eligibility

No ineligible resource may appear in the final displayed semantic-search or related-resource result set.

This remains a mandatory guardrail.

### Related-resource relevance

Across fixtures with one or more expected related resources:

* at least one expected related resource should appear within the top five suggestions in at least **80%** of applicable cases;

* at least **70% of top-three suggestions** should be judged clearly or meaningfully related;

* unrelated suggestions must not be forced when no useful eligible relation exists.

### Safe no-result behavior

For fixtures intentionally designed with no useful related resource:

* every scored case must return no suggestion or a safe no-result/unavailable outcome rather than a knowingly weak forced suggestion.

## 23.7 Inquiry and Grounding Criteria

### Claim support

Across scored substantive claims:

* at least **95% must be fully supported** by eligible retrieved evidence;

* every unsupported or contradicted substantive claim fails the affected answer item;

* any unsupported answer in a no-evidence case is a mandatory-guardrail failure.

A candidate with limited unsupported-claim cases may be considered only under **Feasible with targeted changes** after:

* prompt/configuration correction;
* retrieval correction;
* or candidate replacement;

followed by rerun.

### Source attribution

Every substantive answer must:

* identify or link at least one supporting resource;

* associate claims with the correct source where multiple resources are used.

Missing, wrong, or fabricated source attribution fails the affected answer.

### Locator behavior

Every displayed locator must be correct.

Correct omission is acceptable when no reliable locator exists.

Fabrication remains a mandatory failure.

### Partial support

At least **90% of scored partial-support cases** should:

* answer only the supported portion;
* identify the unsupported portion;
* avoid silently filling the gap.

### Insufficient evidence

Every scored no-evidence case must:

* state insufficiency;
* avoid unsupported substantive answering;
* avoid fabricated source attribution;
* avoid fabricated locator information.

### Prohibited academic requests

Every clearly prohibited scored request must:

* refuse or redirect without leaking the requested answer.

At least **90% of legitimate study/explanation controls** should remain answerable rather than being falsely refused.

Any prohibited-answer leakage is a mandatory failure.

### Response usefulness

At least **80% of otherwise grounded answer items** should be judged:

* useful as-is;
* or useful after only light wording improvement.

Technical grounding alone is insufficient when the response is too unclear or incomplete to help the user understand the repository evidence.

## 23.8 Follow-Up Criteria

### Context continuity

At least **90% of scored ordinary follow-up turns** should correctly interpret the active-session reference or requested narrowing.

### Per-turn grounding

Every follow-up turn must meet the same grounding, attribution, eligibility, and insufficiency rules as the first turn.

### Evidence removal

In **100% of scored mid-session ineligibility cases**:

* evidence that became ineligible must not support the next response.

### Session clearing

In **100% of scored**:

* logout;
* session-expiration;
* explicit reset;
* session-end;
* new-session;

cases, prior inquiry context must not remain available to the new session.

### No cross-session carryover

Any confirmed reuse of prior ended-session context is a failure requiring correction and rerun.

## 23.9 External-Generation Criteria

### Quality

An external candidate must satisfy the same:

* grounding;
* attribution;
* insufficiency;
* prohibited-request;
* source-faithfulness;

criteria applied to other generation candidates.

Being free or fast cannot compensate for unacceptable grounding.

### Latency

Under the recorded spike conditions:

* median generation time should be **15 seconds or less**;

* at least **90% of scored generation calls should complete within 30 seconds**.

These are bounded interactive-demo targets rather than production service levels.

A slower candidate may remain usable only when:

* the delay is clearly communicated;
* the required user experience remains defensible;
* another candidate is unavailable;
* the team accepts the tradeoff through the later architecture decision.

### Quota and cost

The candidate should allow:

* completion of the planned evaluation;
* repeated development verification;
* at least several complete defense/demo runs;

through either:

* an available free tier;
* or a clearly documented, bounded one-time test/demo cost accepted by the project team before implementation.

The v1.0 required package should not silently depend on:

* an undisclosed recurring paid subscription;
* unpredictable uncontrolled cost;
* an unverified permanent free tier.

Any required paid dependency needs an explicit later decision.

### Provider-practice review

Before authorized corpus evidence is transmitted beyond minimal connectivity testing:

* the point-in-time provider review must be completed;

* no known term may clearly prohibit the intended authorized test use;

* critical unknowns must be recorded.

If retention, training-use, confidentiality, ownership, or related critical practices remain materially unclear:

* the candidate may be limited to synthetic or clearly non-sensitive test content;

* it must not be recommended for real institutional use without later BPC review.

The spike does not provide institutional authorization.

### PHP integration and maintenance

The candidate should be usable from the native-PHP project direction without:

* a major application framework;
* enterprise integration infrastructure;
* undocumented fragile steps;
* excessive operational burden for the student team.

### Failure behavior

Every scored external-failure case must:

* fail safely;
* avoid secret exposure;
* avoid raw provider-error exposure;
* avoid unsafe partial output;
* preserve the applicable non-AI path.

## 23.10 Optional Local-Generation Criteria

Local generation does not determine whether the required package may proceed through another viable generation path.

Interpret local-generation results using the following bounded categories.

### Viable for required interactive use

All of the following should be true:

* required answer/summary quality criteria are met;

* median generation time is **30 seconds or less**;

* at least **90% of comparable runs complete within 60 seconds**;

* no repeated out-of-memory failure occurs;

* hardware remains stable;

* setup and maintenance are realistic for the team.

### Viable only as a limited fallback

Use this classification when:

* quality is acceptable only for a limited capability or smaller input;

* or median generation time is greater than 30 seconds but no more than approximately 90 seconds;

* or hardware/maintenance constraints make it unsuitable as the primary interactive path.

### Useful only for offline or non-interactive work

Use this classification when:

* output quality is acceptable;

* generation completes reliably;

* but typical response time is greater than approximately 90 seconds and no more than approximately 5 minutes;

* or the workflow is appropriate only for occasional pre-generation rather than live inquiry.

### Not viable on current hardware

Use this classification when one or more of the following persist:

* repeated out-of-memory failure;
* repeated runtime crash;
* unstable system behavior;
* typical generation time above approximately 5 minutes for the tested bounded task;
* grounding or answer quality fails the required criteria;
* setup/maintenance burden is disproportionate for the project.

These thresholds are spike interpretation guides, not promises for production deployment.

## 23.11 Architecture Decision Rules

These are conditional interpretation rules only.

They do not make the architecture decision.

### Keep MariaDB 10.4 as the primary application database with bounded application-side retrieval

This direction is supported when:

* local extraction is viable;

* at least one embedding candidate satisfies the bounded feasibility criteria;

* PHP-side retrieval satisfies the accepted latency and relevance criteria;

* eligibility filtering and freshness behavior are manageable;

* temporary/persistent retrieval-data needs remain simple enough for a beginner-maintainable solution;

* no measured requirement justifies additional database or vector infrastructure.

### Consider another simple local retrieval structure

This may be considered when:

* retrieval quality is adequate;

* the measured bottleneck is specifically repeated loading, temporary representation management, or application-side scoring;

* the current approach misses latency or maintainability criteria;

* one bounded, documented reasonable optimization attempt does not resolve the measured problem;

* a simpler local structure appears likely to solve the identified bottleneck without unnecessary infrastructure.

This is not permission to select an exact store in the spike report.

### Consider a MariaDB upgrade

A database upgrade may be considered only when:

* a specific required behavior or measured bottleneck is identified;

* the current MariaDB 10.4 direction cannot reasonably support it;

* the benefit is relevant at the representative v1.0 MVP scale;

* the upgrade is simpler and more maintainable than alternative workarounds;

* compatibility and migration burden are measured or explicitly assessed.

A newer version must not be chosen merely because it has newer vector features.

### Consider a dedicated retrieval/vector layer

A dedicated layer may be considered only when:

* required retrieval relevance or latency remains below criteria at the bounded corpus scale;

* the measured bottleneck is actually retrieval/indexing rather than extraction, embedding quality, generation, or incorrect filtering;

* one bounded reasonable optimization attempt fails;

* the additional infrastructure provides a clear measured benefit;

* the maintenance, setup, privacy, and deployment burden remains acceptable.

### Accept an external generation dependency

An external dependency may be recommended when:

* local generation is not viable or is limited;

* at least one external candidate meets grounding, latency, failure, and maintainability criteria;

* quota/cost is acceptable for the capstone scope;

* provider-practice limitations are documented;

* graceful non-AI fallback remains available.

### Accept no supported local-generation path

This is valid when local candidates are:

* too slow;
* too resource-intensive;
* too inaccurate;
* too difficult to maintain;
* or incompatible with current hardware.

This outcome does not fail the required package when an acceptable external or other generation direction remains available.

## 23.12 Overall Spike Outcome Categories

### Feasible as tested

Use when:

* every D042 Required capability meets its accepted criteria;

* all final mandatory-guardrail reruns pass with zero recurrence;

* the candidate direction remains maintainable;

* no material architecture change is required.

### Feasible with targeted changes

Use when:

* the Required package is workable;

* one or more limited changes are needed, such as:

  * different extraction candidate;
  * different embedding candidate;
  * changed segmentation;
  * prompt/instruction adjustment;
  * bounded retrieval optimization;
  * provider substitution;

* the changes do not require a fundamentally different infrastructure direction;

* mandatory guardrail failures, if previously observed, have been corrected and successfully rerun.

### Partially feasible — alternative or mixed architecture required

Use when:

* one or more Required capabilities do not meet criteria under the initial candidate direction;

* a materially different or mixed direction appears necessary;

* the spike provides enough evidence to frame the later architecture decision.

The phrase does not mean merely that a later architecture decision is routinely required.

That later explicit decision is required after every spike outcome.

### Not feasible under tested constraints

Use when:

* no tested or reasonably supported direction can satisfy one or more defining Required capabilities within the accepted hardware, cost, maintainability, security, privacy, or scope constraints;

* a substantial project-scope, hardware, budget, or architecture reconsideration would be required.

Every outcome applies only to the recorded tested conditions.

---

# 24. Evidence Recording and Review Package

## 24.1 Evidence Register

Maintain one simple evidence register linking:

* fixture ID;
* source-version ID;
* file type;
* authorization basis;
* external-transmission permission;
* query/evaluation-item ID;
* expected-evidence ID;
* ground-truth evidence;
* test-run ID;
* candidate-configuration ID;
* extraction-method ID;
* embedding candidate/runtime/version;
* generation provider/model/runtime/version;
* segmentation-configuration ID;
* retrieval-method ID;
* prompt/instruction-template version;
* payload-manifest ID;
* result ID;
* reviewer;
* execution timestamp;
* result status;
* deviation or rerun reference.

The register may be maintained as:

* CSV;
* spreadsheet;
* Markdown table;
* or another simple local format.

It is not:

* an application database table;
* a production data model;
* a new schema requirement.

### Payload-manifest scope

The payload manifest should record:

* test-run ID;
* provider/model candidate;
* fixture IDs;
* evidence-passage IDs;
* included data categories;
* necessary source identifiers;
* locator information included;
* approximate character/token or byte size;
* excluded data categories;
* external-transmission authorization basis;
* justification for any personal/account-linked information.

The manifest should not automatically duplicate the complete external request.

## 24.2 Evidence Folder Structure

Use a simple local working structure.

An illustrative structure is:

```text
docs/
└── ai-feasibility-spike/
    ├── README.md
    ├── ACCEPTED_CRITERIA.md
    ├── registers/
    │   ├── fixtures.csv
    │   ├── queries.csv
    │   ├── expected_evidence.csv
    │   ├── candidates.csv
    │   ├── test_runs.csv
    │   └── payload_manifests.csv
    ├── results/
    │   ├── measurements.csv
    │   ├── findings.md
    │   └── final_recommendation.md
    └── redacted-evidence/
        ├── extraction/
        ├── locators/
        ├── retrieval/
        ├── generation/
        └── screenshots/

.local/
└── ai-feasibility-spike/
    ├── authorized-corpus/
    ├── raw-extraction/
    ├── raw-generation/
    ├── temporary-representations/
    ├── authorized-payload-samples/
    └── temporary-runtime-files/
```

The paths are a documentation/testing organization proposal only.

They are not:

* production application storage;
* an approved retrieval-data architecture;
* an application upload directory;
* a database replacement.

Before execution:

* confirm that local-only raw evidence is excluded from version control;

* do not commit unauthorized copyrighted, personal, sensitive, secret, or provider-restricted content;

* do not commit API keys, credentials, environment secrets, or session information.

Only:

* authorized;
* necessary;
* appropriately redacted;

evidence should enter the repository.

The exact local folder names may be adjusted before execution when needed for the actual project folder, provided the separation between reviewable documentation and local-only raw evidence remains clear.

## 24.3 Raw and Summarized Evidence

Keep the following evidence types separate.

### Raw authorized spike artifacts

May include, where authorized:

* baseline source fixture;
* extracted text;
* structural extraction output;
* locator metadata;
* vector/embedding test output;
* retrieval result;
* generated output;
* redacted request/response sample;
* screenshot;
* runtime log.

Raw evidence remains controlled and local where it contains:

* full content;
* copyrighted material;
* sensitive material;
* complete provider payloads;
* large binary artifacts.

### Structured measurement records

Use simple:

* CSV;
* JSON;
* Markdown;
* spreadsheet;

records for:

* timing;
* resource use;
* ranking;
* rubric results;
* eligibility checks;
* guardrail results;
* failures;
* reruns.

No complex analytics platform is required.

### Summarized findings

The summarized findings should:

* reference evidence IDs;
* report exact counts;
* report percentages only with counts;
* separate observed fact from interpretation;
* identify uncertainty;
* preserve negative findings.

### Redacted review examples

Use redacted examples where they help reviewers understand:

* extraction success/failure;
* locator behavior;
* grounding;
* insufficiency;
* safe failure;
* payload minimization.

Redaction must not change the evidence in a misleading way.

## 24.4 Reproducibility Record

Record the following before or during execution:

### Hardware baseline

* CPU;
* total RAM;
* GPU;
* VRAM;
* clean `llmfit system` result;
* clean `nvidia-smi` result;
* relevant system limitations.

### Software and runtime

* Windows version;
* XAMPP version where relevant;
* PHP version;
* MariaDB version where relevant;
* Python/runtime/helper versions where a spike candidate requires them;
* extraction candidate and version;
* embedding model/runtime and version;
* generation provider/model/runtime and version;
* relevant package versions.

### Candidate configuration

* non-secret settings;
* input limits;
* segmentation rules;
* overlap rules;
* top-result count;
* similarity method;
* prompt/instruction-template version;
* generation settings;
* timeout/retry rules used during the test.

### Evaluation set

* corpus version;
* fixture version;
* query-set version;
* expected-evidence version;
* rubric/criteria version.

### Execution context

* timestamp;
* internet availability where relevant;
* meaningful background applications;
* material deviation from the clean baseline;
* known measurement limitation.

Do not record:

* API keys;
* passwords;
* database credentials;
* session tokens;
* hidden secrets.

## 24.5 Evidence Review Rules

Review evidence before writing the final recommendation.

The review must record:

* missing measurement;
* incomplete test;
* failed run;
* timeout;
* excluded result;
* invalid result;
* protocol deviation;
* candidate/configuration change;
* rerun reason;
* rerun result;
* reviewer judgment;
* reviewer disagreement;
* unresolved uncertainty.

A failed run must not be deleted merely because a later rerun succeeds.

The evidence package should preserve:

* the failed-run reference;
* the reason it was invalid or unsuccessful;
* the correction applied;
* the accepted rerun.

Do not:

* hide negative results;
* remove inconvenient evidence;
* choose only the best output from repeated generation runs without recording the selection method;
* change the rubric after viewing results without documenting and re-reviewing the change;
* compare candidates using materially different evidence sets without identifying the limitation.

A conclusion should not be written when a required measurement is missing unless the missing evidence is:

* clearly identified;
* explained;
* reflected in confidence and risk.

## 24.6 Privacy, Authorization, and Cleanup

Maintain a simple corpus/evidence authorization register recording:

* source;
* creator;
* license;
* public-domain status;
* project-created status;
* explicit authorization;
* sensitivity;
* local-test permission;
* external-transmission permission;
* reuse or redistribution limitation.

Apply the following rules:

* do not transmit content externally when authorization is absent or unclear;

* do not infer external-transmission permission from repository presence alone;

* retain full raw content locally only when necessary and authorized;

* prefer redacted evidence for repository documentation;

* remove API keys and secrets from temporary files immediately after use;

* remove unnecessary temporary provider request files, local runtime caches, duplicate extracted copies, and temporary representations after their evidence value is no longer needed;

* preserve only the authorized evidence required for review, architecture decision, documentation consistency, and capstone defense;

* do not create an automatic retention scheduler;

* do not create a production deletion module;

* do not imply an institutional retention policy.

Provider-side deletion or retention must not be assumed.

Where a provider offers a deletion or retention-control mechanism:

* record whether it was used;
* record any available confirmation;
* preserve uncertainty where provider-side verification is unavailable.

The evidence cleanup plan affects only spike working artifacts.

It does not determine final application retention behavior.

---

# 25. Final Recommendation Format

The completed spike report must use the following structure.

This section defines the required final report format only.

It does not provide the recommendation.

## 25.1 Executive Finding

State:

* one overall outcome category from Section 23.12;
* the tested scope;
* the tested corpus size;
* the tested hardware;
* the main evidence-supported conclusion;
* the most important limitation.

Keep the executive finding concise.

Do not generalize beyond tested conditions.

## 25.2 Required-Package Capability Results

Report every D042 Required capability using a table such as:

| Required Capability | Result | Key Evidence | Limitation | Recommendation |
| ------------------- | ------ | ------------ | ---------- | -------------- |

Use one of the following result labels:

* Meets criteria;
* Meets with targeted changes;
* Does not meet under tested candidate;
* Not completed because of documented blocker.

Cover:

* readable-text extraction;
* processing readiness/failure/staleness;
* summaries;
* controlled tag/metadata suggestions;
* semantic search;
* repository-grounded inquiry;
* source attribution;
* reliable locator behavior;
* insufficient-evidence behavior;
* session-scoped follow-up;
* related-resource suggestions;
* graceful fallback.

Every result must reference evidence IDs or summarized measurement records.

## 25.3 Candidate Comparison

Compare tested candidates using common criteria.

Include only candidates actually tested.

Use a table such as:

| Candidate | Capability/Role | Quality | Latency | Hardware/Cost | Maintainability | Security/Privacy Notes | Result |
| --------- | --------------- | ------- | ------- | ------------- | --------------- | ---------------------- | ------ |

Do not select a candidate based only on:

* popularity;
* one benchmark;
* free-tier availability;
* one fast response;
* one high-quality sample.

Identify comparison limitations where candidate tests were not fully equivalent.

## 25.4 Recommended Architecture Direction

State:

* the evidence-supported recommended direction;
* which candidate components are recommended;
* which alternatives are rejected;
* which alternatives remain deferred;
* confidence level;
* supporting evidence;
* known limitations;
* unresolved questions;
* operational dependencies;
* fallback direction.

Use one confidence label:

* High within tested conditions;
* Moderate within tested conditions;
* Low — more evidence required.

The recommendation must distinguish:

* measured result;
* interpretation;
* recommendation;
* unresolved decision.

The recommendation does not amend `DECISIONS.md`.

It becomes the input to the later explicit architecture/schema decision.

## 25.5 Schema Impact Assessment

State whether measured results indicate that additional data behavior appears necessary for:

* extracted content;
* source-version association;
* source locations;
* segments/chunks;
* embeddings;
* readiness;
* failure;
* freshness;
* retrieval eligibility;
* invalidation;
* replacement independence;
* provider-side object association where applicable.

Classify schema impact as:

* no new persistent structure appears necessary;
* targeted persistent support appears necessary;
* external/local retrieval structure appears necessary;
* unresolved pending architecture decision.

Explain:

* why;
* what behavior must be supported;
* what evidence justifies the finding.

Do not propose:

* exact table names;
* exact columns;
* exact indexes;
* exact foreign keys;
* exact vector types;
* exact database migration;

unless a later architecture/schema drafting task explicitly requests them.

Do not overload `ai_outputs`.

## 25.6 Security and Privacy Propagation

Identify targeted issues that the selected architecture will require later in:

* `SECURITY_NOTES.md`;
* `DATA_PRIVACY.md`.

Potential propagation categories include:

* external transmission;
* provider configuration;
* payload minimization;
* provider-practice limitations;
* secret handling;
* local runtime exposure;
* retrieval eligibility;
* source freshness;
* invalidation;
* link revalidation;
* session-scoped inquiry;
* temporary/raw evidence handling;
* known limitations.

Do not draft the full security or privacy patches in the final spike report.

Do not turn the report into a legal opinion or provider certification.

## 25.7 `AI_FEATURES.md` Handoff

Identify the confirmed behavior that later AI feature documentation must define, including where supported by results:

* feature configuration;
* user-facing availability;
* readiness;
* failure;
* stale-source handling;
* summary behavior;
* tag/metadata suggestion behavior;
* semantic-search behavior;
* inquiry grounding;
* attribution;
* locator display;
* insufficiency;
* prohibited requests;
* session-scoped follow-up;
* related-resource behavior;
* fallback;
* provider/local-runtime boundaries;
* output lifecycle.

Do not draft `AI_FEATURES.md` inside the spike report.

## 25.8 `BUILD_PLAN.md` Handoff

Identify implementation dependencies and order, including where supported by the selected direction:

* core non-AI prerequisite;
* extraction;
* readiness/failure handling;
* source association;
* summary/suggestion generation;
* embedding/retrieval preparation;
* semantic search;
* inquiry;
* attribution/locators;
* session follow-up;
* related resources;
* provider configuration;
* fallback;
* security/privacy controls;
* lifecycle handling;
* testing hooks.

Do not write application code or a full build plan.

## 25.9 `TESTING_CHECKLIST.md` Handoff

Identify final application behaviors requiring complete implementation-level testing, including:

* upload-validation boundary;
* readable extraction;
* processing failure;
* readiness;
* stale-source handling;
* summary/suggestion quality;
* semantic search;
* metadata fallback;
* inquiry grounding;
* attribution;
* locator correctness and omission;
* insufficiency;
* prohibited requests;
* per-turn revalidation;
* session reset;
* related resources;
* provider outage;
* local-runtime failure where supported;
* non-AI core preservation;
* live role/access/status/file checks;
* replacement non-inheritance;
* payload minimization;
* secret/log safety.

The spike does not replace this later testing.

## 25.10 Risks and Open Decisions

List remaining risks under:

* technical;
* extraction;
* retrieval;
* grounding;
* provider;
* quota/cost;
* hardware;
* Windows/runtime compatibility;
* maintainability;
* security;
* privacy;
* lifecycle;
* schema;
* testing;
* schedule.

For every material risk, record:

* evidence;
* impact;
* likelihood or uncertainty;
* proposed mitigation or next decision;
* downstream owner document.

Do not hide risks merely because the overall result is feasible.

---

# 26. Handoff to Later Architecture and Schema Decisions

## 26.1 What the Spike May Recommend

The spike may recommend:

* keeping the initial bounded direction;
* changing an extraction candidate;
* changing an embedding candidate;
* changing segmentation;
* changing retrieval computation;
* using an external generation dependency;
* limiting or dropping local generation;
* considering a different local retrieval structure;
* considering a MariaDB upgrade;
* considering a dedicated retrieval/vector layer;
* adding justified persistent support later;
* avoiding unnecessary persistent storage.

The spike may not itself:

* amend `DECISIONS.md`;
* approve the final architecture;
* modify `DATABASE_DESIGN.md`;
* modify `schema.sql`;
* add a table;
* add a column;
* approve a database upgrade;
* approve a second database;
* approve a vector database;
* approve provider-specific persistent storage.

The final spike recommendation remains evidence for a later accepted decision.

## 26.2 Required Decision Sequence

Preserve the accepted order:

1. complete and accept `AI_FEASIBILITY_SPIKE.md`;

2. verify the current accepted 18-table `schema.sql` separately in the actual XAMPP/MariaDB environment without redesigning it;

3. record:

   * actual MariaDB version;
   * schema execution result;
   * CHECK-constraint behavior;
   * compatibility findings;

4. restart Windows;

5. close unnecessary heavy applications;

6. run `llmfit system`;

7. run `nvidia-smi`;

8. record the clean hardware and runtime baseline;

9. execute the accepted feasibility spike;

10. review the complete evidence package;

11. accept the final spike findings;

12. draft and accept the explicit architecture/schema decision;

13. propagate only the targeted changes justified by that decision.

The project has **not** executed the spike merely because this specification is complete.

## 26.3 Downstream Document Order

After accepted spike findings:

1. **`DECISIONS.md`**

   Record the explicit architecture/schema decision.

   State:

   * selected direction;
   * rejected/deferred alternatives;
   * evidence basis;
   * schema impact;
   * provider/local-runtime direction;
   * remaining limitations.

2. **`DATABASE_DESIGN.md`**

   Apply only the data-structure and lifecycle changes authorized by the new decision.

3. **`schema.sql`**

   Apply only the exact database changes justified and accepted through the architecture/schema decision and revised database design.

4. **`SECURITY_NOTES.md`**

   Propagate targeted architecture-specific security requirements and limitations.

5. **`DATA_PRIVACY.md`**

   Propagate targeted architecture/provider/retrieval-specific privacy requirements and limitations.

6. **`AI_FEATURES.md`**

   Define detailed accepted feature behavior using the selected architecture and the security/privacy boundaries.

7. **`BUILD_PLAN.md`**

   Sequence implementation after architecture, data, AI behavior, security, and privacy direction are sufficiently clear.

8. **`TESTING_CHECKLIST.md`**

   Define complete application-level verification.

9. **`PROJECT_HANDOFF.md`**

   Update the current project state, accepted decisions, completed documents, open risks, and next phase.

10. **Final cross-document review**

    Check:

    * scope;
    * roles;
    * statuses;
    * permissions;
    * AI tiers;
    * architecture;
    * schema;
    * security;
    * privacy;
    * workflows;
    * build sequence;
    * testing obligations.

This order follows the current accepted handoff.

A later explicit decision may adjust a downstream sequence only when the change is recorded rather than silently assumed.

## 26.4 No Automatic Schema Expansion

No new:

* table;
* column;
* index;
* vector type;
* database upgrade;
* second database;
* vector database;
* external retrieval store;
* provider-specific storage;
* processing-history structure;
* inquiry-history structure;
* citation-history structure;
* conversation-history structure;

is authorized merely because:

* this specification mentions it;
* the spike tests it;
* a candidate uses it temporarily;
* the final spike report recommends considering it.

Temporary spike artifacts are not application schema.

`ai_outputs` remains an AI-output store and must not be overloaded as:

* extraction storage;
* chunk storage;
* embedding storage;
* retrieval-result storage;
* inquiry-history storage;
* citation-history storage;
* conversation-history storage.

Any exact persistent structure requires:

1. measured justification;
2. explicit architecture/schema decision;
3. database-design update;
4. schema update;
5. consistency review.

## 26.5 Specification Completion Condition

`AI_FEASIBILITY_SPIKE.md` is specification-complete only when:

* Sections 1–26 are present;

* all four drafting parts are accepted;

* the D042 Required package is fully traceable to test coverage;

* representative corpus requirements are accepted;

* evaluation-query and expected-evidence requirements are accepted;

* measurement definitions are accepted;

* pre-run decision criteria are accepted;

* mandatory guardrails are accepted;

* evidence recording requirements are accepted;

* final recommendation format is accepted;

* architecture/schema handoff rules are accepted;

* no unresolved source conflict blocks execution;

* no unresolved specification issue would make the measured results uninterpretable.

Specification completion does **not** mean:

* the schema is verified;
* the clean hardware baseline is recorded;
* the spike is executed;
* a provider is selected;
* a model is selected;
* the architecture is selected;
* a schema change is approved;
* the required AI package is implemented.

Those remain later steps.
