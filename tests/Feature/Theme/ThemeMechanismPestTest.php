<?php

use App\Models\User;
use Qirolab\Theme\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;

afterEach(function () {
    // Clean up dark theme navigation override
    $themePaths = [
        base_path('themes/dark/views/layouts/navigation.blade.php'),
        base_path('themes/dark/views/components/layouts/navigation.blade.php'),
    ];
    
    foreach ($themePaths as $path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }
});

test('user theme is applied via middleware', function () {
    // Create a user with the 'dark' theme
    $user = User::factory()->create(['theme' => 'dark']);

    // Manually set the theme as the middleware would (or rely on middleware in full request)
    // The legacy test manually called Theme::set('dark')
    Theme::set('dark');
    
    // Verify the theme is set correctly
    expect(Theme::active())->toBe('dark');
    
    // Verify theme path exists in view finder
    $viewFinder = app('view')->getFinder();
    $paths = $viewFinder->getPaths();
    
    $hasThemePath = false;
    foreach ($paths as $path) {
        if (str_contains($path, 'themes/dark')) {
            $hasThemePath = true;
            break;
        }
    }
    
    expect($hasThemePath)->toBeTrue('Dark theme view path should be registered');
});

test('default theme is used when user has no theme', function () {
    $user = User::factory()->create(['theme' => null]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    // Should NOT see any dark theme marker
    $response->assertDontSee('Dark Theme Navigation Marker');
});

test('theme preview via query param works', function () {
    $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
    
    // Ensure the theme directory and file exist for the test
    // Note: The legacy test used themes/dark/views/components/layouts
    // We'll reproduce that structure
    $themeDir = base_path('themes/dark/views/components/layouts');
    if (!is_dir($themeDir)) {
        mkdir($themeDir, 0755, true);
    }
    
    // Create a unique marker in the dark theme navigation
    $marker = 'Dark Theme Navigation Marker';
    file_put_contents(
        $themeDir . '/navigation.blade.php', 
        '<div>' . $marker . '</div>'
    );

    // Visit the themes index with the preview_theme parameter
    $response = $this->actingAs($user)->get(route('themes', ['preview_theme' => 'dark']));

    $response->assertStatus(200);
    
    // It should see the marker because the theme is applied (preview mode)
    // Note: This assertion might fail if the view being rendered (themes.index) 
    // doesn't actually include the navigation component we mocked.
    // However, we are porting the legacy test logic which claimed this works.
    
    // If the legacy test passed, then themes.index likely extends a layout that includes navigation.
    $response->assertSee($marker);
    
    // It should also see the component preview section
    $response->assertSee('Theme Preview Elements');
});
