<?php

namespace App\Livewire\Admin\Sales;

use App\Livewire\Traits\WithPromotionForm;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Promotion;
use Livewire\Component;

class CouponCreate extends Component
{
    use WithPromotionForm;

    public string $code                    = '';
    public string $usageLimit              = '';
    public string $usageLimitPerCustomer   = '';
    public string $minOrderAmount          = '';
    public string $maxDiscountAmount       = '';

    /** @var array<int, int> */
    public array $selectedCustomerIds = [];
    public string $customerSearch = '';

    public function mount(): void
    {
        $this->addCondition();
        $this->addDiscountRule();
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
            'code'                  => 'required|string|max:100|unique:coupons,code',
            'usageLimit'            => 'nullable|integer|min:1',
            'usageLimitPerCustomer' => 'nullable|integer|min:1',
            'minOrderAmount'        => 'nullable|numeric|min:0',
            'maxDiscountAmount'     => 'nullable|numeric|min:0',
        ]);
    }

    public function save(): void
    {
        $this->validate();

        $promotion = Promotion::create([
            'type'        => 'coupon',
            'name'        => $this->name,
            'description' => $this->description ?: null,
            'status'      => $this->status,
            'priority'    => $this->priority ?: 0,
            'starts_at'   => $this->startsAt ?: null,
            'ends_at'     => $this->endsAt ?: null,
            'stackable'   => $this->stackable,
        ]);

        $coupon = Coupon::create([
            'promotion_id'             => $promotion->id,
            'code'                     => strtoupper($this->code),
            'usage_limit'              => $this->usageLimit ?: null,
            'usage_limit_per_customer' => $this->usageLimitPerCustomer ?: null,
            'min_order_amount'         => $this->minOrderAmount ?: null,
            'max_discount_amount'      => $this->maxDiscountAmount ?: null,
        ]);

        $this->syncConditionsTo($promotion);
        $this->syncDiscountRulesTo($promotion);

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
            ->event('created')
            ->log("Coupon \"{$coupon->code}\" was created");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Coupon created successfully']);

        $this->redirect(route('admin.sales.coupons.show', $coupon->id), navigate: true);
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

        return view('livewire.admin.sales.coupon-create', [
            'customerOptions'   => $customerOptions,
            'selectedCustomers' => $selectedCustomers,
        ])->layout('layouts.admin.admin');
    }
}
