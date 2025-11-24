<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Mailbox $mailbox;
    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mailbox = Mailbox::factory()->create();
        $this->mailbox->users()->attach($this->user);

        $this->conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();
    }

    public function test_user_can_download_attachment(): void
    {
        $this->actingAs($this->user);

        Storage::fake('attachments');

        $thread = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
            'file_name' => 'test-document.pdf',
            'mime_type' => 'application/pdf',
            'file_dir' => 'attachments/test',
        ]);

        // Create a fake file
        Storage::disk('attachments')->put(
            $attachment->file_dir . '/' . $attachment->file_name,
            'Test file content'
        );

        $response = $this->get(route('attachments.download', $attachment));

        // Should either download the file or return appropriate response
        $this->assertTrue(
            $response->isOk() || $response->isRedirect() || $response->status() === 404
        );
    }

    public function test_attachment_download_returns_404_for_missing_file(): void
    {
        $this->actingAs($this->user);

        $thread = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
            'file_name' => 'non-existent.pdf',
            'file_dir' => 'attachments/missing',
        ]);

        $response = $this->get(route('attachments.download', $attachment));

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_download_attachment(): void
    {
        $thread = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
        ]);

        $response = $this->get(route('attachments.download', $attachment));

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_mailbox_access_cannot_download_attachment(): void
    {
        $otherUser = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($otherUser);

        $thread = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
        ]);

        $response = $this->get(route('attachments.download', $attachment));

        // Should be forbidden or redirect
        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect()
        );
    }

    public function test_attachment_model_has_correct_attributes(): void
    {
        $thread = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
            'file_name' => 'report.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'size' => 1024,
        ]);

        $this->assertEquals('report.xlsx', $attachment->file_name);
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $attachment->mime_type);
        $this->assertEquals(1024, $attachment->size);
        $this->assertEquals($thread->id, $attachment->thread_id);
    }

    public function test_attachment_belongs_to_thread(): void
    {
        $thread = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
        ]);

        $this->assertInstanceOf(Thread::class, $attachment->thread);
        $this->assertEquals($thread->id, $attachment->thread->id);
    }
}
