<?php

namespace Tests\Unit\OrderIntake;

use App\OrderIntake\DeliveryAreas;
use PHPUnit\Framework\TestCase;

class DeliveryAreasTest extends TestCase
{
    use IntakeFixtures;

    public function test_zone_kind_is_read_from_its_name(): void
    {
        $this->assertSame(DeliveryAreas::INSIDE, DeliveryAreas::kindOf('Inside Dhaka'));
        $this->assertSame(DeliveryAreas::OUTSIDE, DeliveryAreas::kindOf('Outside Dhaka'));
        $this->assertSame(DeliveryAreas::SUB, DeliveryAreas::kindOf('Dhaka Sub Area'));
        $this->assertSame(DeliveryAreas::OUTSIDE, DeliveryAreas::kindOf('ঢাকার বাইরে'));
    }

    public function test_dhaka_city_areas_are_inside(): void
    {
        foreach (['House 5, Road 3, Dhanmondi', 'Mirpur 10, Dhaka', 'বাসা ১২, উত্তরা সেক্টর ৭'] as $address) {
            $this->assertSame(10, $this->areas()->resolve($address)['method_id'], $address);
        }
    }

    public function test_districts_and_upazilas_are_outside(): void
    {
        $this->assertSame(12, $this->areas()->resolve('Sadar Road, Cumilla')['method_id']);
        $this->assertSame(12, $this->areas()->resolve('গ্রাম: চরপাড়া, ময়মনসিংহ')['method_id']);
        $this->assertSame(12, $this->areas()->resolve('Bazar road, Bhaluka')['method_id']);
    }

    public function test_a_district_beats_a_dhaka_area_with_the_same_name(): void
    {
        // There's a Mirpur upazila in Kushtia.
        $this->assertSame(12, $this->areas()->resolve('Mirpur, Kushtia')['method_id']);
    }

    public function test_dhaka_sub_areas(): void
    {
        $this->assertSame(11, $this->areas()->resolve('Hemayetpur, Savar')['method_id']);
    }

    public function test_stated_inside_or_outside_wins(): void
    {
        $this->assertSame(12, $this->areas()->resolve('House 5', 'delivery outside dhaka please')['method_id']);
        $this->assertSame(10, $this->areas()->resolve('House 5', 'ঢাকার ভিতরে')['method_id']);
    }

    public function test_only_dhaka_is_low_confidence(): void
    {
        $area = $this->areas()->resolve('Dhaka');

        $this->assertSame(10, $area['method_id']);
        $this->assertLessThan(0.7, $area['confidence']);
        $this->assertNotNull($area['note']);
    }

    public function test_unknown_place_is_unresolved(): void
    {
        $this->assertNull($this->areas()->resolve('near the big mosque')['method_id']);
    }
}
