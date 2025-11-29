<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Database\Seeder;

class MailboxSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        // Create support mailbox
        $supportMailbox = Mailbox::firstOrCreate(
            ['email' => 'support@example.com'],
            ['name' => 'Support']
        );

        // Create sales mailbox
        $salesMailbox = Mailbox::firstOrCreate(
            ['email' => 'sales@example.com'],
            ['name' => 'Sales']
        );

        // Attach users to mailboxes
        if ($users->isNotEmpty()) {
            $supportMailbox->users()->syncWithoutDetaching($users->pluck('id'));
            $salesMailbox->users()->syncWithoutDetaching($users->pluck('id'));
        }

        // Create default folders for each mailbox
        foreach ([$supportMailbox, $salesMailbox] as $mailbox) {
            Folder::firstOrCreate(
                ['mailbox_id' => $mailbox->id, 'type' => 1],
                ['user_id' => null, 'name' => 'Inbox']
            );

            Folder::firstOrCreate(
                ['mailbox_id' => $mailbox->id, 'type' => 2],
                ['user_id' => null, 'name' => 'Sent']
            );

            Folder::firstOrCreate(
                ['mailbox_id' => $mailbox->id, 'type' => 3],
                ['user_id' => null, 'name' => 'Drafts']
            );

            Folder::firstOrCreate(
                ['mailbox_id' => $mailbox->id, 'type' => 5],
                ['user_id' => null, 'name' => 'Trash']
            );
        }
    }
}
