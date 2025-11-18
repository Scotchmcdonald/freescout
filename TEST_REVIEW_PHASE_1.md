# Comprehensive Test Review Analysis

**Generated:** 2025-11-18
**Repository:** Scotchmcdonald/freescout

---

## Phase 1: Complete Classes and Methods Inventory

### Summary Statistics

- **Total Classes Analyzed:** 116
- **Total Methods Found:** 410
- **Average Methods per Class:** 3.5

### Classes by Category

- **Console:** 15 classes
- **Events:** 11 classes
- **Http:** 23 classes
- **Jobs:** 5 classes
- **Listeners:** 14 classes
- **Mail:** 8 classes
- **Misc:** 3 classes
- **Models:** 18 classes
- **Observers:** 6 classes
- **Policies:** 5 classes
- **Providers:** 3 classes
- **Root:** 1 classes
- **Services:** 2 classes
- **View:** 2 classes

---

## Detailed Class and Method Listing

### Console (15 classes)

#### AfterAppUpdate

- **Full Name:** `AfterAppUpdate`
- **Category:** Console
- **Source File:** `app/Console/Commands/AfterAppUpdate.php`
- **Coverage Report:** `Console/Commands/AfterAppUpdate.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### CheckRequirements

- **Full Name:** `CheckRequirements`
- **Category:** Console
- **Source File:** `app/Console/Commands/CheckRequirements.php`
- **Coverage Report:** `Console/Commands/CheckRequirements.php.html`
- **Total Methods:** 5

**Methods:**

1. `checkDirectoryPermissions()`
2. `checkRequiredExtensions()`
3. `checkRequiredFunctions()`
4. `handle()`
5. `outputItems()`

#### ClearCache

- **Full Name:** `ClearCache`
- **Category:** Console
- **Source File:** `app/Console/Commands/ClearCache.php`
- **Coverage Report:** `Console/Commands/ClearCache.php.html`
- **Total Methods:** 2

**Methods:**

1. `__construct()`
2. `handle()`

#### ConfigureGmailMailbox

- **Full Name:** `ConfigureGmailMailbox`
- **Category:** Console
- **Source File:** `app/Console/Commands/ConfigureGmailMailbox.php`
- **Coverage Report:** `Console/Commands/ConfigureGmailMailbox.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### CreateUser

- **Full Name:** `CreateUser`
- **Category:** Console
- **Source File:** `app/Console/Commands/CreateUser.php`
- **Coverage Report:** `Console/Commands/CreateUser.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### FetchEmails

- **Full Name:** `FetchEmails`
- **Category:** Console
- **Source File:** `app/Console/Commands/FetchEmails.php`
- **Coverage Report:** `Console/Commands/FetchEmails.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### GenerateVars

- **Full Name:** `GenerateVars`
- **Category:** Console
- **Source File:** `app/Console/Commands/GenerateVars.php`
- **Coverage Report:** `Console/Commands/GenerateVars.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### Kernel

- **Full Name:** `Kernel`
- **Category:** Console
- **Source File:** `app/Console/Kernel.php`
- **Coverage Report:** `Console/Kernel.php.html`
- **Total Methods:** 2

**Methods:**

1. `commands()`
2. `schedule()`

#### LogoutUsers

- **Full Name:** `LogoutUsers`
- **Category:** Console
- **Source File:** `app/Console/Commands/LogoutUsers.php`
- **Coverage Report:** `Console/Commands/LogoutUsers.php.html`
- **Total Methods:** 2

**Methods:**

1. `__construct()`
2. `handle()`

#### ModuleBuild

- **Full Name:** `ModuleBuild`
- **Category:** Console
- **Source File:** `app/Console/Commands/ModuleBuild.php`
- **Coverage Report:** `Console/Commands/ModuleBuild.php.html`
- **Total Methods:** 3

**Methods:**

1. `buildModule()`
2. `buildVars()`
3. `handle()`

#### ModuleInstall

- **Full Name:** `ModuleInstall`
- **Category:** Console
- **Source File:** `app/Console/Commands/ModuleInstall.php`
- **Coverage Report:** `Console/Commands/ModuleInstall.php.html`
- **Total Methods:** 3

**Methods:**

1. `__construct()`
2. `createModulePublicSymlink()`
3. `handle()`

#### ModuleUpdate

- **Full Name:** `ModuleUpdate`
- **Category:** Console
- **Source File:** `app/Console/Commands/ModuleUpdate.php`
- **Coverage Report:** `Console/Commands/ModuleUpdate.php.html`
- **Total Methods:** 2

**Methods:**

1. `__construct()`
2. `handle()`

#### TestEventSystem

- **Full Name:** `TestEventSystem`
- **Category:** Console
- **Source File:** `app/Console/Commands/TestEventSystem.php`
- **Coverage Report:** `Console/Commands/TestEventSystem.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### Update

- **Full Name:** `Update`
- **Category:** Console
- **Source File:** `app/Console/Commands/Update.php`
- **Coverage Report:** `Console/Commands/Update.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### UpdateFolderCounters

- **Full Name:** `UpdateFolderCounters`
- **Category:** Console
- **Source File:** `app/Console/Commands/UpdateFolderCounters.php`
- **Coverage Report:** `Console/Commands/UpdateFolderCounters.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

