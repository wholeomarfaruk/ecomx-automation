<?php

namespace App\Livewire\Admin\Sales;

use App\Actions\Sales\ReceiveReturn;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\Order;
use DomainException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Receive a returned parcel whose money doesn't add up — opened by Orders →
 * "Return received" only when ReceiveReturn::assess() finds something off
 * (customer paid more or less than the delivery charge, a partial
 * delivery's returned items not marked, kept items not matching what was
 * collected). One screen to fix it before receiving: Kept/Returned per item
 * (pre-filled with the split that matches the amount paid), the delivery
 * charge and discount (with a reason, logged), and a live total/due
 * preview — the sums run in the browser, saving goes through
 * ReceiveReturn::handle().
 */
class ReceiveReturnModal extends Component
{
    public bool $show = false;

    public ?int $orderId = null;

    /** @var array<int, string> item id => returned quantity */
    public array $returned = [];

    public string $shipping = '';

    public string $discount = '';

    public string $reason = '';

    /** What assess() found off — shown at the top. */
    public ?string $issue = null;

    /** The Kept/Returned split was worked out from the amount paid, not what was saved. */
    public bool $suggested = false;

    #[On('open-receive-return')]
    public function open(int $orderId): void
    {
        $this->resetErrorBag();
        $this->orderId = $orderId;
        unset($this->order);
        $order = $this->order;

        if (! $order || ! ReceiveReturn::awaiting($order)) {
            $this->dispatch('toast', ['type' => 'error', 'message' => "Order #{$orderId} has no returned parcel waiting."]);

            return;
        }

        $check = app(ReceiveReturn::class)->assess($order);

        $this->returned = collect($check['returned'])->map(fn ($qty) => (string) (float) $qty)->all();
        $this->shipping = (string) (float) $order->shipping_amount;
        $this->discount = (string) (float) $order->discount_amount;
        $this->reason = '';
        $this->issue = $check['issue'];
        $this->suggested = $check['suggested'];
        $this->show = true;
    }

    #[Computed]
    public function order(): ?Order
    {
        return $this->orderId
            ? Order::with(['customer:id,full_name,phone', 'items.product', 'items.variant.media'])->find($this->orderId)
            : null;
    }

    public function confirm(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'returned.*' => 'numeric|min:0',
            'shipping'   => 'required|numeric|min:0',
            'discount'   => 'required|numeric|min:0',
            'reason'     => 'nullable|string|max:255',
        ], [], [
            'returned.*' => 'returned quantity',
            'shipping'   => 'delivery charge',
        ]);

        $order = $this->order;

        if (! $order) {
            return;
        }

        try {
            app(ReceiveReturn::class)->handle($order, $this->returned, (float) $this->shipping, (float) $this->discount, $this->reason);
        } catch (DomainException|InsufficientStockException $e) {
            $this->addError('receive', $e->getMessage());

            return;
        }

        $order->refresh();
        $over = round((float) $order->paid_amount - (float) $order->total_amount, 2);

        $message = "Order #{$order->id}: return received";
        $message .= match (true) {
            (float) $order->due_amount > ReceiveReturn::TOLERANCE => ' — ' . number_format((float) $order->due_amount, 2) . ' still due',
            $over > ReceiveReturn::TOLERANCE => ' — customer paid ' . number_format($over, 2) . ' more; refund it from the order page',
            default => '',
        };

        $this->show = false;
        $this->dispatch('order-return-received', orderId: $order->id);
        $this->dispatch('toast', ['type' => 'success', 'message' => $message]);
    }

    public function render()
    {
        return view('livewire.admin.sales.receive-return-modal');
    }
}
