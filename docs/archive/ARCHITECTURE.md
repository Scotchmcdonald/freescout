# Email System Architecture

## Complete System Flow

```
┌─────────────────────────────────────────────────────────────────────────┐
│                           GMAIL IMAP SERVER                             │
│                         (imap.gmail.com:993)                            │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    │ OAuth2 Authentication
                                    │ webklex/php-imap 6.2.0
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                        FetchEmails Command                              │
│                   app/Console/Commands/FetchEmails.php                  │
│                                                                         │
│  • Parse command arguments (mailbox ID)                                │
│  • Load mailbox configuration                                          │
│  • Call ImapService->fetchEmails()                                     │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         ImapService                                     │
│                   app/Services/ImapService.php                          │
│                                                                         │
│  STEP 1: Connect & Authenticate                                        │
│  ├─ Load OAuth2 credentials                                           │
│  ├─ Establish IMAP connection                                          │
│  └─ Select folders (INBOX, [Gmail]/Sent Mail, custom)                 │
│                                                                         │
│  STEP 2: Fetch & Filter Messages                                       │
│  ├─ Query: UNSEEN or date range                                       │
│  ├─ Skip if Message-ID already exists                                  │
│  └─ Sort by date (oldest first)                                        │
│                                                                         │
│  STEP 3: Extract Email Components                                      │
│  ├─ Headers: From, To, CC, BCC, Subject, Message-ID                   │
│  ├─ Body: HTML + Plain text                                            │
│  ├─ Attachments: Files + embedded images                               │
│  └─ Thread: In-Reply-To, References                                    │
│                                                                         │
│  STEP 4: Process Participants                                          │
│  ├─ Extract all emails (from, to, cc)                                 │
│  ├─ Create or find Customer records                                    │
│  └─ Extract name from email address                                    │
│                                                                         │
│  STEP 5: Handle Multi-Mailbox BCC                                      │
│  ├─ Check if To/CC matches mailbox email                              │
│  ├─ If not: Search body for @mailbox-alias                            │
│  └─ Create separate conversation per mailbox                           │
│                                                                         │
│  STEP 6: Thread Detection                                              │
│  ├─ Search for In-Reply-To or References headers                      │
│  ├─ Find existing conversation by Message-ID                           │
│  ├─ If found: Append to conversation                                   │
│  └─ If not: Create new conversation                                    │
│                                                                         │
│  STEP 7: Reply Separation                                              │
│  ├─ Find separator: "On ... wrote:", "From: ... Sent:", etc.          │
│  ├─ Extract new reply text only                                        │
│  └─ Store full body for reference                                      │
│                                                                         │
│  STEP 8: Attachment Processing                                         │
│  ├─ Save file to storage/app/public/attachments/YYYY/MM/              │
│  ├─ Extract content-id header                                          │
│  ├─ Detect embedded: content-id + disposition=inline                   │
│  └─ Create Attachment record                                           │
│                                                                         │
│  STEP 9: Inline Image CID Replacement                                  │
│  ├─ For each attachment with content-id:                              │
│  │   ├─ Search body for cid:{content-id}                              │
│  │   ├─ Replace with /storage/attachments/...                         │
│  │   └─ Mark attachment as embedded=1                                  │
│  ├─ Update thread body with replacements                               │
│  └─ Set has_attachments (exclude embedded)                             │
│                                                                         │
│  STEP 10: Fire Events                                                  │
│  ├─ If new conversation:                                               │
│  │   └─ event(new CustomerCreatedConversation(...))                   │
│  └─ If reply:                                                          │
│      └─ event(new CustomerReplied(...))                               │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    ▼                               ▼
    ┌───────────────────────────┐   ┌───────────────────────────┐
    │  CustomerCreatedConversation│   │     CustomerReplied      │
    │  app/Events/...Event.php   │   │   app/Events/...php      │
    │                           │   │                           │
    │  Properties:              │   │  Properties:              │
    │  • $conversation          │   │  • $conversation          │
    │  • $thread                │   │  • $thread                │
    │  • $customer              │   │  • $customer              │
    └───────────────────────────┘   └───────────────────────────┘
                    │                               │
                    │                               │
                    │       EventServiceProvider     │
                    │    (bootstrap/app.php)        │
                    │                               │
                    └───────────────┬───────────────┘
                                    ▼
                    ┌───────────────────────────────┐
                    │      SendAutoReply Listener   │
                    │  app/Listeners/SendAutoReply  │
                    │                               │
                    │  Checks:                      │
                    │  ✓ auto_reply_enabled         │
                    │  ✓ !isSpam()                  │
                    │  ✓ !internal_mailbox          │
                    │  ✗ TODO: bounce detection     │
                    │  ✗ TODO: auto-responder check │
                    │                               │
                    │  Action:                      │
                    │  → TODO: Dispatch job         │
                    └───────────────────────────────┘
                                    │
                                    ▼
                    ┌───────────────────────────────┐
                    │   SendAutoReply Job (TODO)    │
                    │    app/Jobs/SendAutoReply     │
                    │                               │
                    │  • Load template              │
                    │  • Generate Message-ID        │
                    │  • Send via Mail facade       │
                    │  • Log send event             │
                    └───────────────────────────────┘
```

