<?php

namespace App\Models\Concerns;

use App\Enums\Issues\IssueStatus;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Lets a model carry Issues — as what they're about (issues()) and/or as
 * who's answerable for them (issuesAgainst()). Add it to a model, then drop
 * the IssuePanel component on its page (see IssuePanel). An optional
 * issueParties() method on the model lists who an issue about it can be
 * raised against (Order → its couriers).
 */
trait HasIssues
{
    public function issues(): MorphMany
    {
        return $this->morphMany(Issue::class, 'subject');
    }

    public function issuesAgainst(): MorphMany
    {
        return $this->morphMany(Issue::class, 'party');
    }

    /**
     * The counts <x-issue-badge> needs, for withCount() on a list or
     * loadCount() on one record: open_issues_count, resolved_issues_count,
     * closed_issues_count — one subquery each, no per-row queries.
     *
     * @return array<string, \Closure>
     */
    public static function issueBadgeCounts(): array
    {
        return [
            'issues as open_issues_count'     => fn ($q) => $q->whereIn('status', IssueStatus::activeValues()),
            'issues as resolved_issues_count' => fn ($q) => $q->where('status', IssueStatus::RESOLVED->value),
            'issues as closed_issues_count'   => fn ($q) => $q->where('status', IssueStatus::CLOSED->value),
        ];
    }
}
