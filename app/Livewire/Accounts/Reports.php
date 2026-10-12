<?php

namespace App\Livewire\Accounts;

use App\Models\DayClosing;
use App\Models\MasterProfile;
use App\Models\ReportArchive;
use App\Models\SavedReport;
use App\Services\Accounts\DailyReport;
use App\Services\Accounts\ReportDelivery;
use App\Services\Accounts\Reports as Engine;
use App\Services\Accounts\SavedReportRunner;
use App\Support\AccountsAccess;
use App\Support\Bdt;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use RuntimeException;
use Throwable;

/**
 * Accounts Panel › Reports — design: Reports.dc.html.
 * Daily report with close / reopen, saved reports (schedule, recipients,
 * channels, archive) and every financial / book / business report with
 * Excel, PDF and print.
 */
#[Title('Reports')]
#[Layout('layouts.accounts.app')]
class Reports extends Component
{
    #[Url(except: 'daily')]
    public string $rep = 'daily';

    #[Url(except: 'month')]
    public string $period = 'month';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    #[Url(except: '')]
    public string $asOf = '';

    #[Url(as: 'account', except: 0)]
    public int $accountId = 0;

    #[Url(as: 'profile', except: 0)]
    public int $profileId = 0;

    #[Url(except: '')]
    public string $date = '';

    public string $dayNote = '';

    public bool $saving = false;
    public ?int $editId = null;
    public array $save = [];

    public bool $showArchive = false;

    public function mount(): void
    {
        AccountsAccess::authorize('reports');
        if (! $this->validKey($this->rep)) {
            $this->rep = 'daily';
        }
        $this->date = $this->day()->toDateString();
        $this->dayNote = (string) ($this->closing?->note ?? '');
    }

    protected function validKey(string $k): bool
    {
        return collect(Engine::GROUPS)->contains(fn ($items) => isset($items[$k]));
    }

    public function pick(string $k): void
    {
        if ($this->validKey($k)) {
            $this->rep = $k;
            $this->saving = false;
            unset($this->report);
        }
    }

