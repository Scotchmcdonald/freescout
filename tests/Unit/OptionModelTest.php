<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Option;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OptionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_can_be_instantiated(): void
    {
        $option = new Option();
        $this->assertInstanceOf(Option::class, $option);
    }

    public function test_model_has_fillable_attributes(): void
    {
        $option = new Option([
            'name' => 'company_name',
            'value' => 'Freescout',
        ]);

        $this->assertEquals('company_name', $option->name);
        $this->assertEquals('Freescout', $option->value);
    }

    #[Test]
    public function option_can_store_key_value_pairs(): void
    {
        $option = Option::create([
            'name' => 'test_key',
            'value' => 'test_value',
        ]);

        $this->assertEquals('test_key', $option->name);
        $this->assertEquals('test_value', $option->value);
    }

    #[Test]
    public function option_can_retrieve_value_by_name(): void
    {
        Option::create([
            'name' => 'company_name',
            'value' => 'Acme Corp',
        ]);

        $value = Option::getValue('company_name');

        $this->assertEquals('Acme Corp', $value);
    }

    #[Test]
    public function option_returns_default_when_key_not_found(): void
    {
        $value = Option::getValue('non_existent_key', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    #[Test]
    public function option_can_set_value_by_name(): void
    {
        Option::setValue('app_name', 'FreeScout');

        $this->assertDatabaseHas('options', [
            'name' => 'app_name',
            'value' => 'FreeScout',
        ]);
    }

    #[Test]
    public function option_can_update_existing_value(): void
    {
        Option::create([
            'name' => 'company_name',
            'value' => 'Old Name',
        ]);

        Option::setValue('company_name', 'New Name');

        $this->assertDatabaseHas('options', [
            'name' => 'company_name',
            'value' => 'New Name',
        ]);

        // Ensure only one record exists
        $this->assertEquals(1, Option::where('name', 'company_name')->count());
    }

    #[Test]
    public function option_can_delete_by_name(): void
    {
        Option::create([
            'name' => 'temp_option',
            'value' => 'temp_value',
        ]);

        $deleted = Option::deleteOption('temp_option');

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('options', [
            'name' => 'temp_option',
        ]);
    }

    #[Test]
    public function option_delete_returns_false_when_key_not_found(): void
    {
        $deleted = Option::deleteOption('non_existent_key');

        $this->assertFalse($deleted);
    }
}
