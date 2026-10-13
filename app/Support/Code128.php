<?php

namespace App\Support;

/**
 * Code 128 (set B) barcode as inline SVG — what the batch labels print, so
 * any USB/handheld scanner reads a batch_no back as plain text. Set B covers
 * printable ASCII (space to ~); anything else is printed as '?'.
 */
class Code128
{
    /** Bar/space widths for symbol values 0–106 (106 = stop). */
    protected const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    protected const START_B = 104;

    protected const STOP = 106;

    /** Text the barcode actually encodes (non set-B characters become '?'). */
    public static function normalize(string $text): string
    {
        return preg_replace('/[^\x20-\x7E]/', '?', $text) ?? '';
    }

    /** Module widths, bar first (quiet zones not included). */
    public static function modules(string $text): array
    {
        $values = array_map(fn ($c) => ord($c) - 32, str_split(static::normalize($text)));

        $checksum = static::START_B;
        foreach ($values as $i => $v) {
            $checksum += ($i + 1) * $v;
        }

        $symbols = [static::START_B, ...$values, $checksum % 103, static::STOP];

        return array_map('intval', str_split(implode('', array_map(fn ($s) => static::PATTERNS[$s], $symbols))));
    }

    /** Scalable SVG — width/height come from the CSS box it's placed in. */
    public static function svg(string $text, int $quiet = 10): string
    {
        $x = $quiet;
        $rects = '';

        foreach (static::modules($text) as $i => $w) {
            if ($i % 2 === 0) {
                $rects .= '<rect x="' . $x . '" y="0" width="' . $w . '" height="1"/>';
            }
            $x += $w;
        }

        $width = $x + $quiet;

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' 1" preserveAspectRatio="none" shape-rendering="crispEdges" fill="#000" role="img" aria-label="' . e(static::normalize($text)) . '">' . $rects . '</svg>';
    }
}
