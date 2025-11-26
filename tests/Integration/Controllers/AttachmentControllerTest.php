<?php

declare(strict_types=1);

namespace Tests\Integration\Controllers;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\IntegrationTestCase;

class AttachmentControllerTest extends IntegrationTestCase
{
    public function test_download_returns_response_for_authorized_user(): void
    {
        Storage::fake('attachments');

        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $conversation = Conversation::factory()->for($mailbox)->create();
        $thread = Thread::factory()->for($conversation)->create();
        $attachment = Attachment::factory()->for($thread)->create();

        Storage::disk('attachments')->put($attachment->file_dir . '/' . $attachment->file_name, 'content');

        $response = $this->actingAs($user)->get("/attachments/{$attachment->id}/download");

        $response->assertStatus(200);
    }

    public function test_download_denies_unauthorized_user(): void
    {
        Storage::fake('attachments');

        $user = User::factory()->create();
        $otherMailbox = Mailbox::factory()->create();
        
        $conversation = Conversation::factory()->for($otherMailbox)->create();
        $thread = Thread::factory()->for($conversation)->create();
        $attachment = Attachment::factory()->for($thread)->create();

        Storage::disk('attachments')->put($attachment->file_dir . '/' . $attachment->file_name, 'content');

        $response = $this->actingAs($user)->get("/attachments/{$attachment->id}/download");

        $response->assertStatus(403);
    }

    public function test_download_allows_admin_user_regardless_of_mailbox(): void
    {
        Storage::fake('attachments');

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $conversation = Conversation::factory()->for($mailbox)->create();
        $thread = Thread::factory()->for($conversation)->create();
        $attachment = Attachment::factory()->for($thread)->create();

        Storage::disk('attachments')->put($attachment->file_dir . '/' . $attachment->file_name, 'content');

        $response = $this->actingAs($admin)->get("/attachments/{$attachment->id}/download");

        $response->assertStatus(200);
    }

    public function test_download_returns_404_for_missing_attachment(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get("/attachments/999999/download");

        $response->assertStatus(404);
    }

    public function test_download_requires_authentication(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->for($mailbox)->create();
        $thread = Thread::factory()->for($conversation)->create();
        $attachment = Attachment::factory()->for($thread)->create();

        $response = $this->get("/attachments/{$attachment->id}/download");

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
}
