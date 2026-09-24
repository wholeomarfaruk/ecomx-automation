<?php

namespace App\Livewire\Admin\Sales;

use App\Livewire\Traits\WithPromotionForm;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Promotion;
use Livewire\Component;

class OfferCreate extends Component
{
    use WithPromotionForm;

    public string $offerType = 'percentage';

    /** @var array<int, array{product_id: string, label: string}> */
    public array $items = [];
    /** Bound to the Target Products searchable-select; picking a product adds it to $items and resets. */
    public string $productPickerId = '';

    public function mount(): void
    {
        $this->addCondition();
        $this->addDiscountRule();
    }

    public function updatedProductPickerId(string $value): void
    {
        if ($value !== '') {
            $this->addItem((int) $value);
        }

        $this->productPickerId = '';
    }

    public function addItem(int $productId): void
    {
        $product = Product::active()->find($productId);

        if (! $product) {
            return;
        }

        if (collect($this->items)->contains('product_id', (string) $product->id)) {
            return;
        }

        $this->items[] = [
            'product_id' => (string) $product->id,
            'label'      => $product->name,
        ];
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

        $promotion = Promotion::create([
            'type'        => 'offer',
            'name'        => $this->name,
            'description' => $this->description ?: null,
            'status'      => $this->status,
            'priority'    => $this->priority ?: 0,
            'starts_at'   => $this->startsAt ?: null,
            'ends_at'     => $this->endsAt ?: null,
            'stackable'   => $this->stackable,
        ]);

        $offer = Offer::create([
            'promotion_id' => $promotion->id,
            'offer_type'   => $this->offerType,
        ]);

        $this->syncConditionsTo($promotion);
        $this->syncDiscountRulesTo($promotion);

        foreach ($this->items as $item) {
            $promotion->items()->create(['product_id' => $item['product_id']]);
        }

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($offer)
            ->event('created')
            ->log("Offer \"{$promotion->name}\" was created");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Offer created successfully']);

        $this->redirect(route('admin.sales.offers.show', $offer->id), navigate: true);
    }

    public function render(): mixed
    {
        $products = Product::active()
            ->whereNotIn('id', array_column($this->items, 'product_id'))
            ->get(['id', 'name', 'code']);

        return view('livewire.admin.sales.offer-create', [
            'productOptions' => $products->mapWithKeys(fn ($p) => [$p->id => $p->code ? "{$p->name} ({$p->code})" : $p->name]),
        ])->layout('layouts.admin.admin');
    }
}
