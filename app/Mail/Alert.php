<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Alert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $text,
        public string $title = ''
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = '['.config('app.name').'] ';
        if (! empty($this->title)) {
            $subject .= $this->title;
        } else {
            $subject .= 'Alert';
        }
        
        // Get domain from app URL
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?: config('app.url');
        $subject .= ' - '.$domain;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.user.alert',
        );
    }
}
