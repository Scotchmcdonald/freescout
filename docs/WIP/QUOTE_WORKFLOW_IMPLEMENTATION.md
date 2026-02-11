# Quote Workflow Flash Message Implementation Plan

**Status**: Not Started  
**Priority**: High  
**Estimated Effort**: 4-6 hours  
**Affects**: 4 failing tests in HardwareProcurementTest

## Executive Summary

The Quote workflow in ContractManager module is missing flash message implementation, causing 4 tests to fail with timeout errors. This document provides a step-by-step implementation plan to add flash messages to Quote CRUD operations, following the pattern successfully established in the Contract workflow.

## Problem Statement

### Failing Tests
1. `HardwareProcurementTest::test_hardware_procurement_generates_immediate_invoice` - TimeoutException waiting for "Quote saved"
2. `HardwareProcurementTest::test_hardware_invoice_separate_from_recurring_billing` - TimeoutException waiting for "Quote saved"
3. `HardwareProcurementTest::test_rejected_hardware_quote_no_invoice` - NoSuchElementException: Missing @send-quote selector
4. `HardwareProcurementTest::test_multi_item_hardware_procurement_invoice` - TimeoutException waiting for "Quote saved"

### Root Causes
1. **Missing Flash Messages**: Quote controller doesn't return flash messages after save operations
2. **Missing UI Elements**: Quote views don't display flash message blocks
3. **Missing Send Quote Button**: Quote detail view lacks proper @send-quote selector
4. **Missing Confirmation Messages**: Quote sending doesn't produce "Quote sent" confirmation

## Discovery Phase

### Step 1: Locate Quote Controller
**Objective**: Find the Quote controller file in ContractManager module

**Expected Location**: 
- `Modules/ContractManager/Http/Controllers/QuoteController.php` (probable)
- Or within another ContractManager sub-module

**Search Commands**:
```bash
# Search for Quote controller class
find /var/www/html/Modules -name "*QuoteController.php" -type f

# Search for Quote model
find /var/www/html/Modules -name "Quote.php" -type f | grep Models

# Search for quote routes
grep -r "Route.*quotes" Modules/ContractManager/Routes/
grep -r "contractmanager.quotes" Modules/
```

**Expected Findings**:
- Controller class location
- Route definitions (quotes.index, quotes.create, quotes.store, quotes.show, quotes.send)
- Namespace structure

### Step 2: Locate Quote Views
**Objective**: Find all Quote-related Blade templates

**Expected Locations**:
- `Modules/ContractManager/Resources/views/quotes/`
- `Modules/ContractManager/resources/views/quotes/`

**Search Commands**:
```bash
# Find quote view directory
find /var/www/html/Modules -type d -name "quotes" | grep -i views

# List quote views
ls -la Modules/ContractManager/resources/views/quotes/
ls -la Modules/ContractManager/Resources/views/quotes/
```

**Expected Files**:
- `index.blade.php` - Quote list
- `create.blade.php` - Quote creation form ✓ (confirmed from test page objects)
- `show.blade.php` - Quote detail with actions
- `edit.blade.php` - Quote editing form (optional)

### Step 3: Review Current Implementation
**Objective**: Understand existing Quote controller methods

**Analysis Checklist**:
- [ ] Does `store()` method exist?
- [ ] Does it return a redirect?
- [ ] Does it have any flash messages currently?
- [ ] Is there a `send()` method for sending quotes to clients?
- [ ] What validation rules are applied?
- [ ] What relationships exist (Client, LineItems, etc.)?

## Implementation Plan

### Phase 1: Add Flash Messages to Quote Controller

#### Task 1.1: Update `store()` Method
**File**: `Modules/ContractManager/Http/Controllers/QuoteController.php`

**Current Pattern** (expected):
```php
public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'client_id' => 'required|exists:crm_clients,id',
        'title' => 'required|string|max:255',
        // ... other fields
    ]);

    $quote = Quote::create($validated);

    return redirect()->route('contractmanager.quotes.show', $quote);
}
```

