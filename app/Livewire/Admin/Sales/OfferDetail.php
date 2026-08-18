<?php

namespace App\Livewire\Admin\Sales;

use App\Livewire\Traits\WithPromotionForm;
use App\Models\Offer;
use App\Models\Product;
use Livewire\Component;

class OfferDetail extends Component
{
    use WithPromotionForm;

    public int $offerId;

    public string $offerType = 'percentage';

    /** @var array<int, array{product_id: string, label: string}> */
    public array $items = [];
    public string $productSearch = '';

    public function mount(int $id): void
    {
        $offer = Offer::with('promotion.conditions', 'promotion.discountRules', 'promotion.items.product')->findOrFail($id);
        $promotion = $offer->promotion;

        $this->offerId = $offer->id;

        $this->name        = $promotion->name;
        $this->description = $promotion->description ?? '';
        $this->status       = $promotion->status->value;
        $this->priority     = (string) $promotion->priority;
        $this->startsAt     = $promotion->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->endsAt       = $promotion->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->stackable    = $promotion->stackable;

        $this->offerType = $offer->offer_type->value;

        $this->items = $promotion->items->map(fn ($item) => [
            'product_id' => (string) $item->product_id,
            'label'      => $item->product->name ?? 'Unknown product',
        ])->all();

        $this->hydrateConditionsFrom($promotion);
        $this->hydrateDiscountRulesFrom($promotion);
    }

    public function addItem(int $productId): void
    {
        $product = Product::active()->find($productId);

        if (! $product) {
            return;
        }

        if (collect($this->items)->contains('product_id', (string) $product->id)) {
            $this->productSearch = '';
            return;
        }

        $this->items[] = [
            'product_id' => (string) $product->id,
            'label'      => $product->name,
        ];

        $this->productSearch = '';
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    protected function rules(): array
    {
        return array_merge($this->promotionRules(), [
            'offerType' => 'required|in:percentage,fixed,buy_x_get_y,fixed_price,free_item',
        ]);
    }

    public function save(): void
    {
        $this->validate();

        $offer     = Offer::findOrFail($this->offerId);
        $promotion = $offer->promotion;

        $promotion->update([
            'name'        => $this->name,
            'description' => $this->description ?: null,
            'status'      => $this->status,
            'priority'    => $this->priority ?: 0,
            'starts_at'   => $this->startsAt ?: null,
            'ends_at'     => $this->endsAt ?: null,
            'stackable'   => $this->stackable,
        ]);

        $offer->update(['offer_type' => $this->offerType]);

        $this->syncConditionsTo($promotion);
        $this->syncDiscountRulesTo($promotion);

        $promotion->items()->delete();
        foreach ($this->items as $item) {
            $promotion->items()->create(['product_id' => $item['product_id']]);
        }

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($offer)
            ->event('updated')
            ->log("Offer \"{$promotion->name}\" was updated");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Offer updated successfully']);
    }

    public function render(): mixed
    {
        $productOptions = collect();
        if ($this->productSearch !== '') {
            $productOptions = Product::active()
                ->where(fn ($q) => $q->where('name', 'like', "%{$this->productSearch}%")
                    ->orWhere('code', 'like', "%{$this->productSearch}%"))
                ->limit(10)
                ->get();
        }

        return view('livewire.admin.sales.offer-detail', [
            'productOptions' => $productOptions,
        ])->layout('layouts.admin.admin');
    }
}
