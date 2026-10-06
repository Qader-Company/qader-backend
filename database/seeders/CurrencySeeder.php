<?php

namespace Database\Seeders;

use App\Modules\V1\Currencies\Domain\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'EGP', 'en' => 'Egyptian Pound', 'ar' => 'الجنيه المصري', 'symbol' => 'E£'],
            ['code' => 'SAR', 'en' => 'Saudi Riyal', 'ar' => 'الريال السعودي', 'symbol' => '﷼'],
            ['code' => 'AED', 'en' => 'UAE Dirham', 'ar' => 'الدرهم الإماراتي', 'symbol' => 'د.إ'],
            ['code' => 'USD', 'en' => 'US Dollar', 'ar' => 'الدولار الأمريكي', 'symbol' => '$'],
            ['code' => 'GBP', 'en' => 'British Pound', 'ar' => 'الجنيه الإسترليني', 'symbol' => '£'],
        ];

        foreach ($currencies as $data) {
            $currency = Currency::query()->firstOrNew(['code' => $data['code']]);
            $currency->fill([
                'symbol' => $data['symbol'],
                'is_active' => true,
                'en' => ['name' => $data['en']],
                'ar' => ['name' => $data['ar']],
            ]);
            $currency->save();
        }
    }
}
