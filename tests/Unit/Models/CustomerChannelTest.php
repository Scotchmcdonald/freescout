<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\CustomerChannel;
use PHPUnit\Framework\Attributes\Test;
use Tests\UnitTestCase;

class CustomerChannelTest extends UnitTestCase
{

    #[Test]
    public function customer_channel_can_be_created(): void
    {
        $customer = Customer::factory()->create();
        $channel = CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_EMAIL,
            'channel_id' => 'test@example.com',
        ]);

        $this->assertInstanceOf(CustomerChannel::class, $channel);
        $this->assertEquals($customer->id, $channel->customer_id);
        $this->assertEquals(CustomerChannel::CHANNEL_EMAIL, $channel->channel);
        $this->assertEquals('test@example.com', $channel->channel_id);
    }

    #[Test]
    public function customer_channel_uses_correct_table(): void
    {
        $channel = new CustomerChannel();

        $this->assertEquals('customer_channel', $channel->getTable());
    }

    #[Test]
    public function customer_channel_has_fillable_attributes(): void
    {
        $channel = new CustomerChannel();

        $this->assertEquals(['customer_id', 'channel', 'channel_id'], $channel->getFillable());
    }

    #[Test]
    public function customer_channel_has_email_constant(): void
    {
        $this->assertEquals(1, CustomerChannel::CHANNEL_EMAIL);
    }

    #[Test]
    public function customer_channel_has_phone_constant(): void
    {
        $this->assertEquals(2, CustomerChannel::CHANNEL_PHONE);
    }

    #[Test]
    public function customer_channel_has_chat_constant(): void
    {
        $this->assertEquals(3, CustomerChannel::CHANNEL_CHAT);
    }

    #[Test]
    public function customer_channel_casts_customer_id_to_integer(): void
    {
        $customer = Customer::factory()->create();
        $channel = CustomerChannel::create([
            'customer_id' => (string) $customer->id,
            'channel' => '1',
            'channel_id' => 'test@example.com',
        ]);

        $this->assertIsInt($channel->customer_id);
    }

    #[Test]
    public function customer_channel_casts_channel_to_integer(): void
    {
        $customer = Customer::factory()->create();
        $channel = CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => '2',
            'channel_id' => '+1234567890',
        ]);

        $this->assertIsInt($channel->channel);
        $this->assertEquals(2, $channel->channel);
    }

    #[Test]
    public function customer_channel_casts_timestamps(): void
    {
        $customer = Customer::factory()->create();
        $channel = CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_EMAIL,
            'channel_id' => 'test@example.com',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $channel->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $channel->updated_at);
    }

    #[Test]
    public function customer_channel_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        $channel = CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_EMAIL,
            'channel_id' => 'john@example.com',
        ]);

        $this->assertInstanceOf(Customer::class, $channel->customer);
        $this->assertEquals('John', $channel->customer->first_name);
        $this->assertEquals('Doe', $channel->customer->last_name);
    }

    #[Test]
    public function customer_channel_relationship_is_belongs_to(): void
    {
        $channel = new CustomerChannel();

        $relation = $channel->customer();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $relation);
    }

    #[Test]
    public function customer_channel_can_be_updated(): void
    {
        $customer = Customer::factory()->create();
        $channel = CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_EMAIL,
            'channel_id' => 'old@example.com',
        ]);

        $channel->update(['channel_id' => 'new@example.com']);

        $this->assertEquals('new@example.com', $channel->fresh()->channel_id);
    }

    #[Test]
    public function customer_channel_can_be_deleted(): void
    {
        $customer = Customer::factory()->create();
        $channel = CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_EMAIL,
            'channel_id' => 'test@example.com',
        ]);

        $id = $channel->id;
        $channel->delete();

        $this->assertNull(CustomerChannel::find($id));
    }

    #[Test]
    public function customer_can_have_multiple_channels(): void
    {
        $customer = Customer::factory()->create();

        CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_EMAIL,
            'channel_id' => 'test@example.com',
        ]);

        CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_PHONE,
            'channel_id' => '+1234567890',
        ]);

        CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_CHAT,
            'channel_id' => 'user123',
        ]);

        $channels = CustomerChannel::where('customer_id', $customer->id)->get();

        $this->assertCount(3, $channels);
    }

    #[Test]
    public function customer_channel_can_store_email_channel(): void
    {
        $customer = Customer::factory()->create();
        $channel = CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_EMAIL,
            'channel_id' => 'customer@example.com',
        ]);

        $this->assertEquals(CustomerChannel::CHANNEL_EMAIL, $channel->channel);
        $this->assertEquals('customer@example.com', $channel->channel_id);
    }

    #[Test]
    public function customer_channel_can_store_phone_channel(): void
    {
        $customer = Customer::factory()->create();
        $channel = CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_PHONE,
            'channel_id' => '+1234567890',
        ]);

        $this->assertEquals(CustomerChannel::CHANNEL_PHONE, $channel->channel);
        $this->assertEquals('+1234567890', $channel->channel_id);
    }

    #[Test]
    public function customer_channel_can_store_chat_channel(): void
    {
        $customer = Customer::factory()->create();
        $channel = CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_CHAT,
            'channel_id' => 'chat_user_123',
        ]);

        $this->assertEquals(CustomerChannel::CHANNEL_CHAT, $channel->channel);
        $this->assertEquals('chat_user_123', $channel->channel_id);
    }

    #[Test]
    public function customer_channel_records_timestamps(): void
    {
        $customer = Customer::factory()->create();
        $before = now()->subSecond();
        
        $channel = CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_EMAIL,
            'channel_id' => 'test@example.com',
        ]);
        
        $after = now()->addSecond();

        $this->assertTrue($channel->created_at->between($before, $after));
        $this->assertTrue($channel->updated_at->between($before, $after));
    }

    #[Test]
    public function customer_channel_can_be_queried_by_channel_type(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        CustomerChannel::create([
            'customer_id' => $customer1->id,
            'channel' => CustomerChannel::CHANNEL_EMAIL,
            'channel_id' => 'email1@example.com',
        ]);

        CustomerChannel::create([
            'customer_id' => $customer2->id,
            'channel' => CustomerChannel::CHANNEL_EMAIL,
            'channel_id' => 'email2@example.com',
        ]);

        CustomerChannel::create([
            'customer_id' => $customer1->id,
            'channel' => CustomerChannel::CHANNEL_PHONE,
            'channel_id' => '+1234567890',
        ]);

        $emailChannels = CustomerChannel::where('channel', CustomerChannel::CHANNEL_EMAIL)->get();

        $this->assertCount(2, $emailChannels);
    }

    #[Test]
    public function customer_channel_can_be_queried_by_channel_id(): void
    {
        $customer = Customer::factory()->create();
        CustomerChannel::create([
            'customer_id' => $customer->id,
            'channel' => CustomerChannel::CHANNEL_EMAIL,
            'channel_id' => 'specific@example.com',
        ]);

        $channel = CustomerChannel::where('channel_id', 'specific@example.com')->first();

        $this->assertNotNull($channel);
        $this->assertEquals('specific@example.com', $channel->channel_id);
    }
}
