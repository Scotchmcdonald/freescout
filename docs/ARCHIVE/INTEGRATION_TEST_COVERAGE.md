# Integration Test Coverage Documentation

> Last Updated: January 16, 2026

## Overview

This document provides comprehensive documentation of the integration test suite, covering what is currently tested, the rationale behind each test area, and recommendations for future test coverage expansion.

## Test Suite Summary

| Category | Test Files | Tests | Assertions | Status |
|----------|-----------|-------|------------|--------|
| Cross-Module Integration | 6 | 58 | 133 | ✅ 100% Pass |
| Service Integration | 6 | 72 | 133 | ✅ 100% Pass |
| Job Integration | 2 | 29 | 56 | ✅ 100% Pass |
| **Total** | **14** | **159** | **322** | ✅ **100% Pass** |

## Running the Tests

```bash
# Run all integration tests
php artisan test tests/Integration/ --group=integration

# Run cross-module tests only
php artisan test tests/Integration/CrossModule/ --group=integration

# Run service tests only
php artisan test tests/Integration/Services/ --group=integration

# Run job tests only
php artisan test tests/Integration/Jobs/ --group=integration

# Run specific test file
php artisan test tests/Integration/Services/AtomicCounterServiceTest.php --group=integration
```

---

## Part 1: Cross-Module Integration Tests

Located in: `tests/Integration/CrossModule/`

### 1.1 BasicCrmTest (8 tests)

**Purpose:** Validates core CRM module functionality that other modules depend on.

| Test | Description | Validates |
|------|-------------|-----------|
| `test_can_create_company` | Company creation | Database persistence |
| `test_can_create_client` | Client creation | Factory functionality |
| `test_client_belongs_to_company` | Client-Company relationship | Foreign key integrity |
| `test_client_creation_dispatches_event` | Event dispatching | Event system |
| `test_company_has_multiple_clients` | One-to-many relationship | Relationship loading |
| `test_client_can_be_deleted` | Soft/hard delete | Deletion behavior |
| `test_company_data_isolation` | Multi-tenant isolation | Security boundary |
| `test_timestamps_are_set_correctly` | Eloquent timestamps | Model boot |

**Dependencies Tested:**
- `Modules\Crm\Models\Company`
- `Modules\Crm\Models\Client`
- `App\Events\Crm\ClientCreated`

---

### 1.2 CrmAssetIntegrationTest (9 tests)

**Purpose:** Tests the integration between CRM and Asset Management modules.

| Test | Description | Validates |
|------|-------------|-----------|
| `test_client_creation_emits_event_with_dto` | Event DTO structure | Event payload integrity |
| `test_asset_belongs_to_client` | Asset-Client relationship | Cross-module FK |
| `test_asset_status_change_emits_event` | Asset lifecycle events | Event dispatching |
| `test_asset_count_tracking` | Asset counting per client | Counting logic |
| `test_client_status_change_emits_event` | Client status events | Status transitions |
| `test_client_update_emits_event` | Update event dispatching | Eloquent observers |
| `test_asset_client_isolation` | Client data boundaries | Security |
| `test_asset_company_isolation` | Company data boundaries | Multi-tenancy |
| `test_asset_factory_creates_valid_records` | Factory validation | Test infrastructure |

**Cross-Module Dependencies:**
- CRM → AssetManagement (Client owns Assets)
- Events bridge both modules

---

### 1.3 DataConsistencyTest (12 tests)

**Purpose:** Ensures data integrity and referential consistency across all modules.

| Test | Description | Validates |
|------|-------------|-----------|
| `test_client_belongs_to_company` | Company→Client FK | Referential integrity |
| `test_contact_belongs_to_client` | Client→Contact FK | Nested relationships |
| `test_asset_belongs_to_client` | Client→Asset FK | Cross-module FK |
| `test_invoice_belongs_to_client` | Client→Invoice FK | Billing relationship |
| `test_payment_method_belongs_to_company` | Company→PaymentMethod | Payment scope |
| `test_company_client_asset_chain` | Full relationship chain | Eager loading |
| `test_company_client_invoice_chain` | Billing chain | Invoice hierarchy |
| `test_company_data_isolation` | Multi-tenant queries | Security |
| `test_client_data_isolation` | Client-level scope | Data boundaries |
| `test_company_id_consistency` | Cascading company_id | FK propagation |
| `test_company_scope_query` | Query scoping | Scope enforcement |
| `test_transaction_rollback_consistency` | Transaction handling | ACID compliance |
| `test_client_aggregate_relationships` | Complex aggregates | Performance |

