<?php

declare(strict_types=1);

namespace Tests\Unit\Observers;

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

    public function test_it_creates_admin_personal_folders_when_admin_user_created()
    {
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Should have personal folders for both mailboxes
        $this->assertEquals(2, Folder::where('user_id', $admin->id)->count());

        $folder1 = Folder::where('user_id', $admin->id)
            ->where('mailbox_id', $mailbox1->id)
            ->first();

        $this->assertNotNull($folder1);
        $this->assertEquals(Folder::TYPE_MINE, $folder1->type);
        $this->assertEquals('My Conversations', $folder1->name);
    }

    public function test_it_does_not_create_personal_folders_for_non_admin_users()
    {
        Mailbox::factory()->count(2)->create();

        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->assertEquals(0, Folder::where('user_id', $user->id)->count());
    }

    public function test_it_adds_default_subscriptions_when_user_created()
    {
        $user = User::factory()->create();

        // Should have 2 default subscriptions
        $this->assertEquals(2, Subscription::where('user_id', $user->id)->count());

        // Check for assigned conversation subscription
        $assignedSub = Subscription::where('user_id', $user->id)
            ->where('medium', Subscription::MEDIUM_EMAIL)
            ->where('event', Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME)
            ->first();

        $this->assertNotNull($assignedSub);

        // Check for followed conversation subscription
        $followedSub = Subscription::where('user_id', $user->id)
            ->where('medium', Subscription::MEDIUM_EMAIL)
            ->where('event', Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED)
            ->first();

        $this->assertNotNull($followedSub);
    }

    public function test_it_deletes_user_personal_folders_when_user_deleted()
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $folder = Folder::where('user_id', $user->id)->first();
        $this->assertNotNull($folder);

        $folderId = $folder->id;

        $user->delete();

        $this->assertDatabaseMissing('folders', ['id' => $folderId]);
    }

    public function test_it_detaches_user_from_followed_conversations_when_deleted()
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create();

        $conversation->followers()->attach($user->id);

        $user->delete();

        $this->assertDatabaseMissing('followers', [
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
        ]);
    }

    public function test_it_unassigns_user_from_conversations_when_deleted()
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $conversation->user_id);

        $user->delete();

        $this->assertNull($conversation->fresh()->user_id);
    }

    public function test_it_unassigns_multiple_conversations_when_user_deleted()
    {
        $user = User::factory()->create();
        $conversation1 = Conversation::factory()->create(['user_id' => $user->id]);
        $conversation2 = Conversation::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertNull($conversation1->fresh()->user_id);
        $this->assertNull($conversation2->fresh()->user_id);
    }
}
