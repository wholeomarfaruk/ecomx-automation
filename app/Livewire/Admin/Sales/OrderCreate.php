<?php

namespace App\Livewire\Admin\Sales;

use App\Enums\Sales\OrderSource;
use App\Enums\Sales\OrderStatus;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\FulfillmentStatus;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Exceptions\Sales\CouponNotApplicableException;
use App\Livewire\Concerns\QuickAddsCustomer;
use App\Models\Combo;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\DeliveryAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\CouponShippingService;
use App\Services\StockService;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class OrderCreate extends Component
{
    use QuickAddsCustomer;

    public string $customerId  = '';

    public string $billingAddressId  = '';
    public string $shippingAddressId = '';

    public string $source             = 'admin';
    public string $status             = 'pending';
    public string $paymentStatus      = 'pending';
    public string $fulfillmentStatus  = 'unfulfilled';

    public string $discountAmount = '0';
    public string $shippingAmount = '0';
    public string $taxAmount      = '0';

    public string $couponCode      = '';
    public float  $shippingDiscount = 0.0;
    public string $couponError     = '';

    public string $customerNote = '';
    public string $adminNote    = '';

    /** @var array<int, array{kind: string, product_id: string, variant_id: string, combo_id: string, is_gift: bool, label: string, quantity: string, unit_price: string, purchase_price: string}> */
    public array $items = [];

    /** Bound to the Order Items product searchable-select; picking a product adds a line and resets. */
    public string $productPickerId = '';
    public string $comboSearch   = '';

    public function updatedCustomerId(): void
    {
        $this->billingAddressId  = '';
        $this->shippingAddressId = '';
    }

    public function selectCustomer(int $id): void
    {
        $this->customerId     = (string) $id;
        $this->billingAddressId  = '';
        $this->shippingAddressId = '';
    }

    public function clearCustomer(): void
    {
        $this->customerId = '';
        $this->billingAddressId  = '';
        $this->shippingAddressId = '';
    }

    /** QuickAddsCustomer hook: select the new customer and their address for this order. */
    protected function customerQuickAdded(Customer $customer, DeliveryAddress $address): void
    {
        $this->selectCustomer($customer->id);
        $this->billingAddressId  = (string) $address->id;
        $this->shippingAddressId = (string) $address->id;
    }

    public function updatedProductPickerId(string $value): void
    {
        if ($value !== '') {
            $this->addProductItem((int) $value);
        }

        $this->productPickerId = '';
    }

    public function addProductItem(int $productId): void
    {
        $product = Product::active()->find($productId);

        if (! $product) {
            return;
        }

        $this->items[] = [
            'kind'           => 'product',
            'product_id'     => (string) $product->id,
            'variant_id'     => '',
            'combo_id'       => '',
            'is_gift'        => false,
            'label'          => $product->name,
            'quantity'       => '1',
            'unit_price'     => (string) $product->sellingPrice(), // Product::sellingPrice(): min of regular/sale
            'purchase_price' => (string) ($product->purchase_price ?? ''),
        ];
    }

    public function selectVariantForItem(int $index, ?int $variantId): void
    {
        if (! isset($this->items[$index])) {
            return;
        }

        if (! $variantId) {
            $this->items[$index]['variant_id'] = '';
            return;
        }

        $variant = ProductVariant::active()->find($variantId);

        if (! $variant) {
            return;
        }

        $this->items[$index]['variant_id']     = (string) $variant->id;
        $this->items[$index]['unit_price']     = (string) $variant->product->sellingPrice($variant);
        $this->items[$index]['purchase_price'] = (string) ($variant->purchase_price ?? $this->items[$index]['purchase_price']);
    }

    public function addComboItem(int $comboId): void
    {
        $combo = Combo::find($comboId);

        if (! $combo) {
            return;
        }

        $this->items[] = [
            'kind'           => 'combo',
            'product_id'     => '',
            'variant_id'     => '',
            'combo_id'       => (string) $combo->id,
            'is_gift'        => false,
            'label'          => $combo->name ?? "Custom Combo #{$combo->id}",
            'quantity'       => '1',
            'unit_price'     => (string) $combo->price,
            'purchase_price' => '',
        ];

        $this->comboSearch = '';
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function getItemsSubtotalProperty(): float
    {
        return collect($this->items)->sum(function ($item) {
            if ($item['is_gift']) {
                return 0;
            }

            return (float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0);
        });
    }

    public function getGrandTotalProperty(): float
    {
        $netShipping = max(0.0, (float) ($this->shippingAmount ?: 0) - $this->shippingDiscount);

        return $this->itemsSubtotal
            - (float) ($this->discountAmount ?: 0)
            + $netShipping
            + (float) ($this->taxAmount ?: 0);
    }

    public function updatedShippingAmount(): void
    {
        if ($this->couponCode !== '' && $this->shippingDiscount > 0) {
            $this->applyCoupon();
        }
    }

    public function applyCoupon(): void
    {
        $this->couponError = '';

        if ($this->couponCode === '') {
            $this->shippingDiscount = 0.0;
            return;
        }

        $customer = $this->customerId ? Customer::find($this->customerId) : null;

        try {
            $coupon = app(CouponShippingService::class)->validate(
                $this->couponCode,
                $this->itemsSubtotal,
                $customer
            );

            $this->shippingDiscount = app(CouponShippingService::class)
                ->calculateShippingDiscount($coupon, (float) ($this->shippingAmount ?: 0));

            if ($this->shippingDiscount <= 0) {
                $this->couponError = 'This coupon does not offer a shipping discount.';
            }
        } catch (CouponNotApplicableException $e) {
            $this->shippingDiscount = 0.0;
            $this->couponError = $e->getMessage();
        }
    }

    public function removeCoupon(): void
    {
        $this->couponCode       = '';
        $this->shippingDiscount = 0.0;
        $this->couponError      = '';
    }

    protected function rules(): array
    {
        return [
            'customerId'          => 'nullable|integer|exists:customers,id',
            'billingAddressId'    => 'nullable|integer|exists:delivery_addresses,id',
            'shippingAddressId'   => 'nullable|integer|exists:delivery_addresses,id',
            'source'              => 'required|in:website,messenger,whatsapp,pos,admin,api',
            'status'              => ['required', Rule::enum(OrderStatus::class)],
            'paymentStatus'       => 'required|in:pending,partial,paid,failed,refunded',
            'fulfillmentStatus'   => 'required|in:unfulfilled,partial,fulfilled',
            'discountAmount'      => 'nullable|numeric|min:0',
            'shippingAmount'      => 'nullable|numeric|min:0',
            'taxAmount'           => 'nullable|numeric|min:0',
            'items'               => 'required|array|min:1',
            'items.*.quantity'    => 'required|numeric|min:0.001',
            'items.*.unit_price'  => 'required|numeric|min:0',
        ];
    }

    public function save(): void
    {
        $this->validate();

        try {
            $order = DB::transaction(function () {
                $order = Order::create([
                    'customer_id'         => $this->customerId ?: null,
                    'source'              => $this->source,
                    'status'              => $this->status,
                    'payment_status'      => $this->paymentStatus,
                    'fulfillment_status'  => $this->fulfillmentStatus,
                    'discount_amount'     => $this->discountAmount ?: 0,
                    'shipping_amount'     => $this->shippingAmount ?: 0,
                    'tax_amount'          => $this->taxAmount ?: 0,
                    'customer_note'       => $this->customerNote ?: null,
                    'admin_note'          => $this->adminNote ?: null,
                    'billing_address_id'  => $this->billingAddressId ?: null,
                    'shipping_address_id' => $this->shippingAddressId ?: null,
                    'placed_at'           => now(),
                    'confirmed_at'        => $this->status === 'confirmed' ? now() : null,
                ]);

                foreach ($this->items as $item) {
                    $productName  = null;
                    $variantName  = null;
                    $sku          = null;

                    if ($item['kind'] === 'combo') {
                        $combo = Combo::find($item['combo_id']);
                        $productName = $combo?->name ?? "Custom Combo #{$item['combo_id']}";
                    } else {
                        $product = Product::find($item['product_id']);
                        $productName = $product?->name ?? 'Unknown product';
                        $sku = $product?->code;

                        if ($item['variant_id']) {
                            $variant = ProductVariant::find($item['variant_id']);
                            $variantName = $variant?->sku;
                            $sku = $variant?->sku ?? $sku;
                        }
                    }

                    $quantity  = (float) $item['quantity'];
                    $unitPrice = $item['is_gift'] ? 0 : (float) $item['unit_price'];

                    $order->items()->create([
                        'product_id'     => $item['product_id'] ?: null,
                        'variant_id'     => $item['variant_id'] ?: null,
                        'combo_id'       => $item['combo_id'] ?: null,
                        'is_gift'        => $item['is_gift'],
                        'product_name'   => $productName,
                        'variant_name'   => $variantName,
                        'sku'            => $sku,
                        'quantity'       => $quantity,
                        'unit_price'     => $unitPrice,
                        'purchase_price' => $item['purchase_price'] !== '' ? $item['purchase_price'] : null,
                        'total_amount'   => $unitPrice * $quantity,
                    ]);
                }

                if ($this->couponCode !== '' && $this->couponError === '') {
                    try {
                        app(CouponShippingService::class)->applyToOrder($order, $this->couponCode);
                    } catch (CouponNotApplicableException $e) {
                        // Coupon became invalid between preview and save (e.g. usage
                        // limit reached by a concurrent order) — proceed without it
                        // rather than losing the order the admin just built.
                        $order->recalculateTotals();
                    }
                } else {
                    $order->recalculateTotals();
                }

                $bookOnConfirm = (bool) Setting::get('book_on_order_confirm', true, 'inventory');
                $status = OrderStatus::from($this->status);

                if ($bookOnConfirm && $status->isBookable()) {
                    $order->load('items');
                    app(StockService::class)->bookOrder($order);
                }

                return $order;
            });
        } catch (InsufficientStockException $e) {
            $this->addError('items', $e->getMessage());
            return;
        }

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('created')
            ->log("Order #{$order->id} was created");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Order created successfully']);

        $this->redirect(route('admin.sales.orders.show', $order->id), navigate: true);
    }

    public function render(): mixed
    {
        // Searchable-select filters on the label client-side, so the phone is
        // part of it — search works by name or phone.
        $customerOptions = $this->customerId
            ? collect()
            : Customer::orderBy('full_name')
                ->get(['id', 'full_name', 'phone'])
                ->mapWithKeys(fn ($c) => [$c->id => $c->phone ? "{$c->full_name} — {$c->phone}" : $c->full_name]);

        $selectedCustomer = $this->customerId ? Customer::find($this->customerId) : null;

        $customerAddresses = $selectedCustomer
            ? DeliveryAddress::where('customer_id', $selectedCustomer->id)->get()
            : collect();

        $products = Product::active()
            ->where('product_type', '!=', 'combo')
            ->with('featuredImage.items')
            ->get(['id', 'name', 'code', 'featured_image_id']);

        $productOptions = $products->mapWithKeys(fn ($p) => [$p->id => $p->code ? "{$p->name} ({$p->code})" : $p->name]);
        // Eager-loaded thumbnail (falls back to original) — same resolution as
        // file_path($id, 'thumbnail') without a query per product.
        $productImages = $products->mapWithKeys(function ($p) {
            // getRelation(): $p->featuredImage resolves to the getFeaturedImageAttribute() URL string, not the relation.
            $items = $p->getRelation('featuredImage')?->items;
            $item = $items?->firstWhere('type', 'thumbnail') ?? $items?->firstWhere('type', 'original');

            return [$p->id => $item ? asset('storage/' . $item->path) : null];
        });

        $comboOptions = collect();
        if ($this->comboSearch !== '') {
            $comboOptions = Combo::where('name', 'like', "%{$this->comboSearch}%")
                ->orWhereHas('customer', fn ($c) => $c->where('full_name', 'like', "%{$this->comboSearch}%"))
                ->limit(10)
                ->get();
        }

        $productIds = collect($this->items)->where('kind', 'product')->pluck('product_id')->filter()->unique();
        $variantOptions = ProductVariant::active()
            ->whereIn('product_id', $productIds)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('product_id');

        return view('livewire.admin.sales.order-create', [
            'customerOptions'    => $customerOptions,
            'selectedCustomer'   => $selectedCustomer,
            'customerAddresses'  => $customerAddresses,
            'productOptions'     => $productOptions,
            'productImages'      => $productImages,
            'comboOptions'       => $comboOptions,
            'variantOptions'     => $variantOptions,
            'statuses'           => OrderStatus::cases(),
            'paymentStatuses'    => PaymentStatus::cases(),
            'fulfillmentStatuses' => FulfillmentStatus::cases(),
            'sources'            => OrderSource::cases(),
        ])->layout('layouts.admin.admin');
    }
}
