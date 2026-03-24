<?php

declare(strict_types=1);

namespace Tests\Integration\Policies;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use App\Policies\ConversationPolicy;
use App\Policies\MailboxPolicy;
use Tests\IntegrationTestCase;

class PoliciesComprehensiveTest extends IntegrationTestCase
{
    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->user = User::factory()->create([
            'role' => User::ROLE_USER,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    // ConversationPolicy Tests
    // ========================================

    public function test_view_cached_allows_user_with_mailbox_access(): void
    {
        $policy = new ConversationPolicy;

        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($this->user->id);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        // Reload to populate relationships
        $conversation->load('mailbox.users');

        $result = $policy->viewCached($this->user, $conversation);

        $this->assertTrue($result);
    }

    public function test_view_cached_allows_admin(): void
    {
        $policy = new ConversationPolicy;

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $result = $policy->viewCached($this->admin, $conversation);

        $this->assertTrue($result);
    }

    public function test_delete_prevents_unauthorized_user_from_deleting(): void
    {
        $policy = new ConversationPolicy;

        $otherMailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $otherMailbox->id,
        ]);

        $result = $policy->delete($this->user, $conversation);

        $this->assertFalse($result);
    }

    public function test_delete_allows_user_with_mailbox_access(): void
    {
        $policy = new ConversationPolicy;

        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($this->user->id);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $result = $policy->delete($this->user, $conversation);

        $this->assertTrue($result);
    }

    public function test_view_allows_admin(): void
    {
        $policy = new ConversationPolicy;
        $conversation = Conversation::factory()->create();

        $result = $policy->view($this->admin, $conversation);

        $this->assertTrue($result);
    }

    public function test_view_allows_user_with_mailbox_access(): void
    {
        $policy = new ConversationPolicy;

        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($this->user->id);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $result = $policy->view($this->user, $conversation);

        $this->assertTrue($result);
    }

    public function test_view_denies_user_without_mailbox_access(): void
    {
        $policy = new ConversationPolicy;

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $result = $policy->view($this->user, $conversation);

        $this->assertFalse($result);
    }

    public function test_update_allows_admin(): void
    {
        $policy = new ConversationPolicy;
        $conversation = Conversation::factory()->create();

        $result = $policy->update($this->admin, $conversation);

        $this->assertTrue($result);
    }

    public function test_update_allows_user_with_mailbox_access(): void
    {
        $policy = new ConversationPolicy;

        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($this->user->id);

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $result = $policy->update($this->user, $conversation);

        $this->assertTrue($result);
    }

    public function test_update_denies_user_without_mailbox_access(): void
    {
        $policy = new ConversationPolicy;

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $result = $policy->update($this->user, $conversation);

        $this->assertFalse($result);
    }

    public function test_delete_allows_admin(): void
    {
        $policy = new ConversationPolicy;
        $conversation = Conversation::factory()->create();

        $result = $policy->delete($this->admin, $conversation);

        $this->assertTrue($result);
    }

    public function test_delete_allows_conversation_without_id(): void
    {
        $policy = new ConversationPolicy;
        $conversation = new Conversation;

        $result = $policy->delete($this->user, $conversation);

        $this->assertTrue($result);
    }

    public function test_move_allows_user_with_multiple_mailboxes(): void
    {
        $policy = new ConversationPolicy;

        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();

        $this->user->mailboxes()->attach([$mailbox1->id, $mailbox2->id]);

        $result = $policy->move($this->user);

        $this->assertTrue($result);
    }

    public function test_move_allows_when_multiple_mailboxes_exist(): void
    {
        // Clear existing mailboxes
        Mailbox::query()->delete();

        $policy = new ConversationPolicy;

        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();

        $this->user->mailboxes()->attach($mailbox1->id);

        $result = $policy->move($this->user);

        $this->assertTrue($result);
    }

    public function test_move_denies_with_single_mailbox_system(): void
    {
        // Clear existing mailboxes
        Mailbox::query()->delete();

        $policy = new ConversationPolicy;

        $mailbox = Mailbox::factory()->create();
        $this->user->mailboxes()->attach($mailbox->id);

        $result = $policy->move($this->user);

        $this->assertFalse($result);
    }

    public function test_view_cached_denies_user_without_mailbox_access(): void
    {
        $policy = new ConversationPolicy;

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        // Load the mailbox relationship
        $conversation->load('mailbox.users');

        $result = $policy->viewCached($this->user, $conversation);

        $this->assertFalse($result);
    }

