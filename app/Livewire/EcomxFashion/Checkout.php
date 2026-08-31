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
use App\Support\PhoneNumber;
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
    public string $address_type = '';
    public string $note = '';
    public string $delivery_area = 'dhaka';
    public string $payment_method = 'cod';
    public string $transaction_id = '';

    /**
     * Logged-in address picker state. selectedAddressId set = the customer
     * picked one of their saved DeliveryAddress rows, so the free-text
     * name/phone/address fields below are ignored on submit — see rules()
     * and createDeliveryProfile(). Guests, and logged-in customers with no
     * saved addresses yet, never set this and use the plain form as before.
     */
    public ?int $selectedAddressId = null;
    public bool $showNewAddressForm = false;
    public bool $setAsDefault = false;

    /**
     * Set while the "Add/Edit address" modal is open for an existing saved
     * address (Edit button on a card) rather than a brand-new one — same
     * modal and fields, saveNewAddress() just updates this row in place
     * instead of creating a new DeliveryAddress.
     */
    public ?int $editingAddressId = null;

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
        $this->initAddressSelection();
    }

    /**
     * Logged-in customers with at least one saved address start on the
     * picker with their default (or most recent) address pre-selected and
     * the new-address form collapsed. Everyone else (guests, or a logged-in
     * customer with zero saved addresses) sees the plain form right away —
     * there's nothing to pick from yet.
     */
    private function initAddressSelection(): void
    {
        $customer = auth()->check() ? auth()->user()->customer : null;

        if (! $customer) {
            $this->showNewAddressForm = true;

            return;
        }

        $default = $this->savedAddresses->firstWhere('is_default_shipping', true)
            ?? $this->savedAddresses->first();

        if ($default) {
            $this->selectedAddressId = $default->id;
            $this->showNewAddressForm = false;
        } else {
            $this->showNewAddressForm = true;
        }
    }

    public function getSavedAddressesProperty(): \Illuminate\Support\Collection
    {
        if (! auth()->check() || ! auth()->user()->customer) {
            return collect();
        }

        return DeliveryAddress::where('customer_id', auth()->user()->customer->id)
            ->where('is_active', true)
            ->orderByDesc('is_default_shipping')
            ->latest()
            ->get();
    }

    public function selectAddress(int $addressId): void
    {
        $this->selectedAddressId = $addressId;
        $this->showNewAddressForm = false;
        $this->resetErrorBag(['name', 'phone', 'address']);
    }

    public function showAddAddressForm(): void
    {
        // Only clear the current selection for a customer with no saved
        // addresses yet (the inline-form case) — for the modal case
        // (savedAddresses not empty) opening the modal must not drop
        // whichever card was already selected, in case the customer just
        // closes it again without saving a new one.
        if ($this->savedAddresses->isEmpty()) {
            $this->selectedAddressId = null;
        }

        $this->editingAddressId = null;
        $this->reset(['name', 'phone', 'address', 'address_type', 'setAsDefault']);
        $this->showNewAddressForm = true;
    }

    /**
     * Edit button on a saved-address card — opens the same modal as "Add
     * new address" but pre-filled from that row, and setAsDefault reflects
     * whether it already is the default rather than starting unchecked.
     */
    public function editAddress(int $addressId): void
    {
        $customerId = auth()->check() ? auth()->user()->customer?->id : null;

        $address = $customerId
            ? DeliveryAddress::where('id', $addressId)->where('customer_id', $customerId)->first()
            : null;

        if (! $address) {
            return;
        }

        $this->editingAddressId = $address->id;
        $this->name = $address->name;
        $this->phone = $address->phone ?? '';
        $this->address = $address->full_address ?? '';
        $this->address_type = $address->address_type ?? '';
        $this->setAsDefault = $address->is_default_shipping;

        $this->showNewAddressForm = true;
    }

    public function closeAddAddressForm(): void
    {
        $this->showNewAddressForm = false;
        $this->editingAddressId = null;
        $this->reset(['name', 'phone', 'address', 'address_type', 'setAsDefault']);
        $this->resetErrorBag(['name', 'phone', 'address']);
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

    /**
     * name/phone/address only need to come from the form when the customer
     * is actually filling it in — a selected saved address already carries
     * that data in the DeliveryAddress row (see createDeliveryProfile()).
     */
    protected function rules(): array
    {
        $addressRules = $this->selectedAddressId
            ? ['selectedAddressId' => 'required|integer|exists:delivery_addresses,id']
            : [
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'address' => 'required|string',
                'address_type' => 'nullable|string|max:50',
            ];

        return $addressRules + [
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

        if (! $this->selectedAddressId) {
            $this->phone = PhoneNumber::national($this->phone);
        }

        $selectedAddress = null;

        if ($this->selectedAddressId) {
            // Ownership check — a selectedAddressId is a plain public id sent
            // from the browser, so guard against one customer's session
            // referencing another customer's saved address.
            $customerId = auth()->check() ? auth()->user()->customer?->id : null;

            $selectedAddress = $customerId
                ? DeliveryAddress::where('id', $this->selectedAddressId)->where('customer_id', $customerId)->first()
                : null;

            if (! $selectedAddress) {
                $this->addError('selectedAddressId', 'That address is no longer available — please pick another or add a new one.');

                return;
            }

            // findOrCreateCustomer() below matches/logs in by phone number,
            // and the success screen displays $this->phone — both need to
            // reflect the address actually being used for this order.
            $this->name = $selectedAddress->name;
            $this->phone = $selectedAddress->phone ? PhoneNumber::national($selectedAddress->phone) : $this->phone;
        }

        $cart = $this->cart;

        if ($cart->items->isEmpty()) {
            $this->addError('name', 'Your cart is empty — nothing to check out.');

            return;
        }

        $deliveryCharge = collect($this->deliveryAreas)->firstWhere('id', $this->delivery_area)['charge'] ?? 0;

        try {
            $order = DB::transaction(function () use ($cart, $deliveryCharge, $selectedAddress) {
                $customer = $this->findOrCreateCustomer();
                $address = $this->createDeliveryProfile($customer, $selectedAddress);

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

        $user->assignWebsiteAccess();

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
     * Resolves the delivery address to actually bill/ship this order to.
     * If the customer picked a saved address in the picker, reuse that row
     * as-is (optionally promoting it to default) instead of creating a
     * duplicate. Otherwise creates a real, reusable DeliveryAddress from
     * what the form actually collects (a free-text address + a coarse
     * Dhaka / outside-Dhaka choice — no location picker). full_address
     * always carries the real text the customer typed; the structured geo
     * columns (country/state/city — ps/area/zip_code/street have no
     * location data or models in this codebase at all) are set only when
     * they can genuinely be resolved, left null otherwise rather than
     * guessed.
     */
    private function createDeliveryProfile(Customer $customer, ?DeliveryAddress $selectedAddress = null): DeliveryAddress
    {
        if ($selectedAddress) {
            if ($this->setAsDefault && ! $selectedAddress->is_default_shipping) {
                $this->promoteToDefault($customer, $selectedAddress);
            }

            return $selectedAddress;
        }

        return $this->storeAddressFromForm($customer);
    }

    /**
     * Builds a DeliveryAddress row from the current name/phone/address/
     * delivery_area/setAsDefault fields. Shared by the checkout-time path
     * (guest, or a logged-in customer with no saved addresses yet) and
     * saveNewAddress() (the "Add new address" modal, for a logged-in
     * customer who already has at least one saved address).
     */
    private function storeAddressFromForm(Customer $customer): DeliveryAddress
    {
        $country = Country::whereRaw('LOWER(name) = ?', ['bangladesh'])->first();

        $city = $this->delivery_area === 'dhaka'
            ? City::where('name', 'like', 'Dhaka%')
                ->when($country, fn ($q) => $q->whereHas('state', fn ($s) => $s->where('country_id', $country->id)))
                ->first()
            : null;

        $state = $city?->state;

        // address_type is a free-text label ("Home", "Office", ...) the
        // customer types in the form — defaults to "Home" for their very
        // first address if left blank. A brand-new address becomes the
        // default whenever it's the customer's first one, or when they
        // explicitly checked "set as default" in the add-address form.
        $isFirstAddress = ! DeliveryAddress::where('customer_id', $customer->id)->exists();
        $makeDefault = $isFirstAddress || $this->setAsDefault;

        if ($makeDefault && ! $isFirstAddress) {
            DeliveryAddress::where('customer_id', $customer->id)
                ->update(['is_default_shipping' => false, 'is_default_billing' => false]);
        }

        return DeliveryAddress::create([
            'customer_id' => $customer->id,
            'address_type' => trim($this->address_type) ?: ($isFirstAddress ? 'Home' : null),
            'name' => $this->name,
            'phone' => $this->phone,
            'country_id' => $country?->id,
            'state_id' => $state?->id,
            'city_id' => $city?->id,
            'full_address' => $this->address,
            'is_default_shipping' => $makeDefault,
            'is_default_billing' => $makeDefault,
            'is_active' => true,
        ]);
    }

    /**
     * "Add new address" modal action — logged-in customers who already have
     * at least one saved address get a modal instead of the inline form
     * (see showAddAddressForm()/that form only shows inline for guests and
     * first-time customers). Saves immediately, closes the modal, and
     * selects the new address in the picker, same as clicking an existing
     * card — Place Order never sees the raw form fields in this path.
     */
    public function saveNewAddress(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'address_type' => 'nullable|string|max:50',
        ]);

        $this->phone = PhoneNumber::national($this->phone);

        $customer = auth()->check() ? auth()->user()->customer : null;

        if (! $customer) {
            return;
        }

        $address = $this->editingAddressId
            ? $this->updateAddressFromForm($customer, $this->editingAddressId)
            : $this->storeAddressFromForm($customer);

        if (! $address) {
            $this->addError('name', 'That address is no longer available — please pick another or add a new one.');

            return;
        }

        $this->selectedAddressId = $address->id;
        $this->showNewAddressForm = false;
        $this->editingAddressId = null;

        $this->reset(['name', 'phone', 'address', 'address_type', 'setAsDefault']);
    }

    /**
     * Applies the modal's fields onto an existing saved address (Edit
     * button) instead of creating a new row. Ownership-checked the same
     * way as placeOrder()'s selectedAddressId — a plain public id sent
     * from the browser must not let one customer edit another's address.
     */
    private function updateAddressFromForm(Customer $customer, int $addressId): ?DeliveryAddress
    {
        $address = DeliveryAddress::where('id', $addressId)->where('customer_id', $customer->id)->first();

        if (! $address) {
            return null;
        }

        if ($this->setAsDefault && ! $address->is_default_shipping) {
            $this->promoteToDefault($customer, $address);
        }

        $address->update([
            'name' => $this->name,
            'phone' => $this->phone,
            'full_address' => $this->address,
            'address_type' => trim($this->address_type) ?: null,
        ]);

        return $address->refresh();
    }

    private function promoteToDefault(Customer $customer, DeliveryAddress $address): void
    {
        DeliveryAddress::where('customer_id', $customer->id)
            ->update(['is_default_shipping' => false, 'is_default_billing' => false]);

        $address->update(['is_default_shipping' => true, 'is_default_billing' => true]);
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
