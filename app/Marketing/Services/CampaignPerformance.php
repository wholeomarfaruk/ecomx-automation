<?php

namespace App\Marketing\Services;

use App\Marketing\Enums\MarketingEventName;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Per-campaign funnel numbers, credited by last-touch attribution
 * (marketing_attributions.last_touch_campaign) rather than the event's own
 * utm_campaign. Only the landing page view carries UTM parameters in its
 * URL — the product views, add-to-carts and purchases that follow happen
 * on other pages and only know the campaign through the mk_last_touch
 * cookie, which is what last_touch_campaign records.
 */
final class CampaignPerformance
{
    /**
     * @param  array<int, string>  $keys
     * @return array<string, array{visitors: int, page_views: int, product_views: int, add_to_cart: int, checkout: int, purchases: int, revenue: float}>
     *         keyed by lower-cased campaign key (MySQL compares them case-insensitively)
     */
    public function forKeys(array $keys, ?CarbonInterface $since, ?CarbonInterface $until): array
    {
        $keys = array_values(array_filter($keys, 'filled'));

        if ($keys === []) {
            return [];
        }

        $base = fn () => DB::table('marketing_events as e')
            ->join('marketing_attributions as a', 'a.marketing_event_id', '=', 'e.id')
            ->whereIn('a.last_touch_campaign', $keys)
            ->when($since, fn ($q) => $q->where('e.occurred_at', '>=', $since))
            ->when($until, fn ($q) => $q->where('e.occurred_at', '<=', $until));

        $stats = [];
        $blank = self::empty();

        $base()
            ->selectRaw('a.last_touch_campaign AS campaign, e.event_name, COUNT(*) AS total, COALESCE(SUM(e.value), 0) AS value_sum')
            ->groupBy('a.last_touch_campaign', 'e.event_name')
            ->get()
            ->each(function ($row) use (&$stats, $blank) {
                $key = mb_strtolower($row->campaign);
                $stats[$key] ??= $blank;

                $column = match ($row->event_name) {
                    MarketingEventName::PAGE_VIEW->value => 'page_views',
                    MarketingEventName::VIEW_CONTENT->value => 'product_views',
                    MarketingEventName::ADD_TO_CART->value => 'add_to_cart',
                    MarketingEventName::INITIATE_CHECKOUT->value => 'checkout',
                    MarketingEventName::PURCHASE->value => 'purchases',
                    default => null,
                };

                if ($column) {
                    $stats[$key][$column] += (int) $row->total;
                }

                if ($row->event_name === MarketingEventName::PURCHASE->value) {
                    $stats[$key]['revenue'] += (float) $row->value_sum;
                }
            });

        $base()
            ->selectRaw('a.last_touch_campaign AS campaign, COUNT(DISTINCT e.device_id) AS visitors')
            ->groupBy('a.last_touch_campaign')
            ->get()
            ->each(function ($row) use (&$stats, $blank) {
                $key = mb_strtolower($row->campaign);
                $stats[$key] ??= $blank;
                $stats[$key]['visitors'] += (int) $row->visitors;
            });

        return $stats;
    }

    public static function empty(): array
    {
        return ['visitors' => 0, 'page_views' => 0, 'product_views' => 0, 'add_to_cart' => 0, 'checkout' => 0, 'purchases' => 0, 'revenue' => 0.0];
    }
}
