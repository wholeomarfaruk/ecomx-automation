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

    public function updatingSearch(): void            { $this->resetPage(); }
    public function updatingFilterStatus(): void      { $this->resetPage(); }
    public function updatingFilterBrand(): void       { $this->resetPage(); }
    public function updatingFilterStockStatus(): void { $this->resetPage(); }
    public function updatingFilterProductType(): void { $this->resetPage(); }

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
        $products = Product::query()
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

        return view('livewire.admin.catalog.products', [
            'products'      => $products,
            'stockProduct'  => $this->stockProductId ? Product::find($this->stockProductId, ['id', 'name', 'code', 'product_type']) : null,
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
