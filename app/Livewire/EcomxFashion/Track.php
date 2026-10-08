<?php

namespace App\Livewire\EcomxFashion;

use App\Enums\Sales\OrderStatus;
use App\Models\Order;
use App\Models\SmsGatewayConfig;
use App\Sms\Facades\Sms;
use App\Support\OrderTrackLookup;
use App\Support\PhoneNumber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Js;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('ecomx-fashion.layouts.ecomx_fashion')]
class Track extends Component
{
    public string $orderId = '';
    public string $phone = '';
    public bool $tracked = false;

    /**
     * Set after a successful guest lookup or when a logged-in customer opens
     * one of their own orders. Locked — otherwise the browser could swap in
     * any other order's ID and skip the phone check.
     */
    #[Locked]
    public ?int $trackedOrderId = null;

    public string $trackError = '';

    // Confirm/cancel modal state
    public bool $confirmModal = false;
    public bool $otpSent = false;
    public string $otpCode = '';
    public string $otpError = '';
    public string $companyPhone = '';

    /**
     * ?order=… from the order-received page's "Track order" button: opens
     * straight onto that order for the session that placed it, or for its
     * logged-in customer (viewOrder() checks that).
     * ?order_id=…&phone=… (a shared/refreshed track link) runs the same
     * guest lookup as the form — see OrderTrackLookup.
     */
    public function mount(): void
    {
        $query = request()->query();

        if (isset($query['order_id']) || isset($query['phone'])) {
            $this->orderId = (string) (OrderTrackLookup::orderId($query['order_id'] ?? null) ?? '');
            $this->phone = ($national = OrderTrackLookup::phone($query['phone'] ?? null)) ? PhoneNumber::local($national) : '';

            if (! auth()->check()) {
                $this->track();
            }

            return;
        }

        $orderId = OrderTrackLookup::orderId($query['order'] ?? null);

        if (! $orderId) {
            return;
        }

        if (in_array($orderId, session('placed_order_ids', []), true)) {
            $this->trackedOrderId = $orderId;
            $this->tracked = true;

            return;
        }

        $this->viewOrder($orderId);
    }

    /** Guest lookup: order # + phone must both match, so a stranger can't view someone else's order by guessing the ID. */
    public function track(): void
    {
        $this->trackError = '';

        if (OrderTrackLookup::tooManyAttempts()) {
            $this->trackError = 'Too many attempts. Please try again in a minute.';
            $this->tracked = false;

            return;
        }

        $order = OrderTrackLookup::find($this->orderId, $this->phone);

        if (! $order) {
            $this->trackError = 'No order found with that ID and phone number.';
            $this->tracked = false;
            $this->trackedOrderId = null;

            return;
        }

        $this->trackedOrderId = $order->id;
        $this->tracked = true;

        // Shareable/refreshable URL — built from the verified order, never from raw input.
        $url = route('ecomx-fashion.track', OrderTrackLookup::queryFor($order, OrderTrackLookup::phone($this->phone)));
        $this->js('history.replaceState(history.state, "", ' . Js::from($url) . ')');
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
        $this->companyPhone = \App\Support\ContactInfo::phone() ?? '';
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

        $currentIndex = array_search($status, $progression, true);
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

        return Order::with(['items.product', 'items.variant.media', 'shippingAddress', 'payments'])->find($this->trackedOrderId);
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
