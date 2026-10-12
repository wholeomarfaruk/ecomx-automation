<?php

namespace App\Services\Accounts;

use App\Actions\Accounts\PostOrderCompletion;
use App\Actions\Accounts\PostOrderReturn;
use App\Enums\Accounts\JournalEntryStatus;
use App\Enums\Sales\OrderStatus;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\Setting;
use App\Support\AccountsSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Orders waiting to go into the books (since order status follows the
 * courier, nothing posts on delivery by itself any more):
 *
 *   to post   delivered (fulfilled / partial) orders whose sale + COGS isn't
 *             posted yet — or has newly packed quantity since the last post
 *   returns   orders with a posted sale and newly returned quantity whose
 *             reversal isn't posted yet
 *
 * Posting uses the existing order actions (PostOrderCompletion /
 * PostOrderReturn), dated on delivery / return day unless that is inside
 * the locked period. A sale needs every product line packed from a batch
 * (cost comes from the batch), so unpacked orders are listed as blocked.
 */
class OrderPosting
{
    public const AUTO_KEY = 'auto_post_orders';

    public static function accountsOn(): bool
    {
        return (bool) Setting::get('accounts_enabled', false, 'modules');
    }

    public static function inventoryOn(): bool
    {
        return (bool) Setting::get('inventory_enabled', false, 'modules');
    }

    public static function autoPost(): bool
    {
        return (bool) AccountsSettings::get(self::AUTO_KEY, false);
    }

    /** Orders whose sale has a posted journal. */
    protected function saleExists(): \Closure
    {
        return fn ($q) => $q->select(DB::raw(1))->from('journal_entries as je')
            ->whereColumn('je.source_id', 'orders.id')->where('je.source_type', Order::class)
            ->where('je.purpose', 'like', 'sale%')->where('je.status', JournalEntryStatus::POSTED->value);
    }

    /** SQL: packed batch quantity not yet costed into a sale. */
    protected function unpostedBatches(): \Closure
    {
        return fn ($q) => $q->select(DB::raw(1))->from('order_item_batches as oib')
            ->join('order_items as oi', 'oi.id', '=', 'oib.order_item_id')
            ->whereColumn('oi.order_id', 'orders.id')->whereNull('oib.posted_at');
    }

    /** Delivered orders with a sale (or new packed quantity) still to post. */
    public function toPostQuery()
    {
        return Order::query()->whereNotNull('customer_id')
            ->where('status', OrderStatus::DELIVERED->value)
            ->where(fn ($q) => $q->whereNotExists($this->saleExists())->orWhereExists($this->unpostedBatches()));
    }

    /** Orders with a posted sale and something returned. Whether there's an un-posted delta is checked per order. */
    protected function returnCandidates()
    {
        return Order::query()->whereNotNull('customer_id')
            ->where('updated_at', '>=', now()->subDays(120))      // older returns were dealt with long ago
            ->whereExists($this->saleExists())
            ->where(fn ($q) => $q->whereIn('status', [OrderStatus::RETURNED->value, OrderStatus::PARTIALLY_RETURNED->value, OrderStatus::REFUNDED->value])
                ->orWhereExists(fn ($s) => $s->select(DB::raw(1))->from('order_items as ri')->whereColumn('ri.order_id', 'orders.id')->where('ri.returned_quantity', '>', 0)));
    }

    /** @return array{sales: int, returns: int} */
    public function counts(): array
    {
        if (! self::accountsOn()) {
            return ['sales' => 0, 'returns' => 0];
        }

        // Shown on the dashboard and Posting Pending — cached briefly, cleared on every post.
        return \Illuminate\Support\Facades\Cache::remember('accounts.order_posting_counts', 300,
            fn () => ['sales' => $this->toPostQuery()->count(), 'returns' => $this->returns()->count()]);
    }

    /** Delivered orders to post, with what's blocking each (if anything). */
    public function sales(int $limit = 300): Collection
    {
        return $this->toPostQuery()->with(['customer:id,full_name,phone,master_profile_id', 'items.batchAllocations:id,order_item_id,quantity,posted_at'])
            ->orderBy('courier_status_updated_at')->orderBy('id')->limit($limit)->get()
            ->map(fn (Order $o) => ['order' => $o, 'blocked' => $this->blocker($o), 'date' => $this->postDate($o, 'delivered')]);
    }

