<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Tests\UnitTestCase;

class UserRelationshipsTest extends UnitTestCase
{

    public function test_user_has_many_mailboxes(): void
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();

        $user->mailboxes()->attach([$mailbox1->id, $mailbox2->id]);

        $this->assertCount(2, $user->mailboxes);
        $this->assertInstanceOf(Mailbox::class, $user->mailboxes->first());
    }

    public function test_user_has_many_conversations(): void
    {
        $user = User::factory()->create();
        Conversation::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->conversations);
        $this->assertInstanceOf(Conversation::class, $user->conversations->first());
    }

    public function test_user_is_admin_returns_true_for_admin_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($admin->isAdmin());
    }

    public function test_user_is_admin_returns_false_for_regular_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->assertFalse($user->isAdmin());
    }

    public function test_user_can_have_no_mailboxes(): void
    {
        $user = User::factory()->create();

        $this->assertCount(0, $user->mailboxes);
    }

    public function test_user_mailbox_relationship_is_many_to_many(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $user->mailboxes()->attach($mailbox->id);

        // Verify pivot table works
        $this->assertTrue($user->mailboxes->contains($mailbox));
        $this->assertDatabaseHas('mailbox_user', [
            'user_id' => $user->id,
            'mailbox_id' => $mailbox->id,
        ]);
    }

    public function test_user_can_be_detached_from_mailbox(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $user->mailboxes()->attach($mailbox->id);
        $this->assertCount(1, $user->fresh()->mailboxes);

        $user->mailboxes()->detach($mailbox->id);
        $this->assertCount(0, $user->fresh()->mailboxes);
    }

    public function test_multiple_users_can_share_same_mailbox(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $mailbox = Mailbox::factory()->create();

        $user1->mailboxes()->attach($mailbox->id);
        $user2->mailboxes()->attach($mailbox->id);

        $this->assertTrue($user1->mailboxes->contains($mailbox));
        $this->assertTrue($user2->mailboxes->contains($mailbox));
    }

    public function test_user_eager_loads_mailboxes_relationship(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $user->mailboxes()->attach($mailbox->id);

        $loaded = User::with('mailboxes')->find($user->id);

        $this->assertTrue($loaded->relationLoaded('mailboxes'));
    }

    public function test_user_eager_loads_conversations_relationship(): void
    {
        $user = User::factory()->create();
        Conversation::factory()->create(['user_id' => $user->id]);

        $loaded = User::with('conversations')->find($user->id);

        $this->assertTrue($loaded->relationLoaded('conversations'));
    }
}
