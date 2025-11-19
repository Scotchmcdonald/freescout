<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Option;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for Option Model
 * Following TESTING_GUIDE.md - using test_ prefix, UnitTestCase base class
 */
class OptionTest extends UnitTestCase
{
    // ===== MODEL CREATION TESTS =====

    public function test_option_can_be_created(): void
    {
        $option = Option::factory()->create([
            'name' => 'test_option',
            'value' => 'test_value',
        ]);
        
        $this->assertInstanceOf(Option::class, $option);
        $this->assertDatabaseHas('options', [
            'name' => 'test_option',
            'value' => 'test_value',
        ]);
    }

    public function test_option_has_correct_fillable_attributes(): void
    {
        $option = new Option();
        
        $this->assertContains('name', $option->getFillable());
        $this->assertContains('value', $option->getFillable());
    }

    public function test_option_uses_has_factory_trait(): void
    {
        $option = Option::factory()->create();
        
        $this->assertInstanceOf(Option::class, $option);
    }

    // ===== PRIMARY KEY TESTS =====

    public function test_option_primary_key_is_name(): void
    {
        $option = new Option();
        
        $this->assertEquals('name', $option->getKeyName());
    }

    public function test_option_key_type_is_string(): void
    {
        $option = new Option();
        
        $this->assertEquals('string', $option->getKeyType());
    }

    public function test_option_is_not_auto_incrementing(): void
    {
        $option = new Option();
        
        $this->assertFalse($option->getIncrementing());
    }

    public function test_option_can_be_found_by_name(): void
    {
        $option = Option::factory()->create(['name' => 'unique_option']);
        
        $found = Option::find('unique_option');
        
        $this->assertInstanceOf(Option::class, $found);
        $this->assertEquals('unique_option', $found->name);
    }

    // ===== GET_VALUE METHOD TESTS =====

    public function test_get_value_returns_option_value_when_exists(): void
    {
        Option::factory()->create([
            'name' => 'test_setting',
            'value' => 'test_value',
        ]);
        
        $value = Option::getValue('test_setting');
        
        $this->assertEquals('test_value', $value);
    }

    public function test_get_value_returns_default_when_option_not_exists(): void
    {
        $value = Option::getValue('non_existent_option', 'default_value');
        
        $this->assertEquals('default_value', $value);
    }

    public function test_get_value_returns_null_when_option_not_exists_and_no_default(): void
    {
        $value = Option::getValue('non_existent_option');
        
        $this->assertNull($value);
    }

    // ===== SET_VALUE METHOD TESTS =====

    public function test_set_value_creates_new_option(): void
    {
        Option::setValue('new_option', 'new_value');
        
        $this->assertDatabaseHas('options', [
            'name' => 'new_option',
            'value' => 'new_value',
        ]);
    }

    public function test_set_value_updates_existing_option(): void
    {
        Option::factory()->create([
            'name' => 'existing_option',
            'value' => 'old_value',
        ]);
        
        Option::setValue('existing_option', 'new_value');
        
        $this->assertDatabaseHas('options', [
            'name' => 'existing_option',
            'value' => 'new_value',
        ]);
    }

    // ===== CAST TESTS =====

