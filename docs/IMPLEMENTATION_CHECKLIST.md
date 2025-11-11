# Implementation Checklist: Archive Feature Parity

**Last Updated**: November 10, 2025  
**Use**: Track implementation progress for missing features

---

## How to Use This Checklist

- [ ] Unchecked = Not started
- [x] Checked = Implemented and tested
- 🔴 = Critical (Phase 1-5, must have for production)
- 🟡 = Important (Phase 6-9, should have)
- 🟢 = Nice to have (Phase 10-11, could have)

---

## Phase 1: Console Commands (Core) 🔴 CRITICAL

**Goal**: Enable CLI administration  
**Estimated Effort**: 22 hours

### Commands

- [ ] 🔴 CreateUser (2h) - Create users via CLI
- [ ] 🔴 CheckRequirements (3h) - System requirements validation
- [ ] 🔴 ClearCache (1h) - Cache management
- [ ] 🔴 Update (4h) - Application update
- [ ] 🔴 AfterAppUpdate (2h) - Post-update cleanup
- [ ] 🔴 ModuleInstall (4h) - Install modules
- [ ] 🔴 ModuleBuild (3h) - Build module assets
- [ ] 🔴 ModuleUpdate (3h) - Update modules

**Phase 1 Complete**: [ ] 0/8 commands implemented

---

## Phase 2: Missing Models 🔴 CRITICAL

**Goal**: Complete data layer  
**Estimated Effort**: 8 hours

### Models & Migrations

- [ ] 🔴 Follower (2h) - Conversation followers
  - [ ] Model class
  - [ ] Migration
  - [ ] Relationships in Conversation/User models

- [ ] 🔴 MailboxUser (1h) - Mailbox permissions pivot
  - [ ] Pivot model class
  - [ ] Migration with access levels
  - [ ] Relationships in Mailbox/User models

- [ ] 🟡 ConversationFolder (1h) - Conversation-folder pivot
  - [ ] Pivot model class
  - [ ] Migration
  - [ ] Relationships (if needed)

- [ ] 🟡 CustomerChannel (2h) - Customer channels
  - [ ] Model class
  - [ ] Migration
  - [ ] Relationships

- [ ] 🟢 Sendmail (2h) - Sendmail configuration
  - [ ] Model class
  - [ ] Migration

**Phase 2 Complete**: [ ] 0/5 models implemented

---

## Phase 3: Model Observers 🔴 CRITICAL

**Goal**: Model lifecycle hooks for data integrity  
**Estimated Effort**: 11 hours

### Observers

- [ ] 🔴 ConversationObserver (3h)
  - [ ] creating() - Set defaults
  - [ ] created() - Update counters
  - [ ] updated() - Fire events
  - [ ] deleting() - Cleanup relations
  - [ ] Register in AppServiceProvider

- [ ] 🔴 UserObserver (2h)
  - [ ] created() - Create folders, subscriptions
  - [ ] creating() - Set defaults
  - [ ] deleting() - Cleanup relations
  - [ ] Register in AppServiceProvider

- [ ] 🔴 CustomerObserver (2h)
  - [ ] creating() - Normalize email
  - [ ] deleting() - Cleanup conversations
  - [ ] Register in AppServiceProvider

- [ ] 🔴 MailboxObserver (2h)
  - [ ] created() - Create default folders
  - [ ] deleting() - Cleanup
  - [ ] Register in AppServiceProvider

- [ ] 🔴 AttachmentObserver (2h)
  - [ ] deleting() - Delete files
  - [ ] Register in AppServiceProvider

- [ ] 🟡 EmailObserver (2h)
- [ ] 🟡 SendLogObserver (2h)
- [ ] 🟡 FollowerObserver (1h)
- [ ] 🟢 DatabaseNotificationObserver (1h)

**Phase 3 Complete**: [ ] 0/9 observers implemented

---

## Phase 4: Authorization Policies 🔴 CRITICAL

**Goal**: Complete authorization layer  
**Estimated Effort**: 7 hours

### Policies

- [ ] 🔴 ConversationPolicy (3h)
  - [ ] viewAny()
  - [ ] view()
  - [ ] create()
  - [ ] update()
  - [ ] delete()
  - [ ] assign()
  - [ ] Register in AppServiceProvider

- [ ] 🔴 ThreadPolicy (2h)
  - [ ] view()
  - [ ] create()
  - [ ] update()
  - [ ] delete()
  - [ ] Register in AppServiceProvider

- [ ] 🔴 FolderPolicy (2h)
  - [ ] view()
  - [ ] create()
  - [ ] update()
  - [ ] delete()
  - [ ] Register in AppServiceProvider

