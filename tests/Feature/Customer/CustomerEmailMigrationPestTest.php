<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Mailbox;
use App\Models\User;

test('migrate email to another customer', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $source = Customer::factory()->withoutEmail()->create();
    Email::factory()->create(['customer_id' => $source->id, 'email' => 'primary@example.com', 'type' => Email::TYPE_PRIMARY]);
    $emailToMigrate = Email::factory()->create(['customer_id' => $source->id, 'email' => 'secondary@example.com', 'type' => Email::TYPE_SECONDARY]);

    $target = Customer::factory()->withoutEmail()->create();
    $targetEmail = Email::factory()->create(['customer_id' => $target->id, 'email' => 'target@example.com', 'type' => Email::TYPE_PRIMARY]);

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'migrate_email',
        'email_id' => $emailToMigrate->id,
        'source_customer_id' => $source->id,
        'target_customer_id' => $target->id,
    ])->assertJson(['success' => true]);

    expect($emailToMigrate->refresh()->customer_id)->toBe($target->id);
    expect($source->emails()->count())->toBe(1);
});

test('migrate main email sets new main for source', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $source = Customer::factory()->withoutEmail()->create();
    $mainEmail = Email::factory()->create(['customer_id' => $source->id, 'email' => 'primary@example.com', 'type' => Email::TYPE_PRIMARY]);
    Email::factory()->create(['customer_id' => $source->id, 'email' => 'secondary@example.com', 'type' => Email::TYPE_SECONDARY]);

    $target = Customer::factory()->create();

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'migrate_email',
        'email_id' => $mainEmail->id,
        'source_customer_id' => $source->id,
        'target_customer_id' => $target->id,
    ])->assertJson(['success' => true]);

    // Source's remaining email should become primary
    $newMain = $source->emails()->where('type', Email::TYPE_PRIMARY)->first();
    expect($newMain)->not->toBeNull()
        ->and($newMain->id)->not->toBe($mainEmail->id);
});

test('migrate email also migrates conversations', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $source = Customer::factory()->withoutEmail()->create();
    Email::factory()->create(['customer_id' => $source->id, 'email' => 'primary@example.com', 'type' => Email::TYPE_PRIMARY]);
    $emailToMigrate = Email::factory()->create(['customer_id' => $source->id, 'email' => 'migrated@example.com', 'type' => Email::TYPE_SECONDARY]);

    $target = Customer::factory()->create();

    $conv = Conversation::factory()->create([
        'customer_id' => $source->id,
        'customer_email' => 'migrated@example.com',
        'mailbox_id' => $mailbox->id,
    ]);

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'migrate_email',
        'email_id' => $emailToMigrate->id,
        'source_customer_id' => $source->id,
        'target_customer_id' => $target->id,
    ])->assertJson(['success' => true]);

    expect($conv->refresh()->customer_id)->toBe($target->id);
});

test('cannot migrate only email of source customer', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $source = Customer::factory()->create(); // Has 1 email by default factory
    $target = Customer::factory()->create();

    $onlyEmail = $source->emails()->first();

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'migrate_email',
        'email_id' => $onlyEmail->id,
        'source_customer_id' => $source->id,
        'target_customer_id' => $target->id,
    ])->assertJson(['success' => false, 'message' => 'Source customer must retain at least one email']);
});

test('cannot migrate to same customer', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $source = Customer::factory()->create();
    $email = $source->emails()->first();

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'migrate_email',
        'email_id' => $email->id,
        'source_customer_id' => $source->id,
        'target_customer_id' => $source->id,
    ])->assertStatus(422);
});

test('cannot migrate email not belonging to source customer', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $source = Customer::factory()->create(); // Has 1 email
    $target = Customer::factory()->create(); // Has 1 email

    $targetEmail = $target->emails()->first();

    $this->actingAs($user)->postJson(route('customers.ajax'), [
        'action' => 'migrate_email',
        'email_id' => $targetEmail->id,
        'source_customer_id' => $source->id, // Source
        'target_customer_id' => $target->id, // Target
    ])->assertJson(['success' => false, 'message' => 'Email not found for source customer']);
});

test('unauthenticated user cannot migrate email', function () {
    $source = Customer::factory()->create();
    $target = Customer::factory()->create();
    $email = $source->emails()->first();

    $this->postJson(route('customers.ajax'), [
        'action' => 'migrate_email',
        'email_id' => $email->id,
        'source_customer_id' => $source->id,
        'target_customer_id' => $target->id,
    ])->assertStatus(401);
});
