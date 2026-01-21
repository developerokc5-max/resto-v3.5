@extends('layout')

@section('title', 'Items - HawkerOps')

@section('page-title', 'Menu Items')
@section('page-description', 'Browse all items across your restaurants and delivery platforms')

@section('extra-head')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .item-card {
        transition: all 0.3s ease;
    }
    .item-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
    }
    .platform-badge {
        transition: all 0.2s ease;
    }
    .platform-badge:hover {
        transform: scale(1.1);
    }
</style>
@endsection

@section('top-actions')
<div class="hidden sm:flex items-center bg-slate-100 rounded-xl px-3 py-2">
  <input id="searchInput" class="bg-transparent outline-none text-sm w-64" placeholder="Search items..." />
</div>
@endsection

@section('content')
  <!-- Stats Cards -->
  <section class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-slate-500">Total Items</p>
          <p class="text-3xl font-semibold mt-1">{{$stats['total']}}</p>
        </div>
        <i class="fas fa-box-open text-4xl text-slate-300"></i>
      </div>
    </div>
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-slate-500">Restaurants</p>
          <p class="text-3xl font-semibold mt-1">{{$stats['restaurants']}}</p>
        </div>
        <i class="fas fa-store text-4xl text-slate-300"></i>
      </div>
    </div>
    <div class="bg-green-50 border border-green-200 rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-green-700 font-medium">Available</p>
          <p class="text-3xl font-semibold text-green-900 mt-1">{{$stats['available']}}</p>
        </div>
        <i class="fas fa-check-circle text-4xl text-green-200"></i>
      </div>
    </div>
    <div class="bg-white border rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-slate-500">Categories</p>
          <p class="text-3xl font-semibold mt-1">{{count($categories)}}</p>
        </div>
        <i class="fas fa-tags text-4xl text-slate-300"></i>
      </div>
    </div>
  </section>

  <!-- Filters -->
  <section class="bg-white rounded-2xl shadow-sm p-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
      <!-- Restaurant Filter -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">
          <i class="fas fa-store mr-1"></i> Restaurant
        </label>
        <select id="restaurantFilter" class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
          <option value="">All Restaurants</option>
          @foreach($restaurants as $restaurant)
            <option value="{{$restaurant}}">{{$restaurant}}</option>
          @endforeach
        </select>
      </div>

      <!-- Category Filter -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">
          <i class="fas fa-tags mr-1"></i> Category
        </label>
        <select id="categoryFilter" class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
          <option value="">All Categories</option>
          @foreach($categories as $category)
            <option value="{{$category}}">{{$category}}</option>
          @endforeach
        </select>
      </div>

      <!-- Platform Filter -->
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-2">
          <i class="fas fa-filter mr-1"></i> Platform
        </label>
        <select id="platformFilter" class="w-full px-4 py-2 border border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent">
          <option value="">All Platforms</option>
          <option value="grab">Grab</option>
          <option value="foodpanda">FoodPanda</option>
          <option value="deliveroo">Deliveroo</option>
        </select>
      </div>
    </div>

    <!-- Available Only Toggle -->
    <div class="flex items-center">
      <label class="inline-flex items-center cursor-pointer">
        <input type="checkbox" id="availableOnly" class="form-checkbox h-5 w-5 text-slate-900 rounded" checked>
        <span class="ml-2 text-sm text-slate-700">
          <i class="fas fa-check-circle text-green-600"></i> Available Only
        </span>
      </label>
      <span class="ml-auto text-sm text-slate-600">
        Showing <span id="resultCount" class="font-semibold text-slate-900">{{count($items)}}</span> items
      </span>
    </div>
  </section>

  <!-- Items Grid -->
  <div id="itemsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    @foreach($items as $item)
    <div class="item-card bg-white rounded-2xl shadow-sm overflow-hidden border hover:border-slate-300"
         data-name="{{strtolower($item->name)}}"
         data-category="{{$item->category}}"
         data-restaurant="{{$item->shop_name}}"
         data-platform="{{$item->platform}}"
         data-available="{{$item->is_available ? '1' : '0'}}">

      <!-- Image -->
      <div class="relative h-48 bg-gradient-to-br from-slate-100 to-slate-200 overflow-hidden">
        @if($item->image_url)
          <img src="{{$item->image_url}}" alt="{{$item->name}}"
               class="w-full h-full object-cover"
               onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=400'">
        @else
          <div class="w-full h-full flex items-center justify-center">
            <i class="fas fa-utensils text-6xl text-slate-300"></i>
          </div>
        @endif

        <!-- Availability Badge -->
        @if($item->is_available)
          <span class="absolute top-3 right-3 bg-green-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg">
            <i class="fas fa-check-circle mr-1"></i>Available
          </span>
        @else
          <span class="absolute top-3 right-3 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-lg">
            <i class="fas fa-times-circle mr-1"></i>Unavailable
          </span>
        @endif

        <!-- Price Tag -->
        <div class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-sm rounded-xl px-3 py-1 shadow-lg">
          <span class="text-2xl font-bold text-slate-900">${{number_format($item->price, 2)}}</span>
        </div>
      </div>

      <!-- Content -->
      <div class="p-4">
        <!-- Item Name -->
        <h3 class="text-lg font-bold text-slate-900 mb-2 line-clamp-2">
          {{$item->name}}
        </h3>

        <!-- Restaurant -->
        <p class="text-sm text-slate-600 mb-2 flex items-center">
          <i class="fas fa-store text-slate-400 mr-2"></i>
          <span class="truncate">{{$item->shop_name}}</span>
        </p>

        <!-- Category -->
        <span class="inline-block bg-slate-100 text-slate-700 text-xs font-medium px-3 py-1 rounded-full mb-3">
          <i class="fas fa-tag mr-1"></i>{{$item->category}}
        </span>

        <!-- Platform Badge -->
        <div class="flex justify-center">
          @if($item->platform === 'grab')
            <span class="platform-badge bg-green-100 text-green-700 text-xs font-bold px-4 py-2 rounded-full border-2 border-green-500">
              <i class="fas fa-car mr-1"></i>Grab
            </span>
          @elseif($item->platform === 'foodpanda')
            <span class="platform-badge bg-pink-100 text-pink-700 text-xs font-bold px-4 py-2 rounded-full border-2 border-pink-500">
              <i class="fas fa-motorcycle mr-1"></i>foodPanda
            </span>
          @else
            <span class="platform-badge bg-cyan-100 text-cyan-700 text-xs font-bold px-4 py-2 rounded-full border-2 border-cyan-500">
              <i class="fas fa-bicycle mr-1"></i>Deliveroo
            </span>
          @endif
        </div>
      </div>
    </div>
    @endforeach
  </div>

  <!-- Empty State -->
  <div id="emptyState" class="hidden text-center py-16">
    <i class="fas fa-search text-6xl text-slate-300 mb-4"></i>
    <h3 class="text-xl font-semibold text-slate-600 mb-2">No items found</h3>
    <p class="text-slate-500">Try adjusting your filters or search terms</p>
  </div>
