<?php

namespace App\Actions\Sales;

use App\Actions\Accounts\PostCustomerAdvance;
use App\Concerns\CreatesMasterProfile;
use App\Enums\Product\ProductType;
use App\Enums\Sales\OrderPaymentType;
use App\Enums\Sales\OrderStatus;
use App\Enums\Sales\PaymentMethod;
use App\Enums\Sales\PaymentStatus;
use App\Models\Account;
use App\Models\Customer;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Services\Shipping\ShippingCalculator;
use App\Services\StockService;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Places one order from a Bulk Order sheet row (or several rows merged by
 * phone). Mirrors what Sales → Create Order does for a single order —
 * line snapshots, recalculateTotals(), stock booking for a confirmed order,
 * and an advance recorded the way the order page's "Add Payment" records it
 * (payment row + PostCustomerAdvance) — plus customer resolution by phone:
 * an existing customer is reused (and gets this address saved if it's new
 * to them), an unknown phone becomes a new Customer with a default address.
 *
 * Everything runs in one transaction; any problem throws
 * InvalidArgumentException (or InsufficientStockException from booking)
 * and nothing is saved for that order.
 *
 * @phpstan-type BulkItem array{product_id: int, variant_id: ?int, quantity: float, unit_price: ?float}
 * @phpstan-type BulkOrderData array{
 *     phone: string, name: ?string, address: ?string,
 *     items: list<BulkItem>,
 *     shipping_method_id: ?int, delivery_charge: ?float, delivery_manual: bool,
 *     discount: float, advance: float, advance_account_id: ?int, advance_method: ?string,
 *     source: string, status: string, customer_note: ?string, admin_note: ?string,
 * }
 */
class PlaceBulkOrder
{
    use CreatesMasterProfile;

    public function __construct(
        protected StockService $stockService,
        protected ShippingCalculator $shippingCalculator,
    ) {}

    /** @param BulkOrderData $data */
    public function handle(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $phone = PhoneNumber::national($data['phone']);

            if (strlen($phone) < 6) {
                throw new InvalidArgumentException('Phone number is not valid.');
            }

            $lines = $this->resolveLines($data['items']);
            [$customer, $address] = $this->resolveCustomer($phone, trim((string) $data['name']), trim((string) $data['address']));

            $method = $data['shipping_method_id'] ? ShippingMethod::with('zone')->find($data['shipping_method_id']) : null;
            $subtotal = collect($lines)->sum(fn ($l) => $l['quantity'] * $l['unit_price']);

            $quote = $method
                ? $this->shippingCalculator->quote($method, collect($lines)->map(fn ($l) => [
                    'product' => $l['product'], 'quantity' => $l['quantity'], 'is_gift' => false,
                ])->all(), $subtotal)
                : null;

            $delivery = $data['delivery_manual'] || ! $quote
                ? max(0.0, (float) ($data['delivery_charge'] ?? 0))
                : $quote->amount;

            $discount = max(0.0, (float) $data['discount']);

            if ($discount > $subtotal + $delivery) {
                throw new InvalidArgumentException('Discount is larger than the order total.');
            }

            $status = OrderStatus::from($data['status']);

            $order = Order::create([
                'customer_id'         => $customer->id,
                'source'              => $data['source'],
                'status'              => $status,
                'payment_status'      => PaymentStatus::PENDING,
                'fulfillment_status'  => 'unfulfilled',
                'discount_amount'     => $discount,
                'shipping_amount'     => $delivery,
                'shipping_zone_id'    => $method?->shipping_zone_id,
                'shipping_method_id'  => $method?->id,
                'shipping_meta'       => $method && $quote && ! $data['delivery_manual'] ? $quote->toMeta($method) : null,
                'customer_note'       => $data['customer_note'] ?: null,
                'admin_note'          => $data['admin_note'] ?: null,
                'billing_address_id'  => $address->id,
                'shipping_address_id' => $address->id,
                'placed_at'           => now(),
                'confirmed_at'        => $status === OrderStatus::CONFIRMED ? now() : null,
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id'     => $line['product']->id,
                    'variant_id'     => $line['variant']?->id,
                    'is_gift'        => false,
                    'product_name'   => $line['product']->name,
                    'variant_name'   => $line['variant']?->sku,
                    'sku'            => $line['variant']?->sku ?? $line['product']->code,
                    'quantity'       => $line['quantity'],
                    'unit_price'     => $line['unit_price'],
                    'purchase_price' => $line['variant']?->purchase_price ?? $line['product']->purchase_price,
                    'total_amount'   => $line['quantity'] * $line['unit_price'],
                ]);
            }

            $order->recalculateTotals();

            if ($status->isBookable() && $this->stockService->reservesOnConfirm()) {
                $order->load('items');
                $this->stockService->bookOrder($order);
            }

            $advance = round(max(0.0, (float) $data['advance']), 2);

            if ($advance > 0) {
                $this->recordAdvance($order, $customer, $advance, $data['advance_account_id'] ?? null, $data['advance_method'] ?? null);
            }

