<?php

namespace App\Livewire\Admin\Sales;

use App\Actions\Sales\PlaceBulkOrder;
use App\Enums\Product\ProductType;
use App\Enums\Sales\OrderSource;
use App\Enums\Sales\OrderStatus;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Livewire\Admin\Sales\Concerns\HandlesOrderIntake;
use App\Marketing\Services\MarketingEventService;
use App\Models\Account;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\Warehouse;
use App\OrderIntake\CustomerDirectory;
use App\OrderIntake\OrderIntakeService;
use App\Services\Shipping\ShippingCalculator;
use App\Services\StockService;
use App\Support\PhoneNumber;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\WithFileUploads;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options as XlsxOptions;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Sales → Orders → Bulk Order: a spreadsheet-style sheet where many orders
 * are typed or pasted from Excel at once. The grid itself (editing, paste
 * parsing, product/variant matching, totals) runs in the browser for speed;
 * this component only serves data — the catalogue, customer lookups by
 * phone, delivery quotes, Excel upload parsing — and places the orders,
 * one transaction per order through PlaceBulkOrder so one bad row never
 * blocks the rest. "AI Order" (HandlesOrderIntake) turns a pasted message,
 * chat or screenshots into rows on this same sheet.
 */
class BulkOrderCreate extends Component
{
    use HandlesOrderIntake;
    use WithFileUploads;

    /** Excel/CSV picked in "Upload Excel" — parsed by readUpload() and handed to the sheet. */
    public $sheetFile;

    public function mount(): void
    {
        $this->authorizeCreate();
    }

