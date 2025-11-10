<?php

namespace App\Observers;

use Illuminate\Notifications\DatabaseNotification;

class DatabaseNotificationObserver
{
    /**
     * Notifications DB record created.
     */
    public function created(DatabaseNotification $notification)
    {
        $notification->notifiable->clearWebsiteNotificationsCache();
    }
}
