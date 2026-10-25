<?php

namespace App\Modules\V1\Workspaces\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Workspaces\Application\Services\WorkspaceTeamService;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Domain\Models\WorkspaceMember;
use App\Modules\V1\Workspaces\Presentation\Http\Requests\StoreWorkspaceMemberRequest;
use App\Modules\V1\Workspaces\Presentation\Http\Requests\UpdateWorkspaceMemberRequest;
use App\Modules\V1\Workspaces\Presentation\Http\Resources\WorkspaceMemberResource;
use Illuminate\Http\Request;

class WorkspaceMemberController extends Controller
{
    public function index(Request $r, Workspace $workspace, WorkspaceTeamService $s)
    {
        return ApiResponse::success(WorkspaceMemberResource::collection($s->members($r->user(), $workspace)));
    }

    public function show(Request $r, Workspace $workspace, WorkspaceMember $member, WorkspaceTeamService $s)
    {
        return ApiResponse::success(new WorkspaceMemberResource($s->member($r->user(), $workspace, $member)));
    }

    public function store(StoreWorkspaceMemberRequest $r, Workspace $workspace, WorkspaceTeamService $s)
    {
        return ApiResponse::created(
            new WorkspaceMemberResource($s->createMember($r->user(), $workspace, $r->validated())),
            __('workspaces.member_created'),
        );
    }

    public function update(UpdateWorkspaceMemberRequest $r, Workspace $workspace, WorkspaceMember $member, WorkspaceTeamService $s)
    {
        return ApiResponse::updated(new WorkspaceMemberResource($s->updateMember($r->user(), $workspace, $member, $r->validated())));
    }

    public function destroy(Request $r, Workspace $workspace, WorkspaceMember $member, WorkspaceTeamService $s)
    {
        $s->deleteMember($r->user(), $workspace, $member);

        return ApiResponse::deleted();
    }
}
