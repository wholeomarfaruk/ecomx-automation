<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\Brand;
use Illuminate\Support\Str;
use Livewire\Component;

class Brands extends Component
{
    use WithMediaPicker;

    public string $search         = '';
    public string $filterStatus   = '';
    public string $filterFeatured = '';

    // create
    public bool   $createModal        = false;
    public string $newName            = '';
    public string $newSlug            = '';
    public string $newDescription     = '';
    public string $newStatus          = 'active';
    public bool   $newFeatured        = false;
    public $newLogoImageId            = null;
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
    public string $editDescription     = '';
    public string $editStatus          = 'active';
    public bool   $editFeatured        = false;
    public $editLogoImageId            = null;
    public $editCoverImageId           = null;
    public $editMetaImageId            = null;
    public string $editMetaTitle       = '';
    public string $editMetaDescription = '';

    public bool $seoOpen = false;

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

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Brand::where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }

    public function openCreateModal(): void
    {
        $this->reset([
            'newName', 'newSlug', 'newDescription', 'newStatus', 'newFeatured',
            'newLogoImageId', 'newCoverImageId', 'newMetaImageId',
            'newMetaTitle', 'newMetaDescription', 'slugLocked', 'seoOpen',
        ]);
        $this->newStatus = 'active';
        $this->resetValidation();
        $this->createModal = true;
    }

    public function createBrand(): void
    {
        $this->validate([
            'newName'            => 'required|string|max:150',
            'newSlug'            => 'required|string|max:170|unique:brands,slug',
            'newDescription'     => 'nullable|string',
            'newStatus'          => 'required|in:active,inactive',
            'newMetaTitle'       => 'nullable|string|max:255',
            'newMetaDescription' => 'nullable|string',
        ]);

        $maxOrder = Brand::max('sort_order') ?? 0;

        $brand = Brand::create([
            'name'              => $this->newName,
            'slug'              => $this->newSlug,
            'description'       => $this->newDescription ?: null,
            'status'            => $this->newStatus,
            'featured'          => $this->newFeatured,
            'logo_image_id'     => $this->newLogoImageId ?: null,
            'cover_image_id'    => $this->newCoverImageId ?: null,
            'meta_image_id'     => $this->newMetaImageId ?: null,
            'meta_title'        => $this->newMetaTitle ?: null,
            'meta_description'  => $this->newMetaDescription ?: null,
            'sort_order'        => $maxOrder + 1,
        ]);

        activity('catalog')
            ->causedBy(auth()->user())
            ->performedOn($brand)
            ->withProperties(['name' => $brand->name, 'slug' => $brand->slug])
            ->event('created')
            ->log("Brand \"{$brand->name}\" was added");

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Brand added successfully']);
    }

    public function editBrand(int $id): void
    {
        $brand = Brand::findOrFail($id);

        $this->editingId           = $brand->id;
        $this->editName            = $brand->name;
        $this->editSlug            = $brand->slug;
        $this->editDescription     = $brand->description ?? '';
        $this->editStatus          = $brand->status;
        $this->editFeatured        = $brand->featured;
        $this->editLogoImageId     = $brand->logo_image_id;
        $this->editCoverImageId    = $brand->cover_image_id;
        $this->editMetaImageId     = $brand->meta_image_id;
        $this->editMetaTitle       = $brand->meta_title ?? '';
        $this->editMetaDescription = $brand->meta_description ?? '';
        $this->seoOpen             = false;
        $this->resetValidation();
        $this->editModal           = true;
    }

    public function updateBrand(): void
    {
        $brand = Brand::findOrFail($this->editingId);

        $this->validate([
            'editName'            => 'required|string|max:150',
            'editSlug'            => 'required|string|max:170|unique:brands,slug,' . $brand->id,
            'editDescription'     => 'nullable|string',
            'editStatus'          => 'required|in:active,inactive',
            'editMetaTitle'       => 'nullable|string|max:255',
            'editMetaDescription' => 'nullable|string',
        ]);

        $brand->update([
            'name'              => $this->editName,
            'slug'              => $this->editSlug,
            'description'       => $this->editDescription ?: null,
            'status'            => $this->editStatus,
            'featured'          => $this->editFeatured,
            'logo_image_id'     => $this->editLogoImageId ?: null,
            'cover_image_id'    => $this->editCoverImageId ?: null,
            'meta_image_id'     => $this->editMetaImageId ?: null,
            'meta_title'        => $this->editMetaTitle ?: null,
            'meta_description'  => $this->editMetaDescription ?: null,
        ]);

        activity('catalog')
            ->causedBy(auth()->user())
            ->performedOn($brand)
            ->event('updated')
            ->log("Brand \"{$brand->name}\" was updated");

        $this->editModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Brand updated successfully']);
    }

    public function toggleStatus(int $id): void
    {
        $brand     = Brand::findOrFail($id);
        $newStatus = $brand->status === 'active' ? 'inactive' : 'active';
        $brand->update(['status' => $newStatus]);

        activity('catalog')
            ->causedBy(auth()->user())
            ->performedOn($brand)
            ->withProperties(['old' => ['status' => $brand->getOriginal('status')], 'attributes' => ['status' => $newStatus]])
            ->event('updated')
            ->log("Brand \"{$brand->name}\" was set to {$newStatus}");

        $this->dispatch('toast', ['type' => 'success', 'message' => "{$brand->name} is now {$newStatus}"]);
    }

    public function toggleFeatured(int $id): void
    {
        $brand = Brand::findOrFail($id);
        $brand->update(['featured' => ! $brand->featured]);

        $this->dispatch('toast', [
            'type'    => 'success',
            'message' => $brand->name . ' ' . ($brand->featured ? 'marked as featured' : 'removed from featured'),
        ]);
    }

    public function deleteBrand(int $id): void
    {
        $brand = Brand::findOrFail($id);
        $name  = $brand->name;

        $brand->delete();

        activity('catalog')
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name])
            ->event('deleted')
            ->log("Brand \"{$name}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Brand deleted']);
    }

    public function render(): mixed
    {
        $brands = Brand::query()
            ->when($this->search, fn($q) => $q->where(fn($s) => $s
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%")
            ))
            ->when($this->filterStatus !== '', fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterFeatured !== '', fn($q) => $q->where('featured', (bool) $this->filterFeatured))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('livewire.admin.catalog.brands', [
            'brands'         => $brands,
            'totalCount'     => Brand::count(),
            'activeCount'    => Brand::where('status', 'active')->count(),
            'inactiveCount'  => Brand::where('status', 'inactive')->count(),
            'featuredCount'  => Brand::where('featured', true)->count(),
        ])->layout('layouts.admin.admin');
    }
}
