<?php

namespace App\Livewire\Admin\Issues;

use App\Livewire\Admin\Issues\Concerns\AuthorizesIssues;
use App\Models\Issue;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The Issues card for any record's page — the record's issues plus a
 * "Report issue" button. Drop it on a page of a model using HasIssues:
 *
 *   @livewire('admin.issues.issue-panel', ['subjectType' => $model->getMorphClass(), 'subjectId' => $model->id], key(...))
 */
class IssuePanel extends Component
{
    use AuthorizesIssues;

    #[Locked]
    public string $subjectType;

    #[Locked]
    public int $subjectId;

    #[On('issues-updated')]
    public function refreshIssues(): void
    {
    }

    public function render(): mixed
    {
        $issues = $this->canViewIssues()
            ? Issue::query()
                ->where('subject_type', $this->subjectType)
                ->where('subject_id', $this->subjectId)
                ->with('party')
                ->latest('id')
                ->limit(20)
                ->get()
            : collect();

        return view('livewire.admin.issues.issue-panel', [
            'issues'    => $issues,
            'canView'   => $this->canViewIssues(),
            'canManage' => $this->canManageIssues(),
        ]);
    }
}
