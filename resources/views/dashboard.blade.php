@extends('layout')

@section('title', 'HawkerOps Dashboard')
@section('page-title', 'Overview')
@section('page-description', 'Hybrid monitoring: RestoSuite API + Platform scraping')

@section('extra-head')
<style>
  .filter-btn.active {
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
    transform: translateY(-1px);
  }
  .filter-btn:hover {
    transform: translateY(-1px);
  }
</style>
@endsection

@section('top-actions')
<div class="hidden sm:flex items-center bg-slate-100 dark:bg-slate-800 rounded-xl px-3 py-2">
  <input id="searchInput" class="bg-transparent outline-none text-sm w-64 dark:text-slate-100 dark:placeholder-slate-400" placeholder="Search store / item…" onkeyup="searchCards()" />
</div>
<a href="/dashboard/export" class="rounded-xl bg-green-600 hover:bg-green-700 text-white px-4 py-2 text-sm font-semibold transition shadow-sm flex items-center gap-2">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
  </svg>
  Export CSV
</a>
@endsection

@section('content')

  <!-- KPI cards -->
  <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm hover:shadow-md transition">
      <div class="text-sm text-slate-500 dark:text-slate-400">Stores Online</div>
      <div class="mt-2 text-3xl font-semibold">{{ $kpis['stores_online'] ?? 0 }}</div>
    </div>

    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm hover:shadow-md transition">
      <div class="text-sm text-slate-500 dark:text-slate-400">Items OFF</div>
      <div class="mt-2 text-3xl font-semibold">{{ $kpis['items_off'] ?? 0 }}</div>
    </div>

    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm hover:shadow-md transition">
      <div class="text-sm text-slate-500 dark:text-slate-400">Active Alerts</div>
      <div class="mt-2 text-3xl font-semibold">{{ $kpis['alerts'] ?? 0 }}</div>
    </div>

    {{-- HYBRID: Platform Status KPI --}}
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/30 dark:to-indigo-900/30 border border-blue-200 dark:border-blue-800 rounded-2xl p-5 shadow-sm hover:shadow-md transition">
      <div class="text-sm text-blue-700 dark:text-blue-400 font-medium">Platforms Status</div>
      <div class="mt-2 flex items-baseline gap-2">
        <span class="text-3xl font-semibold text-blue-900 dark:text-blue-100">{{ $kpis['platforms_online'] ?? 0 }}</span>
        <span class="text-sm text-blue-600 dark:text-blue-400">/ {{ $kpis['platforms_total'] ?? 0 }}</span>
      </div>
      <div class="mt-1 text-xs text-blue-600 dark:text-blue-400">
        {{ $kpis['platforms_offline'] ?? 0 }} offline
      </div>
    </div>
  </section>

  <!-- Filter Buttons -->
  <section class="flex flex-wrap gap-3">
    <button onclick="filterStores('all')" class="filter-btn active px-4 py-2 bg-white dark:bg-slate-800 border-2 border-slate-300 dark:border-slate-600 rounded-lg text-sm font-semibold text-slate-700 dark:text-slate-300 shadow-sm hover:shadow-md transition" id="filter-all">
      All Stores
    </button>
    <button onclick="filterStores('all_online')" class="filter-btn px-4 py-2 bg-green-50 dark:bg-green-900/30 border-2 border-green-200 dark:border-green-700 rounded-lg text-sm font-semibold text-green-700 dark:text-green-400 shadow-sm hover:shadow-md transition" id="filter-all_online">
      ✓ All Platforms Online
    </button>

    <!-- Partial Offline Dropdown -->
    <div class="relative inline-block" id="partial-dropdown">
      <button onclick="togglePartialDropdown()" class="filter-btn px-4 py-2 bg-amber-50 dark:bg-amber-900/30 border-2 border-amber-300 dark:border-amber-700 rounded-lg text-sm font-semibold text-amber-700 dark:text-amber-400 shadow-sm hover:shadow-md transition flex items-center gap-2" id="filter-partial">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <span id="partial-label">Partial Offline</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
      </button>
      <div id="partial-menu" class="hidden absolute top-full mt-2 w-56 bg-white dark:bg-slate-800 border-2 border-amber-200 dark:border-amber-700 rounded-lg shadow-xl z-50">
        <button onclick="filterStores('1_offline'); togglePartialDropdown();" class="w-full text-left px-4 py-3 hover:bg-amber-50 dark:hover:bg-amber-900/30 transition text-sm font-medium text-slate-700 dark:text-slate-300 border-b border-slate-100 dark:border-slate-700">
          <span class="text-amber-600 dark:text-amber-400 font-semibold">1/3 Offline</span> - 2 Platforms Online
        </button>
        <button onclick="filterStores('2_offline'); togglePartialDropdown();" class="w-full text-left px-4 py-3 hover:bg-amber-50 dark:hover:bg-amber-900/30 transition text-sm font-medium text-slate-700 dark:text-slate-300">
          <span class="text-amber-700 dark:text-amber-400 font-semibold">2/3 Offline</span> - 1 Platform Online
        </button>
      </div>
    </div>

    <button onclick="filterStores('all_offline')" class="filter-btn px-4 py-2 bg-red-50 dark:bg-red-900/30 border-2 border-red-200 dark:border-red-700 rounded-lg text-sm font-semibold text-red-700 dark:text-red-400 shadow-sm hover:shadow-md transition" id="filter-all_offline">
      ✕ All Platforms Offline
    </button>
  </section>

  <!-- Store cards -->
  <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 2xl:grid-cols-3 gap-6">
    @foreach(($stores ?? []) as $s)
      @php
        $offlineCount = 0;
        if (isset($s['platforms'])) {
          foreach($s['platforms'] as $platform => $status) {
            if (!$status['online'] || $status['online'] === false || $status['online'] === 0) {
              $offlineCount++;
            }
          }
        }
        $platformConfig = [
          'grab'      => ['name' => 'Grab',      'gradient' => 'from-green-500 to-emerald-600'],
          'foodpanda' => ['name' => 'foodpanda',  'gradient' => 'from-pink-500 to-rose-600'],
          'deliveroo' => ['name' => 'Deliveroo',  'gradient' => 'from-cyan-500 to-blue-600'],
        ];
      @endphp

      <div class="store-card bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm hover:shadow-xl hover:border-slate-300 dark:hover:border-slate-600 transition-all duration-300 flex flex-col h-full min-h-[420px]"
           data-status="{{ $s['overall_status'] ?? 'mixed' }}"
           data-offline-count="{{ $offlineCount }}"
           data-store-name="{{ strtolower($s['store'] ?? '') }}">

        <!-- Card Header -->
        <div class="bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-700 dark:to-slate-800 border-b-2 border-slate-200 dark:border-slate-600 px-5 min-h-[6rem] flex items-center rounded-t-2xl">
          <div class="flex items-center justify-between w-full">
            <div class="flex-1">
              <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ $s['store'] ?? 'Store' }}</h3>
              @if(isset($s['brand']) && $s['brand'])
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">{{ $s['brand'] }}</p>
              @endif
            </div>

            @if($offlineCount == 0)
              <div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-500 rounded-lg shadow-md">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="text-xs font-bold text-white whitespace-nowrap">All Platforms Online</span>
              </div>
            @elseif($offlineCount == 3)
              <div class="flex items-center gap-2 px-3 py-1.5 bg-red-500 rounded-lg shadow-md">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span class="text-xs font-bold text-white whitespace-nowrap">All Platforms Offline</span>
              </div>
            @else
              <div class="flex items-center gap-2 px-3 py-1.5 bg-amber-500 rounded-lg shadow-md">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                <span class="text-xs font-bold text-white whitespace-nowrap">{{ $offlineCount }}/3 Offline</span>
              </div>
            @endif
          </div>
        </div>

        <!-- Card Body -->
        <div class="p-5 flex-grow flex flex-col justify-between">
          @if(isset($s['platforms']))
          <div class="space-y-3 mb-4 min-h-[240px]">
            @php $platformOrder = ['grab', 'foodpanda', 'deliveroo']; $sortedPlatforms = []; foreach ($platformOrder as $_pk) { if (isset($s['platforms'][$_pk])) $sortedPlatforms[$_pk] = $s['platforms'][$_pk]; } foreach ($s['platforms'] as $_pk => $_pv) { if (!isset($sortedPlatforms[$_pk])) $sortedPlatforms[$_pk] = $_pv; } @endphp
            @foreach($sortedPlatforms as $platform => $status)
              @if($status['online'] !== null)
                @php
                  $config = $platformConfig[$platform] ?? $platformConfig['grab'];
                  $isOnline = $status['online'] ?? false;
                  $lastChecked = $status['last_checked'] ?? null;
                  $lastCheckedText = $lastChecked ? \Carbon\Carbon::parse($lastChecked)->diffForHumans() : 'Never';
                  $offlineItems = $status['offline_items'] ?? 0;
                @endphp

                <div class="group relative {{ $isOnline ? 'bg-white dark:bg-slate-900' : 'bg-slate-50 dark:bg-slate-700' }} border-2 {{ $isOnline ? 'border-slate-200 dark:border-slate-600' : 'border-slate-300 dark:border-slate-500' }} rounded-xl p-3 hover:shadow-md transition-all">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 flex-1">
                      <div class="w-10 h-10 rounded-lg {{ $isOnline ? 'bg-slate-800 dark:bg-slate-600' : 'bg-slate-600 dark:bg-slate-500' }} flex items-center justify-center text-white font-bold text-sm shadow-sm">
                        {{ strtoupper(substr($config['name'], 0, 1)) }}
                      </div>
                      <div class="flex-1">
                        <div class="font-bold text-sm text-slate-900 dark:text-slate-100">{{ $config['name'] }}</div>
                        <div class="flex items-center gap-2 mt-0.5">
                          <span class="text-[10px] {{ $isOnline ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} font-semibold uppercase">
                            {{ $isOnline ? 'Online' : 'OFFLINE' }}
                          </span>
                          <span class="text-[10px] text-slate-400 dark:text-slate-500">• {{ $lastCheckedText }}</span>
                        </div>
                      </div>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                      @if($offlineItems > 0)
                        <div class="px-3 py-1.5 bg-slate-800 dark:bg-slate-600 rounded-lg shadow-sm">
                          <div class="text-xs font-bold text-white">{{ $offlineItems }}</div>
                        </div>
                      @else
                        <div class="px-3 py-1.5 bg-slate-100 dark:bg-slate-700 rounded-lg border border-slate-200 dark:border-slate-600">
                          <div class="text-xs font-bold text-slate-400 dark:text-slate-500">0</div>
                        </div>
                      @endif
                    </div>
                  </div>
                </div>
              @endif
            @endforeach
          </div>
          @endif

          <!-- Action Buttons -->
          <div class="flex gap-3">
            <a href="/store/{{ $s['shop_id'] }}/items" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-slate-800 to-slate-900 hover:from-slate-700 hover:to-slate-800 text-white rounded-xl font-semibold text-sm shadow-md hover:shadow-lg transition-all">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
              <span>View Items</span>
            </a>
            <a href="/store/{{ $s['shop_id'] }}/logs" class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-white dark:bg-slate-700 hover:bg-slate-50 dark:hover:bg-slate-600 border-2 border-slate-200 dark:border-slate-600 hover:border-slate-300 text-slate-700 dark:text-slate-300 rounded-xl font-semibold text-sm shadow-sm hover:shadow-md transition-all">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
              <span>View Logs</span>
            </a>
          </div>
        </div>
      </div>
    @endforeach

    @if(empty($stores))
      <div class="col-span-full bg-white dark:bg-slate-800 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-12 text-center">
        <div class="text-slate-400 mb-3">
          <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
          </svg>
        </div>
        <div class="text-lg font-semibold text-slate-700 dark:text-slate-300 mb-2">No Stores Data Yet</div>
        <div class="text-sm text-slate-500 dark:text-slate-400">
          Run sync to load store data from RestoSuite API and platform scraping
        </div>
      </div>
    @endif
  </section>

