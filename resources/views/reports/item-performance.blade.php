@extends('layout')

@section('title', 'Item Performance - HawkerOps')

@section('page-title', 'Item Performance')
@section('page-description', 'Analyze which items go offline most frequently')

@section('content')
  <!-- Summary Stats -->
  <section class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 dark:text-slate-400 mb-1">Total Items Tracked</div>
      <div class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $itemStats['total'] ?? '2,450' }}</div>
      <div class="text-xs text-slate-600 dark:text-slate-400 mt-1">Across 46 stores</div>
    </div>
    <div class="bg-red-50 dark:bg-red-900/30 border-2 border-red-200 dark:border-red-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-red-700 dark:text-red-400 font-medium mb-1">Frequently Offline</div>
      <div class="text-3xl font-bold text-red-900 dark:text-red-100">{{ $itemStats['frequent_offline'] ?? '47' }}</div>
      <div class="text-xs text-red-600 dark:text-red-400 mt-1">≥5 times this week</div>
    </div>
    <div class="bg-green-50 dark:bg-green-900/30 border-2 border-green-200 dark:border-green-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-green-700 dark:text-green-400 font-medium mb-1">Always Available</div>
      <div class="text-3xl font-bold text-green-900 dark:text-green-100">{{ $itemStats['always_on'] ?? '2,103' }}</div>
      <div class="text-xs text-green-600 dark:text-green-400 mt-1">100% uptime</div>
    </div>
    <div class="bg-amber-50 dark:bg-amber-900/30 border-2 border-amber-200 dark:border-amber-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-amber-700 dark:text-amber-400 font-medium mb-1">Occasionally Offline</div>
      <div class="text-3xl font-bold text-amber-900 dark:text-amber-100">{{ $itemStats['sometimes_off'] ?? '300' }}</div>
      <div class="text-xs text-amber-600 dark:text-amber-400 mt-1">1-4 times this week</div>
    </div>
  </section>

  <!-- Most Frequently Offline Items -->
  <section class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm p-6">
    <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-4">Most Frequently Offline Items (Last 7 Days)</h2>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="border-b-2 border-slate-200 dark:border-slate-700">
            <th class="text-left py-3 px-4 text-sm font-semibold text-slate-900 dark:text-slate-100">Rank</th>
            <th class="text-left py-3 px-4 text-sm font-semibold text-slate-900 dark:text-slate-100">Item Name</th>
            <th class="text-left py-3 px-4 text-sm font-semibold text-slate-900 dark:text-slate-100">Store</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-slate-900 dark:text-slate-100">Times Offline</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-slate-900 dark:text-slate-100">Platforms Affected</th>
            <th class="text-right py-3 px-4 text-sm font-semibold text-slate-900 dark:text-slate-100">Avg Duration</th>
          </tr>
        </thead>
        <tbody>
          @forelse($topOfflineItems as $index => $item)
          <tr class="border-b border-slate-100 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700">
            <td class="py-3 px-4 text-sm">
              <span class="font-bold text-slate-900 dark:text-slate-100">#{{ $index + 1 }}</span>
            </td>
            <td class="py-3 px-4">
              <div class="font-medium text-slate-900 dark:text-slate-100">{{ $item->name }}</div>
              <div class="text-xs text-slate-500 dark:text-slate-400">{{ ucfirst($item->platform) }}</div>
            </td>
            <td class="py-3 px-4 text-sm text-slate-700 dark:text-slate-300">{{ $item->shop_name }}</td>
            <td class="py-3 px-4 text-center">
              <span class="px-3 py-1 bg-red-100 dark:bg-red-900/40 text-red-700 dark:text-red-400 rounded-full text-sm font-bold">{{ $item->offline_count }}</span>
            </td>
            <td class="py-3 px-4 text-center text-sm">
              <span class="w-6 h-6 rounded text-white text-xs flex items-center justify-center"
                style="background-color: {{ $item->platform === 'grab' ? '#22c55e' : ($item->platform === 'foodpanda' ? '#ec4899' : '#06b6d4') }}">
                {{ strtoupper(substr($item->platform, 0, 1)) }}
              </span>
            </td>
            <td class="py-3 px-4 text-right text-sm text-slate-700 dark:text-slate-300">-</td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="py-6 text-center text-slate-500 dark:text-slate-400">No offline items found</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <!-- Category Performance -->
  <section class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm p-6">
    <h2 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-4">Performance by Category</h2>

    @if($categoryData->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      @foreach($categoryData as $category => $data)
      <div class="border-2 border-slate-200 dark:border-slate-700 rounded-xl p-4 hover:border-slate-300 dark:hover:border-slate-600 transition">
        <div class="flex items-center justify-between mb-3">
          <h3 class="font-bold text-slate-900 dark:text-slate-100">{{ $category ?? 'Uncategorized' }}</h3>
          <span class="text-2xl">🍱</span>
        </div>
        <div class="space-y-2">
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-600 dark:text-slate-400">Total Items</span>
            <span class="font-bold text-slate-900 dark:text-slate-100">{{ $data->total_items }}</span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-600 dark:text-slate-400">Avg Availability</span>
            <span class="font-bold text-green-700 dark:text-green-400">{{ $data->availability_percentage }}%</span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-slate-600 dark:text-slate-400">Offline Now</span>
            <span class="font-bold text-red-700 dark:text-red-400">{{ $data->offline_count }}</span>
          </div>
        </div>
      </div>
      @endforeach
    </div>
    @else
    <div class="text-center py-8 text-slate-500 dark:text-slate-400">
      <p>No category data available. Items table may be empty.</p>
    </div>
    @endif
  </section>
@endsection
