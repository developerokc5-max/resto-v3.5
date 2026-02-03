<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>HawkerOps Dashboard</title>

  {{-- If you already use Vite + Tailwind --}}
  {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}

  {{-- If you DON'T use Vite yet, comment the line above and use this CDN temporarily --}}
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .filter-btn.active {
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
      transform: translateY(-1px);
    }
    .filter-btn:hover {
      transform: translateY(-1px);
    }
  </style>
</head>

<body class="bg-slate-50 text-slate-900">
  <div class="min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-72 hidden md:flex flex-col border-r bg-white relative z-20">
      <div class="px-6 py-5 flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl bg-slate-900 text-white grid place-items-center font-bold">HO</div>
        <div class="flex-1">
          <div class="font-semibold leading-tight">HawkerOps</div>
          <div class="text-xs text-slate-500">Store Management</div>
        </div>
        <button onclick="toggleInfoPopup()" class="h-6 w-6 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-600 text-xs font-bold flex items-center justify-center transition">
          i
        </button>
      </div>

      <!-- Info Popup -->
      <div id="infoPopup" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900">App Guide</h3>
            <button onclick="toggleInfoPopup()" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
          </div>

          <div class="space-y-4 text-sm max-h-96 overflow-y-auto">
            <div>
              <div class="font-semibold text-slate-900 mb-1">🔄 Refresh Data Button</div>
              <p class="text-slate-600">Refreshes data from the database. Updates platform status and item availability without running scrapers.</p>
            </div>

            <div>
              <div class="font-semibold text-slate-900 mb-1">↻ Refresh Button</div>
              <p class="text-slate-600">Reloads the current page to show the latest data from the database.</p>
            </div>

            <div>
              <div class="font-semibold text-slate-900 mb-1">⚠️ Platforms Page Issue</div>
              <p class="text-slate-600">Sometimes an entire column may show as offline. Simply refresh the page or press the button again to reload the correct status.</p>
            </div>

            <div>
              <div class="font-semibold text-slate-900 mb-1">🕐 Auto-Refresh</div>
              <p class="text-slate-600">Pages automatically reload every 5 minutes to keep data up-to-date.</p>
            </div>

            <div>
              <div class="font-semibold text-slate-900 mb-1">🏪 Store Details "View" Button</div>
              <p class="text-slate-600">Click "View" on any store to see all menu items with their ACTIVE/INACTIVE status across all platforms.</p>
            </div>

            <div>
              <div class="font-semibold text-slate-900 mb-1">📊 Status Indicators</div>
              <p class="text-slate-600">Green = Online/Active, Red = Offline/Inactive, Yellow/Orange = Mixed status (some platforms down).</p>
            </div>

            <div>
              <div class="font-semibold text-slate-900 mb-1">📝 Store Logs</div>
              <p class="text-slate-600">Click on any store's "Logs" to see daily status history. New entry created each day with real-time updates throughout the day.</p>
            </div>

            <div>
              <div class="font-semibold text-slate-900 mb-1">🔢 Items Count</div>
              <p class="text-slate-600">Each menu item appears 3 times in the database (once per platform: Grab, FoodPanda, Deliveroo). Counts show unique items.</p>
            </div>

            <div>
              <div class="font-semibold text-slate-900 mb-1">🌐 Platforms Coverage</div>
              <p class="text-slate-600">System tracks 3 delivery platforms: Grab (Green), FoodPanda (Pink), Deliveroo (Blue). Total of 46 restaurant outlets monitored.</p>
            </div>

            <div>
              <div class="font-semibold text-slate-900 mb-1">⏰ Timezone</div>
              <p class="text-slate-600">All timestamps are displayed in Singapore Time (SGT, UTC+8).</p>
            </div>
          </div>
        </div>
      </div>

      <nav class="px-3 pb-6 space-y-1 overflow-y-auto flex-1">
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl bg-slate-900 text-white shadow-sm" href="/dashboard">
          <span class="text-sm font-medium">📊 Overview</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="/stores">
          <span class="text-sm font-medium">🏪 Stores</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="/items">
          <span class="text-sm font-medium">📦 Items</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="/platforms">
          <span class="text-sm font-medium">🌐 Platforms</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="/alerts">
          <span class="text-sm font-medium">🔔 Alerts</span>
        </a>

        <!-- Divider -->
        <div class="border-t border-slate-200 my-2"></div>

        <!-- Reports Section -->
        <div class="space-y-1">
          <button onclick="toggleSection('reports')" class="flex items-center justify-between w-full px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition">
            <span class="text-sm font-medium">📈 Reports</span>
            <svg id="reports-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>
          <div id="reports-section" class="hidden pl-4 space-y-1">
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 transition text-sm" href="/reports/daily-trends">
              Daily Trends
            </a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 transition text-sm" href="/reports/platform-reliability">
              Platform Reliability
            </a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 transition text-sm" href="/reports/item-performance">
              Item Performance
            </a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 transition text-sm" href="/reports/store-comparison">
              Store Comparison
            </a>
          </div>
        </div>

        <!-- Settings Section -->
        <div class="space-y-1">
          <button onclick="toggleSection('settings')" class="flex items-center justify-between w-full px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition">
            <span class="text-sm font-medium">⚙️ Settings</span>
            <svg id="settings-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>
          <div id="settings-section" class="hidden pl-4 space-y-1">
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 transition text-sm" href="/settings/scraper-status">
              Scraper Status
            </a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 transition text-sm" href="/settings/configuration">
              Configuration
            </a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-600 hover:bg-slate-50 transition text-sm" href="/settings/export">
              Export Data
            </a>
          </div>
        </div>
      </nav>

      <div class="mt-auto p-4">
        <div class="rounded-2xl bg-slate-50 border p-4">
          <div class="text-xs text-slate-500">Last Updated (SGT)</div>
          <div class="text-xs font-semibold" id="lastSyncTime">{{ $lastSync ?? '—' }}</div>
          <button onclick="triggerSync()" id="syncBtn" class="mt-3 w-full rounded-xl bg-slate-900 text-white py-2 text-sm font-medium hover:opacity-90 transition">
            Refresh Data
          </button>
        </div>
      </div>
    </aside>

    <!-- Main -->
    <main class="flex-1">
      <!-- Topbar -->
      <header class="sticky top-0 z-10 bg-white/80 backdrop-blur border-b">
        <div class="px-4 md:px-8 py-4 flex items-center justify-between gap-3">
          <div>
            <h1 class="text-xl font-semibold">Overview</h1>
            <p class="text-sm text-slate-500">Hybrid monitoring: RestoSuite API + Platform scraping</p>
          </div>

          <div class="flex items-center gap-2">
            <div class="hidden sm:flex items-center bg-slate-100 rounded-xl px-3 py-2">
              <input id="searchInput" class="bg-transparent outline-none text-sm w-64" placeholder="Search store / item…" onkeyup="searchStores()" />
            </div>
            <a href="/dashboard/export" class="rounded-xl bg-green-600 hover:bg-green-700 text-white px-4 py-2 text-sm font-semibold transition shadow-sm flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              Export CSV
            </a>
            <button onclick="window.location.reload()" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:opacity-90 transition">
              Reload
            </button>
          </div>
        </div>
      </header>

      <div class="px-4 md:px-8 py-6 space-y-6">

        <!-- KPI cards -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-white border rounded-2xl p-5 shadow-sm hover:shadow-md transition">
            <div class="text-sm text-slate-500">Stores Online</div>
            <div class="mt-2 text-3xl font-semibold">{{ $kpis['stores_online'] ?? 0 }}</div>
          </div>

          <div class="bg-white border rounded-2xl p-5 shadow-sm hover:shadow-md transition">
            <div class="text-sm text-slate-500">Items OFF</div>
            <div class="mt-2 text-3xl font-semibold">{{ $kpis['items_off'] ?? 0 }}</div>
          </div>

          <div class="bg-white border rounded-2xl p-5 shadow-sm hover:shadow-md transition">
            <div class="text-sm text-slate-500">Active Alerts</div>
            <div class="mt-2 text-3xl font-semibold">{{ $kpis['alerts'] ?? 0 }}</div>
          </div>

          {{-- HYBRID: Platform Status KPI --}}
          <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition">
            <div class="text-sm text-blue-700 font-medium">Platforms Status</div>
            <div class="mt-2 flex items-baseline gap-2">
              <span class="text-3xl font-semibold text-blue-900">{{ $kpis['platforms_online'] ?? 0 }}</span>
              <span class="text-sm text-blue-600">/ {{ $kpis['platforms_total'] ?? 0 }}</span>
            </div>
            <div class="mt-1 text-xs text-blue-600">
              {{ $kpis['platforms_offline'] ?? 0 }} offline
            </div>
          </div>
        </section>

        <!-- Filter Buttons -->
        <section class="flex flex-wrap gap-3 mb-6">
          <button onclick="filterStores('all')" class="filter-btn active px-4 py-2 bg-white border-2 border-slate-300 rounded-lg text-sm font-semibold text-slate-700 shadow-sm hover:shadow-md transition" id="filter-all">
            All Stores
          </button>
          <button onclick="filterStores('all_online')" class="filter-btn px-4 py-2 bg-green-50 border-2 border-green-200 rounded-lg text-sm font-semibold text-green-700 shadow-sm hover:shadow-md transition" id="filter-all_online">
            ✓ All Platforms Online
          </button>

          <!-- Partial Offline Dropdown -->
          <div class="relative inline-block" id="partial-dropdown">
            <button onclick="togglePartialDropdown()" class="filter-btn px-4 py-2 bg-amber-50 border-2 border-amber-300 rounded-lg text-sm font-semibold text-amber-700 shadow-sm hover:shadow-md transition flex items-center gap-2" id="filter-partial">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>
              <span id="partial-label">Partial Offline</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            <div id="partial-menu" class="hidden absolute top-full mt-2 w-56 bg-white border-2 border-amber-200 rounded-lg shadow-xl z-50">
              <button onclick="filterStores('1_offline'); togglePartialDropdown();" class="w-full text-left px-4 py-3 hover:bg-amber-50 transition text-sm font-medium text-slate-700 border-b border-slate-100">
                <span class="text-amber-600 font-semibold">1/3 Offline</span> - 2 Platforms Online
              </button>
              <button onclick="filterStores('2_offline'); togglePartialDropdown();" class="w-full text-left px-4 py-3 hover:bg-amber-50 transition text-sm font-medium text-slate-700">
                <span class="text-amber-700 font-semibold">2/3 Offline</span> - 1 Platform Online
              </button>
            </div>
          </div>

          <button onclick="filterStores('all_offline')" class="filter-btn px-4 py-2 bg-red-50 border-2 border-red-200 rounded-lg text-sm font-semibold text-red-700 shadow-sm hover:shadow-md transition" id="filter-all_offline">
            ✕ All Platforms Offline
          </button>
        </section>

        <!-- Store cards -->
        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3 gap-6">
          @foreach(($stores ?? []) as $s)
            @php
              // Count offline platforms
              $offlineCount = 0;
              if (isset($s['platforms'])) {
                foreach($s['platforms'] as $platform => $status) {
                  if (!$status['online'] || $status['online'] === false || $status['online'] === 0) {
                    $offlineCount++;
                  }
                }
              }

              // Platform display config
              $platformNames = ['grab' => 'Grab', 'foodpanda' => 'FoodPanda', 'deliveroo' => 'Deliveroo'];
              $platformColors = ['grab' => 'text-green-600', 'foodpanda' => 'text-pink-600', 'deliveroo' => 'text-cyan-600'];
              $dotColors = ['grab' => 'bg-green-500', 'foodpanda' => 'bg-pink-500', 'deliveroo' => 'bg-cyan-500'];
            @endphp

            <div class="store-card bg-white border-2 border-slate-200 rounded-2xl shadow-sm hover:shadow-xl hover:border-slate-300 transition-all duration-300 flex flex-col h-full" data-status="{{ $s['overall_status'] ?? 'mixed' }}" data-offline-count="{{ $offlineCount }}">
              <!-- Card Header with Store Name and Overall Badge -->
              <div class="bg-gradient-to-r from-slate-50 to-slate-100 border-b-2 border-slate-200 px-5 py-4 rounded-t-2xl">
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <h3 class="text-xl font-bold text-slate-900">{{ $s['store'] ?? 'Store' }}</h3>
                    @if(isset($s['brand']) && $s['brand'])
                      <p class="text-sm text-slate-600 mt-0.5">{{ $s['brand'] }}</p>
                    @endif
                  </div>

                  <!-- Overall Status Badge -->
                  @if($offlineCount == 0)
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-500 rounded-lg shadow-md">
                      <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                      </svg>
                      <span class="text-xs font-bold text-white whitespace-nowrap">All Platforms Online</span>
                    </div>
                  @elseif($offlineCount == 3)
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-red-500 rounded-lg shadow-md">
                      <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                      </svg>
                      <span class="text-xs font-bold text-white whitespace-nowrap">All Platforms Offline</span>
                    </div>
                  @else
                    <div class="flex items-center gap-2 px-3 py-1.5 bg-amber-500 rounded-lg shadow-md">
                      <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                      </svg>
                      <span class="text-xs font-bold text-white whitespace-nowrap">{{ $offlineCount }}/3 Offline</span>
                    </div>
                  @endif
                </div>
              </div>

              <!-- Card Body with Platforms -->
              <div class="p-5 flex-grow flex flex-col justify-between">
                <!-- Platform Status Cards -->
                @if(isset($s['platforms']))
                <div class="space-y-3 mb-4 min-h-[240px]">
                  @foreach($s['platforms'] as $platform => $status)
                    @if($status['online'] !== null)
                      @php
                        $platformConfig = [
                          'grab' => [
                            'name' => 'Grab',
                            'gradient' => 'from-green-500 to-emerald-600',
                            'bgLight' => 'bg-green-50',
                            'borderColor' => 'border-green-300',
                            'textColor' => 'text-green-700',
                            'icon' => '🟢'
                          ],
                          'foodpanda' => [
                            'name' => 'foodpanda',
                            'gradient' => 'from-pink-500 to-rose-600',
                            'bgLight' => 'bg-pink-50',
                            'borderColor' => 'border-pink-300',
                            'textColor' => 'text-pink-700',
                            'icon' => '🩷'
                          ],
                          'deliveroo' => [
                            'name' => 'Deliveroo',
                            'gradient' => 'from-cyan-500 to-blue-600',
                            'bgLight' => 'bg-cyan-50',
                            'borderColor' => 'border-cyan-300',
                            'textColor' => 'text-cyan-700',
                            'icon' => '🔵'
                          ]
                        ];
                        $config = $platformConfig[$platform] ?? $platformConfig['grab'];
                        $isOnline = $status['online'] ?? false;
                        $lastChecked = $status['last_checked'] ?? null;
                        $lastCheckedText = $lastChecked ? \Carbon\Carbon::parse($lastChecked)->diffForHumans() : 'Never';
                        $offlineItems = $status['offline_items'] ?? 0;
                      @endphp

                      <div class="group relative {{ $isOnline ? 'bg-white' : 'bg-slate-50' }} border-2 {{ $isOnline ? 'border-slate-200' : 'border-slate-300' }} rounded-xl p-3 hover:shadow-md hover:border-slate-400 transition-all">
                        <div class="flex items-center justify-between">
                          <!-- Left: Platform Info -->
                          <div class="flex items-center gap-3 flex-1">
                            <div class="w-10 h-10 rounded-lg {{ $isOnline ? 'bg-slate-800' : 'bg-slate-600' }} flex items-center justify-center text-white font-bold text-sm shadow-sm">
                              {{ strtoupper(substr($config['name'], 0, 1)) }}
                            </div>
                            <div class="flex-1">
                              <div class="font-bold text-sm text-slate-900">{{ $config['name'] }}</div>
                              <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-[10px] {{ $isOnline ? 'text-green-600' : 'text-red-600' }} font-semibold uppercase">
                                  {{ $isOnline ? 'Online' : 'OFFLINE' }}
                                </span>
                                <span class="text-[10px] text-slate-400">• {{ $lastCheckedText }}</span>
                              </div>
                            </div>
                          </div>

                          <!-- Right: Items Status -->
                          <div class="flex flex-col items-end gap-1">
                            @if($offlineItems > 0)
                              <!-- Show offline item count -->
                              <div class="px-3 py-1.5 bg-slate-800 rounded-lg shadow-sm">
                                <div class="text-xs font-bold text-white">{{ $offlineItems }}</div>
                              </div>
                            @else
                              <!-- Show 0 when all items online -->
                              <div class="px-3 py-1.5 bg-slate-100 rounded-lg border border-slate-200">
                                <div class="text-xs font-bold text-slate-400">0</div>
                              </div>
                            @endif
                          </div>
                        </div>
                      </div>
                    @endif
                  @endforeach
                </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex gap-3">
                  <a href="/store/{{ $s['shop_id'] }}/items" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-slate-800 to-slate-900 hover:from-slate-700 hover:to-slate-800 text-white rounded-xl font-semibold text-sm shadow-md hover:shadow-lg transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span>View Items</span>
                  </a>
                  <a href="/store/{{ $s['shop_id'] }}/logs" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-white hover:bg-slate-50 border-2 border-slate-200 hover:border-slate-300 text-slate-700 rounded-xl font-semibold text-sm shadow-sm hover:shadow-md transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>View Logs</span>
                  </a>
                </div>
              </div>
            </div>
          @endforeach

          @if(empty($stores))
            <div class="col-span-full bg-white border-2 border-dashed border-slate-200 rounded-2xl p-12 text-center">
              <div class="text-slate-400 mb-3">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
              </div>
              <div class="text-lg font-semibold text-slate-700 mb-2">No Stores Data Yet</div>
              <div class="text-sm text-slate-500">
                Run sync to load store data from RestoSuite API and platform scraping
              </div>
            </div>
          @endif
        </section>

      </div>
    </main>
  </div>

  <script>
    // Toggle partial offline dropdown
    function togglePartialDropdown() {
      const menu = document.getElementById('partial-menu');
      menu.classList.toggle('hidden');
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      const dropdown = document.getElementById('partial-dropdown');
      const menu = document.getElementById('partial-menu');
      if (dropdown && !dropdown.contains(event.target)) {
        menu.classList.add('hidden');
      }
    });

    // Filter stores by status
    function filterStores(status) {
      const allCards = document.querySelectorAll('.store-card');
      const allButtons = document.querySelectorAll('.filter-btn');
      const partialLabel = document.getElementById('partial-label');

      // Update button active states
      allButtons.forEach(btn => btn.classList.remove('active'));

      // Handle partial dropdown active state and label
      if (status === '1_offline') {
        document.getElementById('filter-partial').classList.add('active');
        partialLabel.textContent = '1/3 Offline';
      } else if (status === '2_offline') {
        document.getElementById('filter-partial').classList.add('active');
        partialLabel.textContent = '2/3 Offline';
      } else if (status !== 'all' && status !== 'all_online' && status !== 'all_offline') {
        // For mixed status, activate partial dropdown
        document.getElementById('filter-partial').classList.add('active');
        partialLabel.textContent = 'Partial Offline';
      } else {
        document.getElementById('filter-' + status)?.classList.add('active');
        partialLabel.textContent = 'Partial Offline';
      }

      // Show/hide cards based on filter
      allCards.forEach(card => {
        const cardStatus = card.getAttribute('data-status');
        const offlineCountAttr = card.getAttribute('data-offline-count');
        const offlineCount = offlineCountAttr ? parseInt(offlineCountAttr) : 0;

        let shouldShow = false;

        if (status === 'all') {
          shouldShow = true;
        } else if (status === 'all_online') {
          shouldShow = cardStatus === 'all_online';
        } else if (status === 'all_offline') {
          shouldShow = cardStatus === 'all_offline';
        } else if (status === '1_offline') {
          shouldShow = offlineCount === 1;
        } else if (status === '2_offline') {
          shouldShow = offlineCount === 2;
        } else {
          shouldShow = cardStatus === status;
        }

        card.style.display = shouldShow ? 'block' : 'none';
      });
    }

    // View store details modal
    function viewStoreDetails(shopId, storeName) {
      // Redirect to store detail page
      window.location.href = `/store/${shopId}`;
    }

    // Refresh Overview - reads from existing database (no scraping)
    function triggerSync() {
      const btn = document.getElementById('syncBtn');
      const originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = 'Refreshing...';

      // Simply reload the page to show latest database data
      // Data is already updated by Platform and Items page scrapers
      setTimeout(() => {
        window.location.reload();
      }, 500);
    }

    // Filter stores by search (for table view)
    function searchStores() {
      const input = document.getElementById('searchInput');
      const filter = input.value.toUpperCase();
      const table = document.querySelector('table tbody');
      const rows = table.getElementsByTagName('tr');

      for (let i = 0; i < rows.length; i++) {
        const brandCell = rows[i].getElementsByTagName('td')[0];
        const storeCell = rows[i].getElementsByTagName('td')[1];

        if (brandCell && storeCell) {
          const brandText = brandCell.textContent || brandCell.innerText;
          const storeText = storeCell.textContent || storeCell.innerText;

          if (brandText.toUpperCase().indexOf(filter) > -1 || storeText.toUpperCase().indexOf(filter) > -1) {
            rows[i].style.display = '';
          } else {
            rows[i].style.display = 'none';
          }
        }
      }
    }

    // Export data to CSV
    function exportData() {
      const table = document.querySelector('table');
      let csv = [];
      const rows = table.querySelectorAll('tr');

      for (let row of rows) {
        const cells = row.querySelectorAll('td, th');
        const rowData = Array.from(cells).map(cell => {
          let text = cell.innerText.replace(/"/g, '""');
          return `"${text}"`;
        });
        csv.push(rowData.join(','));
      }

      const csvContent = csv.join('\n');
      const blob = new Blob([csvContent], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `hawkerops_dashboard_${new Date().toISOString().split('T')[0]}.csv`;
      a.click();
      window.URL.revokeObjectURL(url);
    }

    // Auto-refresh every 5 minutes
    setTimeout(() => {
      window.location.reload();
    }, 300000);

    // Toggle info popup
    function toggleInfoPopup() {
      const popup = document.getElementById('infoPopup');
      popup.classList.toggle('hidden');
    }

    // Close popup when clicking outside
    document.getElementById('infoPopup')?.addEventListener('click', function(e) {
      if (e.target === this) {
        toggleInfoPopup();
      }
    });

    // Toggle sidebar sections
    function toggleSection(sectionName) {
      const section = document.getElementById(sectionName + '-section');
      const arrow = document.getElementById(sectionName + '-arrow');

      section.classList.toggle('hidden');
      arrow.classList.toggle('rotate-180');
    }
  </script>
</body>
</html>
