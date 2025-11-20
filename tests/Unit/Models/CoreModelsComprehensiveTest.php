<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for Core Models (User, Conversation, Thread, Folder, Customer)
 * Following TESTING_GUIDE.md - using test_ prefix, UnitTestCase base class
 */
class CoreModelsComprehensiveTest extends UnitTestCase
{
    // ===== USER MODEL TESTS =====

    public function test_user_can_be_created(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', [
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);
    }

    public function test_user_is_admin_returns_true_for_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $this->assertTrue($admin->isAdmin());
    }

    public function test_user_is_admin_returns_false_for_regular_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        
        $this->assertFalse($user->isAdmin());
    }

    public function test_user_belongs_to_many_mailboxes(): void
    {
        $user = User::factory()->create();
        $mailboxes = Mailbox::factory()->count(3)->create();
        
        foreach ($mailboxes as $mailbox) {
            $mailbox->users()->attach($user->id);
        }
        
        $this->assertCount(3, $user->fresh()->mailboxes);
    }

    public function test_user_has_many_conversations(): void
    {
        $user = User::factory()->create();
        Conversation::factory()->count(5)->create(['user_id' => $user->id]);
        
        $this->assertCount(5, $user->conversations);
    }

    public function test_user_full_name_attribute(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        
        $this->assertEquals('John Doe', $user->full_name);
    }

    public function test_user_password_is_hashed(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password')]);
        
        $this->assertNotEquals('password', $user->password);
        $this->assertTrue(\Hash::check('password', $user->password));
    }

    // ===== CONVERSATION MODEL TESTS =====

    public function test_conversation_can_be_created(): void
    {
        $conversation = Conversation::factory()->create([
            'subject' => 'Test Conversation',
        ]);
        
        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertDatabaseHas('conversations', [
            'subject' => 'Test Conversation',
        ]);
    }

    public function test_conversation_belongs_to_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $this->assertInstanceOf(Mailbox::class, $conversation->mailbox);
        $this->assertEquals($mailbox->id, $conversation->mailbox->id);
    }

    public function test_conversation_belongs_to_folder(): void
    {
        $folder = Folder::factory()->create();
        $conversation = Conversation::factory()->create(['folder_id' => $folder->id]);
        
        $this->assertInstanceOf(Folder::class, $conversation->folder);
        $this->assertEquals($folder->id, $conversation->folder->id);
    }

    public function test_conversation_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        
        $this->assertInstanceOf(Customer::class, $conversation->customer);
        $this->assertEquals($customer->id, $conversation->customer->id);
    }

    public function test_conversation_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(User::class, $conversation->user);
        $this->assertEquals($user->id, $conversation->user->id);
    }

    public function test_conversation_has_many_threads(): void
    {
        $conversation = Conversation::factory()->create();
        Thread::factory()->count(5)->create(['conversation_id' => $conversation->id]);
        
        $this->assertCount(5, $conversation->threads);
    }

    public function test_conversation_is_active_status(): void
    {
        $conversation = Conversation::factory()->create(['status' => Conversation::STATUS_ACTIVE]);
        
        $this->assertTrue($conversation->isActive());
    }

    public function test_conversation_is_closed_status(): void
    {
        $conversation = Conversation::factory()->create(['status' => Conversation::STATUS_CLOSED]);
        
        $this->assertTrue($conversation->isClosed());
    }

    // ===== THREAD MODEL TESTS =====

    public function test_thread_can_be_created(): void
    {
        $thread = Thread::factory()->create([
            'body' => 'Thread content',
        ]);
        
        $this->assertInstanceOf(Thread::class, $thread);
        $this->assertDatabaseHas('threads', [
            'body' => 'Thread content',
        ]);
    }

    public function test_thread_belongs_to_conversation(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        
        $this->assertInstanceOf(Conversation::class, $thread->conversation);
        $this->assertEquals($conversation->id, $thread->conversation->id);
    }

    public function test_thread_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $thread = Thread::factory()->create(['created_by_user_id' => $user->id]);
        
        $this->assertInstanceOf(User::class, $thread->createdByUser);
        $this->assertEquals($user->id, $thread->createdByUser->id);
    }

    public function test_thread_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $thread = Thread::factory()->create(['created_by_customer_id' => $customer->id]);
        
        $this->assertInstanceOf(Customer::class, $thread->customer);
        $this->assertEquals($customer->id, $thread->customer->id);
    }

    public function test_thread_has_many_attachments(): void
    {
        $thread = Thread::factory()->create();
        
        $this->assertIsObject($thread->attachments);
    }

    public function test_thread_type_message_constant(): void
    {
        $this->assertEquals(1, Thread::TYPE_MESSAGE);
    }

    public function test_thread_type_note_constant(): void
    {
        $this->assertEquals(2, Thread::TYPE_NOTE);
    }

    public function test_thread_type_customer_constant(): void
    {
        $this->assertEquals(3, Thread::TYPE_CUSTOMER);
    }

    public function test_thread_is_message_type(): void
    {
        $thread = Thread::factory()->create(['type' => Thread::TYPE_MESSAGE]);
        
        $this->assertEquals(Thread::TYPE_MESSAGE, $thread->type);
    }

    // ===== FOLDER MODEL TESTS =====

    public function test_folder_can_be_created(): void
    {
        $folder = Folder::factory()->create([
            'name' => 'Test Folder',
        ]);
        
        $this->assertInstanceOf(Folder::class, $folder);
        $this->assertDatabaseHas('folders', [
            'name' => 'Test Folder',
        ]);
    }

    public function test_folder_belongs_to_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $this->assertInstanceOf(Mailbox::class, $folder->mailbox);
        $this->assertEquals($mailbox->id, $folder->mailbox->id);
    }

    public function test_folder_has_many_conversations(): void
    {
        $folder = Folder::factory()->create();
        Conversation::factory()->count(5)->create(['folder_id' => $folder->id]);
        
        $this->assertCount(5, $folder->conversations);
    }

    public function test_folder_type_constants_exist(): void
    {
        $this->assertEquals(1, Folder::TYPE_INBOX);
        $this->assertEquals(2, Folder::TYPE_UNASSIGNED);
        $this->assertEquals(3, Folder::TYPE_DRAFTS);
        $this->assertEquals(4, Folder::TYPE_SPAM);
        $this->assertEquals(5, Folder::TYPE_TRASH);
        $this->assertEquals(6, Folder::TYPE_SENT);
        $this->assertEquals(20, Folder::TYPE_ASSIGNED);
        $this->assertEquals(25, Folder::TYPE_MINE);
        $this->assertEquals(30, Folder::TYPE_STARRED);
    }

    public function test_folder_total_counter(): void
    {
        $folder = Folder::factory()->create(['total_count' => 10]);
        
        $this->assertEquals(10, $folder->total_count);
    }

    // ===== CUSTOMER MODEL TESTS =====

    public function test_customer_can_be_created(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
        
        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertDatabaseHas('customers', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
    }

    public function test_customer_has_many_conversations(): void
    {
        $customer = Customer::factory()->create();
        Conversation::factory()->count(5)->create(['customer_id' => $customer->id]);
        
        $this->assertCount(5, $customer->conversations);
    }

    public function test_customer_has_many_emails(): void
    {
        $customer = Customer::factory()->create();
        
        $this->assertIsObject($customer->emails);
    }

    public function test_customer_full_name_attribute(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
        
        $this->assertEquals('Jane Smith', $customer->full_name);
    }

    public function test_customer_create_with_email(): void
    {
        $email = 'customer' . time() . '@example.com';
        $customer = Customer::create($email, [
            'first_name' => 'Test',
            'last_name' => 'Customer',
        ]);
        
        $this->assertInstanceOf(Customer::class, $customer);
        // Following TESTING_GUIDE.md: Check emails table
        $this->assertDatabaseHas('emails', ['email' => $email]);
    }

    // ===== RELATIONSHIP TESTS =====

    public function test_user_mailbox_many_to_many_relationship(): void
    {
        $user = User::factory()->create();
        $mailbox = Mailbox::factory()->create();
        
        $mailbox->users()->attach($user->id);
        
        $this->assertTrue($user->mailboxes->contains($mailbox));
        $this->assertTrue($mailbox->users->contains($user));
    }

    public function test_conversation_thread_one_to_many_relationship(): void
    {
        $conversation = Conversation::factory()->create();
        $thread = Thread::factory()->create(['conversation_id' => $conversation->id]);
        
        $this->assertTrue($conversation->threads->contains($thread));
        $this->assertEquals($conversation->id, $thread->conversation_id);
    }

    public function test_mailbox_folder_one_to_many_relationship(): void
    {
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $this->assertTrue($mailbox->folders->contains($folder));
        $this->assertEquals($mailbox->id, $folder->mailbox_id);
    }

    // ===== EDGE CASES =====



    public function test_conversation_with_null_user(): void
    {
        $conversation = Conversation::factory()->create(['user_id' => null]);
        
        $this->assertNull($conversation->user_id);
    }

    public function test_thread_with_null_created_by_user(): void
    {
        $thread = Thread::factory()->create(['created_by_user_id' => null]);
        
        $this->assertNull($thread->created_by_user_id);
    }

    public function test_customer_with_null_last_name(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => null,
        ]);
        
        $this->assertNull($customer->last_name);
        $this->assertEquals('Jane', $customer->full_name);
    }

    public function test_folder_active_counter(): void
    {
        $folder = Folder::factory()->create(['active_count' => 5]);
        
        $this->assertEquals(5, $folder->active_count);
    }

    public function test_conversation_can_be_soft_deleted(): void
    {
        $conversation = Conversation::factory()->create();
        $id = $conversation->id;
        
        $conversation->delete();
        
        $this->assertSoftDeleted('conversations', ['id' => $id]);
    }

    public function test_user_timestamps_are_set(): void
    {
        $user = User::factory()->create();
        
        $this->assertNotNull($user->created_at);
        $this->assertNotNull($user->updated_at);
    }

    public function test_conversation_timestamps_are_set(): void
    {
        $conversation = Conversation::factory()->create();
        
        $this->assertNotNull($conversation->created_at);
        $this->assertNotNull($conversation->updated_at);
    }

    public function test_multiple_users_can_be_assigned_to_mailbox(): void
    {
        $mailbox = Mailbox::factory()->create();
        $users = User::factory()->count(5)->create();
        
        foreach ($users as $user) {
            $mailbox->users()->attach($user->id);
        }
        
        $this->assertCount(5, $mailbox->fresh()->users);
    }

    public function test_conversation_number_is_unique(): void
    {
        $conv1 = Conversation::factory()->create();
        $conv2 = Conversation::factory()->create();
        
        $this->assertNotEquals($conv1->number, $conv2->number);
    }
}
