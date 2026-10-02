<?php

namespace App\Livewire\Admin\Customers\Reviews;

use App\Enums\File\Type;
use App\Models\Customer;
use App\Models\File;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductReviewMedia;
use App\Models\ProductReviewStatistic;
use App\Models\Setting;

/**
 * Shared Add / Edit review modal for ReviewList and ReviewDetail.
 * Photos and videos come from the media library picker (which also has an Upload tab),
 * so the host component must also use WithMediaPicker.
 */
trait HandlesReviewForm
{
    public bool $formModal = false;
    public ?int $editingReviewId = null;
    public string $formAuthorType = 'admin';

    public $formProductId = '';
    public $formCustomerId = '';
    public $formAuthorName = '';
    public $formSource = 'admin';
    public $formRating = '';
    public $formTitle = '';
    public $formComment = '';
    public $formVerifiedPurchase = false;
    public array $formImageIds = [];
    public array $formVideoIds = [];

    public function openCreateModal(): void
    {
        if (! Setting::get('allow_admin_reviews', true, 'reviews')) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Admin-added reviews are disabled in Review Settings']);
            return;
        }

        $this->resetReviewForm();
        $this->formModal = true;
    }

    public function openEditModal(int $id): void
    {
        $review = ProductReview::with('media')->findOrFail($id);

        $this->resetReviewForm();
        $this->editingReviewId = $review->id;
        $this->formAuthorType = $review->author_type ?: 'admin';
        $this->formProductId = (string) $review->product_id;
        $this->formCustomerId = $review->customer_id ? (string) $review->customer_id : '';
        $this->formAuthorName = $review->author_name ?? '';
        $this->formSource = $review->source ?: 'admin';
        $this->formRating = (string) $review->rating;
        $this->formTitle = $review->title ?? '';
        $this->formComment = $review->comment ?? '';
        $this->formVerifiedPurchase = (bool) $review->is_verified_purchase;

        $media = $review->media->sortBy('sort_order')->whereNotNull('file_id');
        $this->formImageIds = $media->reject->isVideo()->pluck('file_id')->values()->all();
        $this->formVideoIds = $media->filter->isVideo()->pluck('file_id')->values()->all();

        $this->formModal = true;
    }

    public function closeReviewForm(): void
    {
        $this->formModal = false;
    }

    protected function resetReviewForm(): void
    {
        $this->reset([
            'editingReviewId', 'formAuthorType', 'formProductId', 'formCustomerId', 'formAuthorName', 'formTitle',
            'formComment', 'formVerifiedPurchase', 'formImageIds', 'formVideoIds', 'formRating',
        ]);
        $this->formSource = 'admin';
        $this->resetValidation();
    }

    public function saveReview(): void
    {
        $review = $this->editingReviewId ? ProductReview::findOrFail($this->editingReviewId) : null;

        if (! $review && ! Setting::get('allow_admin_reviews', true, 'reviews')) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Admin-added reviews are disabled in Review Settings']);
            return;
        }

        $this->validate([
            'formProductId' => 'required|integer|exists:products,id',
            'formCustomerId' => 'nullable|integer|exists:customers,id',
            'formAuthorName' => 'required_without:formCustomerId|nullable|string|max:150',
            'formSource' => 'required|in:website,admin,facebook,whatsapp,phone,import',
            'formRating' => 'required|integer|min:1|max:5',
            'formTitle' => 'nullable|string|max:255',
            'formComment' => 'required|string|max:2000',
            'formImageIds' => 'array|max:5',
            'formImageIds.*' => 'integer|exists:files,id',
            'formVideoIds' => 'array|max:5',
            'formVideoIds.*' => 'integer|exists:files,id',
        ], [
            'formAuthorName.required_without' => 'Enter a reviewer name or pick a customer.',
        ]);

        $isAdminAuthored = ! $review || $review->author_type === 'admin';
        if ($isAdminAuthored && Setting::get('require_image_admin', true, 'reviews') && empty($this->formImageIds)) {
            $this->addError('formImageIds', 'Please add at least one photo.');
            return;
        }

        $data = [
            'product_id' => $this->formProductId,
            'customer_id' => $this->formCustomerId ?: null,
            'author_name' => $this->formAuthorName ?: null,
            'source' => $this->formSource,
            'is_verified_purchase' => (bool) $this->formVerifiedPurchase,
            'rating' => $this->formRating,
            'title' => $this->formTitle ?: null,
            'comment' => $this->formComment,
        ];

        if ($review) {
            $oldProductId = $review->product_id;
            $review->update($data);

            if ($review->status === 'approved') {
                ProductReviewStatistic::recalculateFor($review->product_id);
                if ($oldProductId != $review->product_id) {
                    ProductReviewStatistic::recalculateFor($oldProductId);
                }
            }
        } else {
            $review = ProductReview::create($data + [
                'author_type' => 'admin',
                'created_by_user_id' => auth()->id(),
                'status' => 'pending',
            ]);
        }

        $this->syncReviewMedia($review);

        $message = $this->editingReviewId
            ? 'Review updated'
            : "Review #{$review->id} added — pending verification";

        $this->formModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => $message]);
    }

    protected function syncReviewMedia(ProductReview $review): void
    {
        $review->media()->delete();

        $ids = array_merge(
            array_map('intval', $this->formImageIds),
            array_map('intval', $this->formVideoIds),
        );

        $files = File::whereIn('id', $ids)->get()->keyBy('id');

        foreach (array_values(array_unique($ids)) as $index => $fileId) {
            $file = $files->get($fileId);
            if (! $file) {
                continue;
            }

            ProductReviewMedia::create([
                'product_review_id' => $review->id,
                'file_id' => $file->id,
                'media_type' => $file->type === Type::VIDEO->value ? 'video' : 'image',
                'sort_order' => $index,
            ]);
        }
    }

    protected function reviewFormViewData(): array
    {
        if (! $this->formModal) {
            return [];
        }

        $products = Product::orderBy('name')->get(['id', 'name', 'featured_image_id']);

        return [
            'productOptions' => $products->pluck('name', 'id'),
            'productImages' => $products->pluck('featured_image', 'id')->filter(),
            'customerOptions' => Customer::orderBy('full_name')->get(['id', 'full_name', 'phone'])
                ->mapWithKeys(fn ($c) => [$c->id => $c->phone ? "{$c->full_name} ({$c->phone})" : $c->full_name]),
            'requireImageAdmin' => (bool) Setting::get('require_image_admin', true, 'reviews'),
        ];
    }
}
