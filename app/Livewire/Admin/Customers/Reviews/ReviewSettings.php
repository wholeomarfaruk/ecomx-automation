<?php

namespace App\Livewire\Admin\Customers\Reviews;

use App\Models\Setting;
use Livewire\Component;

class ReviewSettings extends Component
{
    protected const GROUP = 'reviews';

    public bool $enabled = true;

    public bool $allowCustomerReviews = true;
    public bool $allowAdminReviews = true;

    public bool $requirePurchase = false;
    public bool $requireDeliveredOrder = false;

    public bool $autoApprove = false;
    public bool $preventSelfApproval = true;

    public bool $allowImages = true;
    public bool $allowVideos = true;
    public int $maxImages = 5;
    public int $maxVideos = 1;

    public bool $requireImageStorefront = true;
    public bool $requireImageAdmin = true;

    public bool $allowReplies = true;
    public int $maxReplyDepth = 2;

    public bool $helpfulVoteEnabled = true;

    public int $productPageReviewLimit = 20;

    public function mount(): void
    {
        $this->enabled = (bool) Setting::get('enabled', true, static::GROUP);

        $this->allowCustomerReviews = (bool) Setting::get('allow_customer_reviews', true, static::GROUP);
        $this->allowAdminReviews = (bool) Setting::get('allow_admin_reviews', true, static::GROUP);

        $this->requirePurchase = (bool) Setting::get('require_purchase', false, static::GROUP);
        $this->requireDeliveredOrder = (bool) Setting::get('require_delivered_order', false, static::GROUP);

        $this->autoApprove = (bool) Setting::get('auto_approve', false, static::GROUP);
        $this->preventSelfApproval = (bool) Setting::get('prevent_self_approval', true, static::GROUP);

        $this->allowImages = (bool) Setting::get('allow_images', true, static::GROUP);
        $this->allowVideos = (bool) Setting::get('allow_videos', true, static::GROUP);
        $this->maxImages = (int) Setting::get('max_images', 5, static::GROUP);
        $this->maxVideos = (int) Setting::get('max_videos', 1, static::GROUP);

        $this->requireImageStorefront = (bool) Setting::get('require_image_storefront', true, static::GROUP);
        $this->requireImageAdmin = (bool) Setting::get('require_image_admin', true, static::GROUP);

        $this->allowReplies = (bool) Setting::get('allow_replies', true, static::GROUP);
        $this->maxReplyDepth = (int) Setting::get('max_reply_depth', 2, static::GROUP);

        $this->helpfulVoteEnabled = (bool) Setting::get('helpful_vote_enabled', true, static::GROUP);

        $this->productPageReviewLimit = (int) Setting::get('product_page_review_limit', 20, static::GROUP);
    }

    public function save(): void
    {
        $this->validate([
            'maxImages' => 'required|integer|min:0|max:20',
            'maxVideos' => 'required|integer|min:0|max:5',
            'maxReplyDepth' => 'required|integer|min:1|max:5',
            'productPageReviewLimit' => 'required|integer|min:1|max:100',
        ]);

        $values = [
            'enabled' => $this->enabled,
            'allow_customer_reviews' => $this->allowCustomerReviews,
            'allow_admin_reviews' => $this->allowAdminReviews,
            'require_purchase' => $this->requirePurchase,
            'require_delivered_order' => $this->requireDeliveredOrder,
            'auto_approve' => $this->autoApprove,
            'prevent_self_approval' => $this->preventSelfApproval,
            'allow_images' => $this->allowImages,
            'allow_videos' => $this->allowVideos,
            'max_images' => $this->maxImages,
            'max_videos' => $this->maxVideos,
            'require_image_storefront' => $this->requireImageStorefront,
            'require_image_admin' => $this->requireImageAdmin,
            'allow_replies' => $this->allowReplies,
            'max_reply_depth' => $this->maxReplyDepth,
            'helpful_vote_enabled' => $this->helpfulVoteEnabled,
            'product_page_review_limit' => $this->productPageReviewLimit,
        ];

        foreach ($values as $key => $value) {
            Setting::set($key, $value, static::GROUP);
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Review settings saved']);
    }

    public function render(): mixed
    {
        return view('livewire.admin.customers.reviews.review-settings')
            ->layout('layouts.admin.admin');
    }
}
