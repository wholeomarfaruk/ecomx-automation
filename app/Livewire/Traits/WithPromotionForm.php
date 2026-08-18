<?php

namespace App\Livewire\Traits;

use App\Models\Promotion;

trait WithPromotionForm
{
    public string $name        = '';
    public string $description = '';
    public string $status      = 'draft';
    public string $priority    = '0';
    public string $startsAt    = '';
    public string $endsAt      = '';
    public bool   $stackable   = false;

    /** @var array<int, array{type: string, operator: string, value: string}> */
    public array $conditions = [];

    /** @var array<int, array{type: string, value: string, buy_quantity: string, get_quantity: string, max_discount_amount: string}> */
    public array $discountRules = [];

    public function addCondition(): void
    {
        $this->conditions[] = [
            'type'     => 'cart_amount',
            'operator' => '>=',
            'value'    => '',
        ];
    }

    public function removeCondition(int $index): void
    {
        unset($this->conditions[$index]);
        $this->conditions = array_values($this->conditions);
    }

    public function addDiscountRule(): void
    {
        $this->discountRules[] = [
            'type'                 => 'percentage',
            'value'                => '',
            'buy_quantity'         => '',
            'get_quantity'         => '',
            'max_discount_amount'  => '',
        ];
    }

    public function removeDiscountRule(int $index): void
    {
        unset($this->discountRules[$index]);
        $this->discountRules = array_values($this->discountRules);
    }

    protected function hydrateConditionsFrom(Promotion $promotion): void
    {
        $this->conditions = $promotion->conditions->map(fn ($condition) => [
            'type'     => $condition->type->value,
            'operator' => $condition->operator->value,
            'value'    => $condition->operator->isMultiValue()
                ? implode(',', (array) $condition->value_decoded)
                : (string) $condition->value_decoded,
        ])->all();
    }

    protected function hydrateDiscountRulesFrom(Promotion $promotion): void
    {
        $this->discountRules = $promotion->discountRules->map(fn ($rule) => [
            'type'                => $rule->type->value,
            'value'               => $rule->value !== null ? (string) $rule->value : '',
            'buy_quantity'        => $rule->buy_quantity !== null ? (string) $rule->buy_quantity : '',
            'get_quantity'        => $rule->get_quantity !== null ? (string) $rule->get_quantity : '',
            'max_discount_amount' => $rule->max_discount_amount !== null ? (string) $rule->max_discount_amount : '',
        ])->all();
    }

    protected function syncConditionsTo(Promotion $promotion): void
    {
        $promotion->conditions()->delete();

        foreach ($this->conditions as $condition) {
            $isMultiValue = in_array($condition['operator'], ['in', 'not_in'], true);

            $value = $isMultiValue
                ? json_encode(array_map('trim', explode(',', $condition['value'])))
                : $condition['value'];

            $promotion->conditions()->create([
                'type'     => $condition['type'],
                'operator' => $condition['operator'],
                'value'    => $value,
            ]);
        }
    }

    protected function syncDiscountRulesTo(Promotion $promotion): void
    {
        $promotion->discountRules()->delete();

        foreach ($this->discountRules as $rule) {
            $promotion->discountRules()->create([
                'type'                 => $rule['type'],
                'value'                => $rule['value'] !== '' ? $rule['value'] : null,
                'buy_quantity'         => $rule['buy_quantity'] !== '' ? $rule['buy_quantity'] : null,
                'get_quantity'         => $rule['get_quantity'] !== '' ? $rule['get_quantity'] : null,
                'max_discount_amount'  => $rule['max_discount_amount'] !== '' ? $rule['max_discount_amount'] : null,
            ]);
        }
    }

    protected function promotionRules(): array
    {
        return [
            'name'                          => 'required|string|max:255',
            'description'                   => 'nullable|string',
            'status'                        => 'required|in:draft,active,inactive,expired',
            'priority'                      => 'nullable|integer',
            'startsAt'                      => 'nullable|date',
            'endsAt'                        => 'nullable|date',
            'conditions.*.type'             => 'required|string',
            'conditions.*.operator'         => 'required|string',
            'conditions.*.value'            => 'required|string',
            'discountRules.*.type'          => 'required|string',
        ];
    }
}
