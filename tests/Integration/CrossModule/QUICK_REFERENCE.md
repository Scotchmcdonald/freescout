# Quick Reference: Cross-Module Integration Tests

## Run Tests

```bash
# All tests
./tests/Integration/CrossModule/run-integration-tests.sh

# Specific suite
./tests/Integration/CrossModule/run-integration-tests.sh --suite [name]

# With coverage
./tests/Integration/CrossModule/run-integration-tests.sh --coverage --parallel
```

## Available Test Suites

| Suite Name | Tests | Focus Area |
|-----------|-------|-----------|
| `crm-asset` | 11 | CRM ↔ AssetManagement |
| `quote-billing` | 10 | QuoteWizard ↔ PIB |
| `sync-modules` | 14 | GoogleAdmin/Action1 ↔ CRM/Assets |
| `payment-billing` | 12 | Payment ↔ PIB |
| `client-portal` | 13 | ClientPortal ↔ All Modules |
| `event-workflows` | 13 | Multi-module event chains |
| `data-consistency` | 15 | Data integrity validation |

## Test Groups

```bash
# By PHPUnit group
php artisan test --group=integration
php artisan test --group=cross-module
php artisan test --group=crm-asset
php artisan test --group=quote-billing
```

## Quick Checks

### Verify Module Installation
```bash
php artisan module:list
```

### Check Event Listeners
```bash
php artisan event:list | grep -i "module"
```

### Run Migrations
```bash
php artisan migrate:fresh
php artisan module:migrate
```

## Common Commands

### Single Test
```bash
php artisan test --filter=test_client_creation_emits_event
```

### Verbose Output
```bash
php artisan test tests/Integration/CrossModule --verbose
```

### Stop on Failure
```bash
./run-integration-tests.sh --stop-on-failure
```

## What Each Suite Tests

### CrmAssetIntegrationTest
- ✓ Client-asset relationships
- ✓ Event emission (ClientCreated, AssetStatusChanged)
- ✓ Asset count for billing
- ✓ Multi-tenant isolation
- ✓ Batch operations

### QuoteBillingIntegrationTest
- ✓ Quote approval → billing template
- ✓ Recurring invoice generation
- ✓ Line item preservation
- ✓ Billing cycle enforcement
- ✓ Revision handling

### SyncModuleIntegrationTest
- ✓ Google user sync → CRM users
- ✓ Chromebook sync → assets
- ✓ Action1 device sync → assets
- ✓ Conflict detection
- ✓ Multi-source handling

### PaymentBillingIntegrationTest
- ✓ Payment → invoice status
- ✓ Client credits (atomic ops)
- ✓ Partial payments
- ✓ Auto-payment
- ✓ Refunds

### ClientPortalAggregationTest
- ✓ Multi-module data aggregation
- ✓ Dynamic class checking
- ✓ Client isolation
- ✓ Dashboard performance
- ✓ Missing module handling

### EventDrivenWorkflowTest
- ✓ Multi-event workflows
- ✓ Event ordering
- ✓ Queued jobs
- ✓ Error handling
- ✓ No circular dependencies

### DataConsistencyTest
- ✓ Referential integrity
- ✓ Foreign key enforcement
- ✓ Audit trail completeness
- ✓ Timestamp consistency
- ✓ Company isolation

## Expected Results

✅ **All Pass**: 88 tests, ~18 seconds  
⚠️ **Some Skipped**: Modules not installed (normal)  
❌ **Failures**: Check module installation, migrations, event listeners

## Debug Failed Tests

1. **Run with verbose**: `--verbose` flag
2. **Check specific test**: `--filter=test_name`
3. **Review logs**: `tail -f storage/logs/laravel.log`
4. **Verify events**: `php artisan event:list`
5. **Check database**: Run migrations

## Files Created

```
tests/Integration/CrossModule/
├── CrmAssetIntegrationTest.php
├── QuoteBillingIntegrationTest.php
├── SyncModuleIntegrationTest.php
├── PaymentBillingIntegrationTest.php
├── ClientPortalAggregationTest.php
├── EventDrivenWorkflowTest.php
├── DataConsistencyTest.php
├── README.md (detailed docs)
├── QUICK_REFERENCE.md (this file)
├── IMPLEMENTATION_SUMMARY.md (overview)
└── run-integration-tests.sh (test runner)
```

## Architecture Validation

These tests validate:
- ✅ Core blindness pattern
- ✅ Event-driven communication
- ✅ Eventual consistency
- ✅ Module boundaries
- ✅ Dynamic class checking

Aligned with: `docs/SYSTEM_ARCHITECTURE.md`

## Need Help?

📖 Full docs: `tests/Integration/CrossModule/README.md`  
🏗️ Architecture: `docs/SYSTEM_ARCHITECTURE.md`  
📋 Summary: `tests/Integration/CrossModule/IMPLEMENTATION_SUMMARY.md`

---
**Version**: 1.0 | **Last Updated**: January 16, 2026
