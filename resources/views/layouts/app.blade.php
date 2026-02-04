<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>{{ $title ?? 'HawkerOps' }}</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 16 16'><rect x='1' y='2' width='14' height='10' rx='1.5' fill='%231e3a5f' stroke='%233b82f6' stroke-width='1'/><rect x='2.5' y='3.5' width='11' height='7' rx='0.5' fill='%230f172a'/><polyline points='3.5,7 5,7 6,5 7.5,9 9,6 10.5,7 12.5,7' fill='none' stroke='%2322c55e' stroke-width='1.2' stroke-linecap='round' stroke-linejoin='round'/><rect x='6.5' y='12' width='3' height='1.5' fill='%233b82f6'/><rect x='5' y='13.5' width='6' height='1' rx='0.5' fill='%233b82f6'/></svg>" />

    {{-- Vite --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Livewire --}}
    @livewireStyles
</head>

<body class="bg-slate-100 text-slate-900 min-h-screen">

    <!-- Top Navigation -->
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-6">
            <div class="font-bold text-lg text-slate-800">
                HawkerOps
            </div>

            <nav class="flex gap-4 text-sm">
                <a href="{{ route('rs.dashboard') }}"
                   class="hover:text-blue-600 {{ request()->routeIs('rs.dashboard') ? 'text-blue-600 font-semibold' : '' }}">
                    Dashboard
                </a>

                <a href="{{ route('rs.shops') }}"
                   class="hover:text-blue-600 {{ request()->routeIs('rs.shops*') ? 'text-blue-600 font-semibold' : '' }}">
                    Shops
                </a>

                <a href="{{ route('rs.changes') }}"
                   class="hover:text-blue-600 {{ request()->routeIs('rs.changes') ? 'text-blue-600 font-semibold' : '' }}">
                    Changes
                </a>
            </nav>
        </div>
    </header>

    <!-- Page Header -->
    <section class="bg-slate-50 border-b border-slate-200">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <h1 class="text-xl font-semibold">
                {{ $pageHeading ?? '' }}
            </h1>

            @if(!empty($subtitle))
                <p class="text-sm text-slate-500 mt-1">
                    {{ $subtitle }}
                </p>
            @endif
        </div>
    </section>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 py-6">
        {{ $slot }}
    </main>

    {{-- Livewire --}}
    @livewireScripts
</body>
</html>
