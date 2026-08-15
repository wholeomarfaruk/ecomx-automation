<?php

namespace App\Livewire\Admin\Email;

use App\Mail\TemplatedMail;
use App\Models\EmailTemplate;
use App\Support\PrebuiltEmailTemplates;
use Livewire\Component;

class Templates extends Component
{
    public ?string $previewKey = null;

    // Sample values used only to render the preview — never sent anywhere.
    protected array $previewData = [
        'name' => 'John Doe',
        'code' => '123456',
        'order_id' => '1042',
        'invoice_id' => 'INV-1042',
        'amount' => '$49.00',
        'app_name' => 'Laravel Starter Kit',
        'year' => '2026',
    ];

    protected function guardManage(): void
    {
        if (! auth()->user()->can('email_configuration.manage')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function preview(string $key): void
    {
        $this->previewKey = $key;
    }

    public function closePreview(): void
    {
        $this->previewKey = null;
    }

    public function activate(string $key): void
    {
        $this->guardManage();

        $prebuilt = PrebuiltEmailTemplates::find($key);

        if (! $prebuilt) {
            return;
        }

        $body = PrebuiltEmailTemplates::renderBody($key);

        EmailTemplate::updateOrCreate(
            ['key' => $prebuilt['template_key']],
            [
                'label' => $prebuilt['label'],
                'subject' => $prebuilt['subject'],
                'body' => $body,
                'is_active' => true,
            ]
        );

        $this->previewKey = null;

        $this->dispatch('toast', ['type' => 'success', 'message' => "\"{$prebuilt['label']}\" activated."]);
    }

    public function render()
    {
        if (! auth()->user()->can('email_configuration.view')) {
            return abort(403, 'Unauthorized action.');
        }

        $activeTemplateKeys = EmailTemplate::where('is_active', true)->pluck('subject', 'key');

        return view('livewire.admin.email.templates', [
            'prebuiltTemplates' => PrebuiltEmailTemplates::all(),
            'activeTemplateKeys' => $activeTemplateKeys->keys()->all(),
            'previewSubject' => $this->previewKey ? $this->renderPreviewSubject() : null,
            'previewHtml' => $this->previewKey ? $this->renderPreviewHtml() : null,
        ])->layout('layouts.admin.admin');
    }

    protected function previewMailable(): ?TemplatedMail
    {
        $prebuilt = PrebuiltEmailTemplates::find($this->previewKey);

        if (! $prebuilt) {
            return null;
        }

        $template = new EmailTemplate([
            'subject' => $prebuilt['subject'],
            'body' => PrebuiltEmailTemplates::renderBody($this->previewKey) ?? '',
        ]);

        return new TemplatedMail($template, $this->previewData);
    }

    protected function renderPreviewSubject(): ?string
    {
        return $this->previewMailable()?->envelope()->subject;
    }

    protected function renderPreviewHtml(): ?string
    {
        try {
            return $this->previewMailable()?->render();
        } catch (\Throwable $e) {
            return '<p style="font-family: sans-serif; color: #b91c1c; padding: 16px;">Preview error: ' . e($e->getMessage()) . '</p>';
        }
    }
}