**Critical Chains Validated:**
```
Company → Client → Contact
Company → Client → Asset
Company → Client → Invoice → Payment
Company → PaymentMethod
```

---

### 1.4 EventDrivenWorkflowTest (10 tests)

**Purpose:** Validates the event-driven architecture connecting all modules.

| Test | Description | Validates |
|------|-------------|-----------|
| `test_client_lifecycle_events` | Create/Update/Delete events | Full lifecycle |
| `test_asset_status_change_event` | Asset state machine | Status transitions |
| `test_event_sequence_order` | Event ordering | Temporal consistency |
| `test_client_created_event_dto_structure` | DTO integrity | Contract compliance |
| `test_client_updated_event_dto_structure` | Update DTO | Payload structure |
| `test_event_listener_can_access_related_data` | Listener data access | Event hydration |
| `test_events_not_dispatched_on_rollback` | Transaction safety | ACID events |
| `test_bulk_client_creation_dispatches_events` | Bulk operations | Event batching |
| `test_contact_created_through_client` | Nested creation events | Cascade events |
| `test_event_data_immutability` | DTO immutability | Data safety |

**Event Architecture Validated:**
- `VersionedEvent` base class with DTOs
- Event dispatching on model changes
- Transaction-safe event dispatch
- Cross-module event consumption

---

### 1.5 PaymentBillingIntegrationTest (10 tests)

**Purpose:** Tests payment processing and billing workflows across PIB and Payment modules.

| Test | Description | Validates |
|------|-------------|-----------|
| `test_invoice_factory_creates_valid_records` | Invoice creation | Factory integrity |
| `test_invoice_belongs_to_client` | Invoice-Client relationship | Billing FK |
| `test_payment_relates_to_invoice` | Payment-Invoice link | Payment tracking |
| `test_payment_method_belongs_to_company` | PaymentMethod scope | Company boundary |
| `test_company_has_multiple_payment_methods` | Multiple methods | Business logic |
| `test_invoice_status_tracking` | Invoice state machine | Status workflow |
| `test_client_has_multiple_invoices` | Client invoice history | Historical data |
| `test_invoice_amount_calculation` | Amount precision | Financial accuracy |
| `test_billing_template_factory` | Template creation | Billing config |
| `test_invoice_company_isolation` | Invoice multi-tenancy | Security |

**Billing Flow Validated:**
```
BillingTemplate → Invoice → Payment → Transaction
```

---

### 1.6 SyncModuleIntegrationTest (8 tests)

**Purpose:** Tests external sync integrations (Google Admin, Action1).

| Test | Description | Validates |
|------|-------------|-----------|
| `test_google_user_synced_event_dispatches` | Google user sync | Event emission |
| `test_google_chromebook_discovered_event_dispatches` | Chromebook discovery | Device events |
| `test_action1_device_discovered_event_dispatches` | Action1 sync | Integration events |
| `test_asset_created_from_google_source` | Google→Asset creation | Data import |
| `test_asset_created_from_action1_source` | Action1→Asset creation | Data import |
| `test_multi_source_asset_tracking` | Source tracking | Deduplication |
| `test_asset_type_breakdown` | Type categorization | Classification |
| `test_versioned_event_has_event_id` | Event versioning | Idempotency |

**External Integrations Validated:**
- Google Admin API (Users, Chromebooks)
- Action1 RMM (Devices)
- Asset source tracking and deduplication

---

## Part 2: Service Integration Tests

Located in: `tests/Integration/Services/`

### 2.1 AtomicCounterServiceTest (11 tests)

**Purpose:** Tests thread-safe counter operations critical for financial data integrity.

**Service:** `App\Services\AtomicCounterService`

