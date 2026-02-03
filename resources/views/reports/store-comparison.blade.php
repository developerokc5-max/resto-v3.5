@extends('layout')

@section('title', 'Store Comparison - HawkerOps')

@section('page-title', 'Store Comparison')
@section('page-description', 'Compare performance across all stores')

@section('content')
  <!-- Header Stats -->
  <section class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-bold text-slate-900">Store Health Overview</h2>
      <span class="text-sm text-slate-500">Last Updated: {{ $lastSync }}</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <!-- Total Stores -->
      <div class="bg-slate-50 border-2 border-slate-200 rounded-xl p-4">
        <div class="text-sm text-slate-600 mb-1">Total Stores</div>
        <div class="text-3xl font-bold text-slate-900">{{ $allStoresData->count() ?? 0 }}</div>
      </div>

      <!-- Healthy Stores -->
      <div class="bg-green-50 border-2 border-green-200 rounded-xl p-4">
        <div class="text-sm text-green-700 font-medium mb-1">Healthy (All Online)</div>
        <div class="text-3xl font-bold text-green-900">
          {{ $allStoresData->where('overall_status', 'All Online')->count() ?? 0 }}
        </div>
      </div>

      <!-- Mixed Status -->
      <div class="bg-amber-50 border-2 border-amber-200 rounded-xl p-4">
        <div class="text-sm text-amber-700 font-medium mb-1">Warning (Mixed)</div>
        <div class="text-3xl font-bold text-amber-900">
          {{ $allStoresData->where('overall_status', 'Mixed')->count() ?? 0 }}
        </div>
      </div>

      <!-- Offline Stores -->
      <div class="bg-red-50 border-2 border-red-200 rounded-xl p-4">
        <div class="text-sm text-red-700 font-medium mb-1">Critical (All Offline)</div>
        <div class="text-3xl font-bold text-red-900">
          {{ $allStoresData->where('overall_status', 'All Offline')->count() ?? 0 }}
        </div>
      </div>
    </div>
  </section>

  <!-- Store Comparison Table -->
  <section class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Performance Comparison (All Stores)</h2>

    @if($allStoresData->count() > 0)
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="border-b-2 border-slate-200">
            <th class="text-left py-3 px-4 text-sm font-semibold text-slate-900">Store</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-slate-900">Overall Status</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-slate-900">Platforms Online</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-slate-900">Total Items</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-slate-900">Offline Items</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-slate-900">Availability %</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-slate-900">7-Day Uptime</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-slate-900">Incidents (7d)</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-slate-900">Last Sync</th>
          </tr>
        </thead>
        <tbody>
          @foreach($allStoresData as $store)
          <tr class="border-b border-slate-100 hover:bg-slate-50">
            <!-- Store Name -->
            <td class="py-3 px-4 text-sm font-medium text-slate-900">{{ $store['shop_name'] }}</td>

            <!-- Overall Status -->
            <td class="py-3 px-4 text-center">
              @if($store['status_color'] === 'green')
                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold">
                  ✅ {{ $store['overall_status'] }}
                </span>
              @elseif($store['status_color'] === 'amber')
                <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">
                  ⚠️ {{ $store['overall_status'] }}
                </span>
              @else
                <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold">
                  ❌ {{ $store['overall_status'] }}
                </span>
              @endif
            </td>

            <!-- Platforms Online -->
            <td class="py-3 px-4 text-center text-sm font-bold">{{ $store['platforms_online'] }}/3</td>

            <!-- Total Items -->
            <td class="py-3 px-4 text-center text-sm">{{ $store['total_items'] }}</td>

            <!-- Offline Items -->
            <td class="py-3 px-4 text-center">
              <span class="text-sm font-bold {{ $store['offline_items'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ $store['offline_items'] }}
              </span>
            </td>

            <!-- Availability % -->
            <td class="py-3 px-4 text-center">
              <span class="text-sm font-bold {{ $store['availability_percent'] >= 95 ? 'text-green-600' : ($store['availability_percent'] >= 85 ? 'text-amber-600' : 'text-red-600') }}">
                {{ $store['availability_percent'] }}%
              </span>
            </td>

            <!-- 7-Day Uptime -->
            <td class="py-3 px-4 text-center text-sm">{{ $store['uptime_percent'] }}%</td>

            <!-- Incidents -->
            <td class="py-3 px-4 text-center text-sm font-bold">{{ $store['incidents_7d'] }}</td>

            <!-- Last Sync -->
            <td class="py-3 px-4 text-center text-xs text-slate-600">{{ $store['last_sync'] }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @else
    <div class="text-center py-8 text-slate-500">
      <p>No store data available yet.</p>
    </div>
    @endif
  </section>

  <!-- Platform Status Breakdown -->
  @if($allStoresData->count() > 0)
  <section class="mb-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Platform Status by Store</h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      @foreach($allStoresData as $store)
      <div class="bg-white border-2 border-slate-200 rounded-2xl p-6 shadow-sm">
        <h3 class="font-bold text-slate-900 mb-4 text-center">{{ $store['shop_name'] }}</h3>

        <div class="space-y-3">
          <!-- Grab -->
          <div class="flex items-center justify-between p-3 {{ $store['grab_status'] === 'ONLINE' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }} rounded-lg">
            <span class="text-sm font-medium text-slate-900">Grab</span>
            <span class="px-3 py-1 {{ $store['grab_status'] === 'ONLINE' ? 'bg-green-600 text-white' : 'bg-red-600 text-white' }} rounded-full text-xs font-bold">
              {{ $store['grab_status'] }}
            </span>
          </div>

          <!-- FoodPanda -->
          <div class="flex items-center justify-between p-3 {{ $store['foodpanda_status'] === 'ONLINE' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }} rounded-lg">
            <span class="text-sm font-medium text-slate-900">FoodPanda</span>
            <span class="px-3 py-1 {{ $store['foodpanda_status'] === 'ONLINE' ? 'bg-green-600 text-white' : 'bg-red-600 text-white' }} rounded-full text-xs font-bold">
              {{ $store['foodpanda_status'] }}
            </span>
          </div>

          <!-- Deliveroo -->
          <div class="flex items-center justify-between p-3 {{ $store['deliveroo_status'] === 'ONLINE' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }} rounded-lg">
            <span class="text-sm font-medium text-slate-900">Deliveroo</span>
            <span class="px-3 py-1 {{ $store['deliveroo_status'] === 'ONLINE' ? 'bg-green-600 text-white' : 'bg-red-600 text-white' }} rounded-full text-xs font-bold">
              {{ $store['deliveroo_status'] }}
            </span>
          </div>
        </div>

        <!-- Store Stats -->
        <div class="mt-4 pt-4 border-t border-slate-200 space-y-2 text-xs">
          <div class="flex items-center justify-between">
            <span class="text-slate-600">Items Available:</span>
            <span class="font-bold text-slate-900">{{ $store['online_items'] }}/{{ $store['total_items'] }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-slate-600">Availability:</span>
            <span class="font-bold {{ $store['availability_percent'] >= 95 ? 'text-green-600' : 'text-amber-600' }}">{{ $store['availability_percent'] }}%</span>
          </div>
        </div>
      </div>
      @endforeach
    </div>
  </section>
  @endif
@endsection
