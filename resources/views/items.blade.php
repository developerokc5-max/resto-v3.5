<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Items - HawkerOps</title>
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
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl bg-slate-900 text-white shadow-sm" href="/items">
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
        <div class="px-4 md:px-8 py-4 flex items-center justify-between gap-3">
          <div>
            <h1 class="text-xl font-semibold">Menu Items</h1>
            <p class="text-sm text-slate-500">{{ count($items ?? []) }} items across all stores</p>
          </div>

          <div class="flex items-center gap-2">
            <div class="hidden sm:flex items-center bg-slate-100 rounded-xl px-3 py-2">
              <input class="bg-transparent outline-none text-sm w-64" placeholder="Search items..." />
            </div>
            <select class="rounded-xl border bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50 transition">
              <option value="">All Stores</option>
              <option value="active">Active Only</option>
              <option value="inactive">Inactive Only</option>
            </select>
          </div>
        </div>
      </header>

      <div class="px-4 md:px-8 py-6">

        <!-- Items Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          @forelse($items ?? [] as $item)
            <div class="bg-white border rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition">
              <!-- Food Image -->
              <div class="relative bg-slate-100 h-48 flex items-center justify-center">
                @if(!empty($item['image_url']))
                  <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                @else
                  <!-- Placeholder with food emoji based on item name -->
                  <div class="text-6xl">
                    @php
                      $name = strtolower($item['name'] ?? '');
                      $emoji = '🍽️';
                      if (str_contains($name, 'chicken') || str_contains($name, 'chix')) $emoji = '🍗';
                      elseif (str_contains($name, 'rice')) $emoji = '🍚';
                      elseif (str_contains($name, 'nood') || str_contains($name, 'mee')) $emoji = '🍜';
                      elseif (str_contains($name, 'drink') || str_contains($name, 'redbull') || str_contains($name, 'tea')) $emoji = '🥤';
                      elseif (str_contains($name, 'porridge')) $emoji = '🥣';
                      elseif (str_contains($name, 'egg')) $emoji = '🥚';
                      elseif (str_contains($name, 'soup')) $emoji = '🍲';
                      elseif (str_contains($name, 'prawn') || str_contains($name, 'shrimp')) $emoji = '🦐';
                    @endphp
                    {{ $emoji }}
                  </div>
                @endif

                <!-- Status Badge -->
                <div class="absolute top-2 right-2">
                  @if($item['is_active'])
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-500 text-white shadow-lg">
                      ACTIVE
                    </span>
                  @else
                    <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-500 text-white shadow-lg">
                      OFF
                    </span>
                  @endif
                </div>
              </div>

              <!-- Item Info -->
              <div class="p-4">
                <h3 class="font-semibold text-sm text-slate-900 mb-1">{{ $item['name'] }}</h3>
                <p class="text-xs text-slate-500 mb-2">{{ $item['shop_name'] ?? 'Unknown Store' }}</p>

                <div class="flex items-center justify-between mb-3">
                  <div class="text-lg font-bold text-slate-900">
                    ${{ number_format($item['price'] ?? 0, 2) }}
                  </div>
                  <div class="text-xs text-slate-500 font-mono">
                    #{{ substr($item['item_id'], 0, 8) }}
                  </div>
                </div>

                <div class="flex items-center justify-between text-xs">
                  <span class="text-slate-500">Last updated</span>
                  <span class="text-slate-600 font-medium">{{ $item['last_update'] ?? '—' }}</span>
                </div>
              </div>
            </div>
          @empty
            <div class="col-span-full bg-white border rounded-2xl p-12 text-center">
              <div class="text-6xl mb-4">📦</div>
              <h3 class="text-lg font-semibold text-slate-900 mb-2">No Items Found</h3>
              <p class="text-sm text-slate-500">Run sync to load menu items from RestoSuite</p>
            </div>
          @endforelse
        </div>

        <!-- Pagination -->
        @if(count($items ?? []) > 0)
        <div class="mt-6 flex items-center justify-center gap-2">
          <button class="px-4 py-2 rounded-xl border bg-white text-sm font-medium hover:bg-slate-50 transition">
            ← Previous
          </button>
          <div class="px-4 py-2 text-sm text-slate-600">
            Page 1 of {{ ceil(count($items) / 20) }}
          </div>
          <button class="px-4 py-2 rounded-xl border bg-white text-sm font-medium hover:bg-slate-50 transition">
            Next →
          </button>
        </div>
        @endif

      </div>
    </main>
  </div>
</body>
</html>
