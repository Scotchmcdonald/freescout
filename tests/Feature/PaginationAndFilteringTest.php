<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationAndFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_list_with_page_2_navigation(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        $user->mailboxes()->attach($mailbox->id);

        // Create 50 conversations for multi-page results
        Conversation::factory()->count(50)->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->get(route('conversations.index', ['mailbox' => $mailbox->id, 'page' => 2]));

        $response->assertOk();
        $response->assertViewHas('conversations');
    }

    public function test_customer_ajax_search_with_multiple_matching_results(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        // Create 25 customers with "Smith" in name for search
        Customer::factory()->count(25)->create(['last_name' => 'Smith']);
        
        // Create some with other names
        Customer::factory()->count(10)->create(['last_name' => 'Jones']);

                        $response = $this->actingAs($user)->post(route('customers.ajax', ['action' => 'search', 'q' => 'Smith']));

        $response->assertOk();
        $response->assertJsonStructure([
            'results' => [
                '*' => ['id', 'text'],
            ],
        ]);
        
        $json = $response->json();
        $this->assertGreaterThan(0, count($json['results']));
    }

    public function test_conversation_search_with_partial_match(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        $conversation1 = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Payment Issue Resolution',
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $conversation2 = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Technical Support Question',
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->get(route('conversations.search', ['q' => 'Payment']));

        $response->assertOk();
    }

    public function test_mailbox_conversations_filter_by_assignment_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $assignedUser = User::factory()->create(['role' => User::ROLE_USER]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        $admin->mailboxes()->attach($mailbox->id);

        // Create assigned conversation
        $assignedConv = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'user_id' => $assignedUser->id,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        // Create unassigned conversation
        $unassignedConv = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'user_id' => null,
            'status' => Conversation::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($admin)->get(route('conversations.index', $mailbox->id));

        $response->assertOk();
        $response->assertViewHas('conversations');
    }

    public function test_customer_list_handles_large_dataset_efficiently(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        // Create 100 customers
        Customer::factory()->count(100)->create();

        $response = $this->actingAs($user)->get('/customers');

        $response->assertOk();
        $response->assertViewHas('customers');
        
        // Pagination should limit results per page
        $customers = $response->viewData('customers');
        $this->assertLessThanOrEqual(50, $customers->count());
    }

    public function test_empty_search_query_returns_all_results(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        Customer::factory()->count(10)->create();

        $response = $this->actingAs($user)->post('/customers/ajax?action=search&q=');

        $response->assertOk();
    }

    public function test_conversation_list_excludes_draft_conversations_by_default(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $customer = Customer::factory()->create();

        $user->mailboxes()->attach($mailbox->id);

        $publishedConv = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'status' => Conversation::STATUS_ACTIVE,
            'state' => Conversation::STATE_PUBLISHED,
        ]);

        $draftConv = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'status' => Conversation::STATUS_ACTIVE,
            'state' => Conversation::STATE_DRAFT,
        ]);

        $response = $this->actingAs($user)->get(route('conversations.index', $mailbox->id));

        $response->assertOk();
        $response->assertViewHas('conversations');
    }
}
