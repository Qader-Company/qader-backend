<?php

namespace App\Modules\V1\Talents\Application\UseCases;

use App\Modules\V1\Talents\Application\Services\TalentPlatformAccessService;
use App\Modules\V1\Talents\Application\Services\TalentProfileProgressService;
use App\Modules\V1\Talents\Application\Services\TalentProfileWriter;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Talents\Domain\Repositories\TalentRepositoryInterface;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use Illuminate\Support\Facades\DB;

class UpdateTalentProfileUseCase
{
    public function __construct(
        private readonly TalentProfileWriter $writer,
        private readonly TalentProfileProgressService $progress,
        private readonly TalentRepositoryInterface $talents,
        private readonly TalentPlatformAccessService $platformAccess,
    ) {}

    public function execute(Talent $talent, array $sections): array
    {
        return DB::transaction(function () use ($talent, $sections) {
            $profile = $this->writer->save($talent, $sections);
            $state = $this->progress->calculate($profile);
            $profile->update([
                'profile_completion_percentage' => $state['completion_percentage'],
                'profile_completion_details' => $state,
                'profile_completion_updated_at' => now(),
                'onboarding_step' => $state['current_step']
                    ?? OnboardingStepEnum::WORK_PREFERENCES->value,
                'onboarding_completed_at' => $state['required_steps_completed']
                    ? ($profile->onboarding_completed_at ?? now())
                    : null,
            ]);

            $updatedProfile = $this->talents
                ->findProfileByUserId($profile->user_id)
                ?? throw new \LogicException(
                    'Talent profile was not found after update.',
                );

            $this->platformAccess->sync(
                $updatedProfile,
                $state['required_steps_completed'],
            );

            return [
                'profile' => $updatedProfile,
                'state' => $state,
            ];
        });
    }
}
