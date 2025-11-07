# Comprehensive Test Plan: FreeScout Modernization

Here is the full test plan, broken into parallel-work batches, for the modernization of the FreeScout application. This plan is designed to ensure high test coverage and verify that the modernized application maintains functional parity with the archived Laravel 5 version.

---

## BATCH 1: User Management & Authentication

This batch focuses on user accounts, roles, permissions, and the entire authentication/authorization lifecycle.

### 1.1. Unit Tests
*   [ ] **Test:** `User` model: Verify `isAdmin` and other role-related accessors/scopes.
    *   **File:** `app/Models/User.php`
*   [ ] **Test:** `User` model: Test relationships (`mailboxes`, `conversations`).
    *   **File:** `app/Models/User.php`
*   [ ] **Test:** `ProfileController`: Test validation rules within `update` method for profile changes.
    *   **File:** `app/Http/Controllers/ProfileController.php`
*   [ ] **Test:** `UserController`: Test validation logic for creating and updating users.
    *   **File:** `app/Http/Controllers/UserController.php`

### 1.2. Feature Tests
*   [ ] **Test:** Guest can view the login page.
    *   **Route:** `GET /login`
    *   **File:** `app/Http/Controllers/Auth/LoginController.php` (or equivalent in Fortify/Breeze if used)
    *   **Assert:** Returns `200` status.
*   [ ] **Test:** User can successfully log in with valid credentials.
    *   **Route:** `POST /login`
    *   **File:** `app/Http/Controllers/Auth/LoginController.php`
    *   **Assert:** User is redirected to the dashboard (`/`), session is created.
*   [ ] **Test:** Authenticated user can log out.
    *   **Route:** `POST /logout`
    *   **File:** `app/Http/Controllers/Auth/LoginController.php`
    *   **Assert:** Session is destroyed, user is redirected to home.
*   [ ] **Test:** Authenticated user can view their profile page.
    *   **Route:** `GET /profile`
    *   **File:** `app/Http/Controllers/ProfileController.php`
    *   **Assert:** Returns `200` status with user data.
*   [ ] **Test:** Authenticated user can update their profile information (name, email).
    *   **Route:** `PUT /profile`
    *   **File:** `app/Http/Controllers/ProfileController.php`
    *   **Assert:** Database is updated with new values, returns successful response.
*   [ ] **Test:** Admin user can create a new user.
    *   **Route:** `POST /users`
    *   **File:** `app/Http/Controllers/UserController.php`
    *   **Assert:** New user is created in the database, returns `201` or redirect.
*   [ ] **Test:** Admin user can update an existing user.
    *   **Route:** `PUT /users/{user}`
    *   **File:** `app/Http/Controllers/UserController.php`
    *   **Assert:** User data is updated in the database.

### 1.3. Edge Case & Sad Path Tests
*   [ ] **Test:** Cannot log in with invalid credentials.
    *   **Route:** `POST /login`
    *   **Assert:** Returns validation error (`422`) or redirects back with error message.
*   [ ] **Test:** Unauthenticated user cannot access protected routes (e.g., `/profile`, `/conversations`).
    *   **Route:** `GET /profile`
    *   **Assert:** Redirects to login (`302`).
*   [ ] **Test:** Non-admin user cannot access user management routes (e.g., `GET /users`, `POST /users`).
    *   **Route:** `GET /users`
    *   **Assert:** Returns authorization error (`403`).
*   [ ] **Test:** Cannot update profile with invalid data (e.g., invalid email format).
    *   **Route:** `PUT /profile`
    *   **Assert:** Returns validation error (`422`).

### 1.4. Regression Tests
*   [ ] **Test:** Verify user role logic matches L5 implementation.
    *   **Modern File:** `app/Models/User.php`
    *   **Archived File:** `archive/app/User.php`
    *   **Assert:** A user with a specific role in the L5 structure has the equivalent permissions and role identification in the new structure.
*   [ ] **Test:** Verify password reset token logic and email notification remains consistent.
    *   **Modern File:** `app/Http/Controllers/Auth/ForgotPasswordController.php` (or equivalent)
    *   **Archived File:** `archive/app/Http/Controllers/Auth/ForgotPasswordController.php`
    *   **Assert:** The password reset flow (request, email, reset) functions as it did previously.

---

## BATCH 2: Mailbox Management

This batch covers the creation, configuration, and management of mailboxes and their associated folders.

### 2.1. Unit Tests
*   [ ] **Test:** `Mailbox` model: Test relationships (`users`, `folders`, `conversations`).
    *   **File:** `app/Models/Mailbox.php`
*   [ ] **Test:** `Mailbox` model: Test any custom scopes (e.g., `forUser`).
    *   **File:** `app/Models/Mailbox.php`
*   [ ] **Test:** `Folder` model: Test relationships and hierarchy logic.
    *   **File:** `app/Models/Folder.php`
*   [ ] **Test:** `MailboxController`: Test validation logic for creating and updating mailboxes.
    *   **File:** `app/Http/Controllers/MailboxController.php`