### Events (11 classes)

#### ConversationStatusChanged

- **Full Name:** `ConversationStatusChanged`
- **Category:** Events
- **Source File:** `app/Events/ConversationStatusChanged.php`
- **Coverage Report:** `Events/ConversationStatusChanged.php.html`
- **Total Methods:** 1

**Methods:**

1. `__construct()`

#### ConversationUpdated

- **Full Name:** `ConversationUpdated`
- **Category:** Events
- **Source File:** `app/Events/ConversationUpdated.php`
- **Coverage Report:** `Events/ConversationUpdated.php.html`
- **Total Methods:** 4

**Methods:**

1. `__construct()`
2. `broadcastAs()`
3. `broadcastOn()`
4. `broadcastWith()`

#### ConversationUserChanged

- **Full Name:** `ConversationUserChanged`
- **Category:** Events
- **Source File:** `app/Events/ConversationUserChanged.php`
- **Coverage Report:** `Events/ConversationUserChanged.php.html`
- **Total Methods:** 1

**Methods:**

1. `__construct()`

#### CustomerCreatedConversation

- **Full Name:** `CustomerCreatedConversation`
- **Category:** Events
- **Source File:** `app/Events/CustomerCreatedConversation.php`
- **Coverage Report:** `Events/CustomerCreatedConversation.php.html`
- **Total Methods:** 1

**Methods:**

1. `__construct()`

#### CustomerReplied

- **Full Name:** `CustomerReplied`
- **Category:** Events
- **Source File:** `app/Events/CustomerReplied.php`
- **Coverage Report:** `Events/CustomerReplied.php.html`
- **Total Methods:** 1

**Methods:**

1. `__construct()`

#### NewMessageReceived

- **Full Name:** `NewMessageReceived`
- **Category:** Events
- **Source File:** `app/Events/NewMessageReceived.php`
- **Coverage Report:** `Events/NewMessageReceived.php.html`
- **Total Methods:** 4

**Methods:**

1. `__construct()`
2. `broadcastAs()`
3. `broadcastOn()`
4. `broadcastWith()`

#### UserAddedNote

- **Full Name:** `UserAddedNote`
- **Category:** Events
- **Source File:** `app/Events/UserAddedNote.php`
- **Coverage Report:** `Events/UserAddedNote.php.html`
- **Total Methods:** 1

**Methods:**

1. `__construct()`

#### UserCreatedConversation

- **Full Name:** `UserCreatedConversation`
- **Category:** Events
- **Source File:** `app/Events/UserCreatedConversation.php`
- **Coverage Report:** `Events/UserCreatedConversation.php.html`
- **Total Methods:** 1

**Methods:**

1. `__construct()`

#### UserDeleted

- **Full Name:** `UserDeleted`
- **Category:** Events
- **Source File:** `app/Events/UserDeleted.php`
- **Coverage Report:** `Events/UserDeleted.php.html`
- **Total Methods:** 1

**Methods:**

1. `__construct()`

#### UserReplied

- **Full Name:** `UserReplied`
- **Category:** Events
- **Source File:** `app/Events/UserReplied.php`
- **Coverage Report:** `Events/UserReplied.php.html`
- **Total Methods:** 1

**Methods:**

1. `__construct()`

#### UserViewingConversation

- **Full Name:** `UserViewingConversation`
- **Category:** Events
- **Source File:** `app/Events/UserViewingConversation.php`
- **Coverage Report:** `Events/UserViewingConversation.php.html`
- **Total Methods:** 4

**Methods:**

1. `__construct()`
2. `broadcastAs()`
3. `broadcastOn()`
4. `broadcastWith()`

### Http (23 classes)

#### AuthenticatedSessionController

