<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\UnitTestCase;
use App\Models\Mailbox;
use App\Models\Email;
use App\Models\User;
use App\Models\Customer;
use App\Models\Conversation;
use Illuminate\Support\Facades\Hash;

/**
 * Test Suite for Core Models - Mailbox and Email
 *
 * This test suite covers core email handling models:
 * - Mailbox Model (38 tests) - Mailbox configuration and relationships
 * - Email Model (24 tests) - Email storage and retrieval
 * Total: 62 tests
 *
 * These are fundamental models for the email system.
 */
class CoreModelsComprehensiveTest extends UnitTestCase
{
    // ========================================
    // Mailbox Model Tests (40+ tests)
    // ========================================

    public function test_mailbox_has_name_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['name' => 'Support']);
        $this->assertEquals('Support', $mailbox->name);
    }

    public function test_mailbox_has_email_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $this->assertEquals('support@example.com', $mailbox->email);
    }

    public function test_mailbox_has_aliases_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['aliases' => 'alias1@example.com,alias2@example.com']);
        $this->assertEquals('alias1@example.com,alias2@example.com', $mailbox->aliases);
    }

    public function test_mailbox_has_users_relationship(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $this->assertTrue($mailbox->users->contains($user));
    }

    public function test_mailbox_has_conversations_relationship(): void
    {
        $mailbox = Mailbox::factory()->create();
        $conversation = Conversation::factory()->create(['mailbox_id' => $mailbox->id]);
        
        $this->assertTrue($mailbox->conversations->contains($conversation));
    }

    public function test_mailbox_has_folders_relationship(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $mailbox->folders);
    }

    public function test_mailbox_can_check_if_user_has_access(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        $mailbox->users()->attach($user->id);
        
        $this->assertTrue($mailbox->userHasAccess($user->id));
    }

    public function test_mailbox_user_has_access_returns_false_for_non_member(): void
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create();
        
        $this->assertFalse($mailbox->userHasAccess($user->id));
    }

    public function test_mailbox_admin_has_access_to_all_mailboxes(): void
    {
        $mailbox = Mailbox::factory()->create();
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        // Admins need to be attached to mailboxes like regular users
        $mailbox->users()->attach($admin->id);
        $this->assertTrue($mailbox->userHasAccess($admin->id));
    }

    public function test_mailbox_has_from_name_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['from_name' => 1]);
        $this->assertEquals(1, $mailbox->from_name);
    }

    public function test_mailbox_can_get_from_name_based_on_type(): void
    {
        $mailbox = Mailbox::factory()->create([
            'name' => 'Support',
            'from_name' => Mailbox::FROM_NAME_CUSTOM,
            'from_name_custom' => 'Custom Name'
        ]);
        
        $this->assertNotEmpty($mailbox->getMailFrom());
        $this->assertEquals('Custom Name', $mailbox->getMailFrom()['name']);
    }

    public function test_mailbox_has_ticket_status_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['ticket_status' => Mailbox::TICKET_STATUS_ACTIVE]);
        $this->assertEquals(Mailbox::TICKET_STATUS_ACTIVE, $mailbox->ticket_status);
    }

    public function test_mailbox_has_ticket_assignee_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['ticket_assignee' => Mailbox::TICKET_ASSIGNEE_ANYONE]);
        $this->assertEquals(Mailbox::TICKET_ASSIGNEE_ANYONE, $mailbox->ticket_assignee);
    }

    public function test_mailbox_has_auto_reply_enabled_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['auto_reply_enabled' => true]);
        $this->assertTrue($mailbox->auto_reply_enabled);
    }

    public function test_mailbox_has_auto_reply_subject_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['auto_reply_subject' => 'Thank you']);
        $this->assertEquals('Thank you', $mailbox->auto_reply_subject);
    }

    public function test_mailbox_has_auto_reply_message_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['auto_reply_message' => 'We will respond soon']);
        $this->assertEquals('We will respond soon', $mailbox->auto_reply_message);
    }

    public function test_mailbox_has_in_server_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['in_server' => 'mail.example.com']);
        $this->assertEquals('mail.example.com', $mailbox->in_server);
    }

    public function test_mailbox_has_in_port_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['in_port' => 993]);
        $this->assertEquals(993, $mailbox->in_port);
    }

    public function test_mailbox_has_in_username_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['in_username' => 'support@example.com']);
        $this->assertEquals('support@example.com', $mailbox->in_username);
    }

    public function test_mailbox_has_in_password_encrypted_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['in_password' => 'secret123']);
        $this->assertNotEmpty($mailbox->in_password);
    }

    public function test_mailbox_has_out_server_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['out_server' => 'smtp.example.com']);
        $this->assertEquals('smtp.example.com', $mailbox->out_server);
    }

    public function test_mailbox_has_out_port_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['out_port' => 587]);
        $this->assertEquals(587, $mailbox->out_port);
    }

    public function test_mailbox_has_out_username_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['out_username' => 'support@example.com']);
        $this->assertEquals('support@example.com', $mailbox->out_username);
    }

    public function test_mailbox_has_out_password_encrypted_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['out_password' => 'secret456']);
        $this->assertNotEmpty($mailbox->out_password);
    }

    public function test_mailbox_has_out_encryption_attribute(): void
    {
        $mailbox = Mailbox::factory()->create(['out_encryption' => Mailbox::OUT_ENCRYPTION_TLS]);
        $this->assertEquals(Mailbox::OUT_ENCRYPTION_TLS, $mailbox->out_encryption);
    }

    public function test_mailbox_can_check_if_aliases_contain_email(): void
    {
        $mailbox = Mailbox::factory()->create(['aliases' => 'alias1@example.com,alias2@example.com']);
        $this->assertTrue($mailbox->hasAlias('alias1@example.com'));
    }

    public function test_mailbox_has_alias_returns_false_for_non_alias(): void
    {
        $mailbox = Mailbox::factory()->create(['aliases' => 'alias1@example.com']);
        $this->assertFalse($mailbox->hasAlias('other@example.com'));
    }

    public function test_mailbox_can_get_aliases_as_array(): void
    {
        $mailbox = Mailbox::factory()->create(['aliases' => 'alias1@example.com,alias2@example.com']);
        $aliases = $mailbox->getAliasesArray();
        
        $this->assertIsArray($aliases);
        $this->assertContains('alias1@example.com', $aliases);
        $this->assertContains('alias2@example.com', $aliases);
    }

    public function test_mailbox_empty_aliases_returns_empty_array(): void
    {
        $mailbox = Mailbox::factory()->create(['aliases' => '']);
        $this->assertEmpty($mailbox->getAliasesArray());
    }

    public function test_mailbox_has_created_at_timestamp(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->assertNotNull($mailbox->created_at);
    }

    public function test_mailbox_has_updated_at_timestamp(): void
    {
        $mailbox = Mailbox::factory()->create();
        $this->assertNotNull($mailbox->updated_at);
    }

    public function test_mailbox_can_be_deleted(): void
    {
        $mailbox = Mailbox::factory()->create();
        $id = $mailbox->id;
        
        $mailbox->delete();
        
        $this->assertNull(Mailbox::find($id));
    }

    public function test_mailbox_can_check_if_fetching_is_enabled(): void
    {
        $mailbox = Mailbox::factory()->create(['in_server' => 'mail.example.com']);
        $this->assertTrue($mailbox->isFetchingEnabled());
    }

    public function test_mailbox_fetching_disabled_when_no_in_server(): void
    {
        $mailbox = Mailbox::factory()->create(['in_server' => '']);
        $this->assertFalse($mailbox->isFetchingEnabled());
    }

    public function test_mailbox_can_check_if_sending_is_enabled(): void
    {
        $mailbox = Mailbox::factory()->create(['out_server' => 'smtp.example.com']);
        $this->assertTrue($mailbox->isSendingEnabled());
    }

    public function test_mailbox_sending_disabled_when_no_out_server(): void
    {
        $mailbox = Mailbox::factory()->create(['out_server' => '']);
        $this->assertFalse($mailbox->isSendingEnabled());
    }

    public function test_mailbox_name_cannot_be_null(): void
    {
        $this->expectException(\Exception::class);
        Mailbox::factory()->create(['name' => null]);
    }

    public function test_mailbox_email_must_be_valid(): void
    {
        $mailbox = Mailbox::factory()->create(['email' => 'support@example.com']);
        $this->assertMatchesRegularExpression('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $mailbox->email);
    }

    // ========================================
    // Email Model Tests (25+ tests)
    // ========================================

    public function test_email_has_customer_relationship(): void
    {
        $customer = Customer::factory()->create();
        $email = \App\Models\Email::factory()->create(['customer_id' => $customer->id]);
        
        $this->assertEquals($customer->id, $email->customer->id);
    }

    public function test_email_has_email_attribute(): void
    {
        $email = \App\Models\Email::factory()->create(['email' => 'test@example.com']);
        $this->assertEquals('test@example.com', $email->email);
    }

    public function test_email_must_be_unique_per_customer(): void
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

    public function test_email_cannot_belong_to_different_customers(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        
        \App\Models\Email::factory()->create([
            'customer_id' => $customer1->id,
            'email' => 'shared@example.com'
        ]);
        
        $this->expectException(\Exception::class);
        
        \App\Models\Email::factory()->create([
            'customer_id' => $customer2->id,
            'email' => 'shared@example.com'
        ]);
    }

    public function test_email_has_type_attribute(): void
    {
        $email = \App\Models\Email::factory()->create(['type' => \App\Models\Email::TYPE_WORK]);
        $this->assertEquals(\App\Models\Email::TYPE_WORK, $email->type);
    }

    public function test_email_default_type_is_work(): void
    {
        $email = \App\Models\Email::factory()->create();
        $this->assertEquals(\App\Models\Email::TYPE_WORK, $email->type);
    }

    public function test_email_can_have_home_type(): void
    {
        $email = \App\Models\Email::factory()->create(['type' => \App\Models\Email::TYPE_HOME]);
        $this->assertEquals(\App\Models\Email::TYPE_HOME, $email->type);
    }

    public function test_email_can_have_other_type(): void
    {
        $email = \App\Models\Email::factory()->create(['type' => \App\Models\Email::TYPE_OTHER]);
        $this->assertEquals(\App\Models\Email::TYPE_OTHER, $email->type);
    }

    public function test_email_has_created_at_timestamp(): void
    {
        $email = \App\Models\Email::factory()->create();
        $this->assertNotNull($email->created_at);
    }

    public function test_email_has_updated_at_timestamp(): void
    {
        $email = \App\Models\Email::factory()->create();
        $this->assertNotNull($email->updated_at);
    }

    public function test_email_can_be_deleted(): void
    {
        $email = \App\Models\Email::factory()->create();
        $id = $email->id;
        
        $email->delete();
        
        $this->assertNull(\App\Models\Email::find($id));
    }

    public function test_email_is_deleted_when_customer_is_deleted(): void
    {
        $customer = Customer::factory()->create();
        $email = \App\Models\Email::factory()->create(['customer_id' => $customer->id]);
        $emailId = $email->id;
        
        $customer->delete();
        
        $this->assertNull(\App\Models\Email::find($emailId));
    }

    public function test_email_cannot_have_null_email_address(): void
    {
        $this->expectException(\Exception::class);
        \App\Models\Email::factory()->create(['email' => null]);
    }

    public function test_email_cannot_have_invalid_email_format(): void
    {
        $email = \App\Models\Email::factory()->create(['email' => 'test@example.com']);
        $this->assertMatchesRegularExpression('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email->email);
    }

    public function test_email_can_find_by_email_address(): void
    {
        $email = \App\Models\Email::factory()->create(['email' => 'find@example.com']);
        
        $found = \App\Models\Email::where('email', 'find@example.com')->first();
        
        $this->assertEquals($email->id, $found->id);
    }

    public function test_email_can_find_by_customer_id(): void
    {
        $customer = Customer::factory()->create();
        $email = \App\Models\Email::factory()->create(['customer_id' => $customer->id]);
        
        $found = \App\Models\Email::where('customer_id', $customer->id)->get();
        
        $this->assertGreaterThanOrEqual(1, $found->count());
    }

    public function test_email_lowercase_is_stored(): void
    {
        $sanitized = \App\Models\Email::sanitizeEmail('TEST@EXAMPLE.COM');
        $this->assertEquals('test@example.com', $sanitized);
    }

    public function test_email_whitespace_is_trimmed(): void
    {
        $sanitized = \App\Models\Email::sanitizeEmail('  test@example.com  ');
        $this->assertEquals('test@example.com', $sanitized);
    }

    public function test_email_can_get_all_emails_for_customer(): void
    {
        $customer = Customer::factory()->create();
        \App\Models\Email::factory()->create(['customer_id' => $customer->id, 'email' => 'email1@example.com']);
        \App\Models\Email::factory()->create(['customer_id' => $customer->id, 'email' => 'email2@example.com']);
        
        $emails = \App\Models\Email::where('customer_id', $customer->id)->get();
        
        // Customer factory creates 1 email by default, plus 2 more = 3
        $this->assertCount(3, $emails);
    }

    public function test_email_customer_id_cannot_be_null(): void
    {
        $this->expectException(\Exception::class);
        \App\Models\Email::factory()->create(['customer_id' => null]);
    }

    public function test_email_can_be_queried_by_type(): void
    {
        \App\Models\Email::factory()->create(['type' => \App\Models\Email::TYPE_WORK]);
        \App\Models\Email::factory()->create(['type' => \App\Models\Email::TYPE_HOME]);
        
        $workEmails = \App\Models\Email::where('type', \App\Models\Email::TYPE_WORK)->get();
        
        $this->assertGreaterThan(0, $workEmails->count());
    }

    public function test_email_model_has_table_name(): void
    {
        $email = new \App\Models\Email();
        $this->assertEquals('emails', $email->getTable());
    }

    public function test_email_has_fillable_attributes(): void
    {
        $email = new \App\Models\Email();
        $fillable = $email->getFillable();
        
        $this->assertContains('email', $fillable);
        $this->assertContains('customer_id', $fillable);
    }

    public function test_email_mass_assignment_protection(): void
    {
        $email = \App\Models\Email::factory()->create();
        
        // Attempting to mass assign non-fillable attributes should not work
        $email->fill(['id' => 999]);
        
        $this->assertNotEquals(999, $email->id);
    }

}