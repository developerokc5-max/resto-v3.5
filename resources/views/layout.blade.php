<!doctype html>
<html lang="en" id="html-root">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', 'HawkerOps Dashboard')</title>
  <link rel="icon" type="image/png" href="/favicon.png" />
  <link rel="preconnect" href="https://cdn.tailwindcss.com">
  <link rel="preconnect" href="https://cdnjs.cloudflare.com">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    // Enable Tailwind dark mode via class strategy
    tailwind.config = { darkMode: 'class' }
    // Apply dark mode immediately to prevent flash
    if (localStorage.getItem('darkMode') === 'true') {
      document.documentElement.classList.add('dark');
    }
  </script>
  <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
  @yield('extra-head')
</head>

<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-200">
  <div class="min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-72 hidden md:flex flex-col border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 relative z-20">
      <div class="px-6 py-5 flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl bg-slate-900 dark:bg-slate-700 text-white grid place-items-center font-bold">HO</div>
        <div class="flex-1">
          <div class="font-semibold leading-tight dark:text-slate-100">HawkerOps</div>
          <div class="text-xs text-slate-500 dark:text-slate-400">Store Management</div>
        </div>
        <div class="flex items-center gap-1">
          <!-- Dark mode toggle -->
          <button onclick="toggleDarkMode()" id="darkToggle" class="h-6 w-6 rounded-full bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs flex items-center justify-center transition" title="Toggle dark mode">
            <span id="darkIcon">🌙</span>
          </button>
          <button onclick="toggleInfoPopup()" class="h-6 w-6 rounded-full bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs font-bold flex items-center justify-center transition">
            i
          </button>
        </div>
      </div>

      <nav class="px-3 pb-6 space-y-1 overflow-y-auto flex-1">
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('/') || Request::is('dashboard')) bg-slate-900 dark:bg-slate-700 text-white shadow-sm @else text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 @endif transition" href="/">
          <span class="text-sm font-medium">📊 Overview</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('stores')) bg-slate-900 dark:bg-slate-700 text-white shadow-sm @else text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 @endif transition" href="/stores">
          <span class="text-sm font-medium">🏪 Stores</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('items')) bg-slate-900 dark:bg-slate-700 text-white shadow-sm @else text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 @endif transition" href="/items">
          <span class="text-sm font-medium">📦 Items</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('platforms')) bg-slate-900 dark:bg-slate-700 text-white shadow-sm @else text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 @endif transition" href="/platforms">
          <span class="text-sm font-medium">🌐 Platforms</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('alerts')) bg-slate-900 dark:bg-slate-700 text-white shadow-sm @else text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 @endif transition" href="/alerts">
          <span class="text-sm font-medium">🔔 Alerts</span>
        </a>

        <div class="border-t border-slate-200 dark:border-slate-700 my-2"></div>

        <!-- Reports Section -->
        <div class="space-y-1">
          <button onclick="toggleSection('reports')" class="flex items-center justify-between w-full px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
            <span class="text-sm font-medium">📈 Reports</span>
            <svg id="reports-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>
          <div id="reports-section" class="hidden pl-4 space-y-1">
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg @if(Request::is('reports/daily-trends')) bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white @else text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 @endif transition text-sm" href="/reports/daily-trends">Daily Trends</a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg @if(Request::is('reports/platform-reliability')) bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white @else text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 @endif transition text-sm" href="/reports/platform-reliability">Platform Reliability</a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg @if(Request::is('reports/item-performance')) bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white @else text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 @endif transition text-sm" href="/reports/item-performance">Item Performance</a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg @if(Request::is('reports/store-comparison')) bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white @else text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 @endif transition text-sm" href="/reports/store-comparison">Store Comparison</a>
          </div>
        </div>

        <!-- Settings Section -->
        <div class="space-y-1">
          <button onclick="toggleSection('settings')" class="flex items-center justify-between w-full px-3 py-2 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
            <span class="text-sm font-medium">⚙️ Settings</span>
            <svg id="settings-arrow" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>
          <div id="settings-section" class="hidden pl-4 space-y-1">
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg @if(Request::is('settings/scraper-status')) bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white @else text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 @endif transition text-sm" href="/settings/scraper-status">Scraper Status</a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg @if(Request::is('settings/configuration')) bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white @else text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 @endif transition text-sm" href="/settings/configuration">Configuration</a>
            <a class="flex items-center gap-2 px-3 py-2 rounded-lg @if(Request::is('settings/export')) bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white @else text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 @endif transition text-sm" href="/settings/export">Export Data</a>
          </div>
        </div>
      </nav>

      <div class="mt-auto p-4">
        <div class="rounded-2xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4">
          <div class="text-xs text-slate-500 dark:text-slate-400">Last Updated (SGT)</div>
          <div class="text-sm font-semibold text-slate-900 dark:text-slate-100 break-words leading-tight" id="lastSyncTime">{{ $lastSync ?? 'Never' }}</div>
          <button onclick="triggerSync()" id="syncBtn" class="mt-3 w-full rounded-xl bg-slate-900 dark:bg-slate-700 text-white py-2 text-sm font-medium hover:opacity-90 transition">
            <span id="syncBtnText">Refresh Data</span>
          </button>
        </div>
      </div>

      <script>
        function updateSyncButtonText() {
          const path = window.location.pathname;
          const btnText = document.getElementById('syncBtnText');
          if (path === '/items' || path === '/platforms') {
            btnText.textContent = 'Run Sync';
          } else {
            btnText.textContent = 'Refresh Data';
          }
        }
        document.addEventListener('DOMContentLoaded', updateSyncButtonText);
        updateSyncButtonText();
      </script>
    </aside>

    <!-- Main -->
    <main class="flex-1">
      <!-- Topbar -->
      <header class="sticky top-0 z-10 bg-white/80 dark:bg-slate-900/80 backdrop-blur border-b border-slate-200 dark:border-slate-800">
        <div class="px-4 md:px-8 py-4 flex items-center justify-between gap-3">
          <div>
            <h1 class="text-xl font-semibold dark:text-slate-100">@yield('page-title', 'Overview')</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">@yield('page-description', 'Monitor items & add-ons disabled during peak hours')</p>
          </div>
          <div class="flex items-center gap-2">
            @yield('top-actions')
            <button onclick="window.location.reload()" class="rounded-xl bg-slate-900 dark:bg-slate-700 text-white px-4 py-2 text-sm font-medium hover:opacity-90 transition">
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
      <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
          <div class="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🔄 Refresh Data Button</div>
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">Located in the left sidebar. Refreshes data from the database and updates platform status and item availability without running scrapers.</p>
          </div>
          <div class="bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">↻ Reload Button</div>
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">Located in the top-right corner. Reloads the entire page to show the latest data from the database.</p>
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
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed"><strong>View Items:</strong> See all menu items with their status. <strong>View Logs:</strong> Check daily status history.</p>
          </div>
          <div class="bg-cyan-50 dark:bg-cyan-900/30 border-l-4 border-cyan-500 p-4 rounded-lg">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🎨 Filter Buttons</div>
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">Filter stores by status: All, All Online, Partial Offline, All Offline.</p>
          </div>
        </div>
        <div>
          <div class="bg-pink-50 dark:bg-pink-900/30 border-l-4 border-pink-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">📊 Status Indicators</div>
            <div class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed space-y-1">
              <p><strong>🟢 Green:</strong> All 3 platforms online</p>
              <p><strong>🟡 Orange:</strong> 1-2 platforms offline</p>
              <p><strong>🔴 Red:</strong> All 3 platforms offline</p>
            </div>
          </div>
          <div class="bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🔢 Item Information</div>
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">Each menu item appears 3 times (Grab, FoodPanda, Deliveroo).</p>
          </div>
          <div class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🌐 Platforms Monitored</div>
            <div class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed space-y-1">
              <p><strong>🟢 Grab</strong> · <strong>🩷 FoodPanda</strong> · <strong>🔵 Deliveroo</strong></p>
            </div>
          </div>
          <div class="bg-slate-50 dark:bg-slate-800 border-l-4 border-slate-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">📈 Dashboard Cards</div>
            <div class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed space-y-1">
              <p><strong>Stores Online</strong> · <strong>Items OFF</strong> · <strong>Active Alerts</strong> · <strong>Platforms Status</strong></p>
            </div>
          </div>
          <div class="bg-teal-50 dark:bg-teal-900/30 border-l-4 border-teal-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">📍 Timezone</div>
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">All timestamps in Singapore Time (SGT, UTC+8). 46 outlets monitored.</p>
          </div>
          <div class="bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">⚡ Performance</div>
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">Loads under 1 second. Supports 30+ concurrent users.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Dark mode toggle
    function toggleDarkMode() {
      const html = document.documentElement;
      const isDark = html.classList.toggle('dark');
      localStorage.setItem('darkMode', isDark);
      document.getElementById('darkIcon').textContent = isDark ? '☀️' : '🌙';
    }

    // Set correct icon on load
    document.addEventListener('DOMContentLoaded', function() {
      const isDark = localStorage.getItem('darkMode') === 'true';
      document.getElementById('darkIcon').textContent = isDark ? '☀️' : '🌙';
    });

    async function triggerSync() {
      const btn = document.getElementById('syncBtn');
      const originalText = btn.textContent;
      const currentPath = window.location.pathname;
      const isItemsPage = currentPath.includes('/items');
      const endpoint = isItemsPage ? '/api/v1/items/sync' : '/api/sync/scrape';
      const syncType = isItemsPage ? 'Items' : 'Platform';

      btn.disabled = true;
      btn.textContent = `Syncing ${syncType}...`;
      btn.classList.add('opacity-50', 'cursor-not-allowed');

      try {
        const response = await fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (data.success) {
          btn.classList.remove('bg-slate-900', 'dark:bg-slate-700');
          btn.classList.add('bg-green-600');
          btn.textContent = 'Sync Complete!';
          showNotification('✅ ' + syncType + ' sync completed successfully! Reloading page...', 'success');
          setTimeout(() => window.location.reload(), 2000);
        } else {
          throw new Error(data.message || 'Sync failed');
        }
      } catch (error) {
        btn.classList.remove('bg-slate-900', 'dark:bg-slate-700');
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

    function showNotification(message, type = 'info') {
      const notification = document.createElement('div');
      notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-xl shadow-2xl font-semibold text-white transform transition-all duration-300 ${
        type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600'
      }`;
      notification.textContent = message;
      notification.style.opacity = '0';
      notification.style.transform = 'translateY(-20px)';
      document.body.appendChild(notification);
      setTimeout(() => { notification.style.opacity = '1'; notification.style.transform = 'translateY(0)'; }, 10);
      setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-20px)';
        setTimeout(() => notification.remove(), 300);
      }, 5000);
    }

    setTimeout(() => window.location.reload(), 300000);

    function toggleInfoPopup() {
      document.getElementById('infoPopup').classList.toggle('hidden');
    }

    document.getElementById('infoPopup')?.addEventListener('click', function(e) {
      if (e.target === this) toggleInfoPopup();
    });

    function toggleSection(sectionName) {
      const section = document.getElementById(sectionName + '-section');
      const arrow = document.getElementById(sectionName + '-arrow');
      section.classList.toggle('hidden');
      arrow.classList.toggle('rotate-180');
    }

    function updateSyncButtonText() {
      const path = window.location.pathname;
      const btnText = document.getElementById('syncBtnText');
      btnText.textContent = (path === '/items' || path === '/platforms') ? 'Run Sync' : 'Refresh Data';
    }
    document.addEventListener('DOMContentLoaded', updateSyncButtonText);
    updateSyncButtonText();
  </script>

  @yield('extra-scripts')
</body>
</html>
