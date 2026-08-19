<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DeviceVisit extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'device_id', 'url', 'route_name', 'method', 'status_code', 'ip_address', 'referer',
        'visitable_type', 'visitable_id', 'content_type', 'content_slug', 'content_title',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function visitable(): MorphTo
    {
        return $this->morphTo();
    }
}
