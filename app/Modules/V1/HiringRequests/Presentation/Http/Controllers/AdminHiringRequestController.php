<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\HiringRequests\Application\Services\AdminHiringRequestActor;
use App\Modules\V1\HiringRequests\Application\Services\AdminHiringRequestService;
use App\Modules\V1\HiringRequests\Presentation\Http\Requests\AssignHiringRequestRequest;
use App\Modules\V1\HiringRequests\Presentation\Http\Requests\ListAdminHiringRequestsRequest;
use App\Modules\V1\HiringRequests\Presentation\Http\Resources\HiringRequestResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin
 *
 * @subgroup Hiring Requests
 */
class AdminHiringRequestController extends Controller
{
    public function index(ListAdminHiringRequestsRequest $request, AdminHiringRequestActor $actors, AdminHiringRequestService $service): JsonResponse
    {
        $paginator = $service->paginate($request->validated(), $actors->resolve($request->user()));

        return ApiResponse::success([
            'requests' => HiringRequestResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'summary' => $service->summary(),
            ],
        ]);
    }

    public function assignees(AdminHiringRequestService $service): JsonResponse
    {
        return ApiResponse::success($service->assignees());
    }

    public function show(Request $request, int $hiringRequest, AdminHiringRequestService $service): JsonResponse
    {
        return ApiResponse::success(new HiringRequestResource($service->show($hiringRequest, $request->user())));
    }

    public function assign(AssignHiringRequestRequest $request, int $hiringRequest, AdminHiringRequestService $service): JsonResponse
    {
        return ApiResponse::updated(
            new HiringRequestResource($service->assign($hiringRequest, $request->integer('admin_id'), $request->user())),
            __('hiring.assigned'),
        );
    }

    public function reassign(AssignHiringRequestRequest $request, int $hiringRequest, AdminHiringRequestService $service): JsonResponse
    {
        return ApiResponse::updated(
            new HiringRequestResource($service->reassign($hiringRequest, $request->integer('admin_id'), $request->user())),
            __('hiring.reassigned'),
        );
    }

    public function start(Request $request, int $hiringRequest, AdminHiringRequestActor $actors, AdminHiringRequestService $service): JsonResponse
    {
        return ApiResponse::updated(
            new HiringRequestResource($service->start($hiringRequest, $request->user(), $actors->resolve($request->user()))),
            __('hiring.started'),
        );
    }
}
