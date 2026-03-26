<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Thread;
use Tests\PureUnitTestCase;

// ── Stub ──────────────────────────────────────────────────────────────────────

final class StubThread extends Thread
{
    protected static function booted(): void {}
}

// ── Test class ────────────────────────────────────────────────────────────────

final class ThreadTest extends PureUnitTestCase
{
    // ── isCustomerMessage / isUserMessage / isNote ────────────────────

    public function test_is_customer_message_when_type_matches(): void
    {
        $t = new StubThread();
        $t->setRawAttributes(['type' => Thread::TYPE_CUSTOMER]);
        $this->assertTrue($t->isCustomerMessage());
        $this->assertFalse($t->isUserMessage());
        $this->assertFalse($t->isNote());
    }

    public function test_is_user_message_when_type_is_message(): void
    {
        $t = new StubThread();
        $t->setRawAttributes(['type' => Thread::TYPE_MESSAGE]);
        $this->assertTrue($t->isUserMessage());
        $this->assertFalse($t->isCustomerMessage());
        $this->assertFalse($t->isNote());
    }

    public function test_is_note_when_type_is_note(): void
    {
        $t = new StubThread();
        $t->setRawAttributes(['type' => Thread::TYPE_NOTE]);
        $this->assertTrue($t->isNote());
        $this->assertFalse($t->isCustomerMessage());
        $this->assertFalse($t->isUserMessage());
    }

    public function test_type_constants_are_distinct(): void
    {
        $types = [
            Thread::TYPE_MESSAGE,
            Thread::TYPE_NOTE,
            Thread::TYPE_CUSTOMER,
            Thread::TYPE_LINEITEM,
            Thread::TYPE_DRAFT,
        ];
        $this->assertSame(count($types), count(array_unique($types)));
    }

    // ── isBounce ──────────────────────────────────────────────────────

    public function test_is_bounce_when_meta_indicates_bounce(): void
    {
        $t = new StubThread();
        $t->forceFill(['meta' => ['send_status' => ['is_bounce' => true]]]);
        $this->assertTrue($t->isBounce());
    }

    public function test_is_not_bounce_when_is_bounce_false(): void
    {
        $t = new StubThread();
        $t->forceFill(['meta' => ['send_status' => ['is_bounce' => false]]]);
        $this->assertFalse($t->isBounce());
    }

    public function test_is_not_bounce_when_no_send_status_in_meta(): void
    {
        $t = new StubThread();
        $t->forceFill(['meta' => []]);
        $this->assertFalse($t->isBounce());
    }

    public function test_is_not_bounce_when_meta_is_null(): void
    {
        $t = new StubThread();
        $t->setRawAttributes(['meta' => null]);
        $this->assertFalse($t->isBounce());
    }
}
