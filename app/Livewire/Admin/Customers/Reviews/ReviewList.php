<?php

namespace App\Livewire\Admin\Customers\Reviews;

use App\Livewire\Traits\WithMediaPicker;
use App\Models\ProductReview;
use App\Models\ProductReviewStatistic;
use App\Models\Setting;
use Livewire\Component;
use Livewire\WithPagination;

class ReviewList extends Component
{
    use WithPagination, WithMediaPicker, HandlesReviewForm;

    public $search = '';
    public $tab = 'all';
    public $filterRating = '';
    public $filterSource = '';

    protected string $paginationTheme = 'tailwind';

    // bulk selection
    public array $selected = [];
    public bool $selectPage = false;

    // reject/hide modal
    public bool $reasonModal = false;
    public ?int $reasonReviewId = null;
    public $reasonAction = 'reject';
    public $reason = '';

    public function updatingSearch(): void { $this->resetPage(); $this->clearSelection(); }
    public function updatingTab(): void { $this->resetPage(); $this->clearSelection(); }
    public function updatingFilterRating(): void { $this->resetPage(); $this->clearSelection(); }
    public function updatingFilterSource(): void { $this->resetPage(); $this->clearSelection(); }

    public function updatedSelectPage(bool $value): void
    {
        $ids = $this->currentPageIds();
        $this->selected = $value
            ? array_values(array_unique([...$this->selected, ...$ids]))
            : array_values(array_diff($this->selected, $ids));
    }

    protected function currentPageIds(): array
    {
        return $this->buildQuery()->forPage($this->getPage(), 15)->pluck('id')->all();
    }

    protected function buildQuery()
    {
        return ProductReview::query()
            ->when($this->tab !== 'all', fn ($q) => $q->where('status', $this->tab))
            ->when($this->filterRating !== '', fn ($q) => $q->where('rating', $this->filterRating))
            ->when($this->filterSource !== '', fn ($q) => $q->where('source', $this->filterSource))
            ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                ->where('author_name', 'like', "%{$this->search}%")
                ->orWhere('title', 'like', "%{$this->search}%")
                ->orWhere('comment', 'like', "%{$this->search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('full_name', 'like', "%{$this->search}%"))
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$this->search}%"))
            ))
            ->orderByDesc('id');
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectPage = false;
    }

    public function approve(int $id): void
    {
        $review = ProductReview::findOrFail($id);
        $review->approve(auth()->user());
        ProductReviewStatistic::recalculateFor($review->product_id);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Review approved']);
    }

    public function openReasonModal(int $id, string $action): void
    {
        $this->reasonReviewId = $id;
        $this->reasonAction = $action;
        $this->reason = '';
        $this->reasonModal = true;
    }

    public function confirmReasonAction(): void
    {
        $review = ProductReview::findOrFail($this->reasonReviewId);
        $wasApproved = $review->status === 'approved';

        if ($this->reasonAction === 'reject') {
            $review->reject(auth()->user(), $this->reason ?: null);
        } else {
            $review->hide(auth()->user(), $this->reason ?: null);
        }

        if ($wasApproved) {
            ProductReviewStatistic::recalculateFor($review->product_id);
        }

        $this->reasonModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Review ' . ($this->reasonAction === 'reject' ? 'rejected' : 'hidden')]);
    }

    public function deleteReview(int $id): void
    {
        $review = ProductReview::findOrFail($id);
        $productId = $review->product_id;
        $wasApproved = $review->status === 'approved';

        $review->delete();

        if ($wasApproved) {
            ProductReviewStatistic::recalculateFor($productId);
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Review deleted']);
    }

    public function bulkApprove(): void
    {
        if (empty($this->selected)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Select at least one review first']);
            return;
        }

        $reviews = ProductReview::whereIn('id', $this->selected)->get();
        $productIds = $reviews->pluck('product_id')->unique();

        ProductReview::whereIn('id', $this->selected)->update([
            'status' => 'approved',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $productIds->each(fn ($id) => ProductReviewStatistic::recalculateFor($id));

        $count = count($this->selected);
        $this->clearSelection();
        $this->dispatch('toast', ['type' => 'success', 'message' => "{$count} review(s) approved"]);
    }

    public function render(): mixed
    {
        $reviews = $this->buildQuery()->with(['product', 'customer', 'media'])->paginate(15);

        return view('livewire.admin.customers.reviews.review-list', [
            'reviews' => $reviews,
            'totalCount' => ProductReview::count(),
            'pendingCount' => ProductReview::pending()->count(),
            'approvedCount' => ProductReview::approved()->count(),
            'rejectedCount' => ProductReview::rejected()->count(),
            'hiddenCount' => ProductReview::hidden()->count(),
            'allowAdminReviews' => (bool) Setting::get('allow_admin_reviews', true, 'reviews'),
            ...$this->reviewFormViewData(),
        ])->layout('layouts.admin.admin');
    }
}
