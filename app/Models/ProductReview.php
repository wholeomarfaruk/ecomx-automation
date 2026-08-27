<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReview extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id', 'product_variant_id',
        'customer_id',
        'author_type', 'author_name', 'author_email', 'author_phone',
        'created_by_user_id',
        'source',
        'order_id', 'order_item_id', 'is_verified_purchase',
        'rating', 'title', 'comment',
        'status',
        'verified_by', 'verified_at',
        'rejected_by', 'rejected_at', 'rejection_reason',
        'hidden_by', 'hidden_at', 'hidden_reason',
        'helpful_count',
    ];

    protected function casts(): array
    {
        return [
            'is_verified_purchase' => 'boolean',
            'rating' => 'integer',
            'helpful_count' => 'integer',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductReviewMedia::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ProductReviewReply::class);
    }

    public function helpfulVotes(): HasMany
    {
        return $this->hasMany(ProductReviewHelpfulVote::class);
    }

    public function authorName(): string
    {
        if ($this->author_type === 'admin') {
            return $this->author_name ?: ($this->createdBy?->name ?? 'Admin');
        }

        return $this->customer?->full_name ?: ($this->author_name ?: 'Guest');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', 'rejected');
    }

    public function scopeHidden(Builder $query): Builder
    {
        return $query->where('status', 'hidden');
    }

    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function approve(User $approver): void
    {
        $this->update([
            'status' => 'approved',
            'verified_by' => $approver->id,
            'verified_at' => now(),
        ]);
    }

    public function reject(User $approver, ?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'rejected_by' => $approver->id,
            'rejected_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    public function hide(User $approver, ?string $reason = null): void
    {
        $this->update([
            'status' => 'hidden',
            'hidden_by' => $approver->id,
            'hidden_at' => now(),
            'hidden_reason' => $reason,
        ]);
    }
}
