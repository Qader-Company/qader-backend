<?php

namespace App\Modules\V1\Trash\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\AccessControl\Application\Services\WorkspaceAccessService;
use App\Modules\V1\AccessControl\Domain\ValueObjects\ClientPermissionEnum;
use App\Modules\V1\Trash\Application\Services\TrashService;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientTrashController extends Controller
{
    public function workspaces(Request $request, TrashService $service): JsonResponse
    {
        $clientId = $request->user()->client()->value('id');

        return ApiResponse::success($service->paginate(
            'workspaces',
            $this->filters($request),
            fn (Builder $query) => $query->where('owner_client_id', $clientId),
            ['restore', 'remove_from_trash'],
        ));
    }

    public function deleteWorkspace(Request $request, Workspace $workspace, TrashService $service): JsonResponse
    {
        $clientId = $request->user()->client()->value('id');
        abort_unless((int) $workspace->owner_client_id === (int) $clientId, 404);
        $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        $service->softDelete('workspaces', (int) $workspace->getKey(), $request->user());

        return ApiResponse::deleted();
    }

    public function restoreWorkspace(Request $request, int $workspace, TrashService $service): JsonResponse
    {
        $clientId = $request->user()->client()->value('id');
        $service->restore('workspaces', $workspace, fn (Builder $query) => $query->where('owner_client_id', $clientId));

        return ApiResponse::updated(null, __('trash.restored'));
    }

    public function removeWorkspace(Request $request, int $workspace, TrashService $service): JsonResponse
    {
        $clientId = $request->user()->client()->value('id');
        $service->remove('workspaces', $workspace, $request->user(), fn (Builder $query) => $query->where('owner_client_id', $clientId));

        return ApiResponse::updated(null, __('trash.removed'));
    }

    public function team(Request $request, Workspace $workspace, WorkspaceAccessService $access, TrashService $service): JsonResponse
    {
        $actions = $this->workspaceActions($request, $workspace, $access);

        return ApiResponse::success($service->paginate('team', $this->filters($request), fn (Builder $query) => $query->where('workspace_id', $workspace->id), $actions));
    }

    public function restoreMember(Request $request, Workspace $workspace, int $member, WorkspaceAccessService $access, TrashService $service): JsonResponse
    {
        $access->authorize($request->user(), $workspace, ClientPermissionEnum::TRASH_RESTORE->value);
        $service->restore('team', $member, fn (Builder $query) => $query->where('workspace_id', $workspace->id));

        return ApiResponse::updated(null, __('trash.restored'));
    }

    public function removeMember(Request $request, Workspace $workspace, int $member, WorkspaceAccessService $access, TrashService $service): JsonResponse
    {
        $access->authorize($request->user(), $workspace, ClientPermissionEnum::TRASH_REMOVE->value);
        $service->remove('team', $member, $request->user(), fn (Builder $query) => $query->where('workspace_id', $workspace->id));

        return ApiResponse::updated(null, __('trash.removed'));
    }

    public function roles(Request $request, Workspace $workspace, WorkspaceAccessService $access, TrashService $service): JsonResponse
    {
        $actions = $this->workspaceActions($request, $workspace, $access);

        return ApiResponse::success($service->paginate('workspace-roles', $this->filters($request), fn (Builder $query) => $query->where('workspace_id', $workspace->id), $actions));
    }

    public function restoreRole(Request $request, Workspace $workspace, int $role, WorkspaceAccessService $access, TrashService $service): JsonResponse
    {
        $access->authorize($request->user(), $workspace, ClientPermissionEnum::TRASH_RESTORE->value);
        $service->restore('workspace-roles', $role, fn (Builder $query) => $query->where('workspace_id', $workspace->id));

        return ApiResponse::updated(null, __('trash.restored'));
    }

    public function removeRole(Request $request, Workspace $workspace, int $role, WorkspaceAccessService $access, TrashService $service): JsonResponse
    {
        $access->authorize($request->user(), $workspace, ClientPermissionEnum::TRASH_REMOVE->value);
        $service->remove('workspace-roles', $role, $request->user(), fn (Builder $query) => $query->where('workspace_id', $workspace->id));

        return ApiResponse::updated(null, __('trash.removed'));
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
    }

    private function workspaceActions(Request $request, Workspace $workspace, WorkspaceAccessService $access): array
    {
        $member = $access->authorize($request->user(), $workspace, ClientPermissionEnum::TRASH_VIEW->value);

        return array_values(array_filter([
            $access->allows($member, ClientPermissionEnum::TRASH_RESTORE->value) ? 'restore' : null,
            $access->allows($member, ClientPermissionEnum::TRASH_REMOVE->value) ? 'remove_from_trash' : null,
        ]));
    }
}
