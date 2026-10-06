<?php

namespace App\Modules\V1\Talents\Application\UseCases;

use App\Modules\V1\TalentApplications\Application\UseCases\CreateTalentApplicationUseCase;
use App\Modules\V1\Talents\Application\Services\TalentPlatformAccessService;
use App\Modules\V1\Talents\Application\Services\TalentProfileProgressService;
use App\Modules\V1\Talents\Application\Services\TalentProfileWriter;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Talents\Domain\Repositories\TalentRepositoryInterface;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepEnum;
use App\Modules\V1\Talents\Domain\ValueObjects\OnboardingStepStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveTalentOnboardingStepUseCase
{
    public function __construct(
        private readonly TalentRepositoryInterface $talents,
        private readonly TalentProfileWriter $writer,
        private readonly TalentProfileProgressService $progress,
        private readonly CreateTalentApplicationUseCase $createApplication,
        private readonly TalentPlatformAccessService $platformAccess,
    ) {}

    public function execute(Talent $talent, OnboardingStepEnum $step, array $data): array
    {
        return DB::transaction(function () use ($talent, $step, $data) {
            $this->ensurePreviousStepsAreCompleted($talent, $step);

            $profile = $this->writer->save($talent, [$step->value => $data]);
            $state = $this->progress->calculate($profile);
            $profile = $this->persistProgress($profile, $state);
            $applicationCreated = false;

            $shouldCreateApplication = $step === OnboardingStepEnum::WORK_PREFERENCES
                && $state['required_steps_completed'];

            if ($shouldCreateApplication) {
                $application = $this->createApplication->execute($profile);
                $applicationCreated = $application->wasRecentlyCreated;
            }

            $this->platformAccess->sync(
                $profile,
                $state['required_steps_completed'],
            );

            return compact('profile', 'state', 'applicationCreated');
        });
    }

    private function ensurePreviousStepsAreCompleted(
        Talent $talent,
        OnboardingStepEnum $step,
    ): void {
        $state = $this->progress->calculate($talent);

        foreach ($step->previousSteps() as $previousStep) {
            $previousStepStatus = $state['steps'][$previousStep->value]['status'];

            if ($previousStepStatus === OnboardingStepStatusEnum::COMPLETED->value) {
                continue;
            }

            throw ValidationException::withMessages([
                'step' => [
                    trans(
                        'talents.onboarding.previous_step_required',
                        [
                            'step' => trans(
                                'talents.onboarding.steps.'.$previousStep->value,
                            ),
                        ],
                    ),
                ],
            ]);
        }
    }

    private function persistProgress(Talent $talent, array $state): Talent
    {
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

        return $this->talents->findProfileByUserId($talent->user_id)
            ?? throw new \LogicException(
                'Talent profile was not found after updating its progress.',
            );
    }
}
