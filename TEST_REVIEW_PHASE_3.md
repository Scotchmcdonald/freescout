# Test Review Analysis - Phase 3: Test Coverage Mapping

**Generated:** 2025-11-18
**Repository:** Scotchmcdonald/freescout

---

## Phase 3: Existing Test Coverage Review

### Summary

- **Total Test Files:** 219
- **Total Test Methods:** 2024

### Tests by Type

- **Browser Tests:** 2
- **Feature Tests:** 489
- **Integration Tests:** 58
- **Unit Tests:** 1475

### Coverage Areas

- **Authentication:** 80 test files
- **Authorization:** 21 test files
- **Database:** 124 test files
- **Email:** 171 test files
- **Events:** 42 test files
- **HTTP Response:** 73 test files
- **Jobs:** 27 test files
- **Validation:** 29 test files

---

## Test Coverage by Class

This section maps existing tests to required tests for each class.

### Summary by Status

- ✅ **COMPLETE** (80%+ coverage): 3 classes
- 🟡 **PARTIAL** (30-80% coverage): 20 classes
- 🔴 **MINIMAL** (<30% coverage): 46 classes
- ❌ **MISSING** (no tests): 47 classes

---

## Detailed Class Coverage Analysis

### Console

#### ✅ ConfigureGmailMailbox

- **Status:** COMPLETE
- **Actual Tests:** 15
- **Estimated Needed:** 13
- **Coverage:** 100%
- **Test Files:**
  - `tests/Feature/Commands/ConfigureGmailMailboxTest.php` (15 tests)

#### ✅ TestEventSystem

- **Status:** COMPLETE
- **Actual Tests:** 18
- **Estimated Needed:** 14
- **Coverage:** 100%
- **Test Files:**
  - `tests/Feature/Commands/TestEventSystemTest.php` (18 tests)

#### 🔴 AfterAppUpdate

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 10
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Console/Commands/AfterAppUpdateTest.php` (0 tests)

#### 🔴 CheckRequirements

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 35
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Console/Commands/CheckRequirementsTest.php` (0 tests)

#### 🔴 ClearCache

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 19
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Console/Commands/ClearCacheTest.php` (0 tests)

#### 🔴 CreateUser

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 16
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Console/Commands/CreateUserTest.php` (0 tests)

#### 🔴 Kernel

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 14
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Console/KernelTest.php` (0 tests)

#### 🔴 LogoutUsers

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 17
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Console/Commands/LogoutUsersTest.php` (0 tests)

#### 🔴 ModuleInstall

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 27
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Console/Commands/ModuleInstallTest.php` (0 tests)

#### 🔴 ModuleUpdate

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 19
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Console/Commands/ModuleUpdateTest.php` (0 tests)

