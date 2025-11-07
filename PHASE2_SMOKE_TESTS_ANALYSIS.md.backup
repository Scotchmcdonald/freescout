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

## Summary Statistics

- **Total smoke tests identified:** 12
- **Most common issue:** Only asserting status codes (200/OK) without verifying content
- **Second most common:** Not verifying database changes after actions
- **Third most common:** Not checking for form fields or UI elements

## Priority Categories

### High Priority (Critical User Flows)
1. `test_new_users_can_register()` - User registration
2. `test_login_screen_can_be_rendered()` - Authentication
3. `test_profile_page_is_displayed()` - User profile

### Medium Priority (Admin Functions)
4. `test_admin_can_view_auto_reply_settings_page()` - Mailbox configuration
5. `test_admin_can_view_email_settings_page()` - System settings
6. `test_admin_can_view_mailbox_settings_page()` - Mailbox management

### Lower Priority (Secondary Flows)
7. `test_registration_screen_can_be_rendered()` - Form rendering
8. `test_email_verification_screen_can_be_rendered()` - Email verification
9. `test_reset_password_link_screen_can_be_rendered()` - Password reset
10. `test_user_can_access_customer_edit_page()` - Customer editing
11. `test_confirm_password_screen_can_be_rendered()` - Password confirmation
12. `test_guest_can_view_login_page()` - Login page (duplicate test)

---

## Notes

This analysis focused on identifying tests that use shallow assertions. Many other tests in the codebase already follow best practices by:
- Using `assertDatabaseHas()` to verify data persistence
- Using `assertSee()` to verify content is displayed
- Combining multiple assertions to validate complete behavior
- Testing both positive and negative cases

The identified smoke tests should be enhanced to match the quality of the better-written tests already present in the test suite.
