<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\FraudShield\FraudShield;
use App\Services\FraudShield\FraudShieldException;
use App\Services\FraudShield\FraudShieldSettings;
use Illuminate\Http\JsonResponse;

/**
 * One row's FraudShield badge for the Orders list. The list renders first
 * without any API call; its script then posts here row by row, so a slow
 * FraudShield never holds up the page or the Livewire component.
 */
class OrderFraudCheckController extends Controller
{
    public function __invoke(int $id, FraudShield $fraudShield, FraudShieldSettings $settings): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('superadmin') || $user?->can('order.view'), 403);

        if (! $settings->ready()) {
            return response()->json(['stop' => true, 'html' => '']);
        }

        $order = Order::with('customer', 'shippingAddress')->findOrFail($id);

        try {
            $check = $fraudShield->check(FraudShield::orderPhone($order));
        } catch (FraudShieldException $e) {
            // Key rejected / daily limit hit — every other row would fail the same way.
            $stop = str_contains($e->getMessage(), '(401)') || str_contains($e->getMessage(), '(429)');

            return response()->json([
                'stop' => $stop,
                'html' => view('livewire.admin.sales.partials.fraud-badge', [
                    'fc' => $fraudShield->stored(FraudShield::orderPhone($order)),
                    'orderId' => $order->id,
                    'error' => $e->getMessage(),
                ])->render(),
            ]);
        }

        return response()->json([
            'stop' => false,
            'html' => view('livewire.admin.sales.partials.fraud-badge', ['fc' => $check, 'orderId' => $order->id])->render(),
        ]);
    }
}
