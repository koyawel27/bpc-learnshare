# AI Feasibility Spike — Findings

**Status:** Final evidence reconciliation completed; measured capability findings are recorded, while application integration and the later architecture/schema decision remain pending
**Canonical specification:** `docs/AI_FEASIBILITY_SPIKE.md`

These findings distinguish original measurements, later reviewed interpretation, and versioned ground-truth correction. The bounded conclusion is recorded separately in `final_recommendation.md`; neither document is an architecture/schema decision.

## 1. Execution Scope

- Corpus: 30 registered fixtures — 25 primary-readable and 5 boundary-negative.
- Query set: 75 accepted queries.
- Expected evidence: original scored run used 149 rows; versioned evaluator ground truth now contains 150 rows.
- Corrected segmentation: 102 chunks across all 25 primary-readable fixtures.
- Embedding candidate: `EMB-OLLAMA-ALL-MINILM-001`, Ollama 0.32.1, `all-minilm:latest`, 384 dimensions.
- Similarity method: `SIM-PHP-COSINE-001`.
- Retrieval configuration: `RET-SEMANTIC-STANDALONE-001`.
- Test period covered by the registered checkpoints: 2026-07-27 through 2026-08-14.

## 2. Observed Results by Capability

### 2.1 Readable-Text Extraction

Meets the completed extraction checkpoint. `EX-LOCAL-PHP-001` passed all 9 smoke/boundary cases, extracted all 25 readable fixtures, and passed targeted fidelity and locator review. This does not by itself approve production parser integration.

### 2.2 Processing Readiness, Failure, Independence, and Staleness

Meets with targeted changes. Failed embedding input was isolated without invalidating extraction or segmentation evidence, and the corrected 102-chunk artifact was generated deterministically. The model-independent lifecycle checkpoint and Gate 5B live rollback test excluded stale, ineligible, missing-file, invalidated, and final-revalidation-failed evidence without false success. Persistent readiness state, replacement cleanup, late-result handling, and complete application integration remain pending.

### 2.3 Summaries and Controlled Tag/Metadata Suggestions

Meets the bounded summary and controlled-suggestion criteria under `TR-GEN-GROQ-SUMSUG-001`. Eight of eight summaries were source-supported and usable; 90% of returned Active tags were directly usable; clearly tag-eligible cases had 100% coverage; 85.71% of limited metadata suggestions were supported; and all eight outputs were usable as-is or after light editing. The preserved weak Handout suggestions and broad Programming tag require human review. This capability-specific pass does not select Groq/GPT-OSS or authorize automatic metadata assignment.

### 2.4 Embeddings and Bounded Retrieval

Meets the completed technical checkpoints under the tested bounded corpus:

- `TR-SEG-CORPUS-002` produced 102 corrected chunks while preserving 100 non-parent chunks.
- `TR-EMB-FULL-002` produced 102/102 valid vectors with zero failures, omissions, malformed values, non-finite values, or dimension mismatches.
- `TR-SIM-PHP-COSINE-001` independently validated all 10,404 ordered vector-pair comparisons. Median vector-only retrieval time was 35.466250 ms and p90 was 65.072930 ms.
- `TR-RET-SEMANTIC-001` completed 55/55 query runs. Median positive isolated query-to-results latency was 106.099 ms, and all positive runs completed within five seconds.

These measurements support technical feasibility for the representative local/LAN corpus. They do not select permanent vector storage, final retrieval code, a database change, or production-scale concurrency behavior.

### 2.5 Semantic Search

Meets the resource and passage criteria after a separately audited versioned ground-truth correction:

- Expected resource in top five: 100%.
- Original expected passage in top five: 92%.
- Corrected expected passage in top five: 96%.
- Metadata fallback: 100%.
- Explicit file-type filtering: 100%.
- Original semantic value when metadata missed: 33.33%.
- Corrected semantic value after the two reviewed evidence corrections: 100%.

The original measurements remain preserved. The corrected evaluation reused the same saved vectors and rankings, made zero new embedding requests, and changed only `Q-SEM-002` and `Q-SEM-005`.

The automatic predeclared-misleading top-three rate remains 25% against the 20% maximum and is still recorded as not met. Manual review of ten targeted cases found nine useful and one partially useful result set, with zero cases confirmed genuinely misleading. The manual judgment provides interpretation but does not erase the original automatic measurement.

### 2.6 Repository-Grounded Inquiry

A bounded six-case local comparison was completed for Llama 3.2 3B and Qwen3.5 4B using the same saved retrieval evidence, response contract, settings, and zero-retry policy.

