# RPA System Testing Plan

This document outlines a comprehensive set of scenarios for Robotic Process Automation (RPA) testing of the FreeScout application. These tests are designed to simulate real-world user workflows from end-to-end, ensuring system stability, data integrity, and a seamless user experience.

**Note:** Each scenario assumes the RPA bot starts from a logged-out state unless specified as a prerequisite.

## 1. Admin User Scenarios

### 1.1. Full Conversation Lifecycle (Admin)

**Objective:** Verify that an admin can manage a customer conversation from creation to resolution, including internal notes and replies.

**Persona:** Admin User

**Steps:**

1.  **Login:**
    *   **Action:** Navigate to the login page (`/login`).
    *   **Action:** Find the element with `name="email"` and type the admin's email address.
    *   **Action:** Find the element with `name="password"` and type the admin's password.
    *   **Action:** Find and click the button with the text "Login".
    *   **Validation (UI):** Assert that the current URL is the dashboard (`/`). Assert that an element containing "Logged in as [Admin Name]" is visible.

2.  **Create a New Conversation:**
    *   **Action:** Navigate to a specific mailbox (e.g., `/mailboxes/1/active`).
    *   **Action:** Find and click the button with the text "New Conversation".
    *   **Action:** In the "To" field, type a customer's email address (e.g., `customer@example.com`).
    *   **Action:** In the "Subject" field, type "RPA Test: Admin Inquiry".
    *   **Action:** In the rich text editor for the body, type: "This is a test message initiated by an admin."
    *   **Action:** Find and click the button with the text "Send".
    *   **Validation (UI):** Assert that a success notification element with the text "Conversation created" appears. Assert that the new conversation's subject "RPA Test: Admin Inquiry" is visible in the conversation list.
    *   **Validation (DB):** Execute a query: `SELECT id, subject FROM conversations WHERE subject = 'RPA Test: Admin Inquiry' ORDER BY id DESC LIMIT 1;`. Assert that one row is returned. Store the `id` for subsequent steps.

3.  **Add an Internal Note:**
    *   **Action:** Open the newly created conversation by clicking on its subject in the list.
    *   **Action:** Find and click the tab or button with the text "Note".
    *   **Action:** In the note's text area, type: "RPA: This is an internal note for the team."
    *   **Action:** Find and click the button with the text "Add Note".
    *   **Validation (UI):** Assert that an element with the text "RPA: This is an internal note for the team." is visible in the conversation timeline. Assert this element is styled as a note (e.g., has a specific CSS class like `thread-note`).
    *   **Validation (DB):** Execute a query: `SELECT COUNT(*) FROM threads WHERE conversation_id = [stored_id] AND body LIKE '%internal note%' AND type = 'note';`. Assert the count is 1.

4.  **Reply to the Conversation:**
    *   **Action:** Find and click the tab or button with the text "Reply".
    *   **Action:** In the reply's text area, type: "RPA: This is a public reply to the customer."
    *   **Action:** Find and click the button with the text "Send Reply".
    *   **Validation (UI):** Assert that an element with the text "RPA: This is a public reply to the customer." is visible in the conversation timeline.
    *   **Validation (DB):** Execute a query: `SELECT COUNT(*) FROM threads WHERE conversation_id = [stored_id] AND body LIKE '%public reply%' AND type = 'reply';`. Assert the count is 1.

5.  **Close the Conversation:**
    *   **Action:** Find and click the button with the text "Close".
    *   **Validation (UI):** Assert that the conversation status indicator changes to "Closed". Navigate to the "Closed" folder and assert the conversation subject is present.
    *   **Validation (DB):** Execute a query: `SELECT status FROM conversations WHERE id = [stored_id];`. Assert the `status` is 'closed'.

### 1.2. User Management Lifecycle

**Objective:** Ensure an admin can create a new user, assign them to a mailbox, and subsequently delete them.

**Persona:** Admin User

**Steps:**

1.  **Login:** (As per 1.1)
2.  **Navigate to Users:**
    *   **Action:** Click the "Manage" menu, then click the "Users" link.
    *   **Action:** Find and click the "New User" button.
3.  **Create New User:**
    *   **Action:** Fill in the "First Name" (`rpa_user_firstname`), "Last Name" (`rpa_user_lastname`), and "Email" (`rpa.user@example.com`).
    *   **Action:** Select a role (e.g., "Agent").
    *   **Action:** Click "Create User".
    *   **Validation (UI):** Assert that a success message "User created" is displayed. Assert the new user's email appears in the user list.
    *   **Validation (DB):** Execute a query: `SELECT id FROM users WHERE email = 'rpa.user@example.com';`. Assert one row is returned. Store the `id`.
