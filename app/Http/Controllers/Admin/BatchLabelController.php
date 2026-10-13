<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryBatch;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Printable barcode labels for inventory batches — one label per piece,
 * each carrying its batch_no as a Code 128 barcode, so a unit scanned at
 * packing tells the Pack modal exactly which batch it came from.
 *
 * ?q[batch_id]=copies (defaults to the batch's current quantity),
 * &size=50x25|38x25|a4, &price=1 to print the selling price.
 */
class BatchLabelController extends Controller
{
    public const SIZES = [
        '50x25' => '50 × 25 mm (thermal roll)',
        '38x25' => '38 × 25 mm (thermal roll)',
        'a4'    => 'A4 sheet (4 × 11)',
    ];

    /** Most labels one page will lay out. */
    protected const MAX_LABELS = 2000;

    public function __invoke(Request $request): View
    {
        $requested = collect((array) $request->query('q', []))
            ->mapWithKeys(fn ($qty, $id) => [(int) $id => $qty === '' || $qty === null ? null : max(0, (int) $qty)])
            ->filter(fn ($qty, $id) => $id > 0);

        abort_if($requested->isEmpty(), 404);

        $batches = InventoryBatch::with(['product', 'variant'])
            ->whereIn('id', $requested->keys())
            ->get()
            ->sortBy(fn ($b) => $requested->keys()->search($b->id))
            ->values();

        abort_if($batches->isEmpty(), 404);

        $copies = $batches->mapWithKeys(fn ($b) => [
            $b->id => $requested[$b->id] ?? max(0, (int) ceil((float) $b->quantity)),
        ]);

        $size = $request->query('size');
        $size = is_string($size) && array_key_exists($size, self::SIZES) ? $size : '50x25';

        return view('admin.batch-labels', [
            'batches'   => $batches,
            'copies'    => $copies,
            'total'     => min($copies->sum(), self::MAX_LABELS),
            'max'       => self::MAX_LABELS,
            'size'      => $size,
            'sizes'     => self::SIZES,
            'showPrice' => $request->boolean('price'),
            'currency'  => Setting::get('currency_symbol', '৳', 'localization'),
            'siteName'  => Setting::get('site_name', config('app.name'), 'general'),
        ]);
    }
}
