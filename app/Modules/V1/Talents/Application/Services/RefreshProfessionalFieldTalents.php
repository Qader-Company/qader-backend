<?php

namespace App\Modules\V1\Talents\Application\Services;

use App\Modules\V1\TalentApplications\Application\Contracts\ApplicationFormFieldPolicyProviderInterface;
use App\Modules\V1\Talents\Domain\Repositories\TalentRepositoryInterface;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use Illuminate\Support\Facades\DB;

class RefreshProfessionalFieldTalents
{
    public function __construct(
        private readonly TalentRepositoryInterface $talents,
        private readonly ApplicationFormFieldPolicyProviderInterface $fieldPolicy,
        private readonly TalentProfileProgressService $progress,
        private readonly TalentPlatformAccessService $platformAccess,
    ) {}

    public function execute(int $professionalFieldId): void
    {
        $configuration = $this->fieldPolicy->all($professionalFieldId);

        DB::table('talents')
            ->where('professional_field_id', $professionalFieldId)
            ->select(['id', 'user_id'])
            ->orderBy('id')
            ->chunkById(100, function ($talents) use ($configuration): void {
                foreach ($talents as $talentRecord) {
                    $talent = $this->talents->findProfileByUserId(
                        (int) $talentRecord->user_id,
                    );

                    if ($talent === null) {
                        continue;
                    }

                    $state = $this->progress->calculate(
                        $talent,
                        $configuration,
                    );

                    $talent->update([
                        'profile_completion_percentage' => $state['completion_percentage'],
                        'profile_completion_details' => $state,
                        'profile_completion_updated_at' => now(),
                        'onboarding_step' => $state['current_step']
                            ?? OnboardingStepEnum::WORK_PREFERENCES->value,
                        'onboarding_completed_at' => $state['required_steps_completed']
                            ? ($talent->onboarding_completed_at ?? now())
                            : null,
                    ]);

                    $this->platformAccess->sync(
                        $talent,
                        $state['required_steps_completed'],
                    );
                }
            });
    }
}
