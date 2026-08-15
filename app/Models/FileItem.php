<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileItem extends Model
{
    use SoftDeletes;

    protected $table = 'file_items';

    protected $fillable = [
        'file_id',
        'type',
        'size',
        'path',
    ];
}
