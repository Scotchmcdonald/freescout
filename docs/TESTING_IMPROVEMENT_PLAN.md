# Comprehensive Testing Improvement Plan

## 1. Overview

This document outlines a risk-based testing plan to improve the overall quality, stability, and maintainability of the application. The priorities are derived from an analysis of the PHPUnit code coverage report generated on 2025-11-11.

The primary goal is to address the most critical, complex, and untested areas of the codebase first. Priority is given to targets with a high CRAP (Change Risk Anti-Patterns) index, which is a function of high cyclomatic complexity and low test coverage. This plan is structured to be distributed across a team for parallel execution.

## 2. High-Level Priority Targets

These five targets represent the highest immediate risk to the application's stability and functionality, based on a combination of low test coverage and high operational complexity.

---

### Target 1: `App\Services\ImapService`
*   **Metrics:** **9.08%** Line Coverage
*   **Justification:** This service handles the core functionality of fetching and parsing emails, a primary data ingress point for the application. Its extremely low coverage and inherent complexity create a high risk of data loss, silent failures, and unhandled connection errors.

---

### Target 2: `App\Jobs\SendNotificationToUsers`
*   **Metrics:** **1.06%** Line Coverage
*   **Justification:** This is a critical asynchronous job responsible for user notifications. Its near-zero coverage means any failure (e.g., due to a change in a related model or service) will be silent and difficult to debug, directly impacting user engagement.

---

### Target 3: `App\Console\Commands\ModuleInstall`
*   **Metrics:** **2.00%** Line Coverage
*   **Justification:** As a command that modifies the filesystem and database schema, a bug here could lead to a corrupted or broken application state. Its high-risk nature and lack of tests make it a top priority.

---

### Target 4: `App\Http\Controllers\ConversationController`
*   **Metrics:** **49.21%** Line Coverage (with over 200 untested lines)
*   **Justification:** This is a large, critical controller managing the application's core "conversation" resource. The untested lines likely contain complex authorization checks, state changes, and error-handling logic that are currently unprotected from regression.

---

### Target 5: `App\Jobs\SendAutoReply`
*   **Metrics:** **1.32%** Line Coverage
*   **Justification:** This asynchronous job is a key part of the customer service workflow. Its lack of tests means that a failure would break a core feature and degrade the customer experience without any immediate feedback.

## 3. Enhanced Workload Batches for Parallel Development

To facilitate distribution of this work, the testing tasks have been grouped into the following parallelizable batches. Each batch contains a series of epics and user stories that can be assigned to different developers.

---

### Batch 1: Core Services (Highest Priority)
*   **Objective:** Solidify the application's interaction with external mail services. This is the most critical batch as it protects the primary data ingress/egress paths.
*   **Notes:** This batch requires extensive mocking of external clients (`Webklex\PHPIMAP\Client`, `Illuminate\Mail\Mailer`). The focus is on pure unit tests that do not require a full application boot.

*   **Epic: `App\Services\ImapService` - Full Coverage**
    *   **Story (High Priority):** As a developer, I want to test the IMAP connection logic, so that connection failures and retries are handled gracefully.
        *   *Test Scenarios:* Mock `createClient` and `connect` to throw `ConnectionFailedException`; assert that errors are logged and the service returns a failure state.
    *   **Story (High Priority):** As a developer, I want to test the email processing logic for various email structures.
        *   *Test Scenarios:* Write `processMessage` tests for plain text emails, HTML emails, and multipart emails.
    *   **Story (High Priority):** As a developer, I want to test the charset/encoding error handling for Microsoft mailboxes.
        *   *Test Scenarios:* Mock the IMAP client to first throw a `charset is not supported` exception, then succeed on the second call. Assert that the retry logic is triggered.
    *   **Story (Medium Priority):** As a developer, I want to test the `@fwd` command parsing logic.
        *   *Test Scenarios:* Write unit tests for `getOriginalSenderFromFwd` with various forwarded email body formats to ensure it reliably extracts the original sender.
    *   **Story (High Priority):** As a developer, I want to test the BCC and duplicate message detection logic.
        *   *Test Scenarios:* Create a test where a message with the same `Message-ID` is fetched for two different mailboxes. Assert that the second fetch is identified as a BCC copy and creates a new conversation with an artificial `Message-ID`.
    *   **Story (High Priority):** As a developer, I want to test attachment handling and inline image processing.
        *   *Test Scenarios:* Process a raw email with both a regular attachment and an inline image (`cid:`). Assert that two `Attachment` models are created, one `embedded` and one not. Assert the thread body's `cid:` is replaced with a storage URL.

