<?php

namespace App\Models;

use App\Enums\Sales\OfferType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    protected $fillable = [
        'promotion_id', 'offer_type',
    ];

    protected function casts(): array
    {
        return [
            'offer_type' => OfferType::class,
        ];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
