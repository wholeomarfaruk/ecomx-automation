<?php

namespace App\Actions\Issues;

use App\Actions\Accounts\PostJournalEntry;
use App\Enums\Accounts\TransactionType;
use App\Enums\Issues\IssueStatus;
use App\Models\Account;
use App\Models\Issue;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * Closes an Issue out — Resolved (dealt with) or Closed (dropped) — with
 * what was recovered for it, e.g. the 1,800 Pathao paid for a parcel it
 * lost. With Accounts on, only the increase over what was already recorded
 * is posted: Dr the account it came into (that courier's Courier Cash when
 * the courier adjusts it in its payout, or cash/bank) / Cr Other Income
 * (4100). The lost goods themselves were written off when they were marked
 * lost (PostLostStock).
 */
class ResolveIssue
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(Issue $issue, IssueStatus $status, float $recovered, ?int $receivedIntoAccountId, ?string $resolution, string $entryDate): Issue
    {
        $recovered = round(max(0.0, $recovered), 2);
        $before = ['status' => $issue->status->value, 'recovered' => (float) $issue->recovered_amount, 'resolution' => $issue->resolution];
        $delta = round($recovered - (float) $issue->recovered_amount, 2);

        DB::transaction(function () use ($issue, $status, $recovered, $receivedIntoAccountId, $resolution, $entryDate, $delta) {
            $entryId = $issue->recovery_journal_entry_id;

            if ($delta > 0 && $receivedIntoAccountId && (bool) Setting::get('accounts_enabled', false, 'modules')) {
                $incomeId = Account::where('code', '4100')->value('id')
                    ?? throw new \RuntimeException('Chart of accounts is missing account code 4100.');

                $entryId = $this->postJournalEntry->handle([
                    'entry_date'       => $entryDate,
                    'description'      => "Claim recovered — Issue #{$issue->id}: {$issue->title}",
                    'transaction_type' => TransactionType::MANUAL_INCOME->value,
                    'source_type'      => Issue::class,
                    'source_id'        => $issue->id,
                    'purpose'          => "issue_recovery_{$issue->id}_" . now()->format('YmdHisu'),
                ], [
                    ['account_id' => $receivedIntoAccountId, 'debit' => $delta],
                    ['account_id' => $incomeId, 'credit' => $delta],
                ])->id;
            }

            $issue->update([
                'status'                    => $status,
                'recovered_amount'          => $recovered,
                'recovery_journal_entry_id' => $entryId,
                'resolution'                => $resolution,
                'resolved_by'               => auth()->id(),
                'resolved_at'               => now(),
            ]);
        });

        $after = ['status' => $status->value, 'recovered' => $recovered, 'resolution' => $resolution];
        $message = "Issue #{$issue->id} {$status->label()}" . ($recovered > 0 ? ' — recovered ' . number_format($recovered, 2) : '');

        activity('issues')->causedBy(auth()->user())->performedOn($issue)->event('updated')
            ->withProperties(['changes' => ['before' => $before, 'after' => $after]])->log($message);

        if ($issue->subject) {
            activity('issues')->causedBy(auth()->user())->performedOn($issue->subject)->event('updated')->log($message);
        }

        return $issue->refresh();
    }
}
