<?php

namespace App\Livewire\Accounts;

use App\Enums\Accounts\JournalEntryStatus;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\MasterProfile;
use App\Services\Accounts\ExpenseCategories;
use App\Services\Accounts\Vouchers;
use App\Services\Profiles\LedgerAccounts;
use App\Support\AccountsAccess;
use App\Support\AccountsSettings;
use App\Support\Bdt;
use App\Support\BdPhone;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * The voucher forms behind "+ টাকা আসলো / + টাকা গেলো / + Expense /
 * + Transfer / + Journal" (spec §5.3) — one modal, embedded once per page
 * and opened with the `voucher-form` browser event. Everything is created
 * as a draft (or scheduled when post-dated); "Save & post" tries to post
 * straight away for users allowed to.
 */
class VoucherForm extends Component
{
    use WithFileUploads;

    public const TITLES = [
        'receipt' => '+ টাকা আসলো · Receipt',
        'payment' => '+ টাকা গেলো · Payment',
        'expense' => '+ Expense',
        'contra'  => '+ Transfer',
        'journal' => '+ Journal',
    ];

    public bool $open = false;
    public string $type = 'receipt';
    public ?int $editId = null;

    public array $f = [];
    public array $lines = [];
    public array $files = [];
    public string $partySearch = '';

    #[On('voucher-form')]
    public function show(string $type = 'receipt', ?int $id = null, array $preset = [], bool $duplicate = false): void
    {
        AccountsAccess::authorize('create');
        abort_unless(isset(self::TITLES[$type]) || $id, 404);

        $this->resetErrorBag();
        $this->reset('files', 'partySearch');
        $this->editId = null;
        $this->type = isset(self::TITLES[$type]) ? $type : 'journal';
        $this->f = [
            'date'        => today()->toDateString(),
            'account_id'  => $this->cashAccounts[0]['id'] ?? null,
            'to_id'       => $this->cashAccounts[1]['id'] ?? null,
            'ledger_id'   => null,
            'profile_id'  => null,
            'party_label' => '',
            'amount'      => null,
            'charge'      => null,
            'note'        => '',
        ];
        $this->lines = [$this->blankLine(), $this->blankLine()];

        if ($id) {
            $this->loadEntry(JournalEntry::with('lines.account')->findOrFail($id), $duplicate);
            if ($duplicate) {
                $this->f['date'] = today()->toDateString();
            }
        }

        foreach ($preset as $k => $v) {
            if (array_key_exists($k, $this->f)) {
                $this->f[$k] = $v;
            }
        }

        if ($this->f['profile_id'] && ! $this->f['ledger_id']) {
            $this->f['ledger_id'] = app(Vouchers::class)->defaultLedger($this->type, MasterProfile::with('roles')->find($this->f['profile_id']));
        }

        $this->open = true;
    }

    protected function blankLine(): array
    {
        return ['account_id' => null, 'profile_id' => null, 'debit' => null, 'credit' => null];
    }

