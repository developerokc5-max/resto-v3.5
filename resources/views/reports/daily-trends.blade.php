@extends('layout')

@section('title', 'Daily Trends - HawkerOps')

@section('page-title', 'Daily Trends')
@section('page-description', 'Track daily platform uptime and offline item trends')

@section('content')
  <!-- Date Range Filter -->
  <section class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-bold text-slate-900">Date Range</h2>
      <div class="flex items-center gap-3">
        <input type="date" id="startDate" class="px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
        <span class="text-slate-500">to</span>
        <input type="date" id="endDate" class="px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
        <button class="px-6 py-2 bg-slate-900 text-white rounded-xl font-medium hover:opacity-90 transition">
          Apply
        </button>
      </div>
    </div>
  </section>

  <!-- Summary Stats -->
  <section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 mb-1">Avg Platform Uptime</div>
      <div class="text-3xl font-bold text-slate-900">{{ $trends['avg_uptime'] ?? '98.5' }}%</div>
      <div class="text-xs text-green-600 mt-1">↗ +2.3% vs last week</div>
    </div>
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 mb-1">Avg Offline Items</div>
      <div class="text-3xl font-bold text-slate-900">{{ $trends['avg_offline'] ?? '12' }}</div>
      <div class="text-xs text-red-600 mt-1">↘ -5 vs last week</div>
    </div>
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 mb-1">Peak Offline Hour</div>
      <div class="text-3xl font-bold text-slate-900">{{ $trends['peak_hour'] ?? '2 PM' }}</div>
      <div class="text-xs text-slate-600 mt-1">Lunch rush period</div>
    </div>
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 mb-1">Total Incidents</div>
      <div class="text-3xl font-bold text-slate-900">{{ $trends['incidents'] ?? '8' }}</div>
      <div class="text-xs text-green-600 mt-1">↗ -3 vs last week</div>
    </div>
  </section>

  <!-- Trends Chart Placeholder -->
  <section class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Platform Uptime Trends</h2>
    <div class="h-96 flex items-center justify-center bg-slate-50 rounded-xl border-2 border-dashed border-slate-300">
      <div class="text-center">
        <div class="text-6xl mb-4">📊</div>
        <p class="text-slate-600 font-medium">Chart visualization coming soon</p>
        <p class="text-sm text-slate-500 mt-2">Will show daily uptime percentage per platform</p>
      </div>
    </div>
  </section>

  <!-- Offline Items Trend -->
  <section class="bg-white rounded-2xl shadow-sm p-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Offline Items Over Time</h2>
    <div class="h-96 flex items-center justify-center bg-slate-50 rounded-xl border-2 border-dashed border-slate-300">
      <div class="text-center">
        <div class="text-6xl mb-4">📈</div>
        <p class="text-slate-600 font-medium">Chart visualization coming soon</p>
        <p class="text-sm text-slate-500 mt-2">Will show daily offline item count trends</p>
      </div>
    </div>
  </section>
@endsection