### 2.2. Feature Tests
*   [ ] **Test:** User can view the list of mailboxes they have access to.
    *   **Route:** `GET /mailboxes`
    *   **File:** `app/Http/Controllers/MailboxController.php`
    *   **Assert:** Returns `200` with a list of mailboxes.
*   [ ] **Test:** User can create a new mailbox with valid settings.
    *   **Route:** `POST /mailboxes`
    *   **File:** `app/Http/Controllers/MailboxController.php`
    *   **Assert:** New mailbox is created in the database, returns `201` or redirect.
*   [ ] **Test:** User can update a mailbox's settings.
    *   **Route:** `PUT /mailboxes/{mailbox}`
    *   **File:** `app/Http/Controllers/MailboxController.php`
    *   **Assert:** Mailbox settings are updated in the database.
*   [ ] **Test:** User can delete a mailbox.
    *   **Route:** `DELETE /mailboxes/{mailbox}`
    *   **File:** `app/Http/Controllers/MailboxController.php`
    *   **Assert:** Mailbox is soft-deleted or hard-deleted from the database.

### 2.3. Edge Case & Sad Path Tests
*   [ ] **Test:** Cannot create a mailbox with invalid connection settings (IMAP/SMTP).
    *   **Route:** `POST /mailboxes`
    *   **Assert:** Returns `422` validation error.
*   [ ] **Test:** User cannot view or access a mailbox they do not have permission for.
    *   **Route:** `GET /mailboxes/{mailbox}`
    *   **Assert:** Returns `404` or `403`.
*   [ ] **Test:** User cannot update a mailbox they do not have permission for.
    *   **Route:** `PUT /mailboxes/{mailbox}`
    *   **Assert:** Returns `403`.

### 2.4. Regression Tests
*   [ ] **Test:** Verify mailbox permission logic is consistent with the L5 version.
    *   **Modern File:** `app/Models/Mailbox.php`, `app/Policies/MailboxPolicy.php` (if exists)
    *   **Archived File:** `archive/app/Mailbox.php`, `archive/app/MailboxUser.php`
    *   **Assert:** The logic determining user access to mailboxes remains unchanged.
*   [ ] **Test:** Verify that folder structures and relationships are migrated correctly.
    *   **Modern File:** `app/Models/Folder.php`
    *   **Archived File:** `archive/app/Folder.php`, `archive/app/ConversationFolder.php`
    *   **Assert:** The association between conversations and folders works as it did in the L5 version.

---

## BATCH 3: Conversations & Threads

This batch focuses on the core functionality of the help desk: managing conversations, threads, replies, and attachments.

### 3.1. Unit Tests
*   [ ] **Test:** `Conversation` model: Test relationships (`customer`, `mailbox`, `threads`, `user`).
    *   **File:** `app/Models/Conversation.php`
*   [ ] **Test:** `Conversation` model: Test scopes (e.g., `assignedTo`, `inStatus`).
    *   **File:** `app/Models/Conversation.php`
*   [ ] **Test:** `Thread` model: Test relationships and accessors.
    *   **File:** `app/Models/Thread.php`
*   [ ] **Test:** `Attachment` model: Test file path/URL accessors.
    *   **File:** `app/Models/Attachment.php`

### 3.2. Feature Tests
*   [ ] **Test:** User can view a list of conversations in a mailbox.
    *   **Route:** `GET /conversations` (or `GET /mailboxes/{mailbox}/conversations`)
    *   **File:** `app/Http/Controllers/ConversationController.php`
    *   **Assert:** Returns `200` with a paginated list of conversations.
*   [ ] **Test:** User can view a single conversation and its threads.
    *   **Route:** `GET /conversations/{conversation}`
    *   **File:** `app/Http/Controllers/ConversationController.php`
    *   **Assert:** Returns `200` with conversation details and associated threads.
*   [ ] **Test:** User can reply to a conversation.
    *   **Route:** `POST /conversations/{conversation}/reply`
    *   **File:** `app/Http/Controllers/ConversationController.php`
    *   **Assert:** A new `Thread` is created, an email is potentially queued, and the conversation status is updated.
*   [ ] **Test:** User can add a note to a conversation.
    *   **Route:** `POST /conversations/{conversation}/note`
    *   **File:** `app/Http/Controllers/ConversationController.php`
    *   **Assert:** A new `Thread` (of type 'note') is created.
*   [ ] **Test:** User can upload an attachment with a reply.
    *   **Route:** `POST /conversations/{conversation}/reply`
    *   **File:** `app/Http/Controllers/ConversationController.php`
    *   **Assert:** An `Attachment` record is created and associated with the new `Thread`, and the file is stored.

### 3.3. Edge Case & Sad Path Tests
*   [ ] **Test:** Cannot access a conversation in a mailbox the user does not have access to.
    *   **Route:** `GET /conversations/{conversation}`
    *   **Assert:** Returns `404` or `403`.
*   [ ] **Test:** Replying to a closed conversation re-opens it.
    *   **Route:** `POST /conversations/{conversation}/reply`
    *   **Assert:** The conversation status is updated from 'closed' to 'active'.
*   [ ] **Test:** Cannot upload an attachment larger than the configured limit.
    *   **Route:** `POST /conversations/{conversation}/reply`
    *   **Assert:** Returns `422` validation error.

