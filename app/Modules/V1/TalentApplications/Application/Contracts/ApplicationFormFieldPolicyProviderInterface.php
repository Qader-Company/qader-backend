<?php

namespace App\Modules\V1\TalentApplications\Application\Contracts;

use App\Modules\V1\TalentApplications\Application\Data\ApplicationFormFieldPolicy;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;

interface ApplicationFormFieldPolicyProviderInterface
{
    public function all(?int $professionalFieldId): array;

    public function forStep(
        ?int $professionalFieldId,
        OnboardingStepEnum $step,
    ): ApplicationFormFieldPolicy;
}
