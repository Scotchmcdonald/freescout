# Work Batch 10: Polish, Error Pages & Final Testing

**Batch ID**: BATCH_10  
**Category**: Polish & Testing  
**Priority**: 🟢 LOW  
**Estimated Effort**: 12 hours  
**Parallelizable**: Partially (testing should be after other batches)  
**Dependencies**: All other batches should be complete

---

## Agent Prompt

You are implementing final polish items, error pages, and comprehensive testing for the FreeScout Laravel 11 application.

### Context

This is the final batch that adds professional polish to the application. These items are not critical for functionality but improve user experience and professionalism.

**Repository Location**: `/home/runner/work/freescout/freescout`  
**Target Directories**: 
- `resources/views/errors/` (error pages)
- `resources/views/settings/` (additional settings)
- Various testing directories

---

## Part A: Custom Error Pages (3 hours)

### 1. 403 Forbidden Page (1h)

**File**: `resources/views/errors/403.blade.php`

**Purpose**: Show when user lacks permission

**Requirements**:
- Clear error message
- Explanation of what happened
- Link back to dashboard
- Contact admin option
- Consistent branding

**Template Structure**:
```blade
@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8 text-center">
        <div class="mb-4">
            <svg class="mx-auto h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <!-- Lock icon -->
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
        </div>
        
        <h1 class="text-3xl font-bold text-gray-900 mb-2">403</h1>
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Access Forbidden</h2>
        
        <p class="text-gray-600 mb-6">
            You don't have permission to access this resource. 
            If you believe this is an error, please contact your administrator.
        </p>
        
        <div class="space-x-4">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Go to Dashboard
            </a>
            
            @if(auth()->user() && !auth()->user()->isAdmin())
            <a href="mailto:{{ config('mail.from.address') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                Contact Admin
            </a>
            @endif
        </div>
    </div>
</div>
@endsection
```

---

### 2. 404 Not Found Page (1h)

**File**: `resources/views/errors/404.blade.php`

**Purpose**: Show when resource doesn't exist

**Requirements**:
- Friendly error message
- Search functionality
- Common links (dashboard, mailboxes, etc.)
- Fun illustration or icon
- Breadcrumb trail if possible

**Additional Features**:
- Log 404s for SEO purposes
- Suggest similar pages based on URL
- Recently viewed items

**Template Structure**:
```blade
@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="max-w-2xl w-full bg-white shadow-lg rounded-lg p-8">
        <div class="text-center mb-8">
            <div class="mb-4">
                <svg class="mx-auto h-24 w-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <!-- Sad face or magnifying glass icon -->
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            
            <h1 class="text-6xl font-bold text-gray-900 mb-2">404</h1>
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Page Not Found</h2>
            
            <p class="text-gray-600 mb-6">
                Oops! The page you're looking for doesn't exist. 
                It might have been moved or deleted.
            </p>
        </div>
        
        <!-- Search Box -->
        <div class="mb-6">
            <form action="{{ route('conversations.search') }}" method="GET" class="relative">
                <input type="text" 
                       name="q" 
                       placeholder="Search conversations..." 
                       class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit" class="absolute right-3 top-3 text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </button>
            </form>
        </div>
        
        <!-- Quick Links -->
        <div class="grid grid-cols-2 gap-4">
            <a href="{{ route('dashboard') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                <svg class="w-6 h-6 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="font-medium">Dashboard</span>
            </a>
            
            <a href="{{ route('mailboxes.index') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <span class="font-medium">Mailboxes</span>
            </a>
            
            <a href="{{ route('customers.index') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                <svg class="w-6 h-6 text-purple-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <span class="font-medium">Customers</span>
            </a>
            
            <a href="{{ route('settings.index') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                <svg class="w-6 h-6 text-gray-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="font-medium">Settings</span>
            </a>
        </div>
    </div>
</div>
@endsection
```

---

### 3. 500 Server Error Page (1h)

**File**: `resources/views/errors/500.blade.php`

**Purpose**: Show when server encounters an error

