<!DOCTYPE html>
<html lang="en" id="html-root">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $shopInfo['name'] }} - Menu Items</title>
  <link rel="icon" type="image/png" href="/favicon.png" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { darkMode: 'class' }
    if (localStorage.getItem('darkMode') === 'true') {
      document.documentElement.classList.add('dark');
    }
  </script>
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
<body class="bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-200">
  <!-- Info Popup Modal -->
  <div id="infoPopup" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 pointer-events-none">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-5xl w-full p-8 max-h-[90vh] overflow-y-auto pointer-events-auto">
      <div class="flex items-center justify-between mb-6 sticky top-0 bg-white dark:bg-slate-900 pb-4">
        <div>
          <h3 class="text-3xl font-bold text-slate-900 dark:text-slate-100">📖 HawkerOps Guide</h3>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Complete guide to using the store management system</p>
        </div>
        <button onclick="toggleInfoPopup()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-3xl leading-none hover:bg-slate-100 dark:hover:bg-slate-800 w-8 h-8 flex items-center justify-center rounded-lg transition flex-shrink-0">&times;</button>
      </div>

      <!-- Two Column Layout -->
      <div class="grid grid-cols-2 gap-4 text-sm">
        <!-- LEFT COLUMN -->
        <div>
          <div class="bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🔄 Refresh Data Button</div>
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">Located in the left sidebar. Refreshes data from the database and updates platform status and item availability without running scrapers. Useful for quick data updates.</p>
          </div>

          <div class="bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">↻ Reload Button</div>
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">Located in the top-right corner. Reloads the entire page to show the latest data from the database. Use when data seems outdated.</p>
          </div>

          <div class="bg-orange-50 dark:bg-orange-900/30 border-l-4 border-orange-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">⚠️ Troubleshooting</div>
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">If an entire column shows as offline or data seems incorrect, simply refresh the page. This resolves most display issues with platform status.</p>
          </div>

          <div class="bg-purple-50 dark:bg-purple-900/30 border-l-4 border-purple-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🕐 Auto-Refresh</div>
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">Pages automatically reload every 5 minutes to keep data current. No action needed - happens in the background.</p>
          </div>

          <div class="bg-indigo-50 dark:bg-indigo-900/30 border-l-4 border-indigo-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🏪 Store Actions</div>
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed"><strong>View Items:</strong> See all menu items with their status (Active/Inactive) across all platforms. <strong>View Logs:</strong> Check daily status history and changes.</p>
          </div>

          <div class="bg-cyan-50 dark:bg-cyan-900/30 border-l-4 border-cyan-500 p-4 rounded-lg">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🎨 Filter Buttons</div>
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed"><strong>All Stores:</strong> Show all outlets. <strong>All Online:</strong> Only all 3 platforms online. <strong>Partial Offline:</strong> 1-2 platforms down. <strong>All Offline:</strong> All 3 platforms down.</p>
          </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div>
          <div class="bg-pink-50 dark:bg-pink-900/30 border-l-4 border-pink-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">📊 Status Indicators</div>
            <div class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed space-y-1">
              <p><strong>🟢 Green Badge:</strong> All 3 platforms online - Fully operational</p>
              <p><strong>🟡 Orange Badge:</strong> 1-2 platforms offline - Partial service</p>
              <p><strong>🔴 Red Badge:</strong> All 3 platforms offline - No service</p>
            </div>
          </div>

          <div class="bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🔢 Item Information</div>
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">Each menu item appears 3 times (Grab, FoodPanda, Deliveroo). Total item count shows unique items. Offline count shows items unavailable per platform.</p>
          </div>

          <div class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">🌐 Platforms Monitored</div>
            <div class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed space-y-1">
              <p><strong>🟢 Grab:</strong> Green indicators, food delivery service</p>
              <p><strong>🩷 FoodPanda:</strong> Pink indicators, delivery platform</p>
              <p><strong>🔵 Deliveroo:</strong> Cyan indicators, premium delivery</p>
            </div>
          </div>

          <div class="bg-slate-50 dark:bg-slate-800 border-l-4 border-slate-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">📈 Dashboard Cards</div>
            <div class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed space-y-1">
              <p><strong>Stores Online:</strong> Number of outlets currently online</p>
              <p><strong>Items OFF:</strong> Total items offline across all platforms</p>
              <p><strong>Active Alerts:</strong> Critical status changes requiring attention</p>
              <p><strong>Platforms Status:</strong> Online vs total platform availability</p>
            </div>
          </div>

          <div class="bg-teal-50 dark:bg-teal-900/30 border-l-4 border-teal-500 p-4 rounded-lg mb-4">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">📍 Timezone & Location</div>
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed"><strong>Timezone:</strong> All timestamps in Singapore Time (SGT, UTC+8). <strong>Coverage:</strong> 46 restaurant outlets across Singapore monitored in real-time.</p>
          </div>

          <div class="bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 p-4 rounded-lg">
            <div class="font-semibold text-slate-900 dark:text-slate-100 mb-2">⚡ Performance</div>
            <p class="text-slate-600 dark:text-slate-400 text-xs leading-relaxed">Dashboard optimized for speed - loads in under 1 second. 99% fewer database queries. Real-time updates with gzip compression. Supports 30+ concurrent users.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div>
    <!-- Main Content -->
    <main class="w-full">
      <!-- Header -->
      <header class="bg-white dark:bg-slate-900 border-b dark:border-slate-800 px-4 md:px-8 py-4">
        <div class="flex items-center justify-between">
          <div>
            <div class="flex items-center gap-3 min-w-0">
              <a href="/stores" class="flex-shrink-0 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100">
                <i class="fas fa-arrow-left"></i>
              </a>
              <div class="min-w-0">
                <h2 class="text-base md:text-2xl font-bold text-slate-900 dark:text-slate-100 leading-tight truncate">{{ $shopInfo['name'] }}</h2>
                <p class="text-xs md:text-sm text-slate-500 dark:text-slate-400">{{ count($items) }} items · {{ $shopInfo['brand'] }}</p>
              </div>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button onclick="toggleDarkMode()" id="darkToggle" class="h-8 w-8 rounded-full bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs flex items-center justify-center transition" title="Toggle dark mode">
              <span id="darkIcon">🌙</span>
            </button>
            <button onclick="toggleInfoPopup()" class="h-8 w-8 rounded-full bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 text-xs font-bold flex items-center justify-center transition">
              i
            </button>
            <div class="hidden sm:flex items-center bg-slate-100 dark:bg-slate-800 rounded-xl px-3 py-2">
              <input id="searchInput" class="bg-transparent outline-none text-sm w-64 dark:text-slate-100 dark:placeholder-slate-400" placeholder="Search items..." />
            </div>
          </div>
        </div>
      </header>

      <div class="px-4 md:px-8 py-4 md:py-6 space-y-6 max-w-[1600px] mx-auto">
        <!-- Platform Status Cards -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
          @foreach(['grab' => 'Grab', 'foodpanda' => 'FoodPanda', 'deliveroo' => 'Deliveroo'] as $platform => $name)
            @php
              $status = $platformStatus->get($platform);
              $isOnline = $status ? $status->is_online : false;
            @endphp
            <div class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl p-5 shadow-sm">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm text-slate-500 dark:text-slate-400">{{ $name }}</p>
                  <p class="text-xl font-semibold mt-1">
                    @if($isOnline)
                      <span class="text-green-600 dark:text-green-400"><i class="fas fa-check-circle"></i> Online</span>
                    @else
                      <span class="text-red-600 dark:text-red-400"><i class="fas fa-times-circle"></i> Offline</span>
                    @endif
                  </p>
                </div>
              </div>
            </div>
          @endforeach
        </section>

        <!-- Filter and View Toggle -->
        <section class="bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm p-4 md:p-6">
          {{-- Mobile search --}}
          <div class="sm:hidden mb-3">
            <div class="flex items-center bg-slate-100 dark:bg-slate-700 rounded-xl px-3 py-2">
              <i class="fas fa-search text-slate-400 dark:text-slate-500 mr-2 text-sm"></i>
              <input id="searchInputMobile" class="bg-transparent outline-none text-sm w-full dark:text-slate-100 dark:placeholder-slate-400" placeholder="Search items..." />
            </div>
          </div>
          <div class="flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
            <div class="flex flex-wrap gap-2">
              <select id="statusFilter" class="flex-1 min-w-[120px] px-3 py-2 border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 focus:border-transparent">
                <option value="">All Status</option>
                <option value="active">Active Only</option>
                <option value="inactive">Inactive Only</option>
              </select>
              <select id="categoryFilter" class="flex-1 min-w-[140px] px-3 py-2 border border-slate-300 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100 rounded-xl text-sm focus:ring-2 focus:ring-slate-900 focus:border-transparent">
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
            <div class="flex items-center gap-2 self-end sm:self-auto flex-shrink-0">
              <button id="gridViewBtn" class="px-3 py-2 bg-slate-900 dark:bg-slate-700 text-white rounded-xl text-sm font-medium">
                <i class="fas fa-th"></i> <span class="hidden sm:inline">Grid</span>
              </button>
              <button id="tableViewBtn" class="px-3 py-2 border border-slate-300 dark:border-slate-600 dark:text-slate-300 rounded-xl text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-700">
                <i class="fas fa-list"></i> <span class="hidden sm:inline">Table</span>
              </button>
            </div>
          </div>
        </section>

        <!-- Items Grid View -->
        <section id="gridView" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-3 md:gap-6">
          @foreach($items as $item)
            <div class="item-card bg-white dark:bg-slate-800 rounded-2xl shadow-sm overflow-hidden"
                 data-category="{{ $item['category'] }}"
                 data-status="{{ $item['all_active'] ? 'active' : 'inactive' }}"
                 data-name="{{ strtolower($item['name']) }}">
              <!-- Image -->
              <div class="relative h-32 md:h-48 bg-slate-100 dark:bg-slate-700">
                @if($item['image_url'])
                  <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover" loading="lazy">
                @else
                  <div class="w-full h-full flex items-center justify-center">
                    <i class="fas fa-utensils text-6xl text-slate-300 dark:text-slate-600"></i>
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
              <div class="p-3 md:p-4 flex flex-col">
                <h3 class="font-semibold text-xs md:text-sm text-slate-900 dark:text-slate-100 mb-1 line-clamp-2">{{ $item['name'] }}</h3>
                <p class="text-[10px] md:text-xs text-slate-500 dark:text-slate-400 mb-2 truncate">{{ $item['category'] }}</p>

                <div class="mt-2">
                  <div class="mb-2">
                    <span class="text-sm md:text-lg font-bold text-slate-900 dark:text-slate-100">${{ number_format($item['price'], 2) }}</span>
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
                                    {{ $available ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' }}">
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
        <section id="tableView" class="hidden bg-white dark:bg-slate-800 border dark:border-slate-700 rounded-2xl shadow-sm overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-slate-50 dark:bg-slate-900 border-b dark:border-slate-700">
                <tr>
                  <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Item</th>
                  <th class="hidden sm:table-cell px-3 md:px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Category</th>
                  <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Price</th>
                  <th class="px-3 md:px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Status</th>
                  <th class="px-2 md:px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Grab</th>
                  <th class="px-2 md:px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><span class="md:hidden">Panda</span><span class="hidden md:inline">FoodPanda</span></th>
                  <th class="px-2 md:px-6 py-3 text-center text-xs font-medium text-slate-500 dark:text-slate-400 uppercase"><span class="md:hidden">Delvro</span><span class="hidden md:inline">Deliveroo</span></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($items as $item)
                  <tr class="hover:bg-slate-50 dark:hover:bg-slate-700 transition table-row"
                      data-category="{{ $item['category'] }}"
                      data-status="{{ $item['all_active'] ? 'active' : 'inactive' }}"
                      data-name="{{ strtolower($item['name']) }}">
                    <td class="px-3 md:px-6 py-3 md:py-4">
                      <div class="font-medium text-xs md:text-sm text-slate-900 dark:text-slate-100 leading-tight">{{ $item['name'] }}</div>
                    </td>
                    <td class="hidden sm:table-cell px-3 md:px-6 py-3 md:py-4">
                      <div class="text-xs md:text-sm text-slate-600 dark:text-slate-300">{{ $item['category'] }}</div>
                    </td>
                    <td class="px-3 md:px-6 py-3 md:py-4">
                      <div class="text-xs md:text-sm font-semibold text-slate-900 dark:text-slate-100">${{ number_format($item['price'], 2) }}</div>
                    </td>
                    <td class="px-3 md:px-6 py-3 md:py-4">
                      @if($item['all_active'])
                        <span class="px-1.5 md:px-2.5 py-0.5 md:py-1 rounded-full text-[10px] md:text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400">
                          ON
                        </span>
                      @else
                        <span class="px-1.5 md:px-2.5 py-0.5 md:py-1 rounded-full text-[10px] md:text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">
                          OFF
                        </span>
                      @endif
                    </td>
                    @foreach(['grab', 'foodpanda', 'deliveroo'] as $platform)
                      <td class="px-2 md:px-6 py-3 md:py-4 text-center">
                        @if(isset($item['platforms'][$platform]))
                          @if($item['platforms'][$platform]['is_available'])
                            <i class="fas fa-check-circle text-green-500 text-sm md:text-base"></i>
                          @else
                            <i class="fas fa-times-circle text-red-500 text-sm md:text-base"></i>
                          @endif
                        @else
                          <span class="text-slate-300 dark:text-slate-600">—</span>
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
    // Dark mode toggle
    function toggleDarkMode() {
      const html = document.getElementById('html-root');
      const icon = document.getElementById('darkIcon');
      const isDark = html.classList.toggle('dark');
      localStorage.setItem('darkMode', isDark);
      icon.textContent = isDark ? '☀️' : '🌙';
    }
    // Set initial icon
    document.addEventListener('DOMContentLoaded', () => {
      const icon = document.getElementById('darkIcon');
      if (icon) icon.textContent = localStorage.getItem('darkMode') === 'true' ? '☀️' : '🌙';
    });

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const searchInputMobile = document.getElementById('searchInputMobile');
    const statusFilter = document.getElementById('statusFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const gridViewBtn = document.getElementById('gridViewBtn');
    const tableViewBtn = document.getElementById('tableViewBtn');
    const gridView = document.getElementById('gridView');
    const tableView = document.getElementById('tableView');

    function filterItems() {
      const searchTerm = (searchInput?.value || searchInputMobile?.value || '').toLowerCase();
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

    searchInput?.addEventListener('input', filterItems);
    searchInputMobile?.addEventListener('input', filterItems);
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
