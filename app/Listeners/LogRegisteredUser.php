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
        activity()
            ->causedBy($event->user)
            ->withProperties(['ip' => request()->ip()])
            ->useLog(ActivityLog::NAME_USER)
            ->log(ActivityLog::DESCRIPTION_USER_REGISTER);
    }
}
