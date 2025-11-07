<?php

namespace Database\Seeders;

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmailTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $user = User::firstOrCreate(
            ['email' => 'admin@freescout.local'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'password' => Hash::make('password'),
                'role' => 1, // Admin
                'status' => 1, // Active
                'type' => 1, // User
            ]
        );

        $this->command->info("Created/found admin user: {$user->email}");
        $this->command->info("Password: password");

        // Create test mailbox
        $mailbox = Mailbox::firstOrCreate(
            ['email' => 'support@example.com'],
            [
                'name' => 'Support',
                'out_method' => 3, // SMTP
                'out_server' => '',
                'out_port' => 587,
                'out_username' => '',
                'out_password' => '',
                'out_encryption' => 2, // TLS
                'in_server' => '',
                'in_port' => 993,
                'in_username' => '',
                'in_password' => '',
                'in_protocol' => 1, // IMAP
                'in_encryption' => 1, // SSL
                'in_validate_cert' => true,
            ]
        );

        $this->command->info("Created/found mailbox: {$mailbox->name} (ID: {$mailbox->id})");

        // Create folders
        $folderTypes = [
            ['type' => 1, 'name' => 'Inbox'],
            ['type' => 2, 'name' => 'Drafts'],
            ['type' => 3, 'name' => 'Assigned'],
            ['type' => 4, 'name' => 'Sent'],
            ['type' => 5, 'name' => 'Trash'],
            ['type' => 100, 'name' => 'Spam'],
        ];

        foreach ($folderTypes as $folderData) {
            $folder = Folder::firstOrCreate(
                [
                    'mailbox_id' => $mailbox->id,
                    'type' => $folderData['type'],
                ],
                [
                    'name' => $folderData['name'],
                ]
            );
            $this->command->info("Created/found folder: {$folder->name}");
        }

        // Attach user to mailbox
        if (!$mailbox->users()->wherePivot('user_id', $user->id)->exists()) {
            $mailbox->users()->attach($user->id);
            $this->command->info("Attached user to mailbox");
        }

        $this->command->info('');
        $this->command->info('✓ Seeding complete!');
        $this->command->info('');
        $this->command->info('Next steps:');
        $this->command->info('1. Update mailbox settings with your Gmail credentials');
        $this->command->info('2. Login: admin@freescout.local / password');
        $this->command->info('3. Navigate to: /mailbox/1/settings');
    }
}
