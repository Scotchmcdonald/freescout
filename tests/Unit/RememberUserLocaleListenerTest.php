<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Listeners\RememberUserLocale;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Tests\TestCase;

class RememberUserLocaleListenerTest extends TestCase
{
    public function test_listener_has_handle_method(): void
    {
        $listener = new RememberUserLocale;
        $this->assertTrue(method_exists($listener, 'handle'));
    }

    public function test_listener_handles_login_event(): void
    {
        $user = new User(['id' => 1]);
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale;

        // Should not throw an exception
        $listener->handle($event);
        $this->assertTrue(true);
    }
}
