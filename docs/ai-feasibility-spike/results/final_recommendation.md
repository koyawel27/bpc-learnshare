# AI Feasibility Spike — Final Recommendation

**Status:** Final evidence reconciliation completed and approved for documentation; later architecture/schema decision still required
**Canonical format:** `docs/AI_FEASIBILITY_SPIKE.md`, Section 25
**Evidence baseline:** Repository `5a27440`; 17 candidates, 64 test runs, 15 external payload manifests, and 751 measurements

## 25.1 Executive Finding

**Overall outcome:** **Partially feasible — alternative or mixed architecture required.**

BPC LearnShare can feasibly support readable-text extraction, source-bound segmentation, local embeddings, bounded native-PHP cosine retrieval, metadata-guarded related-resource suggestions, non-authoritative summaries and controlled suggestions, verified locator presentation, deterministic lifecycle controls, and graceful non-AI fallback under the tested small-corpus conditions.

The tested scope used 30 synthetic fixtures—25 readable resources and five boundary-negative resources—producing 102 corrected chunks. Local work ran on Windows/XAMPP with an Intel Core i7-7500U, 11.87 GB RAM, and 2 GB VRAM. Generation testing also included one separately authorized external GroqCloud candidate.

The complete Required package is not feasible as tested because no generation candidate met grounded-inquiry claim support, exact attribution, usefulness, and interactive-latency criteria together. The external candidate was fast and useful but failed strict grounding and attribution; local candidates were slower and failed required quality. Repository-grounded inquiry must therefore remain disabled or explicitly unavailable until another versioned candidate or configuration passes the accepted criteria.

**Confidence:** **Moderate within tested conditions.** Evidence is strong for the bounded local/LAN prototype corpus, but limited for production integration, concurrency, larger corpora, provider continuity, and complete application-level lifecycle and fallback behavior.

## 25.2 Required-Package Capability Results

