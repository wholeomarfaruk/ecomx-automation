<?php

namespace App\Livewire\Admin\Sales;

use App\Enums\Sales\ShippingRateType;
use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Services\Shipping\ShippingCalculator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Sales → Shipping: delivery zones (the areas customers pick at checkout)
 * and each zone's methods with their rate. Priced at checkout by
 * ShippingCalculator.
 */
class Shipping extends Component
{
    public bool $zoneModal = false;
    public ?int $editingZoneId = null;
    public array $zone = [];

    public bool $methodModal = false;
    public ?int $editingMethodId = null;
    public array $method = [];

    public function mount(): void
    {
        $this->zone = $this->blankZone();
        $this->method = $this->blankMethod();
    }

    // ── Zones ────────────────────────────────────────────────────────────

    public function createZone(): void
    {
        $this->resetValidation();
        $this->editingZoneId = null;
        $this->zone = $this->blankZone();
        $this->zone['sort_order'] = (int) ShippingZone::max('sort_order') + 1;
        $this->zoneModal = true;
    }

    public function editZone(int $id): void
    {
        $zone = ShippingZone::findOrFail($id);

        $this->resetValidation();
        $this->editingZoneId = $zone->id;
        $this->zone = [
            'name'       => $zone->name,
            'code'       => $zone->code,
            'is_active'  => $zone->is_active,
            'sort_order' => $zone->sort_order,
        ];
        $this->zoneModal = true;
    }

    public function saveZone(): void
    {
        if (trim((string) $this->zone['code']) === '') {
            $this->zone['code'] = Str::slug((string) $this->zone['name']);
        }

        $this->validate([
            'zone.name'       => 'required|string|max:100',
            'zone.code'       => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('shipping_zones', 'code')->ignore($this->editingZoneId)],
            'zone.is_active'  => 'boolean',
            'zone.sort_order' => 'required|integer|min:0',
        ], [], [
            'zone.name' => 'name', 'zone.code' => 'code', 'zone.sort_order' => 'order',
        ]);

        $zone = ShippingZone::updateOrCreate(['id' => $this->editingZoneId], [
            'name'       => trim($this->zone['name']),
            'code'       => $this->zone['code'],
            'is_active'  => (bool) $this->zone['is_active'],
            'sort_order' => (int) $this->zone['sort_order'],
        ]);

        $this->log($zone, $this->editingZoneId ? 'updated' : 'created', "Shipping zone \"{$zone->name}\"");

