@extends('layout')

@section('title', 'History — HawkerOps')
@section('page-title', 'History')
@section('page-description', 'Daily snapshot log of stores and offline items')

@section('top-actions')
  <a href="/history/export"
     class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition shadow-sm">
    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
    </svg>
    <span class="hidden sm:inline">Export CSV</span>
    <span class="sm:hidden">CSV</span>
  </a>
@endsection

@section('content')

@php
  $totalDays      = count($history);
  $daysWithIssues = collect($history)->where('stores_with_issues', '>', 0)->count();
  $totalOffline   = collect($history)->sum('total_offline_items');
  $todayEntry     = collect($history)->firstWhere('is_today', true);
  $pastDays       = collect($history)->where('is_today', false)->values();
@endphp

{{-- Summary Stats --}}
<div class="grid grid-cols-3 gap-3 mb-5">
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-sm text-center">
    <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium leading-tight">Days<br>Tracked</div>
    <div class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-slate-100 mt-1">{{ $totalDays }}</div>
  </div>
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-sm text-center">
    <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium leading-tight">Days w/<br>Issues</div>
    <div class="text-2xl md:text-3xl font-bold {{ $daysWithIssues > 0 ? 'text-amber-500' : 'text-emerald-500' }} mt-1">{{ $daysWithIssues }}</div>
  </div>
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-sm text-center">
    <div class="text-[11px] text-slate-500 dark:text-slate-400 font-medium leading-tight">Total<br>Items Off</div>
    <div class="text-2xl md:text-3xl font-bold {{ $totalOffline > 0 ? 'text-red-500' : 'text-emerald-500' }} mt-1">{{ number_format($totalOffline) }}</div>
  </div>
</div>

