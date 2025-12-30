<?php

namespace App\Livewire\RestoSuite;

use Livewire\Component;
use App\Models\RestoSuiteItemSnapshot;
use App\Models\RestoSuiteItemChange;

class ItemDetail extends Component
{
    public int $snapshotId;

    public ?RestoSuiteItemSnapshot $snapshot = null;
    public int $lastRun = 0;

    public function mount(int $snapshotId): void
    {
        $this->snapshotId = $snapshotId;

        $this->snapshot = RestoSuiteItemSnapshot::query()
            ->whereKey($this->snapshotId)
            ->first();

        $this->lastRun = (int) (RestoSuiteItemSnapshot::query()->max('run_id') ?? 0);
    }

    public function render()
    {
        $history = collect();

        if ($this->snapshot) {
            $history = RestoSuiteItemChange::query()
                ->where('shop_id', $this->snapshot->shop_id)
                ->where('item_id', $this->snapshot->item_id)
                ->latest('id')
                ->limit(200)
                ->get();
        }

        return view('livewire.resto-suite.item-detail', [
            'snapshot' => $this->snapshot,
            'history'  => $history,
            'lastRun'  => $this->lastRun,
        ])->layout('layouts.app', [
            'title'       => 'Item Detail - HawkerOps',
            'pageHeading' => $this->snapshot?->name ?? 'Item detail',
            'subtitle'    => 'Snapshot + history (latest 200)',
        ]);
    }
}
