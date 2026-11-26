<?php

namespace App\Modules\V1\Payments\Application\Services;

use App\Modules\V1\Payments\Domain\Models\Payment;
use App\Modules\V1\Payments\Domain\ValueObjects\PaidServiceEnum;
use App\Modules\V1\Payments\Domain\ValueObjects\PaymentStatusEnum;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;

class PaidServiceAccessService
{
    public function clientHasExpertOpinion(Workspace $workspace, Talent $talent): bool
    {
        return Payment::query()
            ->where('owner_client_id', $workspace->owner_client_id)
            ->where('talent_id', $talent->id)
            ->where('service', PaidServiceEnum::QADER_EXPERT_OPINION->value)
            ->where('status', PaymentStatusEnum::PAID->value)
            ->exists();
    }

    public function talentHasEvaluation(int $userId, TalentApplication $application): bool
    {
        $service = $this->evaluationService($application);

        return $service !== null && Payment::query()
            ->where('payer_user_id', $userId)
            ->where('talent_application_id', $application->id)
            ->where('service', $service->value)
            ->where('status', PaymentStatusEnum::PAID->value)
            ->exists();
    }

    public function evaluationService(TalentApplication $application): ?PaidServiceEnum
    {
        return match ($application->status) {
            TalentApplicationStatusEnum::ACCEPTED => PaidServiceEnum::PROFILE_EXPERT_REVIEW,
            TalentApplicationStatusEnum::REJECTED => PaidServiceEnum::DETAILED_REJECTION_FEEDBACK,
            default => null,
        };
    }
}
