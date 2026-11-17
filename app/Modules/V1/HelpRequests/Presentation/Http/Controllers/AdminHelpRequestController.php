<?php

namespace App\Modules\V1\HelpRequests\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\HelpRequests\Application\Services\AdminHelpRequestService;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestClosureOutcomeEnum;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestContactMethodEnum;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestListScopeEnum;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestStatusEnum;
use App\Modules\V1\HelpRequests\Domain\ValueObjects\HelpRequestTypeEnum;
use App\Modules\V1\HelpRequests\Presentation\Http\Requests\AssignHelpRequestsRequest;
use App\Modules\V1\HelpRequests\Presentation\Http\Requests\CloseHelpRequestRequest;
use App\Modules\V1\HelpRequests\Presentation\Http\Requests\ListAdminHelpRequestsRequest;
use App\Modules\V1\HelpRequests\Presentation\Http\Requests\ReassignHelpRequestRequest;
use App\Modules\V1\HelpRequests\Presentation\Http\Requests\StoreHelpRequestNoteRequest;
use App\Modules\V1\HelpRequests\Presentation\Http\Resources\HelpRequestResource;
use App\Support\ApiMetadata\EnumLabel;
use App\Support\ApiMetadata\EnumOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Admin
 *
 * @subgroup Help Requests
 */
class AdminHelpRequestController extends Controller
{
    public function options(): JsonResponse
    {
        return ApiResponse::success([
            'scopes' => EnumOptions::make(HelpRequestListScopeEnum::class, 'help_request_scopes'),
            'statuses' => EnumOptions::make(HelpRequestStatusEnum::class, 'help_request_statuses'),
            'help_types' => EnumOptions::make(HelpRequestTypeEnum::class, 'help_request_types'),
            'contact_methods' => EnumOptions::make(HelpRequestContactMethodEnum::class, 'help_request_contact_methods'),
            'closure_outcomes' => EnumOptions::make(HelpRequestClosureOutcomeEnum::class, 'help_request_closure_outcomes'),
        ]);
    }

    public function index(ListAdminHelpRequestsRequest $request, AdminHelpRequestService $service): JsonResponse
    {
        $paginator = $service->paginate($request->validated(), $request->user());

        return ApiResponse::success([
            'requests' => HelpRequestResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function assignees(AdminHelpRequestService $service): JsonResponse
    {
        return ApiResponse::success($service->assignees());
    }

    public function assign(AssignHelpRequestsRequest $request, AdminHelpRequestService $service): JsonResponse
    {
        $requests = $service->assign(
            array_map('intval', $request->validated('help_request_ids')),
            $request->integer('admin_id'),
            $request->user(),
        );

        return ApiResponse::updated(HelpRequestResource::collection($requests), __('help_requests.assigned'));
    }

    public function show(Request $request, int $helpRequest, AdminHelpRequestService $service): JsonResponse
    {
        return ApiResponse::success(new HelpRequestResource($service->show($helpRequest, $request->user())));
    }

    public function reassign(ReassignHelpRequestRequest $request, int $helpRequest, AdminHelpRequestService $service): JsonResponse
    {
        return ApiResponse::updated(
            new HelpRequestResource($service->reassign($helpRequest, $request->integer('admin_id'), $request->user())),
            __('help_requests.reassigned'),
        );
    }

    public function start(Request $request, int $helpRequest, AdminHelpRequestService $service): JsonResponse
    {
        return ApiResponse::updated(
            new HelpRequestResource($service->start($helpRequest, $request->user())),
            __('help_requests.started'),
        );
    }

    public function storeNote(StoreHelpRequestNoteRequest $request, int $helpRequest, AdminHelpRequestService $service): JsonResponse
    {
        return ApiResponse::created(
            new HelpRequestResource($service->addNote($helpRequest, $request->validated('body'), $request->user())),
            __('help_requests.note_added'),
        );
    }

    public function hiringRequests(int $helpRequest, AdminHelpRequestService $service): JsonResponse
    {
        return ApiResponse::success($service->closableHiringRequests($helpRequest)->map(fn ($hiringRequest): array => [
            'id' => $hiringRequest->id,
            'reference' => $hiringRequest->reference,
            'status' => $hiringRequest->status?->value,
            'status_label' => EnumLabel::for($hiringRequest->status, 'hiring_request_statuses'),
            'created_at' => $hiringRequest->created_at,
        ])->values());
    }

    public function close(CloseHelpRequestRequest $request, int $helpRequest, AdminHelpRequestService $service): JsonResponse
    {
        return ApiResponse::updated(
            new HelpRequestResource($service->close($helpRequest, $request->validated(), $request->user())),
            __('help_requests.closed'),
        );
    }
}
