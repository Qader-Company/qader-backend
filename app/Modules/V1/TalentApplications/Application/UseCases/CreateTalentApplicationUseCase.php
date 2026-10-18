<?php

namespace App\Modules\V1\TalentApplications\Application\UseCases;

use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\Repositories\TalentApplicationRepositoryInterface;
use App\Modules\V1\Talents\Application\Services\TalentProfileProgressService;
use App\Modules\V1\Talents\Domain\Models\Talent;

class CreateTalentApplicationUseCase
{
    public function __construct(
        private readonly TalentApplicationRepositoryInterface $applications,
        private readonly TalentProfileProgressService $progress,
    ) {}

    public function execute(Talent $talent): TalentApplication
    {
        $state = $this->progress->calculate($talent);
        $application = $this->applications->firstOrCreate(
            $talent->id,
            [
                'status' => 'submitted',
                'submitted_at' => now(),
                'submission_snapshot' => [
                    'talent_id' => $talent->id,
                    'user' => [
                        'first_name' => $talent->user->first_name,
                        'last_name' => $talent->user->last_name,
                        'email' => $talent->user->email,
                    ],
                    'professional_field_id' => $talent->professional_field_id,
                    'sections' => collect($state['steps'])
                        ->mapWithKeys(fn (array $step): array => [$step['key'] => $step['data']])
                        ->all(),
                ],
                'profile_completion_at_submission' => $state['completion_percentage'],
                'snapshot_version' => 1,
                'snapshot_captured_at' => now(),
            ],
        );

        if ($application->wasRecentlyCreated && ($cv = $talent->getFirstMedia('cv'))) {
            $cv->copy($application, 'submitted_cv', 'local');
        }

        return $application;
    }
}
