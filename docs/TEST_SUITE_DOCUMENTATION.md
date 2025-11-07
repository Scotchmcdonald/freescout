# Test Suite Documentation

**Last Updated**: November 7, 2025

This document provides comprehensive documentation for the complete FreeScout test suite, consolidating information from all test batches.

---

## 📊 Test Suite Overview

### Statistics
- **Total Test Files**: 68 files (66 backend PHP + 2 frontend JavaScript)
- **Backend Tests**: 
  - Feature Tests: 30 files
  - Unit Tests: 36 files
- **Frontend Tests**: 2+ Vitest test files
- **Total Test Methods**: 500+ individual test assertions
- **Test Organization**: 5 batches covering different functional areas + original test suite
- **Infrastructure**: PHPUnit 10+ with PHP 8 attributes, Vitest for frontend
- **Database**: SQLite in-memory for fast unit tests, MySQL for integration tests
- **Runtime**: ~60 seconds for full suite (excluding slow IMAP tests)

### Test Infrastructure Features
- ✅ **Modern PHPUnit 10+** with PHP 8 attributes (`#[Test]`, `#[Group()]`)
- ✅ **Vitest** for JavaScript/frontend testing
- ✅ **PCOV** for code coverage measurement
- ✅ **SQLite In-Memory** testing (10-20x faster)
- ✅ **Test Grouping** for slow tests (IMAP integration)
- ✅ **Database Isolation** with RefreshDatabase trait
- ✅ **Zero Deprecation Warnings**
- ✅ **Automated Setup** via `scripts/setup-dev-environment.sh`

---

## 🎯 Test Batches

### **Batch 1: User Authentication & Authorization**
**Documentation**: `docs/BATCH_1_TESTS.md`  
**Test Files**: 7 files  
**Focus**: User management, authentication flows, role-based access control

#### Test Files
1. **`tests/Feature/AuthenticationBatch1Test.php`**
   - Login workflows (valid/invalid credentials)
   - Logout functionality
   - Session handling
   - Remember me functionality
   - Failed login attempts

2. **`tests/Feature/UserManagementAdminBatch1Test.php`**
   - Admin user CRUD operations
   - User creation with validation
   - User updates (profile, roles, status)
   - User deletion
   - Authorization checks (admin-only operations)

3. **`tests/Feature/UserSecurityBatch1Test.php`**
   - Password policies and validation
   - Password reset flows
   - Email verification
   - Account lockout after failed attempts
   - Session security
   - CSRF protection

4. **`tests/Unit/UserModelBatch1Test.php`**
   - User model attributes
   - Role detection methods (`isAdmin()`, `hasRole()`)
   - Status checks (`isActive()`)
   - Full name generation
   - Email validation
   - Password hashing

#### Coverage Areas
- User authentication and authorization
- Role-based access control (ROLE_ADMIN=2, ROLE_USER=1)
- Password security and policies
- Session management
- User profile management

---

### **Batch 2: Mailbox & Folder Management**
**Documentation**: `docs/BATCH_2_TESTS.md`  
**Test Files**: 8 files  
**Focus**: Mailbox operations, email fetching, folder hierarchy, auto-replies

#### Test Files
1. **`tests/Feature/MailboxAutoReplyTest.php`**
   - Auto-reply configuration (enable/disable)
   - Auto-reply message customization
   - Rate limiting (10 replies per customer per 180 minutes)
   - Duplicate subject detection
   - Auto-responder header detection
   - Bounce detection

2. **`tests/Feature/MailboxFetchEmailsTest.php`**
   - IMAP email fetching workflows
   - Manual fetch via admin UI
   - Scheduled fetch (cron jobs)
   - Message deduplication
   - Conversation threading
   - Attachment handling

3. **`tests/Feature/MailboxRegressionTest.php`**
   - Regression tests for known mailbox bugs
   - Edge cases in email processing
   - Encoding issues (UTF-8, special characters)
   - Malformed email handling

