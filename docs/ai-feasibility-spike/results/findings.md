# AI Feasibility Spike — Findings

**Status:** Partially executed; retrieval checkpoint registered and reviewed, later required capabilities pending
**Canonical specification:** `docs/AI_FEASIBILITY_SPIKE.md`

These findings distinguish original measurements, later reviewed interpretation, and versioned ground-truth correction. They do not constitute the final spike recommendation or an architecture decision.

## 1. Execution Scope

- Corpus: 30 registered fixtures — 25 primary-readable and 5 boundary-negative.
- Query set: 75 accepted queries.
- Expected evidence: original scored run used 149 rows; versioned evaluator ground truth now contains 150 rows.
- Corrected segmentation: 102 chunks across all 25 primary-readable fixtures.
- Embedding candidate: `EMB-OLLAMA-ALL-MINILM-001`, Ollama 0.32.1, `all-minilm:latest`, 384 dimensions.
- Similarity method: `SIM-PHP-COSINE-001`.
- Retrieval configuration: `RET-SEMANTIC-STANDALONE-001`.
- Test period covered by the registered checkpoints: 2026-07-27 through 2026-07-30.

## 2. Observed Results by Capability

### 2.1 Readable-Text Extraction

Meets the completed extraction checkpoint. `EX-LOCAL-PHP-001` passed all 9 smoke/boundary cases, extracted all 25 readable fixtures, and passed targeted fidelity and locator review. This does not by itself approve production parser integration.

### 2.2 Processing Readiness, Failure, Independence, and Staleness

Partially tested. Failed embedding input was isolated without invalidating extraction or segmentation evidence, and the corrected 102-chunk artifact was generated deterministically. Full source-status lifecycle, stale-output exclusion, replacement independence, and late-result handling remain pending.

### 2.3 Summaries and Controlled Tag/Metadata Suggestions

Not yet executed.

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

Not yet executed. Retrieval feasibility does not prove grounded answer generation, citation presentation, or answer-level faithfulness.

### 2.7 Source Attribution and Locator Reliability

Extraction locator preservation passed the completed fidelity review. End-user attribution display and answer-level locator behavior remain untested because grounded generation has not started.

### 2.8 Insufficient-Evidence Behavior

Five unsupported-query score distributions were recorded, but their scores overlapped positive-query scores. No safe no-result threshold was selected. Llama 3.2 3B passed one synthetic insufficient-evidence case, but repository-grounded answer-level behavior remains pending.

### 2.9 Session-Scoped Follow-Up

Not yet executed.

### 2.10 Related-Resource Suggestions

Not yet executed.

### 2.11 External Generation

Not yet executed.

### 2.12 Optional Experimental Local Generation

Two bounded synthetic preflights used Ollama 0.32.1, the same five non-project cases, `num_ctx=4096`, temperature 0, seed 42, `think=false`, a 256-token output limit, and zero automatic retries.

Qwen3 4B completed 5/5 requests but met 0/5 automated checks. Its median case latency was 72.391 seconds, 0/5 cases completed within 60 seconds, all five outputs hit the token limit, and visible internal reasoning displaced the requested user-facing answers. This tested configuration is not justified for grounded progression.

Llama 3.2 3B completed 5/5 requests and met 5/5 automated and manual quality checks. Median case latency was 14.738 seconds, p90 was 26.597 seconds, and 5/5 cases completed within 60 seconds without output-limit hits or visible reasoning exposure. It may proceed only to bounded grounded-inquiry evaluation. No final model, integration, or architecture decision is selected.

### 2.13 Non-AI Fallback

Not yet executed.

### 2.14 Security, Privacy, Eligibility, and Lifecycle

Local-only fixture processing, explicit file-type filters, ignored raw-vector storage, and boundary-fixture exclusion were verified in the completed checkpoints. Live eligibility revalidation, stale-source exclusion, replacement handling, cleanup, and graceful application fallback remain pending.

## 3. Mandatory Guardrail Results

Completed checkpoint guardrails passed for:

- accepted-corpus and boundary-fixture separation;
- deterministic corrected chunk generation;
- complete vector count, dimension, finite values, norms, and omission checks;
- PHP cosine numerical correctness;
- exact query-scope execution and explicit filter enforcement;
- preservation of quality misses and failed runs;
- versioned ground-truth correction without changing saved rankings;
- preservation of both failed Qwen and passed Llama synthetic-generation evidence;
- zero BPC resource or registered-query content transmitted during the synthetic generation preflights;
- zero final candidate, integration, schema, commit, or push decision during evidence generation.

## 4. Measurement Limitations

- The corpus is intentionally bounded to 25 readable resources and 102 chunks.
- Only one embedding model/runtime combination has completed scored retrieval.
- Application concurrency, persistent loading strategy, and complete request lifecycle were not tested.
- Unsupported-query scores overlap supported-query scores; cosine similarity alone cannot safely decide whether the repository contains enough evidence.
- Manual review changed interpretation of the automatic misleading flags but did not redefine that historical criterion.
- Synthetic local-generation preflights cover only five small non-project cases per candidate; grounded corpus-based generation, sustained hardware use, concurrency, and most lifecycle/fallback capabilities remain untested.

## 5. Open Questions

- Whether grounded answer generation can remain faithful, properly attributed, and safe for unsupported or prohibited questions.
- Whether lifecycle, freshness, and live eligibility revalidation remain simple enough for the bounded PHP/MariaDB MVP.
- Whether a hybrid metadata-semantic presentation should be tested for exact-title searches.
- What temporary or persistent retrieval-data behavior is actually necessary.
- Whether the completed and remaining evidence ultimately supports the simplest PHP/MariaDB direction.

No final candidate, architecture, provider, storage format, database upgrade, schema expansion, or application integration is selected by these findings.
