<!DOCTYPE html>
<html lang="en" class="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign in — HawkerOps</title>
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#0f172a">
  @vite(['resources/css/app.css'])
  <style>
    body {
      background: #0a0f1e;
      background-image:
        radial-gradient(ellipse 80% 50% at 50% -20%, rgba(99,102,241,0.15), transparent),
        radial-gradient(ellipse 60% 40% at 80% 80%, rgba(139,92,246,0.08), transparent);
    }
    .grid-bg {
      background-image: linear-gradient(rgba(99,102,241,0.04) 1px, transparent 1px),
                        linear-gradient(90deg, rgba(99,102,241,0.04) 1px, transparent 1px);
      background-size: 40px 40px;
    }
    .glow-ring {
      box-shadow: 0 0 0 1px rgba(99,102,241,0.2), 0 0 40px rgba(99,102,241,0.08), 0 25px 50px rgba(0,0,0,0.5);
    }
    .btn-google:hover { transform: translateY(-1px); box-shadow: 0 8px 25px rgba(0,0,0,0.3); }
    .btn-google { transition: all 0.2s ease; }
    .pill { backdrop-filter: blur(8px); }
  </style>
</head>
<body class="min-h-screen grid-bg flex items-center justify-center px-4 py-12">

  <div class="w-full max-w-md">

    {{-- Top badge --}}
    <div class="flex justify-center mb-8">
      <div class="pill flex items-center gap-2 bg-indigo-500/10 border border-indigo-500/20 text-indigo-300 text-xs font-medium px-4 py-1.5 rounded-full">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse inline-block"></span>
        Live monitoring · 46 stores
      </div>
    </div>

    {{-- Main card --}}
    <div class="glow-ring bg-slate-900/80 backdrop-blur-xl border border-slate-700/50 rounded-3xl p-8 md:p-10">

      {{-- Logo + branding --}}
      <div class="text-center mb-8">
        <div class="flex justify-center mb-5">
          <div class="relative">
            <div class="absolute inset-0 bg-indigo-500/20 rounded-2xl blur-xl"></div>
            <div class="relative bg-slate-800 border border-slate-700 rounded-2xl p-3">
              <img src="/images/logo-dark.png" alt="HawkerOps" class="h-10 w-auto">
            </div>
          </div>
        </div>
        <h1 class="text-2xl font-bold text-white tracking-tight">HawkerOps</h1>
        <p class="text-slate-400 text-sm mt-1.5">Store operations dashboard</p>
      </div>

      {{-- Platform pills --}}
      <div class="flex items-center justify-center gap-2 mb-8">
        <span class="flex items-center gap-1.5 bg-green-500/10 border border-green-500/20 text-green-400 text-xs px-3 py-1 rounded-full font-medium">
          <span class="w-1.5 h-1.5 rounded-full bg-green-400 inline-block"></span>Grab
        </span>
        <span class="flex items-center gap-1.5 bg-pink-500/10 border border-pink-500/20 text-pink-400 text-xs px-3 py-1 rounded-full font-medium">
          <span class="w-1.5 h-1.5 rounded-full bg-pink-400 inline-block"></span>FoodPanda
        </span>
        <span class="flex items-center gap-1.5 bg-sky-500/10 border border-sky-500/20 text-sky-400 text-xs px-3 py-1 rounded-full font-medium">
          <span class="w-1.5 h-1.5 rounded-full bg-sky-400 inline-block"></span>Deliveroo
        </span>
      </div>

      {{-- Divider --}}
      <div class="border-t border-slate-700/50 mb-8"></div>

      {{-- Error --}}
      @if(session('error'))
        <div class="mb-6 flex items-center gap-2.5 bg-red-500/10 border border-red-500/20 text-red-300 text-sm rounded-2xl px-4 py-3">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>{{ session('error') }}</span>
        </div>
      @endif

      {{-- Status --}}
      @if(session('status'))
        <div class="mb-6 flex items-center gap-2.5 bg-slate-700/50 border border-slate-600/50 text-slate-300 text-sm rounded-2xl px-4 py-3">
          <svg class="w-4 h-4 flex-shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span>{{ session('status') }}</span>
        </div>
      @endif

      {{-- Sign in label --}}
      <p class="text-slate-400 text-sm text-center mb-4">
        Sign in to access your dashboard
      </p>

      {{-- Google button --}}
      <a href="/auth/google"
         class="btn-google flex items-center justify-center gap-3 w-full bg-white text-slate-800 font-semibold rounded-2xl px-5 py-4 shadow-lg text-[15px]">
        <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        Continue with Google
      </a>

      <p class="text-slate-600 text-xs text-center mt-5">
        🔒 Authorised accounts only
      </p>
    </div>

    {{-- Footer --}}
    <p class="text-slate-700 text-xs text-center mt-6">
      ⚡ HawkerOps · Store Operations Monitor
    </p>

  </div>

</body>
</html>
