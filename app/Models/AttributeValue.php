<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttributeValue extends Model
{
    use SoftDeletes;

    protected $fillable = ['attribute_id', 'value', 'slug', 'swatch_type', 'swatch_value', 'sort_order'];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
