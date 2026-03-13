<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Channel;
use App\Models\Customer;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for Channel Model
 * Following TESTING_GUIDE.md - using test_ prefix, UnitTestCase base class
 */
class ChannelTest extends UnitTestCase
{
    // ===== MODEL CREATION TESTS =====

    public function test_channel_can_be_created(): void
    {
        $channel = Channel::factory()->create([
            'name' => 'Test Channel',
            'type' => 1,
            'active' => true,
        ]);

        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertDatabaseHas('channels', [
            'id' => $channel->id,
            'name' => 'Test Channel',
        ]);
    }

    public function test_channel_has_correct_fillable_attributes(): void
    {
        $channel = new Channel;

        $this->assertContains('name', $channel->getFillable());
        $this->assertContains('type', $channel->getFillable());
        $this->assertContains('settings', $channel->getFillable());
        $this->assertContains('active', $channel->getFillable());
    }

    public function test_channel_uses_has_factory_trait(): void
    {
        $channel = Channel::factory()->create();

        $this->assertInstanceOf(Channel::class, $channel);
    }

    // ===== CAST TESTS =====

    public function test_type_is_cast_to_integer(): void
    {
        $channel = Channel::factory()->create(['type' => '1']);

        $this->assertIsInt($channel->type);
    }

    public function test_settings_are_cast_to_json(): void
    {
        $settings = ['key' => 'value', 'enabled' => true];
        $channel = Channel::factory()->create(['settings' => $settings]);

        $this->assertEquals($settings, $channel->settings);
        $this->assertIsArray($channel->settings);
    }

    public function test_active_is_cast_to_boolean(): void
    {
        $channel = Channel::factory()->create(['active' => 1]);

        $this->assertIsBool($channel->active);
        $this->assertTrue($channel->active);
    }

