<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>HawkerOps Dashboard</title>

  {{-- If you already use Vite + Tailwind --}}
  {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}

  {{-- If you DON'T use Vite yet, comment the line above and use this CDN temporarily --}}
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
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl bg-slate-900 text-white shadow-sm" href="/dashboard">
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
            <h1 class="text-xl font-semibold">Overview</h1>
            <p class="text-sm text-slate-500">Monitor items & add-ons disabled during peak hours</p>
          </div>

          <div class="flex items-center gap-2">
            <div class="hidden sm:flex items-center bg-slate-100 rounded-xl px-3 py-2">
              <input class="bg-transparent outline-none text-sm w-64" placeholder="Search store / item…" />
            </div>
            <button class="rounded-xl border bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50 transition">
              Export
            </button>
            <button class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:opacity-90 transition">
              Reload
            </button>
          </div>
        </div>
      </header>

      <div class="px-4 md:px-8 py-6 space-y-6">

        <!-- KPI cards -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-white border rounded-2xl p-5 shadow-sm hover:shadow-md transition">
            <div class="text-sm text-slate-500">Stores Online</div>
            <div class="mt-2 text-3xl font-semibold">{{ $kpis['stores_online'] ?? 44 }}</div>
          </div>

          <div class="bg-white border rounded-2xl p-5 shadow-sm hover:shadow-md transition">
            <div class="text-sm text-slate-500">Items OFF</div>
            <div class="mt-2 text-3xl font-semibold">{{ $kpis['items_off'] ?? 0 }}</div>
          </div>

          <div class="bg-white border rounded-2xl p-5 shadow-sm hover:shadow-md transition">
            <div class="text-sm text-slate-500">Add-ons OFF</div>
            <div class="mt-2 text-3xl font-semibold">{{ $kpis['addons_off'] ?? 0 }}</div>
          </div>

          <div class="bg-white border rounded-2xl p-5 shadow-sm hover:shadow-md transition">
            <div class="text-sm text-slate-500">Active Alerts</div>
            <div class="mt-2 text-3xl font-semibold">{{ $kpis['alerts'] ?? 0 }}</div>
          </div>
        </section>

        <!-- Store cards -->
        <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
          @foreach(($stores ?? []) as $s)
            <div class="bg-white border rounded-2xl shadow-sm hover:shadow-md transition overflow-hidden">
              <div class="p-5">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <div class="text-sm text-slate-500">{{ $s['brand'] ?? 'OK Chicken Rice' }}</div>
                    <div class="text-lg font-semibold">{{ $s['store'] ?? 'AMK' }}</div>
                    <div class="text-xs text-slate-500 mt-1">shop_id: {{ $s['shop_id'] ?? '-' }}</div>
                  </div>
                  <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">
                    {{ $s['status'] ?? 'OPERATING' }}
                  </span>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-2">
                  <div class="rounded-xl border bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">Items OFF</div>
                    <div class="text-lg font-semibold">{{ $s['items_off'] ?? 0 }}</div>
                  </div>
                  <div class="rounded-xl border bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">Add-ons OFF</div>
                    <div class="text-lg font-semibold">{{ $s['addons_off'] ?? 0 }}</div>
                  </div>
                  <div class="rounded-xl border bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">Alerts</div>
                    <div class="text-lg font-semibold">{{ $s['alerts'] ?? 0 }}</div>
                  </div>
                </div>

                <div class="mt-5 flex items-center justify-between">
                  <div class="text-xs text-slate-500">Last change: {{ $s['last_change'] ?? '—' }}</div>
                  <button class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-medium hover:opacity-90 transition">
                    View details
                  </button>
                </div>
              </div>
              <div class="h-1 bg-slate-900"></div>
            </div>
          @endforeach

          @if(empty($stores))
            <div class="bg-white border rounded-2xl p-6 text-sm text-slate-600">
              No stores data yet. Next step: load from DB (shops list) and compute OFF items/modifiers.
            </div>
          @endif
        </section>

      </div>
    </main>
  </div>
</body>
</html>
