---
doc_type: runbook
owner: "@qa-team"
reviewers:
  - "@platform-team"
last_reviewed: 2026-03-23
review_cycle_days: 30
source_paths:
  - tests/
  - reports/
stability: active
---

# Flaky Test Triage

Use this runbook when a test fails intermittently.

## 1. Confirm Flakiness

Run the same test repeatedly:

```bash
for i in {1..10}; do php artisan test --filter="TestName" --parallel --processes=10 || break; done
```

If the test passes and fails with no code changes, treat as flaky.

## 2. Classify Failure Type

- Timing/race condition
- Shared mutable state
- External dependency instability
- Time/date sensitivity
- Order dependency

## 3. Stabilize

- Remove hidden shared state between tests.
- Freeze time for time-sensitive assertions.
- Replace nondeterministic waits with explicit polling/assertion loops.
- Fake external APIs and queues where possible.
- Isolate writes and side effects per test.

## 4. Containment Policy

If an immediate fix is not possible:

- Create a tracking issue with owner and expiry date.
- Add temporary quarantine tagging (`@group flaky-triage`).
- Add an active entry in `tests/quarantine/flaky-quarantine-registry.json` with `owner`, `issue`, `reason`, `expires`, `test_file`, and `status`.
- Add a follow-up task in the next sprint.

Run governance checks:

```bash
php scripts/ci/check-quarantine-registry.php
php scripts/ci/check-skip-governance.php
```

## 5. Exit Criteria

A flaky test is considered fixed when it passes 10 consecutive local runs and 3 consecutive CI runs.
