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
        /** @var \App\Models\User $user */
        $user = $event->user;

        $properties = ['ip' => request()->ip()];
        
        // For OAuth logins, include the email in the log
        if ($user->email) {
            $properties['email'] = 'OAuth: ' . $user->email;
        }

        activity()
            ->causedBy($user)
            ->withProperties($properties)
            ->useLog(ActivityLog::NAME_USER)
            ->log(ActivityLog::DESCRIPTION_USER_LOGIN);
    }
}
