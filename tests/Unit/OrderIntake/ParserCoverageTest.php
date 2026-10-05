<?php

namespace Tests\Unit\OrderIntake;

use App\OrderIntake\TextOrderParser;
use PHPUnit\Framework\TestCase;

/**
 * Real-world message shapes the parser must settle on its own — every one
 * of these used to (or could) end up calling the AI.
 */
class ParserCoverageTest extends TestCase
{
    use IntakeFixtures;

    private function readWithShop(string $text): array
    {
        $parser = new TextOrderParser($this->catalog(), $this->areas(), ['1999887766'], ['Seldom Fashion']);
        $resolver = $this->resolver();

        return array_map(fn ($raw) => $resolver->resolve($raw), $parser->parse($text));
    }

    public function test_screenshot_case_safina_yallow_with_price_delivery_and_total(): void
    {
        $catalog = new \App\OrderIntake\Catalog([
            ['id' => 31, 'name' => 'Safina | Unstitched | 3 Piece', 'code' => 'SF-0201', 'variable' => true, 'price' => 1400.0, 'keywords' => [], 'variants' => [
                ['id' => 311, 'sku' => 'SF-0201-YEL', 'label' => 'Yellow', 'values' => ['yellow'], 'price' => 1400.0],
                ['id' => 312, 'sku' => 'SF-0201-PNK', 'label' => 'Pink', 'values' => ['pink'], 'price' => 1400.0],
            ]],
            ['id' => 32, 'name' => 'Safina | Stitched | 2 Piece', 'code' => 'SF-0202', 'variable' => false, 'price' => 1800.0, 'keywords' => [], 'variants' => []],
            ['id' => 33, 'name' => 'Meher Digital Printed 3 Piece', 'code' => 'SF-0170', 'variable' => false, 'price' => 1050.0, 'keywords' => [], 'variants' => []],
        ]);
        $resolver = new \App\OrderIntake\DraftResolver($catalog, $this->areas(), fn () => 120.0, [], 10);
        $text = "Dighee paul\n01949629235\nNarail, lohagara, dighalia bazar\nproduct: safina yallow\n\nProduct price:=1400tk\nDelivery fee:=150tk\nTotal Bill:=1550tk";

        $d = $resolver->resolve((new TextOrderParser($catalog, $this->areas()))->parse($text)[0]);

        $this->assertSame(31, $d['items'][0]['product_id']);   // tie settled by the 1400 price
        $this->assertSame(311, $d['items'][0]['variant_id']);  // "yallow" → Yellow
        $this->assertSame(150.0, $d['amounts']['delivery']);
        $this->assertSame('stated', $d['amounts']['delivery_source']);
        $this->assertSame(1550.0, $d['amounts']['total']);
        $this->assertSame(0.0, $d['amounts']['discount']);
        $this->assertSame(12, $d['area']['method_id']);
        $this->assertSame([], $d['needs_ai']);
    }

    public function test_unfound_product_comes_with_suggestions(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: meher digitel")[0];

        $this->assertContains(3, $d['items'][0]['options'] ?: [$d['items'][0]['product_id']]);
    }

    public function test_shop_number_and_name_in_a_chat_are_not_the_customer(): void
    {
        $chat = "Seldom Fashion\nApu order korte call korun 01999-887766\n\nRahima Akter\n01711223344\nHouse 12, Road 5, Dhanmondi\nSF-0156 x1";
        $drafts = $this->readWithShop($chat);

        $this->assertCount(1, $drafts);
        $this->assertSame('01711223344', $drafts[0]['phone']['display']);
        $this->assertSame('Rahima Akter', $drafts[0]['name']['value']);
        $this->assertNull($drafts[0]['alt_phone']);
    }

    public function test_label_variants(): void
    {
        $text = "👤 Customer Name :- Rahima Akter\n📞 Mobile No. : 01711-223344\n🏠 Address: House 12, Road 5\nThana: Dhanmondi\n🛍 Product Code: SF-0156\nQty: 2";
        $d = $this->read($text)[0];

        $this->assertSame([], $d['needs_ai']);
        $this->assertSame('Rahima Akter', $d['name']['value']);
        $this->assertSame('01711223344', $d['phone']['display']);
        $this->assertStringContainsString('Dhanmondi', $d['address']['value']);
        $this->assertSame(10, $d['area']['method_id']);
        $this->assertSame(2.0, $d['items'][0]['qty']);
    }

    public function test_bangla_numbered_labels(): void
    {
        $d = $this->read("নাম: রহিমা\nমোবাইল নং: ০১৭১১২২৩৩৪৪\nগ্রাম: চরপাড়া\nজেলা: ময়মনসিংহ\nকোড: SF-0170")[0];

        $this->assertSame([], $d['needs_ai']);
        $this->assertStringContainsString('ময়মনসিংহ', $d['address']['value']);
        $this->assertSame(12, $d['area']['method_id']);
    }

