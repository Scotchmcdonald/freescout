<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CustomerExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_attachment_download()
    {
        Storage::fake('local');
        
        $attachment = Attachment::factory()->create([
            'file_name' => 'test.txt',
            'file_dir' => 'attachments',
        ]);
        
        Storage::put('attachments/test.txt', 'content');

        $url = URL::signedRoute('attachments.public_download', ['id' => $attachment->id]);

        $response = $this->get($url);

        $response->assertStatus(200);
        $response->assertHeader('content-disposition', 'attachment; filename=test.txt');
    }

    public function test_tracking_pixel()
    {
        $thread = Thread::factory()->create(['opened_at' => null]);
        
        $url = URL::signedRoute('track.pixel', ['id' => $thread->id]);

        $response = $this->get($url);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'image/gif');
        
        $this->assertNotNull($thread->fresh()->opened_at);
    }

    public function test_user_setup_flow()
    {
        $user = User::factory()->create([
            'invite_hash' => 'some_hash',
            'invite_state' => 2, // Sent
            'status' => 2, // Inactive
        ]);

        $response = $this->get(route('user_setup', ['hash' => 'some_hash']));
        $response->assertStatus(200);

        $response = $this->post(route('user_setup.save', ['hash' => 'some_hash']), [
            'email' => $user->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'timezone' => 'UTC',
            'time_format' => 24,
        ]);

        $response->assertRedirect(route('dashboard'));
        
        $user->refresh();
        $this->assertNull($user->invite_hash);
        $this->assertEquals(1, $user->invite_state);
    }

    public function test_undo_send()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);
        
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'created_at' => now(),
            'type' => 1, // Message
            'state' => 2, // Published
        ]);

        $response = $this->actingAs($user)
            ->post(route('conversations.undo_send', ['conversation' => $conversation->id, 'thread' => $thread->id]));

        $response->assertRedirect();
        
        $thread->refresh();
        $this->assertEquals(Thread::STATE_DRAFT, $thread->state);
    }

    public function test_forward_conversation()
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);
        
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => 'Original message',
            'from' => 'customer@example.com',
        ]);

        $response = $this->actingAs($user)
            ->post(route('conversations.forward', ['conversation' => $conversation->id, 'thread' => $thread->id]));

        $response->assertRedirect();
        
        // Check if new conversation was created
        $newConversation = Conversation::where('subject', 'Fwd: ' . $conversation->subject)->first();
        $this->assertNotNull($newConversation);
        $this->assertEquals(1, $newConversation->state); // Draft
        
        // Check if body contains forwarded content
        $newThread = $newConversation->threads()->first();
        $this->assertStringContainsString('Original message', $newThread->body);
        $this->assertStringContainsString('Forwarded message', $newThread->body);
    }
}
