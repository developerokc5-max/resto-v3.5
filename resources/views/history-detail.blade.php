@extends('layout')

@section('title', 'History · ' . $parsedDate->format('M j, Y') . ' — HawkerOps')
@section('page-title', 'History')
@section('page-description', $parsedDate->format('l, M j, Y'))

@section('extra-head')
<style>
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
  $itemOfflineStores     = $issueStores->filter(fn($s) => $s->total_offline_items > 0);
  $platformOfflineStores = $issueStores->filter(fn($s) => $s->total_offline_items == 0);

  $totalStores      = $stores->count();
  $storesWithIssues = $issueStores->count();
  $totalOffline     = (int) $stores->sum('total_offline_items');

  $platformLabels = ['grab' => 'Grab', 'foodpanda' => 'FoodPanda', 'deliveroo' => 'Deliveroo'];
  $platformBadgeClass = [
    'grab'      => 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400',
    'foodpanda' => 'bg-pink-100 dark:bg-pink-900/40 text-pink-700 dark:text-pink-400',
    'deliveroo' => 'bg-cyan-100 dark:bg-cyan-900/40 text-cyan-700 dark:text-cyan-400',
  ];
@endphp

{{-- ── Date & Status Bar ── --}}
<div class="flex items-center justify-between gap-3 mb-4 flex-wrap">
  <div class="flex items-center gap-2">
    @if($isToday)
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-500 text-white text-xs font-bold">
        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse inline-block"></span> TODAY
      </span>
    @else
      <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-bold">
        📅
      </span>
    @endif
    <span class="font-bold text-slate-900 dark:text-slate-100 text-sm md:text-base">
      {{ $parsedDate->format('l, M j, Y') }}
    </span>
  </div>
  @if($lastUpdated)
    <span class="text-[11px] text-slate-400 dark:text-slate-500">
      {{ $isToday ? '🔴 Live' : '🔒 Final' }} · {{ $lastUpdated }}
    </span>
  @endif
</div>

{{-- ── Summary Stats ── --}}
<div class="grid grid-cols-3 gap-3 mb-6">
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-3 shadow-sm text-center">
    <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium leading-tight">Stores<br>Scanned</div>
    <div class="text-2xl font-bold text-slate-900 dark:text-slate-100 mt-1">{{ $totalStores }}</div>
  </div>
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-3 shadow-sm text-center">
    <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium leading-tight">w/ Issues</div>
    <div class="text-2xl font-bold {{ $storesWithIssues > 0 ? 'text-amber-500' : 'text-emerald-500' }} mt-1">{{ $storesWithIssues }}</div>
  </div>
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-3 shadow-sm text-center">
    <div class="text-[10px] text-slate-500 dark:text-slate-400 font-medium leading-tight">Items<br>Offline</div>
    <div class="text-2xl font-bold {{ $totalOffline > 0 ? 'text-red-500' : 'text-emerald-500' }} mt-1">{{ number_format($totalOffline) }}</div>
  </div>
</div>

