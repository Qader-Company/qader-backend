<?php

namespace Database\Seeders;

use App\Modules\V1\JobTitles\Domain\Models\JobTitle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JobTitleSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            [
                'name' => 'Software Engineer',
                'professional_fields' => ['Software Development'],
            ],
            [
                'name' => 'Frontend Developer',
                'professional_fields' => ['Software Development'],
            ],
            [
                'name' => 'Backend Developer',
                'professional_fields' => ['Software Development'],
            ],
            [
                'name' => 'QA Engineer',
                'professional_fields' => ['Software Development'],
            ],
            [
                'name' => 'Digital Marketing Specialist',
                'professional_fields' => ['Marketing'],
            ],
            [
                'name' => 'Content Creator',
                'professional_fields' => ['Marketing'],
            ],
            [
                'name' => 'Social Media Specialist',
                'professional_fields' => ['Marketing'],
            ],
            [
                'name' => 'Sales Representative',
                'professional_fields' => ['Sales'],
            ],
            [
                'name' => 'Business Development Representative',
                'professional_fields' => ['Sales'],
            ],
            [
                'name' => 'Account Executive',
                'professional_fields' => ['Sales'],
            ],
            [
                'name' => 'Customer Service Representative',
                'professional_fields' => [
                    'Customer Service & Call Center',
                ],
            ],
            [
                'name' => 'Call Center Agent',
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
            $jobTitle = JobTitle::query()->updateOrCreate(
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

            $jobTitle->professionalFields()->sync($ids);
        }
    }
}