**Updated Pattern**:
```php
public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'client_id' => 'required|exists:crm_clients,id',
        'title' => 'required|string|max:255',
        // ... other fields
    ]);

    $quote = Quote::create($validated);

    return redirect()->route('contractmanager.quotes.show', $quote)
        ->with('success', 'Quote saved');
}
```

**Test Command**:
```bash
php artisan dusk tests/Browser/Commerce/HardwareProcurementTest.php::test_hardware_procurement_generates_immediate_invoice
```

#### Task 1.2: Add `send()` Method Flash Message
**File**: `Modules/ContractManager/Http/Controllers/QuoteController.php`

**Expected Current Implementation**:
```php
public function send(Quote $quote): RedirectResponse
{
    // Update quote status to 'sent'
    $quote->update(['status' => 'sent']);
    
    // Create approval request or send email
    // ...
    
    return redirect()->route('contractmanager.quotes.show', $quote);
}
```

**Updated Implementation**:
```php
public function send(Quote $quote): RedirectResponse
{
    // Update quote status to 'sent'
    $quote->update(['status' => 'sent']);
    
    // Create approval request or send email notification
    // ApprovalRequest::create([...]);
    // Mail::to($quote->client->email)->send(new QuoteSent($quote));
    
    return redirect()->route('contractmanager.quotes.show', $quote)
        ->with('success', 'Quote sent');
}
```

#### Task 1.3: Add `update()` Method Flash Message
**File**: `Modules/ContractManager/Http/Controllers/QuoteController.php`

**Pattern**:
```php
public function update(Request $request, Quote $quote): RedirectResponse
{
    $validated = $request->validate([
        'title' => 'sometimes|string|max:255',
        // ... other fields
    ]);

    $quote->update($validated);

    return redirect()->route('contractmanager.quotes.show', $quote)
        ->with('success', 'Quote updated successfully');
}
```

#### Task 1.4: Add `destroy()` Method Flash Message
**File**: `Modules/ContractManager/Http/Controllers/QuoteController.php`

**Pattern**:
```php
public function destroy(Quote $quote): RedirectResponse
{
    $quote->delete();

    return redirect()->route('contractmanager.quotes.index')
        ->with('success', 'Quote deleted successfully');
}
```

### Phase 2: Update Quote Views with Flash Message Display

#### Task 2.1: Add Flash Message Block to Create View
**File**: `Modules/ContractManager/resources/views/quotes/create.blade.php`

**Location**: Add immediately after opening `<div>` or before `<h1>` heading

**Implementation**:
```blade
@if(session('success'))
    <div class="mb-4 rounded-md bg-green-50 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800">
                    {{ session('success') }}
                </p>
            </div>
        </div>
    </div>
@endif

@if(session('error'))
    <div class="mb-4 rounded-md bg-red-50 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-red-800">
                    {{ session('error') }}
                </p>
            </div>
        </div>
    </div>
@endif

@if($errors->any())
    <div class="mb-4 rounded-md bg-red-50 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">
                    There were errors with your submission
                </h3>
                <div class="mt-2 text-sm text-red-700">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endif
```

**Alternative**: Create a shared partial for flash messages
```blade
@include('contractmanager::partials.flash-messages')
```

#### Task 2.2: Add Flash Message Block to Show View
**File**: `Modules/ContractManager/resources/views/quotes/show.blade.php`

**Same Implementation**: Use identical flash message block as in create view

**Location**: Add after page header, before main content

#### Task 2.3: Add Flash Message Block to Edit View
**File**: `Modules/ContractManager/resources/views/quotes/edit.blade.php`

**Same Implementation**: Use identical flash message block

### Phase 3: Add Send Quote Button with Proper Dusk Selector

#### Task 3.1: Locate Send Quote Button in Show View
**File**: `Modules/ContractManager/resources/views/quotes/show.blade.php`

**Expected Current Implementation** (from storage logs):
```blade
<form action="{{ route('contractmanager.quotes.send', $quote) }}" method="POST" class="inline">
    @csrf
    <button type="submit" class="btn btn-primary">Send to Client</button>
</form>
```