**Phase 4 Complete**: [ ] 0/3 policies implemented

---

## Phase 5: Email System Jobs 🔴 CRITICAL

**Goal**: Complete email workflow  
**Estimated Effort**: 7 hours

### Jobs

- [ ] 🔴 SendNotificationToUsers (3h)
  - [ ] Job class
  - [ ] Mailable: UserNotification
  - [ ] Event listener hook

- [ ] 🔴 SendEmailReplyError (2h)
  - [ ] Job class
  - [ ] Mailable: UserEmailReplyError
  - [ ] Error handling

- [ ] 🔴 SendAlert (2h)
  - [ ] Job class
  - [ ] Mailable: Alert
  - [ ] Alert triggers

**Phase 5 Complete**: [ ] 0/3 jobs implemented

---

## CRITICAL PATH MILESTONE

**All Phase 1-5 items must be complete before production deployment**

- [ ] Phase 1 Complete (8 commands)
- [ ] Phase 2 Complete (5 models)
- [ ] Phase 3 Complete (5 critical observers)
- [ ] Phase 4 Complete (3 policies)
- [ ] Phase 5 Complete (3 jobs)
- [ ] All tests passing
- [ ] Code review completed
- [ ] Security review completed

**Total Critical Path**: 0/24 items (55 hours estimated)

---

## Phase 6: Event Listeners 🟡 IMPORTANT

**Goal**: Complete event system  
**Estimated Effort**: 30 hours

### Audit Logging Listeners (12h)

- [ ] 🟡 LogSuccessfulLogin (2h)
- [ ] 🟡 LogSuccessfulLogout (2h)
- [ ] 🟡 LogFailedLogin (2h)
- [ ] 🟡 LogLockout (2h)
- [ ] 🟡 LogPasswordReset (2h)
- [ ] 🟡 LogRegisteredUser (1h)
- [ ] 🟡 LogUserDeletion (1h)

### Email Processing Listeners (8h)

- [ ] 🟡 SendReplyToCustomer (3h)
- [ ] 🟡 SendNotificationToUsers (3h)
- [ ] 🟡 SendPasswordChanged (2h)

### User Management Listeners (6h)

- [ ] 🟡 ActivateUser (2h)
- [ ] 🟡 RememberUserLocale (2h)
- [ ] 🟡 UpdateMailboxCounters (2h)

### UI Update Listeners (4h)

- [ ] 🟡 RefreshConversations (2h)
- [ ] 🟡 ProcessSwiftMessage (2h)

**Phase 6 Complete**: [ ] 0/14 listeners implemented

---

## Phase 7: Mail Classes 🟡 IMPORTANT

**Goal**: Complete email templates  
**Estimated Effort**: 8 hours

### Mailables

- [ ] 🟡 UserNotification (3h)
  - [ ] Mailable class
  - [ ] Blade template
  - [ ] Tests

- [ ] 🟡 UserInvite (2h)
  - [ ] Mailable class
  - [ ] Blade template
  - [ ] Tests

- [ ] 🟡 Test (1h) - SMTP test email
- [ ] 🟡 Alert (2h) - System alerts
- [ ] 🟡 PasswordChanged (1h)
- [ ] 🟡 UserEmailReplyError (1h)

**Phase 7 Complete**: [ ] 0/6 mailables implemented

---

## Phase 8: Events 🟡 IMPORTANT

**Goal**: Granular event tracking  
**Estimated Effort**: 26 hours

### Conversation Events (6h)

- [ ] 🟡 ConversationCustomerChanged (2h)
- [ ] 🟡 ConversationStatusChanged (2h)
- [ ] 🟡 ConversationUserChanged (2h)

### User Action Events (8h)

- [ ] 🟡 UserCreatedConversation (2h)
- [ ] 🟡 UserReplied (2h)
- [ ] 🟡 UserAddedNote (2h)
- [ ] 🟡 UserDeleted (2h)

### Draft Events (4h)

- [ ] 🟡 UserCreatedConversationDraft (2h)
- [ ] 🟡 UserCreatedThreadDraft (2h)

### Real-time Events (8h)

- [ ] 🟡 RealtimeBroadcastNotificationCreated (2h)
- [ ] 🟡 RealtimeChat (2h)
- [ ] 🟡 RealtimeConvNewThread (2h)
- [ ] 🟡 RealtimeConvView (1h)
- [ ] 🟡 RealtimeConvViewFinish (1h)

**Phase 8 Complete**: [ ] 0/12 events implemented

---

## Phase 9: Middleware & Security 🟡 IMPORTANT

**Goal**: Security and UX features  
**Estimated Effort**: 8 hours

