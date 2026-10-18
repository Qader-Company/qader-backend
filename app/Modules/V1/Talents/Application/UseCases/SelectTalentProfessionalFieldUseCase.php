<?php

namespace App\Modules\V1\Talents\Application\UseCases;

use App\Modules\V1\Talents\Application\Services\TalentProfileProgressService;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Talents\Domain\Repositories\TalentRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SelectTalentProfessionalFieldUseCase
{
    public function __construct(
        private readonly TalentRepositoryInterface $talents,
        private readonly TalentProfileProgressService $progress,
    ) {}

    public function execute(Talent $talent, int $professionalFieldId): array
    {
        return DB::transaction(function () use ($talent, $professionalFieldId): array {
            $fieldChanged = $talent->professional_field_id !== $professionalFieldId;
            $talent->update(['professional_field_id' => $professionalFieldId]);

            if ($fieldChanged) {
                $talent->update(['job_title_id' => null]);
                $talent->skills()->sync([]);
                $talent->tools()->sync([]);
            }

            $profile = $this->talents->findProfileByUserId($talent->user_id)
                ?? throw new \LogicException('Talent profile was not found after selecting its professional field.');

            return [
                'profile' => $profile,
                'state' => $this->progress->calculate($profile),
            ];
        });
    }
}
