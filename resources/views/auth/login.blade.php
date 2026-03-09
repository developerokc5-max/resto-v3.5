<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign in — HawkerOps</title>
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#ffffff">
  @vite(['resources/css/app.css'])
  <style>
    body { background: #f1f5f9; }
    .card-shadow { box-shadow: 0 4px 6px -1px rgba(0,0,0,0.07), 0 20px 60px -10px rgba(0,0,0,0.12); }
    .btn-google {
      transition: all 0.2s ease;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1), 0 1px 2px rgba(0,0,0,0.06);
    }
    .btn-google:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .divider { background: linear-gradient(to right, transparent, #e2e8f0, transparent); }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-12">

  <div class="w-full max-w-sm">

    {{-- Logo --}}
    <div class="text-center mb-8">
      <div class="flex justify-center mb-4">
        <div class="bg-white rounded-2xl p-3 shadow-md inline-flex">
          <img src="/images/logo-light.png" alt="HawkerOps" class="h-9 w-auto">
        </div>
      </div>
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Sign In</h1>
      <p class="text-slate-500 text-sm mt-1">Enter your credentials to continue</p>
    </div>

    {{-- Card --}}
    <div class="card-shadow bg-white rounded-2xl p-8">

      {{-- Error --}}
      @if(session('error'))
        <div class="mb-5 flex items-center gap-2.5 bg-red-50 border border-red-200 text-red-600 text-sm rounded-xl px-4 py-3">
          <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>{{ session('error') }}</span>
        </div>
      @endif

      {{-- Status --}}
      @if(session('status'))
        <div class="mb-5 flex items-center gap-2.5 bg-slate-50 border border-slate-200 text-slate-600 text-sm rounded-xl px-4 py-3">
          <svg class="w-4 h-4 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span>{{ session('status') }}</span>
        </div>
      @endif

      {{-- Platform badges --}}
      <div class="flex items-center justify-center gap-2 mb-6">
        <span class="flex items-center gap-1 text-xs text-slate-400 bg-slate-50 border border-slate-100 px-2.5 py-1 rounded-full">
          <span class="w-1.5 h-1.5 rounded-full bg-green-400 inline-block"></span> Grab
        </span>
        <span class="flex items-center gap-1 text-xs text-slate-400 bg-slate-50 border border-slate-100 px-2.5 py-1 rounded-full">
          <span class="w-1.5 h-1.5 rounded-full bg-pink-400 inline-block"></span> FoodPanda
        </span>
        <span class="flex items-center gap-1 text-xs text-slate-400 bg-slate-50 border border-slate-100 px-2.5 py-1 rounded-full">
          <span class="w-1.5 h-1.5 rounded-full bg-sky-400 inline-block"></span> Deliveroo
        </span>
      </div>

      {{-- Divider --}}
      <div class="relative mb-6">
        <div class="divider h-px w-full"></div>
        <span class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white px-3 text-xs text-slate-400">
          authorised access only
        </span>
      </div>

      {{-- Google button --}}
      <a href="/auth/google"
         class="btn-google flex items-center justify-center gap-3 w-full bg-white border-2 border-slate-200 hover:border-slate-300 text-slate-700 font-semibold rounded-xl px-5 py-3.5 text-[15px]">
        <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24">
          <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
          <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
          <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
          <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
        Sign In with Google
      </a>

      <p class="text-slate-400 text-xs text-center mt-5 flex items-center justify-center gap-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        Protected by Google OAuth + 2FA
      </p>
    </div>

    <p class="text-slate-400 text-xs text-center mt-6">
      ⚡ HawkerOps · Store Operations Monitor
    </p>

  </div>

</body>
</html>
