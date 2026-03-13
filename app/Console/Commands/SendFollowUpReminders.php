<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Notifications\FollowUpReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendFollowUpReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'followup:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send follow-up reminders for conversations that are due';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Checking for conversations with due follow-up reminders...');
        $this->newLine();

        // Get conversations with due follow-ups that haven't been reminded yet
        $conversations = Conversation::whereNotNull('follow_up_date')
            ->where('follow_up_date', '<=', now())
            ->whereNull('follow_up_reminded_at')
            ->whereIn('status', [Conversation::STATUS_ACTIVE, Conversation::STATUS_PENDING])
            ->with(['user', 'mailbox', 'customer'])
            ->get();

        if ($conversations->isEmpty()) {
            $this->info('✓ No conversations with due follow-ups found.');

            return Command::SUCCESS;
        }

        $count = $conversations->count();
        $this->info("📋 Found {$count} conversation(s) with due follow-ups.");
        $this->newLine();

        $sentCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        foreach ($conversations as $conversation) {
            try {
                // Ensure conversation has an assigned user
                if (! $conversation->user) {
                    $this->warn("⚠️  Conversation #{$conversation->number} has no assigned user. Skipping.");
                    $skippedCount++;
                    continue;
                }

                // Send notification to the assigned user
                $conversation->user->notify(new FollowUpReminderNotification($conversation));

                // Mark as reminded
                $conversation->update(['follow_up_reminded_at' => now()]);

                $this->line("✓ Sent reminder for conversation #{$conversation->number} to {$conversation->user->email}");
                $sentCount++;

                Log::info('Follow-up reminder sent', [
                    'conversation_id' => $conversation->id,
                    'conversation_number' => $conversation->number,
                    'user_id' => $conversation->user->id,
                    'user_email' => $conversation->user->email,
                    'follow_up_date' => $conversation->follow_up_date?->toDateTimeString(),
                ]);
            } catch (\Exception $e) {
                $this->error("✗ Failed to send reminder for conversation #{$conversation->number}: {$e->getMessage()}");
                $errorCount++;

                Log::error('Failed to send follow-up reminder', [
                    'conversation_id' => $conversation->id,
                    'conversation_number' => $conversation->number,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->newLine();
        $this->info('📊 Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['✓ Sent', $sentCount],
                ['⚠ Skipped', $skippedCount],
                ['✗ Errors', $errorCount],
                ['Total', $count],
            ]
        );

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
