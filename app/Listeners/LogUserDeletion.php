<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserDeleted;
use App\Models\ActivityLog;

class LogUserDeletion
{
    /**
     * Handle the event.
     */
    public function handle(UserDeleted $event): void
    {
        ActivityLog::record(
            description: ActivityLog::DESCRIPTION_USER_DELETED,
            logName: ActivityLog::NAME_USER,
            properties: [
                'deleted_user' => $event->deleted_user->getFullName().' ['.$event->deleted_user->id.']',
            ],
            causer: $event->by_user,
        );
    }
}
