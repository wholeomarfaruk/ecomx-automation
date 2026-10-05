<?php

namespace Tests\Unit\OrderIntake;

use App\OrderIntake\Catalog;
use PHPUnit\Framework\TestCase;

class CatalogTest extends TestCase
{
    use IntakeFixtures;

    public function test_variant_sku_matches_exactly_with_or_without_punctuation(): void
    {
        foreach (['SF-0150-BLUE', 'sf0150blue', 'SF 0150 BLUE'] as $ref) {
            $hit = $this->catalog()->resolve($ref);
            $this->assertSame(2, $hit['product']['id'], $ref);
            $this->assertSame(22, $hit['variant']['id'], $ref);
            $this->assertSame('exact', $hit['match'], $ref);
        }
    }

    public function test_code_with_a_variant_word_finds_the_variant(): void
    {
        // "SF-0150 red" compacts to the RED variant's own SKU.
        $this->assertSame(21, $this->catalog()->resolve('SF-0150 red')['variant']['id']);
    }

    public function test_code_inside_text_leaves_the_rest_as_variant_hint(): void
    {
        $hit = $this->catalog()->resolve('SF-0150 in red please');

        $this->assertSame(2, $hit['product']['id']);
        $this->assertSame('in red please', $hit['hint']);
        $this->assertSame(21, $this->catalog()->pickVariant($hit['product'], $hit['hint'])['variant']['id']);
    }

    public function test_full_name_is_exact_and_case_insensitive(): void
    {
        $hit = $this->catalog()->resolve('meher digital printed 3 piece');

        $this->assertSame(3, $hit['product']['id']);
        $this->assertSame('exact', $hit['match']);
    }

    public function test_keyword_alias_matches(): void
    {
        $hit = $this->catalog()->resolve('zarin');

        $this->assertSame(2, $hit['product']['id']);
        $this->assertSame('keyword', $hit['match']);
    }

    public function test_two_equally_good_products_are_ambiguous_not_guessed(): void
    {
        $hit = $this->catalog()->resolve('Elara Dress');

        $this->assertTrue($hit['ambiguous'] ?? false);
        $this->assertContains('Elara Embroidered Dress', $hit['options']);
        $this->assertContains('Elara Printed Dress', $hit['options']);
    }

    public function test_distinctive_words_find_a_product_by_partial_name(): void
    {
        $hit = $this->catalog()->resolve('sunset er charm ta');

        $this->assertSame(1, $hit['product']['id']);
        $this->assertSame('partial', $hit['match']);
    }

    public function test_spelling_mistakes_are_a_fuzzy_unconfirmed_match(): void
    {
        $hit = $this->catalog()->resolve('Meher Digitel Printd');

        $this->assertSame(3, $hit['product']['id']);
        $this->assertSame('fuzzy', $hit['match']);
    }

    public function test_unknown_product_is_null(): void
    {
        $this->assertNull($this->catalog()->resolve('iphone charger'));
    }

    public function test_variable_product_without_a_hint_needs_a_variant(): void
    {
        $picked = $this->catalog()->pickVariant($this->catalog()->product(2), '');

        $this->assertNull($picked['variant']);
        $this->assertStringContainsString('choose a variant', $picked['problem']);
    }

    public function test_item_quantity_and_price_expressions(): void
    {
        $cat = $this->catalog();

        $this->assertSame(['ref' => 'SF-0156', 'qty' => 2.0, 'price' => null], $cat->parseItem('SF-0156 x2'));
        $this->assertSame(['ref' => 'SF-0156', 'qty' => 3.0, 'price' => null], $cat->parseItem('SF-0156 3 pcs'));
        $this->assertSame(['ref' => 'SF-0156', 'qty' => 2.0, 'price' => null], $cat->parseItem('SF-0156 ২টা'));
        $this->assertSame(['ref' => 'SF-0156', 'qty' => 2.0, 'price' => 2100.0], $cat->parseItem('2 x SF-0156 @2100'));
        $this->assertSame(['ref' => 'Zareen', 'qty' => 2.0, 'price' => null], $cat->parseItem('duita Zareen'));
    }

    public function test_quantity_in_free_text(): void
    {
        $this->assertSame(2.0, Catalog::quantityIn('2 ta nibo'));
        $this->assertSame(3.0, Catalog::quantityIn('qty: 3'));
        $this->assertSame(2.0, Catalog::quantityIn('দুইটা লাগবে'));
        $this->assertNull(Catalog::quantityIn('nibo apu'));
    }

    public function test_ai_candidates_are_the_closest_products_only(): void
    {
        $picked = $this->catalog()->candidates('elara embroidered please', 2);

        $this->assertCount(2, $picked);
        $this->assertSame(4, $picked[0]['id']);
    }
}
