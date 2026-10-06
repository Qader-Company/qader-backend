<?php

namespace Database\Seeders;

use App\Modules\V1\ProfessionalFields\Domain\Models\ProfessionalField;
use Illuminate\Database\Seeder;

class ProfessionalFieldSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            [
                'en' => 'Software Development',
                'ar' => 'تطوير البرمجيات',
                'aliases' => ['Software Development'],
            ],
            [
                'en' => 'Marketing',
                'ar' => 'التسويق',
                'aliases' => ['Marketing'],
            ],
            [
                'en' => 'Sales',
                'ar' => 'المبيعات',
                'aliases' => ['Sales'],
            ],
            [
                'en' => 'Customer Service & Call Center',
                'ar' => 'خدمة العملاء ومراكز الاتصال',
                'aliases' => [
                    'Customer Service',
                    'Customer Service & Call Center',
                ],
            ],
        ];

        foreach ($records as $record) {
            $professionalField = ProfessionalField::query()
                ->whereHas('translations', function ($query) use ($record): void {
                    $query
                        ->where('locale', 'en')
                        ->whereIn('name', $record['aliases']);
                })
                ->firstOrNew();

            $professionalField->fill([
                'is_active' => true,
                'en' => ['name' => $record['en']],
                'ar' => ['name' => $record['ar']],
            ])->save();
        }
    }
}
