<?php

namespace App\Modules\V1\ProfessionalFields\Infrastructure\Persistence;

use App\Modules\V1\ProfessionalFields\Domain\Repositories\ProfessionalFormFieldRepositoryInterface;
use App\Modules\V1\ProfessionalFields\Domain\ValueObjects\ProfessionalFormFieldEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use Illuminate\Support\Facades\DB;

class QueryBuilderProfessionalFormFieldRepository implements ProfessionalFormFieldRepositoryInterface
{
    public function configuration(?int $professionalFieldId): array
    {
        $selectedKeys = $this->selectedKeys($professionalFieldId);

        return collect(OnboardingStepEnum::cases())
            ->mapWithKeys(function (OnboardingStepEnum $step) use ($selectedKeys): array {
                $visibleFields = collect($selectedKeys)
                    ->filter(fn (string $key): bool => ProfessionalFormFieldEnum::tryFrom($key)?->step() === $step->value)
                    ->values()
                    ->all();

                return [$step->value => [
                    'visible_fields' => $visibleFields,
                    'required_fields' => collect($visibleFields)
                        ->filter(fn (string $key): bool => ProfessionalFormFieldEnum::from($key)->isRequiredByDefault())
                        ->values()
                        ->all(),
                ]];
            })
            ->all();
    }

    public function listForAdmin(?int $professionalFieldId): array
    {
        return [
            'available_fields' => DB::table('professional_form_fields')->orderBy('id')->pluck('key')->all(),
            'selected_fields' => $this->selectedKeys($professionalFieldId),
        ];
    }

    public function sync(int $professionalFieldId, array $fields): void
    {
        $fieldIds = DB::table('professional_form_fields')->whereIn('key', $fields)->pluck('id');

        DB::table('professional_field_form_field')->where('professional_field_id', $professionalFieldId)->delete();

        if ($fieldIds->isEmpty()) {
            return;
        }

        $now = now();
        DB::table('professional_field_form_field')->insert(
            $fieldIds->map(fn (int $fieldId): array => [
                'professional_field_id' => $professionalFieldId,
                'professional_form_field_id' => $fieldId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
        );
    }

    private function selectedKeys(?int $professionalFieldId): array
    {
        if ($professionalFieldId === null) {
            return [];
        }

        return DB::table('professional_field_form_field as selection')
            ->join('professional_form_fields as field', 'field.id', '=', 'selection.professional_form_field_id')
            ->where('selection.professional_field_id', $professionalFieldId)
            ->orderBy('field.id')
            ->pluck('field.key')
            ->all();
    }
}
