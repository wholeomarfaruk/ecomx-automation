<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use App\Support\AccountsAccess;
use Carbon\Carbon;

/** Printable payslips for a posted payroll — all employees, or one. */
class PayslipController extends Controller
{
    public function __invoke(string $month, ?int $item = null)
    {
        AccountsAccess::authorize('view');

        $run = PayrollRun::with(['items' => fn ($q) => $q->where('include', true)->when($item, fn ($w) => $w->whereKey($item))->orderBy('id'), 'items.profile.roles'])
            ->whereDate('month', Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString())->firstOrFail();
        abort_if($run->isDraft(), 404, 'This payroll is still a draft.');
        abort_if($run->items->isEmpty(), 404);

        return view('accounts.payslips', ['run' => $run]);
    }
}