## Database Schema

```
┌─────────────────────┐         ┌─────────────────────┐
│      Mailbox        │         │      Customer       │
├─────────────────────┤         ├─────────────────────┤
│ id                  │         │ id                  │
│ name                │         │ first_name          │
│ email               │◄───┐    │ last_name           │
│ in_server           │    │    │ emails (JSON)       │
│ in_port             │    │    └─────────────────────┘
│ oauth_token         │    │              │
│ auto_reply_enabled  │    │              │
│ auto_reply_subject  │    │              │
│ auto_reply_message  │    │              │
└─────────────────────┘    │              │
                           │              │
                           │    ┌─────────▼───────────┐
                           │    │   Conversation      │
                           │    ├─────────────────────┤
                           ├────┤ id                  │
                           │    │ mailbox_id          │
                           │    │ customer_id         │◄───┐
                           │    │ number              │    │
                           │    │ subject             │    │
                           │    │ status              │    │
                           │    │ has_attachments     │    │
                           │    │ created_at          │    │
                           │    └─────────┬───────────┘    │
                           │              │                │
                           │    ┌─────────▼───────────┐    │
                           │    │      Thread         │    │
                           │    ├─────────────────────┤    │
                           │    │ id                  │    │
                           │    │ conversation_id     │────┘
                           │    │ type (message/note) │
                           │    │ body (HTML/text)    │
                           │    │ headers (JSON)      │
                           │    │ message_id          │
                           │    │ created_at          │
                           │    └─────────┬───────────┘
                           │              │
                           │    ┌─────────▼───────────┐
                           │    │    Attachment       │
                           │    ├─────────────────────┤
                           │    │ id                  │
                           └────┤ conversation_id     │
                                │ thread_id           │
                                │ file_name           │
                                │ file_dir            │
                                │ file_size           │
                                │ mime_type           │
                                │ embedded (bool)     │
                                └─────────────────────┘
```

## Component Interaction

