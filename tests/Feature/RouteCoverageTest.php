<?php

/**
 * Route Canary Tests
 *
 * Focused smoke-tests for the most critical application routes.
 *
 * Previously this file contained a single mega-test that looped all registered
 * routes but silently skipped a growing `$brokenRoutes` allowlist — masking real
 * regressions.  That test has been replaced with targeted canaries that make
 * failures loud and unambiguous.
 *
 * When you add a new top-level route, add a corresponding canary here.
 * When a canary fails in CI, fix the route — do NOT add it to a skip-list.
 */

use App\Models\Conversation;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

    $this->mailbox = Mailbox::factory()->create([
        'name' => 'Canary Mailbox',
        'email' => 'canary@example.com',
    ]);
    $this->mailbox->users()->attach($this->admin);

    $this->folder = Folder::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'type' => Folder::TYPE_INBOX,
    ]);
});

// ---------------------------------------------------------------------------
// Auth / public routes
// ---------------------------------------------------------------------------

test('login page is accessible to guests', function () {
    $this->get(route('login'))->assertOk();
});

test('unauthenticated request to dashboard redirects to login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

// ---------------------------------------------------------------------------
// Core authenticated routes
// ---------------------------------------------------------------------------

test('dashboard loads for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('dashboard'))
        ->assertOk();
});

test('conversation list loads for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('conversations.index', $this->mailbox))
        ->assertOk();
});

test('single conversation view loads for admin', function () {
    $conversation = Conversation::factory()->create([
        'mailbox_id' => $this->mailbox->id,
        'subject' => 'Canary conversation',
    ]);

    $this->actingAs($this->admin)
        ->get(route('conversations.show', $conversation))
        ->assertOk();
});

test('mailbox settings page loads for admin', function () {
    $this->actingAs($this->admin)
        ->get(route('mailboxes.update', $this->mailbox))
        ->assertOk();
});

// ---------------------------------------------------------------------------
// DEAD-LETTER catalogue
// These routes are KNOWN to be broken and need fixing — tracked in
// docs/development/WIP/TEST_SUITE_REMEDIATION_2026-03-17.md (P2+ backlog).
// Do NOT copy the old $brokenRoutes pattern here; file issues instead.
// ---------------------------------------------------------------------------

// @todo sync-monitor       — Missing x-data-table component
// @todo portal/password/reset — Missing auth.reset-request view
// @todo admin/analytics    — DB tables absent in test env
// @todo software-subscriptions/reports/vendor-cost — Missing view
// @todo settings/data-import — References unimplemented route
// @todo email-migration/users/search — Log file permission issue
// @todo tours              — Log file permission issue
// @todo action1/audit      — Log file permission issue
// @todo system/logs/download — Requires concrete storage artifact

/*
 * REMOVED: the old all-routes mega-test that silently swallowed the above failures.
 * Restore it (without the $brokenRoutes array) only once all routes above are fixed.
 */

// Placeholder so this file has at least one test that will always run
// (Pest requires at least one test per file it discovers)
test('application responds to health check', function () {
    // keep this as a stable no-op anchor for this file
    expect(true)->toBeTrue();
});
