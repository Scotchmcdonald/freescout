<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Phase 6 - Task 6.1: Complete Workflow Tests
 * 
 * Tests end-to-end workflows to validate system integration:
 * - Full customer inquiry workflow
 * - Auto-reply workflow
 * - Conversation assignment workflow
 * - Multi-user collaboration workflow
 * - Email threading workflow
 * - Attachment handling workflow
 * - User authentication → dashboard → conversation workflow
 * - Settings update → system impact workflow
 */
class CompleteWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $agent;
    protected Mailbox $mailbox;
    protected Customer $customer;
    protected Email $customerEmail;
    protected Folder $inboxFolder;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin and agent users
        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
        ]);

        $this->agent = User::factory()->create([
            'role' => User::ROLE_USER,
            'email' => 'agent@example.com',
        ]);

        // Create mailbox and attach users
        $this->mailbox = Mailbox::factory()->create([
            'name' => 'Support Mailbox',
            'email' => 'support@example.com',
        ]);
        $this->mailbox->users()->attach([$this->admin->id, $this->agent->id]);

        // Create folders
        $this->inboxFolder = Folder::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'type' => Folder::TYPE_INBOX,
            'name' => 'Inbox',
        ]);

        // Create customer
        $this->customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->customerEmail = Email::factory()->create([
            'customer_id' => $this->customer->id,
            'email' => 'john.doe@customer.com',
            'type' => 1, // Primary email
        ]);
    }

    /**
     * Test 1: Full customer inquiry workflow (email in → conversation → reply → email out)
     */
    public function test_full_customer_inquiry_workflow(): void
    {
        Mail::fake();

        // Step 1: Customer inquiry arrives - Create conversation
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
            'subject' => 'Need help with product',
            'status' => Conversation::STATUS_ACTIVE,
            'state' => Conversation::STATE_PUBLISHED,
        ]);

        // Create initial customer thread
        $customerThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => 1, // Message type
            'body' => 'Hello, I need help with your product.',
            'state' => 2, // Published
            'created_by_customer_id' => $this->customer->id,
        ]);

        // Verify conversation exists
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'subject' => 'Need help with product',
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('threads', [
            'id' => $customerThread->id,
            'conversation_id' => $conversation->id,
            'body' => 'Hello, I need help with your product.',
        ]);

        // Step 2: Agent views and assigns conversation to themselves
        $response = $this->actingAs($this->agent)
            ->get(route('conversations.show', $conversation));
        $response->assertOk();
        $response->assertSee('Need help with product');

        // Agent assigns conversation to themselves
        $response = $this->actingAs($this->agent)
            ->patch(route('conversations.update', $conversation), [
                'user_id' => $this->agent->id,
            ]);
        $response->assertRedirect();

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'user_id' => $this->agent->id,
        ]);

        // Step 3: Agent replies to customer
        $response = $this->actingAs($this->agent)
            ->post(route('conversations.reply', $conversation), [
                'body' => 'Hi John, I would be happy to help you with that.',
                'to' => [$this->customerEmail->email],
            ]);
        $response->assertRedirect();

        // Verify reply thread was created
        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'body' => 'Hi John, I would be happy to help you with that.',
            'created_by_user_id' => $this->agent->id,
        ]);

        // Step 4: Verify conversation is still active
        $conversation->refresh();
        $this->assertEquals(Conversation::STATUS_ACTIVE, $conversation->status);
        $this->assertEquals($this->agent->id, $conversation->user_id);
    }

    /**
     * Test 2: Auto-reply workflow
     */
    public function test_auto_reply_workflow(): void
    {
        // Enable auto-reply on mailbox
        $this->mailbox->update([
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'We received your message',
            'auto_reply_message' => 'Thank you for contacting us. We will respond shortly.',
        ]);

        // Create a new conversation (simulating incoming email)
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
            'subject' => 'Initial inquiry',
            'status' => Conversation::STATUS_ACTIVE,
            'state' => Conversation::STATE_PUBLISHED,
        ]);

        // Verify auto-reply settings are stored
        $this->assertDatabaseHas('mailboxes', [
            'id' => $this->mailbox->id,
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'We received your message',
        ]);

        // Note: Actual auto-reply sending would be tested in Job tests (Phase 1)
        // Here we verify the workflow setup and data persistence
        $this->assertTrue($this->mailbox->auto_reply_enabled);
    }

    /**
     * Test 3: Conversation assignment workflow
     */
    public function test_conversation_assignment_workflow(): void
    {
        // Create unassigned conversation
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
            'subject' => 'Unassigned inquiry',
            'user_id' => null,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $this->assertNull($conversation->user_id);

        // Admin assigns to agent
        $response = $this->actingAs($this->admin)
            ->patch(route('conversations.update', $conversation), [
                'user_id' => $this->agent->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'user_id' => $this->agent->id,
        ]);

        // Agent views assigned conversation
        $response = $this->actingAs($this->agent)
            ->get(route('conversations.show', $conversation));
        $response->assertOk();
        $response->assertSee('Unassigned inquiry');

        // Agent can reassign to admin
        $response = $this->actingAs($this->agent)
            ->patch(route('conversations.update', $conversation), [
                'user_id' => $this->admin->id,
            ]);

        $response->assertRedirect();
        $conversation->refresh();
        $this->assertEquals($this->admin->id, $conversation->user_id);
    }

    /**
     * Test 4: Multi-user collaboration workflow
     */
    public function test_multi_user_collaboration_workflow(): void
    {
        // Create conversation assigned to agent
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
            'subject' => 'Collaborative support case',
            'user_id' => $this->agent->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        // Agent adds initial response
        $agentThread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => 1, // Message type
            'body' => 'I am looking into this issue.',
            'created_by_user_id' => $this->agent->id,
            'state' => 2, // Published
        ]);

        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'created_by_user_id' => $this->agent->id,
        ]);

        // Admin adds note (internal communication)
        $response = $this->actingAs($this->admin)
            ->post(route('conversations.reply', $conversation), [
                'body' => 'Please check the logs for this customer.',
                'type' => 2, // Internal note type
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'body' => 'Please check the logs for this customer.',
            'type' => 2, // Internal note type
            'created_by_user_id' => $this->admin->id,
        ]);

        // Agent can see admin's note
        $response = $this->actingAs($this->agent)
            ->get(route('conversations.show', $conversation));
        $response->assertOk();
        $response->assertSee('Please check the logs for this customer.');

        // Verify both users contributed
        $threads = Thread::where('conversation_id', $conversation->id)->get();
        $userIds = $threads->pluck('created_by_user_id')->unique();
        $this->assertCount(2, $userIds);
        $this->assertContains($this->agent->id, $userIds);
        $this->assertContains($this->admin->id, $userIds);
    }

    /**
     * Test 5: Email threading workflow
     */
    public function test_email_threading_workflow(): void
    {
        // Create parent conversation
        $parentConversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
            'subject' => 'Original Issue',
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        // Add threads to conversation
        $thread1 = Thread::factory()->create([
            'conversation_id' => $parentConversation->id,
            'type' => 1, // Message type
            'body' => 'First message in thread',
            'state' => 2, // Published
        ]);

        $thread2 = Thread::factory()->create([
            'conversation_id' => $parentConversation->id,
            'type' => 1, // Message type
            'body' => 'Reply in thread',
            'state' => 2, // Published
        ]);

        $thread3 = Thread::factory()->create([
            'conversation_id' => $parentConversation->id,
            'type' => 1, // Message type
            'body' => 'Follow-up in thread',
            'state' => 2, // Published
        ]);

        // Verify threads are linked to conversation
        $threads = Thread::where('conversation_id', $parentConversation->id)
            ->orderBy('created_at')
            ->get();

        $this->assertCount(3, $threads);
        $this->assertEquals('First message in thread', $threads[0]->body);
        $this->assertEquals('Reply in thread', $threads[1]->body);
        $this->assertEquals('Follow-up in thread', $threads[2]->body);

        // View conversation with all threads
        $response = $this->actingAs($this->agent)
            ->get(route('conversations.show', $parentConversation));

        $response->assertOk();
        $response->assertSee('First message in thread');
        $response->assertSee('Reply in thread');
        $response->assertSee('Follow-up in thread');
    }

    /**
     * Test 6: Attachment handling workflow
     */
    public function test_attachment_handling_workflow(): void
    {
        Storage::fake('public');

        // Create conversation
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
            'subject' => 'Issue with screenshot',
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        // Create thread with attachment
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => 1, // Message type
            'body' => 'Please see attached screenshot',
            'state' => 2, // Published
        ]);

        // Create attachment directly (using database schema names, not model properties)
        $attachment = \DB::table('attachments')->insert([
            'thread_id' => $thread->id,
            'filename' => 'screenshot.png',
            'mime_type' => 'image/png',
            'size' => 12345,
            'inline' => false,
            'public' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify attachment is linked
        $this->assertDatabaseHas('attachments', [
            'thread_id' => $thread->id,
            'filename' => 'screenshot.png',
        ]);

        // View conversation with attachment
        $response = $this->actingAs($this->agent)
            ->get(route('conversations.show', $conversation));

        $response->assertOk();
        // Verify attachment exists in database
        $attachmentCount = \DB::table('attachments')
            ->where('thread_id', $thread->id)
            ->count();
        $this->assertEquals(1, $attachmentCount);
    }

    /**
     * Test 7: User authentication → dashboard → conversation workflow
     */
    public function test_user_authentication_to_conversation_workflow(): void
    {
        // Step 1: User logs in
        $response = $this->post(route('login'), [
            'email' => $this->agent->email,
            'password' => 'password', // Default factory password
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // Step 2: User views dashboard
        $response = $this->actingAs($this->agent)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('mailboxes');

        // Step 3: Create conversations in mailbox
        $conversation1 = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'user_id' => $this->agent->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $conversation2 = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        // Step 4: User navigates to conversations list
        $response = $this->actingAs($this->agent)
            ->get(route('conversations.index', $this->mailbox));

        $response->assertOk();
        $response->assertViewHas('conversations');

        // Step 5: User opens specific conversation
        $response = $this->actingAs($this->agent)
            ->get(route('conversations.show', $conversation1));

        $response->assertOk();
        $response->assertViewIs('conversations.show');
        $response->assertViewHas('conversation');

        // Step 6: User replies to conversation
        $response = $this->actingAs($this->agent)
            ->post(route('conversations.reply', $conversation1), [
                'body' => 'Working on this issue now.',
                'to' => [$this->customerEmail->email],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation1->id,
            'body' => 'Working on this issue now.',
        ]);

        // Step 7: User logs out
        $response = $this->actingAs($this->agent)
            ->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /**
     * Test 8: Settings update → system impact workflow
     */
    public function test_settings_update_affects_system_behavior(): void
    {
        // Initial mailbox settings
        $originalName = $this->mailbox->name;
        $originalEmail = $this->mailbox->email;

        // Admin updates mailbox settings
        $response = $this->actingAs($this->admin)
            ->put(route('mailboxes.update', $this->mailbox), [
                'name' => 'Updated Support Mailbox',
                'email' => 'updated-support@example.com',
            ]);

        $response->assertRedirect();

        // Verify settings were updated
        $this->assertDatabaseHas('mailboxes', [
            'id' => $this->mailbox->id,
            'name' => 'Updated Support Mailbox',
            'email' => 'updated-support@example.com',
        ]);

        // Verify updated settings affect display
        $this->mailbox->refresh();
        $this->assertEquals('Updated Support Mailbox', $this->mailbox->name);
        $this->assertEquals('updated-support@example.com', $this->mailbox->email);

        // Create new conversation in updated mailbox
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $this->customer->id,
            'subject' => 'New conversation after update',
        ]);

        // Verify conversation shows updated mailbox info
        $response = $this->actingAs($this->agent)
            ->get(route('conversations.show', $conversation));

        $response->assertOk();
        $response->assertSee('Updated Support Mailbox');

        // Verify mailbox list shows updated name
        $response = $this->actingAs($this->agent)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Updated Support Mailbox');
        // Verify the original name was replaced (not just prepended)
        $this->assertNotEquals($originalName, $this->mailbox->fresh()->name);
    }
}
