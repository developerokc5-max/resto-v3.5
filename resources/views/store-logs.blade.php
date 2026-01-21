<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $brandName }} - {{ $shopName }} - Status Log</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .platform-dropdown { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
        .platform-dropdown.active { max-height: 2000px; }
    </style>
</head>
<body class="bg-slate-50">
    <!-- Header -->
    <header class="bg-white border-b-2 border-slate-200 sticky top-0 z-50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-slate-900">{{ $brandName }}</h1>
                    <p class="text-sm text-slate-600 mt-0.5">{{ $shopName }} - Status Log Timeline</p>
                </div>
                <a href="/dashboard" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Status History Timeline -->
        <div class="space-y-6">
            @foreach($statusCards as $index => $card)
                @php
                    $isCurrent = isset($card['is_current']) && $card['is_current'];
                    $cardNumber = count($statusCards) - $index;
                @endphp

                <!-- Status Card -->
                <div class="bg-white rounded-2xl shadow-lg border-2 border-slate-200 overflow-hidden">

                    <!-- Card Header -->
                    <div class="bg-white border-b-2 border-slate-200 p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center border-2 border-slate-300">
                                        <span class="text-xl font-bold text-slate-700">#{{ $cardNumber }}</span>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-xl font-bold text-slate-900">{{ $isCurrent ? 'CURRENT STATUS' : 'Status Record' }}</h3>
                                            @if($isCurrent)
                                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs font-semibold">LIVE</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-slate-600 mt-0.5">{{ \Carbon\Carbon::parse($card['timestamp'])->format('l, F j, Y \a\t g:i A') }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Summary -->
                            <div class="flex items-center gap-4">
                                <!-- Outlet Status -->
                                @if($card['outlet_status'] === 'All Online')
                                    <div class="px-5 py-3 bg-green-50 border-2 border-green-500 rounded-xl">
                                        <div class="text-lg font-bold text-green-700">All Online</div>
                                        <div class="text-xs text-green-600">{{ $card['platforms_online'] }}/3 Platforms</div>
                                    </div>
                                @elseif($card['outlet_status'] === 'All Offline')
                                    <div class="px-5 py-3 bg-red-50 border-2 border-red-500 rounded-xl">
                                        <div class="text-lg font-bold text-red-700">All Offline</div>
                                        <div class="text-xs text-red-600">{{ $card['platforms_online'] }}/3 Platforms</div>
                                    </div>
                                @else
                                    <div class="px-5 py-3 bg-amber-50 border-2 border-amber-500 rounded-xl">
                                        <div class="text-lg font-bold text-amber-700">Mixed</div>
                                        <div class="text-xs text-amber-600">{{ $card['platforms_online'] }}/3 Online</div>
                                    </div>
                                @endif

                                <!-- Offline Items Count -->
                                @if($card['total_offline_items'] > 0)
                                    <div class="px-5 py-3 bg-slate-900 rounded-xl">
                                        <div class="text-2xl font-bold text-white">{{ $card['total_offline_items'] }}</div>
                                        <div class="text-xs text-slate-300">Items Off</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Platform Breakdown -->
                    <div class="p-6 bg-slate-50 space-y-3">
                        @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                            @php
                                $data = $card['platform_data'][$platform];
                                $dropdownId = $index . '-' . $platform;
                                $hasOfflineItems = $data['offline_count'] > 0;

                                // Platform-specific colors
                                $platformColors = [
                                    'grab' => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'icon' => 'bg-green-600'],
                                    'foodpanda' => ['bg' => 'bg-pink-50', 'border' => 'border-pink-200', 'icon' => 'bg-pink-600'],
                                    'deliveroo' => ['bg' => 'bg-cyan-50', 'border' => 'border-cyan-200', 'icon' => 'bg-cyan-600'],
                                ];
                                $colors = $platformColors[$platform];
                            @endphp

                            <div class="bg-white border-2 {{ $colors['border'] }} rounded-xl overflow-hidden hover:shadow-md transition">
                                <!-- Platform Header (Clickable) -->
                                <button onclick="toggleDropdown('{{ $dropdownId }}')" class="w-full p-4 {{ $colors['bg'] }} flex items-center justify-between hover:opacity-90 transition">
                                    <div class="flex items-center gap-4">
                                        <!-- Platform Icon -->
                                        <div class="w-12 h-12 {{ $colors['icon'] }} rounded-lg flex items-center justify-center shadow-sm">
                                            <span class="text-lg font-bold text-white">{{ strtoupper(substr($data['name'], 0, 1)) }}</span>
                                        </div>

                                        <!-- Platform Info -->
                                        <div class="text-left">
                                            <h4 class="font-bold text-slate-900 text-base">{{ $data['name'] }}</h4>
                                            @if(isset($data['last_checked']) && $data['last_checked'])
                                                <p class="text-xs text-slate-600">Checked {{ \Carbon\Carbon::parse($data['last_checked'])->diffForHumans() }}</p>
                                            @else
                                                <p class="text-xs text-slate-500">Not checked yet</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <!-- Items Count -->
                                        @if($hasOfflineItems)
                                            <div class="px-4 py-2 bg-red-600 text-white rounded-lg font-bold shadow-sm">
                                                <div class="text-sm">{{ $data['offline_count'] }} OFF</div>
                                            </div>
                                        @else
                                            <div class="px-4 py-2 bg-green-600 text-white rounded-lg font-bold shadow-sm">
                                                <div class="text-sm">0 OFF</div>
                                            </div>
                                        @endif

                                        <!-- Arrow -->
                                        @if($hasOfflineItems)
                                            <svg id="arrow-{{ $dropdownId }}" class="w-5 h-5 text-slate-600 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        @endif
                                    </div>
                                </button>

                                <!-- Dropdown Content (Only show if has offline items) -->
                                @if($hasOfflineItems)
                                    <div id="dropdown-{{ $dropdownId }}" class="platform-dropdown">
                                        <div class="p-5 bg-slate-50 border-t-2 border-slate-200">
                                            <h5 class="font-bold text-slate-900 mb-4 text-sm">Offline Items ({{ $data['offline_count'] }})</h5>
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                                @foreach($data['offline_items'] as $item)
                                                    <div class="bg-white border-2 border-slate-200 rounded-lg p-3 hover:border-slate-300 transition">
                                                        <div class="flex gap-3">
                                                            @if($item->image_url)
                                                                <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="w-16 h-16 rounded-lg object-cover border border-slate-200" onerror="this.style.display='none'">
                                                            @endif
                                                            <div class="flex-1 min-w-0">
                                                                <h6 class="font-bold text-slate-900 text-sm mb-1 line-clamp-2">{{ $item->name }}</h6>
                                                                <div class="flex items-center gap-2 mb-1">
                                                                    <span class="font-bold text-slate-900">${{ number_format($item->price, 2) }}</span>
                                                                    <span class="px-2 py-0.5 bg-red-100 text-red-700 border border-red-200 rounded text-xs font-semibold">OFF</span>
                                                                </div>
                                                                @if($item->category)
                                                                    <p class="text-xs text-slate-500">{{ $item->category }}</p>
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
        function toggleDropdown(id) {
            const dropdown = document.getElementById('dropdown-' + id);
            const arrow = document.getElementById('arrow-' + id);

            if (dropdown && arrow) {
                dropdown.classList.toggle('active');
                arrow.classList.toggle('rotate-180');
            }
        }
    </script>
</body>
</html>
