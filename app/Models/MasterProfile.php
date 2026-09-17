<?php

namespace App\Models;

use App\Enums\Profiles\MasterProfileType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'type',
        'display_name',
        'legal_name',
        'first_name',
        'last_name',
        'country_code',
        'phone',
        'email',
        'website',
        'avatar',
        'date_of_birth',
        'gender',
        'national_id',
        'tax_number',
        'vat_number',
        'registration_number',
        'status',
        'notes',
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
        ];
    }
}
