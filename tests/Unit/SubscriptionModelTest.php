<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Subscription;
use Tests\TestCase;

class SubscriptionModelTest extends TestCase
{
    public function test_model_can_be_instantiated(): void
    {
        $subscription = new Subscription();
        $this->assertInstanceOf(Subscription::class, $subscription);
    }

    public function test_model_has_fillable_attributes(): void
    {
        $subscription = new Subscription([
            'user_id' => 1,
            'medium' => 1,
            'event' => 2,
        ]);

        $this->assertEquals(1, $subscription->user_id);
        $this->assertEquals(1, $subscription->medium);
        $this->assertEquals(2, $subscription->event);
    }
}
