# AI_FEATURES.md

**Project:** BPC LearnShare — AI-Assisted Collaborative Academic Resource Sharing and Management System
**Version:** Draft v1.0 — D043 implementation baseline
**Last Updated:** 2026-08-20
**Status:** Accepted AI architecture/behavior planning through D043; migration and application integration pending

---

## 1. Purpose and authority

This document translates D041–D043 and the completed feasibility evidence into the bounded AI behavior and implementation direction for BPC LearnShare v1.0.

AI remains assistive, non-authoritative, configurable, and independently degradable. The non-AI repository must continue to support authentication, upload, moderation, metadata search/filtering, view/download, and other accepted core workflows when AI is disabled, unconfigured, unavailable, rate-limited, or failing.

No AI feature may approve, reject, publish, validate, Hide, Restrict, Remove, replace, or delete a resource automatically. No AI output replaces Teacher/Instructor, Moderator, or Admin judgment.

## 2. Evidence-based architecture direction

The accepted D043 direction is mixed by capability:

* native PHP controls authorization, eligibility, freshness, processing state, retrieval, citations/links, refusal, fallback, and lifecycle behavior;
* MariaDB 10.4.32 remains the single local system of record;
* readable PDF, DOCX, PPTX, and TXT extraction is local where extraction succeeds;
* source-bound chunks preserve verified locators;
* lightweight embeddings remain local where practical;
* bounded semantic similarity uses PHP cosine plus metadata filters/fallback;
* optional summaries and controlled suggestions may use a replaceable external or local adapter after separate configuration/authorization;
* generated repository inquiry and generated follow-up remain unavailable until another versioned candidate passes every accepted criterion.

The accepted architecture does not select a permanent provider/model and does not introduce a vector database, second database, MariaDB upgrade, hosted retrieval service, or provider-managed index.

## 3. Persistent derived-data boundary

After a separately approved migration, the conceptual 22-table target adds:

* `ai_source_versions` for exact protected-file fingerprint/version and extracted readable text;
* `ai_processing_states` for per-capability readiness/failure/configuration and late-result protection;
* `ai_chunks` for version-bound text and verified locators;
* `ai_embeddings` for chunk-bound vector data and embedding identity.

The current SQL baseline remains 18 tables until the migration is separately approved, implemented, and verified.

`ai_outputs` remains the current-value store for accepted output types such as summaries, controlled suggestions, duplicate flags, and moderation hints. It must not store extraction text, chunks, vectors, retrieval histories, query vectors, inquiry answers, citations, chat messages, or permanent session memory.

Retrieved candidates, query vectors, answers, citations, and active follow-up context remain request- or session-scoped.

## 4. Eligibility and freshness

Before processing, retrieval, external transmission, or display, PHP must recheck all applicable conditions:

1. requester account exists and is Active;
2. requester is authorized for the feature and resource;
3. general retrieval resources are currently Approved;
4. `file_availability = 'available'`;
5. feature is enabled and configured;
6. required processing state is ready;
7. source version is current;
8. stored source fingerprint still matches the protected file;
9. output/candidate still belongs to that source version;
10. conditions still pass immediately before transmission and display.

No stored eligibility boolean replaces these live checks.

Pending and Needs Correction resources may receive uploader/staff-visible draft assistance only after successful basic upload validation, clear notice, and uploader acknowledgment. They never enter general semantic retrieval, related-resource results, or inquiry evidence.

## 5. Processing pipeline

The shared pipeline is:

```text
eligible protected file
  -> fingerprint/current source version
  -> local readable-text extraction
  -> source-bound chunking and locator preservation
  -> local embedding adapter
  -> MariaDB-derived-data storage
  -> PHP cosine plus metadata filters/fallback
  -> live revalidation
  -> semantic results or related resources
```

For the local/LAN prototype, processing may use one bounded CLI/admin-triggered processor rather than a queue server or framework.

The processor must:

* mark one capability queued/processing with a unique run token;
* perform expensive work outside long database transactions;
* validate counts, hashes, finite vectors, dimension, norm, model/config identity, and locator rules;
* recheck run token, source version, status, file availability, and authorization before final write;
* discard late or stale results;
* never promote a partial result to ready;
* preserve only safe error code/summary data.

## 6. Capability behavior

### 6.1 Summaries

