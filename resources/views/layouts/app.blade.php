<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>{{ $title ?? 'HawkerOps' }}</title>
    <link rel="icon" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgdmlld0JveD0iMCAwIDMyIDMyIj4KICAKICA8cmVjdCB4PSI0IiB5PSI2IiB3aWR0aD0iMjQiIGhlaWdodD0iMTYiIHJ4PSIyIiBmaWxsPSIjMWUzYTVmIiBzdHJva2U9IiMzYjgyZjYiIHN0cm9rZS13aWR0aD0iMS41Ii8+CiAgPHJlY3QgeD0iNiIgeT0iOCIgd2lkdGg9IjIwIiBoZWlnaHQ9IjEyIiByeD0iMSIgZmlsbD0iIzBmMTcyYSIvPgogIDxwb2x5bGluZSBwb2ludHM9IjgsMTYgMTEsMTYgMTMsMTIgMTYsMjAgMTksMTMgMjIsMTYgMjUsMTYiIGZpbGw9Im5vbmUiIHN0cm9rZT0iIzIyYzU1ZSIgc3Ryb2tlLXdpZHRoPSIyIiBzdHJva2UtbGluZWNhcD0icm91bmQiIHN0cm9rZS1saW5lam9pbj0icm91bmQiLz4KICAKICA8cmVjdCB4PSIxMiIgeT0iMjIiIHdpZHRoPSI4IiBoZWlnaHQ9IjIiIGZpbGw9IiMzYjgyZjYiLz4KICAKICA8cmVjdCB4PSIxMCIgeT0iMjQiIHdpZHRoPSIxMiIgaGVpZ2h0PSIxLjUiIHJ4PSIwLjUiIGZpbGw9IiMzYjgyZjYiLz4KPC9zdmc+Cg==" type="image/svg+xml" />

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
