<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Item Tracking History - HawkerOps</title>
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
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="#">
          <span class="text-sm font-medium">Add-ons (Modifiers)</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="#">
          <span class="text-sm font-medium">Alerts</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl bg-slate-900 text-white shadow-sm" href="/item-tracking">
          <span class="text-sm font-medium">History</span>
        </a>
      </nav>

      <div class="mt-auto p-4">
        <div class="rounded-2xl bg-slate-50 border p-4">
          <div class="text-xs text-slate-500">Last sync</div>
          <div class="font-semibold">{{ $lastSync ?? '—' }}</div>
          <button class="mt-3 w-full rounded-xl bg-slate-900 text-white py-2 text-sm font-medium hover:opacity-90 transition">
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
            <h1 class="text-xl font-semibold">Item Tracking History</h1>
            <p class="text-sm text-slate-500">Monitor when items are turned ON/OFF across all stores</p>
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

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="bg-white border rounded-2xl p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total Changes Today</div>
            <div class="mt-2 text-3xl font-semibold">{{ count($changes ?? []) }}</div>
          </div>

          <div class="bg-white border rounded-2xl p-5 shadow-sm">
            <div class="text-sm text-slate-500">Items Turned OFF</div>
            <div class="mt-2 text-3xl font-semibold text-red-600">{{ $stats['turned_off'] ?? 0 }}</div>
          </div>

          <div class="bg-white border rounded-2xl p-5 shadow-sm">
            <div class="text-sm text-slate-500">Items Turned ON</div>
            <div class="mt-2 text-3xl font-semibold text-emerald-600">{{ $stats['turned_on'] ?? 0 }}</div>
          </div>
        </div>

        <!-- Changes Timeline -->
        <div class="bg-white border rounded-2xl overflow-hidden">
          <div class="px-6 py-4 border-b bg-slate-50">
            <h2 class="font-semibold text-slate-900">Recent Changes</h2>
          </div>

          <div class="divide-y">
            @forelse($changes ?? [] as $change)
              <div class="px-6 py-4 hover:bg-slate-50 transition">
                <div class="flex items-start gap-4">
                  <!-- Food Icon -->
                  <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-slate-100 flex items-center justify-center text-2xl">
                    @php
                      $name = strtolower($change['item_name'] ?? '');
                      $emoji = '🍽️';
                      if (str_contains($name, 'chicken') || str_contains($name, 'chix')) $emoji = '🍗';
                      elseif (str_contains($name, 'rice')) $emoji = '🍚';
                      elseif (str_contains($name, 'nood') || str_contains($name, 'mee')) $emoji = '🍜';
                      elseif (str_contains($name, 'drink')) $emoji = '🥤';
                      elseif (str_contains($name, 'porridge')) $emoji = '🥣';
                    @endphp
                    {{ $emoji }}
                  </div>

                  <!-- Change Details -->
                  <div class="flex-1 min-w-0">
                    <div class="flex items-start justify-between gap-4 mb-2">
                      <div>
                        <h3 class="font-semibold text-slate-900">{{ $change['item_name'] ?? 'Unknown Item' }}</h3>
                        <p class="text-sm text-slate-500">{{ $change['shop_name'] ?? 'Unknown Store' }} • Shop ID: {{ $change['shop_id'] ?? '' }}</p>
                      </div>
                      <div class="text-xs text-slate-500 whitespace-nowrap">
                        {{ $change['timestamp'] ?? '—' }}
                      </div>
                    </div>

                    <!-- Change Details -->
                    <div class="space-y-2">
                      @if(isset($change['changes']['is_active']))
                        <div class="flex items-center gap-3">
                          <span class="text-sm text-slate-600">Status:</span>
                          <div class="flex items-center gap-2">
                            @if($change['changes']['is_active']['from'] == 1)
                              <span class="px-2 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-700">ON</span>
                              <span class="text-slate-400">→</span>
                              <span class="px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700">OFF</span>
                              <span class="ml-2 px-2 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600">⚠️ Item Disabled</span>
                            @else
                              <span class="px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-700">OFF</span>
                              <span class="text-slate-400">→</span>
                              <span class="px-2 py-1 rounded text-xs font-medium bg-emerald-100 text-emerald-700">ON</span>
                              <span class="ml-2 px-2 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-600">✓ Item Enabled</span>
                            @endif
                          </div>
                        </div>
                      @endif

                      @if(isset($change['changes']['price']))
                        <div class="flex items-center gap-3">
                          <span class="text-sm text-slate-600">Price:</span>
                          <div class="flex items-center gap-2">
                            <span class="text-sm font-mono">${{ number_format($change['changes']['price']['from'] ?? 0, 2) }}</span>
                            <span class="text-slate-400">→</span>
                            <span class="text-sm font-mono font-semibold">${{ number_format($change['changes']['price']['to'] ?? 0, 2) }}</span>
                          </div>
                        </div>
                      @endif

                      @if(isset($change['changes']['name']))
                        <div class="flex items-center gap-3">
                          <span class="text-sm text-slate-600">Name:</span>
                          <div class="flex items-center gap-2">
                            <span class="text-sm text-slate-500">{{ $change['changes']['name']['from'] }}</span>
                            <span class="text-slate-400">→</span>
                            <span class="text-sm font-medium">{{ $change['changes']['name']['to'] }}</span>
                          </div>
                        </div>
                      @endif

                      @if(isset($change['changes']['created']) && $change['changes']['created'])
                        <div class="inline-flex items-center gap-2 px-2 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-600">
                          ✨ New Item Added
                        </div>
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            @empty
              <div class="px-6 py-12 text-center">
                <div class="text-6xl mb-4">📊</div>
                <h3 class="text-lg font-semibold text-slate-900 mb-2">No Changes Recorded</h3>
                <p class="text-sm text-slate-500">Item changes will appear here when items are turned ON/OFF or modified</p>
              </div>
            @endforelse
          </div>
        </div>

      </div>
    </main>
  </div>
</body>
</html>
