<?php

namespace App\Livewire\Admin\File;

use App\Models\File;
use App\Models\FileItem;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class MediaPicker extends Component
{
    use WithPagination;

    public bool $mediapickerModal = false;
    public array $selected = [];
    public bool $multiple = false;
    public ?string $target = null;
    public ?string $type = null;
    public string $search = '';
    public string $filterType = '';

    protected string $paginationTheme = 'tailwind';
    protected $listeners = ['openMediaPicker', 'fileUploaded'];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function openMediaPicker($target = null, $multiple = false, $type = null): void
    {
        $this->target = $target;
        $this->multiple = $multiple;
        $this->type = $type;
        $this->filterType = $type ?? '';
        $this->reset('selected', 'search');
        $this->resetPage();
        $this->mediapickerModal = true;
    }

    public function selectAll(array $ids): void
    {
        $this->selected = array_values(array_unique(array_merge($this->selected, $ids)));
    }

    public function deselectAll(): void
    {
        $this->selected = [];
    }

    public function selectImage(int $id): void
    {
        if ($this->multiple) {
            if (in_array($id, $this->selected)) {
                $this->selected = array_values(array_diff($this->selected, [$id]));
            } else {
                $this->selected[] = $id;
            }
        } else {
            $this->selected = [$id];
        }
    }

    public function save(): void
    {
        if (empty($this->selected)) {
            $this->close();
            return;
        }

        $id = $this->multiple ? array_values($this->selected) : $this->selected[0];
        $this->dispatch('mediaSelected', field: $this->target, id: $id);
        $this->close();
    }

    public function close(): void
    {
        $this->mediapickerModal = false;
        $this->reset('selected', 'search', 'filterType', 'target', 'type', 'multiple');
    }

    public function removeSelect(int $id): void
    {
        $this->selected = array_values(array_diff($this->selected, [$id]));
    }

    public function delete(int $id): void
    {
        $file = File::find($id);

        if ($file) {
            foreach (FileItem::where('file_id', $file->id)->get() as $item) {
                Storage::disk('public')->delete($item->path);
                $item->delete();
            }
            $file->delete();
            $this->selected = array_values(array_diff($this->selected, [$id]));
        }
    }

    public function fileUploaded(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = File::query()
            ->when($this->type, fn($q) => $q->where('type', $this->type))
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->with('items')
            ->latest();

        $files = $query->paginate(15);

        return view('livewire.admin.file.media-picker', [
            'files' => $files,
            'currentPageIds' => $files->pluck('id')->toArray(),
        ]);
    }
}
