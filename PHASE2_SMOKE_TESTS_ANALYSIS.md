# Phase 2: Smoke Tests Identified for Enhancement

This document identifies Feature tests in the FreeScout Laravel project that perform shallow "happy path" assertions without verifying actual outcomes or functionality. These tests only check status codes, redirects, or view names without asserting the actual behavior or data changes.

## Definition of Smoke Tests

Smoke tests are characterized by:
- Only asserting `$response->assertOk()` or `$response->assertStatus(200)`
- Only asserting `$response->assertRedirect()` without verifying data changes
- Only asserting `$response->assertViewIs()` without checking view data
- Not verifying database changes with `assertDatabaseHas()` or `assertDatabaseMissing()`
- Not verifying actual content with `assertSee()` or `assertDontSee()`
- Not verifying authentication state changes

---

## Smoke Tests Identified

### 1. tests/Feature/ExampleTest.php

#### `test_the_application_returns_a_successful_response()`
**Current Implementation:**
```php
public function test_the_application_returns_a_successful_response(): void
{
    $response = $this->get('/');
    $response->assertRedirect('/dashboard');
}
```

**Issues:**
- Only checks redirect status, doesn't verify if dashboard is accessible
- Doesn't check authentication requirements
- Doesn't verify what happens for authenticated vs unauthenticated users

---

### 2. tests/Feature/ProfileTest.php

#### `test_profile_page_is_displayed()`
**Current Implementation:**
```php
public function test_profile_page_is_displayed(): void
{
    $user = User::factory()->create();
    
    $response = $this
        ->actingAs($user)
        ->get('/profile');
    
    $response->assertOk();
}
```

**Issues:**
- Only checks 200 status code
- Doesn't verify that user's actual profile data is displayed
- Doesn't check for presence of profile form fields
- Doesn't verify that the correct user's data is shown

---

### 3. tests/Feature/Auth/RegistrationTest.php

#### `test_registration_screen_can_be_rendered()`
**Current Implementation:**
```php
public function test_registration_screen_can_be_rendered(): void
{
    $response = $this->get('/register');
    $response->assertStatus(200);
}
```

**Issues:**
- Only checks 200 status code
- Doesn't verify registration form fields are present
- Doesn't check for CSRF token
- Doesn't verify form field names

#### `test_new_users_can_register()`
**Current Implementation:**
```php
public function test_new_users_can_register(): void
{
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);
    
    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
}
```

**Issues:**
- Doesn't verify user actually exists in database
- Doesn't check that user data was correctly saved
- Doesn't verify email or name fields were stored properly
- Could add `assertDatabaseHas('users', ['email' => 'test@example.com'])`

---

### 4. tests/Feature/Auth/AuthenticationTest.php

#### `test_login_screen_can_be_rendered()`
**Current Implementation:**
```php
public function test_login_screen_can_be_rendered(): void
{
    $response = $this->get('/login');
    $response->assertStatus(200);
}
```

**Issues:**
- Only checks 200 status code
- Doesn't verify login form elements are present
- Doesn't check for email/password fields
- Doesn't verify CSRF protection

---

### 5. tests/Feature/Auth/EmailVerificationTest.php

#### `test_email_verification_screen_can_be_rendered()`
**Current Implementation:**
```php
public function test_email_verification_screen_can_be_rendered(): void
{
    $user = User::factory()->unverified()->create();
    
    $response = $this->actingAs($user)->get('/verify-email');
    
    $response->assertStatus(200);
}
```

**Issues:**
- Only checks 200 status code
- Doesn't verify verification notice is displayed
- Doesn't check for resend verification link
- Doesn't verify user email is shown

---

### 6. tests/Feature/Auth/PasswordResetTest.php

#### `test_reset_password_link_screen_can_be_rendered()`
**Current Implementation:**
```php
public function test_reset_password_link_screen_can_be_rendered(): void
{
    $response = $this->get('/forgot-password');
    $response->assertStatus(200);
}
```

**Issues:**
- Only checks 200 status code
- Doesn't verify password reset form is present
- Doesn't check for email input field
- Doesn't verify instructions are displayed

