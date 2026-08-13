# External Generation Candidate Preflight

**Status:** Synthetic connectivity validated; grounded evaluation not authorized
**Reviewed:** 2026-08-13
**Candidate ID reserved only for later review:** `GEN-GROQ-GPT-OSS-120B-001`

This checkpoint does not register, select, or integrate an external provider or
model. It identifies one bounded candidate, records one approved synthetic
connectivity probe, and leaves any small grounded evaluation subject to a
separate reviewed payload and approval.

## Candidate

- Provider: GroqCloud
- Endpoint family: OpenAI-compatible Chat Completions
- Endpoint: `https://api.groq.com/openai/v1/chat/completions`
- Model: `openai/gpt-oss-120b`
- Model status: production model in the reviewed Groq documentation
- Response contract: strict JSON Schema, no tools, no web search, no code
  execution, no streaming, one response, and zero automatic retries
- Credential: `GROQ_API_KEY` in the ignored local `.env` file only

`llama-3.3-70b-versatile` was not selected for this checkpoint because Groq's
deprecation page lists a 2026-08-16 shutdown for Free and Developer usage and
recommends `openai/gpt-oss-120b` or `qwen/qwen3.6-27b` instead.

## Why this candidate is reasonable to test

Groq currently lists `openai/gpt-oss-120b` as a production model with a
131,072-token context window and published throughput of approximately 500
tokens per second. The published Developer price is USD 0.15 per million input
tokens and USD 0.60 per million output tokens. The Free plan currently lists
30 requests per minute, 1,000 requests per day, 8,000 tokens per minute, and
200,000 tokens per day for this model.

Those figures make a tiny capstone evaluation operationally plausible. They do
not guarantee permanent free access, stable quotas, an SLA, model continuity,
or acceptable grounded-answer quality.

The model supports Groq strict Structured Outputs. This is useful because the
existing PHP control layer requires a small, machine-validated answer and
source-label contract. Strict JSON structure does not prove factual grounding,
correct source use, safe refusal, or academic usefulness; those remain scored
evaluation requirements.

## Privacy and external-transmission review

Groq's current data documentation says:

- usage metadata is always retained but does not contain customer inputs or
  outputs;
- ordinary inference inputs and outputs are not retained by default;
- inputs and outputs may be logged temporarily for service-reliability or
  suspected-abuse investigation and retained for up to 30 days;
- all customers may enable Zero Data Retention (ZDR), which disables that
  customer-data retention for inference;
- retained customer data is located in United States GCP buckets;
- batch and fine-tuning features have separate retention behavior and are not
  required or authorized for this spike.

Before any approved BPC synthetic evidence is transmitted, the project must:

1. enable and verify Groq Zero Data Retention in the selected project;
2. restrict the project to the tested model where the account controls allow;
3. use a project-specific key stored only in the ignored local `.env` file;
4. send only fixtures whose register explicitly allows intentional external
   transmission for the selected test;
5. exclude all boundary-negative fixtures, account data, private information,
   credentials, protected filenames, database identifiers, and unrelated text;
6. create and review a payload-manifest row before the grounded Apply run;
7. obtain a separate explicit approval for that exact payload and run.

The accepted fixture register currently contains 25 project-created readable
fixtures that permit external transmission only when intentionally approved
for the selected test. The five boundary-negative fixtures explicitly prohibit
external transmission. No fixture content is approved by this preflight.

## Cost boundary

The first connectivity probe must contain one harmless project-independent
sentence, use at most 128 output tokens, make one request, and perform zero
automatic retries. It should fit within the current Free plan. If billing is
enabled later, the same request would have a negligible token cost under the
reviewed published rates, but no payment or recurring subscription is approved
by this checkpoint.

Any later grounded comparison must declare its maximum requests, input tokens,
output tokens, and worst-case published cost before approval. Free-tier
availability must be rechecked immediately before execution.

## Offline validation boundary

`tests/ai/run_gate5d_external_candidate_validate.php --mode=validate` verifies
the candidate contract, fixture authorization metadata, empty payload-manifest
register, ignored credential location, and absence of a plaintext key in the
tracked preflight files. It makes no network request and reads no fixture,
query, expected-evidence, chunk, vector, or generated-answer content.

## Synthetic connectivity result

On 2026-08-13, the user manually confirmed project-level Inference API Zero
Data Retention, restricted the project to `openai/gpt-oss-120b`, and configured
conservative limits of 5 requests per minute, 25 requests per day, 8,000 tokens
per minute, and 50,000 tokens per day. A project-specific key was stored only
in the ignored local `.env` file.

After explicit approval, `tests/ai/run_gate5d_external_connectivity.php` made
exactly one request containing only the accepted project-independent probe:

> Return the status value runtime_ready. Do not include any repository or
> project content.

The provider returned HTTP 200 in 1,668.951 ms. The strict JSON response
matched the required `runtime_ready` / `EXTERNAL_RUNTIME_READY` contract. The
request used 158 prompt tokens, 59 completion tokens, and 217 total tokens.
There were zero retries. No fixture, query, expected-evidence, chunk, vector,
account, filename, or database content was read or transmitted. The checker
persisted neither the provider response nor the key.

This proves only credential, endpoint, model-family, latency, and structured-
response connectivity for one harmless probe. It does not prove grounded
academic quality, refusal behavior, citation correctness, reliability under
quota or interruption, final cost, provider suitability, or application
integration readiness. The candidate remains unregistered and unselected.

Any grounded external comparison still requires a reviewed payload-manifest
row, exact selected synthetic evidence, a declared request/token/cost ceiling,
and separate explicit approval before transmission.

## Official sources reviewed

- Groq supported models and pricing:
  https://console.groq.com/docs/models
- Groq Free-plan rate limits:
  https://console.groq.com/docs/rate-limits
- Groq customer-data controls and Zero Data Retention:
  https://console.groq.com/docs/your-data
- Groq strict Structured Outputs:
  https://console.groq.com/docs/structured-outputs
- Groq OpenAI compatibility:
  https://console.groq.com/docs/openai
- Groq model deprecations:
  https://console.groq.com/docs/deprecations
- Groq Acceptable Use and Responsible AI Policy:
  https://console.groq.com/docs/legal/ai-policy
- OpenAI GPT-OSS model card and Apache 2.0 statement:
  https://openai.com/index/gpt-oss-model-card/

These are time-sensitive observations, not permanent guarantees. They must be
rechecked before the connectivity probe and before any architecture decision.
