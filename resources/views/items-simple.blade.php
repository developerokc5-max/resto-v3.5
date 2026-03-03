<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Items - RestoHub</title>
    <link rel="icon" type="image/png" href="/favicon.png" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-xl font-bold">RestoHub - Menu Items</h1>
                <div class="text-sm text-gray-600">
                    {{$stats['total']}} Items | {{$stats['restaurants']}} Restaurants
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-6">
        <!-- Filters -->
        <div class="bg-white rounded shadow p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="text" id="searchInput" placeholder="Search items..."
                       class="px-4 py-2 border rounded">
                <select id="restaurantFilter" class="px-4 py-2 border rounded">
                    <option value="">All Restaurants</option>
                    @foreach($restaurants as $restaurant)
                        <option value="{{$restaurant}}">{{$restaurant}}</option>
                    @endforeach
                </select>
                <select id="categoryFilter" class="px-4 py-2 border rounded">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{$category}}">{{$category}}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Items Grid -->
        <div id="itemsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($items as $item)
            <div class="item-card bg-white rounded shadow hover:shadow-lg transition"
                 data-name="{{strtolower($item['name'])}}"
                 data-category="{{$item['category']}}"
                 data-restaurant="{{$item['shop_name']}}">

                <!-- Image -->
                <div class="h-48 bg-gray-200 overflow-hidden">
                    @if($item['image_url'])
                        <img src="{{$item['image_url']}}" alt="{{$item['name']}}"
                             class="w-full h-full object-cover"
                             onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <i class="fas fa-utensils text-4xl text-gray-400"></i>
                        </div>
                    @endif
                </div>

                <!-- Content -->
                <div class="p-4">
                    <!-- Name & Price -->
                    <div class="flex justify-between items-start mb-2">
                        <h3 class="font-bold text-gray-900">{{$item['name']}}</h3>
                        <span class="text-lg font-bold text-gray-900">${{number_format($item['price'], 2)}}</span>
                    </div>

                    <!-- Restaurant & Category -->
                    <div class="text-sm text-gray-600 mb-3">
                        <div><i class="fas fa-store mr-1"></i> {{$item['shop_name']}}</div>
                        <div><i class="fas fa-tag mr-1"></i> {{$item['category']}}</div>
                    </div>

                    <!-- Platform Badges -->
                    <div class="flex gap-2">
                        <!-- Grab -->
                        <div class="flex-1 text-center py-1 rounded text-xs font-semibold
                                    {{$item['platforms']['grab'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-400'}}">
                            <i class="fas fa-car mr-1"></i>Grab
                        </div>

                        <!-- foodPanda -->
                        <div class="flex-1 text-center py-1 rounded text-xs font-semibold
                                    {{$item['platforms']['foodpanda'] ? 'bg-pink-100 text-pink-800' : 'bg-gray-100 text-gray-400'}}">
                            <i class="fas fa-motorcycle mr-1"></i>Panda
                        </div>

                        <!-- Deliveroo -->
                        <div class="flex-1 text-center py-1 rounded text-xs font-semibold
                                    {{$item['platforms']['deliveroo'] ? 'bg-cyan-100 text-cyan-800' : 'bg-gray-100 text-gray-400'}}">
                            <i class="fas fa-bicycle mr-1"></i>Roo
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div id="noResults" class="hidden text-center py-12 text-gray-500">
            No items found matching your filters.
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const restaurantFilter = document.getElementById('restaurantFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        const itemsGrid = document.getElementById('itemsGrid');
        const noResults = document.getElementById('noResults');

        function filterItems() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedRestaurant = restaurantFilter.value;
            const selectedCategory = categoryFilter.value;

            const cards = itemsGrid.querySelectorAll('.item-card');
            let visibleCount = 0;

            cards.forEach(card => {
                const name = card.dataset.name || '';
                const restaurant = card.dataset.restaurant || '';
                const category = card.dataset.category || '';

                const matchesSearch = name.includes(searchTerm);
                const matchesRestaurant = !selectedRestaurant || restaurant === selectedRestaurant;
                const matchesCategory = !selectedCategory || category === selectedCategory;

                if (matchesSearch && matchesRestaurant && matchesCategory) {
                    card.classList.remove('hidden');
                    visibleCount++;
                } else {
                    card.classList.add('hidden');
                }
            });

            if (visibleCount === 0) {
                itemsGrid.classList.add('hidden');
                noResults.classList.remove('hidden');
            } else {
                itemsGrid.classList.remove('hidden');
                noResults.classList.add('hidden');
            }
        }

        searchInput.addEventListener('input', filterItems);
        restaurantFilter.addEventListener('change', filterItems);
        categoryFilter.addEventListener('change', filterItems);
    </script>
</body>
</html>
