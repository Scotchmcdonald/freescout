<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Google authentication failed.');
        }

        $user = User::where('google_id', $googleUser->id)->first();

        if ($user) {
            Auth::login($user);
            return redirect()->intended('dashboard');
        } else {
            // Check if user exists with this email
            $user = User::where('email', $googleUser->email)->first();

            if ($user) {
                // Link Google account
                $user->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                ]);
                
                Auth::login($user);
                return redirect()->intended('dashboard');
            } else {
                // Get configured admin emails
                $adminEmailsString = config('services.google.admin_emails');
                $adminEmails = $adminEmailsString ? array_map('trim', explode(',', $adminEmailsString)) : [];
                $isAdmin = in_array(strtolower($googleUser->email), array_map('strtolower', $adminEmails));

                // Auto-register if user is Admin OR from borealtek.ca
                if ($isAdmin || Str::endsWith($googleUser->email, '@borealtek.ca')) {
                    $role = $isAdmin ? User::ROLE_ADMIN : User::ROLE_USER;

                    $newUser = User::create([
                        'first_name' => $googleUser->user['given_name'] ?? 'User',
                        'last_name' => $googleUser->user['family_name'] ?? '',
                        'email' => $googleUser->email,
                        'google_id' => $googleUser->id,
                        'avatar' => $googleUser->avatar,
                        'password' => Hash::make(Str::random(24)),
                        'role' => $role,
                        'status' => User::STATUS_ACTIVE,
                        'email_verified_at' => now(), // Auto-verify email
                        'dark_mode' => true, // Default to Dark Mode
                    ]);

                    Auth::login($newUser);
                    return redirect()->intended('dashboard');
                }

                return redirect()->route('login')->with('error', 'No account found with this email address.');
            }
        }
    }
}
