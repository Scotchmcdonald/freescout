# Phase 3 Addendum 2: Third Review - Additional Test Enhancements

## Overview
This second addendum documents additional smoke tests identified during the third comprehensive review of the test suite. This review focused on specialized test files including connection settings, validation tests, and regression tests.

---

## 13. Mailbox Connection Settings Tests

### Test: `tests/Feature/MailboxConnectionTest.php -> test_admin_can_view_incoming_connection_settings_page()`

**Current Implementation:**
```php
public function admin_can_view_incoming_connection_settings_page()
{
    $this->actingAs($this->adminUser);
    
    $this->get(route('mailboxes.connection.incoming', $this->mailbox))
        ->assertStatus(200)
        ->assertSee('Incoming Connection');
}
```

**Enhancement Recommendations:**

1. **Verify View and Form Structure:**
   ```php
   $response->assertViewIs('mailboxes.connection.incoming');
   $response->assertSee('IMAP Settings');
   $response->assertSee('Protocol');
   $response->assertSee('Server');
   $response->assertSee('Port');
   ```

2. **Verify Current Settings are Pre-filled:**
   ```php
   $this->mailbox->update([
       'in_server' => 'imap.example.com',
       'in_port' => 993,
       'in_protocol' => 'imap',
   ]);
   
   $response->assertSee('imap.example.com');
   $response->assertSee('993');
   ```

3. **Verify Security Warning Messages:**
   ```php
   $response->assertSee('Connection Security');
   $response->assertSee('SSL/TLS');
   ```

**Enhanced Test Example:**
```php
public function admin_can_view_incoming_connection_settings_page()
{
    $this->actingAs($this->adminUser);
    
    $this->mailbox->update([
        'in_server' => 'imap.example.com',
        'in_port' => 993,
        'in_protocol' => 'imap',
    ]);
    
    $response = $this->get(route('mailboxes.connection.incoming', $this->mailbox));
    
    $response->assertStatus(200);
    $response->assertViewIs('mailboxes.connection.incoming');
    $response->assertSee('Incoming Connection Settings');
    $response->assertSee('imap.example.com');
    $response->assertSee('993');
    $response->assertSee('Protocol');
    $response->assertSee('Server Address');
    $response->assertSee('Port');
}
```

---

### Test: `tests/Feature/MailboxConnectionTest.php -> test_admin_can_view_outgoing_connection_settings_page()`

**Current Implementation:**
```php
public function admin_can_view_outgoing_connection_settings_page()
{
    $this->actingAs($this->adminUser);
    
    $this->get(route('mailboxes.connection.outgoing', $this->mailbox))
        ->assertStatus(200)
        ->assertSee('Outgoing Connection');
}
```

**Enhancement Recommendations:**

1. **Verify Complete Form Structure:**
   ```php
   $response->assertViewIs('mailboxes.connection.outgoing');
   $response->assertSee('SMTP Settings');
   $response->assertSee('From Name');
   $response->assertSee('Encryption');
   ```

2. **Verify SMTP Settings are Displayed:**
   ```php
   $this->mailbox->update([
       'out_server' => 'smtp.example.com',
       'out_port' => 587,
       'out_method' => 'smtp',
       'from_name_custom' => 'Support Team',
   ]);
   
   $response->assertSee('smtp.example.com');
   $response->assertSee('587');
   $response->assertSee('Support Team');
   ```

3. **Verify View Data:**
   ```php
   $response->assertViewHas('mailbox');
   $response->assertViewHas('encryptionOptions');
   ```

**Enhanced Test Example:**
```php
public function admin_can_view_outgoing_connection_settings_page()
{
    $this->actingAs($this->adminUser);
    
    $this->mailbox->update([
        'out_server' => 'smtp.example.com',
        'out_port' => 587,
        'out_method' => 'smtp',
        'from_name_custom' => 'Support Team',
    ]);
    
    $response = $this->get(route('mailboxes.connection.outgoing', $this->mailbox));
    
    $response->assertStatus(200);
    $response->assertViewIs('mailboxes.connection.outgoing');
    $response->assertSee('Outgoing Connection Settings');
    $response->assertSee('smtp.example.com');
    $response->assertSee('587');
    $response->assertSee('Support Team');
    $response->assertSee('SMTP Configuration');
    $response->assertSee('Encryption Type');
}
```

