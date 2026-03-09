<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    /** Show the login page */
    public function login()
    {
        if (session('auth_user')) {
            return redirect('/dashboard');
        }
        return view('auth.login');
    }

    /** Redirect to Google OAuth */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /** Handle Google callback */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $email = strtolower($googleUser->getEmail());

            // Check whitelist — use config() so it works with config:cache
            $allowed = collect(explode(',', config('services.google.auth_emails', '')))
                ->map(fn($e) => strtolower(trim($e)))
                ->filter();

            if (!$allowed->contains($email)) {
                return redirect('/login')->with('error', 'Access denied. Your account is not authorised.');
            }

            // Store pending session — TOTP step required before full access
            session([
                'auth_pending'        => $email,
                'auth_pending_name'   => $googleUser->getName(),
                'auth_pending_avatar' => $googleUser->getAvatar(),
            ]);

            // Check if TOTP is set up for this user
            $hasTOTP = \Illuminate\Support\Facades\DB::table('user_totp')
                ->where('email', $email)->exists();

            return $hasTOTP
                ? redirect('/auth/totp/verify')
                : redirect('/auth/totp/setup');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Google callback error: ' . $e->getMessage());
            return redirect('/login')->with('error', 'Sign-in failed. Please try again.');
        }
    }

    /** Handle admin username/password login */
    public function loginPost(Request $request)
    {
        $adminUser = config('services.admin.username');
        $adminPass = config('services.admin.password');

        if (!$adminUser || !$adminPass) {
            return back()->with('error', 'Admin login is not configured.');
        }

        if ($request->username !== $adminUser || $request->password !== $adminPass) {
            return back()->with('error', 'Invalid username or password.');
        }

        session([
            'auth_user'   => $adminUser,
            'auth_name'   => 'Admin',
            'auth_avatar' => null,
        ]);

        return redirect('/dashboard');
    }

    /** Logout */
    public function logout(Request $request)
    {
        $request->session()->forget(['auth_user', 'auth_name', 'auth_avatar']);
        return redirect('/login')->with('status', 'You have been logged out.');
    }
}