| Required Capability | Result | Key Evidence | Limitation | Recommendation |
| --- | --- | --- | --- | --- |
| Readable-text extraction | Meets criteria | `TR-EXT-SMOKE-001`–`009`; `TR-EXT-FULL-001`–`025`; `TR-EXT-FIDELITY-001`. All 25 readable fixtures were extracted and 137 evidence passages retained exact or complete ordered coverage. | Synthetic bounded corpus; OCR/image-only text remains deferred. | Retain `EX-LOCAL-PHP-001` for supported readable PDF, DOCX, PPTX, and TXT with explicit failure states. |
| Processing readiness/failure/staleness | Meets with targeted changes | `TR-EMB-FULL-001` preserved a failed context-fit attempt without false readiness; `TR-SEG-CORPUS-002` and `TR-EMB-FULL-002` corrected it; `TR-CTRL-SESSION-LIFECYCLE-001` and Gate 5B excluded stale and ineligible sources. | Persistent readiness, replacement cleanup, late-result handling, and application-wide integration are not implemented. | Add explicit source-bound readiness, failure, freshness, invalidation, and replacement-independent behavior. |
| Summaries | Meets criteria | `TR-GEN-GROQ-SUMSUG-001`; eight of eight summaries were source-supported and usable. | Eight synthetic resources and one external candidate; provider dependency and human review remain. | Permit optional non-authoritative draft summaries behind a replaceable adapter and mandatory human review. |
| Controlled tag/metadata suggestions | Meets criteria | `TR-GEN-GROQ-SUMSUG-001`; 90% directly usable Active tags, 100% eligible-tag coverage, 85.71% supported metadata, and zero Inactive tags. | Three weak Handout suggestions and one broad Programming tag show that automatic assignment is unsafe. | Present suggestions for confirmation only; enforce Active controlled values and allow “not reliably inferable.” |
| Semantic search | Meets with targeted changes | `TR-EMB-FULL-002`; `TR-SIM-PHP-COSINE-001`; `TR-RET-SEMANTIC-001`; `TR-RET-MANUAL-REVIEW-001`; `TR-RET-GT-V2-001`. Corrected resource top-five was 100%, passage top-five 96%, semantic value 100%, and median query-to-results latency about 106 ms. | Small synthetic corpus; supported and unsupported score distributions overlap; permanent loading, eligibility, and concurrency are unresolved. | Retain local embedding plus PHP cosine as a bounded candidate, combined with metadata filters/fallback and evidence checks. |
| Repository-grounded inquiry | Does not meet under tested candidate | Local Llama/Qwen grounded comparisons reached only 50% usefulness. `TR-GEN-GROQ-GROUNDED-001` was fast and 83.33% useful but achieved 88.89% claim support against the required 95%. | No tested generator met grounding, attribution, usefulness, and latency together. | Keep inquiry unavailable; test a new versioned candidate only after the supported components and controls are integrated. |
| Source attribution | Does not meet under tested candidate | `TR-ATTR-END-USER-PRESENTATION-001` passed 10/10 deterministic display cases, but `TR-GEN-GROQ-GROUNDED-001` failed exact attribution when unsupported details were attached to supplied labels. | Presentation controls cannot repair unsupported generated claims. | Require claim-level support and final source revalidation from any future inquiry candidate; do not expose generated inquiry now. |
| Reliable locator behavior | Meets criteria | `TR-EXT-FIDELITY-001` and `TR-ATTR-END-USER-PRESENTATION-001` preserved verified locators and omitted unavailable locators without fabrication. | Live protected-link integration remains untested. | Retain source-version-bound verified locators and omit rather than infer missing locations. |
| Insufficient-evidence behavior | Meets criteria | `TR-CTRL-GROUNDED-MODEL-INDEPENDENT-001` and `TR-CTRL-SESSION-LIFECYCLE-001` passed fail-closed controls; `TR-GEN-GROQ-GROUNDED-001` passed the fixed no-evidence case. | Live request classification and routing remain implementation work; cosine score alone is unsafe. | Keep deterministic insufficiency handling outside the model and show a visible safe state. |
| Session-scoped follow-up | Meets with targeted changes | `TR-CTRL-SESSION-LIFECYCLE-001` passed 200/200 deterministic context/lifecycle cases. Natural-language Llama interpreted 10/10 references and Qwen 8/10, but both quality runs failed the combined criteria. | Production PHP session behavior, live revalidation, and a passing answer generator are absent. | Retain bounded session-only control rules, but do not enable generated follow-up until inquiry passes. |
| Related-resource suggestions | Meets criteria | `TR-REL-CENTROID-COSINE-001` met top-five/usefulness thresholds; `TR-REL-NO-USEFUL-BOUNDARY-001` safely returned no result; `TR-REL-METADATA-GUARDED-POSITIVE-REGRESSION-001` retained expected results in 5/5 cases; Gate 5C passed 18/18 live checks. | Relation groups and live sample are deliberately small; the final ranking rule and UI remain unselected. | Retain conservative metadata-guarded suggestions with eligibility revalidation and a safe no-result state. |
| Graceful fallback | Meets with targeted changes | `TR-CTRL-GROUNDED-MODEL-INDEPENDENT-001`, `TR-CTRL-SESSION-LIFECYCLE-001`, and Gate 5B preserved metadata search/download and suppressed AI output when disabled, unavailable, stale, missing, or invalidated. | Complete routed application behavior and provider interruption/dependency testing remain incomplete. | Keep every AI feature optional/default-off and preserve ordinary upload, moderation, search, view, and download paths. |

**Reconciled totals:** 6 Meets criteria; 4 Meets with targeted changes; 2 Does not meet under tested candidate; 0 Not completed because of documented blocker.

## 25.3 Candidate Comparison

