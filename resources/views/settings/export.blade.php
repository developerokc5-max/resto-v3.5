@extends('layout')

@section('title', 'Export Data - HawkerOps')

@section('page-title', 'Export Data')
@section('page-description', 'Download reports and data in various formats')

@section('content')
  <!-- Quick Exports -->
  <section class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Quick Exports</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="border-2 border-slate-200 rounded-xl p-5 hover:border-slate-300 transition">
        <div class="flex items-center gap-3 mb-3">
          <div class="text-4xl">📊</div>
          <div>
            <h3 class="font-bold text-slate-900">Overview Report</h3>
            <p class="text-xs text-slate-500">All stores & platforms</p>
          </div>
        </div>
        <p class="text-sm text-slate-600 mb-4">Export current dashboard overview with all store statuses and platform data</p>
        <a href="/export/overview" class="w-full px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-medium hover:opacity-90 transition inline-block text-center">
          Export CSV
        </a>
      </div>

      <div class="border-2 border-slate-200 rounded-xl p-5 hover:border-slate-300 transition">
        <div class="flex items-center gap-3 mb-3">
          <div class="text-4xl">📦</div>
          <div>
            <h3 class="font-bold text-slate-900">All Items</h3>
            <p class="text-xs text-slate-500">Complete menu database</p>
          </div>
        </div>
        <p class="text-sm text-slate-600 mb-4">Export all menu items with availability status across all platforms</p>
        <a href="/export/all-items" class="w-full px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-medium hover:opacity-90 transition inline-block text-center">
          Export CSV
        </a>
      </div>

      <div class="border-2 border-slate-200 rounded-xl p-5 hover:border-slate-300 transition">
        <div class="flex items-center gap-3 mb-3">
          <div class="text-4xl">❌</div>
          <div>
            <h3 class="font-bold text-slate-900">Offline Items</h3>
            <p class="text-xs text-slate-500">Currently unavailable</p>
          </div>
        </div>
        <p class="text-sm text-slate-600 mb-4">Export only items that are currently offline/unavailable</p>
        <a href="/export/offline-items" class="w-full px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-medium hover:opacity-90 transition inline-block text-center">
          Export CSV
        </a>
      </div>

      <div class="border-2 border-slate-200 rounded-xl p-5 hover:border-slate-300 transition">
        <div class="flex items-center gap-3 mb-3">
          <div class="text-4xl">🌐</div>
          <div>
            <h3 class="font-bold text-slate-900">Platform Status</h3>
            <p class="text-xs text-slate-500">Uptime & reliability</p>
          </div>
        </div>
        <p class="text-sm text-slate-600 mb-4">Export platform online/offline status for all stores</p>
        <a href="/export/platform-status" class="w-full px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-medium hover:opacity-90 transition inline-block text-center">
          Export CSV
        </a>
      </div>

      <div class="border-2 border-slate-200 rounded-xl p-5 hover:border-slate-300 transition">
        <div class="flex items-center gap-3 mb-3">
          <div class="text-4xl">📝</div>
          <div>
            <h3 class="font-bold text-slate-900">Store Logs</h3>
            <p class="text-xs text-slate-500">Historical data</p>
          </div>
        </div>
        <p class="text-sm text-slate-600 mb-4">Export historical status logs for all stores</p>
        <a href="/export/store-logs" class="w-full px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-medium hover:opacity-90 transition inline-block text-center">
          Export CSV
        </a>
      </div>

      <div class="border-2 border-slate-200 rounded-xl p-5 hover:border-slate-300 transition">
        <div class="flex items-center gap-3 mb-3">
          <div class="text-4xl">📈</div>
          <div>
            <h3 class="font-bold text-slate-900">Analytics Report</h3>
            <p class="text-xs text-slate-500">Trends & insights</p>
          </div>
        </div>
        <p class="text-sm text-slate-600 mb-4">Export comprehensive analytics with trends and patterns</p>
        <a href="/export/analytics" class="w-full px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-medium hover:opacity-90 transition inline-block text-center">
          Export CSV
        </a>
      </div>
    </div>
  </section>

  <!-- Custom Export -->
  <section class="bg-white rounded-2xl shadow-sm p-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Custom Export</h2>

    <form action="/export/custom" method="POST" id="customExportForm">
      @csrf
      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Data Type</label>
            <select name="data_type" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
              <option value="all">All Data</option>
              <option value="stores">Stores Only</option>
              <option value="items">Items Only</option>
              <option value="platform_status">Platform Status</option>
              <option value="logs">Historical Logs</option>
              <option value="offline_items">Offline Items</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Format</label>
            <select name="format" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
              <option value="csv">CSV</option>
              <option value="json">JSON</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Date Range (From)</label>
            <input type="date" name="date_from" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Date Range (To)</label>
            <input type="date" name="date_to" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Filter by Platform</label>
          <div class="flex gap-3 flex-wrap">
            <label class="flex items-center gap-2 px-4 py-3 border-2 border-slate-300 rounded-xl cursor-pointer hover:border-slate-400 transition">
              <input type="checkbox" name="platforms[]" value="Grab" class="form-checkbox h-5 w-5 text-slate-900 rounded" checked>
              <span class="text-sm font-medium">Grab</span>
            </label>
            <label class="flex items-center gap-2 px-4 py-3 border-2 border-slate-300 rounded-xl cursor-pointer hover:border-slate-400 transition">
              <input type="checkbox" name="platforms[]" value="FoodPanda" class="form-checkbox h-5 w-5 text-slate-900 rounded" checked>
              <span class="text-sm font-medium">FoodPanda</span>
            </label>
            <label class="flex items-center gap-2 px-4 py-3 border-2 border-slate-300 rounded-xl cursor-pointer hover:border-slate-400 transition">
              <input type="checkbox" name="platforms[]" value="Deliveroo" class="form-checkbox h-5 w-5 text-slate-900 rounded" checked>
              <span class="text-sm font-medium">Deliveroo</span>
            </label>
          </div>
        </div>

        <div class="flex items-center gap-4 p-4 bg-slate-50 rounded-xl">
          <input type="checkbox" name="include_images" class="form-checkbox h-5 w-5 text-slate-900 rounded">
          <div class="flex-1">
            <div class="font-medium text-slate-900">Include item images in export</div>
            <div class="text-sm text-slate-600">Add image URLs to exported data</div>
          </div>
        </div>

        <div class="flex justify-end pt-4">
          <button type="submit" class="px-8 py-3 bg-slate-900 text-white rounded-xl font-medium hover:opacity-90 transition">
            Generate Export
          </button>
        </div>
      </div>
    </form>
  </section>
@endsection
