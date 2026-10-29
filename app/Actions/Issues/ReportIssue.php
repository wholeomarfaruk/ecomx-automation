<?php

namespace App\Actions\Issues;

use App\Enums\Issues\IssuePriority;
use App\Enums\Issues\IssueStatus;
use App\Enums\Issues\IssueType;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Model;

/**
 * Opens an Issue — the one write path for raising one, from any module.
 * $subject is what it's about (Order, Product, ...), $party who's
 * answerable (Courier, Supplier, ...); both optional. Logged on the issue
 * and on its subject, so it shows in e.g. the order's timeline too.
 */
class ReportIssue
{
    /**
     * @param  array{title: string, description?: ?string, priority?: IssuePriority|string|null, claim_amount?: float|string|null, assigned_to?: ?int, meta?: ?array}  $data
     */
    public function handle(IssueType $type, array $data, ?Model $subject = null, ?Model $party = null): Issue
    {
        $claim = $data['claim_amount'] ?? null;

        $issue = Issue::create([
            'type'         => $type,
            'status'       => IssueStatus::OPEN,
            'priority'     => $data['priority'] ?? IssuePriority::NORMAL,
            'title'        => $data['title'],
            'description'  => $data['description'] ?? null,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id'   => $subject?->getKey(),
            'party_type'   => $party?->getMorphClass(),
            'party_id'     => $party?->getKey(),
            'claim_amount' => $claim === null || $claim === '' ? null : round((float) $claim, 2),
            'assigned_to'  => $data['assigned_to'] ?? null,
            'meta'         => $data['meta'] ?? null,
            'reported_by'  => auth()->id(),
        ]);

        $message = "Issue #{$issue->id} reported: {$issue->title}";

        activity('issues')->causedBy(auth()->user())->performedOn($issue)->event('created')->log($message);

        if ($subject) {
            activity('issues')->causedBy(auth()->user())->performedOn($subject)->event('updated')->log($message);
        }

        return $issue;
    }
}
