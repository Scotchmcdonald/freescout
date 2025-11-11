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
        activity()
            ->withProperties([
                'ip' => request()->ip(),
                'email' => request()->input('email'),
            ])
            ->useLog(ActivityLog::NAME_USER)
            ->log(ActivityLog::DESCRIPTION_USER_LOGIN_FAILED);
    }
}
