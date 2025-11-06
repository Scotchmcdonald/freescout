<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Mailbox;
use App\Models\User;
use App\Models\Folder;
use Illuminate\Database\Seeder;

class MailboxSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        // Create support mailbox
        $supportMailbox = Mailbox::factory()->create([
            'name' => 'Support',
            'email' => 'support@example.com',
        ]);

        // Create sales mailbox
        $salesMailbox = Mailbox::factory()->create([
            'name' => 'Sales',
            'email' => 'sales@example.com',
        ]);

        // Attach users to mailboxes
        if ($users->isNotEmpty()) {
            $supportMailbox->users()->attach($users->pluck('id'));
            $salesMailbox->users()->attach($users->pluck('id'));
        }

        // Create default folders for each mailbox
        foreach ([$supportMailbox, $salesMailbox] as $mailbox) {
            Folder::factory()->create([
                'mailbox_id' => $mailbox->id,
                'user_id' => null,
                'type' => 1, // Inbox
                'name' => 'Inbox',
            ]);

            Folder::factory()->create([
                'mailbox_id' => $mailbox->id,
                'user_id' => null,
                'type' => 2, // Sent
                'name' => 'Sent',
            ]);

            Folder::factory()->create([
                'mailbox_id' => $mailbox->id,
                'user_id' => null,
                'type' => 3, // Drafts
                'name' => 'Drafts',
            ]);

            Folder::factory()->create([
                'mailbox_id' => $mailbox->id,
                'user_id' => null,
                'type' => 5, // Trash
                'name' => 'Trash',
            ]);
        }
    }
}
