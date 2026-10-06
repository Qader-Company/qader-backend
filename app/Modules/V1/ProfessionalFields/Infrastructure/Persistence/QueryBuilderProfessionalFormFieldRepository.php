<?php

namespace App\Modules\V1\ProfessionalFields\Infrastructure\Persistence;

use App\Modules\V1\ProfessionalFields\Domain\Repositories\ProfessionalFormFieldRepositoryInterface;
use App\Modules\V1\ProfessionalFields\Domain\ValueObjects\ProfessionalFormFieldEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class QueryBuilderProfessionalFormFieldRepository implements ProfessionalFormFieldRepositoryInterface
{
    public function configuration(?int $professionalFieldId): array
    {
        $fields = $this->fields($professionalFieldId)
            ->filter(
                fn (object $field): bool => (bool) $field->is_default
                    || $field->selected_field_id !== null,
            );

        return collect(OnboardingStepEnum::cases())
            ->mapWithKeys(function (OnboardingStepEnum $step) use ($fields): array {
                $stepFields = $fields->where('step', $step->value);

                return [
                    $step->value => [
                        'visible_fields' => $stepFields
                            ->pluck('key')
                            ->values()
                            ->all(),
                        'required_fields' => $stepFields
                            ->filter(
                                fn (object $field): bool => $this
                                    ->isRequired($field),
                            )
                            ->pluck('key')
                            ->values()
                            ->all(),
                    ],
                ];
            })
            ->all();
    }

    public function listForAdmin(?int $professionalFieldId): array
    {
        $fields = $this->fields($professionalFieldId);

        return collect(OnboardingStepEnum::cases())
            ->mapWithKeys(function (OnboardingStepEnum $step) use ($fields): array {
                return [
                    $step->value => $fields
                        ->where('step', $step->value)
                        ->map(fn (object $field): array => [
                            'key' => $field->key,
                            'is_default' => (bool) $field->is_default,
                            'is_selected' => (bool) $field->is_default
                                || $field->selected_field_id !== null,
                            'is_required' => $this->isRequired($field),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->all();
    }

    public function sync(
        int $professionalFieldId,
        array $fields,
    ): void {
        $fieldIds = DB::table('professional_form_fields')
            ->whereIn('key', collect($fields)->pluck('key'))
            ->pluck('id', 'key');

        $records = collect($fields)
            ->map(function (array $field) use (
                $fieldIds,
                $professionalFieldId,
            ): array {
                $formField = ProfessionalFormFieldEnum::from($field['key']);

                return [
                    'professional_field_id' => $professionalFieldId,
                    'professional_form_field_id' => $fieldIds[$field['key']],
                    'is_required' => $formField->isAlwaysRequired()
                        || $field['is_required'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            });

        DB::table('professional_field_form_field')
            ->where('professional_field_id', $professionalFieldId)
            ->delete();

        if ($records->isNotEmpty()) {
            DB::table('professional_field_form_field')->insert(
                $records->all(),
            );
        }
    }

    private function fields(?int $professionalFieldId): Collection
    {
        return DB::table('professional_form_fields as field')
            ->leftJoin(
                'professional_field_form_field as selection',
                function (JoinClause $join) use ($professionalFieldId): void {
                    $join
                        ->on(
                            'selection.professional_form_field_id',
                            '=',
                            'field.id',
                        )
                        ->where(
                            'selection.professional_field_id',
                            $professionalFieldId ?? 0,
                        );
                },
            )
            ->select([
                'field.id',
                'field.key',
                'field.step',
                'field.is_default',
                'selection.professional_form_field_id as selected_field_id',
            ])
            ->selectRaw(
                'COALESCE(selection.is_required, field.is_required_by_default) as is_required',
            )
            ->orderBy('field.id')
            ->get();
    }

    private function isRequired(object $field): bool
    {
        $formField = ProfessionalFormFieldEnum::tryFrom($field->key);

        return $formField?->isAlwaysRequired()
            || (bool) $field->is_required;
    }
}
