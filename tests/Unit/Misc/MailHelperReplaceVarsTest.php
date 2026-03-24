<?php

declare(strict_types=1);

namespace Tests\Unit\Misc;

use App\Misc\MailHelper;
use Tests\PureUnitTestCase;

/**
 * Pure unit tests for MailHelper::replaceMailVars().
 *
 * eventy() does not exist in the test environment, so applyEventyFilter()
 * short-circuits and returns $vars unchanged — no container needed here.
 */
class MailHelperReplaceVarsTest extends PureUnitTestCase
{
    // ─── Factory helpers ─────────────────────────────────────────────

    private function makeConversation(
        string $subject = 'Test Subject',
        int $number = 42,
        string $customerEmail = 'customer@example.com'
    ): object {
        return new class($subject, $number, $customerEmail)
        {
            public string $subject;
            public int $number;
            public string $customer_email;

            public function __construct(string $s, int $n, string $e)
            {
                $this->subject = $s;
                $this->number = $n;
                $this->customer_email = $e;
            }
        };
    }

    private function makeMailbox(
        string $email = 'support@example.com',
        string $name = 'Support Box',
        string $fromName = 'Support'
    ): object {
        return new class($email, $name, $fromName)
        {
            public string $email;
            public string $name;
            private string $fromName;

            public function __construct(string $e, string $n, string $fn)
            {
                $this->email = $e;
                $this->name = $n;
                $this->fromName = $fn;
            }

            /** @return array<string, string> */
            public function getMailFrom(mixed $user = null): array
            {
                return ['name' => $this->fromName, 'email' => $this->email];
            }
        };
    }

    private function makeCustomer(
        string $fullName = 'John Doe',
        string $firstName = 'John',
        string $lastName = 'Doe',
        string $company = 'ACME Corp'
    ): object {
        return new class($fullName, $firstName, $lastName, $company)
        {
            public string $last_name;
            public string $company;
            private string $fn;
            private string $fi;

            public function __construct(string $fn, string $fi, string $ln, string $co)
            {
                $this->fn = $fn;
                $this->fi = $fi;
                $this->last_name = $ln;
                $this->company = $co;
            }

            public function getFullName(): string
            {
                return $this->fn;
            }

            public function getFirstName(): string
            {
                return $this->fi;
            }
        };
    }

    private function makeUser(
        string $fullName = 'Agent Smith',
        string $firstName = 'Agent',
        string $lastName = 'Smith',
        string $email = 'agent@example.com',
        string $phone = '555-0100',
        string $jobTitle = 'Support Engineer',
        string $photoUrl = 'https://example.com/photo.jpg'
    ): object {
        return new class($fullName, $firstName, $lastName, $email, $phone, $jobTitle, $photoUrl)
        {
            public string $last_name;
            public string $email;
            public string $phone;
            public string $job_title;
            private string $fn;
            private string $fi;
            private string $pu;

            public function __construct(
                string $fn,
                string $fi,
                string $ln,
                string $em,
                string $ph,
                string $jt,
                string $pu
            ) {
                $this->fn = $fn;
                $this->fi = $fi;
                $this->last_name = $ln;
                $this->email = $em;
                $this->phone = $ph;
                $this->job_title = $jt;
                $this->pu = $pu;
            }

            public function getFullName(): string
            {
                return $this->fn;
            }

            public function getFirstName(): string
            {
                return $this->fi;
            }

            public function getPhotoUrl(): string
            {
                return $this->pu;
            }
        };
    }

    // ─── Passthrough ─────────────────────────────────────────────────

    public function test_plain_text_without_vars_is_returned_unchanged(): void
    {
        $this->assertSame('Hello, welcome!', MailHelper::replaceMailVars('Hello, welcome!'));
    }

    public function test_empty_data_leaves_unmatched_placeholder_intact(): void
    {
        $result = MailHelper::replaceMailVars('{%subject%}', []);
        $this->assertStringContainsString('{%subject%}', $result);
    }

    // ─── Conversation vars ───────────────────────────────────────────

    public function test_subject_var_replaced_from_conversation(): void
    {
        $result = MailHelper::replaceMailVars('{%subject%}', [
            'conversation' => $this->makeConversation('Re: Help Request'),
        ]);
        $this->assertSame('Re: Help Request', $result);
    }