        $this->zoneModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => $this->editingZoneId ? 'Zone updated' : 'Zone added']);
    }

    public function toggleZone(int $id): void
    {
        $zone = ShippingZone::findOrFail($id);
        $zone->update(['is_active' => ! $zone->is_active]);

        $this->dispatch('toast', ['type' => 'success', 'message' => $zone->name . ' ' . ($zone->is_active ? 'enabled' : 'disabled')]);
    }

    public function deleteZone(int $id): void
    {
        $zone = ShippingZone::findOrFail($id);
        $zone->delete();

        $this->log(null, 'deleted', "Shipping zone \"{$zone->name}\"");
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Zone deleted']);
    }

    // ── Methods ──────────────────────────────────────────────────────────

    public function createMethod(int $zoneId): void
    {
        $this->resetValidation();
        $this->editingMethodId = null;
        $this->method = $this->blankMethod();
        $this->method['zone_id'] = $zoneId;
        $this->method['sort_order'] = (int) ShippingMethod::where('shipping_zone_id', $zoneId)->max('sort_order') + 1;
        $this->methodModal = true;
    }

    public function editMethod(int $id): void
    {
        $method = ShippingMethod::findOrFail($id);
        $config = $method->rate_config ?? [];

        $this->resetValidation();
        $this->editingMethodId = $method->id;
        $this->method = array_merge($this->blankMethod(), array_intersect_key($config, $this->blankMethod()), [
            'zone_id'       => $method->shipping_zone_id,
            'name'          => $method->name,
            'delivery_time' => $method->delivery_time ?? '',
            'rate_type'     => $method->rate_type->value,
            'free_over'     => $method->free_over !== null ? (string) (float) $method->free_over : '',
            'is_active'     => $method->is_active,
            'sort_order'    => $method->sort_order,
            'volumetric'    => ! empty($config['volumetric']),
            'bands'         => $this->bandRows($config['bands'] ?? []),
        ]);
        $this->methodModal = true;
    }

    public function addBand(): void
    {
        $this->method['bands'][] = ['up_to' => '', 'charge' => ''];
    }

    public function removeBand(int $index): void
    {
        unset($this->method['bands'][$index]);
        $this->method['bands'] = array_values($this->method['bands']) ?: [['up_to' => '', 'charge' => '']];
    }

    public function saveMethod(): void
    {
        $type = ShippingRateType::tryFrom((string) $this->method['rate_type']) ?? ShippingRateType::FLAT;

        $this->validate(array_merge([
            'method.zone_id'       => 'required|exists:shipping_zones,id',
            'method.name'          => 'required|string|max:100',
            'method.delivery_time' => 'nullable|string|max:100',
            'method.rate_type'     => ['required', Rule::enum(ShippingRateType::class)],
            'method.free_over'     => 'nullable|numeric|min:0',
            'method.is_active'     => 'boolean',
            'method.sort_order'    => 'required|integer|min:0',
        ], $this->rateRules($type)), [], [
            'method.name'              => 'name',
            'method.free_over'         => 'free over',
            'method.amount'            => 'charge',
            'method.base_weight'       => 'base weight',
            'method.base_charge'       => 'base charge',
            'method.per_kg'            => 'per kg charge',
            'method.extra_per_kg'      => 'extra per kg',
            'method.first_charge'      => 'first item charge',
            'method.additional_charge' => 'each extra item charge',
            'method.percent'           => 'percent',
            'method.min'               => 'minimum',
            'method.max'               => 'maximum',
            'method.divisor'           => 'divisor',
            'method.bands.*.up_to'     => 'up to',
            'method.bands.*.charge'    => 'charge',
        ]);

        if ($type->usesBands() && collect($this->method['bands'])->filter(fn ($b) => trim((string) $b['up_to']) === '')->count() > 1) {
            $this->addError('method.bands', 'Only one band can be left open-ended (no "up to").');

            return;
        }

        $method = ShippingMethod::updateOrCreate(['id' => $this->editingMethodId], [
            'shipping_zone_id' => $this->method['zone_id'],
            'name'             => trim($this->method['name']),
            'delivery_time'    => trim((string) $this->method['delivery_time']) ?: null,
            'rate_type'        => $type,
            'rate_config'      => $this->rateConfig($type),
            'free_over'        => $this->method['free_over'] !== '' ? $this->method['free_over'] : null,
            'is_active'        => (bool) $this->method['is_active'],
            'sort_order'       => (int) $this->method['sort_order'],
        ]);

        $this->log($method, $this->editingMethodId ? 'updated' : 'created', "Shipping method \"{$method->name}\"");

        $this->methodModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => $this->editingMethodId ? 'Method updated' : 'Method added']);
    }

    public function toggleMethod(int $id): void
    {
        $method = ShippingMethod::findOrFail($id);
        $method->update(['is_active' => ! $method->is_active]);

        $this->dispatch('toast', ['type' => 'success', 'message' => $method->name . ' ' . ($method->is_active ? 'enabled' : 'disabled')]);
    }

    public function deleteMethod(int $id): void
    {
        $method = ShippingMethod::findOrFail($id);
        $method->delete();

        $this->log(null, 'deleted', "Shipping method \"{$method->name}\"");
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Method deleted']);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function rateRules(ShippingRateType $type): array
    {
        $money = 'required|numeric|min:0';

        return match ($type) {
            ShippingRateType::FLAT => ['method.amount' => $money],
            ShippingRateType::WEIGHT => [
                'method.base_weight' => 'required|numeric|min:0',
                'method.base_charge' => $money,
                'method.per_kg'      => $money,
                'method.divisor'     => 'nullable|numeric|min:1',
            ],
            ShippingRateType::WEIGHT_BANDS => [
                'method.bands'          => 'required|array|min:1',
                'method.bands.*.up_to'  => 'nullable|numeric|min:0',
                'method.bands.*.charge' => $money,
                'method.extra_per_kg'   => 'nullable|numeric|min:0',
                'method.divisor'        => 'nullable|numeric|min:1',
            ],
            ShippingRateType::QUANTITY => [
                'method.first_charge'      => $money,
                'method.additional_charge' => $money,
            ],
            ShippingRateType::SUBTOTAL_BANDS => [
                'method.bands'          => 'required|array|min:1',
                'method.bands.*.up_to'  => 'nullable|numeric|min:0',
                'method.bands.*.charge' => $money,
            ],
            ShippingRateType::PERCENTAGE => [
                'method.percent' => 'required|numeric|min:0|max:100',
                'method.min'     => 'nullable|numeric|min:0',
                'method.max'     => 'nullable|numeric|min:0',
            ],
            ShippingRateType::FREE => [],
        };
    }

    /** Only the keys the chosen rate type reads, as numbers. */
    private function rateConfig(ShippingRateType $type): array
    {
        $m = $this->method;
        $num = fn (string $key) => is_numeric($m[$key] ?? null) ? (float) $m[$key] : null;
        $bands = fn () => collect($m['bands'])
            ->map(fn ($b) => [
                'up_to'  => is_numeric($b['up_to']) ? (float) $b['up_to'] : null,
                'charge' => (float) $b['charge'],
            ])
            ->sortBy(fn ($b) => $b['up_to'] ?? PHP_FLOAT_MAX)
            ->values()
            ->all();
        $volumetric = fn () => ['volumetric' => (bool) $m['volumetric'], 'divisor' => $num('divisor') ?? ShippingCalculator::DEFAULT_VOLUMETRIC_DIVISOR];

        return match ($type) {
            ShippingRateType::FLAT => ['amount' => $num('amount')],
            ShippingRateType::WEIGHT => ['base_weight' => $num('base_weight'), 'base_charge' => $num('base_charge'), 'per_kg' => $num('per_kg')] + $volumetric(),
            ShippingRateType::WEIGHT_BANDS => ['bands' => $bands(), 'extra_per_kg' => $num('extra_per_kg') ?? 0.0] + $volumetric(),
            ShippingRateType::QUANTITY => ['first_charge' => $num('first_charge'), 'additional_charge' => $num('additional_charge')],
            ShippingRateType::SUBTOTAL_BANDS => ['bands' => $bands()],
            ShippingRateType::PERCENTAGE => ['percent' => $num('percent'), 'min' => $num('min') ?? 0.0, 'max' => $num('max') ?? 0.0],
            ShippingRateType::FREE => [],
        };
    }

    private function bandRows(array $bands): array
    {
        $rows = collect($bands)->map(fn ($b) => [
            'up_to'  => isset($b['up_to']) ? (string) (float) $b['up_to'] : '',
            'charge' => isset($b['charge']) ? (string) (float) $b['charge'] : '',
        ])->values()->all();

        return $rows ?: [['up_to' => '', 'charge' => '']];
    }

    private function blankZone(): array
    {
        return ['name' => '', 'code' => '', 'is_active' => true, 'sort_order' => 0];
    }

    private function blankMethod(): array
    {
        return [
            'zone_id' => null, 'name' => 'Standard Delivery', 'delivery_time' => '',
            'rate_type' => ShippingRateType::FLAT->value,
            'amount' => '', 'base_weight' => '1', 'base_charge' => '', 'per_kg' => '',
            'extra_per_kg' => '', 'first_charge' => '', 'additional_charge' => '',
            'percent' => '', 'min' => '', 'max' => '',
            'volumetric' => false, 'divisor' => (string) ShippingCalculator::DEFAULT_VOLUMETRIC_DIVISOR,
            'bands' => [['up_to' => '', 'charge' => '']],
            'free_over' => '', 'is_active' => true, 'sort_order' => 0,
        ];
    }

    private function log(?object $subject, string $event, string $what): void
    {
        $activity = activity('settings')->causedBy(auth()->user())->event($event);

        if ($subject) {
            $activity->performedOn($subject);
        }

        $activity->log("{$what} was {$event}");
    }

    public function render(): mixed
    {
        return view('livewire.admin.sales.shipping', [
            'zones'     => ShippingZone::with('methods')->orderBy('sort_order')->orderBy('id')->get(),
            'rateTypes' => ShippingRateType::cases(),
        ])->layout('layouts.admin.admin');
    }
}
