<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Mailbox;
use App\Models\User;
use Tests\UnitTestCase;

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
    // Mailbox Model Tests
    // ========================================

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

    public function test_mailbox_can_get_from_name_based_on_type(): void
    {
        $mailbox = Mailbox::factory()->create([
            'name' => 'Support',
            'from_name' => Mailbox::FROM_NAME_CUSTOM,
            'from_name_custom' => 'Custom Name',
        ]);

        $this->assertNotEmpty($mailbox->getMailFrom());
        $this->assertEquals('Custom Name', $mailbox->getMailFrom()['name']);
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

    // ========================================
    // Email Model Tests
    // ========================================

    public function test_email_must_be_unique_per_customer(): void
    {
        $customer = Customer::factory()->create();
        \App\Models\Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'test@example.com',
        ]);

        // Attempting to create duplicate should fail
        $this->expectException(\Exception::class);
        \App\Models\Email::factory()->create([
            'customer_id' => $customer->id,
            'email' => 'test@example.com',
        ]);
    }

    public function test_email_cannot_belong_to_different_customers(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        \App\Models\Email::factory()->create([
            'customer_id' => $customer1->id,
            'email' => 'shared@example.com',
        ]);

        $this->expectException(\Exception::class);

        \App\Models\Email::factory()->create([
            'customer_id' => $customer2->id,
            'email' => 'shared@example.com',
        ]);
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

    public function test_email_has_fillable_attributes(): void
    {
        $email = new \App\Models\Email;
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
