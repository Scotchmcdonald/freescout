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

        $email = $googleUser->email;

        // Get configuration
        $adminEmailsString = config('services.google.admin_emails');
        $adminEmails = $adminEmailsString ? array_map('trim', explode(',', $adminEmailsString)) : [];
        
        $allowedDomainsString = config('services.google.allowed_domains');
        $allowedDomains = $allowedDomainsString ? array_map('trim', explode(',', $allowedDomainsString)) : [];

        $isAdmin = in_array(strtolower($email), array_map('strtolower', $adminEmails));
        
        $isAllowedDomain = false;
        if (empty($allowedDomains)) {
            // If no domains configured, allow none (or maybe allow all? usually safer to allow none if feature is used)
            // But for backward compatibility, if no domains are set, maybe we should rely on manual registration?
            // The user specifically asked to "only allow emails from a configured list".
            // So if list is empty, and not admin, deny.
        } else {
            foreach ($allowedDomains as $domain) {
                if (Str::endsWith($email, '@' . $domain)) {
                    $isAllowedDomain = true;
                    break;
                }
            }
        }

        // Check if user exists (by ID or Email)
        $user = User::where('google_id', $googleUser->id)->orWhere('email', $email)->first();

        if ($user) {
            // Update existing user
            $updates = [
                'google_id' => $googleUser->id,
                'avatar' => $googleUser->avatar,
            ];

            // Auto-verify if admin or allowed domain
            if (($isAdmin || $isAllowedDomain) && !$user->email_verified_at) {
                $updates['email_verified_at'] = now();
            }
            
            // Promote to admin if in admin list and not already admin
            if ($isAdmin && $user->role !== User::ROLE_ADMIN) {
                $updates['role'] = User::ROLE_ADMIN;
            }

            $user->update($updates);
            
            Auth::login($user);
            return redirect()->intended('dashboard');
        } else {
            // Create new user
            if ($isAdmin || $isAllowedDomain) {
                $role = $isAdmin ? User::ROLE_ADMIN : User::ROLE_USER;

                $newUser = User::create([
                    'first_name' => $googleUser->user['given_name'] ?? 'User',
                    'last_name' => $googleUser->user['family_name'] ?? '',
                    'email' => $email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'password' => Hash::make(Str::random(24)),
                    'role' => $role,
                    'status' => User::STATUS_ACTIVE,
                    'email_verified_at' => now(), // Auto-verify
                    'dark_mode' => true,
                ]);

                Auth::login($newUser);
                return redirect()->intended('dashboard');
            }

            return redirect()->route('login')->with('error', 'Access denied. Your email domain is not authorized.');
        }
    }
}
