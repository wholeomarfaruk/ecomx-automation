<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;
use Livewire\Component;

class Categories extends Component
{
    use WithMediaPicker;

    public string $search       = '';
    public string $filterStatus = '';

    /** @var array<int, bool> */
    public array $collapsed = [];

    // create
    public bool   $createModal        = false;
    public string $newName            = '';
    public string $newSlug            = '';
    public string $newParentId        = '';
    public string $newDescription     = '';
    public string $newStatus          = 'active';
    public $newFeaturedImageId        = null;
    public $newCoverImageId           = null;
    public $newMetaImageId            = null;
    public string $newMetaTitle       = '';
    public string $newMetaDescription = '';
    public bool   $slugLocked         = false;

    // edit
    public bool   $editModal           = false;
    public ?int   $editingId           = null;
    public string $editName            = '';
    public string $editSlug            = '';
    public string $editParentId        = '';
    public string $editDescription     = '';
    public string $editStatus          = 'active';
    public $editFeaturedImageId        = null;
    public $editCoverImageId           = null;
    public $editMetaImageId            = null;
    public string $editMetaTitle       = '';
    public string $editMetaDescription = '';

    public bool $seoOpen = false;

    // manage products
    public bool   $productsModal      = false;
    public ?int   $productsCategoryId = null;
    public string $productSearch      = '';

    public function toggleCollapse(int $id): void
    {
        $this->collapsed[$id] = ! ($this->collapsed[$id] ?? false);
    }

    public function reorder(array $orderedIds, ?int $parentId): void
    {
        foreach ($orderedIds as $index => $id) {
            Category::where('id', $id)->update([
                'parent_id'  => $parentId,
                'sort_order' => $index + 1,
            ]);
        }
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
        $this->reset([
            'newName', 'newSlug', 'newParentId', 'newDescription', 'newStatus',
            'newFeaturedImageId', 'newCoverImageId', 'newMetaImageId',
            'newMetaTitle', 'newMetaDescription', 'slugLocked', 'seoOpen',
        ]);
        $this->newStatus = 'active';
        $this->resetValidation();
        $this->createModal = true;
    }

    public function createCategory(): void
    {
        $this->validate([
            'newName'            => 'required|string|max:150',
            'newSlug'            => 'required|string|max:170|unique:categories,slug',
            'newParentId'        => 'nullable|integer|exists:categories,id',
            'newDescription'     => 'nullable|string',
            'newStatus'          => 'required|in:active,inactive',
            'newMetaTitle'       => 'nullable|string|max:255',
            'newMetaDescription' => 'nullable|string',
        ]);

        $maxOrder = Category::max('sort_order') ?? 0;

        $category = Category::create([
            'name'              => $this->newName,
            'slug'              => $this->newSlug,
            'parent_id'         => $this->newParentId ?: null,
            'description'       => $this->newDescription ?: null,
            'status'            => $this->newStatus,
            'featured_image_id' => $this->newFeaturedImageId ?: null,
            'cover_image_id'    => $this->newCoverImageId ?: null,
            'meta_image_id'     => $this->newMetaImageId ?: null,
            'meta_title'        => $this->newMetaTitle ?: null,
            'meta_description'  => $this->newMetaDescription ?: null,
            'sort_order'        => $maxOrder + 1,
        ]);

        activity('catalog')
            ->causedBy(auth()->user())
            ->performedOn($category)
            ->withProperties(['name' => $category->name, 'slug' => $category->slug])
            ->event('created')
            ->log("Category \"{$category->name}\" was added");

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Category added successfully']);
    }

    public function editCategory(int $id): void
    {
        $category = Category::findOrFail($id);

        $this->editingId           = $category->id;
        $this->editName            = $category->name;
        $this->editSlug            = $category->slug;
        $this->editParentId        = (string) ($category->parent_id ?? '');
        $this->editDescription     = $category->description ?? '';
        $this->editStatus          = $category->status;
        $this->editFeaturedImageId = $category->featured_image_id;
        $this->editCoverImageId    = $category->cover_image_id;
        $this->editMetaImageId     = $category->meta_image_id;
        $this->editMetaTitle       = $category->meta_title ?? '';
        $this->editMetaDescription = $category->meta_description ?? '';
        $this->seoOpen             = false;
        $this->resetValidation();
        $this->editModal           = true;
    }

    public function updateCategory(): void
    {
        $category = Category::findOrFail($this->editingId);

        $this->validate([
            'editName'            => 'required|string|max:150',
            'editSlug'            => 'required|string|max:170|unique:categories,slug,' . $category->id,
            'editParentId'        => 'nullable|integer|exists:categories,id|not_in:' . $category->id,
            'editDescription'     => 'nullable|string',
            'editStatus'          => 'required|in:active,inactive',
            'editMetaTitle'       => 'nullable|string|max:255',
            'editMetaDescription' => 'nullable|string',
        ]);

        $category->update([
            'name'              => $this->editName,
            'slug'              => $this->editSlug,
            'parent_id'         => $this->editParentId ?: null,
            'description'       => $this->editDescription ?: null,
            'status'            => $this->editStatus,
            'featured_image_id' => $this->editFeaturedImageId ?: null,
            'cover_image_id'    => $this->editCoverImageId ?: null,
            'meta_image_id'     => $this->editMetaImageId ?: null,
            'meta_title'        => $this->editMetaTitle ?: null,
            'meta_description'  => $this->editMetaDescription ?: null,
        ]);

        activity('catalog')
            ->causedBy(auth()->user())
            ->performedOn($category)
            ->event('updated')
            ->log("Category \"{$category->name}\" was updated");

        $this->editModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Category updated successfully']);
    }

