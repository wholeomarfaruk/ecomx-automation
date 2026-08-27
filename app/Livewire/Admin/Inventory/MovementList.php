<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\InventoryStockMovement;
use Livewire\Component;
use Livewire\WithPagination;

class MovementList extends Component
{
    use WithPagination;

    public $search = '';
    public $filterType = '';

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterType(): void { $this->resetPage(); }

    protected function buildQuery()
    {
        return InventoryStockMovement::query()
            ->with(['product', 'variant', 'warehouse', 'createdBy'])
            ->when($this->filterType !== '', fn ($q) => $q->where('type', $this->filterType))
            ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                ->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('variant', fn ($v) => $v->where('sku', 'like', "%{$this->search}%"))
            ))
            ->orderByDesc('id');
    }

    public function render(): mixed
    {
        return view('livewire.admin.inventory.movement-list', [
            'movements' => $this->buildQuery()->paginate(25),
            'totalCount' => InventoryStockMovement::count(),
        ])->layout('layouts.admin.admin');
    }
}
