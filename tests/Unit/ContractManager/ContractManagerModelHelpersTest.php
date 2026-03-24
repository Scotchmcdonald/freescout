<?php

declare(strict_types=1);

namespace Tests\Unit\ContractManager;

use Illuminate\Support\Carbon;
use Modules\ContractManager\Models\Contract;
use Modules\ContractManager\Models\Milestone;
use Modules\ContractManager\Models\Quote;
use Modules\ContractManager\Models\QuoteLineItem;
use Tests\PureUnitTestCase;

final class TestContract extends Contract
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }

    public function getAttribute($key): mixed
    {
        if ($key === 'end_date') {
            $value = $this->attributes[$key] ?? null;
            if ($value instanceof Carbon) {
                return $value;
            }

            return $value ? Carbon::parse((string) $value) : null;
        }

        return parent::getAttribute($key);
    }
}

final class TestQuote extends Quote
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }

    public function getAttribute($key): mixed
    {
        if ($key === 'valid_until') {
            $value = $this->attributes[$key] ?? null;
            if ($value instanceof Carbon) {
                return $value;
            }

            return $value ? Carbon::parse((string) $value) : null;
        }

        return parent::getAttribute($key);
    }
}

final class TestQuoteLineItem extends QuoteLineItem
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

final class TestMilestone extends Milestone
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }

    public function getAttribute($key): mixed
    {
        if ($key === 'target_date') {
            $value = $this->attributes[$key] ?? null;
            if ($value instanceof Carbon) {
                return $value;
            }

            return $value ? Carbon::parse((string) $value) : null;
        }

        return parent::getAttribute($key);
    }
}

