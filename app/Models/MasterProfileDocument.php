<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterProfileDocument extends Model
{
    protected $fillable = ['master_profile_id', 'title', 'path', 'mime', 'size', 'expires_on', 'uploaded_by'];

    protected function casts(): array
    {
        return ['expires_on' => 'date'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(MasterProfile::class, 'master_profile_id');
    }

    /** "PDF · expires Jun 2027" */
    public function summary(): string
    {
        $kind = str_contains((string) $this->mime, 'pdf') ? 'PDF' : (str_starts_with((string) $this->mime, 'image/') ? 'Image' : 'File');

        return $kind . ($this->expires_on ? ' · expires ' . $this->expires_on->format('M Y') : '');
    }
}
