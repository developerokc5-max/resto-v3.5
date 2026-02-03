@extends('layout')

@section('title', 'Configuration - HawkerOps')

@section('page-title', 'Configuration')
@section('page-description', 'Manage system settings and preferences')

@section('content')
  <!-- Success Message -->
  @if(session('success'))
  <div class="bg-green-50 border-2 border-green-200 rounded-2xl p-4 mb-6 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <span class="text-2xl">✅</span>
      <div>
        <div class="font-bold text-green-900">Configuration Saved!</div>
        <div class="text-sm text-green-700">Your settings have been updated successfully.</div>
      </div>
    </div>
  </div>
  @endif

  <form action="/settings/configuration" method="POST" id="configForm">
    @csrf

    <!-- Scraper Settings -->
    <section class="bg-white rounded-2xl shadow-sm p-6 mb-6">
      <h2 class="text-xl font-bold text-slate-900 mb-4">Scraper Schedule Settings</h2>

      <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Scraper Run Interval -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Scraper Run Interval</label>
            <select name="scraper_run_interval" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
              <option value="every_5_minutes" {{ $scraperInterval === 'every_5_minutes' ? 'selected' : '' }}>Every 5 minutes</option>
              <option value="every_10_minutes" {{ $scraperInterval === 'every_10_minutes' ? 'selected' : '' }}>Every 10 minutes</option>
              <option value="every_15_minutes" {{ $scraperInterval === 'every_15_minutes' ? 'selected' : '' }}>Every 15 minutes</option>
              <option value="every_30_minutes" {{ $scraperInterval === 'every_30_minutes' ? 'selected' : '' }}>Every 30 minutes</option>
              <option value="every_1_hour" {{ $scraperInterval === 'every_1_hour' ? 'selected' : '' }}>Every 1 hour</option>
            </select>
            <p class="text-xs text-slate-500 mt-1">How often scrapers run to fetch latest data</p>
          </div>

          <!-- Auto-Refresh Interval -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Auto-Refresh Interval</label>
            <select name="auto_refresh_interval" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
              <option value="every_1_minute" {{ $autoRefreshInterval === 'every_1_minute' ? 'selected' : '' }}>Every 1 minute</option>
              <option value="every_3_minutes" {{ $autoRefreshInterval === 'every_3_minutes' ? 'selected' : '' }}>Every 3 minutes</option>
              <option value="every_5_minutes" {{ $autoRefreshInterval === 'every_5_minutes' ? 'selected' : '' }}>Every 5 minutes</option>
              <option value="every_10_minutes" {{ $autoRefreshInterval === 'every_10_minutes' ? 'selected' : '' }}>Every 10 minutes</option>
              <option value="disabled" {{ $autoRefreshInterval === 'disabled' ? 'selected' : '' }}>Disabled</option>
            </select>
            <p class="text-xs text-slate-500 mt-1">How often pages auto-reload to show fresh data</p>
          </div>
        </div>

        <!-- Enable Parallel Scraping -->
        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
          <div>
            <div class="font-medium text-slate-900">Enable Parallel Scraping</div>
            <div class="text-sm text-slate-600">Run all 3 scrapers simultaneously for faster updates</div>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="enable_parallel_scraping" class="sr-only peer" {{ $enableParallelScraping ? 'checked' : '' }}>
            <div class="w-14 h-8 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-slate-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:start-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-slate-900"></div>
          </label>
        </div>
      </div>
    </section>

    <!-- Alert & Notification Settings -->
    <section class="bg-white rounded-2xl shadow-sm p-6 mb-6">
      <h2 class="text-xl font-bold text-slate-900 mb-4">Alert & Notification Settings</h2>

      <div class="space-y-4">
        <!-- Platform Offline Alerts -->
        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
          <div>
            <div class="font-medium text-slate-900">Platform Offline Alerts</div>
            <div class="text-sm text-slate-600">Get notified when a platform goes offline</div>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="enable_platform_offline_alerts" class="sr-only peer" {{ $enablePlatformOfflineAlerts ? 'checked' : '' }}>
            <div class="w-14 h-8 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-slate-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:start-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-slate-900"></div>
          </label>
        </div>

        <!-- High Offline Items Alert -->
        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
          <div>
            <div class="font-medium text-slate-900">High Offline Items Alert</div>
            <div class="text-sm text-slate-600">Alert when offline items exceed threshold</div>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="enable_high_offline_items_alert" class="sr-only peer" {{ $enableHighOfflineItemsAlert ? 'checked' : '' }}>
            <div class="w-14 h-8 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-slate-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:start-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-slate-900"></div>
          </label>
        </div>

        <!-- Thresholds and Email -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Offline Items Threshold</label>
            <input type="number" name="offline_items_threshold" value="{{ $offlineItemsThreshold }}" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
            <p class="text-xs text-slate-500 mt-1">Alert when offline items exceed this number</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Email for Alerts</label>
            <input type="email" name="alert_email" value="{{ $alertEmail }}" placeholder="alerts@example.com" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
            <p class="text-xs text-slate-500 mt-1">Where to send critical alerts</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Display Settings -->
    <section class="bg-white rounded-2xl shadow-sm p-6 mb-6">
      <h2 class="text-xl font-bold text-slate-900 mb-4">Display Settings</h2>

      <div class="space-y-4">
        <!-- Timezone and Date Format -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Timezone</label>
            <select name="timezone" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
              <option value="Asia/Singapore" {{ $timezone === 'Asia/Singapore' ? 'selected' : '' }}>Asia/Singapore (SGT, UTC+8)</option>
              <option value="Asia/Kuala_Lumpur" {{ $timezone === 'Asia/Kuala_Lumpur' ? 'selected' : '' }}>Asia/Kuala_Lumpur (MYT, UTC+8)</option>
              <option value="Asia/Bangkok" {{ $timezone === 'Asia/Bangkok' ? 'selected' : '' }}>Asia/Bangkok (ICT, UTC+7)</option>
              <option value="UTC" {{ $timezone === 'UTC' ? 'selected' : '' }}>UTC</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Date Format</label>
            <select name="date_format" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
              <option value="DD/MM/YYYY" {{ $dateFormat === 'DD/MM/YYYY' ? 'selected' : '' }}>DD/MM/YYYY</option>
              <option value="MM/DD/YYYY" {{ $dateFormat === 'MM/DD/YYYY' ? 'selected' : '' }}>MM/DD/YYYY</option>
              <option value="YYYY-MM-DD" {{ $dateFormat === 'YYYY-MM-DD' ? 'selected' : '' }}>YYYY-MM-DD</option>
            </select>
          </div>
        </div>

        <!-- Show Item Images -->
        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
          <div>
            <div class="font-medium text-slate-900">Show Item Images</div>
            <div class="text-sm text-slate-600">Display product images in item lists</div>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="show_item_images" class="sr-only peer" {{ $showItemImages ? 'checked' : '' }}>
            <div class="w-14 h-8 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-slate-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:start-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-slate-900"></div>
          </label>
        </div>
      </div>
    </section>

    <!-- Save Button -->
    <div class="flex justify-end gap-4">
      <button type="reset" class="px-8 py-3 border-2 border-slate-300 text-slate-900 rounded-xl font-medium hover:bg-slate-50 transition">
        Reset
      </button>
      <button type="submit" class="px-8 py-3 bg-slate-900 text-white rounded-xl font-medium hover:opacity-90 transition">
        Save Configuration
      </button>
    </div>
  </form>
@endsection
