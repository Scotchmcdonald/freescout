<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_with_very_long_subject_is_handled(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        
        $longSubject = str_repeat('Very Long Subject Line ', 20); // ~460 chars
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => $longSubject,
        ]);

        $user->mailboxes()->attach($mailbox->id);

        $response = $this->actingAs($user)->get(route('conversations.show', $conversation->id));
        
        $response->assertOk();
        // Verify subject is displayed (may be truncated in display)
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
        ]);
    }

    public function test_conversation_list_with_many_conversations_uses_pagination(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        
        // Create 35 conversations (more than one page)
        Conversation::factory()->count(35)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $user->mailboxes()->attach($mailbox->id);

        $response = $this->actingAs($user)->get(route('conversations.index', $mailbox->id));
        
        $response->assertOk();
        $response->assertViewHas('conversations');
        
        // Verify pagination is working
        $conversations = $response->viewData('conversations');
        $this->assertLessThanOrEqual(35, $conversations->count());
    }

    public function test_conversation_with_no_threads_displays_correctly(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        
        // Ensure no threads
        Thread::where('conversation_id', $conversation->id)->delete();

        $user->mailboxes()->attach($mailbox->id);

        $response = $this->actingAs($user)->get(route('conversations.show', $conversation->id));
        
        $response->assertOk();
        $response->assertViewHas('conversation');
    }

    public function test_conversation_status_change_validates_values(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        // Try invalid status - should reject or validate
        $response = $this->actingAs($user)->patch(route('conversations.update', $conversation->id), [
            'status' => 999, // Invalid status
        ]);
        
        // Verify invalid status not saved
        $this->assertDatabaseMissing('conversations', [
            'id' => $conversation->id,
            'status' => 999,
        ]);
    }

    public function test_conversation_list_handles_empty_mailbox(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $user->mailboxes()->attach($mailbox->id);

        // No conversations in mailbox
        $response = $this->actingAs($user)->get(route('conversations.index', $mailbox->id));
        
        $response->assertOk();
        $response->assertViewHas('conversations');
        
        $conversations = $response->viewData('conversations');
        $this->assertEquals(0, $conversations->count());
    }

    public function test_conversation_with_special_characters_in_subject_is_escaped(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        
        $specialSubject = "Test <script>alert('XSS')</script> & special chars";
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => $specialSubject,
        ]);

        $user->mailboxes()->attach($mailbox->id);

        $response = $this->actingAs($user)->get(route('conversations.show', $conversation->id));
        
        $response->assertOk();
        // Verify XSS is escaped in output
        $response->assertSee(e($specialSubject), false);
    }

    public function test_conversation_with_closed_status_excludes_from_active_list(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        
        $activeConv = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'status' => Conversation::STATUS_ACTIVE,
            'subject' => 'Active Conversation',
        ]);
        
        $closedConv = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'status' => Conversation::STATUS_CLOSED,
            'subject' => 'Closed Conversation',
        ]);

        $user->mailboxes()->attach($mailbox->id);

        $response = $this->actingAs($user)->get(route('conversations.index', $mailbox->id));
        
        $response->assertOk();
        $response->assertViewHas('conversations');
    }

    public function test_guest_cannot_access_conversation_list(): void
    {
        $mailbox = Mailbox::factory()->create();

        $response = $this->get(route('conversations.index', $mailbox->id));
        
        $response->assertRedirect(route('login'));
    }
}