*   **Epic: `App\Services\SmtpService` - Connection & Configuration**
    *   **Story (Medium Priority):** As a developer, I want to test the SMTP connection logic.
        *   *Test Scenarios:* Mock the mailer to simulate success and failure scenarios for `testConnection`.

---

### Batch 2: Asynchronous Jobs & Events (High Priority)
*   **Objective:** Ensure all background tasks are reliable and their failures are handled gracefully.
*   **Notes:** This work involves writing feature tests that use Laravel's `Queue::fake()` helper.

*   **Epic: Job Reliability**
    *   **Story (High Priority):** As a developer, I want to test the `SendNotificationToUsers` job.
        *   *Test Scenarios:* Test successful dispatch; test user filtering based on notification settings; test job failure and retry logic.
    *   **Story (High Priority):** As a developer, I want to test the `SendAutoReply` job.
        *   *Test Scenarios:* Test conditional dispatch based on mailbox settings; test email content generation; test duplicate reply prevention.
    *   **Story (Medium Priority):** As a developer, I want to add tests for the `SendAlert` and `SendEmailReplyError` jobs.
        *   *Test Scenarios:* Test successful dispatch and basic execution for both jobs.

---

### Batch 3: Console Commands & System Integrity (Medium Priority)
*   **Objective:** Protect the application from data or file corruption caused by faulty command-line operations.
*   **Notes:** This involves writing feature tests that use `Artisan::call()` and asserting command output and side-effects.

*   **Epic: Command-Line Interface (CLI) Hardening**
    *   **Story (High Priority):** As a developer, I want to test the `module:install` and `module:update` commands.
        *   *Test Scenarios:* Test successful installation/update; test with a malformed module; test for clean failure if a dependency is missing or a directory is not writable.
    *   **Story (Low Priority):** As a developer, I want to add tests for the `cache:clear` and `users:logout` commands.
        *   *Test Scenarios:* Test successful execution and assert the expected outcome (e.g., cache is empty, user tokens are invalidated).

---

### Batch 4: Controller & Policy Logic (Medium Priority)
*   **Objective:** Harden the most critical API endpoints and user flows against authorization breaches and invalid data.
*   **Notes:** This batch focuses on writing feature tests for the "unhappy paths."

*   **Epic: Endpoint Security & State Management**
    *   **Story (High Priority):** As a developer, I want to test the authorization logic in `ConversationController` and `ConversationPolicy`.
        *   *Test Scenarios:* Test that a user *without* permission receives a 403 when trying to update, delete, or view a conversation in an unassigned mailbox.
    *   **Story (Medium Priority):** As a developer, I want to test the validation logic in `ConversationController`.
        *   *Test Scenarios:* Test the `store` and `reply` methods with invalid data (e.g., missing `subject`, invalid `customer_id`) and assert a 422 response with the correct error structure.
    *   **Story (Medium Priority):** As a developer, I want to test the state management logic in `ConversationController`.
        *   *Test Scenarios:* Test that replying to a "Closed" conversation automatically re-opens it. Test that assigning a user and changing status in one request works correctly.
    *   **Story (Low Priority):** As a developer, I want to add authorization and validation tests for `SettingsController`.
        *   *Test Scenarios:* Test that non-admins receive a 403; test that submitting invalid data (e.g., bad email driver) returns a 422.

---

### Batch 5: Model & Helper Logic (Low Priority)
*   **Objective:** Increase baseline coverage and test critical model-level business logic.
*   **Notes:** This work primarily involves unit tests.

*   **Epic: Business Logic in Models & Helpers**
    *   **Story (Medium Priority):** As a developer, I want to test the static `create()` method on the `Customer` model.
        *   *Test Scenarios:* Test creating a new customer; test finding an existing customer by email; test handling of invalid email formats.
    *   **Story (Low Priority):** As a developer, I want to test the utility functions in `App\Misc\MailHelper`.
        *   *Test Scenarios:* Write simple unit tests for each public static method, asserting their transformations are correct (e.g., `generateMessageId`).
