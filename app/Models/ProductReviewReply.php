<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReviewReply extends Model
{
    use SoftDeletes;

    protected $table = 'product_review_replies';

    protected $fillable = [
        'product_review_id', 'parent_reply_id',
        'customer_id', 'created_by_user_id', 'author_type',
        'comment',
        'status',
        'verified_by', 'verified_at',
        'rejected_by', 'rejected_at', 'rejection_reason',
        'hidden_by', 'hidden_at', 'hidden_reason',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(ProductReview::class, 'product_review_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductReviewReply::class, 'parent_reply_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProductReviewReply::class, 'parent_reply_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
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