#### `test_reset_password_screen_can_be_rendered()`
**Current Implementation:**
```php
public function test_reset_password_screen_can_be_rendered(): void
{
    Notification::fake();
    $user = User::factory()->create();
    $this->post('/forgot-password', ['email' => $user->email]);
    
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);
        $response->assertStatus(200);
        return true;
    });
}
```

**Issues:**
- Only checks 200 status code
- Doesn't verify reset password form fields
- Doesn't check for password and confirmation fields
- Doesn't verify token is present in form

---

### 7. tests/Feature/MailboxAutoReplyTest.php

#### `test_admin_can_view_auto_reply_settings_page()`
**Current Implementation:**
```php
public function test_admin_can_view_auto_reply_settings_page(): void
{
    $this->actingAs($this->admin);
    
    $response = $this->get(route('mailboxes.auto_reply', $this->mailbox));
    
    $response->assertStatus(200);
    $response->assertSee('Auto Reply');
}
```

**Issues:**
- Only checks status and one text string
- Doesn't verify form fields are present (subject, message inputs)
- Doesn't check for current auto-reply settings being displayed
- Doesn't verify mailbox name is shown

---

### 8. tests/Feature/SettingsTest.php

#### `test_admin_can_view_email_settings_page()`
**Current Implementation:**
```php
public function test_admin_can_view_email_settings_page(): void
{
    Option::create(['name' => 'mail_from_address', 'value' => 'test@example.com']);
    
    $response = $this->actingAs($this->admin)->get(route('settings.email'));
    
    $response->assertOk();
}
```

**Issues:**
- Only checks OK status
- Creates an option but doesn't verify it's displayed
- Doesn't check for form fields (mail_driver, mail_host, etc.)
- Doesn't verify the created option value is visible

---

### 9. tests/Feature/MailboxViewTest.php

#### `test_admin_can_view_mailbox_settings_page()`
**Current Implementation:**
```php
public function test_admin_can_view_mailbox_settings_page(): void
{
    $this->actingAs($this->admin);
    
    $response = $this->get(route('mailboxes.settings', $this->mailbox));
    
    $response->assertStatus(200);
}
```

**Issues:**
- Only checks 200 status
- Doesn't verify settings form is present
- Doesn't check for mailbox configuration fields
- Doesn't verify mailbox name or details are displayed

---

### 10. tests/Feature/CustomerManagementTest.php

#### `test_user_can_access_customer_edit_page()`
**Current Implementation:**
```php
public function test_user_can_access_customer_edit_page(): void
{
    $customer = Customer::factory()->create([
        'first_name' => 'Edit',
        'last_name' => 'Test',
    ]);
    
    $response = $this->actingAs($this->user)->get("/customer/{$customer->id}/edit");
    
    $response->assertStatus(200);
    $response->assertSee('Edit');
    $response->assertSee('Test');
}
```

**Issues:**
- Only checks status and customer name
- Doesn't verify edit form fields are present
- Doesn't check for save/cancel buttons
- Doesn't verify all customer fields are shown (email, company, etc.)

---

### 11. tests/Feature/Auth/PasswordConfirmationTest.php

#### `test_confirm_password_screen_can_be_rendered()`
**Current Implementation:**
```php
public function test_confirm_password_screen_can_be_rendered(): void
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->get('/confirm-password');
    
    $response->assertStatus(200);
}
```

**Issues:**
- Only checks 200 status code
- Doesn't verify password confirmation form is present
- Doesn't check for password input field
- Doesn't verify CSRF token or form elements

---

### 12. tests/Feature/AuthenticationBatch1Test.php

#### `test_guest_can_view_login_page()`
**Current Implementation:**
```php
public function test_guest_can_view_login_page(): void
{
    $response = $this->get('/login');
    
    $response->assertStatus(200);
}
```

**Issues:**
- Only checks 200 status code
- Duplicate of `test_login_screen_can_be_rendered()` in AuthenticationTest.php
- Doesn't verify login form elements are present
- Doesn't check for email/password fields or submit button

---

### 13. tests/Feature/MailboxViewTest.php

