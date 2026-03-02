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
<div class="grid grid-cols-3 gap-3 mb-4">
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
      $lastUpdated = $day['last_updated_at']
        ? \Carbon\Carbon::parse($day['last_updated_at'])->setTimezone('Asia/Singapore')->format('g:i A')
        : null;
    @endphp

    {{-- Day Card --}}
    <a href="/history/{{ $day['date'] }}"
       class="block bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden
         {{ $hasIssues ? 'border-l-4 border-l-amber-400 dark:border-l-amber-500' : 'border-l-4 border-l-emerald-400 dark:border-l-emerald-500' }}
         hover:shadow-md transition-shadow">

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
            {{ $day['is_today'] ? '🔴 Live · last updated ' : '🔒 Final · ' }}{{ $lastUpdated }} SGT
            &nbsp;·&nbsp; {{ $day['total_stores'] }} stores
          </div>
        @endif

        {{-- Row 3: stats + view details arrow --}}
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

          {{-- Right: View Details arrow --}}
          <span class="flex-shrink-0 inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 text-slate-600 dark:text-slate-300 text-xs font-semibold">
            <span class="hidden sm:inline">View Details</span>
            <span class="sm:hidden">Details</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </span>

        </div>
      </div>
    </a>
  @empty
    <div class="bg-white dark:bg-slate-800 border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-12 text-center">
      <div class="text-4xl mb-3">📭</div>
      <div class="font-semibold text-slate-700 dark:text-slate-300 mb-1">No history yet</div>
      <div class="text-sm text-slate-500 dark:text-slate-400">Syncs will build your history log automatically.</div>
    </div>
  @endforelse
</div>

@endsection
