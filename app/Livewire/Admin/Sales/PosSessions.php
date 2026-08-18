<?php

namespace App\Livewire\Admin\Sales;

use App\Models\PosRegister;
use App\Models\PosSession;
use Livewire\Component;
use Livewire\WithPagination;

class PosSessions extends Component
{
    use WithPagination;

    public string $search        = '';
    public string $filterRegister = '';
    public string $filterStatus  = '';

    protected string $paginationTheme = 'tailwind';

    public function updatingSearch(): void         { $this->resetPage(); }
    public function updatingFilterRegister(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void   { $this->resetPage(); }

    public function render(): mixed
    {
        $sessions = PosSession::query()
            ->with('register.branch', 'user')
            ->withCount('sales')
            ->when($this->search, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$this->search}%")))
            ->when($this->filterRegister !== '', fn ($q) => $q->where('register_id', $this->filterRegister))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('id')
            ->paginate(20);

        return view('livewire.admin.sales.pos-sessions', [
            'sessions'    => $sessions,
            'registers'   => PosRegister::orderBy('name')->get(['id', 'name']),
            'openCount'   => PosSession::where('status', 'open')->count(),
            'totalCount'  => PosSession::count(),
        ])->layout('layouts.admin.admin');
    }
}
