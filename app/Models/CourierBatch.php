<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A Bulk Courier sheet — see App\Livewire\Admin\Sales\BulkCourierEntry. */
class CourierBatch extends Model
{
    public const DRAFT = 'draft';
    public const COMPLETED = 'completed';

    protected $fillable = ['status', 'created_by', 'completed_at'];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CourierBatchItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** The admin's open sheet, created on first use. */
    public static function draftFor(int $userId): self
    {
        return static::firstOrCreate(['status' => self::DRAFT, 'created_by' => $userId]);
    }
}
