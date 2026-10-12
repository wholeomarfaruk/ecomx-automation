<?php

namespace App\Models;

use App\Enums\Profiles\MasterProfileType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterProfile extends Model
{
    use SoftDeletes;

    public const ROLES = ['customer', 'supplier', 'lender', 'owner', 'employee'];

    protected $fillable = [
        'uuid',
        'code',
        'type',
        'display_name',
        'legal_name',
        'first_name',
        'last_name',
        'contact_person',
        'country_code',
        'phone',
        'alt_phone',
        'email',
        'website',
        'state_id',
        'city_id',
        'ps_id',
        'address',
        'avatar',
        'date_of_birth',
        'gender',
        'national_id',
        'tax_number',
        'vat_number',
        'registration_number',
        'trade_license',
        'status',
        'notes',
        'tags',
        'meta',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => MasterProfileType::class,
            'date_of_birth' => 'date',
            'meta' => 'array',
            'tags' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // P-0001 style code, derived from the id so it never collides.
        static::created(function (MasterProfile $profile) {
            if (! $profile->code) {
                $profile->forceFill(['code' => static::codeFor($profile->id)])->saveQuietly();
            }
        });
    }

    public static function codeFor(int $id): string
    {
        return 'P-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(MasterProfileRole::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function customer(): HasOne
    {
        return $this->hasOne(Customer::class)->oldestOfMany();
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function supplier(): HasOne
    {
        return $this->hasOne(Supplier::class)->oldestOfMany();
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MasterProfileDocument::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(MasterProfileActivity::class);
    }

    public function installmentPlans(): HasMany
    {
        return $this->hasMany(InstallmentPlan::class);
    }

    public function advances(): HasMany
    {
        return $this->hasMany(EmployeeAdvance::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function policeStation(): BelongsTo
    {
        return $this->belongsTo(PoliceStation::class, 'ps_id');
    }

    public function scopeWithRole(Builder $query, string $role): Builder
    {
        return $query->whereHas('roles', fn ($q) => $q->where('role', $role));
    }

    public function isBusiness(): bool
    {
        return $this->type === MasterProfileType::ORGANIZATION;
    }

    public function typeLabel(): string
    {
        return $this->isBusiness() ? 'Business' : 'Individual';
    }

    public function initials(): string
    {
        return static::initialsOf($this->display_name);
    }

    public static function initialsOf(?string $name): string
    {
        $words = array_values(array_filter(preg_split('/\s+/u', trim((string) $name)) ?: []));

        // One word ("superadmin") → first two letters ("SU"), like the dashboard avatar.
        return mb_strtoupper(count($words) === 1
            ? mb_substr($words[0], 0, 2)
            : collect($words)->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode(''));
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('role', $role);
    }

    public function roleSettings(string $role): array
    {
        return $this->roles->firstWhere('role', $role)?->settings ?? [];
    }

    /** Adds a role (no-op when it's already there) and logs it on the timeline. */
    public function addRole(string $role, array $settings = []): MasterProfileRole
    {
        $existing = $this->roles()->where('role', $role)->first();

        if ($existing) {
            if ($settings) {
                $existing->update(['settings' => array_merge($existing->settings ?? [], $settings)]);
            }

            return $existing;
        }

        $row = $this->roles()->create(['role' => $role, 'settings' => $settings ?: null]);
        $this->logActivity('role', 'Role added: ' . ucfirst($role));
        $this->unsetRelation('roles');

        return $row;
    }

    public function logActivity(string $type, string $text, array $meta = []): void
    {
        $this->activities()->create([
            'user_id'    => auth()->id(),
            'type'       => $type,
            'text'       => mb_substr($text, 0, 500),
            'meta'       => $meta ?: null,
            'created_at' => now(),
        ]);
    }

    /** "Dhaka · Mirpur" — district and thana, whichever are set. */
    public function area(): string
    {
        $parts = array_filter([$this->city?->name, $this->policeStation?->name]);

        return $parts ? implode(' · ', $parts) : '—';
    }
}
