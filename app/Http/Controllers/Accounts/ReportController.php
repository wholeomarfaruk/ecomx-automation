<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\DayClosing;
use App\Models\ReportArchive;
use App\Services\Accounts\Reports;
use App\Support\AccountsAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reports: print / PDF copy (the browser's "Save as PDF"), Excel (CSV
 * with a BOM so Bangla opens right) and archived copies (saved runs,
 * closed days).
 */
class ReportController extends Controller
{
    protected function filters(Request $request): array
    {
        return $request->only(['period', 'from', 'to', 'as_of', 'account_id', 'profile_id']);
    }

    protected function check(string $key): void
    {
        AccountsAccess::authorize('reports');
        $keys = collect(Reports::GROUPS)->flatMap(fn ($items) => array_keys($items))->all();
        abort_unless(in_array($key, $keys, true) && ! in_array($key, ['daily', 'saved'], true), 404);
    }

    public function print(Request $request, string $key, Reports $reports)
    {
        $this->check($key);

        return view('accounts.reports.print', ['r' => $reports->build($key, $this->filters($request)), 'autoprint' => $request->boolean('autoprint', true)]);
    }

    public function export(Request $request, string $key, Reports $reports): StreamedResponse
    {
        $this->check($key);
        $r = $reports->build($key, $this->filters($request));
        $name = str($r['title'])->slug() . '-' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($r) {
            $out = fopen('php://output', 'w');
            fwrite($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, [$r['title'], $r['period']]);
            foreach ($r['tiles'] ?? [] as $t) {
                fputcsv($out, [$t[0], $t[1], $t[2]]);
            }
            fputcsv($out, []);
            $sections = ! empty($r['split']) ? $r['split'] : ['' => $r['rows']];
            foreach ($sections as $title => $rows) {
                if ($title) {
                    fputcsv($out, [$title]);
                }
                fputcsv($out, ! empty($r['split']) ? ['Particulars', 'Amount'] : array_column($r['columns'], 0));
                foreach ($rows as $row) {
                    fputcsv($out, array_map(fn ($c) => is_float($c) ? round($c, 2) : $c, $row['cells']));
                }
                fputcsv($out, []);
            }
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function archive(ReportArchive $archive)
    {
        AccountsAccess::authorize('reports');
        abort_unless(Storage::disk('local')->exists($archive->path), 404);

        return response(Storage::disk('local')->get($archive->path))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function closing(DayClosing $closing)
    {
        AccountsAccess::authorize('reports');
        abort_unless($closing->pdf_path && Storage::disk('local')->exists($closing->pdf_path), 404);

        return response(Storage::disk('local')->get($closing->pdf_path))->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
