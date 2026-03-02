@extends('layout')

@section('title', 'History · ' . $parsedDate->format('M j, Y') . ' — HawkerOps')
@section('page-title', 'History')
@section('page-description', $parsedDate->format('l, M j, Y'))

@section('extra-head')
<style>
  /* Prevent any child from creating horizontal scroll */
  body { overflow-x: hidden; }
</style>
@endsection

@section('top-actions')
  <a href="/history"
     class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-700 transition">
    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
    </svg>
    Back
  </a>
  <a href="/history/{{ $date }}/export"
     class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition shadow-sm">
    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
    </svg>
    <span class="hidden sm:inline">Export CSV</span>
    <span class="sm:hidden">CSV</span>
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
  $totalOffline     = (int) $stores->sum('total_offline_items');
@endphp

{{-- Date header --}}
<div class="flex items-center gap-2 flex-wrap mb-1">
  @if($isToday)
    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-500 text-white text-xs font-bold flex-shrink-0">
      <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse inline-block"></span> TODAY
    </span>
  @else
    <span class="text-base">📅</span>
  @endif
  <span class="font-bold text-slate-900 dark:text-slate-100 text-sm md:text-base">
    {{ $parsedDate->format('l, M j, Y') }}
  </span>
</div>

@if($lastUpdated)
  <p class="text-xs text-slate-400 dark:text-slate-500 mb-4 leading-relaxed">
    {{ $isToday ? '🔴 Live' : '🔒 Final' }}
    · {{ $lastUpdated }}
    · {{ $totalStores }} stores
  </p>
@endif

{{-- Summary Stats — compact 3-col --}}
<div class="grid grid-cols-3 gap-2 mb-5">
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 shadow-sm text-center">
    <div class="text-[10px] leading-tight text-slate-500 dark:text-slate-400 font-medium">Stores<br>Scanned</div>
    <div class="text-xl font-bold text-slate-900 dark:text-slate-100 mt-1">{{ $totalStores }}</div>
  </div>
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 shadow-sm text-center">
    <div class="text-[10px] leading-tight text-slate-500 dark:text-slate-400 font-medium">w/ Issues</div>
    <div class="text-xl font-bold {{ $storesWithIssues > 0 ? 'text-amber-500' : 'text-emerald-500' }} mt-1">{{ $storesWithIssues }}</div>
  </div>
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl p-2.5 shadow-sm text-center">
    <div class="text-[10px] leading-tight text-slate-500 dark:text-slate-400 font-medium">Items<br>Offline</div>
    <div class="text-xl font-bold {{ $totalOffline > 0 ? 'text-red-500' : 'text-emerald-500' }} mt-1">{{ number_format($totalOffline) }}</div>
  </div>
</div>

