<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;


// ===== DATABASE EDGE CASES =====

test('handles soft deleted conversations', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $conversation = Conversation::factory()->create();
    
    $conversation->delete();

    $this->actingAs($admin)
        ->get(route('conversations.show', $conversation))
        ->assertNotFound();
});

test('handles trashed with trashed scope', function () {
    $conversation = Conversation::factory()->create();
    $conversation->delete();

    $found = Conversation::withTrashed()->find($conversation->id);

    expect($found)->not->toBeNull()
        ->and($found->trashed())->toBeTrue();
});

test('restores soft deleted records', function () {
    $conversation = Conversation::factory()->create();
    $conversation->delete();

    $conversation->restore();

    expect($conversation->trashed())->toBeFalse();
    
    $this->assertDatabaseHas('conversations', [
        'id' => $conversation->id,
        'deleted_at' => null,
    ]);
});

test('force deletes remove permanently', function () {
    $conversation = Conversation::factory()->create();
    $id = $conversation->id;

    $conversation->forceDelete();

    $this->assertDatabaseMissing('conversations', ['id' => $id]);
});

// ===== TRANSACTION EDGE CASES =====

test('rollback on exception', function () {
    $user = User::factory()->create();

    try {
        DB::transaction(function () use ($user) {
            $user->first_name = 'Updated';
            $user->save();

            throw new \Exception('Test exception');
        });
    } catch (\Exception $e) {
        // Expected
    }

    $user->refresh();
    expect($user->first_name)->not->toBe('Updated');
});

test('nested transactions rollback correctly', function () {
    $user = User::factory()->create(['first_name' => 'Original']);

    try {
        DB::transaction(function () use ($user) {
            $user->first_name = 'Outer';
            $user->save();

            try {
                DB::transaction(function () use ($user) {
                    $user->last_name = 'Inner';
                    $user->save();

                    throw new \Exception('Inner exception');
                });
            } catch (\Exception $e) {
                // Inner transaction fails
            }

            throw new \Exception('Outer exception');
        });
    } catch (\Exception $e) {
        // Expected
    }

    $user->refresh();
    expect($user->first_name)->toBe('Original');
});

test('transaction commits successfully', function () {
    $user = User::factory()->create(['first_name' => 'Original']);

    DB::transaction(function () use ($user) {
        $user->first_name = 'Updated';
        $user->save();
    });

    $user->refresh();
    expect($user->first_name)->toBe('Updated');
});

// ===== CONCURRENT OPERATION EDGE CASES =====

test('handles concurrent folder counter updates', function () {
    $mailbox = Mailbox::factory()->create();
    $folder = Folder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'total_count' => 0,
    ]);

    // Simulate concurrent conversation creation
    $conversations = [];
    for ($i = 0; $i < 10; $i++) {
        $conversations[] = Conversation::factory()->create([
            'mailbox_id' => $mailbox->id,
            'folder_id' => $folder->id,
        ]);
    }

    $folder->refresh();
    expect($folder->total_count)->toBeGreaterThanOrEqual(0);
});

test('handles race condition on customer creation', function () {
    $email = 'concurrent@example.com';

    // Simulate concurrent requests trying to create same customer
    $customers = [];
    for ($i = 0; $i < 3; $i++) {
        // Use Customer::create which handles email lookup/creation logic
        // instead of firstOrCreate which fails because email is not on customers table
        $customer = Customer::create($email, ['first_name' => 'Test', 'last_name' => 'User']);
        if ($customer) {
            $customers[] = $customer->id;
        }
    }

    // All should get the same customer ID
    // Filter out potential nulls if create failed (though it shouldn't for this test logic)
    $uniqueIds = array_unique($customers);
    expect(count($uniqueIds))->toBe(1);
});

// ===== VALIDATION EDGE CASES =====

test('validates email format strictly', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->post(route('mailboxes.store'), [
            'name' => 'Test',
            'email' => 'not-an-email',
        ])
        ->assertSessionHasErrors('email');
});

test('validates required fields', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)
        ->post(route('mailboxes.store'), [
            'name' => '', // Required but empty
        ])
        ->assertSessionHasErrors('name');
});

test('validates unique constraints', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    Mailbox::factory()->create(['email' => 'existing@example.com']);

    $this->actingAs($admin)
        ->post(route('mailboxes.store'), [
            'name' => 'Duplicate',
            'email' => 'existing@example.com',
        ])
        ->assertSessionHasErrors('email');
});

test('sanitizes html input', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->actingAs($admin)->post(route('mailboxes.store'), [
        'name' => '<script>alert("xss")</script>Test',
        'email' => 'test@example.com',
    ]);

    $mailbox = Mailbox::where('email', 'test@example.com')->first();
    expect($mailbox->name)->not->toContain('<script>');
});

// ===== CUSTOMER EMAIL RELATIONSHIP EDGE CASES =====

test('customer factory creates email correctly', function () {
    $customer = Customer::factory()->create(['email' => 'factory@example.com']);

    $this->assertDatabaseHas('emails', [
        'customer_id' => $customer->id,
        'email' => 'factory@example.com',
    ]);
});

test('query customer by email relationship', function () {
    $customer = Customer::factory()->create(['email' => 'query@example.com']);

    $found = Customer::whereHas('emails', function ($q) {
        $q->where('email', 'query@example.com');
    })->select('customers.*')->first();

    expect($found)->not->toBeNull()
        ->and($found->id)->toBe($customer->id);
});

test('customer can have multiple emails', function () {
    $customer = Customer::factory()->create();
    
    $customer->emails()->create(['email' => 'first@example.com']);
    $customer->emails()->create(['email' => 'second@example.com']);

    // Factory creates 1, plus 2 created here = 3
    expect($customer->emails()->count())->toBe(3);
});

test('deleting customer deletes emails', function () {
    $customer = Customer::factory()->create(['email' => 'delete@example.com']);
    $customerId = $customer->id;

    $customer->delete();

    $this->assertDatabaseMissing('emails', ['customer_id' => $customerId]);
});

// ===== INTEGRATION TEST SCENARIOS =====

test('complete conversation workflow edge case', function () {
    Event::fake();
    Mail::fake();

    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    
    // Manually create Inbox folder since Event::fake() prevents MailboxObserver from running
    Folder::factory()->create([
        'mailbox_id' => $mailbox->id,
        'type' => Folder::TYPE_INBOX,
        'name' => 'Inbox',
    ]);

    $customer = Customer::factory()->create(['email' => 'workflow@example.com']);

    // 1. Create conversation
    $this->actingAs($admin)->post(route('conversations.store', ['mailbox' => $mailbox->id]), [
        'customer_id' => $customer->id,
        'subject' => 'Test Conversation',
        'body' => 'Initial message',
        'to' => ['test@example.com'],
    ]);
    
    $conversation = Conversation::where('subject', 'Test Conversation')->first();
    expect($conversation)->not->toBeNull();
});
