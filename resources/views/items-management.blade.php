<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Items Management - Restaurant Dashboard</title>
    <link rel="icon" type="image/png" href="/favicon.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Toggle Switch Styles */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e0;
            transition: .3s;
            border-radius: 24px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .3s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: #48bb78;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(20px);
        }
        input:disabled + .toggle-slider {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .item-row:hover {
            background-color: #f7fafc;
        }
        .status-badge-available {
            background-color: #c6f6d5;
            color: #22543d;
        }
        .status-badge-unavailable {
            background-color: #fed7d7;
            color: #742a2a;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b-4 border-blue-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="/" class="text-2xl font-bold text-blue-600">
                        <i class="fas fa-utensils mr-2"></i>RestoHub
                    </a>
                    <div class="hidden md:flex space-x-4">
                        <a href="/" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition">
                            <i class="fas fa-store mr-1"></i> Platforms
                        </a>
                        <a href="/items" class="text-gray-600 hover:text-blue-600 px-3 py-2 rounded-md text-sm font-medium transition">
                            <i class="fas fa-hamburger mr-1"></i> Items
                        </a>
                        <a href="/items/management" class="text-blue-600 bg-blue-50 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-cog mr-1"></i> Management
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500">
                        <i class="fas fa-box mr-1"></i> <span id="totalItems">{{ $totalItems }}</span> Total Items
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Header -->
    <div class="bg-white border-b px-4 sm:px-6 lg:px-8 py-6">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-cog mr-2"></i>Items Management
            </h1>
            <p class="text-gray-600 mt-1">Monitor menu items across all platforms</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" id="searchInput" placeholder="Search items..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Shop</label>
                    <select id="shopFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Shops</option>
                        @foreach($shops as $shop)
                            <option value="{{ $shop }}">{{ $shop }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select id="categoryFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select id="statusFilter" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shop</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Grab</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">FoodPanda</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Deliveroo</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="itemsTableBody">
                        @foreach($itemsGrouped as $itemGroup)
                        <tr class="item-row"
                            data-name="{{ strtolower($itemGroup['name']) }}"
                            data-shop="{{ strtolower($itemGroup['shop_name']) }}"
                            data-category="{{ strtolower($itemGroup['category']) }}"
                            data-status="{{ $itemGroup['any_available'] ? 'available' : 'unavailable' }}">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="h-10 w-10 flex-shrink-0 bg-gradient-to-br from-blue-100 to-purple-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-utensils text-blue-600"></i>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $itemGroup['name'] }}</div>
                                        <div class="text-sm text-gray-500">{{ $itemGroup['sku'] }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $itemGroup['shop_name'] }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    {{ $itemGroup['category'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">${{ number_format($itemGroup['price'], 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($itemGroup['platforms']['grab'])
                                <label class="toggle-switch">
                                    <input type="checkbox"
                                        {{ $itemGroup['platforms']['grab']['is_available'] ? 'checked' : '' }}
                                        onchange="toggleItemStatus({{ $itemGroup['platforms']['grab']['id'] }}, this.checked, 'grab')"
                                        data-item-id="{{ $itemGroup['platforms']['grab']['id'] }}">
                                    <span class="toggle-slider"></span>
                                </label>
                                @else
                                <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($itemGroup['platforms']['foodpanda'])
                                <label class="toggle-switch">
                                    <input type="checkbox"
                                        {{ $itemGroup['platforms']['foodpanda']['is_available'] ? 'checked' : '' }}
                                        onchange="toggleItemStatus({{ $itemGroup['platforms']['foodpanda']['id'] }}, this.checked, 'foodpanda')"
                                        data-item-id="{{ $itemGroup['platforms']['foodpanda']['id'] }}">
                                    <span class="toggle-slider"></span>
                                </label>
                                @else
                                <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($itemGroup['platforms']['deliveroo'])
                                <label class="toggle-switch">
                                    <input type="checkbox"
                                        {{ $itemGroup['platforms']['deliveroo']['is_available'] ? 'checked' : '' }}
                                        onchange="toggleItemStatus({{ $itemGroup['platforms']['deliveroo']['id'] }}, this.checked, 'deliveroo')"
                                        data-item-id="{{ $itemGroup['platforms']['deliveroo']['id'] }}">
                                    <span class="toggle-slider"></span>
                                </label>
                                @else
                                <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $itemGroup['any_available'] ? 'status-badge-available' : 'status-badge-unavailable' }}">
                                    {{ $itemGroup['any_available'] ? 'AVAILABLE' : 'UNAVAILABLE' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Results Count -->
        <div class="mt-4 text-center text-sm text-gray-600">
            Showing <span id="visibleCount">{{ count($itemsGrouped) }}</span> of <span id="totalCount">{{ count($itemsGrouped) }}</span> items
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 right-4 bg-white rounded-lg shadow-lg p-4 transform translate-y-20 transition-transform duration-300 hidden">
        <div class="flex items-center space-x-3">
            <div id="toastIcon"></div>
            <div id="toastMessage" class="text-sm font-medium text-gray-900"></div>
        </div>
    </div>

    <script>
        // CSRF token setup
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        // Toggle item status
        async function toggleItemStatus(itemId, isAvailable, platform) {
            const toggle = document.querySelector(`input[data-item-id="${itemId}"]`);
            toggle.disabled = true;

            try {
                const response = await fetch('/api/items/toggle-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        item_id: itemId,
                        is_available: isAvailable,
                        platform: platform
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showToast('success', `Item ${isAvailable ? 'enabled' : 'disabled'} on ${platform}`);
                    updateRowStatus(toggle.closest('tr'));
                } else {
                    throw new Error(data.message || 'Failed to update status');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('error', 'Failed to update status');
                toggle.checked = !isAvailable;
            } finally {
                toggle.disabled = false;
            }
        }

        // Update row status badge
        function updateRowStatus(row) {
            const toggles = row.querySelectorAll('input[type="checkbox"]');
            const anyChecked = Array.from(toggles).some(t => t.checked);
            const statusBadge = row.querySelector('td:last-child span');

            if (anyChecked) {
                statusBadge.textContent = 'AVAILABLE';
                statusBadge.className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-badge-available';
                row.dataset.status = 'available';
            } else {
                statusBadge.textContent = 'UNAVAILABLE';
                statusBadge.className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-badge-unavailable';
                row.dataset.status = 'unavailable';
            }
        }

        // Show toast notification
        function showToast(type, message) {
            const toast = document.getElementById('toast');
            const icon = document.getElementById('toastIcon');
            const msg = document.getElementById('toastMessage');

            if (type === 'success') {
                icon.innerHTML = '<i class="fas fa-check-circle text-green-500 text-xl"></i>';
            } else {
                icon.innerHTML = '<i class="fas fa-exclamation-circle text-red-500 text-xl"></i>';
            }

            msg.textContent = message;
            toast.classList.remove('hidden', 'translate-y-20');

            setTimeout(() => {
                toast.classList.add('translate-y-20');
                setTimeout(() => toast.classList.add('hidden'), 300);
            }, 3000);
        }

        // Filtering functionality
        const searchInput = document.getElementById('searchInput');
        const shopFilter = document.getElementById('shopFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        const statusFilter = document.getElementById('statusFilter');
        const tableBody = document.getElementById('itemsTableBody');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const shopValue = shopFilter.value.toLowerCase();
            const categoryValue = categoryFilter.value.toLowerCase();
            const statusValue = statusFilter.value;

            const rows = tableBody.querySelectorAll('.item-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const name = row.dataset.name;
                const shop = row.dataset.shop;
                const category = row.dataset.category;
                const status = row.dataset.status;

                const matchesSearch = name.includes(searchTerm);
                const matchesShop = !shopValue || shop === shopValue;
                const matchesCategory = !categoryValue || category === categoryValue;
                const matchesStatus = !statusValue || status === statusValue;

                if (matchesSearch && matchesShop && matchesCategory && matchesStatus) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            document.getElementById('visibleCount').textContent = visibleCount;
        }

        searchInput.addEventListener('input', filterTable);
        shopFilter.addEventListener('change', filterTable);
        categoryFilter.addEventListener('change', filterTable);
        statusFilter.addEventListener('change', filterTable);
    </script>
</body>
</html>
