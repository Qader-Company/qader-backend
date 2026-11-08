<?php

namespace App\Support\ApiDocumentation;

use App\Modules\V1\ProfessionalFields\Domain\ValueObjects\ProfessionalFormFieldEnum;
use App\Modules\V1\TalentApplications\Application\Contracts\ApplicationFormFieldPolicyProviderInterface;
use App\Modules\V1\TalentApplications\Application\Data\ApplicationFormFieldPolicy;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;

class DocumentationFormFieldPolicy implements ApplicationFormFieldPolicyProviderInterface
{
    public function all(?int $professionalFieldId): array
    {
        $policies = [];

        foreach (OnboardingStepEnum::cases() as $step) {
            $policies[$step->value] = $this->forStep($professionalFieldId, $step);
        }

        return $policies;
    }

    public function forStep(?int $professionalFieldId, OnboardingStepEnum $step): ApplicationFormFieldPolicy
    {
        $fields = array_filter(ProfessionalFormFieldEnum::cases(), fn ($field) => $field->step() === $step->value);

        // Document the full request shape. The live endpoint returns input keys only.
        return new ApplicationFormFieldPolicy(
            visibleFields: array_values(array_map(fn ($field) => $field->value, $fields)),
            requiredFields: array_values(array_map(fn ($field) => $field->value, array_filter($fields, fn ($field) => $field->isRequiredByDefault()))),
        );
    }
}
