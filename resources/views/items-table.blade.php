@extends('layout')

@section('title', 'Items - HawkerOps')

@section('page-title', 'Menu Items')
@section('page-description', 'Browse all items across delivery platforms')

@section('top-actions')
  <div class="text-right">
    <div class="text-xs text-slate-500">Last Updated (SGT)</div>
    <div id="lastUpdateTime" class="text-sm font-semibold">{{ $lastUpdate ?? 'Never' }}</div>
  </div>
@endsection

@section('extra-head')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endsection

@section('content')
  <!-- Stats Cards -->
  <section class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500">Total Items</div>
      <div class="mt-2 text-3xl font-semibold">{{$stats['total']}}</div>
    </div>
    <div class="bg-green-50 border border-green-200 rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-green-700 font-medium">Available</div>
      <div class="mt-2 text-3xl font-semibold text-green-900">{{$stats['available']}}</div>
    </div>
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500">Restaurants</div>
      <div class="mt-2 text-3xl font-semibold">{{$stats['restaurants']}}</div>
    </div>
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="text-sm text-slate-500">Categories</div>
      <div class="mt-2 text-3xl font-semibold">{{count($categories)}}</div>
    </div>
  </section>

  <!-- Filters -->
  <section class="bg-white rounded-2xl shadow-sm p-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <input type="text" id="searchInput" placeholder="Search items..."
             class="px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
      <select id="restaurantFilter" class="px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
        <option value="">All Restaurants</option>
        @foreach($restaurants as $restaurant)
          <option value="{{$restaurant}}" {{request('restaurant') == $restaurant ? 'selected' : ''}}>{{$restaurant}}</option>
        @endforeach
      </select>
      <select id="categoryFilter" class="px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
        <option value="">All Categories</option>
        @foreach($categories as $category)
          <option value="{{$category}}">{{$category}}</option>
        @endforeach
      </select>
    </div>
  </section>

  <!-- Items Table -->
  <section class="bg-white rounded-2xl shadow-sm overflow-hidden border">
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-slate-50 border-b">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Item</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Restaurant</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Category</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Price</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase">Grab</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase">FoodPanda</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase">Deliveroo</th>
            <th class="px-6 py-3 text-center text-xs font-medium text-slate-500 uppercase">Status</th>
          </tr>
        </thead>
        <tbody id="itemsTable">
          @foreach($items as $item)
          <tr class="border-b hover:bg-slate-50 transition item-row"
              data-name="{{strtolower($item['name'])}}"
              data-restaurant="{{$item['shop_name']}}"
              data-category="{{$item['category']}}">
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                @if($item['image_url'])
                  <img src="{{$item['image_url']}}" alt="{{$item['name']}}"
                       class="w-12 h-12 object-cover rounded-lg"
                       onerror="this.src='https://via.placeholder.com/48?text=No+Image'">
                @else
                  <div class="w-12 h-12 bg-slate-200 rounded-lg flex items-center justify-center">
                    <i class="fas fa-utensils text-slate-400"></i>
                  </div>
                @endif
                <div class="font-medium text-slate-900">{{$item['name']}}</div>
              </div>
            </td>
            <td class="px-6 py-4 text-sm text-slate-600">{{$item['shop_name']}}</td>
            <td class="px-6 py-4">
              <span class="inline-block bg-slate-100 text-slate-700 text-xs font-medium px-3 py-1 rounded-full">
                {{$item['category']}}
              </span>
            </td>
            <td class="px-6 py-4 text-sm font-semibold text-slate-900">${{number_format($item['price'], 2)}}</td>
            <td class="px-6 py-4 text-center">
              @if($item['platforms']['grab'])
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                  <i class="fas fa-check-circle"></i> ONLINE
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">
                  <i class="fas fa-times-circle"></i> OFFLINE
                </span>
              @endif
            </td>
            <td class="px-6 py-4 text-center">
              @if($item['platforms']['foodpanda'])
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                  <i class="fas fa-check-circle"></i> ONLINE
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">
                  <i class="fas fa-times-circle"></i> OFFLINE
                </span>
              @endif
            </td>
            <td class="px-6 py-4 text-center">
              @if($item['platforms']['deliveroo'])
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                  <i class="fas fa-check-circle"></i> ONLINE
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 border border-red-200">
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
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $onlineCount === 3 ? 'bg-green-100 text-green-800' : ($onlineCount > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
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
    <div class="flex items-center justify-between px-6 py-4 border-t">
      <div class="text-sm text-slate-600">
        Showing {{($currentPage - 1) * $perPage + 1}} - {{min($currentPage * $perPage, $totalItems)}} of {{$totalItems}} items
      </div>
      <div class="flex items-center gap-2">
        @if($currentPage > 1)
          <a href="?page={{$currentPage - 1}}" class="px-4 py-2 border border-slate-300 rounded-xl text-sm font-medium hover:bg-slate-50 transition">
            Previous
          </a>
        @else
          <span class="px-4 py-2 border border-slate-200 rounded-xl text-sm font-medium text-slate-400 cursor-not-allowed">
            Previous
          </span>
        @endif

        <div class="flex items-center gap-1">
          @php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
          @endphp

          @if($startPage > 1)
            <a href="?page=1" class="px-3 py-2 border border-slate-300 rounded-lg text-sm hover:bg-slate-50 transition">1</a>
            @if($startPage > 2)
              <span class="px-2 text-slate-400">...</span>
            @endif
          @endif

          @for($i = $startPage; $i <= $endPage; $i++)
            @if($i == $currentPage)
              <span class="px-3 py-2 bg-slate-900 text-white rounded-lg text-sm font-medium">{{$i}}</span>
            @else
              <a href="?page={{$i}}" class="px-3 py-2 border border-slate-300 rounded-lg text-sm hover:bg-slate-50 transition">{{$i}}</a>
            @endif
          @endfor

          @if($endPage < $totalPages)
            @if($endPage < $totalPages - 1)
              <span class="px-2 text-slate-400">...</span>
            @endif
            <a href="?page={{$totalPages}}" class="px-3 py-2 border border-slate-300 rounded-lg text-sm hover:bg-slate-50 transition">{{$totalPages}}</a>
          @endif
        </div>

        @if($currentPage < $totalPages)
          <a href="?page={{$currentPage + 1}}" class="px-4 py-2 border border-slate-300 rounded-xl text-sm font-medium hover:bg-slate-50 transition">
            Next
          </a>
        @else
          <span class="px-4 py-2 border border-slate-200 rounded-xl text-sm font-medium text-slate-400 cursor-not-allowed">
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
  const searchInput = document.getElementById('searchInput');
  const restaurantFilter = document.getElementById('restaurantFilter');
  const categoryFilter = document.getElementById('categoryFilter');
  const rows = document.querySelectorAll('.item-row');

  function filterItems() {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedCategory = categoryFilter.value;

    rows.forEach(row => {
      const itemName = row.dataset.name;
      const itemRestaurant = row.dataset.restaurant;
      const itemCategory = row.dataset.category;

      const matchesSearch = itemName.includes(searchTerm) ||
                           itemRestaurant.toLowerCase().includes(searchTerm) ||
                           itemCategory.toLowerCase().includes(searchTerm);
      const matchesCategory = !selectedCategory || itemCategory === selectedCategory;

      if (matchesSearch && matchesCategory) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  }

  searchInput.addEventListener('input', filterItems);
  categoryFilter.addEventListener('change', filterItems);

  // Restaurant filter should reload page with query parameter
  restaurantFilter.addEventListener('change', function() {
    const selectedRestaurant = this.value;
    if (selectedRestaurant) {
      window.location.href = '?restaurant=' + encodeURIComponent(selectedRestaurant);
    } else {
      window.location.href = '/items';
    }
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