    public function test_created_at_is_cast_to_datetime(): void
    {
        $channel = Channel::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $channel->created_at);
    }

    public function test_updated_at_is_cast_to_datetime(): void
    {
        $channel = Channel::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $channel->updated_at);
    }

    // ===== RELATIONSHIP TESTS =====

    public function test_channel_belongs_to_many_customers(): void
    {
        $channel = Channel::factory()->create();
        $customer = Customer::factory()->create();

        $channel->customers()->attach($customer->id);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $channel->customers);
        $this->assertCount(1, $channel->customers);
        $this->assertEquals($customer->id, $channel->customers->first()->id);
    }

    public function test_channel_can_have_multiple_customers(): void
    {
        $channel = Channel::factory()->create();
        $customers = Customer::factory()->count(3)->create();

        foreach ($customers as $customer) {
            $channel->customers()->attach($customer->id);
        }

        $this->assertCount(3, $channel->fresh()->customers);
    }

    public function test_channel_customer_pivot_has_timestamps(): void
    {
        $channel = Channel::factory()->create();
        $customer = Customer::factory()->create();

        $channel->customers()->attach($customer->id);

        $pivot = $channel->customers()->first()->pivot;
        $this->assertNotNull($pivot->created_at);
        $this->assertNotNull($pivot->updated_at);
    }

    // ===== IS_ACTIVE METHOD TESTS =====

    public function test_is_active_returns_true_when_channel_is_active(): void
    {
        $channel = Channel::factory()->create(['active' => true]);

        $this->assertTrue($channel->isActive());
    }

    public function test_is_active_returns_false_when_channel_is_inactive(): void
    {
        $channel = Channel::factory()->create(['active' => false]);

        $this->assertFalse($channel->isActive());
    }

    // ===== ATTRIBUTE TESTS =====

    public function test_channel_has_name_attribute(): void
    {
        $channel = Channel::factory()->create(['name' => 'Email Channel']);

        $this->assertEquals('Email Channel', $channel->name);
    }

    public function test_channel_has_type_attribute(): void
    {
        $channel = Channel::factory()->create(['type' => 5]);

        $this->assertEquals(5, $channel->type);
    }

    // ===== QUERY TESTS =====

    public function test_can_query_active_channels(): void
    {
        Channel::factory()->count(3)->create(['active' => true]);
        Channel::factory()->count(2)->create(['active' => false]);

        $activeChannels = Channel::where('active', true)->get();

        $this->assertCount(3, $activeChannels);
    }

    public function test_can_query_channels_by_type(): void
    {
        Channel::factory()->count(2)->create(['type' => 1]);
        Channel::factory()->create(['type' => 2]);

        $channels = Channel::where('type', 1)->get();

        $this->assertCount(2, $channels);
    }

    public function test_can_query_channels_by_name(): void
    {
        Channel::factory()->create(['name' => 'Channel One']);
        Channel::factory()->create(['name' => 'Channel Two']);

        $channel = Channel::where('name', 'Channel One')->first();

        $this->assertNotNull($channel);
        $this->assertEquals('Channel One', $channel->name);
    }

    // ===== EDGE CASES =====

    public function test_channel_with_null_settings(): void
    {
        $channel = Channel::factory()->create(['settings' => null]);

        $this->assertNull($channel->settings);
    }

    public function test_channel_with_empty_settings_array(): void
    {
        $channel = Channel::factory()->create(['settings' => []]);

        $this->assertEquals([], $channel->settings);
    }

    public function test_channel_with_complex_settings(): void
    {
        $settings = [
            'api_key' => 'secret',
            'webhook_url' => 'https://example.com/webhook',
            'options' => ['opt1' => true, 'opt2' => false],
        ];

        $channel = Channel::factory()->create(['settings' => $settings]);

        $this->assertEquals($settings, $channel->settings);
        $this->assertEquals('secret', $channel->settings['api_key']);
    }

    public function test_channel_can_be_updated(): void
    {
        $channel = Channel::factory()->create(['name' => 'Old Name']);

        $channel->update(['name' => 'New Name']);

        $this->assertEquals('New Name', $channel->fresh()->name);
    }

    public function test_channel_can_be_deleted(): void
    {
        $channel = Channel::factory()->create();
        $id = $channel->id;

        $channel->delete();

        $this->assertDatabaseMissing('channels', ['id' => $id]);
    }

    public function test_channel_timestamps_are_automatically_set(): void
    {
        $channel = Channel::factory()->create();

        $this->assertNotNull($channel->created_at);
        $this->assertNotNull($channel->updated_at);
    }

    public function test_customer_can_be_detached_from_channel(): void
    {
        $channel = Channel::factory()->create();
        $customer = Customer::factory()->create();

        $channel->customers()->attach($customer->id);
        $this->assertCount(1, $channel->fresh()->customers);

        $channel->customers()->detach($customer->id);
        $this->assertCount(0, $channel->fresh()->customers);
    }

    public function test_channel_with_no_customers(): void
    {
        $channel = Channel::factory()->create();

        $this->assertCount(0, $channel->customers);
    }

    public function test_multiple_channels_can_be_created(): void
    {
        Channel::factory()->count(5)->create();

        $this->assertCount(5, Channel::all());
    }

    public function test_channel_with_special_characters_in_name(): void
    {
        $channel = Channel::factory()->create(['name' => 'Channel & Special "Chars"']);

        $this->assertEquals('Channel & Special "Chars"', $channel->name);
    }

    public function test_channel_with_long_name(): void
    {
        $longName = str_repeat('Channel Name ', 20);
        $channel = Channel::factory()->create(['name' => $longName]);

        $this->assertEquals($longName, $channel->name);
    }

    public function test_channel_active_status_can_be_toggled(): void
    {
        $channel = Channel::factory()->create(['active' => true]);

        $channel->update(['active' => false]);
        $this->assertFalse($channel->fresh()->isActive());

        $channel->update(['active' => true]);
        $this->assertTrue($channel->fresh()->isActive());
    }

    public function test_channel_type_can_be_changed(): void
    {
        $channel = Channel::factory()->create(['type' => 1]);

        $channel->update(['type' => 2]);

        $this->assertEquals(2, $channel->fresh()->type);
    }

    public function test_can_sync_customers_to_channel(): void
    {
        $channel = Channel::factory()->create();
        $customers = Customer::factory()->count(3)->create();

        $channel->customers()->sync($customers->pluck('id')->toArray());

        $this->assertCount(3, $channel->fresh()->customers);
    }

    public function test_customer_can_belong_to_multiple_channels(): void
    {
        $customer = Customer::factory()->create();
        $channels = Channel::factory()->count(3)->create();

        foreach ($channels as $channel) {
            $channel->customers()->attach($customer->id);
        }

        $this->assertCount(3, $customer->fresh()->channels);
    }

    public function test_channel_with_zero_type(): void
    {
        $channel = Channel::factory()->create(['type' => 0]);

        $this->assertEquals(0, $channel->type);
    }

    public function test_channel_with_negative_type(): void
    {
        $channel = Channel::factory()->create(['type' => -1]);

        $this->assertEquals(-1, $channel->type);
    }

    public function test_inactive_channels_can_be_queried(): void
    {
        Channel::factory()->count(2)->create(['active' => true]);
        Channel::factory()->count(3)->create(['active' => false]);

        $inactiveChannels = Channel::where('active', false)->get();

        $this->assertCount(3, $inactiveChannels);
    }

    // ===== BASIC CHANNEL MODEL TESTS (Merged from ChannelModelTest.php) =====

    public function test_channel_model_can_be_instantiated(): void
    {
        $channel = new Channel;
        $this->assertInstanceOf(Channel::class, $channel);
    }

    public function test_channel_model_has_fillable_attributes(): void
    {
        $channel = new Channel([
            'type' => 1,
            'name' => 'Support Email',
            'settings' => ['address' => 'support@example.com'],
        ]);

        $this->assertEquals(1, $channel->type);
        $this->assertEquals('Support Email', $channel->name);
        $this->assertEquals(['address' => 'support@example.com'], $channel->settings);
    }
}