    public function test_address_made_of_labelled_parts(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nHouse: 12\nRoad: 5\nArea: Uttara\nProduct: SF-0156")[0];

        $this->assertSame('House 12, Road 5, Uttara', $d['address']['value']);
        $this->assertSame(10, $d['area']['method_id']);
    }

    public function test_labels_without_a_colon(): void
    {
        $d = $this->read("Name Rahima Akter\nMobile 01711223344\nঠিকানা মিরপুর ১০\nSF-0156")[0];

        $this->assertSame('Rahima Akter', $d['name']['value']);
        $this->assertSame(0.95, $d['name']['confidence']);
        $this->assertStringContainsString('মিরপুর', $d['address']['value']);
    }

    public function test_address_and_product_on_one_line(): void
    {
        $d = $this->read("Rahima\n01711223344 Mirpur 10, Dhaka SF-0156 x2")[0];

        $this->assertSame([], $d['needs_ai']);
        $this->assertStringContainsString('mirpur 10', strtolower($d['address']['value']));
        $this->assertSame(2.0, $d['items'][0]['qty']);
    }

    public function test_one_word_district_is_an_address(): void
    {
        $d = $this->read("Rahima\n01711223344\nCumilla\nSF-0156")[0];

        $this->assertSame('Cumilla', $d['address']['value']);
        $this->assertSame(12, $d['area']['method_id']);
    }

    public function test_product_by_code_number(): void
    {
        $d = $this->read("Rahima\n01711223344\nMirpur 10, Dhaka\ncode 156 er ta 2 ta nibo")[0];

        $this->assertSame(1, $d['items'][0]['product_id']);
        $this->assertSame('code', $d['items'][0]['match']);
        $this->assertSame(2.0, $d['items'][0]['qty']);
        $this->assertSame([], $d['needs_ai']);
    }

    public function test_labelled_bare_code_number(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: 170")[0];

        $this->assertSame(3, $d['items'][0]['product_id']);
    }

    public function test_order_number_is_not_a_product(): void
    {
        $d = $this->read("Order No: 156\nName: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: SF-0170")[0];

        $this->assertCount(1, $d['items']);
        $this->assertSame(3, $d['items'][0]['product_id']);
    }

    public function test_one_unique_name_word_finds_the_product(): void
    {
        $d = $this->read("Rahima\n01711223344\nMirpur 10, Dhaka\nmeher ta nibo")[0];

        $this->assertSame(3, $d['items'][0]['product_id']);
        $this->assertSame([], $d['needs_ai']);
    }

    public function test_two_similar_products_are_settled_by_the_quoted_price(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: Elara Dress\nprice 1950 tk")[0];

        $this->assertSame(4, $d['items'][0]['product_id']);
        $this->assertSame('price', $d['items'][0]['match']);
        $this->assertSame([], $d['needs_ai']);
    }

    public function test_two_similar_products_are_settled_by_the_rest_of_the_message(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: Elara Dress\nprinted ta chai")[0];

        $this->assertSame(5, $d['items'][0]['product_id']);
        $this->assertSame('context', $d['items'][0]['match']);
    }

    public function test_variant_from_another_line(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: SF-0150\nblue color ta")[0];

        $this->assertSame(22, $d['items'][0]['variant_id']);
        $this->assertSame([], $d['needs_ai']);
    }

    public function test_variant_by_the_quoted_price(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: SF-0150\ndam 2300")[0];

        $this->assertSame(22, $d['items'][0]['variant_id']);
    }

    public function test_delivery_time_is_not_a_delivery_charge(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: SF-0156\ndelivery 24 hours er moddhe hobe?")[0];

        $this->assertSame('quote', $d['amounts']['delivery_source']);
        $this->assertSame(60.0, $d['amounts']['delivery']);
    }

    public function test_narayangonj_spelling_is_the_sub_area(): void
    {
        $d = $this->read("Name: Rahim\nPhone: 01711223344\nAddress: Chashara, Narayangonj\nProduct: SF-0156")[0];

        $this->assertSame(11, $d['area']['method_id']);
    }

    public function test_nothing_left_unread_means_unused_is_empty(): void
    {
        $d = $this->read("01711223344\nSF-0156\nok apu")[0];

        $this->assertContains('name', $d['needs_ai']);
        $this->assertSame([], $d['unused']);
    }

    public function test_unread_lines_are_kept_for_a_possible_ai_call(): void
    {
        $d = $this->read("01711223344\nSF-0156\nMirpur 10, Dhaka\namar baby er jonno lagbe, gift pack koiren")[0];

        $this->assertNotEmpty($d['unused']);
    }
}
