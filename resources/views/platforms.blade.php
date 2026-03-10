@extends('layout')

@section('title', 'Platform Status - HawkerOps')

@section('page-title', 'Platform Status')
@section('page-description', 'Monitor Grab, FoodPanda & Deliveroo availability')

@section('top-actions')
  <div class="flex items-center gap-2">
    <div class="hidden sm:block text-right mr-1">
      <div class="text-xs text-slate-500 dark:text-slate-400">Last Updated</div>
      <div class="text-sm font-semibold text-slate-900 dark:text-slate-100 break-words leading-tight">{{ $lastScrape ?? '—' }}</div>
    </div>
    <button id="hideTestBtn" onclick="toggleTestFilter()"
      class="hidden md:inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 text-xs font-semibold transition">
      <span class="md:hidden">Filter</span><span class="hidden md:inline">Hide Test/Demo</span>
    </button>
    <button onclick="showSyncInfo()" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 transition" title="Sync Information">
      <svg class="w-5 h-5 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
    </button>
    <button onclick="triggerSync()"
      class="md:hidden inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-900 dark:bg-slate-700 text-white text-xs font-semibold hover:opacity-90 transition">
      <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
      </svg>
      <span>⚡ Sync</span>
    </button>
  </div>
@endsection

@section('content')
  <!-- Summary KPIs -->
  <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 dark:text-slate-400">Total Platforms</div>
      <div class="mt-2 text-3xl font-semibold">{{ $stats['total'] ?? 0 }}</div>
    </div>

    <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-green-700 dark:text-green-400 font-medium">Online</div>
      <div class="mt-2 text-3xl font-semibold text-green-900 dark:text-green-100">{{ $stats['online'] ?? 0 }}</div>
      <div class="mt-1 text-xs text-green-600 dark:text-green-400">
        {{ $stats['percentage'] ?? 0 }}% uptime
      </div>
    </div>

    <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-red-700 dark:text-red-400 font-medium">Offline</div>
      <div class="mt-2 text-3xl font-semibold text-red-900 dark:text-red-100">{{ $stats['offline'] ?? 0 }}</div>
    </div>

    <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-blue-700 dark:text-blue-400 font-medium">Shops Monitored</div>
      <div class="mt-2 text-3xl font-semibold text-blue-900 dark:text-blue-100">{{ count($shops ?? []) }}</div>
    </div>
  </section>

  <!-- Platform Stats -->
  <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
    @foreach(['grab' => 'Grab', 'foodpanda' => 'FoodPanda', 'deliveroo' => 'Deliveroo'] as $key => $name)
      @php $stat = $platformStats[$key] ?? ['total' => 0, 'online' => 0, 'offline' => 0, 'percentage' => 0]; @endphp
      <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-lg">{{ $name }}</h3>
          <span class="text-2xl font-bold {{ $stat['percentage'] > 80 ? 'text-green-600 dark:text-green-400' : ($stat['percentage'] > 50 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
            {{ $stat['percentage'] }}%
          </span>
        </div>
        <div class="space-y-2">
          <div class="flex justify-between text-sm">
            <span class="text-slate-600 dark:text-slate-400">Online:</span>
            <span class="font-semibold text-green-600 dark:text-green-400">{{ $stat['online'] }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-slate-600 dark:text-slate-400">Offline:</span>
            <span class="font-semibold text-red-600 dark:text-red-400">{{ $stat['offline'] }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-slate-600 dark:text-slate-400">Total:</span>
            <span class="font-semibold">{{ $stat['total'] }}</span>
          </div>
        </div>
        <div class="mt-4 w-full bg-slate-200 dark:bg-slate-700 rounded-full h-2">
          <div class="bg-green-500 h-2 rounded-full" style="width: {{ $stat['percentage'] }}%"></div>
        </div>
      </div>
    @endforeach
  </section>

  <!-- Shop Platform Status Table -->
  <section class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden">
    <div class="p-5 border-b dark:border-slate-700 flex items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold">Shop Platform Status</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Detailed status for each store across all platforms</p>
      </div>
      <button id="hideTestBtnMobile" onclick="toggleTestFilter()"
        class="md:hidden flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-300 text-xs font-semibold transition">
        Hide Test/Demo
      </button>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full table-fixed">
        <thead class="bg-slate-50 dark:bg-slate-900 border-b dark:border-slate-700">
          <tr>
            <th class="w-2/5 md:w-1/3 px-3 md:px-5 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Shop</th>
            <th class="w-1/5 md:w-1/6 px-2 md:px-5 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Grab</th>
            <th class="w-1/5 md:w-1/6 px-2 md:px-5 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              <span class="md:hidden">Panda</span><span class="hidden md:inline">FoodPanda</span>
            </th>
            <th class="w-1/5 md:w-1/6 px-2 md:px-5 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              <span class="md:hidden">Delivroo</span><span class="hidden md:inline">Deliveroo</span>
            </th>
            <th class="hidden md:table-cell md:w-1/6 px-5 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
          @forelse($shops as $shop)
            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700 transition" data-name="{{ strtolower($shop['shop_name']) }}">
              <td class="px-3 md:px-5 py-3 md:py-4">
                <div class="font-medium text-xs md:text-sm text-slate-900 dark:text-slate-100 leading-tight">{{ $shop['shop_name'] }}</div>
                <div class="text-[10px] md:text-xs text-slate-500 dark:text-slate-400">{{ $shop['brand'] }}</div>
              </td>

              @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                <td class="px-2 md:px-5 py-3 md:py-4 text-center">
                  @if(isset($shop['platforms'][$platform]))
                    @php $p = $shop['platforms'][$platform]; @endphp
                    <div class="inline-flex flex-col items-center gap-0.5">
                      <span class="inline-flex items-center px-2 py-1 rounded-lg text-[10px] md:text-xs font-medium {{ $p['is_online'] ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-700' : 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-700' }}">
                        <span class="md:hidden">{{ $p['is_online'] ? 'ON' : 'OFF' }}</span>
                        <span class="hidden md:inline">{{ $p['is_online'] ? 'ONLINE' : 'OFFLINE' }}</span>
                      </span>
                      <span class="text-[9px] text-slate-400 dark:text-slate-500 hidden md:block">{{ $p['last_checked'] }}</span>
                    </div>
                  @else
                    <span class="text-xs text-slate-400 dark:text-slate-500">No data</span>
                  @endif
                </td>
              @endforeach

              <td class="hidden md:table-cell px-5 py-4 text-right">
                @php
                  $onlineCount = 0;
                  foreach(['grab', 'foodpanda', 'deliveroo'] as $platform) {
                    if(isset($shop['platforms'][$platform]) && $shop['platforms'][$platform]['is_online']) {
                      $onlineCount++;
                    }
                  }
                @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $onlineCount === 3 ? 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-300' : ($onlineCount > 0 ? 'bg-yellow-100 dark:bg-yellow-900/40 text-yellow-800 dark:text-yellow-300' : 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300') }}">
                  {{ $onlineCount }}/3 platforms
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="px-5 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                No platform data available. Run a scrape to populate data.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
@endsection

