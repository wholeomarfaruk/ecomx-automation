<?php

namespace App\Livewire\EcomxFashion;

use App\Enums\Sales\OrderSource;
use App\Enums\Sales\PaymentStatus;
use App\Enums\User\Status;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Marketing\Events\InitiateCheckout;
use App\Marketing\Events\Purchase as PurchaseEvent;
use App\Marketing\Services\MarketingEventService;
use App\Models\Block;
use App\Models\Cart;
use App\Models\City;
use App\Models\Country;
use App\Models\Customer;
use App\Models\DeliveryAddress;
use App\Models\Device;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Services\BlockGuard;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('ecomx-fashion.layouts.ecomx_fashion')]
class Checkout extends Component
{
    public string $name = '';
    public string $phone = '';
    public string $address = '';
    public string $note = '';
    public string $delivery_area = 'dhaka';
    public string $payment_method = 'cod';
    public string $transaction_id = '';

    public bool $placed = false;

    public array $deliveryAreas = [
        ['id' => 'dhaka', 'name' => 'Inside Dhaka', 'charge' => 70],
        ['id' => 'outside', 'name' => 'Outside Dhaka', 'charge' => 130],
    ];

    public string $bkashNumber = '01682963493';

    public array $marketingEvents = [];

    public function mount(): void
    {
        $this->recordInitiateCheckout();
    }

