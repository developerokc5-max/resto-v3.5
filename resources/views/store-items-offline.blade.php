<!DOCTYPE html>
<html lang="en" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brandName }} — Offline Items</title>
    <link rel="icon" type="image/png" href="/favicon.png" />
    <script>if (localStorage.getItem('darkMode') === 'true') document.documentElement.classList.add('dark');</script>
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        .item-row { transition: background 0.1s ease; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-200">

<!-- Sticky Header -->
<header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-50">
    <div class="max-w-4xl mx-auto px-4 h-14 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
            <a href="/dashboard" class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div class="min-w-0">
                <div class="font-bold text-slate-900 dark:text-slate-100 text-sm leading-tight truncate">{{ $brandName }}</div>
                <div class="text-xs text-slate-400 truncate leading-tight">{{ $shopName }}</div>
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

<main id="main-content" class="max-w-4xl mx-auto px-4 py-8 space-y-6">

    @php
        $grabCount = count($offlineItemsByPlatform['grab'] ?? []);
        $fpCount   = count($offlineItemsByPlatform['foodpanda'] ?? []);
        $platformMeta = [
            'grab'      => ['name' => 'Grab',      'color' => 'bg-green-500', 'ring' => 'ring-green-500/20', 'badge' => 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400', 'cat' => 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400',  'count' => $grabCount],
            'foodpanda' => ['name' => 'foodpanda', 'color' => 'bg-pink-500',  'ring' => 'ring-pink-500/20',  'badge' => 'bg-pink-50 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400',   'cat' => 'bg-pink-50 dark:bg-pink-900/20 text-pink-600 dark:text-pink-400',   'count' => $fpCount],
        ];
    @endphp

    @if($totalOfflineItems == 0)
        <!-- All Good State -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-16 text-center shadow-sm">
            <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-2">All Items Available</h3>
            <p class="text-slate-500 dark:text-slate-400 text-sm">No offline items found across all platforms.</p>
        </div>
    @else

        <!-- Summary Bar -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 pt-6 pb-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Offline Items</h1>
                        <p class="text-sm text-slate-400 mt-0.5">Items currently unavailable</p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-3xl font-black text-red-500 leading-none">{{ $totalOfflineItems }}</div>
                        <div class="text-xs text-slate-400 font-medium mt-0.5">total off</div>
                    </div>
                </div>
            </div>

            <!-- Platform count pills -->
            <div class="grid grid-cols-2 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-700">
                @foreach($platformMeta as $p => $meta)
                    <div class="px-5 py-4 {{ !$loop->last ? 'border-r border-slate-200 dark:border-slate-700' : '' }} flex items-center gap-3">
                        <div class="w-7 h-7 {{ $meta['color'] }} rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-black text-xs">{{ strtoupper(substr($meta['name'], 0, 1)) }}</span>
                        </div>
                        <div>
                            <div class="text-xs text-slate-400 leading-tight">{{ $meta['name'] }}</div>
                            <div class="font-bold text-sm leading-tight {{ $meta['count'] > 0 ? 'text-red-500' : 'text-green-500' }}">
                                {{ $meta['count'] > 0 ? $meta['count'].' off' : 'All on' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Platform Sections -->
        @foreach(['grab', 'foodpanda'] as $platform)
            @php
                $meta      = $platformMeta[$platform];
                $items     = $offlineItemsByPlatform[$platform] ?? [];
                $itemCount = count($items);
                $config    = $platformConfigs[$platform];
            @endphp

            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden ring-1 {{ $meta['ring'] }}">

                <!-- Platform Header -->
                <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 {{ $meta['color'] }} rounded-xl flex items-center justify-center flex-shrink-0">
                            <span class="text-white font-black text-sm">{{ strtoupper(substr($meta['name'], 0, 1)) }}</span>
                        </div>
                        <div>
                            <div class="font-bold text-slate-900 dark:text-slate-100 text-sm">{{ $meta['name'] }}</div>
                            <div class="text-xs text-slate-400">
                                @if($config['last_checked'])
                                    Last checked {{ \Carbon\Carbon::parse($config['last_checked'])->diffForHumans() }}
                                @else
                                    Never checked
                                @endif
                            </div>
                        </div>
                    </div>
                    @if($itemCount > 0)
                        <span class="bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-300 dark:border-red-800 font-bold text-xs px-3 py-1.5 rounded-full">
                            {{ $itemCount }} item{{ $itemCount !== 1 ? 's' : '' }} off
                        </span>
                    @else
                        <span class="bg-emerald-100 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-300 dark:border-emerald-800 font-semibold text-xs px-3 py-1.5 rounded-full">
                            All available
                        </span>
                    @endif
                </div>

                <!-- Items -->
                @if($itemCount == 0)
                    <div class="px-6 py-10 text-center">
                        <svg class="w-7 h-7 text-green-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm text-slate-400 font-medium">All items available on {{ $meta['name'] }}</p>
                    </div>
                @else
                    @php $groupedByCategory = collect($items)->groupBy('category'); @endphp

                    <div class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($groupedByCategory as $category => $categoryItems)

                            <!-- Category Row -->
                            <div class="px-6 py-2.5 {{ $meta['badge'] }} flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider">{{ $category }}</span>
                                <span class="text-xs font-semibold opacity-70">{{ count($categoryItems) }} item{{ count($categoryItems) !== 1 ? 's' : '' }}</span>
                            </div>

                            <!-- Item Rows -->
                            @foreach($categoryItems as $item)
                                <div class="item-row flex items-center gap-4 px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-800/50">

                                    <!-- Image -->
                                    @if($item['image_url'])
                                        <img src="{{ $item['image_url'] }}"
                                             alt="{{ $item['name'] }}"
                                             class="w-12 h-12 rounded-xl object-cover flex-shrink-0 border border-slate-100 dark:border-slate-700"
                                             onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                        <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 flex-shrink-0 items-center justify-center hidden">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 flex-shrink-0 flex items-center justify-center">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                                            </svg>
                                        </div>
                                    @endif

                                    <!-- Name + timestamp -->
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-slate-900 dark:text-slate-100 text-sm truncate">{{ $item['name'] }}</div>
                                        @if($item['updated_at'])
                                            <div class="text-xs text-slate-400 mt-0.5">{{ \Carbon\Carbon::parse($item['updated_at'])->diffForHumans() }}</div>
                                        @endif
                                    </div>

                                    <!-- Price + OFF badge -->
                                    <div class="flex items-center gap-2.5 flex-shrink-0">
                                        <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">${{ number_format($item['price'], 2) }}</span>
                                        <span class="bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded-md tracking-wide">OFF</span>
                                    </div>
                                </div>
                            @endforeach

                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

    @endif

    <!-- Bottom nav -->
    <div class="flex items-center justify-between pt-2 pb-6">
        <a href="/dashboard" class="flex items-center gap-2 text-sm text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition font-medium">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Dashboard
        </a>
        <a href="/store/{{ $shopId }}/logs" class="flex items-center gap-2 text-sm text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition font-medium">
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
<script src="/js/hawkerops.js?v=5" defer></script>
</body>
</html>
