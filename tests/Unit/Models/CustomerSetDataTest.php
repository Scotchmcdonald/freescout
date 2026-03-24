<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Customer;
use Tests\PureUnitTestCase;

final class TestCustomer extends Customer
{
    public bool $saved = false;

    public function save(array $options = []): bool
    {
        $this->saved = true;

        return true;
    }
}

class CustomerSetDataTest extends PureUnitTestCase
{
    private function customer(array $attributes = []): TestCustomer
    {
        $customer = new TestCustomer;
        foreach ($attributes as $key => $value) {
            $customer->{$key} = $value;
        }

        return $customer;
    }

    public function test_set_data_replace_mode_strips_photo_url_ignores_array_values_and_uses_background_as_notes(): void
    {
        $customer = $this->customer();

        $result = $customer->setData([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'photo_url' => 'https://example.com/photo.jpg',
            'background' => 'Imported note',
            'phones' => ['123'],
        ], true, false);

        $this->assertTrue($result);
        $this->assertSame('Jane', $customer->first_name);
        $this->assertSame('Doe', $customer->last_name);
        $this->assertSame('Imported note', $customer->notes);
        $this->assertNull($customer->photo_url);
        $this->assertNull($customer->phones);
        $this->assertFalse($customer->saved);
    }

    public function test_set_data_non_replace_mode_only_fills_empty_fields(): void
    {
        $customer = $this->customer([
            'first_name' => 'Existing',
            'last_name' => '',
            'company' => '',
        ]);

        $result = $customer->setData([
            'first_name' => 'Ignored',
            'last_name' => 'Filled',
            'company' => 'Acme',
        ], false, false);

        $this->assertTrue($result);
        $this->assertSame('Existing', $customer->first_name);
        $this->assertSame('', $customer->last_name);
        $this->assertSame('Acme', $customer->company);
    }

    public function test_set_data_non_replace_mode_blocks_cross_filling_when_name_pair_already_partially_present(): void
    {
        $customer = $this->customer([
            'first_name' => 'Existing',
            'last_name' => 'Lastname',
            'notes' => '',
        ]);

        $result = $customer->setData([
            'first_name' => 'New',
            'last_name' => 'Ignored',
            'notes' => 'Keep this note',
        ], false, false);

        $this->assertTrue($result);
        $this->assertSame('Existing', $customer->first_name);
        $this->assertSame('Lastname', $customer->last_name);
        $this->assertSame('Keep this note', $customer->notes);
    }

    public function test_set_data_save_flag_only_saves_when_changes_were_made(): void
    {
        $changed = $this->customer();
        $unchanged = $this->customer(['first_name' => 'Jane']);

        $this->assertTrue($changed->setData(['first_name' => 'Jane'], false, true));
        $this->assertTrue($changed->saved);

        $this->assertFalse($unchanged->setData(['first_name' => 'Ignored'], false, true));
        $this->assertFalse($unchanged->saved);
    }
}
