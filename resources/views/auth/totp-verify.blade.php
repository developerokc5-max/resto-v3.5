<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verify 2FA — HawkerOps</title>
  <meta name="theme-color" content="#0f172a">
  @vite(['resources/css/app.css'])
  <style>
    body {
      background: #0a0f1e;
      background-image: radial-gradient(ellipse 80% 50% at 50% -20%, rgba(99,102,241,0.15), transparent);
    }
    .glow-ring { box-shadow: 0 0 0 1px rgba(99,102,241,0.2), 0 25px 50px rgba(0,0,0,0.5); }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-12">
  <div class="w-full max-w-sm">

    {{-- Badge --}}
    <div class="flex justify-center mb-6">
      <div class="flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs font-medium px-4 py-1.5 rounded-full">
        ✅ Google sign-in verified
      </div>
    </div>

    <div class="glow-ring bg-slate-900/80 backdrop-blur-xl border border-slate-700/50 rounded-3xl p-8">

      <div class="text-center mb-7">
        <div class="text-3xl mb-3">🔐</div>
        <h1 class="text-xl font-bold text-white">Two-factor authentication</h1>
        <p class="text-slate-400 text-sm mt-1.5">Open <strong class="text-white">Google Authenticator</strong> and enter the 6-digit code for HawkerOps.</p>
      </div>

      {{-- Error --}}
      @if(session('error'))
        <div class="mb-5 flex items-center gap-2 bg-red-500/10 border border-red-500/20 text-red-300 text-sm rounded-xl px-4 py-3">
          ⚠️ {{ session('error') }}
        </div>
      @endif

      <form method="POST" action="/auth/totp/check">
        @csrf
        <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
               autofocus autocomplete="one-time-code"
               placeholder="000000"
               class="w-full bg-slate-800 border border-slate-600 focus:border-indigo-500 text-white text-center text-3xl tracking-[0.6em] font-mono rounded-2xl px-4 py-5 outline-none transition mb-5">
        <button type="submit"
          class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-2xl px-5 py-3.5 transition">
          Verify &amp; Sign in
        </button>
      </form>

      <div class="mt-5 text-center">
        <a href="/login" class="text-slate-500 hover:text-slate-400 text-xs transition">
          ← Sign in with a different account
        </a>
      </div>

    </div>

    <p class="text-slate-700 text-xs text-center mt-6">⚡ HawkerOps · Store Operations Monitor</p>
  </div>
</body>
</html>
