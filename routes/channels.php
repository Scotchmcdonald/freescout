<?php

use App\Models\Conversation;
use App\Models\Mailbox;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// User's private channel for notifications
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return (int) $user->id === $userId;
});

// Mailbox channel - user must have access to the mailbox
Broadcast::channel('mailbox.{mailboxId}', function (User $user, int $mailboxId) {
    return $user->mailboxes()->where('mailboxes.id', $mailboxId)->exists();
});

// Conversation presence channel - user must have access to the mailbox
Broadcast::channel('conversation.{conversationId}', function (User $user, int $conversationId) {
    $conversation = Conversation::find($conversationId);
    
    if (! $conversation) {
        return false;
    }

    // Check if user has access to the conversation's mailbox
    return $user->mailboxes()->where('mailboxes.id', $conversation->mailbox_id)->exists()
        ? ['id' => $user->id, 'name' => $user->getFullName(), 'email' => $user->email]
        : false;
});

