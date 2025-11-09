<?php

namespace App\Console\Commands;

use App\Models\Mailbox;
use Illuminate\Console\Command;

class ConfigureGmailMailbox extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailbox:configure-gmail {mailbox_id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure a mailbox with Gmail SMTP/IMAP settings';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $mailboxId = $this->argument('mailbox_id');

        if (! is_numeric($mailboxId)) {
            $this->error("Mailbox with ID {$mailboxId} not found!");

            return 1;
        }

        // Ensure mailboxId is an integer
        $mailboxIdInt = (int) $mailboxId;

        /** @var \App\Models\Mailbox|null $mailbox */
        $mailbox = Mailbox::find($mailboxIdInt);

        if (! $mailbox) {
            $this->error("Mailbox with ID {$mailboxIdInt} not found!");

            return 1;
        }

        $this->info("Configuring mailbox: {$mailbox->name} (ID: {$mailbox->id})");
        $this->newLine();

        // Get Gmail address
        $gmailAddress = $this->ask('Enter your Gmail address');

        if (! filter_var($gmailAddress, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address!');

            return 1;
        }

        // Instructions for App Password
        $this->newLine();
        $this->warn('IMPORTANT: You need a Gmail App Password (not your regular password)');
        $this->info('To generate one:');
        $this->info('1. Go to: https://myaccount.google.com/apppasswords');
        $this->info('2. Enable 2-Factor Authentication if not already enabled');
        $this->info('3. Generate an app password for "Mail"');
        $this->info('4. Copy the 16-character password (without spaces)');
        $this->newLine();

        // Get App Password
        $appPassword = $this->secret('Enter your Gmail App Password (input will be hidden)');

        if (empty($appPassword)) {
            $this->error('App Password is required!');

            return 1;
        }

        // Update mailbox
        $mailbox->update([
            'email' => $gmailAddress,
            'out_server' => 'smtp.gmail.com',
            'out_port' => 587,
            'out_username' => $gmailAddress,
            'out_password' => $appPassword,
            'out_encryption' => 2, // TLS
            'in_server' => 'imap.gmail.com',
            'in_port' => 993,
            'in_username' => $gmailAddress,
            'in_password' => $appPassword,
            'in_protocol' => 1, // IMAP
            'in_encryption' => 1, // SSL
            'in_validate_cert' => true,
        ]);

        $this->newLine();
        $this->info('✓ Mailbox configured successfully!');
        $this->newLine();
        $this->table(
            ['Setting', 'Value'],
            [
                ['Mailbox ID', $mailbox->id],
                ['Mailbox Name', $mailbox->name],
                ['Email', $mailbox->email],
                ['SMTP Server', 'smtp.gmail.com:587 (TLS)'],
                ['IMAP Server', 'imap.gmail.com:993 (SSL)'],
            ]
        );

        $this->newLine();
        $this->info('Next steps:');
        $this->info('1. Test the connection:');
        $this->comment('   php artisan freescout:fetch-emails 1 --test');
        $this->newLine();
        $this->info('2. Or access via web interface:');
        $this->comment('   - Start server: php artisan serve');
        $this->comment('   - Login: admin@freescout.local / password');
        $this->comment('   - Navigate to: http://localhost:8000/mailbox/1/settings');

        return 0;
    }
}
