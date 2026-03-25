# CI/CD Architecture Compliance Scripts

This directory contains automated compliance checks that enforce architecture principles from [SYSTEM_ARCHITECTURE.md](../../docs/architecture/SYSTEM_ARCHITECTURE.md).

## Quick Start

Run all compliance checks:

```bash
bash scripts/ci/check-architecture-compliance.sh
```

**Exit Code 0** = All checks pass ✅
**Exit Code 1** = Violations detected ❌

## Individual Checks

### 1. Cross-Module Imports (`check-cross-module-imports.sh`)

**Enforces:** Core Blindness Pattern

**Rules:**
- ❌ Core code (`app/`) cannot import from feature modules (`Modules/`)
- ❌ Module listeners cannot import models from other modules
- ✅ Module listeners CAN import Events from other modules (allowed pattern)

**Example Violation:**
```php
// app/Services/BillingService.php
use Modules\PIB\Models\Invoice; // ❌ FAIL: Core imports from module
```

**Fix:**
```php
// Create interface in core
interface BillingTemplateInterface { }

// Module implements interface
class BillingTemplate implements BillingTemplateInterface { }

// Core uses interface
function process(BillingTemplateInterface $template) { }
```

### 2. Atomic Counter Operations (`check-atomic-counters.sh`)

**Enforces:** Race-condition safety for financial data

**Rules:**
- ❌ Raw `lockForUpdate()->increment()` forbidden on financial tables
- ❌ Raw `DB::table('client_credits')->increment()` forbidden
- ✅ Must use `AtomicCounterService` for all financial counter operations

**Financial Tables:**
- `client_asset_counters`
- `client_user_counters`
- `client_credits`
- `credit_ledger`

**Example Violation:**
```php
DB::table('client_credits')
    ->where('client_id', $id)
    ->increment('balance', 100); // ❌ FAIL: Race condition possible
```

**Fix:**
```php
app(AtomicCounterService::class)->increment(
    table: 'client_credits',
    where: ['client_id' => $id],
    column: 'balance',
    amount: 100
); // ✅ PASS: Atomic operation
```

### 3. Rate Limiter Usage (`check-rate-limiter-usage.sh`)

**Enforces:** API resilience patterns

**Rules:**
- ⚠️  WARNING: API services should use `RateLimiter` or `CircuitBreaker`
- Services checked: `GoogleWorkspaceService`, `Action1Service`, `HelcimService`

**Example (Warning only, non-blocking):**
```php
// HelcimService.php - consider adding:
app(CircuitBreaker::class)->call(
    key: "helcim:charge:{$amount}",
    callback: fn() => $this->httpClient->post('/charge', $data)
);
```

### 4. UI/UX Standards (`check-ui-ux-standards.sh`)

**Enforces:** Theme-agnostic UI standards from `docs/development/UX_STYLE_GUIDE.md`

**Rules:**
- ❌ Hardcoded Tailwind palette classes (e.g., `text-blue-600`, `bg-red-50`)
- ❌ Inline hardcoded color values in `style="..."` (hex/rgb/hsl)
- ✅ Semantic theme classes/tokens (`text-primary-600`, CSS variables)

**Modes:**
- `bash scripts/ci/check-ui-ux-standards.sh` scans all UI files in `resources/`, `Modules/`, `themes/`
- `bash scripts/ci/check-ui-ux-standards.sh --changed` scans only changed UI files (staged + unstaged)

**Optional exception marker:**
- Add `uiux-ignore` on a line to suppress a justified one-off exception.

### 5. Event Inheritance (`check-event-inheritance.sh`)

**Enforces:** Versioned event pattern

**Rules:**
- ✅ All events MUST extend `App\Events\VersionedEvent`
- ✅ Events MUST include `CURRENT_VERSION` constant

**Example Violation:**
```php
class InvoiceGenerated extends Event { } // ❌ FAIL
```

