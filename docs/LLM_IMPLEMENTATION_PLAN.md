# LLM Implementation Plan & Checklist

**Target Audience:** AI Agents (Gemini 3 Pro, Copilot, etc.)
**Objective:** Complete the migration of FreeScout to Laravel 11, ensuring feature parity with the legacy codebase (`archive/`) and robust system architecture.

## 1. Context & Architecture

*   **Legacy Codebase**: Located in `archive/`. Use this as the "Source of Truth" for business logic, permission checks, and edge case handling.
*   **Modern Codebase**: Located in `app/`. Built on Laravel 11.
*   **Extensibility**: The application MUST use `torann/laravel-eventy` to allow modules to inject content. Do not hardcode UI elements if they were previously dynamic.
*   **Marketplace**: Uses `app/Misc/WpApi.php` to communicate with the official FreeScout marketplace.

---

## 2. Implementation Checklist

### Phase 1: Core Connectivity & Marketplace (High Priority)

#### 1.1 Module Management (GitHub/Custom Source)
*   **Context**: Users need to install/update modules. We are decoupling from the original FreeScout marketplace to allow deployment of custom/forked modules via GitHub or direct download.
*   **Status**: **Completed**.
*   **Tasks**:
    - [x] **Module Source**: Replace `WpApi` logic with a service that fetches module metadata from a GitHub repository or custom JSON endpoint.
    - [x] **Installation/Update Logic**: Implement `ModulesController::install` and `update` to download ZIPs from GitHub Releases (or provided URL), unzip to `Modules/`, and run migrations.
    - [x] **Remove Licensing**: Remove `activate_license` actions and any dependencies on the legacy licensing system.
    - [x] **UI Updates**: Rename "Marketplace" to "Available Modules" (or similar) and list modules from the new source.

#### 1.2 OAuth Support (Gmail/Outlook)
*   **Context**: Modern email providers require OAuth. Legacy had custom implementation.
*   **Status**: **Completed**.
*   **Tasks**:
    - [x] **Database**: Verify `mailboxes` table has `oauth_provider`, `oauth_token`, `oauth_refresh_token`, `oauth_expires_at`.
    - [x] **Routes**: Add `mailbox/oauth/connect/{provider}` and `mailbox/oauth/callback` to `web.php`.
    - [x] **Controller**: Implement `MailboxController::oauthConnect` (redirect to provider) and `oauthCallback` (exchange code for token).
    - [x] **Mail Drivers**: Update `SmtpService` and `ImapService` to support `XOAUTH2` authentication using stored tokens.

#### 1.3 Connection Diagnostics
*   **Context**: Admins need to debug email connection issues.
*   **Status**: **Completed**.
*   **Tasks**:
    - [x] **Send Test Email**: Implement `MailboxController::sendTestEmail` to verify SMTP settings with a real email.
    - [x] **Verbose Logging**: Ensure connection errors provide detailed feedback (SSL errors, auth failures) in the UI.

### Phase 2: Agent Productivity (Medium Priority)

#### 2.1 Collision Detection
*   **Context**: Prevent two agents from working on the same ticket. Legacy used Cache + AJAX polling.
*   **Status**: Backend Implemented. Frontend verification needed.
*   **Tasks**:
    - [x] **Backend**: Create `CollisionController::viewing($id)` to update a Cache key (`conversation_viewing_{id}_{user_id}`).
    - [x] **API**: Create endpoint to retrieve current viewers of a conversation.
    - [x] **Frontend**: Implemented in `resources/js/conversation.js` using Laravel Echo (`initCollisionDetection`).

#### 2.2 Drafts System
*   **Context**: Auto-save replies to prevent data loss.
*   **Status**: Backend exists (`DraftController`), Frontend integration needed.
*   **Tasks**:
    - [x] **Frontend Integration**: Implemented in `resources/js/conversation.js` (`initAutoSave`).
    - [x] **Restoration**: Ensure `ConversationController::show` injects the saved draft into the editor if one exists.
    - [x] **Discard**: Implement "Discard Draft" button. (Handled by DraftController::discard)

#### 2.3 Advanced Search
*   **Context**: Agents need to find tickets by customer phone, custom fields, etc.
*   **Status**: Implemented.
*   **Tasks**:
    - [x] **Phone Search**: Update `ConversationController::search` and `CustomerController::search` to normalize and search phone numbers.
    - [x] **Scoping**: Ensure search results respect `User::mailboxes()` permissions.

### Phase 3: System Health & Maintenance (Medium Priority)

#### 3.1 Process Monitoring
*   **Context**: Admins need to know if `queue:work` or cron jobs are dead.
*   **Status**: Implemented.
*   **Tasks**:
    - [x] **Heartbeat**: Update `Console/Kernel.php` or individual commands to update a Cache timestamp (`last_run_fetch`, `last_run_queue`).
    - [x] **UI Indicator**: In `SystemController::index`, check these timestamps. If > 5 mins old, show "Background jobs not running" warning.

#### 3.2 Failed Job Management
*   **Context**: Emails fail to send. Admins need to retry them.
*   **Status**: Implemented.
*   **Tasks**:
    - [x] **UI**: Create a view to list rows from `failed_jobs` table.
    - [x] **Actions**: Add "Retry" (call `artisan queue:retry`) and "Delete" (call `artisan queue:forget`) buttons.

#### 3.3 Self-Update
*   **Context**: Easy updates for non-technical users.
*   **Status**: **Completed**.
*   **Tasks**:
    - [x] **Updater Logic**: Implement `SystemController::update` to pull latest release from GitHub/Marketplace, replace files, and run migrations.
    - [x] **UI**: Create `resources/views/system/update.blade.php` with a button to trigger the update.
    - [x] **Controller**: Add `performUpdate` method to `SystemController` to call `freescout:update`.
    - [x] **Route**: Add route `POST /system/update` to `web.php`.
    - [x] **Verification**: Verify `freescout:update` command exists.

### Phase 4: Customer Experience (Completed/Verification Needed)

*   **Public Attachments**: [x] Implemented (`PublicAttachmentController`). Verified with `CustomerExperienceTest`.
*   **Tracking Pixel**: [x] Implemented (`TrackingController`). Verified with `CustomerExperienceTest`.
*   **User Invites**: [x] Implemented (`UserController::userSetup`). Verified with `CustomerExperienceTest`.
*   **Undo Send**: [x] Implemented (`ConversationController::undoSend`). Verified with `CustomerExperienceTest`.
*   **Forwarding**: [x] Implemented (`ConversationController::forward`). Verified with `CustomerExperienceTest`.

---

## 3. Technical References

### Key Files
*   **Routes**: `routes/web.php`
*   **Marketplace API**: `app/Misc/WpApi.php`
*   **Mailbox Controller**: `app/Http/Controllers/MailboxController.php`
*   **Conversation Controller**: `app/Http/Controllers/ConversationController.php`
*   **Legacy Controllers**: `archive/app/Http/Controllers/*.php`

### Eventy Hooks (Must Implement)
*   `dashboard.modules`: Inject widgets into Dashboard.
*   `settings.sections`: Inject tabs into Settings.
*   `conversation.view.buttons`: Inject buttons into Conversation view.
*   `mailbox.settings.menu`: Inject tabs into Mailbox settings.

## 4. Verification Strategy

For each implemented feature:
1.  **Unit Test**: Create a test in `tests/Feature/` (e.g., `MarketplaceTest.php`, `OAuthTest.php`).
2.  **Legacy Check**: Compare logic with `archive/` counterpart to ensure no edge cases (like permission checks) were missed.
3.  **UI Verification**: Ensure the feature is accessible and functional in the browser.
