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

        $data = $request->validate([
            'screen_resolution' => 'nullable|string|max:20',
            'screen_density'    => 'nullable|string|max:10',
            'timezone'          => 'nullable|string|max:100',
        ]);

        $device->fill(array_filter($data, fn ($v) => $v !== null))->save();

        return response()->json(['ok' => true]);
    }
}
