<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\EmailMigration\Models\MigrationProfile;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// Rule C regression tests — MigrationProfile mass assignment.
// Written BEFORE the StoreMigrationProfileRequest/DTO refactor.
// Must pass before AND after.
// ─────────────────────────────────────────────────────────────────────────────

test('MigrationProfile fillable blocks non-fillable id override on create', function () {
    $profile = MigrationProfile::create([
        'name' => 'Test Profile',
        'provider_type' => 'imap',
        'host' => 'mail.example.com',
        'port' => 993,
        'encryption' => 'ssl',
        'id' => 99999, // attack
    ]);

    expect($profile->id)->not->toBe(99999);
    expect($profile->name)->toBe('Test Profile');
});

test('MigrationProfile fillable blocks created_at override on create', function () {
    $fakeDate = '2000-01-01 00:00:00';

    $profile = MigrationProfile::create([
        'name' => 'Test Profile',
        'provider_type' => 'imap',
        'host' => 'mail.example.com',
        'port' => 993,
        'encryption' => 'ssl',
        'created_at' => $fakeDate, // timestamp injection
    ]);

    expect($profile->created_at->year)->not->toBe(2000);
});

test('MigrationProfile fillable blocks unknown column injection on create', function () {
    $profile = MigrationProfile::create([
        'name' => 'Test Profile',
        'provider_type' => 'imap',
        'host' => 'mail.example.com',
        'port' => 993,
        'encryption' => 'ssl',
        'admin_override' => true, // non-existent attack field
    ]);

    $fresh = MigrationProfile::find($profile->id);
    expect(isset($fresh->admin_override))->toBeFalse();
});

test('MigrationProfile update fillable blocks non-fillable id change', function () {
    $profile = MigrationProfile::create([
        'name' => 'Test Profile',
        'provider_type' => 'imap',
        'host' => 'mail.example.com',
        'port' => 993,
        'encryption' => 'ssl',
    ]);

    $originalId = $profile->id;

    $profile->update([
        'name' => 'Updated Profile',
        'id' => 99999, // attack on update
    ]);

    expect($profile->fresh()->id)->toBe($originalId)
        ->and($profile->fresh()->name)->toBe('Updated Profile');
});

test('MigrationProfile accepts all legitimate fillable fields', function () {
    $profile = MigrationProfile::create([
        'name' => 'Exchange Profile',
        'provider_type' => 'exchange',
        'host' => 'autodiscover.contoso.com',
        'port' => 443,
        'encryption' => 'ssl',
        'is_default_source' => true,
        'is_default_destination' => false,
    ]);

    expect($profile->name)->toBe('Exchange Profile')
        ->and($profile->provider_type)->toBe('exchange')
        ->and($profile->is_default_source)->toBeTrue();
});
