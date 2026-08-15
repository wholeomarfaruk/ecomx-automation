<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductCategoryPivot extends Pivot
{
    protected $table = 'product_category_pivot';

    protected $fillable = ['product_id', 'category_id', 'sort_order'];
}
