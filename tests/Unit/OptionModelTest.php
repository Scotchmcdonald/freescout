<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Option;
use Tests\PureUnitTestCase;

class OptionModelTest extends PureUnitTestCase
{
    public function test_model_can_be_instantiated(): void
    {
        $option = new Option;
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
}
