<?php

namespace App\Livewire\EcomxFashion;

use App\Enums\File\Type;
use App\Models\File;
use App\Models\FileItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductReviewHelpfulVote;
use App\Models\ProductReviewMedia;
use App\Models\ProductReviewStatistic;
use App\Models\Setting;
use App\Support\EcomxFashion\Catalog;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Lazy;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Reviews carousel for the ecomx-fashion product page. Shows this product's
 * own approved reviews first, then fills remaining slots (up to the
 * reviews.product_page_review_limit setting) with approved reviews from
 * other products so the slider never looks empty. Only reviews that have at
 * least one photo/video are shown here — the slider is a media grid. Falls
 * back to Catalog demo data only when no approved reviews with media exist
 * at all. Also owns the "Write a review" form — the storefront is
 * device-based (no customer login), so submissions are guest reviews
 * awaiting moderation, and helpful votes are tracked per device.
 */
#[Lazy]
class ProductReviewsSlider extends Component
{
    use WithFileUploads;

    public int $productId;
    public array $reviews = [];

    public ?float $ownAverageRating = null;
    public int $ownReviewCount = 0;

    public bool $showForm = false;
    public bool $submitted = false;

    public $reviewerName;
    public $rating;
    public $comment;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile[] */
    public array $photos = [];

    public bool $requireImageStorefront = true;

    public function mount(int $productId): void
    {
        $this->productId = $productId;
        $this->requireImageStorefront = (bool) Setting::get('require_image_storefront', true, 'reviews');

        $this->loadOwnRatingSummary();
        $this->loadReviews();
    }

    protected function loadOwnRatingSummary(): void
    {
        $stat = Product::find($this->productId)?->reviewStatistic;

        $this->ownAverageRating = $stat && $stat->total_reviews > 0 ? (float) $stat->average_rating : null;
        $this->ownReviewCount = $stat?->total_reviews ?? 0;
    }

    protected function loadReviews(): void
    {
        $limit = (int) Setting::get('product_page_review_limit', 20, 'reviews');
        $device = request()->attributes->get('device');

        $own = ProductReview::approved()
            ->forProduct($this->productId)
            ->whereHas('media')
            ->with(['media', 'product', 'customer', 'helpfulVotes'])
            ->latest()
            ->limit($limit)
            ->get();

        $remaining = $limit - $own->count();

        $fallback = $remaining > 0
            ? ProductReview::approved()
                ->where('product_id', '!=', $this->productId)
                ->whereHas('media')
                ->with(['media', 'product', 'customer', 'helpfulVotes'])
                ->inRandomOrder()
                ->limit($remaining)
                ->get()
            : collect();

        $mapped = $own->concat($fallback)
            ->map(fn (ProductReview $review) => $this->mapReview($review, $review->product_id === $this->productId, $device))
            ->all();

        $this->reviews = ! empty($mapped) ? $mapped : Catalog::reviews();
    }

    public function toggleHelpful(int $reviewId): void
    {
        $device = request()->attributes->get('device');

        if (! $device) {
            return;
        }

        $vote = ProductReviewHelpfulVote::where('product_review_id', $reviewId)
            ->where('device_id', $device->id)
            ->first();

        if ($vote) {
            $vote->delete();
        } else {
            ProductReviewHelpfulVote::create([
                'product_review_id' => $reviewId,
                'device_id' => $device->id,
                'vote_type' => 'helpful',
                'created_at' => now(),
            ]);
        }

        ProductReview::where('id', $reviewId)->update([
            'helpful_count' => ProductReviewHelpfulVote::where('product_review_id', $reviewId)->count(),
        ]);

        $this->loadReviews();
    }

    public function openForm(): void
    {
        $this->reset(['reviewerName', 'rating', 'comment', 'photos', 'submitted']);
        $this->resetValidation();
        $this->showForm = true;
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function setRating(int $value): void
    {
        $this->rating = $value;
    }

    public function submitReview(): void
    {
        if (! Setting::get('enabled', true, 'reviews') || ! Setting::get('allow_customer_reviews', true, 'reviews')) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Reviews are currently closed for this store.']);
            return;
        }

        $requirePhoto = (bool) Setting::get('require_image_storefront', true, 'reviews');

        $this->validate([
            'reviewerName' => 'required|string|max:150',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:2000',
            'photos' => ($requirePhoto ? 'required' : 'nullable') . '|array|max:5',
            'photos.*' => 'image|max:5120',
        ], [
            'photos.required' => 'Please add at least one photo of the product.',
            'photos.min' => 'Please add at least one photo of the product.',
        ]);

        $autoApprove = (bool) Setting::get('auto_approve', false, 'reviews');

        $review = ProductReview::create([
            'product_id' => $this->productId,
            'author_type' => 'customer',
            'author_name' => $this->reviewerName,
            'source' => 'website',
            'rating' => $this->rating,
            'comment' => $this->comment,
            'status' => $autoApprove ? 'approved' : 'pending',
            'verified_at' => $autoApprove ? now() : null,
        ]);

        $this->storePhotos($review);

        if ($autoApprove) {
            ProductReviewStatistic::recalculateFor($this->productId);
            $this->loadOwnRatingSummary();
            $this->loadReviews();
        }

        $this->submitted = true;
    }

    protected function storePhotos(ProductReview $review): void
    {
        foreach ($this->photos as $index => $photo) {
            $extension = $photo->getClientOriginalExtension();
            $path = $photo->store('uploads/reviews', 'public');
            $fullPath = Storage::disk('public')->path($path);

            $file = File::create([
                'name' => $photo->getClientOriginalName(),
                'type' => Type::fromExtension($extension),
                'extension' => $extension,
            ]);

            FileItem::create([
                'file_id' => $file->id,
                'type' => 'original',
                'size' => filesize($fullPath),
                'path' => $path,
            ]);

            ProductReviewMedia::create([
                'product_review_id' => $review->id,
                'file_id' => $file->id,
                'media_type' => 'image',
                'sort_order' => $index,
            ]);
        }
    }

    protected function mapReview(ProductReview $review, bool $isProductReview, mixed $device): array
    {
        $media = $review->media->first();

        return [
            'id' => $review->id,
            'name' => $review->authorName(),
            'verified' => $review->is_verified_purchase,
            'rating' => $review->rating,
            'product' => $review->product->name ?? '',
            'video' => $media?->isVideo() ?? false,
            'img' => $media?->getUrl(),
            'avatar' => null,
            'text' => $review->comment,
            'is_product_review' => $isProductReview,
            'helpful_count' => $review->helpfulVotes->count(),
            'voted_helpful' => $device ? $review->helpfulVotes->contains('device_id', $device->id) : false,
        ];
    }

    public function placeholder()
    {
        return view('ecomx-fashion.livewire.product-reviews-slider-placeholder');
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.product-reviews-slider');
    }
}
