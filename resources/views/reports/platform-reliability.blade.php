@extends('layout')

@section('title', 'Platform Reliability - HawkerOps')

@section('page-title', 'Platform Reliability')
@section('page-description', 'Compare platform performance and uptime statistics')

@section('content')
  <!-- Platform Comparison -->
  <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Grab -->
    <div class="bg-white border-2 border-green-200 rounded-2xl p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-green-600 rounded-xl flex items-center justify-center text-white font-bold text-xl">G</div>
          <div>
            <h3 class="font-bold text-slate-900">Grab</h3>
            <p class="text-xs text-slate-500">Food Delivery</p>
          </div>
        </div>
      </div>

      <div class="space-y-3">
        <div>
          <div class="flex items-center justify-between mb-1">
            <span class="text-sm text-slate-600">Uptime (7 days)</span>
            <span class="text-sm font-bold text-green-700">99.2%</span>
          </div>
          <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
            <div class="h-full bg-green-600" style="width: 99.2%"></div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 pt-3 border-t">
          <div>
            <div class="text-xs text-slate-500">Avg Response</div>
            <div class="text-lg font-bold text-slate-900">1.2s</div>
          </div>
          <div>
            <div class="text-xs text-slate-500">Incidents</div>
            <div class="text-lg font-bold text-slate-900">2</div>
          </div>
        </div>
      </div>
    </div>

    <!-- FoodPanda -->
    <div class="bg-white border-2 border-pink-200 rounded-2xl p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-pink-600 rounded-xl flex items-center justify-center text-white font-bold text-xl">F</div>
          <div>
            <h3 class="font-bold text-slate-900">FoodPanda</h3>
            <p class="text-xs text-slate-500">Food Delivery</p>
          </div>
        </div>
      </div>

      <div class="space-y-3">
        <div>
          <div class="flex items-center justify-between mb-1">
            <span class="text-sm text-slate-600">Uptime (7 days)</span>
            <span class="text-sm font-bold text-pink-700">97.8%</span>
          </div>
          <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
            <div class="h-full bg-pink-600" style="width: 97.8%"></div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 pt-3 border-t">
          <div>
            <div class="text-xs text-slate-500">Avg Response</div>
            <div class="text-lg font-bold text-slate-900">1.8s</div>
          </div>
          <div>
            <div class="text-xs text-slate-500">Incidents</div>
            <div class="text-lg font-bold text-slate-900">5</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Deliveroo -->
    <div class="bg-white border-2 border-cyan-200 rounded-2xl p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 bg-cyan-600 rounded-xl flex items-center justify-center text-white font-bold text-xl">D</div>
          <div>
            <h3 class="font-bold text-slate-900">Deliveroo</h3>
            <p class="text-xs text-slate-500">Food Delivery</p>
          </div>
        </div>
      </div>

      <div class="space-y-3">
        <div>
          <div class="flex items-center justify-between mb-1">
            <span class="text-sm text-slate-600">Uptime (7 days)</span>
            <span class="text-sm font-bold text-cyan-700">98.5%</span>
          </div>
          <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
            <div class="h-full bg-cyan-600" style="width: 98.5%"></div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 pt-3 border-t">
          <div>
            <div class="text-xs text-slate-500">Avg Response</div>
            <div class="text-lg font-bold text-slate-900">1.5s</div>
          </div>
          <div>
            <div class="text-xs text-slate-500">Incidents</div>
            <div class="text-lg font-bold text-slate-900">3</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Detailed Stats Table -->
  <section class="bg-white rounded-2xl shadow-sm p-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Detailed Platform Statistics</h2>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="border-b-2 border-slate-200">
            <th class="text-left py-3 px-4 text-sm font-semibold text-slate-900">Metric</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-green-700">Grab</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-pink-700">FoodPanda</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-cyan-700">Deliveroo</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b border-slate-100">
            <td class="py-3 px-4 text-sm text-slate-700">24h Uptime</td>
            <td class="py-3 px-4 text-sm text-center font-medium">100%</td>
            <td class="py-3 px-4 text-sm text-center font-medium">98.5%</td>
            <td class="py-3 px-4 text-sm text-center font-medium">99.8%</td>
          </tr>
          <tr class="border-b border-slate-100">
            <td class="py-3 px-4 text-sm text-slate-700">7-day Uptime</td>
            <td class="py-3 px-4 text-sm text-center font-medium">99.2%</td>
            <td class="py-3 px-4 text-sm text-center font-medium">97.8%</td>
            <td class="py-3 px-4 text-sm text-center font-medium">98.5%</td>
          </tr>
          <tr class="border-b border-slate-100">
            <td class="py-3 px-4 text-sm text-slate-700">30-day Uptime</td>
            <td class="py-3 px-4 text-sm text-center font-medium">98.9%</td>
            <td class="py-3 px-4 text-sm text-center font-medium">97.2%</td>
            <td class="py-3 px-4 text-sm text-center font-medium">98.1%</td>
          </tr>
          <tr class="border-b border-slate-100">
            <td class="py-3 px-4 text-sm text-slate-700">Avg Downtime/Incident</td>
            <td class="py-3 px-4 text-sm text-center font-medium">12 min</td>
            <td class="py-3 px-4 text-sm text-center font-medium">28 min</td>
            <td class="py-3 px-4 text-sm text-center font-medium">18 min</td>
          </tr>
          <tr class="border-b border-slate-100">
            <td class="py-3 px-4 text-sm text-slate-700">Total Stores Online</td>
            <td class="py-3 px-4 text-sm text-center font-medium">45/46</td>
            <td class="py-3 px-4 text-sm text-center font-medium">44/46</td>
            <td class="py-3 px-4 text-sm text-center font-medium">46/46</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
@endsection
