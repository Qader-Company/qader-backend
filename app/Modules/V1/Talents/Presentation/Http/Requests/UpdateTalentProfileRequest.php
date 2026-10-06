<?php

namespace App\Modules\V1\Talents\Presentation\Http\Requests;

use App\Modules\V1\TalentApplications\Application\Contracts\ApplicationFormValidationFactoryInterface;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class UpdateTalentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(
        ApplicationFormValidationFactoryInterface $validationFactory,
    ): array {
        $rules = [
            'personal-information' => ['sometimes', 'array'],
            'skills-and-experiences' => ['sometimes', 'array'],
            'work-preferences' => ['sometimes', 'array'],
        ];

        $professionalFieldId = $this->professionalFieldId();

        foreach (OnboardingStepEnum::cases() as $step) {
            $section = $step->value;

            $rules += $validationFactory->rulesFor(
                step: $step,
                professionalFieldId: $professionalFieldId,
                prefix: $section.'.',
                userId: $this->user()?->id,
                enforceRequiredFields: false,
                context: (array) $this->input($section, []),
            );
        }

        return $rules;
    }

    private function professionalFieldId(): ?int
    {
        if ($this->filled('personal-information.professional_field_id')) {
            return $this->integer(
                'personal-information.professional_field_id',
            );
        }

        $professionalFieldId = DB::table('talents')
            ->where('user_id', $this->user()?->id)
            ->value('professional_field_id');

        return $professionalFieldId === null
            ? null
            : (int) $professionalFieldId;
    }
}
