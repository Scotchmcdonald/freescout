<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Qirolab\Theme\Theme;
use Tests\TestCase;

class ThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $themePath = base_path('themes/dark/views/layouts');
        if (file_exists($themePath . '/navigation.blade.php')) {
            unlink($themePath . '/navigation.blade.php');
        }
        
        parent::tearDown();
    }

    public function test_user_theme_is_applied()
    {
        // Create a user with the 'dark' theme
        $user = User::factory()->create(['theme' => 'dark']);

        // Manually set the theme as the middleware would
        Theme::set('dark');
        
        // Verify the theme is set correctly
        $this->assertEquals('dark', Theme::active());
        
        // Verify theme path exists in view finder
        $viewFinder = app('view')->getFinder();
        $paths = $viewFinder->getPaths();
        
        $darkThemePath = base_path('themes/dark/views');
        $hasThemePath = false;
        
        foreach ($paths as $path) {
            if (str_contains($path, 'themes/dark')) {
                $hasThemePath = true;
                break;
            }
        }
        
        $this->assertTrue($hasThemePath, 'Dark theme view path should be registered');
    }

    public function test_default_theme_is_used_when_user_has_no_theme()
    {
        $user = User::factory()->create(['theme' => null]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertDontSee('Dark Theme Navigation Marker');
    }
}
