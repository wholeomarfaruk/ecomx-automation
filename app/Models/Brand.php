<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'status', 'featured',
        'logo_image_id', 'cover_image_id',
        'meta_image_id', 'meta_title', 'meta_description',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'featured' => 'boolean',
        ];
    }

    public function logoImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'logo_image_id');
    }

    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'cover_image_id');
    }

    public function metaImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'meta_image_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->orderBy('sort_order')->orderBy('name');
    }
}
