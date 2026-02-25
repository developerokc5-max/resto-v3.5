@extends('layout')

@section('title', 'Daily Trends - HawkerOps')

@section('page-title', 'Daily Trends')
@section('page-description', 'Track daily platform uptime and offline item trends')

@section('content')
  <!-- Date Range Filter -->
  <section class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm p-5 md:p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Date Range</h2>
      <div class="flex flex-wrap items-center gap-2">
        <input type="date" id="startDate" class="flex-1 sm:flex-none px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
        <span class="text-slate-500 dark:text-slate-400 text-sm">to</span>
        <input type="date" id="endDate" class="flex-1 sm:flex-none px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
        <button class="w-full sm:w-auto px-5 py-2 bg-slate-900 dark:bg-slate-700 text-white rounded-xl text-sm font-medium hover:opacity-90 transition">
          Apply
        </button>
      </div>
    </div>
  </section>

  <!-- Summary Stats -->
  <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Avg Platform Uptime</div>
      <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $trends['avg_uptime'] ?? '98.5' }}%</div>
      <div class="text-xs text-green-600 dark:text-green-400 mt-1">↗ +2.3% vs last week</div>
    </div>
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Avg Offline Items</div>
      <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $trends['avg_offline'] ?? '12' }}</div>
      <div class="text-xs text-red-600 dark:text-red-400 mt-1">↘ -5 vs last week</div>
    </div>
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Peak Offline Hour</div>
      <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $trends['peak_hour'] ?? '2 PM' }}</div>
      <div class="text-xs text-slate-600 dark:text-slate-400 mt-1">Lunch rush period</div>
    </div>
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Total Incidents</div>
      <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $trends['incidents'] ?? '8' }}</div>
      <div class="text-xs text-green-600 dark:text-green-400 mt-1">↗ -3 vs last week</div>
    </div>
  </section>

  <!-- Trends Chart Placeholder -->
  <section class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm p-6">
    <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-4">Platform Uptime Trends</h2>
    <div class="h-96 flex items-center justify-center bg-slate-50 dark:bg-slate-900 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700">
      <div class="text-center">
        <div class="text-6xl mb-4">📊</div>
        <p class="text-slate-600 dark:text-slate-400 font-medium">Chart visualization coming soon</p>
        <p class="text-sm text-slate-500 dark:text-slate-500 mt-2">Will show daily uptime percentage per platform</p>
      </div>
    </div>
  </section>

  <!-- Offline Items Trend -->
  <section class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm p-6">
    <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-4">Offline Items Over Time</h2>
    <div class="h-96 flex items-center justify-center bg-slate-50 dark:bg-slate-900 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-700">
      <div class="text-center">
        <div class="text-6xl mb-4">📈</div>
        <p class="text-slate-600 dark:text-slate-400 font-medium">Chart visualization coming soon</p>
        <p class="text-sm text-slate-500 dark:text-slate-500 mt-2">Will show daily offline item count trends</p>
      </div>
    </div>
  </section>
@endsection
