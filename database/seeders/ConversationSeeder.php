<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Folder;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    public function run(): void
    {
        $mailboxes = Mailbox::all();
        $users = User::all();
        $customers = Customer::all();

        if ($mailboxes->isEmpty() || $users->isEmpty() || $customers->isEmpty()) {
            $this->command->warn('Please run UserSeeder, MailboxSeeder, and CustomerSeeder first.');
            return;
        }

        foreach ($mailboxes as $mailbox) {
            $inboxFolder = $mailbox->folders()->where('type', 1)->first();

            if (!$inboxFolder) {
                continue;
            }

            // Create 10 conversations per mailbox
            for ($i = 0; $i < 10; $i++) {
                $customer = $customers->random();
                $user = $users->random();
                $isClosed = fake()->boolean(30);

                $conversation = Conversation::factory()->create([
                    'mailbox_id' => $mailbox->id,
                    'folder_id' => $inboxFolder->id,
                    'user_id' => $user->id,
                    'customer_id' => $customer->id,
                    'customer_email' => $customer->emails->first()?->email ?? fake()->email(),
                    'status' => $isClosed ? 3 : 1,
                    'closed_by_user_id' => $isClosed ? $user->id : null,
                    'closed_at' => $isClosed ? now() : null,
                ]);

                // Create initial customer message
                Thread::factory()->fromCustomer()->create([
                    'conversation_id' => $conversation->id,
                    'customer_id' => $customer->id,
                ]);

                // Create 1-3 reply threads
                $replyCount = fake()->numberBetween(1, 3);
                for ($j = 0; $j < $replyCount; $j++) {
                    Thread::factory()->create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $user->id,
                    ]);

                    // Sometimes customer replies back
                    if (fake()->boolean(60)) {
                        Thread::factory()->fromCustomer()->create([
                            'conversation_id' => $conversation->id,
                            'customer_id' => $customer->id,
                        ]);
                    }
                }

                // Update threads count
                $conversation->update([
                    'threads_count' => $conversation->threads()->count(),
                ]);
            }
        }
    }
}