---

## 14. Mailbox Auto-Reply Tests

### Test: `tests/Feature/MailboxAutoReplyTest.php -> test_admin_can_view_auto_reply_settings_page()`

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

**Enhancement Recommendations:**

1. **Verify Complete Form Structure:**
   ```php
   $response->assertViewIs('mailboxes.auto-reply');
   $response->assertSee('Enable Auto Reply');
   $response->assertSee('Subject');
   $response->assertSee('Message');
   $response->assertSee('Auto BCC');
   ```

2. **Verify Current Settings are Displayed:**
   ```php
   $this->mailbox->update([
       'auto_reply_enabled' => true,
       'auto_reply_subject' => 'Thank you for your message',
       'auto_reply_message' => 'We will get back to you soon.',
   ]);
   
   $response->assertSee('Thank you for your message');
   $response->assertSee('We will get back to you soon');
   ```

3. **Verify Toggle State:**
   ```php
   $response->assertSee('checked', false); // If auto-reply is enabled
   ```

4. **Verify View Data:**
   ```php
   $response->assertViewHas('mailbox');
   $response->assertViewHas('autoReplySettings');
   ```

**Enhanced Test Example:**
```php
public function test_admin_can_view_auto_reply_settings_page(): void
{
    $this->actingAs($this->admin);
    
    $this->mailbox->update([
        'auto_reply_enabled' => true,
        'auto_reply_subject' => 'Thank you for contacting us',
        'auto_reply_message' => 'We received your message and will respond within 24 hours.',
        'auto_bcc' => 'archive@example.com',
    ]);
    
    $response = $this->get(route('mailboxes.auto_reply', $this->mailbox));
    
    $response->assertStatus(200);
    $response->assertViewIs('mailboxes.auto-reply');
    $response->assertSee('Auto Reply Settings');
    $response->assertSee('Enable Auto Reply');
    $response->assertSee('Thank you for contacting us');
    $response->assertSee('We received your message and will respond within 24 hours');
    $response->assertSee('archive@example.com');
    $response->assertSee('Subject Line');
    $response->assertSee('Message Body');
}
```

---

## 15. Mailbox Fetch Emails Tests

### Test: `tests/Feature/MailboxFetchEmailsTest.php -> test_admin_can_trigger_manual_email_fetch()`

**Current Implementation:**
```php
public function test_admin_can_trigger_manual_email_fetch(): void
{
    $this->actingAs($this->admin);
    
    $this->mock(ImapService::class, function (MockInterface $mock) {
        $mock->shouldReceive('fetchEmails')
            ->once()
            ->andReturn([
                'fetched' => 5,
                'created' => 3,
            ]);
    });
    
    $response = $this->postJson(route('mailboxes.fetch-emails', $this->mailbox));
    
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'stats' => [
            'fetched' => 5,
            'created' => 3,
        ],
    ]);
    $response->assertJsonFragment(['message' => 'Successfully fetched 5 emails. Created 3 new conversations.']);
}
```

**Enhancement Recommendations:**

1. **Verify Complete JSON Structure:**
   ```php
   $response->assertJsonStructure([
       'success',
       'message',
       'stats' => [
           'fetched',
           'created',
           'skipped',
           'errors',
       ],
       'timestamp',
   ]);
   ```

2. **Verify Specific Message Format:**
   ```php
   $response->assertJsonPath('success', true);
   $response->assertJsonPath('stats.fetched', 5);
   $response->assertJsonPath('stats.created', 3);
   ```

3. **Test Error Handling:**
   ```php
   // Test when IMAP connection fails
   $this->mock(ImapService::class, function (MockInterface $mock) {
       $mock->shouldReceive('fetchEmails')
           ->once()
           ->andThrow(new \Exception('Connection failed'));
   });
   
   $response2 = $this->postJson(route('mailboxes.fetch-emails', $this->mailbox));
   $response2->assertStatus(500);
   $response2->assertJson(['success' => false]);
   ```

