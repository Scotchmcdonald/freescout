# Code Coverage Analysis & Test Implementation Plan

**Generated:** 2025-11-16  
**Coverage Run:** Unit Tests Only (ConsoleCommandsTest skipped during coverage)  
**Total Coverage:** 2.28% lines, 2.93% methods, 0.00% classes

> **📋 See Also:** [COVERAGE_TARGETS_BY_CLASS.md](./COVERAGE_TARGETS_BY_CLASS.md) - Detailed tracking sheet with test counts per class

---

## Executive Summary

### Coverage Commitments

| Phase | Minimum Class Coverage | Line Coverage | Method Coverage | Deliverable |
|-------|----------------------|---------------|-----------------|-------------|
| **Phase 1** | **50% every class** | 40%+ | 50%+ | All classes have baseline tests |
| **Phase 2** | **80% every class** | 70%+ | 80%+ | Production-ready coverage |
| **Phase 3** | **95% every class** | 85%+ | 90%+ | Comprehensive coverage |

**Non-Negotiable Requirements:**
- ✅ After Phase 1: ZERO classes remain at 0% coverage
- ✅ After Phase 2: ZERO classes below 80% coverage
- ✅ Every critical method (CRAP > 500) has comprehensive tests by end of Phase 1

### Overall Coverage Statistics

| Metric | Coverage | Count |
|--------|----------|-------|
| **Lines** | 2.28% | 96 / 4,216 |
| **Methods** | 2.93% | 12 / 410 |
| **Classes** | 0.00% | 0 / 115 |

### Key Findings

1. **Critical Risk Areas:** Services layer has highest complexity with 0% coverage
2. **HTTP Controllers:** 1,618 untested lines - largest untested area
3. **Models:** Highest existing coverage at 21.99% (86/391 lines)
4. **Event System:** Completely untested (0% across 11 classes)

---

## Top 20 Highest CRAP Score Classes (Prioritized for Testing)

| Rank | Class | CRAP Score | Coverage | Lines | Complexity | Priority |
|------|-------|-----------|----------|-------|-----------|----------|
| 1 | **ImapService** | 34,410 | 0% | 0/788 | 185 | CRITICAL |
| 2 | **ConversationController** | 6,642 | 0% | 0/1,618 | 81 | CRITICAL |
| 3 | **MailboxController** | 2,652 | 0% | 0/51 | 51 | HIGH |
| 4 | **MailHelper** | 2,070 | 0% | 0/45 | 45 | HIGH |
| 5 | **SettingsController** | 1,640 | 0% | 0/40 | 40 | HIGH |
| 6 | **Customer** (Model) | 1,576 | 11% | 86/391 | 47 | HIGH |
| 7 | **SystemController** | 1,190 | 0% | 0/34 | 34 | HIGH |
| 8 | **UserController** | 992 | 0% | 0/31 | 31 | MEDIUM |
| 9 | **SmtpService** | 812 | 0% | 0/28 | 28 | MEDIUM |
| 10 | **ModuleUpdate** (Command) | 620 | 1% | 1/25 | 25 | MEDIUM |
| 11 | **SendNotificationToUsers** (Job) | 600 | 0% | 0/24 | 24 | MEDIUM |
| 12 | **CustomerController** | 462 | 0% | 0/21 | 21 | MEDIUM |
| 13 | **MailboxPolicy** | 462 | 0% | 0/21 | 21 | MEDIUM |
| 14 | **ModuleInstall** (Command) | 396 | 2% | 4/20 | 20 | MEDIUM |
| 15 | **CheckRequirements** (Command) | 380 | 0% | 0/19 | 19 | MEDIUM |
| 16 | **ConversationPolicy** | 306 | 0% | 0/17 | 17 | LOW |
| 17 | **SendNotificationToUsers** (Listener) | 210 | 0% | 0/14 | 14 | LOW |
| 18 | **ModuleBuild** (Command) | 182 | 0% | 0/13 | 13 | LOW |
| 19 | **ModulesController** | 182 | 0% | 0/13 | 13 | LOW |
| 20 | **SendAutoReply** (Job) | 182 | 0% | 0/13 | 13 | LOW |

---

## Top 20 Highest CRAP Score Methods

| Rank | Method | CRAP Score | Coverage | Complexity | Class |
|------|--------|-----------|----------|-----------|-------|
| 1 | **processMessage** | 6,162 | 0% | 78 | ImapService |
| 2 | **ModuleUpdate::handle** | 600 | 0% | 25 | Console Command |
| 3 | **getAddressesWithNames** | 552 | 0% | 23 | ImapService |
| 4 | **SendNotificationToUsers::handle** | 506 | 0% | 22 | Job |
| 5 | **replaceMailVars** | 506 | 0% | 22 | MailHelper |
| 6 | **parseAddresses** | 420 | 0% | 20 | ImapService |
| 7 | **Customer::create** | 342 | 0% | 19 | Model |
| 8 | **Customer::setData** | 272 | 0% | 17 | Model |
| 9 | **MailboxController::ajax** | 240 | 0% | 15 | Controller |
| 10 | **ImapService::fetchEmails** | 240 | 0% | 15 | Service |
| 11 | **SendNotificationToUsers::handle** | 210 | 0% | 14 | Listener |
| 12 | **SmtpService::validateSettings** | 210 | 0% | 14 | Service |
| 13 | **SendAutoReply::handle** | 182 | 0% | 13 | Listener |
| 14 | **SendReplyToCustomer::handle** | 182 | 0% | 13 | Listener |
| 15 | **CreateUser::handle** | 156 | 0% | 12 | Console Command |
| 16 | **SystemController::ajax** | 156 | 0% | 12 | Controller |
| 17 | **ModuleInstall::createModulePublicSymlink** | 132 | 0% | 11 | Console Command |
| 18 | **SystemController::diagnostics** | 132 | 0% | 11 | Controller |
| 19 | **SendAutoReply::handle** | 132 | 0% | 11 | Job |
| 20 | **FetchEmails::handle** | 110 | 0% | 10 | Console Command |

---

## Coverage by Directory

| Directory | Line Coverage | Method Coverage | Class Coverage | Lines | Methods | Classes |
|-----------|--------------|----------------|---------------|-------|---------|---------|
| **Console** | 0.86% | 14.81% | 0% | 4/466 | 4/27 | 0/15 |
| **Events** | 0.00% | 0.00% | 0% | 0/70 | 0/20 | 0/11 |
| **Http** | 0.00% | 0.00% | 0% | 0/1,618 | 0/110 | 0/22 |
| **Jobs** | 0.00% | 0.00% | 0% | 0/249 | 0/12 | 0/5 |
| **Listeners** | 0.00% | 0.00% | 0% | 0/165 | 0/15 | 0/14 |
| **Mail** | 0.00% | 0.00% | 0% | 0/145 | 0/27 | 0/8 |
| **Misc** | 0.00% | 0.00% | 0% | 0/121 | 0/13 | 0/3 |
| **Models** | 21.99% | 5.00% | 0% | 86/391 | 6/120 | 0/18 |
| **Observers** | 0.00% | 0.00% | 0% | 0/77 | 0/17 | 0/6 |
| **Policies** | 0.00% | 0.00% | 0% | 0/97 | 0/23 | 0/5 |
| **Providers** | 25.00% | 33.33% | 0% | 6/24 | 2/6 | 0/3 |
| **Services** | 0.00% | 0.00% | 0% | 0/788 | 0/17 | 0/2 |
| **View** | 0.00% | 0.00% | 0% | 0/4 | 0/2 | 0/2 |

---

## Test Implementation Strategy

### Coverage Targets by Phase

| Phase | Class Coverage | Line Coverage | Method Coverage | Business Goal |
|-------|---------------|---------------|-----------------|---------------|
| **Phase 1** | 50%+ all classes | 40%+ overall | 50%+ overall | Critical paths safe |
| **Phase 2** | 80%+ all classes | 70%+ overall | 80%+ overall | Production ready |
| **Phase 3** | 95%+ all classes | 85%+ overall | 90%+ overall | High confidence |

**No class should remain at 0% after Phase 1 completion**

---

### Phase 1: Critical Infrastructure + Baseline Coverage

**Goal:** Reduce critical risk AND ensure every class has minimum 50% coverage  
**Target Coverage:** 40% lines, 50% methods, 50% classes  
**Duration:** 8 weeks (320 hours)

**Priority Groups:**
1. **Critical CRAP (>1000):** ImapService, ConversationController, MailboxController, MailHelper, SettingsController
2. **Zero Coverage Classes:** All 115 classes currently at 0% class coverage
3. **Model Foundations:** Complete coverage for all Models (currently 21.99% partial)
4. **Event System Baseline:** Basic tests for all Events, Listeners, Observers

#### 1.1 ImapService (CRAP: 34,410)
**Why Critical:** Email processing is core functionality, highest complexity

**Test Categories:**
- **Unit Tests:**
  - `parseAddresses()` - Email address parsing (CRAP: 420)
  - `getAddressesWithNames()` - Address extraction (CRAP: 552)
  - `getEncryption()` - Connection security
  - `getMessageHeaders()` - Header parsing (CRAP: 110)
  - `separateReply()` - Reply separation (CRAP: 90)
  
- **Integration Tests:**
  - `processMessage()` - Full message processing (CRAP: 6,162)
    - Edge case: Malformed headers
    - Edge case: Missing sender
    - Edge case: HTML-only emails
    - Edge case: Multiple attachments
    - Edge case: Forwarded messages
    - Edge case: Auto-responder detection
  - `fetchEmails()` - IMAP connection and retrieval (CRAP: 240)
  - `testConnection()` - Connection validation (CRAP: 110)
  - `createClient()` - Client initialization

