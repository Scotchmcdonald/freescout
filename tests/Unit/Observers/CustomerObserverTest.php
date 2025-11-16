<?php

declare(strict_types=1);

namespace Tests\Unit\Observers;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerObserverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_deletes_conversations_when_customer_deleted()
    {
        $customer = Customer::factory()->create();
        $conversation1 = Conversation::factory()->create(['customer_id' => $customer->id]);
        $conversation2 = Conversation::factory()->create(['customer_id' => $customer->id]);

        $customer->delete();

        $this->assertDatabaseMissing('conversations', ['id' => $conversation1->id]);
        $this->assertDatabaseMissing('conversations', ['id' => $conversation2->id]);
    }

    /** @test */
    public function it_deletes_email_records_when_customer_deleted()
    {
        $customer = Customer::factory()->create();
        $email1 = Email::factory()->create(['customer_id' => $customer->id]);
        $email2 = Email::factory()->create(['customer_id' => $customer->id]);

        $customer->delete();

        $this->assertDatabaseMissing('emails', ['id' => $email1->id]);
        $this->assertDatabaseMissing('emails', ['id' => $email2->id]);
    }

    /** @test */
    public function it_deletes_both_conversations_and_emails_when_customer_deleted()
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->create(['customer_id' => $customer->id]);
        $email = Email::factory()->create(['customer_id' => $customer->id]);

        $customer->delete();

        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('emails', ['id' => $email->id]);
    }

    /** @test */
    public function it_only_deletes_customer_own_conversations()
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        $conversation1 = Conversation::factory()->create(['customer_id' => $customer1->id]);
        $conversation2 = Conversation::factory()->create(['customer_id' => $customer2->id]);

        $customer1->delete();

        $this->assertDatabaseMissing('conversations', ['id' => $conversation1->id]);
        $this->assertDatabaseHas('conversations', ['id' => $conversation2->id]);
    }

    /** @test */
    public function it_only_deletes_customer_own_emails()
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        $email1 = Email::factory()->create(['customer_id' => $customer1->id]);
        $email2 = Email::factory()->create(['customer_id' => $customer2->id]);

        $customer1->delete();

        $this->assertDatabaseMissing('emails', ['id' => $email1->id]);
        $this->assertDatabaseHas('emails', ['id' => $email2->id]);
    }
}