#### 🔴 UpdateFolderCounters

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 13
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Console/Commands/UpdateFolderCountersTest.php` (0 tests)

#### ❌ FetchEmails

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 14
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ GenerateVars

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 9
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ ModuleBuild

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 27
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ Update

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 12
- **Coverage:** 0%
- **Test Files:** None found

### Events

#### 🔴 NewMessageReceived

- **Status:** MINIMAL
- **Actual Tests:** 5
- **Estimated Needed:** 35
- **Coverage:** 14.3%
- **Test Files:**
  - `tests/Unit/Events/NewMessageReceivedTest.php` (5 tests)

#### ❌ ConversationStatusChanged

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 8
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ ConversationUpdated

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 27
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ ConversationUserChanged

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 8
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ CustomerCreatedConversation

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 8
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ CustomerReplied

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 8
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ UserAddedNote

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 8
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ UserCreatedConversation

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 8
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ UserDeleted

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 8
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ UserReplied

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 8
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ UserViewingConversation

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 26
- **Coverage:** 0%
- **Test Files:** None found

### Http

#### 🔴 ConversationController

- **Status:** MINIMAL
- **Actual Tests:** 35
- **Estimated Needed:** 209
- **Coverage:** 16.7%
- **Test Files:**
  - `tests/Unit/Http/Controllers/ConversationControllerTest.php` (0 tests)
  - `tests/Unit/Controllers/ConversationControllerTest.php` (35 tests)

#### 🔴 CustomerController

- **Status:** MINIMAL
- **Actual Tests:** 20
- **Estimated Needed:** 99
- **Coverage:** 20.2%
- **Test Files:**
  - `tests/Unit/CustomerControllerTest.php` (5 tests)
  - `tests/Unit/Controllers/CustomerControllerTest.php` (15 tests)

#### 🔴 LoginRequest

- **Status:** MINIMAL
- **Actual Tests:** 8
- **Estimated Needed:** 38
- **Coverage:** 21.1%
- **Test Files:**
  - `tests/Unit/Requests/LoginRequestTest.php` (8 tests)

#### 🔴 ModulesController

- **Status:** MINIMAL
- **Actual Tests:** 9
- **Estimated Needed:** 45
- **Coverage:** 20.0%
- **Test Files:**
  - `tests/Unit/Controllers/ModulesControllerTest.php` (9 tests)

#### 🔴 NewPasswordController

- **Status:** MINIMAL
- **Actual Tests:** 5
- **Estimated Needed:** 27
- **Coverage:** 18.5%
- **Test Files:**
  - `tests/Unit/Controllers/Auth/NewPasswordControllerTest.php` (5 tests)

#### 🔴 ProfileController

- **Status:** MINIMAL
- **Actual Tests:** 7
- **Estimated Needed:** 33
- **Coverage:** 21.2%
- **Test Files:**
  - `tests/Unit/Controllers/ProfileControllerTest.php` (7 tests)

#### 🔴 SettingsController

- **Status:** MINIMAL
- **Actual Tests:** 11
- **Estimated Needed:** 132
- **Coverage:** 8.3%
- **Test Files:**
  - `tests/Feature/SettingsControllerTest.php` (7 tests)
  - `tests/Unit/SettingsControllerTest.php` (4 tests)

#### 🔴 UserController

- **Status:** MINIMAL
- **Actual Tests:** 15
- **Estimated Needed:** 147
- **Coverage:** 10.2%
- **Test Files:**
  - `tests/Unit/Controllers/UserControllerTest.php` (15 tests)

#### 🔴 VerifyEmailController

- **Status:** MINIMAL
- **Actual Tests:** 4
- **Estimated Needed:** 18
- **Coverage:** 22.2%
- **Test Files:**
  - `tests/Unit/Controllers/Auth/VerifyEmailControllerTest.php` (4 tests)

#### ❌ Controller

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 8
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ EmailVerificationNotificationController

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 17
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ EmailVerificationPromptController

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 17
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ FrameGuard

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 19
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ MailboxController

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 187
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ PasswordController

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 16
- **Coverage:** 0%
- **Test Files:** None found

#### 🟡 AuthenticatedSessionController

- **Status:** PARTIAL
- **Actual Tests:** 11
- **Estimated Needed:** 28
- **Coverage:** 39.3%
- **Test Files:**
  - `tests/Unit/Controllers/Auth/AuthenticatedSessionControllerTest.php` (11 tests)

#### 🟡 ConfirmablePasswordController

- **Status:** PARTIAL
- **Actual Tests:** 8
- **Estimated Needed:** 26
- **Coverage:** 30.8%
- **Test Files:**
  - `tests/Unit/Controllers/Auth/ConfirmablePasswordControllerTest.php` (8 tests)

#### 🟡 DashboardController

- **Status:** PARTIAL
- **Actual Tests:** 14
- **Estimated Needed:** 18
- **Coverage:** 77.8%
- **Test Files:**
  - `tests/Unit/DashboardControllerTest.php` (2 tests)
  - `tests/Unit/Controllers/DashboardControllerTest.php` (12 tests)

#### 🟡 EnsureUserIsAdmin

- **Status:** PARTIAL
- **Actual Tests:** 5
- **Estimated Needed:** 16
- **Coverage:** 31.2%
- **Test Files:**
  - `tests/Unit/Middleware/EnsureUserIsAdminTest.php` (5 tests)

#### 🟡 PasswordResetLinkController

- **Status:** PARTIAL
- **Actual Tests:** 8
- **Estimated Needed:** 21
- **Coverage:** 38.1%
- **Test Files:**
  - `tests/Unit/Controllers/Auth/PasswordResetLinkControllerTest.php` (8 tests)

#### 🟡 ProfileUpdateRequest

- **Status:** PARTIAL
- **Actual Tests:** 10
- **Estimated Needed:** 21
- **Coverage:** 47.6%
- **Test Files:**
  - `tests/Unit/Requests/ProfileUpdateRequestTest.php` (10 tests)

#### 🟡 RegisteredUserController

- **Status:** PARTIAL
- **Actual Tests:** 11
- **Estimated Needed:** 27
- **Coverage:** 40.7%
- **Test Files:**
  - `tests/Unit/Controllers/Auth/RegisteredUserControllerTest.php` (11 tests)

#### 🟡 SystemController

- **Status:** PARTIAL
- **Actual Tests:** 26
- **Estimated Needed:** 51
- **Coverage:** 51.0%
- **Test Files:**
  - `tests/Feature/SystemControllerTest.php` (8 tests)
  - `tests/Unit/Controllers/SystemControllerTest.php` (18 tests)

### Jobs

#### 🔴 SendAlert

- **Status:** MINIMAL
- **Actual Tests:** 4
- **Estimated Needed:** 27
- **Coverage:** 14.8%
- **Test Files:**
  - `tests/Unit/Jobs/SendAlertTest.php` (4 tests)

#### 🔴 SendAutoReply

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 33
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Jobs/SendAutoReplyTest.php` (0 tests)
  - `tests/Unit/Listeners/SendAutoReplyTest.php` (0 tests)

