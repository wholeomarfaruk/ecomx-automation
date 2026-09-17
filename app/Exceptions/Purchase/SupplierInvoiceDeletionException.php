<?php

namespace App\Exceptions\Purchase;

use RuntimeException;

class SupplierInvoiceDeletionException extends RuntimeException
{
    public static function linkedToPurchaseOrder(): self
    {
        return new self('Cannot delete an invoice linked to a purchase order.');
    }

    public static function notLatestSerial(): self
    {
        return new self('Only the most recent invoice can be deleted. Delete newer invoices first.');
    }

    public static function isPaymentWithAllocations(): self
    {
        return new self('This payment is already applied to one or more bills in Accounts and cannot be deleted here. Reverse it from Accounts > Payables instead.');
    }
}
