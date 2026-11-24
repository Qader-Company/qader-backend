<?php

namespace App\Modules\V1\TalentApplications\Application\Services;

use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\Talents\Domain\Models\Talent;

class TalentEvaluationAccessService
{
    /**
     * Payment is intentionally not enforced yet. This service is the single place
     * where the paid-access check can be added when the payment flow is ready.
     *
     * @return array{is_available: bool, is_locked: bool, can_view: bool}
     */
    public function status(Talent $talent): array
    {
        $isAvailable = $this->evaluationApplication($talent) !== null;

        $isLocked = false;

        return [
            'is_available' => $isAvailable,
            'is_locked' => $isLocked,
            'can_view' => $isAvailable && ! $isLocked,
        ];
    }

    public function evaluationApplication(Talent $talent): ?TalentApplication
    {
        return TalentApplication::query()
            ->where('talent_id', $talent->id)
            ->where(function ($query): void {
                $query
                    ->whereHas('evaluation')
                    ->orWhereHas('interviews.evaluation');
            })
            ->latest('id')
            ->first();
    }
}
