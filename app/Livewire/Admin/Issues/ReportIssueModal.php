<?php

namespace App\Livewire\Admin\Issues;

use App\Actions\Issues\ReportIssue;
use App\Enums\Issues\IssuePriority;
use App\Enums\Issues\IssueType;
use App\Livewire\Admin\Issues\Concerns\AuthorizesIssues;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * "Report issue" — one modal for every page. Open it with
 * $dispatch('open-report-issue', { subjectType, subjectId }) (both optional
 * for a general issue). Who it can be raised against comes from the
 * subject's issueParties(), when it has one (Order → its couriers).
 */
class ReportIssueModal extends Component
{
    use AuthorizesIssues;

    public bool $open = false;

    #[Locked]
    public ?string $subjectType = null;

    #[Locked]
    public ?int $subjectId = null;

    public string $type = '';
    public string $title = '';
    public string $priority = 'normal';
    public string $claimAmount = '';
    public string $description = '';
    /** "morph type|id" of the party, or '' */
    public string $party = '';
    public string $assignedTo = '';

    #[On('open-report-issue')]
    public function openFor(?string $subjectType = null, ?int $subjectId = null): void
    {
        if (! $this->canManageIssues()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'You are not allowed to report issues.']);

            return;
        }

        $this->resetValidation();
        $this->reset(['type', 'title', 'priority', 'claimAmount', 'description', 'party', 'assignedTo']);

        $subject = $this->resolveIssueSubject($subjectType, $subjectId);
        $this->subjectType = $subject?->getMorphClass();
        $this->subjectId = $subject?->getKey();

        $this->open = true;
    }

    public function save(ReportIssue $reportIssue): void
    {
        abort_unless($this->canManageIssues(), 403);

        $this->validate([
            'type'        => ['required', Rule::enum(IssueType::class)],
            'title'       => 'required|string|max:255',
            'priority'    => ['required', Rule::enum(IssuePriority::class)],
            'claimAmount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:5000',
            'assignedTo'  => 'nullable|exists:users,id',
        ]);

        $subject = $this->resolveIssueSubject($this->subjectType, $this->subjectId);
        $party = $this->parties($subject)->first(fn (Model $p) => $this->partyKey($p) === $this->party);

        $issue = $reportIssue->handle(IssueType::from($this->type), [
            'title'        => $this->title,
            'description'  => $this->description ?: null,
            'priority'     => IssuePriority::from($this->priority),
            'claim_amount' => $this->claimAmount,
            'assigned_to'  => $this->assignedTo ? (int) $this->assignedTo : null,
        ], $subject, $party);

        $this->open = false;
        $this->dispatch('issues-updated');
        $this->dispatch('toast', ['type' => 'success', 'message' => "Issue #{$issue->id} reported"]);
    }

    /** @return \Illuminate\Support\Collection<int, Model> */
    protected function parties(?Model $subject): \Illuminate\Support\Collection
    {
        return $subject && method_exists($subject, 'issueParties') ? collect($subject->issueParties()) : collect();
    }

    protected function partyKey(Model $party): string
    {
        return $party->getMorphClass() . '|' . $party->getKey();
    }

    public function render(): mixed
    {
        $subject = $this->open ? $this->resolveIssueSubject($this->subjectType, $this->subjectId) : null;

        return view('livewire.admin.issues.report-issue-modal', [
            'subject'    => $subject,
            'subjectName' => Issue::describe($subject),
            'parties'    => $this->parties($subject)->mapWithKeys(fn (Model $p) => [$this->partyKey($p) => Issue::describe($p)]),
            'types'      => IssueType::cases(),
            'priorities' => IssuePriority::cases(),
            'users'      => $this->open ? User::orderBy('name')->limit(200)->pluck('name', 'id') : collect(),
        ]);
    }
}
