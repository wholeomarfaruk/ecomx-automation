<?php

namespace App\Livewire\Traits;

use App\Actions\Accounts\PostJournalEntry;
use App\Actions\Accounts\PostSupplierAdvance;
use App\Actions\Accounts\PostSupplierBill;
use App\Actions\Accounts\PostSupplierPayment;
use App\Enums\Accounts\TransactionType;
use App\Enums\Purchase\SupplierInvoiceType;
use App\Exceptions\Accounts\DuplicateJournalEntryException;
use App\Exceptions\Purchase\SupplierInvoiceDeletionException;
use App\Models\Account;
use App\Models\AccountsSupplierBill;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The full create/edit Supplier Invoice modal — type selector, item picker
 * (variant or simple product, with optional PO auto-fill), manual-amount
 * fallback for advance/payment, documents, and the Accounts postings each
 * type triggers. Shared by SupplierLedger (a fixed supplier from the route)
 * and SupplierInvoices (an admin-picked supplier in the modal) so the two
 * pages can never drift out of sync with each other again — implement
 * invoiceSupplierId() to say which supplier the modal is currently acting
 * on; everything else is identical between the two host components.
 */
trait HandlesSupplierInvoiceModal
{
    // create/edit invoice modal
    public bool    $invoiceModal      = false;
    public ?int    $editingInvoiceId  = null;
    public bool    $editingIsLocked   = false;
    public string  $invoiceType       = 'purchase';
    public string  $invoiceNumber     = '';
    public string  $invoiceDate       = '';
    public bool    $invoiceIsAdjusted = false;
    public string  $invoiceNotes      = '';

    // Which cash/bank account an "advance" or "payment" moved through.
    public string $advanceCashAccountId = '';
    public string $paymentCashAccountId = '';

    // PO-based auto-fill (optional — a PO of the current supplier can be
    // selected to pull its line items in, fully editable afterwards)
    public string $purchaseOrderId = '';

    /**
     * "Payment" type only: which of this supplier's open AccountsSupplierBill
     * rows to apply the payment against, as billId => bool (checkbox state).
     * Left empty applies oldest-open-bill-first automatically.
     *
     * @var array<int, bool>
     */
    public array $paymentBills = [];

    // "Purchase" type only: optionally record a payment for this bill the
    // moment it's created, instead of a separate trip to Accounts > Payables.
    public bool   $markPaidNow          = false;
    public string $payNowAmount         = '';
    public string $payNowCashAccountId  = '';

    /** @var array<int, int> File IDs attached to the invoice being created/edited. */
    public array $documentIds = [];

    /**
     * Each item's `variant_id` is a "picker key": "v:{id}" for a specific
     * ProductVariant or "p:{id}" for a simple product (no variant row of its
     * own) — same convention as PurchaseOrderForm's line items, so one
     * searchable picker can offer both. `purchase_order_item_id` is set only
     * when this line came from (or is linked to) a PO line, so "invoiced so
     * far" can be tracked per PO item.
     *
     * @var array<int, array{variant_id: string, purchase_order_item_id: string, name: string, quantity: string, unit_price: string, amount: string}>
     */
    public array $items = [];

    public string $productSearch = '';
    public string $manualAmount  = '';

    /**
     * Which supplier the modal is currently creating/editing an invoice
     * for — a fixed value on a per-supplier ledger page, or the admin's
     * current pick on a cross-supplier invoice list.
     */
    abstract protected function invoiceSupplierId(): ?int;

    /**
     * Extra property names (beyond the trait's own) the host wants reset
     * when the modal opens/closes — e.g. a "modalSupplierId" picker on a
     * cross-supplier page. Override to add any; empty by default.
     */
    protected function extraInvoiceModalResetKeys(): array
    {
        return [];
    }