    public function test_check_is_only_assigned_returns_true(): void
    {
        $policy = new ConversationPolicy;

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'user_id' => $this->user->id, // User is assignee
        ]);

        $result = $policy->checkIsOnlyAssigned($conversation, $this->user);

        $this->assertTrue($result);
    }

    public function test_check_is_only_assigned_for_creator(): void
    {
        $policy = new ConversationPolicy;

        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'created_by_user_id' => $this->user->id, // User is creator
        ]);

        $result = $policy->checkIsOnlyAssigned($conversation, $this->user);

        $this->assertTrue($result);
    }

    // ========================================
    // MailboxPolicy Tests
    // ========================================

    public function test_restore_allows_admin_to_restore_mailbox(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->restore($this->admin, $mailbox);

        $this->assertTrue($result);
    }

    public function test_restore_prevents_non_admin_from_restoring(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->restore($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_force_delete_allows_admin_to_permanently_delete(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->forceDelete($this->admin, $mailbox);

        $this->assertTrue($result);
    }

    public function test_force_delete_prevents_non_admin_from_permanently_deleting(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->forceDelete($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_restore_handles_null_user(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->restore(null, $mailbox);

        $this->assertFalse($result);
    }

    public function test_force_delete_handles_null_user(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->forceDelete(null, $mailbox);

        $this->assertFalse($result);
    }

    public function test_view_any_allows_authenticated_users(): void
    {
        $policy = new MailboxPolicy;

        $result = $policy->viewAny($this->user);

        $this->assertTrue($result);
    }

    public function test_view_any_denies_null_user(): void
    {
        $policy = new MailboxPolicy;

        $result = $policy->viewAny(null);

        $this->assertFalse($result);
    }

    public function test_mailbox_view_allows_admin(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->view($this->admin, $mailbox);

        $this->assertTrue($result);
    }

    public function test_view_allows_user_with_view_access(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        // Attach user with VIEW access
        $this->user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxPolicy::ACCESS_VIEW,
        ]);

        // Reload to get pivot data
        $this->user->load('mailboxes');

        $result = $policy->view($this->user, $mailbox);

        $this->assertTrue($result);
    }

    public function test_view_denies_user_without_access(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->view($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_view_denies_null_user(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->view(null, $mailbox);

        $this->assertFalse($result);
    }

    public function test_create_allows_admin(): void
    {
        $policy = new MailboxPolicy;

        $result = $policy->create($this->admin);

        $this->assertTrue($result);
    }

    public function test_create_denies_non_admin(): void
    {
        $policy = new MailboxPolicy;

        $result = $policy->create($this->user);

        $this->assertFalse($result);
    }

    public function test_create_denies_null_user(): void
    {
        $policy = new MailboxPolicy;

        $result = $policy->create(null);

        $this->assertFalse($result);
    }

    public function test_mailbox_update_allows_admin(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->update($this->admin, $mailbox);

        $this->assertTrue($result);
    }

    public function test_update_allows_user_with_admin_access(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        // Attach user with ADMIN access
        $this->user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxPolicy::ACCESS_ADMIN,
        ]);

        // Reload to get pivot data
        $this->user->load('mailboxes');

        $result = $policy->update($this->user, $mailbox);

        $this->assertTrue($result);
    }

    public function test_update_denies_user_with_reply_access_only(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        // Attach user with REPLY access (not enough for update)
        $this->user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxPolicy::ACCESS_REPLY,
        ]);

        // Reload to get pivot data
        $this->user->load('mailboxes');

        $result = $policy->update($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_update_denies_user_without_access(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->update($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_update_denies_null_user(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->update(null, $mailbox);

        $this->assertFalse($result);
    }

    public function test_mailbox_delete_allows_admin(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->delete($this->admin, $mailbox);

        $this->assertTrue($result);
    }

    public function test_delete_denies_non_admin(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->delete($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_delete_denies_null_user(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->delete(null, $mailbox);

        $this->assertFalse($result);
    }

    public function test_reply_allows_admin(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->reply($this->admin, $mailbox);

        $this->assertTrue($result);
    }

    public function test_reply_allows_user_with_reply_access(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        // Attach user with REPLY access
        $this->user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxPolicy::ACCESS_REPLY,
        ]);

        // Reload to get pivot data
        $this->user->load('mailboxes');

        $result = $policy->reply($this->user, $mailbox);

        $this->assertTrue($result);
    }

    public function test_reply_denies_user_with_view_access_only(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        // Attach user with VIEW access (not enough for reply)
        $this->user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxPolicy::ACCESS_VIEW,
        ]);

        // Reload to get pivot data
        $this->user->load('mailboxes');

        $result = $policy->reply($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_reply_denies_user_without_access(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->reply($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_reply_denies_null_user(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->reply(null, $mailbox);

        $this->assertFalse($result);
    }

    public function test_admin_policy_allows_admin_user(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->admin($this->admin, $mailbox);

        $this->assertTrue($result);
    }

    public function test_admin_policy_allows_user_with_admin_access(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        // Attach user with ADMIN access
        $this->user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxPolicy::ACCESS_ADMIN,
        ]);

        // Reload to get pivot data
        $this->user->load('mailboxes');

        $result = $policy->admin($this->user, $mailbox);

        $this->assertTrue($result);
    }

    public function test_admin_policy_denies_user_with_reply_access(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        // Attach user with REPLY access (not enough for admin operations)
        $this->user->mailboxes()->attach($mailbox->id, [
            'access' => MailboxPolicy::ACCESS_REPLY,
        ]);

        // Reload to get pivot data
        $this->user->load('mailboxes');

        $result = $policy->admin($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_admin_policy_denies_user_without_access(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->admin($this->user, $mailbox);

        $this->assertFalse($result);
    }

    public function test_admin_policy_denies_null_user(): void
    {
        $policy = new MailboxPolicy;
        $mailbox = Mailbox::factory()->create();

        $result = $policy->admin(null, $mailbox);

        $this->assertFalse($result);
    }

    // ========================================
}
