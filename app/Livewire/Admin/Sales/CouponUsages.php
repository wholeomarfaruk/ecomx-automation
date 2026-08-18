<?php

namespace App\Livewire\Admin\Sales;

use App\Models\Coupon;
use App\Models\CouponUsage;
use Livewire\Component;
use Livewire\WithPagination;

class CouponUsages extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $filterCoupon = '';

    public function updatingFilterCoupon(): void { $this->resetPage(); }

    public function render(): mixed
    {
        $usages = CouponUsage::query()
            ->with('coupon', 'order', 'customer')
            ->when($this->filterCoupon !== '', fn ($q) => $q->where('coupon_id', $this->filterCoupon))
            ->orderByDesc('used_at')
            ->paginate(20);

        return view('livewire.admin.sales.coupon-usages', [
            'usages'  => $usages,
            'coupons' => Coupon::orderBy('code')->get(['id', 'code']),
            'totalDiscountGiven' => CouponUsage::sum('discount_amount'),
        ])->layout('layouts.admin.admin');
    }
}
