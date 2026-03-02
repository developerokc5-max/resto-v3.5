@extends('layout')

@section('title', 'History — HawkerOps')
@section('page-title', 'History')
@section('page-description', 'Daily log of offline stores and items across all platforms')

@section('content')

@php
  $totalDays       = count($history);
  $daysWithIssues  = collect($history)->where('stores_with_issues', '>', 0)->count();
  $totalOffline    = collect($history)->sum('total_offline_items');
@endphp

{{-- Summary Stats --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-4">
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 md:p-5 shadow-sm">
    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Days Tracked</div>
    <div class="text-2xl md:text-3xl font-bold text-slate-900 dark:text-slate-100 mt-1">{{ $totalDays }}</div>
  </div>
  <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 md:p-5 shadow-sm">
    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Days with Issues</div>
    <div class="text-2xl md:text-3xl font-bold {{ $daysWithIssues > 0 ? 'text-amber-500' : 'text-emerald-500' }} mt-1">{{ $daysWithIssues }}</div>
  </div>
  <div class="col-span-2 md:col-span-1 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-4 md:p-5 shadow-sm">
    <div class="text-xs text-slate-500 dark:text-slate-400 font-medium">Total Offline Items Logged</div>
    <div class="text-2xl md:text-3xl font-bold text-red-500 mt-1">{{ number_format($totalOffline) }}</div>
  </div>
</div>

{{-- History Cards --}}
<div class="space-y-4">
  @forelse($history as $day)
    @php
      $date     = \Carbon\Carbon::parse($day['date'])->setTimezone('Asia/Singapore');
      $hasIssues = $day['stores_with_issues'] > 0;
      $dateKey  = str_replace('-', '', $day['date']);
      $storesWithIssues = $day['stores']->filter(fn($s) => $s->platforms_online < $s->total_platforms || $s->total_offline_items > 0);
      $storesAllGood    = $day['stores']->filter(fn($s) => $s->platforms_online >= $s->total_platforms && $s->total_offline_items == 0);
    @endphp

    <div class="bg-white dark:bg-slate-800 border-2 {{ $hasIssues ? 'border-amber-200 dark:border-amber-800/60' : 'border-emerald-200 dark:border-emerald-800/60' }} rounded-2xl shadow-sm overflow-hidden">

      {{-- Card Header --}}
      <button onclick="toggleDay('{{ $dateKey }}')"
              class="w-full px-4 md:px-6 py-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/40 transition text-left gap-3">

        <div class="flex items-center gap-3 min-w-0">
          {{-- Date badge --}}
          @if($day['is_today'])
            <span class="flex-shrink-0 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-500 text-white text-xs font-bold">
              <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse inline-block"></span> LIVE
            </span>
          @else
            <span class="flex-shrink-0 text-xl">📅</span>
          @endif

          <div class="min-w-0">
            <div class="font-bold text-slate-900 dark:text-slate-100 text-sm md:text-base truncate">
              {{ $date->format('l, M j, Y') }}
            </div>
            <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              {{ $day['total_stores'] }} stores monitored
            </div>
          </div>
        </div>

        {{-- Right side badges + arrow --}}
        <div class="flex items-center gap-2 flex-shrink-0">
          @if(!$hasIssues)
            <span class="hidden sm:inline-flex px-2.5 py-1 rounded-lg bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400 text-xs font-semibold">
              ✓ All Good
            </span>
            <span class="sm:hidden text-emerald-500 text-lg">✓</span>
          @else
            <span class="px-2.5 py-1 rounded-lg bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-400 text-xs font-semibold whitespace-nowrap">
              ⚠ {{ $day['stores_with_issues'] }} <span class="hidden sm:inline">stores</span>
            </span>
            @if($day['total_offline_items'] > 0)
              <span class="px-2.5 py-1 rounded-lg bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 text-xs font-semibold whitespace-nowrap">
                📦 {{ $day['total_offline_items'] }}
              </span>
            @endif
          @endif

          <svg id="arrow-{{ $dateKey }}"
               class="w-4 h-4 text-slate-400 transition-transform duration-200 {{ $day['is_today'] ? 'rotate-180' : '' }}"
               fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </div>
      </button>

      {{-- Expandable Content --}}
      <div id="day-{{ $dateKey }}" class="{{ $day['is_today'] ? '' : 'hidden' }}">
        <div class="border-t border-slate-100 dark:border-slate-700">

          {{-- Stores with issues --}}
          @if($storesWithIssues->count() > 0)
            <div class="px-4 md:px-6 py-4 bg-amber-50/50 dark:bg-amber-900/10">
              <div class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wide mb-3">
                ⚠ {{ $storesWithIssues->count() }} Stores with Issues
              </div>
              <div class="space-y-2">
                @foreach($storesWithIssues as $store)
                  @php $pd = $store->platform_data ?? []; @endphp
                  <div class="bg-white dark:bg-slate-800 border border-amber-200 dark:border-amber-800/50 rounded-xl p-3">
                    <div class="flex items-start justify-between gap-2">
                      <div class="min-w-0 flex-1">
                        <div class="font-semibold text-sm text-slate-900 dark:text-slate-100 truncate">
                          {{ $store->shop_name }}
                        </div>
                        <div class="flex flex-wrap gap-1.5 mt-1.5">
                          @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                            @if(isset($pd[$platform]))
                              @php $isOnline = ($pd[$platform]['status'] ?? '') === 'Online'; @endphp
                              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-medium
                                {{ $isOnline
                                  ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-400'
                                  : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' }}">
                                {{ ucfirst($platform) }}
                                <span class="font-bold">{{ $isOnline ? '✓' : '✗' }}</span>
                              </span>
                            @endif
                          @endforeach
                        </div>
                      </div>
                      <a href="/store/{{ $store->shop_id }}/logs"
                         class="flex-shrink-0 text-xs font-semibold px-2.5 py-1.5 rounded-lg transition whitespace-nowrap
                           {{ $store->total_offline_items > 0
                             ? 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/60'
                             : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-600' }}">
                        @if($store->total_offline_items > 0)
                          📦 {{ $store->total_offline_items }} off
                        @else
                          View →
                        @endif
                      </a>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>
          @endif

          {{-- All good stores (collapsible) --}}
          @if($storesAllGood->count() > 0)
            <div class="px-4 md:px-6 py-3">
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
                    <span class="text-xs text-emerald-500 font-semibold flex-shrink-0 ml-2">✓</span>
                  </div>
                @endforeach
              </div>
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
    content.classList.toggle('hidden');
    arrow.classList.toggle('rotate-180');
  }
  function toggleGood(key) {
    const content = document.getElementById('good-' + key);
    const arrow   = document.getElementById('good-arrow-' + key);
    content.classList.toggle('hidden');
    arrow.classList.toggle('rotate-90');
  }
</script>
@endsection
