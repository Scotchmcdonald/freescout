# FreeScout Migration Gap Analysis

## Overview

This document provides a comprehensive gap analysis between the legacy Laravel 5 application (located in `/archive/`) and the modern Laravel 11 application (located in the root directory). The analysis identifies missing features, business logic, and UI components that have not yet been fully ported to the modern version.

---

## Missing or Incomplete Functionality

### 1. Route & Controller Mapping

#### 1.1 Translation Management (MISSING)

The legacy application includes a complete translation management system that is not present in the new version.

- **Missing Routes:**
  - `POST /translations/send` - Send translations to FreeScout team
  - `POST /translations/removeUnpublished` - Remove unpublished translations
  - `POST /translations/download` - Download translations as ZIP

- **Missing Controller:**
  - `/archive/app/Http/Controllers/TranslateController.php`
  
- **Key Features Missing:**
  - Translation submission to development team
  - Translation archive/download functionality
  - Unpublished translation management

---

#### 1.2 Secure Controller Functions (MISSING/REFACTORED)

The legacy `SecureController` handles dashboard and logging functionality that has been partially migrated.

| Feature | Archive Location | New Location | Status |
|---------|------------------|--------------|--------|
| Dashboard | `/archive/app/Http/Controllers/SecureController.php::dashboard()` | `/app/Http/Controllers/DashboardController.php` | Needs Verification |
| Activity Logs with Categories | `/archive/app/Http/Controllers/SecureController.php::logs()` | `/app/Http/Controllers/SystemController.php::logs()` | Incomplete |
| Log Clearing | `/archive/app/Http/Controllers/SecureController.php::logsSubmit()` | N/A | MISSING |
| File Upload Utility | `/archive/app/Http/Controllers/SecureController.php::upload()` | N/A | MISSING |

**Missing Functionality in Logs:**
- Multi-category log support (User logs, Out Emails, Send Errors, Fetch Errors, System, App Logs)
- SendLog integration showing outgoing email details
- Log clearing functionality by category
- Dynamic column detection for log entries

**Reference:** `/archive/resources/views/secure/logs.blade.php`

---

#### 1.3 Open/Public Controller (PARTIALLY IMPLEMENTED)

The legacy `OpenController` handles public routes.

| Feature | Archive Location | New Location | Status |
|---------|------------------|--------------|--------|
| User Setup (Invitation) | `/archive/app/Http/Controllers/OpenController.php::userSetup()` | `/app/Http/Controllers/UserController.php::userSetup()` | Needs Verification |
| Thread Read Tracking | `/archive/app/Http/Controllers/OpenController.php::setThreadAsRead()` | `/app/Http/Controllers/TrackingController.php` | Needs Verification |
| Attachment Download | `/archive/app/Http/Controllers/OpenController.php::downloadAttachment()` | `/app/Http/Controllers/PublicAttachmentController.php` | Needs Verification |

**Missing Features:**
- Apache mod_xsendfile support for attachments
- Nginx X-Accel-Redirect support for attachments  
- Viewable attachment MIME type filtering

**Reference:** `/archive/app/Http/Controllers/OpenController.php` (lines 145-220)

---

#### 1.4 Settings Controller (PARTIALLY IMPLEMENTED)

The legacy settings system is more comprehensive.

| Section | Archive Location | New Location | Status |
|---------|------------------|--------------|--------|
| General Settings | `/archive/app/Http/Controllers/SettingsController.php` | `/app/Http/Controllers/SettingsController.php` | Incomplete |
| Email Settings | `/archive/resources/views/settings/emails.blade.php` | `/resources/views/settings/` | Needs Verification |
| Alerts Settings | `/archive/resources/views/settings/alerts.blade.php` | `/resources/views/settings/` | MISSING |

**Missing General Settings:**
- Custom conversation number configuration
- User global permissions management
- Email conversation history settings (none/last/full)
- Max message size configuration
- Open tracking toggle
- Email branding toggle ("Powered by FreeScout")
- User notification email history settings

**Reference:** `/archive/resources/views/settings/general.blade.php`

**Missing Alert Settings:**
- Alert recipients configuration
- Fetch monitoring alerts
- Log monitoring alerts with name selection
- Subscription defaults configuration

**Reference:** `/archive/resources/views/settings/alerts.blade.php`

---

