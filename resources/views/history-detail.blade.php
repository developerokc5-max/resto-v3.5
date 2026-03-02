@extends('layout')

@section('title', 'History · ' . $parsedDate->format('M j, Y') . ' — HawkerOps')
@section('page-title', 'History')
@section('page-description', $parsedDate->format('l, M j, Y'))

@section('top-actions')
  <a href="/history"
     class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-700 transition">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
    </svg>
    Back
  </a>
@endsection

@section('content')

@php
  $issueStores = $stores->filter(
    fn($s) => $s->platforms_online < $s->total_platforms || $s->total_offline_items > 0
  );
  $goodStores = $stores->filter(
    fn($s) => $s->platforms_online >= $s->total_platforms && $s->total_offline_items == 0
  );
  $totalStores      = $stores->count();
  $storesWithIssues = $issueStores->count();
  $totalOffline     = $stores->sum('total_offline_items');
@endphp

{{-- Date header --}}
<div class="flex items-center gap-2 flex-wrap mb-1">
  @if($isToday)
    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-500 text-white text-xs font-bold">
      <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse inline-block"></span> TODAY
    </span>
  @else
    <span class="text-base">📅</span>
  @endif
  <h2 class="font-bold text-slate-900 dark:text-slate-100 text-base md:text-lg">
    {{ $parsedDate->format('l, M j, Y') }}
  </h2>
</div>

@if($lastUpdated)
  <div class="text-xs text-slate-400 dark:text-slate-500 mb-4">
    {{ $isToday ? '🔴 Live snapshot · last updated ' : '🔒 Final state · ' }}{{ $lastUpdated }}
    &nbsp;·&nbsp; {{ $totalStores }} stores scanned
  </div>
@endif

{{-- Summary Stats --}}
<div class="grid grid-cols-3 gap-3 mb-6">
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-3 md:p-4 shadow-sm text-center">
    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium leading-tight">Stores<br>Scanned</div>
    <div class="text-2xl font-bold text-slate-900 dark:text-slate-100 mt-1">{{ $totalStores }}</div>
  </div>
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-3 md:p-4 shadow-sm text-center">
    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium leading-tight">Stores w/<br>Issues</div>
    <div class="text-2xl font-bold {{ $storesWithIssues > 0 ? 'text-amber-500' : 'text-emerald-500' }} mt-1">{{ $storesWithIssues }}</div>
  </div>
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-3 md:p-4 shadow-sm text-center">
    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium leading-tight">Items<br>Offline</div>
    <div class="text-2xl font-bold text-red-500 mt-1">{{ number_format($totalOffline) }}</div>
  </div>
</div>

