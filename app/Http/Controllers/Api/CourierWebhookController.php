<?php

namespace App\Http\Controllers\Api;

use App\Courier\CourierManager;
use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\CourierShipment;
use App\Models\CourierWebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Single inbound endpoint for every courier's status-update webhook,
 * disambiguated by the {courier} slug in the URL
 * (/api/webhooks/courier/steadfast, /api/webhooks/courier/pathao, ...).
 * Every call is logged to courier_webhook_logs before anything else, even
 * one that can't be matched to a shipment, so a courier's payload shape
 * can always be inspected later. Matching/parsing is delegated to the
 * courier's own driver (webhookIdentifier/parseWebhookEvent) so this
 * controller never branches per courier — see CourierDriverInterface.
 */
class CourierWebhookController extends Controller
{
    public function handle(Request $request, string $courier): JsonResponse
    {
        $courierModel = Courier::where('slug', $courier)->orWhere('driver_key', $courier)->first();
        $payload = $request->all();

        $log = CourierWebhookLog::create([
            'courier_id' => $courierModel?->id,
            'event_type' => $payload['status'] ?? $payload['order_status'] ?? $payload['event'] ?? null,
            'headers' => $request->headers->all(),
            'payload' => $payload,
            'status' => 'received',
            'ip_address' => $request->ip(),
        ]);

        if (! $courierModel) {
            $log->update(['status' => 'unmatched', 'error_message' => 'Unknown courier.']);

            return response()->json(['message' => 'Unknown courier.'], 404);
        }

        // Pathao pings the registered URL with this event once when the
        // webhook is being set up on their merchant dashboard, and expects
        // this exact status/header/secret combination back to mark the
        // integration verified — it isn't a real status update, so it must
        // be answered before the shared secret check below (Pathao doesn't
        // send our ?secret= on this verification call).
        if ($courierModel->driver_key === 'pathao' && ($payload['event'] ?? null) === 'webhook_integration') {
            $log->update(['status' => 'processed', 'event_type' => 'webhook_integration']);

            return response()->json([
                'message' => 'Webhook integrated',
            ], 202)->header(
                'X-Pathao-Merchant-Webhook-Integration-Secret',
                'f3992ecc-59da-4cbe-a049-a13da2018d51'
            );
        }

        // None of the integrated couriers sign their webhook payloads, so
        // this app generates its own secret (Courier > Settings) and
        // requires it back as ?secret= on the URL registered with the
        // courier — without this, anyone who discovers the endpoint could
        // forge status updates for any shipment.
        if ($courierModel->webhook_secret) {
            $provided = (string) $request->query('secret', '');

            if (! hash_equals($courierModel->webhook_secret, $provided)) {
                $log->update([
                    'status' => 'failed',
                    'signature_status' => 'invalid',
                    'error_message' => 'Missing or incorrect webhook secret.',
                ]);

                return response()->json(['message' => 'Unauthorized.'], 401);
            }

            $log->update(['signature_status' => 'valid']);
        }

        try {
            $driver = app(CourierManager::class)->driverFor($courierModel->driver_key);
            $identifier = $driver->webhookIdentifier($payload);
            $shipment = $identifier ? $this->matchShipment($identifier) : null;
            $event = $shipment ? $driver->parseWebhookEvent($payload) : null;
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

            return response()->json(['message' => 'Webhook processing failed.'], 500);
        }

        if (! $shipment) {
            $log->update(['status' => 'unmatched', 'error_message' => 'No matching shipment found.']);

            return response()->json(['message' => 'Shipment not found.'], 404);
        }

        $log->update(['courier_shipment_id' => $shipment->id, 'status' => 'processed']);

        if (! $event) {
            return response()->json(['message' => 'ok']);
        }

        $shipment->trackingEvents()->create([
            'status' => $event->status->value,
            'raw_status' => $event->rawStatus,
            'message' => $event->message,
            'location' => $event->location,
            'event_at' => $event->eventAt ?? now(),
            'raw_data' => $event->rawData,
        ]);

        $shipment->update([
            'previous_status' => $shipment->status,
            'status' => $event->status->value,
        ]);

        $shipment->order->forceFill([
            'courier_status' => $event->status,
            'courier_status_updated_at' => now(),
        ])->save();

        return response()->json(['message' => 'ok']);
    }

    /**
     * @param array{by: 'tracking_number'|'order_id', value: string} $identifier
     */
    protected function matchShipment(array $identifier): ?CourierShipment
    {
        return match ($identifier['by']) {
            'tracking_number' => CourierShipment::where('tracking_number', $identifier['value'])->first(),
            'order_id' => CourierShipment::whereHas('order', fn ($q) => $q->where('id', $identifier['value']))->first(),
        };
    }
}