            return $order->fresh();
        });
    }

    /**
     * Re-reads every line from the catalogue — nothing from the sheet is
     * trusted except ids, quantity and an explicit unit price.
     *
     * @param  list<BulkItem>  $items
     * @return list<array{product: Product, variant: ?ProductVariant, quantity: float, unit_price: float}>
     */
    protected function resolveLines(array $items): array
    {
        if ($items === []) {
            throw new InvalidArgumentException('Add at least one product.');
        }

        $lines = [];

        foreach ($items as $item) {
            $product = Product::active()->find($item['product_id'] ?? 0);

            if (! $product || $product->product_type === ProductType::COMBO) {
                throw new InvalidArgumentException('A product on this order is not available.');
            }

            $variant = null;

            if ($product->product_type === ProductType::VARIABLE) {
                $variant = ProductVariant::active()->where('product_id', $product->id)->find($item['variant_id'] ?? 0);

                if (! $variant) {
                    throw new InvalidArgumentException("Choose a variant for \"{$product->name}\".");
                }
            }

            $quantity = (float) ($item['quantity'] ?? 0);

            if ($quantity <= 0) {
                throw new InvalidArgumentException("Quantity for \"{$product->name}\" must be more than 0.");
            }

            $unitPrice = isset($item['unit_price']) && is_numeric($item['unit_price'])
                ? (float) $item['unit_price']
                : $product->sellingPrice($variant);

            if ($unitPrice < 0) {
                throw new InvalidArgumentException("Price for \"{$product->name}\" can't be negative.");
            }

            $lines[] = ['product' => $product, 'variant' => $variant, 'quantity' => $quantity, 'unit_price' => round($unitPrice, 2)];
        }

        return $lines;
    }

    /**
     * Existing customer by phone, else a new one (name + address required).
     * The address typed on the sheet is reused when the customer already has
     * it, saved as a new address when it's new to them, and when it's left
     * blank their default shipping address is used.
     *
     * @return array{0: Customer, 1: DeliveryAddress}
     */
    protected function resolveCustomer(string $phone, string $name, string $addressText): array
    {
        $customer = Customer::where('phone', $phone)->first();

        if (! $customer) {
            if ($name === '') {
                throw new InvalidArgumentException('Name is required for a new customer.');
            }
            if ($addressText === '') {
                throw new InvalidArgumentException('Address is required for a new customer.');
            }

            [$firstName, $lastName] = array_pad(explode(' ', $name, 2), 2, null);

            $masterProfile = $this->createMasterProfileFor([
                'display_name' => $name,
                'first_name'   => $firstName,
                'last_name'    => $lastName,
                'phone'        => $phone,
            ]);

            $customer = Customer::create([
                'master_profile_id' => $masterProfile->id,
                'customer_code'     => 'CUS-' . str_pad((string) (Customer::withTrashed()->max('id') + 1), 5, '0', STR_PAD_LEFT),
                'first_name'        => $firstName,
                'last_name'         => $lastName,
                'full_name'         => $name,
                'phone'             => $phone,
                'status'            => 'active',
            ]);

            activity('customers')
                ->causedBy(auth()->user())
                ->performedOn($customer)
                ->event('created')
                ->log("Customer \"{$name}\" was added from a bulk order");

            return [$customer, $this->createAddress($customer, $name, $phone, $addressText, true)];
        }

        if ($addressText === '') {
            $address = DeliveryAddress::where('customer_id', $customer->id)
                ->orderByDesc('is_default_shipping')->orderByDesc('id')->first();

            if (! $address) {
                throw new InvalidArgumentException('Address is required — this customer has no saved address.');
            }

            return [$customer, $address];
        }

        $normalized = mb_strtolower(preg_replace('/\s+/u', ' ', $addressText));
        $address = DeliveryAddress::where('customer_id', $customer->id)->get()
            ->first(fn ($a) => mb_strtolower(preg_replace('/\s+/u', ' ', (string) $a->full_address)) === $normalized);

        return [$customer, $address ?? $this->createAddress($customer, $name ?: $customer->full_name, $phone, $addressText, false)];
    }

    protected function createAddress(Customer $customer, string $name, string $phone, string $fullAddress, bool $default): DeliveryAddress
    {
        return DeliveryAddress::create([
            'customer_id'         => $customer->id,
            'address_type'        => 'Home',
            'name'                => $name,
            'phone'               => $phone,
            'full_address'        => $fullAddress,
            'is_default_billing'  => $default,
            'is_default_shipping' => $default,
            'is_active'           => true,
        ]);
    }

    /**
     * Same as the order page's Add Payment (status paid): a payment row, and
     * — with the Accounts module on — a Customer Advance journal into the
     * chosen cash/bank/mobile-banking account.
     */
    protected function recordAdvance(Order $order, Customer $customer, float $amount, ?int $accountId, ?string $method): void
    {
        if ($amount > (float) $order->total_amount) {
            throw new InvalidArgumentException('Advance is larger than the order total.');
        }

        $accountsOn = (bool) Setting::get('accounts_enabled', false, 'modules');
        $account = $accountId ? Account::find($accountId) : null;

        if ($accountsOn && ! $account) {
            throw new InvalidArgumentException('Choose the account the advance was received in.');
        }

        $order->payments()->create([
            'type'            => OrderPaymentType::PAYMENT,
            'payment_method'  => $account ? match ($account->subtype) {
                'bank'           => PaymentMethod::BANK,
                'mobile_banking' => PaymentMethod::BKASH,
                default          => PaymentMethod::CASH,
            } : (PaymentMethod::tryFrom((string) $method) ?? PaymentMethod::CASH),
            'cash_account_id' => $account?->id,
            'amount'          => $amount,
            'status'          => PaymentStatus::PAID,
            'paid_at'         => now(),
        ]);

        $order->recalculateTotals();
        $order->update(['payment_status' => $order->due_amount > 0 ? PaymentStatus::PARTIAL : PaymentStatus::PAID]);

        if ($accountsOn && $account) {
            app(PostCustomerAdvance::class)->handle(
                customer: $customer,
                order: $order,
                customerAdvanceAccountId: Account::where('code', '2160')->value('id')
                    ?? throw new InvalidArgumentException('Chart of accounts is missing account code 2160.'),
                cashAccountId: $account->id,
                amount: $amount,
                entryDate: now()->toDateString(),
                description: "Advance for Order #{$order->id} ({$account->name})",
            );
        }
    }
}
