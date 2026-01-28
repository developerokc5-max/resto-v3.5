@extends('layout')

@section('title', 'Store Comparison - HawkerOps')

@section('page-title', 'Store Comparison')
@section('page-description', 'Compare performance across multiple stores')

@section('content')
  <!-- Store Selector -->
  <section class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <h2 class="text-lg font-bold text-slate-900 mb-4">Select Stores to Compare (up to 3)</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <select class="px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent font-medium">
        <option>Select Store 1</option>
        <option>McDonald's Jurong Point</option>
        <option>KFC Tampines Mall</option>
        <option>Subway Orchard</option>
      </select>
      <select class="px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent font-medium">
        <option>Select Store 2</option>
        <option>McDonald's Jurong Point</option>
        <option>KFC Tampines Mall</option>
        <option>Subway Orchard</option>
      </select>
      <select class="px-4 py-3 border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-slate-900 focus:border-transparent font-medium">
        <option>Select Store 3 (Optional)</option>
        <option>McDonald's Jurong Point</option>
        <option>KFC Tampines Mall</option>
        <option>Subway Orchard</option>
      </select>
    </div>
    <button class="mt-4 px-6 py-3 bg-slate-900 text-white rounded-xl font-medium hover:opacity-90 transition">
      Compare Stores
    </button>
  </section>

  <!-- Comparison Table -->
  <section class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Performance Comparison</h2>

    <div class="overflow-x-auto">
      <table class="w-full">
        <thead>
          <tr class="border-b-2 border-slate-200">
            <th class="text-left py-3 px-4 text-sm font-semibold text-slate-900">Metric</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-slate-900">Store 1</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-slate-900">Store 2</th>
            <th class="text-center py-3 px-4 text-sm font-semibold text-slate-900">Store 3</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b border-slate-100">
            <td class="py-3 px-4 text-sm font-medium text-slate-700">Overall Status</td>
            <td class="py-3 px-4 text-center">
              <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold">All Online</span>
            </td>
            <td class="py-3 px-4 text-center">
              <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">Mixed</span>
            </td>
            <td class="py-3 px-4 text-center">
              <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold">All Online</span>
            </td>
          </tr>
          <tr class="border-b border-slate-100">
            <td class="py-3 px-4 text-sm font-medium text-slate-700">Platforms Online</td>
            <td class="py-3 px-4 text-center text-sm font-bold">3/3</td>
            <td class="py-3 px-4 text-center text-sm font-bold">2/3</td>
            <td class="py-3 px-4 text-center text-sm font-bold">3/3</td>
          </tr>
          <tr class="border-b border-slate-100">
            <td class="py-3 px-4 text-sm font-medium text-slate-700">Total Menu Items</td>
            <td class="py-3 px-4 text-center text-sm">142</td>
            <td class="py-3 px-4 text-center text-sm">98</td>
            <td class="py-3 px-4 text-center text-sm">156</td>
          </tr>
          <tr class="border-b border-slate-100">
            <td class="py-3 px-4 text-sm font-medium text-slate-700">Offline Items</td>
            <td class="py-3 px-4 text-center text-sm font-bold text-green-600">0</td>
            <td class="py-3 px-4 text-center text-sm font-bold text-red-600">12</td>
            <td class="py-3 px-4 text-center text-sm font-bold text-green-600">2</td>
          </tr>
          <tr class="border-b border-slate-100">
            <td class="py-3 px-4 text-sm font-medium text-slate-700">Item Availability %</td>
            <td class="py-3 px-4 text-center text-sm font-bold text-green-600">100%</td>
            <td class="py-3 px-4 text-center text-sm font-bold text-amber-600">87.8%</td>
            <td class="py-3 px-4 text-center text-sm font-bold text-green-600">98.7%</td>
          </tr>
          <tr class="border-b border-slate-100">
            <td class="py-3 px-4 text-sm font-medium text-slate-700">7-Day Uptime</td>
            <td class="py-3 px-4 text-center text-sm">99.8%</td>
            <td class="py-3 px-4 text-center text-sm">96.2%</td>
            <td class="py-3 px-4 text-center text-sm">99.5%</td>
          </tr>
          <tr class="border-b border-slate-100">
            <td class="py-3 px-4 text-sm font-medium text-slate-700">Incidents (7 days)</td>
            <td class="py-3 px-4 text-center text-sm">1</td>
            <td class="py-3 px-4 text-center text-sm">8</td>
            <td class="py-3 px-4 text-center text-sm">2</td>
          </tr>
          <tr class="border-b border-slate-100">
            <td class="py-3 px-4 text-sm font-medium text-slate-700">Last Sync</td>
            <td class="py-3 px-4 text-center text-sm text-slate-600">2 min ago</td>
            <td class="py-3 px-4 text-center text-sm text-slate-600">5 min ago</td>
            <td class="py-3 px-4 text-center text-sm text-slate-600">1 min ago</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- Platform Breakdown -->
  <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white border-2 border-slate-200 rounded-2xl p-6 shadow-sm">
      <h3 class="font-bold text-slate-900 mb-4 text-center">Store 1 Platform Status</h3>
      <div class="space-y-3">
        <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
          <span class="text-sm font-medium">Grab</span>
          <span class="px-3 py-1 bg-green-600 text-white rounded-full text-xs font-bold">ONLINE</span>
        </div>
        <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
          <span class="text-sm font-medium">FoodPanda</span>
          <span class="px-3 py-1 bg-green-600 text-white rounded-full text-xs font-bold">ONLINE</span>
        </div>
        <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
          <span class="text-sm font-medium">Deliveroo</span>
          <span class="px-3 py-1 bg-green-600 text-white rounded-full text-xs font-bold">ONLINE</span>
        </div>
      </div>
    </div>

    <div class="bg-white border-2 border-slate-200 rounded-2xl p-6 shadow-sm">
      <h3 class="font-bold text-slate-900 mb-4 text-center">Store 2 Platform Status</h3>
      <div class="space-y-3">
        <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
          <span class="text-sm font-medium">Grab</span>
          <span class="px-3 py-1 bg-green-600 text-white rounded-full text-xs font-bold">ONLINE</span>
        </div>
        <div class="flex items-center justify-between p-3 bg-red-50 border border-red-200 rounded-lg">
          <span class="text-sm font-medium">FoodPanda</span>
          <span class="px-3 py-1 bg-red-600 text-white rounded-full text-xs font-bold">OFFLINE</span>
        </div>
        <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
          <span class="text-sm font-medium">Deliveroo</span>
          <span class="px-3 py-1 bg-green-600 text-white rounded-full text-xs font-bold">ONLINE</span>
        </div>
      </div>
    </div>

    <div class="bg-white border-2 border-slate-200 rounded-2xl p-6 shadow-sm">
      <h3 class="font-bold text-slate-900 mb-4 text-center">Store 3 Platform Status</h3>
      <div class="space-y-3">
        <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
          <span class="text-sm font-medium">Grab</span>
          <span class="px-3 py-1 bg-green-600 text-white rounded-full text-xs font-bold">ONLINE</span>
        </div>
        <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
          <span class="text-sm font-medium">FoodPanda</span>
          <span class="px-3 py-1 bg-green-600 text-white rounded-full text-xs font-bold">ONLINE</span>
        </div>
        <div class="flex items-center justify-between p-3 bg-green-50 border border-green-200 rounded-lg">
          <span class="text-sm font-medium">Deliveroo</span>
          <span class="px-3 py-1 bg-green-600 text-white rounded-full text-xs font-bold">ONLINE</span>
        </div>
      </div>
    </div>
  </section>
@endsection