**Fix:**
```php
class InvoiceGenerated extends VersionedEvent {
    public const CURRENT_VERSION = 1;

    public function __construct(
        public readonly InvoiceGeneratedData $data,
        ?string $eventId = null
    ) {
        parent::__construct($data, $eventId);
    }
}
```

### 6. Listener Inheritance (`check-listener-inheritance.sh`)

**Enforces:** Idempotent event processing

**Rules:**
- ✅ All listeners MUST extend `App\Listeners\IdempotentListener`
- ✅ Guarantees exactly-once processing via `getIdempotencyKey()`

**Example Violation:**
```php
class ProcessPayment {
    public function handle(PaymentReceived $event) { }
} // ❌ FAIL: Not idempotent
```

**Fix:**
```php
class ProcessPayment extends IdempotentListener {
    protected function getIdempotencyKey($event): string {
        return "payment:process:{$event->data->paymentId}";
    }

    public function handleEvent($event): void {
        // Process payment - safe to replay
    }
}
```

### 7. Markdown Internal Links (`check-markdown-links.sh`)

**Enforces:** Documentation integrity and internal-link hygiene

**Rules:**
- ❌ Broken local Markdown links fail the check
- ✅ External links (`http`, `https`) are ignored by this script
- ✅ Report artifacts are written to `reports/markdown-link-check-<timestamp>.log`

**Run:**
```bash
bash scripts/ci/check-markdown-links.sh
```

### 8. Test Lane Runtime Budgets (`check-test-lane-runtime-budgets.php`)

**Enforces:** Phase 5 lane runtime SLO guardrails

**Lane budgets:**
- `guards` <= 30s
- `unit` <= 30s
- `feature` <= 90s
- `integration` <= 90s
- `architecture` <= 30s

**Behavior:**
- Appends lane durations to a JSONL history file
- Fails on severe spikes (default: > 1.5x budget)
- Fails on sustained regressions (rolling median over full window)
- Writes report artifact: `reports/lane-runtime-budget-<lane>-latest.md`

**Run:**
```bash
php scripts/ci/check-test-lane-runtime-budgets.php --lane=unit --duration=29
```

**Local lane runner:**
```bash
bash scripts/testing/run-test-lane.sh unit
bash scripts/testing/run-test-lane.sh feature
bash scripts/testing/run-test-lane.sh integration
```

**When a lane breaches budget:**
- Run the affected lane locally with `bash scripts/testing/run-test-lane.sh <lane>`.
- Read `reports/lane-runtime-budget-<lane>-latest.md` for the current duration, rolling median, and decision.
- If status is `warn`, inspect recent test additions and new external I/O before changing any thresholds.
- If status is `fail`, bisect to the slowest directory or file, fix the regression, and only then consider a budget change with written rationale.

### 9. Skip Governance (`check-skip-governance.php`)

**Enforces:** skip debt governance in test files

**Rules:**
- Tracks current `markTestSkipped(...)` baseline and lane budgets
- Blocks count increases beyond baseline
- Blocks any untracked skip entry not explicitly listed in the governance allowlist
- Requires allowlist metadata (`owner`, `issue`, `rationale`, `expires`)
- Fails expired skip metadata
- Fails stale allowlist entries that no longer exist in test code

**Report artifact:**
- `reports/skip-governance-latest.md`

**Run:**
```bash
php scripts/ci/check-skip-governance.php
```

### 10. Flaky Trend Report (`generate-flake-report.php`)

**Enforces:** non-blocking flake visibility from recent logs

**Behavior:**
- Scans recent `reports/test-results-*.log` files
- Normalizes mixed Pest/PHPUnit failure lines into stable signatures
- Aggregates recurring failures by event count and distinct log count
- Resolves likely test files from `Tests\\...` class names when possible
- Adds quarantine-aware suggestions using `tests/quarantine/flaky-quarantine-registry.json`
- Writes report artifact (default): `reports/flake-report-latest.md`

