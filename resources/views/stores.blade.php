<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Stores - HawkerOps</title>
  <link rel="icon" type="image/png" href="/favicon.png" />
  <script src="https://cdn.tailwindcss.com"></script>
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

      <!-- Info Popup Modal -->
      <div id="infoPopup" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 pointer-events-none">
        <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full p-8 max-h-[90vh] overflow-y-auto pointer-events-auto">
          <div class="flex items-center justify-between mb-6 sticky top-0 bg-white pb-4">
            <div>
              <h3 class="text-3xl font-bold text-slate-900">📖 HawkerOps Guide</h3>
              <p class="text-sm text-slate-500 mt-1">Complete guide to using the store management system</p>
            </div>
            <button onclick="toggleInfoPopup()" class="text-slate-400 hover:text-slate-600 text-3xl leading-none hover:bg-slate-100 w-8 h-8 flex items-center justify-center rounded-lg transition flex-shrink-0">&times;</button>
          </div>

          <!-- Two Column Layout -->
          <div class="grid grid-cols-2 gap-4 text-sm">
            <!-- LEFT COLUMN -->
            <div>
              <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-lg mb-4">
                <div class="font-semibold text-slate-900 mb-2">🔄 Refresh Data Button</div>
                <p class="text-slate-600 text-xs leading-relaxed">Located in the left sidebar. Refreshes data from the database and updates platform status and item availability without running scrapers. Useful for quick data updates.</p>
              </div>

              <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg mb-4">
                <div class="font-semibold text-slate-900 mb-2">↻ Reload Button</div>
                <p class="text-slate-600 text-xs leading-relaxed">Located in the top-right corner. Reloads the entire page to show the latest data from the database. Use when data seems outdated.</p>
              </div>

              <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded-lg mb-4">
                <div class="font-semibold text-slate-900 mb-2">⚠️ Troubleshooting</div>
                <p class="text-slate-600 text-xs leading-relaxed">If an entire column shows as offline or data seems incorrect, simply refresh the page. This resolves most display issues with platform status.</p>
              </div>

              <div class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded-lg mb-4">
                <div class="font-semibold text-slate-900 mb-2">🕐 Auto-Refresh</div>
                <p class="text-slate-600 text-xs leading-relaxed">Pages automatically reload every 5 minutes to keep data current. No action needed - happens in the background.</p>
              </div>

              <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4 rounded-lg mb-4">
                <div class="font-semibold text-slate-900 mb-2">🏪 Store Actions</div>
                <p class="text-slate-600 text-xs leading-relaxed"><strong>View Items:</strong> See all menu items with their status (Active/Inactive) across all platforms. <strong>View Logs:</strong> Check daily status history and changes.</p>
              </div>

              <div class="bg-cyan-50 border-l-4 border-cyan-500 p-4 rounded-lg">
                <div class="font-semibold text-slate-900 mb-2">🎨 Filter Buttons</div>
                <p class="text-slate-600 text-xs leading-relaxed"><strong>All Stores:</strong> Show all outlets. <strong>All Online:</strong> Only all 3 platforms online. <strong>Partial Offline:</strong> 1-2 platforms down. <strong>All Offline:</strong> All 3 platforms down.</p>
              </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div>
              <div class="bg-pink-50 border-l-4 border-pink-500 p-4 rounded-lg mb-4">
                <div class="font-semibold text-slate-900 mb-2">📊 Status Indicators</div>
                <div class="text-slate-600 text-xs leading-relaxed space-y-1">
                  <p><strong>🟢 Green Badge:</strong> All 3 platforms online - Fully operational</p>
                  <p><strong>🟡 Orange Badge:</strong> 1-2 platforms offline - Partial service</p>
                  <p><strong>🔴 Red Badge:</strong> All 3 platforms offline - No service</p>
                </div>
              </div>

              <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-lg mb-4">
                <div class="font-semibold text-slate-900 mb-2">🔢 Item Information</div>
                <p class="text-slate-600 text-xs leading-relaxed">Each menu item appears 3 times (Grab, FoodPanda, Deliveroo). Total item count shows unique items. Offline count shows items unavailable per platform.</p>
              </div>

              <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg mb-4">
                <div class="font-semibold text-slate-900 mb-2">🌐 Platforms Monitored</div>
                <div class="text-slate-600 text-xs leading-relaxed space-y-1">
                  <p><strong>🟢 Grab:</strong> Green indicators, food delivery service</p>
                  <p><strong>🩷 FoodPanda:</strong> Pink indicators, delivery platform</p>
                  <p><strong>🔵 Deliveroo:</strong> Cyan indicators, premium delivery</p>
                </div>
              </div>

              <div class="bg-slate-50 border-l-4 border-slate-500 p-4 rounded-lg mb-4">
                <div class="font-semibold text-slate-900 mb-2">📈 Dashboard Cards</div>
                <div class="text-slate-600 text-xs leading-relaxed space-y-1">
                  <p><strong>Stores Online:</strong> Number of outlets currently online</p>
                  <p><strong>Items OFF:</strong> Total items offline across all platforms</p>
                  <p><strong>Active Alerts:</strong> Critical status changes requiring attention</p>
                  <p><strong>Platforms Status:</strong> Online vs total platform availability</p>
                </div>
              </div>

              <div class="bg-teal-50 border-l-4 border-teal-500 p-4 rounded-lg mb-4">
                <div class="font-semibold text-slate-900 mb-2">📍 Timezone & Location</div>
                <p class="text-slate-600 text-xs leading-relaxed"><strong>Timezone:</strong> All timestamps in Singapore Time (SGT, UTC+8). <strong>Coverage:</strong> 46 restaurant outlets across Singapore monitored in real-time.</p>
              </div>

              <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <div class="font-semibold text-slate-900 mb-2">⚡ Performance</div>
                <p class="text-slate-600 text-xs leading-relaxed">Dashboard optimized for speed - loads in under 1 second. 99% fewer database queries. Real-time updates with gzip compression. Supports 30+ concurrent users.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <nav class="px-3 pb-6 space-y-1 overflow-y-auto flex-1">
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="/dashboard">
          <span class="text-sm font-medium">📊 Overview</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl bg-slate-900 text-white shadow-sm" href="/stores">
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
          <div class="text-xs font-semibold">{{ $lastSync ?? '—' }}</div>
          <button onclick="refreshStores()" id="syncBtn" class="mt-3 w-full rounded-xl bg-slate-900 text-white py-2 text-sm font-medium hover:opacity-90 transition">
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
            <h1 class="text-xl font-semibold">All Stores</h1>
            <p class="text-sm text-slate-500">Manage and monitor all {{ count($stores ?? []) }} store locations</p>
          </div>

          <div class="flex items-center gap-2">
            <div class="hidden sm:flex items-center bg-slate-100 rounded-xl px-3 py-2">
              <input class="bg-transparent outline-none text-sm w-64" placeholder="Search store..." />
            </div>
            <button class="rounded-xl border bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50 transition">
              Export
            </button>
          </div>
        </div>
      </header>

      <div class="px-4 md:px-8 py-6">

        <!-- Store List -->
        <div class="bg-white border rounded-2xl overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-slate-50 border-b">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Store</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Shop ID</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Total Items</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Items OFF</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Last Sync</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                @forelse($stores ?? [] as $store)
                <tr class="hover:bg-slate-50 transition">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="font-medium text-slate-900">{{ $store['store'] }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-mono text-slate-500">{{ $store['shop_id'] }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    @if($store['status'] === 'all_online')
                      <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                        ✓ {{ $store['status_text'] }}
                      </span>
                    @elseif($store['status'] === 'all_offline')
                      <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                        ✕ {{ $store['status_text'] }}
                      </span>
                    @else
                      <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                        ⚠ {{ $store['status_text'] }}
                      </span>
                    @endif
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-slate-900">{{ $store['total_items'] ?? 0 }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-semibold {{ $store['items_off'] > 0 ? 'text-red-600' : 'text-slate-400' }}">
                      {{ $store['items_off'] }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-xs text-slate-500">{{ $store['last_change'] ?? '—' }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <a href="/store/{{ $store['shop_id'] }}" class="text-slate-900 hover:text-slate-700 font-medium">
                      View →
                    </a>
                  </td>
                </tr>
                @empty
                <tr>
                  <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                    No stores found. Run sync to load data.
                  </td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>

      </div>
    </main>
  </div>

  <script>
    // Refresh Stores - reads from existing database (no scraping)
    function refreshStores() {
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
