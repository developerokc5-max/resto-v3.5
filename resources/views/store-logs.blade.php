<!DOCTYPE html>
<html lang="en" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $shopName }} — Status Log</title>
    <link rel="icon" type="image/png" href="/favicon.png" />
    <script>if (localStorage.getItem('darkMode') === 'true') document.documentElement.classList.add('dark');</script>
    @vite(['resources/css/app.css'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .dropdown-body { max-height: 0; overflow: hidden; transition: max-height 0.35s ease; }
        .dropdown-body.open { max-height: 2000px; }
        .chevron { transition: transform 0.25s ease; }
        .chevron.open { transform: rotate(180deg); }
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

    <!-- Page Title -->
    <div class="mb-6">
        <h1 class="text-xl md:text-2xl font-bold text-slate-900 dark:text-slate-100">Status History</h1>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Platform availability records for this outlet</p>
    </div>

    @if(empty($statusCards))
        <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-16 text-center shadow-sm">
            <div class="w-14 h-14 bg-slate-100 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <p class="font-semibold text-slate-700 dark:text-slate-300">No status records yet</p>
            <p class="text-sm text-slate-400 mt-1">Records will appear once the scraper runs.</p>
        </div>
    @else
        <div class="space-y-5">
            @foreach($statusCards as $index => $card)
                @php
                    $isCurrent  = isset($card['is_current']) && $card['is_current'];
                    $cardNumber = $card['id'] ?? (count($statusCards) - $index);
                    $ts         = \Carbon\Carbon::parse($card['timestamp'])->setTimezone('Asia/Singapore');
                    $status     = $card['outlet_status'] ?? 'Mixed';
                    $onlineCount = $card['platforms_online'] ?? 0;
                    $totalOffline = $card['total_offline_items'] ?? 0;

                    $statusStyle = match(true) {
                        $status === 'All Online'  => ['gradient' => 'from-emerald-500 to-green-600',  'badge' => 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400', 'label' => 'All Online'],
                        $status === 'All Offline' => ['gradient' => 'from-red-500 to-rose-600',        'badge' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400',               'label' => 'All Offline'],
                        default                   => ['gradient' => 'from-amber-500 to-orange-500',   'badge' => 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400',     'label' => 'Mixed'],
                    };
                @endphp

                <!-- Status Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 overflow-hidden">

                    <!-- Card Header -->
                    <div class="bg-gradient-to-r {{ $statusStyle['gradient'] }} px-5 py-4 flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Record number -->
                            <div class="w-10 h-10 flex-shrink-0 bg-white/20 rounded-xl flex items-center justify-center">
                                <span class="text-white font-bold text-sm">#{{ $cardNumber }}</span>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-white font-bold text-base leading-tight">
                                        {{ $isCurrent ? 'Current Status' : 'Status Record' }}
                                    </span>
                                    @if($isCurrent)
                                        <span class="px-2 py-0.5 bg-white/25 text-white rounded-full text-[10px] font-bold uppercase tracking-wide">Live</span>
                                    @endif
                                </div>
                                <p class="text-white/70 text-xs mt-0.5">{{ $ts->format('M j, Y') }} · {{ $ts->format('g:i A') }} SGT</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <!-- Online count -->
                            <div class="text-center bg-white/20 rounded-xl px-3 py-1.5 min-w-[56px]">
                                <div class="text-white font-bold text-lg leading-none">{{ $onlineCount }}/3</div>
                                <div class="text-white/70 text-[10px]">online</div>
                            </div>
                            @if($totalOffline > 0)
                                <div class="text-center bg-black/20 rounded-xl px-3 py-1.5 min-w-[56px]">
                                    <div class="text-white font-bold text-lg leading-none">{{ $totalOffline }}</div>
                                    <div class="text-white/70 text-[10px]">items off</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Platform Rows -->
                    <div class="divide-y divide-slate-100 dark:divide-slate-700">
                        @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                            @php
                                $data     = $card['platform_data'][$platform];
                                $hasItems = ($data['offline_count'] ?? 0) > 0;
                                $dropId   = 'drop-' . $index . '-' . $platform;

                                $plStyle = [
                                    'grab'      => ['color' => 'bg-green-600', 'light' => 'bg-green-50 dark:bg-green-900/10',  'label' => 'Grab'],
                                    'foodpanda' => ['color' => 'bg-pink-600',  'light' => 'bg-pink-50 dark:bg-pink-900/10',    'label' => 'foodpanda'],
                                    'deliveroo' => ['color' => 'bg-cyan-600',  'light' => 'bg-cyan-50 dark:bg-cyan-900/10',    'label' => 'Deliveroo'],
                                ][$platform];
                            @endphp

                            <div>
                                <button
                                    onclick="{{ $hasItems ? "toggleDrop('" . $dropId . "')" : '' }}"
                                    class="w-full flex items-center gap-3 px-5 py-3.5 text-left {{ $hasItems ? 'hover:bg-slate-50 dark:hover:bg-slate-700/40 cursor-pointer' : 'cursor-default' }} transition">

                                    <!-- Platform icon -->
                                    <div class="w-9 h-9 flex-shrink-0 {{ $plStyle['color'] }} rounded-xl flex items-center justify-center shadow-sm">
                                        <span class="text-xs font-bold text-white">{{ strtoupper(substr($plStyle['label'], 0, 1)) }}</span>
                                    </div>

                                    <!-- Name + timestamp -->
                                    <div class="flex-1 min-w-0 text-left">
                                        <div class="font-semibold text-slate-800 dark:text-slate-200 text-sm">{{ $plStyle['label'] }}</div>
                                        @if(isset($data['last_checked']) && $data['last_checked'])
                                            <div class="text-[10px] text-slate-400 dark:text-slate-500">
                                                {{ \Carbon\Carbon::parse($data['last_checked'])->setTimezone('Asia/Singapore')->format('M d, g:i A') }} SGT
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Badge + chevron -->
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        @if($hasItems)
                                            <span class="px-2.5 py-1 bg-red-500 text-white rounded-lg text-xs font-bold">{{ $data['offline_count'] }} OFF</span>
                                            <svg id="chev-{{ $dropId }}" class="chevron w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        @else
                                            <span class="px-2.5 py-1 bg-emerald-500 text-white rounded-lg text-xs font-bold">0 OFF</span>
                                        @endif
                                    </div>
                                </button>

                                @if($hasItems)
                                    <div id="{{ $dropId }}" class="dropdown-body">
                                        <div class="px-5 pb-4 pt-1 {{ $plStyle['light'] }} border-t border-slate-100 dark:border-slate-700">
                                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-3">
                                                {{ $data['offline_count'] }} offline item{{ $data['offline_count'] > 1 ? 's' : '' }}
                                            </p>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                                                @foreach($data['offline_items'] as $item)
                                                    @php $itemData = is_array($item) ? (object)$item : $item; @endphp
                                                    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-3 flex gap-3">
                                                        @if(isset($itemData->image_url) && $itemData->image_url)
                                                            <img src="{{ $itemData->image_url }}"
                                                                 alt="{{ $itemData->name }}"
                                                                 class="w-12 h-12 rounded-lg object-cover flex-shrink-0 border border-slate-100 dark:border-slate-700"
                                                                 loading="lazy" onerror="this.style.display='none'">
                                                        @else
                                                            <div class="w-12 h-12 rounded-lg bg-slate-100 dark:bg-slate-700 flex-shrink-0 flex items-center justify-center">
                                                                <svg class="w-5 h-5 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                                                                </svg>
                                                            </div>
                                                        @endif
                                                        <div class="flex-1 min-w-0">
                                                            <h6 class="font-semibold text-slate-900 dark:text-slate-100 text-xs leading-snug line-clamp-2 mb-1">{{ $itemData->name }}</h6>
                                                            <div class="flex items-center gap-1.5">
                                                                <span class="font-bold text-slate-800 dark:text-slate-200 text-xs">${{ number_format($itemData->price, 2) }}</span>
                                                                <span class="px-1.5 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded text-[9px] font-bold">OFF</span>
                                                            </div>
                                                            @if(isset($itemData->category) && $itemData->category)
                                                                <p class="text-[10px] text-slate-400 mt-0.5">{{ $itemData->category }}</p>
                                                            @endif
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
        <a href="/store/{{ $shopId }}/items" class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 transition font-medium">
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
