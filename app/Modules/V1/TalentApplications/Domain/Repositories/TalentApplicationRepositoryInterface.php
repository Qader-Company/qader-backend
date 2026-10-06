<?php

namespace App\Modules\V1\TalentApplications\Domain\Repositories;

use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;

interface TalentApplicationRepositoryInterface
{
    public function findByTalentId(int $talentId): ?TalentApplication;

    public function existsForTalent(int $talentId): bool;

    public function firstOrCreate(int $talentId, array $attributes): TalentApplication;
}
