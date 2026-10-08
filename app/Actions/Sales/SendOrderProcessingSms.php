<?php

namespace App\Actions\Sales;

use App\Jobs\SendNotificationChannelJob;
use App\Models\NotificationEvent;
use App\Models\Order;
use App\Models\Setting;
use App\Models\SmsTemplate;
use App\Sms\Facades\Sms;
use App\Support\PhoneNumber;

/**
 * The "Order Processing" notification event (Admin > Notifications >
 * Events, key order_processing) — fired from Order::booted() once the
 * order's move to Processing commits, so a courier booking's shipment (and
 * its {tracking_url}) is already saved.
 *
 * SMS goes straight to the order's phone with the event's SMS template, so
 * guest/admin-created orders with no login account still get it; the other
 * channels go through the normal notification jobs and need the customer's
 * user account. Sent once per order (processing_sms_sent_at). Never throws —
 * a failed notification must not undo or block the status change.
 */
class SendOrderProcessingSms
{
    public const EVENT_KEY = 'order_processing';

    public function handle(Order $order): void
    {
        try {
            $order->refresh()->load(['customer.user', 'shippingAddress', 'courierShipments.courier']);

            $event = NotificationEvent::where('event_key', self::EVENT_KEY)->first();

            if ($order->processing_sms_sent_at || ! $event) {
                return;
            }

            $data = $order->smsPlaceholders();
            $smsSent = $event->channel_sms && $this->channelEnabled('sms') ? $this->sendSms($order, $event, $data) : null;

            $user = $order->customer?->user;
            $otherChannels = array_diff($event->enabledChannels(), ['sms']);

            if ($user) {
                foreach ($otherChannels as $channel) {
                    if ($this->channelEnabled($channel)) {
                        SendNotificationChannelJob::dispatch(self::EVENT_KEY, $channel, $user, $data);
                    }
                }
            }

            // Marked once something went out; a refused SMS (SMS off, no gateway)
            // with nothing else sent is retried on a later move to Processing.
            if ($smsSent === true || ($smsSent === null && $user && $otherChannels)) {
                $order->forceFill(['processing_sms_sent_at' => now()])->saveQuietly();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** @return bool whether the gateway accepted (or queued) it */
    protected function sendSms(Order $order, NotificationEvent $event, array $data): bool
    {
        $template = SmsTemplate::where('key', $event->sms_template_key ?: self::EVENT_KEY)->where('is_active', true)->first();
        $rawPhone = $order->shippingAddress?->phone ?: $order->customer?->phone;

        if (! $template || ! $rawPhone) {
            return false;
        }

        return Sms::send(
            PhoneNumber::local(PhoneNumber::national($rawPhone)),
            $template->render($data),
            self::EVENT_KEY,
        )->success;
    }

    /** Same global on/off as NotificationManager (Admin > Notifications > Channels). */
    protected function channelEnabled(string $channel): bool
    {
        return (bool) Setting::get("channel_{$channel}_enabled", true, 'notifications');
    }
}
