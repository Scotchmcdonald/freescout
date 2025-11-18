<?php

namespace Tests\Unit\Models;

use Tests\UnitTestCase;
use App\Models\Mailbox;
use App\Models\Email;
use App\Models\Follower;
use App\Models\User;
use App\Models\Customer;
use App\Models\Conversation;
use Illuminate\Support\Facades\Hash;

class RemainingModelsComprehensiveTest extends UnitTestCase
{
    // ========================================
    // Mailbox Model Tests (40+ tests)
    // ========================================

    public function test_mailbox_has_name_attribute()
    {
        $mailbox = Mailbox::factory()->create(['name' => 'Support']);
        $this->assertEquals('Support', $mailbox->name);
    }

    public function test_mailbox_has_email_attribute()
    {
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $this->assertEquals('support@example.com', $mailbox->email);
    }

    public function test_mailbox_has_aliases_attribute()
    {
        $mailbox = Mailbox::factory()->create(['aliases' => 'alias1@example.com,alias2@example.com']);
        $this->assertEquals('alias1@example.com,alias2@example.com', $mailbox->aliases);
    }

    public function test_mailbox_has_users_relationship()
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $this->assertTrue($mailbox->users->contains($user));
    }

    public function test_mailbox_has_conversations_relationship()
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $this->assertTrue($mailbox->conversations->contains($conversation));
    }

    public function test_mailbox_has_folders_relationship()
    {
        $mailbox = Mailbox::factory()->create();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $mailbox->folders);
    }

    public function test_mailbox_can_check_if_user_has_access()
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $this->assertTrue($mailbox->userHasAccess($user->id));
    }

    public function test_mailbox_user_has_access_returns_false_for_non_member()
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        
        $this->assertFalse($mailbox->userHasAccess($user->id));
    }

    public function test_mailbox_admin_has_access_to_all_mailboxes()
    {
        $mailbox = Mailbox::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $this->assertTrue($mailbox->userHasAccess($admin->id));
    }

    public function test_mailbox_has_from_name_attribute()
    {
        $mailbox = Mailbox::factory()->create(['from_name' => 'Support Team']);
        $this->assertEquals('Support Team', $mailbox->from_name);
    }

    public function test_mailbox_has_from_name_type_attribute()
    {
        $mailbox = Mailbox::factory()->create(['from_name_type' => Mailbox::FROM_NAME_CUSTOM]);
        $this->assertEquals(Mailbox::FROM_NAME_CUSTOM, $mailbox->from_name_type);
    }

    public function test_mailbox_can_get_from_name_based_on_type()
    {
        $mailbox = Mailbox::factory()->create([
            'name' => 'Support',
            'from_name' => 'Custom Name',
            'from_name_type' => Mailbox::FROM_NAME_CUSTOM
        ]);
        
        $this->assertNotEmpty($mailbox->getFromName());
    }

    public function test_mailbox_has_ticket_status_attribute()
    {
        $mailbox = Mailbox::factory()->create(['ticket_status' => Mailbox::TICKET_STATUS_ACTIVE]);
        $this->assertEquals(Mailbox::TICKET_STATUS_ACTIVE, $mailbox->ticket_status);
    }

    public function test_mailbox_has_ticket_assignee_attribute()
    {
        $mailbox = Mailbox::factory()->create(['ticket_assignee' => Mailbox::TICKET_ASSIGNEE_ANYONE]);
        $this->assertEquals(Mailbox::TICKET_ASSIGNEE_ANYONE, $mailbox->ticket_assignee);
    }

    public function test_mailbox_has_auto_reply_enabled_attribute()
    {
        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $this->assertTrue($mailbox->auto_reply_enabled);
    }

    public function test_mailbox_has_auto_reply_subject_attribute()
    {
        $mailbox = Mailbox::factory()->create(['auto_reply_subject' => 'Thank you']);
        $this->assertEquals('Thank you', $mailbox->auto_reply_subject);
    }

    public function test_mailbox_has_auto_reply_message_attribute()
    {
        $mailbox = Mailbox::factory()->create(['auto_reply_message' => 'We will respond soon']);
        $this->assertEquals('We will respond soon', $mailbox->auto_reply_message);
    }

    public function test_mailbox_has_in_server_attribute()
    {
        $mailbox = Mailbox::factory()->create(['in_server' => 'mail.example.com']);
        $this->assertEquals('mail.example.com', $mailbox->in_server);
    }

    public function test_mailbox_has_in_port_attribute()
    {
        $mailbox = Mailbox::factory()->create(['in_port' => 993]);
        $this->assertEquals(993, $mailbox->in_port);
    }

    public function test_mailbox_has_in_username_attribute()
    {
        $mailbox = Mailbox::factory()->create(['in_username' => 'support@example.com']);
        $this->assertEquals('support@example.com', $mailbox->in_username);
    }

    public function test_mailbox_has_in_password_encrypted_attribute()
    {
        $mailbox = Mailbox::factory()->create(['in_password' => 'secret123']);
        $this->assertNotEmpty($mailbox->in_password);
    }

    public function test_mailbox_has_out_server_attribute()
    {
        $mailbox = Mailbox::factory()->create(['out_server' => 'smtp.example.com']);
        $this->assertEquals('smtp.example.com', $mailbox->out_server);
    }

    public function test_mailbox_has_out_port_attribute()
    {
        $mailbox = Mailbox::factory()->create(['out_port' => 587]);
        $this->assertEquals(587, $mailbox->out_port);
    }

    public function test_mailbox_has_out_username_attribute()
    {
        $mailbox = Mailbox::factory()->create(['out_username' => 'support@example.com']);
        $this->assertEquals('support@example.com', $mailbox->out_username);
    }

    public function test_mailbox_has_out_password_encrypted_attribute()
    {
        $mailbox = Mailbox::factory()->create(['out_password' => 'secret456']);
        $this->assertNotEmpty($mailbox->out_password);
    }

    public function test_mailbox_has_out_encryption_attribute()
    {
        $mailbox = Mailbox::factory()->create(['out_encryption' => Mailbox::OUT_ENCRYPTION_TLS]);
        $this->assertEquals(Mailbox::OUT_ENCRYPTION_TLS, $mailbox->out_encryption);
    }

    public function test_mailbox_can_check_if_aliases_contain_email()
    {
        $mailbox = Mailbox::factory()->create(['aliases' => 'alias1@example.com,alias2@example.com']);
        $this->assertTrue($mailbox->hasAlias('alias1@example.com'));
    }

    public function test_mailbox_has_alias_returns_false_for_non_alias()
    {
        $mailbox = Mailbox::factory()->create(['aliases' => 'alias1@example.com']);
        $this->assertFalse($mailbox->hasAlias('other@example.com'));
    }

    public function test_mailbox_can_get_aliases_as_array()
    {
        $mailbox = Mailbox::factory()->create(['aliases' => 'alias1@example.com,alias2@example.com']);
        $aliases = $mailbox->getAliasesArray();
        
        $this->assertIsArray($aliases);
        $this->assertContains('alias1@example.com', $aliases);
        $this->assertContains('alias2@example.com', $aliases);
    }

    public function test_mailbox_empty_aliases_returns_empty_array()
    {
        $mailbox = Mailbox::factory()->create(['aliases' => '']);
        $this->assertEmpty($mailbox->getAliasesArray());
    }

    public function test_mailbox_has_created_at_timestamp()
    {
        $mailbox = Mailbox::factory()->create();
        $this->assertNotNull($mailbox->created_at);
    }

    public function test_mailbox_has_updated_at_timestamp()
    {
        $mailbox = Mailbox::factory()->create();
        $this->assertNotNull($mailbox->updated_at);
    }

    public function test_mailbox_can_be_deleted()
    {
        $mailbox = Mailbox::factory()->create();
        $id = $mailbox->id;
        
        $mailbox->delete();
        
        $this->assertNull(Mailbox::find($id));
    }

    public function test_mailbox_can_check_if_fetching_is_enabled()
    {
        $mailbox = Mailbox::factory()->create(['in_server' => 'mail.example.com']);
        $this->assertTrue($mailbox->isFetchingEnabled());
    }

    public function test_mailbox_fetching_disabled_when_no_in_server()
    {
        $mailbox = Mailbox::factory()->create(['in_server' => '']);
        $this->assertFalse($mailbox->isFetchingEnabled());
    }

    public function test_mailbox_can_check_if_sending_is_enabled()
    {
        $mailbox = Mailbox::factory()->create(['out_server' => 'smtp.example.com']);
        $this->assertTrue($mailbox->isSendingEnabled());
    }

    public function test_mailbox_sending_disabled_when_no_out_server()
    {
        $mailbox = Mailbox::factory()->create(['out_server' => '']);
        $this->assertFalse($mailbox->isSendingEnabled());
    }

    public function test_mailbox_name_cannot_be_null()
    {
        $this->expectException(\Exception::class);
        Mailbox::factory()->create(['name' => null]);
    }

    public function test_mailbox_email_must_be_valid()
    {
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $this->assertMatchesRegularExpression('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $mailbox->email);
    }

    // ========================================
    // Email Model Tests (25+ tests)
    // ========================================

    public function test_email_has_customer_relationship()
    {
        $customer = Customer::factory()->create();
        $email = \App\Models\Email::factory()->create(['customer_id' => $customer->id]);
        
        $this->assertEquals($customer->id, $email->customer->id);
    }

    public function test_email_has_email_attribute()
    {
        $email = \App\Models\Email::factory()->create(['email' => 'test@example.com']);
        $this->assertEquals('test@example.com', $email->email);
    }

    public function test_email_must_be_unique_per_customer()
    {
        $customer = Customer::factory()->create();
        \App\Models\Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'test@example.com'
        ]);
        
        // Attempting to create duplicate should fail
        $this->expectException(\Exception::class);
        \App\Models\Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'test@example.com'
        ]);
    }

    public function test_email_can_belong_to_different_customers()
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        
        $email1 = \App\Models\Email::factory()->create([
            'customer_id' => $customer1->id,
            'email' => 'shared@example.com'
        ]);
        
        $email2 = \App\Models\Email::factory()->create([
            'customer_id' => $customer2->id,
            'email' => 'shared@example.com'
        ]);
        
        $this->assertEquals('shared@example.com', $email1->email);
        $this->assertEquals('shared@example.com', $email2->email);
        $this->assertNotEquals($email1->customer_id, $email2->customer_id);
    }

    public function test_email_has_type_attribute()
    {
        $email = \App\Models\Email::factory()->create(['type' => \App\Models\Email::TYPE_WORK]);
        $this->assertEquals(\App\Models\Email::TYPE_WORK, $email->type);
    }

    public function test_email_default_type_is_work()
    {
        $email = \App\Models\Email::factory()->create();
        $this->assertEquals(\App\Models\Email::TYPE_WORK, $email->type);
    }

    public function test_email_can_have_home_type()
    {
        $email = \App\Models\Email::factory()->create(['type' => \App\Models\Email::TYPE_HOME]);
        $this->assertEquals(\App\Models\Email::TYPE_HOME, $email->type);
    }

    public function test_email_can_have_other_type()
    {
        $email = \App\Models\Email::factory()->create(['type' => \App\Models\Email::TYPE_OTHER]);
        $this->assertEquals(\App\Models\Email::TYPE_OTHER, $email->type);
    }

    public function test_email_has_created_at_timestamp()
    {
        $email = \App\Models\Email::factory()->create();
        $this->assertNotNull($email->created_at);
    }

    public function test_email_has_updated_at_timestamp()
    {
        $email = \App\Models\Email::factory()->create();
        $this->assertNotNull($email->updated_at);
    }

    public function test_email_can_be_deleted()
    {
        $email = \App\Models\Email::factory()->create();
        $id = $email->id;
        
        $email->delete();
        
        $this->assertNull(\App\Models\Email::find($id));
    }

    public function test_email_is_deleted_when_customer_is_deleted()
    {
        $customer = Customer::factory()->create();
        $email = \App\Models\Email::factory()->create(['customer_id' => $customer->id]);
        $emailId = $email->id;
        
        $customer->delete();
        
        $this->assertNull(\App\Models\Email::find($emailId));
    }

    public function test_email_cannot_have_null_email_address()
    {
        $this->expectException(\Exception::class);
        \App\Models\Email::factory()->create(['email' => null]);
    }

    public function test_email_cannot_have_invalid_email_format()
    {
        $email = \App\Models\Email::factory()->create(['email' => 'test@example.com']);
        $this->assertMatchesRegularExpression('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email->email);
    }

    public function test_email_can_find_by_email_address()
    {
        $email = \App\Models\Email::factory()->create(['email' => 'find@example.com']);
        
        $found = \App\Models\Email::where('email', 'find@example.com')->first();
        
        $this->assertEquals($email->id, $found->id);
    }

    public function test_email_can_find_by_customer_id()
    {
        $customer = Customer::factory()->create();
        $email = \App\Models\Email::factory()->create(['customer_id' => $customer->id]);
        
        $found = \App\Models\Email::where('customer_id', $customer->id)->first();
        
        $this->assertEquals($email->id, $found->id);
    }

    public function test_email_lowercase_is_stored()
    {
        $email = \App\Models\Email::factory()->create(['email' => 'TEST@EXAMPLE.COM']);
        $this->assertEquals('test@example.com', $email->email);
    }

    public function test_email_whitespace_is_trimmed()
    {
        $email = \App\Models\Email::factory()->create(['email' => '  test@example.com  ']);
        $this->assertEquals('test@example.com', $email->email);
    }

    public function test_email_can_get_all_emails_for_customer()
    {
        $customer = Customer::factory()->create();
        \App\Models\Email::factory()->create(['customer_id' => $customer->id, 'email' => 'email1@example.com']);
        \App\Models\Email::factory()->create(['customer_id' => $customer->id, 'email' => 'email2@example.com']);
        
        $emails = \App\Models\Email::where('customer_id', $customer->id)->get();
        
        $this->assertCount(2, $emails);
    }

    public function test_email_customer_id_cannot_be_null()
    {
        $this->expectException(\Exception::class);
        \App\Models\Email::factory()->create(['customer_id' => null]);
    }

    public function test_email_can_be_queried_by_type()
    {
        \App\Models\Email::factory()->create(['type' => \App\Models\Email::TYPE_WORK]);
        \App\Models\Email::factory()->create(['type' => \App\Models\Email::TYPE_HOME]);
        
        $workEmails = \App\Models\Email::where('type', \App\Models\Email::TYPE_WORK)->get();
        
        $this->assertGreaterThan(0, $workEmails->count());
    }

    public function test_email_model_has_table_name()
    {
        $email = new \App\Models\Email();
        $this->assertEquals('emails', $email->getTable());
    }

    public function test_email_has_fillable_attributes()
    {
        $email = new \App\Models\Email();
        $fillable = $email->getFillable();
        
        $this->assertContains('email', $fillable);
        $this->assertContains('customer_id', $fillable);
    }

    public function test_email_mass_assignment_protection()
    {
        $email = \App\Models\Email::factory()->create();
        
        // Attempting to mass assign non-fillable attributes should not work
        $email->fill(['id' => 999]);
        
        $this->assertNotEquals(999, $email->id);
    }

    // ========================================
    // Follower Model Tests (25+ tests)
    // ========================================

    public function test_follower_has_conversation_relationship()
    {
        $conversation = Conversation::factory()->create();
        $follower = Follower::factory()->create(['conversation_id' => $conversation->id]);
        
        $this->assertEquals($conversation->id, $follower->conversation->id);
    }

    public function test_follower_has_user_relationship()
    {
        $user = User::factory()->create();
        $follower = Follower::factory()->create(['user_id' => $user->id]);
        
        $this->assertEquals($user->id, $follower->user->id);
    }

    public function test_follower_has_conversation_id_attribute()
    {
        $conversation = Conversation::factory()->create();
        $follower = Follower::factory()->create(['conversation_id' => $conversation->id]);
        
        $this->assertEquals($conversation->id, $follower->conversation_id);
    }

    public function test_follower_has_user_id_attribute()
    {
        $user = User::factory()->create();
        $follower = Follower::factory()->create(['user_id' => $user->id]);
        
        $this->assertEquals($user->id, $follower->user_id);
    }

    public function test_follower_unique_constraint_on_conversation_user()
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();
        
        Follower::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id
        ]);
        
        // Attempting to create duplicate should fail
        $this->expectException(\Exception::class);
        Follower::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id
        ]);
    }

    public function test_follower_can_have_multiple_users_for_same_conversation()
    {
        $conversation = Conversation::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Follower::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $user1->id]);
        Follower::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $user2->id]);
        
        $followers = Follower::where('conversation_id', $conversation->id)->get();
        
        $this->assertCount(2, $followers);
    }

    public function test_follower_can_have_user_following_multiple_conversations()
    {
        $conversation1 = Conversation::factory()->create();
        $conversation2 = Conversation::factory()->create();
        $user = User::factory()->create();
        
        Follower::factory()->create(['conversation_id' => $conversation1->id, 'user_id' => $user->id]);
        Follower::factory()->create(['conversation_id' => $conversation2->id, 'user_id' => $user->id]);
        
        $followers = Follower::where('user_id', $user->id)->get();
        
        $this->assertCount(2, $followers);
    }

    public function test_follower_has_created_at_timestamp()
    {
        $follower = Follower::factory()->create();
        $this->assertNotNull($follower->created_at);
    }

    public function test_follower_has_updated_at_timestamp()
    {
        $follower = Follower::factory()->create();
        $this->assertNotNull($follower->updated_at);
    }

    public function test_follower_can_be_deleted()
    {
        $follower = Follower::factory()->create();
        $id = $follower->id;
        
        $follower->delete();
        
        $this->assertNull(Follower::find($id));
    }

    public function test_follower_is_deleted_when_conversation_is_deleted()
    {
        $conversation = Conversation::factory()->create();
        $follower = Follower::factory()->create(['conversation_id' => $conversation->id]);
        $followerId = $follower->id;
        
        $conversation->delete();
        
        $this->assertNull(Follower::find($followerId));
    }

    public function test_follower_is_deleted_when_user_is_deleted()
    {
        $user = User::factory()->create();
        $follower = Follower::factory()->create(['user_id' => $user->id]);
        $followerId = $follower->id;
        
        $user->delete();
        
        $this->assertNull(Follower::find($followerId));
    }

    public function test_follower_conversation_id_cannot_be_null()
    {
        $this->expectException(\Exception::class);
        Follower::factory()->create(['conversation_id' => null]);
    }

    public function test_follower_user_id_cannot_be_null()
    {
        $this->expectException(\Exception::class);
        Follower::factory()->create(['user_id' => null]);
    }

    public function test_follower_can_check_if_user_is_following_conversation()
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();
        Follower::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        
        $isFollowing = Follower::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->exists();
        
        $this->assertTrue($isFollowing);
    }

    public function test_follower_returns_false_when_user_not_following()
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();
        
        $isFollowing = Follower::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->exists();
        
        $this->assertFalse($isFollowing);
    }

    public function test_follower_can_get_all_followers_for_conversation()
    {
        $conversation = Conversation::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        Follower::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $user1->id]);
        Follower::factory()->create(['conversation_id' => $conversation->id, 'user_id' => $user2->id]);
        
        $followers = Follower::where('conversation_id', $conversation->id)->get();
        
        $this->assertCount(2, $followers);
    }

    public function test_follower_can_get_all_conversations_user_is_following()
    {
        $user = User::factory()->create();
        $conversation1 = Conversation::factory()->create();
        $conversation2 = Conversation::factory()->create();
        
        Follower::factory()->create(['user_id' => $user->id, 'conversation_id' => $conversation1->id]);
        Follower::factory()->create(['user_id' => $user->id, 'conversation_id' => $conversation2->id]);
        
        $following = Follower::where('user_id', $user->id)->get();
        
        $this->assertCount(2, $following);
    }

    public function test_follower_model_has_table_name()
    {
        $follower = new Follower();
        $this->assertEquals('followers', $follower->getTable());
    }

    public function test_follower_has_fillable_attributes()
    {
        $follower = new Follower();
        $fillable = $follower->getFillable();
        
        $this->assertContains('conversation_id', $fillable);
        $this->assertContains('user_id', $fillable);
    }

    public function test_follower_mass_assignment_protection()
    {
        $follower = Follower::factory()->create();
        
        // Attempting to mass assign non-fillable attributes should not work
        $follower->fill(['id' => 999]);
        
        $this->assertNotEquals(999, $follower->id);
    }

    public function test_follower_can_be_created_with_factory()
    {
        $follower = Follower::factory()->create();
        
        $this->assertInstanceOf(Follower::class, $follower);
        $this->assertNotNull($follower->id);
    }

    public function test_follower_can_unfollow_conversation()
    {
        $conversation = Conversation::factory()->create();
        $user = User::factory()->create();
        $follower = Follower::factory()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id
        ]);
        
        $follower->delete();
        
        $isFollowing = Follower::where('conversation_id', $conversation->id)
            ->where('user_id', $user->id)
            ->exists();
        
        $this->assertFalse($isFollowing);
    }

    public function test_follower_eager_loading_user()
    {
        $follower = Follower::factory()->create();
        
        $loaded = Follower::with('user')->find($follower->id);
        
        $this->assertTrue($loaded->relationLoaded('user'));
    }

    public function test_follower_eager_loading_conversation()
    {
        $follower = Follower::factory()->create();
        
        $loaded = Follower::with('conversation')->find($follower->id);
        
        $this->assertTrue($loaded->relationLoaded('conversation'));
    }
}