**Enhanced Test Example:**
```php
public function test_admin_can_trigger_manual_email_fetch(): void
{
    $this->actingAs($this->admin);
    
    $this->mock(ImapService::class, function (MockInterface $mock) {
        $mock->shouldReceive('fetchEmails')
            ->once()
            ->with($this->mailbox)
            ->andReturn([
                'fetched' => 5,
                'created' => 3,
                'skipped' => 2,
                'errors' => 0,
            ]);
    });
    
    $response = $this->postJson(route('mailboxes.fetch-emails', $this->mailbox));
    
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'message',
        'stats' => ['fetched', 'created', 'skipped', 'errors'],
    ]);
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('stats.fetched', 5);
    $response->assertJsonPath('stats.created', 3);
    $response->assertJsonPath('stats.skipped', 2);
    $response->assertJsonFragment(['message' => 'Successfully fetched 5 emails. Created 3 new conversations.']);
}
```

---

## 16. Conversation Advanced Tests

### Test: `tests/Feature/ConversationAdvancedTest.php -> test_conversation_index_shows_only_published_conversations()`

**Current Implementation:**
```php
public function test_conversation_index_shows_only_published_conversations(): void
{
    $this->actingAs($this->agent);
    
    $published = Conversation::factory()
        ->for($this->mailbox)
        ->create(['state' => 2, 'status' => Conversation::STATUS_ACTIVE]);
    
    $draft = Conversation::factory()
        ->for($this->mailbox)
        ->create(['state' => 1, 'status' => Conversation::STATUS_ACTIVE]);
    
    $response = $this->get(route('conversations.index', $this->mailbox));
    
    $response->assertOk();
    $response->assertSee($published->subject);
    $response->assertDontSee($draft->subject);
}
```

**Enhancement Recommendations:**

1. **Verify View Data Structure:**
   ```php
   $response->assertViewIs('conversations.index');
   $response->assertViewHas('conversations');
   $response->assertViewHas('mailbox');
   ```

2. **Verify Conversation Count:**
   ```php
   $response->assertViewHas('conversations', function ($conversations) {
       return $conversations->count() === 1;
   });
   ```

3. **Verify Only Published State:**
   ```php
   $response->assertViewHas('conversations', function ($conversations) {
       return $conversations->every(function ($conv) {
           return $conv->state === 2; // Published
       });
   });
   ```

4. **Verify Pagination:**
   ```php
   $response->assertSee('pagination');
   ```

**Enhanced Test Example:**
```php
public function test_conversation_index_shows_only_published_conversations(): void
{
    $this->actingAs($this->agent);
    
    $published = Conversation::factory()
        ->for($this->mailbox)
        ->create([
            'state' => 2,
            'status' => Conversation::STATUS_ACTIVE,
            'subject' => 'Published Conversation',
        ]);
    
    $draft = Conversation::factory()
        ->for($this->mailbox)
        ->create([
            'state' => 1,
            'status' => Conversation::STATUS_ACTIVE,
            'subject' => 'Draft Conversation',
        ]);
    
    $response = $this->get(route('conversations.index', $this->mailbox));
    
    $response->assertOk();
    $response->assertViewIs('conversations.index');
    $response->assertSee('Published Conversation');
    $response->assertDontSee('Draft Conversation');
    $response->assertViewHas('conversations', function ($conversations) use ($published) {
        return $conversations->count() === 1 && 
               $conversations->first()->id === $published->id &&
               $conversations->first()->state === 2;
    });
}
```

---

### Test: `tests/Feature/ConversationAdvancedTest.php -> test_conversation_index_orders_by_most_recent()`

**Current Implementation:**
```php
public function test_conversation_index_orders_by_most_recent(): void
{
    $this->actingAs($this->agent);
    
    $older = Conversation::factory()
        ->for($this->mailbox)
        ->create([
            'state' => 2,
            'last_reply_at' => now()->subHours(2),
            'subject' => 'Older Conversation',
        ]);
    
    $newer = Conversation::factory()
        ->for($this->mailbox)
        ->create([
            'state' => 2,
            'last_reply_at' => now()->subHour(),
            'subject' => 'Newer Conversation',
        ]);
    
    $response = $this->get(route('conversations.index', $this->mailbox));
    
    $response->assertOk();
}
```

