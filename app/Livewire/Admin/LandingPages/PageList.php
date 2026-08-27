<?php

namespace App\Livewire\Admin\LandingPages;

use App\Models\LandingPage;
use Livewire\Component;
use Livewire\WithPagination;

class PageList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function publish(int $id): void
    {
        $page = LandingPage::findOrFail($id);
        $page->update(['status' => 'published', 'published_at' => now()]);

        $this->dispatch('toast', ['type' => 'success', 'message' => "\"{$page->name}\" published"]);
    }

    public function unpublish(int $id): void
    {
        $page = LandingPage::findOrFail($id);
        $page->update(['status' => 'unpublished']);

        $this->dispatch('toast', ['type' => 'success', 'message' => "\"{$page->name}\" unpublished"]);
    }

    public function duplicate(int $id): void
    {
        $original = LandingPage::findOrFail($id);

        $copy = $original->replicate();
        $copy->name = $original->name . ' Copy';
        $copy->title = $original->title . ' Copy';
        $copy->slug = $this->uniqueSlug($original->slug . '-copy');
        $copy->status = 'draft';
        $copy->published_at = null;
        $copy->created_by = auth()->id();
        $copy->save();

        $this->dispatch('toast', ['type' => 'success', 'message' => "Duplicated as \"{$copy->name}\""]);
    }

    public function deletePage(int $id): void
    {
        $page = LandingPage::findOrFail($id);
        $name = $page->name;
        $page->delete();

        $this->dispatch('toast', ['type' => 'success', 'message' => "\"{$name}\" deleted"]);
    }

    protected function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 1;

        while (LandingPage::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        return $slug;
    }

    public function render(): mixed
    {
        $pages = LandingPage::query()
            ->with('template')
            ->when($this->search, fn($q) => $q->where(fn($s) => $s
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('title', 'like', "%{$this->search}%")
                ->orWhere('slug', 'like', "%{$this->search}%")
            ))
            ->when($this->filterStatus !== '', fn($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('id')
            ->paginate(15);

        return view('livewire.admin.landing-pages.page-list', [
            'pages' => $pages,
            'totalCount' => LandingPage::count(),
            'publishedCount' => LandingPage::where('status', 'published')->count(),
            'draftCount' => LandingPage::where('status', 'draft')->count(),
        ])->layout('layouts.admin.admin');
    }
}
