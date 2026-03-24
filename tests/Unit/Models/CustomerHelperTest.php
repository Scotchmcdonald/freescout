<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Customer;
use Tests\PureUnitTestCase;

final class TestCustomerHelper extends Customer
{
    protected function casts(): array
    {
        return [];
    }
}

class CustomerHelperTest extends PureUnitTestCase
{
    private function customer(array $attrs = []): TestCustomerHelper
    {
        $c = new TestCustomerHelper;
        foreach ($attrs as $key => $value) {
            $c->{$key} = $value;
        }

        return $c;
    }

    // -------------------------------------------------------------------------
    // getFullName
    // -------------------------------------------------------------------------

    public function test_get_full_name_combines_first_and_last_name(): void
    {
        $this->assertSame('Jane Doe', $this->customer(['first_name' => 'Jane', 'last_name' => 'Doe'])->getFullName());
    }

    public function test_get_full_name_trims_when_last_name_empty(): void
    {
        $this->assertSame('Jane', $this->customer(['first_name' => 'Jane', 'last_name' => ''])->getFullName());
    }

    public function test_get_full_name_trims_when_first_name_empty(): void
    {
        $this->assertSame('Doe', $this->customer(['first_name' => '', 'last_name' => 'Doe'])->getFullName());
    }

    public function test_get_full_name_returns_empty_string_when_both_names_empty(): void
    {
        $this->assertSame('', $this->customer(['first_name' => '', 'last_name' => ''])->getFullName());
    }

    // -------------------------------------------------------------------------
    // getFirstName
    // -------------------------------------------------------------------------

    public function test_get_first_name_returns_raw_first_name_without_ucfirst(): void
    {
        $this->assertSame('jane', $this->customer(['first_name' => 'jane'])->getFirstName());
    }

    public function test_get_first_name_with_ucfirst_capitalizes_first_letter(): void
    {
        $this->assertSame('Jane', $this->customer(['first_name' => 'jane'])->getFirstName(true));
    }

    public function test_get_first_name_returns_empty_string_when_first_name_is_null(): void
    {
        $this->assertSame('', $this->customer(['first_name' => null])->getFirstName());
    }

    public function test_get_first_name_with_ucfirst_on_empty_string_stays_empty(): void
    {
        $this->assertSame('', $this->customer(['first_name' => ''])->getFirstName(true));
    }
}
