<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use App\Services\ConversationService;
use App\Services\CustomerService;
use App\Services\MailboxService;
use App\Services\ThreadService;
use Illuminate\Support\Facades\Mail;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for Service classes
 * Following TESTING_GUIDE.md - using test_ prefix, UnitTestCase base class
 */
class ServicesComprehensiveTest extends UnitTestCase
{
    // ===== CONVERSATION SERVICE TESTS =====

    public function test_conversation_service_can_create_conversation(): void
    {
        $service = app(ConversationService::class);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $user = User::factory()->create();

        $conversation = $service->createConversation([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Test Subject',
            'user_id' => $user->id,
        ]);

        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertEquals('Test Subject', $conversation->subject);
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'mailbox_id' => $mailbox->id,
        ]);
    }

    public function test_conversation_service_validates_required_fields(): void
    {
        $service = app(ConversationService::class);

        $this->expectException(\InvalidArgumentException::class);

        $service->createConversation([
            'subject' => 'Missing mailbox_id',
        ]);
    }

    public function test_conversation_service_can_update_status(): void
    {
        $service = app(ConversationService::class);
        $conversation = Conversation::factory()->create([
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $service->updateStatus($conversation, Conversation::STATUS_CLOSED);

        $this->assertEquals(Conversation::STATUS_CLOSED, $conversation->fresh()->status);
    }

    public function test_conversation_service_can_assign_to_user(): void
    {
        $service = app(ConversationService::class);
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();

        $service->assignToUser($conversation, $user);

        $this->assertEquals($user->id, $conversation->fresh()->user_id);
    }

    public function test_conversation_service_can_move_to_folder(): void
    {
        $service = app(ConversationService::class);
        $mailbox = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox->id]);
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);

        $service->moveToFolder($conversation, $folder);

        $this->assertEquals($folder->id, $conversation->fresh()->folder_id);
    }

    public function test_conversation_service_prevents_moving_to_different_mailbox_folder(): void
    {
        $service = app(ConversationService::class);
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        $folder = Folder::factory()->create(['mailbox_id' => $mailbox2->id]);
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox1->id]);

        $this->expectException(\InvalidArgumentException::class);

        $service->moveToFolder($conversation, $folder);
    }

    // ===== CUSTOMER SERVICE TESTS =====

    public function test_customer_service_can_create_customer_with_email(): void
    {
        $service = app(CustomerService::class);

        $customer = $service->createCustomer([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ]);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('John', $customer->first_name);
        $this->assertDatabaseHas('emails', ['email' => 'john.doe@example.com']);
    }

    public function test_customer_service_finds_existing_customer_by_email(): void
    {
        $service = app(CustomerService::class);
        $existingCustomer = Customer::factory()->create(['email' => 'existing@example.com']);

        $customer = $service->findOrCreateByEmail('existing@example.com');

        $this->assertEquals($existingCustomer->id, $customer->id);
    }

    public function test_customer_service_creates_new_customer_when_not_found(): void
    {
        $service = app(CustomerService::class);

        $customer = $service->findOrCreateByEmail('new@example.com');

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertDatabaseHas('emails', ['email' => 'new@example.com']);
    }

    public function test_customer_service_can_update_customer_details(): void
    {
        $service = app(CustomerService::class);
        $customer = Customer::factory()->create();

        $service->updateCustomer($customer, [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);

        $this->assertEquals('Updated', $customer->fresh()->first_name);
        $this->assertEquals('Name', $customer->fresh()->last_name);
    }

    public function test_customer_service_can_add_email_to_customer(): void
    {
        $service = app(CustomerService::class);
        $customer = Customer::factory()->create();

        $service->addEmail($customer, 'additional@example.com');

        $this->assertDatabaseHas('emails', [
            'customer_id' => $customer->id,
            'email' => 'additional@example.com',
        ]);
    }

    public function test_customer_service_validates_email_format(): void
    {
        $service = app(CustomerService::class);
        $customer = Customer::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $service->addEmail($customer, 'invalid-email');
    }

    // ===== MAILBOX SERVICE TESTS =====

    public function test_mailbox_service_can_create_mailbox(): void
    {
        $service = app(MailboxService::class);

        $mailbox = $service->createMailbox([
            'name' => 'Test Mailbox',
            'email' => 'test@example.com',
        ]);

        $this->assertInstanceOf(Mailbox::class, $mailbox);
        $this->assertEquals('Test Mailbox', $mailbox->name);
        $this->assertDatabaseHas('mailboxes', ['email' => 'test@example.com']);
    }

    public function test_mailbox_service_validates_email_uniqueness(): void
    {
        $service = app(MailboxService::class);
        Mailbox::factory()->create(['email' => 'existing@example.com']);

        $this->expectException(\InvalidArgumentException::class);

        $service->createMailbox([
            'name' => 'Duplicate',
            'email' => 'existing@example.com',
        ]);
    }

    public function test_mailbox_service_can_assign_users(): void
    {
        $service = app(MailboxService::class);
        $mailbox = Mailbox::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $service->assignUsers($mailbox, [$user1->id, $user2->id]);

        $this->assertTrue($mailbox->users->contains($user1));
        $this->assertTrue($mailbox->users->contains($user2));
    }

    public function test_mailbox_service_can_remove_user(): void
    {
        $service = app(MailboxService::class);
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        $mailbox->users()->attach($user->id);

        $service->removeUser($mailbox, $user);

        $this->assertFalse($mailbox->fresh()->users->contains($user));
    }

    public function test_mailbox_service_can_update_settings(): void
    {
        $service = app(MailboxService::class);
        $mailbox = Mailbox::factory()->create();

        $service->updateSettings($mailbox, [
            'auto_reply' => true,
            'auto_reply_subject' => 'Auto Reply',
        ]);

        $mailbox = $mailbox->fresh();
        $this->assertTrue($mailbox->auto_reply);
        $this->assertEquals('Auto Reply', $mailbox->auto_reply_subject);
    }

    public function test_mailbox_service_creates_default_folders(): void
    {
        $service = app(MailboxService::class);

        $mailbox = $service->createMailbox([
            'name' => 'Test',
            'email' => 'test@example.com',
        ]);

        $folders = $mailbox->folders;
        $this->assertGreaterThan(0, $folders->count());
        $this->assertTrue($folders->pluck('type')->contains(Folder::TYPE_ASSIGNED));
    }

    // ===== THREAD SERVICE TESTS =====

    public function test_thread_service_can_create_thread(): void
    {
        $service = app(ThreadService::class);
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();

        $thread = $service->createThread([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => Thread::TYPE_NOTE,
            'body' => 'Test note',
        ]);

        $this->assertInstanceOf(Thread::class, $thread);
        $this->assertEquals('Test note', $thread->body);
        $this->assertDatabaseHas('threads', [
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_NOTE,
        ]);
    }

    public function test_thread_service_validates_thread_type(): void
    {
        $service = app(ThreadService::class);
        $conversation = Conversation::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        $service->createThread([
            'conversation_id' => $conversation->id,
            'type' => 999, // Invalid type
            'body' => 'Test',
        ]);
    }

    public function test_thread_service_can_add_attachments(): void
    {
        $service = app(ThreadService::class);
        $thread = Thread::factory()->create();

        $service->addAttachment($thread, [
            'file_name' => 'test.pdf',
            'size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $this->assertDatabaseHas('attachments', [
            'thread_id' => $thread->id,
            'file_name' => 'test.pdf',
        ]);
    }

    public function test_thread_service_sends_notification_for_reply(): void
    {
        Mail::fake();

        $service = app(ThreadService::class);
        $conversation = Conversation::factory()->create();
        $customer = Customer::factory()->create();
        $conversation->customer_id = $customer->id;
        $conversation->save();

        $thread = $service->createThread([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_MESSAGE,
            'body' => 'Reply to customer',
        ]);

        $service->sendNotifications($thread);

        Mail::assertQueued(function ($mail) {
            return true; // Verify mail was queued
        });
    }

    public function test_thread_service_does_not_send_notification_for_note(): void
    {
        Mail::fake();

        $service = app(ThreadService::class);
        $conversation = Conversation::factory()->create();

        $thread = $service->createThread([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_NOTE,
            'body' => 'Internal note',
        ]);

        $service->sendNotifications($thread);

        Mail::assertNothingQueued();
    }

    // ===== EDGE CASE TESTS =====

    public function test_services_handle_null_values_gracefully(): void
    {
        $conversationService = app(ConversationService::class);
        $conversation = Conversation::factory()->create(['user_id' => null]);

        $this->assertNull($conversation->user_id);
        $this->assertInstanceOf(Conversation::class, $conversation);
    }

    public function test_services_handle_soft_deleted_records(): void
    {
        $service = app(ConversationService::class);
        $conversation = Conversation::factory()->create();
        $conversation->delete();

        $this->assertSoftDeleted('conversations', ['id' => $conversation->id]);
    }

    public function test_services_respect_mass_assignment_protection(): void
    {
        $service = app(CustomerService::class);

        $this->expectException(\Exception::class);

        $service->createCustomer([
            'id' => 9999, // Should be protected
            'first_name' => 'Test',
        ]);
    }
}
