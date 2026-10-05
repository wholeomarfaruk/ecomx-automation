<?php

namespace App\Livewire\Admin\Catalog;

use App\Enums\Product\ProductType;
use App\Models\Brand;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Products extends Component
{
    use WithPagination;

    public string $search            = '';
    public string $filterStatus      = '';
    public string $filterBrand       = '';
    #[Url]
    public string $filterStockStatus = '';
    public string $filterProductType = '';
    public string $minPrice          = '';
    public string $maxPrice          = '';

    protected string $paginationTheme = 'tailwind';

    // quick-add modal
    public bool   $createModal = false;
    public string $newName     = '';
    public string $newSlug     = '';
    public string $newCode     = '';
    public string $newBrandId  = '';
    public string $newPrice    = '';
    public bool   $slugLocked  = false;

    // stock modal (Stock column cell → the product editor's Stocks panel)
    public bool $stockModal = false;
    public ?int $stockProductId = null;

    // Quick view modal — read-only product details, never shows purchase price
    public bool $quickViewModal = false;
    public ?int $quickViewId = null;

    public function updatingSearch(): void            { $this->resetPage(); }
    public function updatingFilterStatus(): void      { $this->resetPage(); }
    public function updatingFilterBrand(): void       { $this->resetPage(); }
    public function updatingFilterStockStatus(): void { $this->resetPage(); }
    public function updatingFilterProductType(): void { $this->resetPage(); }
    public function updatingMinPrice(): void          { $this->resetPage(); }
    public function updatingMaxPrice(): void          { $this->resetPage(); }

    public function clearPriceRange(): void
    {
        $this->reset('minPrice', 'maxPrice');
        $this->resetPage();
    }

    public function updatedNewName(string $value): void
    {
        if (! $this->slugLocked) {
            $this->newSlug = Str::slug($value);
        }
    }

    public function updatedNewSlug(): void
    {
        $this->slugLocked = true;
    }

    public function openCreateModal(): void
    {
        $this->reset(['newName', 'newSlug', 'newCode', 'newBrandId', 'newPrice', 'slugLocked']);
        $this->newCode = 'PRD-' . strtoupper(Str::random(6));
        $this->resetValidation();
        $this->createModal = true;
    }

    public function createProduct(): void
    {
        $this->validate([
            'newName'    => 'required|string|max:150',
            'newSlug'    => 'required|string|max:170|unique:products,slug',
            'newCode'    => 'required|string|max:100|unique:products,code',
            'newBrandId' => 'nullable|integer|exists:brands,id',
            'newPrice'   => 'nullable|numeric|min:0',
        ]);

        $maxOrder = Product::max('sort_order') ?? 0;

        $product = Product::create([
            'name'       => $this->newName,
            'slug'       => $this->newSlug,
            'code'       => $this->newCode,
            'brand_id'   => $this->newBrandId ?: null,
            'price'      => $this->newPrice !== '' ? $this->newPrice : null,
            'status'     => 'draft',
            'sort_order' => $maxOrder + 1,
        ]);

        activity('catalog')
            ->causedBy(auth()->user())
            ->performedOn($product)
            ->withProperties(['name' => $product->name, 'code' => $product->code])
            ->event('created')
            ->log("Product \"{$product->name}\" was added");

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Product created. Continue editing to add full details.']);

        $this->redirect(route('admin.catalog.products.edit', $product->id), navigate: true);
    }

    public function openStockModal(int $id): void
    {
        $this->stockProductId = Product::findOrFail($id)->id;
        $this->stockModal = true;
    }

    public function openQuickView(int $id): void
    {
        $this->quickViewId = Product::findOrFail($id)->id;
        $this->quickViewModal = true;
    }

    /** Stock In / Adjust inside the stock modal — re-render so the Stock column shows the new figures. */
    #[On('product-stock-updated')]
    public function refreshStock(): void
    {
    }

    public function toggleStatus(int $id): void
    {
        $product   = Product::findOrFail($id);
        $newStatus = $product->status === 'active' ? 'inactive' : 'active';
        $product->update(['status' => $newStatus]);

        activity('catalog')
            ->causedBy(auth()->user())
            ->performedOn($product)
            ->withProperties(['old' => ['status' => $product->getOriginal('status')], 'attributes' => ['status' => $newStatus]])
            ->event('updated')
            ->log("Product \"{$product->name}\" was set to {$newStatus}");

        $this->dispatch('toast', ['type' => 'success', 'message' => "{$product->name} is now {$newStatus}"]);
    }

    public function deleteProduct(int $id): void
    {
        $product = Product::findOrFail($id);
        $name    = $product->name;

        $product->delete();

        activity('catalog')
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name])
            ->event('deleted')
            ->log("Product \"{$name}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Product deleted']);
    }

    public function render(): mixed
    {
        $min = is_numeric($this->minPrice) ? (float) $this->minPrice : null;
        $max = is_numeric($this->maxPrice) ? (float) $this->maxPrice : null;

        // Selling price: a valid sale price, else the regular price (same rule as Product::sellingPrice).
        $selling = fn (string $t) => "CASE WHEN {$t}.sale_price > 0 AND {$t}.sale_price < {$t}.price THEN {$t}.sale_price ELSE {$t}.price END";
        $inRange = function ($q, string $t) use ($min, $max, $selling) {
            if ($min !== null) $q->whereRaw("{$selling($t)} >= ?", [$min]);
            if ($max !== null) $q->whereRaw("{$selling($t)} <= ?", [$max]);
        };

        $products = Product::query()
            ->when($min !== null || $max !== null, fn ($q) => $q->where(fn ($w) => $w
                ->where(fn ($p) => $inRange($p, 'products'))
                ->orWhereHas('variants', fn ($v) => $inRange($v, 'product_variants'))
            ))
            ->with('brand', 'variants', 'comboItems.product.variants', 'comboItems.variant')
            ->when($this->search, fn($q) => $q->where(fn($s) => $s
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
            ))
            ->when($this->filterStatus !== '', fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterBrand !== '', fn($q) => $q->where('brand_id', $this->filterBrand))
            ->when($this->filterStockStatus !== '', fn($q) => $q->where('stock_status', $this->filterStockStatus))
            ->when($this->filterProductType !== '', fn($q) => $q->where('product_type', $this->filterProductType))
            ->orderByDesc('id')
            ->paginate(15);

        // Units Pending orders are waiting for (taken from stock only on confirm) — per product, variants summed.
        $heldStock = \App\Models\OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', \App\Enums\Sales\OrderStatus::PENDING->value)
            ->whereIn('order_items.product_id', $products->pluck('id'))
            ->groupBy('order_items.product_id')
            ->selectRaw('order_items.product_id, SUM(order_items.quantity) AS held, COUNT(DISTINCT orders.id) AS order_count')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->product_id => ['quantity' => (float) $r->held, 'orders' => (int) $r->order_count]])
            ->all();

        return view('livewire.admin.catalog.products', [
            'products'      => $products,
            'heldStock'     => $heldStock,
            'stockProduct'  => $this->stockProductId ? Product::find($this->stockProductId, ['id', 'name', 'code', 'product_type']) : null,
            'quickProduct'  => $this->quickViewId ? Product::with([
                'brand', 'categories',
                'variants' => fn ($q) => $q->orderBy('sort_order'),
                'variants.values.productAttributeValue.attributeValue.attribute',
                'comboItems.product', 'comboItems.variant.values.productAttributeValue.attributeValue.attribute',
            ])->find($this->quickViewId) : null,
            'brands'        => Brand::orderBy('name')->get(['id', 'name']),
            'totalCount'    => Product::count(),
            'activeCount'   => Product::where('status', 'active')->count(),
            'draftCount'    => Product::where('status', 'draft')->count(),
            'archivedCount' => Product::where('status', 'archived')->count(),
            'totalStock'    => $this->totalStock(),
        ])->layout('layouts.admin.admin');
    }

    /**
     * Units in stock across the catalog, counted the way the Stock column
     * counts each product (Product::stockInfo): simple products' own balance
     * (StockService — stock_quantity while the Inventory module is off, else
     * the default warehouse's available quantity) plus every variable
     * product's variant stock. Combos are left out: their stock is derived
     * from component products that are already counted.
     */
    private function totalStock(): float
    {
        $simple = app(StockService::class)->usesOwnStock()
            ? Product::where('product_type', ProductType::SIMPLE)->sum(DB::raw('GREATEST(stock_quantity, 0)'))
            : InventoryStock::query()
                ->where('warehouse_id', Warehouse::default()->id)
                ->whereNull('variant_id')
                ->whereHas('product', fn ($q) => $q->where('product_type', ProductType::SIMPLE))
                ->sum(DB::raw('GREATEST(quantity - booked_quantity, 0)'));

        $variants = ProductVariant::query()
            ->whereHas('product', fn ($q) => $q->where('product_type', ProductType::VARIABLE))
            ->sum('stock_quantity');

        return (float) $simple + (float) $variants;
    }
}
