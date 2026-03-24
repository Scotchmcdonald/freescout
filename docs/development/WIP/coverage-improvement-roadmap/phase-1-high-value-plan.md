# Services and Models Coverage Improvement Plan

Date: 2026-03-24
Status: Active
Scope: Unit-focused quality coverage for app Services and Models

## Preserved Baseline

- Snapshot folder: reports/coverage/baselines/services-models-20260324-031701
- Latest symlink: reports/coverage/baselines/services-models-latest
- Aggregate metrics: reports/coverage/baselines/services-models-latest/aggregate-baseline.json
- Impact ranking: reports/coverage/baselines/services-models-latest/top-by-impact.txt
- Raw run log: reports/coverage/baselines/services-models-latest/services-models-coverage-run.log

## Current Baseline (Services + Models)

- Overall line coverage: 5.00% (135/2699)
- Overall method coverage: 3.75% (13/347)
- Services line coverage: 1.36% (21/1539)
- Services method coverage: 3.85% (4/104)
- Models line coverage: 9.83% (114/1160)
- Models method coverage: 3.70% (9/243)

## Best-Practice Targets

- End-state target: 80%+ line coverage on high-value Services and Models units.
- Guard target: 70%+ method coverage on targeted classes.
- Quality target: add branch/guard/fallback assertions that would kill common conditional and null-handling mutants.
- Speed target: keep new tests in PureUnitTestCase unless a framework dependency is unavoidable.

## Prioritization Rule

Use score = (100 - method coverage) x executable statements.
Start from highest score methods first.

## Wave Plan

### Wave 1 (Immediate, Highest Value)

Target classes:
- app/Services/ImapService.php
- app/Services/SmtpService.php
- app/Models/Conversation.php

Target methods (first 10):
1. ImapService::processMessage
2. ImapService::fetchEmails
3. ImapService::processAttachments
4. Conversation::search
5. ImapService::findOrCreateConversation
6. SmtpService::testConnection
7. SmtpService::configureSmtp
8. ImapService::getAddressesWithNames
9. ImapService::extractSenderInfo
10. ImapService::testConnection

Expected outcome:
- Lift total services/models line coverage by 8-12 percentage points.
- Add mutation-killing tests for null handling, fallback defaults, guard clauses, and branching around parser behavior.

### Wave 2 (High Value, Moderate Complexity)

Target classes:
- app/Services/RateLimiterService.php
- app/Models/Customer.php
- app/Models/ActivityLog.php
- app/Models/Mailbox.php

Focus behaviors:
- Rate limit boundary colors and reset windows.
- Data normalization and fallback logic.
- Audit message branch selection.
- Alias parsing and empty/null edge paths.

Expected outcome:
- Add another 6-10 percentage points.
- Raise method coverage in high-risk model logic.

### Wave 3 (Breadth and Stabilization)

Target classes:
- app/Models/User.php
- app/Models/SavedSearch.php
- app/Services/ModuleSourceService.php
- app/Services/CircuitBreakerService.php

Focus behaviors:
- Permission and role guard behavior.
- Filter summary composition edge cases.
- Sample/fallback module source behavior.
- Open/half-open/closed transitions and recovery timeout decisions.

Expected outcome:
- Reach 30-40% aggregate line coverage for Services/Models while maintaining fast unit lanes.
- Establish reusable fixture builders and data-provider matrices for continued growth.

## Execution Standards

- Default to PureUnitTestCase.
- Use UnitTestCase only when container/facades/helpers are required.
- Avoid DB in tests unless behavior requires persistence semantics.
- Assert behavior and invariants, not only object construction.
- Add at least one negative-path assertion per branch-heavy method.

## Validation Per Wave

1. Run targeted tests for touched files.
2. Run unit lane in parallel:
   php artisan test tests/Unit --parallel --processes=10
3. Generate focused coverage XML for changed classes.
4. Track delta in the baseline folder with a new timestamped snapshot.

## Stop Conditions and Escalation

- If a target method cannot be tested without deep framework boot or external services, split parsing and branch logic into pure helpers first, then test helpers.
- If mutation survivors persist after two iterations, add explicit branch matrix tests for each survivor condition.
