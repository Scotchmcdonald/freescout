<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\ImapService;
use Tests\PureUnitTestCase;

class ImapServicePureLogicTest extends PureUnitTestCase
{
    private ImapService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new class extends ImapService
        {
            public function callSeparateReply(string $body, bool $isHtml, bool $isReply): string
            {
                return $this->separateReply($body, $isHtml, $isReply);
            }

            public function callGetOriginalSenderFromFwd(string $body): ?array
            {
                return $this->getOriginalSenderFromFwd($body);
            }

            public function callGetAddressesWithNames(mixed $addresses): array
            {
                return $this->getAddressesWithNames($addresses);
            }

            public function callParseAddresses(mixed $addresses): array
            {
                return $this->parseAddresses($addresses);
            }
        };
    }

    public function test_separate_reply_returns_original_body_when_message_is_not_reply(): void
    {
        $body = "Line 1\nLine 2\nOn Tue wrote:";

        $result = $this->service->callSeparateReply($body, false, false);

        $this->assertSame($body, $result);
    }

    public function test_separate_reply_trims_plaintext_before_generic_separator(): void
    {
        $body = "My answer\n\n---- Replied Above ----\nquoted";

        $result = $this->service->callSeparateReply($body, false, true);

        $this->assertStringContainsString('My answer', $result);
        $this->assertStringNotContainsString('quoted', $result);
    }

    public function test_separate_reply_handles_html_on_wrote_pattern(): void
    {
        $body = '<body><p>Thanks for the details</p><p>On Mon, person wrote:</p><blockquote>old</blockquote></body>';

        $result = $this->service->callSeparateReply($body, true, true);

        $this->assertStringContainsString('Thanks for the details', $result);
        $this->assertStringNotContainsString('blockquote', $result);
    }

    public function test_get_original_sender_from_fwd_parses_name_and_email(): void
    {
        $body = 'Forwarded\nFrom: John Doe <john@example.com>\nSubject: hi';

        $sender = $this->service->callGetOriginalSenderFromFwd($body);

        $this->assertSame(['name' => 'John Doe', 'email' => 'john@example.com'], $sender);
    }

    public function test_get_original_sender_from_fwd_parses_bare_email_format(): void
    {
        $body = 'From: customer@example.net';

        $sender = $this->service->callGetOriginalSenderFromFwd($body);

        $this->assertSame(['name' => '', 'email' => 'customer@example.net'], $sender);
    }

    public function test_get_original_sender_from_fwd_returns_null_when_no_email_pattern_exists(): void
    {
        $sender = $this->service->callGetOriginalSenderFromFwd('no sender markers present');

        $this->assertNull($sender);
    }

    public function test_get_addresses_with_names_handles_string_and_array_inputs(): void
    {
        $addresses = [
            'solo@example.com',
            ['mail' => 'person@example.com', 'personal' => 'Jane Smith'],
            ['email' => 'alt@example.com', 'name' => 'Alex Doe'],
        ];

        $result = $this->service->callGetAddressesWithNames($addresses);

        $this->assertSame('solo@example.com', $result[0]['email']);
        $this->assertSame('person@example.com', $result[1]['email']);
        $this->assertSame('Jane', $result[1]['first_name']);
        $this->assertSame('Smith', $result[1]['last_name']);
        $this->assertSame('alt@example.com', $result[2]['email']);
    }

    public function test_get_addresses_with_names_truncates_long_names_to_model_limits(): void
    {
        $longFirst = str_repeat('A', 30);
        $longLast = str_repeat('B', 40);

        $result = $this->service->callGetAddressesWithNames([
            ['mail' => 'trim@example.com', 'personal' => $longFirst.' '.$longLast],
        ]);

        $this->assertSame(20, strlen($result[0]['first_name']));
        $this->assertSame(30, strlen($result[0]['last_name']));
    }

    public function test_parse_addresses_extracts_from_mixed_entries_and_skips_empty_values(): void
    {
        $objectAddr = new class
        {
            public string $mail = 'obj@example.com';
        };

        $result = $this->service->callParseAddresses([
            $objectAddr,
            ['email' => 'arr@example.com'],
            'raw@example.com',
            null,
            '   ',
        ]);

        $this->assertSame(['obj@example.com', 'arr@example.com', 'raw@example.com'], $result);
    }

    public function test_parse_addresses_returns_empty_array_for_non_array_non_string_input(): void
    {
        $result = $this->service->callParseAddresses(new \stdClass);

        $this->assertSame([], $result);
    }
}
