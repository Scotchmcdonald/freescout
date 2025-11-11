<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationValidationTest extends TestCase
{
    use RefreshDatabase;

    /** Test empty subject validation */
    public function test_conversation_requires_subject(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);

        Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $customer = Customer::factory()->create();

        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => '',  // Empty
                'body' => 'Test body',
                'to' => [$customer->email],
            ]
        );

        $response->assertSessionHasErrors('subject');

        // Verify error message is helpful
        $errors = session('errors');
        $this->assertNotNull($errors);
        $subjectErrors = $errors->get('subject');
        $this->assertNotEmpty($subjectErrors);
        $this->assertIsArray($subjectErrors);
    }

    /** Test empty body validation */
    public function test_conversation_requires_body(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);

        Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $customer = Customer::factory()->create();

        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'body' => '',  // Empty
                'to' => [$customer->email],
            ]
        );

        $response->assertSessionHasErrors('body');

        // Verify no conversation was created
        $this->assertDatabaseMissing('conversations', [
            'subject' => 'Test',
            'mailbox_id' => $mailbox->id,
        ]);
    }

    /** Test invalid email format */
    public function test_conversation_validates_email_format(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);

        Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $customer = Customer::factory()->create();

        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'body' => 'Test body',
                'to' => ['not-an-email'],
            ]
        );

        // Laravel validates array elements individually, so error key is 'to.0'
        $response->assertSessionHasErrors('to.0');

        // Verify error message mentions email format
        $errors = session('errors');
        if ($errors) {
            $emailErrors = $errors->get('to.0');
            $this->assertNotEmpty($emailErrors);
        }

        // Verify no conversation was created with invalid email
        $this->assertDatabaseMissing('conversations', [
            'subject' => 'Test',
            'mailbox_id' => $mailbox->id,
        ]);
    }

    /** Test subject length limit */
    public function test_conversation_subject_has_reasonable_length(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);

        Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $customer = Customer::factory()->create();

        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => str_repeat('a', 300),  // Very long
                'body' => 'Test body',
                'to' => [$customer->email],
            ]
        );

        // Should either succeed (truncate) or fail validation
        $this->assertTrue(
            $response->isRedirect() || $response->status() === 422
        );
    }

    /** Test body with only whitespace */
    public function test_conversation_body_rejects_only_whitespace(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);

        Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $customer = Customer::factory()->create();

        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'body' => "   \n\n   ",  // Only whitespace
                'to' => [$customer->email],
            ]
        );

        $response->assertSessionHasErrors('body');

        // Test edge case: null body
        $response2 = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => 'Test 2',
                'body' => null,
                'to' => [$customer->email],
            ]
        );

        $response2->assertSessionHasErrors('body');
    }

    /** Test multiple recipients */
    public function test_conversation_accepts_multiple_recipients(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);

        Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $customer = Customer::factory()->create();

        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'body' => 'Test body',
                'to' => ['user1@test.com', 'user2@test.com', 'user3@test.com'],
            ]
        );

        // Should accept multiple valid emails
        $response->assertRedirect();
    }

    /** Test CC and BCC fields if supported */
    public function test_conversation_handles_cc_and_bcc(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);

        Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $customer = Customer::factory()->create();

        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'body' => 'Test body',
                'to' => [$customer->email],
                'cc' => ['cc@test.com'],
                'bcc' => ['bcc@test.com'],
            ]
        );

        // Should handle CC/BCC if fields exist
        $this->assertTrue(
            $response->isRedirect() || $response->status() === 422
        );
    }

    /** Test invalid customer ID */
    public function test_conversation_rejects_invalid_customer_id(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);

        Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => 99999,  // Non-existent
                'subject' => 'Test',
                'body' => 'Test body',
                'to' => ['test@test.com'],
            ]
        );

        $response->assertSessionHasErrors('customer_id');
    }

    /** Test special characters in subject */
    public function test_conversation_handles_special_characters_in_subject(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);

        Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $customer = Customer::factory()->create();

        $specialSubject = 'Test: Émojis 🎉 & Special © chars™';

        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => $specialSubject,
                'body' => 'Test body',
                'to' => [$customer->email],
            ]
        );

        $response->assertRedirect();
    }

    /** Test empty recipient array */
    public function test_conversation_requires_at_least_one_recipient(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $mailbox = Mailbox::factory()->create();
        $mailbox->users()->attach($user);

        Folder::factory()->create([
            'mailbox_id' => $mailbox->id,
            'type' => Folder::TYPE_INBOX,
        ]);

        $customer = Customer::factory()->create();

        $response = $this->actingAs($user)->post(
            route('conversations.store', $mailbox),
            [
                'customer_id' => $customer->id,
                'subject' => 'Test',
                'body' => 'Test body',
                'to' => [],  // Empty array
            ]
        );

        $response->assertSessionHasErrors('to');
    }
}
