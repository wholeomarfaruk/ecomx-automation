<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionScheme extends Model
{
    public const RULES = [
        'clearance' => ['Clearance / Sale tag-এর item', 'Product-এর category-তে "Clearance" / "Sale" থাকলে', null],
        'discount'  => ['Invoice discount %s%%-এর বেশি', 'বড় discount দেওয়া invoice পুরোটা বাদ', 15],
        'cost'      => ['Cost + %s%%-এর নিচে বিক্রি', 'Line-এর দাম avg cost-এর খুব কাছে হলে', 5],
        'manual'    => ['হাতে বাদ দেওয়া invoice', 'Special deal — কারণ লিখে বাদ', null],
        'sample'    => ['Free / sample item', 'দাম ৳0 বা gift item', null],
    ];

    protected $fillable = [
        'name', 'type', 'basis', 'period', 'starts_on', 'ends_on', 'who', 'who_value', 'tier_mode', 'rules',
        'paid_only', 'hold_days', 'returns_reverse', 'settlement_method', 'accounting', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_on'       => 'date',
            'ends_on'         => 'date',
            'who_value'       => 'array',
            'rules'           => 'array',
            'paid_only'       => 'boolean',
            'returns_reverse' => 'boolean',
        ];
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(CommissionTier::class)->orderBy('from_amount');
    }

    public function exclusions(): HasMany
    {
        return $this->hasMany(CommissionExclusion::class);
    }

    public function accruals(): HasMany
    {
        return $this->hasMany(CommissionAccrual::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(CommissionSettlement::class);
    }

    /** Rule on? (+ its value) — defaults: everything on. */
    public function rule(string $key): array
    {
        $r = $this->rules[$key] ?? [];

        return ['on' => (bool) ($r['on'] ?? true), 'value' => (float) ($r['value'] ?? self::RULES[$key][2] ?? 0)];
    }
}
