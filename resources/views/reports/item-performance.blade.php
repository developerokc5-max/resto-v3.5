@extends('layout')

@section('title', 'Item Performance - HawkerOps')

@section('page-title', 'Item Performance')
@section('page-description', 'Analyze which items go offline most frequently')

@section('content')
  <!-- Summary Stats -->
  <section class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 mb-1">Total Items Tracked</div>
      <div class="text-3xl font-bold text-slate-900">{{ $itemStats['total'] ?? '2,450' }}</div>
      <div class="text-xs text-slate-600 mt-1">Across 46 stores</div>
    </div>
    <div class="bg-red-50 border-2 border-red-200 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-red-700 font-medium mb-1">Frequently Offline</div>
      <div class="text-3xl font-bold text-red-900">{{ $itemStats['frequent_offline'] ?? '47' }}</div>
      <div class="text-xs text-red-600 mt-1">≥5 times this week</div>
    </div>
    <div class="bg-green-50 border-2 border-green-200 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-green-700 font-medium mb-1">Always Available</div>
      <div class="text-3xl font-bold text-green-900">{{ $itemStats['always_on'] ?? '2,103' }}</div>
      <div class="text-xs text-green-600 mt-1">100% uptime</div>
    </div>
    <div class="bg-amber-50 border-2 border-amber-200 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-amber-700 font-medium mb-1">Occasionally Offline</div>
      <div class="text-3xl font-bold text-amber-900">{{ $itemStats['sometimes_off'] ?? '300' }}</div>
      <div class="text-xs text-amber-600 mt-1">1-4 times this week</div>
    </div>
  </section>

  <!-- Most Frequently Offline Items -->
  <section class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Most Frequently Offline Items (Last 7 Days)</h2>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="border-b-2 border-slate-200">
            <th class="text-left py-3 px-4 text-sm font-semibold text-slate-900">Rank</th>
            <th class="text-left py-3 px-4 text-sm font-semibold text-slate-900">Item Name</th>
            <th class="text-left py-3 px-4 text-sm font-semibold text-slate-900">Store</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-slate-900">Times Offline</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-slate-900">Platforms Affected</th>
            <th class="text-right py-3 px-4 text-sm font-semibold text-slate-900">Avg Duration</th>
          </tr>
        </thead>
        <tbody>
          @forelse($topOfflineItems as $index => $item)
          <tr class="border-b border-slate-100 hover:bg-slate-50">
            <td class="py-3 px-4 text-sm">
              <span class="font-bold text-slate-900">#{{ $index + 1 }}</span>
            </td>
            <td class="py-3 px-4">
              <div class="font-medium text-slate-900">{{ $item->name }}</div>
              <div class="text-xs text-slate-500">{{ ucfirst($item->platform) }}</div>
            </td>
            <td class="py-3 px-4 text-sm text-slate-700">{{ $item->shop_name }}</td>
            <td class="py-3 px-4 text-center">
              <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-bold">{{ $item->offline_count }}</span>
            </td>
            <td class="py-3 px-4 text-center text-sm">
              <span class="w-6 h-6 rounded text-white text-xs flex items-center justify-center"
                style="background-color: {{ $item->platform === 'grab' ? '#22c55e' : ($item->platform === 'foodpanda' ? '#ec4899' : '#06b6d4') }}">
                {{ strtoupper(substr($item->platform, 0, 1)) }}
              </span>
            </td>
            <td class="py-3 px-4 text-right text-sm text-slate-700">-</td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="py-6 text-center text-slate-500">No offline items found</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <!-- Category Performance -->
  <section class="bg-white rounded-2xl shadow-sm p-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Performance by Category</h2>

    @if($categoryData->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      @foreach($categoryData as $category => $data)
      <div class="border-2 border-slate-200 rounded-xl p-4 hover:border-slate-300 transition">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-bold text-slate-900">{{ $category ?? 'Uncategorized' }}</h3>
          <span class="text-2xl">🍱</span>
        </div>
        <div class="space-y-2">
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-600">Total Items</span>
            <span class="font-bold text-slate-900">{{ $data->total_items }}</span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-600">Avg Availability</span>
            <span class="font-bold text-green-700">{{ $data->availability_percentage }}%</span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-600">Offline Now</span>
            <span class="font-bold text-red-700">{{ $data->offline_count }}</span>
          </div>
        </div>
      </div>
      @endforeach
    </div>
    @else
    <div class="text-center py-8 text-slate-500">
      <p>No category data available. Items table may be empty.</p>
    </div>
    @endif
  </section>
@endsection