- Llama completed 6/6 requests, passed 1/6 automated checks, and reached 3/6 (50%) useful as-is or after a light edit.
- Qwen3.5 completed 6/6 requests, passed 3/6 automated checks, and reached 3/6 (50%) useful as-is.
- Both missed the accepted 80% grounded-answer usefulness criterion.

The four grounded evaluation stages are registered as failed quality verdicts even though request execution and evidence capture completed successfully. Neither candidate is selected or accepted as a reliable fallback.

### 2.7 Source Attribution and Locator Reliability

Extraction locator preservation passed the completed fidelity review. The fixed JSON claim-source contract was technically achievable but not consistently reliable in the saved local-generation comparisons: Llama omitted supported content and invented one source label, while Qwen3.5 omitted or incompletely attributed some supplied evidence. Those model-quality failures remain preserved.

A later model-independent presentation checkpoint, `TR-ATTR-END-USER-PRESENTATION-001`, passed 10/10 fixed cases using frozen saved evidence and deterministic controls. Six displayed source records reconciled to accepted fixture identity, source version, title, file type, chunk, and locator evidence. Unknown labels, source-version mismatch, and failed second-point revalidation produced safe unavailable states with zero answer or source disclosure. Correct locator omission, insufficiency, refusal, and output escaping passed. Desktop and mobile visual review passed without horizontal overflow. This proves the isolated presentation contract only; the live application route, resource links, database revalidation, and final citation UI remain untested and unselected.

### 2.8 Insufficient-Evidence Behavior

Five unsupported-query score distributions were recorded, but their scores overlapped positive-query scores, so no safe no-result threshold was selected. In the grounded comparison, both models avoided fabricating an answer for the fixed no-evidence case. Qwen3.5's automated miss was a checker false negative confirmed by manual review. The response contract already supported a visible `refused` outcome; Qwen3.5's empty prohibited response remains a model-behavior failure, not a missing contract field.

### 2.9 Session-Scoped Follow-Up

The deterministic model-independent checkpoint `TR-CTRL-SESSION-LIFECYCLE-001` tested all ten accepted follow-up mappings. Same-session parent continuity passed 10/10, missing-parent rejection passed 10/10, cross-session isolation passed 10/10, and all 50 logout, expiration, explicit-reset, session-end, and new-session clearing cases passed.

The later registered natural-language comparison executed the same ten accepted follow-up mappings for each of two local models with the parent available only as active-session context, one eligible source chunk, zero retries, and zero retrieval reruns. All 20 requests returned HTTP 200 and valid structured responses.

- `TR-FOLLOWUP-NL-LLAMA32-001`: 10/10 correct context interpretations, 8/10 grounded correct answers, and 8/10 responses useful as-is or after a light edit. It contradicted the supplied evidence once and produced one critical RBAC error that wrongly allowed a Student approval request. Median generation latency was 26.786 seconds.
- `TR-FOLLOWUP-NL-QWEN35-001`: 8/10 correct context interpretations, 8/10 grounded correct answers, and 8/10 responses useful as-is or after a light edit. It produced zero unsupported substantive answers, but unnecessarily requested clarification for two clear supported turns. Median generation latency was 37.243 seconds, and one response took 117.683 seconds.

Neither model met every-turn grounded correctness, the accepted context requirement, and the 15-second median interactive target together. Both runs are therefore registered as failed, and neither candidate is selected. This comparison tested model interpretation against supplied context only; the production PHP session and live database were not used, so integrated session behavior remains pending.

### 2.10 Related-Resource Suggestions

`TR-REL-CENTROID-COSINE-001` completed five accepted positive related-resource cases using normalized whole-resource centroids derived from the existing 102 saved `all-minilm` chunk vectors. It made zero new embedding, model, or provider requests.

- Expected related resource in the top five: 4/5 (80%), meeting the accepted 80% minimum exactly.
- Human-reviewed top-three usefulness: 11/15 (73.33%), meeting the accepted 70% minimum by one suggestion.
- Top-three judgments: 6 clearly related, 5 meaningfully related, 1 weakly related, and 3 unrelated.
- Predeclared misleading fixtures in the top three: 0.
- Distinct saved suggestions: 16 resources across 25 ranking rows; every case returned five distinct resources and excluded the starting resource.
- Deterministic ineligibility revalidation: 30/30 passed.
- Test-only metadata-cluster fallback: 5/5 diagnostics preserved eligibility.
- Ranking-only latency across the five cases: 1.0921 ms minimum, 1.5985 ms median, and 4.7467 ms maximum. These timings exclude vector loading and live application/database work.

