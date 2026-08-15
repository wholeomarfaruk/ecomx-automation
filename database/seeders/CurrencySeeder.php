<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar',          'symbol' => '$',    'decimal_places' => 2],
            ['code' => 'EUR', 'name' => 'Euro',                'symbol' => '€',    'decimal_places' => 2],
            ['code' => 'GBP', 'name' => 'British Pound',       'symbol' => '£',    'decimal_places' => 2],
            ['code' => 'JPY', 'name' => 'Japanese Yen',        'symbol' => '¥',    'decimal_places' => 0],
            ['code' => 'CAD', 'name' => 'Canadian Dollar',     'symbol' => 'CA$',  'decimal_places' => 2],
            ['code' => 'AUD', 'name' => 'Australian Dollar',   'symbol' => 'A$',   'decimal_places' => 2],
            ['code' => 'CHF', 'name' => 'Swiss Franc',         'symbol' => 'Fr',   'decimal_places' => 2],
            ['code' => 'CNY', 'name' => 'Chinese Yuan',        'symbol' => '¥',    'decimal_places' => 2],
            ['code' => 'INR', 'name' => 'Indian Rupee',        'symbol' => '₹',    'decimal_places' => 2],
            ['code' => 'BRL', 'name' => 'Brazilian Real',      'symbol' => 'R$',   'decimal_places' => 2],
            ['code' => 'MXN', 'name' => 'Mexican Peso',        'symbol' => 'MX$',  'decimal_places' => 2],
            ['code' => 'SGD', 'name' => 'Singapore Dollar',    'symbol' => 'S$',   'decimal_places' => 2],
            ['code' => 'AED', 'name' => 'UAE Dirham',          'symbol' => 'د.إ',  'decimal_places' => 2],
            ['code' => 'SAR', 'name' => 'Saudi Riyal',         'symbol' => '﷼',    'decimal_places' => 2],
            ['code' => 'QAR', 'name' => 'Qatari Riyal',        'symbol' => '﷼',    'decimal_places' => 2],
            ['code' => 'KWD', 'name' => 'Kuwaiti Dinar',       'symbol' => 'KD',   'decimal_places' => 3],
            ['code' => 'MYR', 'name' => 'Malaysian Ringgit',   'symbol' => 'RM',   'decimal_places' => 2],
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah',   'symbol' => 'Rp',   'decimal_places' => 0],
            ['code' => 'THB', 'name' => 'Thai Baht',           'symbol' => '฿',    'decimal_places' => 2],
            ['code' => 'PKR', 'name' => 'Pakistani Rupee',     'symbol' => '₨',    'decimal_places' => 2],
            ['code' => 'BDT', 'name' => 'Bangladeshi Taka',    'symbol' => '৳',    'decimal_places' => 2],
            ['code' => 'NGN', 'name' => 'Nigerian Naira',      'symbol' => '₦',    'decimal_places' => 2],
            ['code' => 'ZAR', 'name' => 'South African Rand',  'symbol' => 'R',    'decimal_places' => 2],
            ['code' => 'KES', 'name' => 'Kenyan Shilling',     'symbol' => 'KSh',  'decimal_places' => 2],
            ['code' => 'GHS', 'name' => 'Ghanaian Cedi',       'symbol' => '₵',    'decimal_places' => 2],
            ['code' => 'EGP', 'name' => 'Egyptian Pound',      'symbol' => 'E£',   'decimal_places' => 2],
            ['code' => 'TRY', 'name' => 'Turkish Lira',        'symbol' => '₺',    'decimal_places' => 2],
            ['code' => 'RUB', 'name' => 'Russian Ruble',       'symbol' => '₽',    'decimal_places' => 2],
            ['code' => 'KRW', 'name' => 'South Korean Won',    'symbol' => '₩',    'decimal_places' => 0],
            ['code' => 'NOK', 'name' => 'Norwegian Krone',     'symbol' => 'kr',   'decimal_places' => 2],
            ['code' => 'SEK', 'name' => 'Swedish Krona',       'symbol' => 'kr',   'decimal_places' => 2],
            ['code' => 'DKK', 'name' => 'Danish Krone',        'symbol' => 'kr',   'decimal_places' => 2],
            ['code' => 'PLN', 'name' => 'Polish Zloty',        'symbol' => 'zł',   'decimal_places' => 2],
            ['code' => 'NZD', 'name' => 'New Zealand Dollar',  'symbol' => 'NZ$',  'decimal_places' => 2],
            ['code' => 'HKD', 'name' => 'Hong Kong Dollar',    'symbol' => 'HK$',  'decimal_places' => 2],
            ['code' => 'PHP', 'name' => 'Philippine Peso',     'symbol' => '₱',    'decimal_places' => 2],
            ['code' => 'LKR', 'name' => 'Sri Lankan Rupee',    'symbol' => 'Rs',   'decimal_places' => 2],
            ['code' => 'VND', 'name' => 'Vietnamese Dong',     'symbol' => '₫',    'decimal_places' => 0],
        ];

        foreach ($currencies as $currency) {
            DB::table('currencies')->updateOrInsert(['code' => $currency['code']], $currency);
        }
    }
}
