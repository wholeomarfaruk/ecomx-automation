<?php

namespace App\Livewire\Admin\Sales;

use App\Enums\Sales\OfferType;
use App\Enums\Sales\PromotionStatus;
use App\Models\Offer;
use Livewire\Component;
use Livewire\WithPagination;

class Offers extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search           = '';
    public string $filterStatus     = '';
    public string $filterOfferType  = '';

    public function updatingSearch(): void          { $this->resetPage(); }
    public function updatingFilterStatus(): void    { $this->resetPage(); }
    public function updatingFilterOfferType(): void { $this->resetPage(); }

    public function deleteOffer(int $id): void
    {
        $offer     = Offer::findOrFail($id);
        $promotion = $offer->promotion;
        $name      = $promotion->name;

        $promotion->delete();

        activity('sales')
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name])
            ->event('deleted')
            ->log("Offer \"{$name}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Offer deleted']);
    }

    public function render(): mixed
    {
        $offers = Offer::query()
            ->with(['promotion' => fn ($q) => $q->withCount('items')])
            ->when($this->search, fn ($q) => $q->whereHas('promotion', fn ($p) => $p->where('name', 'like', "%{$this->search}%")))
            ->when($this->filterStatus !== '', fn ($q) => $q->whereHas('promotion', fn ($p) => $p->where('status', $this->filterStatus)))
            ->when($this->filterOfferType !== '', fn ($q) => $q->where('offer_type', $this->filterOfferType))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.admin.sales.offers', [
            'offers'      => $offers,
            'statuses'    => PromotionStatus::cases(),
            'offerTypes'  => OfferType::cases(),
            'totalCount'  => Offer::count(),
            'activeCount' => Offer::whereHas('promotion', fn ($p) => $p->where('status', PromotionStatus::ACTIVE))->count(),
        ])->layout('layouts.admin.admin');
    }
}