The scored positive-case scope passed, but the result is not a final acceptance of the configuration. Four top-three suggestions were not useful enough, the usefulness pass has little margin, and the accepted register contains no intentionally empty useful-related-resource case. The no-forced-weak-suggestion criterion therefore remains unscored within this positive-case run. The separate synthetic safe no-result control below does not alter these saved rankings or judgments. Live database eligibility checks, lifecycle cleanup, UI behavior, and application integration remain pending.

### 2.10A Related-Resource Safe No-Result Boundary

`TR-REL-NO-USEFUL-BOUNDARY-001` tested one frozen synthetic Philippine Literature resource outside the accepted 30-fixture corpus. It created three valid 384-dimensional local embeddings and reused the 102 accepted saved corpus vectors without re-embedding the corpus.

- Raw semantic top-five neighbors retained for diagnosis: 5.
- Candidates remaining after the predeclared eligibility-plus-academic-relation-group guard: 0.
- Suggestions displayed: 0.
- Safe message returned: `No useful related resource is currently available.`
- Cosine threshold selected: none.
- Ranking-only latency: 2.423 ms.

The single boundary-control case passed, but it does not erase the four weak or unrelated findings from `REL-CENTROID-COSINE-001`, validate live subject/topic/tag mappings, select a final configuration, or authorize application integration. The synthetic fixture remains local payload evidence identified by `PLAN-REL-NO-USEFUL-001-v1`; it was not silently added to the accepted fixture or query registers.

`TR-REL-METADATA-GUARDED-POSITIVE-REGRESSION-001` then applied the same guard to all five accepted positive cases using the existing 102 saved vectors and frozen A-E relation groups. It made zero new embedding, model, or provider requests.

- Accepted raw expected-resource baseline reproduced: 4/5 (80%).
- Guarded expected-resource display: 5/5 (100%).
- Same-group eligible suggestions displayed: 20/20; cross-group displayed suggestions: 0.
- False safe-no-result outcomes across positive cases: 0/5.
- Human review completed: 15/15 gated top-three suggestions.
- Reviewed usefulness: 8 clearly related, 7 meaningfully related, 0 weakly related, 0 unrelated, and 0 redundant.
- Reviewed top-three usefulness: 15/15 (100%), exceeding the accepted 70% minimum.
- Predeclared misleading fixtures in the guarded top three: 0.
- Cosine threshold selected or changed: none.

The positive regression passed its bounded criteria without rewriting the accepted unguarded rankings, expected evidence, or prior manual judgments. It shows that the frozen metadata guard can preserve useful same-group suggestions in the five tested positive cases while still supporting the isolated safe no-result outcome. It does not yet prove how live subject, topic, and tag values should be mapped, how current eligibility will be revalidated inside PHP/database requests, or which final related-resource design should be integrated.

### 2.11 External Generation

The guarded GroqCloud `openai/gpt-oss-120b` comparison completed all six fixed
cases with HTTP 200, zero retries, and all required bounded outcomes. Median
latency was 1,618.82 ms, and all six cases completed within 30 seconds. Usage was
8,220 prompt tokens, 1,253 completion tokens, and 9,473 total tokens. The
estimated published-rate cost was USD 0.0019848.

Manual review found 16 of 18 substantive claims fully supported (88.89%), below
the accepted 95% threshold. Five of six cases were acceptable as-is or after
light wording improvement (83.33%), meeting the accepted 80% usefulness
threshold. Insufficient-evidence behavior, prohibited-request refusal, and
partial-support separation passed. Exact source attribution failed because
`Q-SEM-004` and `Q-MULTI-001` each contained one unsupported detail attached to
supplied evidence labels.

`Q-MULTI-001` also inherited a frozen input limitation: the comparison supplied
an ERD checklist rather than the accepted ERD definition passage. This was
preserved rather than repaired after the result. The registered run is failed on
strict quality, not execution integrity. The external candidate is the fastest
and most useful generation candidate tested so far, but it remains promising
rather than accepted or selected. Provider dependency, privacy controls, quota,
cost continuity, interruption behavior, and application fallback remain material
limitations.

#### Gate 5E summary and controlled suggestions

The first approved Gate 5E attempt stopped safely when request 1 returned HTTP
400 `unsupported_uniqueItems`. It produced zero model outputs, made zero retries,
did not send the remaining seven requests, and preserved the failed evidence. A
separately reviewed v2 removed only unsupported provider-schema keywords while
retaining runner-side uniqueness validation and all other payload and safety
boundaries.

