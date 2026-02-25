<!DOCTYPE html>
<html lang="en" id="html-root">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brandName }} - {{ $shopName }} - Status Log</title>
    <link rel="icon" type="image/png" href="/favicon.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
        if (localStorage.getItem('darkMode') === 'true') {
            document.documentElement.classList.add('dark');
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .platform-dropdown { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
        .platform-dropdown.active { max-height: 2000px; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-200">
    <!-- Info Popup Modal -->
    <div id="infoPopup" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 pointer-events-none">
        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-5xl w-full p-8 max-h-[90vh] overflow-y-auto pointer-events-auto">
            <div class="flex items-center justify-between mb-6 sticky top-0 bg-white dark:bg-slate-900 pb-4">
                <div>
                    <h3 class="text-3xl font-bold text-slate-900 dark:text-slate-100">📖 HawkerOps Guide</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Complete guide to using the store management system</p>
                </div>
                <button onclick="toggleInfoPopup()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-3xl leading-none hover:bg-slate-100 dark:hover:bg-slate-800 w-8 h-8 flex items-center justify-center rounded-lg transition flex-shrink-0">&times;</button>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-2 gap-4 text-sm">
                <!-- LEFT COLUMN -->
                <div>
                    <div class="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 p-4 rounded-lg mb-4">
                        <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🔄 Refresh Data Button</div>
                        <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">Located in the left sidebar. Refreshes data from the database and updates platform status and item availability without running scrapers. Useful for quick data updates.</p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 p-4 rounded-lg mb-4">
                        <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">↻ Reload Button</div>
                        <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">Located in the top-right corner. Reloads the entire page to show the latest data from the database. Use when data seems outdated.</p>
                    </div>
                    <div class="bg-orange-50 dark:bg-orange-900/30 border-l-4 border-orange-500 p-4 rounded-lg mb-4">
                        <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">⚠️ Troubleshooting</div>
                        <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">If an entire column shows as offline or data seems incorrect, simply refresh the page.</p>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/30 border-l-4 border-purple-500 p-4 rounded-lg mb-4">
                        <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🕐 Auto-Refresh</div>
                        <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">Pages automatically reload every 5 minutes to keep data current.</p>
                    </div>
                    <div class="bg-indigo-50 dark:bg-indigo-900/30 border-l-4 border-indigo-500 p-4 rounded-lg mb-4">
                        <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🏪 Store Actions</div>
                        <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed"><strong>View Items:</strong> See all menu items with their status across all platforms. <strong>View Logs:</strong> Check daily status history and changes.</p>
                    </div>
                    <div class="bg-cyan-50 dark:bg-cyan-900/30 border-l-4 border-cyan-500 p-4 rounded-lg">
                        <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🎨 Filter Buttons</div>
                        <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed"><strong>All Stores:</strong> Show all outlets. <strong>All Online:</strong> Only all 3 platforms online. <strong>Partial Offline:</strong> 1-2 platforms down. <strong>All Offline:</strong> All 3 platforms down.</p>
                    </div>
                </div>
                <!-- RIGHT COLUMN -->
                <div>
                    <div class="bg-pink-50 dark:bg-pink-900/30 border-l-4 border-pink-500 p-4 rounded-lg mb-4">
                        <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">📊 Status Indicators</div>
                        <div class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed space-y-1">
                            <p><strong>🟢 Green Badge:</strong> All 3 platforms online - Fully operational</p>
                            <p><strong>🟡 Orange Badge:</strong> 1-2 platforms offline - Partial service</p>
                            <p><strong>🔴 Red Badge:</strong> All 3 platforms offline - No service</p>
                        </div>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-500 p-4 rounded-lg mb-4">
                        <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🔢 Item Information</div>
                        <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">Each menu item appears 3 times (Grab, FoodPanda, Deliveroo). Total item count shows unique items.</p>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 p-4 rounded-lg mb-4">
                        <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🌐 Platforms Monitored</div>
                        <div class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed space-y-1">
                            <p><strong>🟢 Grab:</strong> Green indicators</p>
                            <p><strong>🩷 FoodPanda:</strong> Pink indicators</p>
                            <p><strong>🔵 Deliveroo:</strong> Cyan indicators</p>
                        </div>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-800 border-l-4 border-slate-500 p-4 rounded-lg mb-4">
                        <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">📈 Dashboard Cards</div>
                        <div class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed space-y-1">
                            <p><strong>Stores Online:</strong> Number of outlets currently online</p>
                            <p><strong>Items OFF:</strong> Total items offline across all platforms</p>
                            <p><strong>Active Alerts:</strong> Critical status changes requiring attention</p>
                        </div>
                    </div>
                    <div class="bg-teal-50 dark:bg-teal-900/30 border-l-4 border-teal-500 p-4 rounded-lg mb-4">
                        <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">📍 Timezone & Location</div>
                        <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">All timestamps in Singapore Time (SGT, UTC+8). 46 restaurant outlets across Singapore monitored in real-time.</p>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 p-4 rounded-lg">
                        <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">⚡ Performance</div>
                        <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">Dashboard optimized for speed - loads in under 1 second. 99% fewer database queries.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Header -->
    <header class="bg-white dark:bg-slate-900 border-b-2 border-slate-200 dark:border-slate-800 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-0.5">
                      <a href="/stores" class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                      </a>
                      <h1 class="text-base md:text-2xl font-bold text-slate-900 dark:text-slate-100 truncate">{{ $brandName }}</h1>
                    </div>
                    <p class="text-xs md:text-sm text-slate-600 dark:text-slate-300 truncate">{{ $shopName }} — Status Log</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button onclick="toggleDarkMode()" id="darkToggle" class="h-8 w-8 rounded-full bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs flex items-center justify-center transition" title="Toggle dark mode">
                        <span id="darkIcon">🌙</span>
                    </button>
                    <button onclick="toggleInfoPopup()" class="h-8 w-8 rounded-full bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs font-bold flex items-center justify-center transition">
                        i
                    </button>
                    <button onclick="window.location.reload()" class="px-3 py-2 bg-slate-900 dark:bg-slate-700 text-white rounded-xl text-sm font-medium hover:opacity-90 transition">
                        Reload
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Status History Timeline -->
        <div class="space-y-6">
            @foreach($statusCards as $index => $card)
                @php
                    $isCurrent = isset($card['is_current']) && $card['is_current'];
                    $cardNumber = $card['id'] ?? (count($statusCards) - $index);
                @endphp

                <!-- Status Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-lg border-2 border-slate-200 dark:border-slate-700 overflow-hidden">

                    <!-- Card Header -->
                    <div class="bg-white dark:bg-slate-800 border-b-2 border-slate-200 dark:border-slate-700 p-4 md:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 md:w-12 md:h-12 flex-shrink-0 bg-slate-100 dark:bg-slate-700 rounded-xl flex items-center justify-center border-2 border-slate-300 dark:border-slate-600">
                                    <span class="text-sm md:text-xl font-bold text-slate-700 dark:text-slate-300">#{{ $cardNumber }}</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-sm md:text-xl font-bold text-slate-900 dark:text-slate-100">{{ $isCurrent ? 'CURRENT STATUS' : 'Status Record' }}</h3>
                                        @if($isCurrent)
                                            <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded text-xs font-semibold">LIVE</span>
                                        @endif
                                    </div>
                                    <p class="text-[10px] md:text-sm text-slate-600 dark:text-slate-300 mt-0.5">{{ \Carbon\Carbon::parse($card['timestamp'])->setTimezone('Asia/Singapore')->format('M j, Y g:i A') }} SGT</p>
                                </div>
                            </div>

                            <!-- Status Summary -->
                            <div class="flex items-center gap-2 flex-shrink-0">
                                @if($card['outlet_status'] === 'All Online')
                                    <div class="px-3 md:px-5 py-2 md:py-3 bg-green-50 dark:bg-green-900/30 border-2 border-green-500 dark:border-green-700 rounded-xl text-center">
                                        <div class="text-sm md:text-lg font-bold text-green-700 dark:text-green-400">All On</div>
                                        <div class="text-[10px] md:text-xs text-green-600 dark:text-green-500">{{ $card['platforms_online'] }}/3</div>
                                    </div>
                                @elseif($card['outlet_status'] === 'All Offline')
                                    <div class="px-3 md:px-5 py-2 md:py-3 bg-red-50 dark:bg-red-900/30 border-2 border-red-500 dark:border-red-700 rounded-xl text-center">
                                        <div class="text-sm md:text-lg font-bold text-red-700 dark:text-red-400">All Off</div>
                                        <div class="text-[10px] md:text-xs text-red-600 dark:text-red-500">{{ $card['platforms_online'] }}/3</div>
                                    </div>
                                @else
                                    <div class="px-3 md:px-5 py-2 md:py-3 bg-amber-50 dark:bg-amber-900/30 border-2 border-amber-500 dark:border-amber-700 rounded-xl text-center">
                                        <div class="text-sm md:text-lg font-bold text-amber-700 dark:text-amber-400">Mixed</div>
                                        <div class="text-[10px] md:text-xs text-amber-600 dark:text-amber-500">{{ $card['platforms_online'] }}/3</div>
                                    </div>
                                @endif

                                @if($card['total_offline_items'] > 0)
                                    <div class="px-3 md:px-5 py-2 md:py-3 bg-slate-900 rounded-xl text-center">
                                        <div class="text-lg md:text-2xl font-bold text-white">{{ $card['total_offline_items'] }}</div>
                                        <div class="text-[10px] md:text-xs text-slate-300">Items Off</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Platform Breakdown -->
                    <div class="p-3 md:p-6 bg-slate-50 dark:bg-slate-900/30 space-y-3">
                        @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                            @php
                                $data = $card['platform_data'][$platform];
                                $dropdownId = $index . '-' . $platform;
                                $hasOfflineItems = $data['offline_count'] > 0;

                                $platformColors = [
                                    'grab' => ['bg' => 'bg-green-50 dark:bg-green-900/20', 'border' => 'border-green-200 dark:border-green-800', 'icon' => 'bg-green-600'],
                                    'foodpanda' => ['bg' => 'bg-pink-50 dark:bg-pink-900/20', 'border' => 'border-pink-200 dark:border-pink-800', 'icon' => 'bg-pink-600'],
                                    'deliveroo' => ['bg' => 'bg-cyan-50 dark:bg-cyan-900/20', 'border' => 'border-cyan-200 dark:border-cyan-800', 'icon' => 'bg-cyan-600'],
                                ];
                                $colors = $platformColors[$platform];
                            @endphp

                            <div class="bg-white dark:bg-slate-800 border-2 {{ $colors['border'] }} rounded-xl overflow-hidden hover:shadow-md transition">
                                <!-- Platform Header (Clickable) -->
                                <button onclick="toggleDropdown('{{ $dropdownId }}')" class="w-full p-3 md:p-4 {{ $colors['bg'] }} flex items-center justify-between hover:opacity-90 transition">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 md:w-12 md:h-12 flex-shrink-0 {{ $colors['icon'] }} rounded-lg flex items-center justify-center shadow-sm">
                                            <span class="text-sm md:text-lg font-bold text-white">{{ strtoupper(substr($data['name'], 0, 1)) }}</span>
                                        </div>
                                        <div class="text-left min-w-0">
                                            <h4 class="font-bold text-slate-900 dark:text-slate-100 text-sm md:text-base">{{ $data['name'] }}</h4>
                                            @if(isset($data['last_checked']) && $data['last_checked'])
                                                <p class="text-[10px] md:text-xs text-slate-600 dark:text-slate-300 truncate">{{ \Carbon\Carbon::parse($data['last_checked'])->setTimezone('Asia/Singapore')->format('M d, g:i A') }} SGT</p>
                                            @else
                                                <p class="text-[10px] md:text-xs text-slate-500 dark:text-slate-400">Not checked yet</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        @if($hasOfflineItems)
                                            <div class="px-3 py-1.5 bg-red-600 text-white rounded-lg font-bold shadow-sm">
                                                <div class="text-xs md:text-sm">{{ $data['offline_count'] }} OFF</div>
                                            </div>
                                        @else
                                            <div class="px-3 py-1.5 bg-green-600 text-white rounded-lg font-bold shadow-sm">
                                                <div class="text-xs md:text-sm">0 OFF</div>
                                            </div>
                                        @endif

                                        @if($hasOfflineItems)
                                            <svg id="arrow-{{ $dropdownId }}" class="w-4 h-4 md:w-5 md:h-5 text-slate-600 dark:text-slate-300 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        @endif
                                    </div>
                                </button>

                                @if($hasOfflineItems)
                                    <div id="dropdown-{{ $dropdownId }}" class="platform-dropdown">
                                        <div class="p-3 md:p-5 bg-slate-50 dark:bg-slate-700/50 border-t-2 border-slate-200 dark:border-slate-600">
                                            <h5 class="font-bold text-slate-900 dark:text-slate-100 mb-3 text-sm">Offline Items ({{ $data['offline_count'] }})</h5>
                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                                @foreach($data['offline_items'] as $item)
                                                    @php
                                                        $itemData = is_array($item) ? (object)$item : $item;
                                                    @endphp
                                                    <div class="bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-600 rounded-lg p-3 hover:border-slate-300 dark:hover:border-slate-500 transition">
                                                        <div class="flex gap-3">
                                                            @if(isset($itemData->image_url) && $itemData->image_url)
                                                                <img src="{{ $itemData->image_url }}" alt="{{ $itemData->name }}" class="w-16 h-16 rounded-lg object-cover border border-slate-200 dark:border-slate-600" loading="lazy" onerror="this.style.display='none'">
                                                            @endif
                                                            <div class="flex-1 min-w-0">
                                                                <h6 class="font-bold text-slate-900 dark:text-slate-100 text-sm mb-1 line-clamp-2">{{ $itemData->name }}</h6>
                                                                <div class="flex items-center gap-2 mb-1">
                                                                    <span class="font-bold text-slate-900 dark:text-slate-100">${{ number_format($itemData->price, 2) }}</span>
                                                                    <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-700 rounded text-xs font-semibold">OFF</span>
                                                                </div>
                                                                @if(isset($itemData->category) && $itemData->category)
                                                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $itemData->category }}</p>
                                                                @endif
                                                            </div>
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

    </main>

    <script>
        // Dark mode toggle
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

        function toggleDropdown(id) {
            const dropdown = document.getElementById('dropdown-' + id);
            const arrow = document.getElementById('arrow-' + id);
            if (dropdown && arrow) {
                dropdown.classList.toggle('active');
                arrow.classList.toggle('rotate-180');
            }
        }

        function toggleInfoPopup() {
            const popup = document.getElementById('infoPopup');
            popup.classList.toggle('hidden');
        }

        document.getElementById('infoPopup')?.addEventListener('click', function(e) {
            if (e.target === this) toggleInfoPopup();
        });
    </script>
</body>
</html>
