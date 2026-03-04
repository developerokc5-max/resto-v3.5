<!doctype html>
<html lang="en" id="html-root">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>@yield('title', 'HawkerOps Dashboard')</title>
  <link rel="icon" type="image/png" href="/favicon.png" />

  {{-- PWA --}}
  <link rel="manifest" href="/manifest.json" />
  <meta name="theme-color" content="#0f172a" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
  <meta name="apple-mobile-web-app-title" content="HawkerOps" />
  <link rel="apple-touch-icon" href="/icon-192.png" />
  <link rel="preconnect" href="https://cdnjs.cloudflare.com">
  <script>
    // Apply dark mode immediately to prevent flash
    if (localStorage.getItem('darkMode') === 'true') {
      document.documentElement.classList.add('dark');
    }
  </script>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
  @yield('extra-head')
</head>

<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-200">
  <div class="min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-72 hidden md:flex flex-col border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 relative z-20">
      <div class="px-6 py-5 flex items-center gap-3">
        <img src="/images/logo-light.png?v=3" class="h-10 w-10 rounded-xl object-cover dark:hidden" alt="HO">
        <img src="/images/logo-dark.png?v=3"  class="h-10 w-10 rounded-xl object-cover hidden dark:block" alt="HO">
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
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('history') || Request::is('history/*')) bg-slate-900 dark:bg-slate-700 text-white shadow-sm @else text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 @endif transition" href="/history">
          <span class="text-sm font-medium">📋 History</span>
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

    </aside>

    <!-- Main -->
    <main class="flex-1">
      <!-- Topbar -->
      <header class="sticky top-0 z-10 bg-white/80 dark:bg-slate-900/80 backdrop-blur border-b border-slate-200 dark:border-slate-800">
        <div class="px-4 md:px-8 py-3 md:py-4 flex items-center gap-3">
          {{-- Hamburger: mobile only --}}
          <button onclick="toggleMobileDrawer()" class="md:hidden flex-shrink-0 h-9 w-9 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-700 transition" aria-label="Menu">
            <svg id="hamburger-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
          </button>
          <div class="flex-1 min-w-0">
            <h1 class="text-lg md:text-xl font-semibold dark:text-slate-100 truncate">@yield('page-title', 'Overview')</h1>
            <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400 hidden md:block">@yield('page-description', 'Monitor items & add-ons disabled during peak hours')</p>
          </div>
          <div class="flex items-center gap-2 flex-shrink-0">
            @yield('top-actions')
            <button onclick="smartReload(this)" class="rounded-xl bg-slate-900 dark:bg-slate-700 text-white px-4 py-2 text-sm font-medium hover:opacity-90 transition">
              Reload
            </button>
          </div>
        </div>
      </header>

      <!-- Page Content -->
      <div id="main-content" class="px-4 md:px-8 py-6 space-y-6">
        @yield('content')
      </div>
    </main>
  </div>

  <!-- ── Mobile Drawer Overlay ───────────────────────────────────────────── -->
  <div id="mobile-drawer-overlay"
       class="md:hidden hidden fixed inset-0 z-40 bg-black/50"
       onclick="toggleMobileDrawer()"></div>

  <!-- ── Mobile Drawer Panel ────────────────────────────────────────────── -->
  <div id="mobile-drawer"
       class="md:hidden fixed top-0 left-0 bottom-0 z-50 w-72 bg-white dark:bg-slate-900 shadow-2xl flex flex-col
              transform -translate-x-full transition-transform duration-300 ease-in-out"
       style="padding-bottom: env(safe-area-inset-bottom);">

    {{-- Drawer header --}}
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 dark:border-slate-800">
      <div class="flex items-center gap-3">
        <img src="/images/logo-light.png?v=3" class="h-9 w-9 rounded-xl object-cover dark:hidden" alt="HO">
        <img src="/images/logo-dark.png?v=3"  class="h-9 w-9 rounded-xl object-cover hidden dark:block" alt="HO">
        <div>
          <div class="font-semibold text-sm dark:text-slate-100">HawkerOps</div>
          <div class="text-xs text-slate-500 dark:text-slate-400">Store Management</div>
        </div>
      </div>
      <button onclick="toggleMobileDrawer()" class="h-8 w-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition text-lg">
        &times;
      </button>
    </div>

    {{-- Nav links --}}
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
      <a href="/dashboard" onclick="toggleMobileDrawer()"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                @if(Request::is('/') || Request::is('dashboard')) bg-slate-900 dark:bg-slate-700 text-white @else text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
        📊 Overview
      </a>
      <a href="/stores" onclick="toggleMobileDrawer()"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                @if(Request::is('stores') || Request::is('stores/*')) bg-slate-900 dark:bg-slate-700 text-white @else text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
        🏪 Stores
      </a>
      <a href="/items" onclick="toggleMobileDrawer()"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                @if(Request::is('items')) bg-slate-900 dark:bg-slate-700 text-white @else text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
        📦 Items
      </a>
      <a href="/platforms" onclick="toggleMobileDrawer()"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                @if(Request::is('platforms')) bg-slate-900 dark:bg-slate-700 text-white @else text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
        🌐 Platforms
      </a>
      <a href="/alerts" onclick="toggleMobileDrawer()"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                @if(Request::is('alerts')) bg-slate-900 dark:bg-slate-700 text-white @else text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
        🔔 Alerts
      </a>
      <a href="/history" onclick="toggleMobileDrawer()"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                @if(Request::is('history') || Request::is('history/*')) bg-slate-900 dark:bg-slate-700 text-white @else text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
        📋 History
      </a>

      <div class="border-t border-slate-200 dark:border-slate-700 my-2"></div>

      <div class="px-3 pt-1 pb-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Reports</div>
      <a href="/reports/daily-trends" onclick="toggleMobileDrawer()"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                @if(Request::is('reports/daily-trends')) bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white @else text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
        📈 Daily Trends
      </a>
      <a href="/reports/platform-reliability" onclick="toggleMobileDrawer()"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                @if(Request::is('reports/platform-reliability')) bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white @else text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
        📉 Platform Reliability
      </a>
      <a href="/reports/item-performance" onclick="toggleMobileDrawer()"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                @if(Request::is('reports/item-performance')) bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white @else text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
        🏅 Item Performance
      </a>
      <a href="/reports/store-comparison" onclick="toggleMobileDrawer()"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                @if(Request::is('reports/store-comparison')) bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white @else text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
        🏆 Store Comparison
      </a>

      <div class="border-t border-slate-200 dark:border-slate-700 my-2"></div>

      <div class="px-3 pt-1 pb-1 text-[10px] font-semibold uppercase tracking-widest text-slate-400 dark:text-slate-500">Settings</div>
      <a href="/settings/scraper-status" onclick="toggleMobileDrawer()"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                @if(Request::is('settings/scraper-status')) bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white @else text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
        ⚙️ Scraper Status
      </a>
      <a href="/settings/configuration" onclick="toggleMobileDrawer()"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                @if(Request::is('settings/configuration')) bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white @else text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
        ⚙️ Configuration
      </a>
      <a href="/settings/export" onclick="toggleMobileDrawer()"
         class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition
                @if(Request::is('settings/export')) bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white @else text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 @endif">
        📤 Export Data
      </a>
    </nav>

    {{-- Drawer footer: sync + dark mode --}}
    <div class="px-4 pb-4 space-y-2">
      <button onclick="smartReload(this)" id="mobileRefreshBtn"
              class="w-full rounded-xl bg-slate-600 dark:bg-slate-600 text-white py-2.5 text-sm font-medium hover:opacity-90 transition">
        <span id="mobileRefreshBtnText">🔄 Data Refresh</span>
      </button>
      <button onclick="triggerBothSyncs(); toggleMobileDrawer();" id="mobileSyncBtn"
              class="w-full rounded-xl bg-slate-900 dark:bg-slate-700 text-white py-2.5 text-sm font-medium hover:opacity-90 transition">
        <span id="mobileSyncBtnText">⚡ Sync All</span>
      </button>
      <button onclick="toggleDarkMode()" id="mobileDarkToggle"
              class="w-full rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 py-2.5 text-sm font-medium hover:bg-slate-200 dark:hover:bg-slate-700 transition">
        <span id="mobileDarkIcon">🌙</span> Dark Mode
      </button>
    </div>
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

  <script src="/js/hawkerops.js?v=2" defer></script>

  @yield('extra-scripts')

  {{-- PWA: Register service worker + update banner --}}
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
          .then(reg => console.log('[PWA] Service worker registered:', reg.scope))
          .catch(err => console.log('[PWA] Service worker failed:', err));

        // Show update banner when new SW version activates
        navigator.serviceWorker.addEventListener('message', event => {
          if (event.data?.type === 'SW_UPDATED') {
            const banner = document.createElement('div');
            banner.innerHTML = `
              <div style="position:fixed;bottom:16px;left:50%;transform:translateX(-50%);z-index:9999;
                background:#1e293b;color:#f1f5f9;padding:12px 20px;border-radius:12px;
                box-shadow:0 4px 24px rgba(0,0,0,0.4);display:flex;align-items:center;gap:12px;
                font-family:sans-serif;font-size:14px;border:1px solid #334155;">
                <span>⚡ New version available</span>
                <button onclick="window.location.reload()" style="background:#16a34a;color:#fff;
                  border:none;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;">
                  Reload
                </button>
                <button onclick="this.parentElement.parentElement.remove()" style="background:none;
                  color:#64748b;border:none;cursor:pointer;font-size:18px;line-height:1;">×</button>
              </div>`;
            document.body.appendChild(banner);
          }
        });
      });
    }
  </script>
</body>
</html>