- **Full Name:** `AuthenticatedSessionController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- **Coverage Report:** `Http/Controllers/Auth/AuthenticatedSessionController.php.html`
- **Total Methods:** 3

**Methods:**

1. `create()`
2. `destroy()`
3. `store()`

#### ConfirmablePasswordController

- **Full Name:** `ConfirmablePasswordController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/Auth/ConfirmablePasswordController.php`
- **Coverage Report:** `Http/Controllers/Auth/ConfirmablePasswordController.php.html`
- **Total Methods:** 2

**Methods:**

1. `show()`
2. `store()`

#### Controller

- **Full Name:** `Controller`
- **Category:** Http
- **Source File:** `app/Http/Controllers/Controller.php`
- **Coverage Report:** `Http/Controllers/Controller.php.html`
- **Total Methods:** 0

**No methods found** (may be interface or trait)

#### ConversationController

- **Full Name:** `ConversationController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/ConversationController.php`
- **Coverage Report:** `Http/Controllers/ConversationController.php.html`
- **Total Methods:** 18

**Methods:**

1. `ajax()`
2. `ajaxHtml()`
3. `changeCustomer()`
4. `chats()`
5. `clone()`
6. `create()`
7. `destroy()`
8. `index()`
9. `merge()`
10. `move()`
11. `reply()`
12. `search()`
13. `show()`
14. `store()`
15. `update()`
16. `updateSettings()`
17. `updateThread()`
18. `upload()`

#### CustomerController

- **Full Name:** `CustomerController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/CustomerController.php`
- **Coverage Report:** `Http/Controllers/CustomerController.php.html`
- **Total Methods:** 10

**Methods:**

1. `ajax()`
2. `conversations()`
3. `destroy()`
4. `edit()`
5. `index()`
6. `merge()`
7. `mergeForm()`
8. `show()`
9. `store()`
10. `update()`

#### DashboardController

- **Full Name:** `DashboardController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/DashboardController.php`
- **Coverage Report:** `Http/Controllers/DashboardController.php.html`
- **Total Methods:** 1

**Methods:**

1. `index()`

#### EmailVerificationNotificationController

- **Full Name:** `EmailVerificationNotificationController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/Auth/EmailVerificationNotificationController.php`
- **Coverage Report:** `Http/Controllers/Auth/EmailVerificationNotificationController.php.html`
- **Total Methods:** 1

**Methods:**

1. `store()`

#### EmailVerificationPromptController

- **Full Name:** `EmailVerificationPromptController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/Auth/EmailVerificationPromptController.php`
- **Coverage Report:** `Http/Controllers/Auth/EmailVerificationPromptController.php.html`
- **Total Methods:** 1

**Methods:**

1. `__invoke()`

#### EnsureUserIsAdmin

- **Full Name:** `EnsureUserIsAdmin`
- **Category:** Http
- **Source File:** `app/Http/Middleware/EnsureUserIsAdmin.php`
- **Coverage Report:** `Http/Middleware/EnsureUserIsAdmin.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### FrameGuard

