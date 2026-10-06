<?php

namespace App\Modules\V1\Talents\Application\UseCases;

use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Talents\Domain\Repositories\TalentRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class GetTalentProfileUseCase
{
    public function __construct(private readonly TalentRepositoryInterface $talents) {}

    public function execute(int $userId): Talent
    {
        return $this->talents->findProfileByUserId($userId)
            ?? throw new NotFoundHttpException(__('api.not_found'));
    }
}
