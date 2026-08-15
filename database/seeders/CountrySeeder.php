<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['code' => 'AF', 'name' => 'Afghanistan',          'local_name' => 'افغانستان',        'phone_code' => '+93',  'currency_code' => 'AFN', 'emoji_flag' => '🇦🇫', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'AL', 'name' => 'Albania',              'local_name' => 'Shqipëria',        'phone_code' => '+355', 'currency_code' => 'ALL', 'emoji_flag' => '🇦🇱', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'DZ', 'name' => 'Algeria',              'local_name' => 'الجزائر',           'phone_code' => '+213', 'currency_code' => 'DZD', 'emoji_flag' => '🇩🇿', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'AR', 'name' => 'Argentina',            'local_name' => 'Argentina',        'phone_code' => '+54',  'currency_code' => 'ARS', 'emoji_flag' => '🇦🇷', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'AU', 'name' => 'Australia',            'local_name' => 'Australia',        'phone_code' => '+61',  'currency_code' => 'AUD', 'emoji_flag' => '🇦🇺', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'AT', 'name' => 'Austria',              'local_name' => 'Österreich',       'phone_code' => '+43',  'currency_code' => 'EUR', 'emoji_flag' => '🇦🇹', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'BD', 'name' => 'Bangladesh',           'local_name' => 'বাংলাদেশ',          'phone_code' => '+880', 'currency_code' => 'BDT', 'emoji_flag' => '🇧🇩', 'is_register_allowed' => true,  'sort_order' => 1],
            ['code' => 'BE', 'name' => 'Belgium',              'local_name' => 'België',           'phone_code' => '+32',  'currency_code' => 'EUR', 'emoji_flag' => '🇧🇪', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'BR', 'name' => 'Brazil',               'local_name' => 'Brasil',           'phone_code' => '+55',  'currency_code' => 'BRL', 'emoji_flag' => '🇧🇷', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'CA', 'name' => 'Canada',               'local_name' => 'Canada',           'phone_code' => '+1',   'currency_code' => 'CAD', 'emoji_flag' => '🇨🇦', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'CN', 'name' => 'China',                'local_name' => '中国',              'phone_code' => '+86',  'currency_code' => 'CNY', 'emoji_flag' => '🇨🇳', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'DK', 'name' => 'Denmark',              'local_name' => 'Danmark',          'phone_code' => '+45',  'currency_code' => 'DKK', 'emoji_flag' => '🇩🇰', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'EG', 'name' => 'Egypt',                'local_name' => 'مصر',              'phone_code' => '+20',  'currency_code' => 'EGP', 'emoji_flag' => '🇪🇬', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'FI', 'name' => 'Finland',              'local_name' => 'Suomi',            'phone_code' => '+358', 'currency_code' => 'EUR', 'emoji_flag' => '🇫🇮', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'FR', 'name' => 'France',               'local_name' => 'France',           'phone_code' => '+33',  'currency_code' => 'EUR', 'emoji_flag' => '🇫🇷', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'DE', 'name' => 'Germany',              'local_name' => 'Deutschland',      'phone_code' => '+49',  'currency_code' => 'EUR', 'emoji_flag' => '🇩🇪', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'GH', 'name' => 'Ghana',                'local_name' => 'Ghana',            'phone_code' => '+233', 'currency_code' => 'GHS', 'emoji_flag' => '🇬🇭', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'GR', 'name' => 'Greece',               'local_name' => 'Ελλάδα',            'phone_code' => '+30',  'currency_code' => 'EUR', 'emoji_flag' => '🇬🇷', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'IN', 'name' => 'India',                'local_name' => 'भारत',              'phone_code' => '+91',  'currency_code' => 'INR', 'emoji_flag' => '🇮🇳', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'ID', 'name' => 'Indonesia',            'local_name' => 'Indonesia',        'phone_code' => '+62',  'currency_code' => 'IDR', 'emoji_flag' => '🇮🇩', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'IR', 'name' => 'Iran',                 'local_name' => 'ایران',             'phone_code' => '+98',  'currency_code' => 'IRR', 'emoji_flag' => '🇮🇷', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'IQ', 'name' => 'Iraq',                 'local_name' => 'العراق',            'phone_code' => '+964', 'currency_code' => 'IQD', 'emoji_flag' => '🇮🇶', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'IE', 'name' => 'Ireland',              'local_name' => 'Éire',             'phone_code' => '+353', 'currency_code' => 'EUR', 'emoji_flag' => '🇮🇪', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'IL', 'name' => 'Israel',               'local_name' => 'ישראל',             'phone_code' => '+972', 'currency_code' => 'ILS', 'emoji_flag' => '🇮🇱', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'IT', 'name' => 'Italy',                'local_name' => 'Italia',           'phone_code' => '+39',  'currency_code' => 'EUR', 'emoji_flag' => '🇮🇹', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'JP', 'name' => 'Japan',                'local_name' => '日本',              'phone_code' => '+81',  'currency_code' => 'JPY', 'emoji_flag' => '🇯🇵', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'JO', 'name' => 'Jordan',               'local_name' => 'الأردن',            'phone_code' => '+962', 'currency_code' => 'JOD', 'emoji_flag' => '🇯🇴', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'KE', 'name' => 'Kenya',                'local_name' => 'Kenya',            'phone_code' => '+254', 'currency_code' => 'KES', 'emoji_flag' => '🇰🇪', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'KW', 'name' => 'Kuwait',               'local_name' => 'الكويت',            'phone_code' => '+965', 'currency_code' => 'KWD', 'emoji_flag' => '🇰🇼', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'MY', 'name' => 'Malaysia',             'local_name' => 'Malaysia',         'phone_code' => '+60',  'currency_code' => 'MYR', 'emoji_flag' => '🇲🇾', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'MX', 'name' => 'Mexico',               'local_name' => 'México',           'phone_code' => '+52',  'currency_code' => 'MXN', 'emoji_flag' => '🇲🇽', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'MA', 'name' => 'Morocco',              'local_name' => 'المغرب',            'phone_code' => '+212', 'currency_code' => 'MAD', 'emoji_flag' => '🇲🇦', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'NL', 'name' => 'Netherlands',          'local_name' => 'Nederland',        'phone_code' => '+31',  'currency_code' => 'EUR', 'emoji_flag' => '🇳🇱', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'NZ', 'name' => 'New Zealand',          'local_name' => 'New Zealand',      'phone_code' => '+64',  'currency_code' => 'NZD', 'emoji_flag' => '🇳🇿', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'NG', 'name' => 'Nigeria',              'local_name' => 'Nigeria',          'phone_code' => '+234', 'currency_code' => 'NGN', 'emoji_flag' => '🇳🇬', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'NO', 'name' => 'Norway',               'local_name' => 'Norge',            'phone_code' => '+47',  'currency_code' => 'NOK', 'emoji_flag' => '🇳🇴', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'PK', 'name' => 'Pakistan',             'local_name' => 'پاکستان',           'phone_code' => '+92',  'currency_code' => 'PKR', 'emoji_flag' => '🇵🇰', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'PH', 'name' => 'Philippines',          'local_name' => 'Pilipinas',        'phone_code' => '+63',  'currency_code' => 'PHP', 'emoji_flag' => '🇵🇭', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'PL', 'name' => 'Poland',               'local_name' => 'Polska',           'phone_code' => '+48',  'currency_code' => 'PLN', 'emoji_flag' => '🇵🇱', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'PT', 'name' => 'Portugal',             'local_name' => 'Portugal',         'phone_code' => '+351', 'currency_code' => 'EUR', 'emoji_flag' => '🇵🇹', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'QA', 'name' => 'Qatar',                'local_name' => 'قطر',              'phone_code' => '+974', 'currency_code' => 'QAR', 'emoji_flag' => '🇶🇦', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'RO', 'name' => 'Romania',              'local_name' => 'România',          'phone_code' => '+40',  'currency_code' => 'RON', 'emoji_flag' => '🇷🇴', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'RU', 'name' => 'Russia',               'local_name' => 'Россия',            'phone_code' => '+7',   'currency_code' => 'RUB', 'emoji_flag' => '🇷🇺', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'SA', 'name' => 'Saudi Arabia',         'local_name' => 'السعودية',          'phone_code' => '+966', 'currency_code' => 'SAR', 'emoji_flag' => '🇸🇦', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'SG', 'name' => 'Singapore',            'local_name' => 'Singapore',        'phone_code' => '+65',  'currency_code' => 'SGD', 'emoji_flag' => '🇸🇬', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'ZA', 'name' => 'South Africa',         'local_name' => 'South Africa',     'phone_code' => '+27',  'currency_code' => 'ZAR', 'emoji_flag' => '🇿🇦', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'KR', 'name' => 'South Korea',          'local_name' => '대한민국',            'phone_code' => '+82',  'currency_code' => 'KRW', 'emoji_flag' => '🇰🇷', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'ES', 'name' => 'Spain',                'local_name' => 'España',           'phone_code' => '+34',  'currency_code' => 'EUR', 'emoji_flag' => '🇪🇸', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'LK', 'name' => 'Sri Lanka',            'local_name' => 'ශ්‍රී ලංකාව',          'phone_code' => '+94',  'currency_code' => 'LKR', 'emoji_flag' => '🇱🇰', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'SE', 'name' => 'Sweden',               'local_name' => 'Sverige',          'phone_code' => '+46',  'currency_code' => 'SEK', 'emoji_flag' => '🇸🇪', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'CH', 'name' => 'Switzerland',          'local_name' => 'Schweiz',          'phone_code' => '+41',  'currency_code' => 'CHF', 'emoji_flag' => '🇨🇭', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'TH', 'name' => 'Thailand',             'local_name' => 'ประเทศไทย',          'phone_code' => '+66',  'currency_code' => 'THB', 'emoji_flag' => '🇹🇭', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'TR', 'name' => 'Turkey',               'local_name' => 'Türkiye',          'phone_code' => '+90',  'currency_code' => 'TRY', 'emoji_flag' => '🇹🇷', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'UA', 'name' => 'Ukraine',              'local_name' => 'Україна',           'phone_code' => '+380', 'currency_code' => 'UAH', 'emoji_flag' => '🇺🇦', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'AE', 'name' => 'United Arab Emirates', 'local_name' => 'الإمارات العربية المتحدة', 'phone_code' => '+971', 'currency_code' => 'AED', 'emoji_flag' => '🇦🇪', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'GB', 'name' => 'United Kingdom',       'local_name' => 'United Kingdom',   'phone_code' => '+44',  'currency_code' => 'GBP', 'emoji_flag' => '🇬🇧', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'US', 'name' => 'United States',        'local_name' => 'United States',    'phone_code' => '+1',   'currency_code' => 'USD', 'emoji_flag' => '🇺🇸', 'is_register_allowed' => false, 'sort_order' => 0],
            ['code' => 'VN', 'name' => 'Vietnam',              'local_name' => 'Việt Nam',         'phone_code' => '+84',  'currency_code' => 'VND', 'emoji_flag' => '🇻🇳', 'is_register_allowed' => false, 'sort_order' => 0],
        ];

        foreach ($countries as $country) {
            DB::table('countries')->updateOrInsert(
                ['code' => $country['code']],
                array_merge($country, ['updated_at' => now()])
            );
        }
    }
}