{{-- ── Stores with Issues ── --}}
@if($issueStores->count() > 0)
  <div class="mb-5">
    <p class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider mb-3">
      ⚠ {{ $issueStores->count() }} {{ $issueStores->count() === 1 ? 'Store' : 'Stores' }} with Issues
    </p>

    <div class="space-y-3">
      @foreach($issueStores as $store)
        @php $pd = $store->platform_data ?? []; @endphp

        <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-sm
                    border border-amber-200 dark:border-amber-800/40
                    border-l-[3px] border-l-amber-400 dark:border-l-amber-500">

          {{-- Store name (full width, wraps freely) --}}
          <div class="px-3 pt-3 pb-2">
            <p class="font-semibold text-sm text-slate-900 dark:text-slate-100 leading-snug">
              {{ $store->shop_name }}
            </p>

            {{-- Platform badges — always in their own row so they never get pushed off-screen --}}
            @php $hasPlatformData = !empty(array_filter($pd, fn($p) => isset($p['status']))); @endphp
            @if($hasPlatformData)
              <div class="flex items-center gap-1.5 flex-wrap mt-2">
                @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                  @if(isset($pd[$platform]))
                    @php $isOnline = ($pd[$platform]['status'] ?? '') === 'Online'; @endphp
                    <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-md text-xs font-semibold
                      {{ $isOnline
                        ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400'
                        : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' }}">
                      {{ $platform === 'grab' ? 'Grab' : ($platform === 'foodpanda' ? 'Panda' : 'Deliveroo') }}
                      {{ $isOnline ? '✓' : '✗' }}
                    </span>
                  @endif
                @endforeach
              </div>
            @endif
          </div>

          {{-- Offline items per platform --}}
          @php $hasAnyOffline = false; @endphp
          @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
            @if(isset($pd[$platform]) && !empty($pd[$platform]['offline_items']))
              @php $hasAnyOffline = true; $offCount = count($pd[$platform]['offline_items']); @endphp
              <div class="border-t border-slate-100 dark:border-slate-700 px-3 py-2.5">
                <p class="text-xs font-semibold text-red-500 dark:text-red-400 mb-1.5">
                  📦 {{ $platform === 'grab' ? 'Grab' : ($platform === 'foodpanda' ? 'Panda' : 'Deliveroo') }}
                  &mdash; {{ $offCount }} item{{ $offCount > 1 ? 's' : '' }} offline
                </p>
                <div class="flex flex-wrap gap-1">
                  @foreach($pd[$platform]['offline_items'] as $item)
                    <span class="inline-block px-2 py-0.5 rounded-md text-xs
                                 bg-red-50 dark:bg-red-900/20
                                 border border-red-100 dark:border-red-800/30
                                 text-red-700 dark:text-red-400">
                      {{ $item['name'] }}
                    </span>
                  @endforeach
                </div>
              </div>
            @endif
          @endforeach

          {{-- All platforms offline but no item data --}}
          @if(!$hasAnyOffline && $store->platforms_online < $store->total_platforms)
            <div class="border-t border-slate-100 dark:border-slate-700 px-3 py-2 text-xs text-slate-400 dark:text-slate-500">
              Platform offline — no item data captured
            </div>
          @endif

        </div>
      @endforeach
    </div>
  </div>

@else
  {{-- All-clear banner --}}
  <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/40 rounded-2xl px-4 py-4 mb-5 flex items-center gap-3">
    <span class="text-2xl flex-shrink-0">✅</span>
    <div>
      <p class="font-semibold text-emerald-700 dark:text-emerald-400 text-sm">All platforms online</p>
      <p class="text-xs text-emerald-600 dark:text-emerald-500 mt-0.5">No issues recorded for this day</p>
    </div>
  </div>
@endif

{{-- ── All-Good Stores (collapsible) ── --}}
@if($goodStores->count() > 0)
  <div>
    <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('svg').classList.toggle('rotate-90')"
            class="flex items-center gap-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400
                   hover:text-emerald-700 dark:hover:text-emerald-300 transition mb-2 w-full text-left">
      <svg class="w-3.5 h-3.5 transition-transform duration-200 flex-shrink-0"
           fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
      ✓ {{ $goodStores->count() }} {{ $goodStores->count() === 1 ? 'store' : 'stores' }} all online
    </button>

    <div class="hidden space-y-1">
      @foreach($goodStores as $store)
        @php $pd2 = $store->platform_data ?? []; @endphp
        <div class="flex items-center justify-between gap-2 px-3 py-2 rounded-xl
                    bg-slate-50 dark:bg-slate-800/50
                    border border-slate-100 dark:border-slate-700/50">
          <span class="text-sm text-slate-600 dark:text-slate-400 truncate min-w-0 flex-1">
            {{ $store->shop_name }}
          </span>
          <div class="flex items-center gap-1 flex-shrink-0">
            @foreach(['grab', 'foodpanda', 'deliveroo'] as $p2)
              @if(isset($pd2[$p2]))
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold
                             bg-emerald-100 dark:bg-emerald-900/40
                             text-emerald-700 dark:text-emerald-400">
                  <span class="hidden sm:inline">{{ $p2 === 'grab' ? 'Grab' : ($p2 === 'foodpanda' ? 'Panda' : 'Del') }}</span>
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
    <p class="font-semibold">No store data for this day</p>
  </div>
@endif

@endsection