- **Mock Requirements:**
  - IMAP client library
  - Message objects
  - Folder structures
  - Network failures

**Edge Cases to Cover:**

**Input Validation Edge Cases:**
1. Malformed RFC822 headers (missing colons, extra spaces)
2. Unicode characters in subject/body (emoji, RTL text, combining characters)
3. Nested MIME multipart messages (7+ levels deep)
4. Zero-byte attachments
5. Attachments with no filename
6. Extremely long filenames (255+ chars)
7. Filenames with special characters (null bytes, path traversal attempts)
8. HTML-only emails (no text/plain alternative)
9. Plain text with HTML entities
10. Character encoding issues (UTF-8, Latin-1, Windows-1252, Shift-JIS)

**Business Logic Edge Cases:**
11. Circular reply chains (In-Reply-To pointing to self)
12. Duplicate Message-IDs
13. Messages without From address
14. Messages with multiple From addresses
15. BCC recipients in public headers (privacy leak)
16. Auto-responder loops (vacation replies to vacation replies)
17. Forward chains (Fwd: Fwd: Fwd:)
18. Reply markers in other languages (Re:, RE:, AW:, Ant:, VS:, SV:)

**Configuration Edge Cases:**
19. `in_imap_folders` as null, empty string, single folder, comma-separated list, array
20. Missing IMAP server configuration
21. Invalid port numbers (0, negative, > 65535)
22. Mixed encryption settings (SSL on TLS port)

**Connection/Network Edge Cases:**
23. Connection timeouts (slow network)
24. SSL/TLS negotiation failures (expired cert, self-signed)
25. IMAP AUTH failures (wrong password, locked account)
26. Mailbox quota exceeded
27. Network interruption mid-fetch
28. IMAP IDLE mode disconnection
29. Server returning error in response charset

**Data Integrity Edge Cases:**
30. Database transaction rollback scenarios
31. Concurrent message processing (race conditions)
32. Missing customer record during message processing
33. Orphaned threads without conversation
34. Conversation status changes during processing

---

#### 1.2 ConversationController (CRAP: 6,642)
**Why Critical:** Main user interaction point, 81 methods

**Test Categories:**
- **Feature Tests:**
  - `index()` - Conversation listing with filters
  - `show()` - Single conversation display
  - `store()` - Create new conversation (CRAP: 72)
  - `update()` - Update conversation (CRAP: 20)
  - `reply()` - Add reply to conversation (CRAP: 72)
  - `destroy()` - Delete conversation
  - `ajax()` - AJAX operations (CRAP: 90)
  - `search()` - Search functionality
  - `merge()` - Merge conversations (CRAP: 72)
  - `move()` - Move to different mailbox (CRAP: 20)
  - `changeCustomer()` - Reassign customer (CRAP: 72)
  - `updateThread()` - Edit thread (CRAP: 20)
  
- **Authorization Tests:**
  - Policy checks for view/edit/delete
  - Mailbox access permissions
  - User role restrictions

**Edge Cases:**

**Concurrency & Race Conditions:**
1. Concurrent updates to same conversation (optimistic locking)
2. User A replies while User B changes status
3. Customer replies while agent is merging conversation
4. Multiple agents replying simultaneously
5. Folder counter updates during concurrent operations

**Authorization & Access Control:**
6. User accessing conversation from unassigned mailbox
7. Admin vs non-admin permissions
8. Soft-deleted user accessing old conversations
9. Follower permissions after mailbox reassignment
10. Policy checks bypassed via direct route access

**Data Validation & Business Rules:**
11. Merging conversations with different mailboxes
12. Merging conversations with different customers
13. Moving conversations with active followers
14. Replying to closed/spam conversations
15. Creating conversation without customer
16. Changing customer to one from different channel
17. Setting invalid status transitions
18. Nested CC/BCC with malformed addresses

**File Handling:**
19. Uploading attachments > 10MB
20. Uploading zero-byte files
21. Uploading files with malicious extensions (.php, .exe)
22. File upload interruption
23. Duplicate attachment names
24. Unicode filenames (emoji, non-Latin scripts)

**Search & Query Performance:**
25. Searching with SQL injection attempts
26. Searching with regex special characters
27. Paginating 10,000+ conversations
28. Filtering by multiple criteria simultaneously
29. Sorting by NULL columns
30. Full-text search with stop words

**State Management:**
31. Handling orphaned conversations (no mailbox)
32. Conversations with deleted customers
33. Threads without parent conversation
34. Status changes cascading to related records
35. Soft delete recovery edge cases

**AJAX Operations:**
36. Network timeout during AJAX request
37. Invalid JSON in request
38. CSRF token mismatch
39. Session expiration mid-operation
40. Concurrent AJAX updates

---

#### 1.3 MailboxController (CRAP: 2,652)
**Why Critical:** Mailbox configuration, SMTP/IMAP testing

**Test Categories:**
- **Feature Tests:**
  - `store()` - Create mailbox (CRAP: 30)
  - `update()` - Update mailbox (CRAP: 20)
  - `saveConnectionIncoming()` - IMAP settings
  - `saveConnectionOutgoing()` - SMTP settings
  - `updatePermissions()` - User access
  - `fetchEmails()` - Manual email fetch
  - `ajax()` - AJAX operations (CRAP: 240)
  
- **Integration Tests:**
  - `testConnection()` - Live connection test
  - Email fetching with various providers
  - OAuth flow (Gmail)

**Edge Cases:**
1. Invalid SMTP/IMAP credentials
2. Firewall blocking ports
3. Self-signed SSL certificates
4. OAuth token expiration
5. Mailbox name conflicts
6. Permission inheritance
7. Deleting mailbox with active conversations
8. Changing email settings while fetching

---

#### 1.4 MailHelper (CRAP: 2,070)
**Why Critical:** Email utilities used throughout

**Test Categories:**
- **Unit Tests:**
  - `replaceMailVars()` - Template variable substitution (CRAP: 506)
  - `isAutoResponder()` - Auto-reply detection (CRAP: 110)
  - `parseEmail()` - Email address parsing
  - `sanitizeEmail()` - Email cleanup
  - `formatEmail()` - Email formatting
  - `generateMessageId()` - Unique ID generation
  - `getReplySubject()` - Subject line handling
  - `extractReplyBody()` - Quote removal

**Edge Cases:**
1. Variable recursion ({{var}} containing {{var2}})
2. Undefined variable fallback syntax
3. Malformed variable syntax
4. HTML escaping in variables
5. Auto-responder headers: X-Autoreply, Auto-Submitted, Precedence
6. Vacation messages in multiple languages
7. Out-of-office patterns
8. Message-ID collision handling
9. Reply subject prefixes: Re:, RE:, AW:, Ant:, VS:, SV:
10. Nested reply quotes (>>> >>> >>>)
11. Email validation: RFC822 vs RFC5322
12. Plus addressing (user+tag@example.com)
13. Quoted-printable encoding
14. Base64 encoded subjects
15. Reply body extraction from HTML
16. Signature detection and removal
17. Inline images in replies
18. Variable injection security
19. XSS attempts in email content
20. SQL injection in email search

**Test Count:** 40 tests → Target Coverage: 60%

---

#### 1.5 SettingsController (CRAP: 1,640)
**Why Critical:** System configuration, security settings

**Test Categories:**
- **Feature Tests:**
  - `index()` - Settings page rendering
  - `save()` - Save settings with validation
  - `update()` - Update configuration
  - Settings groups (general, email, security, etc.)
  - Permission-based setting access
  
**Edge Cases:**
1. Invalid configuration values
2. Settings requiring app restart
3. Encrypted setting values
4. Settings affecting running jobs
5. Concurrent settings updates
6. Settings validation failures
7. Rollback on validation error
8. Settings cache invalidation
9. Environment variable overrides
10. Module-specific settings

**Test Count:** 25 tests → Target Coverage: 55%

---

#### 1.6 SystemController (CRAP: 1,190)
**Why Critical:** System diagnostics, health checks

**Test Categories:**
- **Feature Tests:**
  - `diagnostics()` - System health check (CRAP: 132)
  - `ajax()` - AJAX system operations (CRAP: 156)
  - `logs()` - Log viewing
  - `status()` - System status
  - PHP requirements check
  - Database connection check
  - Email sending capability
  - Disk space monitoring

**Edge Cases:**
1. Missing PHP extensions
2. Insufficient permissions
3. Database connection failures
4. Mail server unavailable
5. Disk space critical
6. Queue worker down
7. Scheduler not running
8. Module compatibility issues

**Test Count:** 20 tests → Target Coverage: 50%

---

### Phase 1B: Zero-Coverage Class Baseline (All 115 Classes)

**Goal:** No class remains at 0% - minimum viable test for every class  
**Target:** At least 3-5 tests per class, 50%+ method coverage

#### Console Commands (15 classes, 27 methods, 0% coverage)

**AfterAppUpdate** (CRAP: 56)
- Test command execution
- Test cache clearing
- Test migration checks
- Test module updates
- **Tests:** 5 → Coverage: 55%

**CheckRequirements** (CRAP: 380)
- Test PHP version checks
- Test extension checks (mbstring, openssl, pdo, curl, etc.)
- Test permission checks
- Test missing dependency detection
- Test output formatting
- **Tests:** 8 → Coverage: 60%

