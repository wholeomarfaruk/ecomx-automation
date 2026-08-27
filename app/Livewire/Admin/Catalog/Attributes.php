<?php

namespace App\Livewire\Admin\Catalog;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Support\Str;
use Livewire\Component;

class Attributes extends Component
{
    use WithMediaPicker;

    public string $search = '';

    // create attribute
    public bool   $createModal = false;
    public string $newName     = '';
    public string $newSlug     = '';
    public string $newType     = 'select';
    public bool   $slugLocked  = false;

    // edit attribute
    public bool   $editModal = false;
    public ?int   $editingId = null;
    public string $editName  = '';
    public string $editSlug  = '';
    public string $editType  = 'select';
    public string $editStatus = 'active';

    // values manager
    public bool   $valuesModal      = false;
    public ?int   $valuesAttributeId = null;
    public string $newValueText     = '';
    public string $newValueSlug     = '';
    public string $newSwatchType    = '';
    public string $newSwatchColor   = '#000000';
    public $newSwatchImageId        = null;
    public bool   $valueSlugLocked  = false;

    // edit value (inline, within the values manager modal)
    public ?int   $editingValueId    = null;
    public string $editValueText     = '';
    public string $editValueSlug     = '';
    public string $editSwatchColor   = '#000000';
    public $editSwatchImageId        = null;

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

    public function updatedNewValueText(string $value): void
    {
        if (! $this->valueSlugLocked) {
            $this->newValueSlug = Str::slug($value);
        }
    }

