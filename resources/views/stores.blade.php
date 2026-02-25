@extends('layout')

@section('title', 'Stores - HawkerOps')
@section('page-title', 'All Stores')
{{-- @section('page-description')Manage and monitor all {{ count($stores ?? []) }} store locations@endsection --}}

@section('top-actions')
<div class="hidden sm:flex items-center bg-slate-100 dark:bg-slate-800 rounded-xl px-3 py-2">
  <input id="storeSearch" class="bg-transparent outline-none text-sm w-64 dark:text-slate-100 dark:placeholder-slate-400" placeholder="Search store..." onkeyup="searchTable()" />
</div>
@endsection

@section('content')

  <!-- Mobile Search (visible on small screens only) -->
  <div class="sm:hidden">
    <div class="flex items-center bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-xl px-3 py-2 shadow-sm">
      <svg class="w-4 h-4 text-slate-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
      </svg>
      <input id="storeSearchMobile" class="bg-transparent outline-none text-sm w-full dark:text-slate-100 dark:placeholder-slate-400" placeholder="Search store..." onkeyup="searchTable()" />
    </div>
  </div>

  <!-- Mobile Card List (hidden on sm+) -->
  <div class="sm:hidden space-y-2" id="storeMobileList">
    @forelse($stores ?? [] as $store)
    @php
      $isOnline  = $store['status'] === 'all_online';
      $isOffline = $store['status'] === 'all_offline';
      $stripe    = $isOnline ? 'bg-emerald-500' : ($isOffline ? 'bg-red-500' : 'bg-amber-400');
      $shortSt   = $isOnline ? '✓ All Online' : ($isOffline ? '✕ All Offline' : '⚠ '.$store['status_text']);
      $stColor   = $isOnline ? 'text-emerald-600 dark:text-emerald-400' : ($isOffline ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400');
    @endphp
    <div class="store-row bg-white dark:bg-slate-800 rounded-2xl shadow-sm overflow-hidden flex"
         data-name="{{ strtolower($store['store']) }}">
      <!-- Status colour stripe -->
      <div class="w-1.5 flex-shrink-0 {{ $stripe }}"></div>
      <!-- Card body -->
      <div class="flex-1 px-4 py-3 min-w-0">
        <div class="flex items-start justify-between gap-3">
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-sm text-slate-900 dark:text-slate-100 leading-snug">{{ $store['store'] }}</div>
            <div class="text-xs font-medium {{ $stColor }} mt-0.5">{{ $shortSt }}</div>
          </div>
          <!-- Items OFF counter -->
          <div class="flex-shrink-0 text-right">
            @if($isOffline)
              {{-- Store fully offline — items data irrelevant --}}
              <div class="text-lg font-bold leading-none text-red-500">✕</div>
              <div class="text-[9px] text-slate-400 dark:text-slate-500 mt-0.5 uppercase tracking-wide">offline</div>
            @elseif($store['items_off'] > 0)
              <div class="text-lg font-bold leading-none text-red-600 dark:text-red-400">{{ $store['items_off'] }}</div>
              <div class="text-[9px] text-slate-400 dark:text-slate-500 mt-0.5 uppercase tracking-wide">items off</div>
            @else
              <div class="text-lg font-bold leading-none text-emerald-500">✓</div>
              <div class="text-[9px] text-slate-400 dark:text-slate-500 mt-0.5 uppercase tracking-wide">all ok</div>
            @endif
          </div>
        </div>
        <!-- Footer row -->
        <div class="flex items-center justify-between mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-700/60">
          <div class="flex items-center gap-1.5 text-[10px] text-slate-400 dark:text-slate-500">
            <span>{{ $store['total_items'] ?? 0 }} items</span>
            <span>·</span>
            <span>{{ $store['last_change'] ?? '—' }}</span>
          </div>
          <a href="/store/{{ $store['shop_id'] }}"
             class="text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white transition-colors">
            View →
          </a>
        </div>
      </div>
    </div>
    @empty
    <div class="px-5 py-10 text-center text-sm text-slate-500 dark:text-slate-400 bg-white dark:bg-slate-800 rounded-2xl">
      No stores found. Run sync to load data.
    </div>
    @endforelse
  </div>

  <!-- Desktop Table (hidden on mobile) -->
  <div class="hidden sm:block bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl overflow-hidden">
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
    // Grab value from whichever search input is active
    const desktopVal = document.getElementById('storeSearch')?.value.toLowerCase() || '';
    const mobileVal  = document.getElementById('storeSearchMobile')?.value.toLowerCase() || '';
    const filter = desktopVal || mobileVal;

    document.querySelectorAll('.store-row').forEach(row => {
      const name = row.getAttribute('data-name') || '';
      row.style.display = name.includes(filter) ? '' : 'none';
    });
  }
</script>
@endsection
