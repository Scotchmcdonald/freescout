# Phase 1 Execution Log

Date: 2026-03-24
Status: In progress

## Wave 1 Started

- Added pure-unit tests for branch-heavy helpers in ImapService.
- Added pure-unit tests for SMTP mailbox guard and encryption mapping logic.
- Kept tests on PureUnitTestCase to avoid increasing framework-booting Unit tests.

## Wave 1 Results

- New test files:
	- tests/Unit/Services/ImapServicePureLogicTest.php
	- tests/Unit/Services/SmtpServicePureLogicTest.php
- Targeted run: 16 passed.
- Full unit lane: 287 passed.

Coverage deltas (method-level, focused run):
- ImapService::separateReply: 0% -> 95%
- ImapService::getOriginalSenderFromFwd: 0% -> 66.67%
- ImapService::getAddressesWithNames: 0% -> 56.52%
- ImapService::parseAddresses: 0% -> 64.71%
- SmtpService::validateMailboxSettings: 0% -> 100%
- SmtpService::getEncryption: 0% -> 100%
- SmtpService::decryptPassword: 0% -> 83.33%

## Next

- Expand into higher-impact ImapService decision paths with lightweight doubles.
- Add focused assertions for SmtpService settings validation branches if we can keep facade boot minimal.

## Wave 2 Started

- Avoided IMAP/SMTP file collisions while another agent continued integration-to-unit extraction.
- Added collision-safe pure unit coverage for RateLimiterService behavior and Mailbox alias parsing helpers.
- Kept additions in brand-new test files to avoid touching contested test paths.

## Wave 2 Results

- New test files:
	- tests/Unit/Services/RateLimiterServiceTest.php
	- tests/Unit/Models/MailboxAliasTest.php
- Targeted run: 11 passed.
- Full unit lane: 339 passed.

Coverage deltas (method-level, focused run):
- RateLimiterService::attempt: 0% -> 100%
- RateLimiterService::remaining: baseline not in top-impact list -> 100%
- RateLimiterService::getUsageStats: 0% -> 100%
- Mailbox::hasAlias: baseline not in top-impact list -> 100%
- Mailbox::getAliasesArray: baseline not in top-impact list -> 100%
- Mailbox::getAliases: 0% -> 91.67%
- Mailbox::removeMailboxEmailsFromList: baseline not in top-impact list -> 100%
- Mailbox::isFetchingEnabled: baseline not in top-impact list -> 100%
- Mailbox::isSendingEnabled: baseline not in top-impact list -> 100%

## Next

- Continue selecting untouched, pure-first service/model targets while concurrent test migration work is in progress.
- Good next candidates from the impact list that still look unit-safe: SavedSearch::getFiltersSummary, ModuleSourceService::getSampleModules/getModule, and additional RateLimiterService branches if we can isolate facades cleanly.

## Wave 3 Started

- Continued on untouched pure-first targets to avoid interfering with concurrent IMAP/SMTP and migration work.
- Added focused unit coverage for SavedSearch summary/display logic and ModuleSourceService sample/module lookup behavior.

## Wave 3 Results

- New test files:
	- tests/Unit/Models/SavedSearchTest.php
	- tests/Unit/Services/ModuleSourceServiceTest.php
- Targeted run: 8 passed.
- Full unit lane: 347 passed.

Coverage deltas (method-level, focused run):
- SavedSearch::getDisplayName: baseline not in top-impact list -> 100%
- SavedSearch::getFiltersSummary: 0% -> 100%
- ModuleSourceService::getModule: baseline not in top-impact list -> 100%
- ModuleSourceService::getSampleModules: 0% -> 100%

## Next

- Keep harvesting untouched, unit-safe model/service methods while concurrent refactors continue.
- Strong next candidates: ActivityLog::getEventDescription, Customer::setData, and User::hasPermission if we can isolate dependencies without introducing framework-booting Unit tests.

## Wave 4 Started

