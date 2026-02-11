# Rent-to-Own Invoice Generation Business Logic Implementation

**Status**: Not Started  
**Priority**: High  
**Estimated Effort**: 6-8 hours  
**Affects**: 5 failing tests in RentToOwnTest (2 business logic, 3 JavaScript errors)

## Executive Summary

The rent-to-own contract invoice generation system requires enhanced business logic to properly handle purchase price caps, irregular final payments, early buyouts, payment tracking, and ownership transfer. Currently, 5 tests fail due to missing cap validation, JavaScript errors in modals, and incomplete ownership transfer logic.

## Problem Statement

### Failing Tests

#### Business Logic Failures (2 tests)
1. **`test_rental_invoices_stop_at_purchase_cap`** - Did not see expected text [Invoice generated]
   - **Error**: Flash message "Invoice generated" not appearing
   - **Root Cause**: Invoice generation may not be working, or flash message missing

2. **`test_rent_to_own_early_buyout`** - TimeoutException waiting for "Contract saved successfully"
   - **Error**: Contract creation timeout
   - **Root Cause**: Early buyout form fields may be missing or validation failing

#### JavaScript Errors (3 tests)
3. **`test_rent_to_own_with_irregular_final_payment`** - JavascriptErrorException
4. **`test_rent_to_own_tracks_missed_payments`** - JavascriptErrorException
5. **`test_rent_to_own_ownership_transfer_on_completion`** - JavascriptErrorException
   - **Error**: `Cannot read properties of undefined (reading 'click')`
   - **Root Cause**: Modal event handlers accessing null/undefined elements (Prompt 3 territory)

### Current Implementation Status

**Partially Implemented** ✓ (lines 88-143 in ContractController.php):
```php
public function generateInvoice(Contract $contract): RedirectResponse
{
    // ✓ Handles rent_to_own contract type
    // ✓ Checks purchase price cap
    // ✓ Calculates remaining balance for final payment
    // ✗ Missing detailed flash messages for cap reached
    // ✗ No ownership transfer logic
    // ✗ No tracking of total payments
    // ✗ No early buyout support
}
```

## Business Rules Analysis

### Rule 1: Purchase Price Cap Enforcement
**Formula**: `Σ(invoice amounts) ≤ purchase_price`

**Implementation Requirements**:
- Track total invoiced amount for contract
- Block invoice generation when cap reached
- Calculate irregular final payment when remaining < monthly fee
- Display appropriate flash messages for each scenario

**Test Case**: `test_rental_invoices_stop_at_purchase_cap`
- Purchase price: $1,000
- Monthly rental fee: $50
- Expected: 20 invoices generated (20 × $50 = $1,000)
- Months 21-25: Invoice generation blocked with message "Cannot generate invoice - purchase price cap reached"

### Rule 2: Irregular Final Payment Handling
**Formula**: Final payment = `purchase_price - Σ(previous payments)`

**Implementation Requirements**:
- Detect when remaining balance < monthly rental fee
- Create invoice for exact remaining amount
- Mark invoice as "Final payment"
- Trigger ownership transfer on payment
- Add line item description: "Final payment - ownership transferred"

**Test Case**: `test_rent_to_own_with_irregular_final_payment`
- Purchase price: $1,250
- Monthly rental fee: $100
- Expected: 12 invoices @ $100, 1 invoice @ $50
- Total: 13 invoices, exact $1,250

### Rule 3: Early Buyout Option
**Formula**: Buyout amount = `purchase_price - Σ(payments_to_date)`

**Implementation Requirements**:
- Add `allow_early_buyout` boolean field to contracts table
- Display buyout option in client portal for eligible contracts
- Calculate current balance and remaining amount
- Generate special buyout invoice for remaining balance
- Update contract status to 'purchased' immediately
- Transfer ownership without waiting for full payment schedule

**Test Case**: `test_rent_to_own_early_buyout`
- Purchase price: $2,000
- Monthly rental fee: $150
- After 5 payments: $750 paid
- Buyout amount: $1,250 ($2,000 - $750)
- Expected: Generate single buyout invoice for $1,250

### Rule 4: Missed Payment Tracking
**Formula**: Track invoice status and payment dates

