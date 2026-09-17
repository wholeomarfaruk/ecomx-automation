<?php

namespace App\Livewire\EcomxAnyniche;

use App\Enums\Sales\OrderStatus;
use App\Models\Order;
use App\Models\SmsGatewayConfig;
use App\Sms\Facades\Sms;
use App\Support\PhoneNumber;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Order tracking detail page. Visual design ported from
 * juwel-trade-corporation's storefront.track-order-details (jtc-track,
 * jtc-track-summary, jtc-track-timeline, jtc-order-card classes). Split out
 * of the old single-page Track component so a tracked order has its own
 * shareable /track/{order} URL. Access is guarded either by owning the
 * order as a logged-in customer, or — for a guest — by having verified it
 * via {@see Track::track()}, which stamps a session flag for that order id
 * (mirrors the reference's track_order_verified_ session key).
 */
#[Layout('ecomx-anyniche.layouts.ecomx_anyniche')]
class TrackDetails extends Component
{
    public int $orderId;

    // Confirm/cancel modal state
    public bool $confirmModal = false;
    public bool $otpSent = false;
    public string $otpCode = '';
    public string $otpError = '';
    public string $companyPhone = '';

    public function mount(int $order): void
    {
        $this->orderId = $order;

        if (! $this->canView($this->order())) {
            abort(403);
        }
    }

    private function canView(?Order $order): bool
    {
        if (! $order) {
            return false;
        }

        if ($this->ownsTrackedOrder($order)) {
            return true;
        }

        return (bool) session('track_order_verified_' . $order->id, false);
    }

    /**
     * A real, usable SMS gateway needs an active row in sms_gateway_configs
     * with credentials actually filled in — SmsGatewayConfig::active()
     * alone isn't enough, since the row can exist but be empty. Gates the
     * "Send OTP" button so it never fires a doomed send; the modal shows a
     * "call us" fallback instead when this is false.
     */
    public function smsGatewayReady(): bool
    {
        $active = SmsGatewayConfig::active();

        return $active !== null && ! empty($active->credentials);
    }

    public function openConfirmModal(): void
    {
        $order = $this->order();

        if (! $order || ! $this->ownsTrackedOrder($order)) {
            return;
        }

        $this->confirmModal = true;
        $this->otpSent = false;
        $this->otpCode = '';
        $this->otpError = '';
        $this->companyPhone = config('ecomx-anyniche.phone', '');
    }

    public function closeConfirmModal(): void
    {
        $this->confirmModal = false;
        $this->otpSent = false;
        $this->otpCode = '';
        $this->otpError = '';
    }

    /** OTP always goes to the account phone — the same number the order was placed with (auth()->user()->phone, set at checkout). */
    public function sendOtp(): void
    {
        $this->otpError = '';

        $order = $this->order();

        if (! $order || ! $this->ownsTrackedOrder($order)) {
            return;
        }

        if (! $this->smsGatewayReady()) {
            $this->otpError = 'gateway_unavailable';

            return;
        }

        $user = auth()->user();
        $code = (string) random_int(100000, 999999);

        $user->forceFill([
            'otp' => $code,
            'otp_expires_at' => now()->addMinutes(5),
        ])->save();

        $response = Sms::sendOTP(PhoneNumber::local($user->phone), $code);

        if (! $response->success) {
            $this->otpError = 'gateway_unavailable';

            return;
        }

        $this->otpSent = true;
    }

    public function verifyOtpAndConfirm(): void
    {
        $this->otpError = '';

        $order = $this->order();

        if (! $order || ! $this->ownsTrackedOrder($order)) {
            return;
        }

        $user = auth()->user();

        if (! $user->otp || $user->otp !== trim($this->otpCode)) {
            $this->otpError = 'The code you entered is incorrect.';

            return;
        }

        if (! $user->otp_expires_at || Carbon::parse($user->otp_expires_at)->isPast()) {
            $this->otpError = 'This code has expired — please request a new one.';

            return;
        }

        if ($order->status !== OrderStatus::PENDING) {
            $this->otpError = 'This order can no longer be confirmed.';

            return;
        }

        $user->forceFill(['otp' => null, 'otp_expires_at' => null])->save();

        $order->update([
            'status' => OrderStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);

        $this->closeConfirmModal();
    }

    public function cancelOrder(): void
    {
        $order = $this->order();

        if (! $order || ! $this->ownsTrackedOrder($order)) {
            return;
        }

        if ($order->status !== OrderStatus::PENDING) {
            return;
        }

        $order->update([
            'status' => OrderStatus::CANCELLED,
            'cancelled_at' => now(),
        ]);

        $this->closeConfirmModal();
    }

    private function ownsTrackedOrder(Order $order): bool
    {
        $customer = auth()->user()?->customer;

        return $customer && $order->customer_id === $customer->id;
    }

    /** Ordered progress steps for the tracked order, derived from its real status. */
    public function steps(): array
    {
        $order = $this->order();

        if (! $order) {
            return [];
        }

        $status = $order->status;

        if (in_array($status, [OrderStatus::CANCELLED, OrderStatus::RETURNED, OrderStatus::PARTIALLY_RETURNED, OrderStatus::REFUNDED], true)) {
            return [
                ['label' => 'Order placed', 'sub' => $order->placed_at?->format('d M, Y') ?? '—', 'done' => true, 'current' => false],
                ['label' => $status->label(), 'sub' => $order->cancelled_at?->format('d M, Y') ?? 'Order will not be delivered', 'done' => false, 'current' => true],
            ];
        }

        $progression = [
            OrderStatus::PENDING,
            OrderStatus::CONFIRMED,
            OrderStatus::PROCESSING,
            OrderStatus::SHIPPED,
            OrderStatus::DELIVERED,
        ];

        // partially_delivered counts as having reached "Shipped" but not fully "Delivered".
        $effectiveStatus = $status === OrderStatus::PARTIALLY_DELIVERED ? OrderStatus::SHIPPED : $status;
        $currentIndex = array_search($effectiveStatus, $progression, true);
        $currentIndex = $currentIndex === false ? 0 : $currentIndex;

        $subLabels = [
            OrderStatus::PENDING->value => 'Awaiting confirmation',
            OrderStatus::CONFIRMED->value => 'Confirmed',
            OrderStatus::PROCESSING->value => 'In our atelier',
            OrderStatus::SHIPPED->value => 'With courier',
            OrderStatus::DELIVERED->value => 'Delivered',
        ];

        return collect($progression)->map(fn (OrderStatus $step, int $i) => [
            'label' => $step->label(),
            'sub' => $subLabels[$step->value],
            'done' => $i < $currentIndex,
            'current' => $i === $currentIndex,
        ])->all();
    }

    public function order(): ?Order
    {
        return Order::with(['items.product', 'shippingAddress'])->find($this->orderId);
    }

    public function render()
    {
        $order = $this->order();

        return view('ecomx-anyniche.livewire.track-details', [
            'order' => $order,
            'steps' => $this->steps(),
        ]);
    }
}
