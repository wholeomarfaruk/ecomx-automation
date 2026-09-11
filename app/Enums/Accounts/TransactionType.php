<?php

namespace App\Enums\Accounts;

/**
 * Classifies what kind of business event a journal entry represents, purely
 * for filtering/reporting/UI badges in the Transactions list — it has no
 * effect on posting logic, which always goes through PostJournalEntry.
 * Cases refer to docs/ecomX-accounts-cases.md.
 */
enum TransactionType: string
{
    case MANUAL_INCOME       = 'manual_income';       // Case 1.1
    case MANUAL_EXPENSE      = 'manual_expense';       // Case 1.2, 6.1
    case TRANSFER            = 'transfer';             // Case 1.3, 1.4
    case CURRENCY_CONVERSION = 'currency_conversion';  // Case 1.5
    case OPENING_BALANCE     = 'opening_balance';       // Case 9.1
    case REVERSAL            = 'reversal';              // Case 1.6
    case VOID                = 'void';                  // Case 1.7
    case SALE                = 'sale';                  // Case 3.1, 10.1, 12.1
    case COGS                = 'cogs';                  // Case 3.1
    case PAYMENT_RECEIPT     = 'payment_receipt';       // Case 3.2, 3.3, 3.4
    case SALES_RETURN        = 'sales_return';          // Case 3.6
    case REFUND              = 'refund';                // Case 3.7, 3.9
    case CREDIT_NOTE         = 'credit_note';           // Case 3.8
    case BAD_DEBT            = 'bad_debt';               // Case 3.10
    case SUPPLIER_BILL       = 'supplier_bill';         // Case 4.1
    case SUPPLIER_PAYMENT    = 'supplier_payment';      // Case 4.2, 4.3
    case PURCHASE_RETURN     = 'purchase_return';        // Case 4.4
    case SUPPLIER_ADVANCE    = 'supplier_advance';      // Case 4.5
    case LOAN_RECEIVED       = 'loan_received';         // Case 5.1
    case LOAN_REPAYMENT      = 'loan_repayment';         // Case 5.2
    case RECURRING_EXPENSE   = 'recurring_expense';      // Case 6.2
    case ASSET_PURCHASE      = 'asset_purchase';         // Case 7.1
    case DEPRECIATION        = 'depreciation';           // Case 7.2
    case ASSET_DISPOSAL      = 'asset_disposal';         // Case 7.3
    case OWNER_INVESTMENT    = 'owner_investment';       // Case 8.1, 8.2
    case OWNER_WITHDRAWAL    = 'owner_withdrawal';       // Case 8.3
    case COD_SETTLEMENT      = 'cod_settlement';          // Case 10.2
    case GATEWAY_SETTLEMENT  = 'gateway_settlement';      // Case 11.1
    case VAT_SALE            = 'vat_sale';                 // Case 12.1
    case BANK_RECONCILIATION = 'bank_reconciliation';    // Case 2.2

    public function label(): string
    {
        return match ($this) {
            self::MANUAL_INCOME       => 'Manual Income',
            self::MANUAL_EXPENSE      => 'Manual Expense',
            self::TRANSFER            => 'Transfer',
            self::CURRENCY_CONVERSION => 'Currency Conversion',
            self::OPENING_BALANCE     => 'Opening Balance',
            self::REVERSAL            => 'Reversal',
            self::VOID                => 'Void',
            self::SALE                => 'Sale',
            self::COGS                => 'Cost of Goods Sold',
            self::PAYMENT_RECEIPT     => 'Payment Receipt',
            self::SALES_RETURN        => 'Sales Return',
            self::REFUND              => 'Refund',
            self::CREDIT_NOTE         => 'Customer Credit',
            self::BAD_DEBT            => 'Bad Debt Write-off',
            self::SUPPLIER_BILL       => 'Supplier Bill',
            self::SUPPLIER_PAYMENT    => 'Supplier Payment',
            self::PURCHASE_RETURN     => 'Purchase Return',
            self::SUPPLIER_ADVANCE    => 'Supplier Advance',
            self::LOAN_RECEIVED       => 'Loan Received',
            self::LOAN_REPAYMENT      => 'Loan Repayment',
            self::RECURRING_EXPENSE   => 'Recurring Expense',
            self::ASSET_PURCHASE      => 'Asset Purchase',
            self::DEPRECIATION        => 'Depreciation',
            self::ASSET_DISPOSAL      => 'Asset Disposal',
            self::OWNER_INVESTMENT    => 'Owner Investment',
            self::OWNER_WITHDRAWAL    => 'Owner Withdrawal',
            self::COD_SETTLEMENT      => 'Courier COD Settlement',
            self::GATEWAY_SETTLEMENT  => 'Gateway Settlement',
            self::VAT_SALE            => 'VAT Sale',
            self::BANK_RECONCILIATION => 'Bank Reconciliation',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::MANUAL_INCOME, self::SALE, self::PAYMENT_RECEIPT,
            self::LOAN_RECEIVED, self::OWNER_INVESTMENT, self::VAT_SALE => 'bg-emerald-50 text-emerald-600',

            self::MANUAL_EXPENSE, self::COGS, self::SUPPLIER_PAYMENT,
            self::LOAN_REPAYMENT, self::RECURRING_EXPENSE, self::DEPRECIATION,
            self::OWNER_WITHDRAWAL, self::BAD_DEBT => 'bg-orange-50 text-orange-600',

            self::TRANSFER, self::CURRENCY_CONVERSION, self::COD_SETTLEMENT,
            self::GATEWAY_SETTLEMENT, self::BANK_RECONCILIATION => 'bg-blue-50 text-blue-600',

            self::SALES_RETURN, self::REFUND, self::PURCHASE_RETURN,
            self::VOID, self::REVERSAL => 'bg-red-50 text-red-500',

            self::CREDIT_NOTE, self::SUPPLIER_ADVANCE => 'bg-indigo-50 text-indigo-600',

            self::SUPPLIER_BILL, self::ASSET_PURCHASE, self::ASSET_DISPOSAL,
            self::OPENING_BALANCE => 'bg-gray-100 text-gray-600',
        };
    }
}
