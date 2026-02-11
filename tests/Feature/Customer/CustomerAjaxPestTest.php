<?php

use App\Models\Customer;
use App\Models\User;
use App\Models\Email;
use App\Models\Conversation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

// --- Search & Conversations ---

test('ajax search by first name', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->withoutEmail()->create(['first_name' => 'Alice']);
    Email::factory()->create(['customer_id' => $customer->id, 'email' => 'alice@example.com']);
    Customer::factory()->withoutEmail()->create(['first_name' => 'Bob']);

    $response = $this->actingAs($user)->post(route('customers.ajax'), [
        'action' => 'search',
        'q' => 'Alice',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['results' => [['id', 'text']]]);

    $results = $response->json('results');
    expect($results)->toHaveCount(1);
    expect($results[0]['text'])->toContain('Alice');
});

test('ajax search by last name', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

    $response = $this->actingAs($user)->post(route('customers.ajax'), [
        'action' => 'search',
        'q' => 'Doe',
    ]);

    $response->assertOk();
    $results = $response->json('results');
    expect($results[0]['text'])->toContain('Doe');
});

test('ajax search limits results', function () {
    $user = User::factory()->create();
    Customer::factory()->count(30)->create(['first_name' => 'TestCommon']);

    $response = $this->actingAs($user)->post(route('customers.ajax'), [
        'action' => 'search',
        'q' => 'TestCommon',
    ]);

    $results = $response->json('results');
    expect($results)->toHaveCount(25); 
});

test('ajax conversation fetch', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    
    Conversation::factory()->count(2)->create([
        'customer_id' => $customer->id,
        'state' => Conversation::STATE_PUBLISHED,
    ]);
    Conversation::factory()->create([
        'customer_id' => $customer->id,
        'state' => Conversation::STATE_DRAFT,
    ]);

    $response = $this->actingAs($user)->post(route('customers.ajax'), [
        'action' => 'conversations',
        'customer_id' => $customer->id,
    ]);

    $response->assertOk();
    $conversations = $response->json('conversations');
    expect($conversations)->toHaveCount(2);
});

test('ajax conversation ordering', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    Conversation::factory()->create([
        'customer_id' => $customer->id,
        'subject' => 'Old',
        'state' => Conversation::STATE_PUBLISHED,
        'last_reply_at' => now()->subDays(5),
    ]);
    Conversation::factory()->create([
        'customer_id' => $customer->id,
        'subject' => 'New',
        'state' => Conversation::STATE_PUBLISHED,
        'last_reply_at' => now()->subDays(1),
    ]);

    $response = $this->actingAs($user)->post(route('customers.ajax'), [
        'action' => 'conversations',
        'customer_id' => $customer->id,
    ]);

    $conversations = $response->json('conversations');
    expect($conversations[0]['subject'])->toBe('New'); 
});

// --- Email Management ---

test('admin can add email to customer', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create();

    $this->actingAs($admin)->postJson(route('customers.ajax'), [
        'customer_id' => $customer->id,
        'action' => 'add_email',
        'email' => 'new@example.com',
    ])->assertOk();

    $this->assertDatabaseHas('emails', [
        'customer_id' => $customer->id,
        'email' => 'new@example.com',
    ]);
});

test('add email validates format', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create();

    $response = $this->actingAs($admin)->postJson(route('customers.ajax'), [
        'customer_id' => $customer->id,
        'action' => 'add_email',
        'email' => 'invalid-email',
    ]);

    // Expect error
    expect($response->status() >= 400 || $response->json('error') !== null)->toBeTrue();
});

test('add email prevents duplicates', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create();
    Email::factory()->create(['customer_id' => $customer->id, 'email' => 'existing@example.com']);

    $response = $this->actingAs($admin)->postJson(route('customers.ajax'), [
        'customer_id' => $customer->id,
        'action' => 'add_email',
        'email' => 'existing@example.com',
    ]);

    expect($response->status() >= 400 || 
           $response->json('success') === false || 
           $response->json('error') !== null || 
           $response->json('status') === 'exists')->toBeTrue();
});

test('admin can delete customer email', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create();
    // Ensure 2 emails so we can delete one
    Email::factory()->create(['customer_id' => $customer->id, 'email' => 'keep@example.com']);
    $email = Email::factory()->create(['customer_id' => $customer->id, 'email' => 'delete@example.com']);

    $this->actingAs($admin)->postJson(route('customers.ajax'), [
        'customer_id' => $customer->id,
        'action' => 'delete_email',
        'email_id' => $email->id,
    ])->assertOk();

    $this->assertDatabaseMissing('emails', ['id' => $email->id]);
});

test('cannot delete last email', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create(); // Has 1 email
    $email = $customer->emails()->first();

    $this->actingAs($admin)->postJson(route('customers.ajax'), [
        'customer_id' => $customer->id,
        'action' => 'delete_email',
        'email_id' => $email->id,
    ]);

    $this->assertDatabaseHas('emails', ['id' => $email->id]);
});

test('admin can set main email', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create();
    $newMain = Email::factory()->create(['customer_id' => $customer->id, 'email' => 'newmain@example.com', 'type' => Email::TYPE_SECONDARY]);

    $this->actingAs($admin)->postJson(route('customers.ajax'), [
        'customer_id' => $customer->id,
        'action' => 'set_main_email',
        'email_id' => $newMain->id,
    ])->assertOk();

    expect($newMain->refresh()->type)->toBe(Email::TYPE_PRIMARY);
});

// --- Photo Management ---

test('admin can upload customer photo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create();
    Storage::fake('public');

    $this->actingAs($admin)->postJson(route('customers.ajax'), [
        'customer_id' => $customer->id,
        'action' => 'upload_photo',
        'photo' => UploadedFile::fake()->image('customer.jpg'),
    ])->assertOk();
});

test('upload photo rejects non image', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create();
    Storage::fake('public');

    $response = $this->actingAs($admin)->postJson(route('customers.ajax'), [
        'customer_id' => $customer->id,
        'action' => 'upload_photo',
        'photo' => UploadedFile::fake()->create('doc.pdf'),
    ]);

    expect($response->status() >= 400 || $response->json('error') !== null)->toBeTrue();
});

test('admin can delete customer photo', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create(['photo_url' => 'pic.jpg']);
    Storage::fake('public');

    $this->actingAs($admin)->postJson(route('customers.ajax'), [
        'customer_id' => $customer->id,
        'action' => 'delete_photo',
    ])->assertOk();
    
    // Note: Database verification depends on implementation details (field nulling?)
});

// --- Phone Management ---

test('admin can add phone', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create();

    $this->actingAs($admin)->postJson(route('customers.ajax'), [
        'customer_id' => $customer->id,
        'action' => 'add_phone',
        'phone' => '+1234567890',
    ])->assertOk();
});

test('admin can delete phone', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $customer = Customer::factory()->create(['phones' => ['+123456']]);

    $this->actingAs($admin)->postJson(route('customers.ajax'), [
        'customer_id' => $customer->id,
        'action' => 'delete_phone',
        'phone_index' => 0,
    ])->assertOk();

    expect($customer->fresh()->phones)->toBeEmpty();
});

test('guest cannot access customer ajax', function () {
    $customer = Customer::factory()->create();
    $this->postJson(route('customers.ajax'), [
        'customer_id' => $customer->id,
        'action' => 'add_email',
        'email' => 'test@test.com',
    ])->assertUnauthorized();
});
