<?php

namespace App\Modules\V1\ProfessionalFields\Application\Services;

use App\Modules\V1\ProfessionalFields\Domain\Repositories\ProfessionalFormFieldRepositoryInterface;
use App\Modules\V1\TalentApplications\Application\Contracts\ApplicationFormFieldPolicyProviderInterface;
use App\Modules\V1\TalentApplications\Application\Data\ApplicationFormFieldPolicy;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;

class ProfessionalFieldFormPolicyProvider implements ApplicationFormFieldPolicyProviderInterface
{
    public function __construct(
        private readonly ProfessionalFormFieldRepositoryInterface $formFields,
    ) {}

    public function all(?int $professionalFieldId): array
    {
        return collect($this->formFields->configuration($professionalFieldId))
            ->map(
                fn (array $configuration): ApplicationFormFieldPolicy => new ApplicationFormFieldPolicy(
                    visibleFields: $configuration['visible_fields'],
                    requiredFields: $configuration['required_fields'],
                ),
            )
            ->all();
    }

    public function forStep(
        ?int $professionalFieldId,
        OnboardingStepEnum $step,
    ): ApplicationFormFieldPolicy {
        return $this->all($professionalFieldId)[$step->value]
            ?? new ApplicationFormFieldPolicy([], []);
    }
}