| Test | Description | Validates |
|------|-------------|-----------|
| `test_increment_increases_counter` | Basic increment | Counter update |
| `test_decrement_decreases_counter` | Basic decrement | Counter update |
| `test_get_returns_current_value` | Value retrieval | Read operation |
| `test_get_returns_zero_for_nonexistent` | Default value | Edge case |
| `test_increment_by_custom_amount` | Custom increment | Flexible amounts |
| `test_increment_throws_on_negative_amount` | Validation | Input safety |
| `test_decrement_throws_on_negative_amount` | Validation | Input safety |
| `test_multiple_where_conditions` | Complex queries | Filter accuracy |
| `test_counter_isolation_between_entities` | Entity isolation | Data boundaries |
| `test_sequential_operations_maintain_consistency` | Consistency | ACID |
| `test_set_updates_counter_value` | Direct set | Override support |

**Critical For:**
- Billing counters (asset counts)
- Credit balance tracking
- Any financial counter that must not lose updates

---

### 2.2 CircuitBreakerTest (10 tests)

**Purpose:** Tests the circuit breaker pattern for external service resilience.

**Service:** `App\Services\CircuitBreaker`

| Test | Description | Validates |
|------|-------------|-----------|
| `test_successful_call_passes_through` | Normal operation | Happy path |
| `test_circuit_starts_closed` | Initial state | Default behavior |
| `test_single_failure_keeps_circuit_closed` | Fault tolerance | Threshold logic |
| `test_circuit_opens_after_threshold_failures` | Open transition | Protection trigger |
| `test_open_circuit_throws_without_calling_callback` | Fast fail | Service protection |
| `test_success_resets_failure_count` | Recovery | Self-healing |
| `test_services_have_independent_circuits` | Circuit isolation | Service independence |
| `test_callback_exceptions_propagate` | Error handling | Exception flow |
| `test_callback_return_value_passed_through` | Value passing | Transparency |
| `test_realistic_payment_scenario` | Real-world use case | Integration |

**States Validated:**
```
CLOSED (normal) → OPEN (failing) → HALF_OPEN (testing) → CLOSED
```

---

### 2.3 ClientCreditServiceTest (15 tests)

**Purpose:** Tests credit balance management with atomic operations and full audit trail.

**Service:** `Modules\PIB\Services\ClientCreditService`

| Test | Description | Validates |
|------|-------------|-----------|
| `test_can_get_credit_balance` | Balance retrieval | Read operation |
| `test_can_add_credit` | Credit addition | Write operation |
| `test_can_add_multiple_credits` | Cumulative credits | Accumulation |
| `test_can_deduct_credit` | Credit deduction | Consumption |
| `test_cannot_deduct_more_than_available` | Overdraft protection | Business rule |
| `test_ledger_is_maintained` | Audit trail | Compliance |
| `test_client_credit_isolation` | Client boundaries | Security |
| `test_atomic_credit_operations` | Concurrent safety | Race conditions |
| `test_zero_amount_rejected` | Validation | Input safety |
| `test_negative_amount_rejected` | Validation | Input safety |
| `test_credit_precision` | Financial precision | Cents accuracy |
| `test_has_sufficient_credit` | Balance check | Pre-validation |
| `test_ledger_includes_timestamp` | Timestamp audit | Traceability |
| `test_add_credit_with_reference` | Reference linking | Audit association |
| `test_balance_after_tracked` | Running balance | Ledger integrity |

**Financial Safety Validated:**
- Cents-based storage (avoids floating point errors)
- Atomic increment/decrement (prevents lost updates)
- Full ledger with balance_after for reconciliation

---

### 2.4 AssetCounterServiceTest (11 tests)

**Purpose:** Tests asset counting for billing calculations.

**Service:** `Modules\AssetManagement\Services\AssetCounterService`

| Test | Description | Validates |
|------|-------------|-----------|
| `test_counts_zero_for_new_client` | Default count | New client handling |
| `test_increment_increases_count` | Count increment | Asset addition |
| `test_decrement_decreases_count` | Count decrement | Asset removal |
| `test_counts_assets_by_type` | Type breakdown | Classification |
| `test_counts_assets_by_allocation_type` | Allocation tracking | Billing categories |
| `test_get_sum_all_allocation_types` | Total calculation | Aggregation |
| `test_client_isolation` | Client boundaries | Data safety |
| `test_initialize_creates_record` | Counter initialization | Setup flow |
| `test_initialize_idempotent` | Safe re-initialization | Idempotency |
| `test_multiple_operations_consistency` | Sequential ops | Consistency |
| `test_handles_multiple_asset_types` | Multi-type support | Flexibility |