| Candidate | Capability/Role | Quality | Latency | Hardware/Cost | Maintainability | Security/Privacy Notes | Result |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `EX-LOCAL-PHP-001` | Readable extraction | 25/25 readable resources plus fidelity/locator review passed | Accepted bounded local execution | Local CPU; no provider cost | Native PHP with narrow format helpers | Source content remains local; boundary failures explicit | Retain as tested component |
| `SEG-BLOCK-AWARE-CONTEXT-FIT-002` | Corrected segmentation | 102 deterministic chunks; one context-fit failure corrected without hiding it | Preparation-time operation | Local; negligible added cost | Narrow versioned rule | Source IDs and locators retained locally | Retain as tested component |
| `EMB-OLLAMA-ALL-MINILM-001` | Local embeddings | 102/102 valid normalized 384-dimensional vectors after correction | Practical for the bounded corpus | Current laptop; no per-call API cost | Requires local Ollama/model identity checks | Corpus remains local | Retain as bounded embedding candidate |
| `SIM-PHP-COSINE-001` / `RET-SEMANTIC-STANDALONE-001` | Similarity and semantic retrieval | Corrected resource 100%, passage 96%, semantic value 100% | About 106 ms median for scored query-to-results | Native PHP over 102 vectors | Simple at current scale | Requires eligibility and freshness revalidation | Retain with targeted hybrid/evidence changes |
| Metadata-guarded related-resource configuration | Related resources | Safe no-result passed; positive regression returned expected resources 5/5 with reviewed usefulness 15/15 | Millisecond ranking in isolated tests | Local; reuses vectors/metadata | Simple rule but mapping is corpus-specific | Must revalidate status, file, and Active tags | Retain as conservative candidate rule |
| Gate 5A/5B controls and `TR-CTRL-SESSION-LIFECYCLE-001` | Safety, lifecycle, session, fallback | 18/18, 19/19, and 200/200 bounded checks passed | Deterministic control overhead only | Native PHP/MariaDB; no model cost | Explicit and testable | Fail-closed eligibility, revalidation, session clearing, safe logs | Required control foundation |
| `GEN-OLLAMA-QWEN3-4B-001` | Local synthetic-generation preflight | 0/5 automated checks; visible reasoning and output-limit hits | 72.391 s median | CPU-heavy; partial GPU offload; no API fee | Local runtime but poor tested behavior | Content stays local | Reject tested configuration |
| `GEN-OLLAMA-LLAMA32-3B-001` plus follow-up configuration | Local grounded inquiry/follow-up | Grounded usefulness 50%; follow-up 8/10 grounded with one critical RBAC error | Grounded median 41.6 s; follow-up 26.786 s | Current laptop; no API fee | Simple local service but weak quality/latency | Local data; model still unsafe for authority-sensitive answers | Reject for required interactive inquiry; defer optional experiments |
| `GEN-OLLAMA-QWEN35-4B-002` plus follow-up configuration | Local grounded inquiry/follow-up | Grounded usefulness 50%; follow-up 8/10 correct with unnecessary clarification | Grounded median 66.0 s; follow-up 37.243 s | Current laptop with low remaining RAM | Local service; slow and inconsistent | Local data; prohibited/follow-up behavior failed | Reject for required interactive inquiry |
| `GEN-GROQ-GPT-OSS-120B-001` grounded configuration | External grounded inquiry | 88.89% claim support failed 95%; 83.33% usefulness passed; attribution failed | 1,618.82 ms median; 6/6 within 30 s | Low measured test cost; network/provider dependency | Replaceable HTTP adapter is practical | External transmission, ZDR, terms, quota, retention, and key controls required | Reject tested grounded configuration |
| `GEN-GROQ-GPT-OSS-120B-001` Gate 5E configuration | External summaries/suggestions | All accepted summary, tag, metadata, and usability thresholds passed | 1,944.858 ms median; 8/8 within 15 s | Estimated USD 0.0038472 for the bounded run | Structured response needs provider-compatible schema and human review | Minimized authorized synthetic payloads only; provider remains unselected | Viable capability-specific candidate; provider/model not selected |
| Metadata search/download and visible unavailable states | Non-AI fallback | Preserved through tested disabled/unavailable/ineligible conditions | Normal non-AI path | No AI cost or runtime dependency | Existing core remains independently maintainable | Avoids unauthorized transmission and unsafe output | Mandatory retained fallback |