**ConfigureGmailMailbox** (CRAP: 72)
- Test OAuth flow
- Test credential storage
- Test connection validation
- Test error handling
- **Tests:** 5 → Coverage: 50%

**CreateUser** (CRAP: 156)
- Test user creation via CLI (CRAP: 156)
- Test password generation
- Test email validation
- Test role assignment
- Test duplicate email handling
- **Tests:** 7 → Coverage: 55%

**FetchEmails** (CRAP: 110)
- Test manual fetch trigger (CRAP: 110)
- Test mailbox selection
- Test error reporting
- Test concurrent fetch prevention
- **Tests:** 6 → Coverage: 55%

**GenerateVars** (CRAP: 20)
- Test variable documentation generation
- Test output file creation
- **Tests:** 3 → Coverage: 50%

**ModuleBuild** (CRAP: 182)
- Test module compilation (CRAP: 182)
- Test asset building
- Test dependency resolution
- **Tests:** 4 → Coverage: 50%

**ModuleInstall** (CRAP: 396)
- Test module installation (CRAP: 272)
- Test symlink creation (CRAP: 132)
- Test database migrations
- Test asset publishing
- Test rollback on failure
- **Tests:** 8 → Coverage: 60%

**ModuleUpdate** (CRAP: 620)
- Test module updates (CRAP: 600)
- Test version comparison
- Test migration execution
- Test cache clearing
- **Tests:** 7 → Coverage: 55%

**TestEventSystem** (CRAP: 12)
- Test event dispatching
- Test listener execution
- **Tests:** 3 → Coverage: 50%

**Update** (CRAP: 110)
- Test application update
- Test version detection
- Test pre-update backup
- **Tests:** 5 → Coverage: 50%

**UpdateFolderCounters** (CRAP: 110)
- Test counter recalculation (CRAP: 110)
- Test orphaned record detection
- Test dry-run mode
- **Tests:** 5 → Coverage: 50%

**Console Command Total:** 66 tests across 15 classes

---

#### Events (11 classes, 20 methods, 0% coverage)

**All Events Need:**
- Constructor test
- Property assignment test
- Serialization test (for queue)
- Event data validation test

**ConversationStatusChanged** (CRAP: 6)
- Test conversation reference
- Test old/new status tracking
- **Tests:** 4 → Coverage: 60%

**ConversationUpdated** (CRAP: 12)
- Test conversation reference
- Test update payload
- **Tests:** 4 → Coverage: 55%

**ConversationUserChanged** (CRAP: 6)
- Test user reassignment tracking
- **Tests:** 4 → Coverage: 55%

**CustomerCreatedConversation** (CRAP: 6)
- Test customer reference
- Test conversation reference
- **Tests:** 4 → Coverage: 55%

**CustomerReplied** (CRAP: 6)
- Test thread reference
- **Tests:** 4 → Coverage: 55%

**NewMessageReceived** (CRAP: 12)
- Test message data
- **Tests:** 4 → Coverage: 55%

**UserAddedNote** (CRAP: 6)
- Test note thread reference
- **Tests:** 4 → Coverage: 55%

**UserCreatedConversation** (CRAP: 6)
- Test user reference
- **Tests:** 4 → Coverage: 55%

**UserDeleted** (CRAP: 2)
- Test user reference
- Test soft delete flag
- **Tests:** 4 → Coverage: 60%

**UserReplied** (CRAP: 6)
- Test reply thread reference
- **Tests:** 4 → Coverage: 55%

**UserInvited** (if exists)
- Test invitation data
- **Tests:** 4 → Coverage: 55%

**Event Total:** 44 tests across 11 classes

---

#### HTTP Controllers (22 classes, 110 methods, 0% coverage)

**Already Planned:** ConversationController (Phase 1.2), MailboxController (Phase 1.3)

**UserController** (CRAP: 992)
- Test index, show, create, store, edit, update, destroy
- Test profile updates
- Test password changes
- Test permission management
- **Tests:** 15 → Coverage: 55%

**CustomerController** (CRAP: 462)
- Test customer CRUD
- Test email management
- Test merge customers
- Test search
- **Tests:** 12 → Coverage: 55%

**ModulesController** (CRAP: 182)
- Test module listing
- Test activate/deactivate
- Test settings per module
- **Tests:** 8 → Coverage: 50%

**DashboardController** (CRAP: 20)
- Test dashboard data loading
- Test widget rendering
- Test metrics calculation
- **Tests:** 5 → Coverage: 50%

**Remaining 17 Controllers:**
- Minimum 5 tests each for basic CRUD
- Target: 50% coverage per controller
- **Tests:** 85 tests

**Controller Total:** 125 tests across 22 classes

---

#### Jobs (5 classes, 12 methods, 0% coverage)

**SendNotificationToUsers** (CRAP: 600)
- Already partially tested (12 existing tests)
- Add failure scenarios
- Add timeout tests
- Add queue delay tests
- **Additional Tests:** 8 → Coverage: 70%

**SendAutoReply** (CRAP: 182)
- Already partially tested (6 existing tests)
- Add template rendering tests
- Add conditional logic tests
- **Additional Tests:** 6 → Coverage: 70%

**SendConversationReply** (CRAP: 90)
- Test reply sending
- Test attachment handling
- Test CC/BCC processing
- Test failure handling
- **Tests:** 8 → Coverage: 60%

**SendReplyToCustomer** (CRAP: 72)
- Test customer email delivery
- Test email formatting
- **Tests:** 6 → Coverage: 55%

**RecalculateFolderCounters** (if exists)
- Test counter accuracy
- Test batch processing
- **Tests:** 5 → Coverage: 55%

**Job Total:** 33 tests (18 existing + 15 new) across 5 classes

---

#### Listeners (14 classes, 15 methods, 0% coverage)

**SendNotificationToUsers** (Listener, CRAP: 210)
- Already tested via LogListenersTest
- Add subscription filtering tests
- Add notification preference tests
- **Additional Tests:** 5 → Coverage: 65%

**SendAutoReply** (Listener, CRAP: 182)
- Already tested via LogListenersTest
- Add auto-reply rule tests
- **Additional Tests:** 5 → Coverage: 65%

**SendReplyToCustomer** (Listener, CRAP: 182)
- Already tested via LogListenersTest
- Add email queue tests
- **Additional Tests:** 5 → Coverage: 65%

**Remaining 11 Listeners:**
- Minimum 4 tests each
- Test event handling
- Test conditional execution
- Test error scenarios
- **Tests:** 44 tests

**Listener Total:** 59 tests across 14 classes

---

#### Mail Classes (8 classes, 27 methods, 0% coverage)

**All Mail Classes Need:**
- Constructor test
- Build method test (envelope, content, attachments)
- Recipient test
- Subject test
- Template rendering test
- Minimum 5 tests each

**UserNotification** - User notification emails
**ConversationNotification** - Conversation updates
**PasswordReset** - Password reset emails
**UserInvite** - User invitation emails
**AutoReply** - Automated responses
**CustomerReply** - Customer reply emails
**ThreadNotification** - Thread update emails
**SystemAlert** - System alert emails

**Mail Total:** 40 tests across 8 classes (5 per class)

---

#### Models (18 classes, 120 methods, 21.99% coverage)

**Goal:** Complete model coverage - all relationships, scopes, mutators, accessors

**Customer** (CRAP: 1,576, 11% coverage)
- Already some tests exist
- Need: create() method (CRAP: 342), setData() (CRAP: 272)
- Need: email relationship tests
- Need: conversation relationship tests
- Need: merge customer tests
- **Additional Tests:** 25 → Target: 70%

**Conversation** (CRAP: 102, 43% coverage)
- Already some tests exist
- Need: updateFolder() (CRAP: 90)
- Need: status change cascades
- Need: follower management
- Need: counter updates
- **Additional Tests:** 20 → Target: 75%

**Thread** (CRAP: 36, 51% coverage)
- Already some tests exist
- Need: type-specific logic
- Need: attachment relationships
- Need: edit tracking
- **Additional Tests:** 15 → Target: 75%

**User** (CRAP: 90, 31% coverage)
- Need: permission checks
- Need: mailbox relationships
- Need: notification preferences
- **Additional Tests:** 20 → Target: 70%

**Mailbox** (CRAP: 72, 61% coverage)
- Need: connection settings
- Need: user assignments
- Need: folder relationships
- **Additional Tests:** 15 → Target: 80%

**Remaining 13 Models:**
- Folder, Email, Attachment, Subscription, Follower, SendLog, ActivityLog, Channel, etc.
- Minimum 10 tests each
- **Tests:** 130 tests

**Model Total:** 225 tests across 18 models

---

#### Observers (6 classes, 17 methods, 0% coverage)

**All Observers Need:**
- Created event test
- Updated event test
- Deleted/Deleting event tests
- Cascade behavior tests
- Transaction rollback tests

**ConversationObserver**
- Test folder counter updates on create/delete
- Test status change side effects
- Test number assignment
- **Tests:** 8 → Coverage: 60%

**ThreadObserver**
- Test conversation counter updates
- Test edited_at tracking
- Test attachment cleanup
- **Tests:** 8 → Coverage: 60%

**UserObserver**
- Already has some tests (UserObserver exists)
- Test folder creation on user create
- Test cleanup on user delete
- Test subscription setup
- **Additional Tests:** 6 → Coverage: 65%

**CustomerObserver**
- Test email normalization
- Test duplicate detection
- **Tests:** 6 → Coverage: 55%

