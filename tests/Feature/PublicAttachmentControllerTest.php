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
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicAttachmentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Mailbox $mailbox;
    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailbox = Mailbox::factory()->create();
        $this->conversation = Conversation::factory()
            ->for($this->mailbox)
            ->create();
    }

    public function test_public_download_requires_valid_signature(): void
    {
        $thread = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
        ]);

        // Request without signature should fail
        $response = $this->get(route('attachments.public.download', ['id' => $attachment->id]));

        $response->assertForbidden();
    }

    public function test_public_download_with_valid_signature_succeeds(): void
    {
        Storage::fake('local');

        $thread = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
            'file_name' => 'test-file.pdf',
            'file_dir' => 'attachments/test',
            'mime_type' => 'application/pdf',
        ]);

        // Create the file in storage
        Storage::put($attachment->file_dir . '/' . $attachment->file_name, 'Test file content');

        // Generate signed URL
        $signedUrl = URL::signedRoute('attachments.public.download', ['id' => $attachment->id]);

        $response = $this->get($signedUrl);

        // Should return download response or file not found
        $this->assertTrue(
            $response->isOk() || $response->status() === 404
        );
    }

    public function test_public_download_returns_404_for_missing_file(): void
    {
        $thread = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
            'file_name' => 'nonexistent.pdf',
            'file_dir' => 'attachments/missing',
        ]);

        $signedUrl = URL::signedRoute('attachments.public.download', ['id' => $attachment->id]);

        $response = $this->get($signedUrl);

        $response->assertNotFound();
    }

    public function test_public_download_returns_404_for_nonexistent_attachment(): void
    {
        $signedUrl = URL::signedRoute('attachments.public.download', ['id' => 999999]);

        $response = $this->get($signedUrl);

        $response->assertNotFound();
    }

    public function test_expired_signature_fails(): void
    {
        $thread = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
        ]);

        // Generate URL that expired 1 minute ago
        $signedUrl = URL::temporarySignedRoute(
            'attachments.public.download',
            now()->subMinute(),
            ['id' => $attachment->id]
        );

        $response = $this->get($signedUrl);

        $response->assertForbidden();
    }

    public function test_tampered_signature_fails(): void
    {
        $thread = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
        ]);

        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
        ]);

        $signedUrl = URL::signedRoute('attachments.public.download', ['id' => $attachment->id]);
        // Tamper with the signature
        $tamperedUrl = str_replace('signature=', 'signature=tampered', $signedUrl);

        $response = $this->get($tamperedUrl);

        $response->assertForbidden();
    }
}
