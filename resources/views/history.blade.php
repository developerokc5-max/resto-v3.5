@extends('layout')

@section('title', 'History — HawkerOps')
@section('page-title', 'History')
@section('page-description', 'Daily snapshot log of stores and offline items')

@section('content')

@php
  $totalDays      = count($history);
  $daysWithIssues = collect($history)->where('stores_with_issues', '>', 0)->count();
  $totalOffline   = collect($history)->sum('total_offline_items');
@endphp

{{-- Summary Stats --}}
<div class="grid grid-cols-3 gap-3">
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-sm text-center">
    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium leading-tight">Days<br>Tracked</div>
    <div class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-slate-100 mt-1">{{ $totalDays }}</div>
  </div>
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-sm text-center">
    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium leading-tight">Days w/<br>Issues</div>
    <div class="text-2xl md:text-3xl font-bold {{ $daysWithIssues > 0 ? 'text-amber-500' : 'text-emerald-500' }} mt-1">{{ $daysWithIssues }}</div>
  </div>
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 shadow-sm text-center">
    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium leading-tight">Total<br>Items Off</div>
    <div class="text-2xl md:text-3xl font-bold text-red-500 mt-1">{{ number_format($totalOffline) }}</div>
  </div>
</div>

{{-- History Cards --}}
<div class="space-y-3">
  @forelse($history as $day)
    @php
      $date        = \Carbon\Carbon::parse($day['date'])->setTimezone('Asia/Singapore');
      $hasIssues   = $day['stores_with_issues'] > 0;
      $dateKey     = str_replace('-', '', $day['date']);
      $lastUpdated = $day['last_updated_at']
        ? \Carbon\Carbon::parse($day['last_updated_at'])->setTimezone('Asia/Singapore')->format('g:i A')
        : null;

      $storesWithIssues = $day['stores']->filter(
        fn($s) => $s->platforms_online < $s->total_platforms || $s->total_offline_items > 0
      );
      $storesAllGood = $day['stores']->filter(
        fn($s) => $s->platforms_online >= $s->total_platforms && $s->total_offline_items == 0
      );
    @endphp

    {{-- Day Card --}}
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden
      {{ $hasIssues ? 'border-l-4 border-l-amber-400 dark:border-l-amber-500' : 'border-l-4 border-l-emerald-400 dark:border-l-emerald-500' }}">

      {{-- Card Body (always visible summary) --}}
      <div class="px-4 py-4">

        {{-- Row 1: date + live/final label --}}
        <div class="flex items-center gap-2 flex-wrap">
          @if($day['is_today'])
            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-blue-500 text-white text-xs font-bold">
              <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse inline-block"></span> TODAY
            </span>
          @else
            <span class="text-base">📅</span>
          @endif
          <span class="font-bold text-slate-900 dark:text-slate-100 text-sm md:text-base">
            {{ $date->format('l, M j, Y') }}
          </span>
        </div>

        {{-- Row 2: last updated + store count --}}
        @if($lastUpdated)
          <div class="text-xs text-slate-400 dark:text-slate-500 mt-1">
            {{ $day['is_today'] ? '🔴 Live · last updated ' : '🔒 Final state · ' }}{{ $lastUpdated }} SGT
            &nbsp;·&nbsp; {{ $day['total_stores'] }} stores scanned
          </div>
        @endif

        {{-- Row 3: stats + view more button --}}
        <div class="flex items-end justify-between mt-3 gap-3">

          {{-- Left: issue stats --}}
          <div class="flex flex-wrap gap-2">
            @if(!$hasIssues)
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 text-xs font-semibold">
                ✓ All platforms online
              </span>
            @else
              <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 text-xs font-semibold">
                🏪 {{ $day['stores_with_issues'] }} stores w/ issues
              </span>
              @if($day['total_offline_items'] > 0)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 text-xs font-semibold">
                  📦 {{ $day['total_offline_items'] }} items offline
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 text-xs font-semibold">
                  📦 0 items offline
                </span>
              @endif
            @endif
          </div>

          {{-- Right: View More button --}}
          <button onclick="toggleDay('{{ $dateKey }}')"
                  id="btn-{{ $dateKey }}"
                  class="flex-shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-slate-700 transition">
            <span id="btn-label-{{ $dateKey }}">View More</span>
            <svg id="arrow-{{ $dateKey }}"
                 class="w-3.5 h-3.5 transition-transform duration-200 {{ $day['is_today'] ? 'rotate-180' : '' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
          </button>
        </div>
      </div>

      {{-- Expanded Detail --}}
      <div id="day-{{ $dateKey }}" class="{{ $day['is_today'] ? '' : 'hidden' }}">
        <div class="border-t border-slate-100 dark:border-slate-700">

          {{-- ── Stores with issues ── --}}
          @if($storesWithIssues->count() > 0)
            <div class="px-4 py-4 space-y-3 bg-amber-50/40 dark:bg-amber-900/10">
              <div class="text-xs font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider">
                ⚠ {{ $storesWithIssues->count() }} Stores with Issues
              </div>

              @foreach($storesWithIssues as $store)
                @php $pd = $store->platform_data ?? []; @endphp

                <div class="bg-white dark:bg-slate-800 border border-amber-200 dark:border-amber-800/40 rounded-xl overflow-hidden">

                  {{-- Store header: name + platform badges --}}
                  <div class="flex items-center justify-between px-3 py-2.5 gap-2">
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

                  {{-- Offline items per platform (names only, no images) --}}
                  @php $hasAnyOfflineItems = false; @endphp
                  @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                    @if(isset($pd[$platform]) && !empty($pd[$platform]['offline_items']))
                      @php $hasAnyOfflineItems = true; @endphp
                      <div class="border-t border-slate-100 dark:border-slate-700 px-3 py-2">
                        <span class="text-xs font-semibold text-red-500 dark:text-red-400">
                          {{ $platform === 'foodpanda' ? 'Panda' : ucfirst($platform) }} · {{ count($pd[$platform]['offline_items']) }} offline:
                        </span>
                        <span class="text-xs text-slate-600 dark:text-slate-400 ml-1">
                          {{ implode(' · ', array_map(fn($i) => $i['name'], $pd[$platform]['offline_items'])) }}
                        </span>
                      </div>
                    @endif
                  @endforeach

                </div>
              @endforeach
            </div>
          @endif

          {{-- ── All-good stores (collapsible) ── --}}
          @if($storesAllGood->count() > 0)
            <div class="px-4 py-3 {{ $storesWithIssues->count() > 0 ? 'border-t border-slate-100 dark:border-slate-700' : '' }}">
              <button onclick="toggleGood('{{ $dateKey }}')"
                      class="flex items-center gap-1.5 text-xs font-medium text-slate-400 dark:text-slate-500 hover:text-slate-600 dark:hover:text-slate-300 transition">
                <svg id="good-arrow-{{ $dateKey }}" class="w-3 h-3 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                {{ $storesAllGood->count() }} stores all online
              </button>
              <div id="good-{{ $dateKey }}" class="hidden mt-2 space-y-1">
                @foreach($storesAllGood as $store)
                  <div class="flex items-center justify-between px-3 py-1.5 rounded-lg bg-slate-50 dark:bg-slate-700/50">
                    <span class="text-sm text-slate-600 dark:text-slate-400 truncate">{{ $store->shop_name }}</span>
                    <span class="text-xs text-emerald-500 font-bold flex-shrink-0 ml-2">✓</span>
                  </div>
                @endforeach
              </div>
            </div>
          @endif

          {{-- Empty body --}}
          @if($storesWithIssues->count() === 0 && $storesAllGood->count() === 0)
            <div class="px-4 py-6 text-center text-sm text-slate-400 dark:text-slate-500">
              No store data for this day
            </div>
          @endif

        </div>
      </div>

    </div>
  @empty
    <div class="bg-white dark:bg-slate-800 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-12 text-center">
      <div class="text-4xl mb-3">📭</div>
      <div class="font-semibold text-slate-700 dark:text-slate-300 mb-1">No history yet</div>
      <div class="text-sm text-slate-500 dark:text-slate-400">Visit this page daily to start building your history log.</div>
    </div>
  @endforelse
</div>

@endsection

@section('extra-scripts')
<script>
  function toggleDay(key) {
    const content = document.getElementById('day-' + key);
    const arrow   = document.getElementById('arrow-' + key);
    const label   = document.getElementById('btn-label-' + key);
    const hidden  = content.classList.toggle('hidden');
    arrow.classList.toggle('rotate-180');
    label.textContent = hidden ? 'View More' : 'View Less';
  }
  function toggleGood(key) {
    const content = document.getElementById('good-' + key);
    const arrow   = document.getElementById('good-arrow-' + key);
    content.classList.toggle('hidden');
    arrow.classList.toggle('rotate-90');
  }
</script>
@endsection
