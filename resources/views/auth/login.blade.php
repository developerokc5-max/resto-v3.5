<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign in — HawkerOps</title>
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#6366f1">
  <script>
    (function(){
      var t = localStorage.getItem('hawkerops-theme') || 'light';
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
  @vite(['resources/css/app.css'])
  <style>
    * { box-sizing: border-box; }

    /* ── THEME VARIABLES ── */
    :root {
      --bg:           #f0f4ff;
      --glow:         rgba(99,102,241,0.10);
      --card:         #ffffff;
      --card-bdr:     rgba(0,0,0,0.07);
      --card-shadow:  0 0 0 1px rgba(99,102,241,0.07), 0 24px 60px rgba(0,0,0,0.08);
      --logo-bg:      #f1f5f9;
      --logo-bdr:     rgba(0,0,0,0.06);
      --logo-glow:    rgba(99,102,241,0.08);
      --text1:        #0f172a;
      --text2:        #475569;
      --text3:        #94a3b8;
      --badge-bg:     rgba(99,102,241,0.08);
      --badge-bdr:    rgba(99,102,241,0.2);
      --badge-text:   #4338ca;
      --dot-color:    #10b981;
      --div-hr:       rgba(0,0,0,0.07);
      --div-bg:       #ffffff;
      --div-text:     #94a3b8;
      --pill-grab-bg: rgba(16,185,129,0.08); --pill-grab-bdr: rgba(16,185,129,0.2); --pill-grab-c: #059669;
      --pill-panda-bg: rgba(244,114,182,0.08); --pill-panda-bdr: rgba(244,114,182,0.2); --pill-panda-c: #db2777;
      --pill-dlv-bg:  rgba(14,165,233,0.08); --pill-dlv-bdr: rgba(14,165,233,0.2); --pill-dlv-c: #0284c7;
      --gbtn-bg:      #f8fafc;
      --gbtn-bdr:     rgba(0,0,0,0.09);
      --gbtn-text:    #1e293b;
      --gbtn-shadow:  rgba(0,0,0,0.06);
      --err-bg:       rgba(220,38,38,0.06);
      --err-bdr:      rgba(220,38,38,0.15);
      --err-text:     #dc2626;
      --stat-bg:      rgba(0,0,0,0.03);
      --stat-bdr:     rgba(0,0,0,0.07);
      --stat-text:    #64748b;
      --footnote:     #94a3b8;
      --footer:       #cbd5e1;
      --tbtn-bg:      #ffffff;
      --tbtn-bdr:     rgba(0,0,0,0.09);
      --tbtn-shadow:  rgba(0,0,0,0.06);
      color-scheme: light;
    }
    [data-theme="dark"] {
      --bg:           #080f1f;
      --glow:         rgba(99,102,241,0.18);
      --card:         #111827;
      --card-bdr:     rgba(255,255,255,0.08);
      --card-shadow:  0 0 0 1px rgba(99,102,241,0.12), 0 32px 64px rgba(0,0,0,0.6);
      --logo-bg:      #1e293b;
      --logo-bdr:     rgba(255,255,255,0.08);
      --logo-glow:    rgba(99,102,241,0.15);
      --text1:        #f1f5f9;
      --text2:        #64748b;
      --text3:        #475569;
      --badge-bg:     rgba(99,102,241,0.1);
      --badge-bdr:    rgba(99,102,241,0.25);
      --badge-text:   #a5b4fc;
      --dot-color:    #34d399;
      --div-hr:       rgba(255,255,255,0.06);
      --div-bg:       #111827;
      --div-text:     #475569;
      --pill-grab-bg: rgba(52,211,153,0.1); --pill-grab-bdr: rgba(52,211,153,0.2); --pill-grab-c: #6ee7b7;
      --pill-panda-bg: rgba(244,114,182,0.1); --pill-panda-bdr: rgba(244,114,182,0.2); --pill-panda-c: #f9a8d4;
      --pill-dlv-bg:  rgba(56,189,248,0.1); --pill-dlv-bdr: rgba(56,189,248,0.2); --pill-dlv-c: #7dd3fc;
      --gbtn-bg:      #ffffff;
      --gbtn-bdr:     transparent;
      --gbtn-text:    #1e293b;
      --gbtn-shadow:  rgba(0,0,0,0.3);
      --err-bg:       rgba(239,68,68,0.08);
      --err-bdr:      rgba(239,68,68,0.2);
      --err-text:     #fca5a5;
      --stat-bg:      rgba(255,255,255,0.04);
      --stat-bdr:     rgba(255,255,255,0.08);
      --stat-text:    #94a3b8;
      --footnote:     #334155;
      --footer:       #1e293b;
      --tbtn-bg:      #1e293b;
      --tbtn-bdr:     rgba(255,255,255,0.1);
      --tbtn-shadow:  rgba(0,0,0,0.3);
      color-scheme: dark;
    }

    html, body {
      margin: 0; padding: 0;
      min-height: 100vh;
      font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
      background: var(--bg);
    }
    .page {
      min-height: 100vh;
      display: flex; align-items: center; justify-content: center;
      padding: 2rem 1rem;
      background:
        radial-gradient(ellipse 90% 60% at 50% -10%, var(--glow) 0%, transparent 70%),
        var(--bg);
    }
    .wrap { width: 100%; max-width: 400px; }

    /* Toggle button */
    .theme-btn {
      position: fixed; top: 16px; right: 16px; z-index: 100;
      width: 38px; height: 38px; border-radius: 50%;
      background: var(--tbtn-bg);
      border: 1px solid var(--tbtn-bdr);
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      box-shadow: 0 2px 8px var(--tbtn-shadow);
      transition: transform 0.2s;
      color: var(--text2);
      padding: 0;
    }
    .theme-btn:hover { transform: scale(1.08); }
    .icon-sun  { display: none; }
    .icon-moon { display: block; }
    [data-theme="dark"] .icon-sun  { display: block; }
    [data-theme="dark"] .icon-moon { display: none; }

    .badge {
      display: flex; align-items: center; justify-content: center;
      gap: 8px; margin-bottom: 2rem;
    }
    .badge-inner {
      display: flex; align-items: center; gap: 8px;
      background: var(--badge-bg);
      border: 1px solid var(--badge-bdr);
      color: var(--badge-text);
      font-size: 12px; font-weight: 500;
      padding: 6px 16px; border-radius: 999px;
    }
    .dot-pulse {
      width: 7px; height: 7px; border-radius: 50%;
      background: var(--dot-color);
      animation: pulse 2s infinite;
    }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

    .card {
      background: var(--card);
      border: 1px solid var(--card-bdr);
      border-radius: 24px;
      padding: 2.5rem 2rem;
      box-shadow: var(--card-shadow);
    }
    .logo-wrap {
      display: flex; justify-content: center; margin-bottom: 1.25rem;
    }
    .logo-box {
      background: var(--logo-bg);
      border: 1px solid var(--logo-bdr);
      border-radius: 16px; padding: 12px;
      box-shadow: 0 0 30px var(--logo-glow);
    }
    .logo-box img { height: 40px; width: auto; display: block; }
    .logo-light { display: block; }
    .logo-dark  { display: none; }
    [data-theme="dark"] .logo-light { display: none; }
    [data-theme="dark"] .logo-dark  { display: block; }

    .title { text-align: center; margin-bottom: 1.5rem; }
    .title h1 { font-size: 22px; font-weight: 700; color: var(--text1); margin: 0 0 4px; }
    .title p  { font-size: 13px; color: var(--text2); margin: 0; }

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
    .pill-grab     { background: var(--pill-grab-bg);  border: 1px solid var(--pill-grab-bdr);  color: var(--pill-grab-c); }
    .pill-panda    { background: var(--pill-panda-bg); border: 1px solid var(--pill-panda-bdr); color: var(--pill-panda-c); }
    .pill-deliveroo{ background: var(--pill-dlv-bg);   border: 1px solid var(--pill-dlv-bdr);   color: var(--pill-dlv-c); }

    .divider {
      height: 1px; background: var(--div-hr);
      margin-bottom: 1.75rem; position: relative;
    }
    .divider span {
      position: absolute; top: 50%; left: 50%;
      transform: translate(-50%, -50%);
      background: var(--div-bg); color: var(--div-text);
      font-size: 11px; padding: 0 12px; white-space: nowrap;
    }
    .error-box {
      display: flex; align-items: center; gap: 10px;
      background: var(--err-bg);
      border: 1px solid var(--err-bdr);
      color: var(--err-text); font-size: 13px;
      border-radius: 12px; padding: 12px 14px;
      margin-bottom: 1.25rem;
    }
    .status-box {
      display: flex; align-items: center; gap: 10px;
      background: var(--stat-bg);
      border: 1px solid var(--stat-bdr);
      color: var(--stat-text); font-size: 13px;
      border-radius: 12px; padding: 12px 14px;
      margin-bottom: 1.25rem;
    }
    .btn-google {
      display: flex; align-items: center; justify-content: center;
      gap: 12px; width: 100%;
      background: var(--gbtn-bg); color: var(--gbtn-text);
      font-size: 15px; font-weight: 600;
      border: 1px solid var(--gbtn-bdr); border-radius: 14px;
      padding: 15px 20px; cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      box-shadow: 0 4px 20px var(--gbtn-shadow);
    }
    .btn-google:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 30px var(--gbtn-shadow);
    }
    .btn-google svg { width: 20px; height: 20px; flex-shrink: 0; }

    .footnote {
      text-align: center; color: var(--footnote);
      font-size: 12px; margin-top: 1.25rem;
      display: flex; align-items: center; justify-content: center; gap: 6px;
    }
    .footer { text-align: center; color: var(--footer); font-size: 11px; margin-top: 1.5rem; }
  </style>
</head>
<body>

<button class="theme-btn" onclick="toggleTheme()" title="Toggle light/dark">
  <svg class="icon-sun" width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
  <svg class="icon-moon" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/></svg>
</button>

<div class="page">
  <div class="wrap">

    <div class="badge">
      <div class="badge-inner">
        <span class="dot-pulse"></span>
        Live monitoring · 46 stores
      </div>
    </div>

    <div class="card">

      <div class="logo-wrap">
        <div class="logo-box">
          <img src="/images/logo-light.png" alt="HawkerOps" class="logo-light">
          <img src="/images/logo-dark.png"  alt="HawkerOps" class="logo-dark">
        </div>
      </div>

      <div class="title">
        <h1>HawkerOps</h1>
        <p>Store operations dashboard</p>
      </div>

      <div class="pills">
        <span class="pill pill-grab">
          <span class="pill-dot" style="background:#10b981"></span>Grab
        </span>
        <span class="pill pill-panda">
          <span class="pill-dot" style="background:#f472b6"></span>FoodPanda
        </span>
        <span class="pill pill-deliveroo">
          <span class="pill-dot" style="background:#38bdf8"></span>Deliveroo
        </span>
      </div>

      <div class="divider"><span>sign in to continue</span></div>

      @if(session('error'))
        <div class="error-box">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          {{ session('error') }}
        </div>
      @endif

      @if(session('status'))
        <div class="status-box">
          <svg width="16" height="16" fill="none" stroke="#10b981" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          {{ session('status') }}
        </div>
      @endif

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

<script>
  function toggleTheme() {
    var cur = document.documentElement.getAttribute('data-theme');
    var next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('hawkerops-theme', next);
  }
</script>
</body>
</html>
