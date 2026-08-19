<?php

namespace App\Livewire\Admin\Users;

use App\Models\Block;
use App\Models\Device;
use App\Services\BlockGuard;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DeviceDetail extends Component
{
    use WithPagination;

    public int $deviceId;

    #[Url]
    public string $tab = 'info';

    protected string $paginationTheme = 'tailwind';

    // block modal
    public bool   $blockModal     = false;
    public string $blockScope     = Block::SCOPE_FULL_SITE;
    public string $blockReason    = '';
    public string $blockExpiresAt = '';

    public function mount(int $id): void
    {
        $this->deviceId = $id;
    }

    public function openBlockModal(): void
    {
        $this->reset(['blockReason', 'blockExpiresAt']);
        $this->blockScope = Block::SCOPE_FULL_SITE;
        $this->resetValidation();
        $this->blockModal = true;
    }

    public function createBlock(): void
    {
        $this->validate([
            'blockScope'     => 'required|in:' . implode(',', Block::SCOPES),
            'blockReason'    => 'nullable|string|max:500',
            'blockExpiresAt' => 'nullable|date|after:now',
        ]);

        $block = Block::create([
            'blockable_type' => Device::class,
            'blockable_id'   => $this->deviceId,
            'scope'          => $this->blockScope,
            'reason'         => $this->blockReason ?: null,
            'blocked_by'     => auth()->id(),
            'expires_at'     => $this->blockExpiresAt ?: null,
            'is_active'      => true,
        ]);

        BlockGuard::forget(Device::class, $this->deviceId, $this->blockScope);

        activity('blocks')
            ->causedBy(auth()->user())
            ->performedOn($block)
            ->event('created')
            ->log("Block created ({$block->scopeLabel()}) on device #{$this->deviceId}");

        $this->blockModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Block created']);
    }

    public function toggleBlock(int $blockId): void
    {
        $block = Block::findOrFail($blockId);
        $block->update(['is_active' => ! $block->is_active]);

        BlockGuard::forget($block->blockable_type, $block->blockable_id, $block->scope);

        $this->dispatch('toast', ['type' => 'success', 'message' => $block->is_active ? 'Block enabled' : 'Block disabled']);
    }

    public function deleteBlock(int $blockId): void
    {
        $block = Block::findOrFail($blockId);
        BlockGuard::forget($block->blockable_type, $block->blockable_id, $block->scope);
        $block->delete();

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Block removed']);
    }

    public function render(): mixed
    {
        $device = Device::with(['customer', 'user'])->findOrFail($this->deviceId);

        $ipAddresses = $this->tab === 'ips'
            ? $device->ipAddresses()->orderByDesc('last_seen_at')->paginate(15, pageName: 'ipsPage')
            : null;

        $visits = $this->tab === 'visits'
            ? $device->visits()->orderByDesc('created_at')->paginate(15, pageName: 'visitsPage')
            : null;

        $blocks = $this->tab === 'blocks'
            ? $device->blocks()->with('blockedBy')->orderByDesc('id')->get()
            : null;

        return view('livewire.admin.users.device-detail', [
            'device'       => $device,
            'ipAddresses'  => $ipAddresses,
            'visits'       => $visits,
            'blocks'       => $blocks,
            'isBlocked'    => $device->hasActiveBlock(Block::SCOPE_FULL_SITE),
        ])->layout('layouts.admin.admin');
    }
}
