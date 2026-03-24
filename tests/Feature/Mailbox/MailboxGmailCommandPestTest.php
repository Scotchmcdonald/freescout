<?php

declare(strict_types=1);

use App\Models\Mailbox;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('command runs successfully with valid inputs', function () {
    $mailbox = Mailbox::factory()->create([
        'name' => 'Test Support',
    ]);

    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
        ->expectsQuestion('Enter your Gmail address', 'test@gmail.com')
        ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'test-app-password')
        ->expectsOutput('✓ Mailbox configured successfully!')
        ->assertExitCode(0);

    $mailbox->refresh();
    expect($mailbox->email)->toBe('test@gmail.com')
        ->and($mailbox->out_server)->toBe('smtp.gmail.com')
        ->and($mailbox->out_port)->toBe(587)
        ->and($mailbox->out_username)->toBe('test@gmail.com')
        ->and($mailbox->out_encryption)->toBe(2) // TLS
        ->and($mailbox->in_server)->toBe('imap.gmail.com')
        ->and($mailbox->in_port)->toBe(993)
        ->and($mailbox->in_username)->toBe('test@gmail.com')
        ->and($mailbox->in_encryption)->toBe(1) // SSL
        ->and($mailbox->in_protocol)->toBe(1) // IMAP
        ->and($mailbox->in_validate_cert)->toBeTrue()
        ->and($mailbox->out_password)->toBe('test-app-password')
        ->and($mailbox->in_password)->toBe('test-app-password');
});

test('command uses default mailbox id when not provided', function () {
    $mailbox = Mailbox::factory()->create(['id' => 1]);

    $this->artisan('mailbox:configure-gmail')
        ->expectsQuestion('Enter your Gmail address', 'default@gmail.com')
        ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password123')
        ->assertExitCode(0);

    $mailbox->refresh();
    expect($mailbox->email)->toBe('default@gmail.com');
});

test('command fails with invalid mailbox id', function () {
    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => 999])
        ->expectsOutput('Mailbox with ID 999 not found!')
        ->assertExitCode(1);
});

test('command rejects invalid email address', function () {
    $mailbox = Mailbox::factory()->create();

    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
        ->expectsQuestion('Enter your Gmail address', 'not-an-email')
        ->expectsOutput('Invalid email address!')
        ->assertExitCode(1);

    $mailbox->refresh();
    expect($mailbox->email)->not->toBe('not-an-email');
});

test('command rejects empty password', function () {
    $mailbox = Mailbox::factory()->create();
    $originalPassword = $mailbox->out_password;

    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
        ->expectsQuestion('Enter your Gmail address', 'valid@gmail.com')
        ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', '')
        ->expectsOutput('App Password is required!')
        ->assertExitCode(1);

    // Verify password was not updated
    $mailbox->refresh();
    expect($mailbox->out_password)->toBe($originalPassword);
});

test('command displays configuration summary', function () {
    $mailbox = Mailbox::factory()->create([
        'name' => 'Customer Support',
    ]);

    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
        ->expectsQuestion('Enter your Gmail address', 'support@gmail.com')
        ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'app-pass-16-chars')
        ->expectsOutput('Configuring mailbox: Customer Support (ID: '.$mailbox->id.')')
        ->expectsOutputToContain('smtp.gmail.com:587 (TLS)')
        ->expectsOutputToContain('imap.gmail.com:993 (SSL)')
        ->assertExitCode(0);
});

test('command shows app password instructions', function () {
    $mailbox = Mailbox::factory()->create();

    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
        ->expectsQuestion('Enter your Gmail address', 'test@gmail.com')
        ->expectsOutputToContain('IMPORTANT: You need a Gmail App Password')
        ->expectsOutputToContain('https://myaccount.google.com/apppasswords')
        ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
        ->assertExitCode(0);
});

test('command shows next steps after configuration', function () {
    $mailbox = Mailbox::factory()->create();

    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
        ->expectsQuestion('Enter your Gmail address', 'test@gmail.com')
        ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
        ->expectsOutputToContain('Next steps:')
        ->expectsOutputToContain('php artisan freescout:fetch-emails')
        ->assertExitCode(0);
});

