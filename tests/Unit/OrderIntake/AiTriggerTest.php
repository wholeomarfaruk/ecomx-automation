<?php

namespace Tests\Unit\OrderIntake;

use App\OrderIntake\OrderIntakeService;
use Tests\TestCase;

/** When OrderIntakeService asks the AI — only for what the AI could actually fix. */
class AiTriggerTest extends TestCase
{
    use IntakeFixtures;

    private function reasons(string $text, array $media = []): array
    {
        $drafts = $this->read($text);
        $method = new \ReflectionMethod(OrderIntakeService::class, 'aiReasons');

        return $method->invoke(app(OrderIntakeService::class), $text, $media, $drafts, false);
    }

    public function test_complete_order_needs_no_ai(): void
    {
        $this->assertSame([], $this->reasons("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: SF-0156 x1"));
    }

    public function test_missing_phone_in_plain_text_does_not_call_ai(): void
    {
        $this->assertSame([], $this->reasons("Name: Rahim\nAddress: Road 2, Uttara\nProduct: SF-0156"));
    }

    public function test_missing_name_with_nothing_left_to_read_does_not_call_ai(): void
    {
        $this->assertSame([], $this->reasons("01711223344\nRoad 2, Uttara\nSF-0156\nok apu"));
    }

    public function test_unknown_product_text_calls_ai(): void
    {
        $this->assertContains('products', $this->reasons("Name: Rahim\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: oi lal jama ta"));
    }

    public function test_screenshot_always_calls_ai(): void
    {
        $this->assertContains('screenshots/files', $this->reasons('', [['mime' => 'image/png', 'name' => 'a.png', 'data' => '']]));
    }

    public function test_typed_product_and_screenshot_customer_become_one_order(): void
    {
        $parsed = $this->parser()->parse('product: zareen');
        $resolved = array_map(fn ($r) => $this->resolver()->resolve($r), $parsed);
        $fromScreenshot = \App\OrderIntake\Ai\AiOrderExtractor::toRawDraft(\App\OrderIntake\Ai\AiOrderExtractor::validate(['orders' => [[
            'customer_name' => 'Saila Barkat', 'phone' => '01572146290', 'alt_phone' => null,
            'address' => 'House 23, Road A2, Hasnabad, South Keraniganj, Dhaka', 'area' => 'Keraniganj', 'delivery_zone' => null,
            'items' => [], 'discount' => ['type' => 'none', 'value' => null], 'delivery_charge' => 80, 'advance' => null,
            'stated_total' => ['kind' => 'total', 'value' => 2280], 'payment_method' => null, 'note' => null, 'confidence' => 0.9, 'unresolved' => [],
        ]]])[0], 'product: zareen');

        $merge = new \ReflectionMethod(OrderIntakeService::class, 'merge');
        $merged = $merge->invoke(app(OrderIntakeService::class), $parsed, $resolved, [$fromScreenshot]);
        $d = $this->resolver()->resolve($merged[0]);

        $this->assertCount(1, $merged);
        $this->assertSame('Saila Barkat', $d['name']['value']);
        $this->assertSame('01572146290', $d['phone']['display']);
        $this->assertSame(2, $d['items'][0]['product_id']);       // Zareen, from the typed text
        $this->assertSame(11, $d['area']['method_id']);            // Keraniganj → sub area
        $this->assertSame(80.0, $d['amounts']['delivery']);
    }

    public function test_greeting_only_text_is_not_sent_to_ai(): void
    {
        $this->assertSame([], $this->reasons('??'));
    }
}
