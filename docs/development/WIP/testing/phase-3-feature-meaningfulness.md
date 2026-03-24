# Phase 3: Feature Meaningfulness

Status: In Progress (2026-03-24; shallow write baseline reduced to 0)
Duration: 1 to 2 weeks
Goal: Ensure Feature tests validate business outcomes, not only HTTP status codes.

## Scope

- Enforce assertion depth for write endpoints.
- Expand coverage of critical user journeys and negative paths.
- Reduce status-only Feature tests to a small, intentional set (smoke/canary only).

## Implementation Tasks

1. Assertion depth policy
- For POST/PUT/PATCH/DELETE Feature tests, require at least one of:
  - assertDatabaseHas/assertDatabaseMissing
  - Event::assertDispatched
  - Queue::assertPushed/Bus::assertDispatched
  - Mail::assertSent/Notification::assertSentTo
  - cache or filesystem side-effect assertion

2. Journey-first suites
- For top business flows, add multi-step tests that include auth + state transition + side effects.
- Priority domains:
  - authentication and lockout
  - conversation lifecycle
  - billing and payment critical path
  - settings changes with observable behavior

3. Negative-path coverage
- Add explicit unauthorized, validation failure, and throttling tests where missing.
- Require at least one failure-path test per critical endpoint group.

4. Refactor status-only tests
- Keep status-only tests only for:
  - health checks
  - route canaries
  - static page smoke checks

## Acceptance Criteria

- >= 85 percent of write-endpoint Feature tests include side-effect assertions.
- Status-only write tests <= 10 percent.
- Each critical journey has at least one end-to-end Feature flow test.

## Risks

- Risk: deeper assertions can increase brittleness if too tied to HTML structure.
- Mitigation: assert behavior/state and contract fields, not brittle rendered strings.

## Exit Gate

- Feature assertion-quality report passes thresholds for two consecutive CI runs.