4.  **Assign to Mailbox:**
    *   **Action:** On the user list, find the row for `rpa.user@example.com` and click "Edit".
    *   **Action:** In the "Mailboxes" section, check the box for a specific mailbox (e.g., "Support").
    *   **Action:** Click "Save Changes".
    *   **Validation (DB):** Execute a query: `SELECT COUNT(*) FROM mailbox_user WHERE user_id = [stored_id];`. Assert the count is 1.
5.  **Delete User:**
    *   **Action:** On the user list, find the row for `rpa.user@example.com`.
    *   **Action:** Click the "Delete" button or icon for that user.
    *   **Action:** Confirm the deletion in the confirmation dialog.
    *   **Validation (UI):** Assert the user `rpa.user@example.com` is no longer in the user list.
    *   **Validation (DB):** Execute a query: `SELECT COUNT(*) FROM users WHERE id = [stored_id];`. Assert the count is 0.

### 1.3. Workflow (Rule) Creation and Execution

**Objective:** Verify an admin can create a workflow rule that automatically tags and assigns a new conversation based on its content.

**Persona:** Admin User

**Steps:**

1.  **Login:** (As per 1.1)
2.  **Navigate to Workflows:**
    *   **Action:** Click the "Manage" menu, then click the "Workflows" link.
    *   **Action:** Find and click the "New Workflow" button.
3.  **Define Workflow:**
    *   **Action:** For "Name", enter "RPA Urgent Triage".
    *   **Action:** In the "Conditions" section, select "Subject" contains "Urgent Support".
    *   **Action:** In the "Actions" section, add the action "Add Tag" and select an existing tag (e.g., "Urgent").
    *   **Action:** Add a second action "Assign to User" and select a specific user (e.g., the admin user).
    *   **Action:** Click "Save Workflow".
    *   **Validation (UI):** Assert the new workflow appears in the list and is enabled.
    *   **Validation (DB):** Execute a query: `SELECT id FROM workflows WHERE name = 'RPA Urgent Triage' AND is_active = 1;`. Assert one row is returned.
4.  **Trigger Workflow:**
    *   **Action:** Create a new conversation (as per 1.1, step 2) with the subject "Urgent Support: System Down".
    *   **Validation (DB):** Get the `id` of the new conversation.
5.  **Validate Workflow Execution:**
    *   **Action:** Navigate to the mailbox and open the "Urgent Support: System Down" conversation.
    *   **Validation (UI):** Assert that the "Urgent" tag is visible on the conversation. Assert that the "Assigned to" field shows the user selected in the workflow.
    *   **Validation (DB):** Execute a query: `SELECT user_id FROM conversations WHERE id = [new_conversation_id];`. Assert the `user_id` matches the user from the workflow. Execute a second query: `SELECT COUNT(*) FROM conversation_tag WHERE conversation_id = [new_conversation_id] AND tag_id = (SELECT id FROM tags WHERE name = 'Urgent');`. Assert the count is 1.

## 3. Advanced Scenarios

### 3.1. Merging Conversations

**Objective:** Verify a user can merge two separate conversations from the same customer into one.

**Persona:** Agent User

**Prerequisites:**
*   Two distinct conversations from the same customer email address exist.
*   The agent is logged in.

**Steps:**

1.  **Identify Conversations:**
    *   **Action:** Navigate to the customer's profile page (as per 2.2) to see both conversations listed.
    *   **Validation (DB):** Get the `id`s for both conversations (e.g., `conv_id_1`, `conv_id_2`).
2.  **Initiate Merge:**
    *   **Action:** Open the first conversation (`conv_id_1`).
    *   **Action:** Find and click the "More" or "..." menu, then select "Merge".
3.  **Select Conversation to Merge:**
    *   **Action:** In the merge dialog, search for the subject of the second conversation (`conv_id_2`).
    *   **Action:** Select the second conversation from the search results.
    *   **Action:** Click the "Merge Conversations" button.
    *   **Action:** Confirm the merge in the confirmation dialog.
4.  **Validate Merge:**
    *   **Validation (UI):** Assert that the current conversation view (for `conv_id_1`) now contains the thread messages from `conv_id_2`. The timeline should show a "Conversation Merged" event.
    *   **Validation (DB):** Execute a query: `SELECT conversation_id FROM threads WHERE conversation_id = [conv_id_2];`. Assert that 0 rows are returned (all threads should now belong to `conv_id_1`). Execute a second query: `SELECT COUNT(*) FROM threads WHERE conversation_id = [conv_id_1];`. Assert the count equals the original number of threads from both conversations.
    *   **Validation (DB):** Execute a query: `SELECT status FROM conversations WHERE id = [conv_id_2];`. Assert the status is 'merged' or that the record has been deleted (depending on application logic).

