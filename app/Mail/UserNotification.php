<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class UserNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  Collection<int, \App\Models\Thread>  $threads
     * @param  array<string, string>  $headers
     * @param  array{address?: string, name?: string}  $fromAddress
     */
    public function __construct(
        public User $user,
        public Conversation $conversation,
        public Collection $threads,
        public Mailbox $mailbox,
        public array $headers = [],
        public array $fromAddress = []
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = '[#'.$this->conversation->number.'] '.$this->conversation->subject;
        $configFrom = config('mail.from.address');
        $fromAddress = $this->fromAddress['address'] ?? (is_string($configFrom) ? $configFrom : '');

        return new Envelope(
            subject: $subject,
            from: $fromAddress,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $customer = $this->conversation->customer;
        $thread = $this->threads->first();

        return new Content(
            view: 'emails.user.notification',
            text: 'emails.user.notification_text',
            with: [
                'customer' => $customer,
                'thread' => $thread,
                'mailbox' => $this->mailbox,
            ],
        );
    }

    /**
     * Build the message (for custom headers and custom from).
     */
    public function build(): self
    {
        $subject = '[#'.$this->conversation->number.'] '.$this->conversation->subject;
        $customer = $this->conversation->customer;
        $thread = $this->threads->first();

        $configFrom = config('mail.from.address');
        $fromAddress = $this->fromAddress['address'] ?? (is_string($configFrom) ? $configFrom : '');

        $configName = config('mail.from.name');
        $fromName = $this->fromAddress['name'] ?? (is_string($configName) ? $configName : '');

        $mail = $this->subject($subject)
            ->from($fromAddress, $fromName)
            ->view('emails.user.notification', [
                'customer' => $customer,
                'thread' => $thread,
                'mailbox' => $this->mailbox,
                'conversation' => $this->conversation,
                'threads' => $this->threads,
                'user' => $this->user,
                'headers' => $this->headers,
            ])
            ->text('emails.user.notification_text', [
                'customer' => $customer,
                'thread' => $thread,
                'mailbox' => $this->mailbox,
                'conversation' => $this->conversation,
                'threads' => $this->threads,
                'user' => $this->user,
            ]);

        // Set custom headers
        if (! empty($this->headers)) {
            $mail->withSymfonyMessage(function ($symfonyMessage) {
                $symfonyHeaders = $symfonyMessage->getHeaders();

                // Set Message-ID if provided
                if (! empty($this->headers['Message-ID'])) {
                    $symfonyHeaders->addIdHeader('Message-ID', $this->headers['Message-ID']);
                }

                // Add other custom headers
                foreach ($this->headers as $header => $value) {
                    if ($header !== 'Message-ID') {
                        $symfonyHeaders->addTextHeader($header, $value);
                    }
                }
            });
        }

        return $mail;
    }
}
