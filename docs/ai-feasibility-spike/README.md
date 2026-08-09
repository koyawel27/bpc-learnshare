# AI Feasibility Spike — Evidence Package

**Project:** BPC LearnShare  
**Status:** Partially executed — extraction, corrected segmentation, local embedding, PHP cosine, standalone retrieval, manual review, synthetic preflights, bounded local grounded-generation comparisons, model-independent grounded-response controls, and deterministic session/lifecycle controls recorded; live integration, related-resource evaluation, complete fallback, and final recommendation pending
**Canonical specification:** `docs/AI_FEASIBILITY_SPIKE.md`

## Purpose

This folder stores the reviewable, version-controlled documentation and redacted evidence for the accepted AI feasibility spike.

It does not contain production application data, final AI architecture, schema changes, or unrestricted raw corpus content.

## Current Execution Stage

The representative corpus, evaluation-query set, and versioned expected-evidence set have been prepared and reviewed.

Completed and registered checkpoints now cover:

1. readable-text extraction and locator/fidelity review;
2. deterministic corrected segmentation into 102 chunks;
3. complete local embedding of all 102 chunks;
4. native PHP cosine correctness and bounded vector-only timing;
5. standalone semantic retrieval over 55 executed queries;
6. targeted manual relevance review;
7. an independently audited versioned ground-truth evaluation;
8. two bounded synthetic local-generation preflights comparing Qwen3 4B and Llama 3.2 3B;
9. two fixed six-case repository-grounded local-generation comparisons for Llama 3.2 3B and Qwen3.5 4B;
10. a 21-case model-independent control-layer checkpoint covering deterministic policy gates, evidence validation, second revalidation, safe fallback, stale-output exclusion, non-authority, and minimal safe logging; and
11. a 200-case model-independent session/lifecycle checkpoint covering all ten accepted follow-up mappings, missing and cross-session context, five clearing triggers, eleven lifecycle/access change classes, final revalidation, and metadata fallback.

Bounded repository-grounded answer construction, corpus-based insufficient-evidence behavior, prohibited-request behavior, and claim-to-source labeling were executed using six fixed cases per model. Both candidates completed every request, but each reached only 3/6 (50%) useful as-is or after a light edit against the accepted 80% requirement. Neither is accepted as the interactive local solution or as a reliable fallback.

The deterministic session/lifecycle checkpoint passed its supplied-state control criteria, but it did not call a model or test the production PHP session or live database. Natural-language follow-up quality, end-user source-attribution display, related-resource evaluation, live integration-level lifecycle/staleness behavior, complete application fallback, any justified external-generation comparison, and the final recommendation remain incomplete. The completed checkpoints do not select a final model, provider, retrieval method, permanent storage design, database change, mixed architecture, or application integration.

## File Map

### `registers/fixtures.csv`

Records every primary-readable, boundary, limitation, and negative-input fixture together with its authorization and external-transmission rules.

### `registers/queries.csv`

Records the fixed evaluation questions, search requests, follow-up turns, unsupported cases, prohibited academic requests, and metadata-filter cases.

### `registers/expected_evidence.csv`

Records evaluator ground truth: expected resources, supporting evidence, reliable source locations, and known misleading or unrelated candidates.

### `registers/candidates.csv`

Records only candidates that are deliberately selected for later testing. A named candidate remains experimental and non-binding.

### `registers/test_runs.csv`

Links each executed test to its fixture, query, expected evidence, candidate configuration, environment baseline, result, and rerun history.

### `registers/payload_manifests.csv`

Records the minimum data categories transmitted to an external provider without automatically duplicating the full request or response.

### `results/measurements.csv`

Uses long-form measurement rows so each timing, count, score, guardrail result, and reviewer judgment remains traceable.

### `results/findings.md`

Summarizes observed results after measurements exist. Do not write conclusions before evidence is recorded.

### `results/final_recommendation.md`

Uses the final-report structure required by Section 25 of `AI_FEASIBILITY_SPIKE.md`. Keep it as a template until the spike is complete.

### `redacted-evidence/`

Stores only authorized and appropriately redacted review examples. Full raw corpus files, raw extraction, raw generation, temporary representations, and unrestricted payload samples belong under the ignored local-only working area:

```text
.local/ai-feasibility-spike/
```

## ID Conventions

Use stable IDs and do not recycle them.

Suggested patterns:

- Fixture: `FX-PDF-001`, `FX-DOCX-001`, `FX-PPTX-001`, `FX-TXT-001`, `FX-NEG-001`
- Source version: `SV-<fixture>-001`
- Query: `Q-META-001`, `Q-SEM-001`, `Q-INQ-001`, `Q-FOLLOW-001`, `Q-NOEVID-001`, `Q-PROHIB-001`
- Expected evidence: `EE-001`
- Candidate configuration: `C-EXT-001`, `C-EMB-001`, `C-RET-001`, `C-LOCALGEN-001`
- Test run: `TR-YYYYMMDD-001`
- Payload manifest: `PM-001`
- Measurement record: `M-001`

Equivalent clear conventions are acceptable when used consistently.

## Data-Handling Rules

- Prefer project-created, public-domain, openly licensed, explicitly authorized, or synthetic academic fixtures.
- Repository presence alone does not authorize external transmission.
- Record local-test and external-transmission permission separately.
- Do not commit API keys, credentials, session data, unrestricted raw provider payloads, or unauthorized full source content.
- Keep primary readable corpus fixtures separate from scanned/image-only, empty, corrupt, unreadable, or other boundary and negative fixtures.
- Do not use real production accounts or production data.
- Do not add tables, columns, providers, models, or architecture decisions through these registers.

## Review Gate Before Scored Testing

Before scored execution, confirm that:

- the primary corpus covers PDF, DOCX, PPTX, and TXT;
- each required format has enough representative readable fixtures;
- authorization fields are complete;
- the evaluation-query set includes all required categories;
- expected evidence was manually checked before testing;
- the accepted criteria snapshot remains consistent with Section 23 of `AI_FEASIBILITY_SPIKE.md`.
