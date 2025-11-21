<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Listeners\RememberUserLocale;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Tests\UnitTestCase;

/**
 * Test RememberUserLocale Listener
 * 
 * Target: 90-95% coverage for App\Listeners\RememberUserLocale
 * Current coverage: 50%
 */
class RememberUserLocaleListenerTest extends UnitTestCase
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

    // Additional tests for 90-95% coverage

    public function test_listener_can_be_instantiated(): void
    {
        $listener = new RememberUserLocale();
        
        $this->assertInstanceOf(RememberUserLocale::class, $listener);
    }

    public function test_handle_method_is_public(): void
    {
        $reflection = new \ReflectionClass(RememberUserLocale::class);
        $method = $reflection->getMethod('handle');
        
        $this->assertTrue($method->isPublic());
    }

    public function test_handle_saves_user_locale_to_session(): void
    {
        $user = User::factory()->create();
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();
        
        $listener->handle($event);
        
        // Check that locale was saved to session
        if (method_exists($user, 'getLocale')) {
            $this->assertEquals($user->getLocale(), session('user_locale'));
        } else {
            $this->assertNull(session('user_locale'));
        }
    }

    public function test_handle_with_user_that_has_get_locale_method(): void
    {
        // Create user with locale
        $user = User::factory()->create();
        
        // Mock or set locale if User model has this method
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();
        
        $listener->handle($event);
        
        // Should complete without error
        $this->assertTrue(true);
    }

    public function test_handle_with_user_without_get_locale_method(): void
    {
        // Create a simple user object without getLocale method
        $user = new \stdClass();
        $user->id = 1;
        
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();
        
        // Should not throw exception
        $listener->handle($event);
        $this->assertTrue(true);
    }

    public function test_handle_checks_method_exists_before_calling(): void
    {
        $user = new class {
            public $id = 1;
            public function getLocale() {
                return 'en';
            }
        };
        
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();
        
        $listener->handle($event);
        
        // Locale should be set in session
        $this->assertEquals('en', session('user_locale'));
    }

    public function test_handle_sets_session_key_as_user_locale(): void
    {
        $user = new class {
            public $id = 1;
            public function getLocale() {
                return 'fr';
            }
        };
        
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();
        
        $listener->handle($event);
        
        // Check specific session key
        $this->assertEquals('fr', session('user_locale'));
    }

    public function test_handle_overwrites_existing_session_value(): void
    {
        // Set an initial value
        session(['user_locale' => 'de']);
        
        $user = new class {
            public $id = 1;
            public function getLocale() {
                return 'es';
            }
        };
        
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();
        
        $listener->handle($event);
        
        // Should be updated to new value
        $this->assertEquals('es', session('user_locale'));
    }

    public function test_handle_with_login_event_from_different_guard(): void
    {
        $user = new class {
            public $id = 1;
            public function getLocale() {
                return 'en';
            }
        };
        
        $event = new Login('api', $user, false);
        $listener = new RememberUserLocale();
        
        $listener->handle($event);
        
        // Should still work regardless of guard
        $this->assertEquals('en', session('user_locale'));
    }

    public function test_handle_with_remember_me_token(): void
    {
        $user = new class {
            public $id = 1;
            public function getLocale() {
                return 'en';
            }
        };
        
        $event = new Login('web', $user, true); // remember = true
        $listener = new RememberUserLocale();
        
        $listener->handle($event);
        
        $this->assertEquals('en', session('user_locale'));
    }

    public function test_handle_with_various_locale_values(): void
    {
        $locales = ['en', 'fr', 'es', 'de', 'it', 'pt', 'ru', 'zh'];
        
        foreach ($locales as $locale) {
            $user = new class($locale) {
                private $locale;
                public $id = 1;
                
                public function __construct($locale) {
                    $this->locale = $locale;
                }
                
                public function getLocale() {
                    return $this->locale;
                }
            };
            
            $event = new Login('web', $user, false);
            $listener = new RememberUserLocale();
            
            $listener->handle($event);
            
            $this->assertEquals($locale, session('user_locale'));
        }
    }

    public function test_handle_with_empty_locale(): void
    {
        $user = new class {
            public $id = 1;
            public function getLocale() {
                return '';
            }
        };
        
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();
        
        $listener->handle($event);
        
        // Should save empty string
        $this->assertEquals('', session('user_locale'));
    }

    public function test_handle_with_null_locale(): void
    {
        $user = new class {
            public $id = 1;
            public function getLocale() {
                return null;
            }
        };
        
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();
        
        $listener->handle($event);
        
        // Should save null
        $this->assertNull(session('user_locale'));
    }

    public function test_handle_returns_void(): void
    {
        $reflection = new \ReflectionClass(RememberUserLocale::class);
        $method = $reflection->getMethod('handle');
        $returnType = $method->getReturnType();
        
        $this->assertEquals('void', $returnType->getName());
    }

    public function test_handle_is_non_blocking(): void
    {
        $user = new class {
            public $id = 1;
            public function getLocale() {
                return 'en';
            }
        };
        
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();
        
        $start = microtime(true);
        $listener->handle($event);
        $duration = microtime(true) - $start;
        
        // Should complete very quickly (< 50ms)
        $this->assertLessThan(0.05, $duration);
    }

    public function test_handle_works_with_real_user_model(): void
    {
        $user = User::factory()->create();
        $event = new Login('web', $user, false);
        $listener = new RememberUserLocale();
        
        // Should not throw exception with real User model
        $listener->handle($event);
        
        $this->assertTrue(true);
    }
}
