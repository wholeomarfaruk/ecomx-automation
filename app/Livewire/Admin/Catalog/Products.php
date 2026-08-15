<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class Products extends Component
{
    use WithPagination;

    public string $search            = '';
    public string $filterStatus      = '';
    public string $filterBrand       = '';
    public string $filterStockStatus = '';

    protected string $paginationTheme = 'tailwind';

    // quick-add modal
    public bool   $createModal = false;
    public string $newName     = '';
    public string $newSlug     = '';
    public string $newCode     = '';
    public string $newBrandId  = '';
    public string $newPrice    = '';
    public bool   $slugLocked  = false;

    public function updatingSearch(): void            { $this->resetPage(); }
    public function updatingFilterStatus(): void      { $this->resetPage(); }
    public function updatingFilterBrand(): void       { $this->resetPage(); }
    public function updatingFilterStockStatus(): void { $this->resetPage(); }

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
            ->with('brand')
            ->when($this->search, fn($q) => $q->where(fn($s) => $s
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%")
            ))
            ->when($this->filterStatus !== '', fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterBrand !== '', fn($q) => $q->where('brand_id', $this->filterBrand))
            ->when($this->filterStockStatus !== '', fn($q) => $q->where('stock_status', $this->filterStockStatus))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.admin.catalog.products', [
            'products'      => $products,
            'brands'        => Brand::orderBy('name')->get(['id', 'name']),
            'totalCount'    => Product::count(),
            'activeCount'   => Product::where('status', 'active')->count(),
            'draftCount'    => Product::where('status', 'draft')->count(),
            'archivedCount' => Product::where('status', 'archived')->count(),
        ])->layout('layouts.admin.admin');
    }
}
