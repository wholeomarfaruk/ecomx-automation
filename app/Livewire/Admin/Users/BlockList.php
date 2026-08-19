<?php

namespace App\Livewire\Admin\Users;

use App\Models\Block;
use App\Models\Customer;
use App\Models\Device;
use App\Models\User;
use App\Services\BlockGuard;
use Livewire\Component;
use Livewire\WithPagination;

class BlockList extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterScope  = '';
    public string $filterType   = '';
    public string $filterStatus = '';

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterScope(): void  { $this->resetPage(); }
    public function updatingFilterType(): void   { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function toggleBlock(int $blockId): void
    {
        $block = Block::findOrFail($blockId);
        $block->update(['is_active' => ! $block->is_active]);

        $this->clearCacheFor($block);

        activity('blocks')
            ->causedBy(auth()->user())
            ->performedOn($block)
            ->event('updated')
            ->log($block->is_active ? 'Block re-enabled' : 'Block disabled');

        $this->dispatch('toast', ['type' => 'success', 'message' => $block->is_active ? 'Block enabled' : 'Block disabled']);
    }

    public function deleteBlock(int $blockId): void
    {
        $block = Block::findOrFail($blockId);
        $this->clearCacheFor($block);
        $block->delete();

        activity('blocks')
            ->causedBy(auth()->user())
            ->event('deleted')
            ->log('Block removed');

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Block removed']);
    }

    protected function clearCacheFor(Block $block): void
    {
        if ($block->ip_address) {
            BlockGuard::forgetIp($block->ip_address, $block->scope);
        } elseif ($block->blockable_type && $block->blockable_id) {
            BlockGuard::forget($block->blockable_type, $block->blockable_id, $block->scope);
        }
    }

    protected function targetLabel(Block $block): string
    {
        if ($block->ip_address) {
            return $block->ip_address;
        }

        return match ($block->blockable_type) {
            Device::class   => $block->blockable ? (($block->blockable->device_brand ?? ucfirst($block->blockable->device_type)) . " (#{$block->blockable_id})") : "Device #{$block->blockable_id}",
            Customer::class => $block->blockable?->full_name ?? "Customer #{$block->blockable_id}",
            User::class     => $block->blockable?->name ?? "User #{$block->blockable_id}",
            default         => '—',
        };
    }

    public function render(): mixed
    {
        $blocks = Block::query()
            ->with(['blockable', 'blockedBy'])
            ->when($this->filterScope !== '', fn ($q) => $q->where('scope', $this->filterScope))
            ->when($this->filterType === 'ip', fn ($q) => $q->whereNotNull('ip_address'))
            ->when($this->filterType === 'device', fn ($q) => $q->where('blockable_type', Device::class))
            ->when($this->filterType === 'customer', fn ($q) => $q->where('blockable_type', Customer::class))
            ->when($this->filterType === 'user', fn ($q) => $q->where('blockable_type', User::class))
            ->when($this->filterStatus === 'active', fn ($q) => $q->applicable())
            ->when($this->filterStatus === 'disabled', fn ($q) => $q->where('is_active', false))
            ->when($this->filterStatus === 'expired', fn ($q) => $q->where('is_active', true)->whereNotNull('expires_at')->where('expires_at', '<=', now()))
            ->when($this->search, fn ($q) => $q->where(fn ($s) => $s
                ->where('ip_address', 'like', "%{$this->search}%")
                ->orWhere('reason', 'like', "%{$this->search}%")
            ))
            ->orderByDesc('id')
            ->paginate(20);

        $blocks->getCollection()->each(fn (Block $b) => $b->setAttribute('target_label', $this->targetLabel($b)));

        return view('livewire.admin.users.block-list', [
            'blocks'        => $blocks,
            'totalCount'    => Block::count(),
            'activeCount'   => Block::applicable()->count(),
            'fullSiteCount' => Block::applicable()->forScope(Block::SCOPE_FULL_SITE)->count(),
        ])->layout('layouts.admin.admin');
    }
}
