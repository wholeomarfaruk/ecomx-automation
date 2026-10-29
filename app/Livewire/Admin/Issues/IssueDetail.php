<?php

namespace App\Livewire\Admin\Issues;

use App\Actions\Issues\ResolveIssue;
use App\Enums\Issues\IssuePriority;
use App\Enums\Issues\IssueStatus;
use App\Livewire\Admin\Issues\Concerns\AuthorizesIssues;
use App\Models\Account;
use App\Models\Courier;
use App\Models\Issue;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class IssueDetail extends Component
{
    use AuthorizesIssues;

    public int $issueId;

    public string $status = '';
    public string $priority = '';
    public string $assignedTo = '';
    public string $claimAmount = '';
    public string $description = '';

    public string $note = '';

    public bool $resolveModal = false;
    public string $resolveStatus = 'resolved';
    public string $recoveredAmount = '';
    public string $recoveryAccountId = '';
    public string $resolution = '';

    public function mount(int $id): void
    {
        abort_unless($this->canViewIssues(), 403);

        $this->issueId = Issue::findOrFail($id)->id;
        $this->fillFields();
    }

    protected function issue(): Issue
    {
        return Issue::findOrFail($this->issueId);
    }

    protected function fillFields(): void
    {
        $issue = $this->issue();

        $this->status      = $issue->status->value;
        $this->priority    = $issue->priority->value;
        $this->assignedTo  = (string) ($issue->assigned_to ?? '');
        $this->claimAmount = $issue->claim_amount !== null ? (string) (float) $issue->claim_amount : '';
        $this->description = (string) $issue->description;
    }

    protected function accountsEnabled(): bool
    {
        return (bool) Setting::get('accounts_enabled', false, 'modules');
    }

    /** Status (open / in progress — resolving goes through the Resolve modal), priority, assignee, claim, details. */
    public function save(): void
    {
        abort_unless($this->canManageIssues(), 403);

        $issue = $this->issue();

        $this->validate([
            'status'      => ['required', Rule::in([...IssueStatus::activeValues(), $issue->status->value])],
            'priority'    => ['required', Rule::enum(IssuePriority::class)],
            'assignedTo'  => 'nullable|exists:users,id',
            'claimAmount' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:5000',
        ]);

        $fields = [
            'status'       => $this->status,
            'priority'     => $this->priority,
            'assigned_to'  => $this->assignedTo !== '' ? (int) $this->assignedTo : null,
            'claim_amount' => $this->claimAmount !== '' ? round((float) $this->claimAmount, 2) : null,
            'description'  => $this->description ?: null,
        ];

        $before = collect($fields)->mapWithKeys(fn ($v, $k) => [$k => $issue->getRawOriginal($k)])->all();
        // Moved back to open / in progress from resolved or closed — it isn't resolved any more.
        $reopened = ! $issue->status->isActive() && IssueStatus::from($this->status)->isActive();
        $issue->update($fields + ($reopened ? ['resolved_at' => null, 'resolved_by' => null] : []));

        activity('issues')->causedBy(auth()->user())->performedOn($issue)->event('updated')
            ->withProperties(['changes' => ['before' => $before, 'after' => $fields]])
            ->log("Issue #{$issue->id} updated");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Issue saved']);
    }

    public function addNote(): void
    {
        abort_unless($this->canManageIssues(), 403);

        $this->validate(['note' => 'required|string|max:2000']);

        activity('issues')->causedBy(auth()->user())->performedOn($this->issue())->event('commented')->log($this->note);

        $this->note = '';
    }

    public function openResolveModal(): void
    {
        abort_unless($this->canManageIssues(), 403);

        $issue = $this->issue()->load('party');

        $this->resetValidation();
        $this->resolveStatus = 'resolved';
        $this->recoveredAmount = (string) (float) ((float) $issue->recovered_amount > 0 ? $issue->recovered_amount : ($issue->claim_amount ?? 0));
        $this->resolution = (string) $issue->resolution;
        // A courier usually adjusts a claim in its next payout — default to its Courier Cash account.
        $this->recoveryAccountId = (string) ($issue->party instanceof Courier ? ($issue->party->cashAccount?->id ?? '') : '');
        $this->resolveModal = true;
    }

    public function resolve(ResolveIssue $resolveIssue): void
    {
        abort_unless($this->canManageIssues(), 403);

        $issue = $this->issue();
        $needsAccount = $this->accountsEnabled() && (float) $this->recoveredAmount > (float) $issue->recovered_amount;

        $this->validate([
            'resolveStatus'     => ['required', Rule::in([IssueStatus::RESOLVED->value, IssueStatus::CLOSED->value])],
            'recoveredAmount'   => 'nullable|numeric|min:' . (float) $issue->recovered_amount,
            'recoveryAccountId' => [$needsAccount ? 'required' : 'nullable', 'exists:accounts,id'],
            'resolution'        => 'required|string|max:2000',
        ], [
            'recoveredAmount.min' => 'Already recorded ' . number_format((float) $issue->recovered_amount, 2) . ' — it can only go up (post a manual journal to undo a recovery).',
        ], [
            'recoveryAccountId' => 'received into',
        ]);

        $resolveIssue->handle(
            $issue,
            IssueStatus::from($this->resolveStatus),
            (float) $this->recoveredAmount,
            $this->recoveryAccountId !== '' ? (int) $this->recoveryAccountId : null,
            $this->resolution,
            now()->toDateString(),
        );

        $this->resolveModal = false;
        $this->fillFields();
        $this->dispatch('toast', ['type' => 'success', 'message' => "Issue {$this->resolveStatus}"]);
    }

    public function reopen(): void
    {
        abort_unless($this->canManageIssues(), 403);

        $issue = $this->issue();
        $issue->update(['status' => IssueStatus::OPEN, 'resolved_at' => null, 'resolved_by' => null]);

        activity('issues')->causedBy(auth()->user())->performedOn($issue)->event('updated')->log("Issue #{$issue->id} reopened");

        $this->fillFields();
    }

    public function render(): mixed
    {
        $issue = Issue::with(['subject', 'party', 'reporter', 'assignee', 'resolver', 'recoveryEntry'])->findOrFail($this->issueId);

        return view('livewire.admin.issues.issue-detail', [
            'issue'           => $issue,
            'canManage'       => $this->canManageIssues(),
            'accountsEnabled' => $this->accountsEnabled(),
            'priorities'      => IssuePriority::cases(),
            'activeStatuses'  => [IssueStatus::OPEN, IssueStatus::IN_PROGRESS],
            'users'           => User::orderBy('name')->limit(200)->pluck('name', 'id'),
            'recoveryAccounts' => $this->resolveModal
                ? Account::active()
                    ->where(fn ($q) => $q->whereIn('subtype', ['cash', 'bank', 'mobile_banking'])
                        ->orWhere(fn ($qq) => $qq->where('subtype', 'courier')->whereNotNull('courier_id')))
                    ->orderBy('code')
                    ->get()
                : collect(),
            'timeline'        => Activity::query()
                ->where('subject_type', $issue->getMorphClass())
                ->where('subject_id', $issue->id)
                ->with('causer')
                ->latest('id')
                ->limit(100)
                ->get(),
        ])->layout('layouts.admin.admin');
    }
}
