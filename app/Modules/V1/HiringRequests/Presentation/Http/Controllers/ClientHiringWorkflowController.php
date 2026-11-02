<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\AccessControl\Application\Services\WorkspaceAccessService;
use App\Modules\V1\AccessControl\Domain\ValueObjects\ClientPermissionEnum;
use App\Modules\V1\HiringRequests\Application\Services\HiringDecisionWorkflow;
use App\Modules\V1\HiringRequests\Application\Services\HiringInterviewWorkflow;
use App\Modules\V1\HiringRequests\Application\Services\HiringItemControlWorkflow;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringDecisionEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringInterviewResponseEnum;
use App\Modules\V1\HiringRequests\Presentation\Http\Requests\ClientInterviewReasonRequest;
use App\Modules\V1\HiringRequests\Presentation\Http\Resources\HiringInterviewResource;
use App\Modules\V1\HiringRequests\Presentation\Http\Resources\HiringRequestItemResource;
use App\Modules\V1\Workspaces\Domain\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * @group Client
 *
 * @subgroup Hiring Requests
 */
class ClientHiringWorkflowController extends Controller
{
    private function authorize(Request $request, Workspace $workspace, WorkspaceAccessService $access, bool $manage = false): void
    {
        $access->authorize($request->user(), $workspace, ($manage ? ClientPermissionEnum::HIRING_REQUESTS_MANAGE : ClientPermissionEnum::HIRING_REQUESTS_VIEW)->value);
    }

    public function interview(Request $request, Workspace $workspace, int $interview, WorkspaceAccessService $access, HiringInterviewWorkflow $workflow): JsonResponse
    {
        $this->authorize($request, $workspace, $access);

        return ApiResponse::success(new HiringInterviewResource($workflow->showForClient($request->user(), $workspace, $interview)));
    }

    public function acceptInterviewSchedule(Request $request, Workspace $workspace, int $interview, WorkspaceAccessService $access, HiringInterviewWorkflow $workflow): JsonResponse
    {
        return $this->saveInterviewResponse($request, $workspace, $interview, $access, $workflow, HiringInterviewResponseEnum::ACCEPTED);
    }

    public function requestInterviewScheduleChange(ClientInterviewReasonRequest $request, Workspace $workspace, int $interview, WorkspaceAccessService $access, HiringInterviewWorkflow $workflow): JsonResponse
    {
        return $this->saveInterviewResponse($request, $workspace, $interview, $access, $workflow, HiringInterviewResponseEnum::CHANGE_REQUESTED);
    }

    public function apologizeForInterview(ClientInterviewReasonRequest $request, Workspace $workspace, int $interview, WorkspaceAccessService $access, HiringInterviewWorkflow $workflow): JsonResponse
    {
        return $this->saveInterviewResponse($request, $workspace, $interview, $access, $workflow, HiringInterviewResponseEnum::APOLOGIZED);
    }

    public function decision(Request $request, Workspace $workspace, int $item, WorkspaceAccessService $access, HiringDecisionWorkflow $workflow): JsonResponse
    {
        $this->authorize($request, $workspace, $access, true);
        $data = $request->validate(['decision' => ['required', Rule::enum(HiringDecisionEnum::class)], 'reason' => ['nullable', 'string', 'max:2000']]);

        return ApiResponse::updated(new HiringRequestItemResource($workflow->submitAsClient($request->user(), $workspace, $item, HiringDecisionEnum::from($data['decision']), $data['reason'] ?? null)), __('hiring.decision_saved'));
    }

    public function withdrawDecision(Request $request, Workspace $workspace, int $item, WorkspaceAccessService $access, HiringDecisionWorkflow $workflow): JsonResponse
    {
        $this->authorize($request, $workspace, $access, true);

        return ApiResponse::updated(new HiringRequestItemResource($workflow->withdrawAsClient($request->user(), $workspace, $item)), __('hiring.decision_withdrawn'));
    }

    public function hold(Request $request, Workspace $workspace, int $item, WorkspaceAccessService $access, HiringItemControlWorkflow $workflow): JsonResponse
    {
        $this->authorize($request, $workspace, $access, true);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);

        return ApiResponse::updated(new HiringRequestItemResource($workflow->holdForClient($request->user(), $workspace, $item, $data['reason'] ?? null)), __('hiring.item_held'));
    }

    public function resume(Request $request, Workspace $workspace, int $item, WorkspaceAccessService $access, HiringItemControlWorkflow $workflow): JsonResponse
    {
        $this->authorize($request, $workspace, $access, true);

        return ApiResponse::updated(new HiringRequestItemResource($workflow->resumeForClient($request->user(), $workspace, $item)), __('hiring.item_resumed'));
    }

    public function cancel(Request $request, Workspace $workspace, int $item, WorkspaceAccessService $access, HiringItemControlWorkflow $workflow): JsonResponse
    {
        $this->authorize($request, $workspace, $access, true);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);

        return ApiResponse::updated(new HiringRequestItemResource($workflow->cancelForClient($request->user(), $workspace, $item, $data['reason'] ?? null)), __('hiring.item_cancelled'));
    }

    private function saveInterviewResponse(
        Request $request,
        Workspace $workspace,
        int $interview,
        WorkspaceAccessService $access,
        HiringInterviewWorkflow $workflow,
        HiringInterviewResponseEnum $response,
    ): JsonResponse {
        $this->authorize($request, $workspace, $access, true);
        $data = $request->only('reason');

        return ApiResponse::updated(
            new HiringInterviewResource($workflow->respondAsClient($request->user(), $workspace, $interview, $response, $data['reason'] ?? null)),
            __('hiring.interview_response_saved'),
        );
    }
}