    /** Orders with a return reversal still to post: [order, amount, cost]. */
    public function returns(int $limit = 300): Collection
    {
        $action = app(PostOrderReturn::class);

        return $this->returnCandidates()->with(['customer:id,full_name,phone', 'items'])->orderByDesc('updated_at')->limit($limit)->get()
            ->map(function (Order $o) use ($action) {
                [$sale, $cost] = rescue(fn () => $action->newlyReturnedAmounts($o), [0, 0], false);

                return ['order' => $o, 'amount' => (float) $sale, 'cost' => (float) $cost, 'date' => $this->postDate($o, 'returned')];
            })
            ->filter(fn ($r) => $r['amount'] > 0.004 || $r['cost'] > 0.004)->values();
    }

    /** Why this order can't post yet, or null. */
    public function blocker(Order $o): ?string
    {
        if (! self::inventoryOn()) {
            // Sale cost comes from the packed batches; without the Inventory module there are none.
            return $o->items->contains(fn ($i) => ! $i->is_gift && $i->product_id) ? 'Inventory module is off — sale cost (COGS) needs packed batches.' : null;
        }
        $missing = $o->items->filter(fn ($i) => ! $i->is_gift && $i->product_id
            && (float) $i->batchAllocations->sum('quantity') + 0.001 < (float) $i->quantity);

        return $missing->isEmpty() ? null
            : 'Batch not picked: ' . $missing->map(fn ($i) => $i->product_name . ' (' . rtrim(rtrim(number_format((float) $i->batchAllocations->sum('quantity'), 3), '0'), '.') . '/' . rtrim(rtrim(number_format((float) $i->quantity, 3), '0'), '.') . ')')->take(3)->implode(', ');
    }

    /** Delivery / return day — or today when that day is inside the locked period. */
    public function postDate(Order $o, string $kind): string
    {
        $d = $kind === 'delivered'
            ? ($o->courier_status_updated_at ?? $o->completed_at ?? $o->updated_at)
            : ($o->updated_at);
        $date = CarbonImmutable::parse($d ?? now())->toDateString();
        $lock = AccountsSettings::lockDate();

        return $lock && $date <= $lock->toDateString() ? today()->toDateString() : min($date, today()->toDateString());
    }

    public function postSale(Order $o): void
    {
        $this->guard();
        if ($why = $this->blocker($o->loadMissing('items.batchAllocations'))) {
            throw new RuntimeException("Order #{$o->id}: {$why}");
        }
        app(PostOrderCompletion::class)->handle($o->fresh(['items', 'customer']), $this->postDate($o, 'delivered'));
        \Illuminate\Support\Facades\Cache::forget('accounts.order_posting_counts');
    }

    public function postReturn(Order $o): void
    {
        $this->guard();
        app(PostOrderReturn::class)->handle($o->fresh(['items', 'customer']), $this->postDate($o, 'returned'), "Return — Order #{$o->id}");
        \Illuminate\Support\Facades\Cache::forget('accounts.order_posting_counts');
    }

    protected function guard(): void
    {
        if (! self::accountsOn()) {
            throw new RuntimeException('The Accounts module is off.');
        }
    }

    /**
     * Post many: sales first, then returns. Returns [posted, skipped messages].
     *
     * @param  list<int>  $saleIds
     * @param  list<int>  $returnIds
     * @return array{0: int, 1: list<string>}
     */
    public function postMany(array $saleIds, array $returnIds = []): array
    {
        $posted = 0;
        $skipped = [];
        foreach (Order::whereIn('id', $saleIds ?: [0])->get() as $o) {
            try {
                $this->postSale($o);
                $posted++;
            } catch (Throwable $e) {
                $skipped[] = "#{$o->id}: " . $e->getMessage();
            }
        }
        foreach (Order::whereIn('id', $returnIds ?: [0])->get() as $o) {
            try {
                $this->postReturn($o);
                $posted++;
            } catch (Throwable $e) {
                $skipped[] = "#{$o->id} return: " . $e->getMessage();
            }
        }

        return [$posted, $skipped];
    }

    /** Scheduler (when auto-post is on): everything that isn't blocked. */
    public function runAuto(): array
    {
        if (! self::accountsOn() || ! self::autoPost()) {
            return [0, []];
        }

        return $this->postMany(
            $this->sales()->whereNull('blocked')->pluck('order.id')->all(),
            $this->returns()->pluck('order.id')->all(),
        );
    }
}