**Implementation Requirements**:
- Track each invoice generation date
- Monitor invoice payment status (pending, paid, overdue)
- Calculate missed payments count
- Display payment history on contract detail page
- Alert on multiple missed payments
- Potentially block future invoices if too many missed payments

**Test Case**: `test_rent_to_own_tracks_missed_payments`
- Generate 10 monthly invoices
- Mark 2 as unpaid/overdue
- Expected: Display "2 missed payments" warning
- Display payment history with status for each month

### Rule 5: Ownership Transfer on Completion
**Formula**: Transfer when `total_paid >= purchase_price`

**Implementation Requirements**:
- Add `ownership_status` field to contracts table ('renting', 'owned')
- Track `ownership_transferred_at` timestamp
- Update asset record to reflect new owner
- Generate ownership certificate/documentation
- Send notification to client
- Update contract status to 'completed'
- Display ownership badge on contract detail page

**Test Case**: `test_rent_to_own_ownership_transfer_on_completion`
- After final payment processed
- Expected: Contract shows "Status: Purchased"
- Expected: "Ownership transferred" badge visible
- Expected: Asset ownership updated in database

## Current Code Analysis

### Existing Implementation (ContractController.php lines 88-143)

**✅ What Works**:
```php
// Purchase price cap check
if ($totalInvoiced >= ($contract->purchase_price ?? 0)) {
    throw new \Exception('Cannot generate invoice - purchase price cap reached');
}

// Irregular final payment calculation
$remaining = ($contract->purchase_price ?? 0) - $totalInvoiced;
$amount = min($remaining, $amount);
```

**❌ What's Missing**:
1. **Flash Message for Invoice Generated**: Success case doesn't return proper flash message
2. **Ownership Transfer**: No logic to mark contract as purchased after final payment
3. **Invoice Description**: Final payment invoices need special description
4. **Early Buyout**: No support for buyout before schedule completion
5. **Payment Tracking**: No count or display of missed payments
6. **Status Updates**: Contract status doesn't change after cap reached

### Required Database Schema Updates

#### contracts table additions:
```php
Schema::table('cm_contracts', function (Blueprint $table) {
    $table->boolean('allow_early_buyout')->default(false)->after('monthly_rental_fee');
    $table->enum('ownership_status', ['renting', 'owned'])->default('renting')->after('status');
    $table->timestamp('ownership_transferred_at')->nullable()->after('ownership_status');
    $table->integer('missed_payments_count')->default(0)->after('ownership_transferred_at');
});
```

#### invoices table additions (if needed):
```php
Schema::table('pib_invoices', function (Blueprint $table) {
    $table->boolean('is_final_payment')->default(false)->after('status');
    $table->boolean('is_buyout')->default(false)->after('is_final_payment');
    $table->text('special_notes')->nullable()->after('is_buyout');
});
```

## Implementation Plan

### Phase 1: Database Schema Updates

#### Task 1.1: Create Migration for Contract Fields
**File**: `database/migrations/YYYY_MM_DD_HHMMSS_add_ownership_tracking_to_contracts.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cm_contracts', function (Blueprint $table) {
            // Early buyout option
            $table->boolean('allow_early_buyout')
                ->default(false)
                ->after('monthly_rental_fee')
                ->comment('Allow client to purchase asset before full payment schedule');
            
            // Ownership tracking
            $table->enum('ownership_status', ['renting', 'owned'])
                ->default('renting')
                ->after('status')
                ->comment('Current ownership status of rent-to-own asset');
            
            $table->timestamp('ownership_transferred_at')
                ->nullable()
                ->after('ownership_status')
                ->comment('When ownership was transferred to client');
            
            // Payment tracking
            $table->integer('missed_payments_count')
                ->default(0)
                ->after('ownership_transferred_at')
                ->comment('Count of missed rental payments');
        });
    }

    public function down(): void
    {
        Schema::table('cm_contracts', function (Blueprint $table) {
            $table->dropColumn([
                'allow_early_buyout',
                'ownership_status',
                'ownership_transferred_at',
                'missed_payments_count',
            ]);
        });
    }
};
```

**Test Command**:
```bash
php artisan migrate
```

