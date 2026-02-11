<?php

use App\Models\Option;
use Illuminate\Foundation\Testing\RefreshDatabase;

test('option handles null values correctly', function () {
    Option::setValue('nullable_option', null);

    $value = Option::getValue('nullable_option');
    expect($value)->toBeNull();
});

test('option handles empty string values', function () {
    Option::setValue('empty_option', '');

    $value = Option::getValue('empty_option');
    expect($value)->toBe('');
});

test('option handles numeric values', function () {
    Option::setValue('numeric_option', 12345);

    $value = Option::getValue('numeric_option');
    expect($value)->toEqual(12345);
});

test('option handles array values', function () {
    $arrayValue = ['key1' => 'value1', 'key2' => 'value2'];
    Option::setValue('array_option', json_encode($arrayValue));

    $value = Option::getValue('array_option');
    expect($value)->toBeString();
    expect(json_decode($value, true))->toBe($arrayValue);
});

test('option set value creates new record when not exists', function () {
    $this->assertDatabaseMissing('options', ['name' => 'new_test_option']);

    Option::setValue('new_test_option', 'new_value');

    $this->assertDatabaseHas('options', [
        'name' => 'new_test_option',
        'value' => 'new_value',
    ]);
});

test('option set value updates existing record', function () {
    Option::create(['name' => 'existing_option', 'value' => 'old_value']);

    Option::setValue('existing_option', 'new_value');

    $this->assertDatabaseHas('options', [
        'name' => 'existing_option',
        'value' => 'new_value',
    ]);

    // Ensure only one record exists
    expect(Option::where('name', 'existing_option')->count())->toBe(1);
});

test('option delete option handles non existent keys gracefully', function () {
    $result = Option::deleteOption('non_existent_key');

    expect($result)->toBeFalse();
});

test('multiple options can be stored and retrieved', function () {
    $options = [
        'option1' => 'value1',
        'option2' => 'value2',
        'option3' => 'value3',
    ];

    foreach ($options as $name => $value) {
        Option::setValue($name, $value);
    }

    foreach ($options as $name => $value) {
        expect(Option::getValue($name))->toBe($value);
    }
});

test('option get value returns default when not found', function () {
    $value = Option::getValue('non_existent', 'default_value');
    expect($value)->toBe('default_value');

    $value = Option::getValue('another_non_existent');
    expect($value)->toBeNull();
});

test('option delete removes existing option', function () {
    Option::create(['name' => 'temp_option', 'value' => 'temp_value']);

    Option::deleteOption('temp_option');

    $this->assertDatabaseMissing('options', [
        'name' => 'temp_option',
    ]);
});

test('option update or create behavior', function () {
    // Test 1: Create new option
    Option::setValue('test_option_uoc', 'initial_value');

    expect(Option::where('name', 'test_option_uoc')->count())->toBe(1);
    expect(Option::getValue('test_option_uoc'))->toBe('initial_value');

    // Test 2: Update existing option
    Option::setValue('test_option_uoc', 'updated_value');

    // Still should be only one record
    expect(Option::where('name', 'test_option_uoc')->count())->toBe(1);
    expect(Option::getValue('test_option_uoc'))->toBe('updated_value');
});
