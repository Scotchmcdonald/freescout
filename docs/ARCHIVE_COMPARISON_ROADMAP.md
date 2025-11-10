# Archive vs Modernized App: Comprehensive Comparison & Roadmap

**Generated**: November 10, 2025  
**Purpose**: File-by-file comparison of archived Laravel 5.5 app vs modernized Laravel 11 app

## Executive Summary

- **Archive App**: 156 files in `archive/app/`
- **Modernized App**: 60 files in `app/`
- **Modernization Progress**: ~97% complete per PROGRESS.md
- **Missing Critical Features**: Primarily Console Commands and supporting infrastructure

---

## 1. Console Commands Comparison

### Archive Commands (24 total)

| Command | Status | Priority | Notes |
|---------|--------|----------|-------|
| **AfterAppUpdate** | ❌ Missing | 🔴 HIGH | Post-update cleanup and optimization |
| **Build** | ❌ Missing | 🟡 MEDIUM | Asset building (replaced by Vite?) |
| **CheckConvViewers** | ❌ Missing | 🟢 LOW | Real-time conversation viewer checking |
| **CheckRequirements** | ❌ Missing | 🔴 HIGH | System requirements validation |
| **CleanNotificationsTable** | ❌ Missing | 🟡 MEDIUM | Database maintenance |
| **CleanSendLog** | ❌ Missing | 🟡 MEDIUM | Database maintenance |
| **CleanTmp** | ❌ Missing | 🟡 MEDIUM | Temporary file cleanup |
| **ClearCache** | ❌ Missing | 🔴 HIGH | Cache management (Laravel has native) |
| **CreateUser** | ❌ Missing | 🔴 HIGH | CLI user creation |
| **FetchEmails** | ✅ Implemented | - | Email fetching via IMAP |
| **FetchMonitor** | ❌ Missing | 🟡 MEDIUM | Monitor email fetching process |
| **GenerateVars** | ❌ Missing | 🟢 LOW | Generate JavaScript variables |
| **LogoutUsers** | ❌ Missing | 🟡 MEDIUM | Force logout all users |
| **LogsMonitor** | ❌ Missing | 🟡 MEDIUM | Monitor application logs |
| **ModuleBuild** | ❌ Missing | 🔴 HIGH | Build module assets |
| **ModuleCheckLicenses** | ❌ Missing | 🟡 MEDIUM | Validate module licenses |
| **ModuleInstall** | ❌ Missing | 🔴 HIGH | Install modules |
| **ModuleLaroute** | ❌ Missing | 🟡 MEDIUM | Generate JS routes for modules |
| **ModuleUpdate** | ❌ Missing | 🔴 HIGH | Update modules |
| **ParseEml** | ❌ Missing | 🟢 LOW | Parse .eml files |
| **SendMonitor** | ❌ Missing | 🟡 MEDIUM | Monitor email sending |
| **Update** | ❌ Missing | 🔴 HIGH | Application update command |
| **UpdateFolderCounters** | ❌ Missing | 🟡 MEDIUM | Recalculate folder counters |
| **ConfigureGmailMailbox** | ✅ New | - | New Gmail OAuth helper |
| **TestEventSystem** | ✅ New | - | New testing command |

**Summary**: 
- ❌ 22 commands missing
- ✅ 1 command implemented (FetchEmails)
- ✅ 2 new commands added

---

## 2. Models Comparison

### Archive Models (18 in archive/app/)

| Model | Status | Notes |
|-------|--------|-------|
| **ActivityLog** | ✅ Implemented | Tracking user activities |
| **Attachment** | ✅ Implemented | File attachments |
| **Conversation** | ✅ Implemented | Core conversation model |
| **ConversationFolder** | ❌ Missing | Pivot table for conversation-folder relationship |
| **Customer** | ✅ Implemented | Customer records |
| **CustomerChannel** | ❌ Missing | Customer communication channels |
| **Email** | ✅ Implemented | Email storage |
| **FailedJob** | ❌ Missing | Failed queue jobs (Laravel native now) |
| **Folder** | ✅ Implemented | Mailbox folders |
| **Follower** | ❌ Missing | Conversation followers |
| **Job** | ❌ Missing | Queue jobs tracking |
| **Mailbox** | ✅ Implemented | Mailbox configuration |
| **MailboxUser** | ❌ Missing | Mailbox user permissions pivot |
| **Module** | ✅ Implemented | Module management |
| **Option** | ✅ Implemented | Application settings |
| **SendLog** | ✅ Implemented | Email send tracking |
| **Sendmail** | ❌ Missing | Sendmail configuration |
| **Subscription** | ✅ Implemented | User subscriptions |
| **Thread** | ✅ Implemented | Conversation threads |
| **User** | ✅ Implemented | User accounts |

