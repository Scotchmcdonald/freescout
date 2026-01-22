# Manual Testing Plan: Core System Functionality
**Version:** 1.0  
**Date:** January 19, 2026  
**Scope:** Manual entry pathways (GoogleAdmin/Action1 integrations disabled)  
**Estimated Time:** 2-3 hours total

---

## Purpose

This testing plan validates core functionality through **manual entry pathways** with maximum coverage per time invested. Tests are ordered by dependency: foundation modules first, then features that build on them.

**Testing Philosophy:** Each test verifies multiple integration points simultaneously (e.g., creating a client tests CRM + triggers events that PIB/AssetManagement may listen to).

---

## Prerequisites

- [ ] Application is running and accessible
- [ ] You have admin access to the application
- [ ] Test client data can be created/deleted
- [ ] Note the URL you're testing: `__________________`

---

## Quick Reference: Module Status

| Module | Status | Testing Focus |
|--------|--------|---------------|
| CRM | ✅ Enabled | Client/Contact/Company CRUD |
| AssetManagement | ✅ Enabled | Manual asset entry, assignment |
| ContractManager | ✅ Enabled | Quote→Contract→BillingTemplate flow |
| PIB | ✅ Enabled | Invoice generation, credit balances |
| Payment | ✅ Enabled | Payment method management |
| ClientPortal | ✅ Enabled | Client-facing views |
| SoftwareSubscriptions | ✅ Enabled | Manual software assignment |
| DevFeedback | ✅ Enabled | Feedback submission |

---

## Section 1: CRM Foundation (15-20 minutes)

> **Goal:** Verify the foundation module that all others depend on.

### Test 1.1: Create a Test Client

**Steps:**
1. Navigate to **CRM → Clients**
2. Click **Create New Client**
3. Enter the following test data:
   - Name: `TEST-ManualQA-[YourInitials]`
   - Email: `test-qa@example.com`
   - Any other required fields
4. Save the client

**Expected Results:**
- [ ] Client is created successfully
- [ ] Client appears in client list
- [ ] Client detail page loads without errors
- [ ] URL shows a valid client ID (note it: `________`)

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 1.2: Add a Contact to Client

**Steps:**
1. From the client detail page, find **Contacts** section
2. Click **Add Contact**
3. Enter:
   - Name: `Test Contact One`
   - Email: `contact1@test-qa.example.com`
   - Phone: `555-0100`
4. Save the contact

**Expected Results:**
- [ ] Contact is created and linked to client
- [ ] Contact appears in client's contact list
- [ ] Contact email is displayed correctly

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 1.3: Add a Second Contact (For Software Assignment Testing)

**Steps:**
1. Add another contact to the same client:
   - Name: `Test Contact Two`
   - Email: `contact2@test-qa.example.com`

**Expected Results:**
- [ ] Second contact created successfully
- [ ] Both contacts appear in client's contact list

---

### Test 1.4: Verify Client 360 View (Cross-Module Integration)

**Steps:**
1. Navigate to the client's detail/360 view
2. Observe all sections/tabs/widgets displayed