#### 1.5 System Controller (PARTIALLY IMPLEMENTED)

| Feature | Archive Location | New Location | Status |
|---------|------------------|--------------|--------|
| System Status | `/archive/app/Http/Controllers/SystemController.php::status()` | `/app/Http/Controllers/SystemController.php::index()` | Incomplete |
| Tools | `/archive/app/Http/Controllers/SystemController.php::tools()` | N/A | MISSING |
| Web Cron | `/archive/app/Http/Controllers/SystemController.php::cron()` | N/A | MISSING |
| Ajax Update | `/archive/app/Http/Controllers/SystemController.php::ajax()` | `/app/Http/Controllers/SystemController.php::ajax()` | Incomplete |
| Job Details Modal | `/archive/app/Http/Controllers/SystemController.php::ajaxHtml()` | N/A | MISSING |

**Missing System Status Features:**
- PHP extensions check
- Required functions check
- Directory permissions check
- Cache file writability check
- Public symlink verification
- .env file writability check
- Command running status (fetch-emails, queue:work)
- Version update check and update functionality
- Migration status detection
- Module symlink validation

**Missing System Tools Features:**
- Clear cache button with output
- Migrate DB button
- Logout all users button
- Fetch emails with parameters (days, unseen, debug)
- Console output display

**References:** 
- `/archive/app/Http/Controllers/SystemController.php`
- `/archive/resources/views/system/status.blade.php`
- `/archive/resources/views/system/tools.blade.php`

---

#### 1.6 Modules Controller (INCOMPLETE)

The module management system in the legacy app is significantly more comprehensive.

| Feature | Archive Location | New Location | Status |
|---------|------------------|--------------|--------|
| Module Listing | `/archive/app/Http/Controllers/ModulesController.php::modules()` | `/app/Http/Controllers/ModulesController.php::index()` | Incomplete |
| License Management | `/archive/app/Http/Controllers/ModulesController.php::ajax()` | N/A | MISSING |
| Module Installation | `/archive/app/Http/Controllers/ModulesController.php::ajax()` | N/A | MISSING |
| Module Updates | `/archive/app/Http/Controllers/ModulesController.php::ajax()` | N/A | MISSING |

**Missing Module Features (High Priority):**
- License activation/deactivation via WpApi
- Module installation from remote repository
- Module update system with version checking

**Missing Module Features (Medium Priority):**
- Third-party module support
- Module directory (marketplace) integration
- Bulk module updates
- Module symlink validation

**Reference:** `/archive/app/Http/Controllers/ModulesController.php`

---

#### 1.7 Conversations Controller (PARTIALLY IMPLEMENTED)

The conversations controller has extensive functionality in the legacy version.

| Feature | Archive Location | New Location | Status |
|---------|------------------|--------------|--------|
| View Conversation | `/archive/app/Http/Controllers/ConversationsController.php::view()` | `/app/Http/Controllers/ConversationController.php::show()` | Needs Verification |
| Create Conversation | `/archive/app/Http/Controllers/ConversationsController.php::create()` | `/app/Http/Controllers/ConversationController.php::create()` | Needs Verification |
| Clone Conversation | `/archive/app/Http/Controllers/ConversationsController.php::cloneConversation()` | `/app/Http/Controllers/ConversationController.php::clone()` | Needs Verification |
| Ajax Operations | `/archive/app/Http/Controllers/ConversationsController.php::ajax()` | `/app/Http/Controllers/ConversationController.php::ajax()` | Incomplete |
| Undo Reply | `/archive/app/Http/Controllers/ConversationsController.php::undoReply()` | `/app/Http/Controllers/ConversationController.php::undoSend()` | Needs Verification |
| Chats Mode | `/archive/app/Http/Controllers/ConversationsController.php::chats()` | `/app/Http/Controllers/ConversationController.php::chats()` | Needs Verification |