- Added another collision-safe pure unit batch focused on high-value model logic that does not require database-backed relations.
- Targeted ActivityLog label formatting, Customer::setData data-merging logic, and the pure branches of User permission handling.

## Wave 4 Results

- New test files:
	- tests/Unit/Models/ActivityLogTest.php
	- tests/Unit/Models/CustomerSetDataTest.php
	- tests/Unit/Models/UserPermissionLogicTest.php
- Targeted run: 13 passed.
- Full unit lane: 417 passed.

Coverage deltas (method-level, focused run):
- ActivityLog::getEventDescription: 0% -> 25%
- ActivityLog::getLogTitle: baseline not in top-impact list -> 50%
- ActivityLog::formatColTitle: baseline not in top-impact list -> 100%
- ActivityLog::getAvailableLogs: baseline not in top-impact list -> 75%
- Customer::setData: 0% -> 100%
- User::hasPermission: 0% -> 75%
- User::getGlobalUserPermissions: baseline not in top-impact list -> 100%

## Next

- Best next pure-first uplift is likely finishing the remaining unmatched branches in ActivityLog::getEventDescription/getLogTitle and the remaining non-DB User::hasPermission permutations.
- After that, revisit whether any untouched service helpers remain before moving deeper into framework-backed behavior.

## Wave 5 Started

- Executed the approved next chunk: finish ActivityLog pure branches, finish User pure permission branches, and revisit an untouched service helper via a separate ModuleSourceService helper test file.
- Kept the service-helper expansion isolated in a new file to avoid collisions with existing service test work.

## Wave 5 Results

- Updated test files:
	- tests/Unit/Models/ActivityLogTest.php
	- tests/Unit/Models/UserPermissionLogicTest.php
- New test files:
	- tests/Unit/Services/ModuleSourceServiceHelperTest.php
- Targeted run: 15 passed.
- Full unit lane: 442 passed.

Coverage deltas (method-level, focused run):
- ActivityLog::getEventDescription: 25% -> 100%
- ActivityLog::getLogTitle: 50% -> 100%
- ActivityLog::formatColTitle: remained 100%
- ActivityLog::getAvailableLogs: remained 75%
- User::hasPermission: 75% -> 100%
- User::getGlobalUserPermissions: remained 100%
- ModuleSourceService::getSourceUrl: baseline not in top-impact list -> 100%
- ModuleSourceService::getModules: baseline not in top-impact list -> 40%

## Next

- The remaining pure gap in ActivityLog is getAvailableLogs(true), which would require isolating getLogNames without hitting the database.
- The remaining meaningful pure service gap here is additional ModuleSourceService::getModules branches for HTTP success/failure/exception flows if we choose to stub facades rather than rely only on the testing-environment fast path.
- After that, the highest-value remaining work is likely either controlled facade-based service tests or waiting for the concurrent integration-to-unit migration stream to settle before tackling overlapping areas.

## Wave 6 Started

- Executed the approved 1-2-3 continuation: completed ActivityLog getAvailableLogs(true) branch isolation, expanded ModuleSourceService::getModules through HTTP success/failure/exception branches, and added the next safe facade-based helper batch for RateLimiterService.
- Kept all changes in pure/collision-safe unit files and avoided touching active IMAP/SMTP migration surfaces.

## Wave 6 Results

- Updated files:
	- app/Models/ActivityLog.php (late static binding for getAvailableLogs internals)
	- tests/Unit/Models/ActivityLogTest.php
	- tests/Unit/Services/ModuleSourceServiceHelperTest.php
- New file:
	- tests/Unit/Services/RateLimiterServiceFacadeTest.php
- Targeted run: 14 passed.
- Full unit lane: 449 passed.

Coverage deltas (method-level, focused run):
- ActivityLog::getEventDescription: remained 100%
- ActivityLog::getLogTitle: remained 100%
- ActivityLog::formatColTitle: remained 100%
- ActivityLog::getAvailableLogs: 75% -> 100%
- ModuleSourceService::getSourceUrl: remained 100%
- ModuleSourceService::getModules: 40% -> 100%
- RateLimiterService::clear: baseline not in top-impact list -> 100%
- RateLimiterService::resetExpired: baseline not in top-impact list -> 100%