    private function recordInitiateCheckout(): void
    {
        /** @var Device|null $device */
        $device = request()->attributes->get('device');

        if (! $device) {
            return;
        }

        $cart = $this->cart;

        if ($cart->items->isEmpty()) {
            return;
        }

        $items = $cart->items->map(fn ($item) => [
            'item_id' => (string) $item->product_id,
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'item_name' => $item->product?->name,
            'quantity' => $item->quantity,
            'price' => (float) $item->price,
            'currency' => 'BDT',
        ])->all();

        $value = $cart->items->sum(fn ($item) => $item->quantity * $item->price);

        $event = InitiateCheckout::create(
            value: (float) $value,
            currency: 'BDT',
            items: $items,
            itemCount: $cart->items->count(),
        );

        $result = app(MarketingEventService::class)->recordForCurrentRequest(
            event: $event,
            device: $device,
            customer: auth()->check() ? auth()->user()->customer : null,
        );

        $this->marketingEvents[] = $result['browserPayload'];
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'delivery_area' => 'required|in:dhaka,outside',
            'payment_method' => 'required|in:cod,bkash',
            'transaction_id' => 'required_if:payment_method,bkash|nullable|string|max:100',
        ];
    }

    public ?int $orderId = null;

    public function placeOrder(): void
    {
        if (app(BlockGuard::class)->isBlocked(request(), Block::SCOPE_CHECKOUT)) {
            abort(403, 'This action is not available for your account.');
        }

        $this->validate();

        $cart = $this->cart;

        if ($cart->items->isEmpty()) {
            $this->addError('name', 'Your cart is empty — nothing to check out.');

            return;
        }

        $deliveryCharge = collect($this->deliveryAreas)->firstWhere('id', $this->delivery_area)['charge'] ?? 0;

        try {
            $order = DB::transaction(function () use ($cart, $deliveryCharge) {
                $customer = $this->findOrCreateCustomer();
                $address = $this->createDeliveryProfile($customer);

                $order = Order::create([
                    'customer_id' => $customer->id,
                    'source' => OrderSource::WEBSITE,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'fulfillment_status' => 'unfulfilled',
                    'shipping_amount' => $deliveryCharge,
                    'billing_address_id' => $address->id,
                    'shipping_address_id' => $address->id,
                    'customer_note' => $this->note ?: null,
                    'placed_at' => now(),
                ]);

                foreach ($cart->items as $item) {
                    $order->items()->create([
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'combo_id' => $item->combo_id,
                        'is_gift' => $item->is_gift,
                        'product_name' => $item->product?->name ?? 'Deleted product',
                        'variant_name' => $item->variant?->sku,
                        'sku' => $item->variant?->sku ?? $item->product?->code,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->price,
                        'purchase_price' => $item->variant?->purchase_price ?? $item->product?->purchase_price,
                        'total_amount' => $item->is_gift ? 0 : (float) $item->price * (float) $item->quantity,
                    ]);
                }

                $order->recalculateTotals();

                if ($this->payment_method === 'bkash') {
                    $order->payments()->create([
                        'payment_method' => 'bkash',
                        'transaction_id' => $this->transaction_id,
                        'amount' => $order->total_amount,
                        'status' => PaymentStatus::PENDING,
                    ]);
                }

                $deductOnConfirm = (bool) Setting::get('deduct_on_order_confirm', true, 'inventory');

                if (! $deductOnConfirm) {
                    app(StockService::class)->commitOrder($order);
                }

                $cart->update(['status' => 'converted']);

                return $order;
            });
        } catch (InsufficientStockException $e) {
            $this->addError('name', $e->getMessage());

            return;
        }

        $this->recordPurchase($order);

        $this->orderId = $order->id;
        $this->placed = true;
    }

    /**
     * Identify the person checking out, trying every signal available
     * before falling back to creating a brand-new account:
     *   1. Already logged in this session — use that account as-is.
     *   2. An existing Customer with this phone number (Customer.phone is
     *      how repeat guests are recognized here — no email is collected
     *      at checkout) — log them in for this session too, so the order
     *      lands on their real history instead of a duplicate record.
     *   3. Nobody found — register a real User + Customer pair and log
     *      them in with a long-lived "remember me" cookie, so this isn't
     *      a throwaway guest checkout: the account persists and the same
     *      phone number will match step 2 on their next visit.
     */
    private function findOrCreateCustomer(): Customer
    {
        if (auth()->check() && auth()->user()->customer) {
            return auth()->user()->customer;
        }

        $existingCustomer = Customer::where('phone', $this->phone)->first();

        if ($existingCustomer) {
            if ($existingCustomer->user) {
                auth()->login($existingCustomer->user, remember: true);
            }

            return $existingCustomer;
        }

        [$firstName, $lastName] = array_pad(explode(' ', trim($this->name), 2), 2, null);

        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'checkout.local';
        $syntheticEmail = 'guest+' . $this->phone . '@' . $host;

        // Guard against the synthetic email colliding with an existing user
        // (e.g. a legacy Customer row with no linked user_id, so step 2
        // above didn't find/reuse it) — extremely unlikely but would
        // otherwise throw a unique constraint violation.
        if (User::where('email', $syntheticEmail)->exists()) {
            $syntheticEmail = 'guest+' . $this->phone . '+' . Str::random(6) . '@' . $host;
        }

        $user = User::create([
            'name' => $this->name,
            'email' => $syntheticEmail,
            'password' => Str::random(40),
            'phone' => $this->phone,
            'status' => Status::ACTIVE,
        ]);

        auth()->login($user, remember: true);

        $code = 'CUS-' . str_pad((string) (Customer::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT);

        return Customer::create([
            'user_id' => $user->id,
            'customer_code' => $code,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'full_name' => $this->name,
            'phone' => $this->phone,
            'status' => 'active',
        ]);
    }

    /**
     * Creates a real, reusable DeliveryAddress for this customer from what
     * checkout actually collects (a free-text address + a coarse Dhaka /
     * outside-Dhaka choice — no location picker). full_address always
     * carries the real text the customer typed; the structured geo columns
     * (country/state/city — ps/area/zip_code/street have no location data
     * or models in this codebase at all) are set only when they can
     * genuinely be resolved, left null otherwise rather than guessed.
     */
    private function createDeliveryProfile(Customer $customer): DeliveryAddress
    {
        $country = Country::whereRaw('LOWER(name) = ?', ['bangladesh'])->first();

        $city = $this->delivery_area === 'dhaka'
            ? City::where('name', 'like', 'Dhaka%')
                ->when($country, fn ($q) => $q->whereHas('state', fn ($s) => $s->where('country_id', $country->id)))
                ->first()
            : null;

        $state = $city?->state;

        // address_type is a free-text label the customer can rename later
        // (no fixed list) — default a new customer's first address to
        // "Home" and mark it as their default shipping/billing address.
        // A returning customer's new address is saved alongside their
        // existing one(s) without touching which is default — only one
        // address should ever be "the" default, and this form has no way
        // to let the customer choose to replace it.
        $isFirstAddress = ! DeliveryAddress::where('customer_id', $customer->id)->exists();

        return DeliveryAddress::create([
            'customer_id' => $customer->id,
            'address_type' => $isFirstAddress ? 'Home' : null,
            'name' => $this->name,
            'phone' => $this->phone,
            'country_id' => $country?->id,
            'state_id' => $state?->id,
            'city_id' => $city?->id,
            'full_address' => $this->address,
            'is_default_shipping' => $isFirstAddress,
            'is_default_billing' => $isFirstAddress,
            'is_active' => true,
        ]);
    }

    private function recordPurchase(Order $order): void
    {
        /** @var Device|null $device */
        $device = request()->attributes->get('device');

        if (! $device) {
            return;
        }

        $order->load('items');

        $items = $order->items->map(fn ($item) => [
            'item_id' => (string) $item->product_id,
            'product_id' => $item->product_id,
            'variant_id' => $item->variant_id,
            'item_name' => $item->product_name,
            'quantity' => (float) $item->quantity,
            'price' => (float) $item->unit_price,
            'currency' => 'BDT',
        ])->all();

        $event = PurchaseEvent::create(
            value: (float) $order->total_amount,
            currency: 'BDT',
            orderId: $order->id,
            items: $items,
        );

        $result = app(MarketingEventService::class)->recordForCurrentRequest(
            event: $event,
            device: $device,
            customer: $order->customer,
        );

        $this->marketingEvents[] = $result['browserPayload'];
    }

    public function getCartProperty(): Cart
    {
        $device = request()->attributes->get('device');

        $cart = Cart::firstOrCreate(
            ['customer_id' => null, 'device_id' => $device?->id],
            []
        );

        $cart->load('items.product', 'items.variant.values.productAttributeValue.attributeValue.attribute', 'items.variant.media');

        return $cart;
    }

    public function render()
    {
        return view('ecomx-fashion.livewire.checkout', [
            'cart' => $this->cart,
        ]);
    }
}