**Missing Ajax Actions:**
- `conversation_change_user` - Full implementation with redirect logic
- `conversation_change_status` - Including "not_spam" handling
- `send_reply` - Complete email/phone/custom conversation handling
- `save_draft` - Auto-save and manual draft saving
- `discard_draft` - Draft removal
- `create_phone_conversation` - Phone conversation creation
- `change_customer` - Customer change with email handling
- `delete_conversation` - Soft delete to Deleted folder
- `delete_conversation_forever` - Permanent deletion
- `restore_conversation` - Restore from Deleted
- `load_edit_thread` / `save_edit_thread` - Thread editing
- `delete_thread` - Note deletion
- `bulk_conversation_change_user` - Bulk assignee change
- `bulk_conversation_change_status` - Bulk status change
- `bulk_delete_conversation` - Bulk deletion
- `empty_folder` - Empty entire folder
- `conversation_move` - Move to different mailbox
- `conversation_merge` - Merge conversations
- `follow` / `unfollow` - Conversation following
- `update_subject` - Subject editing
- `merge_search` - Search for merge target
- `chats_load_more` - Pagination for chats
- `retry_send` - Retry failed email sending
- `load_customer_info` - Load customer sidebar data

**Missing Ajax HTML Actions:**
- `send_log` - Show send log modal
- `show_original` - Show original email headers
- `change_customer` - Customer change modal
- `move_conv` - Move conversation modal
- `merge_conv` - Merge conversation modal
- `assignee_filter` - Assignee filter dropdown
- `default_redirect` - Default redirect settings

**Reference:** `/archive/app/Http/Controllers/ConversationsController.php` (2400+ lines)

---

#### 1.8 Mailboxes Controller (PARTIALLY IMPLEMENTED)

| Feature | Archive Location | New Location | Status |
|---------|------------------|--------------|--------|
| Mailbox List | `/archive/app/Http/Controllers/MailboxesController.php::mailboxes()` | `/app/Http/Controllers/MailboxController.php::index()` | Needs Verification |
| Create Mailbox | `/archive/app/Http/Controllers/MailboxesController.php::create()` | `/app/Http/Controllers/MailboxController.php::create()` | Needs Verification |
| Update Settings | `/archive/app/Http/Controllers/MailboxesController.php::update()` | `/app/Http/Controllers/MailboxController.php::settings()` | Incomplete |
| Connection Settings | `/archive/app/Http/Controllers/MailboxesController.php::connectionOutgoing()` | `/app/Http/Controllers/MailboxController.php::connectionOutgoing()` | Needs Verification |
| OAuth | `/archive/app/Http/Controllers/MailboxesController.php::oauth()` | `/app/Http/Controllers/MailboxController.php::oauthConnect()` | Needs Verification |
| Auto Reply | `/archive/app/Http/Controllers/MailboxesController.php::autoReply()` | `/app/Http/Controllers/MailboxController.php::autoReply()` | Needs Verification |

**Missing Mailbox Settings:**
- Email aliases configuration with reply behavior
- From name options (mailbox name, user name, both, custom)
- Ticket assignment options (leave unassigned, to replying user)
- Before reply/forward text customization
- Signature configuration with editor
- Ratings system toggle
- IMAP/POP3 incoming mail advanced settings

**Reference:** `/archive/app/Http/Controllers/MailboxesController.php`

---

#### 1.9 Users Controller (PARTIALLY IMPLEMENTED)

| Feature | Archive Location | New Location | Status |
|---------|------------------|--------------|--------|
| Users List | `/archive/app/Http/Controllers/UsersController.php::users()` | `/app/Http/Controllers/UserController.php::index()` | Needs Verification |
| Create User | `/archive/app/Http/Controllers/UsersController.php::create()` | `/app/Http/Controllers/UserController.php::create()` | Needs Verification |
| User Profile | `/archive/app/Http/Controllers/UsersController.php::profile()` | `/app/Http/Controllers/UserController.php::edit()` | Needs Verification |
| Permissions | `/archive/app/Http/Controllers/UsersController.php::permissions()` | `/app/Http/Controllers/UserController.php::permissions()` | Needs Verification |
| Notifications | `/archive/app/Http/Controllers/UsersController.php::notifications()` | `/app/Http/Controllers/UserController.php::notifications()` | Needs Verification |
| Password | `/archive/app/Http/Controllers/UsersController.php::password()` | N/A | MISSING as separate page |
| Ajax Operations | `/archive/app/Http/Controllers/UsersController.php::ajax()` | N/A | MISSING |

**Missing User Features:**
- Separate password change page
- User photo upload and management
- User invitation system with email
- User deletion with conversation reassignment
- AJAX operations (photo delete, resend invite, delete user, etc.)
- Personal folders sync