**MailboxObserver**
- Test folder creation
- Test cleanup on delete
- **Tests:** 6 → Coverage: 55%

**AttachmentObserver**
- Already tested (AttachmentObserverTest exists with 2 tests)
- Add file storage tests
- Add cleanup verification
- **Additional Tests:** 6 → Coverage: 70%

**Observer Total:** 40 tests across 6 observers

---

#### Policies (5 classes, 23 methods, 0% coverage)

**MailboxPolicy** (CRAP: 462)
- Test view, create, update, delete permissions
- Test user role checks (admin vs user)
- Test mailbox assignment checks
- **Tests:** 12 → Coverage: 60%

**ConversationPolicy** (CRAP: 306)
- Test view permission (assigned mailbox)
- Test update permission (user assignment)
- Test delete permission
- Test merge permission
- **Tests:** 10 → Coverage: 60%

**UserPolicy**
- Test user management permissions
- Test self-update permissions
- **Tests:** 8 → Coverage: 55%

**CustomerPolicy**
- Test customer access permissions
- **Tests:** 6 → Coverage: 50%

**SystemPolicy**
- Test admin-only actions
- **Tests:** 6 → Coverage: 50%

**Policy Total:** 42 tests across 5 policies

---

#### Services (2 classes, 17 methods, 0% coverage)

**ImapService** - Covered in Phase 1.1

**SmtpService** (CRAP: 812)
- Test validateSettings() (CRAP: 210)
- Test sendEmail()
- Test connection testing
- Test authentication methods
- Test encryption modes
- **Tests:** 15 → Coverage: 60%

**Service Total:** 15 tests (ImapService handled separately)

---

#### Misc & Providers (5 classes, 0% coverage)

**Helper** (if exists)
- Test utility functions
- **Tests:** 10 → Coverage: 50%

**MailHelper** - Covered in Phase 1.4

**Providers:**
- AppServiceProvider, EventServiceProvider, RouteServiceProvider
- Test service registration
- Test event registration
- Test route registration
- **Tests:** 15 across 3 providers → Coverage: 50%

---

### Phase 1 Summary

**Total New Tests in Phase 1:** ~1,100 tests
- Critical Infrastructure: 250 tests
- Console Commands: 66 tests
- Events: 44 tests
- Controllers: 125 tests
- Jobs: 33 tests
- Listeners: 59 tests
- Mail: 40 tests
- Models: 225 tests
- Observers: 40 tests
- Policies: 42 tests
- Services: 15 tests
- Misc/Providers: 25 tests
- Existing tests: ~100 tests

**Expected Coverage After Phase 1:**
- Class Coverage: 50-60% (all 115 classes covered)
- Line Coverage: 40-45%
- Method Coverage: 50-55%
- CRAP Score: Top 10 reduced by 60%

**Validation Criteria:**
- ✅ Zero classes at 0% coverage
- ✅ All critical paths (CRAP > 500) have tests
- ✅ All models above 50%
- ✅ All controllers have basic CRUD tests
- ✅ All events/listeners/observers have tests

---

### Phase 2: Deep Coverage (80%+ Target)
- **Unit Tests:**
  - `replaceMailVars()` - Variable replacement (CRAP: 506)
  - `isAutoResponder()` - Auto-reply detection (CRAP: 110)
  - `parseEmail()` - Email parsing
  - `sanitizeEmail()` - Email sanitization
  - `formatEmail()` - Email formatting
  - `extractReply()` - Reply extraction
  - `generateMessageId()` - ID generation
  - `getMessageIdHash()` - Hash generation

**Edge Cases:**

**Variable Replacement (`replaceMailVars`):**
1. Variables in HTML vs plain text contexts
2. Nested variable syntax `{%user.{%type%}%}`
3. Variables with missing data (customer null, user null)
4. Fallback syntax variations `{%var,fallback=%}` (empty fallback)
5. Fallback with HTML entities `{%var,fallback=<script>%}`
6. Variables with recursion risk (mailbox.fromName calling itself)
7. Variables in JSON data (conversation.meta)
8. Eventy filter integration (module hooks)
9. Escape mode vs non-escape mode differences
10. UTF-8 variables with `nl2br` transformation
11. Variables containing other variable patterns
12. Malformed variable syntax `{%incomplete`, `%}orphan{%`

**Auto-Responder Detection (`isAutoResponder`):**
13. Auto-responder with only X-Autoreply header
14. Auto-submitted: auto-generated vs auto-replied
15. Precedence: list vs bulk vs junk
16. Multiple auto-responder headers (conflicting)
17. Headers with extra whitespace/tabs
18. Case variations (X-AutoReply, x-autoreply, X-AUTOREPLY)
19. Headers split across multiple lines (RFC 822 folding)
20. Missing headers (null input, empty string)

**Email Parsing (`parseEmail`, `sanitizeEmail`, `formatEmail`):**
21. Email with name: "John Doe <john@example.com>"
22. Email with quoted name: "\"Doe, John\" <john@example.com>"
23. Email with special characters in local part: "john+test@example.com"
24. Email with IDN (internationalized domain): "user@münchen.de"
25. Multiple emails comma-separated
26. Malformed emails: "john@", "@example.com", "plaintext"
27. Email with comments: "john(comment)@example.com"
28. Email with IP address: "user@[192.168.1.1]"

**Reply Extraction (`extractReply`):**
29. Reply markers in multiple languages (Re:, AW:, Ant:, VS:, SV:, R:, Odp:)
30. Multiple reply markers: "Re: Fwd: Re:"
31. Reply with quoted text using >
32. Reply with HTML quoted blocks
33. Reply with "On DATE, NAME wrote:" separators
34. Gmail-style "---------- Forwarded message ----------"
35. Outlook-style "_____" separators
36. Nested quotes (4+ levels deep)

**Message ID Generation:**
37. generateMessageId with empty body
38. generateMessageId with null email
39. getMessageIdHash with very large thread ID
40. Hash collisions (multiple threads same hash)

---

### Phase 2: High-Priority Controllers (CRAP 1000-2000)

#### 2.1 SettingsController (CRAP: 1,640)
**Test Types:** Feature tests for settings management
**Focus:** Email settings, system settings, alerts
**Edge Cases:** 
- Invalid SMTP credentials
- Cache clearing during operations
- Migration while system active

#### 2.2 SystemController (CRAP: 1,190)
**Test Types:** Feature + integration tests
**Focus:** Diagnostics, logs, system info
**Edge Cases:**
- Large log files
- Missing extensions
- Permission issues

#### 2.3 UserController (CRAP: 992)
**Test Types:** Feature + authorization tests
**Focus:** User CRUD, permissions, notifications
**Edge Cases:**
- Deleting user with active conversations
- Permission conflicts
- Notification preference inheritance

---

### Phase 3: Service Layer (CRAP 500-1000)

#### 3.1 SmtpService (CRAP: 812)
**Test Categories:**
- Unit: Connection validation, encryption handling
- Integration: Actual SMTP connections
**Edge Cases:**
- STARTTLS upgrade failures
- Authentication methods (PLAIN, LOGIN, CRAM-MD5)
- Port conflicts

#### 3.2 Customer Model (CRAP: 1,576)
**Test Categories:**
- Unit: `create()` (CRAP: 342), `setData()` (CRAP: 272)
- Feature: Full CRUD operations
**Edge Cases:**
- Duplicate email detection
- Merging customers
- Channel association
- Email validation

---

### Phase 4: Jobs & Events (CRAP 200-600)

#### 4.1 SendNotificationToUsers (CRAP: 600)
**Test Types:** Job tests with queue mocking
**Edge Cases:**
- User preferences filtering
- Batch processing
- Failed notifications

#### 4.2 ModuleUpdate (CRAP: 620)
**Test Types:** Console command tests
**Edge Cases:**
- Download failures
- Version conflicts
- Migration errors

#### 4.3 Event System (0% coverage)
**Classes:** All 11 event classes untested
**Test Types:** Unit tests for event construction/broadcasting
**Focus:** ConversationUpdated, NewMessageReceived, UserViewingConversation

---

### Phase 5: Policies & Authorization (CRAP 100-500)

#### 5.1 MailboxPolicy (CRAP: 462)
**Test Types:** Authorization tests
**Focus:** Admin vs user permissions, mailbox access

#### 5.2 ConversationPolicy (CRAP: 306)
**Test Types:** Authorization tests
**Focus:** View, update, delete, move permissions

#### 5.3 ThreadPolicy (CRAP: 132)
**Test Types:** Authorization tests
**Focus:** Edit and delete permissions

---

### Phase 6: Model Logic & Relationships (HIGH VALUE)

**Priority:** HIGH - Models contain critical business logic with 21.99% existing coverage

#### 6.1 Conversation Model (CRAP: 102, Coverage: 43%)
**Why Critical:** Core domain model with complex state management

**Test Categories:**
- **Relationship Tests:**
  - `folder()`, `mailbox()`, `customer()`, `user()` relationships load correctly
  - `threads()` ordering by created_at
  - Eager loading optimization (N+1 prevention)
  - Polymorphic relationships
  
- **Status/State Tests:**
  - `isActive()`, `isClosed()` state checks
  - `STATUS_ACTIVE`, `STATUS_PENDING`, `STATUS_CLOSED`, `STATUS_SPAM` transitions
  - `STATE_DRAFT` vs `STATE_PUBLISHED` filtering
  - Invalid state transitions
  
- **Counter Updates:**
  - `updateFolder()` - folder counter updates (CRAP: 90)
  - `threads_count` accuracy
  - Cascade effects on mailbox counters
  
