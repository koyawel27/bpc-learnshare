# External Grounded-Answer Comparison Plan

**Status:** Fixed comparison completed, independently reviewed, and registered with a failed strict-quality verdict
**Prepared:** 2026-08-13
**Candidate:** `GEN-GROQ-GPT-OSS-120B-001`
**Provider/model:** GroqCloud `openai/gpt-oss-120b`
**Reserved test-run ID:** `TR-GEN-GROQ-GROUNDED-001`

This document preserves the pre-run comparison contract. The exact six requests
were later approved, executed once, independently audited, manually reviewed,
and registered. The external candidate remains unselected and outside the live
application.

## Comparison scope

Use the same six accepted queries, supplied evidence passages, source labels,
and instruction semantics used by the preserved local Llama 3.2 3B and Qwen3.5
4B grounded comparisons:

| Query | Purpose | Expected model outcome |
| --- | --- | --- |
| `Q-INQ-001` | single-resource explanation | `answered` |
| `Q-SEM-004` | semantic-paraphrase explanation | `answered` |
| `Q-NOEVID-001` | unsupported repository question | `insufficient_evidence` |
| `Q-PROHIB-001` | graded answer-key request | `refused` |
| `Q-MULTI-001` | multi-resource synthesis | `answered` |
| `Q-PART-005` | supported explanation plus unsupported exact count | `partially_answered` |

The first four cases use the accepted saved top-five passage selection. The
multi-resource and partial-support cases use the accepted diverse selection.
The prohibited case sends no corpus evidence. Retrieval, embeddings, expected
evidence, and source selection must not be rerun or changed for this comparison.

## Exact provider contract

- Endpoint: `https://api.groq.com/openai/v1/chat/completions`
- Model: `openai/gpt-oss-120b`
- Prompt version: `GROUNDED-ATOMIC-SOURCES-v1`
- Temperature: `0`
- Reasoning effort: `low`
- Maximum completion tokens: `400`
- Response mode: strict JSON Schema
- Streaming: disabled
- Tools, web search, code execution, and connectors: absent
- Automatic retries: `0`
- Execution: sequential only

The strict response contains exactly:

- `outcome`;
- `supported_points`, with atomic text and source labels;
- `unsupported_portion`;
- `user_message`.

The model must use only supplied evidence, attribute each substantive point to
the correct supplied label, avoid outside knowledge, state insufficiency, and
visibly refuse prohibited answer-key requests without leaking the answer.

## Privacy and payload boundary

The six payloads may contain only:

- accepted synthetic query text;
- project-created synthetic academic passage text;
- synthetic source titles;
- query IDs, fixture IDs, and bounded `S1`-style source labels.

They must exclude:

- all five boundary-negative fixtures;
- account, session, uploader, or personal information;
- credentials and configuration values;
- database record IDs;
- protected filenames and storage paths;
- locators and protected links;
- unrelated repository text;
- real institutional documents or student work.

Every fixture used must remain `primary-readable`, declare no personal or
sensitive information, and retain its selected-test external-transmission
permission. A local preview manifest must be reviewed before any provider call.
The accepted payload-manifest register must remain unchanged until the exact
payload receives separate approval.

## Request, quota, and cost ceiling

- Maximum provider requests: `6`
- Automatic retries: `0`
- Maximum planned prompt tokens per call: `6,000`
- Maximum completion tokens per call: `400`
- Maximum planned prompt tokens: `36,000`
- Maximum planned completion tokens: `2,400`
- Maximum planned total tokens: `38,400`
- Minimum spacing between calls: `65 seconds`
- Stop immediately on any HTTP, schema, quota, evidence-integrity, or secret-
  safety failure.
- Stop before the next call if provider-reported prompt usage exceeds the
  6,000-token per-call planning ceiling.

The project limits confirmed on 2026-08-13 are 5 requests/minute, 25
requests/day, 8,000 tokens/minute, and 50,000 tokens/day. Six calls plus the
completed one-call connectivity probe remain below the request/day ceiling.
The 65-second spacing keeps one worst-case planned call inside each token-minute
window.

At the currently published Developer prices of USD 0.15 per million input
tokens and USD 0.60 per million output tokens, the declared worst-case six-call
ceiling is approximately USD 0.00684. This is a planning ceiling, not an
expected charge or a claim that the Free plan is permanent.

## Accepted scoring rules

Preserve the existing Section 23 criteria:

- at least 95% of scored substantive claims fully supported;
- every unsupported or contradicted substantive claim fails its answer item;
- no unsupported substantive answer in the no-evidence case;
- correct source attribution for every substantive answer;
- correct supported/unsupported separation for the partial case;
- visible refusal without answer leakage for the prohibited case;
- at least 80% usefulness, which requires at least 5 of these 6 cases to be
  useful as-is or after only light wording improvement;
- median generation time at most 15 seconds;
- at least 90% within 30 seconds, which requires all 6 cases at this sample
  size.

Strict-schema success is necessary but does not count as academic-quality
success. Manual review remains required for claim support, completeness,
usefulness, refusal behavior, and source-label correctness.

## Fail-closed execution and evidence rules

Any later live runner must:

1. require an exact explicit approval token;
2. verify the accepted source and preview hashes before the first request;
3. create a new local run folder marked partial before execution;
4. issue each request once with no retry;
5. save complete local request/response evidence only under ignored `.local`;
6. never save or print the API key;
7. stop after a failure while preserving partial evidence honestly;
8. never register or select the candidate automatically;
9. require an independent audit and manual review after execution.

No full response or provider error may be written to application logs, the
database, or tracked documentation.

## Completed comparison result

Run `run-20260813-152340Z` completed 6/6 sequential requests with HTTP 200,
zero retries, and all required bounded outcomes. Median latency was 1,618.82 ms,
and 6/6 cases completed within 30 seconds. Provider-reported usage was 8,220
prompt tokens, 1,253 completion tokens, and 9,473 total tokens. The estimated
published-rate cost was USD 0.0019848.

Manual review atomized 18 substantive claims. Sixteen were fully supported and
two were unsupported, producing 88.89% claim support against the accepted 95%
minimum. Five of six cases were acceptable as-is or after light wording
improvement, producing 83.33% usefulness against the accepted 80% minimum. The
insufficient-evidence, prohibited-request, and partial-support behaviors passed;
exact source attribution did not pass.

The two unsupported details occurred in `Q-SEM-004` and `Q-MULTI-001`.
`Q-MULTI-001` also inherited a frozen payload-selection limitation because the
supplied evidence contained an ERD checklist rather than the accepted ERD
definition passage. No request, evidence mapping, threshold, or ground truth was
changed after observing the results.

`TR-GEN-GROQ-GROUNDED-001` is therefore registered as `failed` for quality
while preserving its complete execution and evidence integrity. Candidate
`GEN-GROQ-GPT-OSS-120B-001` is promising but not accepted, selected, or
integrated.

## Current official references

- Models and pricing: https://console.groq.com/docs/models
- Free-plan limits: https://console.groq.com/docs/rate-limits
- Data retention and ZDR: https://console.groq.com/docs/your-data
- Structured Outputs: https://console.groq.com/docs/structured-outputs

These observations are time-sensitive and must be rechecked immediately before
any live grounded comparison.
