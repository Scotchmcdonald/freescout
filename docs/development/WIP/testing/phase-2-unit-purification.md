# Phase 2: Unit Purification

Status: Completed (2026-03-23)
Duration: 1 to 2 weeks
Goal: Convert Unit layer into mostly pure, framework-free tests and move DB-backed tests to Integration.

## Scope

- Reclassify mislabeled Unit tests.
- Increase PureUnitTestCase usage dramatically.
- Remove DB requirements from Unit scope by design.

## Implementation Tasks

1. Taxonomy migration
- Move from tests/Unit to tests/Integration when test does any of the following:
  - factory()->create()
  - assertDatabase*
  - Eloquent relationship shape checks
  - app container resolution for integration behavior

2. Pure unit conversion
- For remaining Unit tests:
  - Use Tests/PureUnitTestCase.
  - Replace DB state with test doubles/fakes.
  - Focus on pure business logic and boundary behavior.

3. Priority migration order
- First: low-signal Unit tests (method existence, class instantiation).
- Second: model relationship and cast tests.
- Third: service/domain calculations that can become pure logic.

4. Guard alignment
- Update guard tests to:
  - fail any new Unit test extending UnitTestCase, except approved transitional directories.
  - fail any explicit RefreshDatabase usage in Unit scope.

## Acceptance Criteria

- >= 70 percent of tests/Unit files use PureUnitTestCase.
- 0 new Unit tests booting framework.
- Unit lane runtime reduced by >= 30 percent from phase 1 baseline.

## Risks

- Risk: migration churn creates temporary duplication across Unit and Integration.
- Mitigation: migrate by domain batch and remove source tests in same PR.

## Exit Gate

- Unit lane consistently under target runtime and isolation guards remain green.
