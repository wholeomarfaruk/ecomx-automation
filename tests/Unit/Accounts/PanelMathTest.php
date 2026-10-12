<?php

namespace Tests\Unit\Accounts;

use App\Services\Accounts\Commission;
use App\Services\Accounts\KistiBuilder;
use App\Services\Accounts\Reports;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * Accounts Panel calculations that need no database: kisti schedules
 * (spec §7.6), commission tiers (§7.17) and report periods.
 */
class PanelMathTest extends TestCase
{
    public function test_kisti_rounds_up_to_ten_and_last_takes_the_rest(): void
    {
        $s = (new KistiBuilder)->build('monthly', 12, '2026-11-15', 200000, 'flat', 10);

        $this->assertSame(220000.0, $s['total']);
        $this->assertSame(18340.0, $s['kisti']);
        $this->assertSame(18260.0, $s['last']);
        $this->assertSame(12, $s['count']);
        $this->assertEqualsWithDelta(220000, array_sum(array_column($s['rows'], 'amount')), 0.001);
        $this->assertEqualsWithDelta(200000, array_sum(array_column($s['rows'], 'principal')), 0.01);
        $this->assertSame('2026-11-15', $s['rows'][0]['due']->toDateString());
        $this->assertSame('2027-10-15', $s['end']->toDateString());
    }

    public function test_kisti_never_collects_more_than_the_total(): void
    {
        $s = (new KistiBuilder)->build('monthly', 12, '2026-11-15', 100, 'none');

        $this->assertEqualsWithDelta(100, array_sum(array_column($s['rows'], 'amount')), 0.001);
        $this->assertSame(10, $s['count']);
    }

    public function test_reducing_emi_and_custom_offsets(): void
    {
        $r = (new KistiBuilder)->build('monthly', 24, '2026-11-15', 200000, 'reducing', 12);
        $this->assertEqualsWithDelta(200000, array_sum(array_column($r['rows'], 'principal')), 0.01);
        $this->assertGreaterThan(200000, $r['total']);

        $c = (new KistiBuilder)->build('custom', 0, '2026-11-15', 200000, 'none', 0, '15, 45, 90');
        $this->assertSame(3, $c['count']);
        $this->assertSame('2026-11-30', $c['rows'][0]['due']->toDateString());
        $this->assertSame([15, 45, 90], KistiBuilder::offsets('90,15 / 45'));
    }

    public function test_commission_whole_and_slab(): void
    {
        $tiers = [
            ['from' => 0, 'to' => 500000, 'rate' => 0],
            ['from' => 500000, 'to' => 1000000, 'rate' => 2],
            ['from' => 1000000, 'to' => 2000000, 'rate' => 3],
            ['from' => 2000000, 'to' => null, 'rate' => 4],
        ];

        $this->assertSame(39000.0, Commission::amount($tiers, 'whole', 1300000));      // 13L × 3%
        $this->assertSame(19000.0, Commission::amount($tiers, 'slab', 1300000));       // 5L×0 + 5L×2% + 3L×3%
        $this->assertSame(60000.0, Commission::amount($tiers, 'slab', 2500000));
        $this->assertSame(0, Commission::tierIndex($tiers, 499999));
        $this->assertSame(3, Commission::tierIndex($tiers, 2000000));
        $this->assertSame(500.0, Commission::amount([['from' => 0, 'to' => null, 'rate' => 50]], 'whole', 10, 'quantity'));  // ৳50 / pc
    }

    public function test_report_comparison_period(): void
    {
        [$from, $to] = Reports::previous(CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-12'));
        $this->assertSame(['2026-09-01', '2026-09-30'], [$from->toDateString(), $to->toDateString()]);

        [$from, $to] = Reports::previous(CarbonImmutable::parse('2026-10-05'), CarbonImmutable::parse('2026-10-11'));
        $this->assertSame(['2026-09-28', '2026-10-04'], [$from->toDateString(), $to->toDateString()]);
    }
}