#### `test_user_with_access_can_view_mailbox_detail()`
**Current Implementation:**
```php
public function test_user_with_access_can_view_mailbox_detail(): void
{
    $this->mailbox->users()->attach($this->regularUser);
    $this->actingAs($this->regularUser);
    
    $response = $this->get(route('mailboxes.view', $this->mailbox));
    
    $response->assertStatus(200);
}
```

**Issues:**
- Only checks 200 status code
- Doesn't verify mailbox name or details are displayed
- Doesn't check for conversations list

---

### 14. tests/Feature/MailboxViewTest.php

#### `test_unauthenticated_user_cannot_view_mailbox_detail()`
**Current Implementation:**
```php
public function test_unauthenticated_user_cannot_view_mailbox_detail(): void
{
    $response = $this->get(route('mailboxes.view', $this->mailbox));
    
    $response->assertRedirect(route('login'));
}
```

**Issues:**
- Only checks redirect
- Could add `assertGuest()` assertion

---

### 15. tests/Feature/MailboxViewTest.php

#### `test_mailbox_index_requires_authentication()`
**Current Implementation:**
```php
public function test_mailbox_index_requires_authentication(): void
{
    $response = $this->get(route('mailboxes.index'));
    
    $response->assertRedirect(route('login'));
}
```

**Issues:**
- Only checks redirect to login
- Should add `assertGuest()` to verify unauthenticated state

---

### 16. tests/Feature/Auth/AuthenticationTest.php

#### `test_users_can_authenticate_using_the_login_screen()`
**Current Implementation:**
```php
public function test_users_can_authenticate_using_the_login_screen(): void
{
    $user = User::factory()->create();
    
    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);
    
    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
}
```

**Issues:**
- Doesn't verify user exists in database after login
- Could enhance with database checks

---

### 17. tests/Feature/Auth/AuthenticationTest.php

#### `test_users_can_logout()`
**Current Implementation:**
```php
public function test_users_can_logout(): void
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->post('/logout');
    
    $this->assertGuest();
    $response->assertRedirect('/');
}
```

**Issues:**
- Could enhance by verifying session is cleared/invalidated

---

### 18. tests/Feature/Auth/PasswordConfirmationTest.php

#### `test_password_can_be_confirmed()`
**Current Implementation:**
```php
public function test_password_can_be_confirmed(): void
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'password',
    ]);
    
    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
}
```

**Issues:**
- Only checks redirect and no errors
- Doesn't verify password confirmation state in session

---

### 19. tests/Feature/Auth/PasswordResetTest.php

#### `test_reset_password_screen_can_be_rendered()`
**Current Implementation:**
```php
public function test_reset_password_screen_can_be_rendered(): void
{
    Notification::fake();
    $user = User::factory()->create();
    $this->post('/forgot-password', ['email' => $user->email]);
    
    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);
        $response->assertStatus(200);
        return true;
    });
}
```

**Issues:**
- Only checks 200 status
- Doesn't verify form fields are present

---

### 20. tests/Feature/Auth/PasswordUpdateTest.php

#### `test_correct_password_must_be_provided_to_update_password()`
**Current Implementation:**
```php
public function test_correct_password_must_be_provided_to_update_password(): void
{
    $user = User::factory()->create();
    
    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);
    
    $response
        ->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/profile');
}
```

**Issues:**
- Should verify password hasn't changed with `Hash::check()`

---

### 21. tests/Feature/ConversationTest.php

#### `test_user_can_view_conversations_list()`
**Current Implementation:**
```php
public function test_user_can_view_conversations_list(): void
{
    $this->actingAs($this->user);
    
    $response = $this->get(route('conversations.index', $this->mailbox));
    
    $response->assertOk();
    $response->assertSee($this->mailbox->name);
}
```

**Issues:**
- Only verifies mailbox name is visible
- Should create conversations and verify they're listed

---

### 22. tests/Feature/ConversationTest.php

#### `test_user_can_view_conversation()`
**Current Implementation:**
```php
public function test_user_can_view_conversation(): void
{
    $this->actingAs($this->user);
    
    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create();
    
    $response = $this->get(route('conversations.show', $conversation));
    
    $response->assertOk();
    $response->assertSee($conversation->subject);
}
```

**Issues:**
- Only verifies subject is visible
- Should verify threads/messages are displayed

---

### 23. tests/Feature/ConversationControllerSecurityTest.php