Candidate tests were not fully equivalent: extraction, retrieval, controls, relation rules, local generation, external grounded generation, and summary/suggestion generation used different capability-specific cases and criteria. The Gate 5E pass must not be generalized into a grounded-inquiry pass.

## 25.4 Recommended Architecture Direction

### Measured result

The bounded evidence supports local extraction, corrected source-bound chunks, local lightweight embeddings, native-PHP cosine retrieval, deterministic PHP controls, conservative metadata-guarded related resources, and optional reviewed summary/suggestion assistance. It does not support any tested local or external configuration for repository-grounded inquiry.

### Interpretation

The simplest viable direction is mixed by capability rather than one model for everything. “Mixed” means combining local deterministic processing and retrieval, optional replaceable provider-backed assistance where it passed, and non-AI fallback. It does not mean that a provider, model, vector database, or second database has been selected.

### Recommendation

1. Keep native PHP and MariaDB 10.4 as the core application platform.
2. Keep upload, moderation, metadata search, browsing, protected download, and other core workflows independent of AI.
3. Retain local extraction, versioned source-bound chunks, local `all-minilm` embeddings, and bounded PHP cosine as candidate components for the small prototype corpus.
4. Combine semantic retrieval with metadata filters and fallback; never use a cosine threshold alone to claim that sufficient evidence exists.
5. Keep classification, eligibility, freshness, final revalidation, insufficiency, refusal, locator shaping, session reset, and fallback controls in PHP outside the model.
6. Permit optional non-authoritative summaries and controlled suggestions only behind a replaceable adapter, explicit configuration, payload minimization, source-version binding, and human review. Provider/model selection remains a later decision.
7. Do not implement generated repository inquiry or generated follow-up until a versioned candidate passes all grounding, attribution, usefulness, and latency criteria. Show a transparent unavailable/fallback state instead.
8. Defer local generation to optional future experimentation; it is not a reliable interactive fallback on the tested laptop.

**Confidence:** **Moderate within tested conditions.**

### Unresolved decisions and dependencies

- The exact provider/model, if any, for optional summaries and suggestions.
- The permanent loading/storage representation for extracted content, chunks, embeddings, readiness, and lifecycle state.
- Live processing orchestration, cleanup, retry/late-result behavior, concurrency, and corpus-growth performance.
- Provider authorization, terms, quota, retention/ZDR recheck, credential handling, and outage behavior.
- A future inquiry candidate and rerun of every grounding/attribution criterion.

This recommendation does not amend `DECISIONS.md`. It is the evidence input to a separate explicit architecture/schema decision.

## 25.5 Schema Impact Assessment

**Classification:** **Targeted persistent support appears necessary.**

Measured extraction, segmentation, embedding, retrieval, readiness, failure, freshness, lifecycle, and replacement behavior require durable source-version association and independently invalidatable derived state. Later architecture/schema work must represent extracted content, verified source locations, versioned chunks, embedding/index identity, per-capability readiness/failure, freshness, retrieval eligibility, invalidation, replacement non-inheritance, and cleanup. Provider-side object association is required only if a later selected provider actually creates such objects.

The evidence does not justify a vector database, second database, MariaDB upgrade, provider-specific schema, or production-scale retrieval service. The existing `ai_outputs` table must not be overloaded for extracted text, chunks, vectors, retrieval results, readiness state, or chat history.

Exact tables, columns, indexes, foreign keys, vector types, and migrations remain deferred to the later architecture/schema decision.

## 25.6 Security and Privacy Propagation

Later targeted updates to `SECURITY_NOTES.md` and `DATA_PRIVACY.md` must cover:

- explicit authorization before any external transmission and a non-AI path when authorization is absent;
- provider configuration, terms, retention/ZDR, quota, availability, and model-version rechecks;
- payload minimization and exclusion of accounts, sessions, credentials, protected paths, unrelated resources, and ineligible content;
- project-specific secret storage, redacted logs, and prevention of prompt/response leakage;
- local runtime network exposure and model-artifact/version controls;
- Approved/current/file-available retrieval eligibility and revalidation before transmission and before display;
- source-version freshness, invalidation, replacement independence, cleanup, and late-result rejection;
- protected source links and correct locator omission;
- bounded session-only inquiry context with logout, expiration, reset, and session-end clearing;
- temporary/raw evidence retention and known external-provider limitations.

This report is not a legal opinion or provider certification.

## 25.7 `AI_FEATURES.md` Handoff

Later AI feature documentation must define:

- optional/default-off configuration and visible unavailable/failure states;
- independent readiness by capability and safe behavior for failed or stale processing;
- human-reviewed, non-authoritative summaries and controlled tag/metadata suggestions;
- hybrid semantic/metadata search and conservative related-resource suggestions;
- Approved/current/file-available eligibility and source-version-bound output lifecycle;
- verified attribution/locator display and omission of unavailable locations;
- deterministic insufficiency and prohibited-request refusal;
- bounded same-session context and reset behavior;
- provider/local-runtime boundaries and complete non-AI fallback;
- generated inquiry as unavailable until a passing candidate is accepted through a later decision.

## 25.8 `BUILD_PLAN.md` Handoff

Recommended implementation order:

1. Preserve and regression-test the working non-AI core.
2. Add source-bound processing readiness, failure, freshness, invalidation, and cleanup behavior.
3. Integrate approved-format extraction and locator preservation.
4. Integrate corrected segmentation and local embedding preparation.
5. Add hybrid semantic search with metadata filtering/fallback and live eligibility revalidation.
6. Add conservative related-resource suggestions and a safe no-result state.
7. Add optional reviewed summary/suggestion assistance behind replaceable configuration and human confirmation.
8. Integrate revalidation, protected attribution links, session controls, provider outage handling, and complete fallback.
9. Reconsider generated inquiry only after a later candidate passes the accepted criteria.

Provider configuration, security/privacy propagation, lifecycle behavior, cleanup, and testing hooks are explicit dependencies. This report does not itself implement them.

## 25.9 `TESTING_CHECKLIST.md` Handoff

Application-level testing must still cover:

- upload validation before processing and readable extraction across supported formats;
- processing failure, readiness, retry, late result, stale source, invalidation, and replacement non-inheritance;
- summary/suggestion support, controlled-value enforcement, ambiguity, human review, and source-version binding;
- semantic search, metadata fallback, filters, corpus growth, loading strategy, and concurrent requests;
- related-resource eligibility, relation mapping, safe no-result behavior, and protected links;
- inquiry grounding, claim-level attribution, locator correctness/omission, insufficiency, prohibited requests, and per-turn/final revalidation for every future candidate;
- session continuity, logout/expiration/reset/session-end clearing, and cross-session isolation;
- provider outage, quota, interruption, timeout, malformed output, and local-runtime failure;
- AI-disabled operation and preservation of upload, moderation, metadata search, view, and download;
- live role/access/status/file checks, payload minimization, secret safety, and safe logs.

The spike evidence does not replace these integration tests.

## 25.10 Risks and Open Decisions

