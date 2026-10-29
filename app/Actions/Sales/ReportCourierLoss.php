<?php

namespace App\Actions\Sales;

use App\Actions\Issues\ReportIssue;
use App\Enums\Issues\IssuePriority;
use App\Enums\Issues\IssueStatus;
use App\Enums\Issues\IssueType;
use App\Models\Issue;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;

/**
 * Keeps one "Lost by courier" Issue per order in step with its lost items
 * (order_items.lost_quantity): opens it against the parcel's courier the
 * first time something is marked lost, refreshes its lines and suggested
 * claim (the lost units' value after the order discount — edit it to what
 * the courier actually agreed) when that changes, and closes it when
 * nothing is lost any more (the parcel turned up). An issue someone already
 * recorded a recovery on is left alone.
 */
class ReportCourierLoss
{
    public function __construct(protected ReportIssue $reportIssue) {}

    public function handle(Order $order): ?Issue
    {
        $order->loadMissing('items', 'courierShipments.courier');

        $factor = $order->returnDiscountFactor();
        $lines = $order->items
            ->filter(fn (OrderItem $item) => (float) $item->lost_quantity > 0)
            ->map(fn (OrderItem $item) => [
                'order_item_id' => $item->id,
                'product'       => $item->product_name . ($item->variant_name ? " ({$item->variant_name})" : ''),
                'quantity'      => (float) $item->lost_quantity,
                'value'         => round((float) $item->total_amount / max(1e-9, (float) $item->quantity) * (float) $item->lost_quantity * $factor, 2),
            ])
            ->values()
            ->all();

        $suggested = round(array_sum(array_column($lines, 'value')), 2);

        $issue = $order->issues()
            ->where('type', IssueType::COURIER_LOST)
            ->active()
            ->latest('id')
            ->first();

        if ($issue && (float) $issue->recovered_amount > 0) {
            return $issue;
        }

        if ($lines === []) {
            if ($issue) {
                $issue->update(['status' => IssueStatus::CLOSED, 'resolution' => 'Nothing lost any more — the parcel turned up.', 'resolved_by' => auth()->id(), 'resolved_at' => now()]);
                activity('issues')->causedBy(auth()->user())->performedOn($issue)->event('updated')->log("Issue #{$issue->id} closed — nothing lost any more");
            }

            return $issue;
        }

        $courier = $order->courierShipments->sortByDesc('id')->first()?->courier;
        $title = Str::limit(($courier?->name ?? 'Courier') . ' lost ' . collect($lines)->map(fn ($l) => rtrim(rtrim(number_format($l['quantity'], 3), '0'), '.') . ' × ' . $l['product'])->implode(', '), 220)
            . " — Order #{$order->id}";

        if (! $issue) {
            return $this->reportIssue->handle(IssueType::COURIER_LOST, [
                'title'        => $title,
                'description'  => 'Marked lost on the order page. Claim it from the courier; resolve this issue with what they pay.',
                'priority'     => IssuePriority::HIGH,
                'claim_amount' => $suggested,
                'meta'         => ['lines' => $lines, 'suggested_claim' => $suggested],
            ], $order, $courier);
        }

        // Keep an amount someone typed in; only follow the suggestion while it's untouched.
        $claimUntouched = abs((float) $issue->claim_amount - (float) ($issue->meta['suggested_claim'] ?? 0)) < 0.005;

        $issue->update([
            'title'        => $title,
            'claim_amount' => $claimUntouched ? $suggested : $issue->claim_amount,
            'meta'         => array_merge($issue->meta ?? [], ['lines' => $lines, 'suggested_claim' => $suggested]),
        ]);

        return $issue;
    }
}
