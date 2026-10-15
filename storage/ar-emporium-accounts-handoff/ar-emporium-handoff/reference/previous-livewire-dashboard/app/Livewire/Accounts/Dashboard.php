<?php

namespace App\Livewire\Accounts;

use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Accounts Dashboard (হিসাবের সারাংশ)
 *
 * Every data method below returns SAMPLE data so the page renders right away.
 * Replace the body of each method with your real queries — the return shape
 * is what the Blade view expects, so keep the array keys the same.
 * All money values are plain numbers (no formatting); the view formats them with money().
 */
#[Title('Accounts Home')]
class Dashboard extends Component
{
    public const PERIODS = [
        'today'     => 'Today',
        'yesterday' => 'Yesterday',
        'week'      => 'This week',
        'month'     => 'This month',
        'year'      => 'This year',
        'custom'    => 'Custom',
    ];

    public const FREQUENCIES = ['all', 'monthly', 'weekly', 'daily', 'yearly', 'custom'];

    #[Url(as: 'period')]
    public string $period = 'today';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    /** EMI table frequency filter */
    public string $freq = 'all';

    /** Selected voucher ids in "Posting Pending" */
    public array $selected = [];

    /* ------------------------------------------------------------------
     |  Actions
     * ------------------------------------------------------------------ */

    public function setPeriod(string $period): void
    {
        if (! array_key_exists($period, self::PERIODS)) {
            return;
        }

        $this->period = $period;

        if ($period === 'custom' && (! $this->from || ! $this->to)) {
            $this->from = now()->startOfMonth()->toDateString();
            $this->to   = now()->toDateString();
        }
    }

    public function applyCustom(): void
    {
        $this->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $this->period = 'custom';
    }

    public function setFreq(string $freq): void
    {
        if (in_array($freq, self::FREQUENCIES, true)) {
            $this->freq = $freq;
        }
    }

    public function toggleAllPending(): void
    {
        $ids = collect($this->pendingVouchers)->pluck('id')->all();

        $this->selected = count($this->selected) === count($ids) ? [] : $ids;
    }

    public function postVoucher(int $id): void
    {
        // TODO: Voucher::findOrFail($id)->post(auth()->user());
        $this->selected = array_values(array_diff($this->selected, [$id]));

        session()->flash('accounts.status', "Voucher #{$id} posted.");
    }

    public function postSelected(): void
    {
        if (empty($this->selected)) {
            return;
        }

        // TODO: Voucher::whereIn('id', $this->selected)->each->post(auth()->user());
        $count = count($this->selected);
        $this->selected = [];

        session()->flash('accounts.status', "{$count} voucher(s) posted.");
    }

    /* ------------------------------------------------------------------
     |  Date range
     * ------------------------------------------------------------------ */