**Reference:** `/archive/app/Http/Controllers/UsersController.php`

---

#### 1.10 Customers Controller (PARTIALLY IMPLEMENTED)

| Feature | Archive Location | New Location | Status |
|---------|------------------|--------------|--------|
| Edit Customer | `/archive/app/Http/Controllers/CustomersController.php::update()` | `/app/Http/Controllers/CustomerController.php::edit()` | Needs Verification |
| Customer Conversations | `/archive/app/Http/Controllers/CustomersController.php::conversations()` | `/app/Http/Controllers/CustomerController.php::conversations()` | Needs Verification |
| Ajax Search | `/archive/app/Http/Controllers/CustomersController.php::ajaxSearch()` | `/app/Http/Controllers/CustomerController.php::search()` | Needs Verification |
| Customer Merge | `/archive/app/Http/Controllers/CustomersController.php::merge()` | `/app/Http/Controllers/CustomerController.php::merge()` | Needs Verification |

**Missing Customer Features:**
- Customer photo upload
- Multiple email management with add/remove
- Email migration between customers
- Phone field management
- Social profiles management
- Website fields management
- Visibility limits based on user permissions

**Reference:** `/archive/app/Http/Controllers/CustomersController.php`

---

### 2. Blade View Inspection

#### 2.1 Missing Views

| View | Archive Location | Purpose |
|------|------------------|---------|
| Logs View | `/archive/resources/views/secure/logs.blade.php` | Activity/send log display with DataTables |
| System Status | `/archive/resources/views/system/status.blade.php` | System health display |
| System Tools | `/archive/resources/views/system/tools.blade.php` | Admin tools with console output |
| Settings Alerts | `/archive/resources/views/settings/alerts.blade.php` | Alert configuration |
| Settings Emails | `/archive/resources/views/settings/emails.blade.php` | Email/SMTP configuration |
| User Setup | `/archive/resources/views/open/user_setup.blade.php` | User invitation setup |
| Modules Directory | `/archive/resources/views/modules/modules.blade.php` | Module marketplace |
| Conversation Partials | `/archive/resources/views/conversations/partials/` | Various conversation UI components |

#### 2.2 Missing UI Components in Conversations

The legacy conversation views contain extensive UI components:

**Missing from `/archive/resources/views/conversations/partials/`:**
- `thread_attachments.blade.php` - Attachment display in threads
- `settings_modal.blade.php` - Conversation settings popup
- `prev_convs_short.blade.php` - Previous conversations sidebar
- `threads.blade.php` - Threads container
- `badges.blade.php` - Status/type badges
- `thread.blade.php` - Individual thread display
- `customer_sidebar.blade.php` - Customer info sidebar
- `edit_thread.blade.php` - Thread edit form
- `bulk_actions.blade.php` - Bulk operation controls
- `merge_search_result.blade.php` - Merge search results

**Missing from `/archive/resources/views/conversations/ajax_html/`:**
- `send_log.blade.php` - Send log modal content
- `show_original.blade.php` - Original email display
- `change_customer.blade.php` - Customer change dialog
- `move_conv.blade.php` - Move conversation dialog
- `merge_conv.blade.php` - Merge conversation dialog
- `assignee_filter.blade.php` - Assignee filter dropdown
- `default_redirect.blade.php` - After-send redirect settings

#### 2.3 Missing Form Inputs & Interaction Points

**Settings General View (`/archive/resources/views/settings/general.blade.php`):**
- Company name input
- Conversation number type (ID vs Custom)
- Next conversation number input
- User permissions checkboxes
- Default language select
- Timezone select
- Time format radio buttons (12h/24h)
- Email conversation history select
- Max message size input
- Open tracking toggle
- Email branding toggle
- User notification history select

**Conversation Create/View (`/archive/resources/views/conversations/`):**
- Multiple recipient inputs (To, CC, BCC)
- Subject editing (inline)
- Assignee dropdown
- Status dropdown
- Folder indicator
- Follow/unfollow button
- Customer change link
- Merge button
- Move button
- Delete/restore buttons
- Edit thread button
- Forwarding controls
- Phone conversation name/phone inputs
- Custom conversation type support

#### 2.4 Conditional Logic Indicating Missing Data Handling

**From archive views, these conditionals suggest missing backend support:**

