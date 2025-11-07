<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\AutoReply;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\SendLog;
use App\Models\Thread;
use App\Services\SmtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendAutoReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     * Laravel 11 uses timeout instead of $timeout property.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Conversation $conversation,
        public Thread $thread,
        public Mailbox $mailbox,
        public Customer $customer
    ) {}

    /**
     * Execute the job.
     * Matches original FreeScout implementation.
     */
    public function handle(SmtpService $smtpService): void
    {
        // Auto reply disabled via meta
        if (! empty($this->conversation->meta['ar_off'])) {
            Log::debug('Auto-reply disabled via meta', [
                'conversation_id' => $this->conversation->id,
            ]);

            return;
        }

        $customerEmail = $this->conversation->customer_email;

        if (! $customerEmail) {
            Log::warning('SendAutoReply job aborted: no customer email', [
                'conversation_id' => $this->conversation->id,
            ]);

            return;
        }

        Log::info('Executing SendAutoReply job', [
            'conversation_id' => $this->conversation->id,
            'mailbox_id' => $this->mailbox->id,
            'customer_email' => $customerEmail,
        ]);

        try {
            // Configure SMTP for the specific mailbox
            $smtpService->configureSmtp($this->mailbox);

            // Auto reply appears as reply in customer's mailbox
            $headers = [
                'In-Reply-To' => '<'.$this->thread->message_id.'>',
                'References' => '<'.$this->thread->message_id.'>',
            ];

            // Create Message-ID for the auto reply
            $atPos = strrchr($this->mailbox->email, '@');
            $domain = $atPos !== false ? substr($atPos, 1) : 'localhost';
            $messageId = 'auto-reply-'.$this->thread->id.'-'.
                \App\Misc\MailHelper::getMessageIdHash($this->thread->id).
                '@'.$domain;

            $headers['Message-ID'] = $messageId;

            $recipients = [$customerEmail];
            $failures = [];
            $exception = null;

            try {
                Mail::to([['name' => $this->customer->getFullName(), 'email' => $customerEmail]])
                    ->send(new AutoReply($this->conversation, $this->mailbox, $this->customer, $headers));

                Log::info('Auto-reply email sent successfully', [
                    'conversation_id' => $this->conversation->id,
                    'customer_email' => $customerEmail,
                ]);
            } catch (\Exception $e) {
                Log::error('SendAutoReply job failed', [
                    'conversation_id' => $this->conversation->id,
                    'mailbox_id' => $this->mailbox->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $failures = $recipients;
                $exception = $e;
            }

            // Log to send_logs table
            foreach ($recipients as $recipient) {
                $statusMessage = '';

                if ($exception) {
                    $status = 2; // SendLog::STATUS_SEND_ERROR
                    $statusMessage = $exception->getMessage();
                } else {
                    $mailFailures = Mail::failures();

                    if (! empty($mailFailures) && in_array($recipient, $mailFailures)) {
                        $status = 2; // SendLog::STATUS_SEND_ERROR
                    } else {
                        $status = 1; // SendLog::STATUS_ACCEPTED
                    }
                }

                $customerId = ($customerEmail == $recipient) ? $this->customer->id : null;

                SendLog::create([
                    'thread_id' => $this->thread->id,
                    'message_id' => $messageId,
                    'email' => $recipient,
                    'mail_type' => 3, // SendLog::MAIL_TYPE_AUTO_REPLY
                    'status' => $status,
                    'customer_id' => $customerId,
                    'status_message' => $statusMessage,
                ]);
            }

            if ($exception) {
                throw $exception;
            }
        } catch (\Exception $e) {
            Log::error('SendAutoReply job exception', [
                'conversation_id' => $this->conversation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendAutoReply job failed permanently', [
            'conversation_id' => $this->conversation->id,
            'customer_id' => $this->customer->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