## Next

- Re-run impact ranking from the latest coverage baseline to refresh priorities now that ActivityLog/User/ModuleSourceService and RateLimiter helper methods are at or near full method coverage.
- Continue with untouched high-impact pure-safe methods, or pause for collision-safe synchronization if concurrent migration work introduces overlap.

## Wave 7 Started

- Proceeded with the next collision-safe pure model chunk by expanding GooglePushChannel helper/scope method coverage and adding a dedicated User helper-methods unit test file.
- Kept all work in unit-only test files and avoided overlap with ongoing integration-to-unit migration streams.

## Wave 7 Results

- Updated files:
	- tests/Unit/Models/GooglePushChannelTest.php
- New files:
	- tests/Unit/Models/UserHelperMethodsTest.php
- Targeted run: 15 passed.
- Full unit lane: 500 passed.

Coverage deltas (method-level, focused run):
- GooglePushChannel::isExpired: baseline not in top-impact list -> 100%
- GooglePushChannel::isExpiringSoon: baseline not in top-impact list -> 100%
- GooglePushChannel::getHealthStatus: 0% -> 100%
- GooglePushChannel::getExpiresInAttribute: baseline not in top-impact list -> 100%
- GooglePushChannel::scopeActive: baseline not in top-impact list -> 100%
- GooglePushChannel::scopeExpired: baseline not in top-impact list -> 100%
- GooglePushChannel::scopeExpiringSoon: baseline not in top-impact list -> 100%
- User::hasVerifiedEmail: baseline not in top-impact list -> 100%
- User::isInternalStaff: baseline not in top-impact list -> 100%
- User::hasAdminAccess: baseline not in top-impact list -> 100%
- User::isClient: baseline not in top-impact list -> 100%
- User::isAutomaton: baseline not in top-impact list -> 100%
- User::isFinance: baseline not in top-impact list -> 100%
- User::isReporter: baseline not in top-impact list -> 100%
- User::isActive: baseline not in top-impact list -> 100%
- User::getFullName: baseline not in top-impact list -> 100%
- User::getFirstName: baseline not in top-impact list -> 100%
- User::getPhotoUrl: baseline not in top-impact list -> 100%
- User::dateFormat: baseline not in top-impact list -> 90%

## Next

- Finish the remaining dateFormat branch in User (invalid timezone path) to close that method to 100%.
- Re-rank remaining uncovered high-impact methods from the latest baseline and continue with the next pure-safe batch.

## Wave 8 Started

Two-pronged effort:
1. Close `User::dateFormat` to 100% by adding one test covering the invalid-timezone catch branch.
2. Add a comprehensive pure unit test file for Conversation scalar/helper methods.

## Wave 8 Results

- Edited test file:
    - tests/Unit/Models/UserHelperMethodsTest.php (added `test_date_format_silently_ignores_invalid_timezone_and_still_formats_date`)
- New test file:
    - tests/Unit/Models/ConversationHelperTest.php (25 new tests)
- Targeted run: 25 passed (77 assertions).
- Full unit lane: 500 → 520 passed.

Coverage deltas (method-level, focused clover run):
- User::dateFormat: 90% -> 100%
- Conversation::getCcArray: 0% -> 100%
- Conversation::getBccArray: 0% -> 100%
- Conversation::isActive: 0% -> 100%
- Conversation::isClosed: 0% -> 100%
- Conversation::isPhone: 0% -> 100%
- Conversation::isChat: 0% -> 100%
- Conversation::getStatusName: 0% -> 100%
- Conversation::getStatusColor: 0% -> 100%
- Conversation::getStatusLabel: 0% -> 100%
- Conversation::getTypeLabel: 0% -> 100%
- Conversation::sanitizeEmails: 0% -> 100%
- Conversation::hasFollowUpScheduled: 0% -> 100%
- Conversation::hasFollowUpBeenReminded: 0% -> 100%
- Conversation::isFollowUpOverdue: 0% -> 100%
- Conversation::getFollowUpStatus: 0% -> 93% (final defensive `return null;` is unreachable dead code)
- Conversation::getViewersInfo: 0% -> 41% (pure paths only; DB user-lookup path excluded)