### Modernized New Models

| Model | Notes |
|-------|-------|
| **Channel** | New - Broadcasting channels |

**Summary**:
- ✅ 14 core models implemented
- ❌ 6 models missing (mostly pivot/relationship tables)
- ✅ 1 new model added

---

## 3. Controllers Comparison

### Archive Controllers (15 total)

| Controller | Status | Notes |
|------------|--------|-------|
| **ConversationsController** | ✅ Renamed | Now `ConversationController` |
| **CustomersController** | ✅ Renamed | Now `CustomerController` |
| **MailboxesController** | ✅ Renamed | Now `MailboxController` |
| **ModulesController** | ✅ Implemented | Module management |
| **OpenController** | ❌ Missing | Unauthenticated actions (attachments, setup) |
| **SecureController** | ❌ Missing | Main dashboard (split into DashboardController) |
| **SettingsController** | ✅ Implemented | Application settings |
| **SystemController** | ✅ Implemented | System diagnostics |
| **TranslateController** | ❌ Missing | Translation management UI |
| **UsersController** | ✅ Renamed | Now `UserController` |

### New Controllers

| Controller | Purpose |
|------------|---------|
| **DashboardController** | Main dashboard (from SecureController) |
| **ProfileController** | User profile (Laravel Breeze) |

### Auth Controllers

- Archive: 4 controllers (Laravel 5.5 auth)
- Modernized: 9 controllers (Laravel 11 Breeze)

**Summary**:
- ✅ 8 core controllers implemented/renamed
- ❌ 3 controllers missing
- ✅ 2 new controllers added
- ✅ Auth system modernized

---

## 4. Events Comparison

### Archive Events (17 total)

| Event | Status | Purpose |
|-------|--------|---------|
| **ConversationCustomerChanged** | ❌ Missing | Customer reassignment |
| **ConversationStatusChanged** | ❌ Missing | Status changes |
| **ConversationUserChanged** | ❌ Missing | User assignment |
| **CustomerCreatedConversation** | ✅ Implemented | New conversation from customer |
| **CustomerReplied** | ✅ Implemented | Customer reply |
| **RealtimeBroadcastNotificationCreated** | ❌ Missing | Real-time notifications |
| **RealtimeChat** | ❌ Missing | Live chat |
| **RealtimeConvNewThread** | ❌ Missing | New thread notification |
| **RealtimeConvView** | ❌ Missing | Conversation viewing |
| **RealtimeConvViewFinish** | ❌ Missing | Stop viewing |
| **RealtimeMailboxNewThread** | ❌ Missing | Mailbox updates |
| **UserAddedNote** | ❌ Missing | Internal note added |
| **UserCreatedConversation** | ❌ Missing | New conversation from user |
| **UserCreatedConversationDraft** | ❌ Missing | Draft creation |
| **UserCreatedThreadDraft** | ❌ Missing | Thread draft |
| **UserDeleted** | ❌ Missing | User deletion |
| **UserReplied** | ❌ Missing | User reply |

### Modernized Events (5 total)

| Event | Purpose |
|-------|---------|
| **ConversationUpdated** | ✅ Conversation changes (consolidated) |
| **CustomerCreatedConversation** | ✅ New conversation from customer |
| **CustomerReplied** | ✅ Customer reply |
| **NewMessageReceived** | ✅ New message arrived |
| **UserViewingConversation** | ✅ Real-time presence |

**Summary**:
- ✅ 5 core events implemented (consolidated architecture)
- ❌ 15 granular events missing
- Note: Modernized app uses consolidated events vs granular ones

---

## 5. Listeners Comparison

### Archive Listeners (17 total)

