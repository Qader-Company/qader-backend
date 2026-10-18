<?php

namespace Database\Seeders;

use App\Modules\V1\Skills\Domain\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['name' => 'PHP', 'professional_fields' => ['Software Development']],
            ['name' => 'Laravel', 'professional_fields' => ['Software Development']],
            ['name' => 'JavaScript', 'professional_fields' => ['Software Development']],
            ['name' => 'React', 'professional_fields' => ['Software Development']],
            ['name' => 'SQL', 'professional_fields' => ['Software Development']],
            ['name' => 'Search Engine Optimization', 'professional_fields' => ['Marketing']],
            ['name' => 'Content Marketing', 'professional_fields' => ['Marketing']],
            ['name' => 'Google Ads', 'professional_fields' => ['Marketing']],
            ['name' => 'Social Media Marketing', 'professional_fields' => ['Marketing']],
            ['name' => 'Lead Generation', 'professional_fields' => ['Marketing', 'Sales']],
            ['name' => 'B2B Sales', 'professional_fields' => ['Sales']],
            [
                'name' => 'Negotiation',
                'professional_fields' => [
                    'Sales',
                    'Customer Service',
                ],
            ],
            [
                'name' => 'Customer Support',
                'professional_fields' => [
                    'Customer Service',
                ],
            ],
            [
                'name' => 'Call Handling',
                'professional_fields' => [
                    'Customer Service',
                ],
            ],
            [
                'name' => 'Complaint Resolution',
                'professional_fields' => [
                    'Customer Service',
                ],
            ],
            ['name' => 'User Research', 'professional_fields' => ['Design']],
            ['name' => 'Wireframing', 'professional_fields' => ['Design']],
            ['name' => 'Visual Design', 'professional_fields' => ['Design']],
            ['name' => 'Recruitment', 'professional_fields' => ['Human Resources']],
            ['name' => 'Employee Relations', 'professional_fields' => ['Human Resources']],
            ['name' => 'Performance Management', 'professional_fields' => ['Human Resources']],
            ['name' => 'Financial Reporting', 'professional_fields' => ['Finance & Accounting']],
            ['name' => 'Budgeting', 'professional_fields' => ['Finance & Accounting']],
            ['name' => 'Bookkeeping', 'professional_fields' => ['Finance & Accounting']],
            ['name' => 'Process Improvement', 'professional_fields' => ['Operations']],
            ['name' => 'Inventory Management', 'professional_fields' => ['Operations']],
            ['name' => 'Logistics Planning', 'professional_fields' => ['Operations']],
        ];

        $professionalFieldIds = DB::table('professional_fields')
            ->join(
                'professional_field_translations',
                'professional_field_translations.professional_field_id',
                '=',
                'professional_fields.id',
            )
            ->where('professional_field_translations.locale', 'en')
            ->pluck(
                'professional_fields.id',
                'professional_field_translations.name',
            );

        foreach ($records as $record) {
            $skill = Skill::query()->updateOrCreate(
                ['name' => $record['name']],
                ['is_active' => true],
            );
            $ids = collect($record['professional_fields'])
                ->map(
                    fn (string $name) => $professionalFieldIds->get($name),
                )
                ->filter()
                ->values()
                ->all();

            $skill->professionalFields()->sync($ids);
        }
    }
}
