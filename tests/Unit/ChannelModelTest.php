<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Channel;
use Tests\TestCase;

class ChannelModelTest extends TestCase
{
    public function test_model_can_be_instantiated(): void
    {
        $channel = new Channel;
        $this->assertInstanceOf(Channel::class, $channel);
    }

    public function test_model_has_fillable_attributes(): void
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
