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
                'description_en' => 'Roles related to coding, engineering, and software architecture.',
                'description_ar' => 'وظائف مرتبطة بالبرمجة والهندسة ومعمارية البرمجيات.',
                'aliases' => ['Software Development'],
            ],
            [
                'en' => 'Marketing',
                'ar' => 'التسويق',
                'description_en' => 'Digital marketing, brand management, and growth strategies.',
                'description_ar' => 'التسويق الرقمي وإدارة العلامات التجارية واستراتيجيات النمو.',
                'aliases' => ['Marketing'],
            ],
            [
                'en' => 'Design',
                'ar' => 'التصميم',
                'description_en' => 'Graphic, UI/UX, and product design roles.',
                'description_ar' => 'وظائف التصميم الجرافيكي وتجربة وواجهة المستخدم وتصميم المنتجات.',
                'aliases' => ['Design'],
            ],
            [
                'en' => 'Sales',
                'ar' => 'المبيعات',
                'description_en' => 'Business development, account management, and sales operations.',
                'description_ar' => 'تطوير الأعمال وإدارة الحسابات وعمليات المبيعات.',
                'aliases' => ['Sales'],
            ],
            [
                'en' => 'Customer Service',
                'ar' => 'خدمة العملاء',
                'description_en' => 'Support, success, and customer experience roles.',
                'description_ar' => 'وظائف الدعم ونجاح العملاء وتجربة العملاء.',
                'aliases' => [
                    'Customer Service',
                    'Customer Service & Call Center',
                ],
            ],
            [
                'en' => 'Human Resources',
                'ar' => 'الموارد البشرية',
                'description_en' => 'Talent acquisition, people operations, and HR management.',
                'description_ar' => 'استقطاب المواهب وعمليات الأفراد وإدارة الموارد البشرية.',
                'aliases' => ['Human Resources'],
            ],
            [
                'en' => 'Finance & Accounting',
                'ar' => 'المالية والمحاسبة',
                'description_en' => 'Financial planning, accounting, and treasury roles.',
                'description_ar' => 'وظائف التخطيط المالي والمحاسبة والخزانة.',
                'aliases' => ['Finance & Accounting'],
            ],
            [
                'en' => 'Operations',
                'ar' => 'العمليات',
                'description_en' => 'Supply chain, logistics, and business operations management.',
                'description_ar' => 'إدارة سلاسل الإمداد والخدمات اللوجستية وعمليات الأعمال.',
                'aliases' => ['Operations'],
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
                'en' => [
                    'name' => $record['en'],
                    'description' => $record['description_en'],
                ],
                'ar' => [
                    'name' => $record['ar'],
                    'description' => $record['description_ar'],
                ],
            ])->save();
        }
    }
}