    public function openInvoiceModal(): void
    {
        $this->reset([
            'editingInvoiceId', 'editingIsLocked', 'invoiceType', 'invoiceNumber', 'invoiceIsAdjusted',
            'invoiceNotes', 'purchaseOrderId', 'items', 'productSearch', 'manualAmount', 'documentIds',
            'advanceCashAccountId', 'paymentCashAccountId', 'paymentBills',
            'markPaidNow', 'payNowAmount', 'payNowCashAccountId', ...$this->extraInvoiceModalResetKeys(),
        ]);
        $this->invoiceType = 'purchase';
        $this->invoiceDate = now()->format('Y-m-d');
        $this->resetValidation();
        $this->invoiceModal = true;
    }

    public function openEditInvoiceModal(int $id): void
    {
        $invoice = SupplierInvoice::with('items')->findOrFail($id);

        $this->reset(['items', 'productSearch', 'manualAmount', 'purchaseOrderId', 'advanceCashAccountId', 'paymentCashAccountId', 'paymentBills']);

        $this->editingInvoiceId  = $invoice->id;
        // A "payment" invoice's amount is already applied against specific
        // bills via PostSupplierPayment — allocations can't be safely
        // re-run on edit, so it's always metadata-only here regardless of
        // serial position (see updateInvoice()).
        $this->editingIsLocked   = ! $invoice->isLatestSerial() || $invoice->type === SupplierInvoiceType::PAYMENT;
        $this->invoiceType       = $invoice->type->value;
        $this->invoiceNumber     = $invoice->invoice_number ?? '';
        $this->invoiceDate       = $invoice->invoice_date?->format('Y-m-d') ?? '';
        $this->invoiceIsAdjusted = $invoice->is_adjusted;
        $this->invoiceNotes      = $invoice->notes ?? '';
        $this->manualAmount      = (string) $invoice->amount;
        $this->documentIds       = $invoice->document_ids ?? [];
        $this->items             = $invoice->items->map(fn ($item) => [
            'variant_id'             => $item->product_variant_id ? "v:{$item->product_variant_id}" : '',
            'purchase_order_item_id' => $item->purchase_order_item_id ? (string) $item->purchase_order_item_id : '',
            'name'                   => $item->name,
            'quantity'               => $item->quantity !== null ? (string) $item->quantity : '',
            'unit_price'             => $item->unit_price !== null ? (string) $item->unit_price : '',
            'amount'                 => (string) $item->amount,
        ])->all();

        $this->afterOpenEditInvoiceModal($invoice);

        $this->resetValidation();
        $this->invoiceModal = true;
    }

    /**
     * Hook for a host to pick up extra per-invoice state when opening the
     * edit modal (e.g. setting its own supplier picker to this invoice's
     * supplier_id). No-op by default.
     */
    protected function afterOpenEditInvoiceModal(SupplierInvoice $invoice): void
    {
        //
    }

    public function closeInvoiceModal(): void
    {
        $this->invoiceModal = false;
    }

    public function updatedInvoiceType(): void
    {
        if (! in_array($this->invoiceType, [SupplierInvoiceType::PURCHASE->value, SupplierInvoiceType::RETURN->value])) {
            $this->items = [];
            $this->purchaseOrderId = '';
            $this->markPaidNow = false;
        }

        if ($this->invoiceType !== SupplierInvoiceType::PAYMENT->value) {
            $this->paymentBills = [];
        }
    }

    /**
     * Defaults the pay-now amount to the invoice's own total the moment the
     * checkbox is ticked, so the common case (paying the whole bill) needs
     * no typing — still fully editable for a partial payment.
     */
    public function updatedMarkPaidNow(): void
    {
        if ($this->markPaidNow && $this->payNowAmount === '') {
            $this->payNowAmount = (string) $this->itemsTotal;
        }
    }