```blade
@if ($current_name != App\ActivityLog::NAME_OUT_EMAILS)
    <!-- Log clearing form -->
@endif

@if (count($to_customers))
    <!-- Multiple customer email handling -->
@endif

@if ($conversation->state == Conversation::STATE_DRAFT)
    <!-- Draft conversation handling -->
@endif

@foreach ($viewers as $viewer)
    <!-- Real-time conversation viewing indicators -->
@endforeach

@if ($is_following)
    <!-- Conversation follow/unfollow state -->
@endif

@foreach ($from_aliases as $alias)
    <!-- Mailbox alias selection -->
@endforeach
```

---

### 3. Model & Service Logic

#### 3.1 Missing Models

| Model | Archive Location | Purpose |
|-------|------------------|---------|
| ActivityLog | `/archive/app/ActivityLog.php` | Activity logging with categories |
| FailedJob | `/archive/app/FailedJob.php` | Failed queue job management |
| Job | `/archive/app/Job.php` | Queue job management |

#### 3.2 Missing Model Methods & Scopes

**User Model (`/archive/app/User.php`):**
- `scopeNonDeleted()` - Exclude deleted users
- `mailboxesCanView()` - Get accessible mailboxes
- `mailboxesCanViewWithSettings()` - Get accessible mailboxes with settings
- `mailboxesWithSettings()` - Get mailboxes with pivot data
- `canManageMailbox()` - Check mailbox management permission
- `hasManageMailboxPermission()` - Check specific permission
- `sendInvite()` - Send invitation email
- `savePhoto()` - Photo upload handling
- `getPhones()` - Get phone numbers
- `followConversation()` - Follow a conversation
- `clearWebsiteNotificationsCache()` - Clear notification cache
- `generateRandomPassword()` - Generate secure password
- `dateFormat()` - User-specific date formatting

**Conversation Model (`/archive/app/Conversation.php`):**
- `isInFolderAllowed()` - Check folder permission
- `changeUser()` - Change assignee with logging
- `changeStatus()` - Change status with logging
- `changeSubject()` - Change subject with logging
- `changeCustomer()` - Change customer with logging
- `mergeConversations()` - Merge two conversations
- `moveToMailbox()` - Move to different mailbox
- `deleteToFolder()` - Soft delete to Deleted folder
- `deleteForever()` - Permanent deletion
- `url()` - Generate conversation URL
- `urlNext()` - Get next conversation URL
- `getExcludeArray()` - Get emails to exclude from CC
- `getCcArray()` / `getBccArray()` - Get CC/BCC arrays
- `sanitizeEmails()` - Clean email array
- `getForwardChildConversation()` - Get forwarded conversation
- `isUserFollowing()` - Check if user follows conversation

**Thread Model (`/archive/app/Thread.php`):**
- `createExtended()` - Create thread with full data
- `replaceBase64ImagesWithAttachments()` - Handle pasted images
- `getCleanBody()` - Get sanitized body
- `isForward()` - Check if thread is forward
- `fetchBody()` - Fetch original body via IMAP
- `getFailedJobId()` - Get associated failed job
- `updateSendStatusData()` - Update send status meta

**Mailbox Model (`/archive/app/Mailbox.php`):**
- `getEmails()` - Get all mailbox emails including aliases
- `getAliases()` - Get configured aliases
- `getUserSettings()` - Get user-specific settings
- `userHasAccess()` - Check user access
- `usersAssignable()` - Get users who can be assigned
- `updateFoldersCounters()` - Update folder counts
- `getAccessibleFolders()` - Get accessible folders

**Customer Model (`/archive/app/Customer.php`):**
- `getByEmail()` - Find customer by email
- `emailsToCustomers()` - Convert emails to customer data
- `findByPhone()` - Find customer by phone
- `setData()` - Set customer data from array
- `syncEmails()` - Sync email addresses
- `savePhoto()` - Photo upload handling

**SendLog Model (`/archive/app/SendLog.php`):**
- `log()` - Create log entry
- `getStatusName()` - Human-readable status
- `getMailTypeName()` - Human-readable mail type
- `isErrorStatus()` / `isSuccessStatus()` - Status checks

**ActivityLog Model (`/archive/app/ActivityLog.php`):**
- `getEventDescription()` - Human-readable event description
- `getLogTitle()` - Category title
- `getLogNames()` - Available log categories
- `getAvailableLogs()` - All available logs
- `formatColTitle()` - Format column headers

