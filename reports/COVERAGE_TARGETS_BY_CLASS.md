# Coverage Targets by Class - Detailed Tracking

**Last Updated:** 2025-11-16  
**Current Overall Coverage:** 2.28% lines, 2.93% methods, 0% classes

## Coverage Goals

| Phase | Minimum Class Coverage | Target Line Coverage | Target Method Coverage | Timeline |
|-------|----------------------|---------------------|----------------------|----------|
| **Phase 1** | 50% (every class) | 40% | 50% | 8 weeks |
| **Phase 2** | 80% (every class) | 70% | 80% | +8 weeks |
| **Phase 3** | 95% (every class) | 85% | 90% | +4 weeks |

**Non-Negotiable:** No class remains at 0% after Phase 1

---

## Phase 1: Baseline Coverage (50% minimum all classes)

### Critical Infrastructure (CRAP > 1000)

| Class | Current | Phase 1 Target | Test Count | CRAP | Priority |
|-------|---------|---------------|------------|------|----------|
| **ImapService** | 0% | 60% | 80 | 34,410 | CRITICAL |
| **ConversationController** | 0% | 55% | 60 | 6,642 | CRITICAL |
| **MailboxController** | 0% | 55% | 25 | 2,652 | HIGH |
| **MailHelper** | 0% | 60% | 40 | 2,070 | HIGH |
| **SettingsController** | 0% | 55% | 25 | 1,640 | HIGH |
| **Customer** (Model) | 11% | 65% | 30 | 1,576 | HIGH |
| **SystemController** | 0% | 50% | 20 | 1,190 | HIGH |
| **UserController** | 0% | 55% | 15 | 992 | MEDIUM |
| **SmtpService** | 0% | 60% | 15 | 812 | MEDIUM |

**Subtotal:** 310 tests

---

### Console Commands (15 classes, all 0%)

| Command | Phase 1 Target | Test Count | CRAP | Key Methods |
|---------|---------------|------------|------|-------------|
| AfterAppUpdate | 55% | 5 | 56 | handle |
| CheckRequirements | 60% | 8 | 380 | handle, checkPhp, checkExtensions |
| ConfigureGmailMailbox | 50% | 5 | 72 | handle |
| CreateUser | 55% | 7 | 156 | handle, validateEmail |
| FetchEmails | 55% | 6 | 110 | handle |
| GenerateVars | 50% | 3 | 20 | handle |
| ModuleBuild | 50% | 4 | 182 | handle |
| ModuleInstall | 60% | 8 | 396 | handle, createSymlink |
| ModuleUpdate | 55% | 7 | 620 | handle |
| TestEventSystem | 50% | 3 | 12 | handle |
| Update | 50% | 5 | 110 | handle |
| UpdateFolderCounters | 50% | 5 | 110 | handle |
| (3 additional commands) | 50% | 15 | - | - |

**Subtotal:** 81 tests (all commands to 50%+)

---

### Events (11 classes, all 0%)

| Event | Phase 1 Target | Test Count | Key Tests |
|-------|---------------|------------|-----------|
| ConversationStatusChanged | 60% | 4 | constructor, properties, serialize |
| ConversationUpdated | 55% | 4 | constructor, properties, serialize |
| ConversationUserChanged | 55% | 4 | constructor, properties, serialize |
| CustomerCreatedConversation | 55% | 4 | constructor, properties, serialize |
| CustomerReplied | 55% | 4 | constructor, properties, serialize |
| NewMessageReceived | 55% | 4 | constructor, properties, serialize |
| UserAddedNote | 55% | 4 | constructor, properties, serialize |
| UserCreatedConversation | 55% | 4 | constructor, properties, serialize |
| UserDeleted | 60% | 4 | constructor, properties, serialize |
| UserReplied | 55% | 4 | constructor, properties, serialize |
| UserInvited | 55% | 4 | constructor, properties, serialize |

**Subtotal:** 44 tests (all events to 55%+)

---

### HTTP Controllers (22 classes, all 0% except 2 planned above)

