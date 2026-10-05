<?php

namespace Tests\Unit\OrderIntake;

use App\OrderIntake\Ai\AiExtractionException;
use App\OrderIntake\Ai\AiOrderExtractor;
use App\OrderIntake\Ai\OpenRouterClient;
use App\OrderIntake\IntakeSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** The OpenRouter call and the reply handling — HTTP faked, no database. */
class AiOrderExtractorTest extends TestCase
{
    use IntakeFixtures;

    private function settings(): IntakeSettings
    {
        return new class extends IntakeSettings {
            public function enabled(): bool { return true; }
            public function apiKey(): string { return 'sk-or-test-SECRET'; }
            public function model(): string { return 'openrouter/free'; }
            public function fallbackModel(): string { return 'google/gemini-2.5-flash'; }
            public function temperature(): float { return 0.0; }
            public function timeout(): int { return 30; }
            public function maxOutputTokens(): int { return 2000; }
            public function extraPrompt(): string { return ''; }
            public function baseUrl(): string { return 'https://openrouter.test/api/v1'; }
        };
    }

    private function extractor(): AiOrderExtractor
    {
        $settings = $this->settings();

        return new AiOrderExtractor(new OpenRouterClient($settings), $settings);
    }

    private function aiOrder(array $overrides = []): array
    {
        return array_replace([
            'customer_name' => 'Rahima Akter', 'phone' => '01711 223344', 'alt_phone' => null,
            'address' => 'House 12, Road 5, Dhanmondi, Dhaka', 'area' => 'Dhanmondi', 'delivery_zone' => null,
            'items' => [['product' => 'জারিন নীল', 'candidate_code' => 'SF-0150', 'variant' => 'blue', 'quantity' => 2, 'unit_price' => null]],
            'discount' => ['type' => 'amount', 'value' => 100], 'delivery_charge' => null, 'advance' => null,
            'stated_total' => ['kind' => 'none', 'value' => null], 'payment_method' => 'cod', 'note' => null,
            'confidence' => 0.9, 'unresolved' => [],
        ], $overrides);
    }

    private function reply(array $orders, string $model = 'google/gemini-2.5-flash'): array
    {
        return [
            'model'   => $model,
            'choices' => [['message' => ['content' => json_encode(['orders' => $orders])]]],
            'usage'   => ['prompt_tokens' => 900, 'completion_tokens' => 120, 'total_tokens' => 1020, 'cost' => 0.00042],
        ];
    }

    public function test_screenshot_is_sent_as_an_image_with_a_strict_schema_and_the_key_only_in_the_header(): void
    {
        Http::fake(['openrouter.test/*' => Http::response($this->reply([$this->aiOrder()]))]);

        $result = $this->extractor()->extract('', [['mime' => 'image/png', 'name' => 'chat.png', 'data' => base64_encode('png-bytes')]], [], [['code' => 'SF-0150', 'name' => 'Zareen', 'variants' => ['Red', 'Blue']]], ['Inside Dhaka']);

        Http::assertSent(function (Request $request) {
            $body = $request->data();
            $parts = $body['messages'][1]['content'];

            return $request->url() === 'https://openrouter.test/api/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer sk-or-test-SECRET')
                && ! str_contains(json_encode($body), 'SECRET')
                && $body['model'] === 'openrouter/free'
                && $body['response_format']['type'] === 'json_schema'
                && $body['response_format']['json_schema']['strict'] === true
                && collect($parts)->contains(fn ($p) => $p['type'] === 'image_url' && str_starts_with($p['image_url']['url'], 'data:image/png;base64,'));
        });

