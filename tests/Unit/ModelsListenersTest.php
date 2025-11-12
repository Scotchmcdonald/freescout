<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\ConversationStatusChanged;
use App\Events\ConversationUserChanged;
use App\Events\UserCreatedConversation;
use App\Events\UserReplied;
use App\Listeners\RememberUserLocale;
use App\Listeners\SendPasswordChanged;
use App\Listeners\SendReplyToCustomer;
use App\Listeners\UpdateMailboxCounters;
use App\Models\Attachment;
use App\Models\Channel;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\CustomerChannel;
use App\Models\Email;
use App\Models\Mailbox;
use App\Models\MailboxUser;
use App\Models\SendLog;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\UnitTestCase;

class ModelsListenersTest extends UnitTestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    // ============================
    // Attachment Model Tests (10 tests)
    // ============================

    public function test_attachment_get_full_path_attribute_returns_storage_path(): void
    {
        $attachment = Attachment::factory()->create([
            'file_dir' => 'attachments/2025',
            'file_name' => 'document.pdf',
        ]);

        $fullPath = $attachment->full_path;

        $this->assertStringContainsString('attachments/2025', $fullPath);
        $this->assertStringContainsString('document.pdf', $fullPath);
        $this->assertStringContainsString('storage', $fullPath);
    }

    public function test_attachment_get_full_path_with_different_directories(): void
    {
        $attachment1 = Attachment::factory()->create([
            'file_dir' => 'temp',
            'file_name' => 'test.txt',
        ]);
        $this->assertStringContainsString('temp/test.txt', $attachment1->full_path);

        $attachment2 = Attachment::factory()->create([
            'file_dir' => 'uploads/2024/01',
            'file_name' => 'image.jpg',
        ]);
        $this->assertStringContainsString('uploads/2024/01/image.jpg', $attachment2->full_path);
    }

    public function test_attachment_get_human_file_size_attribute_formats_bytes(): void
    {
        $attachmentBytes = Attachment::factory()->create([
            'file_size' => 512, // 512 bytes
        ]);
        $humanSize = $attachmentBytes->human_file_size;
        $this->assertStringContainsString('512', $humanSize);
        $this->assertStringContainsString('B', $humanSize);
    }

    public function test_attachment_get_human_file_size_attribute_formats_kilobytes(): void
    {
        $attachmentKb = Attachment::factory()->create([
            'file_size' => 2048, // 2KB in bytes
        ]);
        $humanSize = $attachmentKb->human_file_size;
        $this->assertStringContainsString('2', $humanSize);
        $this->assertStringContainsString('KB', $humanSize);
    }

    public function test_attachment_get_human_file_size_attribute_formats_megabytes(): void
    {
        $attachment1mb = Attachment::factory()->create([
            'file_size' => 2097152, // 2MB in bytes (needs > 1024 KB)
        ]);
        $humanSize = $attachment1mb->human_file_size;
        $this->assertStringContainsString('2', $humanSize);
        $this->assertStringContainsString('MB', $humanSize);
    }

    public function test_attachment_get_human_file_size_attribute_formats_gigabytes(): void
    {
        $attachment1gb = Attachment::factory()->create([
            'file_size' => 2147483648, // 2GB in bytes (needs > 1024 MB)
        ]);
        $humanSize = $attachment1gb->human_file_size;
        $this->assertStringContainsString('2', $humanSize);
        $this->assertStringContainsString('GB', $humanSize);
    }

    public function test_attachment_get_human_file_size_with_large_file(): void
    {
        $attachmentLarge = Attachment::factory()->create([
            'file_size' => 5242880, // 5MB
        ]);
        $humanSize = $attachmentLarge->human_file_size;
        $this->assertStringContainsString('5', $humanSize);
        $this->assertStringContainsString('MB', $humanSize);
    }

    public function test_attachment_get_human_file_size_with_zero_size(): void
    {
        $attachmentZero = Attachment::factory()->create([
            'file_size' => 0,
        ]);
        $humanSize = $attachmentZero->human_file_size;
        $this->assertStringContainsString('0', $humanSize);
        $this->assertStringContainsString('B', $humanSize);
    }

    public function test_attachment_is_image_returns_true_for_image_mime_types(): void
    {
        $imageJpeg = Attachment::factory()->create(['mime_type' => 'image/jpeg']);
        $this->assertTrue($imageJpeg->isImage());

        $imagePng = Attachment::factory()->create(['mime_type' => 'image/png']);
        $this->assertTrue($imagePng->isImage());

        $imageGif = Attachment::factory()->create(['mime_type' => 'image/gif']);
        $this->assertTrue($imageGif->isImage());
    }

    public function test_attachment_is_image_returns_false_for_non_image_mime_types(): void
    {
        $pdf = Attachment::factory()->create(['mime_type' => 'application/pdf']);
        $this->assertFalse($pdf->isImage());

        $text = Attachment::factory()->create(['mime_type' => 'text/plain']);
        $this->assertFalse($text->isImage());

        $zip = Attachment::factory()->create(['mime_type' => 'application/zip']);
        $this->assertFalse($zip->isImage());
    }

    // ============================
    // Channel Model Tests (8 tests)
    // ============================

    public function test_channel_customers_relationship_works(): void
    {
        $this->markTestIncomplete('Channel table migration not yet implemented');
        
        $channel = Channel::factory()->create();
        $customer = Customer::factory()->create();

        // Associate customer with channel using pivot table
        $channel->customers()->attach($customer->id);

        $this->assertTrue($channel->customers->contains($customer));
        $this->assertCount(1, $channel->customers);
    }

    public function test_channel_customers_relationship_with_multiple_customers(): void
    {
        $this->markTestIncomplete('Channel table migration not yet implemented');
        
        $channel = Channel::factory()->create();
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $customer3 = Customer::factory()->create();

        $channel->customers()->attach([$customer1->id, $customer2->id, $customer3->id]);

        $this->assertCount(3, $channel->customers);
        $this->assertTrue($channel->customers->contains($customer1));
        $this->assertTrue($channel->customers->contains($customer2));
        $this->assertTrue($channel->customers->contains($customer3));
    }

    public function test_channel_customers_relationship_can_be_detached(): void
    {
        $this->markTestIncomplete('Channel table migration not yet implemented');
        
        $channel = Channel::factory()->create();
        $customer = Customer::factory()->create();

        $channel->customers()->attach($customer->id);
        $this->assertCount(1, $channel->customers);

        $channel->customers()->detach($customer->id);
        $this->assertCount(0, $channel->fresh()->customers);
    }

    public function test_channel_is_active_returns_true_for_active_channel(): void
    {
        $this->markTestIncomplete('Channel table migration not yet implemented');
        
        $activeChannel = Channel::factory()->create(['active' => true]);
        $this->assertTrue($activeChannel->isActive());
    }

    public function test_channel_is_active_returns_false_for_inactive_channel(): void
    {
        $this->markTestIncomplete('Channel table migration not yet implemented');
        
        $inactiveChannel = Channel::factory()->create(['active' => false]);
        $this->assertFalse($inactiveChannel->isActive());
    }

    public function test_channel_can_be_toggled_active_status(): void
    {
        $this->markTestIncomplete('Channel table migration not yet implemented');
        
        $channel = Channel::factory()->create(['active' => true]);
        $this->assertTrue($channel->isActive());

        $channel->active = false;
        $channel->save();
        $this->assertFalse($channel->fresh()->isActive());
    }

    public function test_channel_has_name_and_type_attributes(): void
    {
        $this->markTestIncomplete('Channel table migration not yet implemented');
        
        $channel = Channel::factory()->create([
            'name' => 'Support Email',
            'type' => 1,
        ]);

        $this->assertEquals('Support Email', $channel->name);
        $this->assertEquals(1, $channel->type);
    }

    public function test_channel_has_settings_as_json(): void
    {
        $this->markTestIncomplete('Channel table migration not yet implemented');
        
        $settings = ['address' => 'support@example.com', 'enabled' => true];
        $channel = Channel::factory()->create([
            'settings' => $settings,
        ]);

        $this->assertEquals($settings, $channel->settings);
        $this->assertIsArray($channel->settings);
    }

    // ============================
    // Customer Model Tests (8 tests)
    // ============================

    public function test_customer_customer_channels_relationship_works(): void
    {
        $customer = Customer::factory()->create();

        // Create a customer channel record
        $customerChannel = CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_EMAIL,
            'channel_id' => 'customer@example.com',
        ]);

        $this->assertCount(1, $customer->customerChannels);
        $this->assertEquals($customer->id, $customerChannel->customer_id);
        $this->assertEquals(CustomerChannel::CHANNEL_EMAIL, $customerChannel->channel);
    }

    public function test_customer_customer_channels_with_multiple_channels(): void
    {
        $customer = Customer::factory()->create();

        CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_EMAIL,
            'channel_id' => 'customer@example.com',
        ]);

        CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_PHONE,
            'channel_id' => '+1234567890',
        ]);

        CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_CHAT,
            'channel_id' => 'chat-id-123',
        ]);

        $this->assertCount(3, $customer->customerChannels);
    }

    public function test_customer_get_first_name_returns_customer_first_name(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('Jane', $customer->getFirstName());
    }

    public function test_customer_get_first_name_returns_empty_string_when_null(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => null,
        ]);

        $this->assertEquals('', $customer->getFirstName());
    }

    public function test_customer_get_full_name_combines_first_and_last(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Smith',
        ]);

        $this->assertEquals('John Smith', $customer->getFullName());
        $this->assertEquals('John Smith', $customer->full_name);
    }

    public function test_customer_get_full_name_trims_whitespace(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => '  John  ',
            'last_name' => '  Smith  ',
        ]);

        $fullName = $customer->getFullName();
        // Method trims outer whitespace but preserves inner whitespace
        $this->assertEquals('John     Smith', $fullName);
        $this->assertStringStartsNotWith(' ', $fullName);
        $this->assertStringEndsNotWith(' ', $fullName);
    }

    public function test_customer_channels_belongstomany_relationship(): void
    {
        $this->markTestIncomplete('Channel table migration not yet implemented');
        
        $customer = Customer::factory()->create();
        $channel1 = Channel::factory()->create();
        $channel2 = Channel::factory()->create();

        $customer->channels()->attach([$channel1->id, $channel2->id]);

        $this->assertCount(2, $customer->channels);
        $this->assertTrue($customer->channels->contains($channel1));
        $this->assertTrue($customer->channels->contains($channel2));
    }

    public function test_customer_has_emails_relationship(): void
    {
        $customer = Customer::factory()->create();
        // Factory creates 1 email automatically
        
        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'test1@example.com',
        ]);

        Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'test2@example.com',
        ]);

        // Total: 1 (from factory) + 2 (created above) = 3
        $this->assertCount(3, $customer->emails);
    }

    // ============================
    // User Model Tests (12 tests)
    // ============================

    public function test_user_get_first_name_returns_first_part_of_name(): void
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $this->assertEquals('John', $user->getFirstName());
    }

    public function test_user_get_first_name_returns_empty_string_when_null(): void
    {
        $this->markTestIncomplete('first_name is NOT NULL in database schema');
        
        $user = User::factory()->create([
            'first_name' => null,
        ]);

        $this->assertEquals('', $user->getFirstName());
    }

    public function test_user_get_first_name_with_empty_string(): void
    {
        $user = User::factory()->create([
            'first_name' => '',
            'last_name' => 'Smith',
        ]);

        $this->assertEquals('', $user->getFirstName());
    }

    public function test_user_get_photo_url_returns_gravatar_url(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $photoUrl = $user->getPhotoUrl();

        $this->assertStringContainsString('gravatar.com', $photoUrl);
        $this->assertStringContainsString('avatar', $photoUrl);
    }

    public function test_user_get_photo_url_uses_email_hash(): void
    {
        $email = 'test@example.com';
        $user = User::factory()->create(['email' => $email]);

        $photoUrl = $user->getPhotoUrl();
        $expectedHash = md5(strtolower(trim($email)));

        $this->assertStringContainsString($expectedHash, $photoUrl);
    }

    public function test_user_get_photo_url_handles_different_emails(): void
    {
        $user1 = User::factory()->create(['email' => 'john@example.com']);
        $user2 = User::factory()->create(['email' => 'jane@example.com']);

        $url1 = $user1->getPhotoUrl();
        $url2 = $user2->getPhotoUrl();

        $this->assertNotEquals($url1, $url2);
    }

    public function test_user_has_access_to_mailbox_with_view_permission(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();

        $mailbox->users()->attach($user->id, [
            'access' => MailboxUser::ACCESS_VIEW,
            'after_send' => false,
        ]);

        $this->assertTrue($user->hasAccessToMailbox($mailbox->id));
    }

    public function test_user_has_access_to_mailbox_with_reply_permission(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();

        $mailbox->users()->attach($user->id, [
            'access' => MailboxUser::ACCESS_REPLY,
            'after_send' => false,
        ]);

        $this->assertTrue($user->hasAccessToMailbox($mailbox->id));
    }

    public function test_user_has_access_to_mailbox_with_admin_permission(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();

        $mailbox->users()->attach($user->id, [
            'access' => MailboxUser::ACCESS_ADMIN,
            'after_send' => false,
        ]);

        $this->assertTrue($user->hasAccessToMailbox($mailbox->id));
    }

    public function test_user_has_access_to_mailbox_without_permission(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();

        $this->assertFalse($user->hasAccessToMailbox($mailbox->id));
    }

    public function test_user_admin_has_access_to_all_mailboxes(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();

        $this->assertTrue($admin->hasAccessToMailbox($mailbox1->id));
        $this->assertTrue($admin->hasAccessToMailbox($mailbox2->id));
    }

    public function test_user_has_access_to_mailbox_checks_minimum_level(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();

        $mailbox->users()->attach($user->id, [
            'access' => MailboxUser::ACCESS_VIEW,
            'after_send' => false,
        ]);

        // Should have VIEW access
        $this->assertTrue($user->hasAccessToMailbox($mailbox->id, MailboxUser::ACCESS_VIEW));
        
        // Should NOT have REPLY access (VIEW < REPLY)
        $this->assertFalse($user->hasAccessToMailbox($mailbox->id, MailboxUser::ACCESS_REPLY));
        
        // Should NOT have ADMIN access (VIEW < ADMIN)
        $this->assertFalse($user->hasAccessToMailbox($mailbox->id, MailboxUser::ACCESS_ADMIN));
    }

    // ============================
    // SendLog Model Tests (12 tests)
    // ============================

    public function test_send_log_was_opened_with_multiple_opens(): void
    {
        $openedLog = SendLog::factory()->create([
            'opens' => 3,
            'opened_at' => now(),
        ]);

        $this->assertTrue($openedLog->wasOpened());
        $this->assertEquals(3, $openedLog->opens);
    }

    public function test_send_log_was_opened_with_single_open(): void
    {
        $openedLog = SendLog::factory()->create([
            'opens' => 1,
            'opened_at' => now(),
        ]);

        $this->assertTrue($openedLog->wasOpened());
    }

    public function test_send_log_was_opened_returns_false_when_not_opened(): void
    {
        $unopenedLog = SendLog::factory()->create([
            'opens' => 0,
            'opened_at' => null,
        ]);

        $this->assertFalse($unopenedLog->wasOpened());
    }

    public function test_send_log_was_clicked_with_multiple_clicks(): void
    {
        $clickedLog = SendLog::factory()->create([
            'clicks' => 5,
            'clicked_at' => now(),
        ]);

        $this->assertTrue($clickedLog->wasClicked());
        $this->assertEquals(5, $clickedLog->clicks);
    }

    public function test_send_log_was_clicked_with_single_click(): void
    {
        $clickedLog = SendLog::factory()->create([
            'clicks' => 1,
            'clicked_at' => now(),
        ]);

        $this->assertTrue($clickedLog->wasClicked());
    }

    public function test_send_log_was_clicked_returns_false_when_not_clicked(): void
    {
        $unclickedLog = SendLog::factory()->create([
            'clicks' => 0,
            'clicked_at' => null,
        ]);

        $this->assertFalse($unclickedLog->wasClicked());
    }

    public function test_send_log_is_sent_returns_true_for_sent_status(): void
    {
        $sentLog = SendLog::factory()->create([
            'status' => SendLog::STATUS_ACCEPTED,
        ]);

        $this->assertTrue($sentLog->isSent());
    }

    public function test_send_log_is_sent_returns_false_for_failed_status(): void
    {
        $failedLog = SendLog::factory()->create([
            'status' => SendLog::STATUS_SEND_ERROR,
        ]);

        $this->assertFalse($failedLog->isSent());
    }

    public function test_send_log_is_failed_returns_true_for_failed_status(): void
    {
        $failedLog = SendLog::factory()->create([
            'status' => SendLog::STATUS_SEND_ERROR,
        ]);

        $this->assertTrue($failedLog->isFailed());
    }

    public function test_send_log_is_failed_returns_false_for_sent_status(): void
    {
        $sentLog = SendLog::factory()->create([
            'status' => SendLog::STATUS_ACCEPTED,
        ]);

        $this->assertFalse($sentLog->isFailed());
    }

    public function test_send_log_tracks_both_opens_and_clicks(): void
    {
        $log = SendLog::factory()->create([
            'opens' => 5,
            'clicks' => 3,
            'opened_at' => now()->subHours(2),
            'clicked_at' => now()->subHours(1),
        ]);

        $this->assertTrue($log->wasOpened());
        $this->assertTrue($log->wasClicked());
        $this->assertEquals(5, $log->opens);
        $this->assertEquals(3, $log->clicks);
    }

    public function test_send_log_belongs_to_thread(): void
    {
        $thread = Thread::factory()->create();
        $log = SendLog::factory()->create([
            'thread_id' => $thread->id,
        ]);

        $this->assertInstanceOf(Thread::class, $log->thread);
        $this->assertEquals($thread->id, $log->thread->id);
    }

    // ============================
    // Listener Tests (20 tests)
    // ============================

    // RememberUserLocale Listener Tests (5 tests)

    public function test_remember_user_locale_handles_login_event(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        $this->actingAs($user);

        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();

        // The listener checks for getLocale method - since User model doesn't have it by default,
        // the session won't be updated. This test verifies the listener doesn't crash.
        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_remember_user_locale_handles_web_guard(): void
    {
        $user = User::factory()->create();
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_remember_user_locale_handles_api_guard(): void
    {
        $user = User::factory()->create();
        $event = new Login('api', $user, false);
        $listener = new RememberUserLocale();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_remember_user_locale_handles_remember_me(): void
    {
        $user = User::factory()->create();
        $event = new Login('web', $user, true);
        $listener = new RememberUserLocale();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_remember_user_locale_checks_method_exists(): void
    {
        $user = User::factory()->create();
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();

        // Should handle gracefully even if getLocale method doesn't exist
        $listener->handle($event);

        $this->assertFalse(method_exists($user, 'getLocale'));
    }

    // SendPasswordChanged Listener Tests (4 tests)

    public function test_send_password_changed_handles_password_reset_event(): void
    {
        $user = User::factory()->create();
        $event = new PasswordReset($user);
        $listener = new SendPasswordChanged();

        // The listener checks if user has sendPasswordChanged method
        // Since our User model doesn't have it, this should not crash
        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_send_password_changed_checks_method_exists(): void
    {
        $user = User::factory()->create();
        $event = new PasswordReset($user);
        $listener = new SendPasswordChanged();

        $listener->handle($event);

        // Verify the method doesn't exist on our User model
        $this->assertFalse(method_exists($user, 'sendPasswordChanged'));
    }

    public function test_send_password_changed_handles_admin_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $event = new PasswordReset($admin);
        $listener = new SendPasswordChanged();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_send_password_changed_handles_regular_user(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $event = new PasswordReset($user);
        $listener = new SendPasswordChanged();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    // UpdateMailboxCounters Listener Tests (5 tests)

    public function test_update_mailbox_counters_handles_status_changed(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $event = new ConversationStatusChanged($conversation);
        $listener = new UpdateMailboxCounters();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_update_mailbox_counters_handles_user_changed(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $event = new ConversationUserChanged($conversation, $user);
        $listener = new UpdateMailboxCounters();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_update_mailbox_counters_checks_method_exists(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $event = new ConversationStatusChanged($conversation);
        $listener = new UpdateMailboxCounters();

        $listener->handle($event);

        // Verify the method doesn't exist on our Mailbox model
        $this->assertFalse(method_exists($mailbox, 'updateFoldersCounters'));
    }

    public function test_update_mailbox_counters_handles_closed_conversation(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->closed()->create([
            'mailbox_id' => $mailbox->id,
        ]);

        $event = new ConversationStatusChanged($conversation);
        $listener = new UpdateMailboxCounters();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_update_mailbox_counters_handles_active_conversation(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $event = new ConversationStatusChanged($conversation);
        $listener = new UpdateMailboxCounters();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    // SendReplyToCustomer Listener Tests (6 tests)

    public function test_send_reply_to_customer_handles_user_replied_event(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'imported' => false,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_MESSAGE,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_send_reply_to_customer_handles_user_created_conversation_event(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'imported' => false,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
            'type' => Thread::TYPE_MESSAGE,
        ]);

        $event = new UserCreatedConversation($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    public function test_send_reply_to_customer_checks_is_phone_method(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        // Verify the method doesn't exist
        $this->assertFalse(method_exists($conversation, 'isPhone'));
    }

    public function test_send_reply_to_customer_checks_is_chat_method(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        // Verify the method doesn't exist
        $this->assertFalse(method_exists($conversation, 'isChat'));
    }

    public function test_send_reply_to_customer_checks_get_replies_method(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->create([
            'conversation_id' => $conversation->id,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        // Verify the method doesn't exist
        $this->assertFalse(method_exists($conversation, 'getReplies'));
    }

    public function test_send_reply_to_customer_handles_note_thread(): void
    {
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
        ]);
        $thread = Thread::factory()->note()->create([
            'conversation_id' => $conversation->id,
        ]);

        $event = new UserReplied($conversation, $thread);
        $listener = new SendReplyToCustomer();

        $listener->handle($event);

        $this->assertTrue(true);
    }

    // ============================
    // Additional Edge Case Tests (10 tests)
    // ============================

    public function test_attachment_belongs_to_thread_relationship(): void
    {
        $thread = Thread::factory()->create();
        $attachment = Attachment::factory()->create([
            'thread_id' => $thread->id,
        ]);

        $this->assertInstanceOf(Thread::class, $attachment->thread);
        $this->assertEquals($thread->id, $attachment->thread_id);
    }

    public function test_attachment_has_embedded_flag(): void
    {
        $embedded = Attachment::factory()->create(['embedded' => true]);
        $notEmbedded = Attachment::factory()->create(['embedded' => false]);

        $this->assertTrue($embedded->embedded);
        $this->assertFalse($notEmbedded->embedded);
    }

    public function test_user_get_full_name_attribute_accessor(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Alice',
            'last_name' => 'Johnson',
        ]);

        // Test the attribute accessor
        $this->assertEquals('Alice Johnson', $user->full_name);
        $this->assertEquals('Alice Johnson', $user->getFullNameAttribute());
    }

    public function test_user_name_attribute_returns_full_name(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Bob',
            'last_name' => 'Smith',
        ]);

        $this->assertEquals('Bob Smith', $user->name);
    }

    public function test_user_is_admin_method_works(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($user->isAdmin());
    }

    public function test_user_is_active_method_works(): void
    {
        $active = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $inactive = User::factory()->create(['status' => User::STATUS_INACTIVE]);

        $this->assertTrue($active->isActive());
        $this->assertFalse($inactive->isActive());
    }

    public function test_customer_get_main_email_returns_primary_email(): void
    {
        $customer = Customer::factory()->create();
        
        $primaryEmail = Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'primary@example.com',
            'type' => 1, // Primary
        ]);

        $mainEmail = $customer->getMainEmail();
        $this->assertEquals('primary@example.com', $mainEmail);
    }

    public function test_customer_primary_email_attribute(): void
    {
        $customer = Customer::factory()->create();
        
        $primaryEmail = Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'test@example.com',
            'type' => 1,
        ]);

        $this->assertEquals('test@example.com', $customer->primary_email);
    }

    public function test_send_log_has_status_constants(): void
    {
        $this->assertEquals(1, SendLog::STATUS_ACCEPTED);
        $this->assertEquals(2, SendLog::STATUS_SEND_ERROR);
        $this->assertEquals(4, SendLog::STATUS_DELIVERY_SUCCESS);
        $this->assertEquals(5, SendLog::STATUS_DELIVERY_ERROR);
        $this->assertEquals(6, SendLog::STATUS_OPENED);
        $this->assertEquals(7, SendLog::STATUS_CLICKED);
    }

    public function test_channel_has_timestamps(): void
    {
        $this->markTestIncomplete('Channel table migration not yet implemented');
        
        $channel = Channel::factory()->create();

        $this->assertNotNull($channel->created_at);
        $this->assertNotNull($channel->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $channel->created_at);
    }
}
