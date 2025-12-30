<?php

namespace App\Livewire\RestoSuite;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class ShopItems extends Component
{
    use WithPagination;

    public string $shopId;
    public string $q = '';

    public function mount(string $shopId): void
    {
        $this->shopId = $shopId;
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function getItemsOffProperty(): int
    {
        return (int) DB::table('restosuite_item_snapshots')
            ->where('shop_id', $this->shopId)
            ->where('is_active', 0)
            ->count();
    }

    public function render()
    {
        $items = DB::table('restosuite_item_snapshots')
            ->where('shop_id', $this->shopId)
            ->when($this->q !== '', function ($query) {
                $query->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->q . '%')
                        ->orWhere('item_id', 'like', '%' . $this->q . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate(25);

        return view('livewire.resto-suite.shop-items', [
            'items' => $items,
        ])->layout('layouts.app', [
            'title' => 'Shop Items - HawkerOps',
            'pageHeading' => 'Shop items',
            'subtitle' => 'Items and last known status',
        ]);
    }
}
