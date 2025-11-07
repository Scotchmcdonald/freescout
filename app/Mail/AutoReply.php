<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AutoReply extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Conversation $conversation,
        public Mailbox $mailbox,
        public Customer $customer,
        public array $headers = []
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->mailbox->auto_reply_subject ?: 'Re: '.$this->conversation->subject;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $message = $this->mailbox->auto_reply_message ?:
            'We have received your message and will get back to you shortly.';

        return new Content(
            text: 'emails.auto-reply',
            with: [
                'message' => $message,
                'conversation' => $this->conversation,
                'mailbox' => $this->mailbox,
                'customer' => $this->customer,
            ],
        );
    }

    /**
     * Build the message (for custom headers).
     */
    public function build(): self
    {
        $subject = $this->mailbox->auto_reply_subject ?: 'Re: '.$this->conversation->subject;
        $message = $this->mailbox->auto_reply_message ?:
            'We have received your message and will get back to you shortly.';

        $mail = $this->subject($subject)
            ->text('emails.auto-reply', [
                'message' => $message,
                'conversation' => $this->conversation,
                'mailbox' => $this->mailbox,
                'customer' => $this->customer,
            ]);

        // Set custom headers
        if (! empty($this->headers)) {
            $mail->withSymfonyMessage(function ($symfonyMessage) {
                $symfonyHeaders = $symfonyMessage->getHeaders();

                foreach ($this->headers as $header => $value) {
                    if ($header === 'Message-ID') {
                        // Message-ID is handled specially
                        continue;
                    }
                    $symfonyHeaders->addTextHeader($header, $value);
                }
            });
        }

        return $mail;
    }
}
