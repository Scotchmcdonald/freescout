<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'locale' => 'required|string|max:5',
        ]);

        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if ($user) {
            $locale = $request->input('locale');
            $user->locale = is_string($locale) ? $locale : '';
            $user->save();
        }

        return back();
    }
}
