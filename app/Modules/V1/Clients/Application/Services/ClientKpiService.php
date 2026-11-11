<?php

namespace App\Modules\V1\Clients\Application\Services;

use App\Modules\V1\Clients\Domain\Models\Client;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequest;
use App\Modules\V1\HiringRequests\Domain\Models\HiringRequestItem;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestStatusEnum;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceMemberStatusEnum;
use Illuminate\Database\Eloquent\Builder;

class ClientKpiService
{
    /** @return array{total_workspaces: int, active_hiring_requests: int, hired_talents: int, managed_talents: int} */
    public function forClient(Client $client): array
    {
        $workspaceIds = $client->ownedWorkspaces()->pluck('id')->all();

        return [
            'total_workspaces' => count($workspaceIds),
            // Decision 2.4: active includes pending (submitted, not yet started) and active requests.
            'active_hiring_requests' => HiringRequest::query()
                ->whereIn('workspace_id', $workspaceIds)
                ->whereIn('status', [HiringRequestStatusEnum::PENDING->value, HiringRequestStatusEnum::ACTIVE->value])
                ->count(),
            'hired_talents' => $this->hiredTalents($workspaceIds),
            // Decision 2.3: distinct talents with any action taken on them (an item that moved past pending).
            'managed_talents' => $this->items($workspaceIds)
                ->where('status', '!=', HiringRequestItemStatusEnum::PENDING->value)
                ->distinct()
                ->count('talent_id'),
        ];
    }

    /** @return array{team_members: int, hiring_requests: int, hired_talents: int} */
    public function forWorkspace(Workspace $workspace): array
    {
        return [
            'team_members' => $workspace->members()->where('status', WorkspaceMemberStatusEnum::ACTIVE->value)->count(),
            'hiring_requests' => $workspace->hiringRequests()->count(),
            'hired_talents' => $this->hiredTalents([$workspace->id]),
        ];
    }

    /** S07: a talent is hired when its item is accepted inside a completed hiring request. */
    private function hiredTalents(array $workspaceIds): int
    {
        return $this->items($workspaceIds)
            ->where('status', HiringRequestItemStatusEnum::ACCEPTED->value)
            ->whereHas('hiringRequest', fn (Builder $request) => $request->where('status', HiringRequestStatusEnum::COMPLETED->value))
            ->distinct()
            ->count('talent_id');
    }

    private function items(array $workspaceIds): Builder
    {
        return HiringRequestItem::query()
            ->whereHas('hiringRequest', fn (Builder $request) => $request->whereIn('workspace_id', $workspaceIds));
    }
}
