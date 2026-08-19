<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\User\Status;
use App\Models\File;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasRoles;
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;
    use TwoFactorAuthenticatable;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_id',
        'phone',
        'country_code',
        'address',
        'bio',
        'gender',
        'status',
        'cover_photo_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'otp',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'phone_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'status' => Status::class,
        ];
    }
    public function avatar()
    {
        return $this->belongsTo(File::class, 'avatar_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class);
    }

    public function blocks()
    {
        return $this->morphMany(Block::class, 'blockable');
    }

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

    public function coverPhoto()
    {
        return $this->belongsTo(File::class, 'cover_photo_id');
    }

    // User has many panels
    public function panels()
    {
        return $this->belongsToMany(Panel::class);
    }

    public function hasPanel(string $panelSlug): bool
    {
        if ($this->hasRole('superadmin')) {
            return true;
        }

        return $this->panels()->where('slug', $panelSlug)->exists();
    }
    //roleName
    public function roleName(): ?string
    {
        return $this->getRoleNames()->first();
    }

}
