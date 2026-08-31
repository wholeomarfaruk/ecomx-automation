<?php

namespace App\Livewire\EcomxFashion;

use App\Enums\Sales\OrderStatus;
use App\Models\Order;
use App\Models\SmsGatewayConfig;
use App\Sms\Facades\Sms;
use App\Support\PhoneNumber;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('ecomx-fashion.layouts.ecomx_fashion')]
class Track extends Component
{
    public string $orderId = '';
    public string $phone = '';
    public bool $tracked = false;

    /** Set after a successful guest lookup or when a logged-in customer opens one of their own orders. */
    public ?int $trackedOrderId = null;

    public string $trackError = '';

    // Confirm/cancel modal state
    public bool $confirmModal = false;
    public bool $otpSent = false;
    public string $otpCode = '';
    public string $otpError = '';
    public string $companyPhone = '';

    /** Guest lookup: order # + phone must both match, so a stranger can't view someone else's order by guessing the ID. */
    public function track(): void
    {
        $this->trackError = '';

        $numericId = preg_replace('/\D/', '', $this->orderId);

        $nationalPhone = PhoneNumber::national($this->phone);

        $order = $numericId !== ''
            ? Order::where('id', $numericId)
                ->whereHas('customer', fn ($q) => $q->where('phone', $nationalPhone))
                ->first()
            : null;

        if (! $order) {
            $this->trackError = 'No order found with that ID and phone number.';
            $this->tracked = false;

            return;
        }

        $this->trackedOrderId = $order->id;
        $this->tracked = true;
    }

    public function viewOrder(int $orderId): void
    {
        $customer = auth()->user()?->customer;

        // Only ever open an order that actually belongs to the logged-in customer.
        if (! $customer || ! Order::where('id', $orderId)->where('customer_id', $customer->id)->exists()) {
            return;
        }

        $this->trackedOrderId = $orderId;
        $this->tracked = true;
        $this->trackError = '';
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
        $order = $this->trackedOrder();

        if (! $order || ! $this->ownsTrackedOrder($order)) {
            return;
        }

        $this->confirmModal = true;
        $this->otpSent = false;
        $this->otpCode = '';
        $this->otpError = '';
        $this->companyPhone = config('ecomx-fashion.phone', '');
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

        $order = $this->trackedOrder();

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

        $order = $this->trackedOrder();

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
        $order = $this->trackedOrder();

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

    public function backToList(): void
    {
        $this->tracked = false;
        $this->trackedOrderId = null;
    }

    /** Ordered progress steps for the tracked order, derived from its real status. */
    public function steps(): array
    {
        $order = $this->trackedOrder();

        if (! $order) {
            return [];
        }

        $status = $order->status;

        if (in_array($status, [OrderStatus::CANCELLED, OrderStatus::RETURNED, OrderStatus::PARTIALLY_RETURNED, OrderStatus::REFUNDED], true)) {
            return [
                ['label' => 'Order placed', 'sub' => $order->placed_at?->format('d M, Y') ?? '—', 'done' => true],
                ['label' => $status->label(), 'sub' => $order->cancelled_at?->format('d M, Y') ?? 'Order will not be delivered', 'done' => true],
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
            'done' => $i <= $currentIndex,
        ])->all();
    }

    public function trackedOrder(): ?Order
    {
        if (! $this->trackedOrderId) {
            return null;
        }

        return Order::with(['items.product', 'shippingAddress'])->find($this->trackedOrderId);
    }

    public function render()
    {
        $customer = auth()->user()?->customer;

        $myOrders = $customer
            ? Order::where('customer_id', $customer->id)
                ->withCount('items')
                ->orderByDesc('id')
                ->get()
            : collect();

        return view('ecomx-fashion.livewire.track', [
            'myOrders' => $myOrders,
            'trackedOrder' => $this->trackedOrder(),
            'steps' => $this->steps(),
        ]);
    }
}
