<?php

declare(strict_types=1);

namespace App\Jobs;

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
 * Send alert to super admin.
 */
class SendAlert implements ShouldQueue
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
        public string $text,
        public string $title = ''
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get all activated admin users
        $recipients = User::where('role', User::ROLE_ADMIN)
            ->where('status', User::STATUS_ACTIVE)
            ->where('invite_state', User::INVITE_STATE_ACTIVATED ?? 1)
            ->pluck('email')
            ->toArray();

        // Add extra recipients from configuration if available
        // Note: This would need Option model to be implemented
        // $extra = \App\Models\Option::get('alert_recipients');
        // if ($extra) {
        //     $recipients = array_unique(array_merge($recipients, $extra));
        // }

        $exception = null;

        foreach ($recipients as $recipient) {
            $exception = null;

            try {
                // Note: In a real implementation, this would use the Alert mailable
                // For now, we'll just log the action
                Log::info('Sending alert email', [
                    'recipient' => $recipient,
                    'title' => $this->title,
                    'text' => substr($this->text, 0, 100),
                ]);

                // TODO: Uncomment when Alert mailable is implemented
                // Mail::to([['name' => '', 'email' => $recipient]])
                //     ->send(new Alert($this->text, $this->title));
            } catch (\Exception $e) {
                Log::error('Error sending alert email', [
                    'recipient' => $recipient,
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
                'email' => $recipient,
                'mail_type' => SendLog::MAIL_TYPE_ALERT,
                'status' => $status,
                'user_id' => null,
                'status_message' => $statusMessage,
            ]);
        }

        if ($exception) {
            throw $exception;
        }
    }
}
