@extends('layout')

@section('title', 'Daily Trends - HawkerOps')
@section('page-title', 'Daily Trends')
@section('page-description', 'Track daily platform uptime and offline item trends')

@section('extra-head')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endsection

@section('content')

  {{-- Date Range Filter --}}
  <section class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm p-4 md:p-5">
    <form method="GET" action="/reports/daily-trends"
          class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wide">Date Range</h2>
      <div class="flex flex-wrap items-center gap-2">
        <input type="date" name="start" value="{{ $startDate }}"
               class="flex-1 sm:flex-none px-3 py-2 text-sm border border-slate-300 dark:border-slate-600
                      dark:bg-slate-700 dark:text-slate-100 rounded-xl focus:ring-2 focus:ring-slate-400
                      focus:border-transparent transition">
        <span class="text-slate-400 dark:text-slate-500 text-sm font-medium">→</span>
        <input type="date" name="end" value="{{ $endDate }}"
               class="flex-1 sm:flex-none px-3 py-2 text-sm border border-slate-300 dark:border-slate-600
                      dark:bg-slate-700 dark:text-slate-100 rounded-xl focus:ring-2 focus:ring-slate-400
                      focus:border-transparent transition">
        <button type="submit"
                class="w-full sm:w-auto px-5 py-2 bg-slate-900 dark:bg-slate-600 text-white rounded-xl
                       text-sm font-medium hover:opacity-90 transition">
          Apply
        </button>
      </div>
    </form>
  </section>

  {{-- Summary Stats --}}
  <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2">Avg Uptime</div>
      <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $trends['avg_uptime'] }}%</div>
      <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Across all platforms now</div>
    </div>
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2">Items Offline</div>
      <div class="text-3xl font-bold {{ $trends['avg_offline'] > 0 ? 'text-red-500' : 'text-green-600 dark:text-green-400' }}">
        {{ number_format($trends['avg_offline']) }}
      </div>
      <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Across all stores now</div>
    </div>
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2">Peak Hour Today</div>
      <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $trends['peak_hour'] }}</div>
      <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Busiest offline hour (SGT)</div>
    </div>
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-xs font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 mb-2">Events Today</div>
      <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($trends['incidents']) }}</div>
      <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">Status log entries (SGT)</div>
    </div>
  </section>

  {{-- Platform Uptime Chart --}}
  <section class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm p-5 md:p-6">
    <div class="flex items-start justify-between mb-5">
      <div>
        <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Platform Uptime Trends</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">% of stores online per platform, per day</p>
      </div>
      <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mt-1">
        <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-0.5 bg-green-600 rounded"></span>Grab</span>
        <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-0.5 bg-pink-500 rounded"></span>FoodPanda</span>
        <span class="flex items-center gap-1.5"><span class="inline-block w-3 h-0.5 bg-sky-500 rounded"></span>Deliveroo</span>
      </div>
    </div>
    @if($platformUptimeData->isEmpty())
      <div class="h-52 flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700">
        <div class="text-3xl mb-2">📊</div>
        <p class="text-sm text-slate-500 dark:text-slate-400">No data for this date range</p>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Snapshots are saved daily — check back tomorrow</p>
      </div>
    @else
      <canvas id="uptimeChart" height="90"></canvas>
    @endif
  </section>

  {{-- Offline Items Chart --}}
  <section class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm p-5 md:p-6">
    <div class="mb-5">
      <h2 class="text-base font-bold text-slate-900 dark:text-slate-100">Offline Items Over Time</h2>
      <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Total menu items unavailable across all stores per day</p>
    </div>
    @if($dailyData->isEmpty())
      <div class="h-52 flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-700">
        <div class="text-3xl mb-2">📈</div>
        <p class="text-sm text-slate-500 dark:text-slate-400">No data for this date range</p>
      </div>
    @else
      <canvas id="offlineChart" height="90"></canvas>
    @endif
  </section>

  @if(!$platformUptimeData->isEmpty() || !$dailyData->isEmpty())
  <script>
  (function () {
    const isDark    = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(148,163,184,0.07)' : 'rgba(203,213,225,0.6)';

    function fmtDate(s) {
      const [y, m, d] = s.split('-');
      const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      return months[parseInt(m, 10) - 1] + ' ' + parseInt(d, 10);
    }

    const baseScaleOpts = {
      ticks: { color: textColor, font: { size: 11 } },
      grid:  { color: gridColor },
      border: { display: false },
    };

    // ── Platform Uptime Line Chart ──────────────────────────────────────────
    const uptimeEl = document.getElementById('uptimeChart');
    if (uptimeEl) {
      const rawLabels     = @json($platformUptimeData->pluck('snapshot_date'));
      const grabData      = @json($platformUptimeData->pluck('grab_uptime'));
      const foodpandaData = @json($platformUptimeData->pluck('foodpanda_uptime'));
      const deliverooData = @json($platformUptimeData->pluck('deliveroo_uptime'));

      const mkDataset = (label, data, color) => ({
        label,
        data,
        borderColor: color,
        backgroundColor: color,
        borderWidth: 2.5,
        pointRadius: rawLabels.length <= 7 ? 5 : 3,
        pointHoverRadius: 7,
        pointBackgroundColor: color,
        tension: 0.35,
        fill: false,
      });

      new Chart(uptimeEl, {
        type: 'line',
        data: {
          labels: rawLabels.map(fmtDate),
          datasets: [
            mkDataset('Grab',      grabData,      '#16a34a'),
            mkDataset('FoodPanda', foodpandaData, '#ec4899'),
            mkDataset('Deliveroo', deliverooData, '#0ea5e9'),
          ],
        },
        options: {
          responsive: true,
          interaction: { mode: 'index', intersect: false },
          scales: {
            y: {
              ...baseScaleOpts,
              min: 0, max: 100,
              ticks: { ...baseScaleOpts.ticks, stepSize: 25, callback: v => v + '%' },
            },
            x: { ...baseScaleOpts, grid: { display: false } },
          },
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: isDark ? '#1e293b' : '#fff',
              titleColor: isDark ? '#f1f5f9' : '#0f172a',
              bodyColor: textColor,
              borderColor: isDark ? '#334155' : '#e2e8f0',
              borderWidth: 1,
              padding: 10,
              callbacks: {
                label: ctx => `  ${ctx.dataset.label}:  ${ctx.parsed.y}%`,
              },
            },
          },
        },
      });
    }

    // ── Offline Items Bar Chart ─────────────────────────────────────────────
    const offlineEl = document.getElementById('offlineChart');
    if (offlineEl) {
      const rawLabels   = @json($dailyData->pluck('snapshot_date'));
      const offlineData = @json($dailyData->pluck('total_offline'));

      new Chart(offlineEl, {
        type: 'bar',
        data: {
          labels: rawLabels.map(fmtDate),
          datasets: [{
            label: 'Offline Items',
            data: offlineData,
            backgroundColor: 'rgba(239,68,68,0.5)',
            borderColor: '#ef4444',
            borderWidth: 1.5,
            borderRadius: 6,
            borderSkipped: false,
          }],
        },
        options: {
          responsive: true,
          interaction: { mode: 'index', intersect: false },
          scales: {
            y: {
              ...baseScaleOpts,
              beginAtZero: true,
              ticks: { ...baseScaleOpts.ticks, callback: v => v.toLocaleString() },
            },
            x: { ...baseScaleOpts, grid: { display: false } },
          },
          plugins: {
            legend: { display: false },
            tooltip: {
              backgroundColor: isDark ? '#1e293b' : '#fff',
              titleColor: isDark ? '#f1f5f9' : '#0f172a',
              bodyColor: textColor,
              borderColor: isDark ? '#334155' : '#e2e8f0',
              borderWidth: 1,
              padding: 10,
              callbacks: {
                label: ctx => `  Offline Items:  ${ctx.parsed.y.toLocaleString()}`,
              },
            },
          },
        },
      });
    }
  })();
  </script>
  @endif

@endsection
