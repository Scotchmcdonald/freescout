<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Module;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for Module Model
 * Following TESTING_GUIDE.md - using test_ prefix, UnitTestCase base class
 */
class ModuleTest extends UnitTestCase
{
    // ===== MODEL CREATION TESTS =====

    public function test_module_can_be_created(): void
    {
        $module = Module::factory()->create([
            'alias' => 'test_module',
            'name' => 'Test Module',
            'active' => true,
        ]);
        
        $this->assertInstanceOf(Module::class, $module);
        $this->assertDatabaseHas('modules', [
            'id' => $module->id,
            'alias' => 'test_module',
        ]);
    }

    public function test_module_has_correct_fillable_attributes(): void
    {
        $module = new Module();
        
        $this->assertContains('alias', $module->getFillable());
        $this->assertContains('name', $module->getFillable());
        $this->assertContains('active', $module->getFillable());
        $this->assertContains('version', $module->getFillable());
        $this->assertContains('description', $module->getFillable());
        $this->assertContains('author', $module->getFillable());
        $this->assertContains('settings', $module->getFillable());
    }

    public function test_module_uses_has_factory_trait(): void
    {
        $module = Module::factory()->create();
        
        $this->assertInstanceOf(Module::class, $module);
    }

    // ===== CAST TESTS =====

    public function test_active_is_cast_to_boolean(): void
    {
        $module = Module::factory()->create(['active' => 1]);
        
        $this->assertIsBool($module->active);
        $this->assertTrue($module->active);
    }

    public function test_settings_are_cast_to_json(): void
    {
        $settings = ['key' => 'value', 'enabled' => true];
        $module = Module::factory()->create(['settings' => $settings]);
        
        $this->assertEquals($settings, $module->settings);
        $this->assertIsArray($module->settings);
    }

