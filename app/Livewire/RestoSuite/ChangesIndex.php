<?php

namespace App\Livewire\RestoSuite;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RestoSuiteItemChange;

class ChangesIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $q = '';

    // Reset pagination when searching
    public function updatedQ(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $changes = RestoSuiteItemChange::query()
            ->when($this->q !== '', function ($query) {
                $query->where('shop_id', 'like', '%' . $this->q . '%')
                      ->orWhere('item_id', 'like', '%' . $this->q . '%')
                      ->orWhere('change_type', 'like', '%' . $this->q . '%');
            })
            ->orderByDesc('id')
            ->paginate(50);

        return view('livewire.resto-suite.changes-index', [
            'changes' => $changes,
        ])->layout('layouts.app', [
            'title'       => 'Changes - HawkerOps',
            'pageHeading' => 'Item Changes',
            'subtitle'    => 'Live history of menu availability changes',
        ]);
    }
}