#### `test_guest_cannot_view_conversations()`
**Current Implementation:**
```php
public function test_guest_cannot_view_conversations(): void
{
    $mailbox = Mailbox::factory()->create();
    
    $response = $this->get(route('conversations.index', $mailbox));
    
    $response->assertRedirect(route('login'));
}
```

**Issues:**
- Only checks redirect
- Should add `assertGuest()` assertion

---

### 24. tests/Feature/AuthenticationBatch1Test.php

#### `test_authenticated_user_can_log_out()`
**Current Implementation:**
```php
public function test_authenticated_user_can_log_out(): void
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->post('/logout');
    
    $this->assertGuest();
    $response->assertRedirect('/');
}
```

**Issues:**
- Duplicate of test in AuthenticationTest.php
- Could verify session invalidation

---

### 25. tests/Feature/ConversationAdvancedTest.php

#### `test_update_changes_conversation_status()`
**Current Implementation:**
```php
public function test_update_changes_conversation_status(): void
{
    $this->actingAs($this->agent);
    
    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create(['status' => Conversation::STATUS_ACTIVE]);
    
    $response = $this->patchJson(route('conversations.update', $conversation), [
        'status' => Conversation::STATUS_CLOSED,
    ]);
    
    $response->assertOk();
}
```

**Issues:**
- Only checks OK status
- Missing `assertDatabaseHas()` to verify status changed

---

## Summary Statistics

- **Total smoke tests identified:** 47 (updated from 25 in second review, 12 in first review)
- **Most common issue:** Only asserting status codes (200/OK) without verifying content
- **Second most common:** Not verifying database changes after data modification
- **Third most common:** Not checking for form fields or UI elements
- **Fourth most common:** Missing authentication state assertions
- **Fifth most common:** Security tests only checking status codes without verifying no data leakage
- **Sixth most common:** Validation tests not checking error message quality

## Priority Categories

### High Priority (Critical User Flows)
1. `test_new_users_can_register()` - User registration (missing DB assertions)
2. `test_users_can_authenticate_using_the_login_screen()` - Login flow
3. `test_profile_page_is_displayed()` - User profile display
4. `test_user_can_view_conversations_list()` - Conversation listing  
5. `test_user_can_view_conversation()` - Conversation detail view
6. `test_search_finds_by_subject()` - Search functionality minimal check
7. `test_admin_can_view_mailboxes_list()` - Mailbox listing
8. `test_admin_can_view_users_list()` - User management listing

### Medium Priority (Admin Functions & Search)
9. `test_admin_can_view_auto_reply_settings_page()` - Mailbox configuration
10. `test_admin_can_view_email_settings_page()` - System settings
11. `test_admin_can_view_mailbox_settings_page()` - Mailbox management
12. `test_update_changes_conversation_status()` - Status updates
13. `test_user_with_access_can_view_mailbox_detail()` - Mailbox access
14. `test_admin_can_view_mailbox_detail()` - Mailbox detail view
15. `test_admin_search_shows_all_mailboxes()` - Admin search scope
16. `test_conversation_search_prevents_sql_injection()` - Security check only

### Lower Priority (Form Rendering & Auth Guards)
17. `test_registration_screen_can_be_rendered()` - Form rendering
18. `test_login_screen_can_be_rendered()` - Login page
19. `test_email_verification_screen_can_be_rendered()` - Email verification
20. `test_reset_password_link_screen_can_be_rendered()` - Password reset
21. `test_reset_password_screen_can_be_rendered()` - Password reset form
22. `test_confirm_password_screen_can_be_rendered()` - Password confirmation
23. `test_user_can_access_customer_edit_page()` - Customer editing
24. `test_guest_can_view_login_page()` - Login page (duplicate)
25. `test_password_can_be_confirmed()` - Password confirmation flow
26. `test_correct_password_must_be_provided_to_update_password()` - Password validation
27. `test_users_can_logout()` - Logout flow
28. `test_authenticated_user_can_log_out()` - Logout flow (duplicate)
29. `test_unauthenticated_user_cannot_view_mailbox_detail()` - Auth guard
30. `test_mailbox_index_requires_authentication()` - Auth guard
31. `test_guest_cannot_view_conversations()` - Auth guard

