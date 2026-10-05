<?php

namespace Tests\Unit\OrderIntake;

use App\OrderIntake\DraftResolver;
use PHPUnit\Framework\TestCase;

/** TextOrderParser + DraftResolver: the deterministic pass of AI Order. */
class OrderDraftTest extends TestCase
{
    use IntakeFixtures;

    public function test_labelled_order_resolves_fully_without_ai(): void
    {
        $drafts = $this->read("Name: Rahima Akter\nPhone: 01711-223344\nAddress: House 12, Road 5, Dhanmondi, Dhaka\nProduct: SF-0156 x2");

        $this->assertCount(1, $drafts);
        $d = $drafts[0];
        $this->assertSame([], $d['needs_ai']);
        $this->assertTrue($d['ready']);
        $this->assertSame('Rahima Akter', $d['name']['value']);
        $this->assertSame('01711223344', $d['phone']['display']);
        $this->assertSame('House 12, Road 5, Dhanmondi, Dhaka', $d['address']['value']);
        $this->assertSame(10, $d['area']['method_id']);
        $this->assertSame(1, $d['items'][0]['product_id']);
        $this->assertSame(2.0, $d['items'][0]['qty']);
    }

    public function test_unlabelled_chat_message_resolves_without_ai(): void
    {
        $chat = "Assalamu alaikum apu\nsunset charm ta nibo 2 ta\n\nRahima\n01711223344\nবাসা ১২, রোড ৫, মিরপুর ১০\n10:45 AM\nSeen";
        $d = $this->read($chat)[0];

        $this->assertSame([], $d['needs_ai']);
        $this->assertSame('Rahima', $d['name']['value']);
        $this->assertStringContainsString('মিরপুর', $d['address']['value']);
        $this->assertSame(1, $d['items'][0]['product_id']);
        $this->assertSame(2.0, $d['items'][0]['qty']);
        $this->assertSame('partial', $d['items'][0]['match']);
    }

    public function test_bangla_labels(): void
    {
        $d = $this->read("নাম: রহিমা আক্তার\nমোবাইল: ০১৭১১২২৩৩৪৪\nঠিকানা: গ্রাম চরপাড়া, ময়মনসিংহ\nপণ্য: SF-0170 x1")[0];

        $this->assertSame('রহিমা আক্তার', $d['name']['value']);
        $this->assertSame('1711223344', $d['phone']['value']);
        $this->assertSame(12, $d['area']['method_id']);
        $this->assertSame(3, $d['items'][0]['product_id']);
    }

    public function test_one_order_per_line_is_bulk(): void
    {
        $drafts = $this->read("Rahim, 01711223344, Sadar Road Cumilla, SF-0150 red x1\nKarim, 01811223344, Mirpur 10 Dhaka, SF-0170 x3");

        $this->assertCount(2, $drafts);
        $this->assertSame(['01711223344', '01811223344'], array_map(fn ($d) => $d['phone']['display'], $drafts));
        $this->assertSame(21, $drafts[0]['items'][0]['variant_id']);
        $this->assertSame(3.0, $drafts[1]['items'][0]['qty']);
        $this->assertSame([12, 10], array_map(fn ($d) => $d['area']['method_id'], $drafts));
    }

    public function test_blank_line_separated_forms_are_bulk(): void
    {
        $text = "Name: A Rahman\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: SF-0156\n\n"
            . "Name: B Akter\nPhone: 01911223344\nAddress: Bazar road, Bhaluka\nProduct: SF-0170 x2";

        $drafts = $this->read($text);

        $this->assertCount(2, $drafts);
        $this->assertSame(['A Rahman', 'B Akter'], array_map(fn ($d) => $d['name']['value'], $drafts));
    }

    public function test_a_second_number_is_an_alternative_phone_not_a_second_order(): void
    {
        $drafts = $this->read("Name: Rahim\nPhone: 01711223344\nAlternative: 01811223344\nAddress: Road 2, Uttara\nSF-0156");

        $this->assertCount(1, $drafts);
        $this->assertSame('01811223344', $drafts[0]['alt_phone']);
    }

    public function test_several_products_for_one_customer(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProducts: SF-0156 x1, SF-0170 x2")[0];

        $this->assertCount(2, $d['items']);
        $this->assertSame(2250.0 + 2 * 1050.0, $d['amounts']['subtotal']);
    }

    public function test_fixed_discount(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: SF-0156 x2\nDiscount: 100 tk")[0];

        $this->assertSame(100.0, $d['amounts']['discount']);
    }

    public function test_percentage_discount_is_taken_from_the_subtotal(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: SF-0156 x2\n10% discount")[0];

        $this->assertSame(450.0, $d['amounts']['discount']);
    }

    public function test_material_percentage_is_not_a_discount(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: SF-0156\n100% cotton, 50% silk")[0];

        $this->assertSame(0.0, $d['amounts']['discount']);
    }

