<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign in — HawkerOps</title>
  <link rel="manifest" href="/manifest.json">
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
    .wrap { width: 100%; max-width: 400px; }
    .badge {
      display: flex; align-items: center; justify-content: center;
      gap: 8px; margin-bottom: 2rem;
    }
    .badge-inner {
      display: flex; align-items: center; gap: 8px;
      background: rgba(99,102,241,0.1);
      border: 1px solid rgba(99,102,241,0.25);
      color: #a5b4fc;
      font-size: 12px; font-weight: 500;
      padding: 6px 16px; border-radius: 999px;
    }
    .dot-pulse {
      width: 7px; height: 7px; border-radius: 50%;
      background: #34d399;
      animation: pulse 2s infinite;
    }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
    .card {
      background: #111827;
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 24px;
      padding: 2.5rem 2rem;
      box-shadow: 0 0 0 1px rgba(99,102,241,0.12), 0 32px 64px rgba(0,0,0,0.6);
    }
    .logo-wrap {
      display: flex; justify-content: center; margin-bottom: 1.25rem;
    }
    .logo-box {
      background: #1e293b;
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 16px; padding: 12px;
      box-shadow: 0 0 30px rgba(99,102,241,0.15);
    }
    .logo-box img { height: 40px; width: auto; display: block; }
    .title { text-align: center; margin-bottom: 1.5rem; }
    .title h1 { font-size: 22px; font-weight: 700; color: #f1f5f9; margin: 0 0 4px; }
    .title p  { font-size: 13px; color: #64748b; margin: 0; }
    .pills {
      display: flex; align-items: center; justify-content: center;
      gap: 8px; margin-bottom: 1.75rem; flex-wrap: wrap;
    }
    .pill {
      display: flex; align-items: center; gap: 6px;
      font-size: 12px; font-weight: 500;
      padding: 5px 12px; border-radius: 999px;
    }
    .pill-dot { width: 6px; height: 6px; border-radius: 50%; }
    .pill-grab    { background: rgba(52,211,153,0.1); border: 1px solid rgba(52,211,153,0.2); color: #6ee7b7; }
    .pill-panda   { background: rgba(244,114,182,0.1); border: 1px solid rgba(244,114,182,0.2); color: #f9a8d4; }
    .pill-deliveroo { background: rgba(56,189,248,0.1); border: 1px solid rgba(56,189,248,0.2); color: #7dd3fc; }
    .divider {
      height: 1px; background: rgba(255,255,255,0.06);
      margin-bottom: 1.75rem; position: relative;
    }
    .divider span {
      position: absolute; top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      background: #111827; color: #475569;
      font-size: 11px; padding: 0 12px; white-space: nowrap;
    }
    .error-box {
      display: flex; align-items: center; gap: 10px;
      background: rgba(239,68,68,0.08);
      border: 1px solid rgba(239,68,68,0.2);
      color: #fca5a5; font-size: 13px;
      border-radius: 12px; padding: 12px 14px;
      margin-bottom: 1.25rem;
    }
    .status-box {
      display: flex; align-items: center; gap: 10px;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      color: #94a3b8; font-size: 13px;
      border-radius: 12px; padding: 12px 14px;
      margin-bottom: 1.25rem;
    }
    .btn-google {
      display: flex; align-items: center; justify-content: center;
      gap: 12px; width: 100%;
      background: #ffffff; color: #1e293b;
      font-size: 15px; font-weight: 600;
      border: none; border-radius: 14px;
      padding: 15px 20px; cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }
    .btn-google:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 30px rgba(0,0,0,0.4);
      background: #f8fafc;
    }
    .btn-google svg { width: 20px; height: 20px; flex-shrink: 0; }
    .footnote {
      text-align: center; color: #334155;
      font-size: 12px; margin-top: 1.25rem;
      display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .footer { text-align: center; color: #1e293b; font-size: 11px; margin-top: 1.5rem; }
  </style>
</head>
<body>
<div class="page">
  <div class="wrap">

    {{-- Live badge --}}
    <div class="badge">
      <div class="badge-inner">
        <span class="dot-pulse"></span>
        Live monitoring · 46 stores
      </div>
    </div>

    {{-- Card --}}
    <div class="card">

      {{-- Logo --}}
      <div class="logo-wrap">
        <div class="logo-box">
          <img src="/images/logo-dark.png" alt="HawkerOps">
        </div>
      </div>

      {{-- Title --}}
      <div class="title">
        <h1>HawkerOps</h1>
        <p>Store operations dashboard</p>
      </div>

      {{-- Platform pills --}}
      <div class="pills">
        <span class="pill pill-grab">
          <span class="pill-dot" style="background:#34d399"></span>Grab
        </span>
        <span class="pill pill-panda">
          <span class="pill-dot" style="background:#f472b6"></span>FoodPanda
        </span>
        <span class="pill pill-deliveroo">
          <span class="pill-dot" style="background:#38bdf8"></span>Deliveroo
        </span>
      </div>

      {{-- Divider --}}
      <div class="divider"><span>sign in to continue</span></div>

      {{-- Error --}}
      @if(session('error'))
        <div class="error-box">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          {{ session('error') }}
        </div>
      @endif

      {{-- Status --}}
      @if(session('status'))
        <div class="status-box">
          <svg width="16" height="16" fill="none" stroke="#34d399" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          {{ session('status') }}
        </div>
      @endif

      {{-- Google button --}}
      <a href="/auth/google" class="btn-google">
        <svg viewBox="0 0 24 24">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        Sign in with Google
      </a>

      <div class="footnote">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        Protected by Google OAuth + 2FA
      </div>
    </div>

    <div class="footer">⚡ HawkerOps · Store Operations Monitor</div>

  </div>
</div>
</body>
</html>
