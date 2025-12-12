<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\PasswordReset;

class SendPasswordChanged
{
    /**
     * Handle the event.
     *
     * Send email to user when their password changes.
     */
    public function handle(PasswordReset $event): void
    {
        /** @var \App\Models\User $user */
        $user = $event->user;
        
        // User model has sendPasswordChanged method
        $user->sendPasswordChanged();
    }
}
