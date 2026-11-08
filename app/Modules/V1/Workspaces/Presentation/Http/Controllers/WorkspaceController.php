<?php

namespace App\Modules\V1\Workspaces\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Workspaces\Application\Services\WorkspaceService;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use App\Modules\V1\Workspaces\Presentation\Http\Requests\StoreWorkspaceRequest;
use App\Modules\V1\Workspaces\Presentation\Http\Requests\UpdateWorkspaceRequest;
use App\Modules\V1\Workspaces\Presentation\Http\Resources\WorkspaceResource;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function index(Request $r, WorkspaceService $s)
    {
        return ApiResponse::success(WorkspaceResource::collection($s->list($r->user())));
    }

    public function store(StoreWorkspaceRequest $r, WorkspaceService $s)
    {
        return ApiResponse::created(new WorkspaceResource($s->create($r->user(), $r->validated())));
    }

    public function show(Request $r, Workspace $workspace, WorkspaceService $s)
    {
        return ApiResponse::success(new WorkspaceResource($s->show($r->user(), $workspace)));
    }

    public function update(UpdateWorkspaceRequest $r, Workspace $workspace, WorkspaceService $s)
    {
        return ApiResponse::updated(new WorkspaceResource($s->update($r->user(), $workspace, $r->validated())));
    }

    public function dashboard(Request $r, Workspace $workspace, WorkspaceService $s)
    {
        return ApiResponse::success($s->dashboard($r->user(), $workspace));
    }
}
