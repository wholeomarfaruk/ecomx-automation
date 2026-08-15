<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Panel extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'route_name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'panel_user');
    }
}