4. **`tests/Feature/MailboxViewTest.php`**
   - Mailbox list view
   - Mailbox detail view
   - Permissions (who can view which mailboxes)
   - UI components and interactions

5. **`tests/Unit/FolderEdgeCasesTest.php`**
   - Folder creation edge cases
   - Empty folder handling
   - Invalid folder names
   - Folder deletion with conversations

6. **`tests/Unit/FolderHierarchyTest.php`**
   - Folder nesting and parent-child relationships
   - Folder tree traversal
   - Folder sorting and ordering

7. **`tests/Unit/MailboxControllerValidationTest.php`**
   - Input validation for mailbox operations
   - Email format validation
   - Required field validation
   - IMAP/SMTP connection validation

8. **`tests/Unit/MailboxScopesTest.php`**
   - Eloquent query scopes for mailboxes
   - Active mailbox filtering
   - User-accessible mailbox filtering
   - Performance optimizations (N+1 prevention)

#### Coverage Areas
- Mailbox CRUD operations
- IMAP/SMTP configuration
- Email fetching and processing
- Folder management and hierarchy
- Auto-reply system
- Mailbox permissions

---

### **Batch 3: Conversations & Threads** (Core Feature Tests)
**Test Files**: 11 files  
**Focus**: Core conversation functionality, threading, validation, security, real-time updates

#### Feature Test Files

1. **`tests/Feature/ConversationTest.php`** (10 tests)
   - Conversation viewing (authorized and unauthorized)
   - Conversation creation workflows
   - Reply workflows (customer and user replies)
   - Status updates (active, closed, spam, pending)
   - User assignments
   - Thread ordering
   - Pagination

2. **`tests/Feature/ConversationValidationTest.php`** (10 tests)
   - Subject validation (required, max length)
   - Body content validation (required)
   - Email format validation for recipients
   - To/CC/BCC field validation
   - File attachment validation
   - Required field enforcement
   - Error message verification

3. **`tests/Feature/ConversationControllerSecurityTest.php`** (8 tests)
   - Authorization checks (view, create, update, delete)
   - CSRF token validation
   - XSS sanitization (subject, body, HTML content)
   - SQL injection prevention
   - Unauthorized access attempts
   - Cross-mailbox authorization

4. **`tests/Feature/ConversationAdvancedTest.php`**
   - Complex conversation workflows
   - Multi-user collaboration
   - Conversation merging
   - Bulk operations
   - Advanced filtering

#### Unit Test Files

5. **`tests/Unit/ConversationModelTest.php`** (12 tests)
   - Status constants (STATUS_ACTIVE, STATUS_CLOSED, etc.)
   - Conversation attributes
   - Thread relationship (one-to-many)
   - Mailbox relationship
   - Customer relationship
   - User relationship (assignee)
   - Status change methods
   - Number generation
   - Created by detection

6. **`tests/Unit/ThreadModelTest.php`** (15 tests)
   - Thread type constants (TYPE_MESSAGE, TYPE_NOTE, etc.)
   - Thread attributes
   - Conversation relationship
   - User/Customer creator relationships
   - Email header methods (Message-ID, References, In-Reply-To)
   - Auto-responder detection (`isAutoResponder()`)
   - Bounce detection (`isBounce()`)
   - Thread body handling
   - Attachment relationships

7. **`tests/Unit/ThreadObserverTest.php`** (5 tests)
   - Thread count updates on conversation
   - Observer lifecycle (created, updated, deleted)
   - Thread count accuracy
   - Soft delete handling

8. **`tests/Unit/ModelRelationshipsTest.php`** (18 tests)
   - All Eloquent relationships across models
   - N+1 query prevention
   - Eager loading verification
   - Relationship integrity (foreign keys)
   - Polymorphic relationships

