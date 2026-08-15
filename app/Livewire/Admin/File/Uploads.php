<?php

namespace App\Livewire\Admin\File;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\File;
use App\Models\FileItem;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;

class Uploads extends Component
{
    use WithPagination, WithMediaPicker;

    public string $search = '';
    public string $filterType = '';
    public array $selected = [];
    public bool $showUploadPanel = false;

    protected $listeners = ['fileUploaded'];
    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function toggleSelect(int $id): void
    {
        if (in_array($id, $this->selected)) {
            $this->selected = array_values(array_diff($this->selected, [$id]));
        } else {
            $this->selected[] = $id;
        }
    }

    public function selectAll(): void
    {
        $this->selected = File::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->pluck('id')
            ->toArray();
    }

    public function deselectAll(): void
    {
        $this->selected = [];
    }

    public function delete(int $id): void
    {
        $file = File::find($id);

        if ($file) {
            $fileName = $file->name;
            $fileType = $file->type;

            foreach (FileItem::where('file_id', $file->id)->get() as $item) {
                Storage::disk('public')->delete($item->path);
                $item->delete();
            }
            $file->delete();
            $this->selected = array_values(array_diff($this->selected, [$id]));

            activity('uploads')
                ->causedBy(auth()->user())
                ->withProperties(['name' => $fileName, 'type' => $fileType])
                ->event('deleted')
                ->log("File \"{$fileName}\" was deleted");
        }
    }

    public function deleteSelected(): void
    {
        $count = count($this->selected);

        foreach ($this->selected as $id) {
            $this->delete($id);
        }
        $this->selected = [];

        $this->dispatch('toast', [
            'type'    => 'success',
            'message' => 'Selected files deleted successfully.',
        ]);
    }

    public function fileUploaded(): void
    {
        $this->showUploadPanel = false;
        $this->resetPage();
    }

    public function render()
    {
        $files = File::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->with('items')
            ->latest()
            ->paginate(20);

        return view('livewire.admin.file.uploads', compact('files'))
            ->layout('layouts.admin.admin');
    }
}
