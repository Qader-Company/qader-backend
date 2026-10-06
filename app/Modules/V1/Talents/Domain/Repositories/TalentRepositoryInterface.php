<?php

namespace App\Modules\V1\Talents\Domain\Repositories;

use App\Modules\V1\Talents\Domain\Models\Talent;

interface TalentRepositoryInterface
{
    public function findProfileByUserId(int $userId);

    public function update(Talent $talent, array $attributes): Talent;
}
