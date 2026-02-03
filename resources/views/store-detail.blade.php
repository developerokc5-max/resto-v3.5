<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $shopInfo['name'] }} - Menu Items</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .item-card {
        transition: all 0.3s ease;
    }
    .item-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    }
  </style>
</head>
<body class="bg-slate-50">
  <!-- Info Popup -->
  <div id="infoPopup" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-slate-900">App Guide</h3>
        <button onclick="toggleInfoPopup()" class="text-slate-400 hover:text-slate-600 text-2xl leading-none">&times;</button>
      </div>

      <div class="space-y-4 text-sm max-h-96 overflow-y-auto">
        <div>
          <div class="font-semibold text-slate-900 mb-1">🔄 Refresh Data Button</div>
          <p class="text-slate-600">Refreshes data from the database. Updates platform status and item availability without running scrapers.</p>
        </div>

        <div>
          <div class="font-semibold text-slate-900 mb-1">↻ Refresh Button</div>
          <p class="text-slate-600">Reloads the current page to show the latest data from the database.</p>
        </div>

        <div>
          <div class="font-semibold text-slate-900 mb-1">⚠️ Platforms Page Issue</div>
          <p class="text-slate-600">Sometimes an entire column may show as offline. Simply refresh the page or press the button again to reload the correct status.</p>
        </div>

        <div>
          <div class="font-semibold text-slate-900 mb-1">🕐 Auto-Refresh</div>
          <p class="text-slate-600">Pages automatically reload every 5 minutes to keep data up-to-date.</p>
        </div>

        <div>
          <div class="font-semibold text-slate-900 mb-1">🏪 Store Details "View" Button</div>
          <p class="text-slate-600">Click "View" on any store to see all menu items with their ACTIVE/INACTIVE status across all platforms.</p>
        </div>

        <div>
          <div class="font-semibold text-slate-900 mb-1">📊 Status Indicators</div>
          <p class="text-slate-600">Green = Online/Active, Red = Offline/Inactive, Yellow/Orange = Mixed status (some platforms down).</p>
        </div>

        <div>
          <div class="font-semibold text-slate-900 mb-1">📝 Store Logs</div>
          <p class="text-slate-600">Click on any store's "Logs" to see daily status history. New entry created each day with real-time updates throughout the day.</p>
        </div>

        <div>
          <div class="font-semibold text-slate-900 mb-1">🔢 Items Count</div>
          <p class="text-slate-600">Each menu item appears 3 times in the database (once per platform: Grab, FoodPanda, Deliveroo). Counts show unique items.</p>
        </div>

        <div>
          <div class="font-semibold text-slate-900 mb-1">🌐 Platforms Coverage</div>
          <p class="text-slate-600">System tracks 3 delivery platforms: Grab (Green), FoodPanda (Pink), Deliveroo (Blue). Total of 46 restaurant outlets monitored.</p>
        </div>

        <div>
          <div class="font-semibold text-slate-900 mb-1">⏰ Timezone</div>
          <p class="text-slate-600">All timestamps are displayed in Singapore Time (SGT, UTC+8).</p>
        </div>
      </div>
    </div>
  </div>

  <div>
    <!-- Main Content -->
    <main class="w-full">
      <!-- Header -->
      <header class="bg-white border-b px-4 md:px-8 py-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="flex items-center gap-3">
              <a href="/stores" class="text-slate-500 hover:text-slate-900">
                <i class="fas fa-arrow-left"></i>
              </a>
              <div>
                <h2 class="text-2xl font-bold text-slate-900">{{ $shopInfo['name'] }}</h2>
                <p class="text-sm text-slate-500">{{ count($items) }} items from {{ $shopInfo['brand'] }}</p>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button onclick="toggleInfoPopup()" class="h-8 w-8 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-600 text-xs font-bold flex items-center justify-center transition">
              i
            </button>
            <div class="hidden sm:flex items-center bg-slate-100 rounded-xl px-3 py-2">
              <input id="searchInput" class="bg-transparent outline-none text-sm w-64" placeholder="Search items..." />
            </div>
          </div>
        </div>
      </header>

      <div class="px-8 py-6 space-y-6 max-w-[1600px] mx-auto">
        <!-- Platform Status Cards -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
          @foreach(['grab' => 'Grab', 'foodpanda' => 'FoodPanda', 'deliveroo' => 'Deliveroo'] as $platform => $name)
            @php
              $status = $platformStatus->get($platform);
              $isOnline = $status ? $status->is_online : false;
            @endphp
            <div class="bg-white border rounded-2xl p-5 shadow-sm">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-slate-500">{{ $name }}</p>
                  <p class="text-xl font-semibold mt-1">
                    @if($isOnline)
                      <span class="text-green-600"><i class="fas fa-check-circle"></i> Online</span>
                    @else
                      <span class="text-red-600"><i class="fas fa-times-circle"></i> Offline</span>
                    @endif
                  </p>
                </div>
              </div>
            </div>
          @endforeach
        </section>

        <!-- Filter and View Toggle -->
        <section class="bg-white rounded-2xl shadow-sm p-6">
          <div class="flex items-center justify-between mb-4">
            <div class="flex gap-4">
              <select id="statusFilter" class="px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
                <option value="">All Status</option>
                <option value="active">Active Only</option>
                <option value="inactive">Inactive Only</option>
              </select>
              <select id="categoryFilter" class="px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
                <option value="">All Categories</option>
                @php
                  $categories = array_unique(array_column($items, 'category'));
                  sort($categories);
                @endphp
                @foreach($categories as $category)
                  <option value="{{ $category }}">{{ $category }}</option>
                @endforeach
              </select>
            </div>
            <div class="flex items-center gap-2">
              <button id="gridViewBtn" class="px-4 py-2 bg-slate-900 text-white rounded-xl font-medium">
                <i class="fas fa-th"></i> Grid View
              </button>
              <button id="tableViewBtn" class="px-4 py-2 border border-slate-300 rounded-xl font-medium hover:bg-slate-50">
                <i class="fas fa-list"></i> Table View
              </button>
            </div>
          </div>
        </section>

        <!-- Items Grid View -->
        <section id="gridView" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6">
          @foreach($items as $item)
            <div class="item-card bg-white rounded-2xl shadow-sm overflow-hidden"
                 data-category="{{ $item['category'] }}"
                 data-status="{{ $item['all_active'] ? 'active' : 'inactive' }}"
                 data-name="{{ strtolower($item['name']) }}">
              <!-- Image -->
              <div class="relative h-48 bg-slate-100">
                @if($item['image_url'])
                  <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover" loading="lazy">
                @else
                  <div class="w-full h-full flex items-center justify-center">
                    <i class="fas fa-utensils text-6xl text-slate-300"></i>
                  </div>
                @endif

                <!-- Status Badge -->
                <div class="absolute top-3 right-3">
                  @if($item['all_active'])
                    <span class="px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full">
                      ACTIVE
                    </span>
                  @else
                    <span class="px-3 py-1 bg-red-500 text-white text-xs font-bold rounded-full">
                      INACTIVE
                    </span>
                  @endif
                </div>
              </div>

              <!-- Details -->
              <div class="p-4 flex flex-col h-[140px]">
                <h3 class="font-semibold text-slate-900 mb-1 line-clamp-2 min-h-[48px]">{{ $item['name'] }}</h3>
                <p class="text-xs text-slate-500 mb-2 truncate">{{ $item['category'] }}</p>

                <div class="mt-auto">
                  <div class="mb-3">
                    <span class="text-lg font-bold text-slate-900">${{ number_format($item['price'], 2) }}</span>
                  </div>

                  <!-- Platform Status -->
                  <div class="flex gap-1">
                    @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                      @if(isset($item['platforms'][$platform]))
                        @php
                          $platformData = $item['platforms'][$platform];
                          $available = $platformData['is_available'];
                        @endphp
                        <div class="flex-1 text-center py-1 rounded text-xs font-medium
                                    {{ $available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                          {{ substr(ucfirst($platform), 0, 4) }}
                        </div>
                      @endif
                    @endforeach
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </section>

        <!-- Items Table View (hidden by default) -->
        <section id="tableView" class="hidden bg-white rounded-2xl shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-slate-50 border-b">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Item</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Category</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Price</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Grab</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">FoodPanda</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase">Deliveroo</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200">
                @foreach($items as $item)
                  <tr class="hover:bg-slate-50 table-row"
                      data-category="{{ $item['category'] }}"
                      data-status="{{ $item['all_active'] ? 'active' : 'inactive' }}"
                      data-name="{{ strtolower($item['name']) }}">
                    <td class="px-6 py-4">
                      <div class="font-medium text-slate-900">{{ $item['name'] }}</div>
                    </td>
                    <td class="px-6 py-4">
                      <div class="text-sm text-slate-600">{{ $item['category'] }}</div>
                    </td>
                    <td class="px-6 py-4">
                      <div class="font-semibold text-slate-900">${{ number_format($item['price'], 2) }}</div>
                    </td>
                    <td class="px-6 py-4">
                      @if($item['all_active'])
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                          ACTIVE
                        </span>
                      @else
                        <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                          INACTIVE
                        </span>
                      @endif
                    </td>
                    @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                      <td class="px-6 py-4">
                        @if(isset($item['platforms'][$platform]))
                          @if($item['platforms'][$platform]['is_available'])
                            <i class="fas fa-check-circle text-green-500"></i>
                          @else
                            <i class="fas fa-times-circle text-red-500"></i>
                          @endif
                        @else
                          <span class="text-slate-300">—</span>
                        @endif
                      </td>
                    @endforeach
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </main>
  </div>

  <script>
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const gridViewBtn = document.getElementById('gridViewBtn');
    const tableViewBtn = document.getElementById('tableViewBtn');
    const gridView = document.getElementById('gridView');
    const tableView = document.getElementById('tableView');

    function filterItems() {
      const searchTerm = searchInput.value.toLowerCase();
      const statusValue = statusFilter.value;
      const categoryValue = categoryFilter.value;

      const items = document.querySelectorAll('.item-card, .table-row');

      items.forEach(item => {
        const name = item.dataset.name || '';
        const category = item.dataset.category || '';
        const status = item.dataset.status || '';

        const matchesSearch = name.includes(searchTerm);
        const matchesStatus = !statusValue || status === statusValue;
        const matchesCategory = !categoryValue || category === categoryValue;

        if (matchesSearch && matchesStatus && matchesCategory) {
          item.style.display = '';
        } else {
          item.style.display = 'none';
        }
      });
    }

    searchInput.addEventListener('input', filterItems);
    statusFilter.addEventListener('change', filterItems);
    categoryFilter.addEventListener('change', filterItems);

    // View toggle
    gridViewBtn.addEventListener('click', () => {
      gridView.classList.remove('hidden');
      tableView.classList.add('hidden');
      gridViewBtn.classList.add('bg-slate-900', 'text-white');
      gridViewBtn.classList.remove('border', 'border-slate-300');
      tableViewBtn.classList.remove('bg-slate-900', 'text-white');
      tableViewBtn.classList.add('border', 'border-slate-300');
    });

    tableViewBtn.addEventListener('click', () => {
      tableView.classList.remove('hidden');
      gridView.classList.add('hidden');
      tableViewBtn.classList.add('bg-slate-900', 'text-white');
      tableViewBtn.classList.remove('border', 'border-slate-300');
      gridViewBtn.classList.remove('bg-slate-900', 'text-white');
      gridViewBtn.classList.add('border', 'border-slate-300');
    });

    // Toggle info popup
    function toggleInfoPopup() {
      const popup = document.getElementById('infoPopup');
      popup.classList.toggle('hidden');
    }

    // Close popup when clicking outside
    document.getElementById('infoPopup')?.addEventListener('click', function(e) {
      if (e.target === this) {
        toggleInfoPopup();
      }
    });
  </script>
</body>
</html>
