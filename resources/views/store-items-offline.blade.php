<!DOCTYPE html>
<html lang="en" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brandName }} — Offline Items</title>
    <link rel="icon" type="image/png" href="/favicon.png" />
    <script>if (localStorage.getItem('darkMode') === 'true') document.documentElement.classList.add('dark');</script>
    @vite(['resources/css/app.css'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
        .tab-btn.active { background: white; color: #0f172a; box-shadow: 0 1px 3px rgba(0,0,0,0.12); }
        .dark .tab-btn.active { background: #1e293b; color: #f1f5f9; }
        .item-card { transition: transform 0.15s ease, box-shadow 0.15s ease; }
        .item-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-200">

<!-- Sticky Header -->
<header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-50 shadow-sm">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 h-14 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <a href="/dashboard" class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div class="min-w-0">
                <div class="font-bold text-slate-900 dark:text-slate-100 text-sm md:text-base truncate leading-tight">{{ $brandName }}</div>
                <div class="text-xs text-slate-500 dark:text-slate-400 truncate leading-tight">{{ $shopName }}</div>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <button onclick="toggleDarkMode()" id="darkToggle" class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-500 dark:text-slate-400 flex items-center justify-center transition text-sm">
                <span id="darkIcon">🌙</span>
            </button>
            <button onclick="window.location.reload()" class="h-8 px-3 bg-slate-900 dark:bg-slate-700 text-white rounded-lg text-xs font-semibold hover:opacity-90 transition">
                Reload
            </button>
        </div>
    </div>
</header>

<main class="max-w-6xl mx-auto px-4 sm:px-6 py-6">

    @php
        $grabCount = count($offlineItemsByPlatform['grab']);
        $fpCount = count($offlineItemsByPlatform['foodpanda']);
        $delCount = count($offlineItemsByPlatform['deliveroo']);
    @endphp

    <!-- Page Title + Summary -->
    <div class="mb-6">
        <div class="flex items-start justify-between gap-4 mb-4">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-slate-900 dark:text-slate-100">Offline Items</h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Items currently unavailable across delivery platforms</p>
            </div>
            @if($totalOfflineItems > 0)
                <div class="flex-shrink-0 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl px-4 py-2 text-center">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $totalOfflineItems }}</div>
                    <div class="text-xs text-red-500 dark:text-red-400 font-medium">Total OFF</div>
                </div>
            @endif
        </div>

        <!-- Platform summary pills -->
        <div class="flex flex-wrap gap-2">
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-full text-xs font-semibold text-green-700 dark:text-green-400">
                <span class="w-2 h-2 rounded-full bg-green-500 flex-shrink-0"></span>
                Grab — {{ $grabCount }} off
            </div>
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-pink-50 dark:bg-pink-900/20 border border-pink-200 dark:border-pink-800 rounded-full text-xs font-semibold text-pink-700 dark:text-pink-400">
                <span class="w-2 h-2 rounded-full bg-pink-500 flex-shrink-0"></span>
                foodpanda — {{ $fpCount }} off
            </div>
            <div class="flex items-center gap-1.5 px-3 py-1.5 bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800 rounded-full text-xs font-semibold text-cyan-700 dark:text-cyan-400">
                <span class="w-2 h-2 rounded-full bg-cyan-500 flex-shrink-0"></span>
                Deliveroo — {{ $delCount }} off
            </div>
        </div>
    </div>

    @if($totalOfflineItems == 0)
        <!-- All Good State -->
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-16 text-center shadow-sm">
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-2">All Items Available</h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm">No offline items found across all platforms.</p>
        </div>
    @else
        <!-- Platform Tabs -->
        <div class="bg-slate-200 dark:bg-slate-800 p-1 rounded-xl flex gap-1 mb-6">
            <button onclick="switchTab('grab')" id="tab-grab" class="tab-btn active flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-slate-500 dark:text-slate-400 transition">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                Grab
                @if($grabCount > 0)<span class="ml-1 px-1.5 py-0.5 bg-red-500 text-white rounded-full text-[10px] font-bold">{{ $grabCount }}</span>@endif
            </button>
            <button onclick="switchTab('foodpanda')" id="tab-foodpanda" class="tab-btn flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-slate-500 dark:text-slate-400 transition">
                <span class="w-2 h-2 rounded-full bg-pink-500"></span>
                foodpanda
                @if($fpCount > 0)<span class="ml-1 px-1.5 py-0.5 bg-red-500 text-white rounded-full text-[10px] font-bold">{{ $fpCount }}</span>@endif
            </button>
            <button onclick="switchTab('deliveroo')" id="tab-deliveroo" class="tab-btn flex-1 flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-slate-500 dark:text-slate-400 transition">
                <span class="w-2 h-2 rounded-full bg-cyan-500"></span>
                Deliveroo
                @if($delCount > 0)<span class="ml-1 px-1.5 py-0.5 bg-red-500 text-white rounded-full text-[10px] font-bold">{{ $delCount }}</span>@endif
            </button>
        </div>

        <!-- Tab Panels -->
        @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
            @php
                $config = $platformConfigs[$platform];
                $items  = $offlineItemsByPlatform[$platform];
                $count  = count($items);
                $accent = ['grab' => 'green', 'foodpanda' => 'pink', 'deliveroo' => 'cyan'][$platform];
            @endphp

            <div id="panel-{{ $platform }}" class="tab-panel {{ $platform === 'grab' ? 'active' : '' }}">

                @if($count == 0)
                    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-12 text-center shadow-sm">
                        <div class="w-12 h-12 bg-{{ $accent }}-100 dark:bg-{{ $accent }}-900/20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-6 h-6 text-{{ $accent }}-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="font-semibold text-slate-700 dark:text-slate-300">All items available on {{ $config['name'] }}</p>
                        @if($config['last_checked'])
                            <p class="text-xs text-slate-400 mt-1">Last checked {{ \Carbon\Carbon::parse($config['last_checked'])->diffForHumans() }}</p>
                        @endif
                    </div>
                @else
                    <!-- Platform info bar -->
                    <div class="flex items-center justify-between mb-4 px-1">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">{{ $config['name'] }}</span>
                            <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-full text-xs font-bold">{{ $count }} items off</span>
                        </div>
                        @if($config['last_checked'])
                            <span class="text-xs text-slate-400 dark:text-slate-500">{{ \Carbon\Carbon::parse($config['last_checked'])->diffForHumans() }}</span>
                        @endif
                    </div>

                    @php $groupedByCategory = collect($items)->groupBy('category'); @endphp

                    <div class="space-y-6">
                        @foreach($groupedByCategory as $category => $categoryItems)
                            <div>
                                <!-- Category label -->
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></div>
                                    <span class="text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 px-2">{{ $category }} ({{ count($categoryItems) }})</span>
                                    <div class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></div>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    @foreach($categoryItems as $item)
                                        <div class="item-card bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 flex gap-3">
                                            @if($item['image_url'])
                                                <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}"
                                                     class="w-14 h-14 rounded-lg object-cover flex-shrink-0 border border-slate-100 dark:border-slate-700"
                                                     onerror="this.style.display='none'">
                                            @else
                                                <div class="w-14 h-14 rounded-lg bg-slate-100 dark:bg-slate-700 flex-shrink-0 flex items-center justify-center">
                                                    <svg class="w-6 h-6 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                                                    </svg>
                                                </div>
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                <h5 class="font-semibold text-slate-900 dark:text-slate-100 text-sm leading-snug line-clamp-2 mb-1">{{ $item['name'] }}</h5>
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">${{ number_format($item['price'], 2) }}</span>
                                                    <span class="px-1.5 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded text-[10px] font-bold">OFF</span>
                                                </div>
                                                @if($item['updated_at'])
                                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">{{ \Carbon\Carbon::parse($item['updated_at'])->diffForHumans() }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    @endif

    <!-- Bottom nav -->
    <div class="mt-8 flex items-center justify-between">
        <a href="/dashboard" class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Dashboard
        </a>
        <a href="/store/{{ $shopId }}/logs" class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition font-medium">
            View Logs
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </a>
    </div>

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

    function switchTab(platform) {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + platform).classList.add('active');
        document.getElementById('panel-' + platform).classList.add('active');
    }
</script>
</body>
</html>
