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

    public function test_greeting_only_text_is_not_sent_to_ai(): void
    {
        $this->assertSame([], $this->reasons('??'));
    }
}
