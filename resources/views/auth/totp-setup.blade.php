<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set up 2FA — HawkerOps</title>
  <meta name="theme-color" content="#0f172a">
  @vite(['resources/css/app.css'])
  <style>
    * { box-sizing: border-box; }
    html, body {
      margin: 0; padding: 0;
      min-height: 100vh;
      background: #080f1f !important;
      font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
      color-scheme: dark;
    }
    .page {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
      background:
        radial-gradient(ellipse 90% 60% at 50% -10%, rgba(99,102,241,0.18) 0%, transparent 70%),
        #080f1f;
    }
    .wrap { width: 100%; max-width: 420px; }
    .badge {
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 2rem;
    }
    .badge-inner {
      display: flex; align-items: center; gap: 8px;
      background: rgba(99,102,241,0.1);
      border: 1px solid rgba(99,102,241,0.2);
      color: #a5b4fc;
      font-size: 12px; font-weight: 500;
      padding: 6px 16px; border-radius: 999px;
    }
    .card {
      background: #111827;
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 24px;
      padding: 2.5rem 2rem;
      box-shadow: 0 0 0 1px rgba(99,102,241,0.12), 0 32px 64px rgba(0,0,0,0.6);
    }
    .icon-wrap {
      display: flex; justify-content: center; margin-bottom: 1.25rem;
    }
    .icon-box {
      background: #1e293b;
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 16px; padding: 14px;
      box-shadow: 0 0 30px rgba(99,102,241,0.15);
      font-size: 28px; line-height: 1;
    }
    .title { text-align: center; margin-bottom: 1.75rem; }
    .title h1 { font-size: 22px; font-weight: 700; color: #f1f5f9; margin: 0 0 6px; }
    .title p  { font-size: 13px; color: #64748b; margin: 0; line-height: 1.6; }
    .steps {
      background: #1e293b;
      border: 1px solid rgba(255,255,255,0.06);
      border-radius: 14px;
      padding: 16px 18px;
      margin-bottom: 1.5rem;
      font-size: 13px;
      color: #94a3b8;
      line-height: 1.8;
    }
    .steps strong { color: #e2e8f0; font-weight: 600; }
    .qr-wrap {
      display: flex; justify-content: center;
      margin-bottom: 1.5rem;
    }
    .qr-box {
      background: #fff;
      border-radius: 16px;
      padding: 16px;
      box-shadow: 0 4px 24px rgba(0,0,0,0.4);
    }
    .qr-box img { width: 180px; height: 180px; display: block; }
    .manual-key {
      background: #1e293b;
      border: 1px solid rgba(255,255,255,0.06);
      border-radius: 12px;
      padding: 12px 16px;
      margin-bottom: 1.5rem;
      text-align: center;
    }
    .manual-key p { font-size: 12px; color: #475569; margin: 0 0 4px; }
    .manual-key code { font-size: 13px; font-family: ui-monospace, monospace; color: #818cf8; letter-spacing: 0.1em; }
    .error-box {
      display: flex; align-items: center; gap: 10px;
      background: rgba(239,68,68,0.08);
      border: 1px solid rgba(239,68,68,0.2);
      color: #fca5a5; font-size: 13px;
      border-radius: 12px; padding: 12px 14px;
      margin-bottom: 1.25rem;
    }
    .input-label {
      display: block; text-align: center;
      font-size: 12px; color: #475569;
      margin-bottom: 8px;
    }
    .code-input {
      width: 100%;
      background: #1e293b;
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 14px;
      color: #f1f5f9;
      font-size: 30px;
      font-weight: 700;
      font-family: ui-monospace, monospace;
      letter-spacing: 0.55em;
      text-align: center;
      padding: 18px 20px;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
      margin-bottom: 1rem;
      display: block;
    }
    .code-input:focus {
      border-color: rgba(99,102,241,0.5);
      box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    }
    .code-input::placeholder { color: #334155; }
    .btn-primary {
      display: flex; align-items: center; justify-content: center;
      gap: 8px; width: 100%;
      background: #4f46e5;
      color: #fff;
      font-size: 15px; font-weight: 600;
      border: none; border-radius: 14px;
      padding: 15px 20px; cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 0 4px 20px rgba(79,70,229,0.3);
    }
    .btn-primary:hover {
      background: #4338ca;
      transform: translateY(-1px);
      box-shadow: 0 8px 30px rgba(79,70,229,0.4);
    }
    .footer { text-align: center; color: #1e293b; font-size: 11px; margin-top: 1.5rem; }
  </style>
</head>
<body>
<div class="page">
  <div class="wrap">

    <div class="badge">
      <div class="badge-inner">
        🔐 One-time setup &middot; {{ $email }}
      </div>
    </div>

    <div class="card">

      <div class="icon-wrap">
        <div class="icon-box">📱</div>
      </div>

      <div class="title">
        <h1>Set up Google Authenticator</h1>
        <p>Scan the QR code once, then enter the code to confirm.</p>
      </div>

      <div class="steps">
        <p style="margin:0 0 4px;">1. Download <strong>Google Authenticator</strong> on your phone</p>
        <p style="margin:0 0 4px;">2. Tap <strong>+</strong> → <strong>Scan QR code</strong></p>
        <p style="margin:0 0 4px;">3. Scan the code below</p>
        <p style="margin:0;">4. Enter the 6-digit code to confirm</p>
      </div>

      <div class="qr-wrap">
        <div class="qr-box">
          <img src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="QR Code">
        </div>
      </div>

      <div class="manual-key">
        <p>Can't scan? Enter this key manually:</p>
        <code>{{ $secret }}</code>
      </div>

      @if(session('error'))
        <div class="error-box">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          {{ session('error') }}
        </div>
      @endif

      <form method="POST" action="/auth/totp/setup/confirm">
        @csrf
        <label class="input-label">Enter the 6-digit code from the app</label>
        <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
               autofocus autocomplete="one-time-code"
               placeholder="000000"
               class="code-input">
        <button type="submit" class="btn-primary">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Confirm &amp; Activate 2FA
        </button>
      </form>

    </div>

    <div class="footer">⚡ HawkerOps · Store Operations Monitor</div>

  </div>
</div>
</body>
</html>
