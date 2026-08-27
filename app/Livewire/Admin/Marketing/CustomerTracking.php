<?php

namespace App\Livewire\Admin\Marketing;

use App\Marketing\Enums\MarketingEventName;
use App\Models\Customer;
use App\Models\Marketing\MarketingEvent;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin.admin')]
class CustomerTracking extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public function updatingSearch(): void { $this->resetPage(); }

    public function render()
    {
        $customerIds = MarketingEvent::query()->whereNotNull('customer_id')->distinct()->pluck('customer_id');

        $customers = Customer::query()
            ->whereIn('id', $customerIds)
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('full_name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
            ))
            ->paginate(20);

        $customers->getCollection()->transform(function (Customer $customer) {
            $events = MarketingEvent::query()->where('customer_id', $customer->id);

            $first = (clone $events)->with('attribution')->orderBy('occurred_at')->first();
            $last = (clone $events)->with('attribution')->orderByDesc('occurred_at')->first();
            $purchases = (clone $events)->where('event_name', MarketingEventName::PURCHASE->value)->count();
            $revenue = (clone $events)->where('event_name', MarketingEventName::PURCHASE->value)->sum('value');

            $customer->setAttribute('_first_source', $first?->attribution?->first_touch_source ?? $first?->utm_source);
            $customer->setAttribute('_last_source', $last?->attribution?->last_touch_source ?? $last?->utm_source);
            $customer->setAttribute('_events_count', (clone $events)->count());
            $customer->setAttribute('_purchases', $purchases);
            $customer->setAttribute('_revenue', (float) $revenue);
            $customer->setAttribute('_last_seen', $last?->occurred_at);

            return $customer;
        });

        return view('livewire.admin.marketing.customer-tracking', [
            'customers' => $customers,
        ]);
    }
}
