<?php

namespace App\Livewire\RestoSuite;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public array $kpis = [];
    public $topOff;

    public function mount(): void
    {
        $lastRun = DB::table('restosuite_item_snapshots')->max('run_id');

        $this->kpis = [
            'shops' => (int) DB::table('restosuite_item_snapshots')->distinct('shop_id')->count('shop_id'),
            'items_off' => (int) DB::table('restosuite_item_snapshots')->where('is_active', 0)->count(),
            'changes_today' => (int) DB::table('restosuite_item_changes')
                ->whereDate('created_at', now()->toDateString())
                ->count(),
            'last_run' => $lastRun,
        ];

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
