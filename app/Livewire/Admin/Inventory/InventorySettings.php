<?php

namespace App\Livewire\Admin\Inventory;

use App\Models\Setting;
use App\Models\Warehouse;
use Livewire\Component;

class InventorySettings extends Component
{
    protected const GROUP = 'inventory';

    public int $lowStockThreshold = 5;
    public bool $allowNegativeStock = false;

    public bool $deductOnOrderConfirm = true;
    public bool $restockOnCancelOrReturn = true;

    public $defaultWarehouseId = '';

    public function mount(): void
    {
        $this->lowStockThreshold = (int) Setting::get('low_stock_threshold', 5, static::GROUP);
        $this->allowNegativeStock = (bool) Setting::get('allow_negative_stock', false, static::GROUP);

        $this->deductOnOrderConfirm = (bool) Setting::get('deduct_on_order_confirm', true, static::GROUP);
        $this->restockOnCancelOrReturn = (bool) Setting::get('restock_on_cancel_or_return', true, static::GROUP);

        $this->defaultWarehouseId = (string) (Setting::get('default_warehouse_id', '', static::GROUP) ?: '');
    }

    public function save(): void
    {
        $this->validate([
            'lowStockThreshold' => 'required|integer|min:0|max:1000',
            'defaultWarehouseId' => 'nullable|integer|exists:warehouses,id',
        ]);

        if ($this->defaultWarehouseId !== '') {
            Warehouse::where('is_default', true)->update(['is_default' => false]);
            Warehouse::whereKey($this->defaultWarehouseId)->update(['is_default' => true]);
        }

        $values = [
            'low_stock_threshold' => $this->lowStockThreshold,
            'allow_negative_stock' => $this->allowNegativeStock,
            'deduct_on_order_confirm' => $this->deductOnOrderConfirm,
            'restock_on_cancel_or_return' => $this->restockOnCancelOrReturn,
            'default_warehouse_id' => $this->defaultWarehouseId ?: null,
        ];

        foreach ($values as $key => $value) {
            Setting::set($key, $value, static::GROUP);
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Inventory settings saved']);
    }

    public function render(): mixed
    {
        return view('livewire.admin.inventory.inventory-settings', [
            'warehouses' => Warehouse::orderByDesc('is_default')->orderBy('name')->get(['id', 'name', 'is_default']),
        ])->layout('layouts.admin.admin');
    }
}