{{-- ── Stores with Issues ── --}}
@if($issueStores->count() > 0)
  <div class="mb-6">
    <div class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider mb-3">
      ⚠ {{ $issueStores->count() }} {{ $issueStores->count() === 1 ? 'Store' : 'Stores' }} with Issues
    </div>

    <div class="space-y-3">
      @foreach($issueStores as $store)
        @php $pd = $store->platform_data ?? []; @endphp

        <div class="bg-white dark:bg-slate-800 border border-amber-200 dark:border-amber-800/40 border-l-4 border-l-amber-400 dark:border-l-amber-500 rounded-2xl overflow-hidden shadow-sm">

          {{-- Store header: name + platform badges --}}
          <div class="flex items-center justify-between px-4 py-3 gap-2">
            <span class="font-semibold text-sm text-slate-900 dark:text-slate-100 truncate">
              {{ $store->shop_name }}
            </span>
            <div class="flex items-center gap-1 flex-shrink-0">
              @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                @if(isset($pd[$platform]))
                  @php $isOnline = ($pd[$platform]['status'] ?? '') === 'Online'; @endphp
                  <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-xs font-semibold
                    {{ $isOnline
                      ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400'
                      : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' }}">
                    <span class="hidden sm:inline">{{ $platform === 'foodpanda' ? 'Panda' : ucfirst($platform) }}</span>
                    <span class="sm:hidden">{{ $platform === 'grab' ? 'G' : ($platform === 'foodpanda' ? 'P' : 'D') }}</span>
                    {{ $isOnline ? ' ✓' : ' ✗' }}
                  </span>
                @endif
              @endforeach
            </div>
          </div>

          {{-- Offline items per platform --}}
          @php $hasAnyOffline = false; @endphp
          @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
            @if(isset($pd[$platform]) && !empty($pd[$platform]['offline_items']))
              @php $hasAnyOffline = true; @endphp
              <div class="border-t border-slate-100 dark:border-slate-700 px-4 py-2.5">
                <div class="text-xs font-semibold text-red-500 dark:text-red-400 mb-1.5">
                  📦 {{ $platform === 'foodpanda' ? 'Panda' : ucfirst($platform) }}
                  · {{ count($pd[$platform]['offline_items']) }} item{{ count($pd[$platform]['offline_items']) > 1 ? 's' : '' }} offline:
                </div>
                <div class="flex flex-wrap gap-1">
                  @foreach($pd[$platform]['offline_items'] as $item)
                    <span class="inline-block px-2 py-0.5 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-100 dark:border-red-800/30 text-xs text-red-700 dark:text-red-400">
                      {{ $item['name'] }}
                    </span>
                  @endforeach
                </div>
              </div>
            @endif
          @endforeach

          {{-- If all platforms offline but no offline items, show a note --}}
          @if(!$hasAnyOffline && $store->platforms_online < $store->total_platforms)
            <div class="border-t border-slate-100 dark:border-slate-700 px-4 py-2.5 text-xs text-slate-400 dark:text-slate-500">
              Platform offline — no item data captured
            </div>
          @endif

        </div>
      @endforeach
    </div>
  </div>
@else
  {{-- All clear banner --}}
  <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/40 rounded-2xl px-4 py-4 mb-6 flex items-center gap-3">
    <span class="text-2xl">✅</span>
    <div>
      <div class="font-semibold text-emerald-700 dark:text-emerald-400 text-sm">All platforms online</div>
      <div class="text-xs text-emerald-600 dark:text-emerald-500 mt-0.5">No issues recorded for this day</div>
    </div>
  </div>
@endif

{{-- ── All-Good Stores (collapsible) ── --}}
@if($goodStores->count() > 0)
  <div>
    <button onclick="document.getElementById('good-stores').classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-90')"
            class="flex items-center gap-1.5 text-xs font-medium text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 transition mb-2 w-full text-left">
      <svg class="w-3.5 h-3.5 transition-transform duration-200 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
      <span class="text-emerald-600 dark:text-emerald-400 font-semibold">
        ✓ {{ $goodStores->count() }} {{ $goodStores->count() === 1 ? 'store' : 'stores' }} all online
      </span>
    </button>

    <div id="good-stores" class="hidden space-y-1">
      @foreach($goodStores as $store)
        <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50">
          <span class="text-sm text-slate-600 dark:text-slate-400 truncate">{{ $store->shop_name }}</span>
          <div class="flex items-center gap-1 flex-shrink-0 ml-2">
            @php $pd2 = $store->platform_data ?? []; @endphp
            @foreach(['grab', 'foodpanda', 'deliveroo'] as $p2)
              @if(isset($pd2[$p2]))
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400">
                  <span class="hidden sm:inline">{{ $p2 === 'foodpanda' ? 'Panda' : ucfirst($p2) }}</span>
                  <span class="sm:hidden">{{ $p2 === 'grab' ? 'G' : ($p2 === 'foodpanda' ? 'P' : 'D') }}</span>
                  ✓
                </span>
              @endif
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  </div>
@endif

{{-- Empty state --}}
@if($issueStores->count() === 0 && $goodStores->count() === 0)
  <div class="text-center py-12 text-slate-400 dark:text-slate-500">
    <div class="text-4xl mb-3">📭</div>
    <div class="font-semibold">No store data for this day</div>
  </div>
@endif

@endsection
