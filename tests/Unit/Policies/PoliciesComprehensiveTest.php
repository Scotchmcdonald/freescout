<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use App\Policies\ConversationPolicy;
use App\Policies\FolderPolicy;
use App\Policies\MailboxPolicy;
use App\Policies\ThreadPolicy;
use App\Policies\UserPolicy;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for Policy Classes
 * Following TESTING_GUIDE.md - using test_ prefix, UnitTestCase base class
 */
class PoliciesComprehensiveTest extends UnitTestCase
{
    // ===== THREAD_POLICY TESTS =====

    public function test_thread_policy_user_can_edit_own_message(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create([
            'type' => Thread::TYPE_MESSAGE,
            'created_by_user_id' => $user->id,
        ]);
        
        $policy = new ThreadPolicy();
        
        $this->assertTrue($policy->edit($user, $thread));
    }

    public function test_thread_policy_user_cannot_edit_other_users_message(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $otherUser = User::factory()->create();
        $thread = Thread::factory()->create([
            'type' => Thread::TYPE_MESSAGE,
            'created_by_user_id' => $otherUser->id,
        ]);
        
        $policy = new ThreadPolicy();
        
        $this->assertFalse($policy->edit($user, $thread));
    }

    public function test_thread_policy_admin_can_edit_any_message(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $otherUser = User::factory()->create();
        $thread = Thread::factory()->create([
            'type' => Thread::TYPE_MESSAGE,
            'created_by_user_id' => $otherUser->id,
        ]);
        
        $policy = new ThreadPolicy();
        
        $this->assertTrue($policy->edit($admin, $thread));
    }

    public function test_thread_policy_user_can_edit_own_note(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create([
            'type' => Thread::TYPE_NOTE,
            'created_by_user_id' => $user->id,
        ]);
        
        $policy = new ThreadPolicy();
        
        $this->assertTrue($policy->edit($user, $thread));
    }

    public function test_thread_policy_user_can_delete_own_thread(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create([
            'type' => Thread::TYPE_MESSAGE,
            'created_by_user_id' => $user->id,
        ]);
        
        $policy = new ThreadPolicy();
        
        $this->assertTrue($policy->delete($user, $thread));
    }

    public function test_thread_policy_user_cannot_delete_other_users_thread(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $otherUser = User::factory()->create();
        $thread = Thread::factory()->create([
            'type' => Thread::TYPE_MESSAGE,
            'created_by_user_id' => $otherUser->id,
        ]);
        
        $policy = new ThreadPolicy();
        
        $this->assertFalse($policy->delete($user, $thread));
    }

    public function test_thread_policy_customer_threads_can_be_edited(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create([
            'type' => Thread::TYPE_CUSTOMER,
            'created_by_customer_id' => 1,
        ]);
        
        $policy = new ThreadPolicy();
        
        $this->assertTrue($policy->edit($user, $thread));
    }

    // ===== CONVERSATION_POLICY TESTS =====

    public function test_conversation_policy_user_can_view_assigned_conversation(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        
        $policy = new ConversationPolicy();
        
        $this->assertTrue($policy->view($user, $conversation));
    }

    public function test_conversation_policy_user_cannot_view_unassigned_conversation(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        
        $policy = new ConversationPolicy();
        
        $this->assertFalse($policy->view($user, $conversation));
    }

    public function test_conversation_policy_admin_can_view_any_conversation(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $conversation = Conversation::factory()->create();
        
        $policy = new ConversationPolicy();
        
        $this->assertTrue($policy->view($admin, $conversation));
    }

    public function test_conversation_policy_user_can_update_assigned_conversation(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);
        
        $policy = new ConversationPolicy();
        
