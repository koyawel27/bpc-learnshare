# AI Feasibility Spike — Representative Corpus Plan

**Project:** BPC LearnShare  
**Status:** Pre-execution corpus plan  
**Canonical specification:** `docs/AI_FEASIBILITY_SPIKE.md`  
**Target primary readable corpus:** 25 authorized resources  
**Boundary/negative fixtures:** Maintained separately and excluded from the 25-resource primary-corpus denominator

---

## 1. Purpose

This plan defines the first representative corpus for the BPC LearnShare AI feasibility spike.

The corpus is designed to support measured evaluation of:

- readable-text extraction;
- source-location preservation;
- summaries;
- controlled tag and metadata suggestions;
- local embeddings;
- bounded similarity retrieval;
- semantic search;
- repository-grounded inquiry;
- source attribution;
- insufficient-evidence behavior;
- session-scoped follow-up;
- related-resource suggestions;
- graceful fallback.

This plan does not:

- select an AI provider, model, runtime, retrieval layer, or storage architecture;
- approve a database or schema change;
- authorize use of copyrighted or personal material without a recorded basis;
- begin scored testing.

---

## 2. Corpus Design Principles

The primary corpus should:

- include readable PDF, DOCX, PPTX, and TXT resources;
- include short, medium, and longer materials;
- include headings, lists, tables, and ordinary structured academic content where practical;
- include several clearly related resources within each topic cluster;
- include clearly unrelated resources across clusters;
- include overlapping terminology with different meanings;
- include paraphrase opportunities where relevant concepts are expressed using different wording;
- use project-created, public-domain, openly licensed, explicitly authorized, or synthetic content;
- record local-testing permission and external-transmission permission separately.

Repository presence alone is not sufficient authorization for external transmission.

---

## 3. Planned Primary Corpus Distribution

| Format | Target Count |
|---|---:|
| PDF | 7 |
| DOCX | 6 |
| PPTX | 6 |
| TXT | 6 |
| **Total** | **25** |

The distribution is a practical starting plan, not a new project decision. It may be adjusted before scored execution when the final corpus still remains representative and satisfies the accepted specification.

---

## 4. Topic Clusters

### Cluster A — Database Design and SQL

Purpose:

- provide clearly related resources;
- support exact metadata queries and semantic paraphrases;
- test resource-level and passage-level retrieval;
- provide structured concepts such as keys, relationships, normalization, and joins.

| Fixture ID | Format | Planned Title | Length |
|---|---|---|---|
| FX-PDF-001 | PDF | Database Normalization Study Guide | Medium |
| FX-PDF-002 | PDF | SQL Joins and Query Design Module | Long |
| FX-DOCX-001 | DOCX | ERD and Cardinality Notes | Short |
| FX-PPTX-001 | PPTX | Database Keys and Relationships | Medium |
| FX-TXT-001 | TXT | SQL Terminology Quick Reference | Short |

Expected relationships:

- all five resources should be related at the broad database level;
- normalization should relate more strongly to ERD/cardinality than to unrelated research-method resources;
- joins should relate to keys and relationships but not necessarily to every normalization passage.

---

### Cluster B — Systems Analysis, Requirements, and Scope

Purpose:

- support requirements terminology;
- test paraphrases such as “quality attributes” versus “nonfunctional requirements”;
- support related-resource suggestions across requirements, use cases, and scope.

| Fixture ID | Format | Planned Title | Length |
|---|---|---|---|
| FX-PDF-003 | PDF | Requirements Elicitation Handout | Medium |
| FX-DOCX-002 | DOCX | Functional and Nonfunctional Requirements Reviewer | Medium |
| FX-DOCX-006 | DOCX | Information Systems Project Scope Guide | Medium |
| FX-PPTX-002 | PPTX | Use Cases and Process Models | Medium |
| FX-TXT-002 | TXT | Requirements Ambiguity Examples | Short |

Expected relationships:

- requirements elicitation, requirements classification, and ambiguity should be strongly related;
- project scope should be meaningfully related but broader;
- use cases should relate to functional requirements and process understanding.

