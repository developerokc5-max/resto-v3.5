<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brandName }} - {{ $shopName }} - Offline Items</title>
    <link rel="icon" type="image/png" href="/favicon.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .item-card { transition: all 0.2s ease; }
        .item-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12); }
    </style>
</head>
<body class="bg-slate-50">
    <!-- Header -->
    <header class="bg-white border-b-2 border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-slate-900">{{ $brandName }}</h1>
                    <p class="text-sm text-slate-600 mt-0.5">{{ $shopName }}</p>
                </div>
                <a href="/" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Summary Banner -->
        <div class="bg-gradient-to-r from-red-500 to-rose-600 rounded-2xl p-6 mb-8 shadow-lg">
            <div class="flex items-center justify-between text-white">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <h2 class="text-3xl font-bold">ITEMS CURRENTLY OFF</h2>
                    </div>
                    <p class="text-red-100 text-sm">Items marked as unavailable across delivery platforms</p>
                </div>
                <div class="text-right">
                    <div class="text-5xl font-bold">{{ $totalOfflineItems }}</div>
                    <div class="text-sm text-red-100">Total Offline</div>
                </div>
            </div>
        </div>

        @if($totalOfflineItems == 0)
            <!-- No Offline Items -->
            <div class="bg-white border-2 border-dashed border-slate-300 rounded-2xl p-16 text-center">
                <svg class="w-20 h-20 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h3 class="text-2xl font-bold text-slate-900 mb-2">All Items Available!</h3>
                <p class="text-slate-600">No offline items found for this store across all platforms.</p>
            </div>
        @else
            <!-- Platform Sections -->
            @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                @php
                    $config = $platformConfigs[$platform];
                    $items = $offlineItemsByPlatform[$platform];
                    $itemCount = count($items);

                    // Color schemes
                    $colors = [
                        'grab' => [
                            'bg' => 'bg-green-50',
                            'border' => 'border-green-500',
                            'text' => 'text-green-700',
                            'badge' => 'bg-green-100 text-green-800',
                            'button' => 'bg-green-600 hover:bg-green-700',
                            'header' => 'bg-gradient-to-r from-green-500 to-emerald-600',
                        ],
                        'foodpanda' => [
                            'bg' => 'bg-pink-50',
                            'border' => 'border-pink-500',
                            'text' => 'text-pink-700',
                            'badge' => 'bg-pink-100 text-pink-800',
                            'button' => 'bg-pink-600 hover:bg-pink-700',
                            'header' => 'bg-gradient-to-r from-pink-500 to-rose-600',
                        ],
                        'deliveroo' => [
                            'bg' => 'bg-cyan-50',
                            'border' => 'border-cyan-500',
                            'text' => 'text-cyan-700',
                            'badge' => 'bg-cyan-100 text-cyan-800',
                            'button' => 'bg-cyan-600 hover:bg-cyan-700',
                            'header' => 'bg-gradient-to-r from-cyan-500 to-blue-600',
                        ],
                    ];
                    $color = $colors[$platform];
                @endphp

                <section class="mb-8">
                    <!-- Platform Header -->
                    <div class="{{ $color['header'] }} rounded-t-2xl p-4 shadow-md">
                        <div class="flex items-center justify-between text-white">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-xl flex items-center justify-center">
                                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-xl font-bold">{{ $config['name'] }}</h3>
                                    @if($config['last_checked'])
                                        <p class="text-sm text-white text-opacity-90">
                                            Last checked: {{ \Carbon\Carbon::parse($config['last_checked'])->diffForHumans() }}
                                        </p>
                                    @else
                                        <p class="text-sm text-white text-opacity-90">Never checked</p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right">
                                @if($itemCount > 0)
                                    <div class="px-4 py-2 bg-white bg-opacity-25 rounded-lg">
                                        <div class="text-3xl font-bold">{{ $itemCount }}</div>
                                        <div class="text-xs text-white text-opacity-90">Items OFF</div>
                                    </div>
                                @else
                                    <div class="px-4 py-2 bg-white bg-opacity-20 rounded-lg">
                                        <div class="text-2xl font-bold">0</div>
                                        <div class="text-xs text-white text-opacity-90">Items OFF</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Platform Items -->
                    <div class="bg-white rounded-b-2xl border-2 border-t-0 {{ $color['border'] }} shadow-md">
                        @if($itemCount == 0)
                            <div class="{{ $color['bg'] }} p-8 text-center rounded-b-2xl">
                                <svg class="w-12 h-12 {{ $color['text'] }} mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="{{ $color['text'] }} font-medium">No offline items on this platform</p>
                            </div>
                        @else
                            <!-- Items Grid -->
                            <div class="p-6 space-y-4">
                                @php
                                    $groupedByCategory = collect($items)->groupBy('category');
                                @endphp

                                @foreach($groupedByCategory as $category => $categoryItems)
                                    <!-- Category Header -->
                                    <div class="{{ $color['bg'] }} rounded-lg p-3 border-l-4 {{ $color['border'] }}">
                                        <h4 class="{{ $color['text'] }} font-bold text-sm uppercase tracking-wide">
                                            {{ $category }} ({{ count($categoryItems) }})
                                        </h4>
                                    </div>

                                    <!-- Items in Category -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pl-4">
                                        @foreach($categoryItems as $item)
                                            <div class="item-card bg-white border-2 border-slate-200 rounded-xl p-4 hover:border-red-300">
                                                <div class="flex gap-3">
                                                    <!-- Item Image -->
                                                    @if($item['image_url'])
                                                        <div class="flex-shrink-0">
                                                            <img src="{{ $item['image_url'] }}"
                                                                 alt="{{ $item['name'] }}"
                                                                 class="w-16 h-16 rounded-lg object-cover border border-slate-200"
                                                                 onerror="this.style.display='none'">
                                                        </div>
                                                    @endif

                                                    <!-- Item Details -->
                                                    <div class="flex-1 min-w-0">
                                                        <h5 class="font-bold text-slate-900 text-sm mb-1 line-clamp-2">{{ $item['name'] }}</h5>
                                                        <div class="flex items-center gap-2 mb-2">
                                                            <span class="text-lg font-bold text-slate-900">${{ number_format($item['price'], 2) }}</span>
                                                            <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs font-semibold">
                                                                OFF
                                                            </span>
                                                        </div>
                                                        @if($item['updated_at'])
                                                            <p class="text-xs text-slate-500">
                                                                Updated: {{ \Carbon\Carbon::parse($item['updated_at'])->diffForHumans() }}
                                                            </p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </section>
            @endforeach
        @endif

        <!-- Footer Actions -->
        <div class="mt-8 bg-white border-2 border-slate-200 rounded-2xl p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="font-bold text-slate-900">Need to update items?</h4>
                    <p class="text-sm text-slate-600">Manage your menu items directly on the platform apps</p>
                </div>
                <div class="flex gap-3">
                    <a href="/dashboard" class="px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-medium transition">
                        Back to Dashboard
                    </a>
                    <button onclick="window.location.reload()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Refresh Data
                    </button>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