| Listener | Status | Purpose |
|----------|--------|---------|
| **ActivateUser** | ❌ Missing | User activation |
| **LogFailedLogin** | ❌ Missing | Security logging |
| **LogLockout** | ❌ Missing | Security logging |
| **LogPasswordReset** | ❌ Missing | Security logging |
| **LogRegisteredUser** | ❌ Missing | Audit logging |
| **LogSuccessfulLogin** | ❌ Missing | Audit logging |
| **LogSuccessfulLogout** | ❌ Missing | Audit logging |
| **LogUserDeletion** | ❌ Missing | Audit logging |
| **ProcessSwiftMessage** | ❌ Missing | Email processing (SwiftMailer) |
| **RefreshConversations** | ❌ Missing | UI refresh |
| **RememberUserLocale** | ❌ Missing | Localization |
| **RestartSwiftMailer** | ❌ Missing | Email system (SwiftMailer) |
| **SendAutoReply** | ✅ Implemented | Auto-reply system |
| **SendNotificationToUsers** | ❌ Missing | User notifications |
| **SendPasswordChanged** | ❌ Missing | Password change email |
| **SendReplyToCustomer** | ❌ Missing | Reply emails |
| **UpdateMailboxCounters** | ❌ Missing | Counter updates |

### Modernized Listeners (2 total)

| Listener | Purpose |
|----------|---------|
| **HandleNewMessage** | ✅ Process incoming messages |
| **SendAutoReply** | ✅ Auto-reply system |

**Summary**:
- ✅ 2 core listeners implemented
- ❌ 16 listeners missing (especially audit logging)

---

## 6. Jobs Comparison

### Archive Jobs (8 total)

| Job | Status | Purpose |
|-----|--------|---------|
| **RestartQueueWorker** | ❌ Missing | Queue management |
| **SendAlert** | ❌ Missing | Alert emails |
| **SendAutoReply** | ✅ Implemented | Auto-replies |
| **SendEmailReplyError** | ❌ Missing | Error notifications |
| **SendNotificationToUsers** | ❌ Missing | User notifications |
| **SendReplyToCustomer** | ❌ Missing | Reply emails |
| **TriggerAction** | ❌ Missing | Workflow automation |
| **UpdateFolderCounters** | ❌ Missing | Counter maintenance |

### Modernized Jobs (2 total)

| Job | Purpose |
|-----|---------|
| **SendAutoReply** | ✅ Auto-reply emails |
| **SendConversationReply** | ✅ Conversation replies |

**Summary**:
- ✅ 2 core jobs implemented
- ❌ 6 jobs missing

---

## 7. Mail Classes Comparison

### Archive Mail (8 total)

| Mailable | Status | Purpose |
|----------|--------|---------|
| **Alert** | ❌ Missing | System alerts |
| **AutoReply** | ✅ Implemented | Auto-reply emails |
| **PasswordChanged** | ❌ Missing | Password change notification |
| **ReplyToCustomer** | ❌ Missing | Reply emails |
| **Test** | ❌ Missing | SMTP test email |
| **UserEmailReplyError** | ❌ Missing | Error notifications |
| **UserInvite** | ❌ Missing | User invitations |
| **UserNotification** | ❌ Missing | User notifications |

### Modernized Mail (2 total)

| Mailable | Purpose |
|----------|---------|
| **AutoReply** | ✅ Auto-reply emails |
| **ConversationReplyNotification** | ✅ Reply notifications |

**Summary**:
- ✅ 2 core mailables implemented
- ❌ 6 mailables missing

---

## 8. Observers Comparison

### Archive Observers (10 total)

| Observer | Status | Purpose |
|----------|--------|---------|
| **AttachmentObserver** | ❌ Missing | Attachment lifecycle |
| **ConversationObserver** | ❌ Missing | Conversation lifecycle |
| **CustomerObserver** | ❌ Missing | Customer lifecycle |
| **DatabaseNotificationObserver** | ❌ Missing | Notification handling |
| **EmailObserver** | ❌ Missing | Email lifecycle |
| **FollowerObserver** | ❌ Missing | Follower management |
| **MailboxObserver** | ❌ Missing | Mailbox lifecycle |
| **SendLogObserver** | ❌ Missing | Send log tracking |
| **ThreadObserver** | ✅ Implemented | Thread lifecycle |
| **UserObserver** | ❌ Missing | User lifecycle |

**Summary**:
- ✅ 1 observer implemented
- ❌ 9 observers missing

---

## 9. Policies Comparison

### Archive Policies (5 total)

