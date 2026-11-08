<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\HiringRequests\Application\Services\ClientHiringRequestService;
use App\Modules\V1\HiringRequests\Presentation\Http\Requests\ListClientHiringRequestsRequest;
use App\Modules\V1\HiringRequests\Presentation\Http\Requests\SubmitHiringRequestRequest;
use App\Modules\V1\HiringRequests\Presentation\Http\Resources\HiringRequestResource;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Client
 *
 * @subgroup Hiring Requests
 */
class ClientHiringRequestController extends Controller
{
    public function index(ListClientHiringRequestsRequest $request, Workspace $workspace, ClientHiringRequestService $service): JsonResponse
    {
        $paginator = $service->paginate($request->user(), $workspace, $request->validated());

        return ApiResponse::success([
            'requests' => HiringRequestResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(SubmitHiringRequestRequest $request, Workspace $workspace, ClientHiringRequestService $service): JsonResponse
    {
        return ApiResponse::created(
            new HiringRequestResource($service->submitDirect($request->user(), $workspace, $request->validated())),
            __('hiring.submitted'),
        );
    }

    public function show(Request $request, Workspace $workspace, int $hiringRequest, ClientHiringRequestService $service): JsonResponse
    {
        return ApiResponse::success(new HiringRequestResource($service->show($request->user(), $workspace, $hiringRequest)));
    }

}
