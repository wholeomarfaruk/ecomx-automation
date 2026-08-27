<?php

namespace App\Livewire\Admin\LandingPages;

use App\Models\LandingPageTemplate;
use App\Models\Setting;
use Livewire\Component;

class Settings extends Component
{
    public const GROUP = 'landingpage';

    public ?int $defaultTemplateId = null;
    public string $defaultSeoTitle = '';
    public string $defaultSeoDescription = '';
    public string $defaultHeaderMode = 'global';
    public string $defaultFooterMode = 'global';

    public function mount(): void
    {
        $this->defaultTemplateId = Setting::get('default_template_id', null, self::GROUP);
        $this->defaultSeoTitle = Setting::get('default_seo_title', '', self::GROUP);
        $this->defaultSeoDescription = Setting::get('default_seo_description', '', self::GROUP);
        $this->defaultHeaderMode = Setting::get('default_header_mode', 'global', self::GROUP);
        $this->defaultFooterMode = Setting::get('default_footer_mode', 'global', self::GROUP);
    }

    protected function rules(): array
    {
        return [
            'defaultTemplateId' => 'nullable|exists:landing_page_templates,id',
            'defaultSeoTitle' => 'nullable|string|max:70',
            'defaultSeoDescription' => 'nullable|string|max:160',
            'defaultHeaderMode' => 'required|in:global,custom,none',
            'defaultFooterMode' => 'required|in:global,custom,none',
        ];
    }

    public function save(): void
    {
        $this->validate();

        Setting::set('default_template_id', $this->defaultTemplateId, self::GROUP);
        Setting::set('default_seo_title', $this->defaultSeoTitle, self::GROUP);
        Setting::set('default_seo_description', $this->defaultSeoDescription, self::GROUP);
        Setting::set('default_header_mode', $this->defaultHeaderMode, self::GROUP);
        Setting::set('default_footer_mode', $this->defaultFooterMode, self::GROUP);

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Settings saved']);
    }

    public function render(): mixed
    {
        return view('livewire.admin.landing-pages.settings', [
            'templates' => LandingPageTemplate::active()->orderBy('name')->get(),
        ])->layout('layouts.admin.admin');
    }
}