@endsection

@section('extra-scripts')
<script>
  function togglePartialDropdown() {
    const menu = document.getElementById('partial-menu');
    menu.classList.toggle('hidden');
  }

  document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('partial-dropdown');
    const menu = document.getElementById('partial-menu');
    if (dropdown && !dropdown.contains(event.target)) {
      menu.classList.add('hidden');
    }
  });

  function filterStores(status) {
    const allCards = document.querySelectorAll('.store-card');
    const allButtons = document.querySelectorAll('.filter-btn');
    const partialLabel = document.getElementById('partial-label');

    allButtons.forEach(btn => btn.classList.remove('active'));

    if (status === '1_offline') {
      document.getElementById('filter-partial').classList.add('active');
      partialLabel.textContent = '1/3 Offline';
    } else if (status === '2_offline') {
      document.getElementById('filter-partial').classList.add('active');
      partialLabel.textContent = '2/3 Offline';
    } else {
      document.getElementById('filter-' + status)?.classList.add('active');
      partialLabel.textContent = 'Partial Offline';
    }

    allCards.forEach(card => {
      const cardStatus = card.getAttribute('data-status');
      const offlineCount = parseInt(card.getAttribute('data-offline-count') || '0');
      let shouldShow = false;

      if (status === 'all') shouldShow = true;
      else if (status === 'all_online') shouldShow = cardStatus === 'all_online';
      else if (status === 'all_offline') shouldShow = cardStatus === 'all_offline';
      else if (status === '1_offline') shouldShow = offlineCount === 1;
      else if (status === '2_offline') shouldShow = offlineCount === 2;
      else shouldShow = cardStatus === status;

      card.style.display = shouldShow ? 'block' : 'none';
    });
  }

  function searchCards() {
    const filter = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.store-card').forEach(card => {
      const name = card.getAttribute('data-store-name') || '';
      card.style.display = name.includes(filter) ? 'block' : 'none';
    });
  }
</script>
@endsection
