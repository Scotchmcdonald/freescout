<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Lockout;

class LogLockout
{
    /**
     * Handle the event.
     */
    public function handle(Lockout $event): void
    {
        ActivityLog::record(
            description: ActivityLog::DESCRIPTION_USER_LOCKED,
            logName: ActivityLog::NAME_USER,
            properties: [
                'ip' => request()->ip(),
                'email' => $event->request->input('email'),
            ],
        );
    }
}
