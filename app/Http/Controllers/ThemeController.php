<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThemeController extends Controller
{
    /**
     * Display the list of available themes.
     */
    public function index(Request $request): View|ViewFactory
    {
        $themes = Theme::all();
        $currentTheme = Auth::user()?->theme ?? 'default';
        
        return view('themes.index', compact('themes', 'currentTheme'));
    }

    /**
     * Update the user's theme preference.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'theme' => ['nullable', 'string', 'max:50', 'exists:themes,name'],
            'dark_mode' => ['nullable', 'boolean'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($request->has('dark_mode')) {
            $user->dark_mode = $request->boolean('dark_mode');
            $user->save();
            
            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'dark_mode' => $user->dark_mode]);
            }
            
            return back()->with('success', __('Theme mode updated.'));
        }

        $theme = $validated['theme'] ?? null;

        // If "default" is selected, store null (or keep it as 'default' if that's how we want to handle it)
        // The previous logic stored null for default. Let's stick to storing the string 'default' 
        // if it exists in the DB, or null if we want to fallback to system default.
        // However, our seeder created a 'default' theme in the DB.
        // So we should probably store 'default'.
        
        $user->theme = $theme;
        $user->save();

        if ($request->wantsJson()) {
             return response()->json(['success' => true]);
        }

        return back()->with('success', __('Theme updated successfully.'));
    }

    /**
     * Run the theme seeder to update themes.
     */
    public function seed(): RedirectResponse
    {
        if (!Auth::user()?->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            \Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'ThemeSeeder',
                '--force' => true,
            ]);
            
            return back()->with('success', __('Themes re-seeded successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to seed themes: ') . $e->getMessage());
        }
    }
}
