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
        activity()
            ->causedBy($event->by_user)
            ->withProperties([
                'deleted_user' => $event->deleted_user->getFullName() . ' [' . $event->deleted_user->id . ']',
            ])
            ->useLog(ActivityLog::NAME_USER)
            ->log(ActivityLog::DESCRIPTION_USER_DELETED);
    }
}
