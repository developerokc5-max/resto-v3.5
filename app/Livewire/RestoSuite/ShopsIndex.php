<?php

namespace App\Livewire\RestoSuite;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\ShopService;

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
        // Use ShopService to get shops with statistics
        // Moved database logic to service layer (PHP)
        $shops = ShopService::getAllShopsWithStats($this->q, 25);

        return view('livewire.resto-suite.shops-index', [
            'shops' => $shops,
        ])->layout('layouts.app', [
            'title' => 'Shops - HawkerOps',
            'pageHeading' => 'Shops',
            'subtitle' => 'All shops and current OFF counts',
        ]);
    }
}
