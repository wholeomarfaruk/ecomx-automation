<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A role a MasterProfile plays, with that role's settings:
 *   customer: credit_limit, terms_days, price_group, allow_kisti
 *   supplier: category, terms (cash|7|15|30|kisti), payout_account
 *   lender:   lender_type (bank|business|personal)
 *   owner:    share_percent
 *   employee: designation, department, joined_on, salary, pay_via, pay_account
 */
class MasterProfileRole extends Model
{
    protected $fillable = ['master_profile_id', 'role', 'settings'];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class, 'master_profile_id');
    }

    /** Idempotent: used by the Customer / Supplier model hooks. */
    public static function ensure(?int $profileId, string $role): void
    {
        if (! $profileId) {
            return;
        }

        static::query()->insertOrIgnore([
            'master_profile_id' => $profileId,
            'role'              => $role,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }
}