    public function test_conversation_number_var_replaced(): void
    {
        $result = MailHelper::replaceMailVars('Case #{%conversation.number%}', [
            'conversation' => $this->makeConversation('Subject', 99),
        ]);
        $this->assertSame('Case #99', $result);
    }

    public function test_customer_email_var_from_conversation(): void
    {
        $result = MailHelper::replaceMailVars('{%customer.email%}', [
            'conversation' => $this->makeConversation('S', 1, 'cust@test.com'),
        ]);
        $this->assertSame('cust@test.com', $result);
    }

    // ─── Mailbox vars ────────────────────────────────────────────────

    public function test_mailbox_email_var_replaced(): void
    {
        $result = MailHelper::replaceMailVars('{%mailbox.email%}', [
            'mailbox' => $this->makeMailbox('inbox@company.com'),
        ]);
        $this->assertSame('inbox@company.com', $result);
    }

    public function test_mailbox_name_var_replaced(): void
    {
        $result = MailHelper::replaceMailVars('{%mailbox.name%}', [
            'mailbox' => $this->makeMailbox('inbox@company.com', 'Company Inbox'),
        ]);
        $this->assertSame('Company Inbox', $result);
    }

    public function test_mailbox_from_name_uses_explicit_override(): void
    {
        $result = MailHelper::replaceMailVars('{%mailbox.fromName%}', [
            'mailbox' => $this->makeMailbox(),
            'mailbox_from_name' => 'Support Team',
        ]);
        $this->assertSame('Support Team', $result);
    }

    // ─── Customer vars ───────────────────────────────────────────────

    public function test_customer_full_name_var_replaced(): void
    {
        $result = MailHelper::replaceMailVars('Hello {%customer.fullName%}!', [
            'customer' => $this->makeCustomer('Jane Smith'),
        ]);
        $this->assertSame('Hello Jane Smith!', $result);
    }

    public function test_customer_first_name_var_replaced(): void
    {
        $result = MailHelper::replaceMailVars('Hi {%customer.firstName%},', [
            'customer' => $this->makeCustomer('Jane Smith', 'Jane'),
        ]);
        $this->assertSame('Hi Jane,', $result);
    }

    public function test_customer_last_name_var_replaced(): void
    {
        $result = MailHelper::replaceMailVars('{%customer.lastName%}', [
            'customer' => $this->makeCustomer('Jane Smith', 'Jane', 'Smith'),
        ]);
        $this->assertSame('Smith', $result);
    }

    public function test_customer_company_var_replaced(): void
    {
        $result = MailHelper::replaceMailVars('{%customer.company%}', [
            'customer' => $this->makeCustomer('Jane', 'Jane', 'Smith', 'TechCorp'),
        ]);
        $this->assertSame('TechCorp', $result);
    }

    // ─── User vars ───────────────────────────────────────────────────

    public function test_user_full_name_var_replaced(): void
    {
        $result = MailHelper::replaceMailVars('{%user.fullName%}', [
            'user' => $this->makeUser('Bob Jones'),
        ]);
        $this->assertSame('Bob Jones', $result);
    }

    public function test_user_first_name_var_replaced(): void
    {
        $result = MailHelper::replaceMailVars('{%user.firstName%}', [
            'user' => $this->makeUser('Bob Jones', 'Bob'),
        ]);
        $this->assertSame('Bob', $result);
    }

    public function test_user_last_name_var_replaced(): void
    {
        $result = MailHelper::replaceMailVars('{%user.lastName%}', [
            'user' => $this->makeUser(lastName: 'Roberts'),
        ]);
        $this->assertSame('Roberts', $result);
    }

    public function test_user_email_var_replaced(): void
    {
        $result = MailHelper::replaceMailVars('{%user.email%}', [
            'user' => $this->makeUser(email: 'bob@company.com'),
        ]);
        $this->assertSame('bob@company.com', $result);
    }

    public function test_user_phone_var_replaced(): void
    {
        $result = MailHelper::replaceMailVars('{%user.phone%}', [
            'user' => $this->makeUser(phone: '555-9999'),
        ]);
        $this->assertSame('555-9999', $result);
    }

