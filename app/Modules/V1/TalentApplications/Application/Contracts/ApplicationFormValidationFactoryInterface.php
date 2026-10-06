<?php

namespace App\Modules\V1\TalentApplications\Application\Contracts;

use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;

interface ApplicationFormValidationFactoryInterface
{
    public function rulesFor(
        OnboardingStepEnum $step,
        ?int $professionalFieldId,
        ?int $userId = null,
        string $prefix = '',
        bool $enforceRequiredFields = true,
        array $context = [],
    ): array;
}