**Billing Integration:**
- Per-asset-type counters for tiered pricing
- User-assigned vs non-allocated tracking
- Direct integration with EntitlementEngine

---

### 2.5 EntitlementEngineTest (9 tests)

**Purpose:** Tests the central billing calculation routing system.

**Service:** `App\Services\EntitlementEngine`

| Test | Description | Validates |
|------|-------------|-----------|
| `test_resolver_can_be_registered` | Resolver registration | Plugin system |
| `test_resolve_routes_to_correct_resolver` | Routing logic | Dispatcher |
| `test_throws_for_unregistered_product_type` | Error handling | Missing config |
| `test_has_resolver_returns_false_for_unregistered` | Query method | Introspection |
| `test_get_registered_product_types` | Type listing | Discovery |
| `test_resolver_can_be_replaced` | Hot replacement | Flexibility |
| `test_resolver_receives_template` | Template passing | Data flow |
| `test_entitlement_result_structure` | Result DTO | Contract |
| `test_multiple_templates_processed_correctly` | Batch processing | Throughput |

**Product Types Supported:**
- `silver_plan` - Basic managed services
- `rent_to_own` - Hardware financing
- `ad_hoc` - One-time charges

---

### 2.6 RateLimiterTest (16 tests)

**Purpose:** Tests API quota management for external service calls.

**Service:** `App\Services\RateLimiter`

| Test | Description | Validates |
|------|-------------|-----------|
| `test_executes_callback_within_limit` | Basic execution | Core functionality |
| `test_returns_callback_result` | Result passthrough | Return value handling |
| `test_increments_attempt_counter` | Counter increment | Tracking accuracy |
| `test_throws_when_limit_exceeded` | Throttle exception | Limit enforcement |
| `test_remaining_count_accurate` | Remaining calculation | Math accuracy |
| `test_can_clear_rate_limit` | Clear functionality | Reset capability |
| `test_key_isolation` | Key separation | Multi-service support |
| `test_persists_to_database` | DB persistence | Reliability across restarts |
| `test_usage_stats_calculation` | Stats generation | Dashboard support |
| `test_usage_stats_warning_color` | 70%+ warning | Visual indicators |
| `test_usage_stats_danger_color` | 90%+ danger | Alert thresholds |
| `test_multiple_services_stats` | Multi-service stats | Aggregation |
| `test_reset_expired_clears_old_records` | Cleanup routine | Maintenance |
| `test_callback_exception_propagates` | Error passthrough | Exception handling |
| `test_zero_limit_always_throttles` | Zero limit edge case | Edge case handling |
| `test_complex_key_format` | Complex keys | Key format support |

**Services Protected:**
- Google Admin API (100 req/min)
- Action1 API (60 req/min)
- Helcim Payment API (60 req/min)

---

## Part 3: Job Integration Tests

Located in: `tests/Integration/Jobs/`

### 3.1 RecurringInvoiceJobIntegrationTest (17 tests)

**Purpose:** Tests recurring invoice generation infrastructure and billing template management.

**Job:** `Modules\PIB\Jobs\GenerateRecurringInvoicesJob`

| Test | Description | Validates |
|------|-------------|-----------|
| `test_job_can_be_instantiated` | Job instantiation | Class loading |
| `test_billing_template_can_be_created` | Template creation | Data persistence |
| `test_billing_template_status_filtering` | Status queries | Active/paused filtering |
| `test_billing_template_due_date_filtering` | Due date queries | Invoice timing |
| `test_monthly_cycle_calculation` | Monthly advance | Date math |
| `test_quarterly_cycle_calculation` | Quarterly advance | Date math |
| `test_annual_cycle_calculation` | Annual advance | Date math |
| `test_invoice_can_be_created` | Invoice creation | Data persistence |
| `test_invoice_status_checks` | Status methods | Draft/pending/paid |
| `test_entitlement_engine_can_be_created` | Engine creation | Dependency injection |
| `test_entitlement_resolver_can_be_registered` | Resolver registration | Plugin system |
| `test_entitlement_result_structure` | Result DTO | Contract validation |
| `test_entitlement_result_with_goal_reached` | Rent-to-own goal | Business logic |
| `test_invoice_number_uniqueness` | Unique constraints | Data integrity |
| `test_billing_template_product_config_json` | JSON casting | Config storage |
| `test_invoice_due_date_calculation` | Due date math | 30-day terms |
| `test_combined_template_filtering` | Multi-criteria queries | Query building |

