<?php

namespace App\Modules\V1\TalentApplications\Application\UseCases;

use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\Repositories\TalentApplicationRepositoryInterface;
use App\Modules\V1\Talents\Domain\Models\Talent;

class CreateTalentApplicationUseCase
{
    public function __construct(
        private readonly TalentApplicationRepositoryInterface $applications,
    ) {}

    public function execute(Talent $talent): TalentApplication
    {
        return $this->applications->firstOrCreate(
            $talent->id,
            [
                'status' => 'submitted',
                'submitted_at' => now(),
            ],
        );
    }
}
