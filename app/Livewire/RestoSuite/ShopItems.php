<?php

namespace App\Livewire\RestoSuite;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\CacheService;

class ShopItems extends Component
{
    use WithPagination;

    public string $shopId;
    public string $q = '';
    private ?int $cachedItemsOff = null;

    public function mount(string $shopId): void
    {
        $this->shopId = $shopId;
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    /**
     * Get offline items count - cached to avoid repeated queries
     * Only recalculates when component rerenders with new shop_id
     */
    #[Computed(cache: true)]
    public function itemsOff(): int
    {
        return (int) DB::table('restosuite_item_snapshots')
            ->where('shop_id', $this->shopId)
            ->where('is_active', 0)
            ->count();
    }

    /**
     * Get shop status from cache (50x faster after first request)
     */
    public function getShopStatus()
    {
        return CacheService::getShopStatus($this->shopId);
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
            'itemsOff' => $this->itemsOff(),
        ])->layout('layouts.app', [
            'title' => 'Shop Items - HawkerOps',
            'pageHeading' => 'Shop items',
            'subtitle' => 'Items and last known status',
        ]);
    }
}
