<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_created_adds_default_subscriptions(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_EMAIL,
            'event' => Subscription::EVENT_USER_ASSIGNED,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_EMAIL,
            'event' => Subscription::EVENT_NEW_REPLY,
        ]);
    }

    public function test_created_creates_admin_personal_folders(): void
    {
        $mailbox = Mailbox::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertDatabaseHas('folders', [
            'mailbox_id' => $mailbox->id,
            'user_id' => $admin->id,
            'type' => Folder::TYPE_MINE,
        ]);
    }

    public function test_deleting_removes_personal_folders(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $user->id,
            'type' => Folder::TYPE_MINE,
        ]);

        $user->delete();

        $this->assertDatabaseMissing('folders', ['id' => $folder->id]);
    }

    public function test_deleting_detaches_followed_conversations(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();
        $conversation->followers()->attach($user->id);

        $this->assertDatabaseHas('followers', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);

        $user->delete();

        $this->assertDatabaseMissing('followers', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_deleting_unassigns_from_conversations(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $conversation->refresh();
        $this->assertNull($conversation->user_id);
    }
}
