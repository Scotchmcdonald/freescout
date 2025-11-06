<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Customer;
use Tests\TestCase;

class CustomerModelTest extends TestCase
{
    public function test_customer_has_first_name_attribute(): void
    {
        $customer = new Customer(['first_name' => 'John']);
        
        $this->assertEquals('John', $customer->first_name);
    }

    public function test_customer_has_last_name_attribute(): void
    {
        $customer = new Customer(['last_name' => 'Doe']);
        
        $this->assertEquals('Doe', $customer->last_name);
    }

    public function test_customer_has_company_attribute(): void
    {
        $customer = new Customer(['company' => 'Acme Corp']);
        
        $this->assertEquals('Acme Corp', $customer->company);
    }

    public function test_customer_has_phones_attribute(): void
    {
        $customer = new Customer(['phones' => ['555-1234']]);
        
        $this->assertEquals(['555-1234'], $customer->phones);
    }

    public function test_customer_get_full_name_with_both_names(): void
    {
        $customer = new Customer([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        
        $this->assertEquals('John Doe', $customer->getFullName());
    }

    public function test_customer_get_full_name_with_only_first_name(): void
    {
        $customer = new Customer([
            'first_name' => 'John',
            'last_name' => '',
        ]);
        
        $this->assertEquals('John', $customer->getFullName());
    }

    public function test_customer_get_full_name_with_only_last_name(): void
    {
        $customer = new Customer([
            'first_name' => '',
            'last_name' => 'Doe',
        ]);
        
        $this->assertEquals('Doe', $customer->getFullName());
    }

    public function test_customer_has_notes_attribute(): void
    {
        $customer = new Customer(['notes' => 'Important customer']);
        
        $this->assertEquals('Important customer', $customer->notes);
    }

    public function test_customer_has_address_attribute(): void
    {
        $customer = new Customer(['address' => '123 Main St']);
        
        $this->assertEquals('123 Main St', $customer->address);
    }

    public function test_customer_has_city_attribute(): void
    {
        $customer = new Customer(['city' => 'Springfield']);
        
        $this->assertEquals('Springfield', $customer->city);
    }
}