#### 3.3 Missing Services/Helpers

**WpApi (`/archive/app/Misc/WpApi.php`):**
- Module marketplace API integration
- License activation/deactivation
- Module version checking
- Module download functionality

**MailHelper:**
- `sendTestMail()` - Send test email
- `sendEmailToDevs()` - Send to developers
- `sanitizeSmtpStatusMessage()` - Clean SMTP messages

**Helper Functions (from `/archive/app/Misc/Helper.php`):**
- `createZipArchive()` - Create ZIP files
- `downloadRemoteFile()` - Download from URL
- `unzip()` - Extract ZIP files
- `setEnvFileVar()` - Modify .env file
- `checkRequiredExtensions()` - Check PHP extensions
- `checkRequiredFunctions()` - Check PHP functions
- `isFolderWritable()` - Check folder permissions
- `getWebCronHash()` - Get cron security hash
- `runCommand()` - Run artisan command
- `backgroundAction()` - Schedule delayed action
- `setGlobalEntity()` - Store global entity reference
- `isChatMode()` / `setChatMode()` - Chat mode handling

---

### 4. Event System

The legacy application uses an extensive event system that may not be fully ported:

**Eventy Actions & Filters:**
- `dashboard.mailboxes` - Filter dashboard mailbox list
- `conversation.view.start` - Action before view render
- `conversation.view.threads` - Filter thread list
- `conversation.send_reply_save` - Action after reply save
- `conversation.status_changed` - Action on status change
- `conversation.user_changed` - Action on assignee change
- `conversation.state_changed` - Action on state change
- `conversation.created_by_user` - Action on creation
- `conversation.created_by_user_can_undo` - Action before undo timeout
- `conversation.user_replied` - Action on reply
- `conversation.user_replied_can_undo` - Action before undo timeout
- `conversation.user_forwarded` - Action on forward
- `conversation.note_added` - Action on note add
- `settings.sections` - Filter settings sections
- `settings.section_params` - Filter section parameters
- `settings.section_settings` - Filter section settings
- `settings.before_save` / `settings.after_save` - Save hooks
- `user.setup_save` - Filter user during setup
- `user.create_save` - Filter user during creation
- `customer.updated` - Action after customer update
- `modules.register_error` - Filter module registration errors

---

### 5. Summary of Critical Missing Features

#### High Priority (Core Functionality)

1. **Translation Management System** - Complete system missing
2. **System Tools & Status Page** - Comprehensive admin tools missing
3. **Activity Logging System** - Multi-category logging missing
4. **Module License Management** - Activation/deactivation missing
5. **Conversation Draft System** - Auto-save and manual drafts incomplete
6. **Bulk Operations** - Missing bulk status/assignee/delete operations
7. **Thread Editing** - Missing thread edit functionality
8. **Conversation Following** - Follow/unfollow system missing

#### Medium Priority (Enhanced Functionality)

1. **Email Aliases** - Mailbox alias support incomplete
2. **Phone Conversations** - Phone conversation type incomplete
3. **Custom Conversation Types** - Custom type support missing
4. **Advanced Search** - Filter-based search incomplete
5. **Real-time Viewing** - Who's viewing conversation feature
6. **Web Cron** - HTTP-based cron trigger missing
7. **Attachments via sendfile** - Server-optimized downloads missing

#### Low Priority (Nice to Have)

1. **DataTables Integration** - Advanced table features
2. **Floating Flash Messages** - Enhanced notification display
3. **Photo Management** - User/customer photo uploads
4. **Email Open Tracking** - Tracking pixel system

---

### 6. Recommendations

1. **Prioritize Core Conversation Operations:** The conversation AJAX actions are critical for daily use and should be fully ported.

2. **Implement Activity Logging:** The SendLog and ActivityLog systems provide valuable debugging and audit capabilities.

3. **Port System Administration:** System status and tools are essential for server administrators.

4. **Review Event System:** Ensure all Eventy hooks are implemented to maintain module compatibility.

5. **Test Module System:** The module licensing and update system is critical for the FreeScout ecosystem.

---

*Generated: November 2024*
*Comparison: Laravel 5 (archive) → Laravel 11 (root)*
