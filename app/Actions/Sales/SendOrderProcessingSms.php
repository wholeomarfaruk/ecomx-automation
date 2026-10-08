<?php

namespace App\Actions\Sales;

use App\Models\Order;
use App\Models\SmsTemplate;
use App\Sms\Facades\Sms;
use App\Support\PhoneNumber;

/**
 * Texts the customer when their order moves to Processing — fired from
 * Order::booted() after the status change commits, so a courier booking's
 * shipment (and its {tracking_url}) is already saved. Sent once per order
 * (processing_sms_sent_at); switch it off by deactivating the
 * "order_processing" SMS template. Never throws — a failed SMS must not
 * undo or block the status change.
 */
class SendOrderProcessingSms
{
    public const TEMPLATE_KEY = 'order_processing';

    public function handle(Order $order): void
    {
        try {
            $order->refresh()->load(['customer', 'shippingAddress', 'courierShipments.courier']);

            if ($order->processing_sms_sent_at) {
                return;
            }

            $template = SmsTemplate::where('key', self::TEMPLATE_KEY)->where('is_active', true)->first();
            $rawPhone = $order->shippingAddress?->phone ?: $order->customer?->phone;

            if (! $template || ! $rawPhone) {
                return;
            }

            $response = Sms::send(
                PhoneNumber::local(PhoneNumber::national($rawPhone)),
                $template->render($order->smsPlaceholders()),
                self::TEMPLATE_KEY,
            );

            // 'queued' counts too — only a refused send (SMS off, no gateway) is retried on a later Processing.
            if ($response->success) {
                $order->forceFill(['processing_sms_sent_at' => now()])->saveQuietly();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
