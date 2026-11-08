<?php

namespace Database\Seeders;

use App\Modules\V1\Industries\Domain\Models\Industry;
use Illuminate\Database\Seeder;

class IndustrySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['technology', 'Technology & Software', 'التكنولوجيا والبرمجيات'], ['financial-services', 'Financial Services', 'الخدمات المالية'], ['healthcare', 'Healthcare', 'الرعاية الصحية'], ['education', 'Education', 'التعليم'], ['retail-ecommerce', 'Retail & E-commerce', 'التجزئة والتجارة الإلكترونية'], ['manufacturing', 'Manufacturing', 'التصنيع'], ['real-estate', 'Real Estate', 'العقارات'], ['marketing-media', 'Marketing & Media', 'التسويق والإعلام'], ['hospitality-tourism', 'Hospitality & Tourism', 'الضيافة والسياحة'], ['nonprofit', 'Nonprofit', 'القطاع غير الربحي'], ['other', 'Other', 'أخرى'],
        ];
        foreach ($items as [$slug,$en,$ar]) {
            $industry = Industry::query()->updateOrCreate(['slug' => $slug], ['is_active' => true]);
            $industry->translations()->updateOrCreate(['locale' => 'en'], ['name' => $en]);
            $industry->translations()->updateOrCreate(['locale' => 'ar'], ['name' => $ar]);
        }
    }
}