#### 🔴 SendConversationReply

- **Status:** MINIMAL
- **Actual Tests:** 3
- **Estimated Needed:** 20
- **Coverage:** 15.0%
- **Test Files:**
  - `tests/Unit/Jobs/SendConversationReplyTest.php` (3 tests)

#### 🔴 SendEmailReplyError

- **Status:** MINIMAL
- **Actual Tests:** 3
- **Estimated Needed:** 25
- **Coverage:** 12.0%
- **Test Files:**
  - `tests/Unit/Jobs/SendEmailReplyErrorTest.php` (3 tests)

#### 🟡 SendNotificationToUsers

- **Status:** PARTIAL
- **Actual Tests:** 19
- **Estimated Needed:** 33
- **Coverage:** 57.6%
- **Test Files:**
  - `tests/Unit/Jobs/SendNotificationToUsersTest.php` (19 tests)
  - `tests/Unit/Listeners/SendNotificationToUsersTest.php` (0 tests)

### Listeners

#### ✅ SendNotificationToUsers

- **Status:** COMPLETE
- **Actual Tests:** 19
- **Estimated Needed:** 13
- **Coverage:** 100%
- **Test Files:**
  - `tests/Unit/Jobs/SendNotificationToUsersTest.php` (19 tests)
  - `tests/Unit/Listeners/SendNotificationToUsersTest.php` (0 tests)

#### 🔴 SendAutoReply

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 15
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Jobs/SendAutoReplyTest.php` (0 tests)
  - `tests/Unit/Listeners/SendAutoReplyTest.php` (0 tests)

#### 🔴 SendReplyToCustomer

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 15
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Listeners/SendReplyToCustomerTest.php` (0 tests)

#### ❌ HandleNewMessage

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 15
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ LogFailedLogin

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 11
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ LogLockout

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 11
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ LogPasswordReset

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 11
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ LogRegisteredUser

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 11
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ LogSuccessfulLogin

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 11
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ LogSuccessfulLogout

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 11
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ LogUserDeletion

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 11
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ RememberUserLocale

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 11
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ SendPasswordChanged

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 11
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ UpdateMailboxCounters

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 11
- **Coverage:** 0%
- **Test Files:** None found

### Mail

#### 🔴 Alert

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 24
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Mail/AlertTest.php` (0 tests)

#### 🔴 PasswordChanged

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 22
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Mail/PasswordChangedTest.php` (0 tests)

#### 🔴 UserEmailReplyError

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 22
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Mail/UserEmailReplyErrorTest.php` (0 tests)

#### 🔴 UserInvite

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 22
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Mail/UserInviteTest.php` (0 tests)

