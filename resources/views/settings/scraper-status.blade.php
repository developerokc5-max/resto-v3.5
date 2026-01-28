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
          <p class="text-3xl font-semibold text-green-900 mt-1">3/3</p>
        </div>
        <div class="text-4xl">✅</div>
      </div>
    </div>
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-slate-500">Last Run</p>
          <p class="text-xl font-semibold text-slate-900 mt-1">5 min ago</p>
        </div>
        <div class="text-4xl">⏰</div>
      </div>
    </div>
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-slate-500">Success Rate (24h)</p>
          <p class="text-3xl font-semibold text-slate-900 mt-1">98%</p>
        </div>
        <div class="text-4xl">📊</div>
      </div>
    </div>
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-slate-500">Total Runs (24h)</p>
          <p class="text-3xl font-semibold text-slate-900 mt-1">288</p>
        </div>
        <div class="text-4xl">🔄</div>
      </div>
    </div>
  </section>

  <!-- Individual Scraper Status -->
  <section class="space-y-4">
    <!-- Grab Scraper -->
    <div class="bg-white border-2 border-green-200 rounded-2xl p-6 shadow-sm">
      <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 bg-green-600 rounded-xl flex items-center justify-center text-white font-bold text-2xl">
            G
          </div>
          <div>
            <h3 class="text-xl font-bold text-slate-900">Grab Scraper</h3>
            <p class="text-sm text-slate-600">grab_scraper.py</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-bold">HEALTHY</span>
          <button class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-medium hover:opacity-90 transition">
            Run Now
          </button>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-slate-50 rounded-xl">
        <div>
          <div class="text-xs text-slate-500 mb-1">Last Run</div>
          <div class="font-bold text-slate-900">5 min ago</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Status</div>
          <div class="font-bold text-green-600">Success</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Duration</div>
          <div class="font-bold text-slate-900">2m 34s</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Items Scraped</div>
          <div class="font-bold text-slate-900">2,450</div>
        </div>
      </div>

      <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-xl">
        <div class="text-xs font-semibold text-green-700 mb-2">RECENT LOGS</div>
        <div class="space-y-1 font-mono text-xs text-slate-700">
          <div>[2026-01-28 14:35:22] Starting Grab scraper...</div>
          <div>[2026-01-28 14:35:24] Fetching menu for store 400133646...</div>
          <div>[2026-01-28 14:37:56] ✓ Successfully scraped 2,450 items</div>
        </div>
      </div>
    </div>

    <!-- FoodPanda Scraper -->
    <div class="bg-white border-2 border-pink-200 rounded-2xl p-6 shadow-sm">
      <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 bg-pink-600 rounded-xl flex items-center justify-center text-white font-bold text-2xl">
            F
          </div>
          <div>
            <h3 class="text-xl font-bold text-slate-900">FoodPanda Scraper</h3>
            <p class="text-sm text-slate-600">foodpanda_scraper.py</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-bold">HEALTHY</span>
          <button class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-medium hover:opacity-90 transition">
            Run Now
          </button>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-slate-50 rounded-xl">
        <div>
          <div class="text-xs text-slate-500 mb-1">Last Run</div>
          <div class="font-bold text-slate-900">7 min ago</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Status</div>
          <div class="font-bold text-green-600">Success</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Duration</div>
          <div class="font-bold text-slate-900">3m 12s</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Items Scraped</div>
          <div class="font-bold text-slate-900">2,380</div>
        </div>
      </div>

      <div class="mt-4 p-4 bg-pink-50 border border-pink-200 rounded-xl">
        <div class="text-xs font-semibold text-pink-700 mb-2">RECENT LOGS</div>
        <div class="space-y-1 font-mono text-xs text-slate-700">
          <div>[2026-01-28 14:33:18] Starting FoodPanda scraper...</div>
          <div>[2026-01-28 14:33:21] Fetching menu for store 400133646...</div>
          <div>[2026-01-28 14:36:30] ✓ Successfully scraped 2,380 items</div>
        </div>
      </div>
    </div>

    <!-- Deliveroo Scraper -->
    <div class="bg-white border-2 border-cyan-200 rounded-2xl p-6 shadow-sm">
      <div class="flex items-start justify-between mb-4">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 bg-cyan-600 rounded-xl flex items-center justify-center text-white font-bold text-2xl">
            D
          </div>
          <div>
            <h3 class="text-xl font-bold text-slate-900">Deliveroo Scraper</h3>
            <p class="text-sm text-slate-600">deliveroo_scraper.py</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-bold">HEALTHY</span>
          <button class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-medium hover:opacity-90 transition">
            Run Now
          </button>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-4 bg-slate-50 rounded-xl">
        <div>
          <div class="text-xs text-slate-500 mb-1">Last Run</div>
          <div class="font-bold text-slate-900">3 min ago</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Status</div>
          <div class="font-bold text-green-600">Success</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Duration</div>
          <div class="font-bold text-slate-900">2m 48s</div>
        </div>
        <div>
          <div class="text-xs text-slate-500 mb-1">Items Scraped</div>
          <div class="font-bold text-slate-900">2,410</div>
        </div>
      </div>

      <div class="mt-4 p-4 bg-cyan-50 border border-cyan-200 rounded-xl">
        <div class="text-xs font-semibold text-cyan-700 mb-2">RECENT LOGS</div>
        <div class="space-y-1 font-mono text-xs text-slate-700">
          <div>[2026-01-28 14:37:45] Starting Deliveroo scraper...</div>
          <div>[2026-01-28 14:37:48] Fetching menu for store 400133646...</div>
          <div>[2026-01-28 14:40:33] ✓ Successfully scraped 2,410 items</div>
        </div>
      </div>
    </div>
  </section>
@endsection
