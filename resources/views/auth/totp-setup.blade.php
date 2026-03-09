<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set up 2FA — HawkerOps</title>
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
  <div class="w-full max-w-md">

    {{-- Badge --}}
    <div class="flex justify-center mb-6">
      <div class="flex items-center gap-2 bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-medium px-4 py-1.5 rounded-full">
        🔐 One-time setup · {{ $email }}
      </div>
    </div>

    <div class="glow-ring bg-slate-900/80 backdrop-blur-xl border border-slate-700/50 rounded-3xl p-8">

      <div class="text-center mb-6">
        <div class="text-3xl mb-3">📱</div>
        <h1 class="text-xl font-bold text-white">Set up Google Authenticator</h1>
        <p class="text-slate-400 text-sm mt-1.5">Scan the QR code once, then enter the code to confirm.</p>
      </div>

      {{-- Steps --}}
      <div class="flex items-start gap-3 bg-slate-800/60 border border-slate-700/50 rounded-2xl p-4 mb-6 text-sm text-slate-300">
        <div class="space-y-1.5">
          <p>1. Download <strong class="text-white">Google Authenticator</strong> on your phone</p>
          <p>2. Tap <strong class="text-white">+</strong> → <strong class="text-white">Scan QR code</strong></p>
          <p>3. Scan the code below</p>
          <p>4. Enter the 6-digit code to confirm</p>
        </div>
      </div>

      {{-- QR Code --}}
      <div class="flex justify-center mb-6">
        <div class="bg-white p-4 rounded-2xl shadow-lg">
          <img src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="QR Code" class="w-48 h-48 block">
        </div>
      </div>

      {{-- Manual key fallback --}}
      <div class="bg-slate-800/60 border border-slate-700/50 rounded-xl px-4 py-3 mb-6 text-center">
        <p class="text-slate-500 text-xs mb-1">Can't scan? Enter this key manually:</p>
        <code class="text-indigo-300 text-sm font-mono tracking-widest">{{ $secret }}</code>
      </div>

      {{-- Error --}}
      @if(session('error'))
        <div class="mb-4 flex items-center gap-2 bg-red-500/10 border border-red-500/20 text-red-300 text-sm rounded-xl px-4 py-3">
          ⚠️ {{ session('error') }}
        </div>
      @endif

      {{-- Confirm form --}}
      <form method="POST" action="/auth/totp/setup/confirm">
        @csrf
        <label class="block text-slate-400 text-sm mb-2 text-center">Enter the 6-digit code from the app</label>
        <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
               autofocus autocomplete="one-time-code"
               placeholder="000000"
               class="w-full bg-slate-800 border border-slate-600 focus:border-indigo-500 text-white text-center text-2xl tracking-[0.5em] font-mono rounded-2xl px-4 py-4 outline-none transition mb-4">
        <button type="submit"
          class="w-full bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-2xl px-5 py-3.5 transition">
          Confirm &amp; Activate 2FA
        </button>
      </form>

    </div>

    <p class="text-slate-700 text-xs text-center mt-6">⚡ HawkerOps · Store Operations Monitor</p>
  </div>
</body>
</html>
