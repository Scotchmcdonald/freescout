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
            $properties['email'] = 'OAuth: '.$user->email;
        }

        ActivityLog::record(
            description: ActivityLog::DESCRIPTION_USER_LOGIN,
            logName: ActivityLog::NAME_USER,
            properties: $properties,
            causer: $user,
        );
    }
}
