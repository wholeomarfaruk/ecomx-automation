<?php

namespace Tests\Feature\OrderIntake;

use App\Livewire\Admin\Sales\BulkOrderCreate;
use App\Livewire\Admin\SiteSettings\AiOrderSettings;
use App\Models\Customer;
use App\Models\OrderIntakeLog;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\OrderIntake\IntakeSettings;
use App\OrderIntake\OrderIntakeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * AI Order end to end on the database: parser-first, AI only as fallback,
 * logging/caching, duplicate warning, placed-order linking, permissions.
 * Shipping zones come from the migration (Inside Dhaka 70, Outside 130).
 */
class OrderIntakeServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('inventory_enabled', '0', 'modules');
        Setting::set('accounts_enabled', '0', 'modules');

        Product::create(['code' => 'SF-0156', 'name' => 'Sunset Charm 3 Pcs Unstitched Dress', 'slug' => 'sunset-charm', 'status' => 'active', 'product_type' => 'simple', 'price' => 2250, 'stock_quantity' => 50, 'stock_status' => 'in_stock']);
        Product::create(['code' => 'SF-0170', 'name' => 'Meher Digital Printed 3 Piece', 'slug' => 'meher', 'status' => 'active', 'product_type' => 'simple', 'price' => 1050, 'stock_quantity' => 50, 'stock_status' => 'in_stock']);

        Permission::create(['name' => 'order.create', 'guard_name' => 'web']);
        $this->admin = User::factory()->create();
        $this->admin->givePermissionTo('order.create');
        $this->actingAs($this->admin);
    }

    private function enableAi(): void
    {
        Setting::set('enabled', '1', IntakeSettings::GROUP);
        IntakeSettings::storeApiKey('sk-or-test-SECRET');
    }

    private function aiReply(array $orders): array
    {
        return [
            'model'   => 'google/gemini-2.5-flash',
            'choices' => [['message' => ['content' => json_encode(['orders' => $orders])]]],
            'usage'   => ['prompt_tokens' => 800, 'completion_tokens' => 100, 'cost' => 0.0003],
        ];
    }

    private function aiOrder(array $overrides = []): array
    {
        return array_replace([
            'customer_name' => 'Rahima Akter', 'phone' => '01711223344', 'alt_phone' => null,
            'address' => 'House 12, Road 5, Dhanmondi, Dhaka', 'area' => 'Dhanmondi', 'delivery_zone' => null,
            'items' => [['product' => 'Sunset Charm', 'candidate_code' => 'SF-0156', 'variant' => null, 'quantity' => 2, 'unit_price' => null]],
            'discount' => ['type' => 'none', 'value' => null], 'delivery_charge' => null, 'advance' => null,
            'stated_total' => ['kind' => 'none', 'value' => null], 'payment_method' => null, 'note' => null,
            'confidence' => 0.9, 'unresolved' => [],
        ], $overrides);
    }

    private function extract(string $text, array $files = [], bool $forceAi = false): array
    {
        return app(OrderIntakeService::class)->extract($text, $files, 'messenger', $forceAi, $this->admin->id);
    }

    public function test_normal_text_order_is_resolved_without_calling_ai(): void
    {
        $this->enableAi();
        Http::fake();

        $result = $this->extract("Name: Rahima Akter\nPhone: 01711-223344\nAddress: House 12, Road 5, Dhanmondi, Dhaka\nProduct: SF-0156 x2\nDiscount: 100");

        Http::assertNothingSent();
        $this->assertSame(OrderIntakeLog::RESOLUTION_PARSER, $result['resolution']);
        $d = $result['drafts'][0];
        $this->assertTrue($d['ready']);
        $this->assertSame(4500.0, $d['amounts']['subtotal']);
        $this->assertSame(70.0, $d['amounts']['delivery']);       // Inside Dhaka, from ShippingCalculator
        $this->assertSame(4470.0, $d['amounts']['total']);
        $this->assertSame('SF-0156 x2', $result['rows'][0]['cells']['products']);
    }

    public function test_structured_bulk_text_is_resolved_without_ai(): void
    {
        $this->enableAi();
        Http::fake();

        $result = $this->extract("Rahim, 01711223344, Sadar Road Cumilla, SF-0156 x1\nKarim, 01811223344, Mirpur 10 Dhaka, SF-0170 x3");

        Http::assertNothingSent();
        $this->assertCount(2, $result['rows']);
        $this->assertSame([130.0, 70.0], array_map(fn ($d) => $d['amounts']['delivery'], $result['drafts']));
    }

    public function test_screenshot_goes_to_ai_and_is_logged_with_usage(): void
    {
        $this->enableAi();
        Http::fake(['openrouter.ai/*' => Http::response($this->aiReply([$this->aiOrder()]))]);

        $result = $this->extract('', [UploadedFile::fake()->image('chat.png', 400, 800)]);

        Http::assertSentCount(1);
        $this->assertSame(OrderIntakeLog::RESOLUTION_AI, $result['resolution']);
        $this->assertSame('ai', $result['drafts'][0]['via']);
        $this->assertSame(Product::where('code', 'SF-0156')->value('id'), $result['drafts'][0]['items'][0]['product_id']);

        $log = OrderIntakeLog::find($result['intake_id']);
        $this->assertSame('image', $log->source_type);
        $this->assertSame('google/gemini-2.5-flash', $log->model);
        $this->assertSame(800, $log->prompt_tokens);
        $this->assertEquals(0.0003, (float) $log->cost);
    }

    public function test_unresolved_product_triggers_ai_and_parser_fields_are_kept(): void
    {
        $this->enableAi();
        Http::fake(['openrouter.ai/*' => Http::response($this->aiReply([$this->aiOrder(['customer_name' => 'Wrong Name'])]))]);

        $result = $this->extract("Name: Rahima Akter\nPhone: 01711223344\nAddress: House 12, Road 5, Dhanmondi, Dhaka\nProduct: oi lal jama ta");

        $this->assertContains('products', $result['ai']['reasons']);
        $this->assertSame(OrderIntakeLog::RESOLUTION_AI, $result['resolution']);
        $d = $result['drafts'][0];
        $this->assertSame('Rahima Akter', $d['name']['value']);   // labelled parser value wins
        $this->assertNotNull($d['items'][0]['product_id']);        // product from the AI, resolved by code
    }

    public function test_unresolved_delivery_area_uses_the_default_zone_without_ai(): void
    {
        $this->enableAi();
        Http::fake();

        $result = $this->extract("Name: Rahima\nPhone: 01711223344\nAddress: near the big mosque, house 4\nProduct: SF-0156");

        Http::assertNothingSent();
        $this->assertSame('default', $result['drafts'][0]['area']['source']);
        $this->assertSame(70.0, $result['drafts'][0]['amounts']['delivery']);
    }

    public function test_text_pdf_is_parsed_without_ai(): void
    {
        $this->enableAi();
        Http::fake();
        $pdf = UploadedFile::fake()->createWithContent('order.pdf', file_get_contents(base_path('tests/Unit/OrderIntake/fixtures/order.pdf')));

        $result = $this->extract('', [$pdf]);

        Http::assertNothingSent();
        $this->assertSame(OrderIntakeLog::RESOLUTION_PARSER, $result['resolution']);
        $this->assertSame('01711223344', $result['rows'][0]['cells']['phone']);
    }

    public function test_ai_timeout_keeps_the_parser_draft_and_logs_the_failure(): void
    {
        $this->enableAi();
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('Operation timed out'));

        $result = $this->extract("Name: Rahima\nPhone: 01711223344\nAddress: Road 2, Uttara\nProduct: something unknown");

        $this->assertSame(OrderIntakeLog::RESOLUTION_AI_FAILED, $result['resolution']);
        $this->assertStringContainsString('timed out', $result['ai']['error']);
        $this->assertCount(1, $result['rows']);                    // draft not lost
        $this->assertSame('01711223344', $result['rows'][0]['cells']['phone']);
        $this->assertSame(OrderIntakeLog::RESOLUTION_AI_FAILED, OrderIntakeLog::find($result['intake_id'])->resolution);
    }

    public function test_invalid_ai_json_keeps_the_parser_draft(): void
    {
        $this->enableAi();
        Http::fake(['openrouter.ai/*' => Http::response(['model' => 'x', 'choices' => [['message' => ['content' => 'not json at all']]]])]);

        $result = $this->extract("Phone: 01711223344\nProduct: mystery item");

        $this->assertSame(OrderIntakeLog::RESOLUTION_AI_FAILED, $result['resolution']);
        $this->assertCount(1, $result['drafts']);
    }

    public function test_openrouter_error_is_reported_not_thrown(): void
    {
        $this->enableAi();
        Http::fake(['openrouter.ai/*' => Http::response(['error' => ['message' => 'No credits']], 402)]);

        $result = $this->extract("Phone: 01711223344\nProduct: mystery item");

        $this->assertSame(OrderIntakeLog::RESOLUTION_AI_FAILED, $result['resolution']);
        $this->assertStringContainsString('out of credit', $result['ai']['error']);
    }

    public function test_ai_off_means_parser_only_with_a_reason(): void
    {
        Http::fake();

        $result = $this->extract("Phone: 01711223344\nProduct: mystery item");

        Http::assertNothingSent();
        $this->assertSame(OrderIntakeLog::RESOLUTION_AI_SKIPPED, $result['resolution']);
        $this->assertStringContainsString('turned off', $result['ai']['error']);
    }

    public function test_daily_limit_stops_ai_calls(): void
    {
        $this->enableAi();
        Setting::set('daily_limit', '1', IntakeSettings::GROUP);
        Http::fake(['openrouter.ai/*' => Http::response($this->aiReply([$this->aiOrder()]))]);

        $this->extract('first', [UploadedFile::fake()->image('a.png')]);
        $second = $this->extract('second', [UploadedFile::fake()->image('b.png')]);

        Http::assertSentCount(1);
        $this->assertSame(OrderIntakeLog::RESOLUTION_AI_SKIPPED, $second['resolution']);
    }

    public function test_same_input_reuses_the_cached_ai_reply_and_retry_calls_again(): void
    {
        $this->enableAi();
        Http::fake(['openrouter.ai/*' => Http::response($this->aiReply([$this->aiOrder()]))]);
        $text = "Phone: 01711223344\nProduct: lal jama";

        $this->extract($text);
        $cached = $this->extract($text);
        Http::assertSentCount(1);
        $this->assertTrue($cached['ai']['cached']);

        $retry = $this->extract($text, [], forceAi: true);
        Http::assertSentCount(2);
        $this->assertFalse($retry['ai']['cached']);
    }

    public function test_existing_customer_is_matched_by_normalized_phone(): void
    {
        Customer::create(['customer_code' => 'CUS-00001', 'first_name' => 'Rahima', 'full_name' => 'Rahima Akter', 'phone' => '1711223344', 'status' => 'active']);

        $d = $this->extract("+880 1711-223344\nSF-0156 x1")['drafts'][0];

        $this->assertTrue($d['customer']['found']);
        $this->assertSame('customer', $d['name']['source']);
    }

    public function test_placing_rows_links_the_orders_and_a_repeat_paste_warns_of_a_duplicate(): void
    {
        $text = "Name: Rahima Akter\nPhone: 01711223344\nAddress: House 12, Road 5, Dhanmondi, Dhaka\nProduct: SF-0156 x2";
        $first = $this->extract($text);
        $row = $first['rows'][0];

        $result = Livewire::test(BulkOrderCreate::class)->instance()->submit([[
            'key'        => 'r1',
            'phone'      => $row['cells']['phone'],
            'name'       => $row['cells']['name'],
            'address'    => $row['cells']['address'],
            'items'      => [['product_id' => $first['drafts'][0]['items'][0]['product_id'], 'variant_id' => null, 'quantity' => 2, 'unit_price' => 2250]],
            'method_id'  => (int) $row['cells']['method'],
            'delivery'   => 70,
            'discount'   => 0,
            'advance'    => 0,
            'source'     => 'messenger',
            'intake_ids' => [$row['meta']['intakeId']],
        ]], ['status' => 'pending', 'batch' => 'B-TEST']);

        $orderId = $result['results']['r1']['id'];
        $this->assertSame([$orderId], OrderIntakeLog::find($first['intake_id'])->order_ids);

        $again = $this->extract($text);
        $this->assertSame([$orderId], $again['previous']['order_ids']);
    }

    public function test_extract_orders_requires_order_create_permission(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(BulkOrderCreate::class)->assertForbidden();
    }

    public function test_extract_orders_rejects_disallowed_files(): void
    {
        $result = Livewire::test(BulkOrderCreate::class)->instance()
            ->extractOrders('hello', 'messenger', false, [['name' => 'virus.exe', 'type' => 'application/x-msdownload', 'data' => base64_encode("MZ " . str_repeat(" ", 200))]]);

        $this->assertArrayHasKey('error', $result);
        $this->assertSame(0, OrderIntakeLog::count());
    }

    public function test_api_key_is_stored_encrypted_and_never_rendered(): void
    {
        $this->admin->assignRole(\Spatie\Permission\Models\Role::create(['name' => 'superadmin', 'guard_name' => 'web']));

        Livewire::test(AiOrderSettings::class)
            ->set('newApiKey', 'sk-or-v1-TOPSECRETKEY1234')
            ->call('save')
            ->assertDontSee('TOPSECRETKEY')
            ->assertSee('1234')
            ->assertSet('newApiKey', '');

        $stored = Setting::where('group', IntakeSettings::GROUP)->where('key', 'api_key')->value('value');
        $this->assertStringNotContainsString('TOPSECRET', $stored);
        $this->assertSame('sk-or-v1-TOPSECRETKEY1234', app(IntakeSettings::class)->apiKey());
    }

    public function test_ai_settings_need_manage_permission(): void
    {
        Livewire::test(AiOrderSettings::class)->call('save')->assertForbidden();
    }
}