9. **`tests/Unit/EventBroadcastingTest.php`** (12 tests)
   - `CustomerCreatedConversation` event
   - `CustomerReplied` event
   - `NewMessageReceived` event
   - `ConversationUpdated` event
   - Channel authorization
   - Event payload verification
   - Listener registration

10. **`tests/Unit/EventsTest.php`** (8 tests)
    - Event properties
    - Broadcast channel names
    - Event serialization
    - Queue configuration

#### Coverage Areas
- Conversation CRUD operations
- Thread management and relationships
- Status workflows
- Security (authorization, XSS, CSRF, SQL injection)
- Validation (input, format, required fields)
- Real-time events and broadcasting
- Model relationships and N+1 prevention
- Email threading (headers, reply chains)

---

### **Batch 4: Customer Management**
**Documentation**: `docs/BATCH_4_TESTS.md`  
**Test Files**: 5 files  
**Focus**: Customer data, email addresses, AJAX operations

#### Test Files
1. **`tests/Feature/CustomerAjaxTest.php`**
   - AJAX customer search
   - Customer autocomplete
   - Customer data updates via AJAX
   - Real-time customer validation

2. **`tests/Feature/CustomerManagementTest.php`**
   - Customer CRUD workflows
   - Customer creation with validation
   - Customer profile updates
   - Customer merging
   - Customer deletion

3. **`tests/Feature/CustomerRegressionTest.php`**
   - Regression tests for customer bugs
   - Edge cases in customer handling
   - Duplicate email detection
   - Name parsing edge cases

4. **`tests/Unit/CustomerModelTest.php`** (Enhanced)
   - Customer model attributes
   - Full name generation
   - Email relationship (one-to-many)
   - Conversation relationship
   - Customer search functionality

5. **`tests/Unit/EmailModelEnhancedTest.php`**
   - Email model attributes
   - Email validation
   - Customer-email relationships
   - Primary email detection
   - Email uniqueness constraints

#### Coverage Areas
- Customer CRUD operations
- Customer-email relationships
- Customer search and filtering
- AJAX interactions
- Data validation

---

### **Batch 5: System Settings & Options**
**Documentation**: `docs/BATCH_5_TESTS.md`  
**Test Files**: 6 files  
**Focus**: Application settings, system configuration, security testing

#### Test Files
1. **`tests/Feature/OptionRegressionTest.php`**
   - Option model regression tests
   - Setting persistence
   - Default values
   - Type casting (boolean, integer, JSON)

2. **`tests/Feature/SecurityAndEdgeCasesTest.php`**
   - Cross-cutting security tests
   - Input sanitization across all controllers
   - SQL injection prevention
   - XSS prevention
   - CSRF token validation
   - Authentication middleware

3. **`tests/Feature/SettingsTest.php`**
   - Settings management workflows
   - General settings updates
   - Email settings configuration
   - System settings modification
   - Settings validation

4. **`tests/Feature/SystemTest.php`**
   - System health checks
   - Log viewing and management
   - Cache clearing
   - Queue monitoring
   - System status endpoints

5. **`tests/Unit/OptionModelTest.php`** (Enhanced)
   - Option model CRUD
   - Type casting logic
   - Caching behavior
   - Default value handling

6. **`tests/Unit/SettingsControllerTest.php`** (Enhanced)
   - Settings controller methods
   - Authorization checks
   - Validation logic
   - Success/error responses

#### Coverage Areas
- Application configuration
- System settings management
- Security testing (cross-cutting)
- System monitoring
- Option persistence

---

## 📋 Complete Test File Index

### Feature Tests (30 files)

#### Authentication & Authorization (6 files from Laravel Breeze)
- `tests/Feature/Auth/AuthenticationTest.php` - Login/logout flows
- `tests/Feature/Auth/EmailVerificationTest.php` - Email verification
- `tests/Feature/Auth/PasswordConfirmationTest.php` - Password confirmation
- `tests/Feature/Auth/PasswordResetTest.php` - Password reset flow
- `tests/Feature/Auth/PasswordUpdateTest.php` - Password change
- `tests/Feature/Auth/RegistrationTest.php` - User registration

