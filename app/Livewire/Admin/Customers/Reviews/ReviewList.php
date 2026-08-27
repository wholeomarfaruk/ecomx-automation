<?php

namespace App\Livewire\Admin\Customers\Reviews;

use App\Enums\File\Type;
use App\Models\Customer;
use App\Models\File;
use App\Models\FileItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductReviewMedia;
use App\Models\ProductReviewStatistic;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ReviewList extends Component
{
    use WithPagination, WithFileUploads;

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

    // create (admin-added) review modal
    public bool $createModal = false;
    public $newProductId = '';
    public $newCustomerId = '';
    public $newAuthorName = '';
    public $newSource = 'admin';
    public $newRating = '';
    public $newTitle = '';
    public $newComment = '';
    public $newVerifiedPurchase = false;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $newMedia = [];

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

    public function openCreateModal(): void
    {
        if (! Setting::get('allow_admin_reviews', true, 'reviews')) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Admin-added reviews are disabled in Review Settings']);
            return;
        }

        $this->reset([
            'newProductId', 'newCustomerId', 'newAuthorName', 'newTitle',
            'newComment', 'newVerifiedPurchase', 'newMedia',
        ]);
        $this->newSource = 'admin';
        $this->newRating = '';
        $this->resetValidation();
        $this->createModal = true;
    }

    public function removeNewMedia(int $index): void
    {
        unset($this->newMedia[$index]);
        $this->newMedia = array_values($this->newMedia);
    }

    public function createReview(): void
    {
        if (! Setting::get('allow_admin_reviews', true, 'reviews')) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Admin-added reviews are disabled in Review Settings']);
            return;
        }

        $requireImage = (bool) Setting::get('require_image_admin', true, 'reviews');

        $this->validate([
            'newProductId' => 'required|integer|exists:products,id',
            'newCustomerId' => 'nullable|integer|exists:customers,id',
            'newAuthorName' => 'required_without:newCustomerId|nullable|string|max:150',
            'newSource' => 'required|in:website,admin,facebook,whatsapp,phone,import',
            'newRating' => 'required|integer|min:1|max:5',
            'newTitle' => 'nullable|string|max:255',
            'newComment' => 'required|string|max:2000',
            'newMedia' => ($requireImage ? 'required' : 'nullable') . '|array|max:5',
            'newMedia.*' => 'mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi,mkv|max:20480',
        ], [
            'newMedia.required' => 'Please add at least one photo.',
        ]);

        if ($requireImage) {
            $hasImage = collect($this->newMedia)->contains(
                fn ($file) => str_starts_with($file->getMimeType() ?? '', 'image')
            );

            if (! $hasImage) {
                $this->addError('newMedia', 'Please add at least one photo (videos alone are not enough).');
                return;
            }
        }

        $review = ProductReview::create([
            'product_id' => $this->newProductId,
            'customer_id' => $this->newCustomerId ?: null,
            'author_type' => 'admin',
            'author_name' => $this->newAuthorName ?: null,
            'created_by_user_id' => auth()->id(),
            'source' => $this->newSource,
            'is_verified_purchase' => (bool) $this->newVerifiedPurchase,
            'rating' => $this->newRating,
            'title' => $this->newTitle ?: null,
            'comment' => $this->newComment,
            'status' => 'pending',
        ]);

        $this->storeNewMedia($review);

        $this->createModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => "Review added for review #{$review->id} — pending verification"]);
    }

    protected function storeNewMedia(ProductReview $review): void
    {
        foreach ($this->newMedia as $index => $file) {
            $extension = $file->getClientOriginalExtension();
            $type = Type::fromExtension($extension);
            $path = $file->store('uploads/reviews', 'public');
            $fullPath = Storage::disk('public')->path($path);

            $fileModel = File::create([
                'name' => $file->getClientOriginalName(),
                'type' => $type,
                'extension' => $extension,
            ]);

            FileItem::create([
                'file_id' => $fileModel->id,
                'type' => 'original',
                'size' => filesize($fullPath),
                'path' => $path,
            ]);

            ProductReviewMedia::create([
                'product_review_id' => $review->id,
                'file_id' => $fileModel->id,
                'media_type' => $type === Type::VIDEO ? 'video' : 'image',
                'sort_order' => $index,
            ]);
        }
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

        $products = Product::orderBy('name')->get(['id', 'name', 'featured_image_id']);

        return view('livewire.admin.customers.reviews.review-list', [
            'reviews' => $reviews,
            'totalCount' => ProductReview::count(),
            'pendingCount' => ProductReview::pending()->count(),
            'approvedCount' => ProductReview::approved()->count(),
            'rejectedCount' => ProductReview::rejected()->count(),
            'hiddenCount' => ProductReview::hidden()->count(),
            'allowAdminReviews' => (bool) Setting::get('allow_admin_reviews', true, 'reviews'),
            'requireImageAdmin' => (bool) Setting::get('require_image_admin', true, 'reviews'),
            'productOptions' => $products->pluck('name', 'id'),
            'productImages' => $products->pluck('featured_image', 'id')->filter(),
            'customerOptions' => Customer::orderBy('full_name')->get(['id', 'full_name', 'phone'])
                ->mapWithKeys(fn ($c) => [$c->id => $c->phone ? "{$c->full_name} ({$c->phone})" : $c->full_name]),
        ])->layout('layouts.admin.admin');
    }
}
