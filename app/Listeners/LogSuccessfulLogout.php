<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        /** @var \App\Models\User $user */
        $user = $event->user;

        activity()
            ->causedBy($user)
            ->withProperties(['ip' => request()->ip()])
            ->useLog(ActivityLog::NAME_USER)
            ->log(ActivityLog::DESCRIPTION_USER_LOGOUT);
    }
}
