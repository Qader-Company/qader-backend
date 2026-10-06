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
                    'Customer Service & Call Center',
                ],
            ],
            [
                'name' => 'Customer Support',
                'professional_fields' => [
                    'Customer Service & Call Center',
                ],
            ],
            [
                'name' => 'Call Handling',
                'professional_fields' => [
                    'Customer Service & Call Center',
                ],
            ],
            [
                'name' => 'Complaint Resolution',
                'professional_fields' => [
                    'Customer Service & Call Center',
                ],
            ],
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