| Controller | Phase 1 Target | Test Count | CRAP | Key Methods |
|------------|---------------|------------|------|-------------|
| ConversationController | 55% | 60 | 6,642 | index, show, store, reply, ajax, merge |
| MailboxController | 55% | 25 | 2,652 | store, update, ajax, testConnection |
| SettingsController | 55% | 25 | 1,640 | index, save |
| SystemController | 50% | 20 | 1,190 | diagnostics, ajax |
| UserController | 55% | 15 | 992 | CRUD, permissions |
| CustomerController | 55% | 12 | 462 | CRUD, merge, search |
| ModulesController | 50% | 8 | 182 | index, activate, settings |
| DashboardController | 50% | 5 | 20 | index, widgets |
| (14 additional controllers) | 50% | 70 | - | Basic CRUD |

**Subtotal:** 240 tests (all controllers to 50%+)

---

### Jobs (5 classes)

| Job | Current | Phase 1 Target | Test Count | CRAP | Status |
|-----|---------|---------------|------------|------|--------|
| SendNotificationToUsers | Partial | 70% | 20 | 600 | 12 tests exist, add 8 |
| SendAutoReply | Partial | 70% | 12 | 182 | 6 tests exist, add 6 |
| SendConversationReply | 0% | 60% | 8 | 90 | New |
| SendReplyToCustomer | 0% | 55% | 6 | 72 | New |
| RecalculateFolderCounters | 0% | 55% | 5 | - | New |

**Subtotal:** 51 tests (18 existing + 33 new)

---

### Listeners (14 classes, all 0% except 3 with LogListenersTest)

| Listener | Phase 1 Target | Test Count | CRAP | Status |
|----------|---------------|------------|------|--------|
| SendNotificationToUsers | 65% | 5 | 210 | Add to existing |
| SendAutoReply | 65% | 5 | 182 | Add to existing |
| SendReplyToCustomer | 65% | 5 | 182 | Add to existing |
| (11 additional listeners) | 55% | 44 | - | New |

**Subtotal:** 59 tests

---

### Mail Classes (8 classes, all 0%)

| Mail Class | Phase 1 Target | Test Count | Key Tests |
|------------|---------------|------------|-----------|
| UserNotification | 60% | 5 | build, envelope, content, attachments |
| ConversationNotification | 60% | 5 | build, envelope, content |
| PasswordReset | 60% | 5 | build, envelope, content |
| UserInvite | 60% | 5 | build, envelope, content |
| AutoReply | 60% | 5 | build, envelope, content |
| CustomerReply | 60% | 5 | build, envelope, content |
| ThreadNotification | 60% | 5 | build, envelope, content |
| SystemAlert | 60% | 5 | build, envelope, content |

**Subtotal:** 40 tests (all mail classes to 60%+)

---

### Models (18 classes, 21.99% average)

| Model | Current | Phase 1 Target | Test Count | CRAP | Key Methods |
|-------|---------|---------------|------------|------|-------------|
| Customer | 11% | 70% | 30 | 1,576 | create, setData, relationships |
| Conversation | 43% | 75% | 25 | 102 | updateFolder, status, relationships |
| Thread | 51% | 75% | 20 | 36 | type checks, relationships |
| User | 31% | 70% | 20 | 90 | permissions, relationships |
| Mailbox | 61% | 80% | 15 | 72 | connections, relationships |
| Folder | ? | 65% | 12 | - | counters, relationships |
| Email | ? | 65% | 10 | - | validation, normalization |
| Attachment | Partial | 70% | 12 | - | storage, types |
| Subscription | ? | 60% | 10 | - | default subscriptions |
| Follower | ? | 60% | 8 | - | follower management |
| SendLog | ? | 60% | 8 | - | logging |
| ActivityLog | ? | 60% | 8 | - | activity tracking |
| Channel | ? | 60% | 8 | - | channel management |
| (5 more models) | ? | 60% | 50 | - | Various |

**Subtotal:** 236 tests (all models to 60%+)

---

### Observers (6 classes, all 0% except AttachmentObserver)

| Observer | Phase 1 Target | Test Count | CRAP | Key Events |
|----------|---------------|------------|------|------------|
| ConversationObserver | 60% | 8 | - | created, updated, deleted, counters |
| ThreadObserver | 60% | 8 | - | created, updated, deleted, counters |
| UserObserver | 65% | 6 | - | created, deleted (some tests exist) |
| CustomerObserver | 55% | 6 | - | created, updated, validation |
| MailboxObserver | 55% | 6 | - | created, deleted, folders |
| AttachmentObserver | 70% | 6 | - | deleting (2 tests exist, add 4) |

**Subtotal:** 40 tests (all observers to 55%+)

---

