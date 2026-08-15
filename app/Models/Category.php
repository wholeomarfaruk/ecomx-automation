<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'parent_id', 'description', 'status',
        'featured_image_id', 'cover_image_id',
        'meta_image_id', 'meta_title', 'meta_description',
        'sort_order',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'featured_image_id');
    }

    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'cover_image_id');
    }

    public function metaImage(): BelongsTo
    {
        return $this->belongsTo(File::class, 'meta_image_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_category_pivot')
            ->using(ProductCategoryPivot::class)
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')->orderBy('sort_order')->orderBy('name');
    }
}
