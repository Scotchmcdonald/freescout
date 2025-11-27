<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Qirolab\Theme\Theme;
use Symfony\Component\HttpFoundation\Response;

class ApplyUserTheme
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('preview_theme')) {
            $previewTheme = $request->input('preview_theme');
            // Basic validation to prevent directory traversal if the Theme package doesn't handle it
            if (is_string($previewTheme) && preg_match('/^[a-zA-Z0-9_-]+$/', $previewTheme)) {
                Theme::set($previewTheme);
            }
        } elseif (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            if ($user->theme) {
                Theme::set($user->theme);
            }
        }

        return $next($request);
    }
}