### Validation-Only Tests (New Category)
32. `test_cannot_log_in_with_nonexistent_email()` - Login validation
33. `test_login_requires_email()` - Email required
34. `test_login_requires_password()` - Password required
35. `test_auto_reply_requires_subject_when_enabled()` - Subject validation
36. `test_auto_reply_requires_message_when_enabled()` - Message validation
37. `test_auto_reply_subject_has_max_length()` - Length validation
38. `test_auto_bcc_must_be_valid_email()` - Email format validation
39. `test_password_is_not_confirmed_with_invalid_password()` - Password mismatch

### Security Guard Tests (New Category)
40. `test_user_cannot_view_conversation_in_unauthorized_mailbox()` - Authorization
41. `test_non_admin_cannot_view_auto_reply_settings_page()` - Admin-only access
42. `test_non_admin_cannot_create_mailbox()` - Creation restriction
43. `test_user_without_access_cannot_view_mailbox_detail()` - Access control
44. `test_non_admin_cannot_view_mailbox_settings_page()` - Settings access
45. `test_non_admin_user_cannot_access_user_management_routes()` - User management access
46. `test_non_admin_cannot_access_users_list()` - User list access (duplicate)
47. `test_admin_cannot_delete_themselves()` - Self-deletion prevention

---

## Notes

This analysis focused on identifying tests that use shallow assertions. Many other tests in the codebase already follow best practices by:
- Using `assertDatabaseHas()` to verify data persistence
- Using `assertSee()` to verify content is displayed
- Combining multiple assertions to validate complete behavior
- Testing both positive and negative cases

The identified smoke tests should be enhanced to match the quality of the better-written tests already present in the test suite.

---

## THIRD REVIEW: Additional Smoke Tests Discovered

### 26. tests/Feature/ConversationAdvancedTest.php

#### `test_search_finds_by_subject()`
**Current Implementation:**
```php
public function test_search_finds_by_subject(): void
{
    $this->actingAs($this->agent);
    
    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create([
            'subject' => 'Password Reset Help',
            'state' => 2,
        ]);
    
    $response = $this->get(route('conversations.search', ['q' => 'Password']));
    
    $response->assertOk();
    $response->assertSee('Password Reset Help');
}
```

**Issues:**
- Creates conversation but only checks one is visible
- Doesn't verify search functionality is working correctly
- Should create multiple conversations and verify only matching ones appear
- Should test partial matching and case-insensitivity

---

### 27. tests/Feature/ConversationAdvancedTest.php

#### `test_admin_search_shows_all_mailboxes()`
**Current Implementation:**
```php
public function test_admin_search_shows_all_mailboxes(): void
{
    $this->actingAs($this->admin);
    
    $otherMailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()
        ->for($otherMailbox)
        ->create(['subject' => 'Admin Search Test', 'state' => 2]);
    
    $response = $this->get(route('conversations.search', ['q' => 'Admin']));
    
    $response->assertOk();
    $response->assertSee('Admin Search Test');
}
```

**Issues:**
- Only checks status and one conversation appears
- Doesn't verify admin can see conversations from ALL mailboxes
- Should create conversations in multiple mailboxes and verify all appear

---

### 28. tests/Feature/ConversationControllerSecurityTest.php

#### `test_conversation_search_prevents_sql_injection()`
**Current Implementation:**
```php
public function test_conversation_search_prevents_sql_injection(): void
{
    // ... setup code ...
    $maliciousInput = "' OR '1'='1";
    
    $response = $this->actingAs($user)->get(
        route('conversations.index', $mailbox) . '?q=' . urlencode($maliciousInput)
    );
    
    // Should return OK and handle safely, not throw SQL error
    $response->assertOk();
}
```

**Issues:**
- Only checks that no SQL error is thrown
- Doesn't verify the malicious input was properly escaped
- Should verify expected results (likely empty or no matches)

---

### 29. tests/Feature/MailboxTest.php

#### `test_admin_can_view_mailboxes_list()`
**Current Implementation:**
```php
public function test_admin_can_view_mailboxes_list(): void
{
    $this->actingAs($this->admin);
    
    $mailbox = Mailbox::factory()->create();
    
    $response = $this->get(route('mailboxes.index'));
    
    $response->assertOk();
    $response->assertSee($mailbox->name);
}
```