#### Task 1.2: Create Migration for Invoice Fields
**File**: `database/migrations/YYYY_MM_DD_HHMMSS_add_payment_type_flags_to_invoices.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pib_invoices', function (Blueprint $table) {
            $table->boolean('is_final_payment')
                ->default(false)
                ->after('status')
                ->comment('Marks invoice as final payment for rent-to-own');
            
            $table->boolean('is_buyout')
                ->default(false)
                ->after('is_final_payment')
                ->comment('Marks invoice as early buyout payment');
            
            $table->text('special_notes')
                ->nullable()
                ->after('is_buyout')
                ->comment('Special notes for invoice (e.g., final payment, ownership transfer)');
        });
    }

    public function down(): void
    {
        Schema::table('pib_invoices', function (Blueprint $table) {
            $table->dropColumn(['is_final_payment', 'is_buyout', 'special_notes']);
        });
    }
};
```

#### Task 1.3: Update Contract Model
**File**: `Modules/ContractManager/Models/Contract.php`

```php
// Add to $fillable array
protected $fillable = [
    // ... existing fields
    'allow_early_buyout',
    'ownership_status',
    'ownership_transferred_at',
    'missed_payments_count',
];

// Add to $casts array
protected $casts = [
    // ... existing casts
    'allow_early_buyout' => 'boolean',
    'ownership_transferred_at' => 'datetime',
    'missed_payments_count' => 'integer',
];

// Add helper methods
public function isPurchased(): bool
{
    return $this->ownership_status === 'owned';
}

public function getTotalInvoiced(): float
{
    return (float) $this->invoices()
        ->sum('total_amount');
}

public function getRemainingBalance(): float
{
    if ($this->contract_type !== 'rent_to_own') {
        return 0;
    }
    
    return max(0, ($this->purchase_price ?? 0) - $this->getTotalInvoiced());
}

public function canGenerateInvoice(): bool
{
    if ($this->contract_type !== 'rent_to_own') {
        return true; // Standard contracts always can
    }
    
    return $this->getRemainingBalance() > 0;
}
```

### Phase 2: Enhance Invoice Generation Logic

#### Task 2.1: Update `generateInvoice()` Method
**File**: `Modules/ContractManager/Http/Controllers/ContractController.php`

**Current Implementation** (lines 88-143):
```php
public function generateInvoice(Contract $contract): RedirectResponse
{
    DB::transaction(function () use ($contract, &$invoice, &$creditApplied) {
        // Current logic...
        
        if ($contract->contract_type === 'rent_to_own') {
            $amount = $contract->monthly_rental_fee ?? $amount;
            $amountCents = (int)($amount * 100);
            
            $totalInvoiced = Invoice::where('client_id', $contract->client_id)
                ->where('contract_id', $contract->id)
                ->sum('total_amount');

            if ($totalInvoiced >= ($contract->purchase_price ?? 0)) {
                throw new \Exception('Cannot generate invoice - purchase price cap reached');
            }

            $remaining = ($contract->purchase_price ?? 0) - $totalInvoiced;
            $amount = min($remaining, $amount);
            $amountCents = (int)($amount * 100);
        }
        
        // Create invoice...
    });
    
    return redirect()->route('contractmanager.contracts.show', $contract)
        ->with('success', 'Invoice created');
}
```

