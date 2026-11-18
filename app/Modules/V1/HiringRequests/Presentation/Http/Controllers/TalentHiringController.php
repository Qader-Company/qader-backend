<?php

namespace App\Modules\V1\HiringRequests\Presentation\Http\Controllers;

use App\Facades\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\V1\HiringRequests\Application\Services\HiringInterviewWorkflow;
use App\Modules\V1\HiringRequests\Application\Services\HiringOfferWorkflow;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringInterviewResponseEnum;
use App\Modules\V1\HiringRequests\Domain\ValueObjects\HiringOfferSortEnum;
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
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\UrlParam;

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
            'sort_options' => EnumOptions::make(HiringOfferSortEnum::class, 'hiring_offer_sort_options'),
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

    #[UrlParam('item', 'integer', 'Hiring request item ID, not the offer ID.', example: 7)]
    #[Response('{"success":true,"message":"Offer accepted successfully.","data":{"id":7,"reference":"RI-2026-ZHPPYLQH","status":"interview","status_label":"Interview","interview_required":true,"company":{"id":1,"name":"Qader Technology","logo_url":null},"offer":{"id":10,"proposed_by":"client","salary_amount":"25000.0000","currency":{"id":1,"code":"EGP","symbol":"E£","name":"Egyptian Pound"},"status":"accepted","status_label":"Accepted","sent_at":"2026-11-18T08:24:42.000000Z","response_due_at":"2026-11-20T08:24:42.000000Z","is_overdue":false},"available_actions":["view"],"rejection_reason":null,"hold_reason":null,"cancellation_reason":null}}', 200, 'Offer accepted')]
    #[Response('{"success":false,"message":"This offer can no longer be actioned."}', 409, 'Offer cannot be actioned')]
    public function accept(Request $request, int $item, HiringOfferWorkflow $workflow): JsonResponse
    {
        return ApiResponse::updated(new HiringRequestItemResource($workflow->talentAccept($request->user(), $item)), __('hiring.offer_accepted'));
    }

    #[UrlParam('item', 'integer', 'Hiring request item ID, not the offer ID.', example: 7)]
    #[Response('{"success":true,"message":"Offer rejected successfully.","data":{"id":7,"reference":"RI-2026-ZHPPYLQH","status":"rejected","status_label":"Rejected","interview_required":true,"company":{"id":1,"name":"Qader Technology","logo_url":null},"offer":{"id":10,"proposed_by":"client","salary_amount":"25000.0000","currency":{"id":1,"code":"EGP","symbol":"E£","name":"Egyptian Pound"},"status":"rejected","status_label":"Rejected","sent_at":"2026-11-18T08:24:42.000000Z","response_due_at":"2026-11-20T08:24:42.000000Z","is_overdue":false},"available_actions":["view"],"rejection_reason":null,"hold_reason":null,"cancellation_reason":null}}', 200, 'Offer rejected')]
    #[Response('{"success":false,"message":"This offer can no longer be actioned."}', 409, 'Offer cannot be actioned')]
    public function reject(Request $request, int $item, HiringOfferWorkflow $workflow): JsonResponse
    {
        return ApiResponse::updated(new HiringRequestItemResource($workflow->talentReject($request->user(), $item)), __('hiring.offer_rejected'));
    }

    #[UrlParam('item', 'integer', 'Hiring request item ID, not the offer ID.', example: 7)]
    #[Response('{"success":true,"message":"Counter offer sent successfully.","data":{"id":7,"reference":"RI-2026-ZHPPYLQH","status":"offer","status_label":"Offer","interview_required":true,"company":{"id":1,"name":"Qader Technology","logo_url":null},"offer":{"id":11,"proposed_by":"talent","salary_amount":"27000.0000","currency":{"id":1,"code":"EGP","symbol":"E£","name":"Egyptian Pound"},"work_type":"full_time","work_mode":"hybrid","work_start_day":"sunday","work_end_day":"thursday","hybrid_work_days":["thursday"],"work_hours":"8","notice_period_days":30,"work_location":"Cairo, Egypt","status":"pending","status_label":"Pending","sent_at":"2026-11-18T09:00:00.000000Z","response_due_at":null,"is_overdue":false},"available_actions":["view"],"rejection_reason":null,"hold_reason":null,"cancellation_reason":null}}', 200, 'Counter offer sent')]
    #[Response('{"success":false,"message":"This offer can no longer be actioned."}', 409, 'Offer cannot be actioned')]
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
