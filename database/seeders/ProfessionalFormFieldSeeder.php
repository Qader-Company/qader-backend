<?php

namespace Database\Seeders;

use App\Modules\V1\ProfessionalFields\Domain\ValueObjects\ProfessionalFormFieldEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfessionalFormFieldSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $records = collect(ProfessionalFormFieldEnum::cases())
            ->map(fn (ProfessionalFormFieldEnum $field): array => [
                'key' => $field->value,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        DB::table('professional_form_fields')->upsert(
            $records,
            ['key'],
            ['updated_at'],
        );

        DB::table('professional_form_fields')
            ->whereNotIn('key', ProfessionalFormFieldEnum::values())
            ->delete();

        $fieldIds = DB::table('professional_form_fields')->pluck('id', 'key');
        $professionalFieldIds = DB::table('professional_fields')
            ->join('professional_field_translations', 'professional_field_translations.professional_field_id', '=', 'professional_fields.id')
            ->where('professional_field_translations.locale', 'en')
            ->pluck('professional_fields.id', 'professional_field_translations.name');
        $specializedFields = [
            'Software Development' => ['github_url', 'portfolio_url', 'projects'],
            'Design' => ['behance_url', 'portfolio_url', 'projects'],
            'Marketing' => ['portfolio_url', 'projects'],
        ];
        $universalKeys = collect(ProfessionalFormFieldEnum::values())
            ->reject(fn (string $key): bool => in_array($key, [
                'github_url',
                'behance_url',
                'portfolio_url',
                'projects',
            ], true));
        $selectionRows = $professionalFieldIds->flatMap(
            function (int $professionalFieldId, string $name) use ($fieldIds, $now, $specializedFields, $universalKeys) {
                return $universalKeys
                    ->merge($specializedFields[$name] ?? [])
                    ->unique()
                    ->map(fn (string $key) => [
                        'professional_field_id' => $professionalFieldId,
                        'professional_form_field_id' => $fieldIds[$key],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
            },
        )->values()->all();

        DB::table('professional_field_form_field')
            ->whereIn('professional_field_id', $professionalFieldIds->values())
            ->delete();

        DB::table('professional_field_form_field')->insert($selectionRows);
    }
}
