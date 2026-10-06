<?php

namespace App\Modules\V1\Talents\Presentation\Http\Requests;

use App\Modules\V1\TalentApplications\Application\Contracts\ApplicationFormValidationFactoryInterface;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class SaveTalentOnboardingStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(
        ApplicationFormValidationFactoryInterface $validationFactory,
    ): array {
        $step = OnboardingStepEnum::tryFrom((string) $this->route('step'));

        if ($step === null) {
            return [];
        }

        return $validationFactory->rulesFor(
            step: $step,
            professionalFieldId: $this->professionalFieldId($step),
            userId: $this->user()?->id,
            context: $this->all(),
        );
    }

    private function professionalFieldId(OnboardingStepEnum $step): ?int
    {
        if (
            $step === OnboardingStepEnum::PERSONAL_INFORMATION
            && $this->filled('professional_field_id')
        ) {
            return $this->integer('professional_field_id');
        }

        $professionalFieldId = DB::table('talents')
            ->where('user_id', $this->user()?->id)
            ->value('professional_field_id');

        return $professionalFieldId === null
            ? null
            : (int) $professionalFieldId;
    }
}
