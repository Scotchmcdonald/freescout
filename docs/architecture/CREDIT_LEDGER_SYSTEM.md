# Credit Ledger & Invoice Generation System
**Module:** PIB (Billing)  
**Status:** ⏳ PLANNED - Not Yet Implemented  
**Priority:** High - Required for Automated Billing  
**Dependencies:** CRM (Clients), ContractManager (Billing Templates, Contracts)  
**Last Updated:** February 9, 2026

---

## ⚠️ Implementation Status

This document describes a **planned feature** for automated credit management and invoice generation. Implementation is scheduled for upcoming sprints.

**Current State:**
- ✅ Manual invoice creation available
- ✅ Payment processing operational (Helcim integration)
- ❌ Automated credit ledger system not implemented
- ❌ Automatic credit application to invoices not available
- ❌ Contract-based invoice generation not automated

**Implementation Priority:** High (Q1 2026 - Next Sprint)

---

## Overview

The Credit Ledger system tracks client prepayments and automatically applies credits to invoices. When combined with contract-based invoice generation, it enables automated billing workflows.

## Architecture Principles

### 1. Double-Entry Accounting
Credits are tracked with full transaction history:
- **Credit** (positive): Client makes prepayment
- **Debit** (negative): Credit applied to invoice
- **Balance:** Running total of available credit

### 2. Credit Application Priority
1. **Automatic Application:** Credits automatically apply to new invoices
2. **FIFO Order:** Oldest credits used first
3. **Partial Application:** Credits can partially cover invoices

## Data Model

