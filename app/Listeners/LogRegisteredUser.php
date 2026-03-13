<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Registered;

class LogRegisteredUser
{
    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        /** @var \App\Models\User $user */
        $user = $event->user;

        ActivityLog::record(
            description: ActivityLog::DESCRIPTION_USER_REGISTER,
            logName: ActivityLog::NAME_USER,
            properties: ['ip' => request()->ip()],
            causer: $user,
        );
    }
}