The corrected v2 run completed 8/8 HTTP 200 requests with zero retries, a
1,944.858 ms median, and 8/8 within 15 seconds. Provider usage was 15,778 total
tokens and estimated published-rate cost was USD 0.0038472. User-approved review
found 8/8 source-supported summaries (100%), 9/10 directly usable Active tags
(90%), 6/6 clearly tag-eligible cases covered (100%), 18/21 supported metadata
suggestions (85.71%), and 8/8 outputs usable as-is or after light editing (100%).
Every accepted Gate 5E threshold passed.

The review preserved three weak Handout suggestions, one broad Programming tag,
and broad but source-related secondary tags. These support mandatory human review
before any future assignment. The result is a bounded pass for non-authoritative
summary and controlled-suggestion feasibility only. It does not repair the same
candidate's failed grounded-answer claim-support and source-attribution result,
select Groq/GPT-OSS, authorize application integration, or choose storage/schema.
### 2.12 Optional Experimental Local Generation

Two bounded synthetic preflights used Ollama 0.32.1, the same five non-project cases, `num_ctx=4096`, temperature 0, seed 42, `think=false`, a 256-token output limit, and zero automatic retries.

Qwen3 4B completed 5/5 requests but met 0/5 automated checks. Its median case latency was 72.391 seconds, 0/5 cases completed within 60 seconds, all five outputs hit the token limit, and visible internal reasoning displaced the requested user-facing answers. This tested configuration was not justified for grounded progression.

Llama 3.2 3B completed 5/5 requests and met 5/5 automated and manual quality checks. Median case latency was 14.738 seconds, p90 was 26.597 seconds, and 5/5 cases completed within 60 seconds without output-limit hits or visible reasoning exposure. It progressed to the bounded grounded comparison.

In the six-case grounded comparison, Llama's pooled conventional median was 41.6 seconds with 6/6 within 60 seconds. Qwen3.5 4B's pooled conventional median was 66.0 seconds with 2/6 within 60 seconds. Both medians fall in the documented limited-fallback timing band, but neither candidate is accepted as a reliable fallback because each achieved only 50% usefulness against the accepted 80% requirement. The samples are directional (n=6 per model), and no final model, integration, mixed architecture, or provider decision is selected.

### 2.13 Non-AI Fallback

Meets with targeted changes. Gate 5A deterministic controls, Gate 5B live rollback checks, and `TR-CTRL-SESSION-LIFECYCLE-001` preserved metadata search and protected-download behavior while suppressing AI output for disabled, unavailable, stale, missing-file, ineligible, and final-revalidation-failed states. Complete routed upload/moderation behavior, provider interruption, and application-wide fallback still require implementation testing.

### 2.14 Security, Privacy, Eligibility, and Lifecycle

Local-only fixture processing, explicit file-type filters, ignored raw-vector storage, and boundary-fixture exclusion were verified in the completed checkpoints. The deterministic grounded-response control layer rejected stale or ineligible synthetic evidence and prevented answers after second revalidation failed. The later session/lifecycle checkpoint passed all 110 mid-session ineligibility cases and all 10 final-revalidation cases with zero unsupported or ineligible carryover. Gate 5B separately passed 19/19 rollback-based live MariaDB lifecycle/fallback checks. Persistent replacement cleanup, late-result handling, and complete routed application fallback remain pending.

### 2.15 Model-Independent Grounded-Response Control Layer

`TR-CTRL-GROUNDED-MODEL-INDEPENDENT-001` passed all 21 fixed synthetic cases with zero mandatory guardrail occurrences. The checkpoint blocked prohibited-answer leakage after classification, stale or ineligible evidence use, unsupported substantive answers, fabricated source or locator behavior, post-generation staleness, invalid authority side effects, false completion, and unsafe logging while preserving metadata search and safe user-facing fallback behavior.

That run made zero real-model or provider requests, used zero registered evaluation queries, and used zero BPC resource records. It proves its tested deterministic enforcement logic only. It does not prove prohibited-request detection quality, end-user source presentation, complete application fallback, live PHP/database integration, or final architecture suitability; the separate later session/lifecycle checkpoint is recorded in Section 2.16.

### 2.16 Model-Independent Session and Lifecycle Control

`TR-CTRL-SESSION-LIFECYCLE-001` passed all 200 fixed cases: 10 ordinary same-session continuity cases, 10 missing-parent cases, 10 cross-session isolation cases, 50 context-clearing cases, 110 mid-session eligibility changes, and 10 final-revalidation changes. Ordinary continuity was 100% against the accepted 90% minimum. Mid-session evidence removal, context clearing, cross-session isolation, and metadata fallback were each 100%, with zero unsupported or ineligible carryover.