#### User Management (5 files)
- `tests/Feature/AuthenticationBatch1Test.php` - Login workflows (Batch 1)
- `tests/Feature/UserManagementAdminBatch1Test.php` - Admin user CRUD (Batch 1)
- `tests/Feature/UserSecurityBatch1Test.php` - Password policies (Batch 1)
- `tests/Feature/UserManagementTest.php` - User management (original)
- `tests/Feature/ProfileTest.php` - User profile updates

#### Conversations (4 files)
- `tests/Feature/ConversationTest.php` - Conversation CRUD workflows
- `tests/Feature/ConversationAdvancedTest.php` - Advanced conversation features
- `tests/Feature/ConversationValidationTest.php` - Input validation
- `tests/Feature/ConversationControllerSecurityTest.php` - Security testing

#### Mailboxes (7 files)
- `tests/Feature/MailboxTest.php` - Mailbox CRUD (original)
- `tests/Feature/MailboxConnectionTest.php` - IMAP/SMTP connections
- `tests/Feature/MailboxPermissionsTest.php` - Per-mailbox permissions
- `tests/Feature/MailboxAutoReplyTest.php` - Auto-reply system (Batch 2)
- `tests/Feature/MailboxFetchEmailsTest.php` - Email fetching (Batch 2)
- `tests/Feature/MailboxRegressionTest.php` - Mailbox bug fixes (Batch 2)
- `tests/Feature/MailboxViewTest.php` - Mailbox UI (Batch 2)

#### Customers (3 files)
- `tests/Feature/CustomerAjaxTest.php` - AJAX operations (Batch 4)
- `tests/Feature/CustomerManagementTest.php` - Customer CRUD (Batch 4)
- `tests/Feature/CustomerRegressionTest.php` - Customer bugs (Batch 4)

#### System & Settings (4 files)
- `tests/Feature/OptionRegressionTest.php` - Option persistence (Batch 5)
- `tests/Feature/SecurityAndEdgeCasesTest.php` - Cross-cutting security (Batch 5)
- `tests/Feature/SettingsTest.php` - Settings management (Batch 5)
- `tests/Feature/SystemTest.php` - System operations (Batch 5)

#### Other (1 file)
- `tests/Feature/ExampleTest.php` - Example test (Laravel default)

### Unit Tests (36 files)

#### Models (16 files)
- `tests/Unit/ActivityLogModelTest.php` - Activity logging
- `tests/Unit/AttachmentModelTest.php` - File attachments
- `tests/Unit/ChannelModelTest.php` - Communication channels
- `tests/Unit/ConversationModelTest.php` - Conversation logic
- `tests/Unit/CustomerModelTest.php` - Customer logic (enhanced, Batch 4)
- `tests/Unit/EmailModelTest.php` - Email addresses (original)
- `tests/Unit/EmailModelEnhancedTest.php` - Email addresses (enhanced, Batch 4)
- `tests/Unit/FolderModelTest.php` - Folder organization
- `tests/Unit/MailboxModelTest.php` - Mailbox configuration
- `tests/Unit/ModuleModelTest.php` - Module system
- `tests/Unit/OptionModelTest.php` - Application options (enhanced, Batch 5)
- `tests/Unit/SendLogModelTest.php` - Email send tracking
- `tests/Unit/SubscriptionModelTest.php` - Subscription management
- `tests/Unit/ThreadModelTest.php` - Thread/message logic
- `tests/Unit/UserModelTest.php` - User logic (original)
- `tests/Unit/UserModelBatch1Test.php` - User logic (enhanced, Batch 1)

#### Controllers (3 files)
- `tests/Unit/CustomerControllerTest.php` - Customer controller
- `tests/Unit/DashboardControllerTest.php` - Dashboard controller
- `tests/Unit/SettingsControllerTest.php` - Settings controller (enhanced, Batch 5)

