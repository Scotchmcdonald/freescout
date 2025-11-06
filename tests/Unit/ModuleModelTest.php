<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Module;
use Tests\TestCase;

class ModuleModelTest extends TestCase
{
    public function test_model_can_be_instantiated(): void
    {
        $module = new Module();
        $this->assertInstanceOf(Module::class, $module);
    }

    public function test_model_has_fillable_attributes(): void
    {
        $module = new Module([
            'name' => 'Test Module',
            'alias' => 'test-module',
            'version' => '1.0.0',
            'active' => true,
        ]);

        $this->assertEquals('Test Module', $module->name);
        $this->assertEquals('test-module', $module->alias);
        $this->assertEquals('1.0.0', $module->version);
        $this->assertTrue($module->active);
    }
}
