<?php

use App\Models\Mailbox;
use App\Models\User;
use App\Services\ImapService;
use Illuminate\Support\Facades\Crypt;

// =========================================================================
// Connection Settings UI (from MailboxConnectionTest.php)
// =========================================================================

test('non-admins cannot view connection settings pages', function () {
    $regularUser = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($regularUser)
        ->get(route('mailboxes.connection.incoming', $mailbox))
        ->assertStatus(403);

    $this->actingAs($regularUser)
        ->get(route('mailboxes.connection.outgoing', $mailbox))
        ->assertStatus(403);
});

test('non-admins cannot update connection settings', function () {
    $regularUser = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($regularUser)
        ->post(route('mailboxes.connection.incoming', $mailbox), [])
        ->assertStatus(403);

    $this->actingAs($regularUser)
        ->post(route('mailboxes.connection.outgoing', $mailbox), [])
        ->assertStatus(403);
});

test('admin can view incoming connection settings page', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($adminUser)
        ->get(route('mailboxes.connection.incoming', $mailbox))
        ->assertStatus(200)
        ->assertViewIs('mailboxes.connection_incoming');
});

test('admin can update incoming connection settings', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $data = [
        'in_protocol' => 'imap',
        'in_server' => 'imap.test.com',
        'in_port' => 993,
        'in_encryption' => 'ssl',
        'in_username' => 'testuser',
        'in_password' => 'newpassword',
    ];

    $this->actingAs($adminUser)
        ->post(route('mailboxes.connection.incoming', $mailbox), $data)
        ->assertRedirect(route('mailboxes.connection.incoming', $mailbox))
        ->assertSessionHas('success');

    $mailbox->refresh();

    expect($mailbox->in_server)->toBe($data['in_server'])
        ->and($mailbox->in_username)->toBe($data['in_username'])
        ->and(Crypt::decrypt($mailbox->in_password))->toBe($data['in_password']);
});

test('incoming connection validation fails with invalid data', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $data = [
        'in_protocol' => 'invalid',
        'in_server' => '',
        'in_port' => 'not-a-number',
    ];

    $this->actingAs($adminUser)
        ->post(route('mailboxes.connection.incoming', $mailbox), $data)
        ->assertSessionHasErrors(['in_protocol', 'in_server', 'in_port']);
});

test('admin can view outgoing connection settings page', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($adminUser)
        ->get(route('mailboxes.connection.outgoing', $mailbox))
        ->assertStatus(200)
        ->assertViewIs('mailboxes.connection_outgoing');
});

test('admin can update outgoing connection settings', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $data = [
        'out_method' => 'smtp',
        'from_name' => 'Test From Name',
        'out_server' => 'smtp.test.com',
        'out_port' => 587,
        'out_encryption' => 'tls',
        'out_username' => 'smtpuser',
        'out_password' => 'new-smtp-password',
    ];

    $this->actingAs($adminUser)
        ->post(route('mailboxes.connection.outgoing', $mailbox), $data)
        ->assertRedirect(route('mailboxes.connection.outgoing', $mailbox))
        ->assertSessionHas('success');

    $mailbox->refresh();

    expect($mailbox->out_server)->toBe($data['out_server'])
        ->and($mailbox->out_username)->toBe($data['out_username'])
        ->and(Crypt::decrypt($mailbox->out_password))->toBe($data['out_password'])
        ->and($mailbox->from_name)->toBe(3)
        ->and($mailbox->from_name_custom)->toBe($data['from_name']);
});

test('outgoing connection validation fails with invalid data', function () {
    $adminUser = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $data = [
        'out_method' => 'invalid',
        'out_port' => 'not-a-number',
    ];

    $this->actingAs($adminUser)
        ->post(route('mailboxes.connection.outgoing', $mailbox), $data)
        ->assertSessionHasErrors(['out_method', 'out_port']);
});

// =========================================================================
// Connection Testing / IMAP Service (from MailboxConnectionImapTest.php)
// =========================================================================

test('ajax fetch test succeeds with valid credentials', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create([
        'in_server' => 'imap.example.com',
        'in_port' => 993,
        'in_username' => 'test@example.com',
        'in_password' => 'password123',
    ]);

    $this->mock(ImapService::class, function ($mock) {
        $mock->shouldReceive('testConnection')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Connected successfully. Found 25 messages in INBOX.',
            ]);
    });

    $this->actingAs($admin)
        ->postJson(route('mailboxes.ajax'), [
            'action' => 'fetch_test',
            'mailbox_id' => $mailbox->id,
        ])
        ->assertStatus(200)
        ->assertJson(['status' => 'success']);
});

test('ajax fetch test fails with invalid credentials', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create([
        'in_server' => 'imap.example.com',
        'in_port' => 993,
        'in_username' => 'test@example.com',
        'in_password' => 'wrongpassword',
    ]);

    $this->mock(ImapService::class, function ($mock) {
        $mock->shouldReceive('testConnection')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Authentication failed: Invalid credentials',
            ]);
    });

    $this->actingAs($admin)
        ->postJson(route('mailboxes.ajax'), [
            'action' => 'fetch_test',
            'mailbox_id' => $mailbox->id,
        ])
        ->assertStatus(200)
        ->assertJson([
            'status' => 'error',
            'msg' => 'Authentication failed: Invalid credentials',
        ]);
});

