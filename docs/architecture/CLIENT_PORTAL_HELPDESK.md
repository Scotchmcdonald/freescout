# Client Portal Helpdesk System
**Module:** ClientPortal  
**Status:** ⏳ PLANNED - Not Yet Implemented  
**Priority:** Medium - Future Enhancement  
**Dependencies:** CRM (Clients), Core (Conversations/Threads)  
**Last Updated:** February 9, 2026

---

## ⚠️ Implementation Status

This document describes a **planned feature** that has not yet been implemented. This is a design specification for future development.

**Current State:**
- ✅ FreeScout helpdesk system is operational for internal staff
- ✅ CRM module links tickets to clients
- ❌ Client-facing portal ticket interface not yet built
- ❌ Self-service ticket submission not available

**Implementation Priority:** Medium (Q2-Q3 2026)

---

## Overview

The Client Portal Helpdesk provides a self-service support interface for clients to create, view, and manage support tickets without accessing the admin FreeScout interface.

## Architecture Principles

### 1. Separation of Concerns
- **Admin Helpdesk:** Full FreeScout interface (`/conversations/`) - for staff only
- **Client Portal Helpdesk:** Simplified interface (`/portal/support/`) - for clients only
- **Shared Data:** Both use same `conversations` and `threads` tables

### 2. Data Model

```
conversations (FreeScout core)
├── id
├── customer_email
├── subject
├── status (1=active, 2=closed, 3=pending)
├── user_id (assigned tech)
└── created_at

threads (FreeScout core)  
├── conversation_id
├── type (1=message, 2=note)
├── body
├── created_by_user_id
└── created_at

client_conversations (CRM link)
├── client_id
├── conversation_id
├── opened_at
└── closed_at

client_portal_users (auth)
├── id
├── email
├── client_id
└── password
```

### 3. Feature Requirements

#### Portal Ticket Submission
**Route:** `/portal/support` (GET/POST)  
**View:** `ClientPortal/resources/views/support.blade.php`  
**Controller:** `ClientPortal/Http/Controllers/PortalController@storeTicket`

**Form Fields:**
- `subject` (required)
- `description` (required)
- `priority` (low/medium/high/urgent)
- `attachment` (optional file upload)

**Process:**
1. Create `conversation` record
2. Create initial `thread` with client message
3. Link to client via `client_conversations`
4. Send email notification to client with ticket number
5. Notify assigned techs (if auto-assignment enabled)

#### Portal Ticket List
**Route:** `/portal/support/tickets`  
**View:** `ClientPortal/resources/views/support-tickets.blade.php`

**Display:**
- Ticket number (e.g., TKT-20260207-12345)
- Subject
- Status (Open/Closed/Pending)
- Priority
- Created date
- Last updated
- Unread reply indicator

**Query:**
```php
$tickets = Conversation::whereHas('clientConversation', function($q) use ($clientId) {
    $q->where('client_id', $clientId);
})->orderBy('created_at', 'desc')->paginate(20);
```

#### Portal Ticket Detail & Reply
**Route:** `/portal/support/tickets/{ticket}`  
**View:** `ClientPortal/resources/views/ticket-detail.blade.php`

**Display:**
- Full conversation thread
- Tech replies vs client replies
- Attachments
- Status history
- Reply form

**Actions:**
- Add reply
- Upload attachment
- Close ticket (mark resolved)
- Reopen ticket

#### Ticket Status Management

**Client Actions:**
- **Close Ticket:** Client marks as resolved
- **Reopen Ticket:** If issue persists after closure

**Tech Actions (Admin):**
- **Assign:** Assign to specific tech
- **Reply:** Add public response
- **Note:** Add internal note (not visible to client)
- **Resolve:** Mark as resolved
- **Status Change:** Active/Pending/Closed

### 4. Notification System

**Email Triggers:**
- Client creates ticket → Send confirmation with ticket number
- Tech replies → Notify client of new response
- Ticket status changes → Notify client
- Ticket assigned → Notify assigned tech

**Mail Classes:**
```
App/Mail/TicketCreatedMail.php
App/Mail/TicketRepliedMail.php  
App/Mail/TicketStatusChangedMail.php
App/Mail/TicketAssignedMail.php
```