test('command accepts various email formats', function () {
    // Standard Gmail
    $mailbox1 = Mailbox::factory()->create();
    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox1->id])
        ->expectsQuestion('Enter your Gmail address', 'user@gmail.com')
        ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
        ->assertExitCode(0);

    // Google Workspace
    $mailbox2 = Mailbox::factory()->create();
    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox2->id])
        ->expectsQuestion('Enter your Gmail address', 'admin@company.com')
        ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
        ->assertExitCode(0);

    // Dots in username
    $mailbox3 = Mailbox::factory()->create();
    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox3->id])
        ->expectsQuestion('Enter your Gmail address', 'first.last@gmail.com')
        ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
        ->assertExitCode(0);

    $mailbox1->refresh();
    expect($mailbox1->email)->toBe('user@gmail.com');

    $mailbox2->refresh();
    expect($mailbox2->email)->toBe('admin@company.com');

    $mailbox3->refresh();
    expect($mailbox3->email)->toBe('first.last@gmail.com');
});

test('command updates both incoming and outgoing settings', function () {
    $mailbox = Mailbox::factory()->create([
        'out_server' => 'old-smtp.example.com',
        'in_server' => 'old-imap.example.com',
    ]);

    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
        ->expectsQuestion('Enter your Gmail address', 'new@gmail.com')
        ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'new-password')
        ->assertExitCode(0);

    $mailbox->refresh();

    // Outgoing (SMTP)
    expect($mailbox->out_server)->toBe('smtp.gmail.com')
        ->and($mailbox->out_port)->toBe(587)
        ->and($mailbox->out_username)->toBe('new@gmail.com')
        ->and($mailbox->out_encryption)->toBe(2); // TLS

    // Incoming (IMAP)
    expect($mailbox->in_server)->toBe('imap.gmail.com')
        ->and($mailbox->in_port)->toBe(993)
        ->and($mailbox->in_username)->toBe('new@gmail.com')
        ->and($mailbox->in_encryption)->toBe(1) // SSL
        ->and($mailbox->in_protocol)->toBe(1) // IMAP
        ->and($mailbox->in_validate_cert)->toBeTrue();
});

test('command handles email with special characters', function () {
    $mailbox = Mailbox::factory()->create();

    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
        ->expectsQuestion('Enter your Gmail address', 'user+tag@gmail.com')
        ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
        ->assertExitCode(0);

    $mailbox->refresh();
    expect($mailbox->email)->toBe('user+tag@gmail.com');
});

test('command accepts password with whitespace', function () {
    $mailbox = Mailbox::factory()->create();

    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
        ->expectsQuestion('Enter your Gmail address', 'test@gmail.com')
        ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', '   ')
        ->assertExitCode(0);

    $mailbox->refresh();
    expect($mailbox->out_password)->toBe('   ');
});

test('command handles non-numeric mailbox id gracefully', function () {
    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => 'abc'])
        ->expectsOutput('Mailbox with ID abc not found!')
        ->assertExitCode(1);
});

test('command preserves mailbox name', function () {
    $mailbox = Mailbox::factory()->create([
        'name' => 'Important Support Mailbox',
    ]);

    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
        ->expectsQuestion('Enter your Gmail address', 'test@gmail.com')
        ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
        ->assertExitCode(0);

    $mailbox->refresh();
    expect($mailbox->name)->toBe('Important Support Mailbox');
});

test('command handles uppercase email address', function () {
    $mailbox = Mailbox::factory()->create();

    $this->artisan('mailbox:configure-gmail', ['mailbox_id' => $mailbox->id])
        ->expectsQuestion('Enter your Gmail address', 'User@Gmail.COM')
        ->expectsQuestion('Enter your Gmail App Password (input will be hidden)', 'password')
        ->assertExitCode(0);

    $mailbox->refresh();
    expect($mailbox->email)->toBe('User@Gmail.COM');
});