#### 🔴 UserNotification

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 33
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Mail/UserNotificationTest.php` (0 tests)

#### ❌ AutoReply

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 30
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ ConversationReplyNotification

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 28
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ Test

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 22
- **Coverage:** 0%
- **Test Files:** None found

### Misc

#### ❌ Helper

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 18
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ WpApi

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 7
- **Coverage:** 0%
- **Test Files:** None found

#### 🟡 MailHelper

- **Status:** PARTIAL
- **Actual Tests:** 18
- **Estimated Needed:** 56
- **Coverage:** 32.1%
- **Test Files:**
  - `tests/Unit/MailHelperTest.php` (18 tests)

### Models

#### 🔴 Conversation

- **Status:** MINIMAL
- **Actual Tests:** 9
- **Estimated Needed:** 99
- **Coverage:** 9.1%
- **Test Files:**
  - `tests/Feature/ConversationTest.php` (9 tests)
  - `tests/Unit/Models/ConversationTest.php` (0 tests)

#### 🔴 ConversationFolder

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 15
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Models/ConversationFolderTest.php` (0 tests)

#### 🔴 Customer

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 105
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Models/CustomerTest.php` (0 tests)

#### 🔴 CustomerChannel

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 22
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Models/CustomerChannelTest.php` (0 tests)

#### 🔴 Email

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 43
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Models/EmailTest.php` (0 tests)

#### 🔴 Folder

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 81
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Models/FolderTest.php` (0 tests)

#### 🔴 Follower

- **Status:** MINIMAL
- **Actual Tests:** 3
- **Estimated Needed:** 29
- **Coverage:** 10.3%
- **Test Files:**
  - `tests/Unit/Models/FollowerTest.php` (3 tests)

#### 🔴 Mailbox

- **Status:** MINIMAL
- **Actual Tests:** 8
- **Estimated Needed:** 49
- **Coverage:** 16.3%
- **Test Files:**
  - `tests/Feature/MailboxTest.php` (8 tests)
  - `tests/Unit/Models/MailboxTest.php` (0 tests)