**Issues:**
- Only verifies one mailbox name is visible
- Should verify multiple mailboxes are listed
- Should check for pagination elements
- Should verify mailbox email addresses are shown

---

### 30. tests/Feature/MailboxViewTest.php

#### `test_admin_can_view_mailbox_detail()`
**Current Implementation:**
```php
public function test_admin_can_view_mailbox_detail(): void
{
    $this->actingAs($this->admin);
    
    $response = $this->get(route('mailboxes.view', $this->mailbox));
    
    $response->assertStatus(200);
    $response->assertSee('Support Mailbox');
}
```

**Issues:**
- Only checks status and mailbox name
- Should verify mailbox email is shown
- Should check for conversation count or list
- Should verify settings/edit buttons are present

---

### 31. tests/Feature/UserManagementTest.php

#### `test_admin_can_view_users_list()`
**Current Implementation:**
```php
public function test_admin_can_view_users_list(): void
{
    $this->actingAs($this->admin);
    
    $user = User::factory()->create();
    
    $response = $this->get(route('users.index'));
    
    $response->assertOk();
    $response->assertSee($user->email);
}
```

**Issues:**
- Only checks one user email is visible
- Should verify user names are shown
- Should check for role information display
- Should verify action buttons (edit, delete) are present

---

## Validation-Only Tests (New Category)

These tests only check for validation errors without verifying the underlying business logic or security implications.

### 32. tests/Feature/AuthenticationBatch1Test.php

#### `test_cannot_log_in_with_nonexistent_email()`
**Current Implementation:**
```php
public function test_cannot_log_in_with_nonexistent_email(): void
{
    $response = $this->post('/login', [
        'email' => 'nonexistent@example.com',
        'password' => 'password',
    ]);
    
    $this->assertGuest();
    $response->assertSessionHasErrors();
}
```

**Issues:**
- Good test but could be enhanced
- Should verify specific error message content
- Should check rate limiting isn't triggered
- Should verify no timing attacks are possible

---

### 33. tests/Feature/AuthenticationBatch1Test.php

#### `test_login_requires_email()`
**Current Implementation:**
```php
public function test_login_requires_email(): void
{
    $response = $this->post('/login', [
        'password' => 'password123',
    ]);
    
    $this->assertGuest();
    $response->assertSessionHasErrors('email');
}
```

**Issues:**
- Basic validation test
- Should verify error message is user-friendly
- Could check for specific validation rule (required, email format)

---

### 34. tests/Feature/AuthenticationBatch1Test.php

#### `test_login_requires_password()`
**Current Implementation:**
```php
public function test_login_requires_password(): void
{
    $response = $this->post('/login', [
        'email' => 'test@example.com',
    ]);
    
    $this->assertGuest();
    $response->assertSessionHasErrors('password');
}
```

**Issues:**
- Basic validation test
- Should verify error message content

---

### 35-38. tests/Feature/MailboxAutoReplyTest.php

#### Multiple validation tests:
- `test_auto_reply_requires_subject_when_enabled()`
- `test_auto_reply_requires_message_when_enabled()`
- `test_auto_reply_subject_has_max_length()`
- `test_auto_bcc_must_be_valid_email()`

**Common Issues:**
- Only check for validation errors
- Don't verify the validation messages are helpful
- Don't verify the form retains user input on error
- Don't test edge cases (empty strings vs null, whitespace-only)

---

### 39. tests/Feature/Auth/PasswordConfirmationTest.php

#### `test_password_is_not_confirmed_with_invalid_password()`
**Current Implementation:**
```php
public function test_password_is_not_confirmed_with_invalid_password(): void
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)->post('/confirm-password', [
        'password' => 'wrong-password',
    ]);
    
    $response->assertSessionHasErrors();
}
```

**Issues:**
- Only checks for errors
- Should verify user remains on confirmation page
- Should check specific error field
- Should verify rate limiting after multiple failures

---

## Security Guard Tests (New Category)

These tests verify authorization but don't check for data leakage or proper error messages.

### 40. tests/Feature/ConversationTest.php

