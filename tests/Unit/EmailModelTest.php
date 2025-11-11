<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Email;
use Tests\TestCase;

class EmailModelTest extends TestCase
{
    public function test_email_has_email_attribute(): void
    {
        $email = new Email(['email' => 'test@example.com']);

        $this->assertEquals('test@example.com', $email->email);
    }

    public function test_email_has_customer_id_attribute(): void
    {
        $email = new Email(['customer_id' => 123]);

        $this->assertEquals(123, $email->customer_id);
    }

    public function test_email_can_set_all_attributes(): void
    {
        $email = new Email([
            'email' => 'work@example.com',
            'customer_id' => 456,
        ]);

        $this->assertEquals('work@example.com', $email->email);
        $this->assertEquals(456, $email->customer_id);
    }
}
