<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Tests\UnitTestCase;

class UserMethodsTest extends UnitTestCase
{
    protected User $user;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->mailbox = Mailbox::factory()->create();
    }

    // ===== scopeNonDeleted tests =====

    public function test_scope_non_deleted_excludes_deleted_users(): void
    {
        $activeUser = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $deletedUser = User::factory()->create(['status' => User::STATUS_DELETED]);
        $inactiveUser = User::factory()->create(['status' => User::STATUS_INACTIVE]);

        $nonDeleted = User::nonDeleted()->get();

        $this->assertTrue($nonDeleted->contains('id', $activeUser->id));
        $this->assertTrue($nonDeleted->contains('id', $inactiveUser->id));
        $this->assertFalse($nonDeleted->contains('id', $deletedUser->id));
    }

    public function test_scope_non_deleted_returns_only_non_deleted(): void
    {
        User::factory()->count(3)->create(['status' => User::STATUS_ACTIVE]);
        User::factory()->count(2)->create(['status' => User::STATUS_DELETED]);

        // +1 from setUp
        $total = User::count();
        $nonDeleted = User::nonDeleted()->count();

        $this->assertEquals(4, $nonDeleted); // 3 active + 1 from setUp
        $this->assertEquals(6, $total); // All including deleted
    }

    // ===== followConversation tests =====

    public function test_follow_conversation_adds_to_followed(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();

        $this->user->followConversation($conversation);

        $this->assertTrue($this->user->followedConversations()->where('conversation_id', $conversation->id)->exists());
    }

    public function test_follow_conversation_does_not_duplicate(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();

        $this->user->followConversation($conversation);
        $this->user->followConversation($conversation);

        $this->assertEquals(1, $this->user->followedConversations()->where('conversation_id', $conversation->id)->count());
    }

    // ===== unfollowConversation tests =====

    public function test_unfollow_conversation_removes_from_followed(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();
        $this->user->followConversation($conversation);

        $this->user->unfollowConversation($conversation);

        $this->assertFalse($this->user->followedConversations()->where('conversation_id', $conversation->id)->exists());
    }

    public function test_unfollow_conversation_does_nothing_if_not_following(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();

        // Should not throw an exception
        $this->user->unfollowConversation($conversation);

        $this->assertFalse($this->user->followedConversations()->where('conversation_id', $conversation->id)->exists());
    }

    // ===== isFollowingConversation tests =====

    public function test_is_following_conversation_returns_true_when_following(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();
        $this->user->followConversation($conversation);

        $this->assertTrue($this->user->isFollowingConversation($conversation));
    }

    public function test_is_following_conversation_returns_false_when_not_following(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();

        $this->assertFalse($this->user->isFollowingConversation($conversation));
    }

    public function test_is_following_conversation_only_checks_own_follows(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();
        $otherUser = User::factory()->create();
        $otherUser->followConversation($conversation);

        $this->assertFalse($this->user->isFollowingConversation($conversation));
        $this->assertTrue($otherUser->isFollowingConversation($conversation));
    }

    // ===== generateRandomPassword tests =====

    public function test_generate_random_password_returns_string(): void
    {
        $password = User::generateRandomPassword();

        $this->assertIsString($password);
    }

    public function test_generate_random_password_default_length(): void
    {
        $password = User::generateRandomPassword();

        $this->assertEquals(16, strlen($password));
    }

    public function test_generate_random_password_custom_length(): void
    {
        $password = User::generateRandomPassword(32);

        $this->assertEquals(32, strlen($password));
    }

    public function test_generate_random_password_short_length(): void
    {
        $password = User::generateRandomPassword(8);

        $this->assertEquals(8, strlen($password));
    }

    public function test_generate_random_password_generates_unique_passwords(): void
    {
        $passwords = [];
        for ($i = 0; $i < 10; $i++) {
            $passwords[] = User::generateRandomPassword();
        }

        $uniquePasswords = array_unique($passwords);
        $this->assertCount(10, $uniquePasswords);
    }

    // ===== sendInvite tests =====

    public function test_send_invite_updates_invite_state_on_success(): void
    {
        // Mock the mail facade
        \Illuminate\Support\Facades\Mail::fake();

        $inviteUser = User::factory()->create([
            'invite_state' => User::INVITE_STATE_NOT_INVITED,
            'invite_hash' => 'test-hash-' . uniqid(),
        ]);

        $result = $inviteUser->sendInvite();

        $this->assertTrue($result);
        $inviteUser->refresh();
        $this->assertEquals(User::INVITE_STATE_SENT, $inviteUser->invite_state);
    }

    public function test_send_invite_sends_mail(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $inviteUser = User::factory()->create([
            'invite_hash' => 'test-hash-' . uniqid(),
        ]);

        $inviteUser->sendInvite();

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\UserInvite::class, function ($mail) use ($inviteUser) {
            return $mail->hasTo($inviteUser->email);
        });
    }

    // ===== isDeleted tests =====

    public function test_is_deleted_returns_true_for_deleted_user(): void
    {
        $deletedUser = User::factory()->create(['status' => User::STATUS_DELETED]);

        $this->assertTrue($deletedUser->isDeleted());
    }

    public function test_is_deleted_returns_false_for_active_user(): void
    {
        $activeUser = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $this->assertFalse($activeUser->isDeleted());
    }

    public function test_is_deleted_returns_false_for_inactive_user(): void
    {
        $inactiveUser = User::factory()->create(['status' => User::STATUS_INACTIVE]);

        $this->assertFalse($inactiveUser->isDeleted());
    }

    // ===== canManageMailbox tests =====

    public function test_can_manage_mailbox_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->assertTrue($admin->canManageMailbox($this->mailbox->id));
    }

    public function test_can_manage_mailbox_returns_false_for_user_without_access(): void
    {
        $regularUser = User::factory()->create(['role' => User::ROLE_USER]);

        $this->assertFalse($regularUser->canManageMailbox($this->mailbox->id));
    }

    public function test_can_manage_mailbox_returns_true_for_user_with_full_access(): void
    {
        $regularUser = User::factory()->create(['role' => User::ROLE_USER]);
        $this->mailbox->users()->attach($regularUser->id, ['access' => 2]); // Full access

        $this->assertTrue($regularUser->canManageMailbox($this->mailbox->id));
    }

    // ===== followedConversations relationship tests =====

    public function test_followed_conversations_relationship(): void
    {
        $conversation1 = Conversation::factory()->for($this->mailbox)->create();
        $conversation2 = Conversation::factory()->for($this->mailbox)->create();

        $this->user->followConversation($conversation1);
        $this->user->followConversation($conversation2);

        $followed = $this->user->followedConversations;

        $this->assertCount(2, $followed);
        $this->assertTrue($followed->contains('id', $conversation1->id));
        $this->assertTrue($followed->contains('id', $conversation2->id));
    }

    // ===== Edge cases =====

    public function test_follow_multiple_conversations(): void
    {
        $conversations = Conversation::factory()->for($this->mailbox)->count(5)->create();

        foreach ($conversations as $conversation) {
            $this->user->followConversation($conversation);
        }

        $this->assertEquals(5, $this->user->followedConversations()->count());
    }

    public function test_multiple_users_can_follow_same_conversation(): void
    {
        $conversation = Conversation::factory()->for($this->mailbox)->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $this->user->followConversation($conversation);
        $user2->followConversation($conversation);
        $user3->followConversation($conversation);

        $this->assertTrue($this->user->isFollowingConversation($conversation));
        $this->assertTrue($user2->isFollowingConversation($conversation));
        $this->assertTrue($user3->isFollowingConversation($conversation));
    }

    // ===== Status constants tests =====

    public function test_status_constants_exist(): void
    {
        $this->assertEquals(1, User::STATUS_ACTIVE);
        $this->assertEquals(2, User::STATUS_INACTIVE);
        $this->assertEquals(3, User::STATUS_DELETED);
    }

    // ===== Invite state constants tests =====

    public function test_invite_state_constants_exist(): void
    {
        $this->assertEquals(1, User::INVITE_STATE_ACTIVATED);
        $this->assertEquals(2, User::INVITE_STATE_SENT);
        $this->assertEquals(3, User::INVITE_STATE_NOT_INVITED);
    }
}
