<?php

use App\Models\Mailbox;
use App\Services\ImapService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('fetches from multiple imap folders', function () {
    $mailbox = Mailbox::factory()->create([
        'name' => 'Support',
        'in_server' => 'imap.example.com',
        'in_imap_folders' => json_encode(['INBOX', 'Archive']),
    ]);

    $this->mock(ImapService::class)
        ->shouldReceive('fetchEmails')
        ->once()
        ->andReturn([
            'fetched' => 10,
            'created' => 8,
            'errors' => 0,
            'messages' => [],
        ]);

    $this->artisan('freescout:fetch-emails', ['mailbox_id' => $mailbox->id])
        ->expectsOutputToContain('Fetched: 10')
        ->expectsOutputToContain('Created: 8')
        ->assertExitCode(0);
});

test('handles empty mailbox gracefully', function () {
    $mailbox = Mailbox::factory()->create([
        'in_server' => 'imap.example.com',
    ]);

    $this->mock(ImapService::class)
        ->shouldReceive('fetchEmails')
        ->once()
        ->andReturn([
            'fetched' => 0,
            'created' => 0,
            'errors' => 0,
            'messages' => [],
        ]);

    $this->artisan('freescout:fetch-emails', ['mailbox_id' => $mailbox->id])
        ->expectsOutputToContain('Fetched: 0')
        ->expectsOutputToContain('Created: 0')
        ->expectsOutputToContain('Errors: 0')
        ->assertExitCode(0);
});

test('connection test mode works correctly', function () {
    $mailbox = Mailbox::factory()->create([
        'name' => 'Test Mailbox',
        'in_server' => 'imap.example.com',
    ]);

    $mock = $this->mock(ImapService::class);
    $mock->shouldReceive('testConnection')
        ->once()
        ->andReturn([
            'success' => true,
            'message' => 'Connected successfully. Found 15 messages.',
        ]);
        
    $mock->shouldNotReceive('fetchEmails');

    $this->artisan('freescout:fetch-emails', [
        'mailbox_id' => $mailbox->id,
        '--test' => true,
    ])
        ->expectsOutputToContain('✓ Connected successfully')
        ->doesntExpectOutput('=== Summary ===')
        ->assertExitCode(0);
});

test('connection failure in test mode', function () {
    $mailbox = Mailbox::factory()->create([
        'in_server' => 'invalid.example.com',
    ]);

    $this->mock(ImapService::class)
        ->shouldReceive('testConnection')
        ->once()
        ->andReturn([
            'success' => false,
            'message' => 'Connection failed: Could not connect to server',
        ]);

    $this->artisan('freescout:fetch-emails', [
        'mailbox_id' => $mailbox->id,
        '--test' => true,
    ])
        ->expectsOutputToContain('✗ Connection failed')
        ->assertExitCode(1);
});

test('fetches from all mailboxes', function () {
    $mailbox1 = Mailbox::factory()->create([
        'name' => 'Sales',
        'in_server' => 'imap1.example.com',
    ]);
    $mailbox2 = Mailbox::factory()->create([
        'name' => 'Support',
        'in_server' => 'imap2.example.com',
    ]);

    $this->mock(ImapService::class)
        ->shouldReceive('fetchEmails')
        ->twice() // Called for each mailbox
        ->andReturn([
            'fetched' => 5,
            'created' => 3,
            'errors' => 0,
            'messages' => [],
        ]);

    $this->artisan('freescout:fetch-emails')
        ->expectsOutput('Processing 2 mailbox(es)...')
        ->expectsOutputToContain('Sales')
        ->expectsOutputToContain('Support')
        ->expectsOutputToContain('Total fetched: 10')
        ->expectsOutputToContain('Total created: 6')
        ->assertExitCode(0);
});

test('handles fetch errors', function () {
    $mailbox = Mailbox::factory()->create([
        'in_server' => 'imap.example.com',
    ]);

    $this->mock(ImapService::class)
        ->shouldReceive('fetchEmails')
        ->once()
        ->andReturn([
            'fetched' => 10,
            'created' => 7,
            'errors' => 3,
            'messages' => [
                'Failed to parse message 1',
                'Failed to parse message 2',
                'Failed to parse message 3',
            ],
        ]);

    $this->artisan('freescout:fetch-emails', ['mailbox_id' => $mailbox->id])
        ->expectsOutputToContain('Errors: 3')
        ->expectsOutputToContain('Failed to parse message 1')
        ->expectsOutputToContain('Failed to parse message 2')
        ->expectsOutputToContain('Failed to parse message 3')
        ->assertExitCode(1);
});

test('skips mailboxes without imap server', function () {
    Mailbox::factory()->create(['in_server' => null]);
    Mailbox::factory()->create(['in_server' => '']);

    $mock = $this->mock(ImapService::class);
    $mock->shouldNotReceive('fetchEmails');
    $mock->shouldNotReceive('testConnection');

    $this->artisan('freescout:fetch-emails')
        ->expectsOutput('No mailboxes configured for IMAP.')
        ->assertExitCode(1);
});