**Enhanced Implementation**:
```php
public function generateInvoice(Contract $contract): RedirectResponse
{
    // Check if invoice can be generated
    if ($contract->contract_type === 'rent_to_own' && !$contract->canGenerateInvoice()) {
        return redirect()->route('contractmanager.contracts.show', $contract)
            ->with('error', 'Cannot generate invoice - purchase price cap reached');
    }

    $invoice = null;
    $creditApplied = 0;
    $isFinalPayment = false;

    DB::transaction(function () use ($contract, &$invoice, &$creditApplied, &$isFinalPayment) {
        // Determine invoice amount
        $amount = $contract->price_override ?? $contract->monthly_amount ?? 0;
        
        if ($contract->contract_type === 'rent_to_own') {
            $amount = $contract->monthly_rental_fee ?? $amount;
            $remaining = $contract->getRemainingBalance();
            
            // Check if this is the final payment
            if ($remaining <= $amount && $remaining > 0) {
                $amount = $remaining;
                $isFinalPayment = true;
            }
        }
        
        $amountCents = (int)($amount * 100);

        // Create Invoice
        $invoice = Invoice::create([
            'company_id' => $contract->client->company_id ?? 1,
            'client_id' => $contract->client_id,
            'contract_id' => $contract->id,
            'invoice_number' => 'INV-' . uniqid(),
            'status' => 'pending',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'total_amount' => $amount,
            'subtotal' => $amount,
            'tax_amount' => 0,
            'is_final_payment' => $isFinalPayment,
            'special_notes' => $isFinalPayment ? 'Final payment - ownership will transfer upon payment' : null,
        ]);
        
        // Create invoice line item
        $billingTemplate = $contract->billingTemplate;
        $description = $contract->title ?? 'Service';
        
        if ($billingTemplate) {
            $description = $billingTemplate->name;
        }
        
        if ($isFinalPayment) {
            $description .= ' (Final Payment)';
        }
        
        $invoice->lineItems()->create([
            'description' => $description,
            'quantity' => 1,
            'unit_price' => $amount,
            'total' => $amount,
        ]);
        
        // Auto-apply available credit
        $creditApplied = $this->applyCreditToInvoice($invoice, $amountCents);
        
        // If final payment for rent-to-own, mark for ownership transfer
        // Actual transfer happens after payment is confirmed
        if ($isFinalPayment && $contract->contract_type === 'rent_to_own') {
            // Add event listener or job to check payment status
            // For now, we'll handle it in a separate method
        }
    });
    
    // Build flash message
    $message = 'Invoice generated';
    if ($creditApplied > 0) {
        $creditFormatted = number_format($creditApplied / 100, 2);
        $message .= " and ${$creditFormatted} credit applied";
    }
    
    if ($isFinalPayment) {
        $message .= '. This is the final payment - ownership will transfer upon payment.';
    }
    
    return redirect()->route('contractmanager.contracts.show', $contract)
        ->with('success', $message);
}
```

#### Task 2.2: Add Early Buyout Method
**File**: `Modules/ContractManager/Http/Controllers/ContractController.php`

```php
/**
 * Generate early buyout invoice for rent-to-own contract.
 */
public function generateBuyout(Contract $contract): RedirectResponse
{
    // Validate contract is rent-to-own and allows early buyout
    if ($contract->contract_type !== 'rent_to_own') {
        return redirect()->route('contractmanager.contracts.show', $contract)
            ->with('error', 'Buyout is only available for rent-to-own contracts');
    }
    
    if (!$contract->allow_early_buyout) {
        return redirect()->route('contractmanager.contracts.show', $contract)
            ->with('error', 'Early buyout is not enabled for this contract');
    }
    
    if ($contract->isPurchased()) {
        return redirect()->route('contractmanager.contracts.show', $contract)
            ->with('error', 'Contract is already purchased');
    }
    
    $remaining = $contract->getRemainingBalance();
    
    if ($remaining <= 0) {
        return redirect()->route('contractmanager.contracts.show', $contract)
            ->with('error', 'No remaining balance for buyout');
    }
    
    $invoice = null;
    
    DB::transaction(function () use ($contract, $remaining, &$invoice) {
        $totalPaid = $contract->getTotalInvoiced();
        
        // Create buyout invoice
        $invoice = Invoice::create([
            'company_id' => $contract->client->company_id ?? 1,
            'client_id' => $contract->client_id,
            'contract_id' => $contract->id,
            'invoice_number' => 'BUYOUT-' . uniqid(),
            'status' => 'pending',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'total_amount' => $remaining,
            'subtotal' => $remaining,
            'tax_amount' => 0,
            'is_buyout' => true,
            'is_final_payment' => true,
            'special_notes' => "Early buyout - {$contract->title}. Previously paid: $" . number_format($totalPaid, 2),
        ]);
        
        $invoice->lineItems()->create([
            'description' => "Early buyout - {$contract->title}",
            'quantity' => 1,
            'unit_price' => $remaining,
            'total' => $remaining,
        ]);
    });
    
    return redirect()->route('pib.invoices.show', $invoice)
        ->with('success', "Buyout invoice generated for $" . number_format($remaining, 2) . ". Ownership will transfer upon payment.");
}
```

**Add Route**:
```php
// Modules/ContractManager/Routes/web.php
Route::post('/contracts/{contract}/buyout', [ContractController::class, 'generateBuyout'])
    ->name('contracts.buyout');
```

