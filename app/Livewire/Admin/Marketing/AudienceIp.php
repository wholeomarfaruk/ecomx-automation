<?php

namespace App\Livewire\Admin\Marketing;

use App\Models\Marketing\MarketingEvent;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin.admin')]
class AudienceIp extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public string $search = '';

    public function updatingSearch(): void { $this->resetPage(); }

    public function render()
    {
        $ips = MarketingEvent::query()
            ->whereNotNull('ip_address')
            ->when($this->search, fn ($q) => $q->where('ip_address', 'like', "%{$this->search}%"))
            ->selectRaw('ip_address, COUNT(DISTINCT device_id) as device_count, COUNT(DISTINCT customer_id) as customer_count, COUNT(*) as event_count, MAX(occurred_at) as last_seen')
            ->groupBy('ip_address')
            ->orderByDesc('event_count')
            ->paginate(25);

        return view('livewire.admin.marketing.audience-ip', [
            'ips' => $ips,
        ]);
    }
}
