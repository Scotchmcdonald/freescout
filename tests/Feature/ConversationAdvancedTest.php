<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Advanced functional tests for ConversationController.
 * Focus on complex workflows, edge cases, and business logic.
 */
class ConversationAdvancedTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected Mailbox $mailbox;
    protected Folder $inboxFolder;
    protected Folder $closedFolder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->agent = User::factory()->create(['role' => User::ROLE_USER]);

        $this->mailbox = Mailbox::factory()->create([
            'name' => 'Support',
            'email' => 'support@example.com',
        ]);

        $this->mailbox->users()->attach($this->admin);
        $this->mailbox->users()->attach($this->agent);

        $this->inboxFolder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_INBOX,
            'name' => 'Inbox',
        ]);

        $this->closedFolder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_TRASH,
            'name' => 'Trash',
        ]);
    }

    /** Test conversation listing with pagination and filtering */
    public function test_conversation_index_shows_only_published_conversations(): void
    {
        $this->actingAs($this->agent);

        // Create published conversation
        $published = Conversation::factory()
            ->for($this->mailbox)
            ->create(['state' => 2, 'status' => Conversation::STATUS_ACTIVE]);

        // Create draft conversation (should not appear)
        $draft = Conversation::factory()
            ->for($this->mailbox)
            ->create(['state' => 1, 'status' => Conversation::STATUS_ACTIVE]);

        $response = $this->get(route('conversations.index', $this->mailbox));

        $response->assertOk();
        $response->assertViewIs('conversations.index');
        $response->assertSee($published->subject);
        $response->assertDontSee($draft->subject);
        $response->assertViewHas('conversations', function ($conversations) use ($published, $draft) {
            return $conversations->contains($published) && !$conversations->contains($draft);
        });
    }

    /** Test conversation listing ordering by last_reply_at */
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
        $response->assertViewHas('conversations', function ($conversations) use ($newer, $older) {
            $ids = $conversations->pluck('id')->toArray();
            $newerIndex = array_search($newer->id, $ids);
            $olderIndex = array_search($older->id, $ids);
            return $newerIndex !== false && $olderIndex !== false && $newerIndex < $olderIndex;
        });
        
        // Newer should appear before older in the HTML
        $content = $response->getContent();
        $newerPos = strpos($content, 'Newer Conversation');
        $olderPos = strpos($content, 'Older Conversation');
        
        $this->assertNotFalse($newerPos);
        $this->assertNotFalse($olderPos);
        $this->assertLessThan($olderPos, $newerPos, 'Newer conversation should appear before older');
    }

    /** Test conversation show marks notifications as read */
    public function test_viewing_conversation_marks_notifications_as_read(): void
    {
        $this->actingAs($this->agent);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        // Create a notification for this user about this conversation
        $this->agent->notifications()->create([
            'id' => \Illuminate\Support\Str::uuid(),
            'type' => 'App\Notifications\NewConversationReply',
            'data' => ['conversation_id' => $conversation->id, 'message' => 'Test'],
            'read_at' => null,
        ]);

        $this->assertCount(1, $this->agent->unreadNotifications);

        $this->get(route('conversations.show', $conversation));

        $this->agent->refresh();
        $this->assertCount(0, $this->agent->unreadNotifications);
    }

    /** Test conversation show loads threads in chronological order */
    public function test_conversation_show_loads_threads_in_order(): void
    {
        $this->actingAs($this->agent);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        // Create threads out of order
        $thread3 = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => 2,
            'body' => 'Third message',
            'created_at' => now()->addMinutes(2),
        ]);

        $thread1 = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => 2,
            'body' => 'First message',
            'created_at' => now(),
        ]);

        $thread2 = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'state' => 2,
            'body' => 'Second message',
            'created_at' => now()->addMinute(),
        ]);

        $response = $this->get(route('conversations.show', $conversation));

        $response->assertOk();
        $response->assertViewIs('conversations.show');
        
        // Check threads appear in chronological order
        $content = $response->getContent();
        $firstPos = strpos($content, 'First message');
        $secondPos = strpos($content, 'Second message');
        $thirdPos = strpos($content, 'Third message');
        
        $this->assertNotFalse($firstPos);
        $this->assertNotFalse($secondPos);
        $this->assertNotFalse($thirdPos);
        $this->assertLessThan($secondPos, $firstPos);
        $this->assertLessThan($thirdPos, $secondPos);
    }

    /** Test creating conversation with new customer */
    public function test_store_creates_conversation_with_new_customer(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('conversations.store', $this->mailbox), [
            'subject' => 'New Support Request',
            'body' => 'I need help with my account',
            'to' => ['newcustomer@example.com'],
            'customer_email' => 'newcustomer@example.com',
            'customer_first_name' => 'John',
            'customer_last_name' => 'Doe',
        ]);

        $response->assertRedirect();

        // Check customer was created
        $this->assertDatabaseHas('customers', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        // Check conversation was created
        $this->assertDatabaseHas('conversations', [
            'mailbox_id' => $this->mailbox->id,
            'subject' => 'New Support Request',
        ]);

        // Check initial thread was created
        $this->assertDatabaseHas('threads', [
            'body' => 'I need help with my account',
        ]);
    }

    /** Test creating conversation with existing customer */
    public function test_store_uses_existing_customer(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::factory()->create();
        $email = Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'existing@example.com',
            'type' => 1,
        ]);

        $response = $this->post(route('conversations.store', $this->mailbox), [
            'subject' => 'Follow-up Request',
            'body' => 'Another question',
            'to' => [$email->email],
            'customer_id' => $customer->id,
        ]);

        $response->assertRedirect();

        // Should use existing customer, not create a new one
        $this->assertEquals(1, Customer::count());

        $conversation = Conversation::where('subject', 'Follow-up Request')->first();
        $this->assertEquals($customer->id, $conversation->customer_id);
    }

    /** Test conversation number auto-increments */
    public function test_store_auto_increments_conversation_number(): void
    {
        $this->actingAs($this->admin);

        // Create first conversation manually
        Conversation::factory()->for($this->mailbox)->create(['number' => 1]);

        $customer = Customer::factory()->create();
        $email = Email::factory()->create(['customer_id' => $customer->id]);

        $this->post(route('conversations.store', $this->mailbox), [
            'subject' => 'Second Conversation',
            'body' => 'Test body',
            'to' => [$email->email],
            'customer_id' => $customer->id,
        ]);

        $conversation = Conversation::where('subject', 'Second Conversation')->first();
        $this->assertEquals(2, $conversation->number);
    }

    /** Test store handles database errors gracefully */
    public function test_store_rolls_back_on_error(): void
    {
        $this->actingAs($this->admin);

        $initialConversations = Conversation::count();
        $initialCustomers = Customer::count();

        // Try to create conversation with invalid email (should trigger validation error)
        $response = $this->post(route('conversations.store', $this->mailbox), [
            'subject' => 'Test',
            'body' => 'Test',
            'to' => ['invalid-email-format'], // Invalid email
            'customer_email' => 'test@example.com',
        ]);

        // Should redirect back with validation errors
        $response->assertSessionHasErrors('to.0');

        // Database should be unchanged (no customer or conversation created)
        $this->assertEquals($initialConversations, Conversation::count());
        $this->assertEquals($initialCustomers, Customer::count());
    }

    /** Test updating conversation status */
    public function test_update_changes_conversation_status(): void
    {
        $this->actingAs($this->agent);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create(['status' => Conversation::STATUS_ACTIVE]);

        $response = $this->patch(route('conversations.update', $conversation), [
            'status' => Conversation::STATUS_PENDING,
        ]);

        $response->assertRedirect();

        // Verify status was updated in database
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'status' => Conversation::STATUS_PENDING,
        ]);

        $conversation->refresh();
        $this->assertEquals(Conversation::STATUS_PENDING, $conversation->status);
    }

    /** Test reassigning conversation to different user */
    public function test_update_reassigns_conversation(): void
    {
        $this->actingAs($this->admin);

        $otherAgent = User::factory()->create(['role' => User::ROLE_USER]);
        $this->mailbox->users()->attach($otherAgent);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create(['user_id' => $this->agent->id]);

        $response = $this->patch(route('conversations.update', $conversation), [
            'user_id' => $otherAgent->id,
        ]);

        $response->assertRedirect();

        $conversation->refresh();
        $this->assertEquals($otherAgent->id, $conversation->user_id);
    }

    /** Test moving conversation to different folder */
    public function test_update_moves_conversation_to_folder(): void
    {
        $this->actingAs($this->agent);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'folder_id' => $this->inboxFolder->id,
                'status' => Conversation::STATUS_ACTIVE,
            ]);

        $response = $this->patch(route('conversations.update', $conversation), [
            'folder_id' => $this->closedFolder->id,
        ]);

        $response->assertRedirect();

        $conversation->refresh();
        $this->assertEquals($this->closedFolder->id, $conversation->folder_id);
    }

    /** Test reply creates thread and updates conversation */
    public function test_reply_creates_thread_and_updates_timestamps(): void
    {
        $this->actingAs($this->agent);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'threads_count' => 1,
                'last_reply_at' => now()->subHour(),
            ]);

        $initialLastReply = $conversation->last_reply_at;

        $response = $this->post(route('conversations.reply', $conversation), [
            'body' => 'This is my reply to the customer',
        ]);

        $response->assertRedirect();

        // Check thread was created
        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'body' => 'This is my reply to the customer',
            'created_by_user_id' => $this->agent->id,
        ]);

        // Check conversation was updated
        $conversation->refresh();
        $this->assertEquals(2, $conversation->threads_count);
        $this->assertTrue($conversation->last_reply_at->greaterThan($initialLastReply));
    }

    /** Test reply can change conversation status */
    public function test_reply_can_update_status_to_closed(): void
    {
        $this->actingAs($this->agent);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create(['status' => Conversation::STATUS_ACTIVE]);

        $response = $this->post(route('conversations.reply', $conversation), [
            'body' => 'Issue resolved, closing ticket',
            'status' => Conversation::STATUS_CLOSED,
        ]);

        $response->assertRedirect();

        $conversation->refresh();
        $this->assertEquals(Conversation::STATUS_CLOSED, $conversation->status);
    }

    /** Test reply supports JSON responses for AJAX */
    public function test_reply_returns_json_for_ajax_requests(): void
    {
        $this->actingAs($this->agent);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        $response = $this->postJson(route('conversations.reply', $conversation), [
            'body' => 'AJAX reply test',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure([
            'success',
            'thread' => ['id', 'body', 'user'],
        ]);
        
        // Verify the response data
        $data = $response->json();
        $this->assertTrue($data['success']);
        $this->assertEquals('AJAX reply test', $data['thread']['body']);
    }

    /** Test reply with type=2 creates internal note */
    public function test_reply_can_create_internal_note(): void
    {
        $this->actingAs($this->agent);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();

        $response = $this->post(route('conversations.reply', $conversation), [
            'body' => 'Internal note: Customer seems frustrated',
            'type' => 2, // Internal note
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'body' => 'Internal note: Customer seems frustrated',
            'type' => 2,
        ]);
    }

    /** Test search finds conversations by subject */
    public function test_search_finds_by_subject(): void
    {
        $this->actingAs($this->agent);

        $targetConv = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'subject' => 'Password Reset Help',
                'state' => 2,
            ]);
            
        $otherConv = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'subject' => 'Billing Question',
                'state' => 2,
            ]);

        $response = $this->get(route('conversations.search', ['q' => 'Password']));

        $response->assertOk();
        $response->assertViewIs('conversations.search');
        $response->assertSee('Password Reset Help');
        $response->assertDontSee('Billing Question');
        $response->assertViewHas('conversations', function ($conversations) use ($targetConv, $otherConv) {
            return $conversations->contains($targetConv) && !$conversations->contains($otherConv);
        });
    }

    /** Test search finds conversations by customer name */
    public function test_search_finds_by_customer_name(): void
    {
        $this->actingAs($this->agent);

        $customer = Customer::factory()->create([
            'first_name' => 'Sarah',
            'last_name' => 'Connor',
        ]);

        $conversation = Conversation::factory()
            ->for($this->mailbox)
            ->for($customer)
            ->create([
                'subject' => 'Technical Issue',
                'state' => 2,
            ]);

        $response = $this->get(route('conversations.search', ['q' => 'Sarah']));

        $response->assertOk();
        $response->assertSee('Technical Issue');
        $response->assertSee('Sarah');
    }

    /** Test search respects mailbox permissions */
    public function test_search_only_shows_authorized_mailboxes(): void
    {
        $this->actingAs($this->agent);

        // Create conversation in authorized mailbox
        $authorized = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'subject' => 'Authorized Conversation',
                'state' => 2,
            ]);

        // Create conversation in unauthorized mailbox
        $otherMailbox = Mailbox::factory()->create();
        $unauthorized = Conversation::factory()
            ->for($otherMailbox)
            ->create([
                'subject' => 'Unauthorized Conversation',
                'state' => 2,
            ]);

        $response = $this->get(route('conversations.search', ['q' => 'Conversation']));

        $response->assertOk();
        $response->assertSee('Authorized Conversation');
        $response->assertDontSee('Unauthorized Conversation');
    }

    /** Test admin can search across all mailboxes */
    public function test_admin_search_shows_all_mailboxes(): void
    {
        $this->actingAs($this->admin);

        // Create conversations in multiple mailboxes
        $mailbox1Conv = Conversation::factory()
            ->for($this->mailbox)
            ->create([
                'subject' => 'Mailbox 1 Search Test',
                'state' => 2,
            ]);

        $otherMailbox = Mailbox::factory()->create();
        $mailbox2Conv = Conversation::factory()
            ->for($otherMailbox)
            ->create([
                'subject' => 'Mailbox 2 Search Test',
                'state' => 2,
            ]);

        $response = $this->get(route('conversations.search', ['q' => 'Search Test']));

        $response->assertOk();
        $response->assertViewIs('conversations.search');
        $response->assertSee('Mailbox 1 Search Test');
        $response->assertSee('Mailbox 2 Search Test');
        $response->assertViewHas('conversations', function ($conversations) use ($mailbox1Conv, $mailbox2Conv) {
            return $conversations->contains($mailbox1Conv) && $conversations->contains($mailbox2Conv);
        });
    }

    /** Test search pagination works correctly */
    public function test_search_paginates_results(): void
    {
        $this->actingAs($this->admin);

        // Create 60 conversations (more than 50 per page)
        Conversation::factory()
            ->count(60)
            ->for($this->mailbox)
            ->create([
                'subject' => 'Test Conversation',
                'state' => 2,
            ]);

        $response = $this->get(route('conversations.search', ['q' => 'Test']));

        $response->assertOk();
        $response->assertViewHas('conversations');
        
        $conversations = $response->viewData('conversations');
        $this->assertEquals(50, $conversations->count());
        $this->assertTrue($conversations->hasMorePages());
    }
}