#### 🔴 Thread

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 88
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Models/ThreadTest.php` (0 tests)

#### 🔴 User

- **Status:** MINIMAL
- **Actual Tests:** 0
- **Estimated Needed:** 113
- **Coverage:** 0.0%
- **Test Files:**
  - `tests/Unit/Models/UserTest.php` (0 tests)

#### ❌ ActivityLog

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 55
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ Attachment

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 41
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ Channel

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 28
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ MailboxUser

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 15
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ Module

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 35
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ Option

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 41
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ SendLog

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 60
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ Subscription

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 40
- **Coverage:** 0%
- **Test Files:** None found

### Observers

#### 🟡 AttachmentObserver

- **Status:** PARTIAL
- **Actual Tests:** 7
- **Estimated Needed:** 10
- **Coverage:** 70.0%
- **Test Files:**
  - `tests/Unit/AttachmentObserverTest.php` (2 tests)
  - `tests/Unit/Observers/AttachmentObserverTest.php` (5 tests)

#### 🟡 ConversationObserver

- **Status:** PARTIAL
- **Actual Tests:** 17
- **Estimated Needed:** 40
- **Coverage:** 42.5%
- **Test Files:**
  - `tests/Unit/ConversationObserverTest.php` (5 tests)
  - `tests/Unit/Observers/ConversationObserverTest.php` (12 tests)

#### 🟡 CustomerObserver

- **Status:** PARTIAL
- **Actual Tests:** 7
- **Estimated Needed:** 16
- **Coverage:** 43.8%
- **Test Files:**
  - `tests/Unit/CustomerObserverTest.php` (2 tests)
  - `tests/Unit/Observers/CustomerObserverTest.php` (5 tests)

#### 🟡 MailboxObserver

- **Status:** PARTIAL
- **Actual Tests:** 12
- **Estimated Needed:** 24
- **Coverage:** 50.0%
- **Test Files:**
  - `tests/Unit/MailboxObserverTest.php` (3 tests)
  - `tests/Unit/Observers/MailboxObserverTest.php` (9 tests)

#### 🟡 ThreadObserver

- **Status:** PARTIAL
- **Actual Tests:** 9
- **Estimated Needed:** 22
- **Coverage:** 40.9%
- **Test Files:**
  - `tests/Unit/ThreadObserverTest.php` (4 tests)
  - `tests/Unit/Observers/ThreadObserverTest.php` (5 tests)

#### 🟡 UserObserver

- **Status:** PARTIAL
- **Actual Tests:** 12
- **Estimated Needed:** 31
- **Coverage:** 38.7%
- **Test Files:**
  - `tests/Unit/UserObserverTest.php` (5 tests)
  - `tests/Unit/Observers/UserObserverTest.php` (7 tests)

### Policies

#### 🔴 ConversationPolicy

- **Status:** MINIMAL
- **Actual Tests:** 9
- **Estimated Needed:** 48
- **Coverage:** 18.8%
- **Test Files:**
  - `tests/Unit/ConversationPolicyTest.php` (9 tests)
  - `tests/Unit/Policies/ConversationPolicyTest.php` (0 tests)

#### 🔴 MailboxPolicy

- **Status:** MINIMAL
- **Actual Tests:** 8
- **Estimated Needed:** 65
- **Coverage:** 12.3%
- **Test Files:**
  - `tests/Unit/MailboxPolicyTest.php` (8 tests)
  - `tests/Unit/Policies/MailboxPolicyTest.php` (0 tests)

#### 🔴 UserPolicy

- **Status:** MINIMAL
- **Actual Tests:** 8
- **Estimated Needed:** 36
- **Coverage:** 22.2%
- **Test Files:**
  - `tests/Unit/UserPolicyTest.php` (8 tests)

#### 🟡 FolderPolicy

- **Status:** PARTIAL
- **Actual Tests:** 4
- **Estimated Needed:** 12
- **Coverage:** 33.3%
- **Test Files:**
  - `tests/Unit/FolderPolicyTest.php` (4 tests)

#### 🟡 ThreadPolicy

- **Status:** PARTIAL
- **Actual Tests:** 7
- **Estimated Needed:** 18
- **Coverage:** 38.9%
- **Test Files:**
  - `tests/Unit/ThreadPolicyTest.php` (7 tests)

### Providers

#### 🔴 AppServiceProvider

- **Status:** MINIMAL
- **Actual Tests:** 4
- **Estimated Needed:** 20
- **Coverage:** 20.0%
- **Test Files:**
  - `tests/Unit/Providers/AppServiceProviderTest.php` (4 tests)

#### ❌ EventServiceProvider

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 19
- **Coverage:** 0%
- **Test Files:** None found

#### ❌ ModuleCompatibilityServiceProvider

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 21
- **Coverage:** 0%
- **Test Files:** None found

### Root

#### ❌ Module

- **Status:** MISSING
- **Actual Tests:** 0
- **Estimated Needed:** 7
- **Coverage:** 0%
- **Test Files:** None found

### Services

#### 🔴 ImapService

- **Status:** MINIMAL
- **Actual Tests:** 20
- **Estimated Needed:** 124
- **Coverage:** 16.1%
- **Test Files:**
  - `tests/Unit/ImapServiceTest.php` (2 tests)
  - `tests/Unit/Services/ImapServiceTest.php` (18 tests)

#### 🔴 SmtpService

- **Status:** MINIMAL
- **Actual Tests:** 7
- **Estimated Needed:** 42
- **Coverage:** 16.7%
- **Test Files:**
  - `tests/Unit/SmtpServiceTest.php` (3 tests)
  - `tests/Unit/Services/SmtpServiceTest.php` (4 tests)

### View

#### 🟡 AppLayout

- **Status:** PARTIAL
- **Actual Tests:** 5
- **Estimated Needed:** 8
- **Coverage:** 62.5%
- **Test Files:**
  - `tests/Unit/View/Components/AppLayoutTest.php` (5 tests)

#### 🟡 GuestLayout

- **Status:** PARTIAL
- **Actual Tests:** 5
- **Estimated Needed:** 8
- **Coverage:** 62.5%
- **Test Files:**
  - `tests/Unit/View/Components/GuestLayoutTest.php` (5 tests)

---

## Priority Recommendations

### High Priority (Missing Tests)

Classes with no test coverage:

- [ ] **FetchEmails** (Console) - 14 tests needed
- [ ] **GenerateVars** (Console) - 9 tests needed
- [ ] **ModuleBuild** (Console) - 27 tests needed
- [ ] **Update** (Console) - 12 tests needed
- [ ] **ConversationStatusChanged** (Events) - 8 tests needed
- [ ] **ConversationUpdated** (Events) - 27 tests needed
- [ ] **ConversationUserChanged** (Events) - 8 tests needed
- [ ] **CustomerCreatedConversation** (Events) - 8 tests needed
- [ ] **CustomerReplied** (Events) - 8 tests needed
- [ ] **UserAddedNote** (Events) - 8 tests needed
- [ ] **UserCreatedConversation** (Events) - 8 tests needed
- [ ] **UserDeleted** (Events) - 8 tests needed
- [ ] **UserReplied** (Events) - 8 tests needed
- [ ] **UserViewingConversation** (Events) - 26 tests needed
- [ ] **Controller** (Http) - 8 tests needed
- [ ] **EmailVerificationNotificationController** (Http) - 17 tests needed
- [ ] **EmailVerificationPromptController** (Http) - 17 tests needed
- [ ] **FrameGuard** (Http) - 19 tests needed
- [ ] **MailboxController** (Http) - 187 tests needed
- [ ] **PasswordController** (Http) - 16 tests needed

...and 27 more

### Medium Priority (Minimal Coverage)

Classes with <30% coverage:

- [ ] **AfterAppUpdate** (Console) - 10 more tests needed
- [ ] **CheckRequirements** (Console) - 35 more tests needed
- [ ] **ClearCache** (Console) - 19 more tests needed
- [ ] **CreateUser** (Console) - 16 more tests needed
- [ ] **Kernel** (Console) - 14 more tests needed
- [ ] **LogoutUsers** (Console) - 17 more tests needed
- [ ] **ModuleInstall** (Console) - 27 more tests needed
- [ ] **ModuleUpdate** (Console) - 19 more tests needed
- [ ] **UpdateFolderCounters** (Console) - 13 more tests needed
- [ ] **NewMessageReceived** (Events) - 30 more tests needed
- [ ] **ConversationController** (Http) - 174 more tests needed
- [ ] **CustomerController** (Http) - 79 more tests needed
- [ ] **LoginRequest** (Http) - 30 more tests needed
- [ ] **ModulesController** (Http) - 36 more tests needed
- [ ] **NewPasswordController** (Http) - 22 more tests needed

...and 31 more

### Low Priority (Partial Coverage)

Classes with 30-80% coverage (enhancement opportunities):

- [ ] **AuthenticatedSessionController** (Http) - 17 more tests for complete coverage
- [ ] **ConfirmablePasswordController** (Http) - 18 more tests for complete coverage
- [ ] **DashboardController** (Http) - 4 more tests for complete coverage
- [ ] **EnsureUserIsAdmin** (Http) - 11 more tests for complete coverage
- [ ] **PasswordResetLinkController** (Http) - 13 more tests for complete coverage
- [ ] **ProfileUpdateRequest** (Http) - 11 more tests for complete coverage
- [ ] **RegisteredUserController** (Http) - 16 more tests for complete coverage
- [ ] **SystemController** (Http) - 25 more tests for complete coverage
- [ ] **SendNotificationToUsers** (Jobs) - 14 more tests for complete coverage
- [ ] **MailHelper** (Misc) - 38 more tests for complete coverage

...and 10 more

---

## All Phases Complete! 🎉

✅ **Phase 1:** All classes and methods inventoried

✅ **Phase 2:** Comprehensive test requirements defined

✅ **Phase 3:** Existing tests mapped to requirements

### Next Steps

1. Review priority recommendations above
2. Focus on HIGH priority classes first (no tests)
3. Gradually improve MINIMAL and PARTIAL coverage
4. Maintain COMPLETE coverage for tested classes

