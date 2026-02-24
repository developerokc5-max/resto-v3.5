@extends('layout')

@section('title', 'Stores - HawkerOps')
@section('page-title', 'All Stores')
@section('page-description', 'Manage and monitor all {{ count($stores ?? []) }} store locations')

@section('top-actions')
<div class="hidden sm:flex items-center bg-slate-100 dark:bg-slate-800 rounded-xl px-3 py-2">
  <input id="storeSearch" class="bg-transparent outline-none text-sm w-64 dark:text-slate-100 dark:placeholder-slate-400" placeholder="Search store..." onkeyup="searchTable()" />
</div>
@endsection

@section('content')

  <!-- Store List -->
  <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-slate-50 dark:bg-slate-900 border-b dark:border-slate-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Store</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Shop ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Status</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Items</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Items OFF</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Last Sync</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700" id="storeTableBody">
          @forelse($stores ?? [] as $store)
          <tr class="hover:bg-slate-50 dark:hover:bg-slate-700 transition store-row" data-name="{{ strtolower($store['store']) }}">
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="font-medium text-slate-900 dark:text-slate-100">{{ $store['store'] }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-mono text-slate-500 dark:text-slate-400">{{ $store['shop_id'] }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              @if($store['status'] === 'all_online')
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-700">
                  ✓ {{ $store['status_text'] }}
                </span>
              @elseif($store['status'] === 'all_offline')
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-700">
                  ✕ {{ $store['status_text'] }}
                </span>
              @else
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-700">
                  ⚠ {{ $store['status_text'] }}
                </span>
              @endif
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm text-slate-900 dark:text-slate-100">{{ $store['total_items'] ?? 0 }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-sm font-semibold {{ $store['items_off'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-400 dark:text-slate-500' }}">
                {{ $store['items_off'] }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <div class="text-xs text-slate-500 dark:text-slate-400">{{ $store['last_change'] ?? '—' }}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <a href="/store/{{ $store['shop_id'] }}" class="text-slate-900 dark:text-slate-100 hover:text-slate-700 dark:hover:text-slate-300 font-medium">
                View →
              </a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
              No stores found. Run sync to load data.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

@endsection

@section('extra-scripts')
<script>
  function searchTable() {
    const filter = document.getElementById('storeSearch').value.toLowerCase();
    document.querySelectorAll('.store-row').forEach(row => {
      const name = row.getAttribute('data-name') || '';
      row.style.display = name.includes(filter) ? '' : 'none';
    });
  }
</script>
@endsection
