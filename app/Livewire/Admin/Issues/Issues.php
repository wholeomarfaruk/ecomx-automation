<?php

namespace App\Livewire\Admin\Issues;

use App\Enums\Issues\IssuePriority;
use App\Enums\Issues\IssueStatus;
use App\Enums\Issues\IssueType;
use App\Livewire\Admin\Issues\Concerns\AuthorizesIssues;
use App\Models\Issue;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Issues extends Component
{
    use AuthorizesIssues;
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    /** 'active' (open + in progress), '' (all) or one IssueStatus value. */
    #[Url]
    public string $filterStatus = 'active';

    #[Url]
    public string $filterType = '';

    public function mount(): void
    {
        abort_unless($this->canViewIssues(), 403);
    }

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingFilterType(): void   { $this->resetPage(); }

    #[On('issues-updated')]
    public function refreshIssues(): void
    {
    }

    public function render(): mixed
    {
        $issues = Issue::query()
            ->with(['subject', 'party', 'assignee'])
            ->when($this->filterStatus === 'active', fn ($q) => $q->active())
            ->when($this->filterStatus !== 'active' && $this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterType !== '', fn ($q) => $q->where('type', $this->filterType))
            ->when($this->search !== '', fn ($q) => $q->where(fn ($qq) => $qq
                ->where('title', 'like', "%{$this->search}%")
                ->when(ctype_digit(ltrim($this->search, '#')), fn ($qqq) => $qqq->orWhere('id', (int) ltrim($this->search, '#')))))
            // Most urgent first, then newest.
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'normal', 'low')")
            ->latest('id')
            ->paginate(20);

        // One pass for the header cards.
        $stats = Issue::query()->selectRaw("
            SUM(status = 'open') AS open_count,
            SUM(status = 'in_progress') AS in_progress_count,
            SUM(CASE WHEN status IN ('open', 'in_progress') THEN GREATEST(COALESCE(claim_amount, 0) - recovered_amount, 0) ELSE 0 END) AS outstanding,
            SUM(recovered_amount) AS recovered
        ")->first();

        return view('livewire.admin.issues.issues', [
            'issues'     => $issues,
            'stats'      => $stats,
            'statuses'   => IssueStatus::cases(),
            'types'      => IssueType::cases(),
            'priorities' => IssuePriority::cases(),
            'canManage'  => $this->canManageIssues(),
        ])->layout('layouts.admin.admin');
    }
}
