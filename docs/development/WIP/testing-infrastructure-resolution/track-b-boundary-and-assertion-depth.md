# Track B - Boundary Coverage And Assertion Depth

## Goal
Increase the trustworthiness of feature and integration tests by strengthening edge-case coverage and ensuring write tests assert observable outcomes, not only HTTP status codes.

## Why This Track Exists
- The audit found 29 rate-limit files and 44 auth-boundary feature files, but the newest write-endpoint tests are still shallow.
- `app/Policies` has 7 source files but only 5 corresponding test files.
- `app/Actions` has 11 source files but only 5 test files.
- Request validation coverage is thin relative to the HTTP surface area.

## Primary Deliverables
- [ ] The three shallow rate-limit tests are upgraded with side-effect assertions.
- [ ] A prioritized backlog for untested Actions, Policies, and FormRequests exists.
- [ ] At least one missing boundary test is added in each weak category.

## Tasks
### B1. Harden write-endpoint feature tests
- [ ] Review all write-oriented feature tests in:
  - `tests/Feature/Webhooks`
  - `tests/Feature/DeploymentManager`
  - `tests/Feature/Security`
- [ ] For tests that currently assert only `200`, `201`, `403`, or `429`, add one of:
  - persisted state assertions
  - dispatched event assertions
  - queued job assertions
  - notification/mail assertions
  - rate-limiter bucket assertions where applicable

### B2. Close policy gaps
- [ ] Inventory `app/Policies` versus `tests/*/Policies/*`.
- [ ] Add tests for the 2 uncovered policy classes.
- [ ] Verify both allow and deny paths, not just happy-path authorization.

### B3. Close action-layer gaps
- [ ] Inventory `app/Actions` versus `tests/*Action*Test*`.
- [ ] Add focused unit tests for the 6 untested action classes.
- [ ] Cover at least one invalid-input or edge-condition case per class.

### B4. Improve request validation coverage
- [ ] Inventory `FormRequest` classes and compare them to `tests/*/Requests/*`.
- [ ] Add tests for missing validation rules with emphasis on:
  - required fields
  - authorization failures
  - enum/value boundary checks
  - malformed payload handling

## Acceptance Markers
- [ ] New write-endpoint tests contain side-effect assertions in addition to status assertions.
- [ ] Policy suite covers every class in `app/Policies`.
- [ ] Action suite covers the highest-risk untested action classes.
- [ ] Request validation tests exist for newly identified gaps.

## Suggested Agent Assignment
- Best for an agent focused on feature/integration behavior and business-rule boundaries.