    public function test_discount_worked_out_from_a_stated_total(): void
    {
        // 2 × 2250 + 60 delivery = 4560; customer was told 4400.
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: SF-0156 x2\nTotal: 4400")[0];

        $this->assertSame(160.0, $d['amounts']['discount']);
        $this->assertSame(4400.0, $d['amounts']['total']);
        $this->assertStringContainsString('worked out', implode(' ', array_column($d['issues'], 'message')));
    }

    public function test_cod_amount_with_advance(): void
    {
        // 2250 + 60 = 2310; 200 advance, COD 2050 → 2250 payable → 60 discount.
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: SF-0156\nadvance 200 bkash\nCOD 2050")[0];

        $this->assertSame(200.0, $d['amounts']['advance']);
        $this->assertSame(60.0, $d['amounts']['discount']);
        $this->assertSame(2050.0, $d['amounts']['due']);
    }

    public function test_final_amount_calculation_uses_catalogue_prices_and_delivery_quote(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Sadar Road, Cumilla\nProduct: SF-0150-BLUE x2, SF-0170\nDiscount 50")[0];

        $this->assertSame(2 * 2300.0 + 1050.0, $d['amounts']['subtotal']);
        $this->assertSame(120.0, $d['amounts']['delivery']);
        $this->assertSame('quote', $d['amounts']['delivery_source']);
        $this->assertSame(5650.0 + 120.0 - 50.0, $d['amounts']['total']);
    }

    public function test_stated_delivery_charge_overrides_the_quote(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: SF-0156\ndelivery charge 80")[0];

        $this->assertSame(80.0, $d['amounts']['delivery']);
        $this->assertSame('stated', $d['amounts']['delivery_source']);
    }

    public function test_unresolved_product_needs_ai(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: iphone charger")[0];

        $this->assertContains('products', $d['needs_ai']);
        $this->assertFalse($d['ready']);
    }

    public function test_ambiguous_product_needs_ai_instead_of_a_guess(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: Elara Dress")[0];

        $this->assertNull($d['items'][0]['product_id']);
        $this->assertContains('products', $d['needs_ai']);
    }

    public function test_variable_product_without_variant_needs_ai(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: SF-0150")[0];

        $this->assertContains('products', $d['needs_ai']);
    }

    public function test_unresolved_delivery_area_uses_the_default_zone_with_a_warning_not_ai(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: near the big mosque, house 4\nProduct: SF-0156")[0];

        $this->assertSame([], $d['needs_ai']);
        $this->assertStringContainsString('not recognised', implode(' ', array_column($d['issues'], 'message')));
        $this->assertSame(10, $d['area']['method_id']);
        $this->assertSame('default', $d['area']['source']);
    }

    public function test_missing_phone_needs_ai(): void
    {
        $d = $this->read("Name: Rahim\nAddress: Road 2, Uttara\nProduct: SF-0156")[0];

        $this->assertContains('phone', $d['needs_ai']);
    }

    public function test_existing_customer_covers_missing_name_and_address(): void
    {
        $customers = ['1711223344' => ['found' => true, 'national' => '1711223344', 'id' => 7, 'name' => 'Rahima Akter', 'address' => 'Road 2, Uttara', 'orders' => 3, 'cancelled' => 0, 'recent' => null]];
        $d = $this->read("01711223344\nSF-0156 x1", $customers)[0];

        $this->assertSame([], $d['needs_ai']);
        $this->assertSame('customer', $d['name']['source']);
        $this->assertSame('customer', $d['address']['source']);
        $this->assertSame(10, $d['area']['method_id']);
    }

    public function test_quantity_not_stated_is_flagged_not_silently_assumed(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: SF-0156")[0];

        $this->assertSame(1.0, $d['items'][0]['qty']);
        $this->assertSame('default', $d['items'][0]['qty_source']);
        $this->assertStringContainsString('Quantity not stated', implode(' ', array_column($d['issues'], 'message')));
    }

    public function test_sheet_row_uses_codes_and_keeps_intake_meta(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: SF-0150 blue x2 @2250\nDiscount: 100\nNote: call first")[0];
        $row = DraftResolver::toSheetRow($d, 'whatsapp', 42);

        $this->assertSame('01711223344', $row['cells']['phone']);
        $this->assertSame('SF-0150-BLUE x2 @2250', $row['cells']['products']);
        $this->assertSame('10', $row['cells']['method']);
        $this->assertSame('100', $row['cells']['discount']);
        $this->assertSame('whatsapp', $row['cells']['source']);
        $this->assertSame('call first', $row['cells']['note']);
        $this->assertSame(42, $row['meta']['intakeId']);
        $this->assertSame('parser', $row['meta']['via']);
    }
}
