<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use Tests\UnitTestCase;

class CustomerObserverTest extends UnitTestCase
{

    public function test_deleting_removes_conversations(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);

        $customer->delete();

        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
    }

    public function test_deleting_removes_emails(): void
    {
        $customer = Customer::factory()->create();
        $email = Email::factory()->create(['customer_id' => $customer->id]);

        $customer->delete();

        $this->assertDatabaseMissing('emails', ['id' => $email->id]);
    }
}
