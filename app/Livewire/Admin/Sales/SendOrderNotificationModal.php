<?php

namespace App\Livewire\Admin\Sales;

use App\Models\Order;
use App\Models\SmsTemplate;
use App\Sms\Facades\Sms;
use App\Support\PhoneNumber;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "Send Notification" modal, shared by the Orders list and the Order page
 * (same pattern as CourierBookingModal). Opened with the browser event
 * `open-order-notification` ({ orderId }). The admin picks any active SMS
 * template (or a custom message); the body is pre-rendered with the
 * order's placeholders (Order::smsPlaceholders()) and stays editable
 * before sending.
 */
class SendOrderNotificationModal extends Component
{
    public const CUSTOM = '__custom';

    public bool $modalOpen = false;
    public ?int $orderId = null;
    public string $templateKey = '';
    public string $phone = '';
    public string $message = '';

    protected function authorizeSend(): void
    {
        $user = auth()->user();

        if (! $user?->hasRole('superadmin') && ! $user?->can('order.edit')) {
            abort(403, 'Unauthorized action.');
        }
    }

    #[On('open-order-notification')]
    public function open(int $orderId): void
    {
        $this->authorizeSend();

        $order = $this->loadOrder($orderId);

        $this->resetValidation();
        $this->orderId = $order->id;
        $rawPhone = $order->shippingAddress?->phone ?: $order->customer?->phone;
        $this->phone = $rawPhone ? PhoneNumber::local(PhoneNumber::national($rawPhone)) : '';

        $default = SmsTemplate::where('is_active', true)->where('key', 'order_processing')->value('key')
            ?? SmsTemplate::where('is_active', true)->orderBy('label')->value('key')
            ?? self::CUSTOM;

        $this->templateKey = $default;
        $this->renderMessage($order);
        $this->modalOpen = true;
    }

    /** Picking another template re-fills the message from it (custom keeps whatever was typed). */
    public function updatedTemplateKey(): void
    {
        if ($this->orderId && $this->templateKey !== self::CUSTOM) {
            $this->renderMessage($this->loadOrder($this->orderId));
        }
    }

    public function send(): void
    {
        $this->authorizeSend();

        $this->validate([
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[\d\s\-]{6,20}$/'],
            'message' => 'required|string|max:1000',
        ], ['phone.regex' => 'Enter a valid phone number.']);

        $order = $this->loadOrder($this->orderId);
        $label = $this->templateKey === self::CUSTOM
            ? 'Custom message'
            : (SmsTemplate::where('key', $this->templateKey)->value('label') ?? $this->templateKey);

        $response = Sms::send(trim($this->phone), $this->message, 'order_manual');

        if (! $response->success) {
            $this->addError('message', 'Not sent: ' . ($response->errorMessage ?? 'SMS gateway error'));

            return;
        }

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->log("SMS sent to {$this->phone}: {$label}");

        $this->modalOpen = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => $response->status === 'queued' ? 'SMS queued for sending' : 'SMS sent']);
    }

    public function close(): void
    {
        $this->modalOpen = false;
    }

    protected function renderMessage(Order $order): void
    {
        $template = $this->templateKey !== self::CUSTOM
            ? SmsTemplate::where('key', $this->templateKey)->where('is_active', true)->first()
            : null;

        $this->message = $template ? $template->render($order->smsPlaceholders()) : '';
    }

    protected function loadOrder(?int $orderId): Order
    {
        return Order::with(['customer', 'shippingAddress', 'courierShipments.courier'])->findOrFail($orderId);
    }

    public function render()
    {
        return view('livewire.admin.sales.send-order-notification-modal', [
            'templates' => $this->modalOpen ? SmsTemplate::where('is_active', true)->orderBy('label')->get(['key', 'label']) : collect(),
        ]);
    }
}
