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
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="/platforms">
          <span class="text-sm font-medium">Platforms</span>
        </a>
        <a class="flex items-center gap-3 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 transition" href="/item-tracking">
          <span class="text-sm font-medium">History</span>
        </a>
      </nav>

      <div class="mt-auto p-4">
        <div class="rounded-2xl bg-slate-50 border p-4">
          <div class="text-xs text-slate-500">Last Updated (SGT)</div>
          <div class="text-xs font-semibold">{{ $lastSync ?? '—' }}</div>
          <button onclick="window.location.href='/dashboard'" class="mt-3 w-full rounded-xl bg-slate-900 text-white py-2 text-sm font-medium hover:opacity-90 transition">
            Back to Dashboard
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
            <a href="/dashboard" class="text-slate-500 hover:text-slate-700 text-sm flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
              Back to Overview
            </a>
          </div>
        </div>
      </header>

      <div class="px-4 md:px-8 py-6 max-w-2xl">

        <!-- Store Status Card -->
        <div class="bg-white border rounded-3xl shadow-sm p-8 mb-6">

          <!-- Header -->
          <div class="flex items-start justify-between mb-6">
            <div class="flex-1">
              <h1 class="text-3xl font-bold text-slate-900 mb-1">{{ $store['brand'] ?? 'Brand' }} @ {{ $store['name'] ?? 'Store' }}</h1>
              <div class="flex items-center gap-2 text-sm text-slate-600">
                <span class="font-medium">Live Status</span>
                <span>•</span>
                <span>Auto-synced</span>
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>

            @if($store['offline_count'] == 0)
              <div class="flex items-center gap-2 px-4 py-2 bg-emerald-50 border border-emerald-200 rounded-full">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="text-sm font-semibold text-emerald-700">All Platforms <span class="text-emerald-600">Online</span></span>
              </div>
            @else
              <div class="flex items-center gap-2 px-4 py-2 bg-red-50 border border-red-200 rounded-full">
                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span class="text-sm font-semibold text-red-700">{{ $store['offline_count'] }} Platform{{ $store['offline_count'] > 1 ? 's' : '' }} <span class="text-red-600">Offline</span></span>
              </div>
            @endif
          </div>

          <div class="text-sm text-slate-500 mb-6">
            Refreshed {{ $lastSyncAgo ?? 'Unknown' }}
          </div>

          <!-- Platform Status Cards -->
          <div class="space-y-4 mb-6">

            <!-- Grab -->
            @php
              $grabStatus = $store['platforms']['grab'] ?? ['online' => null];
              $grabOnline = $grabStatus['online'];
              $grabItemsSynced = $grabStatus['items_synced'] ?? 0;
              $grabLastChecked = $grabStatus['last_checked'] ?? null;
            @endphp
            <div class="border-l-4 {{ $grabOnline ? 'border-green-500' : ($grabOnline === false ? 'border-red-500' : 'border-gray-300') }} bg-white border border-slate-200 rounded-xl p-5">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 flex-1">
                  <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center">
                    <span class="text-2xl">🟢</span>
                  </div>
                  <div class="flex-1">
                    <div class="font-bold text-lg text-slate-900">Grab</div>
                    <div class="text-sm {{ $grabOnline ? 'text-green-700' : ($grabOnline === false ? 'text-red-700' : 'text-gray-500') }}">
                      @if($grabOnline === true)
                        Online • Items Active
                      @elseif($grabOnline === false)
                        Online • Partial Items
                      @else
                        Status Unknown
                      @endif
                    </div>
                  </div>
                </div>

                <div class="text-right">
                  <div class="text-xs text-slate-500 mb-1 flex items-center gap-1 justify-end">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Last check • {{ $grabLastChecked ? \Carbon\Carbon::parse($grabLastChecked)->diffForHumans() : 'Never' }}
                  </div>
                  <div class="px-3 py-1 rounded-full text-xs font-semibold {{ $grabOnline ? 'bg-green-100 text-green-700' : ($grabOnline === false ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                    @if($grabOnline === true)
                      All Items Active
                    @elseif($grabOnline === false)
                      {{ $store['items_off'] ?? 0 }} Items OFF
                    @else
                      Unknown
                    @endif
                  </div>
                </div>
              </div>
            </div>

            <!-- FoodPanda -->
            @php
              $foodpandaStatus = $store['platforms']['foodpanda'] ?? ['online' => null];
              $foodpandaOnline = $foodpandaStatus['online'];
              $foodpandaItemsSynced = $foodpandaStatus['items_synced'] ?? 0;
              $foodpandaLastChecked = $foodpandaStatus['last_checked'] ?? null;
            @endphp
            <div class="border-l-4 {{ $foodpandaOnline ? 'border-pink-500' : ($foodpandaOnline === false ? 'border-red-500' : 'border-gray-300') }} bg-white border border-slate-200 rounded-xl p-5">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 flex-1">
                  <div class="w-12 h-12 rounded-full bg-pink-100 flex items-center justify-center">
                    <span class="text-2xl">🐼</span>
                  </div>
                  <div class="flex-1">
                    <div class="font-bold text-lg text-slate-900">foodpanda</div>
                    <div class="text-sm {{ $foodpandaOnline ? 'text-pink-700' : ($foodpandaOnline === false ? 'text-red-700' : 'text-gray-500') }}">
                      @if($foodpandaOnline === true)
                        Online • Fully Active
                      @elseif($foodpandaOnline === false)
                        Online • Partial Items
                      @else
                        Status Unknown
                      @endif
                    </div>
                  </div>
                </div>

                <div class="text-right">
                  <div class="text-xs text-slate-500 mb-1 flex items-center gap-1 justify-end">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Last check • {{ $foodpandaLastChecked ? \Carbon\Carbon::parse($foodpandaLastChecked)->diffForHumans() : 'Never' }}
                  </div>
                  <div class="px-3 py-1 rounded-full text-xs font-semibold {{ $foodpandaOnline ? 'bg-pink-100 text-pink-700' : ($foodpandaOnline === false ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                    @if($foodpandaOnline === true)
                      All Items Active
                    @elseif($foodpandaOnline === false)
                      {{ $store['items_off'] ?? 0 }} Items OFF
                    @else
                      Unknown
                    @endif
                  </div>
                </div>
              </div>
            </div>

            <!-- Deliveroo -->
            @php
              $deliverooStatus = $store['platforms']['deliveroo'] ?? ['online' => null];
              $deliverooOnline = $deliverooStatus['online'];
              $deliverooItemsSynced = $deliverooStatus['items_synced'] ?? 0;
              $deliverooLastChecked = $deliverooStatus['last_checked'] ?? null;
            @endphp
            <div class="border-l-4 {{ $deliverooOnline ? 'border-cyan-500' : ($deliverooOnline === false ? 'border-red-500' : 'border-gray-300') }} bg-white border border-slate-200 rounded-xl p-5">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 flex-1">
                  <div class="w-12 h-12 rounded-full bg-cyan-100 flex items-center justify-center">
                    <span class="text-2xl">🚴</span>
                  </div>
                  <div class="flex-1">
                    <div class="font-bold text-lg text-slate-900">Deliveroo</div>
                    <div class="text-sm {{ $deliverooOnline ? 'text-cyan-700' : ($deliverooOnline === false ? 'text-red-700' : 'text-gray-500') }}">
                      @if($deliverooOnline === true)
                        Online • Fully Active
                      @elseif($deliverooOnline === false)
                        Online • Partial Items
                      @else
                        Status Unknown
                      @endif
                    </div>
                  </div>
                </div>

                <div class="text-right">
                  <div class="text-xs text-slate-500 mb-1 flex items-center gap-1 justify-end">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Last check • {{ $deliverooLastChecked ? \Carbon\Carbon::parse($deliverooLastChecked)->diffForHumans() : 'Never' }}
                  </div>
                  <div class="px-3 py-1 rounded-full text-xs font-semibold {{ $deliverooOnline ? 'bg-cyan-100 text-cyan-700' : ($deliverooOnline === false ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                    @if($deliverooOnline === true)
                      All Items Active
                    @elseif($deliverooOnline === false)
                      {{ $store['items_off'] ?? 0 }} Items OFF
                    @else
                      Unknown
                    @endif
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- Action Buttons -->
          <div class="grid grid-cols-2 gap-3 mb-6">
            <button onclick="window.location.href='/items'" class="flex items-center justify-center gap-2 px-4 py-3 border-2 border-slate-200 rounded-xl hover:bg-slate-50 transition font-medium text-slate-700">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
              </svg>
              View Items
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </button>

            <button onclick="window.location.href='/item-tracking'" class="flex items-center justify-center gap-2 px-4 py-3 border-2 border-slate-200 rounded-xl hover:bg-slate-50 transition font-medium text-slate-700">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              View Logs
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
              </svg>
            </button>
          </div>

          <!-- Source Footer -->
          <div class="text-center text-sm text-slate-500 pt-4 border-t border-slate-100">
            Source: <span class="font-medium">API + Live Scan</span>
          </div>

        </div>

      </div>
    </main>
  </div>
</body>
</html>
