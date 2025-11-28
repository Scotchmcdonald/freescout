<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class ThemeEditorController extends Controller
{
    public function index()
    {
        $themes = Theme::all();
        
        // Get active theme
        $currentTheme = 'default';
        // Logic to determine active theme (can be updated later to use DB preference)
        $currentTheme = Auth::user()?->theme ?? config('theme.active', 'default');
        
        // Normalize legacy theme names if necessary
        $themeMap = [
            'light-classic' => 'classic', 'dark-classic' => 'classic',
            'light-blue' => 'synthwave', 'dark-blue' => 'synthwave', 'blue' => 'synthwave',
            'light-green' => 'monokai', 'dark-green' => 'monokai', 'green' => 'monokai',
            'light-purple' => 'purple', 'dark-purple' => 'purple',
            'dark' => 'solarized',
        ];
        $activeTheme = $themeMap[$currentTheme] ?? $currentTheme;

        return view('themes.editor.index', compact('themes', 'activeTheme'));
    }

    public function show(Theme $theme)
    {
        return view('themes.editor.show', compact('theme'));
    }

    public function create()
    {
        return view('themes.editor.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:themes,name|regex:/^[a-z0-9_-]+$/',
            'title' => 'required|string|max:255',
            'base_theme' => 'required|exists:themes,id',
        ]);

        $baseTheme = Theme::find($validated['base_theme']);

        $theme = Theme::create([
            'name' => $validated['name'],
            'title' => $validated['title'],
            'config' => $baseTheme->config,
            'is_system' => false,
            'created_by' => Auth::id(),
        ]);

        // Create theme directory structure
        $themePath = base_path('themes/' . $theme->name . '/views');
        if (!File::exists($themePath)) {
            File::makeDirectory($themePath, 0755, true);
        }

        return redirect()->route('themes.editor.show', $theme)->with('success', 'Theme created successfully.');
    }

    public function edit(Theme $theme)
    {
        if ($theme->is_system) {
            return redirect()->route('themes.editor.show', $theme)->with('error', 'System themes cannot be edited directly. Clone it to make changes.');
        }
        return view('themes.editor.edit', compact('theme'));
    }

    public function update(Request $request, Theme $theme)
    {
        if ($theme->is_system) {
            return back()->with('error', 'System themes cannot be updated.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'config' => 'required|array',
        ]);

        $theme->update([
            'title' => $validated['title'],
            'config' => $validated['config'],
        ]);

        return redirect()->route('themes.editor.show', $theme)->with('success', 'Theme updated successfully.');
    }

    public function destroy(Theme $theme)
    {
        if ($theme->is_system) {
            return back()->with('error', 'System themes cannot be deleted.');
        }

        // Reset users using this theme to default
        \App\Models\User::where('theme', $theme->name)->update(['theme' => 'default']);

        $theme->delete();

        // Remove theme directory
        $themePath = base_path('themes/' . $theme->name);
        if (File::exists($themePath)) {
            File::deleteDirectory($themePath);
        }

        return redirect()->route('themes.editor.index')->with('success', 'Theme deleted successfully.');
    }
}