**Expected Results:**
- [ ] Client basic information displays
- [ ] Contacts section shows both contacts
- [ ] Assets section visible (may be empty - that's OK)
- [ ] Invoices/Billing section visible (may be empty)
- [ ] No error messages or broken sections
- [ ] Page loads within 3 seconds

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

## Section 2: Asset Management - Manual Entry (20-25 minutes)

> **Goal:** Test asset creation, assignment, and status management without external integrations.

### Test 2.1: Create a Manual Asset (Windows Device)

**Steps:**
1. Navigate to **Assets** or **Asset Management**
2. Click **Create New Asset** (or similar)
3. Enter:
   - Serial Number: `TEST-WIN-001`
   - Type/Category: Windows Device (or similar)
   - Model: `Dell Latitude Test`
   - Status: Active
4. Assign to your test client
5. Save

**Expected Results:**
- [ ] Asset is created successfully
- [ ] Asset appears in asset list
- [ ] Asset shows as assigned to test client
- [ ] Asset status shows as Active

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 2.2: Create a Chromebook Asset

**Steps:**
1. Create another asset:
   - Serial Number: `TEST-CB-001`
   - Type: Chromebook
   - Model: `HP Chromebook 14 Test`
   - Status: Active
2. Assign to your test client

**Expected Results:**
- [ ] Chromebook asset created
- [ ] Asset correctly typed as Chromebook
- [ ] Assigned to test client

---

### Test 2.3: Verify Asset Appears in Client View

**Steps:**
1. Navigate back to your test client's detail page
2. Find the Assets section/tab

**Expected Results:**
- [ ] Both assets (Windows + Chromebook) appear under client
- [ ] Asset details (serial, type, status) display correctly
- [ ] Can click through to asset detail from client view

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 2.4: Change Asset Status

**Steps:**
1. Navigate to one of the test assets
2. Change status from Active to **Retired** (or Inactive)
3. Save

**Expected Results:**
- [ ] Status change saves successfully
- [ ] Asset list shows updated status
- [ ] Client view shows updated status
- [ ] (If visible) Status change is logged/audited

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

## Section 3: Software Subscriptions - Manual Assignment (20-25 minutes)

> **Goal:** Test software product catalog and manual assignment to users/devices.

### Test 3.1: Browse Software Product Catalog

**Steps:**
1. Navigate to **Software Subscriptions** or **Software Products**
2. View the list of available software products

**Expected Results:**
- [ ] Software catalog loads without errors
- [ ] Products have names, vendors, and pricing visible
- [ ] Can filter/search products (if feature exists)

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 3.2: Create Client Software Subscription

**Steps:**
1. Navigate to your test client
2. Find **Software Subscriptions** section
3. Add a software subscription (e.g., Microsoft 365, or any available product)
4. Configure:
   - Product: Select any available
   - Billing behavior: Passthrough (or available option)
5. Save

**Expected Results:**
- [ ] Subscription is created for client
- [ ] Subscription appears in client's software list
- [ ] Assignment count shows 0 (no users/devices assigned yet)

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 3.3: Assign Software to Contact (User)

**Steps:**
1. From the client's software subscription, add an assignment
2. Assign to: `Test Contact One`
3. Save

**Expected Results:**
- [ ] Assignment is created
- [ ] Assignment count increases to 1
- [ ] Contact shows as having this software assigned

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 3.4: Assign Software to Second Contact

**Steps:**
1. Add another assignment to `Test Contact Two`

**Expected Results:**
- [ ] Second assignment created
- [ ] Assignment count shows 2
- [ ] Both contacts visible in assignment list

---

### Test 3.5: Verify Atomic Counter Integrity

**Steps:**
1. Note the current assignment count (should be 2)
2. Remove one assignment (Test Contact Two)
3. Verify count

**Expected Results:**
- [ ] Removing assignment decrements count to 1
- [ ] No errors during removal
- [ ] Count is accurate and consistent across views

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

## Section 4: Contract Manager - Quote to Contract Flow (25-30 minutes)

> **Goal:** Test the complete quote → contract → billing template lifecycle.

### Test 4.1: Create a New Quote

**Steps:**
1. Navigate to **Contract Manager** or **Quotes**
2. Click **Create New Quote**
3. Select your test client
4. Add line items:
   - Item 1: "Monthly IT Support" - Quantity: 1 - Price: $500
   - Item 2: "Per-User License" - Quantity: 2 - Price: $15/each
5. Set billing cycle: Monthly
6. Save as Draft

**Expected Results:**
- [ ] Quote is created with draft status
- [ ] Quote number is generated
- [ ] Line items display correctly
- [ ] Total calculates correctly ($500 + $30 = $530)
- [ ] Quote appears in quotes list

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 4.2: Edit Quote (Revision Tracking)

**Steps:**
1. Edit the quote you just created
2. Change one line item price (e.g., $500 → $550)
3. Save

**Expected Results:**
- [ ] Edit saves successfully
- [ ] Total recalculates ($550 + $30 = $580)
- [ ] (If visible) Revision history shows the change

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 4.3: Approve Quote → Create Contract

**Steps:**
1. Find the quote approval action (may be "Approve", "Mark Approved", or similar)
2. Approve the quote
3. Verify contract creation

**Expected Results:**
- [ ] Quote status changes to Approved
- [ ] Contract is created (either automatically or via "Create Contract" button)
- [ ] Contract is linked to the quote
- [ ] Contract shows correct start/end dates
- [ ] Contract appears in contracts list

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 4.4: Verify Billing Template Creation

**Steps:**
1. Navigate to the contract detail page
2. Look for associated Billing Template
3. (Or navigate to Billing Templates list and find the one for your test client)

**Expected Results:**
- [ ] Billing template exists and is linked to contract
- [ ] Billing template shows correct line items from quote
- [ ] Next invoice date is set
- [ ] Billing cycle matches quote settings

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

## Section 5: PIB (Billing & Invoicing) (20-25 minutes)

> **Goal:** Test invoice generation, credit management, and billing workflows.

### Test 5.1: Manual Invoice Creation

**Steps:**
1. Navigate to **Invoices** or **PIB → Invoices**
2. Click **Create Invoice**
3. Select your test client
4. Add line items:
   - Description: "Test Service Charge"
   - Amount: $100
5. Save as Draft

**Expected Results:**
- [ ] Invoice is created with Draft status
- [ ] Invoice number is generated
- [ ] Invoice appears in invoices list
- [ ] Invoice amount shows $100

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 5.2: Publish Invoice

**Steps:**
1. Find the publish action for the draft invoice
2. Publish the invoice

**Expected Results:**
- [ ] Invoice status changes to Published
- [ ] Published date is recorded
- [ ] (If configured) Client notification would be sent
- [ ] Invoice appears in client's invoice history

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 5.3: Client Credit Balance Management

**Steps:**
1. Navigate to your test client
2. Find **Credits** or **Credit Balance** section
3. Add a credit:
   - Amount: $250.00
   - Description: "Test Credit - Hardware Prepayment"
4. Save

**Expected Results:**
- [ ] Credit is added successfully
- [ ] Balance shows $250.00
- [ ] Credit ledger shows the entry with description
- [ ] Timestamp is recorded

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 5.4: Deduct Credit

**Steps:**
1. Deduct from credit balance:
   - Amount: $75.00
   - Description: "Test Deduction - Device Purchase"

**Expected Results:**
- [ ] Deduction successful
- [ ] Balance now shows $175.00
- [ ] Ledger shows both credit and debit entries
- [ ] Running balance is accurate in ledger

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 5.5: Verify Client 360 Shows Financial Data

**Steps:**
1. Return to client's 360/detail view
2. Check financial sections

**Expected Results:**
- [ ] Credit balance displays ($175.00)
- [ ] Invoices section shows the test invoice
- [ ] Invoice status (Published) is visible

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

## Section 6: Client Portal (15-20 minutes)

> **Goal:** Verify client-facing portal displays data correctly.

### Test 6.1: Access Client Portal

**Steps:**
1. Navigate to the Client Portal (may need to switch context or use a different URL)
2. Access as/impersonate your test client (or use a client login if available)

**Expected Results:**
- [ ] Portal loads without errors
- [ ] Dashboard/home page displays
- [ ] Navigation shows available sections

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 6.2: Verify Portal Shows Client Data

**Steps:**
1. Check each available section in the portal:
   - Invoices
   - Assets
   - Quotes/Contracts
   - Support Tickets (if available)

**Expected Results:**
- [ ] Invoices tab shows the published test invoice
- [ ] Assets tab shows the test assets
- [ ] Contracts/Quotes tab shows approved contract
- [ ] Data matches what was created in admin

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 6.3: Invoice Detail in Portal

**Steps:**
1. Click on the test invoice in the portal
2. View invoice details

**Expected Results:**
- [ ] Invoice detail page loads
- [ ] Line items display correctly
- [ ] Amount displays correctly
- [ ] Due date is visible
- [ ] Payment action is available (if configured)

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

## Section 7: Cross-Module Integration Tests (15-20 minutes)

> **Goal:** Verify modules communicate correctly via events.

### Test 7.1: Asset Count → Client View Integration

**Steps:**
1. Navigate to test client 360 view
2. Note asset count displayed
3. Go to Asset Management and create another asset for this client:
   - Serial: `TEST-MAC-001`
   - Type: macOS Device
   - Status: Active
4. Return to client 360 view

**Expected Results:**
- [ ] New asset appears in client's asset list
- [ ] Asset count updated (if displayed)
- [ ] No page refresh required (if using WebSockets) OR refresh shows update

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 7.2: Contact Deactivation → Software Assignment Check

**Steps:**
1. Navigate to Test Contact Two (or one with software assigned)
2. Deactivate/disable the contact
3. Check the software subscription assignment list

**Expected Results:**
- [ ] Contact status changes to inactive
- [ ] (If implemented) Software assignment is auto-revoked
- [ ] Assignment count decrements appropriately
- [ ] OR: Warning displays about active software assignments

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

### Test 7.3: Widget Registry - Module Sections Display

**Steps:**
1. Navigate to Client 360 view
2. Count the number of module sections/widgets visible:
   - [ ] Basic Info (CRM)
   - [ ] Contacts (CRM)
   - [ ] Assets (AssetManagement)
   - [ ] Software (SoftwareSubscriptions)
   - [ ] Invoices/Billing (PIB)
   - [ ] Credits (PIB)
   - [ ] Contracts (ContractManager)

**Expected Results:**
- [ ] All enabled modules display their widgets
- [ ] No "undefined" or error placeholders
- [ ] Widgets load data without errors
- [ ] Empty states display gracefully (no broken UI)

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

## Section 8: DevFeedback Module (5 minutes)

> **Goal:** Verify the developer feedback system works.

### Test 8.1: Submit Feedback

**Steps:**
1. Look for feedback button (usually floating on page)
2. Click to open feedback form
3. Enter:
   - Type: Bug Report (or available option)
   - Description: "Test feedback submission from manual QA"
4. Submit

**Expected Results:**
- [ ] Feedback form appears
- [ ] Form submits successfully
- [ ] Confirmation message displays
- [ ] Current page URL is captured (if visible)

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

## Section 9: Cleanup (5 minutes)

### Test 9.1: Delete Test Data (Optional)

**Steps:**
1. Navigate to test assets and delete them
2. Navigate to test client and delete (or mark as test/inactive)

**Expected Results:**
- [ ] Assets can be deleted
- [ ] (If soft delete) Records marked as deleted
- [ ] Associated data handles deletion gracefully

**Issues Found:**
```
_____________________________________________
_____________________________________________
```

---

## Summary Checklist

### Modules Tested

| Module | Passed | Failed | Blocked |
|--------|--------|--------|---------|
| CRM | ☐ | ☐ | ☐ |
| AssetManagement | ☐ | ☐ | ☐ |
| SoftwareSubscriptions | ☐ | ☐ | ☐ |
| ContractManager | ☐ | ☐ | ☐ |
| PIB | ☐ | ☐ | ☐ |
| ClientPortal | ☐ | ☐ | ☐ |
| DevFeedback | ☐ | ☐ | ☐ |

### Critical Issues Found

```
1. ___________________________________________________
2. ___________________________________________________
3. ___________________________________________________
4. ___________________________________________________
5. ___________________________________________________
```

### High-Value Improvements Identified

```
1. ___________________________________________________
2. ___________________________________________________
3. ___________________________________________________
```

---

## Test Completion Sign-Off

- **Tester Name:** ___________________
- **Date Completed:** ___________________
- **Time Spent:** ___________________
- **Environment:** ___________________
- **Build/Version:** ___________________

### Overall Assessment

- [ ] **Pass** - Core functionality works as expected
- [ ] **Pass with Issues** - Functional but issues noted
- [ ] **Fail** - Critical functionality broken

**Notes:**
```
_____________________________________________
_____________________________________________
_____________________________________________
```
