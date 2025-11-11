# Work Batches - Master Index

**Created**: November 11, 2025  
**Purpose**: Parallelized implementation plan for FreeScout modernization completion

---

## Overview

This directory contains 10 work batches that can be executed in parallel by different agents/developers to complete the FreeScout modernization. Each batch is a self-contained unit of work with clear objectives, requirements, and success criteria.

### Total Effort

- **Critical Path**: 100 hours (~12.5 days single developer)
- **Full Implementation**: 169 hours (~21 days single developer)
- **With 4-5 Parallel Agents**: ~3-4 weeks to completion

---

## Batch Summary

| Batch | Category | Priority | Effort | Status | Dependencies |
|-------|----------|----------|--------|--------|--------------|
| [BATCH_01](#batch-01) | Console Commands | 🔴 HIGH | 22h | ✅ **DONE** | User, Folder, Module models |
| [BATCH_02](#batch-02) | Models & Observers | 🔴 HIGH | 19h | ✅ Yes | Database migrations |
| [BATCH_03](#batch-03) | Conversation Views | 🔴 HIGH | 30h | ✅ Yes | Models exist |
| [BATCH_04](#batch-04) | Policies & Jobs | 🔴 HIGH | 14h | ✅ Yes | Models exist |
| [BATCH_05](#batch-05) | Email Templates | 🟡 MEDIUM | 14h | ✅ Yes | BATCH_04 (jobs) |
| [BATCH_06](#batch-06) | Customer/User Views | 🟡 MEDIUM | 24h | ✅ Yes | Models exist |
| [BATCH_07](#batch-07) | Shared Partials | 🟡 MEDIUM | 14h | ✅ Yes | None |
| [BATCH_08](#batch-08) | Mailbox Views | 🔴 HIGH | 8h | ✅ Yes | Mailbox model |
| [BATCH_09](#batch-09) | Event Listeners | 🟡 MEDIUM | 18h | ✅ Yes | ActivityLog model |
| [BATCH_10](#batch-10) | Polish & Testing | 🟢 LOW | 12h | ⚠️ Partial | All other batches |

**Total**: 175 hours

---

## Batch Descriptions

### BATCH_01: Console Commands (22 hours) ✅ COMPLETED

**File**: [BATCH_01_CONSOLE_COMMANDS.md](BATCH_01_CONSOLE_COMMANDS.md)  
**Status**: ✅ Completed on November 11, 2025  
**Summary**: [BATCH_01_IMPLEMENTATION_SUMMARY.md](../BATCH_01_IMPLEMENTATION_SUMMARY.md)

**What**: Implement 8 critical console commands for CLI administration

**Deliverables**:
- CreateUser command
- CheckRequirements command
- UpdateFolderCounters command
- ModuleInstall command
- ModuleBuild command
- ModuleUpdate command
- Update command
- AfterAppUpdate command

**Why Critical**: Blocks automation, deployment, and module system

**Can Start Immediately**: Yes (models exist)

---

### BATCH_02: Models & Observers (19 hours) 🔴

**File**: [BATCH_02_MODELS_OBSERVERS.md](BATCH_02_MODELS_OBSERVERS.md)

**What**: Implement 5 missing models and 5 critical observers

**Deliverables**:
- Follower model
- MailboxUser model (pivot)
- ConversationFolder model
- CustomerChannel model
- Sendmail model
- ConversationObserver
- UserObserver
- CustomerObserver
- MailboxObserver
- AttachmentObserver

**Why Critical**: Data integrity and lifecycle management

**Can Start Immediately**: Yes (migrations exist)

---

### BATCH_03: Conversation Views (30 hours) 🔴

**File**: [BATCH_03_CONVERSATION_VIEWS.md](BATCH_03_CONVERSATION_VIEWS.md)

**What**: Implement conversation management UI (25 view files)

**Deliverables**:
- Thread display partials (10 files)
- AJAX components (7 files)
- Specialized views (8 files)

**Why Critical**: Core user interface for ticket management

**Can Start Immediately**: Yes (models exist)

**Note**: This is the largest single batch - consider splitting or assigning 2 agents

---

### BATCH_04: Policies & Jobs (14 hours) 🔴

**File**: [BATCH_04_POLICIES_JOBS.md](BATCH_04_POLICIES_JOBS.md)

**What**: Implement authorization and email job handling

**Deliverables**:
- ConversationPolicy
- ThreadPolicy
- FolderPolicy
- SendNotificationToUsers job
- SendEmailReplyError job
- SendAlert job

**Why Critical**: Security and email notifications

**Can Start Immediately**: Yes (models exist)

---

### BATCH_05: Email Templates (14 hours) 🟡

**File**: [BATCH_05_EMAIL_TEMPLATES.md](BATCH_05_EMAIL_TEMPLATES.md)

**What**: Implement email templates and mailable classes

**Deliverables**:
- UserNotification mailable + views
- UserInvite mailable + views
- Alert mailable + views
- UserEmailReplyError mailable + views
- Email layout
- Plain text versions

**Why Important**: User communication and notifications

**Can Start After**: BATCH_04 (needs jobs)

---

### BATCH_06: Customer/User Views (24 hours) 🟡

**File**: [BATCH_06_CUSTOMER_USER_VIEWS.md](BATCH_06_CUSTOMER_USER_VIEWS.md)

**What**: Implement customer and user management UI

**Deliverables**:
- Customer merging UI
- Customer profile components (4 files)
- User notification management (4 files)
- User permissions UI
- Navigation menus

**Why Important**: Complete customer and user management

**Can Start Immediately**: Yes (models exist)

---

### BATCH_07: Shared Partials (14 hours) 🟡

**File**: [BATCH_07_SHARED_PARTIALS.md](BATCH_07_SHARED_PARTIALS.md)

**What**: Implement reusable UI components

**Deliverables**:
- Rich text editor partial
- Calendar/date picker
- Locale and timezone selectors
- Flash messages component
- Avatar/photo component
- Empty state component
- Sidebar toggle

**Why Important**: Code reuse and consistency

**Can Start Immediately**: Yes (no dependencies)

**Note**: Good for junior developer - clear, isolated components

---

### BATCH_08: Mailbox Views (8 hours) 🔴

**File**: [BATCH_08_MAILBOX_VIEWS.md](BATCH_08_MAILBOX_VIEWS.md)

**What**: Implement mailbox management UI

**Deliverables**:
- Mailbox creation form
- Sidebar menu components (2 files)
- Mailbox partials (3 files)

**Why Critical**: Cannot create mailboxes without this

**Can Start Immediately**: Yes (Mailbox model exists)

---

### BATCH_09: Event Listeners (18 hours) 🟡

**File**: [BATCH_09_EVENT_LISTENERS.md](BATCH_09_EVENT_LISTENERS.md)

**What**: Implement event listeners for audit logging and system events

**Deliverables**:
- Audit logging listeners (7 files)
- Email processing listeners (3 files)
- User management listeners (2 files)

**Why Important**: Security tracking and audit trail

**Can Start Immediately**: Yes (ActivityLog model exists)

---

### BATCH_10: Polish & Testing (12 hours) 🟢

**File**: [BATCH_10_POLISH_TESTING.md](BATCH_10_POLISH_TESTING.md)

**What**: Final polish, error pages, and comprehensive testing

**Deliverables**:
- Custom error pages (403, 404, 500)
- Alert settings page
- Integration test suite
- Performance tests
- Security tests

**Why Important**: Professional finish and quality assurance

**Can Start After**: All other batches substantially complete

**Note**: This is the QA batch - assign to senior developer

---

## Parallelization Strategy

### Optimal 4-Agent Distribution

**Agent 1 - Backend Infrastructure** (33h):
- BATCH_01: Console Commands (22h)
- BATCH_02: Models & Observers (19h - partial, first 11h)

**Agent 2 - Frontend Core** (38h):
- BATCH_03: Conversation Views (30h)
- BATCH_08: Mailbox Views (8h)

**Agent 3 - Authorization & Email** (42h):
- BATCH_04: Policies & Jobs (14h)
- BATCH_05: Email Templates (14h)
- BATCH_07: Shared Partials (14h)

**Agent 4 - User Features & Polish** (54h):
- BATCH_02: Models & Observers (8h - remaining)
- BATCH_06: Customer/User Views (24h)
- BATCH_09: Event Listeners (18h)

**Agent 5 - QA & Testing** (12h):
- BATCH_10: Polish & Testing (12h) - starts after others

**Timeline**: ~3-4 weeks with 4-5 agents working in parallel

---

### Alternative 3-Agent Distribution

**Agent 1 - Backend** (41h):
- BATCH_01: Console Commands (22h)
- BATCH_02: Models & Observers (19h)

**Agent 2 - Frontend** (72h):
- BATCH_03: Conversation Views (30h)
- BATCH_06: Customer/User Views (24h)
- BATCH_07: Shared Partials (14h)
- BATCH_08: Mailbox Views (8h)

**Agent 3 - Email & Events** (50h):
- BATCH_04: Policies & Jobs (14h)
- BATCH_05: Email Templates (14h)
- BATCH_09: Event Listeners (18h)
- BATCH_10: Polish & Testing (12h)

**Timeline**: ~5-6 weeks with 3 agents

---

### Sequential Implementation

If only 1 developer:

**Week 1**: BATCH_01 + BATCH_02 (41h)  
**Week 2**: BATCH_03 + BATCH_08 (38h)  
**Week 3**: BATCH_04 + BATCH_05 (28h)  
**Week 4**: BATCH_06 (24h)  
**Week 5**: BATCH_07 + BATCH_09 (32h)  
**Week 6**: BATCH_10 (12h)

**Timeline**: ~6 weeks (175 hours @ 30h/week)

---

## How to Use These Batches

### For Project Managers

1. **Review each batch** to understand scope
2. **Assign batches to agents/developers** based on skills
3. **Track progress** using checklist in each batch
4. **Monitor dependencies** - some batches need others complete
5. **Coordinate testing** - BATCH_10 needs others done first

### For Developers/Agents

1. **Read the entire batch file** before starting
2. **Review reference documentation** linked in each batch
3. **Check archive code** for original implementation patterns
4. **Follow code standards** in Implementation Guidelines
5. **Test thoroughly** before marking complete
6. **Update progress** in implementation checklist
7. **Document issues** encountered

### For AI Agents

Each batch file contains:
- **Complete context** about the task
- **Detailed requirements** for each component
- **Code examples** showing expected patterns
- **Testing strategies** to validate work
- **Success criteria** for completion
- **Troubleshooting guidance** for common issues

Simply provide the batch file content as the agent's prompt, along with repository access.

---

## Success Metrics

### Per Batch

- [ ] All deliverables implemented
- [ ] All tests passing
- [ ] Code passes linting (Pint)
- [ ] Code passes static analysis (PHPStan)
- [ ] Documentation updated
- [ ] Peer reviewed (if multiple agents)

### Overall Project

- [ ] All 10 batches complete
- [ ] Integration tests passing
- [ ] No PHPStan errors at level 7
- [ ] Test coverage > 80%
- [ ] Performance benchmarks met
- [ ] Security review passed
- [ ] Documentation complete
- [ ] Ready for production deployment

---

## Quality Assurance

### Code Review Checklist

For each batch completion:

1. **Functionality**:
   - [ ] Meets all requirements
   - [ ] No regressions
   - [ ] Error handling present

2. **Code Quality**:
   - [ ] Follows Laravel conventions
   - [ ] DRY principle applied
   - [ ] Commented where complex
   - [ ] No code smells

3. **Testing**:
   - [ ] Unit tests present
   - [ ] Feature tests present
   - [ ] Edge cases covered
   - [ ] All tests green

4. **Security**:
   - [ ] Authorization checks
   - [ ] Input validation
   - [ ] XSS prevention
   - [ ] SQL injection prevention

5. **Performance**:
   - [ ] No N+1 queries
   - [ ] Proper indexes
   - [ ] Caching where appropriate
   - [ ] Queries optimized

---

## Troubleshooting

### Common Issues

**Issue**: "Model not found"  
**Solution**: Check if migration has run, model exists in correct namespace

**Issue**: "Policy not registered"  
**Solution**: Register in AppServiceProvider::boot()

**Issue**: "View not found"  
**Solution**: Check view path and file name (case-sensitive on Linux)

**Issue**: "Tests fail with database errors"  
**Solution**: Ensure RefreshDatabase trait used, migrations run

**Issue**: "Undefined method"  
**Solution**: Check model relationships are defined, check for typos

---

## Documentation

### Main Documents

- [ARCHIVE_COMPARISON_ROADMAP.md](../ARCHIVE_COMPARISON_ROADMAP.md) - Complete component analysis
- [CRITICAL_FEATURES_IMPLEMENTATION.md](../CRITICAL_FEATURES_IMPLEMENTATION.md) - Code examples
- [VIEWS_COMPARISON.md](../VIEWS_COMPARISON.md) - View templates analysis
- [COMPLETE_REPOSITORY_ANALYSIS.md](../COMPLETE_REPOSITORY_ANALYSIS.md) - Full repo analysis
- [IMPLEMENTATION_CHECKLIST.md](../IMPLEMENTATION_CHECKLIST.md) - Progress tracking

### Archive Reference

All original implementations available in:
- `archive/app/` - Application code
- `archive/resources/views/` - Blade templates
- `archive/config/` - Configuration

---

## Communication

### Reporting Progress

Use this format when reporting batch completion:

```markdown
## Batch Completion Report

**Batch**: BATCH_XX  
**Agent**: [Name/ID]  
**Status**: Complete  
**Duration**: Xh

### Deliverables
- [x] Item 1
- [x] Item 2
- [x] Item 3

### Test Results
- Unit tests: X passing
- Feature tests: Y passing
- Coverage: Z%

### Issues Encountered
1. Issue description and resolution

### Next Steps
- Recommendations for related work
```

### Getting Help

If blocked on a batch:

1. Review the reference documentation
2. Check archive implementation
3. Search Laravel 11 documentation
4. Ask specific questions with context
5. Document the blocker

---

## Version History

- **v1.0** (Nov 11, 2025): Initial creation
  - 10 batches defined
  - 175 hours total effort
  - Complete parallelization strategy

---

**Ready to Start**: All batches are ready for immediate implementation  
**Recommended**: Start with BATCH_01, BATCH_02, BATCH_03 in parallel (highest priority)
