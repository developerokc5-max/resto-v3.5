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
        <div>
          <div class="font-semibold leading-tight">HawkerOps</div>
          <div class="text-xs text-slate-500">Store Management</div>
        </div>
      </div>

      <nav class="px-3 pb-6 space-y-1">
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('/') || Request::is('dashboard')) bg-slate-900 text-white shadow-sm @else text-slate-700 hover:bg-slate-100 @endif transition" href="/">
          <span class="text-sm font-medium">Overview</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('stores')) bg-slate-900 text-white shadow-sm @else text-slate-700 hover:bg-slate-100 @endif transition" href="/stores">
          <span class="text-sm font-medium">Stores</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('items')) bg-slate-900 text-white shadow-sm @else text-slate-700 hover:bg-slate-100 @endif transition" href="/items">
          <span class="text-sm font-medium">Items</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('platforms')) bg-slate-900 text-white shadow-sm @else text-slate-700 hover:bg-slate-100 @endif transition" href="/platforms">
          <span class="text-sm font-medium">Platforms</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl @if(Request::is('item-tracking')) bg-slate-900 text-white shadow-sm @else text-slate-700 hover:bg-slate-100 @endif transition" href="/item-tracking">
          <span class="text-sm font-medium">History</span>
        </a>
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
  </script>

  @yield('extra-scripts')
</body>
</html>