#### Task 2.3: Add Ownership Transfer Logic
**File**: `Modules/ContractManager/Services/OwnershipTransferService.php`

```php
<?php

namespace Modules\ContractManager\Services;

use Modules\ContractManager\Models\Contract;
use Modules\PIB\Models\Invoice;
use Modules\AssetManagement\Models\Asset;

class OwnershipTransferService
{
    /**
     * Check if ownership should transfer and execute if ready.
     */
    public function checkAndTransferOwnership(Contract $contract): bool
    {
        // Only for rent-to-own contracts
        if ($contract->contract_type !== 'rent_to_own') {
            return false;
        }
        
        // Already transferred
        if ($contract->isPurchased()) {
            return false;
        }
        
        // Check if purchase price reached and all invoices paid
        $totalInvoiced = $contract->getTotalInvoiced();
        $totalPaid = Invoice::where('contract_id', $contract->id)
            ->where('status', 'paid')
            ->sum('total_amount');
        
        // Must have reached purchase price and all invoices paid
        if ($totalPaid >= $contract->purchase_price && $totalInvoiced >= $contract->purchase_price) {
            return $this->transferOwnership($contract);
        }
        
        return false;
    }
    
    /**
     * Execute ownership transfer.
     */
    protected function transferOwnership(Contract $contract): bool
    {
        DB::transaction(function () use ($contract) {
            // Update contract
            $contract->update([
                'ownership_status' => 'owned',
                'ownership_transferred_at' => now(),
                'status' => 'completed',
            ]);
            
            // Update related asset if exists
            if ($contract->asset_id) {
                $asset = Asset::find($contract->asset_id);
                if ($asset) {
                    $asset->update([
                        'owner_type' => 'client',
                        'owner_id' => $contract->client_id,
                        'status' => 'active',
                    ]);
                }
            }
            
            // Send notification to client
            // Mail::to($contract->client->email)->send(new OwnershipTransferred($contract));
            
            // Log activity
            activity()
                ->performedOn($contract)
                ->withProperties(['asset_id' => $contract->asset_id])
                ->log('Ownership transferred to client');
        });
        
        return true;
    }
}
```

**Register Service**:
```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->singleton(OwnershipTransferService::class);
}
```

### Phase 3: Update Contract Creation View

#### Task 3.1: Add Early Buyout Checkbox to Create Form
**File**: `Modules/ContractManager/resources/views/contracts/create.blade.php`

**Find the contract type select field**, then add after it:
```blade
<!-- Existing contract type field -->
<div>
    <label for="contract_type" class="block text-sm font-medium text-gray-700">Contract Type</label>
    <select name="contract_type" id="contract_type" dusk="contract-type" 
            class="mt-1 block w-full rounded-md border-gray-300"
            x-model="contractType">
        <option value="standard">Standard</option>
        <option value="rent_to_own">Rent to Own</option>
    </select>
</div>

<!-- ADD THIS: Early buyout checkbox (only visible for rent-to-own) -->
<div x-show="contractType === 'rent_to_own'" x-cloak>
    <div class="flex items-center">
        <input type="checkbox" 
               name="allow_early_buyout" 
               id="allow_early_buyout"
               dusk="allow-early-buyout"
               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
        <label for="allow_early_buyout" class="ml-2 block text-sm text-gray-700">
            Allow Early Buyout
            <span class="text-gray-500">(Client can purchase before full payment schedule)</span>
        </label>
    </div>
</div>
```

**Add Alpine.js data** to form wrapper:
```blade
<form x-data="{ contractType: 'standard' }" ...>
```

### Phase 4: Update Contract Detail View

#### Task 4.1: Display Ownership Status Badge
**File**: `Modules/ContractManager/resources/views/contracts/show.blade.php`

**Add after contract title**:
```blade
<div class="flex items-center gap-3 mb-4">
    <h1 class="text-2xl font-bold">{{ $contract->title }}</h1>
    
    @if($contract->isPurchased())
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
            <svg class="mr-1.5 h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            Ownership Transferred
        </span>
    @elseif($contract->contract_type === 'rent_to_own')
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
            Renting to Own
        </span>
    @endif
</div>
```