    /** Edit: map an existing draft back onto the simple form, else into journal lines. */
    protected function loadEntry(JournalEntry $e, bool $duplicate = false): void
    {
        if (! $duplicate && ! $e->status->isPending()) {
            throw new RuntimeException('Posted vouchers can\'t be edited — reverse it instead.');
        }

        $this->editId = $duplicate ? null : $e->id;
        $this->f['date'] = ($e->scheduled_for ?? $e->entry_date)->toDateString();
        $this->f['note'] = (string) $e->description;
        $this->f['profile_id'] = $e->master_profile_id;
        $this->f['party_label'] = (string) $e->party_label;

        $cash = $e->lines->filter(fn ($l) => in_array($l->account?->subtype, LedgerAccounts::CASH_SUBTYPES, true));
        $other = $e->lines->diff($cash);
        $simple = in_array($e->voucher_type, ['receipt', 'payment', 'expense'], true) && $cash->count() === 1 && $other->count() === 1;

        if ($simple) {
            $this->type = $e->voucher_type;
            $this->f['account_id'] = $cash->first()->account_id;
            $this->f['ledger_id'] = $other->first()->account_id;
            $this->f['amount'] = (float) $e->amount;
        } elseif ($e->voucher_type === 'contra' && $cash->count() >= 2) {
            $this->type = 'contra';
            $this->f['to_id'] = $cash->firstWhere('debit', '>', 0)?->account_id;
            $this->f['account_id'] = $cash->firstWhere('credit', '>', 0)?->account_id;
            $this->f['amount'] = (float) $cash->firstWhere('debit', '>', 0)?->debit;
            $this->f['charge'] = (float) $other->sum('debit') ?: null;
        } else {
            $this->type = 'journal';
            $this->lines = $e->lines->map(fn ($l) => [
                'account_id' => $l->account_id,
                'profile_id' => $l->subledger_type === MasterProfile::class ? $l->subledger_id : ($e->master_profile_id ?: null),
                'debit'      => (float) $l->debit ?: null,
                'credit'     => (float) $l->credit ?: null,
            ])->all();
        }
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function addLine(): void
    {
        $this->lines[] = $this->blankLine();
    }

    public function removeLine(int $i): void
    {
        if (count($this->lines) > 2) {
            array_splice($this->lines, $i, 1);
        }
    }

    public function pickParty(int $id): void
    {
        $this->f['profile_id'] = $id;
        $this->partySearch = '';
        $this->f['ledger_id'] = app(Vouchers::class)->defaultLedger($this->type, MasterProfile::with('roles')->find($id)) ?? $this->f['ledger_id'];
    }

    public function clearParty(): void
    {
        $this->f['profile_id'] = null;
    }

    /* ---------------- options ---------------- */

    #[Computed]
    public function cashAccounts(): array
    {
        $vouchers = app(Vouchers::class);

        return collect(app(LedgerAccounts::class)->cashAccounts())
            ->map(fn ($a) => $a + ['balance' => $vouchers->postedBalance($a['id'])])
            ->all();
    }

    /** Ledgers for the "against" side of a receipt / payment, and for journal lines. */
    #[Computed]
    public function ledgers(): array
    {
        return Account::query()->where('is_active', true)
            ->when($this->type !== 'journal', fn ($q) => $q->where(fn ($w) => $w->whereNull('subtype')->orWhereNotIn('subtype', LedgerAccounts::CASH_SUBTYPES)))
            ->orderBy('code')->get(['id', 'code', 'name', 'type'])
            ->groupBy(fn ($a) => ucfirst($a->type->value))
            ->map(fn ($g) => $g->map(fn ($a) => ['id' => $a->id, 'name' => "{$a->code} · {$a->name}"])->values()->all())
            ->all();
    }

    #[Computed]
    public function categories(): array
    {
        return ExpenseCategories::all()->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])->all();
    }

    #[Computed]
    public function party(): ?MasterProfile
    {
        return $this->f['profile_id'] ? MasterProfile::with('roles')->find($this->f['profile_id']) : null;
    }

    #[Computed]
    public function partyMatches(): array
    {
        $q = trim($this->partySearch);
        if ($q === '') {
            return [];
        }

        $phone = BdPhone::normalize($q);

        return MasterProfile::query()
            ->where(fn ($w) => $w->where('display_name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%")
                ->when($phone && strlen($phone) >= 4, fn ($w) => $w->orWhere('phone', 'like', '%' . ltrim($phone, '0') . '%')))
            ->orderBy('display_name')->limit(8)->get(['id', 'code', 'display_name', 'phone'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->display_name, 'meta' => trim($p->code . ' · ' . ($p->phone ?? ''), ' ·')])
            ->all();
    }

    #[Computed]
    public function profileOptions(): array
    {
        $ids = collect($this->lines)->pluck('profile_id')->filter()->all();

        return MasterProfile::whereIn('id', $ids)->pluck('display_name', 'id')->all();
    }

    #[Computed]
    public function totals(): array
    {
        $dr = round(collect($this->lines)->sum(fn ($l) => (float) ($l['debit'] ?? 0)), 2);
        $cr = round(collect($this->lines)->sum(fn ($l) => (float) ($l['credit'] ?? 0)), 2);

        return ['dr' => $dr, 'cr' => $cr, 'ok' => $dr > 0 && abs($dr - $cr) < 0.01];
    }

    /* ---------------- save ---------------- */

    public function save(bool $post = false): void
    {
        AccountsAccess::authorize('create');
        $this->validate($this->rules(), [], [
            'f.account_id' => 'account', 'f.to_id' => 'to account', 'f.ledger_id' => $this->type === 'expense' ? 'category' : 'ledger',
            'f.amount' => 'amount', 'lines.*.account_id' => 'account', 'files.*' => 'attachment',
        ]);

        $vouchers = app(Vouchers::class);
        $profile = $this->party;

        try {
            $data = $this->build($vouchers, $profile);
            $entry = $this->editId
                ? $vouchers->update(JournalEntry::findOrFail($this->editId), $data, $this->files)
                : $vouchers->draft($data, $this->files);
        } catch (RuntimeException $e) {
            $this->addError('f.amount', $e->getMessage());

            return;
        }

        $message = "{$entry->ref()} saved as " . ($entry->status === JournalEntryStatus::SCHEDULED ? 'scheduled' : 'draft') . '.';

        if ($post && $entry->status === JournalEntryStatus::DRAFT) {
            try {
                $vouchers->post($entry);
                $message = "{$entry->ref()} posted.";
            } catch (RuntimeException $e) {
                $message .= ' Not posted: ' . $e->getMessage();
            }
        }

        $this->open = false;
        $this->dispatch('voucher-saved', id: $entry->id);
        $this->dispatch('toast', type: str_contains($message, 'Not posted') ? 'warning' : 'success', message: $message);
    }

    protected function rules(): array
    {
        $rules = [
            'f.date'   => 'required|date',
            'f.note'   => 'nullable|string|max:255',
            'files.*'  => 'file|max:10240|mimes:pdf,jpg,jpeg,png,webp,xlsx,csv',
        ];

        return $rules + match ($this->type) {
            'journal' => [
                'lines'              => 'required|array|min:2',
                'lines.*.account_id' => 'required|integer|exists:accounts,id',
                'lines.*.debit'      => 'nullable|numeric|min:0',
                'lines.*.credit'     => 'nullable|numeric|min:0',
            ],
            'contra' => [
                'f.account_id' => 'required|integer|exists:accounts,id',
                'f.to_id'      => 'required|integer|exists:accounts,id|different:f.account_id',
                'f.amount'     => 'required|numeric|min:1',
                'f.charge'     => 'nullable|numeric|min:0',
            ],
            default => [
                'f.account_id'  => 'required|integer|exists:accounts,id',
                'f.ledger_id'   => 'required|integer|exists:accounts,id',
                'f.amount'      => 'required|numeric|min:1',
                'f.party_label' => 'nullable|string|max:120',
            ],
        };
    }

    /** Header + balanced lines for Vouchers::draft(). */
    protected function build(Vouchers $vouchers, ?MasterProfile $profile): array
    {
        $f = $this->f;
        $amount = round((float) ($f['amount'] ?? 0), 2);
        $party = $profile?->display_name ?: ($f['party_label'] ?: null);

        $header = [
            'type'             => $this->type,
            'date'             => $f['date'],
            'description'      => $f['note'] ?: null,
            'profile_id'       => $profile?->id,
            'party_label'      => $profile ? null : ($f['party_label'] ?: null),
            'money_account_id' => in_array($this->type, ['receipt', 'payment', 'expense', 'contra'], true) ? (int) $f['account_id'] : null,
        ];

        $lines = match ($this->type) {
            'receipt' => [
                ['account_id' => (int) $f['account_id'], 'debit' => $amount],
                ['account_id' => (int) $f['ledger_id'], 'credit' => $amount] + $vouchers->partyFields((int) $f['ledger_id'], $profile),
            ],
            'payment', 'expense' => [
                ['account_id' => (int) $f['ledger_id'], 'debit' => $amount, 'memo' => $party] + $vouchers->partyFields((int) $f['ledger_id'], $profile),
                ['account_id' => (int) $f['account_id'], 'credit' => $amount],
            ],
            'contra' => $this->contraLines($amount, round((float) ($f['charge'] ?? 0), 2)),
            'journal' => collect($this->lines)
                ->filter(fn ($l) => (float) ($l['debit'] ?? 0) > 0 || (float) ($l['credit'] ?? 0) > 0)
                ->map(fn ($l) => [
                    'account_id' => (int) $l['account_id'],
                    'debit'      => round((float) ($l['debit'] ?? 0), 2),
                    'credit'     => round((float) ($l['credit'] ?? 0), 2),
                ] + $vouchers->partyFields((int) $l['account_id'], $l['profile_id'] ? MasterProfile::find($l['profile_id']) : null))
                ->values()->all(),
        };

        if ($this->type === 'contra') {
            $header['description'] ??= 'Transfer';
            $header['party_label'] = Account::whereKey($f['account_id'])->value('name') . ' → ' . Account::whereKey($f['to_id'])->value('name');
        }

        if ($this->type === 'journal' && ! $header['profile_id']) {
            $header['profile_id'] = collect($this->lines)->pluck('profile_id')->filter()->first();
        }

        return $header + ['lines' => $lines];
    }

    /** Dr to-account / Cr from-account; a charge goes to Bank & MFS charges, also out of the from-account. */
    protected function contraLines(float $amount, float $charge): array
    {
        $lines = [
            ['account_id' => (int) $this->f['to_id'], 'debit' => $amount],
            ['account_id' => (int) $this->f['account_id'], 'credit' => round($amount + $charge, 2)],
        ];

        if ($charge > 0) {
            array_splice($lines, 1, 0, [['account_id' => app(LedgerAccounts::class)->require(ExpenseCategories::CHARGES), 'debit' => $charge, 'memo' => 'Transfer charge']]);
        }

        return $lines;
    }

    public function render()
    {
        return view('livewire.accounts.voucher-form', [
            'canPost' => AccountsAccess::can('post'),
            'money'   => fn ($v) => Bdt::money($v),
            'attachAbove' => AccountsSettings::attachmentThreshold(),
        ]);
    }
}