    public function test_created_at_is_cast_to_datetime(): void
    {
        $option = Option::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $option->created_at);
    }

    public function test_updated_at_is_cast_to_datetime(): void
    {
        $option = Option::factory()->create();
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $option->updated_at);
    }

    // ===== QUERY TESTS =====

    public function test_can_query_multiple_options_by_name(): void
    {
        Option::factory()->create(['name' => 'option1']);
        Option::factory()->create(['name' => 'option2']);
        Option::factory()->create(['name' => 'option3']);
        
        $options = Option::whereIn('name', ['option1', 'option3'])->get();
        
        $this->assertCount(2, $options);
    }

    public function test_can_query_options_by_value(): void
    {
        Option::factory()->create(['name' => 'opt1', 'value' => 'same_value']);
        Option::factory()->create(['name' => 'opt2', 'value' => 'same_value']);
        Option::factory()->create(['name' => 'opt3', 'value' => 'different_value']);
        
        $options = Option::where('value', 'same_value')->get();
        
        $this->assertCount(2, $options);
    }

    public function test_update_or_create_creates_new_option(): void
    {
        $option = Option::updateOrCreate(
            ['name' => 'new_option'],
            ['value' => 'new_value']
        );
        
        $this->assertInstanceOf(Option::class, $option);
        $this->assertDatabaseHas('options', [
            'name' => 'new_option',
            'value' => 'new_value',
        ]);
    }

    public function test_update_or_create_updates_existing_option(): void
    {
        Option::factory()->create([
            'name' => 'existing_option',
            'value' => 'old_value',
        ]);
        
        Option::updateOrCreate(
            ['name' => 'existing_option'],
            ['value' => 'updated_value']
        );
        
        $this->assertDatabaseHas('options', [
            'name' => 'existing_option',
            'value' => 'updated_value',
        ]);
    }

    // ===== EDGE CASES =====

    public function test_option_with_empty_value(): void
    {
        $option = Option::factory()->create([
            'name' => 'empty_option',
            'value' => '',
        ]);
        
        $this->assertEquals('', $option->value);
    }

    public function test_option_with_null_value(): void
    {
        $option = Option::factory()->create([
            'name' => 'null_option',
            'value' => null,
        ]);
        
        $this->assertNull($option->value);
    }

    public function test_option_with_numeric_value(): void
    {
        $option = Option::factory()->create([
            'name' => 'numeric_option',
            'value' => '12345',
        ]);
        
        $this->assertEquals('12345', $option->value);
    }

    public function test_option_with_json_value(): void
    {
        $jsonValue = json_encode(['key' => 'value', 'array' => [1, 2, 3]]);
        $option = Option::factory()->create([
            'name' => 'json_option',
            'value' => $jsonValue,
        ]);
        
        $this->assertEquals($jsonValue, $option->value);
        $decoded = json_decode($option->value, true);
        $this->assertEquals('value', $decoded['key']);
    }

    public function test_option_with_long_value(): void
    {
        $longValue = str_repeat('a', 10000);
        $option = Option::factory()->create([
            'name' => 'long_option',
            'value' => $longValue,
        ]);
        
        $this->assertEquals($longValue, $option->value);
    }

    public function test_option_name_with_special_characters(): void
    {
        $option = Option::factory()->create([
            'name' => 'option_name.with-special_chars',
            'value' => 'test',
        ]);
        
        $this->assertEquals('option_name.with-special_chars', $option->name);
    }

    public function test_option_can_be_updated(): void
    {
        $option = Option::factory()->create([
            'name' => 'test_option',
            'value' => 'old_value',
        ]);
        
        $option->update(['value' => 'new_value']);
        
        $this->assertEquals('new_value', $option->fresh()->value);
    }

    public function test_option_can_be_deleted(): void
    {
        $option = Option::factory()->create(['name' => 'delete_option']);
        
        $option->delete();
        
        $this->assertDatabaseMissing('options', ['name' => 'delete_option']);
    }

    public function test_option_timestamps_are_automatically_set(): void
    {
        $option = Option::factory()->create();
        
        $this->assertNotNull($option->created_at);
        $this->assertNotNull($option->updated_at);
    }

    public function test_option_with_boolean_string_value(): void
    {
        $option = Option::factory()->create([
            'name' => 'bool_option',
            'value' => 'true',
        ]);
        
        $this->assertEquals('true', $option->value);
    }

    public function test_multiple_options_can_be_created(): void
    {
        Option::factory()->count(10)->create();
        
        $this->assertCount(10, Option::all());
    }

    public function test_get_value_with_zero_as_default(): void
    {
        $value = Option::getValue('non_existent', 0);
        
        $this->assertEquals(0, $value);
    }

    public function test_get_value_with_false_as_default(): void
    {
        $value = Option::getValue('non_existent', false);
        
        $this->assertFalse($value);
    }

    public function test_get_value_with_empty_string_as_default(): void
    {
        $value = Option::getValue('non_existent', '');
        
        $this->assertEquals('', $value);
    }

    public function test_set_value_with_array_value(): void
    {
        $arrayValue = ['key1' => 'value1', 'key2' => 'value2'];
        Option::setValue('array_option', json_encode($arrayValue));
        
        $option = Option::find('array_option');
        $this->assertEquals(json_encode($arrayValue), $option->value);
    }

    public function test_option_name_is_case_sensitive(): void
    {
        Option::factory()->create(['name' => 'CaseSensitive', 'value' => 'value1']);
        Option::factory()->create(['name' => 'casesensitive', 'value' => 'value2']);
        
        $this->assertCount(2, Option::all());
    }

    public function test_option_with_serialized_value(): void
    {
        $data = ['name' => 'test', 'count' => 5];
        $serialized = serialize($data);
        
        $option = Option::factory()->create([
            'name' => 'serialized_option',
            'value' => $serialized,
        ]);
        
        $this->assertEquals($serialized, $option->value);
        $unserialized = unserialize($option->value);
        $this->assertEquals($data, $unserialized);
    }

    public function test_get_all_options(): void
    {
        Option::factory()->count(5)->create();
        
        $options = Option::all();
        
        $this->assertCount(5, $options);
    }
}