    public function filters(): array
    {
        return array_filter([
            'period' => $this->period, 'from' => $this->from ?: null, 'to' => $this->to ?: null, 'as_of' => $this->asOf ?: null,
            'account_id' => $this->accountId ?: null, 'profile_id' => $this->profileId ?: null, 'date' => $this->rep === 'daily' ? $this->day()->toDateString() : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function day(): CarbonImmutable
    {
        try {
            return $this->date ? CarbonImmutable::parse($this->date)->startOfDay() : CarbonImmutable::today();
        } catch (Throwable) {
            return CarbonImmutable::today();
        }
    }

    public function updated(string $name): void
    {
        if (in_array($name, ['period', 'from', 'to', 'asOf', 'accountId', 'profileId'], true)) {
            unset($this->report, $this->drafts);
        }
        if ($name === 'date') {
            unset($this->daily, $this->closing, $this->drafts);
            $this->dayNote = (string) ($this->closing?->note ?? '');
        }
    }

    /* ---------------- report ---------------- */

    #[Computed]
    public function report(): ?array
    {
        if (in_array($this->rep, ['daily', 'saved'], true)) {
            return null;
        }

        try {
            return app(Engine::class)->build($this->rep, $this->filters());
        } catch (Throwable $e) {
            report($e);

            return ['title' => Engine::title($this->rep), 'period' => '', 'columns' => [], 'rows' => [], 'note' => 'Report তৈরি করা যায়নি: ' . $e->getMessage()];
        }
    }

    #[Computed]
    public function drafts(): array
    {
        if ($this->rep === 'saved') {
            return ['count' => 0, 'amount' => 0];
        }
        if ($this->rep === 'daily') {
            return app(Engine::class)->drafts(CarbonImmutable::create(2000), $this->day());
        }
        [$from, $to] = Engine::range($this->filters());

        return app(Engine::class)->drafts(in_array($this->rep, Engine::AS_OF, true) ? CarbonImmutable::create(2000) : $from, $to);
    }

    #[Computed]
    public function profileOptions(): array
    {
        return MasterProfile::orderBy('display_name')->limit(3000)->pluck('display_name', 'id')->all();
    }

    public function exportUrl(string $kind): string
    {
        $q = http_build_query(array_filter([
            'period' => $this->period, 'from' => $this->from, 'to' => $this->to, 'as_of' => $this->asOf,
            'account_id' => $this->accountId ?: null, 'profile_id' => $this->profileId ?: null,
        ]));

        return route($kind === 'excel' ? 'accounts.reports.export' : 'accounts.reports.print', $this->rep) . ($q ? "?{$q}" : '');
    }

    /* ---------------- daily ---------------- */

    #[Computed]
    public function daily(): ?array
    {
        return $this->rep === 'daily' ? app(DailyReport::class)->data($this->day()) : null;
    }

    #[Computed]
    public function closing(): ?DayClosing
    {
        return DayClosing::with('closedBy:id,name')->whereDate('date', $this->day()->toDateString())->orderByDesc('version')->first();
    }

    #[Computed]
    public function history(): Collection
    {
        return DayClosing::with('closedBy:id,name')->whereDate('date', '<', $this->day()->toDateString())
            ->orderByDesc('date')->orderByDesc('version')->limit(40)->get()->unique(fn ($c) => $c->date->toDateString())->take(7)->values();
    }

    public function closeDay(): void
    {
        AccountsAccess::authorize('post');
        $this->validate(['dayNote' => 'nullable|string|max:2000']);
        try {
            app(DailyReport::class)->close($this->day(), $this->dayNote ?: null);
            $this->dispatch('toast', type: 'success', message: $this->day()->format('d M') . ' closed — copy saved in the archive.');
        } catch (RuntimeException $e) {
            $this->dispatch('toast', type: 'warning', message: $e->getMessage());
        }
        unset($this->closing, $this->history, $this->daily);
    }

    public function reopenDay(): void
    {
        AccountsAccess::authorize('approve');
        try {
            app(DailyReport::class)->reopen($this->day());
            $this->dispatch('toast', type: 'success', message: $this->day()->format('d M') . ' reopened — closing again saves a new version.');
        } catch (RuntimeException $e) {
            $this->dispatch('toast', type: 'warning', message: $e->getMessage());
        }
        unset($this->closing, $this->daily);
    }

    public function sendToOwners(): void
    {
        AccountsAccess::authorize('reports');
        $text = app(DailyReport::class)->summaryText($this->daily) . ($this->dayNote ? ' · Note: ' . $this->dayNote : '');
        $sent = app(ReportDelivery::class)->send('owners', ['sms', 'email'], 'দিনের হিসাব · ' . $this->day()->format('d M Y'), $text, $this->closing?->pdf_path);
        $total = array_sum($sent);
        $this->dispatch('toast', type: $total ? 'success' : 'warning', message: $total ? "Owners-কে পাঠানো হয়েছে (SMS {$sent['sms']}, email {$sent['email']})." : 'কাউকে পাঠানো যায়নি — owner profile-এ phone / email দিন।');
    }

    /* ---------------- saved reports ---------------- */

    #[Computed]
    public function saved(): Collection
    {
        return SavedReport::orderBy('name')->get();
    }

    #[Computed]
    public function archiveCounts(): array
    {
        $c = ReportArchive::selectRaw('kind, COUNT(*) as n')->groupBy('kind')->pluck('n', 'kind');

        return ['total' => (int) $c->sum(), 'daily' => (int) ($c['daily'] ?? 0), 'monthly' => (int) ($c['monthly'] ?? 0), 'other' => (int) ($c['other'] ?? 0)];
    }

    #[Computed]
    public function archives(): Collection
    {
        return ReportArchive::latest()->limit(100)->get();
    }

    public function toggleSave(): void
    {
        if ($this->saving) {
            $this->saving = false;

            return;
        }
        $this->editId = null;
        $this->resetErrorBag();
        $title = $this->rep === 'saved' ? 'Daily report' : Engine::title($this->rep);
        $this->save = ['name' => $title . ' — owners', 'schedule' => $this->rep === 'daily' ? 'daily' : 'monthly', 'recipients' => 'owners', 'channels' => ['archive', 'email'], 'key' => $this->rep === 'saved' ? 'daily' : $this->rep];
        $this->saving = true;
    }

    public function editSaved(int $id): void
    {
        $s = SavedReport::findOrFail($id);
        $this->editId = $s->id;
        $this->resetErrorBag();
        $this->save = ['name' => $s->name, 'schedule' => $s->schedule, 'recipients' => $s->recipients, 'channels' => (array) $s->channels, 'key' => $s->report_key];
        $this->saving = true;
    }

    public function saveReport(): void
    {
        AccountsAccess::authorize('reports');
        $this->validate([
            'save.name' => 'required|string|max:120', 'save.schedule' => 'required|in:' . implode(',', array_keys(SavedReport::SCHEDULES)),
            'save.recipients' => 'required|in:' . implode(',', array_keys(SavedReport::RECIPIENTS)), 'save.channels' => 'array',
            'save.channels.*' => 'in:' . implode(',', array_keys(SavedReport::CHANNELS)),
        ], [], ['save.name' => 'name']);

        $data = ['name' => $this->save['name'], 'schedule' => $this->save['schedule'], 'recipients' => $this->save['recipients'], 'channels' => array_values($this->save['channels'] ?? [])];
        if ($this->editId) {
            SavedReport::whereKey($this->editId)->first()?->update($data);
        } else {
            SavedReport::create($data + ['report_key' => $this->save['key'], 'filters' => $this->filters(), 'created_by' => auth()->id()]);
        }

        $this->saving = false;
        unset($this->saved);
        $this->dispatch('toast', type: 'success', message: 'Report saved' . ($this->save['schedule'] !== 'none' ? ' · ' . SavedReport::SCHEDULES[$this->save['schedule']] . ' চলবে' : '') . '.');
    }

    public function openSaved(int $id): void
    {
        $s = SavedReport::findOrFail($id);
        $f = (array) $s->filters;
        $this->rep = $s->report_key;
        $this->period = $f['period'] ?? 'month';
        $this->from = $f['from'] ?? '';
        $this->to = $f['to'] ?? '';
        $this->asOf = $f['as_of'] ?? '';
        $this->accountId = (int) ($f['account_id'] ?? 0);
        $this->profileId = (int) ($f['profile_id'] ?? 0);
        $this->date = $s->schedule === 'none' ? ($f['date'] ?? '') : '';
        unset($this->report, $this->daily, $this->closing);
    }

    public function runNow(int $id): void
    {
        AccountsAccess::authorize('reports');
        try {
            $a = app(SavedReportRunner::class)->run(SavedReport::findOrFail($id));
            $this->dispatch('toast', type: 'success', message: "{$a->title} — archived and sent.");
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', message: 'Run failed: ' . $e->getMessage());
        }
        unset($this->saved, $this->archiveCounts, $this->archives);
    }

    public function deleteSaved(int $id): void
    {
        AccountsAccess::authorize('reports');
        SavedReport::whereKey($id)->delete();
        $this->saving = false;
        unset($this->saved);
    }

    public function render()
    {
        return view('livewire.accounts.reports', [
            'money' => fn ($v) => Bdt::money($v),
        ])->layoutData(['active' => 'reports', 'heading' => 'Reports', 'crumb' => 'Accounts / Reports']);
    }
}
