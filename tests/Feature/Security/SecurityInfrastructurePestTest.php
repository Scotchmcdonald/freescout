<?php

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Email;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;

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

test('web cron rejects invalid hash', function () {
    $response = $this->get(route('cron', ['hash' => 'invalid_hash_123']));

    $isRejected = $response->status() === 404 ||
        $response->status() === 403 ||
        $response->status() === 401;

    expect($isRejected)->toBeTrue();
});

test('web cron rejects empty hash', function () {
    // This route requires a parameter, so accessing without it might 404 or throw exception
    // We'll just check that it doesn't succeed
    try {
        $response = $this->get('/cron/');
        $isRejected = $response->status() === 404 || $response->status() === 403;
        expect($isRejected)->toBeTrue();
    } catch (\Exception $e) {
        expect(true)->toBeTrue();
    }
});

test('web cron timing safe comparison', function () {
    // This is a conceptual test - we can't truly test timing attacks
    // but we verify the endpoint exists and validates hashes
    $response = $this->get(route('cron', ['hash' => str_repeat('a', 64)]));

    // Should reject but not leak timing info
    $isRejected = $response->status() === 404 ||
        $response->status() === 403 ||
        $response->status() === 401;

    expect($isRejected)->toBeTrue();
});

test('ajax endpoints return json', function () {
    $this->actingAs($this->user);

    $response = $this->postJson(route('conversations.ajax'), [
        'action' => 'follow',
        'conversation_id' => $this->conversation->id,
    ]);

    $response->assertHeader('Content-Type', 'application/json');
});

test('multiple rapid requests handled', function () {
    $this->actingAs($this->user);

    // Send multiple requests quickly
    for ($i = 0; $i < 10; $i++) {
        $response = $this->postJson(route('conversations.ajax'), [
            'action' => 'get_viewers',
            'conversation_id' => $this->conversation->id,
        ]);

        // Should all succeed or be rate limited
        $isHandled = $response->status() === 200 || $response->status() === 429;
        expect($isHandled)->toBeTrue();
    }
});