- **Full Name:** `FrameGuard`
- **Category:** Http
- **Source File:** `app/Http/Middleware/FrameGuard.php`
- **Coverage Report:** `Http/Middleware/FrameGuard.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### LoginRequest

- **Full Name:** `LoginRequest`
- **Category:** Http
- **Source File:** `app/Http/Requests/Auth/LoginRequest.php`
- **Coverage Report:** `Http/Requests/Auth/LoginRequest.php.html`
- **Total Methods:** 5

**Methods:**

1. `authenticate()`
2. `authorize()`
3. `ensureIsNotRateLimited()`
4. `rules()`
5. `throttleKey()`

#### MailboxController

- **Full Name:** `MailboxController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/MailboxController.php`
- **Coverage Report:** `Http/Controllers/MailboxController.php.html`
- **Total Methods:** 17

**Methods:**

1. `ajax()`
2. `autoReply()`
3. `connectionIncoming()`
4. `connectionOutgoing()`
5. `create()`
6. `destroy()`
7. `fetchEmails()`
8. `index()`
9. `permissions()`
10. `saveAutoReply()`
11. `saveConnectionIncoming()`
12. `saveConnectionOutgoing()`
13. `settings()`
14. `show()`
15. `store()`
16. `update()`
17. `updatePermissions()`

#### ModulesController

- **Full Name:** `ModulesController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/ModulesController.php`
- **Coverage Report:** `Http/Controllers/ModulesController.php.html`
- **Total Methods:** 5

**Methods:**

1. `__construct()`
2. `delete()`
3. `disable()`
4. `enable()`
5. `index()`

#### NewPasswordController

- **Full Name:** `NewPasswordController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/Auth/NewPasswordController.php`
- **Coverage Report:** `Http/Controllers/Auth/NewPasswordController.php.html`
- **Total Methods:** 2

**Methods:**

1. `create()`
2. `store()`

#### PasswordController

- **Full Name:** `PasswordController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/Auth/PasswordController.php`
- **Coverage Report:** `Http/Controllers/Auth/PasswordController.php.html`
- **Total Methods:** 1

**Methods:**

1. `update()`

#### PasswordResetLinkController

- **Full Name:** `PasswordResetLinkController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/Auth/PasswordResetLinkController.php`
- **Coverage Report:** `Http/Controllers/Auth/PasswordResetLinkController.php.html`
- **Total Methods:** 2

**Methods:**

1. `create()`
2. `store()`

#### ProfileController

- **Full Name:** `ProfileController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/ProfileController.php`
- **Coverage Report:** `Http/Controllers/ProfileController.php.html`
- **Total Methods:** 3

**Methods:**

1. `destroy()`
2. `edit()`
3. `update()`

#### ProfileUpdateRequest

- **Full Name:** `ProfileUpdateRequest`
- **Category:** Http
- **Source File:** `app/Http/Requests/ProfileUpdateRequest.php`
- **Coverage Report:** `Http/Requests/ProfileUpdateRequest.php.html`
- **Total Methods:** 2

**Methods:**

1. `prepareForValidation()`
2. `rules()`

#### RegisteredUserController

- **Full Name:** `RegisteredUserController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/Auth/RegisteredUserController.php`
- **Coverage Report:** `Http/Controllers/Auth/RegisteredUserController.php.html`
- **Total Methods:** 2

**Methods:**

1. `create()`
2. `store()`

#### SettingsController

- **Full Name:** `SettingsController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/SettingsController.php`
- **Coverage Report:** `Http/Controllers/SettingsController.php.html`
- **Total Methods:** 14

**Methods:**

1. `alerts()`
2. `clearCache()`
3. `email()`
4. `index()`
5. `migrate()`
6. `sendTestAlert()`
7. `system()`
8. `testImap()`
9. `testSmtp()`
10. `update()`
11. `updateAlerts()`
12. `updateEmail()`
13. `updateEnvFile()`
14. `validateSmtp()`

#### SystemController

- **Full Name:** `SystemController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/SystemController.php`
- **Coverage Report:** `Http/Controllers/SystemController.php.html`
- **Total Methods:** 4

**Methods:**

1. `ajax()`
2. `diagnostics()`
3. `index()`
4. `logs()`

#### UserController

- **Full Name:** `UserController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/UserController.php`
- **Coverage Report:** `Http/Controllers/UserController.php.html`
- **Total Methods:** 14

**Methods:**

1. `ajax()`
2. `create()`
3. `destroy()`
4. `edit()`
5. `index()`
6. `notifications()`
7. `permissions()`
8. `permissionsForm()`
9. `show()`
10. `store()`
11. `update()`
12. `updateNotifications()`
13. `userSetup()`
14. `userSetupSave()`

#### VerifyEmailController

- **Full Name:** `VerifyEmailController`
- **Category:** Http
- **Source File:** `app/Http/Controllers/Auth/VerifyEmailController.php`
- **Coverage Report:** `Http/Controllers/Auth/VerifyEmailController.php.html`
- **Total Methods:** 1

**Methods:**

1. `__invoke()`

### Jobs (5 classes)

#### SendAlert

- **Full Name:** `SendAlert`
- **Category:** Jobs
- **Source File:** `app/Jobs/SendAlert.php`
- **Coverage Report:** `Jobs/SendAlert.php.html`
- **Total Methods:** 2

**Methods:**

1. `__construct()`
2. `handle()`

#### SendAutoReply

- **Full Name:** `SendAutoReply`
- **Category:** Jobs
- **Source File:** `app/Jobs/SendAutoReply.php`
- **Coverage Report:** `Jobs/SendAutoReply.php.html`
- **Total Methods:** 3

**Methods:**

1. `__construct()`
2. `failed()`
3. `handle()`

#### SendConversationReply

- **Full Name:** `SendConversationReply`
- **Category:** Jobs
- **Source File:** `app/Jobs/SendConversationReply.php`
- **Coverage Report:** `Jobs/SendConversationReply.php.html`
- **Total Methods:** 2

**Methods:**

1. `__construct()`
2. `handle()`

#### SendEmailReplyError

- **Full Name:** `SendEmailReplyError`
- **Category:** Jobs
- **Source File:** `app/Jobs/SendEmailReplyError.php`
- **Coverage Report:** `Jobs/SendEmailReplyError.php.html`
- **Total Methods:** 2

**Methods:**

1. `__construct()`
2. `handle()`

#### SendNotificationToUsers

- **Full Name:** `SendNotificationToUsers`
- **Category:** Jobs
- **Source File:** `app/Jobs/SendNotificationToUsers.php`
- **Coverage Report:** `Jobs/SendNotificationToUsers.php.html`
- **Total Methods:** 3

**Methods:**

1. `__construct()`
2. `failed()`
3. `handle()`

### Listeners (14 classes)

#### HandleNewMessage

- **Full Name:** `HandleNewMessage`
- **Category:** Listeners
- **Source File:** `app/Listeners/HandleNewMessage.php`
- **Coverage Report:** `Listeners/HandleNewMessage.php.html`
- **Total Methods:** 2

**Methods:**

1. `__construct()`
2. `handle()`

#### LogFailedLogin

- **Full Name:** `LogFailedLogin`
- **Category:** Listeners
- **Source File:** `app/Listeners/LogFailedLogin.php`
- **Coverage Report:** `Listeners/LogFailedLogin.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### LogLockout

- **Full Name:** `LogLockout`
- **Category:** Listeners
- **Source File:** `app/Listeners/LogLockout.php`
- **Coverage Report:** `Listeners/LogLockout.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### LogPasswordReset

- **Full Name:** `LogPasswordReset`
- **Category:** Listeners
- **Source File:** `app/Listeners/LogPasswordReset.php`
- **Coverage Report:** `Listeners/LogPasswordReset.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### LogRegisteredUser

- **Full Name:** `LogRegisteredUser`
- **Category:** Listeners
- **Source File:** `app/Listeners/LogRegisteredUser.php`
- **Coverage Report:** `Listeners/LogRegisteredUser.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### LogSuccessfulLogin

- **Full Name:** `LogSuccessfulLogin`
- **Category:** Listeners
- **Source File:** `app/Listeners/LogSuccessfulLogin.php`
- **Coverage Report:** `Listeners/LogSuccessfulLogin.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### LogSuccessfulLogout

