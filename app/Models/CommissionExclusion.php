<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionExclusion extends Model
{
    protected $fillable = ['commission_scheme_id', 'order_id', 'order_item_id', 'source', 'rule', 'reason', 'included_back', 'user_id'];

    protected function casts(): array
    {
        return ['included_back' => 'boolean'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
