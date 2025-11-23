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

    public string $alert_message;
    public string $alert_subject;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $text,
        public string $title = ''
    ) {
        $this->alert_message = $text;
        $this->alert_subject = $title;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $appName = config('app.name');
        $subject = '['.(is_string($appName) ? $appName : '').'] ';
        if (! empty($this->title)) {
            $subject .= $this->title;
        } else {
            $subject .= 'Alert';
        }
        
        // Get domain from app URL
        $appUrl = config('app.url');
        $appUrl = is_string($appUrl) ? $appUrl : '';
        $domain = parse_url($appUrl, PHP_URL_HOST) ?: $appUrl;
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