---

### Cluster C — Web Application Security and Data Privacy

Purpose:

- test security and privacy concepts without using real credentials, incidents, or personal records;
- support eligibility, safe-output, and terminology evaluation;
- create controlled overlap around validation, sessions, access, and data minimization.

| Fixture ID | Format | Planned Title | Length |
|---|---|---|---|
| FX-PDF-004 | PDF | Web Application Security Basics Module | Long |
| FX-PDF-005 | PDF | Philippine Data Privacy Principles for Student Systems | Medium |
| FX-DOCX-003 | DOCX | Input Validation and Output Escaping Notes | Short |
| FX-PPTX-003 | PPTX | Authentication, Sessions, and Role-Based Access Control | Medium |
| FX-TXT-003 | TXT | Security and Privacy Terms Quick Reference | Short |

Expected relationships:

- authentication, sessions, and RBAC should relate strongly to the security module;
- data minimization should relate to privacy but only partially to ordinary authentication material;
- input validation and output escaping should be related to application security but not confused with research-instrument validation.

---

### Cluster D — Human-Computer Interaction and Usability

Purpose:

- support user-interface, accessibility, consistency, and evaluation concepts;
- provide overlap with the word “session” in usability testing without confusing it with authenticated web sessions;
- test related-resource quality within a distinct cluster.

| Fixture ID | Format | Planned Title | Length |
|---|---|---|---|
| FX-PDF-006 | PDF | Usability Evaluation Methods Guide | Medium |
| FX-DOCX-004 | DOCX | Heuristic Evaluation Worksheet Guide | Medium |
| FX-PPTX-004 | PPTX | UI Consistency and Accessibility | Medium |
| FX-TXT-004 | TXT | UX and Usability Terms Quick Reference | Short |
| FX-TXT-006 | TXT | Common Meaning Confusions in Information Systems | Short |

Expected relationships:

- usability evaluation and heuristic evaluation should be strongly related;
- UI consistency and accessibility should be related but not interchangeable;
- the terminology-confusion resource should intentionally mention terms such as “session,” “model,” and “validation” across domains to test ambiguity handling.

---

### Cluster E — Research Methods and Capstone Planning

Purpose:

- support research-design, validity, reliability, sampling, and capstone-planning concepts;
- create terminology overlap with security validation and system models while preserving different meanings;
- provide multi-resource questions.

| Fixture ID | Format | Planned Title | Length |
|---|---|---|---|
| FX-PDF-007 | PDF | Research Design and Sampling Module | Long |
| FX-DOCX-005 | DOCX | Quantitative and Qualitative Research Summary | Medium |
| FX-PPTX-005 | PPTX | Research Instrument Validity and Reliability | Medium |
| FX-PPTX-006 | PPTX | SDLC and Capstone Planning | Medium |
| FX-TXT-005 | TXT | Research Methods Glossary | Short |

Expected relationships:

- research design, sampling, methods, validity, and reliability should be strongly related;
- capstone planning should be partially related through methodology and project sequencing;
- research “validity” must not be confused with server-side input validation.

---

## 5. Deliberate Cross-Cluster Ambiguity

The corpus should contain controlled terminology overlap so semantic retrieval is not trivial.

| Term | Meaning A | Meaning B | Relevant Clusters |
|---|---|---|---|
| Validation | Server-side input/data validation | Research-instrument validity/validation | Security, Research |
| Session | Authenticated web session | Usability-test or research session | Security, HCI/Research |
| Model | ER model or process model | Conceptual/research model or AI model mention | Database, Systems Analysis, Research |
| Requirement | Software/system requirement | Research or project requirement | Systems Analysis, Research |
| Access | Authorization/access control | Accessibility/usability access | Security, HCI |

Evaluation queries should confirm that the retrieval path uses context rather than matching the shared word alone.

---

## 6. Length and Structure Targets

The final primary corpus should include approximately:

| Category | Target |
|---|---:|
| Short resources | 7–9 |
| Medium resources | 11–14 |
| Long resources | 4–6 |

