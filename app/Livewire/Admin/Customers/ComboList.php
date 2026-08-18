<?php

namespace App\Livewire\Admin\Customers;

use App\Models\Combo;
use Livewire\Component;
use Livewire\WithPagination;

class ComboList extends Component
{
    use WithPagination;

    public string $search = '';

    protected string $paginationTheme = 'tailwind';

    /** @var array<int, int> Combo IDs currently expanded in the table. */
    public array $expanded = [];

    public function updatingSearch(): void { $this->resetPage(); }

    public function toggleExpand(int $id): void
    {
        if (in_array($id, $this->expanded)) {
            $this->expanded = array_values(array_diff($this->expanded, [$id]));
        } else {
            $this->expanded[] = $id;
        }
    }

    public function render(): mixed
    {
        $combos = Combo::query()
            ->with('customer', 'device', 'items.product', 'items.variant')
            ->withCount('items')
            ->when($this->search, fn($q) => $q->where(fn($s) => $s
                ->whereHas('customer', fn($c) => $c
                    ->where('full_name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%"))
                ->orWhere('name', 'like', "%{$this->search}%")
            ))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.admin.customers.combo-list', [
            'combos'     => $combos,
            'totalCount' => Combo::count(),
        ])->layout('layouts.admin.admin');
    }
}