### Policies (5 classes, all 0%)

| Policy | Phase 1 Target | Test Count | CRAP | Methods |
|--------|---------------|------------|------|---------|
| MailboxPolicy | 60% | 12 | 462 | view, create, update, delete, manage |
| ConversationPolicy | 60% | 10 | 306 | view, update, delete, merge, move |
| UserPolicy | 55% | 8 | - | view, create, update, delete |
| CustomerPolicy | 50% | 6 | - | view, create, update, merge |
| SystemPolicy | 50% | 6 | - | admin-only actions |

**Subtotal:** 42 tests (all policies to 50%+)

---

### Services (2 classes)

| Service | Current | Phase 1 Target | Test Count | CRAP |
|---------|---------|---------------|------------|------|
| ImapService | 0% | 60% | 80 | 34,410 |
| SmtpService | 0% | 60% | 15 | 812 |

**Subtotal:** 95 tests

---

### Misc/Providers (5 classes)

| Class | Phase 1 Target | Test Count | Type |
|-------|---------------|------------|------|
| MailHelper | 60% | 40 | Misc |
| AppServiceProvider | 50% | 5 | Provider |
| EventServiceProvider | 50% | 5 | Provider |
| RouteServiceProvider | 50% | 5 | Provider |
| Helper | 50% | 10 | Misc |

**Subtotal:** 65 tests

---

## Phase 1 Test Count Summary

| Category | Classes | Current Coverage | Target Coverage | New Tests |
|----------|---------|-----------------|-----------------|-----------|
| Critical Infrastructure | 9 | 1.2% | 55-60% | 310 |
| Console Commands | 15 | 0.9% | 50-60% | 81 |
| Events | 11 | 0% | 55-60% | 44 |
| Controllers | 22 | 0% | 50-55% | 240 |
| Jobs | 5 | Partial | 55-70% | 51 |
| Listeners | 14 | 0% | 55-65% | 59 |
| Mail | 8 | 0% | 60% | 40 |
| Models | 18 | 21.99% | 60-80% | 236 |
| Observers | 6 | Partial | 55-70% | 40 |
| Policies | 5 | 0% | 50-60% | 42 |
| Services | 2 | 0% | 60% | 95 |
| Misc/Providers | 5 | 0-25% | 50-60% | 65 |
| **TOTAL** | **120** | **2.28%** | **50%+** | **~1,303** |

**Expected Phase 1 Outcome:**
- ✅ Zero classes at 0% coverage
- ✅ All classes at 50%+ coverage
- ✅ Overall line coverage: 40-45%
- ✅ Overall method coverage: 50-55%
- ✅ Overall class coverage: 50-60%

---

## Phase 2: Deep Coverage (80% minimum all classes)

### Enhanced Test Counts by Category

| Category | Phase 1 Coverage | Phase 2 Target | Additional Tests | Focus |
|----------|-----------------|---------------|------------------|-------|
| ImapService | 60% | 85% | +100 | Edge cases, error paths |
| ConversationController | 55% | 85% | +80 | Search, filters, bulk ops |
| Models (all) | 60-80% | 85% | +300 | Relationships, scopes, casts |
| Controllers (all) | 50-55% | 80% | +200 | Validation, errors, redirects |
| Policies (all) | 50-60% | 85% | +60 | All auth scenarios |
| Jobs (all) | 55-70% | 85% | +70 | Retry, timeout, failure |
| Events/Listeners | 55-65% | 80% | +80 | Broadcasting, queueing |
| Observers | 55-70% | 85% | +50 | All lifecycle events |
| Mail | 60% | 80% | +70 | All content variations |
| Commands | 50-60% | 80% | +90 | All options, error cases |
| Services | 60% | 85% | +50 | Connection resilience |
| Misc/Providers | 50-60% | 80% | +40 | Complete utility coverage |
| **TOTAL** | **50%+** | **80%+** | **~1,190** | **Production ready** |

**Expected Phase 2 Outcome:**
- ✅ All classes at 80%+ coverage
- ✅ Overall line coverage: 70-75%
- ✅ Overall method coverage: 80-85%
- ✅ CRAP scores all < 200

---

## Phase 3: Excellence (95% target)

> **📊 See Detailed Analysis:** [PHASE_3_ENHANCEMENT_ANALYSIS.md](./PHASE_3_ENHANCEMENT_ANALYSIS.md) - ROI analysis and justification

