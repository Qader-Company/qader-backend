<?php

namespace Database\Seeders;

use App\Modules\V1\Tools\Domain\Models\Tool;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ToolSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['name' => 'GitHub', 'professional_fields' => ['Software Development']],
            ['name' => 'GitLab', 'professional_fields' => ['Software Development']],
            ['name' => 'Jira', 'professional_fields' => ['Software Development']],
            ['name' => 'Postman', 'professional_fields' => ['Software Development']],
            ['name' => 'Google Analytics', 'professional_fields' => ['Marketing']],
            ['name' => 'Meta Ads Manager', 'professional_fields' => ['Marketing']],
            ['name' => 'Mailchimp', 'professional_fields' => ['Marketing']],
            ['name' => 'Canva', 'professional_fields' => ['Marketing']],
            [
                'name' => 'Salesforce',
                'professional_fields' => [
                    'Sales',
                    'Customer Service & Call Center',
                ],
            ],
            [
                'name' => 'HubSpot CRM',
                'professional_fields' => ['Marketing', 'Sales'],
            ],
            ['name' => 'Zoho CRM', 'professional_fields' => ['Sales']],
            [
                'name' => 'Zendesk',
                'professional_fields' => [
                    'Customer Service & Call Center',
                ],
            ],
            [
                'name' => 'Freshdesk',
                'professional_fields' => [
                    'Customer Service & Call Center',
                ],
            ],
            [
                'name' => 'Intercom',
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
            $tool = Tool::query()->updateOrCreate(
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

            $tool->professionalFields()->sync($ids);
        }
    }
}
