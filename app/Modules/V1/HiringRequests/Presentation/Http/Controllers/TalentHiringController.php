<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\HiringRequests\Application\Services\HiringInterviewWorkflow;
use App\Modules\V1\HiringRequests\Application\Services\HiringOfferWorkflow;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringInterviewResponseEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferStatusEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringRequestItemStatusEnum;
use App\Modules\V1\HiringRequests\Presentation\Http\Requests\ListTalentHiringOffersRequest;
use App\Modules\V1\HiringRequests\Presentation\Http\Requests\SaveHiringOfferRequest;
use App\Modules\V1\HiringRequests\Presentation\Http\Requests\TalentInterviewReasonRequest;
use App\Modules\V1\HiringRequests\Presentation\Http\Resources\HiringInterviewResource;
use App\Modules\V1\HiringRequests\Presentation\Http\Resources\HiringRequestItemResource;
use App\Support\ApiMetadata\EnumOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Talent
 *
 * @subgroup Hiring Offers and Interviews
 */
class TalentHiringController extends Controller
{
    public function options(): JsonResponse
    {
        return ApiResponse::success([
            'item_statuses' => EnumOptions::make(HiringRequestItemStatusEnum::class, 'hiring_request_item_statuses'),
            'offer_statuses' => EnumOptions::make(HiringOfferStatusEnum::class, 'hiring_offer_statuses'),
        ]);
    }

    public function index(ListTalentHiringOffersRequest $request, HiringOfferWorkflow $workflow): JsonResponse
    {
        $paginator = $workflow->paginateForTalent($request->user(), $request->validated());

        return ApiResponse::success(['items' => HiringRequestItemResource::collection($paginator->getCollection()), 'meta' => [
            'current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total(),
        ]]);
    }

    public function show(Request $request, int $item, HiringOfferWorkflow $workflow): JsonResponse
    {
        return ApiResponse::success(new HiringRequestItemResource($workflow->showForTalent($request->user(), $item)));
    }

    public function accept(Request $request, int $item, HiringOfferWorkflow $workflow): JsonResponse
    {
        return ApiResponse::updated(new HiringRequestItemResource($workflow->talentAccept($request->user(), $item)), __('hiring.offer_accepted'));
    }

    public function reject(Request $request, int $item, HiringOfferWorkflow $workflow): JsonResponse
    {
        return ApiResponse::updated(new HiringRequestItemResource($workflow->talentReject($request->user(), $item)), __('hiring.offer_rejected'));
    }

    public function counter(SaveHiringOfferRequest $request, int $item, HiringOfferWorkflow $workflow): JsonResponse
    {
        return ApiResponse::updated(new HiringRequestItemResource($workflow->talentCounter($request->user(), $item, $request->validated())), __('hiring.offer_countered'));
    }

    public function interview(Request $request, int $interview, HiringInterviewWorkflow $workflow): JsonResponse
    {
        return ApiResponse::success(new HiringInterviewResource($workflow->showForTalent($request->user(), $interview)));
    }

    public function acceptInterviewSchedule(Request $request, int $interview, HiringInterviewWorkflow $workflow): JsonResponse
    {
        return $this->saveInterviewResponse($request, $interview, $workflow, HiringInterviewResponseEnum::ACCEPTED);
    }

    public function requestInterviewScheduleChange(TalentInterviewReasonRequest $request, int $interview, HiringInterviewWorkflow $workflow): JsonResponse
    {
        return $this->saveInterviewResponse($request, $interview, $workflow, HiringInterviewResponseEnum::CHANGE_REQUESTED);
    }

    public function apologizeForInterview(TalentInterviewReasonRequest $request, int $interview, HiringInterviewWorkflow $workflow): JsonResponse
    {
        return $this->saveInterviewResponse($request, $interview, $workflow, HiringInterviewResponseEnum::APOLOGIZED);
    }

    private function saveInterviewResponse(
        Request $request,
        int $interview,
        HiringInterviewWorkflow $workflow,
        HiringInterviewResponseEnum $response,
    ): JsonResponse {
        $data = $request->only('reason');

        return ApiResponse::updated(
            new HiringInterviewResource($workflow->respondAsTalent($request->user(), $interview, $response, $data['reason'] ?? null)),
            __('hiring.interview_response_saved'),
        );
    }
}
