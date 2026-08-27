<?php

namespace App\Livewire\Admin\Marketing;

use App\Marketing\Enums\MarketingEventName;
use App\Models\Marketing\MarketingEvent;
use App\Models\Marketing\MarketingReportExport;
use App\Models\Marketing\MarketingSavedReport;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class Reports extends Component
{
    public string $name = '';
    public string $eventName = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    public bool $showSaveForm = false;

    private function filteredQuery()
    {
        return MarketingEvent::query()
            ->when($this->eventName !== '', fn ($q) => $q->where('event_name', $this->eventName))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('occurred_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('occurred_at', '<=', $this->dateTo));
    }

    private function currentFilters(): array
    {
        return [
            'eventName' => $this->eventName,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ];
    }

    public function loadFilters(int $savedReportId): void
    {
        $saved = MarketingSavedReport::findOrFail($savedReportId);

        $this->eventName = $saved->filters['eventName'] ?? '';
        $this->dateFrom = $saved->filters['dateFrom'] ?? '';
        $this->dateTo = $saved->filters['dateTo'] ?? '';
    }

    public function saveReport(): void
    {
        $this->validate(['name' => 'required|string|max:255']);

        MarketingSavedReport::create([
            'user_id' => auth()->id(),
            'name' => $this->name,
            'filters' => $this->currentFilters(),
        ]);

        $this->name = '';
        $this->showSaveForm = false;
    }

    public function deleteSavedReport(int $savedReportId): void
    {
        MarketingSavedReport::where('id', $savedReportId)->delete();
    }

    public function export(): void
    {
        $rows = $this->filteredQuery()->orderByDesc('occurred_at')->get();

        $filename = 'marketing-export-' . now()->format('Y-m-d-His') . '.csv';
        $path = 'marketing-exports/' . $filename;

        $csv = "Event ID,Event Name,Occurred At,Device ID,Customer ID,Source,Campaign,Value,Currency\n";
        foreach ($rows as $row) {
            $csv .= implode(',', [
                $row->event_id,
                $row->event_name,
                $row->occurred_at->toDateTimeString(),
                $row->device_id ?? '',
                $row->customer_id ?? '',
                $row->utm_source ?? '',
                $row->utm_campaign ?? '',
                $row->value ?? '',
                $row->currency ?? '',
            ]) . "\n";
        }

        Storage::disk('local')->put($path, $csv);

        MarketingReportExport::create([
            'user_id' => auth()->id(),
            'name' => $this->name !== '' ? $this->name : 'Ad-hoc export',
            'filters' => $this->currentFilters(),
            'format' => 'csv',
            'status' => 'completed',
            'file_path' => $path,
            'row_count' => $rows->count(),
        ]);

        $this->dispatch('notify', type: 'success', message: "Export ready — {$rows->count()} rows.");
    }

    public function downloadExport(int $exportId)
    {
        $export = MarketingReportExport::findOrFail($exportId);

        if (! $export->file_path || ! Storage::disk('local')->exists($export->file_path)) {
            $this->dispatch('notify', type: 'error', message: 'Export file no longer available.');

            return null;
        }

        return Storage::disk('local')->download($export->file_path, basename($export->file_path));
    }

    public function render()
    {
        $preview = $this->filteredQuery()->orderByDesc('occurred_at')->limit(10)->get();

        return view('livewire.admin.marketing.reports', [
            'preview' => $preview,
            'totalMatching' => $this->filteredQuery()->count(),
            'eventNames' => MarketingEventName::cases(),
            'savedReports' => MarketingSavedReport::latest()->get(),
            'exports' => MarketingReportExport::latest()->limit(20)->get(),
        ]);
    }
}
