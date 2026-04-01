@extends('layout')

@section('title', 'Downtime Leaderboard - HawkerOps')
@section('page-title', 'Downtime Leaderboard')
@section('page-description', 'Stores ranked by total downtime in the last 30 days')

@section('content')

{{-- KPI row --}}
<section class="grid grid-cols-2 md:grid-cols-4 gap-4">
  <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
    <div class="text-sm text-slate-500 dark:text-slate-400">Stores Affected</div>
    <div class="mt-2 text-3xl font-semibold text-red-600 dark:text-red-400">{{ $storesAffected }}</div>
    <div class="text-xs text-slate-400 dark:text-slate-500 mt-1">had downtime in 30d</div>
  </div>
  <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
    <div class="text-sm text-slate-500 dark:text-slate-400">Total Incidents</div>
    <div class="mt-2 text-3xl font-semibold text-slate-800 dark:text-slate-100">{{ $totalIncidents }}</div>
    <div class="text-xs text-slate-400 dark:text-slate-500 mt-1">offline events</div>
  </div>
  <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
    <div class="text-sm text-slate-500 dark:text-slate-400">Total Downtime</div>
    <div class="mt-2 text-3xl font-semibold text-slate-800 dark:text-slate-100">{{ $totalDowntimeFormatted }}</div>
    <div class="text-xs text-slate-400 dark:text-slate-500 mt-1">across all stores</div>
  </div>
  <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
    <div class="text-sm text-slate-500 dark:text-slate-400">Avg per Incident</div>
    <div class="mt-2 text-3xl font-semibold text-slate-800 dark:text-slate-100">{{ $avgIncidentFormatted }}</div>
    <div class="text-xs text-slate-400 dark:text-slate-500 mt-1">mean downtime</div>
  </div>
</section>

{{-- Table --}}
<div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden">
  <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
    <h2 class="font-bold text-slate-900 dark:text-slate-100">Stores by Downtime (last 30 days)</h2>
    <span class="text-xs text-slate-400 dark:text-slate-500">Resolved incidents only</span>
  </div>

  @if($leaderboard->isEmpty())
    <div class="px-6 py-12 text-center text-slate-400 dark:text-slate-500">
      <div class="text-4xl mb-3">✅</div>
      <div class="font-semibold">No incidents in the last 30 days</div>
    </div>
  @else
  <table class="w-full text-sm">
    <thead class="bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700">
      <tr>
        <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide w-8">#</th>
        <th class="text-left px-6 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Store</th>
        <th class="text-right px-6 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide hidden sm:table-cell">Incidents</th>
        <th class="text-right px-6 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide">Total Downtime</th>
        <th class="text-right px-6 py-3 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide hidden md:table-cell">Longest</th>
        <th class="px-6 py-3 hidden lg:table-cell"></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
      @foreach($leaderboard as $i => $row)
      @php
        $rank = $i + 1;
        $rankColor = $rank === 1 ? 'text-red-600 dark:text-red-400 font-bold' : ($rank === 2 ? 'text-amber-600 dark:text-amber-400 font-bold' : ($rank === 3 ? 'text-amber-500 dark:text-amber-400' : 'text-slate-400 dark:text-slate-500'));
        $barPct = $leaderboard->first()->total_downtime > 0 ? round($row->total_downtime / $leaderboard->first()->total_downtime * 100) : 0;
        $mins = (int) $row->total_downtime;
        $dur = $mins >= 60 ? floor($mins/60).'h '.($mins%60).'m' : $mins.'m';
        $lmins = (int) $row->max_downtime;
        $ldur = $lmins >= 60 ? floor($lmins/60).'h '.($lmins%60).'m' : $lmins.'m';
      @endphp
      <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition">
        <td class="px-6 py-4 {{ $rankColor }} text-sm">{{ $rank }}</td>
        <td class="px-6 py-4">
          <a href="/store/{{ $row->shop_id }}" class="font-semibold text-slate-900 dark:text-slate-100 hover:text-slate-600 dark:hover:text-slate-300 transition">{{ $row->store_name }}</a>
        </td>
        <td class="px-6 py-4 text-right text-slate-600 dark:text-slate-400 hidden sm:table-cell">{{ $row->incident_count }}×</td>
        <td class="px-6 py-4 text-right font-semibold {{ $rank <= 3 ? 'text-red-600 dark:text-red-400' : 'text-slate-700 dark:text-slate-300' }}">{{ $dur }}</td>
        <td class="px-6 py-4 text-right text-slate-500 dark:text-slate-400 hidden md:table-cell">{{ $ldur }}</td>
        <td class="px-6 py-4 hidden lg:table-cell">
          <div class="w-32 bg-slate-100 dark:bg-slate-700 rounded-full h-1.5">
            <div class="h-1.5 rounded-full {{ $rank <= 3 ? 'bg-red-500' : 'bg-amber-400' }}" style="width:{{ $barPct }}%"></div>
          </div>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif
</div>

@endsection
