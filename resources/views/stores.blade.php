<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Stores - HawkerOps</title>
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

      <!-- Info Popup -->
      <div id="infoPopup" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900">App Guide</h3>
            <button onclick="toggleInfoPopup()" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
          </div>

          <div class="space-y-4 text-sm max-h-96 overflow-y-auto">
            <div>
              <div class="font-semibold text-slate-900 mb-1">🔄 Run Sync Button</div>
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

      <nav class="px-3 pb-6 space-y-1">
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="/dashboard">
          <span class="text-sm font-medium">Overview</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl bg-slate-900 text-white shadow-sm" href="/stores">
          <span class="text-sm font-medium">Stores</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="/items">
          <span class="text-sm font-medium">Items</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="/platforms">
          <span class="text-sm font-medium">Platforms</span>
        </a>
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
  </script>
</body>
</html>
