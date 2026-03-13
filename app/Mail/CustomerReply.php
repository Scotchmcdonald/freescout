<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Conversation;
use App\Models\Thread;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class CustomerReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Conversation $conversation,
        public Thread $thread
    ) {}

    public function envelope(): Envelope
    {
        /** @var \App\Models\Mailbox $mailbox */
        $mailbox = $this->conversation->mailbox;

        return new Envelope(
            subject: 'Re: '.$this->conversation->subject,
            from: $mailbox->email,
            replyTo: [$mailbox->email],
        );
    }

    public function content(): Content
    {
        // Generate tracking pixel URL
        $trackingUrl = URL::signedRoute('track.pixel', ['id' => $this->thread->id]);

        // Process body to replace internal attachment links with public ones
        $body = $this->thread->body;

        if ($body) {
            $body = preg_replace_callback(
                '/(src|href)=["\'](?:[^"\']*)attachments\/(\d+)\/download(?:[^"\']*)["\']/',
                function ($matches) {
                    $attr = $matches[1];
                    $id = $matches[2];
                    $url = URL::signedRoute('attachments.public_download', ['id' => $id]);

                    return $attr.'="'.$url.'"';
                },
                $body
            );
        }

        return new Content(
            view: 'emails.customer.reply',
            with: [
                'body' => $body,
                'trackingUrl' => $trackingUrl,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        foreach ($this->thread->attachments as $attachment) {
            // Attach file from storage
            if ($attachment->file_dir && $attachment->file_name) {
                $path = storage_path('app/'.$attachment->file_dir.'/'.$attachment->file_name);
                if (file_exists($path)) {
                    $attachments[] = \Illuminate\Mail\Mailables\Attachment::fromPath($path)
                        ->as($attachment->file_name)
                        ->withMime($attachment->mime_type ?? 'application/octet-stream');
                }
            }
        }

        return $attachments;
    }
}
