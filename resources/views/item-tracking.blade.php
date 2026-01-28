<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>History - HawkerOps</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-50 text-slate-900">
  <div class="min-h-screen flex">

    <!-- Sidebar -->
    <aside class="w-72 hidden md:flex flex-col border-r bg-white">
      <div class="px-6 py-5 flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl bg-slate-900 text-white grid place-items-center font-bold">HO</div>
        <div>
          <div class="font-semibold leading-tight">HawkerOps</div>
          <div class="text-xs text-slate-500">Store Management</div>
        </div>
      </div>

      <nav class="px-3 pb-6 space-y-1">
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="/dashboard">
          <span class="text-sm font-medium">Overview</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="/stores">
          <span class="text-sm font-medium">Stores</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="/items">
          <span class="text-sm font-medium">Items</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="/platforms">
          <span class="text-sm font-medium">Platforms</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl bg-slate-900 text-white shadow-sm" href="/history">
          <span class="text-sm font-medium">History</span>
        </a>
      </nav>

      <div class="mt-auto p-4">
        <div class="rounded-2xl bg-slate-50 border p-4">
          <div class="text-xs text-slate-500">Last sync</div>
          <div class="text-sm font-semibold" id="lastSyncTime">{{ $lastSync ?? '—' }}</div>
          <button onclick="window.location.reload()" class="mt-3 w-full rounded-xl bg-slate-900 text-white py-2 text-sm font-medium hover:opacity-90 transition">
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
            <h1 class="text-xl font-semibold">History</h1>
            <p class="text-sm text-slate-500">Activity log of outlet and item status changes</p>
          </div>

          <div class="flex items-center gap-2">
            <select class="rounded-xl border bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50 transition">
              <option value="today">Today</option>
              <option value="week">This Week</option>
              <option value="month">This Month</option>
            </select>
            <button class="rounded-xl border bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50 transition">
              Export
            </button>
          </div>
        </div>
      </header>

      <div class="px-4 md:px-8 py-6 space-y-6">

        <!-- Date Display -->
        <div class="bg-gradient-to-r from-slate-900 to-slate-700 rounded-2xl p-6 text-white">
          <div class="text-sm opacity-80">Showing changes for</div>
          <div class="text-2xl font-bold mt-1">{{ $today ?? 'Today' }}</div>
        </div>

        <!-- Summary Card -->
        <div class="bg-white border rounded-2xl shadow-sm overflow-hidden">
          <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x">
            <!-- Platforms Section -->
            <div class="p-6">
              <h3 class="text-sm font-medium text-slate-500 mb-4">Platforms</h3>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-slate-600">ON:</span>
                  <span class="text-3xl font-bold text-green-600">{{ $platformStats['online'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-slate-600">OFF:</span>
                  <span class="text-3xl font-bold text-red-600">{{ $platformStats['offline'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between pt-3 border-t">
                  <span class="text-sm font-semibold text-slate-700">Total:</span>
                  <span class="text-2xl font-bold text-slate-900">{{ $platformStats['total'] ?? 0 }}</span>
                </div>
              </div>
              <a href="/platforms" target="_blank" class="mt-4 block w-full text-center px-4 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:opacity-90 transition">
                View Platforms →
              </a>
            </div>

            <!-- Items Section -->
            <div class="p-6">
              <h3 class="text-sm font-medium text-slate-500 mb-4">Items</h3>
              <div class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-slate-600">ON:</span>
                  <span class="text-3xl font-bold text-green-600">{{ $itemStats['on'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-slate-600">OFF:</span>
                  <span class="text-3xl font-bold text-red-600">{{ $itemStats['off'] ?? 0 }}</span>
                </div>
                <div class="flex items-center justify-between pt-3 border-t">
                  <span class="text-sm font-semibold text-slate-700">Total:</span>
                  <span class="text-2xl font-bold text-slate-900">{{ $itemStats['total'] ?? 0 }}</span>
                </div>
              </div>
              <a href="/items" target="_blank" class="mt-4 block w-full text-center px-4 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:opacity-90 transition">
                View Items →
              </a>
            </div>
          </div>
        </div>


      </div>
    </main>
  </div>

  <script>
    async function runItemsSync() {
      const btn = document.getElementById('runSyncBtn');
      const btnText = document.getElementById('syncBtnText');
      const originalText = btnText.textContent;

      try {
        // Disable button and show loading state
        btn.disabled = true;
        btnText.textContent = 'Syncing...';

        const response = await fetch('/api/sync/items/sync', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
          }
        });

        const data = await response.json();

        if (data.success) {
          btnText.textContent = '✓ Sync Complete';
          // Update last sync time
          const lastSyncEl = document.getElementById('lastSyncTime');
          if (lastSyncEl) {
            const now = new Date();
            lastSyncEl.textContent = now.toLocaleString();
          }

          // Reload page after 2 seconds to show new data
          setTimeout(() => {
            window.location.reload();
          }, 2000);
        } else {
          throw new Error(data.message || 'Sync failed');
        }
      } catch (error) {
        console.error('Sync error:', error);
        btnText.textContent = '✗ Sync Failed';
        alert('Failed to sync items: ' + error.message);

        // Reset button after 3 seconds
        setTimeout(() => {
          btn.disabled = false;
          btnText.textContent = originalText;
        }, 3000);
      }
    }
  </script>
</body>
</html>
