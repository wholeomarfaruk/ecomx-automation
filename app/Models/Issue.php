<?php

namespace App\Models;

use App\Enums\Issues\IssuePriority;
use App\Enums\Issues\IssueStatus;
use App\Enums\Issues\IssueType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Something that went wrong and needs following up. `subject` is what it's
 * about (Order, Product, Customer, ...), `party` who's answerable for it
 * (Courier, Supplier, ...) — any model using HasIssues can carry them, and
 * the IssuePanel component shows them on that model's page.
 */
class Issue extends Model
{
    protected $fillable = [
        'type', 'status', 'priority', 'title', 'description',
        'subject_type', 'subject_id', 'party_type', 'party_id',
        'claim_amount', 'recovered_amount', 'recovery_journal_entry_id',
        'resolution', 'meta',
        'reported_by', 'assigned_to', 'resolved_by', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'type'             => IssueType::class,
            'status'           => IssueStatus::class,
            'priority'         => IssuePriority::class,
            'claim_amount'     => 'decimal:2',
            'recovered_amount' => 'decimal:2',
            'meta'             => 'array',
            'resolved_at'      => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function party(): MorphTo
    {
        return $this->morphTo();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function recoveryEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'recovery_journal_entry_id');
    }

    /** @param  Builder<Issue>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', IssueStatus::activeValues());
    }

    /** Claimed but not yet recovered — what someone still owes for it. */
    public function outstandingClaim(): float
    {
        return max(0.0, (float) $this->claim_amount - (float) $this->recovered_amount);
    }

    /** "Order #268", "Pathao", ... — a short name for whatever a morph points at. */
    public static function describe(?Model $model): ?string
    {
        return match (true) {
            $model === null           => null,
            $model instanceof Order   => "Order #{$model->id}",
            isset($model->name)       => (string) $model->name,
            isset($model->title)      => (string) $model->title,
            default                   => class_basename($model) . " #{$model->getKey()}",
        };
    }

    /** Admin page of whatever a morph points at, when there is one. */
    public static function urlFor(?Model $model): ?string
    {
        return match (true) {
            $model instanceof Order => route('admin.sales.orders.show', $model->id),
            default                 => null,
        };
    }
}