**Edge Cases:**
1. Conversation with deleted mailbox
2. Conversation with deleted customer
3. Multiple threads with same message-id
4. Status changes affecting folder counts
5. Concurrent counter updates
6. Orphaned conversations (no mailbox_id)
7. Conversations with NULL user_id
8. CC/BCC arrays with malformed data
9. Meta JSON with invalid structure
10. Timestamp consistency (created_at, last_reply_at)

---

#### 6.2 Thread Model (CRAP: 36, Coverage: 51%)
**Why Critical:** Message content storage, type system

**Test Categories:**
- **Type System Tests:**
  - `isCustomerMessage()`, `isUserMessage()`, `isNote()` type checks
  - `TYPE_CUSTOMER`, `TYPE_MESSAGE`, `TYPE_NOTE`, `TYPE_CHAT`, `TYPE_BOUNCE`
  - Type-specific validation rules
  
- **Auto-Responder Detection:**
  - `isAutoResponder()` header analysis (CRAP: 12)
  - `isBounce()` bounce detection
  
- **Content Tests:**
  - HTML body sanitization
  - Plain text formatting
  - Attachment relationships
  - Message-ID uniqueness
  
**Edge Cases:**
1. Thread without conversation
2. Thread created_by_user vs created_by_customer conflicts
3. Headers array with non-standard fields
4. Empty body (both null and empty string)
5. Multiple to/cc/bcc recipients
6. first flag accuracy on conversation
7. has_attachments accuracy
8. Edit history (edited_by_user_id, edited_at)
9. Source tracking (source_via, source_type)
10. opened_at timestamp for read tracking

---

#### 6.3 Customer Model (CRAP: 1,576, Coverage: 11%)
**Why Critical:** Customer creation is complex with high CRAP

**Test Categories:**
- **Factory Method Tests:**
  - `create()` static factory (CRAP: 342)
  - Email validation and deduplication
  - `setData()` bulk assignment (CRAP: 272)
  
- **Name Handling:**
  - `getFullName()` with various name formats
  - `getFirstName()` extraction
  - `getPrimaryEmailAttribute` main email selection
  
- **Email Management:**
  - `emails()` relationship
  - `getMainEmail()` selection logic (CRAP: 12)
  - Multiple email handling
  
- **Relationship Tests:**
  - `conversations()`, `threads()` relationships
  - `channels()` multi-channel support
  - `customerChannels()` pivot data
  
**Edge Cases:**
1. Customer with no emails
2. Customer with 10+ emails
3. Customer name with Unicode/emoji
4. Duplicate email across customers
5. Email case sensitivity
6. Customer with no first/last name
7. Company-only customers (no person name)
8. Phone number format variations
9. Website URL validation
10. Custom fields in meta JSON
11. Photo URL handling
12. Customer merge operations
13. Soft-deleted customer relationships
14. Customer channel conflicts

---

#### 6.4 Mailbox Model (CRAP: 9, Coverage: 61%)
**Why High Value:** Already 61% covered, small gap to close

**Test Categories:**
- **Configuration Tests:**
  - `getMailFrom()` name/email selection (CRAP: 6)
  - IMAP/SMTP credentials encryption
  - Folder management
  
- **Permission Tests:**
  - `users()` relationship
  - User access via pivot table
  - Permission inheritance
  
**Edge Cases:**
1. Mailbox with no users assigned
2. Mailbox with 50+ users
3. Missing SMTP/IMAP configuration
4. OAuth vs password authentication
5. Alias email addresses
6. Signature with variables
7. Ticket auto-response settings
8. Fetch frequency validation
9. Before/after fetch hooks

---

#### 6.5 User Model (CRAP: 135, Coverage: 31%)
**Why Important:** Auth and permission system

**Test Categories:**
- **Role Tests:**
  - `isAdmin()` admin detection
  - `isActive()` active status
  - Role-based permissions
  
- **Access Control:**
  - `hasAccessToMailbox()` permission check (CRAP: 12)
  - Mailbox assignment
  - Folder access
  
- **Relationship Tests:**
  - `mailboxes()`, `folders()`, `conversations()`, `threads()`
  - `followedConversations()` follow system
  - `subscriptions()` notification preferences
  
**Edge Cases:**
1. User with no mailboxes
2. Deactivated user with active conversations
3. Admin accessing all mailboxes
4. User photo upload
5. Password reset flow
6. Email change validation
7. Timezone handling
8. Language/locale preferences
9. Notification settings complexity
10. Two-factor authentication state

---

### Phase 7: Observer Patterns & Event Listeners (CRITICAL FOR DATA INTEGRITY)

**Priority:** CRITICAL - These ensure data consistency but have 0% coverage

#### 7.1 Observers (All 0% coverage)

**ConversationObserver (CRAP: 182):**
- `creating()` - Set initial state (CRAP: 12)
- `created()` - Trigger notifications (CRAP: 12)
- `updated()` - Update counters (CRAP: 12)
- `deleting()` - Cascade deletes (CRAP: 12)
- `updateFolderCounters()` - Counter sync (CRAP: 12)

**ThreadObserver (CRAP: 12):**
- `created()` - Update conversation counters
- `deleted()` - Decrement counters

**CustomerObserver (CRAP: 12):**
- `creating()` - Normalize email
- `deleting()` - Cascade to conversations

**UserObserver (CRAP: 42):**
- `created()` - Create default folders (CRAP: 6)
- `deleting()` - Handle orphaned data
- `createAdminPersonalFolders()` - Folder setup
- `addDefaultSubscriptions()` - Notification setup

**AttachmentObserver (CRAP: 20):**
- `deleting()` - Delete physical file

**MailboxObserver (CRAP: 20):**
- `created()` - Create default folders
- `deleting()` - Archive conversations
- `createDefaultFolders()` - Inbox, sent, drafts, trash

**Edge Cases for Observers:**
1. Observer fired during bulk operations
2. Observer exception handling
3. Transaction rollback scenarios
4. Recursive observer calls
5. Observer disabled scenarios
6. Model events not firing (static methods)
7. Soft delete vs hard delete
8. Restore operations
9. Mass update bypassing observers
10. Event order dependencies

---

### Phase 8: Background Jobs & Queue System (RELIABILITY CRITICAL)

**Priority:** HIGH - Email sending failures cause customer complaints

#### 8.1 SendNotificationToUsers (CRAP: 600)
**Test Categories:**
- Unit: User filtering logic
- Integration: Actual email sending (mocked)
- Queue: Job retry on failure

**Edge Cases:**
1. All users unsubscribed
2. User email invalid
3. SMTP connection failure mid-send
4. Job timeout after 5 recipients
5. Notification template missing variables
6. Duplicate notifications
7. User deleted during job processing
8. Mailbox deleted during job
9. Job failed retry exhaustion
10. Queue worker not running

---

#### 8.2 SendAutoReply (CRAP: 182)
**Edge Cases:**
1. Auto-reply to auto-reply (loop detection)
2. Customer email invalid
3. Auto-reply disabled mid-job
4. Template rendering error
5. Schedule window check (business hours)
6. Consecutive auto-reply rate limiting
7. Language detection for template
8. Attachment inclusion

---

#### 8.3 SendConversationReply (CRAP: 30)
**Edge Cases:**
1. Conversation closed before send
2. SMTP authentication failure
3. Large attachment timeout
4. Customer email bounce
5. Rate limiting (too many emails)
6. Email provider blocks (Gmail, Outlook)

---

### Phase 9: Mail Classes & Templates (CUSTOMER-FACING)

**Priority:** HIGH - Customer-facing content must work

#### 9.1 Mail Classes (All 0% coverage)

**UserNotification (CRAP: 72):**
- Test template rendering
- Variable replacement
- Attachment handling

**AutoReply (CRAP: 132):**
- Schedule checking
- Template selection
- Reply-To handling

**Alert (CRAP: 30):**
- Admin notification
- Severity levels
- Action links

**Edge Cases:**
1. Missing email template
2. Variables not replaced
3. HTML rendering broken
4. Plain text fallback
5. Locale/translation missing
6. Attachment inline vs attached
7. Email size exceeds limit
8. Special characters in subject

---

### Phase 10: Integration & Feature Tests (END-TO-END)

**Priority:** MEDIUM-HIGH - Catches integration bugs

#### 10.1 Full Email Flow Tests
1. Receive email → Process → Create conversation → Notify users
2. User reply → Send via SMTP → Update conversation
3. Customer reply → Detect → Parse → Thread → Notify
4. Auto-responder → Detect → Skip notification
5. Bounce handling → Mark thread → Update status

#### 10.2 Full Web Flow Tests  
1. Login → View mailbox → Open conversation → Reply → Send
2. Create conversation → Add customer → Send → Track open
3. Merge conversations → Verify threads → Check counters
4. Move conversation → Verify folder → Check permissions
5. Search conversations → Filter → Sort → Paginate

#### 10.3 Permission Flow Tests
1. Admin adds user → Assigns mailbox → User accesses
2. User creates conversation → Another user views → Policy check
3. Customer creates via email → Agent replies → Customer receives
4. Follower added → Notification sent → Access granted

---

## Critical Data Integrity Tests (Must Have)

**These tests prevent data corruption and should be implemented early:**

### Transaction Safety Tests
1. **Rollback on Exception:** If email processing fails, no partial conversation created
2. **Atomic Counter Updates:** Folder counters match actual conversation counts
3. **Constraint Violations:** Foreign key errors properly handled
4. **Deadlock Handling:** Concurrent updates don't cause deadlocks