**Run:**
```bash
php scripts/ci/generate-flake-report.php \
    --lane=unit \
    --registry=tests/quarantine/flaky-quarantine-registry.json \
    --output=reports/flake-report-unit-latest.md
```

### 11. Quarantine Registry Governance (`check-quarantine-registry.php`)

**Enforces:** Wave 2 flaky quarantine ownership and expiry controls

**Registry file:**
- `tests/quarantine/flaky-quarantine-registry.json`

**Rules:**
- each registry entry must include `owner`, `issue`, `reason`, `expires`, `test_file`, `status`
- active quarantines auto-fail once `expires` is in the past
- tests tagged with quarantine markers must have a matching active registry entry
- active registry entries must point to tests carrying quarantine markers

### 12. Phase 6 Anti-Relapse Guard Tests (GitHub Actions guards lane)

**Enforces:** pre-merge test hardening controls

**Guard tests run in `.github/workflows/test-lanes.yml`:**
- `tests/Unit/ModuleUnitIsolationGuardTest.php`
- `tests/Unit/RefreshDatabaseUsageGuardTest.php`
- `tests/Unit/UnitFrameworkBootingGuardTest.php`
- `tests/Unit/FeatureWriteAssertionDepthGuardTest.php`

**Policy intent:**
- no new Unit framework-booting debt
- no status-only write Feature test debt
- no untracked skip/quarantine exceptions

**Report artifact:**
- `reports/quarantine-registry-latest.md`

**Run:**
```bash
php scripts/ci/check-quarantine-registry.php
```

## CI/CD Integration

### GitHub Actions

```yaml
name: Architecture Compliance
on: [push, pull_request]

jobs:
  compliance:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run Architecture Checks
        run: bash scripts/ci/check-architecture-compliance.sh
```

### Pre-commit Hook

```bash
# .git/hooks/pre-commit
#!/bin/bash
bash scripts/ci/check-architecture-compliance.sh
if [ $? -ne 0 ]; then
    echo "❌ Architecture compliance failed! Fix violations before committing."
    exit 1
fi
```

### GitLab CI

```yaml
# .gitlab-ci.yml
architecture_compliance:
  stage: test
  script:
    - bash scripts/ci/check-architecture-compliance.sh
```

## Exit Codes

| Code | Meaning |
|------|---------|
| 0    | ✅ All checks pass |
| 1    | ❌ Violations detected (check output for details) |

## Warnings vs Errors

- **Errors (Exit 1):** Critical violations that break architecture principles
- **Warnings (Exit 0):** Recommendations for improvement, non-blocking

For Phase 5 runtime checks specifically:
- `check-test-lane-runtime-budgets.php` exits 1 for severe or sustained regressions.
- Budget overages below fail thresholds are reported as warnings.

## Troubleshooting

### False Positives

If checks flag valid code:

1. **Markdown files:** Already excluded (`.md:` pattern)
2. **Test files:** Add to exclude pattern in relevant check script
3. **Intentional violations:** Document with comment explaining why

### Adding New Checks

1. Create `scripts/ci/check-your-rule.sh`
2. Follow existing pattern (exit 0 = pass, exit 1 = fail)
3. Add to `check-architecture-compliance.sh`
4. Document in this README

## Architecture Principles Reference

See [SYSTEM_ARCHITECTURE.md](../../docs/architecture/SYSTEM_ARCHITECTURE.md) for complete architecture documentation:

- Section 1.1: Core Blindness Pattern
- Section 1.2: Event-Driven Communication
- Section 6: Resilience Patterns (AtomicCounterService, CircuitBreaker)
- Section 7: Idempotency & Event Deduplication

## Support

For questions or issues:
1. Check [SYSTEM_ARCHITECTURE.md](../../docs/architecture/SYSTEM_ARCHITECTURE.md)
2. Review [MODULE_DEVELOPMENT_GUIDE.md](../../docs/development/MODULE_DEVELOPMENT_GUIDE.md)
3. See examples in existing modules (CRM, PIB, Payment)
