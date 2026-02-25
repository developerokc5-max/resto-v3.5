@extends('layout')

@section('title', 'Store Comparison - HawkerOps')
@section('page-title', 'Store Comparison')
@section('page-description', 'Compare performance and platform status across all stores')

@section('content')

{{-- ── Summary Cards ─────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">

  <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-3 md:p-5 border border-slate-100 dark:border-slate-700">
    <p class="text-xs text-slate-500 dark:text-slate-400 font-medium uppercase tracking-wide mb-1">Total Stores</p>
    <p class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $summary['total'] }}</p>
  </div>

  <div class="bg-green-50 dark:bg-green-900/30 rounded-2xl shadow-sm p-3 md:p-5 border border-green-200 dark:border-green-700">
    <p class="text-xs text-green-700 dark:text-green-400 font-medium uppercase tracking-wide mb-1">All Online</p>
    <p class="text-2xl md:text-3xl font-bold text-green-800 dark:text-green-100">{{ $summary['all_online'] }}</p>
  </div>

  <div class="bg-amber-50 dark:bg-amber-900/30 rounded-2xl shadow-sm p-3 md:p-5 border border-amber-200 dark:border-amber-700">
    <p class="text-xs text-amber-700 dark:text-amber-400 font-medium uppercase tracking-wide mb-1">Partial</p>
    <p class="text-2xl md:text-3xl font-bold text-amber-800 dark:text-amber-100">{{ $summary['partial'] }}</p>
  </div>

  <div class="bg-red-50 dark:bg-red-900/30 rounded-2xl shadow-sm p-3 md:p-5 border border-red-200 dark:border-red-700">
    <p class="text-xs text-red-700 dark:text-red-400 font-medium uppercase tracking-wide mb-1">All Offline</p>
    <p class="text-2xl md:text-3xl font-bold text-red-800 dark:text-red-100">{{ $summary['all_offline'] }}</p>
  </div>

  <div class="bg-blue-50 dark:bg-blue-900/30 rounded-2xl shadow-sm p-3 md:p-5 border border-blue-200 dark:border-blue-700">
    <p class="text-xs text-blue-700 dark:text-blue-400 font-medium uppercase tracking-wide mb-1">Total Items</p>
    <p class="text-2xl md:text-3xl font-bold text-blue-800 dark:text-blue-100">{{ number_format($summary['total_items']) }}</p>
  </div>

  <div class="bg-rose-50 dark:bg-rose-900/30 rounded-2xl shadow-sm p-3 md:p-5 border border-rose-200 dark:border-rose-700">
    <p class="text-xs text-rose-700 dark:text-rose-400 font-medium uppercase tracking-wide mb-1">Offline Items</p>
    <p class="text-2xl md:text-3xl font-bold text-rose-800 dark:text-rose-100">{{ number_format($summary['offline_items']) }}</p>
  </div>

</div>

{{-- ── Search + Filter Bar ───────────────────────────────────────────────── --}}
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-4 border border-slate-100 dark:border-slate-700 flex flex-col sm:flex-row gap-3 items-center justify-between">
  <input
    id="storeSearch"
    type="text"
    placeholder="Search store name…"
    class="w-full sm:w-72 border border-slate-200 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-400 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-300"
    oninput="filterStores()"
  >
  <div class="flex gap-2 flex-wrap">
    <button onclick="setFilter('all')"     class="filter-btn active px-4 py-2 rounded-xl text-sm font-medium">All</button>
    <button onclick="setFilter('green')"   class="filter-btn px-4 py-2 rounded-xl text-sm font-medium">✅ Online</button>
    <button onclick="setFilter('amber')"   class="filter-btn px-4 py-2 rounded-xl text-sm font-medium">⚠️ Partial</button>
    <button onclick="setFilter('red')"     class="filter-btn px-4 py-2 rounded-xl text-sm font-medium">🔴 Offline</button>
  </div>
  <p class="text-xs text-slate-400 dark:text-slate-500 whitespace-nowrap">Last sync: <span class="font-medium text-slate-600 dark:text-slate-400">{{ $lastSync }}</span></p>
</div>

