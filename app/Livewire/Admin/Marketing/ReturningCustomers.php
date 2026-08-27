<?php

namespace App\Livewire\Admin\Marketing;

use App\Marketing\Enums\MarketingEventName;
use App\Models\Customer;
use App\Models\Marketing\MarketingEvent;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class ReturningCustomers extends Component
{
    public function render()
    {
        $customerPurchaseCounts = MarketingEvent::query()
            ->where('event_name', MarketingEventName::PURCHASE->value)
            ->whereNotNull('customer_id')
            ->selectRaw('customer_id, COUNT(DISTINCT order_id) as orders, SUM(value) as revenue')
            ->groupBy('customer_id')
            ->having('orders', '>', 1)
            ->orderByDesc('orders')
            ->get();

        $customers = Customer::query()
            ->whereIn('id', $customerPurchaseCounts->pluck('customer_id'))
            ->get()
            ->keyBy('id');

        $rows = $customerPurchaseCounts->map(function ($row) use ($customers) {
            $customer = $customers->get($row->customer_id);

            // "What brought them back" — the last-touch source on their
            // most recent purchase, which is what actually drove the
            // repeat order (not their original first-touch acquisition).
            $lastPurchase = MarketingEvent::query()
                ->with('attribution')
                ->where('customer_id', $row->customer_id)
                ->where('event_name', MarketingEventName::PURCHASE->value)
                ->orderByDesc('occurred_at')
                ->first();

            return [
                'customer' => $customer,
                'orders' => (int) $row->orders,
                'revenue' => (float) $row->revenue,
                'brought_back_by' => $lastPurchase?->attribution?->last_touch_source ?? $lastPurchase?->utm_source ?? 'direct',
            ];
        })->filter(fn ($row) => $row['customer'] !== null)->values();

        return view('livewire.admin.marketing.returning-customers', [
            'rows' => $rows,
        ]);
    }
}
