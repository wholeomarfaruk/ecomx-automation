<?php

namespace App\Livewire\Accounts;

use App\Enums\Accounts\JournalEntryStatus;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\Accounts\Vouchers;
use App\Support\AccountsAccess;
use App\Support\AccountsSettings;
use App\Support\Bdt;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

/**
 * Accounts Panel › Posting Pending — design: PostingPending.dc.html.
 * Every voucher that isn't posted yet, with its state (ready / needs
 * approval / has issues / scheduled / rejected), a review panel (lines,
 * live "what changes", checks, history) and the actions per state.
 */
#[Title('Posting Pending')]
#[Layout('layouts.accounts.app')]
class PostingPending extends Component
{
    use WithFileUploads;

    public const TABS = ['open' => 'Waiting to post', 'ready' => 'Ready', 'approval' => 'Needs approval', 'issue' => 'Has issues', 'scheduled' => 'Scheduled', 'rejected' => 'Rejected'];

    public const TYPES = ['receipt' => 'Receipt', 'payment' => 'Payment', 'journal' => 'Journal', 'expense' => 'Expense', 'emi' => 'Loan / EMI', 'salary' => 'Salary', 'contra' => 'Contra'];

    public const RULE_TEXT = [
        'approval' => ['%s-এর বেশি হলে Owner approval', 'Maker post করতে পারবে না; Owner approve করলে post হবে।'],
        'attach'   => ['%s-এর বেশি হলে attachment লাগবে', 'Bill, slip বা receipt-এর ছবি/PDF ছাড়া post হবে না।'],
        'maker'    => ['নিজের entry নিজে post করা যাবে না', 'Maker-checker: অন্য একজন review করে post করবে।'],
        'lock'     => ['বন্ধ মাসে post করা যাবে না', 'Books locked up to %s।'],
        'auto'     => ['System entry নিজে থেকে post হবে', 'COD settlement, payroll — check pass করলে review ছাড়াই post।'],
    ];

    #[Url(except: 'open')]
    public string $tab = 'open';

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $source = '';

    #[Url(as: 'by', except: '')]
    public string $createdBy = '';

    #[Url(as: 'v', except: null)]
    public ?int $sel = null;

    public bool $rejecting = false;
    public string $rejectReason = '';
    public array $bulkIds = [];
    public bool $bulkRejecting = false;
    public ?string $newDate = null;
    public $attachment = null;
    public ?int $reversing = null;
    public string $reverseReason = '';

    public function mount(): void
    {
        AccountsAccess::authorize('view');
    }

    #[On('voucher-saved')]
    public function refresh(?int $id = null): void
    {
        unset($this->pending);
        if ($id && JournalEntry::whereKey($id)->whereIn('status', ['draft', 'scheduled', 'rejected'])->exists()) {
            $this->sel = $id;
        }
    }

    /** All not-yet-posted vouchers with their state, newest first. */
    #[Computed]
    public function pending(): Collection
    {
        $v = app(Vouchers::class);

        return JournalEntry::with(['lines.account', 'attachments', 'profile:id,display_name', 'createdBy:id,name', 'approvedBy:id,name'])
            ->whereIn('status', [JournalEntryStatus::DRAFT->value, JournalEntryStatus::SCHEDULED->value, JournalEntryStatus::REJECTED->value])
            ->latest('id')->limit(500)->get()
            ->map(fn (JournalEntry $e) => ['entry' => $e, 'state' => $v->state($e), 'type' => $v->typeKey($e)]);
    }

    protected function inTab(array $r, string $tab): bool
    {
        return $tab === 'open' ? in_array($r['state'], ['ready', 'approval', 'issue'], true) : $r['state'] === $tab;
    }

    #[Computed]
    public function rows(): Collection
    {
        $q = mb_strtolower(trim($this->q));
        $v = app(Vouchers::class);

        return $this->pending->filter(function ($r) use ($q, $v) {
            $e = $r['entry'];

            return $this->inTab($r, $this->tab)
                && ($this->type === '' || $r['type'] === $this->type)
                && ($this->source === '' || $e->source === $this->source)
                && ($this->createdBy === '' || ($this->createdBy === 'system' ? $e->source === 'system' : (string) $e->created_by === $this->createdBy))
                && ($q === '' || str_contains(mb_strtolower($e->ref() . ' ' . $v->partyOf($e) . ' ' . $e->description . ' ' . (int) $e->amount), $q));
        })->values();
    }

