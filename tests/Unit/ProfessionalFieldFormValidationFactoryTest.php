<?php

namespace Tests\Unit;

use App\Modules\V1\ProfessionalFields\Application\Factories\ProfessionalFieldFormValidationFactory;
use App\Modules\V1\ProfessionalFields\Domain\ValueObjects\ProfessionalFormFieldEnum;
use App\Modules\V1\TalentApplications\Application\Contracts\ApplicationFormFieldPolicyProviderInterface;
use App\Modules\V1\TalentApplications\Application\Data\ApplicationFormFieldPolicy;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use PHPUnit\Framework\TestCase;

class ProfessionalFieldFormValidationFactoryTest extends TestCase
{
    public function test_experiences_remain_optional_when_years_of_experience_is_positive(): void
    {
        $policyProvider = new class implements ApplicationFormFieldPolicyProviderInterface
        {
            public function all(?int $professionalFieldId): array
            {
                return [];
            }

            public function forStep(
                ?int $professionalFieldId,
                OnboardingStepEnum $step,
            ): ApplicationFormFieldPolicy {
                return new ApplicationFormFieldPolicy(
                    visibleFields: [ProfessionalFormFieldEnum::EXPERIENCES->value],
                    requiredFields: [],
                );
            }
        };

        $rules = (new ProfessionalFieldFormValidationFactory($policyProvider))->rulesFor(
            step: OnboardingStepEnum::SKILLS_AND_EXPERIENCES,
            professionalFieldId: 4,
            context: ['years_of_experience' => 4],
        );

        $this->assertContains('sometimes', $rules['experiences']);
        $this->assertNotContains('required', $rules['experiences']);
    }
}
