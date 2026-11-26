<?php

namespace App\Modules\V1\TalentApplications\Application\Services;

use App\Modules\V1\Payments\Application\Services\PaidServiceAccessService;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;

class TalentEvaluationAccessService
{
    public function __construct(private readonly PaidServiceAccessService $payments) {}

    /** @return array{is_available: bool, is_locked: bool, can_view: bool} */
    public function status(Talent $talent): array
    {
        $application = $this->evaluationApplication($talent);
        $hasReport = $application !== null;
        $isLocked = $hasReport && ! $this->payments->talentHasEvaluation($talent->user_id, $application);

        return [
            'is_available' => $hasReport,
            'is_locked' => $isLocked,
            'can_view' => $hasReport && ! $isLocked,
        ];
    }

    public function evaluationApplication(Talent $talent): ?TalentApplication
    {
        return TalentApplication::query()
            ->where('talent_id', $talent->id)
            ->whereIn('status', [TalentApplicationStatusEnum::ACCEPTED->value, TalentApplicationStatusEnum::REJECTED->value])
            ->where(function ($query): void {
                $query
                    ->whereHas('evaluation')
                    ->orWhereHas('interviews.evaluation');
            })
            ->latest('id')
            ->first();
    }

    public function hasReport(TalentApplication $application): bool
    {
        if (! in_array($application->status, [TalentApplicationStatusEnum::ACCEPTED, TalentApplicationStatusEnum::REJECTED], true)) {
            return false;
        }

        return $application->evaluation()->exists()
            || $application->interviews()->whereHas('evaluation')->exists();
    }
}
