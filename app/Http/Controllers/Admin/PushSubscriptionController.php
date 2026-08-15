<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        PushSubscription::updateOrCreate(
            ['user_id' => auth()->id(), 'endpoint' => $validated['endpoint']],
            [
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => 'aesgcm',
            ]
        );

        return response()->json(['success' => true]);
    }
}
