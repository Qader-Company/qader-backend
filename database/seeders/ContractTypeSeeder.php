<?php

namespace Database\Seeders;

use App\Modules\V1\ContractTypes\Domain\Models\ContractType;
use App\Modules\V1\ContractTypes\Domain\ValueObjects\ContractFeeTypeEnum;
use Illuminate\Database\Seeder;

class ContractTypeSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedContractType(
            type: 'managed',
            defaults: [
                'name_en' => 'Managed Contract',
                'name_ar' => 'عقد مُدار',
                'description_en' => 'Qader manages the Talent throughout the contract, providing structural HR support and platform tooling.',
                'description_ar' => 'تدير قادر الموهبة طوال مدة العقد، مع توفير دعم منظم للموارد البشرية وأدوات المنصة.',
                'fee_type' => ContractFeeTypeEnum::FIXED_PERCENTAGE->value,
            ],
            benefits: [
                ['name_en' => 'Dedicated Management Portal', 'name_ar' => 'بوابة إدارة مخصصة'],
                ['name_en' => 'Qader Collaborative Workspace', 'name_ar' => 'مساحة عمل تعاونية من قادر'],
                ['name_en' => 'Full HR Support & Payroll', 'name_ar' => 'دعم كامل للموارد البشرية والرواتب'],
                ['name_en' => 'Attendance Tracking & Timesheets', 'name_ar' => 'تتبع الحضور وسجلات ساعات العمل'],
                ['name_en' => 'Vetted Performance Monitoring', 'name_ar' => 'متابعة موثوقة للأداء'],
                ['name_en' => 'Ongoing 24/7 Account Support', 'name_ar' => 'دعم مستمر للحساب على مدار الساعة'],
            ],
            terms: $this->defaultTerms(),
        );

        $this->seedContractType(
            type: 'one_time',
            defaults: [
                'name_en' => 'One-Time Commission',
                'name_ar' => 'عمولة لمرة واحدة',
                'description_en' => 'Qader helps you hire. Once the placement is finalized, you manage the talent directly on your own terms.',
                'description_ar' => 'تساعدك قادر في التوظيف، وبعد إتمام التعيين تدير الموهبة مباشرة وفق شروطك الخاصة.',
                'fee_type' => ContractFeeTypeEnum::PERCENTAGE->value,
            ],
            benefits: [
                ['name_en' => 'Access to Curated Talent Marketplace', 'name_ar' => 'الوصول إلى سوق مواهب مختارة بعناية'],
                ['name_en' => 'Recruiting and Hiring Support', 'name_ar' => 'دعم الاستقطاب والتوظيف'],
                ['name_en' => 'Direct and Fast Matching Process', 'name_ar' => 'عملية ترشيح مباشرة وسريعة'],
            ],
            terms: $this->defaultTerms(),
        );
    }

    private function seedContractType(string $type, array $defaults, array $benefits, array $terms): void
    {
        $contractType = ContractType::query()->firstOrCreate(
            ['type' => $type],
            [
                'is_active' => false,
                ...$defaults,
            ],
        );

        // The migration creates the system types before this seeder runs. Only
        // backfill empty base data so rerunning the seeder never overwrites an
        // administrator's contract configuration.
        $attributes = [];
        foreach (['name_en', 'name_ar', 'description_en', 'description_ar'] as $attribute) {
            if ($contractType->getRawOriginal($attribute) === null) {
                $attributes[$attribute] = $defaults[$attribute];
            }
        }

        $hasConfiguredFee = $contractType->fixed_amount !== null
            || $contractType->fixed_currency_id !== null
            || $contractType->percentage !== null;
        if (! $hasConfiguredFee && $contractType->getRawOriginal('description_en') === null) {
            $attributes['fee_type'] = $defaults['fee_type'];
        }

        if ($attributes !== []) {
            $contractType->update($attributes);
        }

        if (! $contractType->benefits()->exists()) {
            $contractType->benefits()->createMany(array_map(
                fn (array $benefit, int $index): array => [
                    ...$benefit,
                    'display_order' => $index + 1,
                ],
                $benefits,
                array_keys($benefits),
            ));
        }

        if (! $contractType->terms()->exists()) {
            $contractType->terms()->createMany(array_map(
                fn (array $term, int $index): array => [
                    ...$term,
                    'display_order' => $index + 1,
                ],
                $terms,
                array_keys($terms),
            ));
        }
    }

    private function defaultTerms(): array
    {
        return [[
            'content_en' => 'The service provider agrees to deliver the contracted services as outlined in the agreement. Payment terms are net-30 from invoice date. Any adjustments or custom SLA agreements must be approved by client management.',
            'content_ar' => 'يوافق مقدم الخدمة على تقديم الخدمات المتعاقد عليها كما هو موضح في الاتفاقية. شروط الدفع هي صافي 30 يوماً من تاريخ الفاتورة. ويجب اعتماد أي تعديلات أو اتفاقيات مخصصة لمستوى الخدمة من إدارة العميل.',
        ]];
    }
}
