<?php

namespace App\Livewire\Admin\Accounts\Reports;

use App\Models\Account;
use Livewire\Component;

/**
 * Case 6.3 — "সব fee এক জায়গায়": every account tagged subtype=fee
 * (Transfer/Bank Charge, Gateway Fee, Conversion Fee, plus courier return/
 * settlement fees which post into the courier expense account) rolled up
 * into one view, so hidden costs are visible in one place.
 */
class FeeReport extends Component
{
    public function render(): mixed
    {
        $feeAccounts = Account::where('subtype', 'fee')->get()->map(fn ($a) => [
            'account' => $a,
            'amount'  => $a->balance(),
        ]);

        return view('livewire.admin.accounts.reports.fee-report', [
            'feeAccounts' => $feeAccounts,
            'totalFees'   => $feeAccounts->sum('amount'),
        ])->layout('layouts.admin.admin');
    }
}
