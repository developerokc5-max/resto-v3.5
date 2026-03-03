@extends('layout')

@section('title', 'Daily Trends - HawkerOps')

@section('page-title', 'Daily Trends')
@section('page-description', 'Track daily platform uptime and offline item trends')

@section('extra-head')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endsection

@section('content')
  <!-- Date Range Filter -->
  <section class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm p-5 md:p-6">
    <form method="GET" action="/reports/daily-trends" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Date Range</h2>
      <div class="flex flex-wrap items-center gap-2">
        <input type="date" name="start" value="{{ $startDate }}" class="flex-1 sm:flex-none px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
        <span class="text-slate-500 dark:text-slate-400 text-sm">to</span>
        <input type="date" name="end" value="{{ $endDate }}" class="flex-1 sm:flex-none px-3 py-2 text-sm border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
        <button type="submit" class="w-full sm:w-auto px-5 py-2 bg-slate-900 dark:bg-slate-700 text-white rounded-xl text-sm font-medium hover:opacity-90 transition">
          Apply
        </button>
      </div>
    </form>
  </section>

  <!-- Summary Stats -->
  <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Avg Platform Uptime</div>
      <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $trends['avg_uptime'] }}%</div>
      <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Current across all platforms</div>
    </div>
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Items Offline Now</div>
      <div class="text-3xl font-bold {{ $trends['avg_offline'] > 0 ? 'text-red-500' : 'text-green-600 dark:text-green-400' }}">{{ $trends['avg_offline'] }}</div>
      <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Across all stores</div>
    </div>
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Peak Offline Hour</div>
      <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $trends['peak_hour'] }}</div>
      <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Most active today (SGT)</div>
    </div>
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Status Events Today</div>
      <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $trends['incidents'] }}</div>
      <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Store status changes logged</div>
    </div>
  </section>

  <!-- Platform Uptime Chart -->
  <section class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm p-6">
    <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-1">Platform Uptime Trends</h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">Daily % of stores online per platform</p>
    @if($platformUptimeData->isEmpty())
      <div class="h-64 flex items-center justify-center bg-slate-50 dark:bg-slate-900 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700">
        <div class="text-center">
          <div class="text-4xl mb-3">📊</div>
          <p class="text-slate-500 dark:text-slate-400 text-sm">No data for this date range yet</p>
        </div>
      </div>
    @else
      <canvas id="uptimeChart" height="100"></canvas>
    @endif
  </section>

  <!-- Offline Items Chart -->
  <section class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm p-6">
    <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-1">Offline Items Over Time</h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">Total menu items unavailable across all stores per day</p>
    @if($dailyData->isEmpty())
      <div class="h-64 flex items-center justify-center bg-slate-50 dark:bg-slate-900 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700">
        <div class="text-center">
          <div class="text-4xl mb-3">📈</div>
          <p class="text-slate-500 dark:text-slate-400 text-sm">No data for this date range yet</p>
        </div>
      </div>
    @else
      <canvas id="offlineChart" height="100"></canvas>
    @endif
  </section>

  <script>
    (function () {
      const isDark    = document.documentElement.classList.contains('dark');
      const textColor = isDark ? '#94a3b8' : '#64748b';
      const gridColor = isDark ? 'rgba(148,163,184,0.08)' : 'rgba(148,163,184,0.18)';

      // --- Platform Uptime Chart ---
      const uptimeEl = document.getElementById('uptimeChart');
      if (uptimeEl) {
        const labels         = @json($platformUptimeData->pluck('snapshot_date'));
        const grabData       = @json($platformUptimeData->pluck('grab_uptime'));
        const foodpandaData  = @json($platformUptimeData->pluck('foodpanda_uptime'));
        const deliverooData  = @json($platformUptimeData->pluck('deliveroo_uptime'));

        new Chart(uptimeEl, {
          type: 'line',
          data: {
            labels,
            datasets: [
              { label: 'Grab',      data: grabData,      borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,0.1)',   borderWidth: 2, tension: 0.4, pointRadius: 4, fill: true },
              { label: 'FoodPanda', data: foodpandaData,  borderColor: '#ec4899', backgroundColor: 'rgba(236,72,153,0.1)',  borderWidth: 2, tension: 0.4, pointRadius: 4, fill: true },
              { label: 'Deliveroo', data: deliverooData,  borderColor: '#0ea5e9', backgroundColor: 'rgba(14,165,233,0.1)',  borderWidth: 2, tension: 0.4, pointRadius: 4, fill: true },
            ],
          },
          options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
              y: {
                min: 0, max: 100,
                ticks: { callback: v => v + '%', color: textColor },
                grid: { color: gridColor },
                border: { display: false },
              },
              x: {
                ticks: { color: textColor },
                grid: { color: gridColor },
                border: { display: false },
              },
            },
            plugins: {
              legend: { labels: { color: textColor, usePointStyle: true, pointStyle: 'circle' } },
              tooltip: { callbacks: { label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y}%` } },
            },
          },
        });
      }

      // --- Offline Items Chart ---
      const offlineEl = document.getElementById('offlineChart');
      if (offlineEl) {
        const labels      = @json($dailyData->pluck('snapshot_date'));
        const offlineData = @json($dailyData->pluck('total_offline'));

        new Chart(offlineEl, {
          type: 'bar',
          data: {
            labels,
            datasets: [{
              label: 'Total Offline Items',
              data: offlineData,
              backgroundColor: 'rgba(239,68,68,0.55)',
              borderColor: '#ef4444',
              borderWidth: 1,
              borderRadius: 5,
            }],
          },
          options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
              y: {
                beginAtZero: true,
                ticks: { color: textColor },
                grid: { color: gridColor },
                border: { display: false },
              },
              x: {
                ticks: { color: textColor },
                grid: { display: false },
                border: { display: false },
              },
            },
            plugins: {
              legend: { labels: { color: textColor, usePointStyle: true, pointStyle: 'rect' } },
            },
          },
        });
      }
    })();
  </script>
@endsection
