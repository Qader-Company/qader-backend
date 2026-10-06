<?php

namespace App\Modules\V1\TalentApplications\Infrastructure\Persistence\Repositories;

use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\Repositories\TalentApplicationRepositoryInterface;

class EloquentTalentApplicationRepository implements TalentApplicationRepositoryInterface
{
    public function findByTalentId(int $talentId): ?TalentApplication
    {
        return TalentApplication::query()
            ->where('talent_id', $talentId)
            ->first();
    }

    public function existsForTalent(int $talentId): bool
    {
        return TalentApplication::query()
            ->where('talent_id', $talentId)
            ->exists();
    }

    public function firstOrCreate(int $talentId, array $attributes): TalentApplication
    {
        return TalentApplication::query()->firstOrCreate(
            ['talent_id' => $talentId],
            $attributes,
        );
    }
}