Across the corpus, include where practical:

- ordinary headings;
- multi-level headings;
- bullet lists;
- numbered lists;
- at least several simple tables;
- at least one readable multi-column PDF;
- page-preserving PDF cases;
- slide-preserving PPTX cases;
- DOCX heading-path cases;
- TXT files with and without reliable headings.

Perfect visual-layout preservation is not required.

---

## 7. Authorization Strategy

Preferred first-spike strategy:

1. Use project-created synthetic academic fixtures for most or all of the initial corpus.
2. Use public-domain or openly licensed materials only when the license and intended use are recorded.
3. Use personal class notes or instructor-provided materials only when testing permission is clear.
4. Set external-transmission permission independently for every fixture.

Recommended default for project-created synthetic fixtures:

| Field | Initial Value |
|---|---|
| Authorization basis | Project-created synthetic academic fixture |
| Contains personal/sensitive information | No |
| Local testing allowed | Yes |
| External transmission allowed | Yes, only when intentionally approved for the selected test |
| Redistribution restriction | None beyond project documentation controls |

Do not assume every project-created file must be transmitted externally. External calls should still use only the minimum necessary evidence.

---

## 8. Separate Boundary and Negative Fixture Set

These fixtures are outside the 25-resource primary readable corpus.

Suggested initial set:

| Fixture ID | Category | Purpose |
|---|---|---|
| FX-NEG-001 | Scanned/image-only PDF | Confirm accepted no-OCR limitation |
| FX-NEG-002 | Empty TXT file | Confirm upload-validation rejection / safe component failure |
| FX-NEG-003 | Corrupt PDF | Confirm safe rejection or isolated extractor failure |
| FX-NEG-004 | Corrupt DOCX | Confirm safe rejection or isolated extractor failure |
| FX-NEG-005 | Structurally valid but non-extractable sample, when safely available | Confirm valid-but-AI-unavailable handling |

Keep invalid-upload fixtures separate from Approved-resource simulation.

Do not represent empty, corrupt, unreadable, or otherwise invalid files as ordinary Approved resources.

---

## 9. Folder Layout

Use the ignored local-only working area:

```text
.local/
└── ai-feasibility-spike/
    └── authorized-corpus/
        ├── primary-readable/
        │   ├── pdf/
        │   ├── docx/
        │   ├── pptx/
        │   └── txt/
        └── boundary-negative/
            ├── scanned-image-only/
            ├── empty/
            ├── corrupt/
            └── other/
```

Full raw files remain local unless their license and project purpose clearly permit repository inclusion.

The reviewable register remains:

```text
docs/ai-feasibility-spike/registers/fixtures.csv
```

---

## 10. Corpus Acceptance Checklist

Before scored testing:

- [ ] 25 primary readable fixtures are present or an accepted revised count within the specification’s 25–50 target is documented.
- [ ] PDF, DOCX, PPTX, and TXT are all represented.
- [ ] Each format has at least three expected usable readable examples.
- [ ] Short, medium, and long resources are represented.
- [ ] Related-resource clusters are documented.
- [ ] Clearly unrelated cross-cluster examples exist.
- [ ] Controlled ambiguity examples exist.
- [ ] Authorization basis is complete for every fixture.
- [ ] Local-testing permission is complete for every fixture.
- [ ] External-transmission permission is complete for every fixture.
- [ ] Personal/sensitive-content status is recorded.
- [ ] Boundary and negative fixtures are stored separately.
- [ ] Fixture IDs and source-version IDs are stable.
- [ ] Baseline copies are preserved.
- [ ] `fixtures.csv` matches the actual files.
- [ ] No raw secret, credential, personal record, or unauthorized content is committed.

---

## 11. Immediate Next Task

1. Create the local folder structure.
2. Review and accept this corpus plan.
3. Create or collect the first five project-created synthetic fixtures for Cluster A.
4. Record the corresponding rows in `fixtures.csv`.
5. Review those five fixtures before producing the remaining 20.

Do not begin scored extraction, embedding, retrieval, or generation testing yet.