#### Task 4.2: Display Payment Progress for Rent-to-Own
**File**: `Modules/ContractManager/resources/views/contracts/show.blade.php`

**Add in contract details section**:
```blade
@if($contract->contract_type === 'rent_to_own' && !$contract->isPurchased())
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h3 class="text-lg font-semibold text-blue-900 mb-3">Payment Progress</h3>
        
        @php
            $totalPaid = $contract->getTotalInvoiced();
            $remaining = $contract->getRemainingBalance();
            $progress = $contract->purchase_price > 0 
                ? ($totalPaid / $contract->purchase_price) * 100 
                : 0;
        @endphp
        
        <div class="space-y-3">
            <!-- Progress Bar -->
            <div>
                <div class="flex justify-between text-sm text-blue-700 mb-1">
                    <span>Progress toward ownership</span>
                    <span>{{ number_format($progress, 1) }}%</span>
                </div>
                <div class="w-full bg-blue-200 rounded-full h-3">
                    <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" 
                         style="width: {{ min($progress, 100) }}%"></div>
                </div>
            </div>
            
            <!-- Payment Summary -->
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-blue-600">Total Paid</span>
                    <p class="text-lg font-bold text-blue-900">${{ number_format($totalPaid, 2) }}</p>
                </div>
                <div>
                    <span class="text-blue-600">Remaining</span>
                    <p class="text-lg font-bold text-blue-900">${{ number_format($remaining, 2) }}</p>
                </div>
                <div>
                    <span class="text-blue-600">Purchase Price</span>
                    <p class="text-lg font-bold text-blue-900">${{ number_format($contract->purchase_price, 2) }}</p>
                </div>
            </div>
            
            <!-- Missed Payments Warning -->
            @if($contract->missed_payments_count > 0)
                <div class="flex items-center gap-2 text-red-600 text-sm">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span class="font-medium">{{ $contract->missed_payments_count }} missed payment(s)</span>
                </div>
            @endif
        </div>
    </div>
@endif
```

#### Task 4.3: Add Early Buyout Button
**File**: `Modules/ContractManager/resources/views/contracts/show.blade.php`

**Add to actions section** (near generate invoice button):
```blade
@if($contract->contract_type === 'rent_to_own' 
    && !$contract->isPurchased() 
    && $contract->allow_early_buyout 
    && $contract->getRemainingBalance() > 0)
    
    <form action="{{ route('contractmanager.contracts.buyout', $contract) }}" 
          method="POST" 
          class="inline">
        @csrf
        <button type="button"
                dusk="request-buyout-button"
                onclick="confirmBuyout(this)"
                data-remaining="{{ number_format($contract->getRemainingBalance(), 2) }}"
                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-500 active:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Request Early Buyout
        </button>
    </form>
@endif

<script>
function confirmBuyout(button) {
    const remaining = button.dataset.remaining;
    if (confirm(`Generate early buyout invoice for $${remaining}?\n\nThis will complete your contract and transfer ownership upon payment.`)) {
        button.closest('form').submit();
    }
}
</script>
```

### Phase 5: Invoice Payment Event Listener

#### Task 5.1: Create Invoice Paid Event Listener
**File**: `Modules/PIB/Listeners/CheckOwnershipTransfer.php`

```php
<?php

namespace Modules\PIB\Listeners;

use Modules\PIB\Events\InvoicePaid;
use Modules\ContractManager\Models\Contract;
use Modules\ContractManager\Services\OwnershipTransferService;

class CheckOwnershipTransfer
{
    public function __construct(
        protected OwnershipTransferService $ownershipService
    ) {}
    
    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;
        
        // Check if this invoice is for a rent-to-own contract
        if (!$invoice->contract_id) {
            return;
        }
        
        $contract = Contract::find($invoice->contract_id);
        
        if (!$contract || $contract->contract_type !== 'rent_to_own') {
            return;
        }
        
        // Check if ownership should transfer
        $this->ownershipService->checkAndTransferOwnership($contract);
    }
}
```

**Register Listener**:
```php
// Modules/PIB/Providers/EventServiceProvider.php
protected $listen = [
    InvoicePaid::class => [
        CheckOwnershipTransfer::class,
    ],
];
```

## Testing Strategy

### Manual Testing Checklist