**Billing Infrastructure Tested:**
- BillingTemplate model and filtering
- Invoice model and status checks
- EntitlementEngine resolver system
- Billing cycle calculations
- JSON configuration storage

---

### 3.2 ProcessInvoicePaymentJobIntegrationTest (12 tests)

**Purpose:** Tests payment processing job and related payment infrastructure.

**Job:** `Modules\Payment\Jobs\ProcessInvoicePayment`

| Test | Description | Validates |
|------|-------------|-----------|
| `test_job_can_be_instantiated` | Job instantiation | Constructor |
| `test_job_retry_configuration` | Retry settings | tries=3, timeout=120s |
| `test_job_accepts_payment_method_id` | Parameter passing | Optional params |
| `test_job_accepts_options` | Options array | Custom config |
| `test_job_serializes_invoice` | Queue serialization | Persistence |
| `test_payment_model_scopes` | Payment scopes | forInvoice/successful |
| `test_payment_method_scopes` | PaymentMethod scopes | active/default/notExpired |
| `test_idempotency_detection` | Duplicate prevention | Safety |
| `test_payment_method_company_validation` | Company ownership | Security |
| `test_invoice_total_amount` | Amount retrieval | Data accuracy |
| `test_invoice_status_checks` | Status methods | isPaid/isDraft |
| `test_company_payment_methods_relationship` | Relationship loading | ORM validation |

**Payment Infrastructure Tested:**
- Payment model scopes (successful, failed, pending)
- PaymentMethod model scopes (active, default, notExpired)
- Idempotency checks for duplicate prevention
- Company-level payment method isolation
- Invoice amount and status handling
- Job serialization for queue processing

---

## Part 4: What Should Be Covered Next

### 4.1 High Priority - Financial Critical

| Area | Suggested Tests | Rationale |
|------|-----------------|-----------|
| **HelcimService** | Payment gateway mocking, tokenization, refunds | Payment provider integration |
| **ProcessInvoicePayment Full Flow** | End-to-end with mocked gateway | Complete payment path |
| **ProrationService** | Mid-cycle changes, credit calculations | Billing accuracy |
| **InvoiceUnusual Detection** | Amount variance thresholds | Fraud prevention |

### 4.2 High Priority - External Integrations

| Area | Suggested Tests | Rationale |
|------|-----------------|-----------|
| **SyncGoogleUsersJob** | User sync, deduplication, error recovery | Data accuracy |
| **SyncAction1DevicesJob** | Device discovery, matching, status updates | Asset accuracy |
| **Google Admin API Client** | Authentication, rate limiting, pagination | API reliability |
| **Action1 API Client** | Connection handling, data parsing | Integration stability |

### 4.3 Medium Priority - Business Logic

| Area | Suggested Tests | Rationale |
|------|-----------------|-----------|
| **QuoteWizard Service** | Quote generation, pricing rules, discounts | Sales process |
| **ClientOnboarding Flow** | Full onboarding workflow, multi-step validation | Customer journey |
| **AssetLifecycle** | Full asset state machine transitions | Asset management |
| **BillingTemplate Processing** | Template parsing, variable substitution | Billing configuration |

### 4.4 Medium Priority - Security & Access

| Area | Suggested Tests | Rationale |
|------|-----------------|-----------|
| **Multi-tenancy Enforcement** | Cross-company access prevention | Security |
| **Client Portal Access** | Portal authentication, data scoping | Customer access |
| **User Permissions** | Role-based access, permission inheritance | Authorization |
| **Impersonation** | Admin impersonation safeguards | Audit compliance |

### 4.5 Lower Priority - Supporting Services

