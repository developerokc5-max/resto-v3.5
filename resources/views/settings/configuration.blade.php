@extends('layout')

@section('title', 'Configuration - HawkerOps')

@section('page-title', 'Configuration')
@section('page-description', 'Manage system settings and preferences')

@section('content')
  <!-- Scraper Settings -->
  <section class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Scraper Schedule Settings</h2>

    <div class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Scraper Run Interval</label>
          <select class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
            <option>Every 5 minutes</option>
            <option selected>Every 10 minutes</option>
            <option>Every 15 minutes</option>
            <option>Every 30 minutes</option>
            <option>Every 1 hour</option>
          </select>
          <p class="text-xs text-slate-500 mt-1">How often scrapers run to fetch latest data</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Auto-Refresh Interval</label>
          <select class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
            <option>Every 1 minute</option>
            <option>Every 3 minutes</option>
            <option selected>Every 5 minutes</option>
            <option>Every 10 minutes</option>
            <option>Disabled</option>
          </select>
          <p class="text-xs text-slate-500 mt-1">How often pages auto-reload to show fresh data</p>
        </div>
      </div>

      <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
        <div>
          <div class="font-medium text-slate-900">Enable Parallel Scraping</div>
          <div class="text-sm text-slate-600">Run all 3 scrapers simultaneously for faster updates</div>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" class="sr-only peer" checked>
          <div class="w-14 h-8 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-slate-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:start-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-slate-900"></div>
        </label>
      </div>
    </div>
  </section>

  <!-- Notification Settings -->
  <section class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Alert & Notification Settings</h2>

    <div class="space-y-4">
      <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
        <div>
          <div class="font-medium text-slate-900">Platform Offline Alerts</div>
          <div class="text-sm text-slate-600">Get notified when a platform goes offline</div>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" class="sr-only peer" checked>
          <div class="w-14 h-8 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-slate-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:start-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-slate-900"></div>
        </label>
      </div>

      <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
        <div>
          <div class="font-medium text-slate-900">High Offline Items Alert</div>
          <div class="text-sm text-slate-600">Alert when offline items exceed threshold</div>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" class="sr-only peer" checked>
          <div class="w-14 h-8 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-slate-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:start-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-slate-900"></div>
        </label>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Offline Items Threshold</label>
          <input type="number" value="20" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
          <p class="text-xs text-slate-500 mt-1">Alert when offline items exceed this number</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Email for Alerts</label>
          <input type="email" placeholder="alerts@example.com" class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
          <p class="text-xs text-slate-500 mt-1">Where to send critical alerts</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Display Settings -->
  <section class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Display Settings</h2>

    <div class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Timezone</label>
          <select class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
            <option selected>Asia/Singapore (SGT, UTC+8)</option>
            <option>Asia/Kuala_Lumpur (MYT, UTC+8)</option>
            <option>Asia/Bangkok (ICT, UTC+7)</option>
            <option>UTC</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-2">Date Format</label>
          <select class="w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
            <option selected>DD/MM/YYYY</option>
            <option>MM/DD/YYYY</option>
            <option>YYYY-MM-DD</option>
          </select>
        </div>
      </div>

      <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
        <div>
          <div class="font-medium text-slate-900">Show Item Images</div>
          <div class="text-sm text-slate-600">Display product images in item lists</div>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
          <input type="checkbox" class="sr-only peer" checked>
          <div class="w-14 h-8 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-slate-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:start-[4px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-slate-900"></div>
        </label>
      </div>
    </div>
  </section>

  <!-- Save Button -->
  <div class="flex justify-end">
    <button class="px-8 py-3 bg-slate-900 text-white rounded-xl font-medium hover:opacity-90 transition">
      Save Configuration
    </button>
  </div>
@endsection
