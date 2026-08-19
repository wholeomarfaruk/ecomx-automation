<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Block extends Model
{
    public const SCOPE_FULL_SITE     = 'full_site';
    public const SCOPE_ORDERS        = 'orders';
    public const SCOPE_CHECKOUT      = 'checkout';
    public const SCOPE_ACCOUNT_PANEL = 'account_panel';

    public const SCOPES = [
        self::SCOPE_FULL_SITE,
        self::SCOPE_ORDERS,
        self::SCOPE_CHECKOUT,
        self::SCOPE_ACCOUNT_PANEL,
    ];

    protected $fillable = [
        'blockable_type', 'blockable_id', 'ip_address',
        'scope', 'reason', 'blocked_by', 'expires_at', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active'  => 'boolean',
        ];
    }

    public function blockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    /**
     * Active blocks that haven't expired — the only ones enforcement should
     * ever act on. Expired/inactive rows stay for audit history.
     */
    public function scopeApplicable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function scopeForScope(Builder $query, string $scope): Builder
    {
        return $query->where('scope', $scope);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function scopeLabel(): string
    {
        return match ($this->scope) {
            self::SCOPE_FULL_SITE     => 'Full Site',
            self::SCOPE_ORDERS        => 'Orders',
            self::SCOPE_CHECKOUT      => 'Checkout',
            self::SCOPE_ACCOUNT_PANEL => 'Account Panel',
            default                   => ucfirst($this->scope),
        };
    }
}
