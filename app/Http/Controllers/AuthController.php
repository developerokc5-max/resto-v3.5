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
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Google sign-in failed. Please try again.');
        }

        $email = strtolower($googleUser->getEmail());

        // Check whitelist
        $allowed = collect(explode(',', env('AUTH_EMAILS', '')))
            ->map(fn($e) => strtolower(trim($e)))
            ->filter();

        if (!$allowed->contains($email)) {
            return redirect('/login')->with('error', 'Access denied. Your account is not authorised.');
        }

        // Store in session
        session([
            'auth_user'  => $email,
            'auth_name'  => $googleUser->getName(),
            'auth_avatar'=> $googleUser->getAvatar(),
        ]);

        return redirect()->intended('/dashboard');
    }

    /** Logout */
    public function logout(Request $request)
    {
        $request->session()->forget(['auth_user', 'auth_name', 'auth_avatar']);
        return redirect('/login')->with('status', 'You have been logged out.');
    }
}
