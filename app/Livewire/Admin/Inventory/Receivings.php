<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\StockReceiving;
use App\Models\Supplier;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Goods receiving history (GRN list). */
class Receivings extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $supplier = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    public function updated(string $name): void
    {
        if (in_array($name, ['search', 'supplier', 'status', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function render(): mixed
    {
        $q = StockReceiving::query()
            ->with(['supplier:id,name', 'purchaseOrder:id,order_number', 'warehouse:id,name', 'createdBy:id,name'])
            ->when($this->search !== '', function ($q) {
                $s = '%' . trim($this->search) . '%';
                $q->where(fn ($w) => $w->where('grn_number', 'like', $s)->orWhere('reference', 'like', $s)
                    ->orWhereHas('purchaseOrder', fn ($p) => $p->where('order_number', 'like', $s))
                    ->orWhereHas('items', fn ($i) => $i->where('batch_no', 'like', $s)));
            })
            ->when($this->supplier !== '', fn ($q) => $q->where('supplier_id', $this->supplier))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->from !== '', fn ($q) => $q->whereDate('received_at', '>=', $this->from))
            ->when($this->to !== '', fn ($q) => $q->whereDate('received_at', '<=', $this->to));

        $summary = (clone $q)->where('status', 'received')->selectRaw('COUNT(*) as n, COALESCE(SUM(total_quantity), 0) as qty, COALESCE(SUM(total_cost), 0) as cost')->first();

        return view('livewire.admin.inventory.receivings', [
            'receivings' => $q->orderByDesc('received_at')->orderByDesc('id')->paginate(25),
            'suppliers' => Supplier::orderBy('name')->pluck('name', 'id'),
            'summary' => $summary,
        ])->layout('layouts.admin.admin');
    }
}