**Requirements**:
- Apologetic message
- Error ID/reference number for support
- Report problem button
- Retry action
- Don't reveal sensitive error details

**Template Structure**:
```blade
@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8 text-center">
        <div class="mb-4">
            <svg class="mx-auto h-16 w-16 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <!-- Alert triangle icon -->
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>
        
        <h1 class="text-3xl font-bold text-gray-900 mb-2">500</h1>
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Server Error</h2>
        
        <p class="text-gray-600 mb-2">
            We're sorry! Something went wrong on our end.
        </p>
        
        <p class="text-sm text-gray-500 mb-6">
            Our team has been notified and is working on a fix.
            @if(isset($errorId))
            <br><br>
            Error Reference: <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $errorId }}</code>
            @endif
        </p>
        
        <div class="space-y-3">
            <button onclick="window.location.reload()" 
                    class="w-full inline-flex justify-center items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Try Again
            </button>
            
            <a href="{{ route('dashboard') }}" 
               class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Go Home
            </a>
        </div>
    </div>
</div>
@endsection
```

---

## Part B: Additional Settings Views (2 hours)

### 1. Alert Settings Page (2h)

**File**: `resources/views/settings/alerts.blade.php`

**Purpose**: Configure system alerts

**Requirements**:
- Enable/disable alert types
- Alert recipients (emails)
- Alert thresholds
- Test alert button

**Alert Types**:
- System errors
- High email queue
- Failed jobs
- Disk space low
- Database connection issues

**Form Structure**:
```blade
<form method="POST" action="{{ route('settings.alerts.update') }}">
    @csrf
    @method('PUT')
    
    <div class="space-y-6">
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Email Alerts</h3>
            
            <div class="space-y-4">
                <!-- System Errors -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" 
                               name="alerts[system_errors]" 
                               value="1"
                               {{ old('alerts.system_errors', $settings['system_errors'] ?? false) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </div>
                    <div class="ml-3">
                        <label class="font-medium text-gray-700">System Errors</label>
                        <p class="text-sm text-gray-500">Get notified when system errors occur</p>
                    </div>
                </div>
                
                <!-- High Email Queue -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" 
                               name="alerts[high_queue]" 
                               value="1"
                               {{ old('alerts.high_queue', $settings['high_queue'] ?? false) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    </div>
                    <div class="ml-3 flex-1">
                        <label class="font-medium text-gray-700">High Email Queue</label>
                        <p class="text-sm text-gray-500 mb-2">Alert when email queue exceeds threshold</p>
                        <input type="number" 
                               name="queue_threshold" 
                               value="{{ old('queue_threshold', $settings['queue_threshold'] ?? 100) }}"
                               class="w-32 border-gray-300 rounded-md text-sm"
                               placeholder="100">
                        <span class="text-sm text-gray-500 ml-2">emails</span>
                    </div>
                </div>
                
                <!-- More alert types... -->
            </div>
        </div>
        
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-medium mb-4">Alert Recipients</h3>
            
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Email Addresses</label>
                <textarea name="alert_recipients" 
                          rows="3"
                          class="w-full border-gray-300 rounded-md"
                          placeholder="admin@example.com&#10;tech@example.com">{{ old('alert_recipients', $settings['alert_recipients'] ?? '') }}</textarea>
                <p class="text-xs text-gray-500">One email per line</p>
            </div>
        </div>
        
        <div class="flex justify-between">
            <button type="submit" 
                    name="action" 
                    value="test"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                Send Test Alert
            </button>
            
            <button type="submit" 
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Save Settings
            </button>
        </div>
    </div>
</form>
```

---

## Part C: Comprehensive Testing (7 hours)

### 1. Integration Testing Suite (3h)

**Purpose**: Test complete user workflows

**Test Files to Create**:

**File**: `tests/Feature/CompleteWorkflowTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Conversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompleteWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_complete_full_ticket_lifecycle(): void
    {
        // 1. Create admin user
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        // 2. Create mailbox
        $this->actingAs($admin)
            ->post(route('mailboxes.store'), [
                'name' => 'Support',
                'email' => 'support@example.com',
            ])
            ->assertRedirect();
        
        $mailbox = Mailbox::first();
        $this->assertNotNull($mailbox);
        
        // 3. Create customer
        $customer = Customer::factory()->create();
        
        // 4. Create conversation
        $response = $this->actingAs($admin)
            ->post(route('conversations.store'), [
                'mailbox_id' => $mailbox->id,
                'customer_id' => $customer->id,
                'subject' => 'Test Ticket',
                'message' => 'This is a test message',
            ]);
        
        $conversation = Conversation::first();
        $this->assertNotNull($conversation);
        
        // 5. Reply to conversation
        $this->actingAs($admin)
            ->post(route('conversations.reply', $conversation), [
                'message' => 'Thank you for contacting us.',
            ])
            ->assertRedirect();
        
        // 6. Verify thread created
        $this->assertCount(2, $conversation->fresh()->threads);
        
        // 7. Close conversation
        $this->actingAs($admin)
            ->patch(route('conversations.update', $conversation), [
                'status' => Conversation::STATUS_CLOSED,
            ])
            ->assertRedirect();
        
        // 8. Verify conversation closed
        $this->assertEquals(Conversation::STATUS_CLOSED, $conversation->fresh()->status);
    }

    /** @test */
    public function regular_user_workflow_respects_permissions(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        // User cannot create mailbox
        $this->actingAs($user)
            ->post(route('mailboxes.store'), [
                'name' => 'Test',
                'email' => 'test@example.com',
            ])
            ->assertForbidden();
        
        // Grant mailbox access
        $mailbox->users()->attach($user->id, ['access_level' => 20]); // REPLY
        
        // User CAN create conversation in assigned mailbox
        $customer = Customer::factory()->create();
        $this->actingAs($user)
            ->post(route('conversations.store'), [
                'mailbox_id' => $mailbox->id,
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'message' => 'Message',
            ])
            ->assertRedirect();
        
        $this->assertCount(1, Conversation::all());
    }

    /** @test */
    public function email_fetching_creates_conversations(): void
    {
        $mailbox = Mailbox::factory()->create([
            'email' => 'support@example.com',
        ]);
        
        // Simulate IMAP service finding new email
        // This would typically be mocked
        $this->artisan('freescout:fetch-emails')
            ->assertExitCode(0);
        
        // Verify email processing (would need actual IMAP mock)
    }
}
```

---

### 2. Performance Testing (2h)

**Purpose**: Ensure application performs well under load

**File**: `tests/Feature/PerformanceTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function conversation_list_loads_quickly_with_many_conversations(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        // Create 1000 conversations
        Conversation::factory()->count(1000)->create([
            'mailbox_id' => $mailbox->id,
        ]);
        
        // Measure response time
        $startTime = microtime(true);
        
        $response = $this->actingAs($user)
            ->get(route('mailboxes.conversations', $mailbox));
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        $response->assertOk();
        
        // Should load in under 500ms
        $this->assertLessThan(0.5, $duration, 
            "Conversation list took {$duration}s to load (should be < 0.5s)");
    }

    /** @test */
    public function database_queries_are_optimized(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        Conversation::factory()->count(50)->create([
            'mailbox_id' => $mailbox->id,
        ]);
        
        // Enable query logging
        \DB::enableQueryLog();
        
        $this->actingAs($user)
            ->get(route('mailboxes.conversations', $mailbox))
            ->assertOk();
        
        $queries = \DB::getQueryLog();
        
        // Should not have N+1 query problems
        // Adjust number based on expected queries
        $this->assertLessThan(20, count($queries), 
            "Too many database queries: " . count($queries));
    }
}
```

---

### 3. Security Testing (2h)

**Purpose**: Verify security measures

**File**: `tests/Feature/SecurityTest.php`

