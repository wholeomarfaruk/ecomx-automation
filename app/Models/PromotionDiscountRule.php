<?php

namespace App\Models;

use App\Enums\Sales\DiscountRuleType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionDiscountRule extends Model
{
    protected $fillable = [
        'promotion_id', 'type', 'value',
        'buy_quantity', 'get_quantity', 'max_discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'type'                 => DiscountRuleType::class,
            'value'                => 'decimal:4',
            'max_discount_amount'  => 'decimal:2',
        ];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