    /**
     * Pulls in every line item of the selected purchase order as editable
     * invoice lines — quantity/unit price/amount all pre-filled from the PO,
     * but the admin can still add, remove, or change anything afterwards.
     * Re-selecting a PO (or clearing it) replaces any PO-sourced lines
     * already on the form without touching manually-added ones.
     */
    public function updatedPurchaseOrderId(): void
    {
        $this->items = collect($this->items)->filter(fn ($item) => $item['purchase_order_item_id'] === '')->values()->all();

        if ($this->purchaseOrderId === '') {
            return;
        }

        $order = PurchaseOrder::with('items.product', 'items.variant')->find($this->purchaseOrderId);

        if (! $order) {
            $this->purchaseOrderId = '';
            return;
        }

        $this->invoiceType = SupplierInvoiceType::PURCHASE->value;

        foreach ($order->items as $poItem) {
            $labels = $poItem->variant
                ? $poItem->variant->values->map(fn ($v) => $v->productAttributeValue->attributeValue->value ?? '')->filter()->implode(' / ')
                : '';

            $name = trim(($poItem->product->name ?? 'Unknown product') . ($labels ? " ({$labels})" : '') . ($poItem->variant ? " [{$poItem->variant->sku}]" : ''));

            $quantity  = (float) $poItem->quantity;
            $unitPrice = $poItem->unit_price !== null ? (float) $poItem->unit_price : null;
            $amount    = $unitPrice !== null ? round($quantity * $unitPrice, 2) : (float) $poItem->total_amount;

            $this->items[] = [
                'variant_id'             => $poItem->product_variant_id ? "v:{$poItem->product_variant_id}" : "p:{$poItem->product_id}",
                'purchase_order_item_id' => (string) $poItem->id,
                'name'                   => $name,
                'quantity'               => (string) $quantity,
                'unit_price'             => $unitPrice !== null ? (string) $unitPrice : '',
                'amount'                 => $amount > 0 ? (string) $amount : '',
            ];
        }
    }