### 3.4. Regression Tests
*   [ ] **Test:** Verify conversation status logic (e.g., new, active, pending, closed) matches L5 behavior.
    *   **Modern File:** `app/Models/Conversation.php`
    *   **Archived File:** `archive/app/Conversation.php`
    *   **Assert:** The state machine for conversation statuses operates identically.
*   [ ] **Test:** Verify that email parsing and thread creation from incoming emails is consistent.
    *   **Modern Logic:** Inbound email processing jobs/listeners (e.g., `app/Jobs/FetchEmails.php`)
    *   **Archived Logic:** `archive/app/Jobs/FetchEmails.php` (or equivalent)
    *   **Assert:** An email with a specific `In-Reply-To` header is correctly appended to the existing conversation thread.

---

## BATCH 4: Customer Management

This batch ensures that customer data is managed correctly and is properly associated with conversations.

### 4.1. Unit Tests
*   [ ] **Test:** `Customer` model: Test relationships (`conversations`).
    *   **File:** `app/Models/Customer.php`
*   [ ] **Test:** `Customer` model: Test full name accessor and any other formatters.
    *   **File:** `app/Models/Customer.php`

### 4.2. Feature Tests
*   [ ] **Test:** User can view a list of customers.
    *   **Route:** `GET /customers`
    *   **File:** `app/Http/Controllers/CustomerController.php`
    *   **Assert:** Returns `200` with a paginated list of customers.
*   [ ] **Test:** User can create a new customer.
    *   **Route:** `POST /customers`
    *   **File:** `app/Http/Controllers/CustomerController.php`
    *   **Assert:** New customer is created in the database, returns `201` or redirect.
*   [ ] **Test:** User can view a single customer and their conversation history.
    *   **Route:** `GET /customers/{customer}`
    *   **File:** `app/Http/Controllers/CustomerController.php`
    *   **Assert:** Returns `200` with customer details and a list of their conversations.
*   [ ] **Test:** User can update a customer's details.
    *   **Route:** `PUT /customers/{customer}`
    *   **File:** `app/Http/Controllers/CustomerController.php`
    *   **Assert:** Customer data is updated in the database.

### 4.3. Edge Case & Sad Path Tests
*   [ ] **Test:** Cannot create a customer with a duplicate email address if it's meant to be unique.
    *   **Route:** `POST /customers`
    *   **Assert:** Returns `422` validation error.
*   [ ] **Test:** Merging two customers correctly re-assigns all conversations.
    *   **Route:** `POST /customers/merge` (or equivalent action)
    *   **File:** `app/Http/Controllers/CustomerController.php`
    *   **Assert:** The source customer's conversations are now linked to the destination customer, and the source customer is deleted.

### 4.4. Regression Tests
*   [ ] **Test:** Verify that customer identification from incoming emails matches L5 logic.
    *   **Modern Logic:** Inbound email processing logic.
    *   **Archived Logic:** `archive/app/Libs/EmailParser.php` (or equivalent helper/library)
    *   **Assert:** An incoming email from a new address creates a new customer, while an email from an existing address is correctly associated.

---

## BATCH 5: System & Settings

This batch covers application-wide settings, system health checks, and administrative configurations.

### 5.1. Unit Tests
*   [ ] **Test:** `Option` model: Test key/value storage and retrieval logic.
    *   **File:** `app/Models/Option.php`
*   [ ] **Test:** `SettingsController`: Test validation for various setting types (email, integrations, etc.).
    *   **File:** `app/Http/Controllers/SettingsController.php`

### 5.2. Feature Tests
*   [ ] **Test:** Admin can view the main settings page.
    *   **Route:** `GET /settings/general`
    *   **File:** `app/Http/Controllers/SettingsController.php`
    *   **Assert:** Returns `200` with current settings values.
*   [ ] **Test:** Admin can update a general setting (e.g., application name).
    *   **Route:** `POST /settings/general`
    *   **File:** `app/Http/Controllers/SettingsController.php`
    *   **Assert:** The corresponding value in the `options` table is updated.
*   [ ] **Test:** Admin can view the system status page.
    *   **Route:** `GET /system/status`
    *   **File:** `app/Http/Controllers/SystemController.php`
    *   **Assert:** Returns `200` with system information (PHP version, queue status, etc.).

### 5.3. Edge Case & Sad Path Tests
*   [ ] **Test:** Non-admin user cannot access any settings routes.
    *   **Route:** `GET /settings/general`
    *   **Assert:** Returns `403`.
*   [ ] **Test:** Submitting invalid data to a setting (e.g., a string for a numeric-only field) fails validation.
    *   **Route:** `POST /settings/outgoing-email`
    *   **Assert:** Returns `422` validation error.

### 5.4. Regression Tests
*   [ ] **Test:** Verify that settings are retrieved with the same defaults and values as the L5 application.
    *   **Modern Logic:** `config/app.php`, `app/Models/Option.php`
    *   **Archived Logic:** `archive/config/app.php`, `archive/app/Option.php`
    *   **Assert:** The helper function `option('setting_key')` returns the same value for a given database state.
