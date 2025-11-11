<?php

namespace App\Listeners;

use App\Events\NewMessageReceived;

class HandleNewMessage
{
    /**
     * Create the event listener.
     */
    public function __construct() {}

    /**
     * Handle the event.
     */
    public function handle(NewMessageReceived $event): void {}
}