**Enhancement Recommendations:**

1. **Verify Ordering in View Data:**
   ```php
   $response->assertViewHas('conversations', function ($conversations) use ($newer, $older) {
       return $conversations->first()->id === $newer->id &&
              $conversations->last()->id === $older->id;
   });
   ```

2. **Verify Visual Order:**
   ```php
   // The newer conversation should appear before the older one in the HTML
   $content = $response->getContent();
   $newerPos = strpos($content, 'Newer Conversation');
   $olderPos = strpos($content, 'Older Conversation');
   $this->assertLessThan($olderPos, $newerPos);
   ```

3. **Verify Timestamps are Displayed:**
   ```php
   $response->assertSee($newer->last_reply_at->format('M d, Y'));
   $response->assertSee($older->last_reply_at->format('M d, Y'));
   ```

**Enhanced Test Example:**
```php
public function test_conversation_index_orders_by_most_recent(): void
{
    $this->actingAs($this->agent);
    
    $older = Conversation::factory()
        ->for($this->mailbox)
        ->create([
            'state' => 2,
            'last_reply_at' => now()->subHours(2),
            'subject' => 'Older Conversation',
        ]);
    
    $newer = Conversation::factory()
        ->for($this->mailbox)
        ->create([
            'state' => 2,
            'last_reply_at' => now()->subHour(),
            'subject' => 'Newer Conversation',
        ]);
    
    $response = $this->get(route('conversations.index', $this->mailbox));
    
    $response->assertOk();
    $response->assertViewIs('conversations.index');
    $response->assertSee('Newer Conversation');
    $response->assertSee('Older Conversation');
    
    // Verify ordering in view data
    $response->assertViewHas('conversations', function ($conversations) use ($newer, $older) {
        $ids = $conversations->pluck('id')->toArray();
        return $ids[0] === $newer->id && $ids[1] === $older->id;
    });
    
    // Verify visual order in HTML
    $content = $response->getContent();
    $newerPos = strpos($content, 'Newer Conversation');
    $olderPos = strpos($content, 'Older Conversation');
    $this->assertLessThan($olderPos, $newerPos, 'Newer conversation should appear before older one');
}
```

---

### Test: `tests/Feature/ConversationAdvancedTest.php -> test_conversation_show_loads_threads_in_order()`

**Current Implementation:**
```php
public function test_conversation_show_loads_threads_in_order(): void
{
    // ... setup code ...
    
    $response = $this->get(route('conversations.show', $conversation));
    
    $response->assertOk();
}
```

**Enhancement Recommendations:**

1. **Verify View and Data:**
   ```php
   $response->assertViewIs('conversations.show');
   $response->assertViewHas('conversation');
   $response->assertViewHas('threads');
   ```

2. **Verify Thread Ordering:**
   ```php
   $response->assertViewHas('threads', function ($threads) use ($thread1, $thread2, $thread3) {
       $ids = $threads->pluck('id')->toArray();
       return $ids[0] === $thread1->id &&
              $ids[1] === $thread2->id &&
              $ids[2] === $thread3->id;
   });
   ```

3. **Verify All Thread Content:**
   ```php
   $response->assertSee($thread1->body);
   $response->assertSee($thread2->body);
   $response->assertSee($thread3->body);
   ```

**Enhanced Test Example:**
```php
public function test_conversation_show_loads_threads_in_order(): void
{
    $this->actingAs($this->agent);
    
    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create(['subject' => 'Thread Order Test']);
    
    $thread1 = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'created_at' => now()->subHours(3),
        'body' => 'First thread message',
    ]);
    
    $thread2 = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'created_at' => now()->subHours(2),
        'body' => 'Second thread message',
    ]);
    
    $thread3 = Thread::factory()->create([
        'conversation_id' => $conversation->id,
        'created_at' => now()->subHour(),
        'body' => 'Third thread message',
    ]);
    
    $response = $this->get(route('conversations.show', $conversation));
    
    $response->assertOk();
    $response->assertViewIs('conversations.show');
    $response->assertSee('Thread Order Test');
    $response->assertSee('First thread message');
    $response->assertSee('Second thread message');
    $response->assertSee('Third thread message');
    
    $response->assertViewHas('threads', function ($threads) use ($thread1, $thread2, $thread3) {
        $ids = $threads->pluck('id')->toArray();
        return count($ids) === 3 &&
               $ids[0] === $thread1->id &&
               $ids[1] === $thread2->id &&
               $ids[2] === $thread3->id;
    });
}
```

