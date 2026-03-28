<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Conversation;
use Tests\PureUnitTestCase;

// ── Stub ──────────────────────────────────────────────────────────────────────

if (! class_exists(StubConversation::class)) {
    final class StubConversation extends Conversation
    {
        protected static function booted(): void {}

        public function getDateFormat(): string
        {
            return 'Y-m-d H:i:s';
        }
    }
}

// ── Test class ────────────────────────────────────────────────────────────────

final class ConversationTest extends PureUnitTestCase
{
    private function conv(array $rawAttrs): StubConversation
    {
        $c = new StubConversation;
        $c->setRawAttributes($rawAttrs);

        return $c;
    }

    // ── status predicates ─────────────────────────────────────────────

    public function test_is_active_when_status_is_1(): void
    {
        $this->assertTrue($this->conv(['status' => Conversation::STATUS_ACTIVE])->isActive());
    }

    public function test_is_not_active_when_status_is_closed(): void
    {
        $this->assertFalse($this->conv(['status' => Conversation::STATUS_CLOSED])->isActive());
    }

    public function test_is_closed_when_status_is_3(): void
    {
        $this->assertTrue($this->conv(['status' => Conversation::STATUS_CLOSED])->isClosed());
    }

    public function test_is_not_closed_when_status_is_active(): void
    {
        $this->assertFalse($this->conv(['status' => Conversation::STATUS_ACTIVE])->isClosed());
    }

    // ── type predicates ───────────────────────────────────────────────

    public function test_is_phone_when_type_is_phone(): void
    {
        $this->assertTrue($this->conv(['type' => Conversation::TYPE_PHONE])->isPhone());
    }

    public function test_is_not_phone_for_email_type(): void
    {
        $this->assertFalse($this->conv(['type' => Conversation::TYPE_EMAIL])->isPhone());
    }

    public function test_is_chat_when_type_is_chat(): void
    {
        $this->assertTrue($this->conv(['type' => Conversation::TYPE_CHAT])->isChat());
    }

    public function test_is_not_chat_for_email_type(): void
    {
        $this->assertFalse($this->conv(['type' => Conversation::TYPE_EMAIL])->isChat());
    }

    // ── getStatusColor ────────────────────────────────────────────────

    public function test_status_color_active_is_blue(): void
    {
        $this->assertSame('#3f8abf', $this->conv(['status' => Conversation::STATUS_ACTIVE])->getStatusColor());
    }

    public function test_status_color_pending_is_yellow(): void
    {
        $this->assertSame('#e6b216', $this->conv(['status' => Conversation::STATUS_PENDING])->getStatusColor());
    }

    public function test_status_color_closed_is_green(): void
    {
        $this->assertSame('#5cb85c', $this->conv(['status' => Conversation::STATUS_CLOSED])->getStatusColor());
    }

    public function test_status_color_spam_is_red(): void
    {
        $this->assertSame('#d9534f', $this->conv(['status' => Conversation::STATUS_SPAM])->getStatusColor());
    }

    public function test_status_color_unknown_is_grey(): void
    {
        $this->assertSame('#777777', $this->conv(['status' => 99])->getStatusColor());
    }

    // ── getCcArray ────────────────────────────────────────────────────

    public function test_get_cc_array_returns_empty_when_null(): void
    {
        $this->assertSame([], $this->conv(['cc' => null])->getCcArray());
    }

    public function test_get_cc_array_returns_cast_array(): void
    {
        $c = new StubConversation;
        $c->forceFill(['cc' => ['a@x.com', 'b@x.com']]);
        $this->assertSame(['a@x.com', 'b@x.com'], $c->getCcArray());
    }

    // ── follow-up helpers ─────────────────────────────────────────────

    public function test_has_follow_up_scheduled_when_date_is_set(): void
    {
        $c = new StubConversation;
        $c->setRawAttributes(['follow_up_date' => now()->addDay()->format('Y-m-d H:i:s')]);
        $this->assertTrue($c->hasFollowUpScheduled());
    }

    public function test_has_no_follow_up_scheduled_when_date_is_null(): void
    {
        $this->assertFalse($this->conv(['follow_up_date' => null])->hasFollowUpScheduled());
    }

    public function test_has_follow_up_been_reminded_when_reminded_at_is_set(): void
    {
        $c = new StubConversation;
        $c->setRawAttributes(['follow_up_reminded_at' => now()->subHour()->format('Y-m-d H:i:s')]);
        $this->assertTrue($c->hasFollowUpBeenReminded());
    }

    public function test_has_not_been_reminded_when_null(): void
    {
        $this->assertFalse($this->conv(['follow_up_reminded_at' => null])->hasFollowUpBeenReminded());
    }

    public function test_is_follow_up_overdue_when_past_and_not_reminded(): void
    {
        $c = new StubConversation;
        $c->setRawAttributes([
            'follow_up_date' => now()->subDays(2)->format('Y-m-d H:i:s'),
            'follow_up_reminded_at' => null,
        ]);
        $this->assertTrue($c->isFollowUpOverdue());
    }

    public function test_is_not_overdue_when_reminded_already(): void
    {
        $c = new StubConversation;
        $c->setRawAttributes([
            'follow_up_date' => now()->subDays(2)->format('Y-m-d H:i:s'),
            'follow_up_reminded_at' => now()->subDay()->format('Y-m-d H:i:s'),
        ]);
        $this->assertFalse($c->isFollowUpOverdue());
    }

    public function test_is_not_overdue_when_date_in_future(): void
    {
        $c = new StubConversation;
        $c->setRawAttributes([
            'follow_up_date' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'follow_up_reminded_at' => null,
        ]);
        $this->assertFalse($c->isFollowUpOverdue());
    }

    public function test_is_not_overdue_when_follow_up_date_null(): void
    {
        $this->assertFalse($this->conv(['follow_up_date' => null])->isFollowUpOverdue());
    }
}
