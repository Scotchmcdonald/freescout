# Test Suite Strategy

This project now enforces outbound HTTP isolation in the shared Laravel test base. Any test that exercises external integrations must use `Http::fake()` or explicitly opt out with a narrowly scoped exception.

Use the testing pyramid intentionally:

- `Tests\PureUnitTestCase` for domain logic that does not need the Laravel container, facades, or the database.
- `Tests\UnitTestCase` for Laravel-aware unit tests that still need the application container.
- `Tests\IntegrationTestCase` for database-backed, cross-service, or cross-module interactions.
- Feature and browser tests only for user-visible flows and wiring.

Keep signal high:

- Do not add tests whose only assertion is that code did not throw.
- Do not test Eloquent relationship existence, trait presence, or source-file strings unless the behavior cannot be expressed another way.
- Prefer asserting domain outcomes, dispatched jobs/events, persisted state changes, and externally visible contracts.

Mocking rules:

- Fake all outbound HTTP.
- Fake queues, events, and buses when the test only needs to verify dispatch.
- Avoid over-mocking business services when a fast, direct assertion on real domain logic is possible.

Refactoring direction:

- Migrate pure logic out of database-backed unit tests into `Tests\PureUnitTestCase` over time.
- Collapse placeholder or no-assert files into smaller contract-focused suites.
- Keep deferred modules, including DeploymentManager, out of the current testing improvement scope until explicitly scheduled.