        $this->assertSame('google/gemini-2.5-flash', $result['model']);
        $this->assertSame(0.00042, $result['usage']['cost']);
        $this->assertCount(1, $result['orders']);
        $this->assertSame('ai', $result['orders'][0]['via']);
    }

    public function test_a_bad_reply_is_retried_on_the_fallback_model(): void
    {
        Http::fake(['openrouter.test/*' => Http::sequence()
            ->push(['model' => 'nvidia/content-safety:free', 'choices' => [['message' => ['content' => 'safe']]], 'usage' => ['cost' => 0.0001]])
            ->push($this->reply([$this->aiOrder()]))]);

        $result = $this->extractor()->extract('text', [], [], [], []);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $r) => $r->data()['model'] === 'google/gemini-2.5-flash');
        $this->assertSame('google/gemini-2.5-flash', $result['model']);
        $this->assertEqualsWithDelta(0.00052, $result['usage']['cost'], 1e-9);   // both tries counted
    }

    public function test_screenshot_with_an_empty_answer_is_retried_on_another_model(): void
    {
        Http::fake(['openrouter.test/*' => Http::sequence()
            ->push($this->reply([]))
            ->push($this->reply([$this->aiOrder()]))]);

        $result = $this->extractor()->extract('', [['mime' => 'image/png', 'name' => 'chat.png', 'data' => base64_encode('png')]], [], [], []);

        Http::assertSentCount(2);
        $this->assertCount(1, $result['orders']);
    }

    public function test_no_retry_once_the_deadline_is_near(): void
    {
        Http::fake(['openrouter.test/*' => Http::response(['error' => ['message' => 'overloaded']], 503)]);

        try {
            $this->extractor()->extract('text', [], [], [], [], microtime(true) + 5);
            $this->fail('Expected an exception');
        } catch (AiExtractionException) {
            Http::assertSentCount(1);
        }
    }

    public function test_pdf_is_sent_as_a_file_part(): void
    {
        Http::fake(['openrouter.test/*' => Http::response($this->reply([$this->aiOrder()]))]);

        $this->extractor()->extract('', [['mime' => 'application/pdf', 'name' => 'slip.pdf', 'data' => base64_encode('%PDF')]], [], [], []);

        Http::assertSent(fn (Request $r) => collect($r->data()['messages'][1]['content'])
            ->contains(fn ($p) => $p['type'] === 'file' && $p['file']['filename'] === 'slip.pdf'));
    }

    public function test_ai_never_sets_prices_or_ids_they_are_resolved_from_the_catalogue(): void
    {
        Http::fake(['openrouter.test/*' => Http::response($this->reply([$this->aiOrder([
            'items' => [['product' => 'Zareen blue', 'candidate_code' => 'SF-0150', 'variant' => 'blue', 'quantity' => 2, 'unit_price' => null]],
        ])]))]);

        $raw = $this->extractor()->extract('zareen blue 2 ta', [], [], [], [])['orders'][0];
        $d = $this->resolver()->resolve($raw);

        $this->assertSame(2, $d['items'][0]['product_id']);
        $this->assertSame(22, $d['items'][0]['variant_id']);
        $this->assertSame(2300.0, $d['items'][0]['unit_price']);         // catalogue price
        $this->assertSame(2 * 2300.0 + 60.0 - 100.0, $d['amounts']['total']);
        $this->assertSame('ai', $d['via']);
        $this->assertLessThanOrEqual(0.8, $d['items'][0]['confidence']);
    }

    public function test_a_product_the_ai_made_up_stays_unresolved(): void
    {
        Http::fake(['openrouter.test/*' => Http::response($this->reply([$this->aiOrder([
            'items' => [['product' => 'Golden Saree', 'candidate_code' => 'XX-999', 'variant' => null, 'quantity' => 1, 'unit_price' => 5000]],
        ])]))]);

        $d = $this->resolver()->resolve($this->extractor()->extract('saree', [], [], [], [])['orders'][0]);

        $this->assertNull($d['items'][0]['product_id']);
        $this->assertContains('products', $d['needs_ai']);
        $this->assertSame(0.0, $d['amounts']['subtotal']);
    }

    public function test_reply_wrapped_in_a_code_fence_is_still_read(): void
    {
        $fenced = "```json\n" . json_encode(['orders' => [$this->aiOrder()]]) . "\n```";
        Http::fake(['openrouter.test/*' => Http::response(['model' => 'x', 'choices' => [['message' => ['content' => $fenced]]]])]);

        $this->assertCount(1, $this->extractor()->extract('text', [], [], [], [])['orders']);
    }

    public function test_invalid_json_fails_cleanly(): void
    {
        Http::fake(['openrouter.test/*' => Http::response(['model' => 'x', 'choices' => [['message' => ['content' => 'Sorry, I cannot help with that.']]]])]);

        $this->expectException(AiExtractionException::class);
        $this->expectExceptionMessage('not valid JSON');

        $this->extractor()->extract('text', [], [], [], []);
    }

    public function test_json_in_the_wrong_shape_is_rejected(): void
    {
        Http::fake(['openrouter.test/*' => Http::response(['model' => 'x', 'choices' => [['message' => ['content' => json_encode(['orders' => [['phone' => 1711223344, 'items' => 'two dresses']]])]]]])]);

        $this->expectException(AiExtractionException::class);
        $this->expectExceptionMessage('did not match');

        $this->extractor()->extract('text', [], [], [], []);
    }

    public function test_rate_limit_is_reported(): void
    {
        Http::fake(['openrouter.test/*' => Http::response(['error' => ['message' => 'Rate limit exceeded']], 429)]);

        try {
            $this->extractor()->extract('text', [], [], [], []);
            $this->fail('Expected an exception');
        } catch (AiExtractionException $e) {
            $this->assertStringContainsString('rate limit', strtolower($e->getMessage()));
        }
    }

    public function test_timeout_is_reported(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

        $this->expectException(AiExtractionException::class);
        $this->expectExceptionMessage('timed out');

        $this->extractor()->extract('text', [], [], [], []);
    }

    public function test_unsure_fields_get_low_confidence(): void
    {
        $raw = AiOrderExtractor::toRawDraft(AiOrderExtractor::validate(['orders' => [$this->aiOrder(['unresolved' => ['address']])]])[0], 'x');

        $this->assertSame(0.5, $raw['address_conf']);
        $this->assertGreaterThanOrEqual(0.6, $raw['name_conf']);
    }
}
