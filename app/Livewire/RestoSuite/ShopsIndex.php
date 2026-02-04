<?php

namespace App\Livewire\RestoSuite;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Services\CacheService;

class ShopsIndex extends Component
{
    use WithPagination;

    public string $q = '';

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $shops = DB::table('restosuite_item_snapshots')
            ->select('shop_id', 'shop_name', 'brand_name')
            ->selectRaw('MAX(created_at) as last_seen')
            ->selectRaw('SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as items_off')
            ->when($this->q !== '', function ($query) {
                $query->where(function ($sub) {
                    $sub->where('shop_name', 'like', '%' . $this->q . '%')
                        ->orWhere('brand_name', 'like', '%' . $this->q . '%')
                        ->orWhere('shop_id', 'like', '%' . $this->q . '%');
                });
            })
            ->groupBy('shop_id', 'shop_name', 'brand_name')
            ->orderByDesc('last_seen')
            ->paginate(25);

        return view('livewire.resto-suite.shops-index', [
            'shops' => $shops,
        ])->layout('layouts.app', [
            'title' => 'Shops - HawkerOps',
            'pageHeading' => 'Shops',
            'subtitle' => 'All shops and current OFF counts',
        ]);
    }
}
