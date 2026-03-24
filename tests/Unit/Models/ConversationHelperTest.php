<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Conversation;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Facade;
use Tests\PureUnitTestCase;

final class TestConversationHelper extends Conversation
{
    protected function casts(): array
    {
        return [];
    }
}

class ConversationHelperTest extends PureUnitTestCase
{
    private ?Container $previousContainer = null;

    private mixed $previousFacadeApplication = null;

    /** @var object{store: array<string, mixed>} */
    private object $cacheStub;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();

        $this->cacheStub = new class
        {
            /** @var array<string, mixed> */
            public array $store = [];

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->store[$key] ?? $default;
            }

            public function put(string $key, mixed $value, mixed $ttl = null): void
            {
                $this->store[$key] = $value;
            }
        };

        $app = new Application(getcwd());
        $app->instance('config', new Repository(['app' => []]));
        $app->instance('translator', new class
        {
            public function get(string $key, array $replace = [], ?string $locale = null, bool $fallback = true): string
            {
                foreach ($replace as $token => $value) {
                    $key = str_replace(':'.$token, (string) $value, $key);
                }

                return $key;
            }
        });
        $app->instance('cache', $this->cacheStub);

        Container::setInstance($app);
        Facade::setFacadeApplication($app);
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    private function conv(array $attrs = []): TestConversationHelper
    {
        $conv = new TestConversationHelper;
        foreach ($attrs as $key => $value) {
            $conv->{$key} = $value;
        }

        return $conv;
    }

    // -------------------------------------------------------------------------
    // CC / BCC array helpers
    // -------------------------------------------------------------------------

    public function test_get_cc_and_bcc_array_return_empty_array_when_null(): void
    {
        $conv = $this->conv(['cc' => null, 'bcc' => null]);

        $this->assertSame([], $conv->getCcArray());
        $this->assertSame([], $conv->getBccArray());
    }

    public function test_get_cc_and_bcc_array_return_stored_values_when_set(): void
    {
        $cc = ['a@example.com', 'b@example.com'];
        $bcc = ['c@example.com'];
        $conv = $this->conv(['cc' => $cc, 'bcc' => $bcc]);

        $this->assertSame($cc, $conv->getCcArray());
        $this->assertSame($bcc, $conv->getBccArray());
    }

    // -------------------------------------------------------------------------
    // Boolean status / type helpers
    // -------------------------------------------------------------------------

    public function test_is_active_is_closed_is_phone_and_is_chat_return_correct_booleans(): void
    {
        $active = $this->conv(['status' => Conversation::STATUS_ACTIVE, 'type' => Conversation::TYPE_EMAIL]);
        $closed = $this->conv(['status' => Conversation::STATUS_CLOSED, 'type' => Conversation::TYPE_PHONE]);
        $chat = $this->conv(['status' => Conversation::STATUS_PENDING, 'type' => Conversation::TYPE_CHAT]);

        $this->assertTrue($active->isActive());
        $this->assertFalse($active->isClosed());
        $this->assertFalse($active->isPhone());
        $this->assertFalse($active->isChat());

        $this->assertFalse($closed->isActive());
        $this->assertTrue($closed->isClosed());
        $this->assertTrue($closed->isPhone());
        $this->assertFalse($closed->isChat());

        $this->assertTrue($chat->isChat());
        $this->assertFalse($chat->isPhone());
    }

    // -------------------------------------------------------------------------
    // Status labels
    // -------------------------------------------------------------------------

    public function test_get_status_name_returns_correct_label_for_each_status(): void
    {
        $cases = [
            Conversation::STATUS_ACTIVE => 'Active',
            Conversation::STATUS_PENDING => 'Pending',
            Conversation::STATUS_CLOSED => 'Closed',
            Conversation::STATUS_SPAM => 'Spam',
            999 => 'Unknown',
        ];

        foreach ($cases as $status => $expected) {
            $this->assertSame($expected, $this->conv(['status' => $status])->getStatusName());
        }
    }

    public function test_get_status_color_returns_hex_for_each_status(): void
    {
        $cases = [
            Conversation::STATUS_ACTIVE => '#3f8abf',
            Conversation::STATUS_PENDING => '#e6b216',
            Conversation::STATUS_CLOSED => '#5cb85c',
            Conversation::STATUS_SPAM => '#d9534f',
            999 => '#777777',
        ];

        foreach ($cases as $status => $expected) {
            $this->assertSame($expected, $this->conv(['status' => $status])->getStatusColor());
        }
    }

    public function test_get_status_label_mirrors_status_name(): void
    {
        foreach ([
            Conversation::STATUS_ACTIVE,
            Conversation::STATUS_PENDING,
            Conversation::STATUS_CLOSED,
            Conversation::STATUS_SPAM,
        ] as $status) {
            $conv = $this->conv(['status' => $status]);
            $this->assertSame($conv->getStatusName(), $conv->getStatusLabel());
        }
    }

    // -------------------------------------------------------------------------
    // Type label
    // -------------------------------------------------------------------------

    public function test_get_type_label_returns_correct_label_for_each_type(): void
    {
        $cases = [
            Conversation::TYPE_EMAIL => 'Email',
            Conversation::TYPE_PHONE => 'Phone',
            Conversation::TYPE_CHAT => 'Chat',
            999 => 'Unknown',
        ];

        foreach ($cases as $type => $expected) {
            $this->assertSame($expected, $this->conv(['type' => $type])->getTypeLabel());
        }
    }

    // -------------------------------------------------------------------------
    // sanitizeEmails — static, fully pure
    // -------------------------------------------------------------------------

    public function test_sanitize_emails_keeps_valid_and_discards_invalid_addresses(): void
    {
        $input = [
            'valid@example.com',
            '  padded@example.com  ',
            'not-an-email',
            '',
            'also@valid.org',
        ];

        $result = Conversation::sanitizeEmails($input);

        $this->assertSame(['valid@example.com', 'padded@example.com', 'also@valid.org'], $result);
    }

    public function test_sanitize_emails_returns_empty_array_for_all_invalid_input(): void
    {
        $this->assertSame([], Conversation::sanitizeEmails(['bad', 'worse', '@nope']));
    }

    // -------------------------------------------------------------------------
    // Follow-up helpers
    // -------------------------------------------------------------------------

    public function test_has_follow_up_scheduled_and_has_follow_up_been_reminded(): void
    {
        $noFollowUp = $this->conv(['follow_up_date' => null, 'follow_up_reminded_at' => null]);
        $scheduled = $this->conv(['follow_up_date' => Carbon::now()->addDay(), 'follow_up_reminded_at' => null]);
        $reminded = $this->conv([
            'follow_up_date' => Carbon::now()->addDay(),
            'follow_up_reminded_at' => Carbon::now(),
        ]);

        $this->assertFalse($noFollowUp->hasFollowUpScheduled());
        $this->assertFalse($noFollowUp->hasFollowUpBeenReminded());

        $this->assertTrue($scheduled->hasFollowUpScheduled());
        $this->assertFalse($scheduled->hasFollowUpBeenReminded());

        $this->assertTrue($reminded->hasFollowUpScheduled());
        $this->assertTrue($reminded->hasFollowUpBeenReminded());
    }

    public function test_is_follow_up_overdue_when_past_date_and_not_reminded(): void
    {
        $overdue = $this->conv(['follow_up_date' => Carbon::now()->subDay(), 'follow_up_reminded_at' => null]);
        $future = $this->conv(['follow_up_date' => Carbon::now()->addDay(), 'follow_up_reminded_at' => null]);
        $pastButReminded = $this->conv([
            'follow_up_date' => Carbon::now()->subDay(),
            'follow_up_reminded_at' => Carbon::now(),
        ]);

        $this->assertTrue($overdue->isFollowUpOverdue());
        $this->assertFalse($future->isFollowUpOverdue());
        $this->assertFalse($pastButReminded->isFollowUpOverdue());
    }

    public function test_get_follow_up_status_returns_null_when_no_follow_up_scheduled(): void
    {
        $this->assertNull($this->conv(['follow_up_date' => null])->getFollowUpStatus());
    }

    public function test_get_follow_up_status_returns_reminded_string_when_reminded(): void
    {
        $remindedAt = Carbon::parse('2026-03-15');
        $conv = $this->conv([
            'follow_up_date' => Carbon::now()->addDay(),
            'follow_up_reminded_at' => $remindedAt,
        ]);

        $this->assertSame('Reminded on Mar 15, 2026', $conv->getFollowUpStatus());
    }

    public function test_get_follow_up_status_returns_overdue_string_when_overdue(): void
    {
        $followUpDate = Carbon::parse('2026-03-01');
        $conv = $this->conv(['follow_up_date' => $followUpDate, 'follow_up_reminded_at' => null]);

        $this->assertStringContainsString('Overdue since', (string) $conv->getFollowUpStatus());
    }

    public function test_get_follow_up_status_returns_scheduled_string_for_future_date(): void
    {
        $futureDate = Carbon::parse('2026-12-25');
        $conv = $this->conv(['follow_up_date' => $futureDate, 'follow_up_reminded_at' => null]);

        $this->assertSame('Scheduled for Dec 25, 2026', $conv->getFollowUpStatus());
    }

    // -------------------------------------------------------------------------
    // getViewersInfo — pure paths (no DB hit)
    // -------------------------------------------------------------------------

    public function test_get_viewers_info_returns_empty_for_empty_conversations_array(): void
    {
        $result = Conversation::getViewersInfo([]);

        $this->assertSame([], $result);
    }

    public function test_get_viewers_info_returns_empty_when_conversation_has_no_cache_entry(): void
    {
        // Cache is empty (no entry for this conversation)
        $this->cacheStub->store = [];

        $conv = $this->conv(['id' => 42]);
        $result = Conversation::getViewersInfo([$conv]);

        $this->assertSame([], $result);
    }

    public function test_get_viewers_info_skips_replying_viewer_when_in_exclude_list(): void
    {
        // Replying viewer 100 is in the exclude list — must not appear in result
        $this->cacheStub->store[Conversation::VIEWER_CACHE_KEY] = [
            55 => [100 => ['r' => true, 't' => time()]],
        ];

        $conv = $this->conv(['id' => 55]);
        $result = Conversation::getViewersInfo([$conv], ['id', 'first_name', 'last_name'], [100]);

        $this->assertSame([], $result);
    }

    public function test_get_viewers_info_skips_non_replying_viewer_when_in_exclude_list(): void
    {
        // Non-replying viewer 200 is tracked as $firstUserId but excluded from result
        $this->cacheStub->store[Conversation::VIEWER_CACHE_KEY] = [
            77 => [200 => ['r' => false, 't' => time()]],
        ];

        $conv = $this->conv(['id' => 77]);
        $result = Conversation::getViewersInfo([$conv], ['id', 'first_name', 'last_name'], [200]);

        $this->assertSame([], $result);
    }
}
