<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Mailbox;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Test extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Mailbox $mailbox
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $appName = config('app.name');
        $appName = is_string($appName) ? $appName : '';
        return new Envelope(
            subject: __('Test Email From :app_name', ['app_name' => $appName]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.user.test',
        );
    }
}
