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
        activity()
            ->withProperties([
                'ip' => request()->ip(),
                'email' => $event->request->input('email'),
            ])
            ->useLog(ActivityLog::NAME_USER)
            ->log(ActivityLog::DESCRIPTION_USER_LOCKED);
    }
}
