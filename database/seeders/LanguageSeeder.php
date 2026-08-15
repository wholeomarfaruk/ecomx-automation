<?php

namespace Database\Seeders;

use App\Models\Language;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English (US)', 'native_name' => 'English', 'direction' => 'ltr', 'flag_emoji' => '🇺🇸', 'is_active' => true, 'is_default' => true,  'sort_order' => 1],
            ['code' => 'bn', 'name' => 'Bengali',  'native_name' => 'বাংলা',    'direction' => 'ltr', 'flag_emoji' => '🇧🇩', 'is_active' => true, 'is_default' => false, 'sort_order' => 2],
            ['code' => 'ar', 'name' => 'Arabic',   'native_name' => 'العربية',  'direction' => 'rtl', 'flag_emoji' => '🇸🇦', 'is_active' => true, 'is_default' => false, 'sort_order' => 3],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(['code' => $language['code']], $language);
        }

        // Carry over whatever the old free-text `general.language` setting pointed to,
        // falling back to 'en' if it doesn't match a seeded language code.
        $existing = Setting::get('language', 'en');

        if ($existing !== 'en' && Language::where('code', $existing)->exists()) {
            Language::where('is_default', true)->update(['is_default' => false]);
            Language::where('code', $existing)->update(['is_default' => true]);
        }

        Cache::forget('languages:active');
    }
}