    /** @return array{0: Carbon, 1: Carbon} */
    #[Computed]
    public function range(): array
    {
        $now = now();

        return match ($this->period) {
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'week'      => [$now->copy()->startOfWeek(Carbon::SATURDAY), $now->copy()->endOfDay()], // BD week starts Saturday
            'month'     => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'year'      => [$now->copy()->startOfYear(), $now->copy()->endOfDay()],
            'custom'    => [
                Carbon::parse($this->from ?? $now->copy()->startOfMonth())->startOfDay(),
                Carbon::parse($this->to ?? $now)->endOfDay(),
            ],
            default     => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    #[Computed]
    public function rangeLabel(): string
    {
        [$start, $end] = $this->range;

        return $start->isSameDay($end)
            ? $start->format('d M Y')
            : $start->format('d M') . ' – ' . $end->format('d M Y');
    }

    /* ------------------------------------------------------------------
     |  Data (replace SAMPLE data with real queries)
     * ------------------------------------------------------------------ */

    #[Computed]
    public function summary(): array
    {
        // [$start, $end] = $this->range;
        // e.g. 'cash_in' => Transaction::posted()->whereBetween('date', [$start, $end])->where('direction', 'in')->sum('amount'),
        return [
            'cash_in'            => 48250,
            'cash_in_note'       => '12 receipts · COD ৳31,400',
            'cash_out'           => 31600,
            'cash_out_note'      => '7 payments · 1 EMI',
            'cash_on_hand'       => 124300,
            'bank_mfs'           => 386900,
            'bank_mfs_note'      => '2 banks · bKash · Nagad',
            'receivable'         => 248700,
            'receivable_overdue' => 62400,   // 60+ days
            'payable'            => 192000,
            'payable_7_days'     => 85000,
            'loan_outstanding'   => 420000,
            'lender_count'       => 3,
            'support_advance'    => 1500,
            'profit'             => 86450,   // posted entries only
        ];
    }

    #[Computed]
    public function stock(): array
    {
        // Value = qty × weighted average cost (products.avg_cost)
        // e.g. 'value_cost' => Product::sum(DB::raw('stock_qty * avg_cost')),
        return [
            'in_qty'        => 120,
            'in_cost'       => 132000,
            'in_note'       => 'purchase 110 · return 10',
            'out_qty'       => 38,
            'out_cogs'      => 41800,
            'out_note'      => 'sold 36 · damaged 2',
            'total_qty'     => 789,
            'product_count' => 107,
            'in_transit'    => 45,
            'value_cost'    => 867900,
            'value_sale'    => 1775250,
        ];
    }

    #[Computed]
    public function pendingVouchers(): array
    {
        // e.g. Voucher::where('status', 'draft')->with('party', 'creator')->latest()->get()
        return [
            ['id' => 319,  'ref' => 'EMI-0319', 'type' => 'Loan repayment', 'detail' => 'Kisti 5/12 + support ৳1,500', 'party' => 'Rahim Traders',   'amount' => 22500, 'by' => 'Sumon'],
            ['id' => 2231, 'ref' => 'RV-2231',  'type' => 'Receipt',        'detail' => 'COD settlement, 24 parcels',  'party' => 'Pathao Courier',  'amount' => 58420, 'by' => 'System'],
            ['id' => 877,  'ref' => 'PV-0877',  'type' => 'Payment',        'detail' => 'Office rent, October',        'party' => 'Mirpur Landlord', 'amount' => 18000, 'by' => 'Omar'],
            ['id' => 1042, 'ref' => 'JV-1042',  'type' => 'Journal',        'detail' => 'Supplier advance adjustment', 'party' => 'Rahman Fabrics',  'amount' => 35000, 'by' => 'Sumon'],
            ['id' => 561,  'ref' => 'EXP-0561', 'type' => 'Expense',        'detail' => 'Meta ads top-up',             'party' => 'Facebook Ads',    'amount' => 12000, 'by' => 'Omar'],
        ];
    }

    #[Computed]
    public function masters(): array
    {
        // One person/company = one profile; roles via a pivot (customer, supplier, lender, owner, login)
        return [
            ['label' => 'Customers',      'count' => 199, 'sub' => 'পাবো (due)',                             'amount' => 248700,  'initial' => 'C', 'tone' => 'green'],
            ['label' => 'Suppliers',      'count' => 24,  'sub' => 'দিবো (payable)',                         'amount' => 127000,  'initial' => 'S', 'tone' => 'red'],
            ['label' => 'Lenders',        'count' => 3,   'sub' => 'ঋণ বাকি',                                'amount' => 420000,  'initial' => 'L', 'tone' => 'indigo'],
            ['label' => 'Owners',         'count' => 2,   'sub' => 'Capital · drawings ৳45,000 this month', 'amount' => 1200000, 'initial' => 'O', 'tone' => 'amber'],
            ['label' => 'Login Profiles', 'count' => 6,   'sub' => '2 can post vouchers',                    'amount' => null,    'initial' => 'U', 'tone' => 'gray'],
        ];
    }

    /** All installments due in range (unfiltered). */
    #[Computed]
    public function allInstallments(): array
    {
        // status: paid | due | upcoming | overdue
        // frequency: monthly | weekly | daily | yearly | custom
        return [
            ['party' => 'Rahim Traders',      'role' => 'Lender · আমি দিবো',   'frequency' => 'monthly', 'rule' => 'Monthly',            'no' => '5 / 12',  'due' => '12 Oct', 'emi' => 21000,  'paid' => 22500, 'support' => 1500, 'status' => 'paid',     'status_label' => 'Paid'],
            ['party' => 'Karim Enterprise',   'role' => 'Customer · আমি পাবো', 'frequency' => 'weekly',  'rule' => 'Weekly',             'no' => '3 / 8',   'due' => '13 Oct', 'emi' => 6500,   'paid' => null,  'support' => null, 'status' => 'due',      'status_label' => 'Due'],
            ['party' => 'Nasrin Akter',       'role' => 'Customer · আমি পাবো', 'frequency' => 'daily',   'rule' => 'Daily',              'no' => '18 / 60', 'due' => 'Today',  'emi' => 500,    'paid' => 500,   'support' => null, 'status' => 'paid',     'status_label' => 'Paid'],
            ['party' => 'City Bank SME Loan', 'role' => 'Lender · আমি দিবো',   'frequency' => 'monthly', 'rule' => 'Monthly',            'no' => '9 / 24',  'due' => '15 Oct', 'emi' => 32400,  'paid' => null,  'support' => null, 'status' => 'upcoming', 'status_label' => 'Upcoming'],
            ['party' => 'Shop rent advance',  'role' => 'Landlord · আমি দিবো', 'frequency' => 'yearly',  'rule' => 'Yearly',             'no' => '2 / 3',   'due' => '01 Jan', 'emi' => 120000, 'paid' => null,  'support' => null, 'status' => 'upcoming', 'status_label' => 'Upcoming'],
            ['party' => 'Jamal Hossain',      'role' => 'Supplier · আমি দিবো', 'frequency' => 'custom',  'rule' => 'Custom · 15/45/90d', 'no' => '2 / 3',   'due' => '10 Oct', 'emi' => 40000,  'paid' => 25000, 'support' => null, 'status' => 'overdue',  'status_label' => 'Overdue ৳15,000'],
        ];
    }

    #[Computed]
    public function installments(): array
    {
        if ($this->freq === 'all') {
            return $this->allInstallments;
        }

        return array_values(array_filter(
            $this->allInstallments,
            fn ($row) => $row['frequency'] === $this->freq
        ));
    }

    #[Computed]
    public function freqCounts(): array
    {
        $counts = ['all' => count($this->allInstallments)];

        foreach (array_slice(self::FREQUENCIES, 1) as $f) {
            $counts[$f] = count(array_filter($this->allInstallments, fn ($r) => $r['frequency'] === $f));
        }

        return $counts;
    }

    #[Computed]
    public function aging(): array
    {
        $rows = [
            ['label' => '0–30 দিন',  'amount' => 142300, 'color' => 'bg-emerald-500'],
            ['label' => '31–60 দিন', 'amount' => 44000,  'color' => 'bg-amber-500'],
            ['label' => '61–90 দিন', 'amount' => 38400,  'color' => 'bg-orange-500'],
            ['label' => '90+ দিন',   'amount' => 24000,  'color' => 'bg-red-600'],
        ];

        $total = max(array_sum(array_column($rows, 'amount')), 1);

        return array_map(fn ($r) => $r + ['pct' => round($r['amount'] / $total * 100)], $rows);
    }

    #[Computed]
    public function payables(): array
    {
        return [
            ['party' => 'Jamal Hossain',      'when' => '1 day overdue', 'overdue' => true,  'amount' => 15000],
            ['party' => 'Rahman Fabrics',     'when' => 'Due 14 Oct',    'overdue' => false, 'amount' => 85000],
            ['party' => 'City Bank SME Loan', 'when' => 'EMI · 15 Oct',  'overdue' => false, 'amount' => 32400],
            ['party' => 'Packaging supplier', 'when' => 'Due 20 Oct',    'overdue' => false, 'amount' => 12000],
        ];
    }

    #[Computed]
    public function accounts(): array
    {
        return [
            ['name' => 'Cash box',       'kind' => 'Cash',           'amount' => 124300],
            ['name' => 'City Bank',      'kind' => 'Bank · current', 'amount' => 232000],
            ['name' => 'DBBL',           'kind' => 'Bank · savings', 'amount' => 72000],
            ['name' => 'bKash Merchant', 'kind' => 'Mobile banking', 'amount' => 64200],
            ['name' => 'Nagad',          'kind' => 'Mobile banking', 'amount' => 18700],
        ];
    }

    #[Computed]
    public function transactions(): array
    {
        // amount: positive = in, negative = out
        return [
            ['time' => '1:55 PM',  'title' => 'COD settlement · Pathao',          'account' => 'City Bank',      'posted' => false, 'amount' => 58420],
            ['time' => '1:20 PM',  'title' => 'EMI 5/12 · Rahim Traders',         'account' => 'Cash box',       'posted' => false, 'amount' => -22500],
            ['time' => '12:40 PM', 'title' => 'Daily kisti · Nasrin Akter',       'account' => 'bKash Merchant', 'posted' => true,  'amount' => 500],
            ['time' => '11:15 AM', 'title' => 'Due collection · Karim Enterprise', 'account' => 'Cash box',       'posted' => true,  'amount' => 9000],
            ['time' => '10:30 AM', 'title' => 'Courier charge',                   'account' => 'Cash box',       'posted' => true,  'amount' => -2100],
            ['time' => '9:50 AM',  'title' => 'Office rent, October',             'account' => 'Cash box',       'posted' => false, 'amount' => -18000],
        ];
    }

    #[Computed]
    public function alerts(): array
    {
        // tone: red | amber | green
        return [
            ['title' => 'Jamal Hossain — কিস্তি বাকি', 'text' => '৳15,000 overdue since 10 Oct',        'tone' => 'red'],
            ['title' => '5 vouchers waiting to post',  'text' => 'Not counted in profit until posted', 'tone' => 'amber'],
            ['title' => 'City Bank EMI in 4 days',     'text' => '৳32,400 · bank balance enough',      'tone' => 'amber'],
            ['title' => 'Support advance ৳1,500',      'text' => 'Rahim Traders · next kisti ৳19,500', 'tone' => 'green'],
        ];
    }

    /* ------------------------------------------------------------------
     |  Helpers
     * ------------------------------------------------------------------ */

    /** ৳ with Bangladeshi grouping: 1775250 → ৳17,75,250 */
    public function money(int|float|null $amount, bool $signed = false): string
    {
        if ($amount === null) {
            return '—';
        }

        $negative = $amount < 0;
        $digits   = (string) (int) round(abs($amount));

        if (strlen($digits) > 3) {
            $last3  = substr($digits, -3);
            $rest   = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', substr($digits, 0, -3));
            $digits = $rest . ',' . $last3;
        }

        $prefix = $negative ? '− ' : ($signed ? '+ ' : '');

        return $prefix . '৳' . $digits;
    }

    public function render()
    {
        return view('livewire.accounts.dashboard');
    }
}