## Next

- Next pure-safe candidates are Customer model helpers (getFullName, getDisplayName, etc.) and Thread model scalar helpers.
- Conversation::search, changeCustomer, moveToMailbox are all DB-coupled; skip for pure wave.
- Customer::create is DB-coupled; assess carefully.

## Wave 9 Started

Three new pure unit test files covering scalar helpers for Customer, Thread, and Folder models.

## Wave 9 Results

- New test files:
    - tests/Unit/Models/CustomerHelperTest.php (8 tests: getFullName, getFirstName)
    - tests/Unit/Models/ThreadHelperTest.php (15 tests: isCustomerMessage, isUserMessage, isNote, isBounce, isAutoResponder, getStatusName)
    - tests/Unit/Models/FolderHelperTest.php (2 tests: all 5 isXxx type helpers + constant distinctness)
- Targeted run: 23 passed (44 assertions).
- Full unit lane: 520 → 603 passed (+83).

Coverage deltas (method-level, focused clover run):
- Customer::getFullName: 0% -> 100%
- Customer::getFirstName: 0% -> 100%
- Thread::isCustomerMessage: 0% -> 100%
- Thread::isUserMessage: 0% -> 100%
- Thread::isNote: 0% -> 100%
- Thread::isBounce: 0% -> 100%
- Thread::isAutoResponder: 0% -> 100%
- Thread::getStatusName: 0% -> 100%
- Folder::isInbox: 0% -> 100%
- Folder::isSent: 0% -> 100%
- Folder::isDrafts: 0% -> 100%
- Folder::isSpam: 0% -> 100%
- Folder::isTrash: 0% -> 100%
- MailHelper::isAutoResponder: 0% -> 96% (one deeply-nested branch uncovered)

## Next

- Close MailHelper::isAutoResponder to 100% (one branch: delivered-to header with 'autoresponder' value).
- Customer::getMainEmail is DB-coupled (queries emails relationship); skip.
- Survey remaining uncovered methods in Email.php, Attachment.php, and any lightweight value-object helpers.
- Consider SavedReply, Tag, or other thin models with pure scalar methods.

## Wave 10 Started

Large batch (~76 tests) covering Email/Attachment/SendLog models and MailHelper/Helper misc classes.
Goal: increase throughput to ~80-120 tests per wave rather than 20-25.

## Wave 10 Results

- New test files:
    - tests/Unit/Models/EmailModelTest.php (11 tests: isPrimary, isSecondary, sanitizeEmail)
    - tests/Unit/Models/AttachmentModelTest.php (4 tests: isImage)
    - tests/Unit/Models/SendLogModelTest.php (12 tests: isSent, isFailed, wasOpened, wasClicked)
    - tests/Unit/Misc/MailHelperTest.php (36 tests: isAutoResponder, generateMessageId, getMessageIdHash, hasVars, parseEmail, sanitizeEmail/HTML, formatEmail, extractReply)
    - tests/Unit/Misc/HelperTest.php (19 tests: isInstalled, setGuzzleDefaultOptions, checkRequiredExtensions, getMissingExtensions, checkRequiredFunctions, getMissingFunctions, isFolderWritable, formatBytes, getSubdirectory)
- Targeted run: 76 passed (131 assertions).
- Full unit lane: 603 → 717 passed (+114).

