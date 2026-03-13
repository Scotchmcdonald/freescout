<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\PasswordReset;

class LogPasswordReset
{
    /**
     * Handle the event.
     */
    public function handle(PasswordReset $event): void
    {
        /** @var \App\Models\User $user */
        $user = $event->user;

        ActivityLog::record(
            description: ActivityLog::DESCRIPTION_USER_PASSWORD_RESET,
            logName: ActivityLog::NAME_USER,
            properties: ['ip' => request()->ip()],
            causer: $user,
        );
    }
}
