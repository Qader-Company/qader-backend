<?php

namespace Database\Seeders;

use App\Modules\V1\Countries\Domain\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['code' => 'EG', 'en' => 'Egypt', 'ar' => 'مصر'],
            ['code' => 'SA', 'en' => 'Saudi Arabia', 'ar' => 'المملكة العربية السعودية'],
            ['code' => 'AE', 'en' => 'United Arab Emirates', 'ar' => 'الإمارات العربية المتحدة'],
            ['code' => 'US', 'en' => 'United States', 'ar' => 'الولايات المتحدة'],
            ['code' => 'GB', 'en' => 'United Kingdom', 'ar' => 'المملكة المتحدة'],
        ];

        foreach ($countries as $data) {
            $country = Country::query()->firstOrNew(['code' => $data['code']]);
            $country->fill([
                'is_active' => true,
                'en' => ['name' => $data['en']],
                'ar' => ['name' => $data['ar']],
            ]);
            $country->save();
        }
    }
}