Coverage deltas (method-level, focused clover run):
- Email::isPrimary: 0% -> 100%
- Email::isSecondary: 0% -> 100%
- Email::sanitizeEmail: 0% -> 100%
- Attachment::isImage: 0% -> 100%
- SendLog::isSent: 0% -> 100%
- SendLog::isFailed: 0% -> 100%
- SendLog::wasOpened: 0% -> 100%
- SendLog::wasClicked: 0% -> 100%
- MailHelper::isAutoResponder: stayed at 96% (1 env-only stmt; now covers delivered-to & x-autorespond & x-autoresponder & precedence junk/list & x-precedence)
- MailHelper::generateMessageId: 0% -> 100%
- MailHelper::getMessageIdHash: 0% -> 100%
- MailHelper::hasVars: 0% -> 100%
- MailHelper::parseEmail: 0% -> 100%
- MailHelper::sanitizeEmail(HTML): 0% -> 100%
- MailHelper::formatEmail: 0% -> 100%
- MailHelper::extractReply: 0% -> 100%
- Helper::isInstalled: 0% -> 100%
- Helper::setGuzzleDefaultOptions: 0% -> 100%
- Helper::checkRequiredExtensions: 0% -> 100%
- Helper::getMissingExtensions: 0% -> 80% (missing-ext branch unreachable in test env)
- Helper::checkRequiredFunctions: 0% -> 100%
- Helper::getMissingFunctions: 0% -> 83% (disabled-func branch unreachable in test env)
- Helper::isFolderWritable: 0% -> 100%
- Helper::formatBytes: 0% -> 100%
- Helper::getSubdirectory: 0% -> 100%

## Next

- MailHelper::replaceMailVars: high-value (30+ stmts) — pure with Customer/User/Mailbox/Conversation stubs; no DB
- Survey Services/ for remaining pure-safe methods (beyond collisions): SmtpService/ImapService still excluded
- ValueObjects/ in app/ directory if any exist
- app/Misc/Draft.php methods if safe (currently DB-coupled)

## Wave 11 Started

Large batch focused on pure helpers across misc + module models (MailHelper replacement path, PIB invoice status helpers, CaseManager checklist/decision helpers, CRM scalar helpers, Alerts key generation/constants).

## Wave 11 Results

- New test files:
	- tests/Unit/Misc/MailHelperReplaceVarsTest.php (29 tests: replaceMailVars data sources, fallbacks, escaping, remove_non_replaced)
	- tests/Unit/PIB/InvoiceModelTest.php (42 tests: status helpers, transitions, labels, badges, balances, formatting)
	- tests/Unit/CaseManager/CaseRecordHelperTest.php (25 tests: checklist helpers, decision labels, context-presence helpers)
	- tests/Unit/Crm/CrmModelsTest.php (9 tests: Client::isActive and Contact::getFullNameAttribute)
	- tests/Unit/Alerts/AlertModelsTest.php (12 tests: AlertThrottle::generateKey and AlertType constants/categories)
- Targeted run: 134 passed (170 assertions).
- Full unit lane: 717 -> 853 passed (+136).

Coverage deltas (method-level, focused impact):
- MailHelper::replaceMailVars: 0% -> high branch coverage via placeholder/fallback/escaping/remove tests
- Invoice::getOutstandingBalanceAttribute: 0% -> 100%
- Invoice::isPaid: 0% -> 100%
- Invoice::isDraft: 0% -> 100%
- Invoice::isFinalized: 0% -> 100%
- Invoice::isSubmitted: 0% -> 100%
- Invoice::isDisputed: 0% -> 100%
- Invoice::isPartiallyPaid: 0% -> 100%
- Invoice::isOverdue: 0% -> 100%
- Invoice::isEditable: 0% -> 100%
- Invoice::isPayable: 0% -> 100%
- Invoice::canTransitionTo: 0% -> 100%
- Invoice::statusBadgeVariant: 0% -> 100%
- Invoice::statusLabel: 0% -> 100%
- Invoice::getFormattedTotalAttribute: 0% -> 100%
- CaseRecord::checklistStatus: 0% -> 100%
- CaseRecord::checklistProgress: 0% -> 100%
- CaseRecord::isChecklistComplete: 0% -> 100%
- CaseRecord::isReadyForTech: 0% -> 100%
- CaseRecord::decisionPathLabel: 0% -> 100%
- CaseRecord::isDecisionEngineProcessed: 0% -> 100%
- CaseRecord::hasHistoricalContext: 0% -> 100%
- CaseRecord::hasKbSearchResult: 0% -> 100%
- Client::isActive: 0% -> 100%
- Contact::getFullNameAttribute: 0% -> 100%
- AlertThrottle::generateKey: 0% -> 100%