test('ajax retrieves imap folders', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create([
        'in_server' => 'imap.example.com',
        'in_port' => 993,
    ]);

    $this->mock(ImapService::class, function ($mock) {
        $mock->shouldReceive('getFolders')
            ->once()
            ->andReturn([
                'success' => true,
                'folders' => ['INBOX', 'Sent', 'Drafts', 'Trash', 'Archive'],
            ]);
    });

    $this->actingAs($admin)
        ->postJson(route('mailboxes.ajax'), [
            'action' => 'imap_folders',
            'mailbox_id' => $mailbox->id,
        ])
        ->assertStatus(200)
        ->assertJson([
            'folders' => ['INBOX', 'Sent', 'Drafts', 'Trash', 'Archive'],
        ]);
});

test('ajax folder retrieval handles connection errors', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create([
        'in_server' => 'invalid.example.com',
    ]);

    $this->mock(ImapService::class, function ($mock) {
        $mock->shouldReceive('getFolders')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Could not connect to server',
                'folders' => [],
            ]);
    });

    $this->actingAs($admin)
        ->postJson(route('mailboxes.ajax'), [
            'action' => 'imap_folders',
            'mailbox_id' => $mailbox->id,
        ])
        ->assertStatus(200)
        ->assertJsonStructure(['msg']);
});

test('non-admin cannot access fetch test', function () {
    $user = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($user)
        ->postJson(route('mailboxes.ajax'), [
            'action' => 'fetch_test',
            'mailbox_id' => $mailbox->id,
        ])
        ->assertStatus(200)
        ->assertJson([
            'status' => 'error',
            'msg' => 'Not enough permissions',
        ]);
});

test('fetch test handles timeout', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create([
        'in_server' => 'slow-server.example.com',
    ]);

    $this->mock(ImapService::class, function ($mock) {
        $mock->shouldReceive('testConnection')
            ->once()
            ->andReturn([
                'success' => false,
                'message' => 'Connection timeout after 30 seconds',
            ]);
    });

    $response = $this->actingAs($admin)
        ->postJson(route('mailboxes.ajax'), [
            'action' => 'fetch_test',
            'mailbox_id' => $mailbox->id,
        ]);

    $response->assertStatus(200)
        ->assertJson(['status' => 'error']);

    expect(strtolower($response->json('msg')))->toContain('timeout');
});

test('send test email success', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $testEmail = 'test@example.com';

    // Mock SmtpService
    $mockSmtpService = Mockery::mock(\App\Services\SmtpService::class);
    $mockSmtpService->shouldReceive('testConnection')
        ->with(Mockery::on(function ($arg) use ($mailbox) {
            return $arg->id === $mailbox->id;
        }), $testEmail)
        ->andReturn([
            'success' => true,
            'message' => 'Test email sent successfully',
        ]);

    app()->instance(\App\Services\SmtpService::class, $mockSmtpService);

    $response = $this->actingAs($admin)->postJson(route('mailboxes.send_test_email', $mailbox), [
        'test_email' => $testEmail,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'success',
        'message' => 'Test email sent successfully',
    ]);
});

test('send test email failure', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();
    $testEmail = 'test@example.com';

    // Mock SmtpService
    $mockSmtpService = Mockery::mock(\App\Services\SmtpService::class);
    $mockSmtpService->shouldReceive('testConnection')
        ->with(Mockery::on(function ($arg) use ($mailbox) {
            return $arg->id === $mailbox->id;
        }), $testEmail)
        ->andReturn([
            'success' => false,
            'message' => 'SMTP connection error',
        ]);

    app()->instance(\App\Services\SmtpService::class, $mockSmtpService);

    $response = $this->actingAs($admin)->postJson(route('mailboxes.send_test_email', $mailbox), [
        'test_email' => $testEmail,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'error',
        'message' => 'SMTP connection error',
    ]);
});

// =========================================================================
// Manual Email Fetching (from MailboxFetchEmailsTest.php)
// =========================================================================

test('admin can trigger manual email fetch', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->mock(ImapService::class, function ($mock) {
        $mock->shouldReceive('fetchEmails')
            ->once()
            ->andReturn([
                'fetched' => 5,
                'created' => 3,
            ]);
    });

    $this->actingAs($admin)
        ->postJson(route('mailboxes.fetch-emails', $mailbox))
        ->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonFragment([
            'stats' => [
                'fetched' => 5,
                'created' => 3,
            ],
        ]);
});

test('non-admin cannot trigger manual email fetch', function () {
    $regularUser = User::factory()->create(['role' => User::ROLE_USER, 'type' => 2]);
    $mailbox = Mailbox::factory()->create();

    $this->actingAs($regularUser)
        ->postJson(route('mailboxes.fetch-emails', $mailbox))
        ->assertStatus(403)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthorized access.',
        ]);
});

test('fetch emails returns error on imap failure', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->mock(ImapService::class, function ($mock) {
        $mock->shouldReceive('fetchEmails')
            ->once()
            ->andThrow(new \Exception('IMAP connection failed'));
    });

    $this->actingAs($admin)
        ->postJson(route('mailboxes.fetch-emails', $mailbox))
        ->assertStatus(500)
        ->assertJson(['success' => false])
        ->assertJsonFragment(['message' => 'Failed to fetch emails: IMAP connection failed']);
});

test('fetch emails with zero new emails', function () {
    $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    $mailbox = Mailbox::factory()->create();

    $this->mock(ImapService::class, function ($mock) {
        $mock->shouldReceive('fetchEmails')
            ->once()
            ->andReturn([
                'fetched' => 0,
                'created' => 0,
            ]);
    });

    $this->actingAs($admin)
        ->postJson(route('mailboxes.fetch-emails', $mailbox))
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'stats' => [
                'fetched' => 0,
                'created' => 0,
            ],
        ]);
});
