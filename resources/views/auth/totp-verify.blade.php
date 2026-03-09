<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify 2FA — HawkerOps</title>
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

    :root {
      --bg:          #f0f4ff;
      --glow:        rgba(99,102,241,0.10);
      --card:        #ffffff;
      --card-bdr:    rgba(0,0,0,0.07);
      --card-shadow: 0 0 0 1px rgba(99,102,241,0.07), 0 24px 60px rgba(0,0,0,0.08);
      --icon-bg:     #f1f5f9;
      --icon-bdr:    rgba(0,0,0,0.06);
      --icon-glow:   rgba(99,102,241,0.08);
      --text1:       #0f172a;
      --text2:       #475569;
      --badge-bg:    rgba(16,185,129,0.08);
      --badge-bdr:   rgba(16,185,129,0.2);
      --badge-text:  #059669;
      --dot-color:   #10b981;
      --input-bg:    #f1f5f9;
      --input-bdr:   rgba(0,0,0,0.09);
      --input-ph:    #94a3b8;
      --input-focus-bdr: rgba(99,102,241,0.4);
      --input-focus-ring: rgba(99,102,241,0.08);
      --err-bg:      rgba(220,38,38,0.06);
      --err-bdr:     rgba(220,38,38,0.15);
      --err-text:    #dc2626;
      --back-text:   #94a3b8;
      --back-hover:  #64748b;
      --footer:      #cbd5e1;
      --tbtn-bg:     #ffffff;
      --tbtn-bdr:    rgba(0,0,0,0.09);
      --tbtn-shadow: rgba(0,0,0,0.06);
      color-scheme: light;
    }
    [data-theme="dark"] {
      --bg:          #080f1f;
      --glow:        rgba(99,102,241,0.18);
      --card:        #111827;
      --card-bdr:    rgba(255,255,255,0.08);
      --card-shadow: 0 0 0 1px rgba(99,102,241,0.12), 0 32px 64px rgba(0,0,0,0.6);
      --icon-bg:     #1e293b;
      --icon-bdr:    rgba(255,255,255,0.08);
      --icon-glow:   rgba(99,102,241,0.15);
      --text1:       #f1f5f9;
      --text2:       #64748b;
      --badge-bg:    rgba(52,211,153,0.1);
      --badge-bdr:   rgba(52,211,153,0.2);
      --badge-text:  #6ee7b7;
      --dot-color:   #34d399;
      --input-bg:    #1e293b;
      --input-bdr:   rgba(255,255,255,0.1);
      --input-ph:    #334155;
      --input-focus-bdr: rgba(99,102,241,0.5);
      --input-focus-ring: rgba(99,102,241,0.1);
      --err-bg:      rgba(239,68,68,0.08);
      --err-bdr:     rgba(239,68,68,0.2);
      --err-text:    #fca5a5;
      --back-text:   #475569;
      --back-hover:  #64748b;
      --footer:      #1e293b;
      --tbtn-bg:     #1e293b;
      --tbtn-bdr:    rgba(255,255,255,0.1);
      --tbtn-shadow: rgba(0,0,0,0.3);
      color-scheme: dark;
    }

    html, body {
      margin: 0; padding: 0; min-height: 100vh;
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

    .theme-btn {
      position: fixed; top: 16px; right: 16px; z-index: 100;
      width: 38px; height: 38px; border-radius: 50%;
      background: var(--tbtn-bg); border: 1px solid var(--tbtn-bdr);
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      box-shadow: 0 2px 8px var(--tbtn-shadow);
      transition: transform 0.2s; color: var(--text2); padding: 0;
    }
    .theme-btn:hover { transform: scale(1.08); }
    .icon-sun  { display: none; }
    .icon-moon { display: block; }
    [data-theme="dark"] .icon-sun  { display: block; }
    [data-theme="dark"] .icon-moon { display: none; }

    .badge {
      display: flex; align-items: center; justify-content: center;
      margin-bottom: 2rem;
    }
    .badge-inner {
      display: flex; align-items: center; gap: 8px;
      background: var(--badge-bg); border: 1px solid var(--badge-bdr);
      color: var(--badge-text);
      font-size: 12px; font-weight: 500;
      padding: 6px 16px; border-radius: 999px;
    }
    .dot { width: 7px; height: 7px; border-radius: 50%; background: var(--dot-color); }

    .card {
      background: var(--card); border: 1px solid var(--card-bdr);
      border-radius: 24px; padding: 2.5rem 2rem;
      box-shadow: var(--card-shadow);
    }
    .icon-wrap { display: flex; justify-content: center; margin-bottom: 1.25rem; }
    .icon-box {
      background: var(--icon-bg); border: 1px solid var(--icon-bdr);
      border-radius: 16px; padding: 14px;
      box-shadow: 0 0 30px var(--icon-glow);
      font-size: 28px; line-height: 1;
    }
    .title { text-align: center; margin-bottom: 2rem; }
    .title h1 { font-size: 22px; font-weight: 700; color: var(--text1); margin: 0 0 6px; }
    .title p  { font-size: 13px; color: var(--text2); margin: 0; line-height: 1.6; }

    .error-box {
      display: flex; align-items: center; gap: 10px;
      background: var(--err-bg); border: 1px solid var(--err-bdr);
      color: var(--err-text); font-size: 13px;
      border-radius: 12px; padding: 12px 14px; margin-bottom: 1.25rem;
    }
    .code-input {
      width: 100%; background: var(--input-bg);
      border: 1px solid var(--input-bdr); border-radius: 14px;
      color: var(--text1); font-size: 30px; font-weight: 700;
      font-family: ui-monospace, monospace;
      letter-spacing: 0.55em; text-align: center;
      padding: 18px 20px; outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
      margin-bottom: 1rem; display: block;
    }
    .code-input:focus {
      border-color: var(--input-focus-bdr);
      box-shadow: 0 0 0 3px var(--input-focus-ring);
    }
    .code-input::placeholder { color: var(--input-ph); }

    .btn-primary {
      display: flex; align-items: center; justify-content: center;
      gap: 8px; width: 100%; background: #4f46e5; color: #fff;
      font-size: 15px; font-weight: 600; border: none; border-radius: 14px;
      padding: 15px 20px; cursor: pointer; transition: all 0.2s ease;
      box-shadow: 0 4px 20px rgba(79,70,229,0.3);
    }
    .btn-primary:hover {
      background: #4338ca; transform: translateY(-1px);
      box-shadow: 0 8px 30px rgba(79,70,229,0.4);
    }
    .back-link {
      display: block; text-align: center; color: var(--back-text);
      font-size: 12px; text-decoration: none; margin-top: 1.25rem; transition: color 0.2s;
    }
    .back-link:hover { color: var(--back-hover); }
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
        <span class="dot"></span>
        Google sign-in verified
      </div>
    </div>

    <div class="card">

      <div class="icon-wrap">
        <div class="icon-box">🔐</div>
      </div>

      <div class="title">
        <h1>Two-factor authentication</h1>
        <p>Open <strong>Google Authenticator</strong> and enter<br>the 6-digit code for HawkerOps.</p>
      </div>

      @if(session('error'))
        <div class="error-box">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          {{ session('error') }}
        </div>
      @endif

      <form method="POST" action="/auth/totp/check">
        @csrf
        <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
               autofocus autocomplete="one-time-code"
               placeholder="000000"
               class="code-input">
        <button type="submit" class="btn-primary">
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Verify &amp; Sign in
        </button>
      </form>

      <a href="/login" class="back-link">← Sign in with a different account</a>

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
