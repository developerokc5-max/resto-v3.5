@extends('layout')

@section('title', 'History · ' . $parsedDate->format('M j, Y') . ' — HawkerOps')
@section('page-title', 'History')
@section('page-description', $parsedDate->format('l, M j, Y'))

@section('extra-head')
<style>
  body { overflow-x: hidden; }
  .item-thumb { transition: transform .15s ease; }
  .item-row:hover .item-thumb { transform: scale(1.05); }
</style>
@endsection

@section('top-actions')
  <a href="/history"
     class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 transition">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
    </svg>
    Back
  </a>
  <a href="/history/{{ $date }}/export"
     class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition shadow-sm">
    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

  // Online = platform colour, Offline = red — used in header badges
  $platformOnlineClass = [
    'grab'      => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 ring-1 ring-green-200 dark:ring-green-700',
    'foodpanda' => 'bg-pink-100 dark:bg-pink-900/30 text-pink-700 dark:text-pink-400 ring-1 ring-pink-200 dark:ring-pink-700',
    'deliveroo' => 'bg-cyan-100 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-400 ring-1 ring-cyan-200 dark:ring-cyan-700',
  ];
  $platformDotClass = [
    'grab'      => 'bg-green-500',
    'foodpanda' => 'bg-pink-500',
    'deliveroo' => 'bg-cyan-500',
  ];
  // Used on item rows (which platform the item is offline on)
  $platformBadgeClass = [
    'grab'      => 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400 ring-1 ring-green-200 dark:ring-green-800',
    'foodpanda' => 'bg-pink-100 dark:bg-pink-900/40 text-pink-700 dark:text-pink-400 ring-1 ring-pink-200 dark:ring-pink-800',
    'deliveroo' => 'bg-cyan-100 dark:bg-cyan-900/40 text-cyan-700 dark:text-cyan-400 ring-1 ring-cyan-200 dark:ring-cyan-800',
  ];
@endphp

{{-- ── Date header ── --}}
<div class="flex items-center justify-between flex-wrap gap-2 mb-5">
  <div class="flex items-center gap-2.5">
    @if($isToday)
      <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-500 text-white text-xs font-bold shadow-sm">
        <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span> TODAY
      </span>
    @endif
    <h1 class="font-bold text-slate-900 dark:text-slate-100 text-base">
      {{ $parsedDate->format('l, M j, Y') }}
    </h1>
  </div>
  @if($lastUpdated)
    <span class="text-[11px] text-slate-400 dark:text-slate-500 flex items-center gap-1">
      @if($isToday)
        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse inline-block"></span> Live ·
      @else
        🔒 Final ·
      @endif
      {{ $lastUpdated }}
    </span>
  @endif
</div>

{{-- ── Stats ── --}}
<div class="grid grid-cols-3 gap-3 mb-6">
  <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 text-center shadow-sm">
    <p class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Stores Scanned</p>
    <p class="text-3xl font-bold text-slate-800 dark:text-slate-100 mt-1">{{ $totalStores }}</p>
  </div>
  <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 text-center shadow-sm">
    <p class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">w/ Issues</p>
    <p class="text-3xl font-bold mt-1 {{ $storesWithIssues > 0 ? 'text-amber-500' : 'text-emerald-500' }}">{{ $storesWithIssues }}</p>
  </div>
  <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 text-center shadow-sm">
    <p class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Items Offline</p>
    <p class="text-3xl font-bold mt-1 {{ $totalOffline > 0 ? 'text-red-500' : 'text-emerald-500' }}">{{ number_format($totalOffline) }}</p>
  </div>
</div>

