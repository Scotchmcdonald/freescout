<?php

declare(strict_types=1);

namespace Tests\Unit\ContractManager;

use Carbon\Carbon;
use Modules\ContractManager\Models\Quote;
use Tests\PureUnitTestCase as BaseTestCase;

/**
 * Stub Quote bypasses DB dependencies for pure-logic tests.
 */
if (! class_exists(StubQuote::class)) {
    final class StubQuote extends Quote
    {
        protected static function booted(): void {}

        /** Avoid DB-resolver lookup during date serialisation. */
        public function getDateFormat(): string
        {
            return 'Y-m-d H:i:s';
        }
    }
}

/**
 * Pure-unit tests for Quote model status helpers.
 *
 * Tests status predicates, expiry logic and approval/rejection guard methods
 * without hitting the database.
 */
final class QuoteModelHelperTest extends BaseTestCase
{
    private function quote(string $status, array $extra = []): StubQuote
    {
        $q = new StubQuote;
        $q->setRawAttributes(array_merge(['status' => $status], $extra));

        return $q;
    }

    // ── STATUS constants ───────────────────────────────────────────────────

    public function test_status_constants(): void
    {
        $this->assertSame('draft', Quote::STATUS_DRAFT);
        $this->assertSame('sent', Quote::STATUS_SENT);
        $this->assertSame('approved', Quote::STATUS_APPROVED);
        $this->assertSame('rejected', Quote::STATUS_REJECTED);
        $this->assertSame('expired', Quote::STATUS_EXPIRED);
        $this->assertSame('revised', Quote::STATUS_REVISED);
        $this->assertSame('under_review', Quote::STATUS_UNDER_REVIEW);
    }

    // ── isDraft ───────────────────────────────────────────────────────────

    public function test_is_draft_true(): void
    {
        $this->assertTrue($this->quote('draft')->isDraft());
    }

    public function test_is_draft_false_when_sent(): void
    {
        $this->assertFalse($this->quote('sent')->isDraft());
    }

    public function test_is_draft_false_when_approved(): void
    {
        $this->assertFalse($this->quote('approved')->isDraft());
    }

    // ── isApproved ────────────────────────────────────────────────────────

    public function test_is_approved_true(): void
    {
        $this->assertTrue($this->quote('approved')->isApproved());
    }

    public function test_is_approved_false_when_draft(): void
    {
        $this->assertFalse($this->quote('draft')->isApproved());
    }

    // ── isExpired ─────────────────────────────────────────────────────────

    public function test_is_expired_true_by_status(): void
    {
        $this->assertTrue($this->quote('expired')->isExpired());
    }

    public function test_is_expired_true_by_past_valid_until(): void
    {
        $q = $this->quote('sent', ['valid_until' => Carbon::yesterday()->toDateString()]);
        $this->assertTrue($q->isExpired());
    }

    public function test_is_expired_false_when_valid_until_is_future(): void
    {
        $q = $this->quote('sent', ['valid_until' => Carbon::tomorrow()->toDateString()]);
        $this->assertFalse($q->isExpired());
    }

    public function test_is_expired_false_when_no_valid_until(): void
    {
        $this->assertFalse($this->quote('draft')->isExpired());
    }

    // ── canBeApproved ─────────────────────────────────────────────────────

    public function test_can_be_approved_when_draft_not_expired(): void
    {
        $this->assertTrue($this->quote('draft')->canBeApproved());
    }

    public function test_can_be_approved_when_sent_not_expired(): void
    {
        $this->assertTrue($this->quote('sent')->canBeApproved());
    }

    public function test_cannot_be_approved_when_approved(): void
    {
        $this->assertFalse($this->quote('approved')->canBeApproved());
    }

    public function test_cannot_be_approved_when_rejected(): void
    {
        $this->assertFalse($this->quote('rejected')->canBeApproved());
    }

    public function test_cannot_be_approved_when_expired_status(): void
    {
        $this->assertFalse($this->quote('expired')->canBeApproved());
    }

    public function test_cannot_be_approved_when_sent_but_past_valid_until(): void
    {
        $q = $this->quote('sent', ['valid_until' => Carbon::yesterday()->toDateString()]);
        $this->assertFalse($q->canBeApproved());
    }

    // ── canBeRejected ─────────────────────────────────────────────────────

    public function test_can_be_rejected_when_draft(): void
    {
        $this->assertTrue($this->quote('draft')->canBeRejected());
    }

    public function test_can_be_rejected_when_sent(): void
    {
        $this->assertTrue($this->quote('sent')->canBeRejected());
    }

    public function test_cannot_be_rejected_when_already_rejected(): void
    {
        $this->assertFalse($this->quote('rejected')->canBeRejected());
    }

    public function test_cannot_be_rejected_when_approved(): void
    {
        $this->assertFalse($this->quote('approved')->canBeRejected());
    }

    public function test_cannot_be_rejected_when_expired_status(): void
    {
        $this->assertFalse($this->quote('expired')->canBeRejected());
    }
}
