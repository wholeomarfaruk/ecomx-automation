<?php

namespace App\Livewire\Admin\Sales;

use App\Models\PosCashMovement;
use App\Models\PosRegister;
use App\Models\PosSession;
use Livewire\Component;

class PosSessionDetail extends Component
{
    public ?int $sessionId = null;

    // open-session form
    public string $registerId   = '';
    public string $openingCash  = '0';
    public string $openNotes    = '';

    // cash movement form
    public string $movementType   = 'cash_in';
    public string $movementAmount = '';
    public string $movementReason = '';

    // close-session form
    public bool   $closeModal    = false;
    public string $closingCash   = '';

    public function mount(?int $id = null): void
    {
        $this->sessionId = $id;
    }

    public function openSession(): void
    {
        $this->validate([
            'registerId'  => 'required|integer|exists:pos_registers,id',
            'openingCash' => 'required|numeric|min:0',
        ]);

        if (PosSession::where('register_id', $this->registerId)->open()->exists()) {
            $this->addError('registerId', 'This register already has an open session.');
            return;
        }

        $session = PosSession::create([
            'register_id'  => $this->registerId,
            'user_id'      => auth()->id(),
            'status'       => 'open',
            'opening_cash' => $this->openingCash,
            'opened_at'    => now(),
            'notes'        => $this->openNotes ?: null,
        ]);

        $session->cashMovements()->create([
            'type'       => 'opening',
            'amount'     => $this->openingCash,
            'reason'     => 'Session opened',
            'created_by' => auth()->id(),
        ]);

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($session)
            ->event('created')
            ->log("POS session #{$session->id} opened on register \"{$session->register->name}\"");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Session opened']);

        $this->redirect(route('admin.sales.pos.sessions.show', $session->id), navigate: true);
    }

    public function addCashMovement(): void
    {
        $this->validate([
            'movementType'   => 'required|in:cash_in,cash_out',
            'movementAmount' => 'required|numeric|min:0.01',
        ]);

        $session = PosSession::findOrFail($this->sessionId);

        $session->cashMovements()->create([
            'type'       => $this->movementType,
            'amount'     => $this->movementAmount,
            'reason'     => $this->movementReason ?: null,
            'created_by' => auth()->id(),
        ]);

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($session)
            ->event('updated')
            ->log("Cash movement ({$this->movementType}) of {$this->movementAmount} recorded for session #{$session->id}");

        $this->reset(['movementAmount', 'movementReason']);
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Cash movement recorded']);
    }

    public function openCloseModal(): void
    {
        $session = PosSession::findOrFail($this->sessionId);
        $this->closingCash = (string) round($session->expectedCash(), 2);
        $this->closeModal  = true;
    }

    public function closeSession(): void
    {
        $this->validate([
            'closingCash' => 'required|numeric|min:0',
        ]);

        $session = PosSession::findOrFail($this->sessionId);

        $session->cashMovements()->create([
            'type'       => 'closing',
            'amount'     => $this->closingCash,
            'reason'     => 'Session closed',
            'created_by' => auth()->id(),
        ]);

        $session->update([
            'status'       => 'closed',
            'closing_cash' => $this->closingCash,
            'closed_at'    => now(),
        ]);

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($session)
            ->event('updated')
            ->log("POS session #{$session->id} closed");

        $this->closeModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Session closed']);
    }

    public function render(): mixed
    {
        $session = $this->sessionId ? PosSession::with([
            'register.branch',
            'user',
            'cashMovements' => fn ($q) => $q->orderByDesc('id'),
            'sales.order',
            'sales.customer',
        ])->find($this->sessionId) : null;

        return view('livewire.admin.sales.pos-session-detail', [
            'session'   => $session,
            'registers' => PosRegister::active()->orderBy('name')->get(['id', 'name']),
            'expectedCash' => $session ? $session->expectedCash() : 0,
            'variance'  => ($session && $this->closingCash !== '')
                ? (float) $this->closingCash - $session->expectedCash()
                : null,
        ])->layout('layouts.admin.admin');
    }
}
