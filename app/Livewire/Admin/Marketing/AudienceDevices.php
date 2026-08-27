<?php

namespace App\Livewire\Admin\Marketing;

use App\Models\Device;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin.admin')]
class AudienceDevices extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    #[Url]
    public string $deviceType = '';

    #[Url]
    public string $search = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingDeviceType(): void { $this->resetPage(); }

    public function render()
    {
        $devices = Device::query()
            ->withCount(['marketingEvents' => fn ($q) => $q])
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('fingerprint', 'like', "%{$this->search}%")
                ->orWhere('ip_address', 'like', "%{$this->search}%")
            ))
            ->when($this->deviceType !== '', fn ($q) => $q->where('device_type', $this->deviceType))
            ->orderByDesc('last_active_at')
            ->paginate(25);

        $typeBreakdown = Device::query()
            ->selectRaw('device_type, COUNT(*) as total')
            ->groupBy('device_type')
            ->orderByDesc('total')
            ->get();

        return view('livewire.admin.marketing.audience-devices', [
            'devices' => $devices,
            'typeBreakdown' => $typeBreakdown,
            'totalDevices' => Device::count(),
        ]);
    }
}
