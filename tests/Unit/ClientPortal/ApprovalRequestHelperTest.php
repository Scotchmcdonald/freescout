<?php

declare(strict_types=1);

namespace Tests\Unit\ClientPortal;

use Carbon\Carbon;
use Modules\ClientPortal\Models\ApprovalRequest;
use Tests\PureUnitTestCase;

final class TestApprovalRequest extends ApprovalRequest
{
    public function getAttribute($key): mixed
    {
        if ($key === 'created_at') {
            $value = $this->attributes[$key] ?? null;
            if ($value instanceof Carbon) {
                return $value;
            }

            return $value ? Carbon::parse((string) $value) : null;
        }

        return parent::getAttribute($key);
    }
}

class ApprovalRequestHelperTest extends PureUnitTestCase
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

    private function request(array $attrs = []): TestApprovalRequest
    {
        $model = new TestApprovalRequest;
        $raw = [
            'status' => ApprovalRequest::STATUS_PENDING,
            'request_type' => ApprovalRequest::TYPE_QUOTE_APPROVAL,
            'created_at' => Carbon::now()->subDays(3)->format('Y-m-d H:i:s'),
        ];

        foreach ($attrs as $key => $value) {
            $raw[$key] = $value instanceof Carbon
                ? $value->format('Y-m-d H:i:s')
                : $value;
        }

        $model->setRawAttributes($raw, true);

        return $model;
    }

    public function test_can_be_actioned_only_when_pending(): void
    {
        $this->assertTrue($this->request(['status' => ApprovalRequest::STATUS_PENDING])->canBeActioned());
        $this->assertFalse($this->request(['status' => ApprovalRequest::STATUS_APPROVED])->canBeActioned());
    }

    public function test_authorization_boundary_signed_request_cannot_be_actioned(): void
    {
        // Authorization boundary: a signed (completed) approval request must not
        // accept further actions — signing is a terminal authorization gate that
        // prevents any loophole re-submission.
        $signed = $this->request(['status' => ApprovalRequest::STATUS_SIGNED]);

        $this->assertFalse(
            $signed->canBeActioned(),
            'Authorization boundary: signed requests are finalized and must not be re-actionable'
        );
    }

    public function test_status_badge_mappings_include_default(): void
    {
        $this->assertSame('warning', $this->request(['status' => ApprovalRequest::STATUS_PENDING])->status_badge);
        $this->assertSame('success', $this->request(['status' => ApprovalRequest::STATUS_APPROVED])->status_badge);
        $this->assertSame('danger', $this->request(['status' => ApprovalRequest::STATUS_REJECTED])->status_badge);
        $this->assertSame('primary', $this->request(['status' => ApprovalRequest::STATUS_SIGNED])->status_badge);
        $this->assertSame('secondary', $this->request(['status' => 'other'])->status_badge);
    }

    public function test_status_label_mappings_include_default(): void
    {
        $this->assertSame('Awaiting Your Review', $this->request(['status' => ApprovalRequest::STATUS_PENDING])->status_label);
        $this->assertSame('Approved', $this->request(['status' => ApprovalRequest::STATUS_APPROVED])->status_label);
        $this->assertSame('Rejected', $this->request(['status' => ApprovalRequest::STATUS_REJECTED])->status_label);
        $this->assertSame('Signed & Approved', $this->request(['status' => ApprovalRequest::STATUS_SIGNED])->status_label);
        $this->assertSame('Escalated', $this->request(['status' => 'escalated'])->status_label);
    }

    public function test_type_label_mappings_include_default_formatter(): void
    {
        $this->assertSame('Quote Approval', $this->request(['request_type' => ApprovalRequest::TYPE_QUOTE_APPROVAL])->type_label);
        $this->assertSame('Invoice Dispute', $this->request(['request_type' => ApprovalRequest::TYPE_INVOICE_DISPUTE])->type_label);
        $this->assertSame('Milestone Approval', $this->request(['request_type' => ApprovalRequest::TYPE_MILESTONE_APPROVAL])->type_label);
        $this->assertSame('Other request', $this->request(['request_type' => 'other_request'])->type_label);
    }

    public function test_aging_days_is_zero_for_non_pending_and_diff_for_pending(): void
    {
        $pending = $this->request(['status' => ApprovalRequest::STATUS_PENDING, 'created_at' => Carbon::now()->subDays(5)]);
        $approved = $this->request(['status' => ApprovalRequest::STATUS_APPROVED, 'created_at' => Carbon::now()->subDays(5)]);

        $this->assertSame(-5, $pending->aging_days);
        $this->assertSame(0, $approved->aging_days);
    }
}
