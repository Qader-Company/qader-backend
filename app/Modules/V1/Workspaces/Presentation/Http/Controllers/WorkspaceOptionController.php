<?php

namespace App\Modules\V1\Workspaces\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceMemberStatusEnum;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceSizeBandEnum;
use App\Modules\V1\Workspaces\Domain\ValueObjects\WorkspaceSocialPlatformEnum;

class WorkspaceOptionController extends Controller
{
    public function __invoke()
    {
        return ApiResponse::success([
            'size_bands' => WorkspaceSizeBandEnum::values(),
            'social_platforms' => WorkspaceSocialPlatformEnum::values(),
            'member_statuses' => WorkspaceMemberStatusEnum::values(),
        ]);
    }
}