---

### Test: `tests/Feature/ConversationAdvancedTest.php -> test_reply_returns_json_for_ajax_requests()`

**Current Implementation:**
```php
public function test_reply_returns_json_for_ajax_requests(): void
{
    // ... setup code ...
    
    $response = $this->postJson(route('conversations.reply', $conversation), [
        'body' => 'AJAX reply',
        'to' => [$conversation->customer->email],
    ]);
    
    $response->assertOk();
}
```

**Enhancement Recommendations:**

1. **Verify Complete JSON Structure:**
   ```php
   $response->assertJsonStructure([
       'success',
       'message',
       'thread' => [
           'id',
           'body',
           'created_at',
           'created_by_user_id',
       ],
       'conversation' => [
           'id',
           'status',
           'last_reply_at',
       ],
   ]);
   ```

2. **Verify JSON Values:**
   ```php
   $response->assertJsonPath('success', true);
   $response->assertJsonPath('thread.body', 'AJAX reply');
   $response->assertJsonPath('conversation.id', $conversation->id);
   ```

3. **Verify Database Changes:**
   ```php
   $this->assertDatabaseHas('threads', [
       'conversation_id' => $conversation->id,
       'body' => 'AJAX reply',
   ]);
   ```

**Enhanced Test Example:**
```php
public function test_reply_returns_json_for_ajax_requests(): void
{
    $this->actingAs($this->agent);
    
    $conversation = Conversation::factory()
        ->for($this->mailbox)
        ->create();
    
    $response = $this->postJson(route('conversations.reply', $conversation), [
        'body' => 'AJAX reply message',
        'to' => [$conversation->customer->email],
    ]);
    
    $response->assertOk();
    $response->assertJsonStructure([
        'success',
        'message',
        'thread' => ['id', 'body', 'created_at'],
        'conversation' => ['id', 'status'],
    ]);
    $response->assertJsonPath('success', true);
    $response->assertJsonPath('thread.body', 'AJAX reply message');
    $response->assertJsonPath('conversation.id', $conversation->id);
    
    $this->assertDatabaseHas('threads', [
        'conversation_id' => $conversation->id,
        'body' => 'AJAX reply message',
        'created_by_user_id' => $this->agent->id,
    ]);
}
```

---

## Summary of Third Review Additions

### New Test Categories Analyzed

**13. Mailbox Connection Settings** (2 tests)
- Incoming connection settings page view
- Outgoing connection settings page view

**14. Mailbox Auto-Reply** (1 test)
- Auto-reply settings page view and form display

**15. Mailbox Fetch Emails** (1 test)
- Manual email fetch AJAX endpoint with JSON validation

**16. Conversation Advanced** (4 tests)
- Published conversations filtering
- Conversation ordering by recency
- Thread loading and ordering
- AJAX reply JSON response

### Updated Statistics

**Original Phase 3**: 8 categories, 16 smoke tests  
**After Second Review**: 12 categories, 25+ smoke tests  
**After Third Review**: 16 categories, **33+ smoke tests** (106% increase from original)

### Revised Impact Estimates

- **Coverage Increase**: 25-30% (up from 20-25%)
- **Assertion Coverage**: 250-300% increase (up from 200-250%)
- **New Database Assertions**: 40+ (up from 30+)
- **New Content Assertions**: 80+ (up from 65+)
- **New JSON Assertions**: 25+ (up from 15+)
- **New View Data Assertions**: 30+ (new category)
- **Implementation Time**: 64-80 hours (up from 54-68 hours)

### Enhancement Pattern Distribution

**Total Patterns**: 6 (added View Data Assertions)
1. **View Rendering Tests** (13 tests): Add view assertions and content checks
2. **List/Index Tests** (7 tests): Verify data display, ordering, and view structure
3. **Detail/Show Tests** (5 tests): Verify complete information and relationships
4. **AJAX Tests** (4 tests): Verify JSON structure and data completeness
5. **Edit/Form Tests** (6 tests): Verify form pre-filling and elements
6. **Connection/Settings Tests** (3 tests): Verify configuration display and security