{{-- TODAY — featured card --}}
@if($todayEntry)
  @php
    $day         = $todayEntry;
    $date        = \Carbon\Carbon::parse($day['date'])->setTimezone('Asia/Singapore');
    $lastUpdated = $day['last_updated_at']
      ? \Carbon\Carbon::parse($day['last_updated_at'])->setTimezone('Asia/Singapore')->format('g:i A')
      : null;
    $hasIssues     = $day['stores_with_issues'] > 0;
    $healthyStores = $day['total_stores'] - $day['stores_with_issues'];

    $grabOff = collect($day['stores'])->filter(fn($s) =>
      is_array($s->platform_data) && ($s->platform_data['grab']['status'] ?? '') === 'Offline'
    )->count();
    $fpOff = collect($day['stores'])->filter(fn($s) =>
      is_array($s->platform_data) && ($s->platform_data['foodpanda']['status'] ?? '') === 'Offline'
    )->count();
    $delOff = collect($day['stores'])->filter(fn($s) =>
      is_array($s->platform_data) && ($s->platform_data['deliveroo']['status'] ?? '') === 'Offline'
    )->count();
  @endphp

  <a href="/history/{{ $day['date'] }}" class="block mb-5 group">
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-md border-2 {{ $hasIssues ? 'border-amber-300 dark:border-amber-600' : 'border-emerald-300 dark:border-emerald-600' }} overflow-hidden hover:shadow-lg transition-shadow">

      {{-- Header --}}
      <div class="{{ $hasIssues ? 'bg-amber-50 dark:bg-amber-900/20' : 'bg-emerald-50 dark:bg-emerald-900/20' }} px-5 py-3 border-b {{ $hasIssues ? 'border-amber-200 dark:border-amber-700' : 'border-emerald-200 dark:border-emerald-700' }} flex items-center justify-between gap-2 flex-wrap">
        <div class="flex items-center gap-2.5">
          <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-500 text-white text-xs font-bold shrink-0">
            <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse inline-block"></span> TODAY
          </span>
          <span class="font-bold text-slate-900 dark:text-slate-100 text-sm md:text-base">
            {{ $date->format('l, M j, Y') }}
          </span>
        </div>
        @if($lastUpdated)
          <span class="text-[11px] text-slate-500 dark:text-slate-400 shrink-0">
            🔴 Live · {{ $lastUpdated }} SGT · {{ $day['total_stores'] }} stores
          </span>
        @endif
      </div>

      {{-- Body --}}
      <div class="px-5 py-4 space-y-4">

        {{-- Store health numbers --}}
        <div class="flex items-center gap-4 md:gap-8">
          <div class="text-center">
            <div class="text-3xl font-bold {{ $hasIssues ? 'text-amber-500' : 'text-emerald-500' }}">{{ $day['stores_with_issues'] }}</div>
            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">w/ issues</div>
          </div>
          <div class="w-px h-10 bg-slate-200 dark:bg-slate-700"></div>
          <div class="text-center">
            <div class="text-3xl font-bold text-emerald-500">{{ $healthyStores }}</div>
            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">healthy</div>
          </div>
          <div class="w-px h-10 bg-slate-200 dark:bg-slate-700"></div>
          <div class="text-center">
            <div class="text-3xl font-bold {{ $day['total_offline_items'] > 0 ? 'text-red-500' : 'text-emerald-500' }}">
              {{ $day['total_offline_items'] }}
            </div>
            <div class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">menu items off</div>
          </div>
        </div>

        {{-- Platform breakdown --}}
        <div class="grid grid-cols-3 gap-2">
          @foreach(['grab' => 'Grab', 'foodpanda' => 'FoodPanda', 'deliveroo' => 'Deliveroo'] as $key => $label)
            @php
              $off = match($key) { 'grab' => $grabOff, 'foodpanda' => $fpOff, 'deliveroo' => $delOff };
            @endphp
            <div class="flex items-center justify-between px-3 py-2 rounded-xl border
              {{ $off > 0
                ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-700'
                : 'bg-slate-50 dark:bg-slate-700/50 border-slate-200 dark:border-slate-600' }}">
              <span class="text-[11px] font-semibold truncate
                {{ $off > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400' }}">
                <span class="hidden sm:inline">{{ $label }}</span>
                <span class="sm:hidden">{{ $key === 'foodpanda' ? 'FP' : ucfirst($key) }}</span>
              </span>
              <span class="text-sm font-bold ml-1 shrink-0
                {{ $off > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-500' }}">
                {{ $off > 0 ? $off . ' off' : '✓' }}
              </span>
            </div>
          @endforeach
        </div>

      </div>

      {{-- Footer --}}
      <div class="px-5 py-2.5 border-t border-slate-100 dark:border-slate-700 flex justify-end">
        <span class="text-xs font-semibold text-slate-400 dark:text-slate-500 flex items-center gap-1 group-hover:text-slate-600 dark:group-hover:text-slate-300 transition-colors">
          View Details
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </span>
      </div>
    </div>
  </a>
@endif

{{-- Past days --}}
@if($pastDays->isNotEmpty())
  <div class="space-y-3 mb-4">

    {{-- Filter bar --}}
    <div class="flex items-center gap-2 flex-wrap">
      {{-- Status filter --}}
      <div class="flex rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden text-xs font-semibold">
        <button onclick="filterHistory('all', this)"
                class="hist-filter px-3 py-1.5 bg-slate-700 text-white transition">All</button>
        <button onclick="filterHistory('issues', this)"
                class="hist-filter px-3 py-1.5 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition border-l border-slate-200 dark:border-slate-700">Issues Only</button>
        <button onclick="filterHistory('clear', this)"
                class="hist-filter px-3 py-1.5 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition border-l border-slate-200 dark:border-slate-700">All Clear</button>
      </div>

      {{-- Date range --}}
      <div class="flex rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden text-xs font-semibold">
        <button onclick="filterRange(7, this)"
                class="hist-range px-3 py-1.5 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition">7d</button>
        <button onclick="filterRange(30, this)"
                class="hist-range px-3 py-1.5 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition border-l border-slate-200 dark:border-slate-700">30d</button>
        <button onclick="filterRange(0, this)"
                class="hist-range px-3 py-1.5 bg-slate-700 text-white transition border-l border-slate-200 dark:border-slate-700">All</button>
      </div>

      {{-- Result count --}}
      <span id="histCount" class="text-[11px] text-slate-400 dark:text-slate-500 ml-auto"></span>
    </div>

    <div class="text-xs font-semibold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-1">Previous Days</div>

    {{-- Past-day rows --}}
    <div id="historyList" class="space-y-2">
      @foreach($pastDays as $day)
        @php
          $date        = \Carbon\Carbon::parse($day['date'])->setTimezone('Asia/Singapore');
          $lastUpdated = $day['last_updated_at']
            ? \Carbon\Carbon::parse($day['last_updated_at'])->setTimezone('Asia/Singapore')->format('g:i A')
            : null;
          $hasIssues = $day['stores_with_issues'] > 0;
        @endphp
        <a href="/history/{{ $day['date'] }}"
           data-issues="{{ $hasIssues ? '1' : '0' }}"
           data-date="{{ $day['date'] }}"
           class="hist-row flex items-center gap-3 px-4 py-3 bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 border-l-4 {{ $hasIssues ? 'border-l-amber-400' : 'border-l-emerald-400' }} hover:shadow-sm transition-shadow">
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-sm text-slate-900 dark:text-slate-100">{{ $date->format('D, M j') }}</div>
            @if($lastUpdated)
              <div class="text-[11px] text-slate-400 dark:text-slate-500">🔒 Final · {{ $lastUpdated }} SGT</div>
            @endif
          </div>
          <div class="flex items-center gap-2 shrink-0">
            @if($hasIssues)
              <span class="px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-xs font-bold">
                {{ $day['stores_with_issues'] }} issues
              </span>
            @else
              <span class="px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold">All OK</span>
            @endif
            @if($day['total_offline_items'] > 0)
              <span class="px-2 py-0.5 rounded-md bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-bold">
                {{ $day['total_offline_items'] }} off
              </span>
            @endif
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </div>
        </a>
      @endforeach
    </div>

    {{-- Empty filter result --}}
    <div id="histEmpty" class="hidden text-center py-8 text-slate-400 dark:text-slate-500 text-sm">
      No days match the current filter.
    </div>
  </div>
@endif

{{-- Empty state --}}
@if(empty($history))
  <div class="bg-white dark:bg-slate-800 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-12 text-center">
    <div class="text-4xl mb-3">📭</div>
    <div class="font-semibold text-slate-700 dark:text-slate-300 mb-1">No history yet</div>
    <div class="text-sm text-slate-500 dark:text-slate-400">Syncs will build your history log automatically.</div>
  </div>
@endif

<script>
  let _activeFilter = 'all';
  let _activeRange  = 0;

  const _today = new Date();
  _today.setHours(0, 0, 0, 0);

  function applyFilters() {
    const rows = document.querySelectorAll('.hist-row');
    let shown = 0;

    rows.forEach(row => {
      const hasIssues = row.dataset.issues === '1';
      const rowDate   = new Date(row.dataset.date + 'T00:00:00');
      const daysAgo   = Math.round((_today - rowDate) / 86400000);

      const passStatus =
        _activeFilter === 'all'    ? true :
        _activeFilter === 'issues' ? hasIssues :
        !hasIssues;

      const passRange = _activeRange === 0 ? true : daysAgo <= _activeRange;

      const visible = passStatus && passRange;
      row.classList.toggle('hidden', !visible);
      if (visible) shown++;
    });

    document.getElementById('histEmpty').classList.toggle('hidden', shown > 0);
    const countEl = document.getElementById('histCount');
    countEl.textContent = shown === rows.length ? '' : shown + ' of ' + rows.length + ' days';
  }

  function filterHistory(filter, btn) {
    _activeFilter = filter;
    document.querySelectorAll('.hist-filter').forEach(b => {
      b.className = b.className
        .replace(/bg-slate-700|text-white/g, '')
        .trim();
      if (!b.classList.contains('bg-white') && !b.classList.contains('dark:bg-slate-800')) {
        b.classList.add('bg-white', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-400');
      }
    });
    btn.classList.remove('bg-white', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-400');
    btn.classList.add('bg-slate-700', 'text-white');
    applyFilters();
  }

  function filterRange(days, btn) {
    _activeRange = days;
    document.querySelectorAll('.hist-range').forEach(b => {
      b.className = b.className
        .replace(/bg-slate-700|text-white/g, '')
        .trim();
      if (!b.classList.contains('bg-white') && !b.classList.contains('dark:bg-slate-800')) {
        b.classList.add('bg-white', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-400');
      }
    });
    btn.classList.remove('bg-white', 'dark:bg-slate-800', 'text-slate-600', 'dark:text-slate-400');
    btn.classList.add('bg-slate-700', 'text-white');
    applyFilters();
  }
</script>

@endsection
