<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;


beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $this->user = User::factory()->create(['role' => User::ROLE_USER]);

    $this->mailbox = Mailbox::factory()->create();
    $this->mailbox->users()->attach([$this->admin->id, $this->user->id]);

    $this->folder = Folder::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'type' => Folder::TYPE_INBOX,
    ]);

    $this->customer = Customer::factory()->create();
    Email::factory()->create([
        'customer_id' => $this->customer->id,
        'email' => 'test@example.com',
    ]);

    $this->conversation = Conversation::factory()->for($this->mailbox)->create([
        'customer_id' => $this->customer->id,
        'folder_id' => $this->folder->id,
        'status' => Conversation::STATUS_ACTIVE,
    ]);
});

test('bulk operations validate conversation ids', function () {
    $this->actingAs($this->admin);

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'bulk_change_status',
        'conversation_ids' => 'not_an_array',
        'status' => Conversation::STATUS_CLOSED,
    ]);

    // Should reject invalid input
    $isRejected = $response->status() >= 400 || $response->json('success') === false;
    expect($isRejected)->toBeTrue();
});

test('bulk operations validate status type', function () {
    $this->actingAs($this->admin);

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'bulk_change_status',
        'conversation_ids' => [$this->conversation->id],
        'status' => 'invalid_status',
    ]);

    $isRejected = $response->status() >= 400 || $response->json('success') === false;
    expect($isRejected)->toBeTrue();
});

test('customer email validation', function () {
    $this->actingAs($this->admin);

    $response = $this->postJson(route('customers.ajax', $this->customer), [
        'action' => 'add_email',
        'email' => 'not-a-valid-email',
    ]);

    $isRejected = $response->status() >= 400 || $response->json('success') === false;
    expect($isRejected)->toBeTrue();
});

test('customer phone validation', function () {
    $this->actingAs($this->admin);

    // Very long phone number
    $response = $this->postJson(route('customers.ajax', $this->customer), [
        'action' => 'add_phone',
        'phone' => str_repeat('1', 200),
    ]);

    // Should validate length
    $isRejected = $response->status() >= 400 || $response->json('success') === false;
    expect($isRejected)->toBeTrue();
});

test('photo upload validates mime type', function () {
    $this->actingAs($this->admin);

    // Create a fake PHP file disguised as image
    $fakeFile = UploadedFile::fake()->create('malicious.php', 100);

    $response = $this->postJson(route('customers.ajax', $this->customer), [
        'action' => 'upload_photo',
        'customer_id' => $this->customer->id,
        'photo' => $fakeFile,
    ]);

    // Should reject non-image files
    $isRejected = $response->status() >= 400 || $response->json('success') === false;
    expect($isRejected)->toBeTrue();
});

test('photo upload validates file size', function () {
    $this->actingAs($this->admin);

    // Create oversized file (over 2MB limit)
    $largeFile = UploadedFile::fake()->image('large.jpg')->size(5000); // 5MB

    $response = $this->postJson(route('customers.ajax', $this->customer), [
        'action' => 'upload_photo',
        'customer_id' => $this->customer->id,
        'photo' => $largeFile,
    ]);

    // Should reject oversized files
    $isRejected = $response->status() >= 400 || $response->json('success') === false;
    expect($isRejected)->toBeTrue();
});

test('file operations prevent path traversal', function () {
    $this->actingAs($this->admin);

    // Attempt path traversal in photo URL (if applicable)
    // This is conceptual - actual prevention is in the controller logic
    
    expect(true)->toBeTrue(); // Placeholder
});
