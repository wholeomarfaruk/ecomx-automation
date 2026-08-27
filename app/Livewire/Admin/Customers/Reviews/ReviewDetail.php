<?php

namespace App\Livewire\Admin\Customers\Reviews;

use App\Models\ProductReview;
use App\Models\ProductReviewReply;
use App\Models\ProductReviewStatistic;
use Livewire\Component;

class ReviewDetail extends Component
{
    public int $id;

    public $replyComment = '';

    public bool $reasonModal = false;
    public $reasonTarget = 'review';
    public ?int $reasonReplyId = null;
    public $reasonAction = 'reject';
    public $reason = '';

    public function mount(int $id): void
    {
        $this->id = $id;
    }

    public function approve(): void
    {
        $review = ProductReview::findOrFail($this->id);
        $review->approve(auth()->user());
        ProductReviewStatistic::recalculateFor($review->product_id);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Review approved']);
    }

    public function openReasonModal(string $target, string $action, ?int $replyId = null): void
    {
        $this->reasonTarget = $target;
        $this->reasonAction = $action;
        $this->reasonReplyId = $replyId;
        $this->reason = '';
        $this->reasonModal = true;
    }

    public function confirmReasonAction(): void
    {
        if ($this->reasonTarget === 'review') {
            $review = ProductReview::findOrFail($this->id);
            $wasApproved = $review->status === 'approved';

            $this->reasonAction === 'reject'
                ? $review->reject(auth()->user(), $this->reason ?: null)
                : $review->hide(auth()->user(), $this->reason ?: null);

            if ($wasApproved) {
                ProductReviewStatistic::recalculateFor($review->product_id);
            }
        } else {
            $reply = ProductReviewReply::findOrFail($this->reasonReplyId);

            $this->reasonAction === 'reject'
                ? $reply->reject(auth()->user(), $this->reason ?: null)
                : $reply->hide(auth()->user(), $this->reason ?: null);
        }

        $this->reasonModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Saved']);
    }

    public function approveReply(int $replyId): void
    {
        ProductReviewReply::findOrFail($replyId)->approve(auth()->user());
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Reply approved']);
    }

    public function submitReply(): void
    {
        $this->validate([
            'replyComment' => 'required|string|max:2000',
        ]);

        $review = ProductReview::findOrFail($this->id);

        ProductReviewReply::create([
            'product_review_id' => $review->id,
            'created_by_user_id' => auth()->id(),
            'author_type' => 'admin',
            'comment' => $this->replyComment,
            'status' => 'pending',
        ]);

        $this->replyComment = '';
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Reply added — pending verification']);
    }

    public function render(): mixed
    {
        $review = ProductReview::with([
            'product', 'variant', 'customer', 'createdBy',
            'media', 'verifiedBy', 'rejectedBy', 'hiddenBy',
            'replies' => fn ($q) => $q->with(['customer', 'createdBy'])->orderBy('created_at'),
            'helpfulVotes',
        ])->findOrFail($this->id);

        return view('livewire.admin.customers.reviews.review-detail', [
            'review' => $review,
        ])->layout('layouts.admin.admin');
    }
}
