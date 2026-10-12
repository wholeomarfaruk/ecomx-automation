<?php

namespace App\Livewire\Accounts;

use App\Models\Order;
use App\Services\Accounts\OrderPosting;
use App\Support\AccountsAccess;
use App\Support\AccountsSettings;
use App\Support\Bdt;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Throwable;

/**
 * Accounts Panel › Posting Pending › Orders: delivered orders whose sale +
 * COGS isn't in the books yet, and returns whose reversal isn't. Post one,
 * post selected, or let the scheduler auto-post.
 */
#[Title('Orders to post')]
#[Layout('layouts.accounts.app')]
class OrderPostings extends Component
{
    #[Url(except: 'sales')]
    public string $tab = 'sales';

    public function mount(): void
    {
        AccountsAccess::authorize('view');
    }

    #[Computed]
    public function sales(): Collection
    {
        return app(OrderPosting::class)->sales();
    }

    #[Computed]
    public function returns(): Collection
    {
        return app(OrderPosting::class)->returns();
    }

    public function postOne(int $id, string $kind = 'sale'): void
    {
        AccountsAccess::authorize('post');
        $order = Order::findOrFail($id);
        try {
            $kind === 'return' ? app(OrderPosting::class)->postReturn($order) : app(OrderPosting::class)->postSale($order);
            $this->dispatch('toast', type: 'success', message: "Order #{$id} " . ($kind === 'return' ? 'return' : 'sale') . ' posted.');
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
        unset($this->sales, $this->returns);
    }

    public function postSelected(array $ids): void
    {
        AccountsAccess::authorize('post');
        $ids = array_values(array_filter(array_map('intval', $ids)));
        [$posted, $skipped] = $this->tab === 'returns'
            ? app(OrderPosting::class)->postMany([], $ids)
            : app(OrderPosting::class)->postMany($ids);

        unset($this->sales, $this->returns);
        $this->dispatch('clear-selection');
        $this->dispatch('toast', type: $skipped ? 'warning' : 'success',
            message: "{$posted} posted." . ($skipped ? ' Skipped — ' . implode('; ', array_slice($skipped, 0, 5)) . (count($skipped) > 5 ? ' …' : '') : ''));
    }

    public function toggleAuto(): void
    {
        AccountsAccess::authorize('settings');
        AccountsSettings::set(OrderPosting::AUTO_KEY, ! OrderPosting::autoPost());
        $this->dispatch('toast', type: 'success', message: OrderPosting::autoPost() ? 'Auto-post on — ready orders post every hour.' : 'Auto-post off.');
    }

    public function render()
    {
        return view('livewire.accounts.order-postings', [
            'money' => fn ($v) => Bdt::money($v),
            'accountsOn' => OrderPosting::accountsOn(),
            'auto' => OrderPosting::autoPost(),
        ])->layoutData(['active' => 'posting', 'heading' => 'Orders to post', 'crumb' => 'Accounts / Posting Pending / Orders']);
    }
}
