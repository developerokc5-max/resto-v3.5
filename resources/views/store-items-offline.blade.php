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
        .item-card { transition: transform 0.15s ease, box-shadow 0.15s ease; }
        .item-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
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
        $fpCount   = count($offlineItemsByPlatform['foodpanda']);
        $delCount  = count($offlineItemsByPlatform['deliveroo']);
    @endphp

    <!-- Page Title + Summary strip -->
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl md:text-2xl font-bold text-slate-900 dark:text-slate-100">Offline Items</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Items currently unavailable across delivery platforms</p>
        </div>
        @if($totalOfflineItems > 0)
            <div class="flex-shrink-0 text-right">
                <div class="text-3xl font-bold text-red-500 dark:text-red-400 leading-none">{{ $totalOfflineItems }}</div>
                <div class="text-xs text-slate-400 font-medium mt-0.5">Total OFF</div>
            </div>
        @endif
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
        <div class="space-y-5">
            @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                @php
                    $config    = $platformConfigs[$platform];
                    $items     = $offlineItemsByPlatform[$platform];
                    $itemCount = count($items);

                    $palette = [
                        'grab'      => ['gradient' => 'from-green-500 to-emerald-600',  'ring' => 'ring-green-200 dark:ring-green-800',  'light' => 'bg-green-50 dark:bg-green-900/20',  'text' => 'text-green-700 dark:text-green-400',  'border' => 'border-green-100 dark:border-green-900'],
                        'foodpanda' => ['gradient' => 'from-pink-500 to-rose-600',      'ring' => 'ring-pink-200 dark:ring-pink-800',    'light' => 'bg-pink-50 dark:bg-pink-900/20',    'text' => 'text-pink-700 dark:text-pink-400',    'border' => 'border-pink-100 dark:border-pink-900'],
                        'deliveroo' => ['gradient' => 'from-cyan-500 to-blue-600',      'ring' => 'ring-cyan-200 dark:ring-cyan-800',    'light' => 'bg-cyan-50 dark:bg-cyan-900/20',    'text' => 'text-cyan-700 dark:text-cyan-400',    'border' => 'border-cyan-100 dark:border-cyan-900'],
                    ][$platform];
                @endphp

                <!-- Platform Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden ring-1 {{ $palette['ring'] }}">

                    <!-- Platform Header -->
                    <div class="bg-gradient-to-r {{ $palette['gradient'] }} px-5 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                                <span class="text-white font-bold text-lg">{{ strtoupper(substr($config['name'], 0, 1)) }}</span>
                            </div>
                            <div>
                                <h3 class="text-white font-bold text-base leading-tight">{{ $config['name'] }}</h3>
                                <p class="text-white/70 text-xs">
                                    @if($config['last_checked'])
                                        Last checked {{ \Carbon\Carbon::parse($config['last_checked'])->diffForHumans() }}
                                    @else
                                        Never checked
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-white leading-none">{{ $itemCount }}</div>
                            <div class="text-white/70 text-xs">items off</div>
                        </div>
                    </div>

                    <!-- Items Body -->
                    @if($itemCount == 0)
                        <div class="{{ $palette['light'] }} px-5 py-8 text-center">
                            <svg class="w-8 h-8 {{ $palette['text'] }} mx-auto mb-2 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="{{ $palette['text'] }} font-semibold text-sm">All items available on {{ $config['name'] }}</p>
                        </div>
                    @else
                        <div class="p-5 space-y-5">
                            @php $groupedByCategory = collect($items)->groupBy('category'); @endphp

                            @foreach($groupedByCategory as $category => $categoryItems)
                                <!-- Category heading -->
                                <div>
                                    <div class="flex items-center gap-2 mb-3">
                                        <span class="{{ $palette['light'] }} {{ $palette['text'] }} {{ $palette['border'] }} border text-xs font-bold px-2.5 py-1 rounded-full uppercase tracking-wide">
                                            {{ $category }} · {{ count($categoryItems) }}
                                        </span>
                                        <div class="h-px flex-1 bg-slate-100 dark:bg-slate-700"></div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                        @foreach($categoryItems as $item)
                                            <div class="item-card bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-700 rounded-xl p-3 flex gap-3">
                                                @if($item['image_url'])
                                                    <img src="{{ $item['image_url'] }}"
                                                         alt="{{ $item['name'] }}"
                                                         class="w-14 h-14 rounded-lg object-cover flex-shrink-0 border border-slate-200 dark:border-slate-700"
                                                         onerror="this.style.display='none'">
                                                @else
                                                    <div class="w-14 h-14 rounded-lg bg-slate-200 dark:bg-slate-700 flex-shrink-0 flex items-center justify-center">
                                                        <svg class="w-5 h-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                                                        </svg>
                                                    </div>
                                                @endif
                                                <div class="flex-1 min-w-0">
                                                    <h5 class="font-semibold text-slate-900 dark:text-slate-100 text-sm line-clamp-2 leading-snug mb-1.5">{{ $item['name'] }}</h5>
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">${{ number_format($item['price'], 2) }}</span>
                                                        <span class="px-1.5 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-md text-[10px] font-bold">OFF</span>
                                                    </div>
                                                    @if($item['updated_at'])
                                                        <p class="text-[10px] text-slate-400 mt-1">{{ \Carbon\Carbon::parse($item['updated_at'])->diffForHumans() }}</p>
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
        </div>
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
</script>
</body>
</html>