#### Policies (2 files)
- `tests/Unit/MailboxPolicyTest.php` - Mailbox authorization
- `tests/Unit/UserPolicyTest.php` - User authorization

#### Services (3 files)
- `tests/Unit/ImapServiceTest.php` - IMAP client (basic)
- `tests/Unit/ImapServiceAdvancedTest.php` - IMAP client (advanced, slow)
- `tests/Unit/SmtpServiceTest.php` - SMTP client

#### Events & Broadcasting (2 files)
- `tests/Unit/EventBroadcastingTest.php` - Real-time events
- `tests/Unit/EventsTest.php` - Event properties

#### Mail System (5 files)
- `tests/Unit/MailHelperTest.php` - Email helper utilities
- `tests/Unit/MailTest.php` - Mailable structure
- `tests/Unit/MailVarsTest.php` - Email variable replacement
- `tests/Unit/SendAutoReplyJobTest.php` - Auto-reply job
- `tests/Unit/SendAutoReplyListenerTest.php` - Auto-reply listener

#### Other (5 files)
- `tests/Unit/FolderEdgeCasesTest.php` - Folder edge cases (Batch 2)
- `tests/Unit/FolderHierarchyTest.php` - Folder hierarchy (Batch 2)
- `tests/Unit/MailboxControllerValidationTest.php` - Mailbox validation (Batch 2)
- `tests/Unit/MailboxScopesTest.php` - Mailbox query scopes (Batch 2)
- `tests/Unit/ModelRelationshipsTest.php` - Eloquent relationships
- `tests/Unit/ThreadObserverTest.php` - Thread observer lifecycle

### Frontend Tests (2+ files)
- `tests/javascript/notifications.test.js` - Real-time notifications
- `tests/javascript/ui-helpers.test.js` - SweetAlert2 utilities

### Test Support Files
- `tests/TestCase.php` - Base test case class
- `tests/setup.js` - Frontend test setup (Vitest)

**Total: 68 test files (66 backend + 2 frontend)**

---

### Run All Tests
```bash
php artisan test
```

### Run Specific Batch
```bash
# Batch 1: User tests
php artisan test tests/Feature/AuthenticationBatch1Test.php
php artisan test tests/Feature/UserManagementAdminBatch1Test.php
php artisan test tests/Feature/UserSecurityBatch1Test.php
php artisan test tests/Unit/UserModelBatch1Test.php

# Batch 2: Mailbox tests
php artisan test tests/Feature/MailboxAutoReplyTest.php
php artisan test tests/Feature/MailboxFetchEmailsTest.php
php artisan test tests/Feature/MailboxRegressionTest.php
php artisan test tests/Feature/MailboxViewTest.php
php artisan test tests/Unit/FolderEdgeCasesTest.php
php artisan test tests/Unit/FolderHierarchyTest.php
php artisan test tests/Unit/MailboxControllerValidationTest.php
php artisan test tests/Unit/MailboxScopesTest.php

# Batch 4: Customer tests
php artisan test tests/Feature/CustomerAjaxTest.php
php artisan test tests/Feature/CustomerManagementTest.php
php artisan test tests/Feature/CustomerRegressionTest.php
php artisan test tests/Unit/CustomerModelTest.php
php artisan test tests/Unit/EmailModelEnhancedTest.php

# Batch 5: Settings tests
php artisan test tests/Feature/OptionRegressionTest.php
php artisan test tests/Feature/SecurityAndEdgeCasesTest.php
php artisan test tests/Feature/SettingsTest.php
php artisan test tests/Feature/SystemTest.php
php artisan test tests/Unit/OptionModelTest.php
php artisan test tests/Unit/SettingsControllerTest.php
```

### Exclude Slow Tests
```bash
php artisan test --exclude-group=slow
```

### Run with Coverage
```bash
php artisan test --coverage
```

### Run Frontend Tests
```bash
npm test
npm run test:ui
npm run test:coverage
```

