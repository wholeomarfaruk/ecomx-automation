<?php

namespace App\Models;

use App\Mail\TemplatedMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key',
        'label',
        'subject',
        'body',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function renderSubject(array $data = []): string
    {
        return $this->replace($this->subject, $data);
    }

    public function renderBody(array $data = []): string
    {
        return $this->replace($this->body, $data);
    }

    /**
     * A prebuilt/custom template can be a complete standalone HTML document
     * (<!DOCTYPE html>...<html>...). Those must never be nested inside the
     * mail::message wrapper's own <html>/<body> — that produces invalid,
     * broken markup. Plain-text/snippet bodies still get the wrapper so
     * they render with Laravel's styled header/footer/button chrome.
     */
    public function isFullHtmlDocument(): bool
    {
        return (bool) preg_match('/^\s*(<!DOCTYPE\s+html|<html[\s>])/i', $this->body);
    }

    protected function replace(string $text, array $data): string
    {
        foreach ($data as $placeholder => $value) {
            $text = str_replace('{' . $placeholder . '}', (string) $value, $text);
        }

        return $text;
    }

    public static function send(string $key, string $to, array $data = []): bool
    {
        $template = static::where('key', $key)->where('is_active', true)->first();

        if (! $template) {
            return false;
        }

        Mail::to($to)->send(new TemplatedMail($template, $data));

        return true;
    }
}