    #[Computed]
    public function summary(): array
    {
        $p = $this->pending;
        $open = $p->filter(fn ($r) => $this->inTab($r, 'open'));
        $approval = $p->where('state', 'approval');
        $old = $open->filter(fn ($r) => $r['entry']->created_at->lt(now()->subHours(48)))->sortBy(fn ($r) => $r['entry']->created_at);
        $postedToday = JournalEntry::where('status', JournalEntryStatus::POSTED->value)->where('posted_at', '>=', today());

        return [
            'open'        => $open->count(),
            'openAmount'  => $open->sum(fn ($r) => (float) $r['entry']->amount),
            'ready'       => $p->where('state', 'ready')->count(),
            'approval'    => $approval->count(),
            'approvalAmt' => $approval->sum(fn ($r) => (float) $r['entry']->amount),
            'old'         => $old->count(),
            'oldFirst'    => $old->first() ? $old->first()['entry']->ref() . ' · ' . app(Vouchers::class)->waiting($old->first()['entry'])[0] : 'সব সময়মতো',
            'rejected'    => $p->where('state', 'rejected')->count(),
            'postedToday' => (clone $postedToday)->count(),
            'postedAmt'   => (float) (clone $postedToday)->sum('amount'),
            'posters'     => (clone $postedToday)->whereNotNull('posted_by')->distinct('posted_by')->count('posted_by'),
            'counts'      => collect(self::TABS)->keys()->mapWithKeys(fn ($t) => [$t => $p->filter(fn ($r) => $this->inTab($r, $t))->count()])->all(),
        ];
    }

    #[Computed]
    public function makers(): array
    {
        return User::whereIn('id', $this->pending->pluck('entry.created_by')->filter()->unique())->orderBy('name')->pluck('name', 'id')->all();
    }

    /** The voucher open in the review panel (selected, or the first row). */
    #[Computed]
    public function current(): ?array
    {
        $row = ($this->sel ? $this->pending->first(fn ($r) => $r['entry']->id === $this->sel) : null) ?? $this->rows->first();

        if (! $row) {
            return null;
        }

        $e = $row['entry'];
        $v = app(Vouchers::class);
        $checks = $v->checks($e, auth()->user());
        $firstBad = collect($checks)->firstWhere('state', 'bad');

        [$note, $kind] = match ($row['state']) {
            'approval'  => ['Amount ' . Bdt::money(AccountsSettings::approvalThreshold()) . '-এর বেশি — Owner approval লাগবে।', 'info'],
            'issue'     => [$firstBad['text'] . '। Edit করে ঠিক করুন।', 'warn'],
            'scheduled' => ['Post-dated entry — ' . ($e->scheduled_for ?? $e->entry_date)->format('d M') . ' তারিখে নিজে থেকে post হবে।', 'info'],
            'rejected'  => ['Rejected by ' . ($e->rejectedBy?->name ?? 'reviewer') . ': “' . $e->reject_reason . '” ঠিক করে আবার পাঠাতে হবে।', 'bad'],
            default     => [null, 'info'],
        };

        return [
            'entry'   => $e,
            'state'   => $row['state'],
            'type'    => $v->typeLabel($e),
            'party'   => $v->partyOf($e),
            'note'    => $note,
            'kind'    => $kind,
            'lines'   => $e->lines,
            'impact'  => $row['state'] === 'rejected' ? [] : $v->impact($e),
            'checks'  => $checks,
            'trail'   => $e->events()->with('user:id,name')->get(),
            'canApprove' => AccountsAccess::can('approve'),
            'makerBlocked' => $v->makerBlocked($e, auth()->user()),
        ];
    }

    public function open(int $id): void
    {
        $this->sel = $id;
        $this->rejecting = false;
        $this->newDate = null;
    }

    public function setTab(string $tab): void
    {
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : 'open';
    }

    /* ---------------- actions ---------------- */

    public function post(int $id): void
    {
        $this->run(fn (Vouchers $v) => $v->post(JournalEntry::findOrFail($id)), fn () => JournalEntry::find($id)?->ref() . ' posted.');
    }

    public function postSelected(array $ids): void
    {
        $posted = 0;
        $failed = [];
        $v = app(Vouchers::class);

        foreach (JournalEntry::whereIn('id', array_map('intval', $ids))->get() as $e) {
            try {
                $v->post($e);
                $posted++;
            } catch (RuntimeException $ex) {
                $failed[] = $e->ref() . ' (' . $ex->getMessage() . ')';
            }
        }

        $this->bulkIds = [];
        unset($this->pending);
        $this->dispatch('clear-selection');
        $this->dispatch('toast', type: $failed ? 'warning' : 'success', message: "{$posted} voucher(s) posted." . ($failed ? ' Skipped: ' . implode('; ', $failed) : ''));
    }

    public function startBulkReject(array $ids): void
    {
        $this->bulkIds = array_map('intval', $ids);
        $this->bulkRejecting = true;
        $this->rejectReason = '';
    }

