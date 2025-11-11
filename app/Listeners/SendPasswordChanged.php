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
        if (method_exists($event->user, 'sendPasswordChanged')) {
            $event->user->sendPasswordChanged();
        }
    }
}
