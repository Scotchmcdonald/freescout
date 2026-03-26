<?php

declare(strict_types=1);

namespace Tests\Integration\Actions;

use App\Actions\ForwardConversationAction;
use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForwardConversationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_execute_creates_draft_forward_and_clones_attachments(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);
        $sourceConversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'subject' => 'Original subject',
            'number' => 550,
        ]);
        $sourceConversation->load('mailbox');
        $sourceThread = Thread::factory()->create([
            'conversation_id' => $sourceConversation->id,
            'has_attachments' => true,
            'from' => 'sender@example.com',
            'to' => ['dest@example.com'],
            'body' => 'Original body',
            'created_at' => now()->subHour(),
        ]);
        Attachment::factory()->create([
            'thread_id' => $sourceThread->id,
            'file_name' => 'report.pdf',
            'file_dir' => 'attachments/source',
        ]);
        $user = User::factory()->create();

        $newConversation = (new ForwardConversationAction)->execute(
            $sourceConversation,
            $sourceThread->fresh('attachments'),
            $user,
            ['to' => ['forward@example.com']]
        );

        $forwardDraft = $newConversation->threads()->latest('id')->first();
        $selectedFolder = Folder::find($newConversation->folder_id);

        $this->assertInstanceOf(Conversation::class, $newConversation);
        $this->assertSame('Fwd: Original subject', $newConversation->subject);
        $this->assertNotNull($selectedFolder);
        $this->assertSame($mailbox->id, $selectedFolder->mailbox_id);
        $this->assertSame(1, $selectedFolder->type);
        $this->assertSame($user->id, $newConversation->created_by_user_id);
        $this->assertNotNull($forwardDraft);
        $this->assertSame(Thread::TYPE_DRAFT, $forwardDraft->type);
        $this->assertSame(Thread::STATE_DRAFT, $forwardDraft->state);
        $this->assertSame('["forward@example.com"]', $forwardDraft->to);
        $this->assertStringContainsString('---------- Forwarded message ---------', (string) $forwardDraft->body);
        $this->assertStringContainsString('Subject: Original subject', (string) $forwardDraft->body);
        $this->assertSame(1, $forwardDraft->attachments()->count());
        $this->assertDatabaseHas('attachments', [
            'thread_id' => $forwardDraft->id,
            'file_name' => 'report.pdf',
        ]);
    }

    public function test_forward_with_empty_recipient_list_creates_addressable_draft(): void
    {
        // Validation boundary: forwarding with an empty 'to' array creates a draft that must
        // be addressed before sending. The action layer intentionally defers recipient validation
        // to the transport layer (FormRequest / Mailable), so the conversation must still be
        // persisted (allowing the user to fill in recipients).
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id, 'type' => 1]);
        $sourceConversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
            'subject' => 'Boundary Test',
            'number' => 551,
        ]);
        $sourceConversation->load('mailbox');
        $sourceThread = Thread::factory()->create([
            'conversation_id' => $sourceConversation->id,
            'from' => 'sender@example.com',
            'to' => ['dest@example.com'],
            'body' => 'Original body',
        ]);
        $user = User::factory()->create();

        // Action must still produce a valid draft even with empty recipients
        $draft = (new ForwardConversationAction)->execute(
            $sourceConversation,
            $sourceThread->fresh('attachments'),
            $user,
            ['to' => []] // no recipients yet — draft stage, validation deferred to send-time
        );

        $this->assertInstanceOf(Conversation::class, $draft);
        $this->assertSame(Thread::TYPE_DRAFT, $draft->threads()->latest('id')->first()->type);
        // Boundary: to must be stored as an empty array (not null) so the transport layer can
        // validate and reject an actual send attempt without recipients
        $forwardThread = $draft->threads()->latest('id')->first();
        $this->assertNotNull($forwardThread->to, 'Validation: recipient field must be set (even if empty) to allow transport-layer validation');
    }
}
