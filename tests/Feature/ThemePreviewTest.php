<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $themePath = base_path('themes/dark/views/components/layouts');
        if (file_exists($themePath . '/navigation.blade.php')) {
            unlink($themePath . '/navigation.blade.php');
        }
        // We don't remove directories to avoid accidentally removing something important if it existed before
        
        parent::tearDown();
    }

    public function test_theme_preview_via_query_param_works()
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        // Ensure the theme directory and file exist for the test
        $themePath = base_path('themes/dark/views/components/layouts');
        if (!is_dir($themePath)) {
            mkdir($themePath, 0755, true);
        }
        
        // Create a unique marker in the dark theme navigation
        $marker = 'Dark Theme Navigation Marker';
        file_put_contents(
            $themePath . '/navigation.blade.php', 
            '<div>' . $marker . '</div>'
        );

        // Visit the themes index with the preview_theme parameter
        $response = $this->actingAs($user)->get(route('themes', ['preview_theme' => 'dark']));

        $response->assertStatus(200);
        
        // It should see the marker because the theme is applied
        $response->assertSee($marker);
        
        // It should also see the component preview section
        $response->assertSee('Theme Preview Elements');
    }
}