---

## Implementation Priority Update (Third Review)

### High Priority Tests (Expanded)
- **Authentication & Password Management**: All auth-related screens
- **Customer Management**: List, detail, edit, search, AJAX
- **Conversation Management**: List, detail, ordering, filtering
- **Mailbox Connection**: Incoming/outgoing settings display

### Medium Priority Tests (Expanded)
- **Mailbox Auto-Reply**: Settings page and form validation
- **Mailbox Email Fetch**: Manual fetch with error handling
- **Conversation Advanced**: Thread ordering, AJAX responses

### Lower Priority Tests
- **Regression Tests**: Backward compatibility verification
- **Security Tests**: XSS and injection protection
- **Edge Cases**: Boundary conditions and error scenarios

---

## Key Findings from Third Review

### 1. Configuration Management Tests
Many tests for configuration pages (connection settings, auto-reply) only verify that pages load, without checking:
- Current configuration values are displayed
- Form fields are properly pre-filled
- All configuration options are visible
- Help text and warnings are shown

### 2. AJAX Endpoint Tests
Several AJAX tests verify status codes but miss:
- Complete JSON response structure
- All expected data fields in responses
- Proper error response formats
- Data persistence after AJAX operations

### 3. Ordering and Filtering Tests
Tests that verify list ordering often only check that items appear, without:
- Verifying the actual order in view data
- Checking visual/HTML order matches data order
- Testing that sorting parameters are applied correctly

### 4. Thread and Message Tests
Tests for conversation threads frequently miss:
- Verification of thread ordering
- Complete thread metadata (timestamps, authors)
- Thread type indicators (message vs note)
- Thread status and state information

---

## Enhanced Testing Patterns Discovered

### Pattern 1: Configuration Page Testing
```php
// Bad: Only checks page loads
$response->assertStatus(200);

// Good: Verifies configuration is displayed
$response->assertStatus(200);
$response->assertViewIs('settings.page');
$response->assertSee($currentValue);
$response->assertViewHas('config');
```

### Pattern 2: Ordering Verification
```php
// Bad: Only checks items appear
$response->assertSee($item1->name);
$response->assertSee($item2->name);

// Good: Verifies actual order
$response->assertViewHas('items', function ($items) use ($item1, $item2) {
    return $items->first()->id === $item1->id;
});

// Even Better: Verifies visual order
$content = $response->getContent();
$pos1 = strpos($content, $item1->name);
$pos2 = strpos($content, $item2->name);
$this->assertLessThan($pos2, $pos1);
```

### Pattern 3: AJAX Response Validation
```php
// Bad: Only checks status
$response->assertOk();

// Good: Verifies JSON structure
$response->assertJsonStructure(['success', 'data']);
$response->assertJsonPath('success', true);

// Even Better: Verifies data and persistence
$response->assertJsonPath('data.id', $expected->id);
$this->assertDatabaseHas('table', ['id' => $expected->id]);
```

---

## Conclusion

The third review has identified 8 additional smoke tests across 4 new categories, bringing the total analysis to 33+ tests across 16 categories. This represents a 106% increase from the original Phase 3 analysis.

Key improvements in this review:
1. **Configuration Management**: Added recommendations for settings pages that display current values
2. **Ordering Verification**: Enhanced tests to verify both data and visual ordering
3. **AJAX Completeness**: Expanded JSON validation to include structure and persistence
4. **Thread Management**: Added thread ordering and metadata verification

The cumulative impact of all three reviews:
- **Total Tests Analyzed**: 33+ (up from 16 original)
- **Expected Coverage Increase**: 25-30%
- **Total New Assertions**: 175+ across all categories
- **Total Implementation Effort**: 64-80 hours

These enhancements will provide comprehensive test coverage across authentication, customer management, conversation handling, mailbox configuration, and AJAX endpoints, significantly improving the reliability and maintainability of the test suite.

---

**Document Version**: 1.2  
**Date**: November 7, 2024  
**Phase**: 3 Addendum 2 (Third Review)  
**Status**: Complete
