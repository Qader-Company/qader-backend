<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\HiringRequests\Application\Services\ClientHiringRequestService;
use App\Modules\V1\HiringRequests\Presentation\Http\Requests\ListClientHiringRequestsRequest;
use App\Modules\V1\HiringRequests\Presentation\Http\Requests\SaveHiringRequestDraftRequest;
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
    public function options(Request $request, Workspace $workspace, ClientHiringRequestService $service): JsonResponse
    {
        return ApiResponse::success($service->options($request->user(), $workspace));
    }

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

    public function preview(SubmitHiringRequestRequest $request, Workspace $workspace, ClientHiringRequestService $service): JsonResponse
    {
        return ApiResponse::success($service->preview($request->user(), $workspace, $request->validated()));
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

    public function update(SaveHiringRequestDraftRequest $request, Workspace $workspace, int $hiringRequest, ClientHiringRequestService $service): JsonResponse
    {
        return ApiResponse::updated(
            new HiringRequestResource($service->updateDraft($request->user(), $workspace, $hiringRequest, $request->validated())),
            __('hiring.draft_updated'),
        );
    }

    public function submit(Request $request, Workspace $workspace, int $hiringRequest, ClientHiringRequestService $service): JsonResponse
    {
        return ApiResponse::updated(
            new HiringRequestResource($service->submit($request->user(), $workspace, $hiringRequest)),
            __('hiring.submitted'),
        );
    }
}
