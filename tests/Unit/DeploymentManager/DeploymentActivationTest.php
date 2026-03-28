<?php

declare(strict_types=1);

namespace Tests\Unit\DeploymentManager;

use Modules\DeploymentManager\Models\DeploymentActivation;
use Tests\PureUnitTestCase;

// ── Stub ──────────────────────────────────────────────────────────────────────

if (! class_exists(StubDeploymentActivation::class)) {
    final class StubDeploymentActivation extends DeploymentActivation
    {
        protected static function booted(): void {}

        public function getDateFormat(): string
        {
            return 'Y-m-d H:i:s';
        }
    }
}

// ── Test class ────────────────────────────────────────────────────────────────

final class DeploymentActivationTest extends PureUnitTestCase
{
    // ── isUsed ────────────────────────────────────────────────────────

    public function test_is_used_when_used_at_is_set(): void
    {
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => now()->subHour()->format('Y-m-d H:i:s'), 'expires_at' => now()->addDay()->format('Y-m-d H:i:s')]);
        $this->assertTrue($a->isUsed());
    }

    public function test_is_not_used_when_used_at_is_null(): void
    {
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => null, 'expires_at' => now()->addDay()->format('Y-m-d H:i:s')]);
        $this->assertFalse($a->isUsed());
    }

    // ── isValid ───────────────────────────────────────────────────────

    public function test_is_valid_when_not_used_and_not_expired(): void
    {
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => null, 'expires_at' => now()->addDay()->format('Y-m-d H:i:s')]);
        $this->assertTrue($a->isValid());
    }

    public function test_is_not_valid_when_already_used(): void
    {
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => now()->subHour()->format('Y-m-d H:i:s'), 'expires_at' => now()->addDay()->format('Y-m-d H:i:s')]);
        $this->assertFalse($a->isValid());
    }

    public function test_is_not_valid_when_expired(): void
    {
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => null, 'expires_at' => now()->subDay()->format('Y-m-d H:i:s')]);
        $this->assertFalse($a->isValid());
    }

    // ── isExpired ─────────────────────────────────────────────────────

    public function test_is_expired_when_not_used_and_past(): void
    {
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => null, 'expires_at' => now()->subDay()->format('Y-m-d H:i:s')]);
        $this->assertTrue($a->isExpired());
    }

    public function test_is_not_expired_when_used(): void
    {
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => now()->subHour()->format('Y-m-d H:i:s'), 'expires_at' => now()->subDay()->format('Y-m-d H:i:s')]);
        $this->assertFalse($a->isExpired());
    }

    public function test_is_not_expired_when_future(): void
    {
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => null, 'expires_at' => now()->addDay()->format('Y-m-d H:i:s')]);
        $this->assertFalse($a->isExpired());
    }

    // ── statusLabel ───────────────────────────────────────────────────

    public function test_status_label_used(): void
    {
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => now()->subHour()->format('Y-m-d H:i:s'), 'expires_at' => now()->addDay()->format('Y-m-d H:i:s')]);
        $this->assertSame('Used', $a->statusLabel());
    }

    public function test_status_label_expired(): void
    {
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => null, 'expires_at' => now()->subDay()->format('Y-m-d H:i:s')]);
        $this->assertSame('Expired', $a->statusLabel());
    }

    public function test_status_label_valid(): void
    {
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => null, 'expires_at' => now()->addDay()->format('Y-m-d H:i:s')]);
        $this->assertSame('Valid', $a->statusLabel());
    }

    // ── statusColor ───────────────────────────────────────────────────

    public function test_status_color_used_is_gray(): void
    {
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => now()->subHour()->format('Y-m-d H:i:s'), 'expires_at' => now()->addDay()->format('Y-m-d H:i:s')]);
        $this->assertSame('gray', $a->statusColor());
    }

    public function test_status_color_expired_is_danger(): void
    {
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => null, 'expires_at' => now()->subDay()->format('Y-m-d H:i:s')]);
        $this->assertSame('danger', $a->statusColor());
    }

    public function test_status_color_valid_is_success(): void
    {
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => null, 'expires_at' => now()->addDay()->format('Y-m-d H:i:s')]);
        $this->assertSame('success', $a->statusColor());
    }

    public function test_authorization_boundary_expired_activation_key_is_invalid(): void
    {
        // Authorization boundary: an expired activation key must be refused —
        // time-limited credentials enforce a time-based authorization window.
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => null, 'expires_at' => now()->subDay()->format('Y-m-d H:i:s')]);

        $this->assertFalse(
            $a->isValid(),
            'Authorization boundary: expired activation must not be valid for deployment'
        );
    }

    public function test_authorization_boundary_already_used_activation_key_is_invalid(): void
    {
        // Authorization boundary: a previously used activation key must not be
        // re-usable — single-use activation enforces a one-time authorization gate.
        $a = new StubDeploymentActivation;
        $a->setRawAttributes(['used_at' => now()->subHour()->format('Y-m-d H:i:s'), 'expires_at' => now()->addDay()->format('Y-m-d H:i:s')]);

        $this->assertFalse(
            $a->isValid(),
            'Authorization boundary: already-used activation must not be valid for deployment'
        );
    }
}
