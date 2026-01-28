<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', 'HawkerOps Dashboard')</title>
  <script src="https://cdn.tailwindcss.com"></script>
  @yield('extra-head')
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

      <nav class="px-3 pb-6 space-y-1 overflow-y-auto flex-1">
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('/') || Request::is('dashboard')) bg-slate-900 text-white shadow-sm @else text-slate-700 hover:bg-slate-100 @endif transition" href="/">
          <span class="text-sm font-medium">📊 Overview</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('stores')) bg-slate-900 text-white shadow-sm @else text-slate-700 hover:bg-slate-100 @endif transition" href="/stores">
          <span class="text-sm font-medium">🏪 Stores</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('items')) bg-slate-900 text-white shadow-sm @else text-slate-700 hover:bg-slate-100 @endif transition" href="/items">
          <span class="text-sm font-medium">📦 Items</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('platforms')) bg-slate-900 text-white shadow-sm @else text-slate-700 hover:bg-slate-100 @endif transition" href="/platforms">
          <span class="text-sm font-medium">🌐 Platforms</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('alerts')) bg-slate-900 text-white shadow-sm @else text-slate-700 hover:bg-slate-100 @endif transition" href="/alerts">
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
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg @if(Request::is('reports/daily-trends')) bg-slate-100 text-slate-900 @else text-slate-600 hover:bg-slate-50 @endif transition text-sm" href="/reports/daily-trends">
              Daily Trends
            </a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg @if(Request::is('reports/platform-reliability')) bg-slate-100 text-slate-900 @else text-slate-600 hover:bg-slate-50 @endif transition text-sm" href="/reports/platform-reliability">
              Platform Reliability
            </a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg @if(Request::is('reports/item-performance')) bg-slate-100 text-slate-900 @else text-slate-600 hover:bg-slate-50 @endif transition text-sm" href="/reports/item-performance">
              Item Performance
            </a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg @if(Request::is('reports/store-comparison')) bg-slate-100 text-slate-900 @else text-slate-600 hover:bg-slate-50 @endif transition text-sm" href="/reports/store-comparison">
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
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg @if(Request::is('settings/scraper-status')) bg-slate-100 text-slate-900 @else text-slate-600 hover:bg-slate-50 @endif transition text-sm" href="/settings/scraper-status">
              Scraper Status
            </a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg @if(Request::is('settings/configuration')) bg-slate-100 text-slate-900 @else text-slate-600 hover:bg-slate-50 @endif transition text-sm" href="/settings/configuration">
              Configuration
            </a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg @if(Request::is('settings/export')) bg-slate-100 text-slate-900 @else text-slate-600 hover:bg-slate-50 @endif transition text-sm" href="/settings/export">
              Export Data
            </a>
          </div>
        </div>
      </nav>

      <div class="mt-auto p-4">
        <div class="rounded-2xl bg-slate-50 border p-4">
          <div class="text-xs text-slate-500">Last Updated (SGT)</div>
          <div class="text-xs font-semibold" id="lastSyncTime">{{ $lastSync ?? 'Never' }}</div>
          <button onclick="triggerSync()" id="syncBtn" class="mt-3 w-full rounded-xl bg-slate-900 text-white py-2 text-sm font-medium hover:opacity-90 transition">
            Run Sync
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
            <h1 class="text-xl font-semibold">@yield('page-title', 'Overview')</h1>
            <p class="text-sm text-slate-500">@yield('page-description', 'Monitor items & add-ons disabled during peak hours')</p>
          </div>

          <div class="flex items-center gap-2">
            @yield('top-actions')
            <button onclick="window.location.reload()" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:opacity-90 transition">
              Reload
            </button>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <div class="px-4 md:px-8 py-6 space-y-6">
        @yield('content')
      </div>
    </main>
  </div>

  <script>
    // Trigger scraping - context-aware based on current page
    async function triggerSync() {
      const btn = document.getElementById('syncBtn');
      const originalText = btn.textContent;
      const currentPath = window.location.pathname;

      // Determine which sync to run based on current page
      const isItemsPage = currentPath.includes('/items');
      const endpoint = isItemsPage ? '/api/v1/items/sync' : '/api/sync/scrape';
      const syncType = isItemsPage ? 'Items' : 'Platform';

      btn.disabled = true;
      btn.textContent = `Syncing ${syncType}...`;
      btn.classList.add('opacity-50', 'cursor-not-allowed');

      try {
        const response = await fetch(endpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        });

        const data = await response.json();

        if (data.success) {
          btn.classList.remove('bg-slate-900');
          btn.classList.add('bg-green-600');
          btn.textContent = 'Sync Complete!';

          // Show success notification
          showNotification('✅ ' + syncType + ' sync completed successfully! Reloading page...', 'success');

          setTimeout(() => window.location.reload(), 2000);
        } else {
          throw new Error(data.message || 'Sync failed');
        }
      } catch (error) {
        btn.classList.remove('bg-slate-900');
        btn.classList.add('bg-red-600');
        btn.textContent = 'Sync Failed';

        showNotification('❌ Sync failed: ' + error.message, 'error');

        setTimeout(() => {
          btn.disabled = false;
          btn.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-red-600');
          btn.classList.add('bg-slate-900');
          btn.textContent = originalText;
        }, 3000);
      }
    }

    // Notification function
    function showNotification(message, type = 'info') {
      const notification = document.createElement('div');
      notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-xl shadow-2xl font-semibold text-white transform transition-all duration-300 ${
        type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600'
      }`;
      notification.textContent = message;
      notification.style.opacity = '0';
      notification.style.transform = 'translateY(-20px)';

      document.body.appendChild(notification);

      setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateY(0)';
      }, 10);

      setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-20px)';
        setTimeout(() => notification.remove(), 300);
      }, 5000);
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

  @yield('extra-scripts')
</body>
</html>
