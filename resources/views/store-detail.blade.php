<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $store['name'] ?? 'Store Detail' }} - HawkerOps</title>
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
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl bg-slate-900 text-white shadow-sm" href="/stores">
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
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="/item-tracking">
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
        <div class="px-4 md:px-8 py-4">
          <div class="flex items-center gap-2 mb-2">
            <a href="/stores" class="text-slate-500 hover:text-slate-700 text-sm">← Back to Stores</a>
          </div>
          <div class="flex items-center justify-between gap-3">
            <div>
              <h1 class="text-xl font-semibold">{{ $store['name'] ?? 'Store Details' }}</h1>
              <p class="text-sm text-slate-500">{{ $store['brand'] ?? '' }} • Shop ID: {{ $store['shop_id'] ?? '' }}</p>
            </div>

            <div class="flex items-center gap-2">
              <span class="px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-100">
                {{ $store['status'] ?? 'OPERATING' }}
              </span>
            </div>
          </div>
        </div>
      </header>

      <div class="px-4 md:px-8 py-6 space-y-6">

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-white border rounded-2xl p-5 shadow-sm">
            <div class="text-sm text-slate-500">Total Items</div>
            <div class="mt-2 text-3xl font-semibold">{{ $store['total_items'] ?? 0 }}</div>
          </div>

          <div class="bg-white border rounded-2xl p-5 shadow-sm">
            <div class="text-sm text-slate-500">Active Items</div>
            <div class="mt-2 text-3xl font-semibold text-emerald-600">{{ $store['active_items'] ?? 0 }}</div>
          </div>

          <div class="bg-white border rounded-2xl p-5 shadow-sm">
            <div class="text-sm text-slate-500">Items OFF</div>
            <div class="mt-2 text-3xl font-semibold {{ ($store['items_off'] ?? 0) > 0 ? 'text-red-600' : 'text-slate-400' }}">
              {{ $store['items_off'] ?? 0 }}
            </div>
          </div>

          <div class="bg-white border rounded-2xl p-5 shadow-sm">
            <div class="text-sm text-slate-500">Changes Today</div>
            <div class="mt-2 text-3xl font-semibold">{{ $store['changes_today'] ?? 0 }}</div>
          </div>
        </div>

        <!-- Menu Items -->
        <div class="bg-white border rounded-2xl overflow-hidden">
          <div class="px-6 py-4 border-b bg-slate-50">
            <h2 class="font-semibold text-slate-900">Menu Items</h2>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
            @forelse($items ?? [] as $item)
              <div class="border rounded-xl overflow-hidden hover:shadow-md transition">
                <!-- Food Image -->
                <div class="relative bg-slate-100 h-32 flex items-center justify-center">
                  <div class="text-4xl">
                    @php
                      $name = strtolower($item['name'] ?? '');
                      $emoji = '🍽️';
                      if (str_contains($name, 'chicken') || str_contains($name, 'chix')) $emoji = '🍗';
                      elseif (str_contains($name, 'rice')) $emoji = '🍚';
                      elseif (str_contains($name, 'nood') || str_contains($name, 'mee')) $emoji = '🍜';
                      elseif (str_contains($name, 'drink') || str_contains($name, 'redbull')) $emoji = '🥤';
                      elseif (str_contains($name, 'porridge')) $emoji = '🥣';
                    @endphp
                    {{ $emoji }}
                  </div>

                  <div class="absolute top-2 right-2">
                    @if($item['is_active'])
                      <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-500 text-white">ON</span>
                    @else
                      <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-500 text-white">OFF</span>
                    @endif
                  </div>
                </div>

                <div class="p-3">
                  <h3 class="font-semibold text-sm text-slate-900 mb-1">{{ $item['name'] }}</h3>
                  <div class="flex items-center justify-between">
                    <div class="text-base font-bold text-slate-900">${{ number_format($item['price'] ?? 0, 2) }}</div>
                    <div class="text-xs text-slate-500">{{ $item['last_update'] ?? '—' }}</div>
                  </div>
                </div>
              </div>
            @empty
              <div class="col-span-full text-center py-12 text-slate-500">
                No items found for this store
              </div>
            @endforelse
          </div>
        </div>

      </div>
    </main>
  </div>
</body>
</html>