    public function test_created_at_is_cast_to_datetime(): void
    {
        $module = Module::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $module->created_at);
    }

    public function test_updated_at_is_cast_to_datetime(): void
    {
        $module = Module::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $module->updated_at);
    }

    // ===== IS_ACTIVE METHOD TESTS =====

    public function test_is_active_returns_true_when_module_is_active(): void
    {
        $module = Module::factory()->create(['active' => true]);
        
        $this->assertTrue($module->isActive());
    }

    public function test_is_active_returns_false_when_module_is_inactive(): void
    {
        $module = Module::factory()->create(['active' => false]);
        
        $this->assertFalse($module->isActive());
    }

    // ===== ACTIVATE METHOD TESTS =====

    public function test_activate_sets_module_to_active(): void
    {
        $module = Module::factory()->create(['active' => false]);
        
        $result = $module->activate();
        
        $this->assertTrue($result);
        $this->assertTrue($module->fresh()->isActive());
    }

    public function test_activate_returns_true_on_success(): void
    {
        $module = Module::factory()->create(['active' => false]);
        
        $result = $module->activate();
        
        $this->assertTrue($result);
    }

    // ===== DEACTIVATE METHOD TESTS =====

    public function test_deactivate_sets_module_to_inactive(): void
    {
        $module = Module::factory()->create(['active' => true]);
        
        $result = $module->deactivate();
        
        $this->assertTrue($result);
        $this->assertFalse($module->fresh()->isActive());
    }

    public function test_deactivate_returns_true_on_success(): void
    {
        $module = Module::factory()->create(['active' => true]);
        
        $result = $module->deactivate();
        
        $this->assertTrue($result);
    }

    // ===== ATTRIBUTE TESTS =====

    public function test_module_has_alias_attribute(): void
    {
        $module = Module::factory()->create(['alias' => 'test_module_alias']);
        
        $this->assertEquals('test_module_alias', $module->alias);
    }

    public function test_module_has_name_attribute(): void
    {
        $module = Module::factory()->create(['name' => 'Test Module Name']);
        
        $this->assertEquals('Test Module Name', $module->name);
    }

    public function test_module_has_version_attribute(): void
    {
        $module = Module::factory()->create(['version' => '1.2.3']);
        
        $this->assertEquals('1.2.3', $module->version);
    }

    public function test_module_has_description_attribute(): void
    {
        $module = Module::factory()->create(['description' => 'Module description']);
        
        $this->assertEquals('Module description', $module->description);
    }

    public function test_module_has_author_attribute(): void
    {
        $module = Module::factory()->create(['author' => 'John Doe']);
        
        $this->assertEquals('John Doe', $module->author);
    }

    // ===== QUERY TESTS =====

    public function test_can_query_active_modules(): void
    {
        Module::factory()->count(3)->create(['active' => true]);
        Module::factory()->count(2)->create(['active' => false]);
        
        $activeModules = Module::where('active', true)->get();
        
        $this->assertCount(3, $activeModules);
    }

    public function test_can_query_modules_by_alias(): void
    {
        Module::factory()->create(['alias' => 'module_one']);
        Module::factory()->create(['alias' => 'module_two']);
        
        $module = Module::where('alias', 'module_one')->first();
        
        $this->assertNotNull($module);
        $this->assertEquals('module_one', $module->alias);
    }

    public function test_can_query_modules_by_author(): void
    {
        Module::factory()->count(2)->create(['author' => 'Author One']);
        Module::factory()->create(['author' => 'Author Two']);
        
        $modules = Module::where('author', 'Author One')->get();
        
        $this->assertCount(2, $modules);
    }

    // ===== EDGE CASES =====

    public function test_module_with_null_settings(): void
    {
        $module = Module::factory()->create(['settings' => null]);
        
        $this->assertNull($module->settings);
    }

    public function test_module_with_empty_settings_array(): void
    {
        $module = Module::factory()->create(['settings' => []]);
        
        $this->assertEquals([], $module->settings);
    }

    public function test_module_with_complex_settings(): void
    {
        $settings = [
            'enabled' => true,
            'api_key' => 'secret-key',
            'options' => ['opt1', 'opt2'],
            'nested' => ['deep' => ['value' => 123]],
        ];
        
        $module = Module::factory()->create(['settings' => $settings]);
        
        $this->assertEquals($settings, $module->settings);
        $this->assertEquals('secret-key', $module->settings['api_key']);
    }

    public function test_module_with_null_version(): void
    {
        $module = Module::factory()->create(['version' => null]);
        
        $this->assertNull($module->version);
    }

    public function test_module_with_null_description(): void
    {
        $module = Module::factory()->create(['description' => null]);
        
        $this->assertNull($module->description);
    }

    public function test_module_with_null_author(): void
    {
        $module = Module::factory()->create(['author' => null]);
        
        $this->assertNull($module->author);
    }

    public function test_module_can_be_updated(): void
    {
        $module = Module::factory()->create(['name' => 'Old Name']);
        
        $module->update(['name' => 'New Name']);
        
        $this->assertEquals('New Name', $module->fresh()->name);
    }

    public function test_module_can_be_deleted(): void
    {
        $module = Module::factory()->create();
        $id = $module->id;
        
        $module->delete();
        
        $this->assertDatabaseMissing('modules', ['id' => $id]);
    }

    public function test_module_timestamps_are_automatically_set(): void
    {
        $module = Module::factory()->create();
        
        $this->assertNotNull($module->created_at);
        $this->assertNotNull($module->updated_at);
    }

    public function test_activate_on_already_active_module(): void
    {
        $module = Module::factory()->create(['active' => true]);
        
        $result = $module->activate();
        
        $this->assertTrue($result);
        $this->assertTrue($module->fresh()->isActive());
    }

    public function test_deactivate_on_already_inactive_module(): void
    {
        $module = Module::factory()->create(['active' => false]);
        
        $result = $module->deactivate();
        
        $this->assertTrue($result);
        $this->assertFalse($module->fresh()->isActive());
    }

    public function test_module_with_semantic_versioning(): void
    {
        $versions = ['1.0.0', '2.1.3', '10.5.2-beta', '3.0.0-rc.1'];
        
        foreach ($versions as $version) {
            $module = Module::factory()->create(['version' => $version]);
            $this->assertEquals($version, $module->version);
        }
    }

    public function test_module_with_special_characters_in_name(): void
    {
        $module = Module::factory()->create(['name' => 'Test Module & Feature']);
        
        $this->assertEquals('Test Module & Feature', $module->name);
    }

    public function test_module_with_long_description(): void
    {
        $longDescription = str_repeat('Description text. ', 100);
        $module = Module::factory()->create(['description' => $longDescription]);
        
        $this->assertEquals($longDescription, $module->description);
    }

    public function test_multiple_modules_can_be_created(): void
    {
        Module::factory()->count(10)->create();
        
        $this->assertCount(10, Module::all());
    }

    public function test_can_find_all_inactive_modules(): void
    {
        Module::factory()->count(2)->create(['active' => true]);
        Module::factory()->count(3)->create(['active' => false]);
        
        $inactiveModules = Module::where('active', false)->get();
        
        $this->assertCount(3, $inactiveModules);
    }

    public function test_module_alias_is_case_sensitive(): void
    {
        Module::factory()->create(['alias' => 'TestModule']);
        Module::factory()->create(['alias' => 'testmodule']);
        
        $this->assertCount(2, Module::all());
    }
}
