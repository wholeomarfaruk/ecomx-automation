<?php

namespace App\Enums\File;


enum Type: string
{
    case IMAGE = 'image';
    case VIDEO = 'video';
    case DOCUMENT = 'document';

    public static function fromExtension(string $ext): ?self
    {
        return match (strtolower($ext)) {
            'jpg','jpeg','png','gif','ico','tiff','webp' => self::IMAGE,
            'mp4','mov','avi','mkv' => self::VIDEO,
            'pdf','doc','docx','xls','xlsx','csv','txt','zip' => self::DOCUMENT,
            default => null,
        };
    }
}

