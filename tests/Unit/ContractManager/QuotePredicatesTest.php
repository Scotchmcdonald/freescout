<?php

declare(strict_types=1);

namespace Tests\Unit\ContractManager;

use Illuminate\Support\Carbon;
use Modules\ContractManager\Models\Quote;
use Tests\PureUnitTestCase;

if (! class_exists(StubQuote::class)) {
    final class StubQuote extends Quote
    {
        protected static function booted(): void {}

        public function getDateFormat(): string
        {
            return 'Y-m-d H:i:s';
        }
    }
}

final class QuotePredicatesTest extends PureUnitTestCase
{
    // ─── status constants ─────────────────────────────────────────────────────

    public function test_status_constants_are_distinct(): void
    {
        $statuses = [
            Quote::STATUS_DRAFT,
            Quote::STATUS_SENT,
            Quote::STATUS_APPROVED,
            Quote::STATUS_REJECTED,
            Quote::STATUS_EXPIRED,
            Quote::STATUS_REVISED,
            Quote::STATUS_UNDER_REVIEW,
        ];

        $this->assertSame(count($statuses), count(array_unique($statuses)));
    }

    // ─── isDraft ──────────────────────────────────────────────────────────────

    public function test_is_draft_true_for_draft_status(): void
    {
        $q = new StubQuote;
        $q->status = 'draft';
        $this->assertTrue($q->isDraft());
    }

    public function test_is_draft_false_for_sent_status(): void
    {
        $q = new StubQuote;
        $q->status = 'sent';
        $this->assertFalse($q->isDraft());
    }

    // ─── isApproved ───────────────────────────────────────────────────────────

    public function test_is_approved_true_for_approved_status(): void
    {
        $q = new StubQuote;
        $q->status = 'approved';
        $this->assertTrue($q->isApproved());
    }

    public function test_is_approved_false_for_draft(): void
    {
        $q = new StubQuote;
        $q->status = 'draft';
        $this->assertFalse($q->isApproved());
    }

    // ─── isExpired ────────────────────────────────────────────────────────────

    public function test_is_expired_true_for_expired_status(): void
    {
        $q = new StubQuote;
        $q->status = 'expired';
        $this->assertTrue($q->isExpired());
    }

    public function test_is_expired_true_when_valid_until_is_past(): void
    {
        $q = new StubQuote;
        $q->status = 'sent';
        $q->setRawAttributes(['valid_until' => Carbon::now()->subDays(1)->toDateTimeString()]);
        $this->assertTrue($q->isExpired());
    }

    public function test_is_expired_false_when_valid_until_is_future(): void
    {
        $q = new StubQuote;
        $q->status = 'sent';
        $q->setRawAttributes(['valid_until' => Carbon::now()->addDays(10)->toDateTimeString()]);
        $this->assertFalse($q->isExpired());
    }

    public function test_is_expired_false_for_approved_without_past_valid_until(): void
    {
        $q = new StubQuote;
        $q->status = 'approved';
        $this->assertFalse($q->isExpired());
    }

    // ─── canBeApproved ────────────────────────────────────────────────────────

    public function test_can_be_approved_true_for_draft_not_expired(): void
    {
        $q = new StubQuote;
        $q->status = 'draft';
        $this->assertTrue($q->canBeApproved());
    }

    public function test_can_be_approved_true_for_sent_not_expired(): void
    {
        $q = new StubQuote;
        $q->status = 'sent';
        $this->assertTrue($q->canBeApproved());
    }

    public function test_can_be_approved_false_for_expired_status(): void
    {
        $q = new StubQuote;
        $q->status = 'expired';
        $this->assertFalse($q->canBeApproved());
    }

    public function test_can_be_approved_false_for_draft_past_valid_until(): void
    {
        $q = new StubQuote;
        $q->status = 'draft';
        $q->setRawAttributes(['valid_until' => Carbon::now()->subDay()->toDateTimeString()]);
        $this->assertFalse($q->canBeApproved());
    }

    public function test_can_be_approved_false_for_approved_status(): void
    {
        $q = new StubQuote;
        $q->status = 'approved';
        $this->assertFalse($q->canBeApproved());
    }

    // ─── canBeRejected ────────────────────────────────────────────────────────

    public function test_can_be_rejected_true_for_draft_not_expired(): void
    {
        $q = new StubQuote;
        $q->status = 'draft';
        $this->assertTrue($q->canBeRejected());
    }

    public function test_can_be_rejected_true_for_sent_not_expired(): void
    {
        $q = new StubQuote;
        $q->status = 'sent';
        $this->assertTrue($q->canBeRejected());
    }

    public function test_can_be_rejected_false_when_already_rejected(): void
    {
        $q = new StubQuote;
        $q->status = 'rejected';
        $this->assertFalse($q->canBeRejected());
    }

    public function test_can_be_rejected_false_when_expired_status(): void
    {
        $q = new StubQuote;
        $q->status = 'expired';
        $this->assertFalse($q->canBeRejected());
    }

    public function test_authorization_boundary_approved_quote_cannot_be_rejected(): void
    {
        // Authorization boundary: an approved quote must be protected from rejection —
        // final-state approval is a one-way authorization transition.
        $q = new StubQuote;
        $q->status = 'approved';

        $this->assertFalse(
            $q->canBeRejected(),
            'Authorization boundary: once approved, a quote must not be rejectable'
        );
    }
}