    public function addItem(?string $key = null): void
    {
        $name = '';
        $unitPrice = '';

        if ($key) {
            [$type, $id] = explode(':', $key, 2) + [null, null];

            if ($type === 'v') {
                $variant = ProductVariant::with('values.productAttributeValue.attributeValue', 'product')->find((int) $id);
                if ($variant) {
                    $labels = $variant->values->map(fn ($v) => $v->productAttributeValue->attributeValue->value)->implode(' / ');
                    $name = trim($variant->product->name . ($labels ? " ({$labels})" : '') . " [{$variant->sku}]");
                    $unitPrice = $variant->purchase_price !== null ? (string) $variant->purchase_price : '';
                }
            } elseif ($type === 'p') {
                $product = Product::find((int) $id);
                if ($product) {
                    $name = trim("{$product->name} [{$product->code}]");
                    $unitPrice = $product->purchase_price !== null ? (string) $product->purchase_price : '';
                }
            }
        }

        $this->items[] = [
            'variant_id'             => $key ?? '',
            'purchase_order_item_id' => '',
            'name'                   => $name,
            'quantity'               => '1',
            'unit_price'             => $unitPrice,
            'amount'                 => '',
        ];

        $this->productSearch = '';
        $this->recalculateItemAmount(count($this->items) - 1);
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedItems($value, $key): void
    {
        [$index, $field] = explode('.', $key) + [null, null];

        if (in_array($field, ['quantity', 'unit_price']) && $index !== null) {
            $this->recalculateItemAmount((int) $index);
        }
    }

    protected function recalculateItemAmount(int $index): void
    {
        $qty   = (float) ($this->items[$index]['quantity'] ?? 0);
        $price = (float) ($this->items[$index]['unit_price'] ?? 0);

        if ($qty > 0 && $price > 0) {
            $this->items[$index]['amount'] = (string) round($qty * $price, 2);
        }
    }

    public function getItemsTotalProperty(): float
    {
        return collect($this->items)->sum(fn ($item) => (float) ($item['amount'] ?? 0));
    }

    public function saveInvoice(): void
    {
        if ($this->editingInvoiceId) {
            $this->updateInvoice();
            return;
        }

        $this->createInvoice();
    }

    /**
     * Resolves one item row's "v:{id}"/"p:{id}" picker key into a
     * product_variant_id for saving (null for a simple product — there is no
     * product_id column on supplier_invoice_items yet, so a simple-product
     * line is identified only by its free-text `name`).
     */
    protected function resolveVariantId(string $key): ?int
    {
        [$type, $id] = explode(':', $key, 2) + [null, null];

        return $type === 'v' && $id ? (int) $id : null;
    }

    protected function itemRules(): array
    {
        return [
            'items'               => 'required|array|min:1',
            'items.*.name'        => 'required|string|max:255',
            'items.*.quantity'    => 'nullable|numeric|min:0',
            'items.*.unit_price'  => 'nullable|numeric|min:0',
            'items.*.amount'      => 'required|numeric|min:0.01',
        ];
    }

    /**
     * Extra validation rules the host wants applied before create/update
     * (e.g. requiring its own supplier picker). Empty by default.
     */
    protected function extraInvoiceValidationRules(): array
    {
        return [];
    }

    protected function createInvoice(): void
    {
        if ($this->invoiceType === SupplierInvoiceType::PAYMENT->value) {
            $this->createPaymentInvoice();
            return;
        }

        $supplier = Supplier::findOrFail($this->invoiceSupplierId() ?: 0);

        $usesItems = in_array($this->invoiceType, [SupplierInvoiceType::PURCHASE->value, SupplierInvoiceType::RETURN->value]);

        $rules = [
            ...$this->extraInvoiceValidationRules(),
            'invoiceType'   => ['required', Rule::enum(SupplierInvoiceType::class)],
            'invoiceNumber' => 'nullable|string|max:100',
            'invoiceDate'   => 'nullable|date',
            'invoiceNotes'  => 'nullable|string',
        ];

        $rules = $usesItems ? [...$rules, ...$this->itemRules()] : [...$rules, 'manualAmount' => 'required|numeric|min:0.01'];

        if ($this->invoiceType === SupplierInvoiceType::ADVANCE->value) {
            $rules['advanceCashAccountId'] = 'required|exists:accounts,id';
        }

        if ($this->invoiceType === SupplierInvoiceType::PURCHASE->value && $this->markPaidNow) {
            $rules['payNowAmount']        = 'required|numeric|min:0.01';
            $rules['payNowCashAccountId'] = 'required|exists:accounts,id';
        }

        $this->validate($rules);

        $amount = $usesItems ? $this->itemsTotal : (float) $this->manualAmount;

        if ($amount <= 0) {
            $this->addError('items', 'Total amount must be greater than zero.');
            return;
        }

        $serial = ($supplier->invoices()->max('serial_number') ?? 0) + 1;

        $invoice = SupplierInvoice::create([
            'supplier_id'    => $supplier->id,
            'serial_number'  => $serial,
            'invoice_number' => $this->invoiceNumber ?: null,
            'type'           => $this->invoiceType,
            'amount'         => $amount,
            'is_adjusted'    => $this->invoiceIsAdjusted,
            'invoice_date'   => $this->invoiceDate ?: null,
            'notes'          => $this->invoiceNotes ?: null,
            'document_ids'   => $this->documentIds ?: null,
        ]);

        if ($usesItems) {
            foreach ($this->items as $item) {
                $invoice->items()->create([
                    'product_variant_id'     => $this->resolveVariantId($item['variant_id']),
                    'purchase_order_item_id' => $item['purchase_order_item_id'] ?: null,
                    'name'                   => $item['name'],
                    'quantity'               => $item['quantity'] !== '' ? $item['quantity'] : null,
                    'unit_price'             => $item['unit_price'] !== '' ? $item['unit_price'] : null,
                    'amount'                 => $item['amount'],
                ]);
            }
        }

        if ($invoice->type === SupplierInvoiceType::PURCHASE) {
            $bill = $this->postBillToAccounts($invoice);

            if ($this->markPaidNow && $bill) {
                $this->payBillNow($supplier, $bill);
            }
        } elseif ($invoice->type === SupplierInvoiceType::ADVANCE) {
            $this->postAdvanceToAccounts($invoice);
        }

        activity('purchase')
            ->causedBy(auth()->user())
            ->performedOn($invoice)
            ->withProperties(['type' => $invoice->type->value, 'amount' => $invoice->amount])
            ->event('created')
            ->log("Supplier invoice #{$invoice->serial_number} ({$invoice->type->label()}) recorded for \"{$supplier->name}\"");

        $this->invoiceModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Invoice recorded successfully']);
    }

    /**
     * "Payment" type never creates a plain SupplierInvoice directly — it
     * goes through PostSupplierPayment so the same call both allocates
     * against the chosen (or auto-picked) open bills AND records the
     * SupplierInvoice that keeps Supplier.balance in sync. This is the fix
     * for the two ledgers (Purchase module's Supplier.balance vs Accounts'
     * AccountsSupplierBill) silently drifting apart — previously a
     * "payment" here only touched Supplier.balance with no bill selection
     * and no journal entry at all.
     */
    protected function createPaymentInvoice(): void
    {
        $supplier = Supplier::findOrFail($this->invoiceSupplierId() ?: 0);

        $this->validate([
            ...$this->extraInvoiceValidationRules(),
            'invoiceNumber'        => 'nullable|string|max:100',
            'invoiceDate'          => 'nullable|date',
            'invoiceNotes'         => 'nullable|string',
            'manualAmount'         => 'required|numeric|min:0.01',
            'paymentCashAccountId' => 'required|exists:accounts,id',
        ]);

        $allocations = collect($this->paymentBills)
            ->filter()
            ->keys()
            ->mapWithKeys(function ($billId) use ($supplier) {
                $bill = AccountsSupplierBill::where('supplier_id', $supplier->id)->find($billId);
                return $bill ? [$billId => $bill->amountDue()] : [];
            })
            ->all();

        app(PostSupplierPayment::class)->handle(
            supplier: $supplier,
            payableAccountId: $this->accountId('2100'),
            cashAccountId: (int) $this->paymentCashAccountId,
            amount: (float) $this->manualAmount,
            entryDate: $this->invoiceDate ?: now()->toDateString(),
            allocations: $allocations,
            description: $this->invoiceNotes ?: null,
            invoiceNumber: $this->invoiceNumber ?: null,
        );

        activity('purchase')
            ->causedBy(auth()->user())
            ->withProperties(['type' => 'payment', 'amount' => $this->manualAmount])
            ->event('created')
            ->log("Payment of {$this->manualAmount} recorded for \"{$supplier->name}\"");

        $this->invoiceModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Payment recorded']);
    }

    /**
     * "Mark as paid now" on a fresh Purchase invoice — applies a payment to
     * the bill that was just posted, right after creating it, instead of a
     * separate trip to Accounts > Payables.
     */
    protected function payBillNow(Supplier $supplier, AccountsSupplierBill $bill): void
    {
        app(PostSupplierPayment::class)->handle(
            supplier: $supplier,
            payableAccountId: $this->accountId('2100'),
            cashAccountId: (int) $this->payNowCashAccountId,
            amount: (float) $this->payNowAmount,
            entryDate: $this->invoiceDate ?: now()->toDateString(),
            allocations: [$bill->id => min((float) $this->payNowAmount, $bill->amountDue())],
        );
    }

    /**
     * Only the latest invoice may have its type/amount/items changed —
     * every older invoice is locked to metadata-only edits (invoice
     * number, date, adjusted flag, notes, documents), since changing an
     * older invoice's amount would silently corrupt every running balance
     * and delta computed for invoices recorded after it.
     */
    protected function updateInvoice(): void
    {
        $invoice = SupplierInvoice::findOrFail($this->editingInvoiceId);

        // A "payment" invoice's amount is already applied to specific bills
        // via PostSupplierPayment — those allocations can't be safely
        // re-run on edit, so it never gets the full amount/items form
        // regardless of serial position.
        if ($invoice->isLatestSerial() && $invoice->type !== SupplierInvoiceType::PAYMENT) {
            $this->updateLatestInvoice($invoice);
            return;
        }

        $this->updateInvoiceMetadata($invoice);
    }

    protected function updateInvoiceMetadata(SupplierInvoice $invoice): void
    {
        $this->validate([
            'invoiceNumber' => 'nullable|string|max:100',
            'invoiceDate'   => 'nullable|date',
            'invoiceNotes'  => 'nullable|string',
        ]);

        $invoice->update([
            'invoice_number' => $this->invoiceNumber ?: null,
            'is_adjusted'    => $this->invoiceIsAdjusted,
            'invoice_date'   => $this->invoiceDate ?: null,
            'notes'          => $this->invoiceNotes ?: null,
            'document_ids'   => $this->documentIds ?: null,
        ]);

        activity('purchase')
            ->causedBy(auth()->user())
            ->performedOn($invoice)
            ->event('updated')
            ->log("Supplier invoice #{$invoice->serial_number} ({$invoice->type->label()}) updated for \"{$invoice->supplier->name}\"");

        $this->invoiceModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Invoice updated successfully']);
    }

    protected function updateLatestInvoice(SupplierInvoice $invoice): void
    {
        $usesItems = in_array($this->invoiceType, [SupplierInvoiceType::PURCHASE->value, SupplierInvoiceType::RETURN->value]);

        $rules = [
            'invoiceType'   => ['required', Rule::enum(SupplierInvoiceType::class)],
            'invoiceNumber' => 'nullable|string|max:100',
            'invoiceDate'   => 'nullable|date',
            'invoiceNotes'  => 'nullable|string',
        ];

        $rules = $usesItems ? [...$rules, ...$this->itemRules()] : [...$rules, 'manualAmount' => 'required|numeric|min:0.01'];

        $this->validate($rules);

        $amount = $usesItems ? $this->itemsTotal : (float) $this->manualAmount;

        if ($amount <= 0) {
            $this->addError('items', 'Total amount must be greater than zero.');
            return;
        }

        $originalType   = $invoice->type;
        $originalAmount = (float) $invoice->amount;

        $invoice->update([
            'invoice_number' => $this->invoiceNumber ?: null,
            'type'           => $this->invoiceType,
            'amount'         => $amount,
            'is_adjusted'    => $this->invoiceIsAdjusted,
            'invoice_date'   => $this->invoiceDate ?: null,
            'notes'          => $this->invoiceNotes ?: null,
            'document_ids'   => $this->documentIds ?: null,
        ]);

        $this->reconcileBillOnEdit($invoice, $originalType, $originalAmount);

        $invoice->items()->delete();

        if ($usesItems) {
            foreach ($this->items as $item) {
                $invoice->items()->create([
                    'product_variant_id'     => $this->resolveVariantId($item['variant_id']),
                    'purchase_order_item_id' => $item['purchase_order_item_id'] ?: null,
                    'name'                   => $item['name'],
                    'quantity'               => $item['quantity'] !== '' ? $item['quantity'] : null,
                    'unit_price'             => $item['unit_price'] !== '' ? $item['unit_price'] : null,
                    'amount'                 => $item['amount'],
                ]);
            }
        }

        activity('purchase')
            ->causedBy(auth()->user())
            ->performedOn($invoice)
            ->withProperties(['type' => $invoice->type->value, 'amount' => $invoice->amount])
            ->event('updated')
            ->log("Supplier invoice #{$invoice->serial_number} ({$invoice->type->label()}) updated for \"{$invoice->supplier->name}\"");

        $this->invoiceModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Invoice updated successfully']);
    }

    /**
     * Books the purchase (Dr Inventory / Cr Accounts Payable) and opens an
     * AccountsSupplierBill for payment allocation, linked to this real
     * SupplierInvoice — so bills seen under Accounts always trace back to
     * an actual purchase recorded here. Safely ignored if this invoice was
     * already posted (shouldn't happen on create, but keeps this idempotent
     * like the sales-side equivalent).
     */
    protected function postBillToAccounts(SupplierInvoice $invoice): ?AccountsSupplierBill
    {
        try {
            return app(PostSupplierBill::class)->handle(
                supplier: $invoice->supplier,
                inventoryAccountId: $this->accountId('1200'),
                payableAccountId: $this->accountId('2100'),
                amount: (float) $invoice->amount,
                entryDate: $invoice->invoice_date?->toDateString() ?? now()->toDateString(),
                supplierInvoiceId: $invoice->id,
                description: "Purchase invoice #{$invoice->serial_number} — {$invoice->supplier->name}",
            );
        } catch (DuplicateJournalEntryException) {
            // Already posted for this invoice — nothing to do.
            return AccountsSupplierBill::where('supplier_invoice_id', $invoice->id)->first();
        }
    }

    /**
     * Books an advance payment to the supplier (Dr Supplier Advance / Cr the
     * chosen cash/bank account) and opens an AccountsSupplierAdvance so it
     * can later be applied against a bill from Accounts > Payables. Safely
     * ignored if this invoice was already posted.
     */
    protected function postAdvanceToAccounts(SupplierInvoice $invoice): void
    {
        try {
            app(PostSupplierAdvance::class)->handle(
                supplier: $invoice->supplier,
                supplierAdvanceAccountId: $this->accountId('1150'),
                cashAccountId: (int) $this->advanceCashAccountId,
                amount: (float) $invoice->amount,
                entryDate: $invoice->invoice_date?->toDateString() ?? now()->toDateString(),
                supplierInvoiceId: $invoice->id,
                description: "Advance invoice #{$invoice->serial_number} — {$invoice->supplier->name}",
            );
        } catch (DuplicateJournalEntryException) {
            // Already posted for this invoice — nothing to do.
        }
    }

    protected function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id')
            ?? throw new \RuntimeException("Chart of accounts is missing account code {$code}.");
    }

