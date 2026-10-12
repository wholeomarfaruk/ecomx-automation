<?php

namespace App\Services\Accounts;

use App\Jobs\Accounts\RunSavedReport;
use App\Models\ReportArchive;
use App\Models\SavedReport;
use App\Models\User;
use App\Support\AccountsSettings;
use App\Support\Bdt;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

/**
 * Runs a saved report (spec §5.11 / §7.21): builds it with its saved
 * filters, stores the copy in the archive and delivers it to the
 * recipients over the saved channels.
 */
class SavedReportRunner
{
    public function __construct(protected Reports $reports, protected DailyReport $daily, protected ReportDelivery $delivery) {}

    public function run(SavedReport $s): ReportArchive
    {
        $f = (array) ($s->filters ?? []);

        if ($s->report_key === 'daily') {
            $date = $s->schedule !== 'none' ? CarbonImmutable::today() : CarbonImmutable::parse($f['date'] ?? today())->startOfDay();
            $data = $this->daily->data($date);
            $html = view('accounts.reports.daily-print', ['data' => $data, 'note' => null, 'version' => 1, 'auto' => true])->render();
            $title = 'Daily report · ' . $date->format('d M Y');
            $period = $date->toDateString();
            $summary = $this->daily->summaryText($data);
        } else {
            $r = $this->reports->build($s->report_key, $f);
            $html = view('accounts.reports.print', ['r' => $r])->render();
            $title = $r['title'] . ' · ' . $r['period'];
            $period = $r['period'];
            $summary = $this->summary($r);
        }

        $path = 'report-archives/saved/' . now()->format('Y/m') . '/' . $s->id . '-' . now()->format('Ymd-His') . '.html';
        Storage::disk('local')->put($path, $html);
        $kind = $s->report_key === 'daily' ? 'daily' : (in_array($s->schedule, ['monthly', 'month_end'], true) ? 'monthly' : 'other');
        $archive = ReportArchive::create(['saved_report_id' => $s->id, 'report_key' => $s->report_key, 'kind' => $kind, 'title' => "{$s->name} · {$period}", 'period' => $period, 'path' => $path]);

        $channels = array_values(array_diff((array) ($s->channels ?? []), ['archive']));
        if ($channels) {
            $this->delivery->send($s->recipients, $channels, $title, $summary, $path, $s->created_by ? User::find($s->created_by) : null);
        }

        $s->update(['last_run_at' => now()]);

        return $archive;
    }

    /** A one-line summary for SMS: the tiles, or the last total row. */
    public function summary(array $r): string
    {
        $parts = collect($r['tiles'] ?? [])->map(fn ($t) => $t[0] . ' ' . (is_numeric($t[1]) ? Bdt::money($t[1]) : $t[1]))->take(3);
        if ($parts->isEmpty()) {
            $total = collect($r['rows'] ?? [])->last(fn ($row) => in_array($row['kind'], ['total', 'net'], true));
            $num = $total ? collect($total['cells'])->filter(fn ($c) => is_numeric($c))->last() : null;
            $parts = collect([$num !== null ? 'Total ' . Bdt::money($num) : '']);
        }

        return "{$r['title']} ({$r['period']}): " . $parts->filter()->implode(', ') . ' — ' . AccountsSettings::businessName();
    }

    /** Queue every saved report on a schedule slot. */
    public function dispatchDue(string $schedule): int
    {
        $ids = SavedReport::where('schedule', $schedule)->pluck('id');
        $ids->each(fn ($id) => RunSavedReport::dispatch($id));

        return $ids->count();
    }
}
