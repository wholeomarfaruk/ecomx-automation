<?php

namespace App\Livewire\Admin\Sales\Concerns;

use App\Marketing\Services\MarketingEventService;
use App\Models\Order;

/**
 * "Send Purchase to Meta" on the order page and the orders list — sends the
 * order's Purchase to Meta Conversions API with action_source 'chat' (see
 * MarketingEventService::sendPurchaseFromAdmin). The result shows on the
 * order's timeline.
 */
trait SendsPurchaseToMeta
{
    public function sendPurchaseToMeta(int $orderId): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('superadmin') || $user?->can('order.edit'), 403);

        $order = Order::findOrFail($orderId);

        try {
            $status = app(MarketingEventService::class)->sendPurchaseFromAdmin($order);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', ['type' => 'error', 'message' => "Order #{$orderId}: Purchase event could not be sent — {$e->getMessage()}"]);

            return;
        }

        [$type, $message] = match ($status) {
            'queued'         => ['success', "Order #{$orderId}: Purchase event queued for Meta — the result will show on the order timeline"],
            'already_sent'   => ['info', "Order #{$orderId}: Purchase event was already sent to Meta"],
            'already_queued' => ['info', "Order #{$orderId}: Purchase event is already queued for Meta"],
            'not_configured' => ['error', 'No server-side marketing destination is configured'],
            default          => ['error', "Order #{$orderId}: Meta rejected the Purchase event — see the order timeline"],
        };

        $this->dispatch('toast', ['type' => $type, 'message' => $message]);
    }
}
