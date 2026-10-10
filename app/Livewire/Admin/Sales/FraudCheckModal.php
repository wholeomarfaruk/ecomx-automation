<?php

namespace App\Livewire\Admin\Sales;

use App\Models\FraudCheck;
use App\Models\Order;
use App\Services\FraudShield\FraudShield;
use App\Services\FraudShield\FraudShieldException;
use App\Services\FraudShield\FraudShieldSettings;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "Fraud Check" modal, shared by the Orders list and the Order page (same
 * pattern as SendOrderNotificationModal). Opened with the browser event
 * `open-fraud-check` ({ orderId }). Shows the stored FraudShield result
 * when still fresh, otherwise calls the API; "Re-check" always calls it.
 * Dispatches `fraud-checked` so the page behind can refresh its badge.
 */
class FraudCheckModal extends Component
{
    public bool $modalOpen = false;
    public ?int $orderId = null;
    public string $phone = '';
    public ?int $checkId = null;
    public string $error = '';

    protected function authorizeCheck(): void
    {
        $user = auth()->user();

        if (! $user?->hasRole('superadmin') && ! $user?->can('order.view')) {
            abort(403, 'Unauthorized action.');
        }
    }

    #[On('open-fraud-check')]
    public function open(int $orderId): void
    {
        $this->authorizeCheck();

        $order = Order::with('customer', 'shippingAddress')->findOrFail($orderId);

        $this->orderId = $order->id;
        $this->phone = FraudShield::orderPhone($order)
            ?: (string) ($order->shippingAddress?->phone ?: $order->customer?->phone);
        $this->modalOpen = true;

        $this->run(false);
    }

    public function recheck(): void
    {
        $this->authorizeCheck();
        $this->run(true);
    }

    public function close(): void
    {
        $this->modalOpen = false;
        $this->orderId = null;
        $this->checkId = null;
        $this->error = '';
    }

    private function run(bool $fresh): void
    {
        $this->error = '';

        try {
            $check = app(FraudShield::class)->check($this->phone, $fresh);
            $this->checkId = $check->id;
            $this->dispatch('fraud-checked', phone: $check->phone);
        } catch (FraudShieldException $e) {
            $this->checkId = app(FraudShield::class)->stored($this->phone)?->id;
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.admin.sales.fraud-check-modal', [
            'check' => $this->checkId ? FraudCheck::find($this->checkId) : null,
            'canManage' => auth()->user()?->hasRole('superadmin') || auth()->user()?->can('fraud_checker.manage'),
            'ready' => app(FraudShieldSettings::class)->ready(),
        ]);
    }
}