**Updated Implementation**:
```blade
<form action="{{ route('contractmanager.quotes.send', $quote) }}" method="POST" class="inline">
    @csrf
    <button type="submit" dusk="send-quote" class="btn btn-primary">Send to Client</button>
</form>
```

**Key Change**: Added `dusk="send-quote"` attribute

#### Task 3.2: Verify Conditional Display Logic
**File**: `Modules/ContractManager/resources/views/quotes/show.blade.php`

**Implementation**: Ensure button only appears for 'draft' quotes
```blade
@if($quote->status === 'draft')
    <form action="{{ route('contractmanager.quotes.send', $quote) }}" method="POST" class="inline">
        @csrf
        <button type="submit" dusk="send-quote" class="btn btn-primary">Send to Client</button>
    </form>
@elseif($quote->status === 'sent')
    <span class="badge badge-info">Sent to client</span>
@endif
```

### Phase 4: Add Additional Quote Actions

#### Task 4.1: Add Approve Quote Flash Message
**File**: `Modules/ContractManager/Http/Controllers/QuoteController.php`

**Method**: `approve()`
```php
public function approve(Quote $quote): RedirectResponse
{
    $quote->update(['status' => 'approved']);
    
    // Create contract from quote if applicable
    // $contract = $this->convertQuoteToContract($quote);
    
    return redirect()->route('contractmanager.quotes.show', $quote)
        ->with('success', 'Quote approved successfully');
}
```

#### Task 4.2: Add Reject Quote Flash Message
**File**: `Modules/ContractManager/Http/Controllers/QuoteController.php`

**Method**: `reject()`
```php
public function reject(Request $request, Quote $quote): RedirectResponse
{
    $validated = $request->validate([
        'reason' => 'required|string|max:1000',
    ]);
    
    $quote->update([
        'status' => 'rejected',
        'rejection_reason' => $validated['reason'],
    ]);
    
    return redirect()->route('contractmanager.quotes.show', $quote)
        ->with('success', 'Quote rejected');
}
```

## Testing Strategy

### Unit Tests
Create controller tests for each method:
```php
// tests/Unit/Http/Controllers/QuoteControllerTest.php
public function test_store_returns_flash_message(): void
{
    $response = $this->actingAs($this->admin)
        ->post(route('contractmanager.quotes.store'), [
            'client_id' => $this->client->id,
            'title' => 'Test Quote',
        ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success', 'Quote saved');
}
```

### Browser Tests
Run affected tests individually:
```bash
# Test 1: Hardware procurement
php artisan dusk tests/Browser/Commerce/HardwareProcurementTest.php::test_hardware_procurement_generates_immediate_invoice

# Test 2: Hardware invoice separation
php artisan dusk tests/Browser/Commerce/HardwareProcurementTest.php::test_hardware_invoice_separate_from_recurring_billing

# Test 3: Rejected quote (includes @send-quote)
php artisan dusk tests/Browser/Commerce/HardwareProcurementTest.php::test_rejected_hardware_quote_no_invoice

# Test 4: Multi-item procurement
php artisan dusk tests/Browser/Commerce/HardwareProcurementTest.php::test_multi_item_hardware_procurement_invoice

# Run full suite
php artisan dusk tests/Browser/Commerce/HardwareProcurementTest.php
```

### Manual Testing Checklist
- [ ] Create new quote → See "Quote saved" message
- [ ] Send quote to client → See "Quote sent" message
- [ ] Update existing quote → See "Quote updated successfully" message
- [ ] Delete quote → See "Quote deleted successfully" message
- [ ] Approve quote → See "Quote approved successfully" message
- [ ] Reject quote → See "Quote rejected" message
- [ ] Submit invalid quote form → See validation errors displayed
- [ ] Verify @send-quote button appears on draft quotes only
- [ ] Verify @send-quote button not visible on sent/approved/rejected quotes

## Implementation Checklist