| Policy | Status | Purpose |
|--------|--------|---------|
| **ConversationPolicy** | ❌ Missing | Conversation authorization |
| **FolderPolicy** | ❌ Missing | Folder authorization |
| **MailboxPolicy** | ✅ Implemented | Mailbox authorization |
| **ThreadPolicy** | ❌ Missing | Thread authorization |
| **UserPolicy** | ✅ Implemented | User authorization |

**Summary**:
- ✅ 2 core policies implemented
- ❌ 3 policies missing

---

## 10. Providers Comparison

### Archive Providers (6 total)

| Provider | Status | Purpose |
|----------|--------|---------|
| **AppServiceProvider** | ✅ Implemented | Application bootstrap |
| **AuthServiceProvider** | ❌ Missing | Authorization (merged into AppServiceProvider?) |
| **BroadcastServiceProvider** | ❌ Missing | Broadcasting setup |
| **EventServiceProvider** | ✅ Implemented | Event bindings |
| **PolycastServiceProvider** | ❌ Missing | Polycast broadcasting |
| **RouteServiceProvider** | ❌ Missing | Route configuration (Laravel 11 change) |

**Summary**:
- ✅ 2 core providers implemented
- ❌ 4 providers missing/consolidated

---

## 11. Middleware Comparison

### Archive Middleware (14 total)

| Middleware | Status | Purpose |
|------------|--------|---------|
| **CheckRole** | ❌ Missing | Role verification |
| **CustomHandle** | ❌ Missing | Custom request handling |
| **EncryptCookies** | ✅ Laravel Native | Cookie encryption |
| **FrameGuard** | ❌ Missing | X-Frame-Options |
| **HttpsRedirect** | ❌ Missing | Force HTTPS |
| **Localize** | ❌ Missing | Localization |
| **LogoutIfDeleted** | ❌ Missing | Auto-logout deleted users |
| **RedirectIfAuthenticated** | ✅ Laravel Native | Guest middleware |
| **ResponseHeaders** | ❌ Missing | Custom headers |
| **TerminateHandler** | ❌ Missing | Request termination |
| **TokenAuth** | ❌ Missing | API token auth |
| **TrimStrings** | ✅ Laravel Native | Trim input |
| **TrustProxies** | ✅ Laravel Native | Proxy handling |
| **VerifyCsrfToken** | ✅ Laravel Native | CSRF protection |

### Modernized Middleware (1 custom)

| Middleware | Purpose |
|------------|---------|
| **EnsureUserIsAdmin** | ✅ Admin verification |

**Summary**:
- ✅ 1 custom middleware implemented
- ❌ 9 custom middleware missing
- ✅ 4 Laravel native middleware present

---

## 12. Services Comparison

### Archive Services
- None in archive

### Modernized Services (2 new)

| Service | Purpose |
|---------|---------|
| **ImapService** | ✅ IMAP email fetching |
| **SmtpService** | ✅ SMTP email sending |

**Summary**:
- ✅ 2 new service classes (modern architecture)

---

## 13. Misc/Helpers Comparison

### Archive Misc (6 files)

| Helper | Status | Purpose |
|--------|--------|---------|
| **ConversationActionButtons** | ❌ Missing | UI action buttons |
| **Functions** | ❌ Missing | Global helper functions |
| **Helper** | ❌ Missing | Helper utilities |
| **Mail** | ❌ Missing | Email helpers |
| **SwiftGetSmtpQueueId** | ❌ Missing | SwiftMailer utility |
| **WpApi** | ❌ Missing | WordPress API integration |

### Modernized Misc (1 file)

| Helper | Purpose |
|--------|---------|
| **MailHelper** | ✅ Email utilities |

**Summary**:
- ✅ 1 helper class implemented
- ❌ 6 helper classes missing

---

## 14. Implementation Roadmap

### Phase 1: Critical Console Commands (Priority 🔴)

**Goal**: Enable core administrative functions

| Command | Estimated Effort | Dependencies |
|---------|------------------|--------------|
| CreateUser | 2 hours | User model |
| CheckRequirements | 3 hours | System info |
| ClearCache | 1 hour | Cache system |
| Update | 4 hours | Migration system |
| AfterAppUpdate | 2 hours | Cache, optimization |
| ModuleInstall | 4 hours | Module system |
| ModuleBuild | 3 hours | Asset compilation |
| ModuleUpdate | 3 hours | Module system |

