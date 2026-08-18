<?php

namespace App\Livewire\Admin\Sales;

use App\Enums\Sales\PromotionStatus;
use App\Models\Coupon;
use Livewire\Component;
use Livewire\WithPagination;

class Coupons extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search       = '';
    public string $filterStatus = '';

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function deleteCoupon(int $id): void
    {
        $coupon = Coupon::findOrFail($id);
        $promotion = $coupon->promotion;
        $code = $coupon->code;

        $promotion->delete();

        activity('sales')
            ->causedBy(auth()->user())
            ->withProperties(['code' => $code])
            ->event('deleted')
            ->log("Coupon \"{$code}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Coupon deleted']);
    }

    public function render(): mixed
    {
        $coupons = Coupon::query()
            ->with('promotion')
            ->withCount('usages')
            ->when($this->search, fn ($q) => $q->where('code', 'like', "%{$this->search}%")
                ->orWhereHas('promotion', fn ($p) => $p->where('name', 'like', "%{$this->search}%")))
            ->when($this->filterStatus !== '', fn ($q) => $q->whereHas('promotion', fn ($p) => $p->where('status', $this->filterStatus)))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.admin.sales.coupons', [
            'coupons'      => $coupons,
            'statuses'     => PromotionStatus::cases(),
            'totalCount'   => Coupon::count(),
            'activeCount'  => Coupon::whereHas('promotion', fn ($p) => $p->where('status', PromotionStatus::ACTIVE))->count(),
            'totalUsages'  => \App\Models\CouponUsage::count(),
            'totalDiscountGiven' => \App\Models\CouponUsage::sum('discount_amount'),
        ])->layout('layouts.admin.admin');
    }
}