Artifacts:
- Baseline archive: reports/coverage/baselines/phase1-wave11-20260324-150817/
- Stable link: reports/coverage/baselines/phase1-wave11-latest

## Next

- CircuitBreakerService and CacheService remain candidates but require DB/Cache facade swapping strategy to keep tests pure and deterministic.
- Continue module scalar-helper sweep in Alerts/CaseManager/PIB/CRM for any untouched non-DB methods.
- Keep avoiding IMAP/SMTP collision surfaces while the integration-migration workstream is active.

## Wave 12 Started

Combined large-batch wave on service helpers plus module scalar helpers (CircuitBreakerService + CacheService + Alerts/CaseManager/Action1/CRM pure model methods).

## Wave 12 Results

- New test files:
	- tests/Unit/Services/CircuitBreakerServiceTest.php (13 tests: open-state checks, threshold transitions, recovery attempts, reset paths)
	- tests/Unit/Services/CacheServiceTest.php (20 tests: tagged/non-tagged remember/get/put/has/forget/flush/warm + null-attribute keying)
	- tests/Unit/Alerts/AlertSubscriptionHelperTest.php (22 tests: client/channel/digest helpers, recipient accessors, AlertType accessors, NotificationSubscription helpers)
	- tests/Unit/Alerts/AlertDeliveryDigestHelperTest.php (12 tests: delivery status transitions, digest scheduling, queue payload creation)
	- tests/Unit/CaseManager/DiagnosticQuickWinHelperTest.php (10 tests: completion/success/duration/pending helpers)
	- tests/Unit/Action1/Action1ConfigHelperTest.php (3 tests: assigned/unassigned helpers)
	- tests/Unit/Crm/ClientConversationHelperTest.php (7 tests: isOpen, resolution minutes, constants distinctness)
- Targeted run: 87 passed (149 assertions).
- Full unit lane: 853 -> 940 passed (+87).

Coverage deltas (method-level, focused impact):
- CircuitBreakerService::isOpen: 0% -> covered
- CircuitBreakerService::call: 0% -> covered (closed/open/half-open/failure/reset paths)
- CircuitBreakerService::shouldAttemptRecovery: 0% -> covered
- CircuitBreakerService::reset: 0% -> covered
- CacheService::remember: 0% -> covered (tagged + non-tagged)
- CacheService::put/get/has/forget/flushEntity: 0% -> covered across tag and non-tag branches
- CacheService::warmMultiple: 0% -> covered success + exception path
- CacheService::flushDomain: 0% -> covered
- AlertSubscription::appliesToClient/hasChannel/isDigest/getEmailRecipient/getSmsRecipient: 0% -> covered
- AlertType::getSeverityColorAttribute/getSeverityIconAttribute: 0% -> covered
- NotificationSubscription::getAlertTypes/hasChannel: 0% -> covered
- AlertDeliveryLog::markSent/markDelivered/markFailed/markThrottled: 0% -> covered
- AlertDigestQueue::markProcessed/queueForDigest/calculateDigestTime (+daily/weekly helpers): 0% -> covered
- Diagnostic::isComplete/isSuccessful/getDurationInMinutes: 0% -> covered
- QuickWin::isPending: 0% -> covered
- Action1Config::isAssigned/isUnassigned: 0% -> covered
- ClientConversation::isOpen/getResolutionTimeMinutes: 0% -> covered

Artifacts:
- Baseline archive: reports/coverage/baselines/phase1-wave12-20260324-152145/
- Stable link: reports/coverage/baselines/phase1-wave12-latest