    public function reject(?int $id = null): void
    {
        $this->validate(['rejectReason' => 'required|string|max:500'], [], ['rejectReason' => 'reason']);
        $ids = $id ? [$id] : $this->bulkIds;
        $v = app(Vouchers::class);
        $done = 0;

        foreach (JournalEntry::whereIn('id', $ids)->get() as $e) {
            try {
                $v->reject($e, $this->rejectReason);
                $done++;
            } catch (RuntimeException $ex) {
                $this->dispatch('toast', type: 'error', message: $ex->getMessage());
            }
        }

        $this->reset('rejecting', 'bulkRejecting', 'rejectReason', 'bulkIds');
        unset($this->pending);
        $this->dispatch('clear-selection');
        $this->dispatch('toast', type: 'success', message: "{$done} voucher(s) sent back to maker.");
    }

    public function reschedule(int $id): void
    {
        $this->validate(['newDate' => 'required|date'], [], ['newDate' => 'date']);
        $this->run(fn (Vouchers $v) => $v->reschedule(JournalEntry::findOrFail($id), $this->newDate), fn () => 'Date changed.');
        $this->newDate = null;
    }

    public function postNow(int $id): void
    {
        $this->run(function (Vouchers $v) use ($id) {
            $e = JournalEntry::findOrFail($id);
            $e->update(['status' => JournalEntryStatus::DRAFT]);
            try {
                $v->post($e->fresh());
            } catch (RuntimeException $ex) {
                $e->update(['status' => JournalEntryStatus::SCHEDULED]);
                throw $ex;
            }
        }, fn () => 'Posted now.');
    }

    public function resubmit(int $id): void
    {
        $this->run(fn (Vouchers $v) => $v->resubmit(JournalEntry::findOrFail($id)), fn () => 'Sent back for review.');
    }

    public function deleteVoucher(int $id): void
    {
        $this->run(fn (Vouchers $v) => $v->delete(JournalEntry::findOrFail($id)), fn () => 'Voucher deleted.');
        $this->sel = null;
    }

    public function updatedAttachment(): void
    {
        $this->validate(['attachment' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,webp,xlsx,csv'], [], ['attachment' => 'attachment']);
        $e = JournalEntry::findOrFail($this->current['entry']->id);
        app(Vouchers::class)->attach($e, $this->attachment);
        $this->attachment = null;
        unset($this->pending, $this->current);
        $this->dispatch('toast', type: 'success', message: 'Attachment added.');
    }

    public function reverse(): void
    {
        $id = $this->reversing;
        $this->run(fn (Vouchers $v) => $v->reverse(JournalEntry::findOrFail($id), $this->reverseReason ?: null), fn () => 'Reversed — an opposite journal was posted.');
        $this->reset('reversing', 'reverseReason');
    }

    /** Posting rules card: same switches as Settings › Posting rules. */
    public function toggleRule(string $key): void
    {
        AccountsAccess::authorize('settings');
        $rules = (array) (AccountsSettings::get('rules') ?: []);
        $rules[$key] = ['on' => ! AccountsSettings::ruleOn($key), 'amount' => AccountsSettings::rule($key)['amount']];
        AccountsSettings::set('rules', $rules);
        unset($this->pending);
    }

    protected function run(callable $action, callable $message): void
    {
        try {
            $action(app(Vouchers::class));
        } catch (RuntimeException $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());

            return;
        }

        unset($this->pending, $this->current);
        $this->dispatch('toast', type: 'success', message: $message());
    }

    #[Computed]
    public function postedToday(): Collection
    {
        $v = app(Vouchers::class);

        return JournalEntry::with(['postedBy:id,name', 'profile:id,display_name', 'reversingEntry:id,reversed_journal_entry_id'])
            ->where('status', JournalEntryStatus::POSTED->value)->where('posted_at', '>=', today())
            ->latest('posted_at')->limit(30)->get()
            ->map(fn ($e) => [
                'id' => $e->id, 'time' => $e->posted_at->format('g:i A'), 'ref' => $e->ref(),
                'text' => trim(($e->description ?: $v->typeLabel($e)) . ' · ' . $v->partyOf($e), ' ·'),
                'by' => $e->postedBy?->name ?? 'System', 'amount' => Bdt::money($e->amount),
                'reversed' => $e->reversingEntry->isNotEmpty(),
            ]);
    }

    public function render()
    {
        return view('livewire.accounts.posting-pending', [
            'money'    => fn ($v) => Bdt::money($v),
            'vouchers' => app(Vouchers::class),
            'lock'     => AccountsSettings::lockDate(),
        ])->layoutData(['active' => 'posting', 'heading' => 'Posting Pending', 'crumb' => 'Accounts / Posting Pending']);
    }
}