### Discovery Phase
- [ ] Locate Quote controller file
- [ ] Locate Quote views directory
- [ ] Review current controller methods
- [ ] Document existing routes
- [ ] Identify Quote model location

### Phase 1: Controller Updates
- [ ] Add flash message to `store()` method
- [ ] Add flash message to `send()` method
- [ ] Add flash message to `update()` method
- [ ] Add flash message to `destroy()` method
- [ ] Add flash message to `approve()` method
- [ ] Add flash message to `reject()` method

### Phase 2: View Updates
- [ ] Add flash message block to `create.blade.php`
- [ ] Add flash message block to `show.blade.php`
- [ ] Add flash message block to `edit.blade.php`
- [ ] Consider creating shared partial `partials/flash-messages.blade.php`

### Phase 3: Send Quote Button
- [ ] Add `dusk="send-quote"` to send button
- [ ] Verify conditional display (draft status only)
- [ ] Test button visibility in different quote states

### Phase 4: Testing
- [ ] Run individual test cases
- [ ] Run full HardwareProcurementTest suite
- [ ] Perform manual testing
- [ ] Verify no regressions in other quote tests
- [ ] Document any additional issues found

## Success Criteria

### Functional Requirements
✅ All 4 HardwareProcurementTest tests pass  
✅ Flash messages display correctly on all quote operations  
✅ @send-quote button exists and is clickable  
✅ Validation errors display properly  
✅ No breaking changes to existing quote functionality  

### Code Quality
✅ Consistent with Contract flash message pattern  
✅ Proper Tailwind CSS styling  
✅ Accessibility (ARIA labels, semantic HTML)  
✅ Clean, readable code with comments  

### Documentation
✅ This implementation document updated with actual findings  
✅ Code comments added for complex logic  
✅ Update PROMPT1_MISSING_FEATURES.md when complete  

## Risk Assessment

### Low Risk
- Adding flash messages to controller methods (non-breaking change)
- Adding dusk selectors to existing buttons (doesn't affect functionality)
- Adding flash message display blocks to views (additive change)

### Medium Risk
- Quote sending logic may be more complex than expected
- Multiple quote workflows (hardware vs service) may need different messages
- Approval workflow integration may require additional work

### Mitigation Strategies
1. **Test incrementally**: Implement one method at a time, test before moving on
2. **Reference working code**: Use Contract implementation as proven template
3. **Backup views**: Take screenshots before modifying views
4. **Version control**: Commit after each successful phase

## Dependencies

### Code Dependencies
- ContractManager module structure
- Quote model with proper relationships
- Existing route definitions
- Client and LineItem models

### Test Dependencies
- HardwareProcurementTest must remain unchanged (we're fixing the app, not the tests)
- MultiUserTestCase base class
- Browser test setup (ChromeDriver, database)

## Related Documentation
- [PROMPT1_MISSING_FEATURES.md](./PROMPT1_MISSING_FEATURES.md) - Parent tracking document
- [Contract Flash Message Implementation](../development/contract_flash_messages.md) - Reference pattern
- Test Page Objects:
  - `tests/Browser/Pages/ContractManager/QuoteCreatePage.php`
  - `tests/Browser/Pages/ContractManager/QuoteDetailPage.php`
  - `tests/Browser/Pages/ContractManager/QuoteListPage.php`

## Notes

### Key Insights from Research
1. Quote workflow already exists (confirmed by test page objects and route references)
2. Tests expect exact messages: "Quote saved" and "Quote sent"
3. @send-quote selector is critical for test 3 to pass
4. Hardware procurement creates quotes with type 'hardware'
5. Quote approval workflow exists but may need flash messages

### Questions for Code Review
1. Should quote sending trigger email notifications?
2. Do different quote types (hardware, service, software) need different flash messages?
3. Is there a Quote approval workflow that also needs flash messages?
4. Should we create a shared flash message partial for all ContractManager views?

---

**Last Updated**: February 7, 2026  
**Author**: Development Team  
**Status**: Ready for Implementation