    protected function authorizeCreate(): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('superadmin') || $user?->can('order.create'), 403);
    }

    /**
     * Everything the sheet matches against, loaded once: active non-combo
     * products with their active variants (option values for variant
     * matching), selling prices and available stock.
     */
    #[Renderless]
    public function catalog(): array
    {
        $products = Product::active()
            ->where('product_type', '!=', ProductType::COMBO)
            ->with([
                'variants' => fn ($q) => $q->where('status', 'active')->orderBy('sort_order'),
                'variants.values.productAttributeValue.attributeValue.attribute',
            ])
            ->orderBy('name')
            ->get();

        $available = $this->availability($products);

        return $products->map(function (Product $p) use ($available) {
            $variants = $p->product_type === ProductType::VARIABLE
                ? $p->variants->map(function ($v) use ($p, $available) {
                    $options = $v->options_map;

                    return [
                        'id'     => $v->id,
                        'sku'    => (string) $v->sku,
                        'label'  => implode(' / ', $options) ?: (string) $v->sku,
                        'values' => array_values(array_map(fn ($o) => mb_strtolower((string) $o), $options)),
                        'price'  => $p->sellingPrice($v),
                        'stock'  => $available["{$p->id}:{$v->id}"] ?? 0.0,
                    ];
                })->values()->all()
                : [];

            return [
                'id'       => $p->id,
                'name'     => $p->name,
                'code'     => (string) $p->code,
                'variable' => $p->product_type === ProductType::VARIABLE,
                'price'    => $p->sellingPrice(),
                'stock'    => $p->product_type === ProductType::SIMPLE ? ($available["{$p->id}:"] ?? 0.0) : null,
                'image'    => $p->featured_image_id ? file_path($p->featured_image_id) : null,
                'variants' => $variants,
            ];
        })->values()->all();
    }

    /**
     * Available quantity per "product:variant" key in one query — same
     * figure as StockService::available() (default warehouse, quantity −
     * booked), or the items' own stock_quantity with Inventory off.
     *
     * @return array<string, float>
     */
    protected function availability($products): array
    {
        $map = [];

        if (app(StockService::class)->usesOwnStock()) {
            foreach ($products as $p) {
                $map["{$p->id}:"] = max(0.0, (float) $p->stock_quantity);
                foreach ($p->variants as $v) {
                    $map["{$p->id}:{$v->id}"] = max(0.0, (float) $v->stock_quantity);
                }
            }

            return $map;
        }

        InventoryStock::query()
            ->where('warehouse_id', Warehouse::default()->id)
            ->whereIn('product_id', $products->pluck('id'))
            ->get(['product_id', 'variant_id', 'quantity', 'booked_quantity'])
            ->each(function ($s) use (&$map) {
                $map["{$s->product_id}:{$s->variant_id}"] = max(0.0, (float) $s->quantity - (float) $s->booked_quantity);
            });

        return $map;
    }

    /**
     * Customers matching the sheet's phone numbers, keyed by the phone as
     * typed — with what the sheet shows next to them: saved name/address,
     * order history and any order placed in the last 24 hours (a likely
     * duplicate).
     *
     * @param  list<string>  $phones
     */
    #[Renderless]
    public function lookupCustomers(array $phones): array
    {
        return CustomerDirectory::lookup($phones);
    }

    /**
     * Delivery charges for sheet rows from Settings → Shipping, priced by
     * the storefront's ShippingCalculator (weight, bands, free-over, …).
     *
     * @param  list<array{key: string, method_id: int, subtotal: float, items: list<array{product_id: int, quantity: float}>}>  $requests
     * @return array<string, float>
     */
    #[Renderless]
    public function quoteShipping(array $requests): array
    {
        $requests = array_slice($requests, 0, 500);
        $methods = ShippingMethod::with('zone')->where('is_active', true)
            ->whereIn('id', collect($requests)->pluck('method_id')->filter()->unique())->get()->keyBy('id');
        $products = Product::whereIn('id', collect($requests)->pluck('items')->flatten(1)->pluck('product_id')->filter()->unique())
            ->get()->keyBy('id');
        $calculator = app(ShippingCalculator::class);

        $quotes = [];

        foreach ($requests as $r) {
            $method = $methods->get($r['method_id'] ?? 0);

            if (! $method || ! isset($r['key'])) {
                continue;
            }

            $lines = collect($r['items'] ?? [])
                ->map(fn ($i) => ['product' => $products->get($i['product_id'] ?? 0), 'quantity' => (float) ($i['quantity'] ?? 0), 'is_gift' => false])
                ->filter(fn ($l) => $l['product'] && $l['quantity'] > 0)
                ->values()->all();

            $quotes[$r['key']] = $calculator->quote($method, $lines, (float) ($r['subtotal'] ?? 0))->amount;
        }

        return $quotes;
    }

    /**
     * Places a chunk of orders from the sheet. Returns one result per order
     * key — the created order, or why it failed — so the sheet can mark each
     * row. Each order is its own transaction.
     *
     * @param  list<array<string, mixed>>  $orders
     * @param  array<string, mixed>  $settings
     */
    #[Renderless]
    public function submit(array $orders, array $settings): array
    {
        $this->authorizeCreate();

        validator($settings, [
            'status'      => ['required', Rule::in([OrderStatus::PENDING->value, OrderStatus::CONFIRMED->value])],
            'batch'       => 'required|string|max:40',
            'admin_note'  => 'nullable|string|max:1000',
            'advance_account_id' => 'nullable|integer|exists:accounts,id',
            'advance_method'     => 'nullable|string|max:20',
            'send_capi'          => 'nullable|boolean',
        ])->validate();

        $sendCapi = (bool) ($settings['send_capi'] ?? false);

        $sources = array_column(OrderSource::cases(), 'value');
        $results = [];
        $placed = 0;

        foreach (array_slice($orders, 0, 50) as $o) {
            $key = (string) ($o['key'] ?? '');

            $intakeIds = collect($o['intake_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->take(10)->values()->all();

            try {
                $source = in_array($o['source'] ?? '', $sources, true) ? $o['source'] : OrderSource::ADMIN->value;

                $order = app(PlaceBulkOrder::class)->handle([
                    'phone'              => (string) ($o['phone'] ?? ''),
                    'name'               => isset($o['name']) ? mb_substr((string) $o['name'], 0, 255) : null,
                    'address'            => isset($o['address']) ? mb_substr((string) $o['address'], 0, 1000) : null,
                    'items'              => collect($o['items'] ?? [])->take(100)->map(fn ($i) => [
                        'product_id' => (int) ($i['product_id'] ?? 0),
                        'variant_id' => isset($i['variant_id']) ? (int) $i['variant_id'] : null,
                        'quantity'   => (float) ($i['quantity'] ?? 0),
                        'unit_price' => isset($i['unit_price']) && is_numeric($i['unit_price']) ? (float) $i['unit_price'] : null,
                    ])->all(),
                    'shipping_method_id' => ! empty($o['method_id']) ? (int) $o['method_id'] : null,
                    'delivery_charge'    => is_numeric($o['delivery'] ?? null) ? (float) $o['delivery'] : 0.0,
                    'delivery_manual'    => (bool) ($o['delivery_manual'] ?? false),
                    'discount'           => is_numeric($o['discount'] ?? null) ? (float) $o['discount'] : 0.0,
                    'advance'            => is_numeric($o['advance'] ?? null) ? (float) $o['advance'] : 0.0,
                    'advance_account_id' => ! empty($settings['advance_account_id']) ? (int) $settings['advance_account_id'] : null,
                    'advance_method'     => $settings['advance_method'] ?? null,
                    'source'             => $source,
                    'status'             => $settings['status'],
                    'customer_note'      => isset($o['note']) ? mb_substr((string) $o['note'], 0, 1000) : null,
                    'admin_note'         => trim("Bulk order {$settings['batch']}" . ($intakeIds ? ' · AI Order #' . implode(', #', $intakeIds) : '') . "\n" . ($settings['admin_note'] ?? '')),
                ]);

                $placed++;

                foreach ($intakeIds as $intakeId) {
                    app(OrderIntakeService::class)->recordPlaced($intakeId, $order->id);
                }

                activity('sales')
                    ->causedBy(auth()->user())
                    ->performedOn($order)
                    ->withProperties(['batch' => $settings['batch']])
                    ->event('created')
                    ->log("Order #{$order->id} was created from bulk order {$settings['batch']}");

                // After the order is committed, and never allowed to fail
                // the row — the result shows on the order's timeline.
                if ($sendCapi) {
                    try {
                        app(MarketingEventService::class)->sendPurchaseFromAdmin($order);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }

                $results[$key] = [
                    'ok'    => true,
                    'id'    => $order->id,
                    'total' => (float) $order->total_amount,
                    'url'   => route('admin.sales.orders.show', $order->id),
                ];
            } catch (InvalidArgumentException|InsufficientStockException $e) {
                $results[$key] = ['ok' => false, 'error' => $e->getMessage()];
            } catch (\Throwable $e) {
                report($e);
                $results[$key] = ['ok' => false, 'error' => 'Could not place this order: ' . $e->getMessage()];
            }
        }

        return ['results' => $results, 'placed' => $placed];
    }

    /**
     * Reads the uploaded .xlsx/.csv into a plain grid of strings (first
     * sheet, up to 1000 rows × 30 columns) for the sheet to import the same
     * way as a paste — header row detection and column mapping happen there.
     */
    #[Renderless]
    public function readUpload(): array
    {
        $this->validate(['sheetFile' => 'required|file|max:5120|mimes:xlsx,csv,txt']);

        $path = $this->sheetFile->getRealPath();
        $ext = strtolower($this->sheetFile->getClientOriginalExtension());
        $reader = $ext === 'xlsx' ? new XlsxReader() : new CsvReader();
        $grid = [];

        try {
            $reader->open($path);

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $cells = array_map(function ($v) {
                        if ($v instanceof \DateTimeInterface) {
                            return $v->format('Y-m-d');
                        }
                        if (is_float($v) && floor($v) == $v && abs($v) < 1e15) {
                            return (string) (int) $v;
                        }

                        return trim((string) $v);
                    }, array_slice($row->toArray(), 0, 30));

                    if (array_filter($cells, fn ($c) => $c !== '') !== []) {
                        $grid[] = $cells;
                    }

                    if (count($grid) >= 1000) {
                        break;
                    }
                }

                break; // first sheet only
            }
        } finally {
            $reader->close();
            $this->sheetFile = null;
        }

        return $grid;
    }

    /** Blank .xlsx with the sheet's columns and two example rows from the real catalogue. */
    public function downloadTemplate(): BinaryFileResponse
    {
        $examples = Product::active()->where('product_type', '!=', ProductType::COMBO)
            ->with(['variants' => fn ($q) => $q->where('status', 'active')->orderBy('sort_order')])
            ->orderByDesc('id')->limit(2)->get();

        $productText = fn ($p, $qty) => $p
            ? (($p->product_type === ProductType::VARIABLE && $p->variants->first()?->sku) ? $p->variants->first()->sku : $p->code) . " x{$qty}"
            : 'SKU-001 x1';

        $headers = ['Phone', 'Customer Name', 'Address', 'Products', 'Qty', 'Price', 'Delivery Zone', 'Delivery Charge', 'Discount', 'Advance', 'Source', 'Note'];
        $rows = [
            ['01712345678', 'Rahim Uddin', 'House 12, Road 5, Dhanmondi, Dhaka', $productText($examples->get(0), 1), '', '', '', '', '', '', 'messenger', 'Call before delivery'],
            ['01812345678', 'Karim Ahmed', 'Sadar Road, Cumilla', $productText($examples->get(0), 2) . ', ' . $productText($examples->get(1) ?? $examples->get(0), 1), '', '', '', '', '100', '200', 'whatsapp', ''],
        ];

        $options = new XlsxOptions();
        $options->setColumnWidth(15, 1);
        $options->setColumnWidth(20, 2);
        $options->setColumnWidth(40, 3);
        $options->setColumnWidth(36, 4);
        $options->setColumnWidthForRange(10, 5, 12);

        $path = tempnam(sys_get_temp_dir(), 'bulk') . '.xlsx';
        $writer = new XlsxWriter($options);
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName('Bulk Orders');
        $writer->getCurrentSheet()->setSheetView((new SheetView())->withFreezeRow(2));
        $writer->addRow(Row::fromValuesWithStyle($headers, new Style(fontBold: true, backgroundColor: 'E5E7EB')));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return response()->download($path, 'bulk-order-template.xlsx')->deleteFileAfterSend();
    }

    public function render(): mixed
    {
        $accountsOn = (bool) Setting::get('accounts_enabled', true, 'modules');

        $methods = ShippingMethod::query()
            ->where('is_active', true)
            ->whereHas('zone', fn ($q) => $q->where('is_active', true))
            ->with('zone')
            ->get()
            ->sortBy(fn ($m) => [$m->zone->sort_order, $m->zone->id, $m->sort_order, $m->id])
            ->values();

        $defaultMethod = $methods->first(fn ($m) => $m->zone->is_default) ?? $methods->first();

        return view('livewire.admin.sales.bulk-order-create', [
            'config' => [
                'userId'          => auth()->id(),
                'phoneCode'       => ltrim(PhoneNumber::normalize('0')['country_code'], '+'),
                'accountsOn'      => $accountsOn,
                'defaultMethodId' => $defaultMethod?->id,
                'methods'         => $methods->map(fn ($m) => [
                    'id'    => $m->id,
                    'label' => $m->zone->name . ($methods->where('shipping_zone_id', $m->shipping_zone_id)->count() > 1 ? " — {$m->name}" : ''),
                    'zone'  => mb_strtolower($m->zone->name),
                ])->all(),
                'sources'  => collect(OrderSource::cases())->reject(fn ($s) => $s === OrderSource::POS)
                    ->map(fn ($s) => ['value' => $s->value, 'label' => $s->label()])->values()->all(),
                'accounts' => $accountsOn
                    ? Account::active()->whereIn('subtype', ['cash', 'bank', 'mobile_banking'])->orderBy('code')->get(['id', 'name', 'code'])
                        ->map(fn ($a) => ['id' => $a->id, 'label' => "{$a->code} · {$a->name}"])->all()
                    : [],
                'ordersUrl' => route('admin.sales.orders'),
                'intake'    => $this->intakeConfig(),
            ],
        ])->layout('layouts.admin.admin');
    }
}
