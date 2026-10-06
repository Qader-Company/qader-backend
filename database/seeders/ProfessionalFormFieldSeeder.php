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
                'step' => $field->step(),
                'is_default' => $field->isDefault(),
                'is_required_by_default' => $field->isRequiredByDefault(),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        DB::table('professional_form_fields')->upsert(
            $records,
            ['key'],
            [
                'step',
                'is_default',
                'is_required_by_default',
                'updated_at',
            ],
        );

        DB::table('professional_form_fields')
            ->whereNotIn('key', ProfessionalFormFieldEnum::values())
            ->delete();
    }
}