```php
<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function users_cannot_access_other_mailbox_conversations(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        
        // User has access to mailbox1 only
        $mailbox1->users()->attach($user->id, ['access_level' => 20]);
        
        $conversation1 = Conversation::factory()->create(['mailbox_id' => $mailbox1->id]);
        $conversation2 = Conversation::factory()->create(['mailbox_id' => $mailbox2->id]);
        
        // Can access mailbox1 conversation
        $this->actingAs($user)
            ->get(route('conversations.show', $conversation1))
            ->assertOk();
        
        // Cannot access mailbox2 conversation
        $this->actingAs($user)
            ->get(route('conversations.show', $conversation2))
            ->assertForbidden();
    }

    /** @test */
    public function csrf_protection_is_enabled(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        // POST without CSRF token should fail
        $response = $this->actingAs($user)
            ->post(route('conversations.store'), [
                'mailbox_id' => $mailbox->id,
                'subject' => 'Test',
            ], ['X-CSRF-TOKEN' => 'invalid']);
        
        $response->assertStatus(419); // CSRF token mismatch
    }

    /** @test */
    public function xss_protection_sanitizes_user_input(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $this->actingAs($user)
            ->post(route('conversations.store'), [
                'mailbox_id' => $mailbox->id,
                'subject' => '<script>alert("xss")</script>Test',
                'message' => '<img src=x onerror="alert(1)">',
            ]);
        
        $conversation = Conversation::first();
        
        // Should be escaped
        $this->assertStringNotContainsString('<script>', $conversation->subject);
        $this->assertStringNotContainsString('onerror', $conversation->threads->first()->body);
    }

    /** @test */
    public function sql_injection_is_prevented(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        // Try SQL injection in search
        $response = $this->actingAs($user)
            ->get(route('conversations.search', [
                'q' => "' OR '1'='1",
            ]));
        
        $response->assertOk();
        // Should not return all conversations (SQL injection failed)
    }
}
```

---

## Part D: Documentation Updates (0 hours - informational)

**Files to Update**:
1. `README.md` - Add error page documentation
2. `docs/IMPLEMENTATION_CHECKLIST.md` - Mark items complete
3. `docs/PROGRESS.md` - Update to 100% complete

---

## Implementation Guidelines

### Error Page Best Practices

1. **User-Friendly Language**:
   - Avoid technical jargon
   - Be apologetic but helpful
   - Provide clear next steps

2. **Branding Consistency**:
   - Use app colors and fonts
   - Include logo if appropriate
   - Match overall design language

3. **Actionable Options**:
   - Clear navigation back to safety
   - Search functionality
   - Contact support

4. **SEO Considerations**:
   - Proper HTTP status codes
   - No indexing of error pages
   - Internal link structure

### Testing Best Practices

1. **Test Coverage**:
   - Aim for 80%+ code coverage
   - Focus on critical paths
   - Test edge cases

2. **Test Organization**:
   - Feature tests for user workflows
   - Unit tests for isolated logic
   - Integration tests for system interactions

3. **Performance Benchmarks**:
   - Set realistic thresholds
   - Test with production-like data
   - Monitor query counts

4. **Security Testing**:
   - Test all authorization rules
   - Verify CSRF protection
   - Check XSS/SQL injection prevention

### Success Criteria

- [ ] All 3 error pages implemented and styled
- [ ] Error pages match app branding
- [ ] Alert settings page functional
- [ ] Integration tests pass
- [ ] Performance tests pass
- [ ] Security tests pass
- [ ] Test coverage > 80%
- [ ] Documentation updated

### Time Estimate

- Error pages: 3 hours
- Settings pages: 2 hours
- Integration testing: 3 hours
- Performance testing: 2 hours
- Security testing: 2 hours

**Total**: 12 hours

### Dependencies

- All other batches should be substantially complete
- Test database seeded with realistic data
- All models and policies implemented

### Notes

- Error pages should be visually appealing
- Testing should be comprehensive but not excessive
- Focus on user-facing workflows
- Document any discovered issues
- Create GitHub issues for bugs found during testing

---

**Batch Status**: Ready for implementation  
**This is the final batch**: All features should be complete after this
