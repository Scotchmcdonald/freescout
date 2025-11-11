<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubscriptionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_can_be_instantiated(): void
    {
        $subscription = new Subscription;
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

    public function test_is_email_returns_true_for_email_medium(): void
    {
        $subscription = Subscription::factory()->make(['medium' => 1]);

        $this->assertTrue($subscription->isEmail());
    }

    public function test_is_email_returns_false_for_non_email_medium(): void
    {
        $subscription = Subscription::factory()->make(['medium' => 2]);
        $this->assertFalse($subscription->isEmail());

        $subscription = Subscription::factory()->make(['medium' => 3]);
        $this->assertFalse($subscription->isEmail());
    }

    public function test_is_browser_returns_true_for_browser_medium(): void
    {
        $subscription = Subscription::factory()->make(['medium' => 2]);

        $this->assertTrue($subscription->isBrowser());
    }

    public function test_is_browser_returns_false_for_non_browser_medium(): void
    {
        $subscription = Subscription::factory()->make(['medium' => 1]);
        $this->assertFalse($subscription->isBrowser());

        $subscription = Subscription::factory()->make(['medium' => 3]);
        $this->assertFalse($subscription->isBrowser());
    }

    public function test_is_mobile_returns_true_for_mobile_medium(): void
    {
        $subscription = Subscription::factory()->make(['medium' => 3]);

        $this->assertTrue($subscription->isMobile());
    }

    public function test_is_mobile_returns_false_for_non_mobile_medium(): void
    {
        $subscription = Subscription::factory()->make(['medium' => 1]);
        $this->assertFalse($subscription->isMobile());

        $subscription = Subscription::factory()->make(['medium' => 2]);
        $this->assertFalse($subscription->isMobile());
    }

    public function test_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $subscription = Subscription::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $subscription->user);
        $this->assertEquals($user->id, $subscription->user->id);
    }

    public function test_medium_and_event_cast_to_integer()
    {
        // Disable events to prevent UserObserver from creating default subscriptions.
        Event::fake();

        $user = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'medium' => '1', // Stored as string
            'event' => '2',  // Stored as string
        ]);

        $subscription->refresh();

        // Assert that the attributes are cast to integers when retrieved.
        $this->assertIsInt($subscription->medium);
        $this->assertIsInt($subscription->event);
    }

    #[Test]
    public function test_multiple_subscriptions_for_same_user()
    {
        // Disable events to prevent UserObserver from creating default subscriptions.
        Event::fake();

        $user = User::factory()->create();

        Subscription::factory()->create([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_EMAIL,
            'event' => Subscription::EVENT_NEW_CONVERSATION,
        ]);

        Subscription::factory()->create([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_BROWSER,
            'event' => Subscription::EVENT_NEW_CONVERSATION,
        ]);

        $this->assertCount(2, $user->subscriptions);
    }

    public function test_created_at_and_updated_at_timestamps(): void
    {
        $subscription = Subscription::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $subscription->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $subscription->updated_at);
    }

    public function test_subscription_constants_are_defined(): void
    {
        // Test medium constants
        $this->assertEquals(1, Subscription::MEDIUM_EMAIL);
        $this->assertEquals(2, Subscription::MEDIUM_BROWSER);
        $this->assertEquals(3, Subscription::MEDIUM_MOBILE);

        // Test event constants - general
        $this->assertEquals(1, Subscription::EVENT_NEW_CONVERSATION);
        $this->assertEquals(2, Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME);
        $this->assertEquals(6, Subscription::EVENT_CONVERSATION_ASSIGNED);
        $this->assertEquals(13, Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED);

        // Test event constants - customer replies
        $this->assertEquals(3, Subscription::EVENT_CUSTOMER_REPLIED_TO_MY);
        $this->assertEquals(4, Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED);
        $this->assertEquals(7, Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED);

        // Test event constants - user replies
        $this->assertEquals(5, Subscription::EVENT_USER_REPLIED_TO_MY);
        $this->assertEquals(8, Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED);
        $this->assertEquals(9, Subscription::EVENT_USER_REPLIED_TO_ASSIGNED);
    }
}