        $this->assertTrue($policy->update($user, $conversation));
    }

    // ===== MAILBOX_POLICY TESTS =====

    public function test_mailbox_policy_admin_can_view_any_mailbox(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $policy = new MailboxPolicy();
        
        $this->assertTrue($policy->view($admin, $mailbox));
    }

    public function test_mailbox_policy_user_can_view_assigned_mailbox(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $policy = new MailboxPolicy();
        
        $this->assertTrue($policy->view($user, $mailbox));
    }

    public function test_mailbox_policy_user_cannot_view_unassigned_mailbox(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $policy = new MailboxPolicy();
        
        $this->assertFalse($policy->view($user, $mailbox));
    }

    public function test_mailbox_policy_only_admin_can_create_mailbox(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $policy = new MailboxPolicy();
        
        $this->assertTrue($policy->create($admin));
        $this->assertFalse($policy->create($user));
    }

    public function test_mailbox_policy_only_admin_can_update_mailbox(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $policy = new MailboxPolicy();
        
        $this->assertTrue($policy->update($admin, $mailbox));
        $this->assertFalse($policy->update($user, $mailbox));
    }

    public function test_mailbox_policy_only_admin_can_delete_mailbox(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        
        $policy = new MailboxPolicy();
        
        $this->assertTrue($policy->delete($admin, $mailbox));
        $this->assertFalse($policy->delete($user, $mailbox));
    }

    // ===== USER_POLICY TESTS =====

    public function test_user_policy_admin_can_view_any_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $otherUser = User::factory()->create();
        
        $policy = new UserPolicy();
        
        $this->assertTrue($policy->view($admin, $otherUser));
    }

    public function test_user_policy_user_can_view_own_profile(): void
    {
        $user = User::factory()->create();
        
        $policy = new UserPolicy();
        
        $this->assertTrue($policy->view($user, $user));
    }

    public function test_user_policy_user_cannot_view_other_users(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $otherUser = User::factory()->create();
        
        $policy = new UserPolicy();
        
        $this->assertFalse($policy->view($user, $otherUser));
    }

    public function test_user_policy_only_admin_can_create_users(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $policy = new UserPolicy();
        
        $this->assertTrue($policy->create($admin));
        $this->assertFalse($policy->create($user));
    }

    public function test_user_policy_only_admin_can_delete_users(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $targetUser = User::factory()->create();
        
        $policy = new UserPolicy();
        
        $this->assertTrue($policy->delete($admin, $targetUser));
        $this->assertFalse($policy->delete($user, $targetUser));
    }

    // ===== FOLDER_POLICY TESTS =====

    public function test_folder_policy_admin_can_view_any_folder(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $policy = new FolderPolicy();
        
        $this->assertTrue($policy->view($admin, $folder));
    }

    public function test_folder_policy_user_can_view_folder_in_assigned_mailbox(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user->id);
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $policy = new FolderPolicy();
        
        $this->assertTrue($policy->view($user, $folder));
    }

    public function test_folder_policy_user_cannot_view_folder_in_unassigned_mailbox(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $policy = new FolderPolicy();
        
        $this->assertFalse($policy->view($user, $folder));
    }

    // ===== EDGE CASES =====

    public function test_thread_policy_handles_null_created_by_user_id(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create([
            'type' => Thread::TYPE_MESSAGE,
            'created_by_user_id' => null,
        ]);
        
        $policy = new ThreadPolicy();
        
        $this->assertFalse($policy->edit($user, $thread));
    }

    public function test_conversation_policy_with_multiple_mailbox_assignments(): void
    {
        $user = User::factory()->create();
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        $mailbox1->users()->attach($user->id);
        $mailbox2->users()->attach($user->id);
        
        $conversation1 = Conversation::factory()->create(['mailbox_id' => $mailbox1->id]);
        $conversation2 = Conversation::factory()->create(['mailbox_id' => $mailbox2->id]);
        
        $policy = new ConversationPolicy();
        
        $this->assertTrue($policy->view($user, $conversation1));
        $this->assertTrue($policy->view($user, $conversation2));
    }

    public function test_user_policy_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $policy = new UserPolicy();
        
        $this->assertFalse($policy->delete($admin, $admin));
    }

    public function test_mailbox_policy_consistency_across_operations(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        
        $policy = new MailboxPolicy();
        
        $this->assertTrue($policy->view($admin, $mailbox));
        $this->assertTrue($policy->update($admin, $mailbox));
        $this->assertTrue($policy->delete($admin, $mailbox));
    }
}
