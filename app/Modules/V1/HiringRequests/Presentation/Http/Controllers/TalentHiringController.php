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
            'item_statuses' => EnumOptions::make(HiringRequestItemStatusEnum::class, 'hiring_request_item_statuses', [
                HiringRequestItemStatusEnum::OFFER,
                HiringRequestItemStatusEnum::INTERVIEW,
                HiringRequestItemStatusEnum::ACCEPTED,
                HiringRequestItemStatusEnum::ON_HOLD,
                HiringRequestItemStatusEnum::REJECTED,
                HiringRequestItemStatusEnum::CANCELLED,
                HiringRequestItemStatusEnum::COMPLETED,
            ]),
            'offer_statuses' => EnumOptions::make(HiringOfferStatusEnum::class, 'hiring_offer_statuses', [
                HiringOfferStatusEnum::PENDING,
                HiringOfferStatusEnum::ACCEPTED,
                HiringOfferStatusEnum::REJECTED,
                HiringOfferStatusEnum::EXPIRED,
            ]),
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

    #[UrlParam('item', 'integer', 'Hiring request item ID, not the offer ID.', example: 7)]
    #[Response('{"success":true,"data":{"id":7,"reference":"RI-2026-ZHPPYLQH","status":"offer","status_label":"Offer","contract_type":"managed","interview_required":true,"company":{"id":1,"name":"Qader Technology","logo_url":null},"talent":{"id":22,"talent_code":"QDR-2847","job_title":{"id":10,"name":"Backend Developer"}},"offer":{"id":10,"proposed_by":"client","salary_amount":"25000.0000","currency":{"id":1,"code":"EGP","symbol":"E£","name":"Egyptian Pound"},"work_type":"full_time","work_mode":"hybrid","work_start_day":"sunday","work_end_day":"thursday","hybrid_work_days":["thursday"],"work_hours":"8","notice_period_days":30,"work_location":"Cairo, Egypt","status":"pending","status_label":"Pending","sent_at":"2026-11-18T08:24:42.000000Z","response_due_at":"2026-11-20T08:24:42.000000Z","is_overdue":false},"client_decision":null,"decision_due_at":null,"available_actions":["view","accept_offer","reject_offer","counter_offer"],"rejection_reason":null,"hold_reason":null,"cancellation_reason":null}}', 200, 'Pending client offer — talent can respond')]
    #[Response('{"success":true,"data":{"id":7,"reference":"RI-2026-ZHPPYLQH","status":"offer","status_label":"Offer","contract_type":"managed","interview_required":true,"company":{"id":1,"name":"Qader Technology","logo_url":null},"talent":{"id":22,"talent_code":"QDR-2847","job_title":{"id":10,"name":"Backend Developer"}},"offer":{"id":11,"proposed_by":"talent","salary_amount":"27000.0000","currency":{"id":1,"code":"EGP","symbol":"E£","name":"Egyptian Pound"},"work_type":"full_time","work_mode":"hybrid","work_start_day":"sunday","work_end_day":"thursday","hybrid_work_days":["thursday"],"work_hours":"8","notice_period_days":30,"work_location":"Cairo, Egypt","status":"pending","status_label":"Pending","sent_at":"2026-11-18T09:00:00.000000Z","response_due_at":null,"is_overdue":false},"client_decision":null,"decision_due_at":null,"available_actions":["view"],"rejection_reason":null,"hold_reason":null,"cancellation_reason":null}}', 200, 'Talent counter offer — waiting for admin')]
    #[Response('{"success":true,"data":{"id":7,"reference":"RI-2026-ZHPPYLQH","status":"offer","status_label":"Offer","contract_type":"managed","interview_required":true,"company":{"id":1,"name":"Qader Technology","logo_url":null},"talent":{"id":22,"talent_code":"QDR-2847","job_title":{"id":10,"name":"Backend Developer"}},"offer":{"id":10,"proposed_by":"client","salary_amount":"25000.0000","currency":{"id":1,"code":"EGP","symbol":"E£","name":"Egyptian Pound"},"work_type":"full_time","work_mode":"hybrid","work_start_day":"sunday","work_end_day":"thursday","hybrid_work_days":["thursday"],"work_hours":"8","notice_period_days":30,"work_location":"Cairo, Egypt","status":"expired","status_label":"Expired","sent_at":"2026-11-16T08:24:42.000000Z","response_due_at":"2026-11-18T08:24:42.000000Z","is_overdue":false},"client_decision":null,"decision_due_at":null,"available_actions":["view"],"rejection_reason":null,"hold_reason":null,"cancellation_reason":null}}', 200, 'Expired offer')]
    #[Response('{"success":true,"data":{"id":7,"reference":"RI-2026-ZHPPYLQH","status":"interview","status_label":"Interview","contract_type":"managed","interview_required":true,"company":{"id":1,"name":"Qader Technology","logo_url":null},"talent":{"id":22,"talent_code":"QDR-2847","job_title":{"id":10,"name":"Backend Developer"}},"offer":{"id":10,"proposed_by":"client","salary_amount":"25000.0000","currency":{"id":1,"code":"EGP","symbol":"E£","name":"Egyptian Pound"},"status":"accepted","status_label":"Accepted","is_overdue":false},"interview":{"id":5,"status":"awaiting_responses","status_label":"Awaiting Responses","start":"2026-11-22T10:00:00.000000Z","end":"2026-11-22T11:00:00.000000Z","duration_minutes":60,"talent_response":{"status":"pending","status_label":"Pending","reason":null,"responded_at":null},"meeting_url":null,"can_join":false,"available_actions":["accept_schedule","request_schedule_change","apologize"]},"client_decision":null,"decision_due_at":null,"available_actions":["view"],"rejection_reason":null,"hold_reason":null,"cancellation_reason":null}}', 200, 'Interview stage')]
    #[Response('{"success":true,"data":{"id":7,"reference":"RI-2026-ZHPPYLQH","status":"accepted","status_label":"Accepted","contract_type":"managed","interview_required":false,"company":{"id":1,"name":"Qader Technology","logo_url":null},"talent":{"id":22,"talent_code":"QDR-2847","job_title":{"id":10,"name":"Backend Developer"}},"offer":{"id":10,"proposed_by":"client","salary_amount":"25000.0000","currency":{"id":1,"code":"EGP","symbol":"E£","name":"Egyptian Pound"},"status":"accepted","status_label":"Accepted","is_overdue":false},"client_decision":"accepted","decision_due_at":null,"available_actions":["view"],"rejection_reason":null,"hold_reason":null,"cancellation_reason":null}}', 200, 'Accepted item')]
    #[Response('{"success":true,"data":{"id":7,"reference":"RI-2026-ZHPPYLQH","status":"on_hold","status_label":"On Hold","contract_type":"managed","interview_required":true,"company":{"id":1,"name":"Qader Technology","logo_url":null},"talent":{"id":22,"talent_code":"QDR-2847","job_title":{"id":10,"name":"Backend Developer"}},"offer":{"id":10,"proposed_by":"client","salary_amount":"25000.0000","currency":{"id":1,"code":"EGP","symbol":"E£","name":"Egyptian Pound"},"status":"accepted","status_label":"Accepted","is_overdue":false},"client_decision":null,"decision_due_at":null,"available_actions":["view"],"rejection_reason":null,"hold_reason":"Waiting for client confirmation.","cancellation_reason":null}}', 200, 'Item on hold')]
    #[Response('{"success":true,"data":{"id":7,"reference":"RI-2026-ZHPPYLQH","status":"rejected","status_label":"Rejected","contract_type":"managed","interview_required":true,"company":{"id":1,"name":"Qader Technology","logo_url":null},"talent":{"id":22,"talent_code":"QDR-2847","job_title":{"id":10,"name":"Backend Developer"}},"offer":{"id":10,"proposed_by":"client","salary_amount":"25000.0000","currency":{"id":1,"code":"EGP","symbol":"E£","name":"Egyptian Pound"},"status":"rejected","status_label":"Rejected","is_overdue":false},"client_decision":"rejected","client_decision_reason":"The client selected another candidate.","available_actions":["view"],"rejection_reason":"The client selected another candidate.","hold_reason":null,"cancellation_reason":null}}', 200, 'Rejected item')]
    #[Response('{"success":true,"data":{"id":7,"reference":"RI-2026-ZHPPYLQH","status":"cancelled","status_label":"Cancelled","contract_type":"managed","interview_required":true,"company":{"id":1,"name":"Qader Technology","logo_url":null},"talent":{"id":22,"talent_code":"QDR-2847","job_title":{"id":10,"name":"Backend Developer"}},"offer":{"id":10,"proposed_by":"client","salary_amount":"25000.0000","currency":{"id":1,"code":"EGP","symbol":"E£","name":"Egyptian Pound"},"status":"pending","status_label":"Pending","is_overdue":false},"client_decision":null,"decision_due_at":null,"available_actions":["view"],"rejection_reason":null,"hold_reason":null,"cancellation_reason":"The hiring request was cancelled."}}', 200, 'Cancelled item')]
    #[Response('{"success":true,"data":{"id":7,"reference":"RI-2026-ZHPPYLQH","status":"completed","status_label":"Completed","contract_type":"managed","interview_required":true,"company":{"id":1,"name":"Qader Technology","logo_url":null},"talent":{"id":22,"talent_code":"QDR-2847","job_title":{"id":10,"name":"Backend Developer"}},"offer":{"id":10,"proposed_by":"client","salary_amount":"25000.0000","currency":{"id":1,"code":"EGP","symbol":"E£","name":"Egyptian Pound"},"status":"accepted","status_label":"Accepted","is_overdue":false},"client_decision":"accepted","available_actions":["view"],"rejection_reason":null,"hold_reason":null,"cancellation_reason":null}}', 200, 'Completed item')]
    #[Response('{"success":false,"message":"No query results for model [HiringRequestItem]."}', 404, 'Item not found or does not belong to the authenticated talent')]
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

    #[UrlParam('interview', 'integer', 'Hiring interview ID.', example: 5)]
    #[Response('{"success":true,"data":{"id":5,"status":"awaiting_responses","status_label":"Awaiting Responses","start":"2026-11-22T10:00:00.000000Z","end":"2026-11-22T11:00:00.000000Z","duration_minutes":60,"client_response":{"status":"pending","status_label":"Pending","reason":null,"responded_at":null},"talent_response":{"status":"pending","status_label":"Pending","reason":null,"responded_at":null},"meeting_url":null,"can_join":false,"available_actions":["accept_schedule","request_schedule_change","apologize"],"proposed_at":"2026-11-19T08:00:00.000000Z","confirmed_at":null,"started_at":null,"completed_at":null}}', 200, 'Awaiting talent response')]
    #[Response('{"success":true,"data":{"id":5,"status":"awaiting_responses","status_label":"Awaiting Responses","start":"2026-11-22T10:00:00.000000Z","end":"2026-11-22T11:00:00.000000Z","duration_minutes":60,"client_response":{"status":"pending","status_label":"Pending","reason":null,"responded_at":null},"talent_response":{"status":"accepted","status_label":"Accepted","reason":null,"responded_at":"2026-11-19T09:00:00.000000Z"},"meeting_url":null,"can_join":false,"available_actions":[],"proposed_at":"2026-11-19T08:00:00.000000Z","confirmed_at":null,"started_at":null,"completed_at":null}}', 200, 'Talent accepted — waiting for client response')]
    #[Response('{"success":true,"data":{"id":5,"status":"awaiting_admin_confirmation","status_label":"Awaiting Admin Confirmation","start":"2026-11-22T10:00:00.000000Z","end":"2026-11-22T11:00:00.000000Z","duration_minutes":60,"client_response":{"status":"accepted","status_label":"Accepted","reason":null,"responded_at":"2026-11-19T09:05:00.000000Z"},"talent_response":{"status":"accepted","status_label":"Accepted","reason":null,"responded_at":"2026-11-19T09:00:00.000000Z"},"meeting_url":null,"can_join":false,"available_actions":[],"proposed_at":"2026-11-19T08:00:00.000000Z","confirmed_at":null,"started_at":null,"completed_at":null}}', 200, 'Awaiting admin confirmation')]
    #[Response('{"success":true,"data":{"id":5,"status":"cancelled","status_label":"Cancelled","start":"2026-11-22T10:00:00.000000Z","end":"2026-11-22T11:00:00.000000Z","duration_minutes":60,"client_response":{"status":"pending","status_label":"Pending","reason":null,"responded_at":null},"talent_response":{"status":"change_requested","status_label":"Change Requested","reason":"The proposed time is not suitable.","responded_at":"2026-11-19T09:00:00.000000Z"},"meeting_url":null,"can_join":false,"available_actions":[],"proposed_at":"2026-11-19T08:00:00.000000Z","confirmed_at":null,"started_at":null,"completed_at":null}}', 200, 'Cancelled pending a new single date')]
    #[Response('{"success":true,"data":{"id":5,"status":"confirmed","status_label":"Confirmed","start":"2026-11-22T10:00:00.000000Z","end":"2026-11-22T11:00:00.000000Z","duration_minutes":60,"client_response":{"status":"accepted","status_label":"Accepted","reason":null,"responded_at":"2026-11-19T09:05:00.000000Z"},"talent_response":{"status":"accepted","status_label":"Accepted","reason":null,"responded_at":"2026-11-19T09:00:00.000000Z"},"meeting_url":"https://meet.google.com/abc-defg-hij","can_join":false,"available_actions":["request_schedule_change","apologize"],"proposed_at":"2026-11-19T08:00:00.000000Z","confirmed_at":"2026-11-19T09:10:00.000000Z","started_at":null,"completed_at":null}}', 200, 'Confirmed interview before its start time')]
    #[Response('{"success":true,"data":{"id":5,"status":"in_progress","status_label":"In Progress","start":"2026-11-22T10:00:00.000000Z","end":"2026-11-22T11:00:00.000000Z","duration_minutes":60,"client_response":{"status":"accepted","status_label":"Accepted","reason":null,"responded_at":"2026-11-19T09:05:00.000000Z"},"talent_response":{"status":"accepted","status_label":"Accepted","reason":null,"responded_at":"2026-11-19T09:00:00.000000Z"},"meeting_url":"https://meet.google.com/abc-defg-hij","can_join":true,"available_actions":[],"proposed_at":"2026-11-19T08:00:00.000000Z","confirmed_at":"2026-11-19T09:10:00.000000Z","started_at":"2026-11-22T10:00:00.000000Z","completed_at":null}}', 200, 'Interview in progress')]
    #[Response('{"success":true,"data":{"id":5,"status":"completed","status_label":"Completed","start":"2026-11-22T10:00:00.000000Z","end":"2026-11-22T11:00:00.000000Z","duration_minutes":60,"client_response":{"status":"accepted","status_label":"Accepted","reason":null,"responded_at":"2026-11-19T09:05:00.000000Z"},"talent_response":{"status":"accepted","status_label":"Accepted","reason":null,"responded_at":"2026-11-19T09:00:00.000000Z"},"meeting_url":"https://meet.google.com/abc-defg-hij","can_join":false,"available_actions":[],"proposed_at":"2026-11-19T08:00:00.000000Z","confirmed_at":"2026-11-19T09:10:00.000000Z","started_at":"2026-11-22T10:00:00.000000Z","completed_at":"2026-11-22T11:00:00.000000Z"}}', 200, 'Completed interview')]
    #[Response('{"success":true,"data":{"id":5,"status":"cancelled","status_label":"Cancelled","start":"2026-11-22T10:00:00.000000Z","end":"2026-11-22T11:00:00.000000Z","duration_minutes":60,"client_response":{"status":"pending","status_label":"Pending","reason":null,"responded_at":null},"talent_response":{"status":"pending","status_label":"Pending","reason":null,"responded_at":null},"meeting_url":null,"can_join":false,"available_actions":[],"proposed_at":"2026-11-19T08:00:00.000000Z","confirmed_at":null,"started_at":null,"completed_at":null}}', 200, 'Cancelled interview')]
    #[Response('{"success":false,"message":"No query results for model [HiringInterview]."}', 404, 'Interview not found or does not belong to the authenticated talent')]
    public function interview(Request $request, int $interview, HiringInterviewWorkflow $workflow): JsonResponse
    {
        return ApiResponse::success(new HiringInterviewResource($workflow->showForTalent($request->user(), $interview)));
    }

    #[UrlParam('interview', 'integer', 'Hiring interview ID.', example: 5)]
    #[Response('{"success":true,"message":"Interview response saved successfully.","data":{"id":5,"status":"awaiting_responses","status_label":"Awaiting Responses","start":"2026-11-22T10:00:00.000000Z","end":"2026-11-22T11:00:00.000000Z","duration_minutes":60,"client_response":{"status":"pending","status_label":"Pending","reason":null,"responded_at":null},"talent_response":{"status":"accepted","status_label":"Accepted","reason":null,"responded_at":"2026-11-19T09:00:00.000000Z"},"meeting_url":null,"can_join":false,"available_actions":[],"proposed_at":"2026-11-19T08:00:00.000000Z","confirmed_at":null,"started_at":null,"completed_at":null}}', 200, 'Schedule accepted — waiting for client response')]
    #[Response('{"success":true,"message":"Interview response saved successfully.","data":{"id":5,"status":"awaiting_admin_confirmation","status_label":"Awaiting Admin Confirmation","start":"2026-11-22T10:00:00.000000Z","end":"2026-11-22T11:00:00.000000Z","duration_minutes":60,"client_response":{"status":"accepted","status_label":"Accepted","reason":null,"responded_at":"2026-11-19T08:55:00.000000Z"},"talent_response":{"status":"accepted","status_label":"Accepted","reason":null,"responded_at":"2026-11-19T09:00:00.000000Z"},"meeting_url":null,"can_join":false,"available_actions":[],"proposed_at":"2026-11-19T08:00:00.000000Z","confirmed_at":null,"started_at":null,"completed_at":null}}', 200, 'Schedule accepted — both parties accepted')]
    #[Response('{"success":false,"message":"This party has already responded or the interview is no longer awaiting responses."}', 409, 'Response is not allowed in the current state')]
    #[Response('{"success":false,"message":"No query results for model [HiringInterview]."}', 404, 'Interview not found or does not belong to the authenticated talent')]
    public function acceptInterviewSchedule(Request $request, int $interview, HiringInterviewWorkflow $workflow): JsonResponse
    {
        return $this->saveInterviewResponse($request, $interview, $workflow, HiringInterviewResponseEnum::ACCEPTED);
    }

    #[UrlParam('interview', 'integer', 'Hiring interview ID.', example: 5)]
    #[Response('{"success":true,"message":"Interview response saved successfully.","data":{"id":5,"status":"cancelled","status_label":"Cancelled","start":"2026-11-22T10:00:00.000000Z","end":"2026-11-22T11:00:00.000000Z","duration_minutes":60,"client_response":{"status":"pending","status_label":"Pending","reason":null,"responded_at":null},"talent_response":{"status":"change_requested","status_label":"Change Requested","reason":"The proposed time is not suitable. Please schedule another time.","responded_at":"2026-11-19T09:00:00.000000Z"},"meeting_url":null,"can_join":false,"available_actions":[],"proposed_at":"2026-11-19T08:00:00.000000Z","confirmed_at":null,"started_at":null,"completed_at":null}}', 200, 'Schedule change requested — current date cancelled')]
    #[Response('{"success":false,"message":"api.validation_error","errors":{"reason":["The reason field is required."]}}', 422, 'Reason is required')]
    #[Response('{"success":false,"message":"This party has already responded or the interview is no longer awaiting responses."}', 409, 'Response is not allowed in the current state')]
    #[Response('{"success":false,"message":"No query results for model [HiringInterview]."}', 404, 'Interview not found or does not belong to the authenticated talent')]
    public function requestInterviewScheduleChange(TalentInterviewReasonRequest $request, int $interview, HiringInterviewWorkflow $workflow): JsonResponse
    {
        return $this->saveInterviewResponse($request, $interview, $workflow, HiringInterviewResponseEnum::CHANGE_REQUESTED);
    }

    #[UrlParam('interview', 'integer', 'Hiring interview ID.', example: 5)]
    #[Response('{"success":true,"message":"Interview response saved successfully.","data":{"id":5,"status":"cancelled","status_label":"Cancelled","start":"2026-11-22T10:00:00.000000Z","end":"2026-11-22T11:00:00.000000Z","duration_minutes":60,"client_response":{"status":"pending","status_label":"Pending","reason":null,"responded_at":null},"talent_response":{"status":"apologized","status_label":"Apologized","reason":"I am unable to attend this interview.","responded_at":"2026-11-19T09:00:00.000000Z"},"meeting_url":null,"can_join":false,"available_actions":[],"proposed_at":"2026-11-19T08:00:00.000000Z","confirmed_at":null,"started_at":null,"completed_at":null}}', 200, 'Talent apologized — current date cancelled')]
    #[Response('{"success":false,"message":"api.validation_error","errors":{"reason":["The reason field is required."]}}', 422, 'Reason is required')]
    #[Response('{"success":false,"message":"This party has already responded or the interview is no longer awaiting responses."}', 409, 'Response is not allowed in the current state')]
    #[Response('{"success":false,"message":"No query results for model [HiringInterview]."}', 404, 'Interview not found or does not belong to the authenticated talent')]
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