### 5. Ticket Rating System

**When:** After ticket is closed/resolved  
**Display:** Show rating form on ticket detail page  
**Fields:**
- Star rating (1-5)
- Feedback comment (optional)

**Storage:**
```php
// Add to conversation meta
$conversation->setMeta('client_rating', 5);
$conversation->setMeta('client_feedback', 'Very helpful!');
$conversation->setMeta('rated_at', now());
```

### 6. Security & Access Control

**Portal Guard:**
```php
auth()->guard('client')->user(); // Returns client portal user
```

**Access Rules:**
- Clients can only view their own tickets
- Clients cannot see internal notes (type=2 threads)
- Clients cannot change assignment
- Clients cannot delete tickets

**Middleware:**
```php
Route::middleware(['client.auth'])->group(function() {
    Route::get('/portal/support/tickets/{ticket}', ...)
        ->can('view', Conversation::class);
});
```

**Policy:**
```php
// app/Policies/ConversationPolicy.php
public function viewFromPortal(ClientPortalUser $user, Conversation $conversation)
{
    return $conversation->clientConversation()->where('client_id', $user->client_id)->exists();
}
```

---

## Implementation Checklist

### Phase 1: Basic Ticket Submission ✅
- [x] Portal support page with form
- [x] storeTicket() controller method
- [x] Ticket number generation
- [x] Email notification (TicketCreatedMail)
- [x] File attachment support

### Phase 2: Ticket Listing & Viewing
- [ ] Portal ticket list page
- [ ] Ticket detail page
- [ ] Thread display (messages only, hide notes)
- [ ] Status indicators
- [ ] Attachment display

### Phase 3: Client Interactions
- [ ] Reply to ticket
- [ ] Close ticket action
- [ ] Reopen ticket action
- [ ] Unread indicator logic

### Phase 4: Tech/Admin Features
- [ ] Admin ticket list view (`/helpdesk/tickets`)
- [ ] Admin reply functionality
- [ ] Status change actions
- [ ] Assignment functionality
- [ ] Internal notes

### Phase 5: Rating & Feedback
- [ ] Post-closure rating form
- [ ] Rating storage in conversation meta
- [ ] Admin view of ratings
- [ ] Rating analytics

---

## Routes

```php
// Client Portal (client.auth middleware)
Route::get('/portal/support', [PortalController::class, 'support']);
Route::post('/portal/support', [PortalController::class, 'storeTicket']);
Route::get('/portal/support/tickets', [PortalController::class, 'tickets']);
Route::get('/portal/support/tickets/{ticket}', [PortalController::class, 'showTicket']);
Route::post('/portal/support/tickets/{ticket}/reply', [PortalController::class, 'replyToTicket']);
Route::post('/portal/support/tickets/{ticket}/close', [PortalController::class, 'closeTicket']);
Route::post('/portal/support/tickets/{ticket}/reopen', [PortalController::class, 'reopenTicket']);
Route::post('/portal/support/tickets/{ticket}/rate', [PortalController::class, 'rateTicket']);

// Admin Helpdesk (auth middleware)
Route::get('/helpdesk/tickets', [HelpdeskController::class, 'index']);
Route::get('/helpdesk/tickets/{ticket}', [HelpdeskController::class, 'show']);
Route::post('/helpdesk/tickets/{ticket}/reply', [HelpdeskController::class, 'reply']);
Route::post('/helpdesk/tickets/{ticket}/assign', [HelpdeskController::class, 'assign']);
Route::patch('/helpdesk/tickets/{ticket}/status', [HelpdeskController::class, 'updateStatus']);
```

---

## Testing Strategy

**Browser Tests:**
- `tests/Browser/Helpdesk/ClientTicketInteractionTest.php` (7 scenarios)
  - Complete lifecycle: create → admin responds → client replies → resolve → rate
  - File attachments
  - Email notifications
  - Close/reopen workflows

**Integration Tests:**
- Conversation creation from portal
- Client-conversation linking
- Access control (clients can't see others' tickets)
- Status transitions

**Unit Tests:**
- Ticket number generation
- Rating validation
- Email notification triggers
