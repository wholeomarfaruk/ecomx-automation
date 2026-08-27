<?php

namespace App\Livewire\Admin\Marketing\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;

/**
 * Shared Today/Yesterday/7d/30d/90d/All time/Custom range picker for
 * marketing analytics screens. `since()`/`until()` return null for the
 * open ends of "all time" so callers can conditionally apply where clauses.
 */
trait HasDateRange
{
    #[Url]
    public string $range = '30d';

    #[Url]
    public string $customFrom = '';

    #[Url]
    public string $customTo = '';

    public function ranges(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            '7d' => '7 days',
            '30d' => '30 days',
            '90d' => '90 days',
            'all' => 'All time',
            'custom' => 'Custom',
        ];
    }

    public function updatedCustomFrom(): void
    {
        $this->range = 'custom';
    }

    public function updatedCustomTo(): void
    {
        $this->range = 'custom';
    }

    protected function since(): ?CarbonInterface
    {
        return match ($this->range) {
            'today' => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            '7d' => now()->subDays(7),
            '90d' => now()->subDays(90),
            'all' => null,
            'custom' => $this->customFrom !== '' ? Carbon::parse($this->customFrom)->startOfDay() : null,
            default => now()->subDays(30),
        };
    }

    protected function until(): ?CarbonInterface
    {
        return match ($this->range) {
            'yesterday' => now()->subDay()->endOfDay(),
            'custom' => $this->customTo !== '' ? Carbon::parse($this->customTo)->endOfDay() : null,
            default => null,
        };
    }
}