### Data Consistency Tests
1. **Orphan Prevention:** No threads without conversations
2. **Reference Integrity:** All conversation.customer_id references valid customers
3. **Status Consistency:** Conversation status matches folder type
4. **Counter Accuracy:** threads_count matches actual thread count

### Race Condition Tests
1. **Concurrent Replies:** Two agents replying simultaneously
2. **Status Changes:** Agent closes while customer replies
3. **Folder Moves:** Moving conversation while thread added
4. **Counter Races:** Multiple operations updating same counter

### Audit Trail Tests
1. **Who Created:** created_by_user_id vs created_by_customer_id accurate
2. **Who Modified:** edited_by_user_id and edited_at tracked
3. **Status History:** closed_by_user_id and closed_at tracked
4. **Activity Log:** All actions logged correctly

---

## High-Value Test Scenarios (By Business Impact)

### Scenario 1: Email Fetch Failure Recovery
**Business Impact:** Lost customer emails
**Test:** IMAP fetch fails mid-process, verify partial data not saved, retry works
**Coverage:** ImapService, Observers, Transactions

### Scenario 2: Duplicate Email Detection
**Business Impact:** Duplicate conversations confuse customers
**Test:** Same Message-ID arrives twice, verify only one conversation created
**Coverage:** ImapService::processMessage, Customer::create, Transaction handling

### Scenario 3: Permission Violation Attempt
**Business Impact:** Security breach, unauthorized data access
**Test:** User tries accessing conversation from unassigned mailbox, verify 403
**Coverage:** ConversationController, ConversationPolicy, Middleware

### Scenario 4: Large Attachment Handling
**Business Impact:** Server crashes, out of memory
**Test:** Upload 50MB file, verify size limit enforced, streaming used
**Coverage:** ConversationController::upload, Storage facade

### Scenario 5: SMTP Failure Cascade
**Business Impact:** Customer doesn't receive reply
**Test:** SMTP fails, verify job retries, user notified, conversation marked
**Coverage:** SendConversationReply job, Queue failure handlers

### Scenario 6: Counter Drift Correction
**Business Impact:** Incorrect unread counts, confused users  
**Test:** Manually corrupt counter, run correction script, verify fixed
**Coverage:** UpdateFolderCounters command, Observer accuracy

### Scenario 7: Customer Merge Data Integrity
**Business Impact:** Lost conversation history
**Test:** Merge customers with 100+ conversations, verify all transferred, counters accurate
**Coverage:** CustomerController::merge, Database transactions

### Scenario 8: Auto-Responder Loop Prevention
**Business Impact:** Email storm, server overload
**Test:** Two auto-responders email each other, verify loop detected and stopped
**Coverage:** MailHelper::isAutoResponder, SendAutoReply job

### Scenario 9: Unicode/Emoji Support
**Business Impact:** Broken text display, encoding errors
**Test:** Customer name with emoji, verify stored and displayed correctly
**Coverage:** Database charset, Customer model, Views

### Scenario 10: Timezone Consistency
**Business Impact:** Incorrect timestamps, SLA violations
**Test:** Users in different timezones, verify all see correct local times
**Coverage:** Carbon date handling, User timezone settings

---

## Testing Implementation Guidelines

### Test Structure Template

```php
<?php

namespace Tests\Unit\Services;

use App\Services\ImapService;
use Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

class ImapServiceTest extends UnitTestCase
{
    protected ImapService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImapService();
    }

    #[Test]
    public function it_parses_valid_email_addresses(): void
    {
        $result = $this->service->parseAddresses('john@example.com');
        
        $this->assertEquals('john@example.com', $result[0]['email']);
    }

    #[Test]
    #[DataProvider('malformedAddressProvider')]
    public function it_handles_malformed_email_addresses(string $input, array $expected): void
    {
        $result = $this->service->parseAddresses($input);
        
        $this->assertEquals($expected, $result);
    }

    public static function malformedAddressProvider(): array
    {
        return [
            'missing domain' => ['john@', []],
            'missing at symbol' => ['john.example.com', []],
            'special characters' => ['john+test@example.com', [['email' => 'john+test@example.com']]],
            // Add 20+ edge cases
        ];
    }
}
```

### Mock Strategy

```php
// For ImapService
$mockClient = Mockery::mock(ClientManager::class);
$mockClient->shouldReceive('getFolder')
    ->andReturn($mockFolder);
$mockClient->shouldReceive('fetchEmails')
    ->andReturn($mockMessages);

// For HTTP requests
$this->mock(ImapService::class, function ($mock) {
    $mock->shouldReceive('processMessage')
        ->once()
        ->andReturn(['status' => 'success']);
});
```

### Concrete Test Examples

#### Example 1: ImapService::parseAddresses Edge Cases

```php
#[Test]
#[DataProvider('addressParsingProvider')]
public function it_correctly_parses_email_addresses(string $input, array $expected): void
{
    $result = $this->service->parseAddresses($input);
    
    $this->assertEquals($expected, $result);
}

public static function addressParsingProvider(): array
{
    return [
        'simple email' => [
            'john@example.com',
            [['email' => 'john@example.com', 'name' => null]]
        ],
        'with name' => [
            'John Doe <john@example.com>',
            [['email' => 'john@example.com', 'name' => 'John Doe']]
        ],
        'quoted name with comma' => [
            '"Doe, John" <john@example.com>',
            [['email' => 'john@example.com', 'name' => 'Doe, John']]
        ],
        'multiple addresses' => [
            'john@example.com, jane@example.com',
            [
                ['email' => 'john@example.com', 'name' => null],
                ['email' => 'jane@example.com', 'name' => null]
            ]
        ],
        'malformed - missing domain' => [
            'john@',
            []
        ],
        'malformed - missing at' => [
            'johnexample.com',
            []
        ],
        'unicode name' => [
            '😀 User <test@example.com>',
            [['email' => 'test@example.com', 'name' => '😀 User']]
        ],
        'idn domain' => [
            'user@münchen.de',
            [['email' => 'user@xn--mnchen-3ya.de', 'name' => null]]
        ],
        'plus addressing' => [
            'john+test@example.com',
            [['email' => 'john+test@example.com', 'name' => null]]
        ],
        'empty input' => [
            '',
            []
        ],
        'whitespace only' => [
            '   ',
            []
        ],
    ];
}
```

#### Example 2: MailHelper::replaceMailVars Complex Cases

```php
#[Test]
public function it_replaces_variables_with_fallback_values(): void
{
    $text = 'Hello {%customer.fullName,fallback=there%}!';
    $data = []; // No customer provided
    
    $result = MailHelper::replaceMailVars($text, $data);
    
    $this->assertEquals('Hello there!', $result);
}

#[Test]
public function it_handles_missing_variables_without_fallback(): void
{
    $text = 'Hello {%customer.fullName%}!';
    $data = [];
    
    $result = MailHelper::replaceMailVars($text, $data, false, true);
    
    $this->assertEquals('Hello !', $result);
}

#[Test]
public function it_escapes_html_when_requested(): void
{
    $text = '{%customer.fullName%}';
    $data = [
        'customer' => (object)[
            'first_name' => '<script>alert("xss")</script>',
            'last_name' => 'User'
        ]
    ];
    
    $result = MailHelper::replaceMailVars($text, $data, true);
    
    $this->assertStringNotContainsString('<script>', $result);
    $this->assertStringContainsString('&lt;script&gt;', $result);
}

#[Test]
public function it_prevents_recursion_in_mailbox_from_name(): void
{
    $mailbox = Mailbox::factory()->create();
    
    $text = '{%mailbox.fromName%}';
    $data = [
        'mailbox' => $mailbox,
        'mailbox_from_name' => 'Safe Name' // Prevents getMailFrom() call
    ];
    
    $result = MailHelper::replaceMailVars($text, $data);
    
    $this->assertEquals('Safe Name', $result);
}
```

#### Example 3: ConversationController Authorization

```php
#[Test]
public function it_prevents_unauthorized_access_to_conversation(): void
{
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()->for($mailbox)->create();
    
    // User NOT assigned to mailbox
    
    $response = $this->actingAs($user)
        ->get(route('conversations.show', $conversation));
    
    $response->assertForbidden();
}

#[Test]
public function it_allows_access_when_user_assigned_to_mailbox(): void
{
    $user = User::factory()->create();
    $mailbox = Mailbox::factory()->create();
    $mailbox->users()->attach($user->id);
    $conversation = Conversation::factory()->for($mailbox)->create();
    
    $response = $this->actingAs($user)
        ->get(route('conversations.show', $conversation));
    
    $response->assertOk();
}
```

#### Example 4: Transaction Rollback Test

```php
#[Test]
public function it_rolls_back_transaction_when_email_processing_fails(): void
{
    $mailbox = Mailbox::factory()->create();
    
    // Mock IMAP message that will fail processing
    $mockMessage = Mockery::mock(Message::class);
    $mockMessage->shouldReceive('getFrom')->andThrow(new \Exception('Invalid from'));
    
    $conversationCount = Conversation::count();
    $customerCount = Customer::count();
    
    try {
        $this->service->processMessage($mailbox, $mockMessage);
    } catch (\Exception $e) {
        // Expected to fail
    }
    
    // Verify no records were created
    $this->assertEquals($conversationCount, Conversation::count());
    $this->assertEquals($customerCount, Customer::count());
}
```

#### Example 5: Race Condition Test