### Strategic Focus: Cover the Critical 15%

**What Phase 2 Misses:** The 15% gap from 80% to 95% is NOT random untested code. It's specifically:
- Rare error paths that WILL occur in production
- Performance edge cases under load
- Data integrity corner cases
- Security boundary conditions
- Integration failure scenarios

**These are the bugs that cause 3am emergency calls and customer churn.**

### Phase 3 Test Distribution (250 tests, 160 hours)

| Priority | Tests | Focus Areas | Business Impact |
|----------|-------|-------------|-----------------|
| **Production Incident Prevention** | 120 | Error recovery, concurrency, data integrity | Prevents outages |
| **Security Hardening** | 40 | Advanced auth, injection, rate limiting | Prevents breaches |
| **Rare Edge Cases** | 40 | Unusual data, system limits | Prevents data loss |
| **Performance Verification** | 30 | Query optimization, memory management | Prevents slowdowns |
| **Observability** | 20 | Logging, metrics, monitoring | Improves debugging |

---

### Priority 1: Production Incident Prevention (120 tests)

#### ImapService Error Recovery (30 tests)
**Why Critical:** Email loss is unacceptable, these scenarios happen monthly

Missing scenarios from Phase 2:
- [ ] Network interruption mid-fetch → reconnection logic
- [ ] IMAP server invalid encoding mid-stream
- [ ] Partial message download (connection drops)
- [ ] IMAP IDLE disconnection with queued messages
- [ ] Folder structure changes during sync
- [ ] Memory exhaustion with 50,000+ messages
- [ ] SSL certificate expiry mid-session
- [ ] IMAP server rate limiting/temporary ban
- [ ] Mailbox quota reached mid-operation
- [ ] Concurrent access conflict (two servers fetching)
- [ ] Message UID changes during fetch
- [ ] Server returns duplicate messages
- [ ] Mailbox moved/renamed during fetch
- [ ] IMAP capability negotiation failures
- [ ] Authentication token expiry mid-session

**Coverage Gain:** 80% → 95%

#### Concurrent Operation Safety (25 tests)
**Why Critical:** Race conditions cause data corruption daily

Missing scenarios from Phase 2:
- [ ] Database deadlock during counter updates
- [ ] Race condition: Counter update between read/write
- [ ] Optimistic locking failures
- [ ] Transaction isolation level issues
- [ ] Lock acquisition timeout
- [ ] Distributed lock consistency
- [ ] Two users updating same record simultaneously
- [ ] Observer firing during concurrent operations
- [ ] Cache invalidation race conditions
- [ ] Queue job duplicate processing
- [ ] Unique constraint violation under load
- [ ] Foreign key cascade during concurrent deletes
- [ ] Session race condition (parallel requests)
- [ ] File upload collision (same filename)
- [ ] Search index update conflicts

**Coverage Gain:** Models/Observers 80% → 92%

#### Data Integrity Verification (20 tests)
**Why Critical:** Silent data corruption is worst-case scenario

Missing scenarios from Phase 2:
- [ ] Counter drift detection and correction
- [ ] Orphaned record cleanup verification
- [ ] Cascade delete with circular references
- [ ] Soft delete with active foreign keys
- [ ] JSON column corruption handling
- [ ] Timestamp inconsistency (created_at > updated_at)
- [ ] Relationship loading with trashed records
- [ ] Duplicate key recovery
- [ ] Partial cascade failure
- [ ] Transaction rollback side effects
- [ ] Observer failing mid-operation
- [ ] Database constraint violation recovery
- [ ] NULL constraint violations
- [ ] Enum value integrity
- [ ] Meta data consistency

**Coverage Gain:** Models/Observers 80% → 90%

#### Performance Under Load (20 tests)
**Why Critical:** Slowdowns happen daily during peak hours

Missing scenarios from Phase 2:
- [ ] Conversation with 5,000+ threads (pagination stress)
- [ ] Search with 100,000+ conversations
- [ ] Bulk operations on 1,000+ conversations
- [ ] 50+ concurrent users updating same conversation
- [ ] Database connection pool exhaustion
- [ ] Query timeout handling
- [ ] Memory usage with 100+ attachments
- [ ] Cache stampede prevention
- [ ] N+1 query detection in all paths
- [ ] Index usage verification
- [ ] Large file streaming
- [ ] Chunked processing verification
- [ ] Lazy loading under load
- [ ] Eager loading optimization
- [ ] Query result set size limits

