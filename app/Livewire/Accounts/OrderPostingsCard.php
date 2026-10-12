<?php

namespace App\Livewire\Accounts;

use App\Models\Order;
use App\Services\Accounts\OrderPosting;
use App\Support\AccountsAccess;
use App\Support\Bdt;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * Delivered / returned orders waiting to be posted, shown inside Posting
 * Pending and on the dashboard's Posting Pending card. Hidden when empty.
 */
class OrderPostingsCard extends Component
{
    public int $limit = 8;

    #[Computed]
    public function items(): Collection
    {
        if (! OrderPosting::accountsOn()) {
            return collect();
        }
        $svc = app(OrderPosting::class);

        return $svc->sales()->map(fn ($r) => $r + ['kind' => 'sale', 'amount' => (float) $r['order']->total_amount])
            ->concat($svc->returns()->map(fn ($r) => $r + ['kind' => 'return', 'blocked' => null]))
            ->values();
    }

    public function post(int $id, string $kind): void
    {
        AccountsAccess::authorize('post');
        try {
            $order = Order::findOrFail($id);
            $kind === 'return' ? app(OrderPosting::class)->postReturn($order) : app(OrderPosting::class)->postSale($order);
            $this->dispatch('toast', type: 'success', message: "Order #{$id} " . ($kind === 'return' ? 'return' : 'sale') . ' posted.');
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
        unset($this->items);
    }

    public function postAllReady(): void
    {
        AccountsAccess::authorize('post');
        $items = $this->items->whereNull('blocked');
        [$posted, $skipped] = app(OrderPosting::class)->postMany(
            $items->where('kind', 'sale')->pluck('order.id')->all(),
            $items->where('kind', 'return')->pluck('order.id')->all(),
        );
        unset($this->items);
        $this->dispatch('toast', type: $skipped ? 'warning' : 'success', message: "{$posted} order(s) posted." . ($skipped ? ' Skipped — ' . implode('; ', array_slice($skipped, 0, 3)) : ''));
    }

    public function render()
    {
        return view('livewire.accounts.order-postings-card', ['money' => fn ($v) => Bdt::money($v)]);
    }
}