**Total**: ~22 hours

### Phase 2: Missing Models (Priority 🔴)

**Goal**: Complete data layer

| Model | Estimated Effort | Dependencies |
|-------|------------------|--------------|
| ConversationFolder | 1 hour | Pivot table |
| CustomerChannel | 2 hours | Customer, Channel |
| Follower | 2 hours | User, Conversation |
| MailboxUser | 1 hour | Pivot table |
| Sendmail | 2 hours | Email system |

**Total**: ~8 hours

### Phase 3: Missing Observers (Priority 🔴)

**Goal**: Model lifecycle hooks

| Observer | Estimated Effort | Dependencies |
|----------|------------------|--------------|
| ConversationObserver | 3 hours | Conversation events |
| UserObserver | 2 hours | User events |
| CustomerObserver | 2 hours | Customer events |
| AttachmentObserver | 2 hours | Storage management |
| MailboxObserver | 2 hours | Mailbox setup |

**Total**: ~11 hours

### Phase 4: Missing Policies (Priority 🔴)

**Goal**: Complete authorization

| Policy | Estimated Effort | Dependencies |
|--------|------------------|--------------|
| ConversationPolicy | 3 hours | Conversation model |
| ThreadPolicy | 2 hours | Thread model |
| FolderPolicy | 2 hours | Folder model |

**Total**: ~7 hours

### Phase 5: Email System Jobs (Priority 🔴)

**Goal**: Complete email workflow

| Job | Estimated Effort | Dependencies |
|-----|------------------|--------------|
| SendNotificationToUsers | 3 hours | User notifications |
| SendEmailReplyError | 2 hours | Error handling |
| SendAlert | 2 hours | Alert system |

**Total**: ~7 hours

### Phase 6: Missing Listeners (Priority 🟡)

**Goal**: Complete event system

| Category | Listeners | Estimated Effort |
|----------|-----------|------------------|
| Audit Logging | 8 listeners | 12 hours |
| Email Processing | 3 listeners | 8 hours |
| User Management | 3 listeners | 6 hours |
| UI Updates | 2 listeners | 4 hours |

**Total**: ~30 hours

### Phase 7: Missing Mail Classes (Priority 🟡)

**Goal**: Complete email templates

| Mailable | Estimated Effort | Dependencies |
|----------|------------------|--------------|
| UserNotification | 3 hours | Notification system |
| UserInvite | 2 hours | User management |
| Test | 1 hour | SMTP testing |
| Alert | 2 hours | Alert system |

**Total**: ~8 hours

### Phase 8: Missing Events (Priority 🟡)

**Goal**: Granular event tracking

| Event Category | Count | Estimated Effort |
|----------------|-------|------------------|
| Conversation Changes | 3 events | 6 hours |
| User Actions | 5 events | 8 hours |
| Real-time Updates | 7 events | 12 hours |

**Total**: ~26 hours

### Phase 9: Middleware & Security (Priority 🟡)

**Goal**: Security and UX features

| Middleware | Estimated Effort | Dependencies |
|------------|------------------|--------------|
| Localize | 3 hours | Translation system |
| CheckRole | 2 hours | Role system |
| LogoutIfDeleted | 1 hour | User management |
| HttpsRedirect | 1 hour | Config |
| FrameGuard | 1 hour | Security headers |

**Total**: ~8 hours

### Phase 10: Utility Commands (Priority 🟢)

**Goal**: Maintenance and monitoring

| Command | Estimated Effort | Dependencies |
|---------|------------------|--------------|
| CleanNotificationsTable | 2 hours | Database |
| CleanSendLog | 2 hours | Database |
| CleanTmp | 1 hour | Filesystem |
| UpdateFolderCounters | 2 hours | Folder system |
| FetchMonitor | 3 hours | Monitoring |
| SendMonitor | 3 hours | Monitoring |
| LogsMonitor | 3 hours | Logging |

**Total**: ~16 hours

### Phase 11: Helper Classes (Priority 🟢)

**Goal**: Utility functions

| Helper | Estimated Effort | Dependencies |
|--------|------------------|--------------|
| Functions | 4 hours | Global helpers |
| Helper | 3 hours | Utilities |
| ConversationActionButtons | 2 hours | UI components |

**Total**: ~9 hours

---

## 15. Implementation Summary

### Total Effort Estimate

