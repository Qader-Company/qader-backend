<?php

namespace Database\Seeders;

use App\Modules\V1\Languages\Domain\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'ar', 'en' => 'Arabic', 'ar' => 'العربية'],
            ['code' => 'en', 'en' => 'English', 'ar' => 'الإنجليزية'],
            ['code' => 'fr', 'en' => 'French', 'ar' => 'الفرنسية'],
            ['code' => 'de', 'en' => 'German', 'ar' => 'الألمانية'],
            ['code' => 'es', 'en' => 'Spanish', 'ar' => 'الإسبانية'],
        ];

        foreach ($languages as $data) {
            $language = Language::query()->firstOrNew(['code' => $data['code']]);
            $language->fill([
                'is_active' => true,
                'en' => ['name' => $data['en']],
                'ar' => ['name' => $data['ar']],
            ]);
            $language->save();
        }
    }
}
