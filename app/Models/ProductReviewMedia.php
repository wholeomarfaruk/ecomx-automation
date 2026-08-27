<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReviewMedia extends Model
{
    protected $table = 'product_review_media';

    protected $fillable = [
        'product_review_id', 'file_id', 'media_type', 'sort_order',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(ProductReview::class, 'product_review_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function isVideo(): bool
    {
        return $this->media_type === 'video';
    }

    public function getUrl(): ?string
    {
        return $this->file_id ? file_path($this->file_id) : null;
    }
}