Summaries are non-authoritative, source-version-bound, visibly labelled AI-assisted output. Pending summaries are draft-only. A retained summary requires human review under the accepted workflow.

### 6.2 Suggested tags and metadata

Suggestions are review aids only. They may reference existing controlled tags and limited accepted metadata fields. They must not create taxonomy values, mutate resource metadata, or trigger moderation/status actions without an authorized human action through the normal validated workflow.

### 6.3 Semantic search

Semantic search supplements ordinary metadata search. It uses only current, ready, live-eligible Approved resources. Cosine score alone must not claim that repository evidence is sufficient because the measured supported/unsupported distributions overlap.

When semantic processing is unavailable, the UI must preserve ordinary metadata search/filtering and explain the AI-only limitation in non-technical language.

### 6.4 Related resources

Related resources are computed request-time from current eligible candidates using content similarity plus conservative metadata guards. Every returned resource/link is live-revalidated. No behavioral learner profile or permanently trusted recommendation list is created.

### 6.5 Repository-grounded inquiry and follow-up

Repository-grounded inquiry remains a defining D041–D042 requirement, but no tested generation candidate passed the combined grounding, exact-attribution, usefulness, insufficiency, and latency criteria.

Therefore, generated inquiry and generated follow-up are not authorized for application integration yet. The application may show an honest unavailable status and direct users to metadata/semantic search. A later candidate must be versioned and rerun against every accepted criterion before this boundary changes.

No open-web retrieval, unrestricted tutoring, exam/quiz/graded-answer assistance, permanent chat history, or cross-session memory is allowed.

### 6.6 Planned duplicate flags and moderation hints

These remain planned enhancements after the required foundation is stable. They are non-authoritative indicators only and cannot execute any resource action.

## 7. External-provider boundary

An optional provider adapter must be disabled/unconfigured by default and replaceable by configuration.

Before any external call:

* confirm the feature and provider are separately authorized/configured;
* recheck source eligibility/freshness;
* send only the minimum source text and metadata needed for that capability;
* exclude account data, sessions, credentials, protected paths, unrelated resources, and ineligible content;
* use bounded timeout/retry behavior and feature-specific fallback.

API keys remain in ignored server-side environment configuration. They must not appear in MariaDB, client-side code, page output, ordinary logs, evidence logs, commits, or screenshots.

Do not persist full provider prompts or responses. Safe operational evidence may record IDs, configuration versions, counts, hashes, status codes, token counts, timings, cost estimates, and short redacted error categories.

Provider/model terms, ZDR/retention, quotas, pricing, model identity, and availability must be rechecked before use and before defense claims.

## 8. Lifecycle and cleanup

* File change: old source version and dependent state become stale; late results are rejected; a new current version is processed independently.
* Hidden/Restricted: exclude from new general retrieval/transmission.
* Rejected/Withdrawn: invalidate/delete draft output and derived content as required.
* Replaced: no source/chunk/vector/output/citation inheritance.
* Removed: delete extracted text, chunks, embeddings, and AI output before completion, while preserving only the accepted minimal accountability record.
* AI/provider failure: mark only the affected capability failed and keep core workflows working.

Metadata changes refresh only capabilities whose recorded dependency fingerprint includes the changed fields.

## 9. Configuration and operational notes

Local runtime paths, including the verified Ollama model location `E:\AI\Ollama\Models`, are environment settings and must not be hard-coded into database rows or tracked source. Model reference/digest/configuration identity may be recorded with derived output for reproducibility.

Moving the local model directory does not change vector meaning when the same model digest is loaded, but the drive must remain available under the configured letter and cold loading from an HDD may be slower.

## 10. Implementation and testing gate

D043 accepts the architecture direction. The exact migration package has passed disposable verification; before integration:

1. **Completed:** review exact executable migration and rollback SQL;
2. **Completed:** verify a fresh MariaDB 10.4.32 import and expected 22-table count;
3. **Completed:** verify fail-closed handling for existing `ai_outputs` without fabricated source/configuration identity;
4. implement repositories and processing orchestration;
5. pass source freshness, concurrency, cleanup, eligibility, security/privacy, retrieval regression, and AI-disabled fallback tests;
6. obtain owner browser acceptance for any user-visible AI surface.

No provider/model selection or generated-inquiry integration is implied by completing the storage migration.
