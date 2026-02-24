@extends('layout')

@section('title', 'Alerts - HawkerOps')
@section('page-title', 'Alerts')
@section('page-description', 'Live platform & store health monitoring')

@section('content')

{{-- ── Top Stats Row ──────────────────────────────────────────────────────── --}}
<section class="grid grid-cols-2 md:grid-cols-4 gap-4">

  {{-- Critical --}}
  <div class="relative overflow-hidden bg-white dark:bg-slate-800 border-2 {{ $stats['critical'] > 0 ? 'border-red-400' : 'border-slate-200 dark:border-slate-700' }} rounded-2xl p-5 shadow-sm">
    @if($stats['critical'] > 0)
      <div class="absolute top-0 right-0 w-1.5 h-full bg-red-500 rounded-r-2xl"></div>
    @endif
    <p class="text-xs font-semibold uppercase tracking-wide {{ $stats['critical'] > 0 ? 'text-red-500' : 'text-slate-400 dark:text-slate-500' }}">Critical</p>
    <p class="text-4xl font-bold mt-1 {{ $stats['critical'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-300 dark:text-slate-600' }}">{{ $stats['critical'] }}</p>
    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ $stats['critical'] === 0 ? 'All clear' : 'Needs attention' }}</p>
  </div>

  {{-- Warnings --}}
  <div class="relative overflow-hidden bg-white dark:bg-slate-800 border-2 {{ $stats['warnings'] > 0 ? 'border-amber-400' : 'border-slate-200 dark:border-slate-700' }} rounded-2xl p-5 shadow-sm">
    @if($stats['warnings'] > 0)
      <div class="absolute top-0 right-0 w-1.5 h-full bg-amber-400 rounded-r-2xl"></div>
    @endif
    <p class="text-xs font-semibold uppercase tracking-wide {{ $stats['warnings'] > 0 ? 'text-amber-500' : 'text-slate-400 dark:text-slate-500' }}">Warnings</p>
    <p class="text-4xl font-bold mt-1 {{ $stats['warnings'] > 0 ? 'text-amber-500' : 'text-slate-300 dark:text-slate-600' }}">{{ $stats['warnings'] }}</p>
    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ $stats['warnings'] === 0 ? 'No warnings' : 'Monitor closely' }}</p>
  </div>

  {{-- Info --}}
  <div class="relative overflow-hidden bg-white dark:bg-slate-800 border-2 {{ $stats['info'] > 0 ? 'border-blue-400' : 'border-slate-200 dark:border-slate-700' }} rounded-2xl p-5 shadow-sm">
    @if($stats['info'] > 0)
      <div class="absolute top-0 right-0 w-1.5 h-full bg-blue-400 rounded-r-2xl"></div>
    @endif
    <p class="text-xs font-semibold uppercase tracking-wide {{ $stats['info'] > 0 ? 'text-blue-500' : 'text-slate-400 dark:text-slate-500' }}">Info</p>
    <p class="text-4xl font-bold mt-1 {{ $stats['info'] > 0 ? 'text-blue-500' : 'text-slate-300 dark:text-slate-600' }}">{{ $stats['info'] }}</p>
    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">{{ $stats['info'] === 0 ? 'Nothing to note' : 'For your attention' }}</p>
  </div>

  {{-- Healthy Stores --}}
  <div class="relative overflow-hidden bg-white dark:bg-slate-800 border-2 {{ ($stats['healthy'] ?? 0) === ($stats['total'] ?? 0) && ($stats['total'] ?? 0) > 0 ? 'border-green-400' : 'border-slate-200 dark:border-slate-700' }} rounded-2xl p-5 shadow-sm">
    @if(($stats['healthy'] ?? 0) === ($stats['total'] ?? 0) && ($stats['total'] ?? 0) > 0)
      <div class="absolute top-0 right-0 w-1.5 h-full bg-green-400 rounded-r-2xl"></div>
    @endif
    <p class="text-xs font-semibold uppercase tracking-wide text-green-600 dark:text-green-400">Healthy Stores</p>
    <p class="text-4xl font-bold mt-1 text-green-600 dark:text-green-400">{{ $stats['healthy'] ?? 0 }}<span class="text-lg text-slate-400 dark:text-slate-500 font-normal">/{{ $stats['total'] ?? 0 }}</span></p>
    <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">All platforms online</p>
  </div>

</section>

