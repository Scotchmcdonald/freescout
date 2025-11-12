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
     */
    public function __construct(
        public User $user,
        public Conversation $conversation,
        public Collection $threads,
        public array $headers = [],
        public array $fromAddress = [],
        public Mailbox $mailbox
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = '[#'.$this->conversation->number.'] '.$this->conversation->subject;

        return new Envelope(
            subject: $subject,
            from: $this->fromAddress['address'] ?? config('mail.from.address'),
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

        $mail = $this->subject($subject)
            ->from($this->fromAddress['address'] ?? config('mail.from.address'), $this->fromAddress['name'] ?? config('mail.from.name'))
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
