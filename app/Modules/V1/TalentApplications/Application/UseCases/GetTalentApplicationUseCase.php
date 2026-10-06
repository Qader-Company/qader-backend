<?php

namespace App\Modules\V1\TalentApplications\Application\UseCases;

use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\Repositories\TalentApplicationRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetTalentApplicationUseCase
{
    public function __construct(
        private readonly TalentApplicationRepositoryInterface $applications,
    ) {}

    public function execute(int $talentId): TalentApplication
    {
        return $this->applications->findByTalentId($talentId)
            ?? throw new NotFoundHttpException(__('api.not_found'));
    }
}