### Middleware

- [ ] 🟡 Localize (3h) - Multi-language support
- [ ] 🟡 CheckRole (2h) - Role verification
- [ ] 🟡 LogoutIfDeleted (1h) - Auto-logout
- [ ] 🟡 HttpsRedirect (1h) - Force HTTPS
- [ ] 🟡 FrameGuard (1h) - X-Frame-Options

**Phase 9 Complete**: [ ] 0/5 middleware implemented

---

## Phase 10: Utility Commands 🟢 NICE TO HAVE

**Goal**: Maintenance and monitoring  
**Estimated Effort**: 16 hours

### Maintenance Commands

- [ ] 🟢 CleanNotificationsTable (2h)
- [ ] 🟢 CleanSendLog (2h)
- [ ] 🟢 CleanTmp (1h)
- [ ] 🟢 UpdateFolderCounters (2h)

### Monitoring Commands

- [ ] 🟢 FetchMonitor (3h)
- [ ] 🟢 SendMonitor (3h)
- [ ] 🟢 LogsMonitor (3h)

**Phase 10 Complete**: [ ] 0/7 commands implemented

---

## Phase 11: Helper Classes 🟢 NICE TO HAVE

**Goal**: Utility functions  
**Estimated Effort**: 9 hours

### Helpers

- [ ] 🟢 Functions.php (4h) - Global helper functions
- [ ] 🟢 Helper.php (3h) - Utility methods
- [ ] 🟢 ConversationActionButtons.php (2h) - UI helpers

**Phase 11 Complete**: [ ] 0/3 helpers implemented

---

## Testing Checklist

### Unit Tests

- [ ] Console command tests (all commands)
- [ ] Observer tests (all observers)
- [ ] Policy tests (all policies)
- [ ] Model tests (new models)
- [ ] Job tests (all jobs)
- [ ] Listener tests (all listeners)

### Integration Tests

- [ ] User lifecycle (create, login, logout, delete)
- [ ] Conversation lifecycle (create, update, delete)
- [ ] Module installation flow
- [ ] Email sending workflow
- [ ] Authorization enforcement

### Manual Testing

- [ ] CLI commands work as expected
- [ ] Authorization prevents unauthorized access
- [ ] Audit logs are created
- [ ] Counters update correctly
- [ ] Emails send properly

**Testing Complete**: [ ] All tests passing

---

## Documentation Checklist

- [x] Archive comparison completed
- [x] Implementation roadmap created
- [x] Critical features guide written
- [x] Executive summary prepared
- [x] This checklist created
- [ ] API documentation updated
- [ ] User guide updated
- [ ] Admin guide updated
- [ ] Deployment guide updated

---

## Deployment Checklist

### Pre-Deployment

- [ ] All critical path items complete
- [ ] All tests passing
- [ ] Code review approved
- [ ] Security review approved
- [ ] Database migrations reviewed
- [ ] Rollback plan prepared

### Staging Deployment

- [ ] Deploy to staging
- [ ] Run smoke tests
- [ ] Performance testing
- [ ] Security scan
- [ ] User acceptance testing

### Production Deployment

- [ ] Backup database
- [ ] Deploy to production
- [ ] Run migrations
- [ ] Verify core functionality
- [ ] Monitor for errors
- [ ] Notify users

**Deployment Complete**: [ ] Production ready

---

## Progress Summary

### Overall Progress

**Critical Path (Phase 1-5)**:
- Console Commands: 0/8 (0%)
- Models: 0/5 (0%)
- Observers: 0/5 (0%)
- Policies: 0/3 (0%)
- Jobs: 0/3 (0%)
- **Total**: 0/24 items (0%)

**Medium Priority (Phase 6-9)**:
- Listeners: 0/14 (0%)
- Mailables: 0/6 (0%)
- Events: 0/12 (0%)
- Middleware: 0/5 (0%)
- **Total**: 0/37 items (0%)

**Low Priority (Phase 10-11)**:
- Commands: 0/7 (0%)
- Helpers: 0/3 (0%)
- **Total**: 0/10 items (0%)

**GRAND TOTAL**: 0/71 items (0%)

### Time Tracking

- **Estimated Total**: 152 hours
- **Time Spent**: 0 hours
- **Remaining**: 152 hours

---

## Notes & Updates

### November 10, 2025
- Initial checklist created based on archive comparison
- All items identified and prioritized
- Ready to begin Phase 1 implementation

### Future Updates
- Update this section as phases are completed
- Track any blockers or issues
- Note any scope changes

---

**Use this checklist to track implementation progress. Update regularly and commit changes to Git.**
