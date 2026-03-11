<!DOCTYPE html>
<html lang="en" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $shopName }} — Status Log</title>
    <link rel="icon" type="image/png" href="/favicon.png" />
    <script>if (localStorage.getItem('darkMode') === 'true') document.documentElement.classList.add('dark');</script>
    @vite(['resources/css/app.css'])
    <style>
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        .dropdown-body { max-height: 0; overflow: hidden; transition: max-height 0.35s ease; }
        .dropdown-body.open { max-height: 2000px; }
        .chevron { transition: transform 0.25s ease; }
        .chevron.open { transform: rotate(180deg); }
        .platform-row { transition: background 0.1s ease; }
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

<main class="max-w-4xl mx-auto px-4 py-8 space-y-4">

    <!-- Page Title -->
    <div class="mb-2">
        <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">Status History</h1>
        <p class="text-sm text-slate-400 mt-0.5">Platform availability records for this outlet</p>
    </div>

    @if(empty($statusCards))
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-16 text-center shadow-sm">
            <div class="w-14 h-14 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <p class="font-semibold text-slate-700 dark:text-slate-300">No status records yet</p>
            <p class="text-sm text-slate-400 mt-1">Records will appear once the scraper runs.</p>
        </div>
    @else
        @foreach($statusCards as $index => $card)
            @php
                $isCurrent   = isset($card['is_current']) && $card['is_current'];
                $cardNumber  = $card['id'] ?? (count($statusCards) - $index);
                $ts          = \Carbon\Carbon::parse($card['timestamp'])->setTimezone('Asia/Singapore');
                $status      = $card['outlet_status'] ?? 'Mixed';
                $onlineCount = $card['platforms_online'] ?? 0;
                $totalOffline = $card['total_offline_items'] ?? 0;

                $accentColor = match(true) {
                    $status === 'All Online'  => 'bg-emerald-500',
                    $status === 'All Offline' => 'bg-red-500',
                    default                   => 'bg-amber-400',
                };
                $statusLabel = match(true) {
                    $status === 'All Online'  => ['text' => 'All Online',  'class' => 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800'],
                    $status === 'All Offline' => ['text' => 'All Offline', 'class' => 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800'],
                    default                   => ['text' => $onlineCount.'/3 Online', 'class' => 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800'],
                };

                $platformConfig = [
                    'grab'      => ['name' => 'Grab',      'color' => 'bg-green-500', 'textColor' => 'text-green-600 dark:text-green-400', 'lightBg' => 'bg-green-50 dark:bg-green-900/20'],
                    'foodpanda' => ['name' => 'foodpanda', 'color' => 'bg-pink-500',  'textColor' => 'text-pink-600 dark:text-pink-400',   'lightBg' => 'bg-pink-50 dark:bg-pink-900/20'],
                    'deliveroo' => ['name' => 'Deliveroo', 'color' => 'bg-cyan-500',  'textColor' => 'text-cyan-600 dark:text-cyan-400',   'lightBg' => 'bg-cyan-50 dark:bg-cyan-900/20'],
                ];
            @endphp

            <!-- Status Card -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">

                <!-- Card Header -->
                <div class="px-5 py-4 flex items-center justify-between gap-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- Accent dot -->
                        <div class="w-2.5 h-2.5 rounded-full {{ $accentColor }} flex-shrink-0"></div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="font-bold text-slate-900 dark:text-slate-100 text-sm">
                                    {{ $ts->format('M j, Y') }}
                                </span>
                                <span class="text-slate-400 dark:text-slate-500 text-xs font-medium">
                                    {{ $ts->format('g:i A') }} SGT
                                </span>
                                @if($isCurrent)
                                    <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full text-[10px] font-bold uppercase tracking-wide border border-blue-200 dark:border-blue-800">Live</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-400 mt-0.5">Record #{{ $cardNumber }}</div>
                        </div>
                    </div>

                    <!-- Status badges -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $statusLabel['class'] }}">
                            {{ $statusLabel['text'] }}
                        </span>
                        @if($totalOffline > 0)
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800">
                                {{ $totalOffline }} items off
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Platform Rows -->
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                        @php
                            $data     = $card['platform_data'][$platform] ?? [];
                            $hasItems = ($data['offline_count'] ?? 0) > 0;
                            $isOnline = ($data['status'] ?? '') === 'Online';
                            $dropId   = 'drop-' . $index . '-' . $platform;
                            $cfg      = $platformConfig[$platform];
                        @endphp

                        <div>
                            <button
                                onclick="{{ $hasItems ? "toggleDrop('" . $dropId . "')" : '' }}"
                                class="platform-row w-full flex items-center gap-4 px-5 py-4 text-left {{ $hasItems ? 'hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer' : 'cursor-default' }} transition">

                                <!-- Platform badge -->
                                <div class="w-10 h-10 flex-shrink-0 {{ $cfg['color'] }} rounded-xl flex flex-col items-center justify-center shadow-sm">
                                    <span class="text-white font-black text-sm leading-none">{{ strtoupper(substr($cfg['name'], 0, 1)) }}</span>
                                </div>

                                <!-- Platform name + time -->
                                <div class="flex-1 min-w-0 text-left">
                                    <div class="font-bold text-slate-900 dark:text-slate-100 text-sm">{{ $cfg['name'] }}</div>
                                    @if(isset($data['last_checked']) && $data['last_checked'])
                                        <div class="text-xs text-slate-400 mt-0.5">
                                            {{ \Carbon\Carbon::parse($data['last_checked'])->setTimezone('Asia/Singapore')->format('M d, g:i A') }} SGT
                                        </div>
                                    @endif
                                </div>

                                <!-- Status + items count + chevron -->
                                <div class="flex items-center gap-2.5 flex-shrink-0">
                                    @if($isOnline)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 rounded-full text-xs font-bold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                                            Online
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-full text-xs font-bold">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>
                                            Offline
                                        </span>
                                    @endif

                                    @if($hasItems)
                                        <span class="px-2.5 py-1.5 bg-red-500 text-white rounded-lg text-xs font-bold tabular-nums">
                                            {{ $data['offline_count'] }} OFF
                                        </span>
                                        <svg id="chev-{{ $dropId }}" class="chevron w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    @endif
                                </div>
                            </button>

                            @if($hasItems)
                                <div id="{{ $dropId }}" class="dropdown-body">
                                    <div class="px-5 pb-5 pt-3 {{ $cfg['lightBg'] }} border-t border-slate-100 dark:border-slate-800">
                                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-3">
                                            {{ $data['offline_count'] }} offline item{{ $data['offline_count'] > 1 ? 's' : '' }}
                                        </p>
                                        <div class="space-y-2">
                                            @foreach($data['offline_items'] as $item)
                                                @php $itemData = is_array($item) ? (object)$item : $item; @endphp
                                                <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-3 flex items-center gap-3">
                                                    @if(isset($itemData->image_url) && $itemData->image_url)
                                                        <img src="{{ $itemData->image_url }}"
                                                             alt="{{ $itemData->name }}"
                                                             class="w-11 h-11 rounded-lg object-cover flex-shrink-0 border border-slate-100 dark:border-slate-700"
                                                             loading="lazy" onerror="this.style.display='none'">
                                                    @else
                                                        <div class="w-11 h-11 rounded-lg bg-slate-100 dark:bg-slate-800 flex-shrink-0 flex items-center justify-center">
                                                            <svg class="w-4 h-4 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                                                            </svg>
                                                        </div>
                                                    @endif
                                                    <div class="flex-1 min-w-0">
                                                        <div class="font-semibold text-slate-900 dark:text-slate-100 text-sm truncate">{{ $itemData->name }}</div>
                                                        @if(isset($itemData->category) && $itemData->category)
                                                            <div class="text-xs text-slate-400 mt-0.5">{{ $itemData->category }}</div>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center gap-2 flex-shrink-0">
                                                        <span class="font-bold text-slate-800 dark:text-slate-200 text-sm">${{ number_format($itemData->price, 2) }}</span>
                                                        <span class="bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded-md">OFF</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
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
        <a href="/store/{{ $shopId }}/items" class="flex items-center gap-2 text-sm text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition font-medium">
            View Items
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

    function toggleDrop(id) {
        const body = document.getElementById(id);
        const chev = document.getElementById('chev-' + id);
        if (!body) return;
        body.classList.toggle('open');
        if (chev) chev.classList.toggle('open');
    }
</script>
</body>
</html>
