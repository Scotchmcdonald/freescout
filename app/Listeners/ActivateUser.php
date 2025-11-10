<?php

namespace App\Listeners;

use App\User;
use Illuminate\Auth\Events\Login;

class ActivateUser
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     *
     * @return void
     */
    public function handle(Login $event)
    {
        if ($event->user->invite_state != User::INVITE_STATE_ACTIVATED) {
            $event->user->invite_state = User::INVITE_STATE_ACTIVATED;
            $event->user->invite_hash = '';
            $event->user->save();
        }
    }
}