    /**
     * Keeps the Accounts side in step when an already-posted purchase
     * invoice is edited. A posted journal entry is never mutated (Golden
     * Rule #2), so: type newly becomes "purchase" -> post the first bill;
     * amount changes on a bill that was already posted -> post a small
     * adjustment entry for the delta and correct the AccountsSupplierBill
     * total instead of re-posting the full amount. Switching a purchase
     * invoice to a non-purchase type is left untouched here — no schema
     * support for voiding a bill exists yet (status is open/partial/paid
     * only), so that edit still needs to be corrected by hand in Accounts.
     */
    protected function reconcileBillOnEdit(SupplierInvoice $invoice, SupplierInvoiceType $originalType, float $originalAmount): void
    {
        $wasPurchase = $originalType === SupplierInvoiceType::PURCHASE;
        $isPurchase  = $invoice->type === SupplierInvoiceType::PURCHASE;

        if (! $wasPurchase && $isPurchase) {
            $this->postBillToAccounts($invoice);
            return;
        }

        if (! $wasPurchase || ! $isPurchase) {
            return;
        }

        $bill = AccountsSupplierBill::where('supplier_invoice_id', $invoice->id)->first();

        if (! $bill) {
            $this->postBillToAccounts($invoice);
            return;
        }

        $delta = round((float) $invoice->amount - $originalAmount, 2);

        if (abs($delta) < 0.01) {
            return;
        }

        $this->postBillAdjustment($invoice, $bill, $delta);
    }

