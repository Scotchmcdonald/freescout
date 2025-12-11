<?php

namespace App\Notifications;

use App\Models\Conversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FollowUpReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Conversation $conversation
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->conversation->subject ?: '(No Subject)';
        $customerName = $this->conversation->customer?->getFullName() ?: 'Unknown Customer';
        $customerEmail = $this->conversation->customer_email;
        $followUpDate = $this->conversation->follow_up_date?->format('l, F j, Y');
        $conversationUrl = route('conversations.show', $this->conversation);

        return (new MailMessage)
            ->subject("⏰ Follow-up Reminder: #{$this->conversation->number}")
            ->greeting("Hello {$notifiable->first_name}!")
            ->line("This is a reminder to follow up on a conversation that requires your attention.")
            ->line('')
            ->line("**Conversation #:** {$this->conversation->number}")
            ->line("**Subject:** {$subject}")
            ->line("**Customer:** {$customerName} ({$customerEmail})")
            ->line("**Mailbox:** {$this->conversation->mailbox?->name}")
            ->line("**Follow-up Date:** {$followUpDate}")
            ->line('')
            ->action('View Conversation', $conversationUrl)
            ->line('Thank you for providing excellent customer support!');
    }

    /**
     * Get the array representation of the notification (for database storage).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'follow_up_reminder',
            'conversation_id' => $this->conversation->id,
            'conversation_number' => $this->conversation->number,
            'conversation_subject' => $this->conversation->subject,
            'customer_name' => $this->conversation->customer?->getFullName(),
            'customer_email' => $this->conversation->customer_email,
            'mailbox_id' => $this->conversation->mailbox_id,
            'mailbox_name' => $this->conversation->mailbox?->name,
            'follow_up_date' => $this->conversation->follow_up_date?->toISOString(),
            'message' => "Follow-up reminder for conversation #{$this->conversation->number}",
            'action_url' => route('conversations.show', $this->conversation),
        ];
    }
}
