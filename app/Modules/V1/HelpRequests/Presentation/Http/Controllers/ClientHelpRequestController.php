<?php

namespace App\Modules\V1\HelpRequests\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\HelpRequests\Application\Services\ClientHelpRequestService;
use App\Modules\V1\HelpRequests\Presentation\Http\Requests\SubmitHelpRequestRequest;
use App\Modules\V1\HelpRequests\Presentation\Http\Resources\HelpRequestResource;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Client
 *
 * @subgroup Help Requests
 */
class ClientHelpRequestController extends Controller
{
    public function options(Request $request, Workspace $workspace, ClientHelpRequestService $service): JsonResponse
    {
        return ApiResponse::success($service->options($request->user(), $workspace));
    }

    public function store(SubmitHelpRequestRequest $request, Workspace $workspace, ClientHelpRequestService $service): JsonResponse
    {
        return ApiResponse::created(
            new HelpRequestResource($service->submit($request->user(), $workspace, $request->validated())),
            __('help_requests.submitted'),
        );
    }
}
