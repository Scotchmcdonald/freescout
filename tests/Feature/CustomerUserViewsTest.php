<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerUserViewsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => User::ROLE_USER, 'status' => 1]);
        $this->adminUser = User::factory()->create(['role' => User::ROLE_ADMIN, 'status' => 1]);
    }

    #[Test]
    public function it_displays_customer_conversations_page(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->user)->get(route('customers.conversations', $customer));

        $response->assertOk();
        $response->assertViewIs('customers.conversations');
        $response->assertViewHas('customer');
        $response->assertViewHas('conversations');
    }

    #[Test]
    public function it_displays_customer_merge_form(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->user)->get(route('customers.merge.form', $customer));

        $response->assertOk();
        $response->assertViewIs('customers.merge');
        $response->assertViewHas('customer');
        $response->assertSee('Merge Customer');
    }

    #[Test]
    public function it_displays_user_notifications_page(): void
    {
        $response = $this->actingAs($this->user)->get(route('users.notifications', $this->user));

        $response->assertOk();
        $response->assertViewIs('users.notifications');
        $response->assertViewHas('user');
        $response->assertViewHas('subscriptions');
        $response->assertSee('Notification Preferences');
    }

    #[Test]
    public function it_displays_user_permissions_page(): void
    {
        Mailbox::factory()->create(['name' => 'Support']);

        $response = $this->actingAs($this->adminUser)->get(route('users.permissions', $this->user));

        $response->assertOk();
        $response->assertViewIs('users.permissions');
        $response->assertViewHas('user');
        $response->assertViewHas('mailboxes');
        $response->assertSee('User Permissions');
    }

    #[Test]
    public function it_updates_user_notifications(): void
    {
        $response = $this->actingAs($this->user)->post(
            route('users.notifications.update', $this->user),
            [
                'subscriptions' => [
                    Subscription::MEDIUM_EMAIL => [
                        Subscription::EVENT_NEW_CONVERSATION,
                        Subscription::EVENT_CONVERSATION_ASSIGNED_TO_ME,
                    ],
                    Subscription::MEDIUM_BROWSER => [
                        Subscription::EVENT_NEW_CONVERSATION,
                    ],
                ],
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertCount(3, $this->user->fresh()->subscriptions);
        $this->assertTrue(
            $this->user->subscriptions()->where([
                'medium' => Subscription::MEDIUM_EMAIL,
                'event' => Subscription::EVENT_NEW_CONVERSATION,
            ])->exists()
        );
    }

    #[Test]
    public function it_updates_user_mailbox_permissions(): void
    {
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();

        $response = $this->actingAs($this->adminUser)->post(
            route('users.permissions.update', $this->user),
            [
                'mailboxes' => [$mailbox1->id, $mailbox2->id],
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($this->user->mailboxes()->where('mailboxes.id', $mailbox1->id)->exists());
        $this->assertTrue($this->user->mailboxes()->where('mailboxes.id', $mailbox2->id)->exists());
    }

    #[Test]
    public function it_prevents_non_admin_from_viewing_other_users_permissions(): void
    {
        $otherUser = User::factory()->create(['role' => 2, 'status' => 1]);

        $response = $this->actingAs($this->user)->get(route('users.permissions', $otherUser));

        $response->assertForbidden();
    }

    #[Test]
    public function it_allows_users_to_view_their_own_notifications(): void
    {
        $response = $this->actingAs($this->user)->get(route('users.notifications', $this->user));

        $response->assertOk();
    }

    #[Test]
    public function it_deletes_customer_without_conversations(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('customers.destroy', $customer));

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    #[Test]
    public function it_prevents_deleting_customer_with_conversations(): void
    {
        $customer = Customer::factory()->hasConversations(1)->create();

        $response = $this->actingAs($this->user)->delete(route('customers.destroy', $customer));

        $response->assertRedirect();
        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    #[Test]
    public function subscriptions_table_displays_all_notification_events(): void
    {
        $response = $this->actingAs($this->user)->get(route('users.notifications', $this->user));

        $response->assertSee('There is a new conversation');
        $response->assertSee('A conversation is assigned to me');
        $response->assertSee('To an unassigned conversation');
        $response->assertSee('Email');
        $response->assertSee('Browser');
        $response->assertSee('Mobile');
    }

    #[Test]
    public function customers_table_partial_displays_customers(): void
    {
        $customers = Customer::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('customers.index'));

        foreach ($customers as $customer) {
            $response->assertSee($customer->getFullName());
        }
    }
}