**Coverage Gain:** Controllers 80% → 88%

#### Integration Failure Handling (25 tests)
**Why Critical:** Partial operations leave system in bad state

Missing scenarios from Phase 2:
- [ ] Email fetch → Create → Notify (SMTP fails mid-flow)
- [ ] Reply → Queue → Job fails → Retry → Success
- [ ] Merge → Reindex → Fails → Rollback
- [ ] User delete → Cleanup fails → Partial cleanup
- [ ] Module update → Migration fails → Rollback
- [ ] Mailbox test → IMAP works, SMTP fails
- [ ] Auto-reply → Detects loop → Circuit breaker
- [ ] Attachment upload → Virus scan → Quarantine
- [ ] Import 10K customers → Memory fail → Resume
- [ ] Export large dataset → Timeout → Stream
- [ ] Job chain failure recovery
- [ ] Event cascade failure
- [ ] Observer exception handling
- [ ] Multi-step transaction rollback
- [ ] Distributed system failure handling

**Coverage Gain:** Jobs/Listeners 80% → 92%

---

### Priority 2: Security Hardening (40 tests)

#### Advanced Authorization (15 tests)
**Why Critical:** Auth bypasses are actively exploited

- [ ] JWT token manipulation attempts
- [ ] Token expiry race conditions
- [ ] Session fixation attacks
- [ ] Session hijacking attempts
- [ ] CSRF token replay attacks
- [ ] Permission escalation via parameter tampering
- [ ] Policy bypass via direct method calls
- [ ] Cross-tenant data access attempts
- [ ] Soft-deleted user access attempts
- [ ] Disabled user session persistence
- [ ] Role modification race conditions
- [ ] Permission caching inconsistencies
- [ ] Guest user privilege escalation
- [ ] API key rotation edge cases
- [ ] Multi-factor auth bypass attempts

**Coverage Gain:** Policies/Middleware 80% → 94%

#### Injection Prevention (15 tests)
**Why Critical:** Injection attacks are constant

- [ ] SQL injection via search queries
- [ ] SQL injection via sorting parameters
- [ ] XSS in email content (HTML entities)
- [ ] XSS in customer names (Unicode tricks)
- [ ] Path traversal in file downloads
- [ ] Path traversal in attachment names
- [ ] Command injection via email headers
- [ ] XML injection in import/export
- [ ] LDAP injection (if external auth used)
- [ ] Template injection in mail vars
- [ ] ReDoS via regex in email parsing
- [ ] Header injection in email sending
- [ ] Null byte injection in filenames
- [ ] Control character injection
- [ ] Unicode normalization attacks

**Coverage Gain:** Controllers/Services 80% → 88%

#### Rate Limiting & Abuse (10 tests)
**Why Critical:** DDoS and abuse are daily occurrences

- [ ] Distributed rate limit bypass (multiple IPs)
- [ ] Slowloris attack handling
- [ ] Resource exhaustion (file uploads)
- [ ] API abuse (rapid requests)
- [ ] Email bombing prevention
- [ ] Reply loop detection
- [ ] Attachment spam detection
- [ ] Search abuse prevention
- [ ] Login brute force protection
- [ ] Circuit breaker activation

**Coverage Gain:** Middleware/Services 70% → 90%

---

### Priority 3: Rare Edge Cases (40 tests)

#### Unusual Email Scenarios (15 tests)

- [ ] Email with 100+ attachments
- [ ] 50MB email handling
- [ ] Deeply nested MIME (15+ levels)
- [ ] Malformed MIME boundaries
- [ ] Rare character encodings (ISO-2022-JP, GB2312)
- [ ] Email with null bytes in headers
- [ ] Emails from the future (timezone issues)
- [ ] Duplicate Message-IDs across mailboxes
- [ ] Emails with no Message-ID
- [ ] Forwarded chain 20+ levels deep
- [ ] Email with only BCC recipients
- [ ] Auto-responder loop (3+ hops)
- [ ] Bounce messages with original message
- [ ] Email with no sender (anonymous)
- [ ] Extremely long subject lines (1000+ chars)

**Coverage Gain:** ImapService 85% → 96%

#### Unusual User Behavior (10 tests)

