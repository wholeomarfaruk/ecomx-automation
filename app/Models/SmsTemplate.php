<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsTemplate extends Model
{
    protected $fillable = [
        'key',
        'label',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function render(array $data = []): string
    {
        $body = $this->body;

        foreach ($data as $placeholder => $value) {
            $body = str_replace('{' . $placeholder . '}', (string) $value, $body);
        }

        return $body;
    }
}