@section('extra-scripts')
<script>
  let hideTestActive = false;

  function toggleTestFilter() {
    hideTestActive = !hideTestActive;
    const rows = document.querySelectorAll('tbody tr[data-name]');
    rows.forEach(row => {
      const name = row.getAttribute('data-name') || '';
      const isTest = name.includes('testing') || name.includes('test outlet') || name.includes('(test)') || name.includes('demo');
      if (isTest) row.style.display = hideTestActive ? 'none' : '';
    });
    const label = hideTestActive ? 'Show Test/Demo' : 'Hide Test/Demo';
    ['hideTestBtn', 'hideTestBtnMobile'].forEach(id => {
      const btn = document.getElementById(id);
      if (!btn) return;
      // Update label text (last text node / span)
      const span = btn.querySelector('span.hidden.md\\:inline');
      if (span) span.textContent = label; else btn.textContent = label;
      // Toggle active styling
      btn.classList.toggle('bg-slate-900', hideTestActive);
      btn.classList.toggle('text-white', hideTestActive);
      btn.classList.toggle('bg-slate-100', !hideTestActive);
      btn.classList.toggle('dark:bg-slate-700', !hideTestActive);
      btn.classList.toggle('text-slate-700', !hideTestActive);
      btn.classList.toggle('dark:text-slate-300', !hideTestActive);
    });
  }

  function showSyncInfo() {
    const lastScrape = '{{ $lastScrape ?? "Never" }}';
    const totalPlatforms = {{ $stats['total'] ?? 0 }};
    const onlinePlatforms = {{ $stats['online'] ?? 0 }};
    const offlinePlatforms = {{ $stats['offline'] ?? 0 }};
    const grabOnline = {{ $platformStats['grab']['online'] ?? 0 }};
    const grabTotal = {{ $platformStats['grab']['total'] ?? 0 }};
    const foodpandaOnline = {{ $platformStats['foodpanda']['online'] ?? 0 }};
    const foodpandaTotal = {{ $platformStats['foodpanda']['total'] ?? 0 }};
    const deliverooOnline = {{ $platformStats['deliveroo']['online'] ?? 0 }};
    const deliverooTotal = {{ $platformStats['deliveroo']['total'] ?? 0 }};
    const shopsMonitored = {{ count($shops ?? []) }};

    const info = `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 PLATFORM SYNC INFORMATION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⏰ Last Updated (SGT):
   ${lastScrape}

📈 Overall Statistics:
   • Total Platforms: ${totalPlatforms}
   • Online: ${onlinePlatforms} (${(onlinePlatforms/totalPlatforms*100).toFixed(1)}%)
   • Offline: ${offlinePlatforms}
   • Shops Monitored: ${shopsMonitored}

🟢 Platform Breakdown:

   Grab:
   └─ ${grabOnline}/${grabTotal} online (${(grabOnline/grabTotal*100).toFixed(1)}%)

   FoodPanda:
   └─ ${foodpandaOnline}/${foodpandaTotal} online (${(foodpandaOnline/foodpandaTotal*100).toFixed(1)}%)

   Deliveroo:
   └─ ${deliverooOnline}/${deliverooTotal} online (${(deliverooOnline/deliverooTotal*100).toFixed(1)}%)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`;

    alert(info);
  }
</script>
@endsection
