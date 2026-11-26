<?php

namespace App\Modules\V1\TalentMarketplace\Application\Services;

use App\Modules\V1\Payments\Application\Services\PaidServiceAccessService;
use App\Modules\V1\TalentApplications\Domain\Models\TalentApplication;
use App\Modules\V1\TalentApplications\Domain\ValueObjects\TalentApplicationStatusEnum;
use App\Modules\V1\Talents\Domain\Models\Talent;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;

class TalentExpertOpinionAccessService
{
    public function __construct(private readonly PaidServiceAccessService $payments) {}

    public function isAvailable(Workspace $workspace, Talent $talent): bool
    {
        $hasReport = $this->reportApplication($talent) !== null;

        return $hasReport && ! $this->isLocked($workspace, $talent);
    }

    public function isLocked(Workspace $workspace, Talent $talent): bool
    {
        return ! $this->payments->clientHasExpertOpinion($workspace, $talent);
    }

    public function reportApplication(Talent $talent): ?TalentApplication
    {
        return TalentApplication::query()
            ->where('talent_id', $talent->id)
            ->where('status', TalentApplicationStatusEnum::ACCEPTED->value)
            ->where(function ($query): void {
                $query
                    ->whereHas('evaluation')
                    ->orWhereHas('interviews.evaluation');
            })
            ->latest('id')
            ->first();
    }
}
