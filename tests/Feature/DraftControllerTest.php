<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DraftControllerTest extends TestCase
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

    public function test_save_draft_creates_new_draft(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('drafts.save'), [
            'conversation_id' => $this->conversation->id,
            'body' => 'This is a draft message',
            'to' => ['customer@example.com'],
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertDatabaseHas('threads', [
            'conversation_id' => $this->conversation->id,
            'body' => 'This is a draft message',
            'state' => Thread::STATE_DRAFT,
            'created_by_user_id' => $this->user->id,
        ]);
    }

    public function test_save_draft_updates_existing_draft(): void
    {
        $this->actingAs($this->user);

        // Create initial draft
        $draft = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_by_user_id' => $this->user->id,
            'state' => Thread::STATE_DRAFT,
            'body' => 'Original draft',
        ]);

        $response = $this->postJson(route('drafts.save'), [
            'thread_id' => $draft->id,
            'body' => 'Updated draft message',
            'to' => ['customer@example.com'],
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
        ]);

        $draft->refresh();
        $this->assertEquals('Updated draft message', $draft->body);
    }

    public function test_discard_draft_deletes_draft(): void
    {
        $this->actingAs($this->user);

        $draft = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_by_user_id' => $this->user->id,
            'state' => Thread::STATE_DRAFT,
        ]);

        $response = $this->postJson(route('drafts.discard'), [
            'thread_id' => $draft->id,
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
        ]);

        $this->assertDatabaseMissing('threads', [
            'id' => $draft->id,
        ]);
    }

    public function test_cannot_discard_another_users_draft(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $draft = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_by_user_id' => $this->user->id,
            'state' => Thread::STATE_DRAFT,
        ]);

        $response = $this->postJson(route('drafts.discard'), [
            'thread_id' => $draft->id,
        ]);

        $response->assertStatus(500);

        $this->assertDatabaseHas('threads', [
            'id' => $draft->id,
        ]);
    }

    public function test_save_draft_requires_authentication(): void
    {
        $response = $this->postJson(route('drafts.save'), [
            'conversation_id' => $this->conversation->id,
            'body' => 'This is a draft message',
        ]);

        $response->assertUnauthorized();
    }

    public function test_discard_draft_requires_authentication(): void
    {
        $draft = Thread::factory()->create([
            'conversation_id' => $this->conversation->id,
            'created_by_user_id' => $this->user->id,
            'state' => Thread::STATE_DRAFT,
        ]);

        $response = $this->postJson(route('drafts.discard'), [
            'thread_id' => $draft->id,
        ]);

        $response->assertUnauthorized();
    }

    public function test_save_draft_with_cc_and_bcc(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('drafts.save'), [
            'conversation_id' => $this->conversation->id,
            'body' => 'Draft with cc and bcc',
            'to' => ['customer@example.com'],
            'cc' => ['cc@example.com'],
            'bcc' => ['bcc@example.com'],
        ]);

        $response->assertOk();
        $response->assertJson([
            'status' => 'success',
        ]);
    }

    public function test_discard_nonexistent_draft_fails(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson(route('drafts.discard'), [
            'thread_id' => 999999,
        ]);

        $response->assertStatus(500);
    }
}
