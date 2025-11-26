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

        // Ensure the theme directory and file exist for the test
        $themePath = base_path('themes/dark/views/layouts');
        if (!is_dir($themePath)) {
            mkdir($themePath, 0755, true);
        }
        
        // Create a unique marker in the dark theme navigation
        $marker = 'Dark Theme Navigation Marker';
        file_put_contents(
            $themePath . '/navigation.blade.php', 
            '<div>' . $marker . '</div>'
        );

        // Act as the user and visit the dashboard
        $response = $this->actingAs($user)->get('/dashboard');

        // Assert the response contains the marker from the theme view
        $response->assertStatus(200);
        $response->assertSee($marker);
        
        // Clean up (optional, but good for local dev)
        // unlink($themePath . '/navigation.blade.php');
    }

    public function test_default_theme_is_used_when_user_has_no_theme()
    {
        $user = User::factory()->create(['theme' => null]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertDontSee('Dark Theme Navigation Marker');
    }
}