    public function toggleStatus(int $id): void
    {
        $category  = Category::findOrFail($id);
        $newStatus = $category->status === 'active' ? 'inactive' : 'active';
        $category->update(['status' => $newStatus]);

        activity('catalog')
            ->causedBy(auth()->user())
            ->performedOn($category)
            ->withProperties(['old' => ['status' => $category->getOriginal('status')], 'attributes' => ['status' => $newStatus]])
            ->event('updated')
            ->log("Category \"{$category->name}\" was set to {$newStatus}");

        $this->dispatch('toast', ['type' => 'success', 'message' => "{$category->name} is now {$newStatus}"]);
    }

    public function deleteCategory(int $id): void
    {
        $category = Category::findOrFail($id);

        if ($category->children()->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Cannot delete a category that has subcategories']);
            return;
        }

        $name = $category->name;
        $category->delete();

        activity('catalog')
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name])
            ->event('deleted')
            ->log("Category \"{$name}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Category deleted']);
    }

    public function openProductsModal(int $categoryId): void
    {
        $this->productsCategoryId = $categoryId;
        $this->productSearch      = '';
        $this->productsModal      = true;
    }

    public function attachProduct(int $productId): void
    {
        $category = Category::findOrFail($this->productsCategoryId);

        if ($category->products()->where('product_category_pivot.product_id', $productId)->exists()) {
            return;
        }

        $maxOrder = $category->products()->max('product_category_pivot.sort_order') ?? 0;

        $category->products()->attach($productId, ['sort_order' => $maxOrder + 1]);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Product added to category']);
    }

    public function detachProduct(int $productId): void
    {
        $category = Category::findOrFail($this->productsCategoryId);
        $category->products()->detach($productId);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Product removed from category']);
    }

    public function reorderCategoryProducts(array $orderedProductIds): void
    {
        $category = Category::findOrFail($this->productsCategoryId);

        foreach ($orderedProductIds as $index => $productId) {
            $category->products()->updateExistingPivot($productId, ['sort_order' => $index + 1]);
        }
    }

    /**
     * Genuinely nested tree: each node gets a ->nodes collection of its children.
     * Sorted by sort_order then name at every level.
     */
    protected function nestTree(\Illuminate\Support\Collection $all): \Illuminate\Support\Collection
    {
        $byParent = $all->groupBy('parent_id');

        $attach = function ($parentId) use (&$attach, $byParent) {
            return ($byParent->get($parentId) ?? collect())
                ->sortBy([['sort_order', 'asc'], ['name', 'asc']])
                ->values()
                ->map(function ($cat) use (&$attach) {
                    $cat->nodes = $attach($cat->id);
                    return $cat;
                });
        };

        return $attach(null);
    }

    /**
     * Flat, depth-first list with ->depth, used for parent <select> dropdowns.
     */
    protected function flattenForOptions(\Illuminate\Support\Collection $nested, int $depth = 0): \Illuminate\Support\Collection
    {
        return $nested->flatMap(function ($cat) use ($depth) {
            $cat->depth = $depth;
            return collect([$cat])->merge($this->flattenForOptions($cat->nodes, $depth + 1));
        });
    }

    public function render(): mixed
    {
        $filtering = $this->search !== '' || $this->filterStatus !== '';

        $all = Category::query()->withCount(['children', 'products'])->get();

        if ($filtering) {
            $matches = $all->filter(function ($cat) {
                $matchesSearch = $this->search === ''
                    || str_contains(strtolower($cat->name), strtolower($this->search))
                    || str_contains(strtolower($cat->slug), strtolower($this->search));
                $matchesStatus = $this->filterStatus === '' || $cat->status === $this->filterStatus;
                return $matchesSearch && $matchesStatus;
            });

            // Include ancestors of matches for tree context.
            $keepIds = collect();
            $byId = $all->keyBy('id');
            foreach ($matches as $cat) {
                $keepIds->push($cat->id);
                $current = $cat;
                while ($current->parent_id && $byId->has($current->parent_id)) {
                    $current = $byId->get($current->parent_id);
                    $keepIds->push($current->id);
                }
            }
            $all = $all->whereIn('id', $keepIds->unique());
        }

        $tree = $this->nestTree($all);

        $parentOptions = $this->flattenForOptions(
            $this->nestTree(Category::orderBy('name')->get(['id', 'name', 'parent_id', 'sort_order']))
        );

        $productsCategory   = null;
        $assignedProducts   = collect();
        $availableProducts  = collect();

        if ($this->productsModal && $this->productsCategoryId) {
            $productsCategory = Category::find($this->productsCategoryId);

            if ($productsCategory) {
                $assignedProducts = $productsCategory->products()
                    ->orderBy('product_category_pivot.sort_order')
                    ->get();

                $assignedIds = $assignedProducts->pluck('id');

                $availableProducts = Product::query()
                    ->whereNotIn('id', $assignedIds)
                    ->when($this->productSearch, fn($q) => $q->where(fn($s) => $s
                        ->where('name', 'like', "%{$this->productSearch}%")
                        ->orWhere('code', 'like', "%{$this->productSearch}%")
                    ))
                    ->orderBy('name')
                    ->limit(20)
                    ->get();
            }
        }

        return view('livewire.admin.catalog.categories', [
            'tree'               => $tree,
            'autoExpand'         => $filtering,
            'parentOptions'      => $parentOptions,
            'totalCount'         => Category::count(),
            'activeCount'        => Category::where('status', 'active')->count(),
            'inactiveCount'      => Category::where('status', 'inactive')->count(),
            'productsCategory'   => $productsCategory,
            'assignedProducts'   => $assignedProducts,
            'availableProducts'  => $availableProducts,
        ])->layout('layouts.admin.admin');
    }
}
