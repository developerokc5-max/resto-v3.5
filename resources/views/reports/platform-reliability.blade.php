@extends('layout')

@section('title', 'Platform Reliability - HawkerOps')

@section('page-title', 'Platform Reliability')
@section('page-description', 'Compare platform performance and uptime statistics')

@section('content')
  <!-- Platform Comparison -->
  <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Grab -->
    <div class="bg-white dark:bg-slate-800 border-2 border-green-200 dark:border-green-800 rounded-2xl p-4 md:p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 md:w-12 md:h-12 bg-green-600 rounded-xl flex items-center justify-center text-white font-bold text-xl">G</div>
          <div>
            <h3 class="font-bold text-slate-900 dark:text-slate-100">Grab</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Food Delivery</p>
          </div>
        </div>
      </div>

      @php $grab = $platformData['grab'] ?? null; @endphp
      <div class="space-y-3">
        <div>
          <div class="flex items-center justify-between mb-1">
            <span class="text-sm text-slate-600 dark:text-slate-400">Uptime (7 days)</span>
            <span class="text-sm font-bold text-green-700 dark:text-green-400">{{ $grab['uptime'] ?? '99.2' }}%</span>
          </div>
          <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
            <div class="h-full bg-green-600" style="width: {{ $grab['uptime'] ?? '99.2' }}%"></div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 pt-3 border-t dark:border-slate-700">
          <div>
            <div class="text-xs text-slate-500 dark:text-slate-400">Online Stores</div>
            <div class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ $grab['online_stores'] ?? '45' }}/{{ $grab['total_stores'] ?? '46' }}</div>
          </div>
          <div>
            <div class="text-xs text-slate-500 dark:text-slate-400">Status</div>
            <div class="text-lg font-bold {{ $grab['uptime'] >= 98 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
              {{ $grab['uptime'] >= 98 ? '✓ Good' : '✗ Issue' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- FoodPanda -->
    <div class="bg-white dark:bg-slate-800 border-2 border-pink-200 dark:border-pink-800 rounded-2xl p-4 md:p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 md:w-12 md:h-12 bg-pink-600 rounded-xl flex items-center justify-center text-white font-bold text-xl">F</div>
          <div>
            <h3 class="font-bold text-slate-900 dark:text-slate-100">FoodPanda</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Food Delivery</p>
          </div>
        </div>
      </div>

      @php $foodpanda = $platformData['foodpanda'] ?? null; @endphp
      <div class="space-y-3">
        <div>
          <div class="flex items-center justify-between mb-1">
            <span class="text-sm text-slate-600 dark:text-slate-400">Uptime (7 days)</span>
            <span class="text-sm font-bold text-pink-700 dark:text-pink-400">{{ $foodpanda['uptime'] ?? '97.8' }}%</span>
          </div>
          <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
            <div class="h-full bg-pink-600" style="width: {{ $foodpanda['uptime'] ?? '97.8' }}%"></div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 pt-3 border-t dark:border-slate-700">
          <div>
            <div class="text-xs text-slate-500 dark:text-slate-400">Online Stores</div>
            <div class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ $foodpanda['online_stores'] ?? '43' }}/{{ $foodpanda['total_stores'] ?? '46' }}</div>
          </div>
          <div>
            <div class="text-xs text-slate-500 dark:text-slate-400">Status</div>
            <div class="text-lg font-bold {{ $foodpanda['uptime'] >= 98 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
              {{ $foodpanda['uptime'] >= 98 ? '✓ Good' : '⚠ Caution' }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Deliveroo -->
    <div class="bg-white dark:bg-slate-800 border-2 border-cyan-200 dark:border-cyan-800 rounded-2xl p-4 md:p-6 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 md:w-12 md:h-12 bg-cyan-600 rounded-xl flex items-center justify-center text-white font-bold text-xl">D</div>
          <div>
            <h3 class="font-bold text-slate-900 dark:text-slate-100">Deliveroo</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Food Delivery</p>
          </div>
        </div>
      </div>

      @php $deliveroo = $platformData['deliveroo'] ?? null; @endphp
      <div class="space-y-3">
        <div>
          <div class="flex items-center justify-between mb-1">
            <span class="text-sm text-slate-600 dark:text-slate-400">Uptime (7 days)</span>
            <span class="text-sm font-bold text-cyan-700 dark:text-cyan-400">{{ $deliveroo['uptime'] ?? '98.5' }}%</span>
          </div>
          <div class="h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
            <div class="h-full bg-cyan-600" style="width: {{ $deliveroo['uptime'] ?? '98.5' }}%"></div>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3 pt-3 border-t dark:border-slate-700">
          <div>
            <div class="text-xs text-slate-500 dark:text-slate-400">Online Stores</div>
            <div class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ $deliveroo['online_stores'] ?? '44' }}/{{ $deliveroo['total_stores'] ?? '46' }}</div>
          </div>
          <div>
            <div class="text-xs text-slate-500 dark:text-slate-400">Status</div>
            <div class="text-lg font-bold {{ $deliveroo['uptime'] >= 98 ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
              {{ $deliveroo['uptime'] >= 98 ? '✓ Good' : '⚠ Caution' }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Detailed Stats Table -->
  <section class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm p-4 md:p-6">
    <h2 class="text-lg md:text-xl font-bold text-slate-900 dark:text-slate-100 mb-4">Detailed Platform Statistics</h2>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="border-b-2 border-slate-200 dark:border-slate-700">
            <th class="text-left py-3 px-4 text-sm font-semibold text-slate-900 dark:text-slate-100">Metric</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-green-700 dark:text-green-400">Grab</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-pink-700 dark:text-pink-400">FoodPanda</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-cyan-700 dark:text-cyan-400">Deliveroo</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700">
            <td class="py-3 px-4 text-sm text-slate-700 dark:text-slate-300">24h Uptime</td>
            <td class="py-3 px-4 text-sm text-center font-medium">100%</td>
            <td class="py-3 px-4 text-sm text-center font-medium">98.5%</td>
            <td class="py-3 px-4 text-sm text-center font-medium">99.8%</td>
          </tr>
          <tr class="border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700">
            <td class="py-3 px-4 text-sm text-slate-700 dark:text-slate-300">7-day Uptime</td>
            <td class="py-3 px-4 text-sm text-center font-medium">99.2%</td>
            <td class="py-3 px-4 text-sm text-center font-medium">97.8%</td>
            <td class="py-3 px-4 text-sm text-center font-medium">98.5%</td>
          </tr>
          <tr class="border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700">
            <td class="py-3 px-4 text-sm text-slate-700 dark:text-slate-300">30-day Uptime</td>
            <td class="py-3 px-4 text-sm text-center font-medium">98.9%</td>
            <td class="py-3 px-4 text-sm text-center font-medium">97.2%</td>
            <td class="py-3 px-4 text-sm text-center font-medium">98.1%</td>
          </tr>
          <tr class="border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700">
            <td class="py-3 px-4 text-sm text-slate-700 dark:text-slate-300">Avg Downtime/Incident</td>
            <td class="py-3 px-4 text-sm text-center font-medium">12 min</td>
            <td class="py-3 px-4 text-sm text-center font-medium">28 min</td>
            <td class="py-3 px-4 text-sm text-center font-medium">18 min</td>
          </tr>
          <tr class="hover:bg-slate-50 dark:hover:bg-slate-700">
            <td class="py-3 px-4 text-sm text-slate-700 dark:text-slate-300">Total Stores Online</td>
            <td class="py-3 px-4 text-sm text-center font-medium">45/46</td>
            <td class="py-3 px-4 text-sm text-center font-medium">44/46</td>
            <td class="py-3 px-4 text-sm text-center font-medium">46/46</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
@endsection