    protected function postBillAdjustment(SupplierInvoice $invoice, AccountsSupplierBill $bill, float $delta): void
    {
        DB::transaction(function () use ($invoice, $bill, $delta) {
            $inventoryAccountId = $this->accountId('1200');
            $payableAccountId   = $this->accountId('2100');
            $amount             = abs($delta);

            $lines = $delta > 0
                ? [
                    ['account_id' => $inventoryAccountId, 'debit' => $amount],
                    ['account_id' => $payableAccountId, 'credit' => $amount],
                ]
                : [
                    ['account_id' => $payableAccountId, 'debit' => $amount],
                    ['account_id' => $inventoryAccountId, 'credit' => $amount],
                ];

            app(PostJournalEntry::class)->handle([
                'entry_date'       => $invoice->invoice_date?->toDateString() ?? now()->toDateString(),
                'description'      => "Purchase invoice #{$invoice->serial_number} adjustment — {$invoice->supplier->name}",
                'transaction_type' => TransactionType::SUPPLIER_BILL->value,
            ], $lines);

            $bill->amount = round((float) $bill->amount + $delta, 2);
            $bill->status = $bill->amountDue() <= 0.01
                ? 'paid'
                : ((float) $bill->amount_allocated > 0 ? 'partial' : 'open');
            $bill->save();
        });
    }

