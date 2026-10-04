<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives the client-side signals DeviceTracker can't see from headers
 * alone (screen size/density, timezone) — fired once per browser session
 * from the layout, off the critical render path.
 */
class DeviceSignalController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        if (! $device instanceof Device) {
            return response()->json(['ok' => false], 204);
        }

        // devicePixelRatio is a raw float — zoomed browsers and in-app
        // webviews (e.g. Facebook on Android) report values like
        // 1.100000023841858, which would fail max:10 and drop the whole
        // signal. Round it here too, for pages still running the old script.
        if (is_numeric($request->input('screen_density'))) {
            $request->merge(['screen_density' => (string) round((float) $request->input('screen_density'), 2)]);
        }

        $data = $request->validate([
            'screen_resolution' => 'nullable|string|max:20',
            'screen_density'    => 'nullable|string|max:10',
            'timezone'          => 'nullable|string|max:100',
        ]);

        $device->fill(array_filter($data, fn ($v) => $v !== null))->save();

        return response()->json(['ok' => true]);
    }
}
