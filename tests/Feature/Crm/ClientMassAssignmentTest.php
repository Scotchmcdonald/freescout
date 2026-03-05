<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Crm\Models\Client;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// Rule C regression tests — written BEFORE the FormRequest/DTO refactor.
// These must pass before AND after the refactor:
//  - Before: $fillable on Client is the only protection.
//  - After:  StoreClientData DTO provides an additional second layer.
// ─────────────────────────────────────────────────────────────────────────────

test('Client fillable blocks non-fillable id override on create', function () {
    $client = Client::create([
        'name'   => 'Acme Corp',
        'status' => 'active',
        'id'     => 99999, // mass-assignment attack
    ]);

    expect($client->id)->not->toBe(99999);
    expect($client->name)->toBe('Acme Corp');
});

test('Client fillable blocks deleted_at injection on create', function () {
    $client = Client::create([
        'name'       => 'Acme Corp',
        'status'     => 'active',
        'deleted_at' => now()->subDay()->toDateTimeString(), // soft-delete bypass attempt
    ]);

    expect($client->deleted_at)->toBeNull();
});

test('Client fillable blocks unknown field injection on create', function () {
    $client = Client::create([
        'name'          => 'Acme Corp',
        'status'        => 'active',
        'is_superadmin' => true, // non-existent, non-fillable field
    ]);

    // The model is created; the injected field is silently ignored by $fillable
    expect($client->name)->toBe('Acme Corp');

    // Re-fetch from DB — the non-fillable attribute was never persisted
    $fresh = Client::find($client->id);
    expect($fresh)->not->toBeNull();
    expect($fresh->getAttributes())->not->toHaveKey('is_superadmin');
});

test('Client accepts all legitimate fillable fields', function () {
    $client = Client::create([
        'name'                => 'Legitimate Corp',
        'tier'                => 'Small Business', // valid enum value
        'email'               => 'contact@legitimate.com',
        'phone'               => '+1-555-0199',
        'company_type'        => 'business', // valid enum value
        'status'              => 'active',
        'default_hourly_rate' => 150.00,
        'notes'               => 'VIP client',
    ]);

    expect($client->name)->toBe('Legitimate Corp')
        ->and($client->tier)->toBe('Small Business')
        ->and($client->email)->toBe('contact@legitimate.com')
        ->and($client->status)->toBe('active');
});