## Next

- Sweep remaining module scalar helpers in ContractManager and PIB models for another high-yield pure wave.
- Add focused assertions on CacheService registry edge cases if additional regressions appear.
- Keep parallel single-path invocation pattern for this harness (`php artisan test <path> --parallel --processes=10`).

## Wave 13 Started

High-yield pure helper wave focused on ContractManager and PIB model scalar/status helpers (Contract/Quote/QuoteLineItem/Milestone + ReconciliationRun/TimeEntry).

## Wave 13 Results

- New test files:
	- tests/Unit/ContractManager/ContractManagerModelHelpersTest.php (13 tests: contract lifecycle helpers, quote status/expiry, line-item quantity helpers, milestone status/overdue/status-info helpers)
	- tests/Unit/PIB/ReconciliationTimeEntryHelperTest.php (10 tests: reconciliation status/rate/info/duration helpers, time-entry duration/amount/invoiced helpers)
- Targeted run: 23 passed (75 assertions).
- Full unit lane: 940 -> 963 passed (+23).

Coverage deltas (method-level, focused impact):
- Contract::isActive/isExpiringSoon/isExpired/daysUntilExpiration/isPurchased: 0% -> covered
- Quote::isDraft/isApproved/isExpired: 0% -> covered
- QuoteLineItem::isPerUser/isPerAsset/isFixed: 0% -> covered
- Milestone::isAchieved/isPending/isInProgress/isBlocked/isSkipped/isOverdue/getStatusInfo: 0% -> covered
- ReconciliationRun::isComplete/isRunning/isSuccessful/calculateSuccessRate/getStatusInfo/getDurationAttribute: 0% -> covered
- TimeEntry::getDurationHoursAttribute/getFormattedDurationAttribute/getTotalAmountAttribute/isInvoiced: 0% -> covered

Artifacts:
- Baseline archive: reports/coverage/baselines/phase1-wave13-20260324-153037/
- Stable link: reports/coverage/baselines/phase1-wave13-latest

## Next

- Continue module-helper sweep with remaining ContractManager/PIB helpers that do not require relation queries.
- Consider a focused pass on ContractManager DTO/enum/value-object style helpers if present.
- Preserve pure-test isolation pattern (raw attribute setup + relation preloading/null markers) for DB-free model helper coverage.

## Wave 14 Started

Pure helper sweep across remaining ContractManager and PIB model utility logic (BillingTemplate/ContractSchedule and ServiceUsage/InvoiceLineItem/ClientCredit).

## Wave 14 Results

- New test files:
	- tests/Unit/ContractManager/BillingAndScheduleHelperTest.php (7 tests: billing template status/due/config/billing-date advance + contract schedule type/due/advance helpers)
	- tests/Unit/PIB/UsageAndCreditHelperTest.php (6 tests: service usage type/status/permission/total helpers, invoice line item total, client credit balance accessor/mutator)
- Targeted run: 13 passed (63 assertions).
- Full unit lane: 963 -> 976 passed (+13).

Coverage deltas (method-level, focused impact):
- BillingTemplate::getProductType/isActive/isPaused/isDue/getConfigValue/advanceToNextBillingDate: 0% -> covered
- ContractSchedule::isBillingSchedule/isRenewalSchedule/isDue/advanceToNext: 0% -> covered
- ServiceUsage::serviceTypes/calculateTotal/isDraft/isPending/isApproved/isBilled/canEdit/canDelete/canApprove: 0% -> covered
- InvoiceLineItem::calculateTotal: 0% -> covered
- ClientCredit::getBalanceAttribute/setBalanceAttribute: 0% -> covered

Artifacts:
- Baseline archive: reports/coverage/baselines/phase1-wave14-20260324-153552/
- Stable link: reports/coverage/baselines/phase1-wave14-latest

## Next

- Continue harvesting remaining pure helper pockets in ContractManager/PIB models with low DB coupling.
- Re-run impact ranking after each large wave to focus on the highest statement-weight methods still uncovered.

