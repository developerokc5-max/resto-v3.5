<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;

class TotpController extends Controller
{
    private function g2fa(): Google2FA
    {
        return new Google2FA();
    }

    /** Show QR code setup page (first-time users) */
    public function setup()
    {
        $email = session('auth_pending');
        if (!$email) return redirect('/login');

        // Already set up → go to verify
        if (DB::table('user_totp')->where('email', $email)->exists()) {
            return redirect('/auth/totp/verify');
        }

        $g2fa   = $this->g2fa();
        $secret = $g2fa->generateSecretKey();
        session(['totp_setup_secret' => $secret]);

        $qrUrl = $g2fa->getQRCodeUrl(config('app.name'), $email, $secret);

        $renderer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(280),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $qrSvg = base64_encode((new \BaconQrCode\Writer($renderer))->writeString($qrUrl));

        return view('auth.totp-setup', compact('secret', 'qrSvg', 'email'));
    }

    /** Confirm setup — user scans QR and enters first code */
    public function confirmSetup(Request $request)
    {
        $email  = session('auth_pending');
        $secret = session('totp_setup_secret');
        if (!$email || !$secret) return redirect('/login');

        $request->validate(['code' => 'required|digits:6']);

        if (!$this->g2fa()->verifyKey($secret, $request->code)) {
            return back()->with('error', 'Invalid code. Try again.');
        }

        DB::table('user_totp')->updateOrInsert(
            ['email' => $email],
            ['secret' => $secret, 'updated_at' => now(), 'created_at' => now()]
        );

        session()->forget('totp_setup_secret');
        $this->completeLogin($email);
        return redirect()->intended('/dashboard');
    }

    /** Show TOTP verify page (returning users) */
    public function verify()
    {
        $email = session('auth_pending');
        if (!$email) return redirect('/login');

        if (!DB::table('user_totp')->where('email', $email)->exists()) {
            return redirect('/auth/totp/setup');
        }

        return view('auth.totp-verify', compact('email'));
    }

    /** Verify TOTP code on login */
    public function check(Request $request)
    {
        $email = session('auth_pending');
        if (!$email) return redirect('/login');

        $request->validate(['code' => 'required|digits:6']);

        $record = DB::table('user_totp')->where('email', $email)->first();
        if (!$record) return redirect('/auth/totp/setup');

        if (!$this->g2fa()->verifyKey($record->secret, $request->code)) {
            return back()->with('error', 'Invalid code. Check your authenticator app.');
        }

        $this->completeLogin($email);
        return redirect()->intended('/dashboard');
    }

    private function completeLogin(string $email): void
    {
        $name   = session('auth_pending_name');
        $avatar = session('auth_pending_avatar');
        session()->forget(['auth_pending', 'auth_pending_name', 'auth_pending_avatar']);
        session(['auth_user' => $email, 'auth_name' => $name, 'auth_avatar' => $avatar]);
    }
}
