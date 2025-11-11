<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class RememberUserLocale
{
    /**
     * Handle the event.
     *
     * Save user locale to session to show user app in their chosen language.
     */
    public function handle(Login $event): void
    {
        if (method_exists($event->user, 'getLocale')) {
            session()->put('user_locale', $event->user->getLocale());
        }
    }
}
