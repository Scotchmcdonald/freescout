<?php

declare(strict_types=1);

namespace Tests\Unit\Action1;

use Modules\Action1\Models\Action1Config;
use Tests\PureUnitTestCase;

final class TestAction1Config extends Action1Config
{
    protected static function booted(): void {}

    protected function casts(): array
    {
        return [];
    }
}

class Action1ConfigHelperTest extends PureUnitTestCase
{
    public function test_is_assigned_returns_true_when_client_id_is_present(): void
    {
        $config = new TestAction1Config;
        $config->client_id = 42;

        $this->assertTrue($config->isAssigned());
        $this->assertFalse($config->isUnassigned());
    }

    public function test_is_unassigned_returns_true_when_client_id_is_null(): void
    {
        $config = new TestAction1Config;
        $config->client_id = null;

        $this->assertFalse($config->isAssigned());
        $this->assertTrue($config->isUnassigned());
    }

    public function test_is_assigned_treats_zero_as_assigned_because_it_is_not_null(): void
    {
        $config = new TestAction1Config;
        $config->client_id = 0;

        $this->assertTrue($config->isAssigned());
        $this->assertFalse($config->isUnassigned());
    }
}
