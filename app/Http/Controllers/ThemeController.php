<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Qirolab\Theme\Theme;

class ThemeController extends Controller
{
    /**
     * Display the list of available themes.
     */
    public function index(): View|ViewFactory
    {
        $themes = $this->getAvailableThemes();
        $currentTheme = Auth::user()?->theme ?? config('theme.active');

        return view('themes.index', compact('themes', 'currentTheme'));
    }

    /**
     * Update the user's theme preference.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme' => ['nullable', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_-]+$/'],
        ]);

        $theme = $validated['theme'] ?? null;

        // Additional sanitization: prevent directory traversal
        if ($theme && (str_contains($theme, '..') || str_contains($theme, '/') || str_contains($theme, '\\'))) {
            return back()->with('error', __('Invalid theme name.'));
        }

        // Verify theme exists if not null or "default"
        if ($theme && $theme !== 'default') {
            $themes = $this->getAvailableThemes();
            $themeNames = array_column($themes, 'name');
            if (! in_array($theme, $themeNames)) {
                return back()->with('error', __('Selected theme does not exist.'));
            }
        }

        // If "default" is selected, store null
        $themeToStore = ($theme === 'default') ? null : $theme;

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->theme = $themeToStore;
        $user->save();

        return back()->with('success', __('Theme updated successfully.'));
    }

    /**
     * Get the list of available themes from the themes directory.
     *
     * @return array<int, array{name: string, path: string, title: string, description: string|null, preview: string|null}>
     */
    protected function getAvailableThemes(): array
    {
        $themes = [];
        $themesPath = base_path('themes');

        if (! File::isDirectory($themesPath)) {
            return $themes;
        }

        $directories = File::directories($themesPath);

        foreach ($directories as $dir) {
            $themeName = basename($dir);
            $themeInfo = [
                'name' => $themeName,
                'path' => $dir,
                'title' => ucfirst(str_replace(['-', '_'], ' ', $themeName)),
                'description' => null,
                'preview' => null,
            ];

            // Check for theme.json to get additional info
            $themeJsonPath = $dir.'/theme.json';
            if (File::exists($themeJsonPath)) {
                $themeJson = json_decode(File::get($themeJsonPath), true);
                if (is_array($themeJson)) {
                    $themeInfo['title'] = $themeJson['title'] ?? $themeInfo['title'];
                    $themeInfo['description'] = $themeJson['description'] ?? null;
                    $themeInfo['preview'] = $themeJson['preview'] ?? null;
                }
            }

            $themes[] = $themeInfo;
        }

        return $themes;
    }

    /**
     * Preview a theme without applying it.
     */
    public function preview(string $theme): View|ViewFactory
    {
        // Sanitize theme name - only allow alphanumeric, hyphens, and underscores
        if (! preg_match('/^[a-zA-Z0-9_-]+$/', $theme)) {
            abort(400, __('Invalid theme name.'));
        }

        // Additional check to prevent directory traversal
        if (str_contains($theme, '..') || str_contains($theme, '/') || str_contains($theme, '\\')) {
            abort(400, __('Invalid theme name.'));
        }

        // Verify theme exists
        $themes = $this->getAvailableThemes();
        $themeNames = array_column($themes, 'name');
        if (! in_array($theme, $themeNames)) {
            abort(404, __('Theme not found.'));
        }

        // Temporarily set the theme for preview
        Theme::set($theme);

        return view('themes.preview', ['theme' => $theme]);
    }
}
