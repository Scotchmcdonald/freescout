<?php

declare(strict_types=1);

namespace Tests\Unit\Action1;

use Modules\Action1\Enums\Action1Role;
use Tests\PureUnitTestCase;

final class Action1RoleEnumTest extends PureUnitTestCase
{
    public function test_sync_config_key(): void
    {
        $this->assertSame('sync', Action1Role::Sync->configKey());
    }

    public function test_automation_runner_config_key(): void
    {
        $this->assertSame('automation_runner', Action1Role::AutomationRunner->configKey());
    }

    public function test_script_manager_config_key(): void
    {
        $this->assertSame('script_manager', Action1Role::ScriptManager->configKey());
    }

    public function test_token_cache_key_contains_role_value(): void
    {
        $this->assertSame('action1_token:sync', Action1Role::Sync->tokenCacheKey());
        $this->assertSame('action1_token:automation_runner', Action1Role::AutomationRunner->tokenCacheKey());
        $this->assertSame('action1_token:script_manager', Action1Role::ScriptManager->tokenCacheKey());
    }

    public function test_labels_are_non_empty_strings(): void
    {
        foreach (Action1Role::cases() as $role) {
            $this->assertNotEmpty($role->label());
        }
    }

    public function test_labels_are_distinct(): void
    {
        $labels = array_map(fn (Action1Role $r) => $r->label(), Action1Role::cases());
        $this->assertSame(count($labels), count(array_unique($labels)));
    }

    public function test_descriptions_are_non_empty_strings(): void
    {
        foreach (Action1Role::cases() as $role) {
            $this->assertNotEmpty($role->description());
        }
    }

    public function test_config_and_value_match(): void
    {
        foreach (Action1Role::cases() as $role) {
            $this->assertSame($role->value, $role->configKey());
        }
    }
}