#### Test 1: Purchase Price Cap
- [ ] Create rent-to-own contract ($1,000 purchase, $50 monthly)
- [ ] Generate 20 invoices successfully
- [ ] Attempt 21st invoice → See "Cannot generate invoice - purchase price cap reached"
- [ ] Verify only 20 invoices exist in database
- [ ] Verify "Invoice generated" flash message on successful generations

#### Test 2: Irregular Final Payment
- [ ] Create rent-to-own contract ($1,250 purchase, $100 monthly)
- [ ] Generate 12 invoices → Each shows $100
- [ ] Generate 13th invoice → Shows $50
- [ ] Verify 13th invoice has `is_final_payment = true`
- [ ] Verify line item description includes "(Final Payment)"
- [ ] Total: $1,250 exactly

#### Test 3: Early Buyout
- [ ] Create rent-to-own with `allow_early_buyout = true`
- [ ] Generate 5 monthly invoices ($750 total)
- [ ] Mark all 5 invoices as paid
- [ ] Click "Request Early Buyout" button
- [ ] Verify buyout invoice created for $1,250 ($2,000 - $750)
- [ ] Verify buyout invoice has `is_buyout = true`
- [ ] Pay buyout invoice
- [ ] Verify ownership transferred

#### Test 4: Ownership Transfer
- [ ] Complete rent-to-own payment schedule
- [ ] Pay final invoice
- [ ] Verify `ownership_status` changed to 'owned'
- [ ] Verify `ownership_transferred_at` timestamp set
- [ ] Verify "Ownership Transferred" badge appears
- [ ] Verify asset ownership updated (if applicable)

#### Test 5: Missed Payment Tracking
- [ ] Create rent-to-own contract
- [ ] Generate 10 monthly invoices
- [ ] Mark 2 invoices as overdue
- [ ] Verify `missed_payments_count = 2`
- [ ] Verify warning displays on contract detail page

### Browser Test Commands

```bash
# Test 1: Purchase cap
php artisan dusk tests/Browser/Billing/RentToOwnTest.php::test_rental_invoices_stop_at_purchase_cap

# Test 2: Irregular payment
php artisan dusk tests/Browser/Billing/RentToOwnTest.php::test_rent_to_own_with_irregular_final_payment

# Test 3: Early buyout
php artisan dusk tests/Browser/Billing/RentToOwnTest.php::test_rent_to_own_early_buyout

# Test 4: Missed payments
php artisan dusk tests/Browser/Billing/RentToOwnTest.php::test_rent_to_own_tracks_missed_payments

# Test 5: Ownership transfer
php artisan dusk tests/Browser/Billing/RentToOwnTest.php::test_rent_to_own_ownership_transfer_on_completion

# Run full suite
php artisan dusk tests/Browser/Billing/RentToOwnTest.php 2>&1 | tee reports/rent_to_own_test.txt
```

### Unit Tests

**File**: `tests/Unit/Services/OwnershipTransferServiceTest.php`

```php
public function test_ownership_transfers_when_fully_paid(): void
{
    $contract = Contract::factory()->rentToOwn()->create([
        'purchase_price' => 1000,
        'monthly_rental_fee' => 50,
    ]);
    
    // Create 20 paid invoices
    Invoice::factory()->count(20)->create([
        'contract_id' => $contract->id,
        'total_amount' => 50,
        'status' => 'paid',
    ]);
    
    $service = app(OwnershipTransferService::class);
    $result = $service->checkAndTransferOwnership($contract);
    
    $this->assertTrue($result);
    $this->assertEquals('owned', $contract->fresh()->ownership_status);
    $this->assertNotNull($contract->fresh()->ownership_transferred_at);
}
```

## Implementation Checklist

### Phase 1: Database
- [ ] Create migration for contract ownership fields
- [ ] Create migration for invoice payment type flags
- [ ] Run migrations
- [ ] Update Contract model with new fields
- [ ] Update Invoice model with new fields
- [ ] Add helper methods to Contract model

### Phase 2: Business Logic
- [ ] Enhance `generateInvoice()` method with cap validation
- [ ] Add final payment detection and handling
- [ ] Improve flash messages for all scenarios
- [ ] Create `generateBuyout()` method
- [ ] Add route for buyout action
- [ ] Create OwnershipTransferService
- [ ] Register service in service provider