| Area | Suggested Tests | Rationale |
|------|-----------------|-----------|
| **Email Notifications** | Invoice emails, payment receipts | Customer communication |
| **Activity Logging** | Audit trail completeness | Compliance |
| **Report Generation** | Financial reports, data exports | Business intelligence |
| **Webhook Handling** | External webhook processing | Integration reliability |

---

## Part 5: Test Implementation Guidelines

### 5.1 Test Structure Pattern

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('integration')]
#[Group('services')]
#[Group('category-name')]
class ServiceNameTest extends TestCase
{
    use RefreshDatabase;

    private ServiceClass $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create any required tables
        Schema::dropIfExists('table_name');
        Schema::create('table_name', function ($table) {
            // ...
        });
        
        $this->service = app(ServiceClass::class);
    }

    public function test_descriptive_name(): void
    {
        // Arrange
        $input = $this->createTestData();
        
        // Act
        $result = $this->service->method($input);
        
        // Assert
        $this->assertEquals($expected, $result);
    }
}
```

### 5.2 Naming Conventions

- **Test Files:** `{ServiceName}Test.php`
- **Test Methods:** `test_{action}_{expected_outcome}` or `test_{scenario}`
- **Groups:** Use `#[Group('integration')]` plus domain-specific groups

### 5.3 Database Handling

For integration tests that need specific tables:

```php
protected function setUp(): void
{
    parent::setUp();
    
    // Always drop first to handle test reruns
    Schema::dropIfExists('table_name');
    Schema::create('table_name', function ($table) {
        // Define schema
    });
}
```

### 5.4 Event Testing Pattern

```php
Event::fake([EventClass::class]);

// Trigger action
$model->performAction();

// Assert event dispatched
Event::assertDispatched(EventClass::class, function ($event) {
    return $event->dto->id === $expectedId;
});
```

---

## Part 5: Coverage Metrics

### Current Coverage by Module

| Module | Models | Services | Jobs | Events | Test Coverage |
|--------|--------|----------|------|--------|---------------|
| CRM | ✅ High | ⚠️ Medium | ❌ Low | ✅ High | 70% |
| AssetManagement | ✅ High | ✅ High | ❌ Low | ⚠️ Medium | 65% |
| PIB | ⚠️ Medium | ✅ High | ❌ Low | ⚠️ Medium | 55% |
| Payment | ⚠️ Medium | ⚠️ Medium | ❌ Low | ❌ Low | 40% |
| GoogleAdmin | ❌ Low | ❌ Low | ❌ Low | ⚠️ Medium | 25% |
| Action1 | ❌ Low | ❌ Low | ❌ Low | ⚠️ Medium | 25% |
| QuoteWizard | ❌ Low | ❌ Low | N/A | N/A | 15% |

### Recommended Next Sprint Focus

1. **GenerateRecurringInvoicesJob** - Direct revenue impact
2. **ProcessInvoicePaymentJob** - Payment reliability
3. **HelcimService** - Payment gateway critical path
4. **SyncGoogleUsersJob** - Data accuracy for billing

---

## Appendix A: File Locations

```
tests/
├── Integration/
│   ├── CrossModule/
│   │   ├── BasicCrmTest.php
│   │   ├── CrmAssetIntegrationTest.php
│   │   ├── DataConsistencyTest.php
│   │   ├── EventDrivenWorkflowTest.php
│   │   ├── PaymentBillingIntegrationTest.php
│   │   └── SyncModuleIntegrationTest.php
│   └── Services/
│       ├── AssetCounterServiceTest.php
│       ├── AtomicCounterServiceTest.php
│       ├── CircuitBreakerTest.php
│       ├── ClientCreditServiceTest.php
│       └── EntitlementEngineTest.php
├── Feature/
│   └── ... (existing feature tests)
└── Unit/
    └── ... (existing unit tests)
```

## Appendix B: Related Documentation

- [ARCHITECTURE_OVERVIEW.md](ARCHITECTURE_OVERVIEW.md) - System architecture
- [MODULE_DEVELOPMENT_GUIDE.md](MODULE_DEVELOPMENT_GUIDE.md) - Module patterns
- [INTEGRATION_TEST_REMEDIATION_PLAN.md](review%20and%20implementation/INTEGRATION_TEST_REMEDIATION_PLAN.md) - Test fixes applied
