# CI/CD Architecture Compliance Scripts

This directory contains automated compliance checks that enforce architecture principles from [SYSTEM_ARCHITECTURE.md](../../docs/SYSTEM_ARCHITECTURE.md).

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

### 4. Event Inheritance (`check-event-inheritance.sh`)

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

### 5. Listener Inheritance (`check-listener-inheritance.sh`)

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

See [SYSTEM_ARCHITECTURE.md](../../docs/SYSTEM_ARCHITECTURE.md) for complete architecture documentation:

- Section 1.1: Core Blindness Pattern
- Section 1.2: Event-Driven Communication
- Section 6: Resilience Patterns (AtomicCounterService, CircuitBreaker)
- Section 7: Idempotency & Event Deduplication

## Support

For questions or issues:
1. Check [SYSTEM_ARCHITECTURE.md](../../docs/SYSTEM_ARCHITECTURE.md)
2. Review [MODULE_DEVELOPMENT_GUIDE.md](../../docs/MODULE_DEVELOPMENT_GUIDE.md)
3. See examples in existing modules (CRM, PIB, Payment)
