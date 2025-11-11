<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Mailbox;
use App\Models\SendLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * User replied from wrong email address to the email notification.
 */
class SendEmailReplyError implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $from,
        public User $user,
        public Mailbox $mailbox
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $exception = null;

        try {
            // Note: In a real implementation, this would use the UserEmailReplyError mailable
            // For now, we'll just log the action
            Log::info('Sending email reply error notification', [
                'from' => $this->from,
                'user_id' => $this->user->id,
                'mailbox_id' => $this->mailbox->id,
            ]);

            // TODO: Uncomment when UserEmailReplyError mailable is implemented
            // Mail::to([['name' => '', 'email' => $this->from]])
            //     ->send(new UserEmailReplyError());
        } catch (\Exception $e) {
            Log::error('Error sending email reply error notification', [
                'from' => $this->from,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $exception = $e;
        }

        $statusMessage = '';
        if ($exception) {
            $status = SendLog::STATUS_SEND_ERROR;
            $statusMessage = $exception->getMessage();
        } else {
            $failures = Mail::failures();

            if (! empty($failures)) {
                $status = SendLog::STATUS_SEND_ERROR;
            } else {
                $status = SendLog::STATUS_ACCEPTED;
            }
        }

        // Log the send attempt
        SendLog::create([
            'thread_id' => null,
            'message_id' => null,
            'email' => $this->from,
            'mail_type' => SendLog::MAIL_TYPE_WRONG_USER_EMAIL_MESSAGE,
            'status' => $status,
            'user_id' => $this->user->id,
            'status_message' => $statusMessage,
        ]);

        if ($exception) {
            throw $exception;
        }
    }
}
