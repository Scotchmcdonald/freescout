<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        ActivityLog::record(
            description: ActivityLog::DESCRIPTION_USER_LOGIN_FAILED,
            logName: ActivityLog::NAME_USER,
            properties: [
                'ip' => request()->ip(),
                'email' => request()->input('email'),
            ],
        );
    }
}
