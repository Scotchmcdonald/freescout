<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\SendLog;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotificationToUsers implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Max retries + 1
     */
    public int $tries = 168; // One per hour

    /**
     * The number of seconds the job can run before timing out.
     * fwrite() function in Swift/Symfony Mailer may get stuck and continue infinitely.
     * This blocks queue:work and no other jobs are processed.
     * On timeout, the whole queue:work process is killed by Laravel.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Collection $users,
        public Conversation $conversation,
        public Collection $threads
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mailbox = $this->conversation->mailbox;

        if (! $mailbox) {
            Log::error('Mailbox not found for conversation', ['conversation_id' => $this->conversation->id]);

            return;
        }

        // Sort threads
        $this->threads = $this->threads->sortByDesc('created_at');

        $lastThread = $this->threads->first();

        if (! $lastThread) {
            return;
        }

        // If thread is draft, it means it has been undone
        if ($lastThread->state == Thread::STATE_DRAFT) {
            return;
        }

        // Do not send email notifications to support agents
        // if the email is a bounce and mail server send limit reached
        if ($lastThread->type == Thread::TYPE_BOUNCE 
            && str_contains($lastThread->body, 'message limit exceeded')
        ) {
            return;
        }

        // Limit conversation history based on configuration
        $historyConfig = config('app.email_user_history', 'full');
        if ($historyConfig == 'last') {
            $this->threads = $this->threads->slice(0, 2);
        } elseif ($historyConfig == 'none') {
            $this->threads = $this->threads->slice(0, 1);
        }

        // We throw an exception if any of the send attempts throws an exception
        $globalException = null;

        foreach ($this->users as $user) {
            // User can be deleted from DB
            if (! isset($user->id)) {
                continue;
            }

            // Skip deleted users
            if ($user->status == User::STATUS_DELETED) {
                continue;
            }

            // If for one user sending fails, the job is marked as failed and retried after some time
            // So we have to check if notification email has already been successfully sent to this user
            if ($this->attempts() > 1) {
                $alreadySent = SendLog::where('thread_id', $lastThread->id)
                    ->where('mail_type', SendLog::MAIL_TYPE_USER_NOTIFICATION)
                    ->where('user_id', $user->id)
                    ->whereIn('status', [SendLog::STATUS_ACCEPTED, SendLog::STATUS_SENT])
                    ->exists();

                if ($alreadySent) {
                    continue;
                }
            }

            $messageId = 'notification-'.$lastThread->id.'-'.$user->id.'-'.time().'@'.$mailbox->email;

            // Build headers
            $headers = [
                'Message-ID' => $messageId,
                'In-Reply-To' => '<notification-in-reply-'.$this->conversation->id.'-'.md5((string) $this->conversation->id).'@'.$mailbox->email.'>',
                'References' => '<notification-in-reply-'.$this->conversation->id.'-'.md5((string) $this->conversation->id).'@'.$mailbox->email.'>',
                'X-Auto-Response-Suppress' => 'All',
                'X-FreeScout-Mail-Type' => 'user.notification',
            ];

            // Set from name
            $fromName = $mailbox->name;
            if ($lastThread->type == Thread::TYPE_CUSTOMER && $lastThread->customer) {
                $customerName = $lastThread->customer->getFullName();
                if ($customerName) {
                    $fromName = $customerName.' via '.$mailbox->name;
                }
            }

            $from = ['address' => $mailbox->email, 'name' => $fromName];

            $exception = null;

            try {
                // Note: In a real implementation, this would use the UserNotification mailable
                // For now, we'll just log the action
                Log::info('Sending notification to user', [
                    'user_id' => $user->id,
                    'conversation_id' => $this->conversation->id,
                    'thread_id' => $lastThread->id,
                ]);

                // TODO: Uncomment when UserNotification mailable is implemented
                // Mail::to([['name' => $user->getFullNameAttribute(), 'email' => $user->email]])
                //     ->send(new UserNotification($user, $this->conversation, $this->threads, $headers, $from, $mailbox));
            } catch (\Exception $e) {
                Log::error('Error sending notification to user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                $exception = $e;
                $globalException = $e;
            }

            $statusMessage = '';
            if ($exception) {
                $status = SendLog::STATUS_SEND_ERROR;
                $statusMessage = $exception->getMessage();
            } else {
                $failures = Mail::failures();

                if (! empty($failures) && in_array($user->email, $failures)) {
                    $status = SendLog::STATUS_SEND_ERROR;
                } else {
                    $status = SendLog::STATUS_ACCEPTED;
                }
            }

            // Log the send attempt
            SendLog::create([
                'thread_id' => $lastThread->id,
                'message_id' => $messageId,
                'email' => $user->email,
                'mail_type' => SendLog::MAIL_TYPE_USER_NOTIFICATION,
                'status' => $status,
                'user_id' => $user->id,
                'status_message' => $statusMessage,
            ]);
        }

        if ($globalException) {
            // Retry job with delay
            // We do not try to resend Bounce messages
            if ($this->attempts() < $this->tries 
                && $lastThread->type != Thread::TYPE_BOUNCE
            ) {
                if ($this->attempts() == 1) {
                    // Second attempt after 5 min
                    $this->release(300);
                } else {
                    // Others - after 1 hour
                    $this->release(3600);
                }

                throw $globalException;
            } else {
                $this->fail($globalException);

                return;
            }
        }
    }

    /**
     * The job failed to process.
     * This method is called after attempts had finished.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendNotificationToUsers job failed', [
            'conversation_id' => $this->conversation->id,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }
}
