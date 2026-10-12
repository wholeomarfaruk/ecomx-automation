<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\StockReceiving;
use App\Services\Inventory\StockReceivingService;
use Livewire\Component;

/** One goods receiving (GRN): lines, batches, print, cancel. */
class ReceivingShow extends Component
{
    public StockReceiving $receiving;

    public bool $showCancel = false;

    public string $cancelReason = '';

    public function mount(StockReceiving $receiving): void
    {
        $this->receiving = $receiving;

        if ($toast = session('toast')) {
            $this->dispatch('toast', $toast);
        }
    }

    public function cancel(): void
    {
        $this->validate(['cancelReason' => 'required|string|min:3|max:255'], [], ['cancelReason' => 'reason']);

        app(StockReceivingService::class)->cancel($this->receiving, $this->cancelReason);

        $this->showCancel = false;
        $this->receiving->refresh();
        $this->dispatch('toast', ['type' => 'success', 'message' => "{$this->receiving->grn_number} cancelled — stock taken back out of its batches."]);
    }

    public function render(): mixed
    {
        $this->receiving->load([
            'items.product:id,name', 'items.variant.values.productAttributeValue.attributeValue.attribute', 'items.batch:id,quantity,status',
            'supplier:id,name,phone', 'purchaseOrder:id,order_number', 'warehouse:id,name', 'createdBy:id,name', 'cancelledBy:id,name',
        ]);

        return view('livewire.admin.inventory.receiving-show')->layout('layouts.admin.admin');
    }
}
