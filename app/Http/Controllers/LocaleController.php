<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'locale' => 'required|string|max:5',
        ]);

        $user = auth()->user();
        if ($user) {
            $user->locale = $request->locale;
            $user->save();
        }

        return back();
    }
}
