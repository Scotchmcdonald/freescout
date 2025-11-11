<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        activity()
            ->causedBy($event->user)
            ->withProperties(['ip' => request()->ip()])
            ->useLog(ActivityLog::NAME_USER)
            ->log(ActivityLog::DESCRIPTION_USER_LOGIN);
    }
}