class ContractManagerModelHelpersTest extends PureUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-24 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function contract(array $attrs = []): TestContract
    {
        $model = new TestContract;
        $raw = [];
        foreach ($attrs as $key => $value) {
            if ($value instanceof Carbon) {
                $raw[$key] = $value->format('Y-m-d H:i:s');
            } else {
                $raw[$key] = $value;
            }
        }
        $model->setRawAttributes($raw, true);

        return $model;
    }

    private function quote(array $attrs = []): TestQuote
    {
        $model = new TestQuote;
        $raw = [];
        foreach ($attrs as $key => $value) {
            if ($value instanceof Carbon) {
                $raw[$key] = $value->format('Y-m-d H:i:s');
            } else {
                $raw[$key] = $value;
            }
        }
        $model->setRawAttributes($raw, true);

        return $model;
    }

    private function milestone(array $attrs = []): TestMilestone
    {
        $model = new TestMilestone;
        $raw = [];
        foreach ($attrs as $key => $value) {
            if ($value instanceof Carbon) {
                $raw[$key] = $value->format('Y-m-d H:i:s');
            } else {
                $raw[$key] = $value;
            }
        }
        $model->setRawAttributes($raw, true);

        return $model;
    }

    public function test_contract_is_active_true_only_for_active_status(): void
    {
        $this->assertTrue($this->contract(['status' => 'active'])->isActive());
        $this->assertFalse($this->contract(['status' => 'draft'])->isActive());
    }

    public function test_contract_is_expiring_soon_false_when_no_end_date(): void
    {
        $contract = $this->contract(['renewal_notice_days' => 30, 'end_date' => null]);

        $this->assertFalse($contract->isExpiringSoon());
    }

    public function test_contract_is_expiring_soon_true_within_notice_window(): void
    {
        $contract = $this->contract([
            'renewal_notice_days' => 30,
            'end_date' => Carbon::parse('2026-04-05 00:00:00'),
        ]);

        $this->assertTrue($contract->isExpiringSoon());
    }

    public function test_contract_is_expiring_soon_true_at_notice_boundary(): void
    {
        $contract = $this->contract([
            'renewal_notice_days' => 5,
            'end_date' => Carbon::parse('2026-03-29 00:00:00'),
        ]);

        $this->assertTrue($contract->isExpiringSoon());
    }

    public function test_contract_is_expired_true_for_expired_status_or_past_end_date(): void
    {
        $byStatus = $this->contract(['status' => 'expired', 'end_date' => Carbon::parse('2026-05-01 00:00:00')]);
        $byDate = $this->contract(['status' => 'active', 'end_date' => Carbon::parse('2026-03-01 00:00:00')]);
        $notExpired = $this->contract(['status' => 'active', 'end_date' => Carbon::parse('2026-04-01 00:00:00')]);

        $this->assertTrue($byStatus->isExpired());
        $this->assertTrue($byDate->isExpired());
        $this->assertFalse($notExpired->isExpired());
    }

    public function test_contract_days_until_expiration_handles_null_future_and_past_dates(): void
    {
        $none = $this->contract(['end_date' => null]);
        $future = $this->contract(['end_date' => Carbon::parse('2026-03-30 00:00:00')]);
        $past = $this->contract(['end_date' => Carbon::parse('2026-03-20 00:00:00')]);

        $this->assertNull($none->daysUntilExpiration());
        $this->assertSame(5, $future->daysUntilExpiration());
        $this->assertSame(0, $past->daysUntilExpiration());
    }

    public function test_contract_is_purchased_true_for_owned_or_transferred_only(): void
    {
        $this->assertTrue($this->contract(['ownership_status' => 'owned'])->isPurchased());
        $this->assertTrue($this->contract(['ownership_status' => 'transferred'])->isPurchased());
        $this->assertFalse($this->contract(['ownership_status' => 'leased'])->isPurchased());
    }

    public function test_quote_is_draft_and_is_approved_helpers(): void
    {
        $this->assertTrue($this->quote(['status' => 'draft'])->isDraft());
        $this->assertFalse($this->quote(['status' => 'approved'])->isDraft());

        $this->assertTrue($this->quote(['status' => 'approved'])->isApproved());
        $this->assertFalse($this->quote(['status' => 'draft'])->isApproved());
    }

    public function test_quote_is_expired_by_status_or_date(): void
    {
        $expiredStatus = $this->quote(['status' => 'expired', 'valid_until' => Carbon::parse('2026-04-01 00:00:00')]);
        $expiredDate = $this->quote(['status' => 'approved', 'valid_until' => Carbon::parse('2026-03-01 00:00:00')]);
        $active = $this->quote(['status' => 'approved', 'valid_until' => Carbon::parse('2026-04-01 00:00:00')]);

        $this->assertTrue($expiredStatus->isExpired());
        $this->assertTrue($expiredDate->isExpired());
        $this->assertFalse($active->isExpired());
    }

    public function test_quote_line_item_quantity_type_helpers(): void
    {
        $perUser = new TestQuoteLineItem(['quantity_type' => 'per_user']);
        $perAsset = new TestQuoteLineItem(['quantity_type' => 'per_asset']);
        $fixed = new TestQuoteLineItem(['quantity_type' => 'fixed']);

        $this->assertTrue($perUser->isPerUser());
        $this->assertFalse($perUser->isPerAsset());
        $this->assertFalse($perUser->isFixed());

        $this->assertTrue($perAsset->isPerAsset());
        $this->assertFalse($perAsset->isPerUser());

        $this->assertTrue($fixed->isFixed());
        $this->assertFalse($fixed->isPerUser());
    }

    public function test_milestone_status_predicates(): void
    {
        $this->assertTrue($this->milestone(['status' => 'achieved'])->isAchieved());
        $this->assertTrue($this->milestone(['status' => 'pending'])->isPending());
        $this->assertTrue($this->milestone(['status' => 'in_progress'])->isInProgress());
        $this->assertTrue($this->milestone(['status' => 'blocked'])->isBlocked());
        $this->assertTrue($this->milestone(['status' => 'skipped'])->isSkipped());

        $this->assertFalse($this->milestone(['status' => 'pending'])->isAchieved());
    }

    public function test_milestone_is_overdue_requires_target_date_and_non_achieved_status(): void
    {
        $noDate = $this->milestone(['status' => 'pending', 'target_date' => null]);
        $pastPending = $this->milestone(['status' => 'pending', 'target_date' => Carbon::parse('2026-03-20 00:00:00')]);
        $pastAchieved = $this->milestone(['status' => 'achieved', 'target_date' => Carbon::parse('2026-03-20 00:00:00')]);
        $futurePending = $this->milestone(['status' => 'pending', 'target_date' => Carbon::parse('2026-03-30 00:00:00')]);

        $this->assertFalse($noDate->isOverdue());
        $this->assertTrue($pastPending->isOverdue());
        $this->assertFalse($pastAchieved->isOverdue());
        $this->assertFalse($futurePending->isOverdue());
    }

    public function test_milestone_get_status_info_maps_known_statuses_and_defaults_to_pending(): void
    {
        $achieved = $this->milestone(['status' => 'achieved'])->getStatusInfo();
        $blocked = $this->milestone(['status' => 'blocked'])->getStatusInfo();
        $unknown = $this->milestone(['status' => 'custom'])->getStatusInfo();

        $this->assertSame('Achieved', $achieved['label']);
        $this->assertSame('check-circle', $achieved['icon']);

        $this->assertSame('Blocked', $blocked['label']);
        $this->assertSame('x-circle', $blocked['icon']);

        $this->assertSame('Pending', $unknown['label']);
        $this->assertSame('clock', $unknown['icon']);
    }
}