{{-- ── Stores with Offline Items ── --}}
@if($itemOfflineStores->count() > 0)
  <div class="mb-6">
    <p class="text-xs font-bold text-red-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
      <span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>
      {{ $itemOfflineStores->count() }} {{ $itemOfflineStores->count() === 1 ? 'Store' : 'Stores' }} with Offline Items
    </p>

    <div class="space-y-3">
      @foreach($itemOfflineStores as $store)
        @php
          $pd = $store->platform_data ?? [];

          // Deduplicate items: group by name, collect which platforms each item is offline on
          $mergedItems = [];
          foreach (['grab', 'foodpanda', 'deliveroo'] as $platform) {
            foreach ($pd[$platform]['offline_items'] ?? [] as $item) {
              $key = $item['name'];
              if (!isset($mergedItems[$key])) {
                $mergedItems[$key] = [
                  'name'      => $item['name'],
                  'category'  => $item['category'] ?? null,
                  'price'     => $item['price'] ?? null,
                  'image_url' => $item['image_url'] ?? null,
                  'platforms' => [],
                ];
              }
              $mergedItems[$key]['platforms'][] = $platform;
            }
          }
        @endphp

        <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">

          {{-- Store header --}}
          <div class="flex items-start justify-between gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-700">
            <div class="min-w-0 flex-1">
              <p class="font-bold text-sm text-slate-900 dark:text-slate-100 leading-snug">{{ $store->shop_name }}</p>
              <div class="flex items-center gap-1.5 flex-wrap mt-1.5">
                @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                  @if(isset($pd[$platform]))
                    @php $isOnline = ($pd[$platform]['status'] ?? '') === 'Online'; @endphp
                    <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[11px] font-semibold
                      {{ $isOnline
                        ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400'
                        : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' }}">
                      {{ $platformLabels[$platform] }} {{ $isOnline ? '✓' : '✗' }}
                    </span>
                  @endif
                @endforeach
              </div>
            </div>
            <span class="text-xs font-bold text-red-500 shrink-0 mt-0.5">
              {{ count($mergedItems) }} item{{ count($mergedItems) !== 1 ? 's' : '' }} off
            </span>
          </div>

          {{-- Deduplicated item list --}}
          <div class="divide-y divide-slate-100 dark:divide-slate-700">
            @foreach($mergedItems as $item)
              <div class="flex items-center gap-3 px-4 py-3">

                {{-- Thumbnail --}}
                <div class="w-12 h-12 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700 flex-shrink-0 relative">
                  @if(!empty($item['image_url']))
                    <img
                      src="{{ $item['image_url'] }}"
                      alt="{{ $item['name'] }}"
                      class="w-full h-full object-cover"
                      onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"
                    >
                    <div class="absolute inset-0 hidden items-center justify-center text-slate-400 dark:text-slate-500 font-bold text-base">
                      {{ strtoupper(substr($item['name'], 0, 1)) }}
                    </div>
                  @else
                    <div class="w-full h-full flex items-center justify-center text-slate-400 dark:text-slate-500 font-bold text-base">
                      {{ strtoupper(substr($item['name'], 0, 1)) }}
                    </div>
                  @endif
                </div>

                {{-- Name + meta --}}
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 leading-snug truncate">{{ $item['name'] }}</p>
                  <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                    @if($item['category'])
                      <span class="text-[11px] text-slate-400 dark:text-slate-500">{{ $item['category'] }}</span>
                    @endif
                    @if($item['price'])
                      <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">${{ number_format($item['price'], 2) }}</span>
                    @endif
                  </div>
                </div>

                {{-- Platform badges --}}
                <div class="flex items-center gap-1 flex-shrink-0">
                  @foreach($item['platforms'] as $platform)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold {{ $platformBadgeClass[$platform] }}">
                      <span class="hidden sm:inline">{{ $platformLabels[$platform] }}</span>
                      <span class="sm:hidden">{{ $platform === 'grab' ? 'G' : ($platform === 'foodpanda' ? 'FP' : 'Del') }}</span>
                    </span>
                  @endforeach
                </div>

              </div>
            @endforeach
          </div>

        </div>
      @endforeach
    </div>
  </div>
@endif

{{-- ── Platform-Offline Stores (closed, no item data) ── --}}
@if($platformOfflineStores->count() > 0)
  <div class="mb-6">
    <p class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-3 flex items-center gap-1.5">
      <span class="w-1.5 h-1.5 rounded-full bg-amber-400 inline-block"></span>
      {{ $platformOfflineStores->count() }} {{ $platformOfflineStores->count() === 1 ? 'Store' : 'Stores' }} Platform Offline
    </p>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm divide-y divide-slate-100 dark:divide-slate-700">
      @foreach($platformOfflineStores as $store)
        @php $pd = $store->platform_data ?? []; @endphp
        <div class="flex items-center gap-3 px-4 py-3 flex-wrap">
          <span class="font-semibold text-sm text-slate-800 dark:text-slate-200 flex-1 min-w-0 truncate">
            {{ $store->shop_name }}
          </span>
          <div class="flex items-center gap-1 shrink-0">
            @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
              @if(isset($pd[$platform]))
                @php $isOnline = ($pd[$platform]['status'] ?? '') === 'Online'; @endphp
                <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[11px] font-semibold
                  {{ $isOnline
                    ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400'
                    : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' }}">
                  {{ $platformLabels[$platform] }} {{ $isOnline ? '✓' : '✗' }}
                </span>
              @endif
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  </div>
@endif

{{-- ── All-clear banner ── --}}
@if($issueStores->count() === 0)
  <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/40 rounded-2xl px-4 py-5 mb-5 flex items-center gap-3">
    <span class="text-3xl flex-shrink-0">✅</span>
    <div>
      <p class="font-bold text-emerald-700 dark:text-emerald-400">All platforms online</p>
      <p class="text-xs text-emerald-600 dark:text-emerald-500 mt-0.5">No issues recorded for this day</p>
    </div>
  </div>
@endif

{{-- ── All-Good Stores (collapsible) ── --}}
@if($goodStores->count() > 0)
  <div>
    <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.chevron').classList.toggle('rotate-90')"
            class="flex items-center gap-2 w-full text-left mb-2 py-1 group">
      <svg class="chevron w-3.5 h-3.5 text-emerald-500 transition-transform duration-200 flex-shrink-0"
           fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
      <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
        ✓ {{ $goodStores->count() }} {{ $goodStores->count() === 1 ? 'Store' : 'Stores' }} All Online
      </span>
    </button>

    <div class="hidden">
      <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm divide-y divide-slate-100 dark:divide-slate-700">
        @foreach($goodStores as $store)
          @php $pd2 = $store->platform_data ?? []; @endphp
          <div class="flex items-center justify-between gap-2 px-4 py-2.5">
            <span class="text-sm text-slate-700 dark:text-slate-300 font-medium truncate flex-1 min-w-0">
              {{ $store->shop_name }}
            </span>
            <div class="flex items-center gap-1 shrink-0">
              @foreach(['grab', 'foodpanda', 'deliveroo'] as $p2)
                @if(isset($pd2[$p2]))
                  <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[11px] font-bold bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400">
                    <span class="hidden sm:inline">{{ $platformLabels[$p2] }}</span>
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
