<?php

declare(strict_types=1);

namespace Tests\Unit\Misc;

use App\Misc\MailHelper;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\User;
use Tests\UnitTestCase;

/**
 * Test MailHelper::replaceMailVars() method
 *
 * CRAP Score: 506 (Critical Priority)
 * Target Coverage: 90%+
 */
class MailHelperReplaceMailVarsTest extends UnitTestCase
{
    public function test_replace_mail_vars_with_empty_text_returns_empty(): void
    {
        $result = MailHelper::replaceMailVars('');

        $this->assertSame('', $result);
    }

    public function test_replace_mail_vars_with_no_vars_returns_original(): void
    {
        $text = 'Hello, this is plain text without variables.';

        $result = MailHelper::replaceMailVars($text);

        $this->assertSame($text, $result);
    }

    public function test_replace_mail_vars_with_conversation_subject(): void
    {
        $conversation = new Conversation(['subject' => 'Test Subject']);
        $text = 'Subject: {%subject%}';

        $result = MailHelper::replaceMailVars($text, ['conversation' => $conversation]);

        $this->assertStringContainsString('Test Subject', $result);
    }

    public function test_replace_mail_vars_with_conversation_number(): void
    {
        $conversation = new Conversation(['number' => 12345]);
        $text = 'Ticket #{%conversation.number%}';

        $result = MailHelper::replaceMailVars($text, ['conversation' => $conversation]);

        $this->assertStringContainsString('12345', $result);
    }

    public function test_replace_mail_vars_with_customer_email_from_conversation(): void
    {
        $conversation = new Conversation(['customer_email' => 'customer@example.com']);
        $text = 'Reply to: {%customer.email%}';

        $result = MailHelper::replaceMailVars($text, ['conversation' => $conversation]);

        $this->assertStringContainsString('customer@example.com', $result);
    }

    public function test_replace_mail_vars_with_mailbox_email(): void
    {
        $mailbox = new Mailbox(['email' => 'support@example.com']);
        $text = 'From: {%mailbox.email%}';

        $result = MailHelper::replaceMailVars($text, ['mailbox' => $mailbox]);

        $this->assertStringContainsString('support@example.com', $result);
    }

    public function test_replace_mail_vars_with_mailbox_name(): void
    {
        $mailbox = new Mailbox(['name' => 'Support Team']);
        $text = 'Team: {%mailbox.name%}';

        $result = MailHelper::replaceMailVars($text, ['mailbox' => $mailbox]);

        $this->assertStringContainsString('Support Team', $result);
    }

    public function test_replace_mail_vars_with_customer_full_name(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $text = 'Hello {%customer.fullName%}!';

        $result = MailHelper::replaceMailVars($text, ['customer' => $customer]);

        $this->assertStringContainsString('John Doe', $result);
    }

    public function test_replace_mail_vars_with_customer_first_name(): void
    {
        $customer = Customer::factory()->create(['first_name' => 'Jane']);
        $text = 'Hi {%customer.firstName%}!';

        $result = MailHelper::replaceMailVars($text, ['customer' => $customer]);

        $this->assertStringContainsString('Jane', $result);
    }

    public function test_replace_mail_vars_with_customer_last_name(): void
    {
        $customer = Customer::factory()->create(['last_name' => 'Smith']);
        $text = 'Dear {%customer.lastName%}';

        $result = MailHelper::replaceMailVars($text, ['customer' => $customer]);

        $this->assertStringContainsString('Smith', $result);
    }

    public function test_replace_mail_vars_with_customer_company(): void
    {
        $customer = Customer::factory()->create(['company' => 'Acme Corp']);
        $text = 'Company: {%customer.company%}';

        $result = MailHelper::replaceMailVars($text, ['customer' => $customer]);

        $this->assertStringContainsString('Acme Corp', $result);
    }

