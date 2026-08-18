<?php

namespace App\Models;

use App\Enums\Sales\ConditionOperator;
use App\Enums\Sales\ConditionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionCondition extends Model
{
    protected $fillable = [
        'promotion_id', 'type', 'operator', 'value',
    ];

    protected function casts(): array
    {
        return [
            'type'     => ConditionType::class,
            'operator' => ConditionOperator::class,
        ];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * Decoded value: an array for in/not_in operators, a scalar string otherwise.
     */
    public function getValueDecodedAttribute(): array|string
    {
        if ($this->operator->isMultiValue()) {
            return json_decode($this->value, true) ?? [];
        }

        return $this->value;
    }

    public function setValueDecodedAttribute(array|string $value): void
    {
        $this->attributes['value'] = is_array($value) ? json_encode($value) : (string) $value;
    }
}
