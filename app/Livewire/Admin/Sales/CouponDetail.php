<?php

namespace App\Livewire\Admin\Sales;

use App\Livewire\Traits\WithPromotionForm;
use App\Models\Coupon;
use App\Models\Customer;
use Livewire\Component;

class CouponDetail extends Component
{
    use WithPromotionForm;

    public int $couponId;

    public string $code                    = '';
    public string $usageLimit              = '';
    public string $usageLimitPerCustomer   = '';
    public string $minOrderAmount          = '';
    public string $maxDiscountAmount       = '';

    /** @var array<int, int> */
    public array $selectedCustomerIds = [];
    public string $customerSearch = '';

    public function mount(int $id): void
    {
        $coupon = Coupon::with('promotion.conditions', 'promotion.discountRules', 'customers')->findOrFail($id);
        $promotion = $coupon->promotion;

        $this->couponId = $coupon->id;

        $this->name        = $promotion->name;
        $this->description = $promotion->description ?? '';
        $this->status       = $promotion->status->value;
        $this->priority     = (string) $promotion->priority;
        $this->startsAt     = $promotion->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->endsAt       = $promotion->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->stackable    = $promotion->stackable;

        $this->code                  = $coupon->code;
        $this->usageLimit             = $coupon->usage_limit !== null ? (string) $coupon->usage_limit : '';
        $this->usageLimitPerCustomer  = $coupon->usage_limit_per_customer !== null ? (string) $coupon->usage_limit_per_customer : '';
        $this->minOrderAmount         = $coupon->min_order_amount !== null ? (string) $coupon->min_order_amount : '';
        $this->maxDiscountAmount      = $coupon->max_discount_amount !== null ? (string) $coupon->max_discount_amount : '';

        $this->selectedCustomerIds = $coupon->customers->pluck('customer_id')->all();

        $this->hydrateConditionsFrom($promotion);
        $this->hydrateDiscountRulesFrom($promotion);
    }

    public function addCustomer(int $customerId): void
    {
        if (! in_array($customerId, $this->selectedCustomerIds, true)) {
            $this->selectedCustomerIds[] = $customerId;
        }

        $this->customerSearch = '';
    }

    public function removeCustomer(int $customerId): void
    {
        $this->selectedCustomerIds = array_values(array_diff($this->selectedCustomerIds, [$customerId]));
    }

    protected function rules(): array
    {
        return array_merge($this->promotionRules(), [
            'code'                  => 'required|string|max:100|unique:coupons,code,' . $this->couponId,
            'usageLimit'            => 'nullable|integer|min:1',
            'usageLimitPerCustomer' => 'nullable|integer|min:1',
            'minOrderAmount'        => 'nullable|numeric|min:0',
            'maxDiscountAmount'     => 'nullable|numeric|min:0',
        ]);
    }

    public function save(): void
    {
        $this->validate();

        $coupon    = Coupon::findOrFail($this->couponId);
        $promotion = $coupon->promotion;

        $promotion->update([
            'name'        => $this->name,
            'description' => $this->description ?: null,
            'status'      => $this->status,
            'priority'    => $this->priority ?: 0,
            'starts_at'   => $this->startsAt ?: null,
            'ends_at'     => $this->endsAt ?: null,
            'stackable'   => $this->stackable,
        ]);

        $coupon->update([
            'code'                     => strtoupper($this->code),
            'usage_limit'              => $this->usageLimit ?: null,
            'usage_limit_per_customer' => $this->usageLimitPerCustomer ?: null,
            'min_order_amount'         => $this->minOrderAmount ?: null,
            'max_discount_amount'      => $this->maxDiscountAmount ?: null,
        ]);

        $this->syncConditionsTo($promotion);
        $this->syncDiscountRulesTo($promotion);

        $coupon->customers()->delete();
        foreach ($this->selectedCustomerIds as $customerId) {
            $coupon->customers()->create([
                'customer_id' => $customerId,
                'status'      => 'saved',
                'saved_at'    => now(),
            ]);
        }

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($coupon)
            ->event('updated')
            ->log("Coupon \"{$coupon->code}\" was updated");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Coupon updated successfully']);
    }

    public function render(): mixed
    {
        $customerOptions = collect();
        if ($this->customerSearch !== '') {
            $customerOptions = Customer::where(fn ($q) => $q
                ->where('full_name', 'like', "%{$this->customerSearch}%")
                ->orWhere('phone', 'like', "%{$this->customerSearch}%"))
                ->whereNotIn('id', $this->selectedCustomerIds)
                ->limit(10)
                ->get();
        }

        $selectedCustomers = Customer::whereIn('id', $this->selectedCustomerIds)->get();

        $usages = \App\Models\CouponUsage::where('coupon_id', $this->couponId)
            ->with('order', 'customer')
            ->orderByDesc('used_at')
            ->limit(10)
            ->get();

        return view('livewire.admin.sales.coupon-detail', [
            'customerOptions'   => $customerOptions,
            'selectedCustomers' => $selectedCustomers,
            'usages'            => $usages,
        ])->layout('layouts.admin.admin');
    }
}
