<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UpdateLog extends Model
{
    protected $fillable = [
        'from_version',
        'to_version',
        'status',
        'output',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