### Phase 3: Views - Create Form
- [ ] Add `allow_early_buyout` checkbox
- [ ] Add Alpine.js conditional display
- [ ] Test form submission with new field

### Phase 4: Views - Show Page
- [ ] Add ownership status badge
- [ ] Add payment progress section
- [ ] Add missed payments warning
- [ ] Add early buyout button
- [ ] Add JavaScript confirmation for buyout
- [ ] Test all visual elements

### Phase 5: Event Handling
- [ ] Create CheckOwnershipTransfer listener
- [ ] Register listener for InvoicePaid event
- [ ] Test ownership transfer after payment

### Phase 6: Testing
- [ ] Run all 5 RentToOwnTest tests
- [ ] Perform manual testing checklist
- [ ] Fix any remaining issues
- [ ] Document results

## Success Criteria

### Functional Requirements
✅ Purchase price cap enforced (no invoices when cap reached)  
✅ Irregular final payment calculated correctly  
✅ Early buyout option available when enabled  
✅ Ownership transfers automatically on full payment  
✅ Missed payments tracked and displayed  
✅ All flash messages display correctly  
✅ All 5 RentToOwnTest tests pass (2 business logic + 3 after JavaScript fixes)  

### Code Quality
✅ Clean, well-documented code  
✅ Proper error handling  
✅ Database transactions for consistency  
✅ Event-driven architecture for ownership transfer  
✅ Reusable service classes  

### User Experience
✅ Clear progress visualization  
✅ Informative flash messages  
✅ Easy-to-understand payment summary  
✅ Confirmation dialogs for major actions  

## Risk Assessment

### High Risk
- **Ownership transfer timing**: Must happen at right moment (after payment confirmed, not just invoice created)
- **Race conditions**: Multiple invoices paid simultaneously could cause issues
- **Database integrity**: Transactions must be atomic

### Medium Risk
- **Irregular payment calculation**: Edge cases (very small remainders, rounding errors)
- **Event listener dependencies**: Invoice payment event must exist
- **Asset management integration**: Asset ownership update may have dependencies

### Mitigation Strategies
1. Use database transactions for all financial operations
2. Extensive testing of edge cases (penny remainders, concurrent payments)
3. Add database constraints to prevent invalid states
4. Implement event logging for audit trail
5. Add rollback capability for failed ownership transfers

## Dependencies

### Code Dependencies
- Contract model and migrations
- Invoice model and PIB module
- Asset Management module (for ownership update)
- Event system (InvoicePaid event)
- Mail system (for notifications)

### External Dependencies
- Payment processor integration (Stripe, etc.)
- Email delivery system
- Activity logging system

### Test Dependencies
- Browser tests must wait for database updates
- Event listeners must complete before assertions
- JavaScript modal fixes (Prompt 3) for tests 3-5

## Related Documentation
- [PROMPT1_MISSING_FEATURES.md](./PROMPT1_MISSING_FEATURES.md) - Parent tracking
- [ContractController.php](../../Modules/ContractManager/Http/Controllers/ContractController.php) - Current implementation
- [RentToOwnTest.php](../../tests/Browser/Billing/RentToOwnTest.php) - Test specifications

## Notes

### Business Logic Insights
1. Rent-to-own is a financing mechanism, not a rental
2. Clients build equity with each payment
3. Early buyout provides flexibility and potentially saves money
4. Ownership transfer is a significant legal event requiring proper tracking
5. Missed payments need careful handling (grace periods, penalties, etc.)

### Technical Insights
1. Flash messages are critical for user feedback on financial operations
2. Progress visualization helps clients understand their payment journey
3. Event-driven architecture allows decoupling of ownership transfer logic
4. Database flags (is_final_payment, is_buyout) enable special invoice handling
5. Transaction safety is paramount for financial operations

### Future Enhancements
- Payment plan adjustments (change monthly amount mid-contract)
- Late fee calculation for missed payments
- Grace period before marking payment as missed
- Ownership certificate generation (PDF)
- Email notifications at key milestones (50% paid, 75% paid, final payment)
- Integration with asset tracking system
- Buyout discount options (pay off early, save 10%)

---

**Last Updated**: February 7, 2026  
**Author**: Development Team  
**Status**: Ready for Implementation  
**Estimated Completion**: 2-3 days