- **Full Name:** `LogSuccessfulLogout`
- **Category:** Listeners
- **Source File:** `app/Listeners/LogSuccessfulLogout.php`
- **Coverage Report:** `Listeners/LogSuccessfulLogout.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### LogUserDeletion

- **Full Name:** `LogUserDeletion`
- **Category:** Listeners
- **Source File:** `app/Listeners/LogUserDeletion.php`
- **Coverage Report:** `Listeners/LogUserDeletion.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### RememberUserLocale

- **Full Name:** `RememberUserLocale`
- **Category:** Listeners
- **Source File:** `app/Listeners/RememberUserLocale.php`
- **Coverage Report:** `Listeners/RememberUserLocale.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### SendAutoReply

- **Full Name:** `SendAutoReply`
- **Category:** Listeners
- **Source File:** `app/Listeners/SendAutoReply.php`
- **Coverage Report:** `Listeners/SendAutoReply.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### SendNotificationToUsers

- **Full Name:** `SendNotificationToUsers`
- **Category:** Listeners
- **Source File:** `app/Listeners/SendNotificationToUsers.php`
- **Coverage Report:** `Listeners/SendNotificationToUsers.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### SendPasswordChanged

- **Full Name:** `SendPasswordChanged`
- **Category:** Listeners
- **Source File:** `app/Listeners/SendPasswordChanged.php`
- **Coverage Report:** `Listeners/SendPasswordChanged.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### SendReplyToCustomer