    public function test_replace_mail_vars_with_user_full_name(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Agent',
            'last_name' => 'Smith',
        ]);
        $text = 'Agent: {%user.fullName%}';

        $result = MailHelper::replaceMailVars($text, ['user' => $user]);

        $this->assertStringContainsString('Agent Smith', $result);
    }

    public function test_replace_mail_vars_with_user_first_name(): void
    {
        $user = User::factory()->create(['first_name' => 'Alice']);
        $text = 'From: {%user.firstName%}';

        $result = MailHelper::replaceMailVars($text, ['user' => $user]);

        $this->assertStringContainsString('Alice', $result);
    }

    public function test_replace_mail_vars_with_user_phone(): void
    {
        $user = User::factory()->create(['phone' => '555-1234']);
        $text = 'Call: {%user.phone%}';

        $result = MailHelper::replaceMailVars($text, ['user' => $user]);

        $this->assertStringContainsString('555-1234', $result);
    }

    public function test_replace_mail_vars_with_user_email(): void
    {
        $user = User::factory()->create(['email' => 'agent@example.com']);
        $text = 'Email: {%user.email%}';

        $result = MailHelper::replaceMailVars($text, ['user' => $user]);

        $this->assertStringContainsString('agent@example.com', $result);
    }

    public function test_replace_mail_vars_with_user_job_title(): void
    {
        $user = User::factory()->create(['job_title' => 'Support Manager']);
        $text = 'Title: {%user.jobTitle%}';

        $result = MailHelper::replaceMailVars($text, ['user' => $user]);

        $this->assertStringContainsString('Support Manager', $result);
    }

    public function test_replace_mail_vars_with_fallback_value_when_var_missing(): void
    {
        $text = 'Hello {%customer.firstName,fallback=Guest%}!';

        $result = MailHelper::replaceMailVars($text, []);

        $this->assertStringContainsString('Guest', $result);
    }

    public function test_replace_mail_vars_with_fallback_not_used_when_var_exists(): void
    {
        $customer = Customer::factory()->create(['first_name' => 'John']);
        $text = 'Hello {%customer.firstName,fallback=Guest%}!';

        $result = MailHelper::replaceMailVars($text, ['customer' => $customer]);

        $this->assertStringContainsString('John', $result);
        $this->assertStringNotContainsString('Guest', $result);
    }

    public function test_replace_mail_vars_with_empty_fallback(): void
    {
        $text = 'Hello {%customer.firstName,fallback=%}!';

        $result = MailHelper::replaceMailVars($text, []);

        $this->assertStringContainsString('Hello !', $result);
    }

    public function test_replace_mail_vars_with_multiple_vars(): void
    {
        $conversation = new Conversation(['subject' => 'Test', 'number' => 123]);
        $customer = Customer::factory()->create(['first_name' => 'John']);

        $text = 'Hi {%customer.firstName%}, ticket #{%conversation.number%}: {%subject%}';

        $result = MailHelper::replaceMailVars($text, [
            'conversation' => $conversation,
            'customer' => $customer,
        ]);

        $this->assertStringContainsString('John', $result);
        $this->assertStringContainsString('123', $result);
        $this->assertStringContainsString('Test', $result);
    }

    public function test_replace_mail_vars_preserves_newlines_as_html_br(): void
    {
        $customer = Customer::factory()->create(['first_name' => "John\nDoe"]);
        $text = 'Name: {%customer.firstName%}';

        $result = MailHelper::replaceMailVars($text, ['customer' => $customer]);

        $this->assertStringContainsString('<br />', $result);
    }

    public function test_replace_mail_vars_with_escape_true_escapes_html(): void
    {
        $customer = Customer::factory()->create(['first_name' => '<script>alert("XSS")</script>']);
        $text = 'Name: {%customer.firstName%}';

        $result = MailHelper::replaceMailVars($text, ['customer' => $customer], escape: true);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function test_replace_mail_vars_with_escape_false_preserves_html(): void
    {
        $customer = Customer::factory()->create(['first_name' => '<b>John</b>']);
        $text = 'Name: {%customer.firstName%}';

        $result = MailHelper::replaceMailVars($text, ['customer' => $customer], escape: false);

        $this->assertStringContainsString('<b>John</b>', $result);
    }

    public function test_replace_mail_vars_removes_non_replaced_placeholders_when_flag_true(): void
    {
        $text = 'Hello {%customer.firstName%}, your ticket is {%unknown.var%}';

        $result = MailHelper::replaceMailVars($text, [], escape: false, remove_non_replaced: true);

        $this->assertStringNotContainsString('{%unknown.var%}', $result);
        $this->assertStringContainsString('Hello', $result);
    }

    public function test_replace_mail_vars_keeps_non_replaced_placeholders_when_flag_false(): void
    {
        $text = 'Hello {%customer.firstName%}';

        $result = MailHelper::replaceMailVars($text, [], escape: false, remove_non_replaced: false);

        $this->assertStringContainsString('{%customer.firstName%}', $result);
    }

    public function test_replace_mail_vars_handles_null_values_gracefully(): void
    {
        $customer = Customer::factory()->create(['company' => null]);
        $text = 'Company: {%customer.company%}';

        $result = MailHelper::replaceMailVars($text, ['customer' => $customer]);

        $this->assertStringContainsString('Company:', $result);
    }

    public function test_replace_mail_vars_with_unicode_characters(): void
    {
        $customer = Customer::factory()->withUnicodeName()->create();
        $text = 'Customer: {%customer.fullName%}';

        $result = MailHelper::replaceMailVars($text, ['customer' => $customer]);

        $this->assertStringContainsString('山田', $result);
    }

    public function test_replace_mail_vars_with_emoji_in_values(): void
    {
        $customer = Customer::factory()->withEmoji()->create();
        $text = 'Hello {%customer.firstName%}!';

        $result = MailHelper::replaceMailVars($text, ['customer' => $customer]);

        $this->assertStringContainsString('😀', $result);
    }

    public function test_replace_mail_vars_complex_fallback_with_special_chars(): void
    {
        $text = 'Status: {%ticket.status,fallback=Open (Default)%}';

        $result = MailHelper::replaceMailVars($text, []);

        $this->assertStringContainsString('Open (Default)', $result);
    }

    public function test_replace_mail_vars_all_entity_types_together(): void
    {
        $conversation = Conversation::factory()->create(['subject' => 'Issue', 'number' => 789]);
        $customer = Customer::factory()->create(['first_name' => 'Jane']);
        $user = User::factory()->create(['first_name' => 'Bob']);
        $mailbox = Mailbox::factory()->create(['name' => 'Support']);

        $text = '{%mailbox.name%}: {%user.firstName%} → {%customer.firstName%} (#{%conversation.number%}): {%subject%}';

        $result = MailHelper::replaceMailVars($text, [
            'conversation' => $conversation,
            'customer' => $customer,
            'user' => $user,
            'mailbox' => $mailbox,
        ]);

        $this->assertStringContainsString('Support', $result);
        $this->assertStringContainsString('Bob', $result);
        $this->assertStringContainsString('Jane', $result);
        $this->assertStringContainsString('789', $result);
        $this->assertStringContainsString('Issue', $result);
    }

    public function test_replace_mail_vars_with_repeated_vars(): void
    {
        $customer = Customer::factory()->create(['first_name' => 'John']);
        $text = '{%customer.firstName%} and {%customer.firstName%} again';

        $result = MailHelper::replaceMailVars($text, ['customer' => $customer]);

        $this->assertEquals(2, substr_count($result, 'John'));
    }

    public function test_replace_mail_vars_handles_very_long_text(): void
    {
        $customer = Customer::factory()->create(['first_name' => 'Alice']);
        $longText = str_repeat('Hello {%customer.firstName%}! ', 1000);

        $result = MailHelper::replaceMailVars($longText, ['customer' => $customer]);

        $this->assertEquals(1000, substr_count($result, 'Alice'));
    }

    public function test_replace_mail_vars_with_mixed_valid_and_invalid_vars(): void
    {
        $customer = Customer::factory()->create(['first_name' => 'Bob']);
        $text = 'Hi {%customer.firstName%}, your {%invalid.var%} is ready.';

        $result = MailHelper::replaceMailVars($text, ['customer' => $customer], remove_non_replaced: true);

        $this->assertStringContainsString('Bob', $result);
        $this->assertStringNotContainsString('{%invalid.var%}', $result);
    }
}
