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
        /** @phpstan-ignore-next-line */
        if (method_exists($event->user, 'sendPasswordChanged')) {
            $event->user->sendPasswordChanged();
        }
    }
}