```php
#[Test]
public function it_handles_concurrent_counter_updates(): void
{
    $conversation = Conversation::factory()->create(['threads_count' => 5]);
    
    // Simulate two threads being created simultaneously
    $thread1 = Thread::factory()->for($conversation)->make();
    $thread2 = Thread::factory()->for($conversation)->make();
    
    // Run in parallel using forks or separate database connections
    $promises = [
        async(fn() => $thread1->save()),
        async(fn() => $thread2->save()),
    ];
    
    await($promises);
    
    $conversation->refresh();
    
    // Counter should be accurate
    $this->assertEquals(7, $conversation->threads_count);
    $this->assertEquals(7, $conversation->threads()->count());
}
```

#### Example 6: Observer Test

```php
#[Test]
public function it_updates_folder_counters_when_conversation_created(): void
{
    $folder = Folder::factory()->create(['active_count' => 0]);
    
    Conversation::factory()->create([
        'folder_id' => $folder->id,
        'status' => Conversation::STATUS_ACTIVE
    ]);
    
    $folder->refresh();
    
    $this->assertEquals(1, $folder->active_count);
}

#[Test]
public function it_cascades_deletes_to_threads(): void
{
    $conversation = Conversation::factory()
        ->has(Thread::factory()->count(3))
        ->create();
    
    $threadIds = $conversation->threads->pluck('id');
    
    $conversation->delete();
    
    // Verify threads were deleted
    $this->assertEquals(0, Thread::whereIn('id', $threadIds)->count());
}
```

#### Example 7: Job Retry Test

```php
#[Test]
public function it_retries_send_notification_job_on_failure(): void
{
    Queue::fake();
    
    $job = new SendNotificationToUsers(
        $conversation = Conversation::factory()->create(),
        []
    );
    
    // Simulate SMTP failure
    Mail::shouldReceive('to')->andThrow(new \Exception('SMTP error'));
    
    try {
        $job->handle();
    } catch (\Exception $e) {
        // Call failed() method
        $job->failed($e);
    }
    
    // Verify job was released back to queue for retry
    Queue::assertPushed(SendNotificationToUsers::class);
}
```

### Edge Case Testing Checklist

For each method, ensure coverage of:
- ✅ Happy path (valid input, expected output)
- ✅ Empty input (null, empty string, empty array)
- ✅ Invalid input (wrong type, malformed data)
- ✅ Boundary conditions (min/max values, limits)
- ✅ Special characters (Unicode, HTML entities, SQL injection attempts)
- ✅ Network failures (timeouts, connection errors)
- ✅ Database failures (constraint violations, deadlocks)
- ✅ Permission failures (unauthorized access)
- ✅ Race conditions (concurrent access)
- ✅ Resource exhaustion (memory limits, disk space)

---

## Coverage Goals

### Target Coverage by Phase

| Phase | Classes | Target Line Coverage | Target Method Coverage | Timeline | Effort (hrs) |
|-------|---------|---------------------|----------------------|----------|--------------|
| **Phase 1** | 4 | 70%+ | 80%+ | 3-4 weeks | 120-160 |
| **Phase 2** | 3 | 60%+ | 70%+ | 2-3 weeks | 60-80 |
| **Phase 3** | 2 | 70%+ | 80%+ | 1-2 weeks | 40-60 |
| **Phase 4** | 5+ | 50%+ | 60%+ | 2-3 weeks | 60-80 |
| **Phase 5** | 5+ | 80%+ | 90%+ | 1-2 weeks | 40-60 |
| **Phase 6** | 5 | 70%+ | 80%+ | 3-4 weeks | 100-120 |
| **Phase 7** | 6 | 90%+ | 95%+ | 2 weeks | 40-60 |
| **Phase 8** | 3 | 70%+ | 80%+ | 2 weeks | 50-70 |
| **Phase 9** | 8 | 60%+ | 70%+ | 2 weeks | 40-60 |
| **Phase 10** | Integration | 50%+ | 60%+ | 2-3 weeks | 60-80 |

### Overall Project Goals

- **3 months:** 40% line coverage (Phases 1-3 complete)
- **6 months:** 60% line coverage (Phases 1-6 complete)
- **12 months:** 80% line coverage (Phases 1-9 complete)
- **18 months:** 90%+ line coverage (All phases + maintenance)

**Total Estimated Effort:** 610-830 hours (~4-5 months full-time)

### Critical Path (Revised)

**Month 1: Foundation (Phases 1-2)**
- Week 1-2: ImapService unit tests (parse/format/validate methods) - 40 hrs
- Week 2-3: ImapService integration tests (processMessage, fetchEmails) - 80 hrs
- Week 3-4: ConversationController feature tests (CRUD operations) - 40 hrs
- Week 4: MailboxController + MailHelper basics - 20 hrs

**Month 2: Controllers & Services (Phases 2-3)**
- Week 5-6: Complete MailboxController + MailHelper - 60 hrs
- Week 7-8: SettingsController, SystemController, UserController - 60 hrs
- Week 8: SmtpService tests - 40 hrs

**Month 3: Business Logic (Phases 4-6)**
- Week 9-10: Customer model + creation logic - 50 hrs
- Week 11: Jobs (SendNotificationToUsers, SendAutoReply) - 40 hrs
- Week 12: Events + Module system - 30 hrs
- Week 13-14: Conversation, Thread, User, Mailbox models - 100 hrs

**Month 4: Data Integrity (Phases 7-8)**
- Week 15-16: All Observers - 50 hrs
- Week 17: Additional Jobs - 30 hrs
- Week 18: Policies - 40 hrs

**Month 5: Polish & Integration (Phases 9-10)**
- Week 19-20: Mail classes + templates - 50 hrs
- Week 21-22: Integration tests - 60 hrs
- Week 23: Performance optimization - 20 hrs
- Week 24: Documentation + cleanup - 20 hrs

---

## Metrics to Track

### Weekly Tracking
- Lines covered (absolute + percentage)
- Methods covered (absolute + percentage)
- CRAP score reduction (top 20 classes)
- New tests added
- Test execution time

### Quality Metrics
- Test failures (should remain 0)
- Code review feedback
- Bug discoveries from tests
- Regression prevention

---

## Test Data Strategy

### Factory Enhancements Needed

**Current State:** Factories exist but need edge case states

#### Conversation Factory States
```php
// Add to ConversationFactory
public function active(): static
{
    return $this->state(['status' => Conversation::STATUS_ACTIVE]);
}

public function closed(): static
{
    return $this->state([
        'status' => Conversation::STATUS_CLOSED,
        'closed_at' => now(),
        'closed_by_user_id' => User::factory()
    ]);
}

public function withThreads(int $count = 3): static
{
    return $this->has(Thread::factory()->count($count));
}

public function withAttachments(): static
{
    return $this->has(
        Thread::factory()
            ->has(Attachment::factory()->count(2))
    );
}

public function spam(): static
{
    return $this->state(['status' => Conversation::STATUS_SPAM]);
}

public function draft(): static
{
    return $this->state(['state' => Conversation::STATE_DRAFT]);
}
```

#### Customer Factory States
```php
// Add to CustomerFactory
public function withMultipleEmails(int $count = 3): static
{
    return $this->has(Email::factory()->count($count));
}

public function withCompany(): static
{
    return $this->state([
        'company' => fake()->company(),
        'job_title' => fake()->jobTitle()
    ]);
}

public function withUnicodeName(): static
{
    return $this->state([
        'first_name' => '山田',
        'last_name' => '太郎'
    ]);
}

public function withEmoji(): static
{
    return $this->state([
        'first_name' => '😀 Happy',
        'last_name' => 'Customer'
    ]);
}
```

#### Thread Factory States
```php
// Add to ThreadFactory
public function customerMessage(): static
{
    return $this->state([
        'type' => Thread::TYPE_CUSTOMER,
        'customer_id' => Customer::factory(),
        'user_id' => null
    ]);
}

public function userReply(): static
{
    return $this->state([
        'type' => Thread::TYPE_MESSAGE,
        'user_id' => User::factory(),
        'customer_id' => null
    ]);
}

public function note(): static
{
    return $this->state([
        'type' => Thread::TYPE_NOTE,
        'user_id' => User::factory()
    ]);
}

public function withLargeBody(): static
{
    return $this->state([
        'body' => fake()->paragraphs(50, true) // ~5KB
    ]);
}

public function withHtmlBody(): static
{
    return $this->state([
        'body' => '<html><body>' . fake()->paragraphs(5, true) . '</body></html>'
    ]);
}
```

### Test Fixtures (Email Messages)

**Location:** `tests/Fixtures/emails/`

#### valid_email.eml
```eml
From: customer@example.com
To: support@myapp.com
Subject: Test Message
Date: Mon, 1 Jan 2024 12:00:00 +0000
Message-ID: <test123@example.com>

This is a test email body.
```

#### email_with_attachment.eml
```eml
From: customer@example.com
To: support@myapp.com
Subject: Email with PDF
Content-Type: multipart/mixed; boundary="boundary123"

--boundary123
Content-Type: text/plain

Please see attached.

--boundary123
Content-Type: application/pdf; name="document.pdf"
Content-Transfer-Encoding: base64

[base64 encoded PDF data]
--boundary123--
```

#### auto_responder_email.eml
```eml
From: customer@example.com
To: support@myapp.com
Subject: Out of Office
X-Autoreply: yes
Auto-Submitted: auto-replied

I am currently out of office.
```

#### malformed_email.eml
```eml
From customer@example.com
Subject Test Message
Missing required headers

Body without proper structure
```

### Fixture Helper Class

