@extends('layout')

@section('title', 'Alerts - HawkerOps')

@section('page-title', 'Alerts')
@section('page-description', 'Real-time notifications for platform and store issues')

@section('content')
  <!-- Stats Cards -->
  <section class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <div class="bg-red-50 border-2 border-red-200 rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-red-700 font-medium">Critical Alerts</p>
          <p class="text-3xl font-semibold text-red-900 mt-1">{{ $stats['critical'] ?? 0 }}</p>
        </div>
        <div class="text-4xl">🚨</div>
      </div>
    </div>
    <div class="bg-amber-50 border-2 border-amber-200 rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-amber-700 font-medium">Warnings</p>
          <p class="text-3xl font-semibold text-amber-900 mt-1">{{ $stats['warnings'] ?? 0 }}</p>
        </div>
        <div class="text-4xl">⚠️</div>
      </div>
    </div>
    <div class="bg-blue-50 border-2 border-blue-200 rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-blue-700 font-medium">Info</p>
          <p class="text-3xl font-semibold text-blue-900 mt-1">{{ $stats['info'] ?? 0 }}</p>
        </div>
        <div class="text-4xl">ℹ️</div>
      </div>
    </div>
    <div class="bg-green-50 border-2 border-green-200 rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-green-700 font-medium">Resolved</p>
          <p class="text-3xl font-semibold text-green-900 mt-1">{{ $stats['resolved'] ?? 0 }}</p>
        </div>
        <div class="text-4xl">✅</div>
      </div>
    </div>
  </section>

  <!-- Active Alerts -->
  <section class="bg-white rounded-2xl shadow-sm p-6">
    <h2 class="text-xl font-bold text-slate-900 mb-4">Active Alerts</h2>

    <div class="space-y-3">
      @forelse($alerts as $alert)
        <div class="border-2 @if($alert['type'] === 'critical') border-red-300 bg-red-50 @elseif($alert['type'] === 'warning') border-amber-300 bg-amber-50 @else border-blue-300 bg-blue-50 @endif rounded-xl p-4">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                @if($alert['type'] === 'critical')
                  <span class="text-2xl">🚨</span>
                  <span class="px-3 py-1 bg-red-600 text-white text-xs font-bold rounded-full">CRITICAL</span>
                @elseif($alert['type'] === 'warning')
                  <span class="text-2xl">⚠️</span>
                  <span class="px-3 py-1 bg-amber-600 text-white text-xs font-bold rounded-full">WARNING</span>
                @else
                  <span class="text-2xl">ℹ️</span>
                  <span class="px-3 py-1 bg-blue-600 text-white text-xs font-bold rounded-full">INFO</span>
                @endif
                <span class="text-xs text-slate-500">{{ $alert['time'] }}</span>
              </div>
              <h3 class="font-bold text-slate-900 mb-1">{{ $alert['title'] }}</h3>
              <p class="text-sm text-slate-600">{{ $alert['message'] }}</p>
              @if(isset($alert['store']))
                <p class="text-xs text-slate-500 mt-2">Store: {{ $alert['store'] }}</p>
              @endif
            </div>
            <button class="px-4 py-2 bg-slate-900 text-white rounded-lg text-sm font-medium hover:opacity-90 transition">
              Resolve
            </button>
          </div>
        </div>
      @empty
        <div class="text-center py-12">
          <div class="text-6xl mb-4">✨</div>
          <h3 class="text-xl font-semibold text-slate-600 mb-2">All Clear!</h3>
          <p class="text-slate-500">No active alerts at the moment</p>
        </div>
      @endforelse
    </div>
  </section>
@endsection