- **Full Name:** `SendReplyToCustomer`
- **Category:** Listeners
- **Source File:** `app/Listeners/SendReplyToCustomer.php`
- **Coverage Report:** `Listeners/SendReplyToCustomer.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

#### UpdateMailboxCounters

- **Full Name:** `UpdateMailboxCounters`
- **Category:** Listeners
- **Source File:** `app/Listeners/UpdateMailboxCounters.php`
- **Coverage Report:** `Listeners/UpdateMailboxCounters.php.html`
- **Total Methods:** 1

**Methods:**

1. `handle()`

### Mail (8 classes)

#### Alert

- **Full Name:** `Alert`
- **Category:** Mail
- **Source File:** `app/Mail/Alert.php`
- **Coverage Report:** `Mail/Alert.php.html`
- **Total Methods:** 3

**Methods:**

1. `__construct()`
2. `content()`
3. `envelope()`

#### AutoReply

- **Full Name:** `AutoReply`
- **Category:** Mail
- **Source File:** `app/Mail/AutoReply.php`
- **Coverage Report:** `Mail/AutoReply.php.html`
- **Total Methods:** 4

**Methods:**

1. `__construct()`
2. `build()`
3. `content()`
4. `envelope()`

#### ConversationReplyNotification

- **Full Name:** `ConversationReplyNotification`
- **Category:** Mail
- **Source File:** `app/Mail/ConversationReplyNotification.php`
- **Coverage Report:** `Mail/ConversationReplyNotification.php.html`
- **Total Methods:** 4

**Methods:**

1. `__construct()`
2. `attachments()`
3. `content()`
4. `envelope()`

#### PasswordChanged

- **Full Name:** `PasswordChanged`
- **Category:** Mail
- **Source File:** `app/Mail/PasswordChanged.php`
- **Coverage Report:** `Mail/PasswordChanged.php.html`
- **Total Methods:** 3

**Methods:**

1. `__construct()`
2. `content()`
3. `envelope()`

#### Test

- **Full Name:** `Test`
- **Category:** Mail
- **Source File:** `app/Mail/Test.php`
- **Coverage Report:** `Mail/Test.php.html`
- **Total Methods:** 3

**Methods:**

1. `__construct()`
2. `content()`
3. `envelope()`

#### UserEmailReplyError

- **Full Name:** `UserEmailReplyError`
- **Category:** Mail
- **Source File:** `app/Mail/UserEmailReplyError.php`
- **Coverage Report:** `Mail/UserEmailReplyError.php.html`
- **Total Methods:** 3

**Methods:**

1. `__construct()`
2. `content()`
3. `envelope()`

#### UserInvite

- **Full Name:** `UserInvite`
- **Category:** Mail
- **Source File:** `app/Mail/UserInvite.php`
- **Coverage Report:** `Mail/UserInvite.php.html`
- **Total Methods:** 3

**Methods:**

1. `__construct()`
2. `content()`
3. `envelope()`

#### UserNotification

- **Full Name:** `UserNotification`
- **Category:** Mail
- **Source File:** `app/Mail/UserNotification.php`
- **Coverage Report:** `Mail/UserNotification.php.html`
- **Total Methods:** 4

**Methods:**

1. `__construct()`
2. `build()`
3. `content()`
4. `envelope()`

### Misc (3 classes)

#### Helper

- **Full Name:** `Helper`
- **Category:** Misc
- **Source File:** `app/Misc/Helper.php`
- **Coverage Report:** `Misc/Helper.php.html`
- **Total Methods:** 3

**Methods:**

1. `isInstalled()`
2. `queueWorkerRestart()`
3. `setGuzzleDefaultOptions()`

#### MailHelper

- **Full Name:** `MailHelper`
- **Category:** Misc
- **Source File:** `app/Misc/MailHelper.php`
- **Coverage Report:** `Misc/MailHelper.php.html`
- **Total Methods:** 9

**Methods:**

1. `extractReply()`
2. `formatEmail()`
3. `generateMessageId()`
4. `getMessageIdHash()`
5. `hasVars()`
6. `isAutoResponder()`
7. `parseEmail()`
8. `replaceMailVars()`
9. `sanitizeEmail()`

#### WpApi

- **Full Name:** `WpApi`
- **Category:** Misc
- **Source File:** `app/Misc/WpApi.php`
- **Coverage Report:** `Misc/WpApi.php.html`
- **Total Methods:** 1

**Methods:**

1. `getModules()`

### Models (18 classes)

#### ActivityLog

- **Full Name:** `ActivityLog`
- **Category:** Models
- **Source File:** `app/Models/ActivityLog.php`
- **Coverage Report:** `Models/ActivityLog.php.html`
- **Total Methods:** 7

**Methods:**

1. `casts()`
2. `causer()`
3. `scopeCausedBy()`
4. `scopeForSubject()`
5. `scopeInLog()`
6. `subject()`
7. `user()`

#### Attachment

- **Full Name:** `Attachment`
- **Category:** Models
- **Source File:** `app/Models/Attachment.php`
- **Coverage Report:** `Models/Attachment.php.html`
- **Total Methods:** 5

**Methods:**

1. `casts()`
2. `getFullPathAttribute()`
3. `getHumanFileSizeAttribute()`
4. `isImage()`
5. `thread()`

#### Channel

- **Full Name:** `Channel`
- **Category:** Models
- **Source File:** `app/Models/Channel.php`
- **Coverage Report:** `Models/Channel.php.html`
- **Total Methods:** 3

**Methods:**

1. `casts()`
2. `customers()`
3. `isActive()`

#### Conversation

- **Full Name:** `Conversation`
- **Category:** Models
- **Source File:** `app/Models/Conversation.php`
- **Coverage Report:** `Models/Conversation.php.html`
- **Total Methods:** 13

**Methods:**

1. `casts()`
2. `closedByUser()`
3. `createdByUser()`
4. `customer()`
5. `folder()`
6. `folders()`
7. `followers()`
8. `isActive()`
9. `isClosed()`
10. `mailbox()`
11. `threads()`
12. `updateFolder()`
13. `user()`

#### ConversationFolder

- **Full Name:** `ConversationFolder`
- **Category:** Models
- **Source File:** `app/Models/ConversationFolder.php`
- **Coverage Report:** `Models/ConversationFolder.php.html`
- **Total Methods:** 1

**Methods:**

1. `casts()`

#### Customer

- **Full Name:** `Customer`
- **Category:** Models
- **Source File:** `app/Models/Customer.php`
- **Coverage Report:** `Models/Customer.php.html`
- **Total Methods:** 13

**Methods:**

1. `casts()`
2. `channels()`
3. `conversations()`
4. `create()`
5. `customerChannels()`
6. `emails()`
7. `getFirstName()`
8. `getFullName()`
9. `getFullNameAttribute()`
10. `getMainEmail()`
11. `getPrimaryEmailAttribute()`
12. `setData()`
13. `threads()`

#### CustomerChannel

- **Full Name:** `CustomerChannel`
- **Category:** Models
- **Source File:** `app/Models/CustomerChannel.php`
- **Coverage Report:** `Models/CustomerChannel.php.html`
- **Total Methods:** 2

**Methods:**

1. `casts()`
2. `customer()`

#### Email

- **Full Name:** `Email`
- **Category:** Models
- **Source File:** `app/Models/Email.php`
- **Coverage Report:** `Models/Email.php.html`
- **Total Methods:** 5

**Methods:**

1. `casts()`
2. `customer()`
3. `isPrimary()`
4. `isSecondary()`
5. `sanitizeEmail()`

#### Folder

- **Full Name:** `Folder`
- **Category:** Models
- **Source File:** `app/Models/Folder.php`
- **Coverage Report:** `Models/Folder.php.html`
- **Total Methods:** 11

**Methods:**

1. `casts()`
2. `conversations()`
3. `conversationsViaFolder()`
4. `isDrafts()`
5. `isInbox()`
6. `isSent()`
7. `isSpam()`
8. `isTrash()`
9. `mailbox()`
10. `updateCounters()`
11. `user()`

#### Follower

- **Full Name:** `Follower`
- **Category:** Models
- **Source File:** `app/Models/Follower.php`
- **Coverage Report:** `Models/Follower.php.html`
- **Total Methods:** 3

**Methods:**

1. `casts()`
2. `conversation()`
3. `user()`

#### Mailbox

- **Full Name:** `Mailbox`
- **Category:** Models
- **Source File:** `app/Models/Mailbox.php`
- **Coverage Report:** `Models/Mailbox.php.html`
- **Total Methods:** 6

**Methods:**

1. `casts()`
2. `conversations()`
3. `folders()`
4. `getMailFrom()`
5. `url()`
6. `users()`

#### MailboxUser

- **Full Name:** `MailboxUser`
- **Category:** Models
- **Source File:** `app/Models/MailboxUser.php`
- **Coverage Report:** `Models/MailboxUser.php.html`
- **Total Methods:** 1

**Methods:**

1. `casts()`

#### Module

- **Full Name:** `Module`
- **Category:** Models
- **Source File:** `app/Models/Module.php`
- **Coverage Report:** `Models/Module.php.html`
- **Total Methods:** 4

**Methods:**

1. `activate()`
2. `casts()`
3. `deactivate()`
4. `isActive()`

#### Option

- **Full Name:** `Option`
- **Category:** Models
- **Source File:** `app/Models/Option.php`
- **Coverage Report:** `Models/Option.php.html`
- **Total Methods:** 5

**Methods:**

1. `casts()`
2. `deleteOption()`
3. `get()`
4. `getValue()`
5. `setValue()`

#### SendLog

- **Full Name:** `SendLog`
- **Category:** Models
- **Source File:** `app/Models/SendLog.php`
- **Coverage Report:** `Models/SendLog.php.html`
- **Total Methods:** 8

**Methods:**

1. `casts()`
2. `customer()`
3. `isFailed()`
4. `isSent()`
5. `thread()`
6. `user()`
7. `wasClicked()`
8. `wasOpened()`

#### Subscription

- **Full Name:** `Subscription`
- **Category:** Models
- **Source File:** `app/Models/Subscription.php`
- **Coverage Report:** `Models/Subscription.php.html`
- **Total Methods:** 5

**Methods:**

1. `casts()`
2. `isBrowser()`
3. `isEmail()`
4. `isMobile()`
5. `user()`

#### Thread

- **Full Name:** `Thread`
- **Category:** Models
- **Source File:** `app/Models/Thread.php`
- **Coverage Report:** `Models/Thread.php.html`
- **Total Methods:** 12

**Methods:**

1. `attachments()`
2. `casts()`
3. `conversation()`
4. `createdByUser()`
5. `customer()`
6. `editedByUser()`
7. `isAutoResponder()`
8. `isBounce()`
9. `isCustomerMessage()`
10. `isNote()`
11. `isUserMessage()`
12. `user()`

#### User

- **Full Name:** `User`
- **Category:** Models
- **Source File:** `app/Models/User.php`
- **Coverage Report:** `Models/User.php.html`
- **Total Methods:** 16

**Methods:**

1. `casts()`
2. `conversations()`
3. `folders()`
4. `followedConversations()`
5. `getFirstName()`
6. `getFullName()`
7. `getFullNameAttribute()`
8. `getNameAttribute()`
9. `getPhotoUrl()`
10. `hasAccessToMailbox()`
11. `isActive()`
12. `isAdmin()`
13. `mailboxes()`
14. `subscriptions()`
15. `threads()`
16. `urlSetup()`

### Observers (6 classes)

#### AttachmentObserver

- **Full Name:** `AttachmentObserver`
- **Category:** Observers
- **Source File:** `app/Observers/AttachmentObserver.php`
- **Coverage Report:** `Observers/AttachmentObserver.php.html`
- **Total Methods:** 1

**Methods:**

1. `deleting()`

#### ConversationObserver

- **Full Name:** `ConversationObserver`
- **Category:** Observers
- **Source File:** `app/Observers/ConversationObserver.php`
- **Coverage Report:** `Observers/ConversationObserver.php.html`
- **Total Methods:** 5

**Methods:**

1. `created()`
2. `creating()`
3. `deleting()`
4. `updateFolderCounters()`
5. `updated()`

#### CustomerObserver

- **Full Name:** `CustomerObserver`
- **Category:** Observers
- **Source File:** `app/Observers/CustomerObserver.php`
- **Coverage Report:** `Observers/CustomerObserver.php.html`
- **Total Methods:** 2

**Methods:**

1. `creating()`
2. `deleting()`

#### MailboxObserver

- **Full Name:** `MailboxObserver`
- **Category:** Observers
- **Source File:** `app/Observers/MailboxObserver.php`
- **Coverage Report:** `Observers/MailboxObserver.php.html`
- **Total Methods:** 3

**Methods:**

1. `createDefaultFolders()`
2. `created()`
3. `deleting()`

#### ThreadObserver

- **Full Name:** `ThreadObserver`
- **Category:** Observers
- **Source File:** `app/Observers/ThreadObserver.php`
- **Coverage Report:** `Observers/ThreadObserver.php.html`
- **Total Methods:** 2

**Methods:**

1. `created()`
2. `deleted()`

#### UserObserver

- **Full Name:** `UserObserver`
- **Category:** Observers
- **Source File:** `app/Observers/UserObserver.php`
- **Coverage Report:** `Observers/UserObserver.php.html`
- **Total Methods:** 4

**Methods:**

1. `addDefaultSubscriptions()`
2. `createAdminPersonalFolders()`
3. `created()`
4. `deleting()`

### Policies (5 classes)

#### ConversationPolicy

- **Full Name:** `ConversationPolicy`
- **Category:** Policies
- **Source File:** `app/Policies/ConversationPolicy.php`
- **Coverage Report:** `Policies/ConversationPolicy.php.html`
- **Total Methods:** 6

**Methods:**

1. `checkIsOnlyAssigned()`
2. `delete()`
3. `move()`
4. `update()`
5. `view()`
6. `viewCached()`

#### FolderPolicy

- **Full Name:** `FolderPolicy`
- **Category:** Policies
- **Source File:** `app/Policies/FolderPolicy.php`
- **Coverage Report:** `Policies/FolderPolicy.php.html`
- **Total Methods:** 1

**Methods:**

1. `view()`

#### MailboxPolicy

- **Full Name:** `MailboxPolicy`
- **Category:** Policies
- **Source File:** `app/Policies/MailboxPolicy.php`
- **Coverage Report:** `Policies/MailboxPolicy.php.html`
- **Total Methods:** 9

**Methods:**

1. `admin()`
2. `create()`
3. `delete()`
4. `forceDelete()`
5. `reply()`
6. `restore()`
7. `update()`
8. `view()`
9. `viewAny()`

#### ThreadPolicy

- **Full Name:** `ThreadPolicy`
- **Category:** Policies
- **Source File:** `app/Policies/ThreadPolicy.php`
- **Coverage Report:** `Policies/ThreadPolicy.php.html`
- **Total Methods:** 2

**Methods:**

1. `delete()`
2. `edit()`

#### UserPolicy

- **Full Name:** `UserPolicy`
- **Category:** Policies
- **Source File:** `app/Policies/UserPolicy.php`
- **Coverage Report:** `Policies/UserPolicy.php.html`
- **Total Methods:** 5

**Methods:**

1. `create()`
2. `delete()`
3. `update()`
4. `view()`
5. `viewAny()`

### Providers (3 classes)

#### AppServiceProvider

- **Full Name:** `AppServiceProvider`
- **Category:** Providers
- **Source File:** `app/Providers/AppServiceProvider.php`
- **Coverage Report:** `Providers/AppServiceProvider.php.html`
- **Total Methods:** 2

**Methods:**

1. `boot()`
2. `register()`

#### EventServiceProvider

- **Full Name:** `EventServiceProvider`
- **Category:** Providers
- **Source File:** `app/Providers/EventServiceProvider.php`
- **Coverage Report:** `Providers/EventServiceProvider.php.html`
- **Total Methods:** 2

**Methods:**

1. `boot()`
2. `shouldDiscoverEvents()`

#### ModuleCompatibilityServiceProvider

- **Full Name:** `ModuleCompatibilityServiceProvider`
- **Category:** Providers
- **Source File:** `app/Providers/ModuleCompatibilityServiceProvider.php`
- **Coverage Report:** `Providers/ModuleCompatibilityServiceProvider.php.html`
- **Total Methods:** 2

**Methods:**

1. `boot()`
2. `register()`

### Root (1 classes)

#### Module

- **Full Name:** `Module`
- **Category:** Root
- **Source File:** `app/Module.php`
- **Coverage Report:** `Module.php.html`
- **Total Methods:** 1

**Methods:**

1. `isOfficial()`

### Services (2 classes)

#### ImapService

- **Full Name:** `ImapService`
- **Category:** Services
- **Source File:** `app/Services/ImapService.php`
- **Coverage Report:** `Services/ImapService.php.html`
- **Total Methods:** 12

**Methods:**

1. `createClient()`
2. `createCustomersFromMessage()`
3. `fetchEmails()`
4. `getAddressesWithNames()`
5. `getEncryption()`
6. `getFolders()`
7. `getMessageHeaders()`
8. `getOriginalSenderFromFwd()`
9. `parseAddresses()`
10. `processMessage()`
11. `separateReply()`
12. `testConnection()`

#### SmtpService

- **Full Name:** `SmtpService`
- **Category:** Services
- **Source File:** `app/Services/SmtpService.php`
- **Coverage Report:** `Services/SmtpService.php.html`
- **Total Methods:** 5

**Methods:**

1. `configureSmtp()`
2. `getEncryption()`
3. `testConnection()`
4. `validateMailboxSettings()`
5. `validateSettings()`

### View (2 classes)

#### AppLayout

- **Full Name:** `AppLayout`
- **Category:** View
- **Source File:** `app/View/Components/AppLayout.php`
- **Coverage Report:** `View/Components/AppLayout.php.html`
- **Total Methods:** 1

**Methods:**

1. `render()`

#### GuestLayout

- **Full Name:** `GuestLayout`
- **Category:** View
- **Source File:** `app/View/Components/GuestLayout.php`
- **Coverage Report:** `View/Components/GuestLayout.php.html`
- **Total Methods:** 1

**Methods:**

1. `render()`


---

## Next Steps

✅ **Phase 1 Complete:** All classes and methods extracted

📋 **Phase 2:** For each class and method, determine comprehensive test requirements

🔍 **Phase 3:** Review existing tests and map to requirements

