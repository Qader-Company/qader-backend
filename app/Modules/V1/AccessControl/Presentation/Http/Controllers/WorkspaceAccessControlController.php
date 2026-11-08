<?php

namespace App\Modules\V1\AccessControl\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\AccessControl\Application\Services\WorkspaceRoleService;
use App\Modules\V1\AccessControl\Domain\Models\WorkspaceRole;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\StoreWorkspaceRoleRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\UpdateWorkspaceRoleRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Requests\UpdateWorkspaceRoleStatusRequest;
use App\Modules\V1\AccessControl\Presentation\Http\Resources\WorkspaceRoleResource;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use Illuminate\Http\Request;

class WorkspaceAccessControlController extends Controller
{
    public function permissions(Request $r, Workspace $workspace, WorkspaceRoleService $s)
    {
        return ApiResponse::success($s->permissions($r->user(), $workspace));
    }

    public function index(Request $r, Workspace $workspace, WorkspaceRoleService $s)
    {
        return ApiResponse::success(WorkspaceRoleResource::collection($s->roles($r->user(), $workspace)));
    }

    public function show(Request $r, Workspace $workspace, WorkspaceRole $role, WorkspaceRoleService $s)
    {
        return ApiResponse::success(new WorkspaceRoleResource($s->role($r->user(), $workspace, $role)));
    }

    public function store(StoreWorkspaceRoleRequest $r, Workspace $workspace, WorkspaceRoleService $s)
    {
        return ApiResponse::created(new WorkspaceRoleResource($s->createRole($r->user(), $workspace, $r->validated())));
    }

    public function update(UpdateWorkspaceRoleRequest $r, Workspace $workspace, WorkspaceRole $role, WorkspaceRoleService $s)
    {
        return ApiResponse::updated(new WorkspaceRoleResource($s->updateRole($r->user(), $workspace, $role, $r->validated())));
    }

    public function status(UpdateWorkspaceRoleStatusRequest $r, Workspace $workspace, WorkspaceRole $role, WorkspaceRoleService $s)
    {
        return ApiResponse::updated(new WorkspaceRoleResource($s->updateRole($r->user(), $workspace, $role, ['is_active' => $r->boolean('is_active')])));
    }

    public function destroy(Request $r, Workspace $workspace, WorkspaceRole $role, WorkspaceRoleService $s)
    {
        $s->deleteRole($r->user(), $workspace, $role);

        return ApiResponse::deleted();
    }
}