---

## 📈 Test Coverage Goals

### Current Coverage
- **Unit Tests**: Comprehensive model, service, and helper coverage
- **Feature Tests**: End-to-end HTTP workflows for all major features
- **Frontend Tests**: JavaScript module testing with Vitest
- **Integration Tests**: Real IMAP/SMTP connections (grouped as slow)

### Coverage Targets
- **Overall**: 80%+ code coverage
- **Models**: 90%+ coverage (high business logic concentration)
- **Controllers**: 75%+ coverage (HTTP layer)
- **Services**: 85%+ coverage (complex business logic)
- **Frontend**: 70%+ coverage (JavaScript modules)

### Measuring Coverage
```bash
# Generate coverage report
php artisan test --coverage --min=80

# View detailed HTML report
php artisan test --coverage-html coverage-report/
```

---

## 🔧 Test Maintenance

### Adding New Tests
1. Identify the appropriate batch/category
2. Create test file following naming conventions:
   - Feature tests: `tests/Feature/[Feature]Test.php`
   - Unit tests: `tests/Unit/[Component]Test.php`
3. Use PHP 8 attributes: `#[Test]`, `#[Group('slow')]`
4. Include RefreshDatabase trait for database tests
5. Document test purpose in docblocks

### Test Naming Conventions
- Test methods should be descriptive: `user_can_create_conversation()`
- Group related tests in the same file
- Use `#[Group()]` attribute for categorization
- Mark slow tests with `#[Group('slow')]`

### Database Testing Best Practices
- Use `RefreshDatabase` trait for isolation
- Use factories for test data creation
- Mock external services (IMAP, SMTP) in unit tests
- Use real connections only in integration tests
- Use SQLite for fast unit tests, MySQL for feature tests

### Mocking Guidelines
- Mock external services (IMAP, SMTP, APIs)
- Don't mock models or Eloquent relationships
- Use Laravel's built-in mocking helpers
- Keep mocks simple and focused

---

## 📚 Additional Resources

### Documentation Files
- **`docs/BATCH_1_TESTS.md`**: Detailed Batch 1 test documentation
- **`docs/BATCH_2_TESTS.md`**: Detailed Batch 2 test documentation
- **`docs/BATCH_4_TESTS.md`**: Detailed Batch 4 test documentation
- **`docs/BATCH_5_TESTS.md`**: Detailed Batch 5 test documentation
- **`docs/FINAL_SUMMARY.md`**: Overall test validation summary
- **`docs/TEST_VALIDATION_SUMMARY.md`**: Test validation results
- **`docs/PROGRESS.md`**: Overall modernization progress including testing

### Test Infrastructure
- **`phpunit.xml`**: PHPUnit configuration
- **`vitest.config.js`**: Vitest configuration for frontend tests
- **`tests/TestCase.php`**: Base test case class
- **`tests/setup.js`**: Frontend test setup
- **`scripts/setup-dev-environment.sh`**: Automated test environment setup

### Related Documentation
- **Laravel Testing**: https://laravel.com/docs/11.x/testing
- **PHPUnit Documentation**: https://phpunit.de/documentation.html
- **Vitest Documentation**: https://vitest.dev/

---

## 🎯 Success Metrics

### Test Quality Indicators
- ✅ All tests passing (100% pass rate)
- ✅ Zero PHPUnit deprecation warnings
- ✅ Fast test execution (< 60 seconds for full suite excluding slow tests)
- ✅ High code coverage (80%+ target)
- ✅ Clear, descriptive test names
- ✅ Proper test isolation (no interdependencies)
- ✅ Comprehensive edge case coverage

### Continuous Improvement
- Regular review and refactoring of test code
- Addition of new tests for bug fixes
- Performance optimization of slow tests
- Documentation updates for new test patterns
- Coverage gap analysis and remediation

---

**This document is maintained alongside the test suite and should be updated when new tests are added or test infrastructure changes.**
