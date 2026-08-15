<?php

namespace App\Livewire\Admin\Sms;

use App\Models\SmsTemplate;
use Livewire\Component;

class Templates extends Component
{
    public ?int $editingId = null;
    public string $key = '';
    public string $label = '';
    public string $body = '';
    public bool $is_active = true;

    protected function guardManage(): void
    {
        if (! auth()->user()->can('sms_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function edit(int $id): void
    {
        $template = SmsTemplate::findOrFail($id);
        $this->editingId = $template->id;
        $this->key = $template->key;
        $this->label = $template->label;
        $this->body = $template->body;
        $this->is_active = $template->is_active;
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'key', 'label', 'body', 'is_active']);
        $this->is_active = true;
    }

    public function save(): void
    {
        $this->guardManage();

        $this->validate([
            'key' => ['required', 'string'],
            'label' => ['required', 'string'],
            'body' => ['required', 'string'],
        ]);

        SmsTemplate::updateOrCreate(
            ['id' => $this->editingId],
            [
                'key' => $this->key,
                'label' => $this->label,
                'body' => $this->body,
                'is_active' => $this->is_active,
            ]
        );

        $this->resetForm();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Template saved.']);
    }

    public function delete(int $id): void
    {
        $this->guardManage();

        SmsTemplate::findOrFail($id)->delete();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Template deleted.']);
    }

    public function render()
    {
        if (! auth()->user()->can('sms_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        return view('livewire.admin.sms.templates', [
            'templates' => SmsTemplate::orderBy('label')->get(),
        ])->layout('layouts.admin.admin');
    }
}
