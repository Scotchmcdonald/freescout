<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners;

use App\Listeners\SendPasswordChanged;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Mail;
use Tests\UnitTestCase;

class SendPasswordChangedTest extends UnitTestCase
{
    public function test_handle_calls_send_password_changed_on_user(): void
    {
        $user = \Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('sendPasswordChanged')
            ->once()
            ->andReturn(true);
        
        $event = new PasswordReset($user);
        $listener = new SendPasswordChanged();
        
        $listener->handle($event);
        
        // Verify the mock expectations were met
        $this->assertTrue(true);
    }

    public function test_handle_does_not_fail_when_method_does_not_exist(): void
    {
        // Create a mock object without the sendPasswordChanged method
        $user = new class {
            public $id = 1;
        };
        
        $event = new PasswordReset($user);
        $listener = new SendPasswordChanged();

        // Should not throw exception
        try {
            $listener->handle($event);
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('Listener should not throw exception: ' . $e->getMessage());
        }
    }

    public function test_handle_checks_if_method_exists_before_calling(): void
    {
        $user = new class {
            public $id = 1;
            public $methodCalled = false;
            
            public function sendPasswordChanged(): void
            {
                $this->methodCalled = true;
            }
        };
        
        $event = new PasswordReset($user);
        $listener = new SendPasswordChanged();
        
        $listener->handle($event);
        
        $this->assertTrue($user->methodCalled);
    }

    public function test_listener_can_be_instantiated(): void
    {
        $listener = new SendPasswordChanged();
        
        $this->assertInstanceOf(SendPasswordChanged::class, $listener);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