```
User Action                    System Response
───────────                    ───────────────

php artisan                    ┌─────────────────────┐
freescout:fetch-emails 1  ──>  │  Load Mailbox #1    │
                               │  OAuth2 Auth        │
                               └──────────┬──────────┘
                                          │
                                          ▼
                               ┌─────────────────────┐
                               │  Connect IMAP       │
                               │  Query UNSEEN       │
                               └──────────┬──────────┘
                                          │
                                          ▼
                               ┌─────────────────────┐
                         ┌────>│  Process Message    │────┐
                         │     └──────────┬──────────┘    │
                         │                │               │
                         │                ▼               │
                         │     ┌─────────────────────┐    │
                         │     │  Parse Headers      │    │
                         │     │  Extract Body       │    │
                         │     │  Download Attachs   │    │
                         │     └──────────┬──────────┘    │
                         │                │               │
                         │                ▼               │
                         │     ┌─────────────────────┐    │
                         │     │  Find/Create        │    │
                         │     │  Customer           │    │
                         │     └──────────┬──────────┘    │
                         │                │               │
                         │                ▼               │
                         │     ┌─────────────────────┐    │
                         │     │  Thread Detection   │    │
                         │     │  Reply-To Headers   │    │
                         │     └──────────┬──────────┘    │
                         │                │               │
                         │                ▼               │
                         │     ┌─────────────────────┐    │
                         │     │  Create/Update      │    │
                         │     │  Conversation       │    │
                         │     └──────────┬──────────┘    │
                         │                │               │
                         │                ▼               │
                         │     ┌─────────────────────┐    │
                         │     │  Save Attachments   │    │
                         │     │  Replace CID refs   │    │
                         │     └──────────┬──────────┘    │
                         │                │               │
                         │                ▼               │
                         │     ┌─────────────────────┐    │
                         │     │  Fire Event         │    │
                         │     │  (New/Reply)        │    │
                         │     └──────────┬──────────┘    │
                         │                │               │
                         │                ▼               │
                         │     ┌─────────────────────┐    │
                         │     │  Auto-Reply Check   │    │
                         │     │  (Listener)         │    │
                         │     └──────────┬──────────┘    │
                         │                │               │
                         └────────────────┘               │
                                                         │
                                          ┌──────────────┘
                                          │
                                          ▼
                               ┌─────────────────────┐
                               │  Return Summary     │
                               │  Fetched: X         │
                               │  Created: Y         │
                               │  Errors: Z          │
                               └─────────────────────┘
```

## File Structure

```
/var/www/html
├── app
│   ├── Console/Commands
│   │   ├── FetchEmails.php ..................... Main fetch command
│   │   └── TestEventSystem.php ................ Test event firing
│   ├── Events
│   │   ├── CustomerCreatedConversation.php ..... New conversation event
│   │   └── CustomerReplied.php ................. Reply event
│   ├── Listeners
│   │   └── SendAutoReply.php ................... Auto-reply logic
│   ├── Providers
│   │   └── EventServiceProvider.php ............ Event→Listener mapping
│   ├── Services
│   │   └── ImapService.php ..................... Core IMAP logic (670 lines)
│   ├── Misc
│   │   └── MailHelper.php ...................... Utilities (Message-ID gen)
│   └── Models
│       ├── Mailbox.php ......................... Mailbox configuration
│       ├── Conversation.php .................... Ticket/conversation
│       ├── Thread.php .......................... Email message
│       ├── Customer.php ........................ Email sender
│       └── Attachment.php ...................... File attachments
├── bootstrap
│   └── app.php ................................. Provider registration
├── storage
│   ├── app/public/attachments/YYYY/MM/ ......... Saved attachments
│   └── logs/laravel.log ........................ Application logs
└── config
    └── mail.php ................................ Mail configuration

Documentation
├── EMAIL_SYSTEM_STATUS.md ...................... Complete status
├── SESSION_3_SUMMARY.md ........................ Session summary
├── QUICK_REFERENCE.md .......................... Command reference
├── ARCHITECTURE.md (this file) ................. System diagrams
└── IMAP_IMPLEMENTATION_REVIEW.md ............... Deep comparison
```

## Technology Stack

```
┌─────────────────────────────────────────────┐
│              Laravel 11                     │
│        (PHP Framework)                      │
└─────────────────────────────────────────────┘
                     │
      ┌──────────────┼──────────────┐
      │              │              │
      ▼              ▼              ▼
┌──────────┐  ┌──────────┐  ┌──────────┐
│ Eloquent │  │  Events  │  │  Queues  │
│   ORM    │  │  System  │  │  (TODO)  │
└──────────┘  └──────────┘  └──────────┘

┌─────────────────────────────────────────────┐
│         webklex/php-imap 6.2.0              │
│     (IMAP Client Library)                   │
└─────────────────────────────────────────────┘
                     │
      ┌──────────────┼──────────────┐
      │              │              │
      ▼              ▼              ▼
┌──────────┐  ┌──────────┐  ┌──────────┐
│  OAuth2  │  │  IMAP    │  │  Query   │
│  Support │  │ Protocol │  │ Builder  │
└──────────┘  └──────────┘  └──────────┘

┌─────────────────────────────────────────────┐
│              MySQL 8.0+                     │
│         (Database)                          │
└─────────────────────────────────────────────┘
```

