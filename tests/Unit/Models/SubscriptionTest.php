<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Subscription;
use App\Models\User;
use Tests\UnitTestCase;

/**
 * Comprehensive tests for Subscription Model
 * Following TESTING_GUIDE.md - using test_ prefix, UnitTestCase base class
 */
class SubscriptionTest extends UnitTestCase
{
    // ===== MODEL CREATION TESTS =====

    public function test_subscription_can_be_created(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_EMAIL,
            'event' => Subscription::EVENT_NEW_CONVERSATION,
        ]);
        
        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_subscription_has_correct_fillable_attributes(): void
    {
        $subscription = new Subscription();
        
        $this->assertContains('user_id', $subscription->getFillable());
        $this->assertContains('medium', $subscription->getFillable());
        $this->assertContains('event', $subscription->getFillable());
    }

    public function test_subscription_uses_has_factory_trait(): void
    {
        // Use withoutEvents to prevent UserObserver from creating default subscriptions
        $user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(Subscription::class, $subscription);
    }

    // ===== MEDIUM CONSTANT TESTS =====

    public function test_medium_email_constant_exists(): void
    {
        $this->assertEquals(1, Subscription::MEDIUM_EMAIL);
    }

    public function test_medium_browser_constant_exists(): void
    {
        $this->assertEquals(2, Subscription::MEDIUM_BROWSER);
    }

    public function test_medium_mobile_constant_exists(): void
    {
        $this->assertEquals(3, Subscription::MEDIUM_MOBILE);
    }

    // ===== EVENT CONSTANT TESTS - NEW CONVERSATION =====

    public function test_event_new_conversation_constant_exists(): void
    {
        $this->assertEquals(1, Subscription::EVENT_NEW_CONVERSATION);
    }

    public function test_event_conversation_assigned_to_me_constant_exists(): void
    {
        $this->assertEquals(2, Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME);
    }

    public function test_event_conversation_assigned_constant_exists(): void
    {
        $this->assertEquals(6, Subscription::EVENT_CONVERSATION_ASSIGNED);
    }

    public function test_event_followed_conversation_updated_constant_exists(): void
    {
        $this->assertEquals(13, Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED);
    }

    // ===== EVENT CONSTANT TESTS - CUSTOMER REPLIES =====

    public function test_event_customer_replied_to_my_constant_exists(): void
    {
        $this->assertEquals(3, Subscription::EVENT_CUSTOMER_REPLIED_TO_MY);
    }

    public function test_event_customer_replied_to_unassigned_constant_exists(): void
    {
        $this->assertEquals(4, Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED);
    }

    public function test_event_customer_replied_to_assigned_constant_exists(): void
    {
        $this->assertEquals(7, Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED);
    }

    // ===== EVENT CONSTANT TESTS - USER REPLIES =====

    public function test_event_user_replied_to_my_constant_exists(): void
    {
        $this->assertEquals(5, Subscription::EVENT_USER_REPLIED_TO_MY);
    }

    public function test_event_user_replied_to_unassigned_constant_exists(): void
    {
        $this->assertEquals(8, Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED);
    }

    public function test_event_user_replied_to_assigned_constant_exists(): void
    {
        $this->assertEquals(9, Subscription::EVENT_USER_REPLIED_TO_ASSIGNED);
    }

    // ===== RELATIONSHIP TESTS =====

    public function test_subscription_belongs_to_user(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(User::class, $subscription->user);
        $this->assertEquals($user->id, $subscription->user->id);
    }

    // ===== CAST TESTS =====

    public function test_user_id_is_cast_to_integer(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        $subscription = Subscription::factory()->create(['user_id' => (string) $user->id]);
        
        $this->assertIsInt($subscription->user_id);
    }

    public function test_medium_is_cast_to_integer(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        $subscription = Subscription::factory()->create(['user_id' => $user->id, 'medium' => '1']);
        
        $this->assertIsInt($subscription->medium);
    }

    public function test_event_is_cast_to_integer(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        $subscription = Subscription::factory()->create(['user_id' => $user->id, 'event' => '1']);
        
        $this->assertIsInt($subscription->event);
    }

    public function test_created_at_is_cast_to_datetime(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $subscription->created_at);
    }

    public function test_updated_at_is_cast_to_datetime(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $subscription->updated_at);
    }

    // ===== QUERY TESTS =====

    public function test_can_query_subscriptions_by_user(): void
    {
        // Clear any existing subscriptions first
        Subscription::query()->delete();
        
        $user = User::withoutEvents(fn() => User::factory()->create());
        // Create unique subscriptions
        Subscription::factory()->create(['user_id' => $user->id, 'event' => 1]);
        Subscription::factory()->create(['user_id' => $user->id, 'event' => 2]);
        Subscription::factory()->create(['user_id' => $user->id, 'event' => 3]);
        
        // Create another subscription for a different user
        $otherUser = User::withoutEvents(fn() => User::factory()->create());
        Subscription::factory()->create(['user_id' => $otherUser->id]);
        
        $subscriptions = Subscription::where('user_id', $user->id)->get();
        
        $this->assertCount(3, $subscriptions);
    }

    public function test_can_query_subscriptions_by_medium(): void
    {
        // Clear any existing subscriptions first
        Subscription::query()->delete();
        
        $user1 = User::withoutEvents(fn() => User::factory()->create());
        $user2 = User::withoutEvents(fn() => User::factory()->create());
        
        Subscription::factory()->create(['medium' => Subscription::MEDIUM_EMAIL, 'user_id' => $user1->id, 'event' => 1]);
        Subscription::factory()->create(['medium' => Subscription::MEDIUM_EMAIL, 'user_id' => $user1->id, 'event' => 2]);
        
        Subscription::factory()->create(['medium' => Subscription::MEDIUM_BROWSER, 'user_id' => $user2->id]);
        
        $emailSubscriptions = Subscription::where('medium', Subscription::MEDIUM_EMAIL)->get();
        
        $this->assertCount(2, $emailSubscriptions);
    }

    public function test_can_query_subscriptions_by_event(): void
    {
        Subscription::factory()->create(['event' => Subscription::EVENT_NEW_CONVERSATION]);
        Subscription::factory()->create(['event' => Subscription::EVENT_NEW_CONVERSATION]);
        Subscription::factory()->create(['event' => Subscription::EVENT_CUSTOMER_REPLIED_TO_MY]);
        
        $newConvSubscriptions = Subscription::where('event', Subscription::EVENT_NEW_CONVERSATION)->get();
        
        $this->assertCount(2, $newConvSubscriptions);
    }

    public function test_can_query_subscriptions_by_user_and_medium(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        
        Subscription::factory()->create([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_EMAIL,
        ]);
        
        Subscription::factory()->create([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_BROWSER,
        ]);
        
        $emailSubscriptions = Subscription::where('user_id', $user->id)
            ->where('medium', Subscription::MEDIUM_EMAIL)
            ->get();
        
        $this->assertCount(1, $emailSubscriptions);
    }

    // ===== SUBSCRIPTION SCENARIOS =====

    public function test_user_can_subscribe_to_new_conversations_via_email(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_EMAIL,
            'event' => Subscription::EVENT_NEW_CONVERSATION,
        ]);
        
        $this->assertEquals(Subscription::MEDIUM_EMAIL, $subscription->medium);
        $this->assertEquals(Subscription::EVENT_NEW_CONVERSATION, $subscription->event);
    }

    public function test_user_can_subscribe_to_assigned_conversations(): void
    {
        $user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_EMAIL,
            'event' => Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME,
        ]);
        
        $this->assertEquals(Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME, $subscription->event);
    }

    public function test_user_can_subscribe_to_customer_replies(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_EMAIL,
            'event' => Subscription::EVENT_CUSTOMER_REPLIED_TO_MY,
        ]);
        
        $this->assertEquals(Subscription::EVENT_CUSTOMER_REPLIED_TO_MY, $subscription->event);
    }

    public function test_user_can_have_multiple_subscriptions(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        
        // Create specific unique subscriptions to avoid collisions
        $events = [1, 3, 4, 5, 7];
        foreach ($events as $event) {
            Subscription::factory()->create([
                'user_id' => $user->id,
                'event' => $event,
                'medium' => Subscription::MEDIUM_EMAIL
            ]);
        }
        
        $this->assertCount(5, $user->subscriptions);
    }

    public function test_user_can_subscribe_via_multiple_mediums(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        
        Subscription::create([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_EMAIL,
            'event' => Subscription::EVENT_NEW_CONVERSATION,
        ]);
        
        Subscription::create([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_BROWSER,
            'event' => Subscription::EVENT_NEW_CONVERSATION,
        ]);
        
        $this->assertCount(2, $user->fresh()->subscriptions);
    }

    // ===== EDGE CASES =====

    public function test_subscription_can_be_updated(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        $subscription = Subscription::factory()->create([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_EMAIL,
        ]);
        
        $subscription->update(['medium' => Subscription::MEDIUM_BROWSER]);
        
        $this->assertEquals(Subscription::MEDIUM_BROWSER, $subscription->fresh()->medium);
    }

    public function test_subscription_can_be_deleted(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);
        $id = $subscription->id;
        
        $subscription->delete();
        
        $this->assertDatabaseMissing('subscriptions', ['id' => $id]);
    }

    public function test_subscription_timestamps_are_automatically_set(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        $subscription = Subscription::factory()->create(['user_id' => $user->id]);
        
        $this->assertNotNull($subscription->created_at);
        $this->assertNotNull($subscription->updated_at);
    }

    public function test_can_find_all_email_subscriptions(): void
    {
        // Clear any existing subscriptions first
        Subscription::query()->delete();

        $user1 = User::withoutEvents(fn() => User::factory()->create());
        $user2 = User::withoutEvents(fn() => User::factory()->create());

        // Create unique subscriptions for user1
        Subscription::factory()->create(['medium' => Subscription::MEDIUM_EMAIL, 'user_id' => $user1->id, 'event' => 1]);
        Subscription::factory()->create(['medium' => Subscription::MEDIUM_EMAIL, 'user_id' => $user1->id, 'event' => 3]);
        Subscription::factory()->create(['medium' => Subscription::MEDIUM_EMAIL, 'user_id' => $user1->id, 'event' => 4]);

        // Create unique subscriptions for user2
        Subscription::factory()->create(['medium' => Subscription::MEDIUM_BROWSER, 'user_id' => $user2->id, 'event' => 1]);
        Subscription::factory()->create(['medium' => Subscription::MEDIUM_BROWSER, 'user_id' => $user2->id, 'event' => 3]);
        
        $emailSubs = Subscription::where('medium', Subscription::MEDIUM_EMAIL)->get();
        
        $this->assertCount(3, $emailSubs);
    }

    public function test_can_find_all_subscriptions_for_event(): void
    {
        // We need unique users for each subscription to avoid unique constraint violations
        // if the factory defaults to same user/medium/event combination
        
        $users = User::withoutEvents(fn() => User::factory()->count(6)->create());
        
        foreach($users->take(4) as $user) {
            Subscription::factory()->create(['user_id' => $user->id, 'event' => Subscription::EVENT_NEW_CONVERSATION]);
        }
        
        foreach($users->skip(4) as $user) {
            Subscription::factory()->create(['user_id' => $user->id, 'event' => Subscription::EVENT_CUSTOMER_REPLIED_TO_MY]);
        }
        
        $newConvSubs = Subscription::where('event', Subscription::EVENT_NEW_CONVERSATION)->get();
        
        $this->assertCount(4, $newConvSubs);
    }

    public function test_subscription_with_all_customer_reply_events(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        
        $events = [
            Subscription::EVENT_CUSTOMER_REPLIED_TO_MY,
            Subscription::EVENT_CUSTOMER_REPLIED_TO_UNASSIGNED,
            Subscription::EVENT_CUSTOMER_REPLIED_TO_ASSIGNED,
        ];
        
        foreach ($events as $event) {
            Subscription::create([
                'user_id' => $user->id,
                'medium' => Subscription::MEDIUM_EMAIL,
                'event' => $event,
            ]);
        }
        
        $this->assertCount(3, $user->fresh()->subscriptions);
    }

    public function test_subscription_with_all_user_reply_events(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        
        $events = [
            Subscription::EVENT_USER_REPLIED_TO_MY,
            Subscription::EVENT_USER_REPLIED_TO_UNASSIGNED,
            Subscription::EVENT_USER_REPLIED_TO_ASSIGNED,
        ];
        
        foreach ($events as $event) {
            Subscription::create([
                'user_id' => $user->id,
                'medium' => Subscription::MEDIUM_EMAIL,
                'event' => $event,
            ]);
        }
        
        $this->assertCount(3, $user->fresh()->subscriptions);
    }

    public function test_subscription_can_be_created_with_mobile_medium(): void
    {
        $user = User::withoutEvents(fn() => User::factory()->create());
        
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_MOBILE,
            'event' => Subscription::EVENT_NEW_CONVERSATION,
        ]);
        
        $this->assertEquals(Subscription::MEDIUM_MOBILE, $subscription->medium);
    }

    public function test_subscription_for_followed_conversation_updated(): void
    {
        $user = User::withoutEvents(function () {
            return User::factory()->create();
        });
        
        $subscription = Subscription::create([
            'user_id' => $user->id,
            'medium' => Subscription::MEDIUM_EMAIL,
            'event' => Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED,
        ]);
        
        $this->assertEquals(Subscription::EVENT_FOLLOWED_CONVERSATION_UPDATED, $subscription->event);
    }

    public function test_multiple_users_can_have_same_subscription(): void
    {
        $user1 = User::withoutEvents(fn() => User::factory()->create());
        $user2 = User::withoutEvents(fn() => User::factory()->create());
        
        Subscription::create([
            'user_id' => $user1->id,
            'medium' => Subscription::MEDIUM_EMAIL,
            'event' => Subscription::EVENT_NEW_CONVERSATION,
        ]);
        
        Subscription::create([
            'user_id' => $user2->id,
            'medium' => Subscription::MEDIUM_EMAIL,
            'event' => Subscription::EVENT_NEW_CONVERSATION,
        ]);
        
        // We need to count only the subscriptions we just created, or clear before
        // Since we can't easily clear all subscriptions without affecting other tests if parallel,
        // but here we are in a single test method.
        // Let's count subscriptions for these users.
        
        $count = Subscription::whereIn('user_id', [$user1->id, $user2->id])->count();
        $this->assertEquals(2, $count);
    }
}