{{-- ── Stores with Offline Items ── --}}
@if($itemOfflineStores->count() > 0)
  <section class="mb-6">
    <div class="flex items-center gap-2 mb-3">
      <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
      <p class="text-xs font-bold text-red-500 uppercase tracking-widest">
        {{ $itemOfflineStores->count() }} {{ $itemOfflineStores->count() === 1 ? 'Store' : 'Stores' }} — Items Offline
      </p>
    </div>

    <div class="space-y-3">
      @foreach($itemOfflineStores as $store)
        @php
          $pd = $store->platform_data ?? [];

          // Merge & deduplicate items across platforms
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
          $uniqueCount = count($mergedItems);
        @endphp

        <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden shadow-sm
                    border border-slate-200 dark:border-slate-700
                    border-l-[3px] border-l-red-400 dark:border-l-red-500">

          {{-- Store header --}}
          <div class="px-4 py-3.5 flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
              <p class="font-bold text-slate-900 dark:text-slate-100 text-sm leading-snug">{{ $store->shop_name }}</p>
              <div class="flex items-center gap-2 flex-wrap mt-2.5">
                @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                  @if(isset($pd[$platform]))
                    @php $online = ($pd[$platform]['status'] ?? '') === 'Online'; @endphp
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold
                      {{ $online ? $platformOnlineClass[$platform] : 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 ring-1 ring-red-200 dark:ring-red-700' }}">
                      {{-- Status dot --}}
                      <span class="w-2 h-2 rounded-full shrink-0 {{ $online ? $platformDotClass[$platform] : 'bg-red-500' }}"></span>
                      {{ $platformLabels[$platform] }}
                      {{-- SVG icon --}}
                      @if($online)
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                      @else
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                      @endif
                    </span>
                  @endif
                @endforeach
              </div>
            </div>
            <div class="shrink-0 text-right">
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-xs font-bold ring-1 ring-red-200 dark:ring-red-800/50">
                {{ $uniqueCount }} item{{ $uniqueCount !== 1 ? 's' : '' }} off
              </span>
            </div>
          </div>

          {{-- Item rows --}}
          <div class="border-t border-slate-100 dark:border-slate-700 divide-y divide-slate-100 dark:divide-slate-700/70">
            @foreach($mergedItems as $item)
              <div class="item-row flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">

                {{-- Thumbnail --}}
                <div class="w-14 h-14 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-700 shrink-0">
                  @if(!empty($item['image_url']))
                    <img
                      src="{{ $item['image_url'] }}"
                      alt="{{ $item['name'] }}"
                      title="{{ $item['name'] }}"
                      class="item-thumb w-full h-full object-cover"
                      onerror="this.replaceWith(Object.assign(document.createElement('div'),{className:'w-full h-full flex items-center justify-center text-slate-400 font-bold text-lg',textContent:'{{ strtoupper(substr($item["name"], 0, 1)) }}'}))"
                    >
                  @else
                    <div class="w-full h-full flex items-center justify-center text-slate-400 dark:text-slate-500 font-bold text-lg">
                      {{ strtoupper(substr($item['name'], 0, 1)) }}
                    </div>
                  @endif
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-semibold text-slate-800 dark:text-slate-100 truncate" title="{{ $item['name'] }}">
                    {{ $item['name'] }}
                  </p>
                  <div class="flex items-center gap-2 mt-0.5">
                    @if($item['category'])
                      <span class="text-[11px] text-slate-400 dark:text-slate-500 truncate">{{ $item['category'] }}</span>
                    @endif
                    @if($item['price'])
                      <span class="text-[11px] font-semibold text-slate-500 dark:text-slate-400 shrink-0">${{ number_format($item['price'], 2) }}</span>
                    @endif
                  </div>
                </div>

                {{-- Platform badges --}}
                <div class="flex items-center gap-1 shrink-0 flex-wrap justify-end">
                  @foreach($item['platforms'] as $platform)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold {{ $platformBadgeClass[$platform] }}">
                      <span class="hidden sm:inline">{{ $platformLabels[$platform] }}</span>
                      <span class="sm:hidden">{{ $platform === 'grab' ? 'G' : ($platform === 'foodpanda' ? 'FP' : 'D') }}</span>
                    </span>
                  @endforeach
                </div>

              </div>
            @endforeach
          </div>

        </div>
      @endforeach
    </div>
  </section>
@endif

{{-- ── Platform-Offline Stores ── --}}
@if($platformOfflineStores->count() > 0)
  <section class="mb-6">
    <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.chev').classList.toggle('rotate-90')"
            class="flex items-center gap-2 text-left mb-2 py-1 w-full group">
      <svg class="chev w-3.5 h-3.5 text-amber-500 transition-transform duration-200 shrink-0 rotate-90"
           fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
      <span class="w-2 h-2 rounded-full bg-amber-400 shrink-0"></span>
      <p class="text-xs font-bold text-amber-500 uppercase tracking-widest">
        {{ $platformOfflineStores->count() }} {{ $platformOfflineStores->count() === 1 ? 'Store' : 'Stores' }} — Platform Offline
      </p>
    </button>

    <div class="">
    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm divide-y divide-slate-100 dark:divide-slate-700">
      @foreach($platformOfflineStores as $store)
        @php $pd = $store->platform_data ?? []; @endphp
        <div class="flex items-center gap-3 px-4 py-3 flex-wrap">
          <div class="w-1.5 h-6 rounded-full bg-amber-400 shrink-0"></div>
          <span class="font-semibold text-sm text-slate-800 dark:text-slate-200 flex-1 min-w-0 truncate">
            {{ $store->shop_name }}
          </span>
          <div class="flex items-center gap-1.5 shrink-0 flex-wrap">
            @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
              @if(isset($pd[$platform]))
                @php $online = ($pd[$platform]['status'] ?? '') === 'Online'; @endphp
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold
                  {{ $online ? $platformOnlineClass[$platform] : 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 ring-1 ring-red-200 dark:ring-red-700' }}">
                  <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $online ? $platformDotClass[$platform] : 'bg-red-500' }}"></span>
                  {{ $platformLabels[$platform] }}
                  @if($online)
                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                  @else
                    <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  @endif
                </span>
              @endif
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
    </div>
  </section>
@endif

{{-- ── All-clear ── --}}
@if($issueStores->count() === 0)
  <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/40 rounded-2xl px-5 py-5 mb-5 flex items-center gap-4">
    <span class="text-3xl">✅</span>
    <div>
      <p class="font-bold text-emerald-700 dark:text-emerald-400">All platforms online</p>
      <p class="text-xs text-emerald-600 dark:text-emerald-500 mt-0.5">No issues recorded for this day</p>
    </div>
  </div>
@endif

{{-- ── All-Good Stores (collapsible) ── --}}
@if($goodStores->count() > 0)
  <section>
    <button onclick="this.nextElementSibling.classList.toggle('hidden'); this.querySelector('.chev').classList.toggle('rotate-90')"
            class="flex items-center gap-2 text-left mb-2 py-1 w-full group">
      <svg class="chev w-3.5 h-3.5 text-emerald-500 transition-transform duration-200 shrink-0"
           fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
      </svg>
      <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">
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
                  <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold {{ $platformBadgeClass[$p2] }}">
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
  </section>
@endif

@if($issueStores->count() === 0 && $goodStores->count() === 0)
  <div class="text-center py-12 text-slate-400 dark:text-slate-500">
    <div class="text-4xl mb-3">📭</div>
    <p class="font-semibold">No store data for this day</p>
  </div>
@endif

{{-- Scan Log --}}
@if($scrapeLog->isNotEmpty())
  <section class="mt-6">
    <div class="flex items-center gap-2 mb-3">
      <div class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider">
        Scan Log
      </div>
      <span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 text-[11px] font-semibold">
        {{ $scrapeLog->count() }}× today
      </span>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
      @foreach($scrapeLog as $i => $scan)
        @php $isLast = $loop->last; @endphp
        <div class="flex gap-3 px-4 py-3 {{ !$isLast ? 'border-b border-slate-100 dark:border-slate-700' : '' }}">

          {{-- Timeline dot + line --}}
          <div class="flex flex-col items-center shrink-0 pt-0.5">
            <div class="w-2 h-2 rounded-full {{ count($scan->recoveries) > 0 ? 'bg-emerald-500' : ($scan->stores_offline > 0 ? 'bg-amber-400' : 'bg-slate-300 dark:bg-slate-600') }} mt-1"></div>
            @if(!$isLast)
              <div class="w-px flex-1 bg-slate-200 dark:bg-slate-700 mt-1"></div>
            @endif
          </div>

          {{-- Content --}}
          <div class="flex-1 min-w-0 pb-1">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                {{ $scan->scanned_sgt->format('g:i A') }}
              </span>
              <span class="text-[11px] text-slate-400 dark:text-slate-500">SGT</span>

              {{-- Stats --}}
              @if($scan->stores_offline > 0)
                <span class="px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-[11px] font-semibold">
                  {{ $scan->stores_offline }} stores w/ issues
                </span>
              @else
                <span class="px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-[11px] font-semibold">
                  All clear
                </span>
              @endif

              @if($scan->items_offline > 0)
                <span class="px-2 py-0.5 rounded-md bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-[11px] font-semibold">
                  {{ $scan->items_offline }} items off
                </span>
              @endif
            </div>

            {{-- Recoveries --}}
            @if(count($scan->recoveries) > 0)
              <div class="mt-1.5 flex flex-wrap gap-1.5">
                @foreach($scan->recoveries as $rec)
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 text-[11px] font-semibold ring-1 ring-emerald-200 dark:ring-emerald-700">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    {{ $rec['shop_name'] }} recovered
                  </span>
                @endforeach
              </div>
            @endif
          </div>

        </div>
      @endforeach
    </div>
  </section>
@endif

@endsection