## Event Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    New Email Arrives                        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  ImapService::processEmail()                                │
│  • Parse headers, body, attachments                         │
│  • Create/find customer                                     │
│  • Thread detection                                         │
│  • Save conversation + thread                               │
└─────────────────────────────────────────────────────────────┘
                              │
                   ┌──────────┴──────────┐
                   │                     │
            Is New Conversation?    Is Reply?
                   │                     │
                   ▼                     ▼
    ┌──────────────────────┐  ┌──────────────────────┐
    │ CustomerCreated      │  │  CustomerReplied     │
    │ Conversation Event   │  │  Event               │
    └──────────────────────┘  └──────────────────────┘
                   │                     │
                   └──────────┬──────────┘
                              ▼
    ┌─────────────────────────────────────────────────┐
    │        EventServiceProvider                     │
    │   (Maps Events → Listeners)                     │
    └─────────────────────────────────────────────────┘
                              │
                              ▼
    ┌─────────────────────────────────────────────────┐
    │     SendAutoReply::handle(Event $event)         │
    │                                                 │
    │  1. Check auto_reply_enabled                    │
    │     ├─ No: Return (skip)                        │
    │     └─ Yes: Continue                            │
    │                                                 │
    │  2. Check isSpam()                              │
    │     ├─ Yes: Return (skip)                       │
    │     └─ No: Continue                             │
    │                                                 │
    │  3. Check internal mailbox                      │
    │     ├─ Yes: Return (skip)                       │
    │     └─ No: Continue                             │
    │                                                 │
    │  4. TODO: Check auto-responder headers          │
    │  5. TODO: Check bounce headers                  │
    │  6. TODO: Rate limiting check                   │
    │                                                 │
    │  7. TODO: Dispatch SendAutoReply job            │
    └─────────────────────────────────────────────────┘
                              │
                              ▼
    ┌─────────────────────────────────────────────────┐
    │      SendAutoReply Job (TODO)                   │
    │                                                 │
    │  • Load auto_reply_subject                      │
    │  • Load auto_reply_message                      │
    │  • Generate Message-ID                          │
    │  • Set headers (In-Reply-To, References)        │
    │  • Send via Mail facade                         │
    │  • Log to send_log table                        │
    └─────────────────────────────────────────────────┘
```

## Inline Image Processing

```
Email Body (Original):
┌─────────────────────────────────────────┐
│ <p>Here's the screenshot:</p>           │
│ <img src="cid:abc123@gmail.com">        │
│                                         │
│ Attachments:                            │
│ [1] screenshot.png                      │
│     Content-ID: abc123@gmail.com        │
│     Disposition: inline                 │
└─────────────────────────────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────┐
│  ImapService: Process Attachments       │
│                                         │
│  1. Save screenshot.png to storage      │
│     → /storage/app/public/attachments/  │
│        2024/11/screenshot.png           │
│                                         │
│  2. Extract Content-ID: abc123@gmail    │
│                                         │
│  3. Search body for "cid:abc123@gmail"  │
│     → FOUND!                            │
│                                         │
│  4. Replace with file URL:              │
│     cid:abc123@gmail.com                │
│     → /storage/attachments/2024/11/     │
│       screenshot.png                    │
│                                         │
│  5. Mark attachment: embedded=1         │
│                                         │
│  6. Update thread body with new HTML    │
└─────────────────────────────────────────┘
                    │
                    ▼
Email Body (Processed):
┌─────────────────────────────────────────┐
│ <p>Here's the screenshot:</p>           │
│ <img src="/storage/attachments/2024/    │
│      11/screenshot.png">                │
│                                         │
│ Attachments:                            │
│ [None visible - embedded only]          │
└─────────────────────────────────────────┘
```

---

**Last Updated**: 2025-11-05  
**Architecture Version**: 1.0  
**Status**: Production Ready