    public function deleteInvoice(int $id): void
    {
        $invoice = SupplierInvoice::findOrFail($id);

        try {
            $invoice->delete();
        } catch (SupplierInvoiceDeletionException $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
            return;
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Invoice deleted']);
    }

    /**
     * View data the invoice modal's blade needs — purchase orders for the
     * given supplier (for PO auto-fill) and the product/variant picker
     * results for the current search, plus the cash/bank account list for
     * the advance field. Pass $supplierId explicitly (SupplierLedger has a
     * fixed one; SupplierInvoices passes its current modalSupplierId pick,
     * which can be empty before anything is chosen).
     */
    protected function invoiceModalViewData(?int $supplierId): array
    {
        $purchaseOrders = collect();
        if ($this->invoiceModal && $supplierId) {
            $purchaseOrders = PurchaseOrder::with('items')
                ->where('supplier_id', $supplierId)
                ->orderByDesc('id')
                ->get()
                ->map(function (PurchaseOrder $order) {
                    $order->invoiced_total = $order->items->sum(fn (PurchaseOrderItem $item) => $item->invoicedAmount());
                    return $order;
                });
        }

        $productOptions = collect();
        if ($this->invoiceModal && $this->productSearch !== '') {
            $variants = ProductVariant::with('product', 'values.productAttributeValue.attributeValue')
                ->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$this->productSearch}%"))
                ->orWhere('sku', 'like', "%{$this->productSearch}%")
                ->limit(10)
                ->get();

            $productOptions = $variants->mapWithKeys(function (ProductVariant $variant) {
                $labels = $variant->values->map(fn ($v) => $v->productAttributeValue->attributeValue->value)->implode(' / ');
                $label = trim(($variant->product->name ?? 'Unknown product') . ($labels ? " ({$labels})" : '') . " [{$variant->sku}]");
                return ["v:{$variant->id}" => $label];
            });

            $simpleProducts = Product::where('product_type', 'simple')
                ->whereDoesntHave('variants')
                ->where(fn ($q) => $q->where('name', 'like', "%{$this->productSearch}%")->orWhere('code', 'like', "%{$this->productSearch}%"))
                ->limit(10)
                ->get(['id', 'name', 'code']);

            $productOptions = $productOptions->union(
                $simpleProducts->mapWithKeys(fn (Product $product) => ["p:{$product->id}" => trim("{$product->name} [{$product->code}]")])
            );
        }

        $openBills = collect();
        if ($this->invoiceModal && $supplierId && $this->invoiceType === SupplierInvoiceType::PAYMENT->value) {
            $openBills = AccountsSupplierBill::where('supplier_id', $supplierId)
                ->whereIn('status', ['open', 'partial'])
                ->oldest()
                ->get();
        }

        return [
            'purchaseOrders' => $purchaseOrders,
            'productOptions' => $productOptions,
            'cashAccounts'   => Account::active()->whereIn('subtype', ['cash', 'bank', 'mobile_banking'])->orderBy('code')->get(),
            'openBills'      => $openBills,
        ];
    }
}