| Risk Category | Evidence | Impact | Likelihood/Uncertainty | Mitigation or Next Decision | Downstream Owner |
| --- | --- | --- | --- | --- | --- |
| Technical | Bounded components passed separately; complete routed pipeline was not tested. | Integration faults could invalidate otherwise passing components. | Medium; integration evidence is incomplete. | Build in small gates with fail-closed seams and regression tests. | `BUILD_PLAN.md`, `TESTING_CHECKLIST.md` |
| Extraction | 25/25 readable synthetic fixtures passed; OCR/image-only content is deferred. | Some real uploads may yield no usable text. | Medium for mixed real-world files. | Support only verified readable formats initially and expose failure states. | `AI_FEATURES.md`, `TESTING_CHECKLIST.md` |
| Retrieval | 102-vector corpus passed, but supported/unsupported scores overlap. | Weak or unsupported results may appear plausible. | High uncertainty beyond the small corpus. | Use metadata/evidence guards, no score-only sufficiency rule, and remeasure after growth. | Architecture decision, `TESTING_CHECKLIST.md` |
| Grounding | Local candidates reached 50% usefulness; Groq reached 88.89% claim support and failed attribution. | Unsupported academic answers could be shown as repository-grounded. | High under tested candidates. | Block inquiry until a later candidate passes all criteria. | `AI_FEATURES.md`, later candidate decision |
| Provider | Gate 5D/5E depended on Groq availability, terms, ZDR, model listing, and network access. | A feature can become unavailable or its handling assumptions can change. | Medium and time-sensitive. | Keep adapter replaceable, recheck before use, and preserve visible fallback. | `DATA_PRIVACY.md`, `SECURITY_NOTES.md` |
| Quota/cost | Bounded runs were inexpensive but project limits were low and sustained use was not measured. | Demo or real use may hit limits or incur unexpected cost. | Medium uncertainty. | Add hard budgets/limits, cache current outputs, and document provider failure. | Architecture decision, `BUILD_PLAN.md` |
| Hardware | Local embedding worked; local generation was slow and quality-limited on the i7/11.87 GB/2 GB VRAM laptop. | Offline interactive inquiry is not dependable. | High for tested local generators. | Keep local generation experimental; retain local extraction/embedding only where measured. | Architecture decision |
| Windows/runtime compatibility | XAMPP PHP, Ollama, Composer helpers, and provider schemas have version-specific behavior. | Environment drift can break processing or requests. | Medium. | Pin and record versions, validate model digest/schema support, and fail closed. | `BUILD_PLAN.md`, `TESTING_CHECKLIST.md` |
| Maintainability | Simple PHP components passed; extra databases/vector services were not justified. | Premature infrastructure could exceed student-team capacity. | Medium if scope expands. | Prefer explicit PHP seams and add infrastructure only from measured need. | Architecture decision |
| Security | Controls passed bounded tests but complete routes and protected links are not integrated. | Ineligible data or links could leak if checks are omitted. | Medium until integration. | Enforce live role/status/file checks at request and display time. | `SECURITY_NOTES.md`, `TESTING_CHECKLIST.md` |
| Privacy | External tests used authorized synthetic payloads only. | Real student/institutional content may be transmitted improperly. | High impact; actual likelihood depends on deployment policy. | Require explicit authorization, minimization, ZDR/terms review, and non-AI fallback. | `DATA_PRIVACY.md` |
| Lifecycle | Deterministic and rollback tests passed; persistent cleanup and late-result handling remain unimplemented. | Stale/replaced content could continue influencing AI. | Medium to high without persistent state design. | Design source-bound invalidation, cleanup, and replacement independence before integration. | Architecture/schema decision |
| Schema | Targeted persistent behavior appears necessary, but exact representation is undecided. | Poor storage design could overload `ai_outputs` or create inconsistent state. | Medium. | Make a separate explicit architecture/schema decision; do not improvise tables. | `DECISIONS.md`, `DATABASE_DESIGN.md`, `schema.sql` |
| Testing | Evidence is synthetic, small, sequential, and partly component-level. | Demonstration success may not generalize to real files or simultaneous users. | High uncertainty beyond the tested scope. | Add application-level regression, larger-corpus, real-format, and concurrency tests. | `TESTING_CHECKLIST.md` |
| Schedule | AI work can consume time needed for the core repository prototype and defense preparation. | Core functionality or presentation readiness could suffer. | Medium. | Freeze inquiry, implement only supported bounded components, and prioritize core demo reliability. | `BUILD_PLAN.md`, project handoff |

The next authorized activity is a separate architecture/schema decision preview based on this recommendation. No provider, model, storage implementation, schema change, application integration, commit, or push is authorized by this document alone.
