# Gate 5E Summary and Controlled Suggestion Payload Plan

**Status:** Offline candidate payload packet prepared and audited; live generation unauthorized
**Prepared:** 2026-08-14
**Candidate:** `GEN-GROQ-GPT-OSS-120B-001`
**Model:** `openai/gpt-oss-120b`
**Prompt template:** `SUMMARY-SUGGESTION-v1`

## Why this is the first comparison candidate

The existing Groq/GPT-OSS candidate is reused only because its connectivity, project controls, model allowlist, quota, strict Structured Outputs support, and one earlier bounded grounded run are already documented. Its earlier grounded result failed strict claim-support and exact-attribution criteria. This summary/suggestion checkpoint does not erase that failure and does not accept or select the candidate.

The payload preview is an experiment-preparation artifact. It is not an architecture decision and does not imply that external generation is preferred over a local upload-time candidate.

## Fixed execution ceiling

- Eight requests, one for each predeclared Gate 5E fixture.
- One request per fixture; no batching.
- Zero automatic retries.
- `temperature=0`.
- `reasoning_effort=low`.
- `max_completion_tokens=700` per request.
- Strict JSON Schema response.
- No tools, web access, file access, streaming, or conversation memory.
- Minimum future request spacing: 65 seconds unless current provider limits are re-reviewed and a different safe spacing is explicitly accepted.

The future live runner, if separately approved, must stop rather than silently skip a case, reuse an old output, substitute a model, loosen the schema, exceed the request ceiling, or retry automatically.

## Data included in each preview payload

- synthetic fixture ID and source-version ID;
- synthetic resource title and file type;
- the complete accepted extracted text for that one fixture;
- five Active demo tag IDs/names and two test-only Inactive tag IDs/names;
- the four Active demo subjects;
- the six Active demo resource types;
- instructions for a concise non-authoritative summary;
- instructions for controlled tags and the fixed `subject`, `resource_type`, and `topic` metadata subset.

The human reference note, expected tags, expected metadata, review judgment, and accepted thresholds are kept outside the provider payload. This prevents the model from seeing the answer key before generation.

## Data excluded

- accounts, usernames, display names, sessions, roles, and personal data;
- uploader or moderator records;
- database row IDs;
- protected filenames, server paths, and local artifact paths;
- real institutional or student content;
- the five boundary-negative fixtures;
- unrelated resources or extracted text;
- prior generated outputs and manual judgments;
- API keys, credentials, provider settings, and hidden reasoning;
- course/program and year-level suggestions;
- any instruction to publish, approve, reject, validate, moderate, or change resource status.

## Strict output contract

Each response must contain exactly:

- `summary.text` — a concise source-faithful summary of at most 120 words;
- `controlled_tag_ids` — zero to three IDs from the supplied fixture vocabulary;
- `unmapped_tag_terms` — optional descriptive terms that are not assignable controlled tags;
- `metadata.subject` — an Active supplied subject or `not_reliably_inferable`;
- `metadata.resource_type` — an Active supplied type or `not_reliably_inferable`;
- `metadata.topic` — concise source-supported descriptive text or `not_reliably_inferable`;
- a short evidence basis for each metadata decision; and
- `caveats` — zero to three brief source uncertainties or limitations.

Inactive tag IDs are present in the fixture so failure to follow availability rules can be measured. They are unavailable for new assignment. Unmapped tag terms remain quality observations only. Neither form can modify the application taxonomy.

## Review and authorization boundary

`tests/ai/run_gate5e_summary_suggestion_prepare.php --mode=validate` verifies the exact contract and computes the eight payloads without writing a packet. `--mode=apply` creates an ignored local review packet containing the exact serialized request bodies, hashes, sizes, and a manifest. Both modes make zero network requests and read no credential.

Creating the packet permits only human payload review. It does not authorize the live run. Before any external transmission, the team must recheck current provider terms, retention/ZDR, model availability, model allowlist, quotas, exact token/cost ceiling, the eight payloads, and the one-run approval phrase. A different candidate requires a separate versioned preview.

If live approval is withheld, the packet stays local and no project capability is lost. Summaries and suggestions simply remain unmeasured.

## Saved packet result

On 2026-08-14, the offline preparation validator passed 104/104 checks and created the ignored seven-file packet at:

`.local/ai-feasibility-spike/results/summary-suggestion-preview/GEN-GROQ-GPT-OSS-120B-001/payload-review-v1`

The packet contains eight exact serialized request bodies. Their combined body size is 56,918 bytes, the conservative input-token planning ceiling is 14,232, the maximum completion-token ceiling is 5,600, and the published-rate worst-case cost estimate is USD 0.0054948. These are planning ceilings, not observed live usage or cost.

`tests/ai/run_gate5e_summary_suggestion_payload_audit.php` independently passed 187/187 checks. It reconciled all seven files, five manifested content artifacts, eight request bodies, hashes, sizes, schema/settings, answer-key exclusion, eight unique accepted synthetic fixtures, data exclusions, token totals, and the explicit no-send boundary.

No network/provider request was made, no credential was read, and no accepted payload-register row was created. The next step still requires a separate live-run decision after human review and rechecking current provider controls.