The saved evidence used aliases rather than raw registered session IDs and called no model/provider. The checkpoint did not test natural-language understanding, generated-answer quality, provider retention, the production PHP session, or live database integration. It does not authorize permanent chat history, schema changes, application integration, or a final candidate/architecture decision.

## 3. Mandatory Guardrail Results

Completed checkpoint guardrails passed for:

- accepted-corpus and boundary-fixture separation;
- deterministic corrected chunk generation;
- complete vector count, dimension, finite values, norms, and omission checks;
- PHP cosine numerical correctness;
- exact query-scope execution and explicit filter enforcement;
- preservation of quality misses and failed runs;
- versioned ground-truth correction without changing saved rankings;
- preservation of failed and passed synthetic-preflight evidence and all four quality-failed grounded-generation stages;
- 21/21 deterministic control-layer cases with zero prohibited-answer leakage, stale or ineligible evidence use, unsupported answers, invalid authority side effects, false completion, or unsafe log exposure;
- 200/200 deterministic session/lifecycle cases with 100% same-session continuity, context clearing, cross-session isolation, lifecycle exclusion, final revalidation, and metadata fallback under the supplied state transitions;
- five accepted unguarded related-resource cases with 80% expected-resource top-five coverage and 73.33% human-reviewed top-three usefulness;
- 30/30 deterministic related-resource eligibility revalidations and five distinct non-self suggestions per positive case;
- one separately versioned safe no-result boundary-control case with three valid local embeddings, five retained raw cross-group neighbors, zero displayed suggestions, a safe no-result message, and no selected cosine threshold;
- one five-case metadata-guarded positive regression with 100% expected-resource display, 15/15 clearly or meaningfully related reviewed top-three suggestions, zero false no-result outcomes, and zero new model/provider requests;
- 10/10 model-independent source-attribution presentation cases, six verified displayed source records, five fail-closed safe states, and desktop/mobile visual review without horizontal overflow;
- zero BPC resource or registered-query content transmitted during the synthetic generation preflights;
- one guarded external grounded comparison with complete execution, preserved strict-quality failure, minimized authorized payloads, zero retries, and no hidden unsupported claims;
- one preserved Gate 5E schema-compatibility failure followed by a separately approved versioned run whose summary, tag, metadata, usability, latency, and boundary criteria all passed;
- zero final candidate, integration, schema, commit, or push decision during evidence generation.

## 4. Measurement Limitations

- The corpus is intentionally bounded to 25 readable resources and 102 chunks.
- Only one embedding model/runtime combination has completed scored retrieval.
- Application concurrency, persistent loading strategy, and complete request lifecycle were not tested.
- Unsupported-query scores overlap supported-query scores; cosine similarity alone cannot safely decide whether the repository contains enough evidence.
- Manual review changed interpretation of the automatic misleading flags but did not redefine that historical criterion.
- The grounded local-generation comparison used only six fixed cases per candidate. Its results are directional; sustained hardware use, concurrency, live control-layer integration, live application citation/link behavior, and most application-level lifecycle/fallback capabilities remain untested. The later isolated presentation checkpoint does not repair those model-quality limitations.
- The session/lifecycle checkpoint supplied deterministic state changes and did not call a model. The later natural-language comparison tested model reference interpretation but still did not use the production PHP session, live database transitions, cleanup synchronization, or provider-side retention.
- The natural-language comparison used ten fixed turns per model on one laptop with sequential requests. It does not establish concurrency, sustained-load behavior, live eligibility revalidation, or final local/external generation suitability.
- The external grounded and summary/suggestion runs used one provider/model under time-sensitive limits, terms, retention controls, and network conditions. The Gate 5E pass is capability-specific and does not repair the failed grounded-inquiry result.

## 5. Open Questions

- Whether the registered deterministic grounded-response, session/lifecycle, and natural-language follow-up rules remain effective after live PHP/database integration and real request classification.
- Whether lifecycle, freshness, and live eligibility revalidation remain simple enough for the bounded PHP/MariaDB MVP.
- Whether a hybrid metadata-semantic presentation should be tested for exact-title searches.
- What temporary or persistent retrieval-data behavior is actually necessary.
- How the later architecture decision should represent the targeted persistent extraction, chunk, embedding, readiness, freshness, invalidation, and cleanup behavior without unnecessary infrastructure.
- Which replaceable provider/model, if any, should be considered only for optional reviewed summaries and suggestions.
- Which future inquiry candidate can meet grounding, attribution, usefulness, and latency together.

The reconciled outcome is **Partially feasible — alternative or mixed architecture required**, with Moderate confidence within tested conditions. No final candidate, architecture, provider, storage format, database upgrade, schema expansion, or application integration is selected by these findings.
