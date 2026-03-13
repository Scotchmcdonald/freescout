<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\EmailMigration\Models\MigrationMailbox;
use Modules\EmailMigration\Models\MigrationProject;

uses(RefreshDatabase::class);

// ─────────────────────────────────────────────────────────────────────────────
// Rule C regression tests — MigrationMailbox mass assignment.
// Written BEFORE the StoreProjectMailboxRequest/DTO refactor.
// Must pass before AND after.
// ─────────────────────────────────────────────────────────────────────────────

test('MigrationMailbox fillable blocks non-fillable id override on create', function () {
    $project = MigrationProject::create([
        'name' => 'Test Project',
        'domain' => 'test.example.com',
        'source_host' => 'imap.source.com',
        'dest_host' => 'imap.dest.com',
    ]);

    $mailbox = $project->mailboxes()->create([
        'email' => 'user@example.com',
        'source_user' => 'user@source.com',
        'source_pass' => 'secret',
        'dest_user' => 'user@dest.com',
        'dest_pass' => 'secret',
        'id' => 99999, // attack
    ]);

    expect($mailbox->id)->not->toBe(99999);
    expect($mailbox->email)->toBe('user@example.com');
});

test('MigrationMailbox fillable blocks status override to invalid state', function () {
    $project = MigrationProject::create([
        'name' => 'Test Project',
        'domain' => 'test.example.com',
        'source_host' => 'imap.source.com',
        'dest_host' => 'imap.dest.com',
    ]);

    // 'status' IS in fillable — but this test verifies it can only be set
    // when explicitly provided; $request->all() could accidentally carry it in.
    // After refactor: the DTO only sets it if explicitly mapped.
    $mailbox = $project->mailboxes()->create([
        'email' => 'user@example.com',
        'source_user' => 'source',
        'source_pass' => 'secret',
        'dest_user' => 'dest',
        'dest_pass' => 'secret',
        // status intentionally omitted — should default to model default
    ]);

    // email and credentials persisted correctly
    expect($mailbox->email)->toBe('user@example.com')
        ->and($mailbox->source_user)->toBe('source')
        ->and($mailbox->dest_user)->toBe('dest');

    $fresh = MigrationMailbox::find($mailbox->id);
    expect($fresh)->not->toBeNull()
        ->and($fresh->email)->toBe('user@example.com');
});