- [ ] User with 1,000+ followed conversations
- [ ] Mailbox with 100+ users assigned
- [ ] Conversation with 10,000+ threads
- [ ] Customer with 50+ email addresses
- [ ] Rapid clicking same action (debounce test)
- [ ] Names with 500+ characters
- [ ] Names with control characters
- [ ] User accessing deleted conversation
- [ ] User with zero permissions (edge case)
- [ ] Multiple browser tabs simultaneous actions

**Coverage Gain:** Controllers 88% → 93%

#### System Edge Cases (15 tests)

- [ ] Disk space exhaustion
- [ ] Database connection exhaustion
- [ ] Cache server unavailable
- [ ] Session store unavailable
- [ ] Queue worker down
- [ ] Log file rotation during operation
- [ ] Config cache corruption
- [ ] Timezone inconsistencies
- [ ] Daylight saving time transitions
- [ ] Leap second handling
- [ ] Server clock drift
- [ ] Out of memory errors
- [ ] File descriptor limits
- [ ] Inode exhaustion
- [ ] Network partition (split brain)

**Coverage Gain:** System/Infrastructure 60% → 85%

---

### Priority 4: Performance Verification (30 tests)

#### Query Performance (12 tests)

- [ ] N+1 query detection in all controllers
- [ ] Index usage verification for searches
- [ ] Join optimization validation
- [ ] Subquery performance checks
- [ ] Aggregate query efficiency
- [ ] EXPLAIN ANALYZE for critical queries
- [ ] Query result set pagination
- [ ] Cursor-based pagination performance
- [ ] Full-text search performance
- [ ] Query caching effectiveness
- [ ] Connection pool utilization
- [ ] Slow query log integration

**Coverage Gain:** Adds performance test suite

#### Memory Management (10 tests)

- [ ] Large collection chunking
- [ ] Streaming large file uploads
- [ ] Streaming large file downloads
- [ ] Lazy loading verification
- [ ] Memory leak detection
- [ ] Garbage collection monitoring
- [ ] Iterator usage for large datasets
- [ ] Generator usage validation
- [ ] Memory limit enforcement
- [ ] OOM recovery handling

**Coverage Gain:** Adds memory test suite

#### Caching Strategy (8 tests)

- [ ] Cache hit rate measurement
- [ ] Cache invalidation correctness
- [ ] Cache stampede prevention
- [ ] Distributed cache consistency
- [ ] Cache warming strategies
- [ ] Cache key collision handling
- [ ] TTL accuracy
- [ ] Cache fallback when unavailable

**Coverage Gain:** Adds caching test suite

---

### Priority 5: Observability (20 tests)

#### Logging Coverage (10 tests)

- [ ] Critical errors logged with context
- [ ] Security events logged (auth failures)
- [ ] Performance degradation logged
- [ ] Audit trail completeness
- [ ] Log format consistency (JSON)
- [ ] PII redaction in logs
- [ ] Log level appropriateness
- [ ] Exception stack traces complete
- [ ] Request ID correlation
- [ ] Log rotation handling

#### Monitoring Metrics (10 tests)

- [ ] Health check endpoint accuracy
- [ ] Metric collection (response times)
- [ ] Alert trigger conditions
- [ ] Dashboard data accuracy
- [ ] SLA violation detection
- [ ] Queue depth monitoring
- [ ] Error rate tracking
- [ ] Resource utilization metrics
- [ ] Business metrics (emails processed)
- [ ] Anomaly detection readiness

---

## Phase 3 Summary

**Total Tests:** 250 focused tests  
**Duration:** 4 weeks (160 hours)  
**Cost:** $8K-16K  
**ROI:** 750-1,700% annually

**Expected Phase 3 Outcome:**
- ✅ 95%+ class coverage
- ✅ 85%+ line coverage  
- ✅ 90%+ method coverage
- ✅ CRAP scores all < 100
- ✅ Production-hardened codebase
- ✅ Near-zero incidents from tested code
- ✅ High developer confidence
- ✅ Easy refactoring capability

**Key Achievement:** The critical 15% that prevents production incidents is now covered

---

## Critical Methods Requiring Explicit Testing

### Methods with CRAP > 500 (Must be tested in Phase 1)

| Method | CRAP | Class | Lines | Tests Needed |
|--------|------|-------|-------|-------------|
| processMessage | 6,162 | ImapService | ~200 | 30 |
| ModuleUpdate::handle | 600 | Console | ~60 | 8 |
| getAddressesWithNames | 552 | ImapService | ~40 | 6 |
| SendNotificationToUsers::handle | 506 | Job | ~50 | 12 (exists) |
| replaceMailVars | 506 | MailHelper | ~40 | 10 |

