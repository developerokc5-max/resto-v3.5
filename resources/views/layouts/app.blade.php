<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>{{ $title ?? 'HawkerOps' }}</title>
    <link rel="icon" href="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNiIgaGVpZ2h0PSIxNiIgdmlld0JveD0iMCAwIDE2IDE2Ij48cmVjdCB4PSIxIiB5PSIyIiB3aWR0aD0iMTQiIGhlaWdodD0iMTAiIHJ4PSIxLjUiIGZpbGw9IiMxZTNhNWYiIHN0cm9rZT0iIzNiODJmNiIgc3Ryb2tlLXdpZHRoPSIxIi8+PHJlY3QgeD0iMi41IiB5PSIzLjUiIHdpZHRoPSIxMSIgaGVpZ2h0PSI3IiByeD0iMC41IiBmaWxsPSIjMGYxNzJhIi8+PHBvbHlsaW5lIHBvaW50cz0iMy41LDcgNSw3IDYsNSA3LjUsOSA5LDYgMTAuNSw3IDEyLjUsNyIgZmlsbD0ibm9uZSIgc3Ryb2tlPSIjMjJjNTVlIiBzdHJva2Utd2lkdGg9IjEuMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+PHJlY3QgeD0iNi41IiB5PSIxMiIgd2lkdGg9IjMiIGhlaWdodD0iMS41IiBmaWxsPSIjM2I4MmY2Ii8+PHJlY3QgeD0iNSIgeT0iMTMuNSIgd2lkdGg9IjYiIGhlaWdodD0iMSIgcng9IjAuNSIgZmlsbD0iIzNiODJmNiIvPjwvc3ZnPg==" type="image/svg+xml" />

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
