<?php

namespace Database\Seeders;

use App\Modules\V1\Cities\Domain\Models\City;
use App\Modules\V1\Countries\Domain\Models\Country;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $countriesByCode = Country::query()
            ->whereIn('code', ['EG', 'SA', 'AE', 'US', 'GB'])
            ->pluck('id', 'code');
        $citiesByCountry = [
            'EG' => [
                ['en' => 'Cairo', 'ar' => 'القاهرة'],
                ['en' => 'Giza', 'ar' => 'الجيزة'],
                ['en' => 'Alexandria', 'ar' => 'الإسكندرية'],
            ],
            'SA' => [
                ['en' => 'Riyadh', 'ar' => 'الرياض'],
                ['en' => 'Jeddah', 'ar' => 'جدة'],
                ['en' => 'Dammam', 'ar' => 'الدمام'],
            ],
            'AE' => [
                ['en' => 'Dubai', 'ar' => 'دبي'],
                ['en' => 'Abu Dhabi', 'ar' => 'أبو ظبي'],
                ['en' => 'Sharjah', 'ar' => 'الشارقة'],
            ],
            'US' => [
                ['en' => 'New York', 'ar' => 'نيويورك'],
                ['en' => 'Los Angeles', 'ar' => 'لوس أنجلوس'],
                ['en' => 'Chicago', 'ar' => 'شيكاغو'],
            ],
            'GB' => [
                ['en' => 'London', 'ar' => 'لندن'],
                ['en' => 'Manchester', 'ar' => 'مانشستر'],
                ['en' => 'Birmingham', 'ar' => 'برمنغهام'],
            ],
        ];

        foreach ($citiesByCountry as $countryCode => $cities) {
            $countryId = $countriesByCode->get($countryCode);

            if ($countryId === null) {
                continue;
            }

            foreach ($cities as $data) {
                $city = City::query()
                    ->where('country_id', $countryId)
                    ->whereHas('translations', function ($query) use ($data): void {
                        $query
                            ->where('locale', 'en')
                            ->where('name', $data['en']);
                    })
                    ->firstOrNew(['country_id' => $countryId]);

                $city->fill([
                    'is_active' => true,
                    'en' => ['name' => $data['en']],
                    'ar' => ['name' => $data['ar']],
                ]);
                $city->save();
            }
        }
    }
}
