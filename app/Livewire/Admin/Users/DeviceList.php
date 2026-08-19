<?php

namespace App\Livewire\Admin\Users;

use App\Models\Block;
use App\Models\Device;
use App\Services\BlockGuard;
use App\Support\DeviceActivity;
use Livewire\Component;
use Livewire\WithPagination;

class DeviceList extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterType   = '';
    public string $filterStatus = '';

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterType(): void   { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function toggleTrusted(int $id): void
    {
        $device = Device::findOrFail($id);
        $device->update(['is_trusted' => ! $device->is_trusted]);

        $this->dispatch('toast', ['type' => 'success', 'message' => $device->is_trusted ? 'Device marked as trusted' : 'Device marked as untrusted']);
    }

    /**
     * Quick full_site block toggle from the list — creates/removes a Block
     * row instead of flipping the old standalone is_allowed flag, so this
     * stays the single source of truth BlockGuard actually enforces.
     */
    public function toggleAllowed(int $id): void
    {
        $device = Device::findOrFail($id);
        $block  = $device->activeBlocks()->forScope(Block::SCOPE_FULL_SITE)->first();

        if ($block) {
            $block->delete();
            $message = 'Device unblocked';
        } else {
            Block::create([
                'blockable_type' => Device::class,
                'blockable_id'   => $device->id,
                'scope'          => Block::SCOPE_FULL_SITE,
                'blocked_by'     => auth()->id(),
                'is_active'      => true,
            ]);
            $message = 'Device blocked';
        }

        BlockGuard::forget(Device::class, $device->id, Block::SCOPE_FULL_SITE);

        activity('blocks')
            ->causedBy(auth()->user())
            ->performedOn($device)
            ->event($block ? 'deleted' : 'created')
            ->log($message . " for device #{$device->id}");

        $this->dispatch('toast', ['type' => 'success', 'message' => $message]);
    }

    public function deleteDevice(int $id): void
    {
        Device::findOrFail($id)->delete();

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Device removed']);
    }

    public function render(): mixed
    {
        $activeSince = DeviceActivity::threshold();

        $devices = Device::query()
            ->with(['customer', 'user'])
            ->withExists(['activeBlocks as is_blocked' => fn ($q) => $q->forScope(Block::SCOPE_FULL_SITE)])
            ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                ->where('device_brand', 'like', "%{$this->search}%")
                ->orWhere('device_model', 'like', "%{$this->search}%")
                ->orWhere('operating_system', 'like', "%{$this->search}%")
                ->orWhere('browser', 'like', "%{$this->search}%")
                ->orWhere('ip_address', 'like', "%{$this->search}%")
                ->orWhere('fingerprint', 'like', "%{$this->search}%")
            ))
            ->when($this->filterType !== '', fn ($q) => $q->where('device_type', $this->filterType))
            ->when($this->filterStatus === 'trusted', fn ($q) => $q->where('is_trusted', true))
            ->when($this->filterStatus === 'blocked', fn ($q) => $q->whereHas('activeBlocks', fn ($b) => $b->forScope(Block::SCOPE_FULL_SITE)))
            ->when($this->filterStatus === 'active', fn ($q) => $q->where('last_active_at', '>=', $activeSince))
            ->when($this->filterStatus === 'inactive', fn ($q) => $q->where(fn ($sq) => $sq->whereNull('last_active_at')->orWhere('last_active_at', '<', $activeSince)))
            ->orderByDesc('last_active_at')
            ->paginate(15);

        return view('livewire.admin.users.device-list', [
            'devices'      => $devices,
            'deviceTypes'  => Device::distinct()->whereNotNull('device_type')->pluck('device_type'),
            'totalCount'   => Device::count(),
            'trustedCount' => Device::where('is_trusted', true)->count(),
            'blockedCount' => Device::whereHas('activeBlocks', fn ($q) => $q->forScope(Block::SCOPE_FULL_SITE))->count(),
            'activeCount'  => Device::where('last_active_at', '>=', $activeSince)->count(),
        ])->layout('layouts.admin.admin');
    }
}
