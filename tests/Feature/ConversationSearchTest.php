<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->mailbox = Mailbox::factory()->create();
        $this->mailbox->users()->attach($this->user);
    }

    /** @test */
    public function can_search_conversations_by_subject(): void
    {
        // Arrange
        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'subject' => 'Payment issue with invoice',
            'state' => 2,
        ]);

        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'subject' => 'Shipping delay question',
            'state' => 2,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('conversations.search', ['q' => 'payment']));

        // Assert
        $response->assertOk();
        $response->assertSee('Payment issue');
        $response->assertDontSee('Shipping delay');
    }

    /** @test */
    public function can_search_conversations_by_preview(): void
    {
        // Arrange
        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'subject' => 'General inquiry',
            'preview' => 'I need help with my refund request',
            'state' => 2,
        ]);

        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'subject' => 'Another inquiry',
            'preview' => 'When will my order ship',
            'state' => 2,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('conversations.search', ['q' => 'refund']));

        // Assert
        $response->assertOk();
        $response->assertSee('General inquiry');
        $response->assertDontSee('Another inquiry');
    }

    /** @test */
    public function can_search_conversations_by_customer_name(): void
    {
        // Arrange
        $customer1 = Customer::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Smith',
        ]);

        $customer2 = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $customer1->id,
            'subject' => 'Customer 1 inquiry',
            'state' => 2,
        ]);

        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'customer_id' => $customer2->id,
            'subject' => 'Customer 2 inquiry',
            'state' => 2,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('conversations.search', ['q' => 'John']));

        // Assert
        $response->assertOk();
        $response->assertSee('Customer 1 inquiry');
        $response->assertDontSee('Customer 2 inquiry');
    }

    /** @test */
    public function search_only_returns_published_conversations(): void
    {
        // Arrange
        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'subject' => 'Published conversation with searchterm',
            'state' => 2, // Published
        ]);

        Conversation::factory()->create([
            'mailbox_id' => $this->mailbox->id,
            'subject' => 'Draft conversation with searchterm',
            'state' => 1, // Draft
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->get(route('conversations.search', ['q' => 'searchterm']));

        // Assert
        $response->assertOk();
        $response->assertSee('Published conversation');
        $response->assertDontSee('Draft conversation');
    }

    /** @test */
    public function non_admin_can_only_search_their_mailboxes(): void
    {
        // Arrange
        $regularUser = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);
        
        $accessibleMailbox = Mailbox::factory()->create();
        $accessibleMailbox->users()->attach($regularUser);

        $inaccessibleMailbox = Mailbox::factory()->create();

        Conversation::factory()->create([
            'mailbox_id' => $accessibleMailbox->id,
            'subject' => 'Accessible conversation',
            'state' => 2,
        ]);

        Conversation::factory()->create([
            'mailbox_id' => $inaccessibleMailbox->id,
            'subject' => 'Inaccessible conversation',
            'state' => 2,
        ]);

        // Act
        $response = $this->actingAs($regularUser)
            ->get(route('conversations.search', ['q' => 'conversation']));

        // Assert
        $response->assertOk();
        $response->assertSee('Accessible conversation');
        $response->assertDontSee('Inaccessible conversation');
    }
}
