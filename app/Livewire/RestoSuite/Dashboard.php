<?php

namespace App\Livewire\RestoSuite;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    // Mark properties as non-reactive to prevent unnecessary re-renders
    #[\Livewire\Attributes\Reactive]
    public array $kpis = [];

    public $topOff;

    public function mount(): void
    {
        // Combined query: get snapshots stats + changes stats in fewer queries
        $snapshotStats = DB::table('restosuite_item_snapshots')
            ->select(
                DB::raw('MAX(run_id) as last_run'),
                DB::raw('COUNT(DISTINCT shop_id) as shops'),
                DB::raw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as items_off')
            )
            ->first();

        $changesToday = DB::table('restosuite_item_changes')
            ->whereDate('created_at', now()->toDateString())
            ->count();

        $this->kpis = [
            'shops' => (int) ($snapshotStats->shops ?? 0),
            'items_off' => (int) ($snapshotStats->items_off ?? 0),
            'changes_today' => (int) $changesToday,
            'last_run' => $snapshotStats->last_run,
        ];

        // Get top 10 offline items
        $this->topOff = DB::table('restosuite_item_snapshots')
            ->where('is_active', 0)
            ->orderByDesc('id')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.resto-suite.dashboard')
            ->layout('layouts.app', [
                'title' => 'HawkerOps Dashboard',
                'pageHeading' => 'Overview',
                'subtitle' => 'Monitor items disabled during peak hours',
            ]);
    }
}
