<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_search_by_phone()
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $customer = Customer::create('test@example.com', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $customer->phones = [['type' => 'mobile', 'value' => '+1234567890']];
        $customer->save();

        $response = $this->actingAs($user)->get(route('customers.search', ['q' => '1234567890']));

        $response->assertStatus(200);
        $response->assertSee('John Doe');
    }

    public function test_conversation_search_by_customer_phone()
    {
        $mailbox = Mailbox::factory()->create();
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $customer = Customer::create('test@example.com', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);
        $customer->phones = [['type' => 'mobile', 'value' => '+9876543210']];
        $customer->save();

        $conversation = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'subject' => 'Test Subject',
            'state' => 2, // Published
        ]);

        $response = $this->actingAs($user)->get(route('conversations.search', ['q' => '9876543210']));

        $response->assertStatus(200);
        $response->assertSee('Test Subject');
    }

    public function test_conversation_search_scoping()
    {
        $mailbox1 = Mailbox::factory()->create();
        $mailbox2 = Mailbox::factory()->create();
        
        $user = User::factory()->create();
        $user->mailboxes()->attach($mailbox1->id);
        // User NOT attached to mailbox2

        $conversation1 = Conversation::factory()->create([
            'mailbox_id' => $mailbox1->id,
            'subject' => 'Subject 1',
            'state' => 2,
        ]);

        $conversation2 = Conversation::factory()->create([
            'mailbox_id' => $mailbox2->id,
            'subject' => 'Subject 2',
            'state' => 2,
        ]);

        $response = $this->actingAs($user)->get(route('conversations.search', ['q' => 'Subject']));

        $response->assertStatus(200);
        $response->assertSee('Subject 1');
        $response->assertDontSee('Subject 2');
    }
}