```php
// tests/Support/EmailFixtures.php
namespace Tests\Support;

class EmailFixtures
{
    public static function load(string $name): string
    {
        $path = __DIR__ . '/../Fixtures/emails/' . $name . '.eml';
        
        if (!file_exists($path)) {
            throw new \RuntimeException("Fixture not found: $name");
        }
        
        return file_get_contents($path);
    }
    
    public static function parseMockMessage(string $name): MockMessage
    {
        $content = self::load($name);
        return new MockMessage($content);
    }
}

// Usage in tests:
$emailContent = EmailFixtures::load('valid_email');
$mockMessage = EmailFixtures::parseMockMessage('auto_responder_email');
```

### Database Seeder for Testing

```php
// database/seeders/TestDataSeeder.php
class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create realistic test scenario
        $admin = User::factory()->admin()->create([
            'email' => 'admin@test.com'
        ]);
        
        $agent = User::factory()->create([
            'email' => 'agent@test.com'
        ]);
        
        $mailbox = Mailbox::factory()->create([
            'name' => 'Support',
            'email' => 'support@test.com'
        ]);
        
        $mailbox->users()->attach([$admin->id, $agent->id]);
        
        // Create folders
        $folders = [
            'inbox' => Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]),
            'sent' => Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 2]),
            'closed' => Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 3]),
        ];
        
        // Create sample conversations
        $customer = Customer::factory()->create();
        
        Conversation::factory()
            ->count(10)
            ->for($mailbox)
            ->for($customer)
            ->for($folders['inbox'], 'folder')
            ->withThreads(3)
            ->create();
    }
}
```

---

## Tooling Recommendations

### Code Coverage
- ✅ PCOV (already configured)
- ✅ PHPUnit HTML reports (already generating)
- ⚠️ Consider adding: Coverage badges, CI/CD integration

### Testing Utilities
- **Mockery** - Advanced mocking (already available)
- **Faker** - Test data generation
- **Laravel Dusk** - Browser testing (already configured)
- **Pest** - Alternative test syntax (optional)

### Static Analysis
- ✅ PHPStan (already configured)
- ⚠️ Consider adding: Psalm, PHP-CS-Fixer strict rules

---

## Next Steps

1. **Immediate (This Week):**
   - Create `tests/Unit/Services/ImapServiceTest.php`
   - Implement first 10 unit tests for parseAddresses()
   - Set up test data fixtures for email messages

2. **Short Term (Next 2 Weeks):**
   - Complete ImapService unit test suite
   - Begin ConversationController feature tests
   - Document testing patterns in TESTING.md

3. **Medium Term (Next Month):**
   - Complete Phase 1 (critical infrastructure)
   - Achieve 20%+ overall line coverage
   - Integrate coverage reporting into CI/CD

4. **Long Term (Next Quarter):**
   - Complete Phases 1-3
   - Achieve 50%+ overall line coverage
   - Establish testing culture/standards

---

## Risk Mitigation

### Testing Risks

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Tests take too long to run | Dev slowdown | Use parallel testing, optimize slow tests |
| Brittle tests (false failures) | Lost confidence | Focus on behavior, not implementation |
| Low-value tests | Wasted effort | Prioritize by CRAP score |
| Coverage without quality | False security | Review test quality, add edge cases |
| Mocking too much | Missing integration bugs | Balance unit + integration tests |

### Implementation Risks

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Breaking existing functionality | Production bugs | Run full test suite before merge |
| Time investment | Delayed features | Phase approach, parallel work |
| Team resistance | Low adoption | Document benefits, share wins |
| Coverage regression | Lost progress | Add coverage checks to CI/CD |

---

## Implementation Quick Start Guide

### Week 1 Action Items

**Day 1-2: Setup**
```bash
# 1. Enhance factories with state methods
php artisan make:factory CustomerFactory --model=Customer
# Add: withMultipleEmails(), withUnicodeName(), withEmoji()

# 2. Create email fixtures directory
mkdir -p tests/Fixtures/emails
# Add: valid_email.eml, auto_responder_email.eml, etc.

# 3. Create EmailFixtures helper
# tests/Support/EmailFixtures.php

# 4. Run baseline coverage
php artisan test --coverage-html reports/baseline
```

**Day 3-5: Start Critical Tests**
```bash
# Priority 1: ImapService parseAddresses (CRAP 420)
touch tests/Unit/Services/ImapServiceTest.php
# Add 10 tests with DataProvider for edge cases

# Priority 2: MailHelper replaceMailVars (CRAP 506)
touch tests/Unit/Misc/MailHelperTest.php
# Add 15 tests for variable replacement edge cases

# Priority 3: ConversationController index/show
touch tests/Feature/Controllers/ConversationControllerTest.php
# Add 10 tests for basic CRUD operations
```

### Daily Targets (Phase 1)

- **Week 1-2:** 20 tests/day → 200 tests (Critical infrastructure to 30%)
- **Week 3-4:** 25 tests/day → 350 tests (Models to 60%, Controllers to 25%)
- **Week 5-6:** 25 tests/day → 350 tests (Controllers to 50%, Complete Jobs/Listeners)
- **Week 7-8:** 20 tests/day → 280 tests (Polish, reach 50% all classes)

**Total Phase 1:** ~1,200 tests in 8 weeks (5 tests/hour)

### Test Template Examples

**Unit Test Template:**
```php
<?php
namespace Tests\Unit\Services;

use Tests\UnitTestCase;
use App\Services\ImapService;

class ImapServiceTest extends UnitTestCase
{
    /** @test */
    public function parseAddresses_with_valid_email_returns_array(): void
    {
        $service = new ImapService();
        $result = $service->parseAddresses('John Doe <john@example.com>');
        
        $this->assertIsArray($result);
        $this->assertEquals('john@example.com', $result[0]['email']);
        $this->assertEquals('John Doe', $result[0]['name']);
    }
    
    /**
     * @test
     * @dataProvider malformedEmailProvider
     */
    public function parseAddresses_with_malformed_input_handles_gracefully($input): void
    {
        $service = new ImapService();
        $result = $service->parseAddresses($input);
        
        $this->assertIsArray($result);
        // Should not throw exception
    }
    
    public static function malformedEmailProvider(): array
    {
        return [
            'missing domain' => ['john@'],
            'missing local' => ['@example.com'],
            'plain text' => ['not an email'],
            'null input' => [null],
            'empty string' => [''],
        ];
    }
}
```

**Feature Test Template:**
```php
<?php
namespace Tests\Feature\Controllers;

use Tests\FeatureTestCase;
use App\Models\{User, Mailbox, Conversation};

class ConversationControllerTest extends FeatureTestCase
{
    /** @test */
    public function user_can_view_conversation_in_assigned_mailbox(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $conversation = Conversation::factory()
            ->for($mailbox)
            ->withThreads(3)
            ->create();
        
        $response = $this->actingAs($user)
            ->get(route('conversations.show', $conversation));
        
        $response->assertOk();
        $response->assertSee($conversation->subject);
    }
    
    /** @test */
    public function user_cannot_view_conversation_in_unassigned_mailbox(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('conversations.show', $conversation));
        
        $response->assertForbidden();
    }
}
```

### Monitoring Progress

**Weekly Coverage Check:**
```bash
# Generate coverage report
php artisan test --coverage-html reports/week-X

# Check class coverage
grep -r "coverage.*%" reports/week-X/dashboard.html | grep "0%"
# Goal: List should get shorter each week

# Check overall metrics
# Goal Week 4: 20% lines, 30% methods
# Goal Week 8: 40% lines, 50% methods
```

**Weekly Review Checklist:**
- [ ] All new tests passing
- [ ] Coverage increased by 5%+
- [ ] No new classes at 0%
- [ ] CRAP scores reduced
- [ ] CI/CD pipeline green
- [ ] Code review completed
- [ ] Team sync on blockers

---

## Success Criteria

### Definition of Done (Per Phase)

**Phase 1 Complete When:**
- ✅ All 115 classes have test files
- ✅ Zero classes at 0% coverage
- ✅ All classes at 50%+ coverage
- ✅ Target coverage: 40% lines, 50% methods
- ✅ All tests passing in CI/CD
- ✅ Top 10 CRAP methods reduced by 60%
- ✅ Code reviewed and approved
- ✅ Edge cases documented and tested
- ✅ Test execution time < 5 minutes

**Phase 2 Complete When:**
- ✅ Zero classes below 80% coverage
- ✅ Target coverage: 70% lines, 80% methods
- ✅ All CRAP scores < 200
- ✅ Integration tests for key workflows
- ✅ Performance tests added
- ✅ Security tests completed

**Phase 3 Complete When:**
- ✅ 95%+ class coverage achieved
- ✅ 85%+ line coverage achieved
- ✅ All edge cases tested
- ✅ Mutation testing score > 80%

### Quality Gates

- ⚠️ Coverage cannot decrease (enforce in CI/CD)
- ⚠️ New code must include tests
- ⚠️ Critical methods (CRAP > 30) require 80%+ coverage
- ⚠️ All public methods require at least 1 test

---

## Conclusion

This plan provides a systematic approach to improving code coverage from 2.28% to 80%+ over 12 months. By prioritizing high-CRAP classes and focusing on edge cases, we'll reduce technical debt while preventing regressions.

**Immediate Focus:** ImapService testing (CRAP: 34,410) will provide the highest risk reduction per test hour invested.

**Success Indicator:** Achieving 70%+ coverage on ImapService will reduce overall project CRAP by ~40%.