{{-- ── Store Cards Grid ─────────────────────────────────────────────────── --}}
@if($allStoresData->count() > 0)
<div id="storeGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
  @foreach($allStoresData as $store)
  @php
    $color   = $store['status_color'];
    $border  = $color === 'green' ? 'border-green-200 dark:border-green-700' : ($color === 'amber' ? 'border-amber-200 dark:border-amber-700' : 'border-red-200 dark:border-red-700');
    $badge   = $color === 'green' ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400' : ($color === 'amber' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400' : 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400');
    $icon    = $color === 'green' ? '✅' : ($color === 'amber' ? '⚠️' : '❌');
    $availColor = $store['availability_pct'] >= 90 ? 'text-green-600 dark:text-green-400' : ($store['availability_pct'] >= 70 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400');
    $barColor   = $store['availability_pct'] >= 90 ? 'bg-green-500' : ($store['availability_pct'] >= 70 ? 'bg-amber-400' : 'bg-red-500');
  @endphp
  <div
    class="store-card bg-white dark:bg-slate-800 rounded-2xl shadow-sm border-2 {{ $border }} p-5 hover:shadow-md transition-shadow"
    data-color="{{ $color }}"
    data-name="{{ strtolower($store['shop_name']) }}"
  >
    {{-- Header --}}
    <div class="flex items-start justify-between mb-4">
      <div class="flex-1 min-w-0 pr-3">
        <h3 class="font-bold text-slate-900 dark:text-slate-100 text-sm leading-tight">{{ $store['shop_name'] }}</h3>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $store['last_checked'] === 'Never' ? 'Never checked' : 'Checked ' . $store['last_checked'] }}</p>
      </div>
      <span class="shrink-0 px-2 py-1 rounded-full text-xs font-bold {{ $badge }}">
        {{ $icon }} {{ $store['overall_status'] }}
      </span>
    </div>

    {{-- Platform Pills --}}
    <div class="flex gap-2 mb-4">
      @php
        $platforms = [
          'Grab'      => $store['grab_online'],
          'Foodpanda' => $store['foodpanda_online'],
          'Deliveroo' => $store['deliveroo_online'],
        ];
      @endphp
      @foreach($platforms as $platform => $online)
      <div class="flex-1 text-center py-1.5 rounded-lg text-xs font-bold {{ $online ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' }}">
        {{ $platform }}<br>
        <span class="font-normal">{{ $online ? 'ON' : 'OFF' }}</span>
      </div>
      @endforeach
    </div>

    {{-- Item Availability Bar --}}
    <div class="mb-3">
      <div class="flex justify-between text-xs mb-1">
        <span class="text-slate-500 dark:text-slate-400">Item Availability</span>
        <span class="font-bold {{ $availColor }}">{{ $store['availability_pct'] }}%</span>
      </div>
      <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
        <div class="{{ $barColor }} h-2 rounded-full transition-all" style="width: {{ $store['availability_pct'] }}%"></div>
      </div>
    </div>

    {{-- Footer Stats --}}
    <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-700 pt-3 mt-2">
      <span>Items: <span class="font-bold text-slate-700 dark:text-slate-300">{{ $store['online_items'] }}/{{ $store['total_items'] }}</span></span>
      @if($store['offline_items'] > 0)
        <span class="text-red-500 dark:text-red-400 font-semibold">{{ $store['offline_items'] }} offline</span>
      @else
        <span class="text-green-600 dark:text-green-400 font-semibold">All available</span>
      @endif
      <span>{{ $store['platforms_online'] }}/{{ $store['total_platforms'] }} platforms</span>
    </div>
  </div>
  @endforeach
</div>

{{-- No results message --}}
<div id="noResults" class="hidden text-center py-12 text-slate-400 dark:text-slate-500">
  <p class="text-lg font-medium">No stores match your search.</p>
  <p class="text-sm mt-1">Try a different filter or search term.</p>
</div>

@else
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm p-12 text-center text-slate-400 dark:text-slate-500">
  <p class="text-lg font-medium">No store data available yet.</p>
  <p class="text-sm mt-1">Run a scrape to populate store data.</p>
</div>
@endif

