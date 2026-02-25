@extends('layout')

@section('title', 'Items - HawkerOps')

@section('page-title', 'Menu Items')
@section('page-description', 'Browse all items across delivery platforms')

@section('top-actions')
  {{-- Mobile: ⚡ Sync button next to Reload --}}
  <button id="runSyncBtn" onclick="runSync()"
          class="sm:hidden flex items-center gap-1.5 px-3 py-2 bg-slate-900 dark:bg-slate-700 text-white rounded-xl text-xs font-semibold hover:opacity-90 transition">
    <svg id="syncIcon" class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
    </svg>
    <span id="syncBtnText">⚡ Sync</span>
  </button>
  {{-- Desktop: Last Updated --}}
  <div class="hidden sm:block text-right">
    <div class="text-xs text-slate-500 dark:text-slate-400">Last Updated (SGT)</div>
    <div id="lastUpdateTime" class="text-sm font-semibold text-slate-900 dark:text-slate-100 break-words leading-tight">{{ $lastUpdate ?? 'Never' }}</div>
  </div>
@endsection

{{-- Font Awesome loaded in layout.blade.php --}}

@section('content')
  <!-- Stats Cards -->
  <section class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 dark:text-slate-400">Total Items</div>
      <div class="mt-2 text-3xl font-semibold dark:text-slate-100">{{$stats['total']}}</div>
    </div>
    <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-green-700 dark:text-green-400 font-medium">Available</div>
      <div class="mt-2 text-3xl font-semibold text-green-900 dark:text-green-100">{{$stats['available']}}</div>
    </div>
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 dark:text-slate-400">Restaurants</div>
      <div class="mt-2 text-3xl font-semibold dark:text-slate-100">{{$stats['restaurants']}}</div>
    </div>
    <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500 dark:text-slate-400">Categories</div>
      <div class="mt-2 text-3xl font-semibold dark:text-slate-100">{{count($categories)}}</div>
    </div>
  </section>

  <!-- Filters -->
  <section class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm p-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <input type="text" id="searchInput" placeholder="Search items..."
             class="px-4 py-2 border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 dark:placeholder-slate-400 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
      <select id="restaurantFilter" class="px-4 py-2 border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
        <option value="">All Restaurants</option>
        @foreach($restaurants as $restaurant)
          <option value="{{$restaurant}}" {{request('restaurant') == $restaurant ? 'selected' : ''}}>{{$restaurant}}</option>
        @endforeach
      </select>
      <select id="categoryFilter" class="px-4 py-2 border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
        <option value="">All Categories</option>
        @foreach($categories as $category)
          <option value="{{$category}}">{{$category}}</option>
        @endforeach
      </select>
      <select id="statusFilter" class="px-4 py-2 border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
        <option value="">All Status</option>
        <option value="online">✓ All Online (3/3)</option>
        <option value="partial">⚠ Partial (1–2/3)</option>
        <option value="offline">✕ All Offline (0/3)</option>
      </select>
    </div>
  </section>

  <!-- Items Table -->
  <section class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm overflow-hidden border dark:border-slate-700">

    {{-- ── MOBILE: card list (hidden on md+) ───────────────────────── --}}
    <div id="itemsTableMobile" class="md:hidden divide-y divide-slate-100 dark:divide-slate-700">
      @foreach($items as $item)
      @php
        $oc = 0;
        if ($item['platforms']['grab']) $oc++;
        if ($item['platforms']['foodpanda']) $oc++;
        if ($item['platforms']['deliveroo']) $oc++;
      @endphp
      <div class="px-4 py-3 item-row"
           data-name="{{strtolower($item['name'])}}"
           data-restaurant="{{$item['shop_name']}}"
           data-category="{{$item['category']}}"
           data-status="{{ $oc === 3 ? 'online' : ($oc > 0 ? 'partial' : 'offline') }}">
        <div class="flex items-center gap-3">
          @if($item['image_url'])
            <img src="{{$item['image_url']}}" alt="{{$item['name']}}"
                 class="w-14 h-14 object-cover rounded-xl flex-shrink-0"
                 loading="lazy"
                 onerror="this.style.display='none'">
          @else
            <div class="w-14 h-14 bg-slate-100 dark:bg-slate-700 rounded-xl flex items-center justify-center flex-shrink-0">
              <i class="fas fa-utensils text-slate-400 dark:text-slate-500 text-base"></i>
            </div>
          @endif
          <div class="flex-1 min-w-0">
            <div class="font-medium text-sm text-slate-900 dark:text-slate-100 truncate">{{$item['name']}}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400 truncate">{{$item['shop_name']}}</div>
            <div class="flex items-center gap-2 mt-1 flex-wrap">
              <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">${{number_format($item['price'], 2)}}</span>
              <span class="text-[10px] bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400 px-2 py-0.5 rounded-full">{{$item['category']}}</span>
            </div>
          </div>
          <div class="flex-shrink-0 flex flex-col items-end gap-1">
            <span class="text-xs font-medium px-2 py-0.5 rounded-full
              {{ $oc === 3 ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400'
                 : ($oc > 0 ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400'
                 : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400') }}">
              {{$oc}}/3
            </span>
            <div class="flex gap-1">
              <span class="text-[9px] px-1.5 py-0.5 rounded font-bold {{ $item['platforms']['grab'] ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' }}">G</span>
              <span class="text-[9px] px-1.5 py-0.5 rounded font-bold {{ $item['platforms']['foodpanda'] ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' }}">F</span>
              <span class="text-[9px] px-1.5 py-0.5 rounded font-bold {{ $item['platforms']['deliveroo'] ? 'bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400' }}">D</span>
            </div>
          </div>
        </div>
      </div>
      @endforeach
    </div>

    {{-- ── DESKTOP: full table (hidden on mobile) ──────────────────── --}}
    <div class="hidden md:block overflow-x-auto">
      <table class="w-full">
        <thead class="bg-slate-50 dark:bg-slate-900 border-b dark:border-slate-700">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Item</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Restaurant</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Category</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Price</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Grab</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">FoodPanda</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Deliveroo</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Status</th>
          </tr>
        </thead>
        <tbody id="itemsTable" class="divide-y divide-slate-100 dark:divide-slate-700">
          @foreach($items as $item)
          @php $onlineCount = (int)$item['platforms']['grab'] + (int)$item['platforms']['foodpanda'] + (int)$item['platforms']['deliveroo']; @endphp
          <tr class="hover:bg-slate-50 dark:hover:bg-slate-700 transition item-row"
              data-name="{{strtolower($item['name'])}}"
              data-restaurant="{{$item['shop_name']}}"
              data-category="{{$item['category']}}"
              data-status="{{ $onlineCount === 3 ? 'online' : ($onlineCount > 0 ? 'partial' : 'offline') }}">
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                @if($item['image_url'])
                  <img src="{{$item['image_url']}}" alt="{{$item['name']}}"
                       class="w-14 h-14 object-cover rounded-xl"
                       loading="lazy"
                       onerror="this.src='https://via.placeholder.com/56?text=No+Image'">
                @else
                  <div class="w-14 h-14 bg-slate-200 dark:bg-slate-700 rounded-xl flex items-center justify-center">
                    <i class="fas fa-utensils text-slate-400 dark:text-slate-500"></i>
                  </div>
                @endif
                <div class="font-medium text-slate-900 dark:text-slate-100">{{$item['name']}}</div>
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-300">{{$item['shop_name']}}</td>
            <td class="px-6 py-4">
              <span class="inline-block bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-medium px-3 py-1 rounded-full">
                {{$item['category']}}
              </span>
            </td>
            <td class="px-6 py-4 text-sm font-semibold text-slate-900 dark:text-slate-100">${{number_format($item['price'], 2)}}</td>
            <td class="px-6 py-4 text-center">
              @if($item['platforms']['grab'])
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-700">
                  <i class="fas fa-check-circle"></i> ONLINE
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-700">
                  <i class="fas fa-times-circle"></i> OFFLINE
                </span>
              @endif
            </td>
            <td class="px-6 py-4 text-center">
              @if($item['platforms']['foodpanda'])
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-700">
                  <i class="fas fa-check-circle"></i> ONLINE
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-700">
                  <i class="fas fa-times-circle"></i> OFFLINE
                </span>
              @endif
            </td>
            <td class="px-6 py-4 text-center">
              @if($item['platforms']['deliveroo'])
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-700">
                  <i class="fas fa-check-circle"></i> ONLINE
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-700">
                  <i class="fas fa-times-circle"></i> OFFLINE
                </span>
              @endif
            </td>
            <td class="px-6 py-4 text-center">
              @php
                $onlineCount = 0;
                if ($item['platforms']['grab']) $onlineCount++;
                if ($item['platforms']['foodpanda']) $onlineCount++;
                if ($item['platforms']['deliveroo']) $onlineCount++;
              @endphp
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                {{ $onlineCount === 3
                    ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400'
                    : ($onlineCount > 0
                        ? 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400'
                        : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400') }}">
                {{$onlineCount}}/3 platforms
              </span>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    @if($totalPages > 1)
    <div class="flex items-center justify-between px-6 py-4 border-t dark:border-slate-700">
      <div class="text-sm text-slate-600 dark:text-slate-400">
        Showing {{($currentPage - 1) * $perPage + 1}} - {{min($currentPage * $perPage, $totalItems)}} of {{$totalItems}} items
      </div>
      <div class="flex items-center gap-2">
        @php
          // Build query string to preserve filters
          $queryParams = request()->except('page');
          $queryString = http_build_query($queryParams);
          $queryPrefix = $queryString ? '&' : '';
        @endphp

        @if($currentPage > 1)
          <a href="?page={{$currentPage - 1}}{{$queryPrefix}}{{$queryString}}" class="px-4 py-2 border border-slate-300 dark:border-slate-600 dark:text-slate-300 rounded-xl text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">
            Previous
          </a>
        @else
          <span class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-400 dark:text-slate-600 cursor-not-allowed">
            Previous
          </span>
        @endif

        <div class="flex items-center gap-1">
          @php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
          @endphp

          @if($startPage > 1)
            <a href="?page=1{{$queryPrefix}}{{$queryString}}" class="px-3 py-2 border border-slate-300 dark:border-slate-600 dark:text-slate-300 rounded-lg text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition">1</a>
            @if($startPage > 2)
              <span class="px-2 text-slate-400 dark:text-slate-500">...</span>
            @endif
          @endif

          @for($i = $startPage; $i <= $endPage; $i++)
            @if($i == $currentPage)
              <span class="px-3 py-2 bg-slate-900 dark:bg-slate-600 text-white rounded-lg text-sm font-medium">{{$i}}</span>
            @else
              <a href="?page={{$i}}{{$queryPrefix}}{{$queryString}}" class="px-3 py-2 border border-slate-300 dark:border-slate-600 dark:text-slate-300 rounded-lg text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition">{{$i}}</a>
            @endif
          @endfor

          @if($endPage < $totalPages)
            @if($endPage < $totalPages - 1)
              <span class="px-2 text-slate-400 dark:text-slate-500">...</span>
            @endif
            <a href="?page={{$totalPages}}{{$queryPrefix}}{{$queryString}}" class="px-3 py-2 border border-slate-300 dark:border-slate-600 dark:text-slate-300 rounded-lg text-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition">{{$totalPages}}</a>
          @endif
        </div>

        @if($currentPage < $totalPages)
          <a href="?page={{$currentPage + 1}}{{$queryPrefix}}{{$queryString}}" class="px-4 py-2 border border-slate-300 dark:border-slate-600 dark:text-slate-300 rounded-xl text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition">
            Next
          </a>
        @else
          <span class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-400 dark:text-slate-600 cursor-not-allowed">
            Next
          </span>
        @endif
      </div>
    </div>
    @endif
  </section>
@endsection

@section('extra-scripts')
<script>
  const searchInput      = document.getElementById('searchInput');
  const restaurantFilter = document.getElementById('restaurantFilter');
  const categoryFilter   = document.getElementById('categoryFilter');
  const statusFilter     = document.getElementById('statusFilter');
  const rows = document.querySelectorAll('.item-row');

  function filterItems() {
    const searchTerm       = searchInput.value.toLowerCase();
    const selectedCategory = categoryFilter.value;
    const selectedStatus   = statusFilter.value;
    rows.forEach(row => {
      const matchesSearch   = row.dataset.name.includes(searchTerm)
                           || row.dataset.restaurant.toLowerCase().includes(searchTerm)
                           || row.dataset.category.toLowerCase().includes(searchTerm);
      const matchesCategory = !selectedCategory || row.dataset.category === selectedCategory;
      const matchesStatus   = !selectedStatus   || row.dataset.status   === selectedStatus;
      row.style.display = (matchesSearch && matchesCategory && matchesStatus) ? '' : 'none';
    });
  }

  searchInput.addEventListener('input', filterItems);
  categoryFilter.addEventListener('change', filterItems);
  statusFilter.addEventListener('change', filterItems);

  restaurantFilter.addEventListener('change', function() {
    const val = this.value;
    window.location.href = val ? '?restaurant=' + encodeURIComponent(val) : '/items';
  });

  function showItemsInfo() {
    const lastUpdate = '{{ $lastUpdate ?? "Never" }}';
    const totalItems = {{ $stats['total'] ?? 0 }};
    const availableItems = {{ $stats['available'] ?? 0 }};
    const restaurants = {{ $stats['restaurants'] ?? 0 }};
    const categories = {{ count($categories ?? []) }};
    const currentPage = {{ $currentPage ?? 1 }};
    const totalPages = {{ $totalPages ?? 1 }};
    const perPage = {{ $perPage ?? 50 }};

    const info = `━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 ITEMS DATABASE INFORMATION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

⏰ Last Updated (SGT):
   ${lastUpdate}

📈 Overall Statistics:
   • Total Unique Items: ${totalItems}
   • Available Items: ${availableItems}
   • Restaurants: ${restaurants}
   • Categories: ${categories}

📄 Pagination:
   • Items per Page: ${perPage}
   • Current Page: ${currentPage} of ${totalPages}
   • Total Pages: ${totalPages}

🔄 Data Source:
   1. Items scraped from RestoSuite
   2. Grouped by shop + item name
   3. Shows multi-platform availability
   4. Real-time database query

💡 Features:
   • Search by name/restaurant/category
   • Filter by restaurant or category
   • Platform status (Grab/FoodPanda/Deliveroo)
   • Image preview for 99.8% of items

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━`;

    alert(info);
  }

  // Run Sync Functionality
  async function runSync() {
    const btn = document.getElementById('runSyncBtn');
    const btnText = document.getElementById('syncBtnText');
    const syncIcon = document.getElementById('syncIcon');

    // Disable button and show loading state
    btn.disabled = true;
    btn.classList.add('opacity-75', 'cursor-not-allowed');
    btnText.textContent = 'Syncing...';
    syncIcon.classList.add('animate-spin');

    try {
      const response = await fetch('/api/v1/items/sync', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      });

      const data = await response.json();

      if (data.success) {
        // Show success message
        btnText.textContent = 'Sync Complete!';
        syncIcon.classList.remove('animate-spin');
        btn.classList.remove('bg-slate-900', 'hover:bg-slate-800');
        btn.classList.add('bg-green-600');

        // Show success notification
        showNotification('✅ Items sync completed successfully! Reloading page...', 'success');

        // Reload page after 2 seconds to show updated data
        setTimeout(() => {
          window.location.reload();
        }, 2000);
      } else {
        throw new Error(data.message || 'Sync failed');
      }
    } catch (error) {
      console.error('Sync error:', error);

      // Show error state
      btnText.textContent = 'Sync Failed';
      syncIcon.classList.remove('animate-spin');
      btn.classList.remove('bg-slate-900', 'hover:bg-slate-800');
      btn.classList.add('bg-red-600');

      // Show error notification
      showNotification('❌ Sync failed: ' + error.message, 'error');

      // Reset button after 3 seconds
      setTimeout(() => {
        btn.disabled = false;
        btn.classList.remove('opacity-75', 'cursor-not-allowed', 'bg-red-600');
        btn.classList.add('bg-slate-900', 'hover:bg-slate-800');
        btnText.textContent = 'Run Sync';
      }, 3000);
    }
  }

  // Notification function
  function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-xl shadow-2xl font-semibold text-white transform transition-all duration-300 ${
      type === 'success' ? 'bg-green-600' : type === 'error' ? 'bg-red-600' : 'bg-blue-600'
    }`;
    notification.textContent = message;
    notification.style.opacity = '0';
    notification.style.transform = 'translateY(-20px)';

    document.body.appendChild(notification);

    // Animate in
    setTimeout(() => {
      notification.style.opacity = '1';
      notification.style.transform = 'translateY(0)';
    }, 10);

    // Remove after 5 seconds
    setTimeout(() => {
      notification.style.opacity = '0';
      notification.style.transform = 'translateY(-20px)';
      setTimeout(() => notification.remove(), 300);
    }, 5000);
  }
</script>
@endsection