    public function updatedNewValueSlug(): void
    {
        $this->valueSlugLocked = true;
    }

    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Attribute::where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }

    public function openCreateModal(): void
    {
        $this->reset(['newName', 'newSlug', 'newType', 'slugLocked']);
        $this->resetValidation();
        $this->createModal = true;
    }

    public function createAttribute(): void
    {
        $this->validate([
            'newName' => 'required|string|max:100',
            'newSlug' => 'required|string|max:120|unique:attributes,slug',
            'newType' => 'required|in:select,color,image,text',
        ]);

        $attribute = Attribute::create([
            'name'   => $this->newName,
            'slug'   => $this->newSlug,
            'type'   => $this->newType,
            'status' => 'active',
        ]);

        activity('catalog')
            ->causedBy(auth()->user())
            ->performedOn($attribute)
            ->event('created')
            ->log("Attribute \"{$attribute->name}\" was added");

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Attribute added successfully']);
    }

    public function editAttribute(int $id): void
    {
        $attribute = Attribute::findOrFail($id);

        $this->editingId  = $attribute->id;
        $this->editName   = $attribute->name;
        $this->editSlug   = $attribute->slug;
        $this->editType   = $attribute->type;
        $this->editStatus = $attribute->status;
        $this->resetValidation();
        $this->editModal  = true;
    }

    public function updateAttribute(): void
    {
        $attribute = Attribute::findOrFail($this->editingId);

        $this->validate([
            'editName'   => 'required|string|max:100',
            'editSlug'   => 'required|string|max:120|unique:attributes,slug,' . $attribute->id,
            'editType'   => 'required|in:select,color,image,text',
            'editStatus' => 'required|in:active,inactive',
        ]);

        $attribute->update([
            'name'   => $this->editName,
            'slug'   => $this->editSlug,
            'type'   => $this->editType,
            'status' => $this->editStatus,
        ]);

        activity('catalog')
            ->causedBy(auth()->user())
            ->performedOn($attribute)
            ->event('updated')
            ->log("Attribute \"{$attribute->name}\" was updated");

        $this->editModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Attribute updated successfully']);
    }

    public function toggleStatus(int $id): void
    {
        $attribute = Attribute::findOrFail($id);
        $newStatus = $attribute->status === 'active' ? 'inactive' : 'active';
        $attribute->update(['status' => $newStatus]);

        $this->dispatch('toast', ['type' => 'success', 'message' => "{$attribute->name} is now {$newStatus}"]);
    }

    public function deleteAttribute(int $id): void
    {
        $attribute = Attribute::findOrFail($id);
        $name = $attribute->name;

        $attribute->delete();

        activity('catalog')
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name])
            ->event('deleted')
            ->log("Attribute \"{$name}\" was deleted");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Attribute deleted']);
    }

    public function openValuesModal(int $attributeId): void
    {
        $this->valuesAttributeId = $attributeId;
        $this->resetValueForm();
        $this->cancelEditValue();
        $this->valuesModal = true;
    }

    protected function resetValueForm(): void
    {
        $this->reset(['newValueText', 'newValueSlug', 'newSwatchType', 'newSwatchImageId', 'valueSlugLocked']);
        $this->newSwatchColor = '#000000';
    }

    public function addValue(): void
    {
        $attribute = Attribute::findOrFail($this->valuesAttributeId);

        $this->validate([
            'newValueText' => 'required|string|max:100',
            'newValueSlug' => 'required|string|max:120|unique:attribute_values,slug,NULL,id,attribute_id,' . $attribute->id,
        ]);

        $swatchType  = null;
        $swatchValue = null;

        if ($attribute->type === 'color') {
            $swatchType  = 'color';
            $swatchValue = $this->newSwatchColor;
        } elseif ($attribute->type === 'image') {
            $swatchType  = 'image';
            $swatchValue = $this->newSwatchImageId ?: null;
        }

        $maxOrder = $attribute->values()->max('sort_order') ?? 0;

        AttributeValue::create([
            'attribute_id' => $attribute->id,
            'value'        => $this->newValueText,
            'slug'         => $this->newValueSlug,
            'swatch_type'  => $swatchType,
            'swatch_value' => $swatchValue,
            'sort_order'   => $maxOrder + 1,
        ]);

        $this->resetValueForm();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Value added']);
    }

    public function deleteValue(int $id): void
    {
        AttributeValue::findOrFail($id)->delete();

        if ($this->editingValueId === $id) {
            $this->cancelEditValue();
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Value removed']);
    }

    public function editValue(int $id): void
    {
        $value = AttributeValue::findOrFail($id);

        $this->editingValueId   = $value->id;
        $this->editValueText    = $value->value;
        $this->editValueSlug    = $value->slug;
        $this->editSwatchColor  = $value->swatch_type === 'color' ? ($value->swatch_value ?: '#000000') : '#000000';
        $this->editSwatchImageId = $value->swatch_type === 'image' ? $value->swatch_value : null;
        $this->resetValidation();
    }

    public function cancelEditValue(): void
    {
        $this->reset(['editingValueId', 'editValueText', 'editValueSlug', 'editSwatchImageId']);
        $this->editSwatchColor = '#000000';
    }

    public function updateValue(): void
    {
        $value = AttributeValue::findOrFail($this->editingValueId);
        $attribute = $value->attribute;

        $this->validate([
            'editValueText' => 'required|string|max:100',
            'editValueSlug' => 'required|string|max:120|unique:attribute_values,slug,' . $value->id . ',id,attribute_id,' . $attribute->id,
        ]);

        $swatchType  = $value->swatch_type;
        $swatchValue = $value->swatch_value;

        if ($attribute->type === 'color') {
            $swatchType  = 'color';
            $swatchValue = $this->editSwatchColor;
        } elseif ($attribute->type === 'image') {
            $swatchType  = 'image';
            $swatchValue = $this->editSwatchImageId ?: null;
        }

        $value->update([
            'value'        => $this->editValueText,
            'slug'         => $this->editValueSlug,
            'swatch_type'  => $swatchType,
            'swatch_value' => $swatchValue,
        ]);

        $this->cancelEditValue();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Value updated']);
    }

    public function reorderValues(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            AttributeValue::where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }

    public function render(): mixed
    {
        $attributes = Attribute::query()
            ->withCount('values')
            ->when($this->search, fn($q) => $q->where(fn($s) => $s
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%")
            ))
            ->orderBy('name')
            ->get();

        $valuesAttribute = $this->valuesModal && $this->valuesAttributeId
            ? Attribute::with('values')->find($this->valuesAttributeId)
            : null;

        return view('livewire.admin.catalog.attributes', [
            'attributes'      => $attributes,
            'totalCount'      => Attribute::count(),
            'activeCount'     => Attribute::where('status', 'active')->count(),
            'valuesAttribute' => $valuesAttribute,
        ])->layout('layouts.admin.admin');
    }
}
