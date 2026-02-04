<?php

namespace App\Livewire\RestoSuite;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use App\Services\ShopService;

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
     * Get offline items count - cached in ShopService
     * Only recalculates when component rerenders with new shop_id
     */
    #[Computed(cache: true)]
    public function itemsOff(): int
    {
        return ShopService::getOfflineItemsCountCached($this->shopId);
    }

    public function render()
    {
        // Use ShopService to get items for this shop
        // Moved database logic to service layer (PHP)
        $items = ShopService::getShopItems($this->shopId, $this->q, 25);

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
