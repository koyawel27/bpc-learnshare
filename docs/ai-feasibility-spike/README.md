# AI Feasibility Spike — Evidence Package

**Project:** BPC LearnShare  
**Status:** Final evidence reconciliation and the D043 architecture/storage decision completed — bounded outcome remains **Partially feasible — alternative or mixed architecture required**; public AI integration and provider/model selection remain pending
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
11. a 200-case model-independent session/lifecycle checkpoint covering all ten accepted follow-up mappings, missing and cross-session context, five clearing triggers, eleven lifecycle/access change classes, final revalidation, and metadata fallback; and
12. a five-case related-resource evaluation using whole-resource embedding centroids, including 25 saved top-five rankings, manual review of all 15 top-three suggestions, 30 supplied-state eligibility revalidations, and five test-only metadata-fallback diagnostics;
13. a separate one-case safe no-result related-resource boundary control using a frozen synthetic resource, three local embeddings, five retained raw neighbors, zero displayed suggestions, and no cosine threshold;
14. a five-case metadata-guarded related-resource positive regression using the existing saved vectors, frozen relation groups, zero model/provider requests, and manual review of all 15 displayed top-three suggestions;
15. a ten-case model-independent end-user source-attribution presentation checkpoint, including independent saved-evidence audit and desktop/mobile visual review; and
16. a ten-case natural-language follow-up comparison for each of two installed local models, including 20 sequential requests and complete manual review;
17. Gate 5A model-independent safety controls and Gate 5B rollback-based live lifecycle/fallback checks;
18. Gate 5C live related-resource metadata/link validation on four controlled Approved resources;
19. Gate 5D external-candidate offline validation, harmless connectivity, guarded grounded execution, and manual quality review;
20. Gate 5E versioned summary/controlled-suggestion execution, independent audit, and approved quality review; and
21. final reconciliation of all 12 Required capabilities, tested candidates, limitations, schema impact, and downstream handoffs; and
22. an independently audited six-query live semantic-retrieval checkpoint over four controlled Approved resources using the guarded D043 backend and the registered local embedding identity.

Bounded repository-grounded answer construction, corpus-based insufficient-evidence behavior, prohibited-request behavior, and claim-to-source labeling were executed using six fixed cases per model. Both candidates completed every request, but each reached only 3/6 (50%) useful as-is or after a light edit against the accepted 80% requirement. Neither is accepted as the interactive local solution or as a reliable fallback.

The deterministic and live control checkpoints passed their bounded criteria, but complete routed application behavior, persistent derived-data cleanup, production-session follow-up, provider interruption, and larger-scale concurrency remain incomplete. Related-resource testing passed both positive and safe no-result boundaries under small controlled relation groups; this does not select a final ranking rule. Isolated attribution presentation passed, but no tested generator met strict grounding and attribution requirements.

The external Groq/GPT-OSS grounded comparison met latency and usefulness but failed claim support at 88.89% against the 95% requirement and failed exact source attribution. The later Gate 5E configuration met every accepted summary and controlled-suggestion threshold, including 100% supported summaries, 90% directly usable Active tags, 85.71% supported limited metadata, and 100% light-edit usability. That capability-specific pass does not repair the grounded-inquiry failure or select the provider/model.

The final recommendation therefore records **Partially feasible — alternative or mixed architecture required**, with Moderate confidence within tested conditions. D043 later accepted the bounded provider-neutral MariaDB/PHP persistence and retrieval direction. The audited live retrieval checkpoint supports that implementation boundary but does not select a provider/model, a no-result threshold, public semantic-search routing/UI, or generated inquiry. Generated repository inquiry remains unavailable until a future candidate passes.

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

Records the completed Section 25 evidence reconciliation, bounded outcome, capability results, candidate comparison, recommended direction, schema-impact classification, handoffs, and remaining risks. It provided the evidence input for D043; it does not itself select a provider/model or authorize application integration.

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
