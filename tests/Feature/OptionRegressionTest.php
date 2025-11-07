<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Option;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OptionRegressionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression Test: Verify option retrieval logic matches L5 implementation.
     * 
     * Modern File: app/Models/Option.php
     * Archived File: archive/app/Option.php
     * 
     * The L5 version used Option::get($name, $default) static method.
     * The modern version uses Option::getValue($name, $default).
     * Both should behave identically.
     */
    /** @test */
    public function option_retrieval_matches_l5_behavior(): void
    {
        // Test 1: Get existing option
        Option::create([
            'name' => 'company_name',
            'value' => 'FreeScout LLC',
        ]);

        $value = Option::getValue('company_name');
        $this->assertEquals('FreeScout LLC', $value);

        // Test 2: Get non-existent option with default
        $value = Option::getValue('non_existent', 'default_value');
        $this->assertEquals('default_value', $value);

        // Test 3: Get non-existent option without default (should be null)
        $value = Option::getValue('another_non_existent');
        $this->assertNull($value);
    }

    /**
     * Regression Test: Verify option setting logic matches L5 implementation.
     * 
     * The L5 version used Option::set($name, $value).
     * The modern version uses Option::setValue($name, $value).
     * Both should create or update the option.
     */
    /** @test */
    public function option_setting_matches_l5_behavior(): void
    {
        // Test 1: Set a new option
        Option::setValue('new_option', 'new_value');

        $this->assertDatabaseHas('options', [
            'name' => 'new_option',
            'value' => 'new_value',
        ]);

        // Test 2: Update existing option
        Option::setValue('new_option', 'updated_value');

        $this->assertDatabaseHas('options', [
            'name' => 'new_option',
            'value' => 'updated_value',
        ]);

        // Verify only one record exists
        $this->assertEquals(1, Option::where('name', 'new_option')->count());
    }

    /**
     * Regression Test: Verify option deletion matches L5 implementation.
     * 
     * The L5 version used Option::remove($name).
     * The modern version uses Option::deleteOption($name).
     */
    /** @test */
    public function option_deletion_matches_l5_behavior(): void
    {
        // Arrange
        Option::create([
            'name' => 'temp_option',
            'value' => 'temp_value',
        ]);

        // Act
        Option::deleteOption('temp_option');

        // Assert
        $this->assertDatabaseMissing('options', [
            'name' => 'temp_option',
        ]);
    }

    /**
     * Regression Test: Verify settings are retrieved with same defaults as L5.
     * 
     * In L5, the helper function option('setting_key') was used.
     * In modern app, we use Option::getValue('setting_key', $default).
     * Both should return the same default values.
     */
    /** @test */
    public function settings_default_values_match_l5(): void
    {
        // Test default for non-existent company_name
        // L5 would return config('app.name') as default
        $appName = config('app.name');
        $value = Option::getValue('company_name', $appName);

        $this->assertEquals($appName, $value);
    }

    /**
     * Regression Test: Verify that option values can be updated via controller.
     * This ensures the entire flow from L5 settings page works the same way.
     */
    /** @test */
    public function settings_update_flow_matches_l5(): void
    {
        // Arrange
        $admin = \App\Models\User::factory()->create([
            'role' => \App\Models\User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin);

        // Act - Update settings like in L5
        $response = $this->post(route('settings.update'), [
            'company_name' => 'L5 Compatible Name',
            'next_ticket' => 1000,
        ]);

        // Assert - Settings should be stored in options table
        $this->assertDatabaseHas('options', [
            'name' => 'company_name',
            'value' => 'L5 Compatible Name',
        ]);

        $this->assertDatabaseHas('options', [
            'name' => 'next_ticket',
            'value' => '1000',
        ]);

        // Verify retrieval works
        $companyName = Option::getValue('company_name');
        $this->assertEquals('L5 Compatible Name', $companyName);
    }

    /**
     * Regression Test: Verify boolean values are handled correctly.
     * 
     * In L5, boolean options were stored as integers (0 or 1).
     * The modern implementation should maintain this compatibility.
     */
    /** @test */
    public function boolean_options_stored_as_integers_like_l5(): void
    {
        // Arrange
        $admin = \App\Models\User::factory()->create([
            'role' => \App\Models\User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin);

        // Act - Update boolean setting
        $response = $this->post(route('settings.update'), [
            'email_branding' => true,
            'open_tracking' => false,
        ]);

        // Assert - Booleans should be stored as 1 and 0
        $this->assertDatabaseHas('options', [
            'name' => 'email_branding',
            'value' => '1',
        ]);

        $this->assertDatabaseHas('options', [
            'name' => 'open_tracking',
            'value' => '0',
        ]);
    }

    /**
     * Regression Test: Verify updateOrCreate behavior matches L5.
     * 
     * In L5, Option::set() used firstOrCreate() + save().
     * In modern, Option::setValue() uses updateOrCreate().
     * Both should result in the same database state.
     */
    /** @test */
    public function update_or_create_behavior_matches_l5(): void
    {
        // Test 1: Create new option
        Option::setValue('test_option', 'initial_value');

        $this->assertEquals(1, Option::where('name', 'test_option')->count());
        $this->assertEquals('initial_value', Option::getValue('test_option'));

        // Test 2: Update existing option
        Option::setValue('test_option', 'updated_value');

        // Still should be only one record
        $this->assertEquals(1, Option::where('name', 'test_option')->count());
        $this->assertEquals('updated_value', Option::getValue('test_option'));
    }
}
