<?php

namespace App\Livewire\Admin\SiteSettings;

use App\Livewire\Traits\WithMediaPicker;
use App\Marketing\Destinations\DestinationRegistry;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class SiteSettings extends Component
{
    use WithMediaPicker;

    // Bound to ?group=... so sidebar links (e.g. Advance → Marketing) can
    // deep-link straight into a tab. Not written back to the URL on every
    // in-page tab click (as:'group' + no history push isn't needed) — the
    // default keeps existing bare /admin/site-settings links landing on
    // "general" exactly as before.
    #[Url(as: 'group')]
    public string $activeGroup = 'general';

    // General
    public string  $site_name        = '';
    public string  $site_short_name  = '';
    public string  $site_tagline     = '';

    // Application (read-only display + maintenance toggle, not part of the general save)
    public bool $maintenance_mode = false;

    // Company
    public string $company_name          = '';
    public string $company_legal_name    = '';
    public string $company_email         = '';
    public string $company_phone         = '';
    public string $company_mobile        = '';
    public string $company_website       = '';
    public string $company_address       = '';
    public string $company_city          = '';
    public string $company_state         = '';
    public ?int   $company_country_id    = null;
    public string $company_postal_code   = '';
    public string $company_tax_number    = '';
    public string $company_trade_license = '';
    public string $company_map_location  = '';

    public ?int $company_logo    = null;
    public ?int $company_favicon = null;

    // Branding (file IDs)
    public ?int $site_logo        = null;
    public ?int $site_logo_black  = null;
    public ?int $site_logo_white  = null;
    public ?int $site_logo_symbol = null;
    public ?int $site_favicon     = null;

    // Localization
    public string  $timezone           = 'UTC';
    public string  $date_format        = 'd-m-Y';
    public string  $time_format        = 'H:i';
    public string  $currency           = 'USD';
    public string  $currency_symbol    = '$';
    public string  $number_format      = '1,234.56';
    public ?int    $default_language_id = null;

    // Mail
    public string $mail_from_name    = '';
    public string $mail_from_address = '';

    // Social
    public string $facebook       = '';
    public string $facebook_group = '';
    public string $twitter        = '';
    public string $instagram      = '';
    public string $linkedin       = '';
    public string $tiktok         = '';

    // Registration
    public bool $allow_registration    = true;
    public bool $restrict_by_country   = false;
    public bool $require_email_verify  = false;

    // Pricing
    public string $min_margin_percent = '15';

    // Marketing
    public string $meta_pixel_id             = '';
    public string $meta_access_token         = '';
    public string $meta_api_version          = 'v23.0';
    public string $meta_test_event_code      = '';
    public bool   $gtm_enabled               = false;
    public string $gtm_container_id          = '';
    public string $tiktok_pixel_id           = '';
    public string $ga4_measurement_id        = '';
    public string $server_destinations       = 'meta';
    public string $session_timeout_minutes   = '30';
    public string $attribution_lifetime_days = '90';

    // Queue (read-only display + notes)
    public string $queue_notes = '';
    public string $queue_cron_supervisor_path = '';

    /** @var array<int, string> */
    public array $timezoneOptions = [];

    public function mount(): void
    {
        $this->timezoneOptions = timezone_identifiers_list();

        $this->loadSettings();
    }

    public function setGroup(string $group): void
    {
        $this->activeGroup = $group;
    }

    /**
     * Applies immediately — not part of the "Save Settings" form flow, since
     * this mutates live filesystem/maintenance state rather than a Setting row.
     */
    public function toggleMaintenanceMode(): void
    {
        $goingDown = ! app()->isDownForMaintenance();

        Artisan::call($goingDown ? 'down' : 'up');

        $this->maintenance_mode = app()->isDownForMaintenance();

        activity('settings')
            ->causedBy(auth()->user())
            ->event('updated')
            ->log($goingDown ? 'Application was put into maintenance mode' : 'Application was brought back online');

        $this->dispatch('toast', [
            'type'    => 'success',
            'message' => $goingDown ? 'Application is now in maintenance mode' : 'Application is back online',
        ]);
    }

    public function save(): void
    {
        if ($this->activeGroup === 'general') {
            $this->validate([
                'site_name'       => 'required|string|max:100',
                'site_short_name' => 'nullable|string|max:50',
            ]);

            $old = [
                'site_name'       => Setting::get('site_name'),
                'site_short_name' => Setting::get('site_short_name'),
                'site_tagline'    => Setting::get('site_tagline'),
            ];

            Setting::set('site_name',         $this->site_name);
            Setting::set('site_short_name',   $this->site_short_name);
            Setting::set('site_tagline',      $this->site_tagline);
            Setting::set('site_logo',         $this->site_logo        ? (string) $this->site_logo        : null);
            Setting::set('site_logo_black',   $this->site_logo_black  ? (string) $this->site_logo_black  : null);
            Setting::set('site_logo_white',   $this->site_logo_white  ? (string) $this->site_logo_white  : null);
            Setting::set('site_logo_symbol',  $this->site_logo_symbol ? (string) $this->site_logo_symbol : null);
            Setting::set('site_favicon',      $this->site_favicon     ? (string) $this->site_favicon     : null);

            $new = [
                'site_name'       => $this->site_name,
                'site_short_name' => $this->site_short_name,
                'site_tagline'    => $this->site_tagline,
            ];

            $this->logSettingsChange('General settings were updated', $old, $new);
        }

        if ($this->activeGroup === 'company') {
            $this->validate([
                'company_name'       => 'required|string|max:150',
                'company_legal_name' => 'nullable|string|max:150',
                'company_email'      => 'nullable|email|max:150',
                'company_phone'      => 'nullable|string|max:30',
                'company_mobile'     => 'nullable|string|max:30',
                'company_website'    => 'nullable|url|max:150',
                'company_address'    => 'nullable|string|max:255',
                'company_city'       => 'nullable|string|max:100',
                'company_state'      => 'nullable|string|max:100',
                'company_country_id' => 'nullable|integer|exists:countries,id',
                'company_postal_code'   => 'nullable|string|max:20',
                'company_tax_number'    => 'nullable|string|max:50',
                'company_trade_license' => 'nullable|string|max:50',
                'company_map_location'  => 'nullable|string|max:1000',
            ]);

            $companyFields = [
                'company_name', 'company_legal_name', 'company_email', 'company_phone',
                'company_mobile', 'company_website', 'company_address', 'company_city',
                'company_state', 'company_country_id', 'company_postal_code',
                'company_tax_number', 'company_trade_license', 'company_map_location',
            ];

            $old = [];
            foreach ($companyFields as $field) {
                $old[$field] = Setting::get($field, null, 'company');
            }

            foreach ($companyFields as $field) {
                Setting::set($field, $this->$field !== null ? (string) $this->$field : null, 'company');
            }

            Setting::set('company_logo',    $this->company_logo    ? (string) $this->company_logo    : null, 'company');
            Setting::set('company_favicon', $this->company_favicon ? (string) $this->company_favicon : null, 'company');

            $new = [];
            foreach ($companyFields as $field) {
                $new[$field] = $this->$field;
            }

            $this->logSettingsChange('Company information was updated', $old, $new);
        }

        if ($this->activeGroup === 'localization') {
            $this->validate([
                'timezone'            => 'required|string|timezone',
                'date_format'         => 'required|string',
                'time_format'         => 'required|string',
                'currency'            => 'required|string|exists:currencies,code',
                'default_language_id' => 'required|integer|exists:languages,id',
            ]);

            $old = [
                'timezone'        => Setting::get('timezone', null, 'localization'),
                'date_format'     => Setting::get('date_format', null, 'localization'),
                'time_format'     => Setting::get('time_format', null, 'localization'),
                'currency'        => Setting::get('currency', null, 'localization'),
                'currency_symbol' => Setting::get('currency_symbol', null, 'localization'),
                'number_format'   => Setting::get('number_format', null, 'localization'),
            ];

            $selectedCurrency = Currency::where('code', $this->currency)->firstOrFail();
            $this->currency_symbol = $selectedCurrency->symbol ?? '';

            Setting::set('timezone',        $this->timezone,        'localization');
            Setting::set('date_format',     $this->date_format,     'localization');
            Setting::set('time_format',     $this->time_format,     'localization');
            Setting::set('currency',        $this->currency,        'localization');
            Setting::set('currency_symbol', $this->currency_symbol, 'localization');
            Setting::set('number_format',   $this->number_format,   'localization');

            $language = Language::findOrFail($this->default_language_id);

            if (! $language->is_default) {
                DB::transaction(function () use ($language) {
                    Language::where('is_default', true)->update(['is_default' => false]);
                    $language->update(['is_default' => true]);
                });

                Cache::forget('languages:active');
            }

            $new = [
                'timezone'        => $this->timezone,
                'date_format'     => $this->date_format,
                'time_format'     => $this->time_format,
                'currency'        => $this->currency,
                'currency_symbol' => $this->currency_symbol,
                'number_format'   => $this->number_format,
            ];

            $this->logSettingsChange('Localization settings were updated', $old, $new);
        }

        if ($this->activeGroup === 'mail') {
            $this->validate([
                'mail_from_name'    => 'required|string|max:100',
                'mail_from_address' => 'required|email',
            ]);

            $old = [
                'from_name'    => Setting::get('from_name',    '', 'mail'),
                'from_address' => Setting::get('from_address', '', 'mail'),
            ];

            Setting::set('from_name',    $this->mail_from_name,    'mail');
            Setting::set('from_address', $this->mail_from_address, 'mail');

            $this->logSettingsChange('Mail settings were updated', $old, [
                'from_name'    => $this->mail_from_name,
                'from_address' => $this->mail_from_address,
            ]);
        }

        if ($this->activeGroup === 'social') {
            $old = [
                'facebook'       => Setting::get('facebook',       '', 'social'),
                'facebook_group' => Setting::get('facebook_group', '', 'social'),
                'twitter'        => Setting::get('twitter',        '', 'social'),
                'instagram'      => Setting::get('instagram',      '', 'social'),
                'linkedin'       => Setting::get('linkedin',       '', 'social'),
                'tiktok'         => Setting::get('tiktok',         '', 'social'),
            ];

            Setting::set('facebook',       $this->facebook,       'social');
            Setting::set('facebook_group', $this->facebook_group, 'social');
            Setting::set('twitter',        $this->twitter,        'social');
            Setting::set('instagram',      $this->instagram,      'social');
            Setting::set('linkedin',       $this->linkedin,       'social');
            Setting::set('tiktok',         $this->tiktok,         'social');

            $this->logSettingsChange('Social settings were updated', $old, [
                'facebook'       => $this->facebook,
                'facebook_group' => $this->facebook_group,
                'twitter'        => $this->twitter,
                'instagram'      => $this->instagram,
                'linkedin'       => $this->linkedin,
                'tiktok'         => $this->tiktok,
            ]);
        }

        if ($this->activeGroup === 'registration') {
            $old = [
                'allow_registration'   => (bool) Setting::get('allow_registration',   '1', 'registration'),
                'restrict_by_country'  => (bool) Setting::get('restrict_by_country',  '0', 'registration'),
                'require_email_verify' => (bool) Setting::get('require_email_verify', '0', 'registration'),
            ];

            Setting::set('allow_registration',   $this->allow_registration   ? '1' : '0', 'registration');
            Setting::set('restrict_by_country',  $this->restrict_by_country  ? '1' : '0', 'registration');
            Setting::set('require_email_verify', $this->require_email_verify ? '1' : '0', 'registration');
            Setting::forgetGroup('registration');

            $this->logSettingsChange('Registration settings were updated', $old, [
                'allow_registration'   => $this->allow_registration,
                'restrict_by_country'  => $this->restrict_by_country,
                'require_email_verify' => $this->require_email_verify,
            ]);
        }

        if ($this->activeGroup === 'pricing') {
            $this->validate([
                'min_margin_percent' => 'required|numeric|min:0|max:100',
            ]);

            $old = ['min_margin_percent' => Setting::get('min_margin_percent', '15', 'pricing')];

            Setting::set('min_margin_percent', $this->min_margin_percent, 'pricing');

            $this->logSettingsChange('Pricing settings were updated', $old, [
                'min_margin_percent' => $this->min_margin_percent,
            ]);
        }

        if ($this->activeGroup === 'marketing') {
            $registeredDestinations = (new DestinationRegistry())->keys();

            $this->validate([
                'meta_pixel_id'             => 'nullable|string|max:100',
                'meta_access_token'         => 'nullable|string|max:1000',
                'meta_api_version'          => 'nullable|string|max:20',
                'meta_test_event_code'      => 'nullable|string|max:100',
                'gtm_container_id'          => 'nullable|string|max:50|required_if:gtm_enabled,true',
                'tiktok_pixel_id'           => 'nullable|string|max:100',
                'ga4_measurement_id'        => 'nullable|string|max:50',
                'server_destinations'       => 'nullable|string',
                'session_timeout_minutes'   => 'required|numeric|min:1',
                'attribution_lifetime_days' => 'required|numeric|min:1',
            ]);

            $destinationKeys = array_filter(array_map('trim', explode(',', $this->server_destinations)));
            $unknown = array_diff($destinationKeys, $registeredDestinations);

            if (! empty($unknown)) {
                $this->addError('server_destinations', 'Unknown destination(s): '.implode(', ', $unknown).'. Available: '.implode(', ', $registeredDestinations).'.');

                return;
            }

            $marketingFields = [
                'meta_pixel_id', 'meta_access_token', 'meta_api_version', 'meta_test_event_code',
                'gtm_container_id', 'tiktok_pixel_id', 'ga4_measurement_id', 'server_destinations',
                'session_timeout_minutes', 'attribution_lifetime_days',
            ];

            $old = ['gtm_enabled' => (bool) Setting::get('gtm_enabled', '0', 'marketing')];
            foreach ($marketingFields as $field) {
                $old[$field] = Setting::get($field, null, 'marketing');
            }

            Setting::set('gtm_enabled', $this->gtm_enabled ? '1' : '0', 'marketing');
            foreach ($marketingFields as $field) {
                Setting::set($field, $this->$field, 'marketing');
            }

            $new = ['gtm_enabled' => $this->gtm_enabled];
            foreach ($marketingFields as $field) {
                $new[$field] = $this->$field;
            }

            $this->logSettingsChange('Marketing settings were updated', $old, $new);
        }

        if ($this->activeGroup === 'queue') {
            $old = [
                'notes' => Setting::get('notes', '', 'queue'),
                'cron_supervisor_path' => Setting::get('cron_supervisor_path', '', 'queue'),
            ];

            Setting::set('notes', $this->queue_notes, 'queue');
            Setting::set('cron_supervisor_path', $this->queue_cron_supervisor_path, 'queue');

            $this->logSettingsChange('Queue notes were updated', $old, [
                'notes' => $this->queue_notes,
                'cron_supervisor_path' => $this->queue_cron_supervisor_path,
            ]);
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Settings saved successfully']);
    }

    private function logSettingsChange(string $description, array $old, array $new): void
    {
        $changed = array_filter(array_keys($new), fn ($k) => ($old[$k] ?? null) != ($new[$k] ?? null));

        if (empty($changed)) {
            return;
        }

        activity('settings')
            ->causedBy(auth()->user())
            ->withProperties([
                'old'        => array_intersect_key($old, array_flip($changed)),
                'attributes' => array_intersect_key($new, array_flip($changed)),
            ])
            ->event('updated')
            ->log($description);
    }

    public function render()
    {
        return view('livewire.admin.site-settings.site-settings', [
            'languages'  => Language::active()->ordered()->get(),
            'currencies' => Currency::ordered()->get(),
            'countries'  => Country::active()->orderBy('name')->get(),
            'availableDestinations' => (new DestinationRegistry())->keys(),
            'queueConnection' => config('queue.default'),
            'queueFailedDriver' => config('queue.failed.driver'),
            'queueName' => config('queue.connections.'.config('queue.default').'.queue', 'default'),
        ])->layout('layouts.admin.admin');
    }

    private function loadSettings(): void
    {
        $this->site_name        = Setting::get('site_name',       'Laravel Starter Kit');
        $this->site_short_name  = Setting::get('site_short_name', '');
        $this->site_tagline     = Setting::get('site_tagline',    '');

        $this->maintenance_mode = app()->isDownForMaintenance();

        $this->company_name          = Setting::get('company_name',          '', 'company');
        $this->company_legal_name    = Setting::get('company_legal_name',    '', 'company');
        $this->company_email         = Setting::get('company_email',         '', 'company');
        $this->company_phone         = Setting::get('company_phone',         '', 'company');
        $this->company_mobile        = Setting::get('company_mobile',        '', 'company');
        $this->company_website       = Setting::get('company_website',       '', 'company');
        $this->company_address       = Setting::get('company_address',       '', 'company');
        $this->company_city          = Setting::get('company_city',          '', 'company');
        $this->company_state         = Setting::get('company_state',         '', 'company');
        $this->company_postal_code   = Setting::get('company_postal_code',   '', 'company');
        $this->company_tax_number    = Setting::get('company_tax_number',    '', 'company');
        $this->company_trade_license = Setting::get('company_trade_license', '', 'company');
        $this->company_map_location  = Setting::get('company_map_location',  '', 'company');

        $this->company_country_id = ($v = Setting::get('company_country_id', null, 'company')) ? (int) $v : null;
        $this->company_logo       = ($v = Setting::get('company_logo',       null, 'company')) ? (int) $v : null;
        $this->company_favicon    = ($v = Setting::get('company_favicon',    null, 'company')) ? (int) $v : null;

        $this->site_logo        = ($v = Setting::get('site_logo'))        ? (int) $v : null;
        $this->site_logo_black  = ($v = Setting::get('site_logo_black'))  ? (int) $v : null;
        $this->site_logo_white  = ($v = Setting::get('site_logo_white'))  ? (int) $v : null;
        $this->site_logo_symbol = ($v = Setting::get('site_logo_symbol')) ? (int) $v : null;
        $this->site_favicon     = ($v = Setting::get('site_favicon'))     ? (int) $v : null;

        $this->timezone         = Setting::get('timezone',        'Asia/Dhaka', 'localization');
        $this->date_format      = Setting::get('date_format',     'd-m-Y',      'localization');
        $this->time_format      = Setting::get('time_format',     'H:i',        'localization');
        $this->currency         = Setting::get('currency',        'BDT',        'localization');
        $this->currency_symbol  = Setting::get('currency_symbol', '৳',          'localization');
        $this->number_format    = Setting::get('number_format',  '1,234.56',    'localization');

        $this->default_language_id = Language::where('is_default', true)->value('id');

        $this->mail_from_name    = Setting::get('from_name',    'Laravel Starter Kit', 'mail');
        $this->mail_from_address = Setting::get('from_address', '', 'mail');

        $this->facebook       = Setting::get('facebook',       '', 'social');
        $this->facebook_group = Setting::get('facebook_group', '', 'social');
        $this->twitter        = Setting::get('twitter',        '', 'social');
        $this->instagram      = Setting::get('instagram',      '', 'social');
        $this->linkedin       = Setting::get('linkedin',       '', 'social');
        $this->tiktok         = Setting::get('tiktok',         '', 'social');

        $this->allow_registration   = (bool) Setting::get('allow_registration',   '1', 'registration');
        $this->restrict_by_country  = (bool) Setting::get('restrict_by_country',  '0', 'registration');
        $this->require_email_verify = (bool) Setting::get('require_email_verify', '0', 'registration');

        $this->min_margin_percent = Setting::get('min_margin_percent', '15', 'pricing');

        $this->meta_pixel_id             = Setting::get('meta_pixel_id',             '',      'marketing');
        $this->meta_access_token         = Setting::get('meta_access_token',         '',      'marketing');
        $this->meta_api_version          = Setting::get('meta_api_version',          'v23.0', 'marketing');
        $this->meta_test_event_code      = Setting::get('meta_test_event_code',      '',      'marketing');
        $this->gtm_enabled               = (bool) Setting::get('gtm_enabled',        '0',     'marketing');
        $this->gtm_container_id          = Setting::get('gtm_container_id',          '',      'marketing');
        $this->tiktok_pixel_id           = Setting::get('tiktok_pixel_id',           '',      'marketing');
        $this->ga4_measurement_id        = Setting::get('ga4_measurement_id',        '',      'marketing');
        $this->server_destinations       = Setting::get('server_destinations',       'meta',  'marketing');
        $this->session_timeout_minutes   = Setting::get('session_timeout_minutes',   '30',    'marketing');
        $this->attribution_lifetime_days = Setting::get('attribution_lifetime_days', '90',    'marketing');

        $this->queue_notes = Setting::get('notes', '', 'queue');
        $this->queue_cron_supervisor_path = Setting::get('cron_supervisor_path', '', 'queue');
    }
}
