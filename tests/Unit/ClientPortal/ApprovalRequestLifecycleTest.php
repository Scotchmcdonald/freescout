<?php

declare(strict_types=1);

namespace Tests\Unit\ClientPortal;

use Carbon\Carbon;
use Modules\ClientPortal\Models\ApprovalRequest;
use Tests\PureUnitTestCase;

final class TestApprovalRequestLifecycleModel extends ApprovalRequest
{
    public bool $saved = false;

    public function setAttribute($key, $value): static
    {
        if (in_array($key, ['approved_at', 'signed_at'], true)) {
            $this->attributes[$key] = $value;

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function save(array $options = []): bool
    {
        $this->saved = true;

        return true;
    }
}

class ApprovalRequestLifecycleTest extends PureUnitTestCase
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

    private function request(array $attrs = []): TestApprovalRequestLifecycleModel
    {
        $model = new TestApprovalRequestLifecycleModel;
        $model->setRawAttributes(array_merge([
            'status' => ApprovalRequest::STATUS_PENDING,
        ], $attrs), true);

        return $model;
    }

    public function test_approve_sets_status_metadata_and_saves(): void
    {
        $request = $this->request();
        $ok = $request->approve('looks good', 42);

        $this->assertTrue($ok);
        $this->assertTrue($request->saved);
        $this->assertSame(ApprovalRequest::STATUS_APPROVED, $request->status);
        $this->assertSame(42, $request->approved_by);
        $this->assertSame('looks good', $request->approval_notes);
        $this->assertInstanceOf(Carbon::class, $request->approved_at);
    }

    public function test_reject_sets_status_metadata_and_saves(): void
    {
        $request = $this->request();
        $ok = $request->reject('missing scope', 7);

        $this->assertTrue($ok);
        $this->assertTrue($request->saved);
        $this->assertSame(ApprovalRequest::STATUS_REJECTED, $request->status);
        $this->assertSame(7, $request->approved_by);
        $this->assertSame('missing scope', $request->approval_notes);
        $this->assertInstanceOf(Carbon::class, $request->approved_at);
    }

    public function test_sign_sets_signature_fields_and_saves(): void
    {
        $request = $this->request();
        $ok = $request->sign('sig-data', 'typed');

        $this->assertTrue($ok);
        $this->assertTrue($request->saved);
        $this->assertSame(ApprovalRequest::STATUS_SIGNED, $request->status);
        $this->assertSame('sig-data', $request->signature_data);
        $this->assertSame('typed', $request->signature_method);
        $this->assertInstanceOf(Carbon::class, $request->signed_at);
    }
}