#### `test_user_cannot_view_conversation_in_unauthorized_mailbox()`
**Current Implementation:**
```php
public function test_user_cannot_view_conversation_in_unauthorized_mailbox(): void
{
    $this->actingAs($this->user);
    
    $otherMailbox = Mailbox::factory()->create();
    $conversation = Conversation::factory()
        ->for($otherMailbox)
        ->create();
    
    $response = $this->get(route('conversations.show', $conversation));
    
    $response->assertForbidden();
}
```

**Issues:**
- Only checks 403 status
- Should verify no conversation data is leaked in response
- Should check error message is generic (not revealing internal info)

---

### 41. tests/Feature/MailboxAutoReplyTest.php

#### `test_non_admin_cannot_view_auto_reply_settings_page()`
**Current Implementation:**
```php
public function test_non_admin_cannot_view_auto_reply_settings_page(): void
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $this->actingAs($user);
    
    $response = $this->get(route('mailboxes.auto_reply', $this->mailbox));
    
    $response->assertStatus(403);
}
```

**Issues:**
- Only checks 403 status
- Should verify no settings data is leaked

---

### 42. tests/Feature/MailboxTest.php

#### `test_non_admin_cannot_create_mailbox()`
**Current Implementation:**
```php
public function test_non_admin_cannot_create_mailbox(): void
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    
    $this->actingAs($user);
    
    $response = $this->post(route('mailboxes.store'), [
        'name' => 'New Mailbox',
        'email' => 'new@example.com',
    ]);
    
    $response->assertForbidden();
}
```

**Issues:**
- Only checks 403 status
- Should verify no mailbox was created in database
- Should add `assertDatabaseMissing('mailboxes', ['email' => 'new@example.com'])`

---

### 43. tests/Feature/MailboxViewTest.php

#### `test_user_without_access_cannot_view_mailbox_detail()`
**Current Implementation:**
```php
public function test_user_without_access_cannot_view_mailbox_detail(): void
{
    $this->actingAs($this->regularUser);
    
    $response = $this->get(route('mailboxes.view', $this->mailbox));
    
    $response->assertStatus(403);
}
```

**Issues:**
- Only checks 403 status
- Should verify no mailbox data is visible

---

### 44. tests/Feature/MailboxViewTest.php

#### `test_non_admin_cannot_view_mailbox_settings_page()`
**Current Implementation:**
```php
public function test_non_admin_cannot_view_mailbox_settings_page(): void
{
    $this->actingAs($this->regularUser);
    
    $response = $this->get(route('mailboxes.settings', $this->mailbox));
    
    $response->assertStatus(403);
}
```

**Issues:**
- Only checks 403 status
- Should verify no settings data is leaked

---

### 45. tests/Feature/UserManagementAdminBatch1Test.php

#### `test_non_admin_user_cannot_access_user_management_routes()`
**Current Implementation:**
```php
public function test_non_admin_user_cannot_access_user_management_routes(): void
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    $this->actingAs($user);
    
    $response = $this->get(route('users.index'));
    
    $response->assertStatus(403);
}
```

**Issues:**
- Only checks 403 status
- Should verify no user data is leaked

---

### 46. tests/Feature/UserManagementTest.php

#### `test_non_admin_cannot_access_users_list()`
**Current Implementation:**
```php
public function test_non_admin_cannot_access_users_list(): void
{
    $user = User::factory()->create(['role' => User::ROLE_USER]);
    
    $this->actingAs($user);
    
    $response = $this->get(route('users.index'));
    
    $response->assertForbidden();
}
```

**Issues:**
- Duplicate of test in UserManagementAdminBatch1Test
- Only checks forbidden status

---

### 47. tests/Feature/UserManagementTest.php

#### `test_admin_cannot_delete_themselves()`
**Current Implementation:**
```php
public function test_admin_cannot_delete_themselves(): void
{
    $this->actingAs($this->admin);
    
    $response = $this->delete(route('users.destroy', $this->admin));
    
    $response->assertForbidden();
}
```

**Issues:**
- Only checks forbidden status
- Should verify admin still exists in database
- Should add `assertDatabaseHas('users', ['id' => $this->admin->id])`

---

