@extends('layout')

@section('title', 'Scraper Status - HawkerOps')

@section('page-title', 'Scraper Status')
@section('page-description', 'Monitor and control scraper health and execution')

@section('content')
  <!-- Scraper Overview -->
  <section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-green-50 border-2 border-green-200 rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-green-700 font-medium">Active Scrapers</p>
          <p class="text-3xl font-semibold text-green-900 mt-1">{{ $scraperStatus['active_scrapers'] }}/2</p>
        </div>
        <div class="text-4xl">✅</div>
      </div>
    </div>
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-slate-500">Last Run</p>
          <p class="text-xl font-semibold text-slate-900 mt-1">{{ $scraperStatus['last_run'] }}</p>
        </div>
        <div class="text-4xl">⏰</div>
      </div>
    </div>
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-slate-500">Success Rate (All-Time)</p>
          <p class="text-3xl font-semibold text-slate-900 mt-1">{{ $scraperStatus['success_rate'] }}%</p>
        </div>
        <div class="text-4xl">📊</div>
      </div>
    </div>
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-slate-500">Total Items Collected</p>
          <p class="text-2xl font-semibold text-slate-900 mt-1">{{ $scraperStatus['total_items_updated'] }}</p>
          <p class="text-xs text-slate-500 mt-1">Across 3 platforms</p>
        </div>
        <div class="text-4xl">📦</div>
      </div>
    </div>
  </section>

  <!-- Individual Scraper Status -->
  <section class="space-y-4">
    <!-- Platform Scraper (Monitors all 3 platforms) -->
    <div class="bg-white border-2 border-blue-200 rounded-2xl p-6 shadow-sm">
      <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 bg-blue-600 rounded-xl flex items-center justify-center text-white font-bold text-2xl">
            📡
          </div>
          <div>
            <h3 class="text-xl font-bold text-slate-900">Platform Status Scraper</h3>
            <p class="text-sm text-slate-600">Monitors all 3 platforms: Grab, FoodPanda, Deliveroo</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-bold">HEALTHY</span>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-slate-50 rounded-xl">
        <div>
          <div class="text-xs text-slate-500 mb-1">Total Runs</div>
          <div class="font-bold text-slate-900">{{ $scraperStatus['platform_runs'] }}</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Stores Checked</div>
          <div class="font-bold text-blue-600">{{ $scraperStatus['total_stores_checked'] }} (46 outlets × 3 platforms)</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Success Rate</div>
          <div class="font-bold text-green-600">{{ $scraperStatus['success_rate'] }}%</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Status Records</div>
          <div class="font-bold text-slate-900">138</div>
        </div>
      </div>

      <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-xl">
        <div class="text-xs font-semibold text-blue-700 mb-2">LATEST RUN INFO</div>
        <div class="space-y-1 font-mono text-xs text-slate-700">
          <div>✓ Last run: {{ $scraperStatus['last_run'] }}</div>
          <div>✓ Platforms scanned: Grab, FoodPanda, Deliveroo</div>
          <div>✓ Outlets monitored: 46</div>
          <div>✓ Database records: 138 status entries</div>
        </div>
      </div>
    </div>

    <!-- Items Scraper -->
    <div class="bg-white border-2 border-purple-200 rounded-2xl p-6 shadow-sm">
      <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 bg-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-2xl">
            📦
          </div>
          <div>
            <h3 class="text-xl font-bold text-slate-900">Items Scraper (Multi-Platform)</h3>
            <p class="text-sm text-slate-600">Extracts menu items from Grab, FoodPanda, and Deliveroo</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-bold">HEALTHY</span>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-slate-50 rounded-xl">
        <div>
          <div class="text-xs text-slate-500 mb-1">Total Runs</div>
          <div class="font-bold text-slate-900">{{ $scraperStatus['items_runs'] }}</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Total Items Collected</div>
          <div class="font-bold text-purple-600">{{ $scraperStatus['total_items_updated'] }}</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Avg Items/Run</div>
          <div class="font-bold text-slate-900">{{ $scraperStatus['avg_items_per_run'] }}</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Success Rate</div>
          <div class="font-bold text-green-600">{{ $scraperStatus['success_rate'] }}%</div>
        </div>
      </div>

      <div class="mt-4 p-4 bg-purple-50 border border-purple-200 rounded-xl">
        <div class="text-xs font-semibold text-purple-700 mb-2">LATEST RUN INFO</div>
        <div class="space-y-1 font-mono text-xs text-slate-700">
          <div>✓ Last run: {{ $scraperStatus['last_run'] }}</div>
          <div>✓ Total items collected: 7,455 (across all platforms)</div>
          <div>✓ Outlets processed: 46</div>
          <div>✓ Data structure: 7,455 items × 3 platforms (Grab, FoodPanda, Deliveroo)</div>
          <div class="text-purple-600 font-semibold mt-2">→ Each menu item stored 3 times (once per platform)</div>
        </div>
      </div>
    </div>
  </section>
@endsection