### client_credits (PIB Module)
```sql
CREATE TABLE client_credits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id BIGINT UNSIGNED NOT NULL,
    balance_cents INT DEFAULT 0,  -- Running balance
    lifetime_credits_cents INT DEFAULT 0,  -- Total ever added
    lifetime_debits_cents INT DEFAULT 0,   -- Total ever used
    last_transaction_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_client (client_id),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### client_credit_ledger (PIB Module)
```sql
CREATE TABLE client_credit_ledger (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id BIGINT UNSIGNED NOT NULL,
    
    -- Transaction details
    transaction_type ENUM('credit', 'debit') NOT NULL,
    amount_cents INT NOT NULL,  -- Always positive, type determines direction
    balance_after_cents INT NOT NULL,  -- Balance after this transaction
    
    -- Reference  
    reference_type VARCHAR(50) NULL,  -- 'payment', 'invoice', 'adjustment', 'refund'
    reference_id BIGINT UNSIGNED NULL,  -- payment_id or invoice_id
    description TEXT NULL,
    
    -- Metadata
    created_by_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_client (client_id),
    INDEX idx_reference (reference_type, reference_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### invoices (PIB Module)
```sql
-- Add credit tracking columns
ALTER TABLE invoices ADD COLUMN credit_applied_cents INT DEFAULT 0;
ALTER TABLE invoices ADD COLUMN amount_due_cents INT GENERATED ALWAYS AS (
    subtotal_cents - discount_cents - credit_applied_cents
) STORED;
```

---

## Feature Requirements

### 1. Record Prepayment

**Route:** `/admin/billing/payments/create`  
**Controller:** `PIB/Http/Controllers/BillingController@storePayment`

**Payment Types:**
- `prepayment`: General credit for future invoices
- `asset_prepayment`: Credit for hardware/asset purchases
- `check`: Payment via check (can be credit)
- `ach`: ACH transfer
- `wire`: Wire transfer
- `credit_card`: Card payment
- `cash`: Cash payment

**Process:**
```php
public function storePayment(Request $request)
{
    $validated = $request->validate([
        'client_id' => 'required|exists:clients,id',
        'amount' => 'required|numeric|min:0.01',
        'payment_type' => 'required|in:prepayment,asset_prepayment,...',
        'description' => 'nullable|string',
    ]);
    
    $amountCents = (int)($validated['amount'] * 100);
    
    // Only create credit for prepayment types
    if (in_array($validated['payment_type'], ['prepayment', 'asset_prepayment'])) {
        DB::transaction(function() use ($validated, $amountCents) {
            // Get or create client credit record
            $clientCredit = ClientCredit::firstOrCreate(
                ['client_id' => $validated['client_id']],
                ['balance_cents' => 0, 'lifetime_credits_cents' => 0]
            );
            
            // Update balance
            $newBalance = $clientCredit->balance_cents + $amountCents;
            $clientCredit->update([
                'balance_cents' => $newBalance,
                'lifetime_credits_cents' => $clientCredit->lifetime_credits_cents + $amountCents,
                'last_transaction_at' => now(),
            ]);
            
            // Record in ledger
            ClientCreditLedger::create([
                'client_id' => $validated['client_id'],
                'transaction_type' => 'credit',
                'amount_cents' => $amountCents,
                'balance_after_cents' => $newBalance,
                'reference_type' => 'payment',
                'reference_id' => $paymentId, // from payment record
                'description' => $validated['description'],
                'created_by_user_id' => auth()->id(),
            ]);
        });
    }
    
    return redirect()->back()->with('success', 'Payment recorded');
}
```

### 2. View Credit Ledger

**Route:** `/admin/billing/credit-ledger`  
**View:** Display all clients with credit balances

```php
public function creditLedgerIndex()
{
    $clients = Client::whereHas('creditBalance', function($q) {
        $q->where('balance_cents', '>', 0);
    })
    ->with('creditBalance')
    ->get();
    
    return view('pib::credit-ledger.index', compact('clients'));
}
```

**Route:** `/admin/billing/credit-ledger/{client}`  
**View:** Show full transaction history for one client

```php
public function creditLedgerShow(Client $client)
{
    $credit = $client->creditBalance ?? ClientCredit::create(['client_id' => $client->id]);
    $transactions = ClientCreditLedger::where('client_id', $client->id)
        ->orderBy('created_at', 'desc')
        ->paginate(50);
    
    return view('pib::credit-ledger.show', compact('client', 'credit', 'transactions'));
}
```

### 3. Contract-Based Invoice Generation

**Trigger:** Manual or scheduled (monthly billing run)  
**Route:** `/contracts/{contract}/generate-invoice`  
**Controller:** `ContractManager/Http/Controllers/ContractController@generateInvoice`

**Process:**
```php
public function generateInvoice(Contract $contract)
{
    DB::transaction(function() use ($contract) {
        // 1. Calculate invoice amount from contract
        $subtotalCents = $contract->monthly_amount_cents;
        
        // 2. Create invoice
        $invoice = Invoice::create([
            'client_id' => $contract->client_id,
            'contract_id' => $contract->id,
            'subtotal_cents' => $subtotalCents,
            'discount_cents' => 0,
            'credit_applied_cents' => 0,
            'status' => 'draft',
            'due_date' => now()->addDays(30),
        ]);
        
        // 3. Add line items
        InvoiceLineItem::create([
            'invoice_id' => $invoice->id,
            'description' => $contract->name,
            'quantity' => 1,
            'unit_price_cents' => $subtotalCents,
        ]);
        
        // 4. Auto-apply available credit
        $this->applyCreditToInvoice($invoice);
        
        // 5. Finalize invoice
        $invoice->update(['status' => 'sent']);
    });
    
    return redirect()->back()->with('success', 'Invoice generated');
}

private function applyCreditToInvoice(Invoice $invoice)
{
    $clientCredit = ClientCredit::where('client_id', $invoice->client_id)->first();
    
    if (!$clientCredit || $clientCredit->balance_cents <= 0) {
        return; // No credit available
    }
    
    // Calculate how much credit to apply
    $invoiceAmountCents = $invoice->subtotal_cents - $invoice->discount_cents;
    $creditToApplyCents = min($clientCredit->balance_cents, $invoiceAmountCents);
    
    if ($creditToApplyCents > 0) {
        DB::transaction(function() use ($invoice, $clientCredit, $creditToApplyCents) {
            // Update invoice
            $invoice->update(['credit_applied_cents' => $creditToApplyCents]);
            
            // Update client credit balance
            $newBalance = $clientCredit->balance_cents - $creditToApplyCents;
            $clientCredit->update([
                'balance_cents' => $newBalance,
                'lifetime_debits_cents' => $clientCredit->lifetime_debits_cents + $creditToApplyCents,
                'last_transaction_at' => now(),
            ]);
            
            // Record in ledger
            ClientCreditLedger::create([
                'client_id' => $invoice->client_id,
                'transaction_type' => 'debit',
                'amount_cents' => $creditToApplyCents,
                'balance_after_cents' => $newBalance,
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
                'description' => "Applied to Invoice #{$invoice->id}",
                'created_by_user_id' => auth()->id(),
            ]);
        });
    }
}
```

### 4. Flash Message Requirements

**Contract Created:**
```php
return redirect()->route('contractmanager.contracts.index')
    ->with('success', 'Contract created');
```

**Contract Saved:**
```php
return redirect()->route('contractmanager.contracts.index')
    ->with('success', 'Contract saved successfully');
```

**Invoice Generated:**
```php
return redirect()->back()
    ->with('success', 'Invoice generated');
```

**Display in Views:**
```blade
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
```

---

## Implementation Checklist

### Phase 1: Credit Ledger Foundation
- [ ] Create `client_credits` migration
- [ ] Create `client_credit_ledger` migration
- [ ] Create `ClientCredit` model
- [ ] Create `ClientCreditLedger` model
- [ ] Add relationship to Client model

### Phase 2: Record Payments
- [ ] Update `BillingController@storePayment` to create credits
- [ ] Add credit ledger transaction recording
- [ ] Test payment recording

### Phase 3: Credit Ledger Views
- [ ] Create `credit-ledger/index.blade.php` (client list)
- [ ] Create `credit-ledger/show.blade.php` (transaction history)
- [ ] Add routes for credit ledger
- [ ] Style ledger tables

### Phase 4: Invoice Generation
- [ ] Create `ContractController@generateInvoice` method
- [ ] Implement automatic credit application
- [ ] Add invoice line items from contract
- [ ] Test invoice generation flow

### Phase 5: Contract Flash Messages
- [ ] Add flash messages to contract create/update
- [ ] Display flash in contract index view
- [ ] Test flash persistence

---

## Routes

```php
// Credit Ledger (admin only)
Route::get('/admin/billing/credit-ledger', [BillingController::class, 'creditLedgerIndex'])
    ->name('admin.billing.credit-ledger.index');
Route::get('/admin/billing/credit-ledger/{client}', [BillingController::class, 'creditLedgerShow'])
    ->name('admin.billing.credit-ledger.show');

// Invoice Generation
Route::post('/contracts/{contract}/generate-invoice', [ContractController::class, 'generateInvoice'])
    ->name('contractmanager.contracts.generate-invoice');
```

---

## Testing Strategy

**Browser Tests:**
- `tests/Browser/Billing/AssetCreditLedgerTest.php` (7 scenarios)
  - Upfront payment creates credit
  - Credit automatically applied to invoices
  - Partial credit application
  - Multiple prepayments aggregate
  - FIFO credit application
  - Credit ledger display
  - Invoice shows credit applied

**Integration Tests:**
- Credit balance calculations
- Ledger transaction recording
- FIFO credit application
- Partial vs full credit coverage
- Contract to invoice generation

**Unit Tests:**
- ClientCredit model methods
- Amount calculations (cents vs dollars)
- Balance after transaction calculation