{{-- ── Main content + Sidebar ─────────────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  {{-- ── Alert Feed (left, 2/3) ──────────────────────────────────────────── --}}
  <div class="lg:col-span-2 space-y-4">

    {{-- Header row --}}
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Active Alerts</h2>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
          Last scrape: <span class="font-medium text-slate-600 dark:text-slate-400">{{ $latestScrape ?? 'Never' }}</span>
        </p>
      </div>
      <a href="/dashboard" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-900 dark:bg-slate-700 text-white text-sm font-medium rounded-xl hover:bg-slate-700 dark:hover:bg-slate-600 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        Run Scrape
      </a>
    </div>

    @forelse($alerts as $alert)
      @php
        $isCritical = $alert['type'] === 'critical';
        $isWarning  = $alert['type'] === 'warning';
        $borderColor = $isCritical ? 'border-red-300 dark:border-red-700' : ($isWarning ? 'border-amber-300 dark:border-amber-700' : 'border-blue-200 dark:border-blue-700');
        $badgeBg     = $isCritical ? 'bg-red-600' : ($isWarning ? 'bg-amber-500' : 'bg-blue-500');
        $dotColor    = $isCritical ? 'bg-red-500' : ($isWarning ? 'bg-amber-400' : 'bg-blue-400');
        $label       = strtoupper($alert['type']);
      @endphp

      <div class="bg-white dark:bg-slate-800 border-2 {{ $borderColor }} rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow">
        <div class="flex items-start gap-4">

          {{-- Animated severity dot --}}
          <div class="mt-1.5 flex-shrink-0">
            <span class="relative flex h-3 w-3">
              @if($isCritical)
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $dotColor }} opacity-75"></span>
              @endif
              <span class="relative inline-flex rounded-full h-3 w-3 {{ $dotColor }}"></span>
            </span>
          </div>

          <div class="flex-1 min-w-0">
            {{-- Badge + time --}}
            <div class="flex items-center gap-2 flex-wrap mb-2">
              <span class="px-2.5 py-0.5 {{ $badgeBg }} text-white text-xs font-bold rounded-full tracking-wide">{{ $label }}</span>
              <span class="text-xs text-slate-400 dark:text-slate-500 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Data from {{ $alert['time'] }}
              </span>
            </div>

            {{-- Title --}}
            <h3 class="font-bold text-slate-900 dark:text-slate-100 text-base leading-snug">{{ $alert['title'] }}</h3>

            {{-- Message --}}
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ $alert['message'] }}</p>

            {{-- Store tag --}}
            @if(!empty($alert['store']))
              <div class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 dark:bg-slate-700 rounded-md">
                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                </svg>
                <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ $alert['store'] }}</span>
              </div>
            @endif

            {{-- Expandable store list --}}
            @if(!empty($alert['detail']) && is_array($alert['detail']))
              <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach($alert['detail'] as $storeName)
                  <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 text-xs rounded-md font-medium">{{ $storeName }}</span>
                @endforeach
              </div>
            @endif
          </div>

          {{-- View action --}}
          <div class="flex-shrink-0">
            <a href="/stores" class="inline-flex items-center gap-1 px-3 py-1.5 bg-slate-900 dark:bg-slate-700 text-white text-xs font-semibold rounded-lg hover:bg-slate-700 dark:hover:bg-slate-600 transition">
              View
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </a>
          </div>
        </div>
      </div>

    @empty
      <div class="bg-white dark:bg-slate-800 border-2 border-green-200 dark:border-green-700 rounded-2xl p-12 text-center shadow-sm">
        <div class="text-6xl mb-4">✅</div>
        <h3 class="text-xl font-bold text-slate-800 dark:text-slate-100 mb-1">All Systems Healthy</h3>
        <p class="text-slate-500 dark:text-slate-400 text-sm">No active alerts. All stores and platforms are operating normally.</p>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-3">Last checked: {{ $latestScrape ?? 'Never' }}</p>
      </div>
    @endforelse

  </div>

  {{-- ── Sidebar (right, 1/3) ─────────────────────────────────────────────── --}}
  <div class="space-y-5">

    {{-- Platform Health Summary --}}
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <h3 class="font-bold text-slate-900 dark:text-slate-100 mb-4 flex items-center gap-2">
        <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        Platform Health
      </h3>

      @forelse($platformSummary as $platform)
        @php
          $pct = $platform['total'] > 0 ? round(($platform['online'] / $platform['total']) * 100) : 0;
          $barColor  = $pct >= 80 ? 'bg-green-500' : ($pct >= 50 ? 'bg-amber-400' : 'bg-red-500');
          $textColor = $pct >= 80 ? 'text-green-600 dark:text-green-400' : ($pct >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400');
          $icon = match(strtolower($platform['name'])) {
            'grab'      => '🟢',
            'foodpanda' => '🐼',
            'deliveroo' => '🦘',
            default     => '📦',
          };
        @endphp
        <div class="mb-4 last:mb-0">
          <div class="flex items-center justify-between mb-1.5">
            <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">{{ $icon }} {{ $platform['name'] }}</span>
            <span class="text-sm font-bold {{ $textColor }}">{{ $pct }}%</span>
          </div>
          <div class="w-full bg-slate-100 dark:bg-slate-700 rounded-full h-2">
            <div class="{{ $barColor }} h-2 rounded-full transition-all" style="width: {{ $pct }}%"></div>
          </div>
          <div class="flex justify-between mt-1">
            <span class="text-xs text-slate-400 dark:text-slate-500">{{ $platform['online'] }} online · {{ $platform['offline'] }} offline</span>
            <span class="text-xs text-slate-400 dark:text-slate-500">{{ $platform['total'] }} total</span>
          </div>
          <p class="text-xs text-slate-300 dark:text-slate-600 mt-0.5">Checked {{ $platform['checked'] }}</p>
        </div>
      @empty
        <p class="text-sm text-slate-400 dark:text-slate-500 text-center py-4">No platform data yet.<br>Run a scrape first.</p>
      @endforelse
    </div>

    {{-- Quick Links --}}
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <h3 class="font-bold text-slate-900 dark:text-slate-100 mb-3 flex items-center gap-2 text-sm">
        <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
        </svg>
        Quick Actions
      </h3>
      <div class="space-y-1">
        <a href="/dashboard" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition group">
          <span class="text-xl">📊</span>
          <div>
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white">Dashboard</p>
            <p class="text-xs text-slate-400 dark:text-slate-500">Overview of all stores</p>
          </div>
        </a>
        <a href="/stores" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition group">
          <span class="text-xl">🏪</span>
          <div>
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white">Stores</p>
            <p class="text-xs text-slate-400 dark:text-slate-500">Check individual stores</p>
          </div>
        </a>
        <a href="/platforms" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition group">
          <span class="text-xl">🌐</span>
          <div>
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white">Platforms</p>
            <p class="text-xs text-slate-400 dark:text-slate-500">Grab, Foodpanda, Deliveroo</p>
          </div>
        </a>
        <a href="/items" class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition group">
          <span class="text-xl">🍽️</span>
          <div>
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-white">Items</p>
            <p class="text-xs text-slate-400 dark:text-slate-500">Menu items availability</p>
          </div>
        </a>
      </div>
    </div>

    {{-- Legend --}}
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <h3 class="font-bold text-slate-900 dark:text-slate-100 mb-3 text-sm">Alert Legend</h3>
      <div class="space-y-2.5 text-xs text-slate-500 dark:text-slate-400">
        <div class="flex items-start gap-2">
          <span class="flex-shrink-0 w-2 h-2 rounded-full bg-red-500 mt-0.5"></span>
          <span><strong class="text-slate-700 dark:text-slate-300">Critical</strong> — All platforms offline, or &gt;50% failure on a platform</span>
        </div>
        <div class="flex items-start gap-2">
          <span class="flex-shrink-0 w-2 h-2 rounded-full bg-amber-400 mt-0.5"></span>
          <span><strong class="text-slate-700 dark:text-slate-300">Warning</strong> — Partial outage or high unavailable items (&gt;20)</span>
        </div>
        <div class="flex items-start gap-2">
          <span class="flex-shrink-0 w-2 h-2 rounded-full bg-blue-400 mt-0.5"></span>
          <span><strong class="text-slate-700 dark:text-slate-300">Info</strong> — Stale data or general notices</span>
        </div>
        <div class="flex items-start gap-2 pt-2 border-t border-slate-100 dark:border-slate-700">
          <svg class="w-3 h-3 text-slate-400 dark:text-slate-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span>Timestamps show <strong class="text-slate-700 dark:text-slate-300">when data was scraped</strong>, not when the page loaded</span>
        </div>
      </div>
    </div>

  </div>
</div>

@endsection
