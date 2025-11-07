<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleModelTest extends TestCase
{
    use RefreshDatabase;

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
            'description' => 'Test description',
        ]);

        $this->assertEquals('Test Module', $module->name);
        $this->assertEquals('test-module', $module->alias);
        $this->assertEquals('1.0.0', $module->version);
        $this->assertTrue($module->active);
        $this->assertEquals('Test description', $module->description);
    }

    public function test_active_cast_to_boolean(): void
    {
        $module = Module::factory()->create(['active' => true]);
        $this->assertIsBool($module->active);
        $this->assertTrue($module->active);

        $module = Module::factory()->create(['active' => false]);
        $this->assertIsBool($module->active);
        $this->assertFalse($module->active);
    }

    public function test_description_is_stored(): void
    {
        $module = Module::factory()->create(['description' => 'Module description']);

        $this->assertEquals('Module description', $module->description);
        $this->assertDatabaseHas('modules', [
            'id' => $module->id,
            'description' => 'Module description',
        ]);
    }

    public function test_is_active_returns_true_when_active(): void
    {
        $module = Module::factory()->make(['active' => true]);

        $this->assertTrue($module->isActive());
    }

    public function test_is_active_returns_false_when_inactive(): void
    {
        $module = Module::factory()->make(['active' => false]);

        $this->assertFalse($module->isActive());
    }

    public function test_activate_sets_active_to_true(): void
    {
        $module = Module::factory()->create(['active' => false]);

        $result = $module->activate();

        $this->assertTrue($result);
        $this->assertTrue($module->active);
        $this->assertDatabaseHas('modules', [
            'id' => $module->id,
            'active' => true,
        ]);
    }

    public function test_deactivate_sets_active_to_false(): void
    {
        $module = Module::factory()->create(['active' => true]);

        $result = $module->deactivate();

        $this->assertTrue($result);
        $this->assertFalse($module->active);
        $this->assertDatabaseHas('modules', [
            'id' => $module->id,
            'active' => false,
        ]);
    }
}
