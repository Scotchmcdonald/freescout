# Documentation Frontmatter Template

Use this YAML block at the top of Markdown files to reduce documentation drift and clarify ownership.

```yaml
---
doc_type: reference # tutorial | how-to | reference | explanation | runbook
owner: "@team-or-handle"
reviewers:
  - "@secondary-team"
last_reviewed: 2026-03-23
review_cycle_days: 90
source_paths:
  - path/to/related/code
stability: active # active | experimental | deprecated | archived
---
```

## Field Definitions

- `doc_type`: Diataxis-aligned document category.
- `owner`: Primary team accountable for correctness.
- `reviewers`: Optional supporting reviewers.
- `last_reviewed`: Date the document was last manually validated against code.
- `review_cycle_days`: Maximum days allowed between reviews.
- `source_paths`: Key implementation paths that should trigger doc review.
- `stability`: Lifecycle state of the document.

## Review Policy

- Update `last_reviewed` whenever content is validated.
- If code changes under any `source_paths`, review the doc in the same PR.
- Prefer colocated docs for module-specific behavior and central docs for cross-cutting standards.