{{-- ── Summary Table (collapsible) ─────────────────────────────────────── --}}
<div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 overflow-hidden">
  <button
    onclick="toggleTable()"
    class="w-full flex items-center justify-between px-6 py-4 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
  >
    <span class="font-bold text-slate-900 dark:text-slate-100">Full Comparison Table</span>
    <span id="tableToggleIcon" class="text-slate-400 dark:text-slate-500 text-lg">▼</span>
  </button>

  <div id="comparisonTable" class="hidden overflow-x-auto border-t border-slate-100 dark:border-slate-700">
    <table class="w-full text-sm">
      <thead>
        <tr class="bg-slate-50 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-700">
          <th class="text-left px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Store</th>
          <th class="text-center px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Status</th>
          <th class="text-center px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Grab</th>
          <th class="text-center px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Foodpanda</th>
          <th class="text-center px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Deliveroo</th>
          <th class="text-center px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Items</th>
          <th class="text-center px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Offline</th>
          <th class="text-center px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Avail %</th>
          <th class="text-center px-4 py-3 font-semibold text-slate-700 dark:text-slate-300">Last Checked</th>
        </tr>
      </thead>
      <tbody>
        @foreach($allStoresData as $store)
        @php
          $rc = $store['status_color'];
          $rb = $rc === 'green' ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400' : ($rc === 'amber' ? 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400' : 'bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400');
          $ac = $store['availability_pct'] >= 90 ? 'text-green-600 dark:text-green-400 font-bold' : ($store['availability_pct'] >= 70 ? 'text-amber-600 dark:text-amber-400 font-bold' : 'text-red-600 dark:text-red-400 font-bold');
        @endphp
        <tr class="border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700">
          <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $store['shop_name'] }}</td>
          <td class="px-4 py-3 text-center">
            <span class="px-2 py-1 rounded-full text-xs font-bold {{ $rb }}">{{ $store['overall_status'] }}</span>
          </td>
          <td class="px-4 py-3 text-center">
            <span class="px-2 py-0.5 rounded text-xs font-bold {{ $store['grab_online'] ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' }}">
              {{ $store['grab_online'] ? 'ON' : 'OFF' }}
            </span>
          </td>
          <td class="px-4 py-3 text-center">
            <span class="px-2 py-0.5 rounded text-xs font-bold {{ $store['foodpanda_online'] ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' }}">
              {{ $store['foodpanda_online'] ? 'ON' : 'OFF' }}
            </span>
          </td>
          <td class="px-4 py-3 text-center">
            <span class="px-2 py-0.5 rounded text-xs font-bold {{ $store['deliveroo_online'] ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' }}">
              {{ $store['deliveroo_online'] ? 'ON' : 'OFF' }}
            </span>
          </td>
          <td class="px-4 py-3 text-center text-slate-700 dark:text-slate-300">{{ $store['total_items'] }}</td>
          <td class="px-4 py-3 text-center {{ $store['offline_items'] > 0 ? 'text-red-600 dark:text-red-400 font-bold' : 'text-green-600 dark:text-green-400' }}">
            {{ $store['offline_items'] }}
          </td>
          <td class="px-4 py-3 text-center {{ $ac }}">{{ $store['availability_pct'] }}%</td>
          <td class="px-4 py-3 text-center text-xs text-slate-500 dark:text-slate-400">{{ $store['last_checked'] }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<script>
let activeFilter = 'all';

function setFilter(color) {
  activeFilter = color;
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  event.target.classList.add('active');
  applyFilters();
}

function filterStores() { applyFilters(); }

function applyFilters() {
  const query = document.getElementById('storeSearch').value.toLowerCase().trim();
  const cards = document.querySelectorAll('.store-card');
  let visible = 0;
  cards.forEach(card => {
    const matchColor = activeFilter === 'all' || card.dataset.color === activeFilter;
    const matchName  = card.dataset.name.includes(query);
    if (matchColor && matchName) {
      card.style.display = '';
      visible++;
    } else {
      card.style.display = 'none';
    }
  });
  document.getElementById('noResults').classList.toggle('hidden', visible > 0);
}

function toggleTable() {
  const table = document.getElementById('comparisonTable');
  const icon  = document.getElementById('tableToggleIcon');
  const hidden = table.classList.toggle('hidden');
  icon.textContent = hidden ? '▼' : '▲';
}
</script>

<style>
.filter-btn {
  background: #f1f5f9;
  color: #475569;
  transition: all .15s;
}
.dark .filter-btn {
  background: #334155;
  color: #94a3b8;
}
.filter-btn:hover, .filter-btn.active {
  background: #0f172a;
  color: #fff;
}
.dark .filter-btn:hover, .dark .filter-btn.active {
  background: #475569;
  color: #fff;
}
</style>

@endsection
