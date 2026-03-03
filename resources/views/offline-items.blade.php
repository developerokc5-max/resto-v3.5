<!DOCTYPE html>
<html lang="en" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline Items Monitor - HawkerOps</title>
    <link rel="icon" type="image/png" href="/favicon.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .filter-badge { cursor: pointer; transition: all 0.2s ease; }
        .filter-badge:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); }
        .filter-badge.active { box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15); }
        .store-card { display: block; }
        .store-card.hidden { display: none; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-200">
    <!-- Header -->
    <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Platform Status Monitor</h1>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">Track platform availability across all stores</p>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="toggleDarkMode()" id="darkToggle" class="h-8 w-8 rounded-full bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs flex items-center justify-center transition" title="Toggle dark mode">
                        <span id="darkIcon">🌙</span>
                    </button>
                    <a href="/" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-medium transition">
                        ← Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Stats Overview -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
                <div class="text-sm text-slate-500 dark:text-slate-400">Total Stores</div>
                <div class="mt-2 text-3xl font-semibold dark:text-slate-100">{{ $stats['total_stores'] }}</div>
            </div>
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/30 dark:to-emerald-900/30 border border-green-200 dark:border-green-700 rounded-2xl p-5">
                <div class="text-sm text-green-700 dark:text-green-400 font-medium">All Platforms Online</div>
                <div class="mt-2 text-3xl font-semibold text-green-900 dark:text-green-100">{{ $stats['all_online'] }}</div>
                <div class="mt-1 text-xs text-green-600 dark:text-green-500">Perfect operational status</div>
            </div>
            <div class="bg-gradient-to-br from-red-50 to-rose-50 dark:from-red-900/30 dark:to-rose-900/30 border border-red-200 dark:border-red-700 rounded-2xl p-5">
                <div class="text-sm text-red-700 dark:text-red-400 font-medium">All Platforms Offline</div>
                <div class="mt-2 text-3xl font-semibold text-red-900 dark:text-red-100">{{ $stats['all_offline'] }}</div>
                <div class="mt-1 text-xs text-red-600 dark:text-red-500">Require immediate attention</div>
            </div>
            <div class="bg-gradient-to-br from-amber-50 to-yellow-50 dark:from-amber-900/30 dark:to-yellow-900/30 border border-amber-200 dark:border-amber-700 rounded-2xl p-5">
                <div class="text-sm text-amber-700 dark:text-amber-400 font-medium">Partial Offline</div>
                <div class="mt-2 text-3xl font-semibold text-amber-900 dark:text-amber-100">{{ $stats['mixed'] }}</div>
                <div class="mt-1 text-xs text-amber-600 dark:text-amber-500">Some platforms affected</div>
            </div>
        </section>

        <!-- Filter Badges -->
        <section class="flex flex-wrap gap-3 mb-6">
            <button onclick="filterStores('all')" class="filter-badge active px-4 py-2 bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-300 shadow-sm" id="filter-all">
                Show All ({{ $stats['total_stores'] }})
            </button>
            <button onclick="filterStores('all_online')" class="filter-badge px-4 py-2 bg-green-50 dark:bg-green-900/20 border-2 border-green-200 dark:border-green-800 rounded-lg text-sm font-semibold text-green-700 dark:text-green-400 shadow-sm" id="filter-all_online">
                ✓ All Platforms Online ({{ $stats['all_online'] }})
            </button>
            <button onclick="filterStores('all_offline')" class="filter-badge px-4 py-2 bg-red-50 dark:bg-red-900/20 border-2 border-red-200 dark:border-red-800 rounded-lg text-sm font-semibold text-red-700 dark:text-red-400 shadow-sm" id="filter-all_offline">
                ✕ All Platforms Offline ({{ $stats['all_offline'] }})
            </button>
            <button onclick="filterStores('mixed')" class="filter-badge px-4 py-2 bg-amber-50 dark:bg-amber-900/20 border-2 border-amber-200 dark:border-amber-800 rounded-lg text-sm font-semibold text-amber-700 dark:text-amber-400 shadow-sm" id="filter-mixed">
                ⚠ Partial Offline ({{ $stats['mixed'] }})
            </button>
        </section>

        <!-- Last Update Info -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg px-4 py-3 mb-6 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm text-blue-800 dark:text-blue-200">
                    <span class="font-medium">Last platform check:</span> {{ $lastScrapeAgo }}
                </span>
            </div>
            <span class="text-xs text-blue-600 dark:text-blue-300">{{ $lastScrape }}</span>
        </div>

        <!-- Store Cards Grid -->
        <section class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            @php
                $platformConfigs = [
                    'grab' => [
                        'name' => 'Grab',
                        'border' => 'border-green-500',
                        'text' => 'text-green-700 dark:text-green-400',
                        'bg' => 'bg-green-50 dark:bg-green-900/20',
                        'badge' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                    ],
                    'foodpanda' => [
                        'name' => 'foodpanda',
                        'border' => 'border-pink-500',
                        'text' => 'text-pink-700 dark:text-pink-400',
                        'bg' => 'bg-pink-50 dark:bg-pink-900/20',
                        'badge' => 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400'
                    ],
                    'deliveroo' => [
                        'name' => 'Deliveroo',
                        'border' => 'border-cyan-500',
                        'text' => 'text-cyan-700 dark:text-cyan-400',
                        'bg' => 'bg-cyan-50 dark:bg-cyan-900/20',
                        'badge' => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400'
                    ]
                ];
            @endphp

            @foreach($stores as $store)
                <div class="store-card bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm hover:shadow-lg transition"
                     data-status="{{ $store['overall_status'] }}">

                    <!-- Store Header -->
                    <div class="p-5 border-b border-slate-100 dark:border-slate-700">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100 truncate">{{ $store['shop_name'] }}</h3>
                                @if($store['brand'])
                                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">{{ $store['brand'] }}</p>
                                @endif
                            </div>

                            @if($store['overall_status'] === 'all_online')
                                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-xs font-semibold whitespace-nowrap flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    All Platforms Online
                                </span>
                            @elseif($store['overall_status'] === 'all_offline')
                                <span class="px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-xs font-semibold whitespace-nowrap flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    All Platforms Offline
                                </span>
                            @else
                                <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full text-xs font-semibold whitespace-nowrap">
                                    {{ $store['online_count'] }}/3 Online
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Platform Status Cards -->
                    <div class="p-4 space-y-3">
                        @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                            @php
                                $config = $platformConfigs[$platform];
                                $platformData = $store['platforms'][$platform] ?? null;
                                $isOnline = $platformData['is_online'] ?? false;
                            @endphp

                            <div class="border-l-4 {{ $isOnline ? $config['border'] : 'border-red-500' }} bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg px-3 py-2.5">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="font-semibold text-sm text-slate-900 dark:text-slate-100">{{ $config['name'] }}</div>
                                        <div class="text-xs {{ $isOnline ? $config['text'] : 'text-red-700 dark:text-red-400' }} font-medium">
                                            {{ $isOnline ? 'Online • Items Active' : 'OFFLINE • Check Required' }}
                                        </div>
                                    </div>
                                    <div class="text-right ml-3">
                                        @php
                                            $lastChecked = $platformData['last_checked'] ?? 'Never';
                                        @endphp
                                        <div class="text-[11px] text-slate-500 dark:text-slate-400 mb-0.5">{{ $lastChecked }}</div>
                                        @if($isOnline)
                                            <div class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $config['badge'] }}">
                                                Platform Online
                                            </div>
                                        @else
                                            <div class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                                                Platform Offline
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                @if(!$isOnline)
                                    <div class="mt-3 pt-3 border-t border-red-100 dark:border-red-900/50">
                                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg px-3 py-2">
                                            <div class="text-xs font-semibold text-red-800 dark:text-red-300 mb-1">All items affected when platform is offline</div>
                                            <div class="text-[11px] text-red-600 dark:text-red-400">
                                                Platform went offline: {{ $lastChecked }}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- Store Footer -->
                    <div class="px-5 py-3 bg-slate-50 dark:bg-slate-700/50 border-t border-slate-100 dark:border-slate-700 rounded-b-2xl">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 dark:text-slate-400">Shop ID: {{ $store['shop_id'] }}</span>
                            <a href="/store/{{ $store['shop_id'] }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">
                                View Details →
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </section>

        @if(count($stores) === 0)
            <div class="bg-white dark:bg-slate-800 border-2 border-dashed border-slate-300 dark:border-slate-700 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-slate-300 dark:text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
                <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100 mb-2">No stores found</h3>
                <p class="text-slate-600 dark:text-slate-400">No platform status data available yet.</p>
            </div>
        @endif
    </main>

    <script>
        function toggleDarkMode() {
            const html = document.getElementById('html-root');
            const icon = document.getElementById('darkIcon');
            const isDark = html.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
            icon.textContent = isDark ? '☀️' : '🌙';
        }
        document.addEventListener('DOMContentLoaded', () => {
            const icon = document.getElementById('darkIcon');
            if (icon) icon.textContent = localStorage.getItem('darkMode') === 'true' ? '☀️' : '🌙';
        });

        function filterStores(status) {
            const allCards = document.querySelectorAll('.store-card');
            const allButtons = document.querySelectorAll('.filter-badge');
            allButtons.forEach(btn => btn.classList.remove('active'));
            document.getElementById('filter-' + status).classList.add('active');
            allCards.forEach(card => {
                const cardStatus = card.getAttribute('data-status');
                if (status === 'all' || cardStatus === status) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }
    </script>
</body>
</html>