    public function test_user_job_title_var_replaced(): void
    {
        $result = MailHelper::replaceMailVars('{%user.jobTitle%}', [
            'user' => $this->makeUser(jobTitle: 'Senior Engineer'),
        ]);
        $this->assertSame('Senior Engineer', $result);
    }

    public function test_user_photo_url_var_replaced(): void
    {
        $result = MailHelper::replaceMailVars('{%user.photoUrl%}', [
            'user' => $this->makeUser(photoUrl: 'https://cdn.example.com/avatar.png'),
        ]);
        $this->assertSame('https://cdn.example.com/avatar.png', $result);
    }

    // ─── Fallback values ─────────────────────────────────────────────

    public function test_fallback_value_used_when_no_matching_data(): void
    {
        $result = MailHelper::replaceMailVars(
            'Hi {%customer.fullName,fallback=there%}!',
            []
        );
        $this->assertSame('Hi there!', $result);
    }

    public function test_actual_value_takes_priority_over_fallback(): void
    {
        $result = MailHelper::replaceMailVars(
            'Hi {%customer.fullName,fallback=Friend%}!',
            ['customer' => $this->makeCustomer('Alice')]
        );
        $this->assertSame('Hi Alice!', $result);
    }

    public function test_empty_string_fallback_produces_empty_replacement(): void
    {
        $result = MailHelper::replaceMailVars(
            'Reply to {%mailbox.email,fallback=%}.',
            []
        );
        $this->assertSame('Reply to .', $result);
    }

    // ─── remove_non_replaced ─────────────────────────────────────────

    public function test_unmatched_var_stays_when_remove_non_replaced_is_false(): void
    {
        $result = MailHelper::replaceMailVars('{%subject%}', [], false, false);
        $this->assertStringContainsString('{%subject%}', $result);
    }

    public function test_unmatched_var_removed_when_remove_non_replaced_is_true(): void
    {
        $result = MailHelper::replaceMailVars('Prefix {%subject%} Suffix', [], false, true);
        $this->assertStringNotContainsString('{%subject%}', $result);
    }

    public function test_matched_var_stays_after_replacement_even_with_remove_flag(): void
    {
        $result = MailHelper::replaceMailVars('Hello {%subject%}!', [
            'conversation' => $this->makeConversation('World'),
        ], false, true);
        $this->assertSame('Hello World!', $result);
    }

    // ─── Escape mode ─────────────────────────────────────────────────

    public function test_escape_mode_html_encodes_var_values(): void
    {
        $result = MailHelper::replaceMailVars('{%subject%}', [
            'conversation' => $this->makeConversation('<b>Hello</b>'),
        ], true);
        $this->assertSame('&lt;b&gt;Hello&lt;/b&gt;', $result);
    }

    public function test_escape_mode_does_not_double_encode_safe_text(): void
    {
        $result = MailHelper::replaceMailVars('{%subject%}', [
            'conversation' => $this->makeConversation('Safe Text'),
        ], true);
        $this->assertSame('Safe Text', $result);
    }

    // ─── Multiple vars ───────────────────────────────────────────────

    public function test_multiple_vars_replaced_in_single_pass(): void
    {
        $result = MailHelper::replaceMailVars(
            'Dear {%customer.fullName%}, your case #{%conversation.number%} is open.',
            [
                'customer' => $this->makeCustomer('Alice Cooper'),
                'conversation' => $this->makeConversation('Issue', 77),
            ]
        );
        $this->assertSame('Dear Alice Cooper, your case #77 is open.', $result);
    }

    public function test_vars_from_different_data_sources_replaced_together(): void
    {
        $result = MailHelper::replaceMailVars(
            '{%mailbox.name%} - {%user.email%} - {%customer.firstName%}',
            [
                'mailbox' => $this->makeMailbox('m@x.com', 'HelpDesk'),
                'user' => $this->makeUser(email: 'tech@co.com'),
                'customer' => $this->makeCustomer(firstName: 'Carol'),
            ]
        );
        $this->assertSame('HelpDesk - tech@co.com - Carol', $result);
    }
}
