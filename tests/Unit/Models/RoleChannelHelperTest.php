<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Channel;
use App\Models\Role;
use Tests\PureUnitTestCase;

class RoleChannelHelperTest extends PureUnitTestCase
{
    public function test_role_scope_and_super_admin_helpers(): void
    {
        $superAdmin = new Role(['is_super_admin' => true, 'scope' => 'internal']);
        $internal = new Role(['is_super_admin' => false, 'scope' => 'internal']);
        $client = new Role(['is_super_admin' => false, 'scope' => 'client']);

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertFalse($internal->isSuperAdmin());

        $this->assertTrue($internal->isInternal());
        $this->assertFalse($client->isInternal());

        $this->assertTrue($client->isClient());
        $this->assertFalse($internal->isClient());
    }

    public function test_channel_is_active_requires_true_boolean_state(): void
    {
        $active = new Channel(['active' => true]);
        $inactive = new Channel(['active' => false]);

        $this->assertTrue($active->isActive());
        $this->assertFalse($inactive->isActive());
    }
}