@endsection

@section('extra-scripts')
<script>
  const items = document.querySelectorAll('.item-card');
  const searchInput = document.getElementById('searchInput');
  const restaurantFilter = document.getElementById('restaurantFilter');
  const categoryFilter = document.getElementById('categoryFilter');
  const platformFilter = document.getElementById('platformFilter');
  const availableOnly = document.getElementById('availableOnly');
  const resultCount = document.getElementById('resultCount');
  const emptyState = document.getElementById('emptyState');
  const itemsGrid = document.getElementById('itemsGrid');

  function filterItems() {
    const searchTerm = searchInput.value.toLowerCase();
    const selectedRestaurant = restaurantFilter.value;
    const selectedCategory = categoryFilter.value;
    const selectedPlatform = platformFilter.value;
    const availableFilter = availableOnly.checked;

    let visibleCount = 0;

    items.forEach(item => {
      const itemName = item.dataset.name;
      const itemCategory = item.dataset.category;
      const itemRestaurant = item.dataset.restaurant;
      const itemPlatform = item.dataset.platform;
      const itemAvailable = item.dataset.available === '1';

      const matchesSearch = itemName.includes(searchTerm) ||
                           itemCategory.toLowerCase().includes(searchTerm) ||
                           itemRestaurant.toLowerCase().includes(searchTerm);
      const matchesRestaurant = !selectedRestaurant || itemRestaurant === selectedRestaurant;
      const matchesCategory = !selectedCategory || itemCategory === selectedCategory;
      const matchesPlatform = !selectedPlatform || itemPlatform === selectedPlatform;
      const matchesAvailability = !availableFilter || itemAvailable;

      if (matchesSearch && matchesRestaurant && matchesCategory && matchesPlatform && matchesAvailability) {
        item.style.display = 'block';
        visibleCount++;
      } else {
        item.style.display = 'none';
      }
    });

    resultCount.textContent = visibleCount;

    if (visibleCount === 0) {
      itemsGrid.classList.add('hidden');
      emptyState.classList.remove('hidden');
    } else {
      itemsGrid.classList.remove('hidden');
      emptyState.classList.add('hidden');
    }
  }

  // Event listeners
  searchInput.addEventListener('input', filterItems);
  restaurantFilter.addEventListener('change', filterItems);
  categoryFilter.addEventListener('change', filterItems);
  platformFilter.addEventListener('change', filterItems);
  availableOnly.addEventListener('change', filterItems);
</script>
@endsection