| Phase | Priority | Estimated Hours |
|-------|----------|-----------------|
| Phase 1: Console Commands | 🔴 HIGH | 22 |
| Phase 2: Models | 🔴 HIGH | 8 |
| Phase 3: Observers | 🔴 HIGH | 11 |
| Phase 4: Policies | 🔴 HIGH | 7 |
| Phase 5: Email Jobs | 🔴 HIGH | 7 |
| Phase 6: Listeners | 🟡 MEDIUM | 30 |
| Phase 7: Mail Classes | 🟡 MEDIUM | 8 |
| Phase 8: Events | 🟡 MEDIUM | 26 |
| Phase 9: Middleware | 🟡 MEDIUM | 8 |
| Phase 10: Utility Commands | 🟢 LOW | 16 |
| Phase 11: Helpers | 🟢 LOW | 9 |

**Total**: ~152 hours (19 days @ 8 hours/day)

### Critical Path (Required for Production)

**High Priority Only**: Phases 1-5 = ~55 hours (~7 days)

These phases cover:
- ✅ User management (CreateUser command)
- ✅ System health checks
- ✅ Module system
- ✅ Complete data models
- ✅ Authorization policies
- ✅ Email workflow

### Already Implemented (per PROGRESS.md)

- ✅ Core email system (IMAP/SMTP)
- ✅ Auto-reply system
- ✅ Real-time features (broadcasting)
- ✅ Database layer (27 tables)
- ✅ 11 responsive views
- ✅ Basic controllers and routes

---

## 16. Key Findings

### Architecture Changes

1. **Event System**: Archive used granular events (17), modernized uses consolidated events (5)
2. **Mail System**: Switched from SwiftMailer to Laravel native mail
3. **Auth System**: Migrated from Laravel 5.5 auth to Breeze
4. **Service Layer**: New architecture with dedicated service classes
5. **Middleware**: Consolidated - many Laravel native now

### Missing Critical Features

1. **Console Commands** (22/24 missing) - Highest priority
2. **Audit Logging** (8 listeners missing)
3. **Model Observers** (9/10 missing)
4. **Authorization Policies** (3/5 missing)
5. **Email Templates** (6/8 missing)

### Technical Debt

1. **No Follower System**: Conversation following not implemented
2. **No Activity Logging**: Observers missing for audit trail
3. **Limited Middleware**: Security and UX middleware missing
4. **Helper Functions**: Many utility functions not ported
5. **Module System**: Commands for modules incomplete

---

## 17. Next Steps

### Immediate Actions (Week 1)

1. ✅ **CreateUser Command** - Enable CLI user creation
2. ✅ **CheckRequirements Command** - System validation
3. ✅ **ConversationPolicy** - Authorization rules
4. ✅ **ThreadPolicy** - Thread authorization
5. ✅ **Missing Models** - ConversationFolder, Follower, MailboxUser

### Short Term (Weeks 2-3)

1. ✅ **Module Commands** - Install, update, build
2. ✅ **Model Observers** - Lifecycle hooks
3. ✅ **Email Jobs** - Complete email workflow
4. ✅ **Audit Listeners** - Security logging

### Long Term (Month 2)

1. ✅ **Complete Events** - Granular tracking
2. ✅ **Middleware** - Security and UX
3. ✅ **Helper Classes** - Utility functions
4. ✅ **Monitoring Commands** - System health

---

## 18. Conclusion

The modernized FreeScout application has successfully implemented the **core functionality** (~97% complete per PROGRESS.md) with a modern architecture. However, there are **significant gaps** in supporting infrastructure:

### What's Working ✅
- Core email system (IMAP/SMTP)
- Conversation management
- User management (UI)
- Real-time features
- Database layer

### What's Missing ❌
- CLI administration (22 commands)
- Audit logging (16 listeners)
- Model lifecycle hooks (9 observers)
- Complete authorization (3 policies)
- Email templates (6 mailables)

### Recommendation

**Priority**: Focus on **Phase 1-5** (Critical Path) to make the application production-ready. This covers:
- Essential CLI commands for administration
- Complete data models and relationships
- Full authorization policies
- Complete email workflow

**Estimated Time**: 7 days of focused development

After completing the critical path, the application will be **fully production-ready** with all essential features from the archived version while maintaining modern Laravel 11 architecture and best practices.
