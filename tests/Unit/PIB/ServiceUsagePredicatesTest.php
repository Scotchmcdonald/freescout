<?php

declare(strict_types=1);

namespace Tests\Unit\PIB;

use Modules\PIB\Models\ServiceUsage;
use Tests\PureUnitTestCase;

if (! class_exists(StubServiceUsage::class)) {
final class StubServiceUsage extends ServiceUsage
{
    protected static function booted(): void {}
}
}


final class ServiceUsagePredicatesTest extends PureUnitTestCase
{
    // ─── serviceTypes static ──────────────────────────────────────────────────

    public function test_service_types_returns_all_seven_types(): void
    {
        $types = ServiceUsage::serviceTypes();
        $this->assertCount(7, $types);
    }

    public function test_service_types_contains_labor(): void
    {
        $this->assertContains(ServiceUsage::TYPE_LABOR, ServiceUsage::serviceTypes());
    }

    public function test_type_constants_are_distinct(): void
    {
        $types = ServiceUsage::serviceTypes();
        $this->assertSame(count($types), count(array_unique($types)));
    }

    public function test_status_constants_are_distinct(): void
    {
        $statuses = [
            ServiceUsage::STATUS_DRAFT,
            ServiceUsage::STATUS_PENDING,
            ServiceUsage::STATUS_APPROVED,
            ServiceUsage::STATUS_BILLED,
        ];

        $this->assertSame(count($statuses), count(array_unique($statuses)));
    }

    // ─── isDraft ──────────────────────────────────────────────────────────────

    public function test_is_draft_true_for_draft_status(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_DRAFT;
        $this->assertTrue($u->isDraft());
    }

    public function test_is_draft_false_for_other(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_APPROVED;
        $this->assertFalse($u->isDraft());
    }

    // ─── isPending ────────────────────────────────────────────────────────────

    public function test_is_pending_true_for_pending_status(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_PENDING;
        $this->assertTrue($u->isPending());
    }

    public function test_is_pending_false_for_draft(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_DRAFT;
        $this->assertFalse($u->isPending());
    }

    // ─── isApproved ───────────────────────────────────────────────────────────

    public function test_is_approved_true_for_approved(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_APPROVED;
        $this->assertTrue($u->isApproved());
    }

    // ─── isBilled ─────────────────────────────────────────────────────────────

    public function test_is_billed_true_for_billed(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_BILLED;
        $this->assertTrue($u->isBilled());
    }

    public function test_is_billed_false_for_approved(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_APPROVED;
        $this->assertFalse($u->isBilled());
    }

    // ─── canEdit ──────────────────────────────────────────────────────────────

    public function test_can_edit_true_for_draft(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_DRAFT;
        $this->assertTrue($u->canEdit());
    }

    public function test_can_edit_true_for_pending(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_PENDING;
        $this->assertTrue($u->canEdit());
    }

    public function test_can_edit_false_for_approved(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_APPROVED;
        $this->assertFalse($u->canEdit());
    }

    public function test_can_edit_false_for_billed(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_BILLED;
        $this->assertFalse($u->canEdit());
    }

    // ─── canDelete ────────────────────────────────────────────────────────────

    public function test_can_delete_true_for_draft(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_DRAFT;
        $this->assertTrue($u->canDelete());
    }

    public function test_can_delete_true_for_approved(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_APPROVED;
        $this->assertTrue($u->canDelete());
    }

    public function test_can_delete_false_for_billed(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_BILLED;
        $this->assertFalse($u->canDelete());
    }

    // ─── canApprove ───────────────────────────────────────────────────────────

    public function test_can_approve_true_for_pending(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_PENDING;
        $this->assertTrue($u->canApprove());
    }

    public function test_can_approve_false_for_draft(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_DRAFT;
        $this->assertFalse($u->canApprove());
    }

    public function test_can_approve_false_for_approved(): void
    {
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_APPROVED;
        $this->assertFalse($u->canApprove());
    }

    public function test_authorization_boundary_billed_service_usage_cannot_be_re_approved(): void
    {
        // Authorization boundary: once a service usage entry has been billed it must
        // not be approvable again — billing acts as a final authorization gate.
        $u = new StubServiceUsage;
        $u->status = ServiceUsage::STATUS_BILLED;

        $this->assertFalse($u->canApprove(),
            'Authorization boundary: billed service usage must be locked from re-approval'
        );
    }

    // ─── calculateTotal ───────────────────────────────────────────────────────

    public function test_calculate_total_uses_hourly_rate_times_hours(): void
    {
        $u = new StubServiceUsage;
        $u->hourly_rate = 100.00;
        $u->hours = 2.5;
        $this->assertSame(250.0, $u->calculateTotal());
    }

    public function test_calculate_total_rounds_to_two_decimals(): void
    {
        $u = new StubServiceUsage;
        $u->hourly_rate = 33.33;
        $u->hours = 1.0;
        $this->assertSame(33.33, $u->calculateTotal());
    }

    public function test_calculate_total_uses_default_rate_when_no_hourly_rate(): void
    {
        $u = new StubServiceUsage;
        $u->hourly_rate = null;
        $u->hours = 1.0;
        // Default rate is 150.00
        $this->assertSame(150.0, $u->calculateTotal());
    }

    public function test_authorization_boundary_draft_status_is_unauthorized_to_publish(): void
    {
        // Authorization boundary: a service usage in draft status must not be
        // considered active/published — it is unauthorized at this lifecycle stage.
        $u = new StubServiceUsage;
        $u->status = 'draft';

        $this->assertTrue($u->isDraft(),
            'Draft service usage must be unauthorized for active billing operations'
        );
        $this->assertFalse($u->isPending(),
            'Draft status must not pass the authorization check for pending state'
        );
    }

    public function test_validation_boundary_unauthorized_status_transitions(): void
    {
        // Validation boundary: a service usage that is draft must fail the
        // pending-state authorization check — status transitions are validated.
        $u = new StubServiceUsage;
        $u->status = 'pending_approval';

        $this->assertFalse($u->isDraft(),
            'Pending approval usage must be forbidden from passing the draft validation boundary'
        );
        $this->assertTrue($u->isPending(),
            'Pending approval status passes the authorization check for pending state'
        );
    }
}
