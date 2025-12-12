<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\CustomerController;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversMethod;
use Tests\TestCase;

/**
 * Tests for customer email migration functionality.
 */
#[CoversMethod(CustomerController::class, 'ajaxMigrateEmail')]
class CustomerEmailMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Customer $sourceCustomer;
    protected Customer $targetCustomer;
    protected Mailbox $mailbox;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->mailbox = Mailbox::factory()->create();
        
        // Create source customer with multiple emails
        $this->sourceCustomer = Customer::factory()->withoutEmail()->create();
        Email::create([
            'customer_id' => $this->sourceCustomer->id,
            'email' => 'primary@example.com',
            'type' => Email::TYPE_PRIMARY,
        ]);
        Email::create([
            'customer_id' => $this->sourceCustomer->id,
            'email' => 'secondary@example.com',
            'type' => Email::TYPE_SECONDARY,
        ]);
        
        // Create target customer with one email
        $this->targetCustomer = Customer::factory()->withoutEmail()->create();
        Email::create([
            'customer_id' => $this->targetCustomer->id,
            'email' => 'target@example.com',
            'type' => Email::TYPE_PRIMARY,
        ]);
    }

    // ====================
    // SUCCESSFUL MIGRATION TESTS
    // ====================

    public function test_migrate_email_to_another_customer(): void
    {
        $emailToMigrate = $this->sourceCustomer->emails()->where('type', Email::TYPE_SECONDARY)->first();

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'migrate_email',
                'email_id' => $emailToMigrate->id,
                'source_customer_id' => $this->sourceCustomer->id,
                'target_customer_id' => $this->targetCustomer->id,
            ]);

        $response->assertJson(['success' => true]);
        
        // Verify email is now associated with target customer
        $emailToMigrate->refresh();
        $this->assertEquals($this->targetCustomer->id, $emailToMigrate->customer_id);
        
        // Source customer still has primary email
        $this->assertEquals(1, $this->sourceCustomer->emails()->count());
    }

    public function test_migrate_main_email_sets_new_main_for_source(): void
    {
        // Get the main email
        $mainEmail = $this->sourceCustomer->emails()->where('type', Email::TYPE_PRIMARY)->first();

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'migrate_email',
                'email_id' => $mainEmail->id,
                'source_customer_id' => $this->sourceCustomer->id,
                'target_customer_id' => $this->targetCustomer->id,
            ]);

        $response->assertJson(['success' => true]);
        
        // Source customer should have new main email
        $newMainEmail = $this->sourceCustomer->emails()->where('type', Email::TYPE_PRIMARY)->first();
        $this->assertNotNull($newMainEmail);
        $this->assertNotEquals($mainEmail->id, $newMainEmail->id);
    }

    public function test_migrate_email_also_migrates_conversations(): void
    {
        $emailToMigrate = $this->sourceCustomer->emails()->where('type', Email::TYPE_SECONDARY)->first();
        
        // Create a conversation associated with the email
        $conversation = Conversation::factory()->create([
            'customer_id' => $this->sourceCustomer->id,
            'customer_email' => $emailToMigrate->email,
            'mailbox_id' => $this->mailbox->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'migrate_email',
                'email_id' => $emailToMigrate->id,
                'source_customer_id' => $this->sourceCustomer->id,
                'target_customer_id' => $this->targetCustomer->id,
            ]);

        $response->assertJson(['success' => true]);
        
        // Verify conversation is reassigned
        $conversation->refresh();
        $this->assertEquals($this->targetCustomer->id, $conversation->customer_id);
    }

    // ====================
    // VALIDATION TESTS
    // ====================

    public function test_cannot_migrate_only_email_of_source_customer(): void
    {
        // Remove one email so source customer has only one
        $this->sourceCustomer->emails()->where('type', Email::TYPE_SECONDARY)->delete();
        
        $onlyEmail = $this->sourceCustomer->emails()->first();

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'migrate_email',
                'email_id' => $onlyEmail->id,
                'source_customer_id' => $this->sourceCustomer->id,
                'target_customer_id' => $this->targetCustomer->id,
            ]);

        $response->assertJson([
            'success' => false,
            'message' => 'Source customer must retain at least one email',
        ]);
    }

    public function test_cannot_migrate_to_same_customer(): void
    {
        $email = $this->sourceCustomer->emails()->first();

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'migrate_email',
                'email_id' => $email->id,
                'source_customer_id' => $this->sourceCustomer->id,
                'target_customer_id' => $this->sourceCustomer->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_migrate_nonexistent_email(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'migrate_email',
                'email_id' => 9999,
                'source_customer_id' => $this->sourceCustomer->id,
                'target_customer_id' => $this->targetCustomer->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_migrate_email_not_belonging_to_source_customer(): void
    {
        $otherCustomerEmail = $this->targetCustomer->emails()->first();

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('customers.ajax'), [
                'action' => 'migrate_email',
                'email_id' => $otherCustomerEmail->id,
                'source_customer_id' => $this->sourceCustomer->id,
                'target_customer_id' => $this->targetCustomer->id,
            ]);

        $response->assertJson([
            'success' => false,
            'message' => 'Email not found for source customer',
        ]);
    }

    // ====================
    // AUTHORIZATION TESTS
    // ====================

    public function test_unauthenticated_user_cannot_migrate_email(): void
    {
        $email = $this->sourceCustomer->emails()->first();

        $response = $this->postJson(route('customers.ajax'), [
            'action' => 'migrate_email',
            'email_id' => $email->id,
            'source_customer_id' => $this->sourceCustomer->id,
            'target_customer_id' => $this->targetCustomer->id,
        ]);

        $response->assertStatus(401);
    }
}
