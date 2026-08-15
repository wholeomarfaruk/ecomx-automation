<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['group' => 'general', 'key' => 'site_name',      'value' => 'Laravel Starter Kit', 'type' => 'text',    'label' => 'Site Name'],
            ['group' => 'general', 'key' => 'site_short_name','value' => '',                    'type' => 'text',    'label' => 'Short Name'],
            ['group' => 'general', 'key' => 'site_tagline',   'value' => '',                    'type' => 'text',    'label' => 'Site Tagline'],
            ['group' => 'general', 'key' => 'site_logo',        'value' => null, 'type' => 'file', 'label' => 'Logo (Normal)'],
            ['group' => 'general', 'key' => 'site_logo_black', 'value' => null, 'type' => 'file', 'label' => 'Logo (Black)'],
            ['group' => 'general', 'key' => 'site_logo_white', 'value' => null, 'type' => 'file', 'label' => 'Logo (White)'],
            ['group' => 'general', 'key' => 'site_logo_symbol','value' => null, 'type' => 'file', 'label' => 'Logo (Symbol / Icon)'],
            ['group' => 'general', 'key' => 'site_favicon',    'value' => null, 'type' => 'file', 'label' => 'Favicon'],
            // Kept for backward compatibility with pre-Localization-module installs; no longer read or written.
            ['group' => 'general', 'key' => 'language',       'value' => 'en',                  'type' => 'text',    'label' => 'Default Language (legacy)'],

            ['group' => 'company', 'key' => 'company_name',          'value' => '', 'type' => 'text', 'label' => 'Company Name'],
            ['group' => 'company', 'key' => 'company_legal_name',    'value' => '', 'type' => 'text', 'label' => 'Legal Name'],
            ['group' => 'company', 'key' => 'company_logo',          'value' => null, 'type' => 'file', 'label' => 'Company Logo'],
            ['group' => 'company', 'key' => 'company_favicon',       'value' => null, 'type' => 'file', 'label' => 'Company Favicon'],
            ['group' => 'company', 'key' => 'company_email',         'value' => '', 'type' => 'text', 'label' => 'Email'],
            ['group' => 'company', 'key' => 'company_phone',         'value' => '', 'type' => 'text', 'label' => 'Phone'],
            ['group' => 'company', 'key' => 'company_mobile',        'value' => '', 'type' => 'text', 'label' => 'Mobile'],
            ['group' => 'company', 'key' => 'company_website',       'value' => '', 'type' => 'text', 'label' => 'Website'],
            ['group' => 'company', 'key' => 'company_address',       'value' => '', 'type' => 'text', 'label' => 'Address'],
            ['group' => 'company', 'key' => 'company_city',          'value' => '', 'type' => 'text', 'label' => 'City'],
            ['group' => 'company', 'key' => 'company_state',         'value' => '', 'type' => 'text', 'label' => 'State'],
            ['group' => 'company', 'key' => 'company_country_id',    'value' => null, 'type' => 'text', 'label' => 'Country'],
            ['group' => 'company', 'key' => 'company_postal_code',   'value' => '', 'type' => 'text', 'label' => 'Postal Code'],
            ['group' => 'company', 'key' => 'company_tax_number',    'value' => '', 'type' => 'text', 'label' => 'Tax / VAT Number'],
            ['group' => 'company', 'key' => 'company_trade_license', 'value' => '', 'type' => 'text', 'label' => 'Trade License Number'],
            ['group' => 'company', 'key' => 'company_map_location',  'value' => '', 'type' => 'text', 'label' => 'Map Location'],

            ['group' => 'localization', 'key' => 'timezone',        'value' => 'Asia/Dhaka', 'type' => 'text', 'label' => 'Timezone'],
            ['group' => 'localization', 'key' => 'date_format',     'value' => 'd-m-Y',      'type' => 'text', 'label' => 'Date Format'],
            ['group' => 'localization', 'key' => 'time_format',     'value' => 'H:i',        'type' => 'text', 'label' => 'Time Format'],
            ['group' => 'localization', 'key' => 'currency',        'value' => 'BDT',        'type' => 'text', 'label' => 'Default Currency'],
            ['group' => 'localization', 'key' => 'currency_symbol', 'value' => '৳',          'type' => 'text', 'label' => 'Currency Symbol'],
            ['group' => 'localization', 'key' => 'number_format',   'value' => '1,234.56',   'type' => 'text', 'label' => 'Number Format'],

            ['group' => 'mail',    'key' => 'from_name',      'value' => 'Laravel Starter Kit', 'type' => 'text',    'label' => 'Mail From Name'],
            ['group' => 'mail',    'key' => 'from_address',   'value' => 'no-reply@example.com','type' => 'text',    'label' => 'Mail From Address'],

            ['group' => 'social',  'key' => 'facebook',       'value' => '',                    'type' => 'text',    'label' => 'Facebook URL'],
            ['group' => 'social',  'key' => 'facebook_group', 'value' => '',                    'type' => 'text',    'label' => 'Facebook Group URL'],
            ['group' => 'social',  'key' => 'twitter',        'value' => '',                    'type' => 'text',    'label' => 'Twitter / X URL'],
            ['group' => 'social',  'key' => 'instagram',      'value' => '',                    'type' => 'text',    'label' => 'Instagram URL'],
            ['group' => 'social',  'key' => 'linkedin',       'value' => '',                    'type' => 'text',    'label' => 'LinkedIn URL'],
            ['group' => 'social',  'key' => 'tiktok',         'value' => '',                    'type' => 'text',    'label' => 'TikTok URL'],
        ];

        foreach ($defaults as $setting) {
            Setting::updateOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                $setting
            );
        }
    }
}