### Methods with CRAP 200-500 (Must be tested in Phase 1-2)

| Method | CRAP | Class | Tests Needed |
|--------|------|-------|-------------|
| parseAddresses | 420 | ImapService | 8 |
| Customer::create | 342 | Model | 10 |
| Customer::setData | 272 | Model | 8 |
| MailboxController::ajax | 240 | Controller | 12 |
| ImapService::fetchEmails | 240 | Service | 10 |
| (15+ more methods) | 200-272 | Various | 5-10 each |

---

## Progress Tracking Template

### Weekly Tracking (Use this to track progress)

**Week 1 (Phase 1 Start):**
- [ ] ImapService: 30 tests added (0% → 35%)
- [ ] ConversationController: 20 tests added (0% → 25%)
- [ ] MailHelper: 15 tests added (0% → 40%)
- [ ] Console Commands: 20 tests added (4 commands to 50%)

**Week 2:**
- [ ] ImapService: 50 tests total (35% → 60%)
- [ ] ConversationController: 40 tests total (25% → 45%)
- [ ] Models: 30 tests added (Customer, Conversation)

[Continue for 8 weeks...]

### Phase 1 Milestones

- **Week 2:** Critical infrastructure (ImapService, ConversationController) to 50%+
- **Week 4:** All models to 60%+
- **Week 6:** All controllers to 50%+
- **Week 8:** All classes to 50%+, Phase 1 complete

### Quality Gates

**Before Phase 2:**
- [ ] Run full test suite: all passing
- [ ] Coverage report: no class below 50%
- [ ] CRAP report: top 10 methods reduced by 60%
- [ ] CI/CD: tests pass in pipeline
- [ ] Manual testing: critical paths work

**Before Phase 3:**
- [ ] Coverage report: no class below 80%
- [ ] Performance tests: added and passing
- [ ] Security tests: authorization fully tested
- [ ] Integration tests: multi-component flows work

---

## Test Data Requirements

### Factories Needed (Enhanced)

**ConversationFactory states:**
- active(), closed(), spam(), draft()
- withThreads(n), withAttachments()
- withUnicodSubject(), withLargeBodyCount()

**CustomerFactory states:**
- withMultipleEmails(n), withCompany()
- withUnicodeName(), withEmoji()
- withChannels(n)

**ThreadFactory states:**
- customerMessage(), userReply(), note(), bounce()
- withLargeBody(), withHtmlBody()
- withAttachments(n)

**UserFactory states:**
- admin(), agent(), user()
- withMailboxes(n), withPermissions()

### Fixtures Needed

**Email fixtures (tests/Fixtures/emails/):**
- valid_email.eml
- email_with_attachment.eml
- auto_responder_email.eml
- malformed_email.eml
- unicode_email.eml
- html_only_email.eml
- multipart_nested.eml
- bounce_email.eml

---

## Automation & CI/CD

### Coverage Thresholds (Add to CI)

```yaml
# .github/workflows/tests.yml or similar
coverage:
  phase1:
    lines: 40%
    methods: 50%
    classes: 50%
  phase2:
    lines: 70%
    methods: 80%
    classes: 80%
  phase3:
    lines: 85%
    methods: 90%
    classes: 95%
```

### Pre-commit Hooks

```bash
# Ensure new code has tests
php artisan test --coverage --min=80
```

---

## Appendix: Test Naming Conventions

### Unit Tests
```php
test_method_name_with_valid_input_returns_expected_output()
test_method_name_with_invalid_input_throws_exception()
test_method_name_with_null_input_returns_default()
test_method_name_with_edge_case_handles_correctly()
```

### Feature Tests
```php
test_user_can_create_conversation_with_valid_data()
test_user_cannot_create_conversation_without_permission()
test_admin_can_delete_any_conversation()
test_form_validation_fails_with_missing_required_fields()
```

### Integration Tests
```php
test_email_fetching_creates_conversation_and_thread()
test_reply_sending_updates_counters_and_notifies_users()
test_conversation_merge_preserves_all_threads()
```

---

**Document End**

Use this document to track progress toward coverage goals. Update weekly with actual test counts and coverage percentages.
